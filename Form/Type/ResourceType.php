<?php

namespace Sidus\FileUploadBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Easy file-upload integration in forms
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class ResourceType extends AbstractType
{
    /** @var ResourceManagerInterface */
    protected $resourceManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ResourceManagerInterface $resourceManager
     * @param ManagerRegistry          $doctrine
     */
    public function __construct(ResourceManagerInterface $resourceManager, ManagerRegistry $doctrine)
    {
        $this->resourceManager = $resourceManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var ResourceTypeConfiguration $resourceType */
        $resourceType = $options['resource_type'];
        $view->vars['endpoint'] = $resourceType->getEndpoint();
        $view->vars['data'] = $form->getData(); // "data" is the entity and "value" the ID
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // The view must always be an ID
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    if (!$originalData instanceof ResourceInterface) {
                        return $originalData; // Why is this condition necessary when submitting ? Makes no sense...
                    }

                    return $this->getIdentifierValue($originalData);
                },
                function ($submittedData) {
                    return $submittedData;
                }
            )
        );

        // Model data must always be an entity
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    return $originalData;
                },
                function ($submittedData) use ($options) {
                    /** @var ObjectRepository $repository */
                    $repository = $options['repository'];
                    if (null === $submittedData || '' === $submittedData) {
                        return null;
                    }

                    return $repository->find($submittedData);
                }
            )
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sidus_resource';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \UnexpectedValueException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'resource_type',
            ]
        );
        $resolver->setDefaults(
            [
                'class' => null,
                'repository' => null,
                'compound' => false,
                'error_bubbling' => false,
            ]
        );
        $resolver->setNormalizer(
            'resource_type',
            function (Options $options, $value) {
                return $this->resourceManager->getResourceTypeConfiguration($value);
            }
        );
        $resolver->setNormalizer(
            'class',
            function (Options $options, $value) {
                $resourceType = $options['resource_type'];
                if (!$value && $resourceType instanceof ResourceTypeConfiguration) {
                    return $resourceType->getEntity();
                }

                return $value;
            }
        );
        $resolver->setNormalizer(
            'repository',
            function (Options $options, $value) {
                if ($value) {
                    if ($value instanceof ObjectRepository) {
                        return $value;
                    }
                    throw new \UnexpectedValueException("The 'repository' option must be an EntityRepository");
                }
                $class = $options['class'];
                if (!$class) {
                    throw new \UnexpectedValueException("Missing option 'class' or 'repository'");
                }

                $entityManager = $this->doctrine->getManagerForClass($class);
                if (!$entityManager instanceof EntityManagerInterface) {
                    throw new \UnexpectedValueException("No manager found for class {$class}");
                }

                return $entityManager->getRepository($class);
            }
        );
    }

    /**
     * @param ResourceInterface $originalData
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     *
     * @return array
     */
    protected function getIdentifierValue(ResourceInterface $originalData)
    {
        $class = ClassUtils::getClass($originalData);
        $entityManager = $this->doctrine->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \InvalidArgumentException("No manager found for class {$class}");
        }
        $metadata = $entityManager->getClassMetadata($class);
        $identifier = $metadata->getIdentifierValues($originalData);
        if (1 !== \count($identifier)) {
            throw new \LogicException('ResourceInterface must have a single identifier (primary key)');
        }

        return array_pop($identifier);
    }
}
