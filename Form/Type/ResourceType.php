<?php

namespace Sidus\FileUploadBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Manager\ResourceManager;
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
    /** @var ResourceManager */
    protected $resourceManager;

    /** @var Registry */
    protected $doctrine;

    /**
     * @param ResourceManager $resourceManager
     * @param Registry        $doctrine
     */
    public function __construct(ResourceManager $resourceManager, Registry $doctrine)
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

                return $this->doctrine->getRepository($class);
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
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $metadata = $em->getClassMetadata(get_class($originalData));
        $identifier = $metadata->getIdentifierValues($originalData);
        if (count($identifier) !== 1) {
            throw new \LogicException('ResourceInterface must have a single identifier (primary key)');
        }

        return array_pop($identifier);
    }
}
