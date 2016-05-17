<?php

namespace Sidus\FileUploadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Manager\ResourceManager;
use Symfony\Component\Form\AbstractType;
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

    /**
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
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
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'sidus_resource';
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \UnexpectedValueException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'resource_type',
        ]);
        $resolver->setDefaults([
            'query_builder' => $this->getQueryBuilder(),
            'class' => null,
        ]);
        $resolver->setNormalizer('resource_type', function (Options $options, $value) {
            return $this->resourceManager->getResourceTypeConfiguration($value);
        });
        $resolver->setNormalizer('class', function (Options $options, $value) {
            $resourceType = $options['resource_type'];
            if (!$value && $resourceType instanceof ResourceTypeConfiguration) {
                return $resourceType->getEntity();
            }

            return $value;
        });
    }

    /**
     * @return \Closure
     */
    protected function getQueryBuilder()
    {
        return function (EntityRepository $repo) {
            return $repo->createQueryBuilder('e');
        };
    }
}
