<?php

namespace Sidus\FileUploadBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use League\Flysystem\AdapterInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Sidus\FileUploadBundle\Configuration\ResourceTypeConfiguration;
use Sidus\FileUploadBundle\Model\ResourceRepositoryInterface;
use Sidus\FileUploadBundle\Manager\ResourceManagerInterface;
use Sidus\FileUploadBundle\Model\ResourceInterface;
use Sidus\FileUploadBundle\Registry\FilesystemRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Cleanup extra files, orphan files and entities with missing files
 *
 * If no option specified, display interactive dialog and ask for each step
 * If at least an option is specified: Trigger only the specified actions
 * If shell is not interactive, will default to "no" at each steps and cancel the actions UNLESS --force is used
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class CleanAssetsCommand extends ContainerAwareCommand
{
    /** @var ResourceManagerInterface */
    protected $resourceManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var array */
    protected $adaptersByResourceType = [];

    /** @var AdapterInterface[] */
    protected $adapters = [];

    /** @var FilesystemRegistry */
    protected $fileSystemRegistry = [];

    /** @var array */
    protected $extraFiles = [];

    /** @var array */
    protected $missingFiles = [];

    /**
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('sidus:file-upload:clean-resources')
            ->addOption('delete-extra', null, InputOption::VALUE_NONE, 'Delete files with no corresponding entity')
            ->addOption('delete-orphans', null, InputOption::VALUE_NONE, 'Delete orphan entities with no relations')
            ->addOption('delete-missing', null, InputOption::VALUE_NONE, 'Delete entities with missing file')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force actions (no interaction)')
            ->addOption('simulate', null, InputOption::VALUE_NONE, 'Do not remove anything, only simulate the action')
            ->setDescription('Cleanup orphan files and extra assets');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \LogicException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     * @throws \UnexpectedValueException
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Check if this command can be launched ?
        $this->resourceManager = $this->getContainer()->get(ResourceManagerInterface::class);
        $this->doctrine = $this->getContainer()->get(ManagerRegistry::class);
        $this->fileSystemRegistry = $this->getContainer()->get(FilesystemRegistry::class);

        foreach ($this->resourceManager->getResourceConfigurations() as $resourceConfiguration) {
            $filesystem = $this->resourceManager->getFilesystemForType($resourceConfiguration->getCode());
            if (!method_exists($filesystem, 'getAdapter')) {
                // This is due to the fact that the Filesystem layer does not differenciate it's own files from other
                // files owned by a different filesystem but with the same adapter
                // In the end if we want to make sure that we don't delete files from an other filesystem using the
                // same adapter we need to get to the adapter
                throw new \UnexpectedValueException('Filesystem must allow access to adapter');
            }
            $adapter = $filesystem->getAdapter();
            $adapterReference = spl_object_hash($adapter);
            $this->adapters[$adapterReference] = $adapter;
            $this->adaptersByResourceType[$resourceConfiguration->getCode()] = $adapterReference;
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messages = [
            'info' => '<error>WARNING! This command involves a very high risk of data/file losses</error>',
            'skipping' => '<comment>Exiting</comment>',
            'question' => "<info>Are you sure you want to execute this command ? y/[n]</info>\n",
        ];
        if (!$this->askQuestion($input, $output, [true], $messages)) {
            return 0;
        }

        $executeAll = true;
        if ($input->getOption('delete-extra')
            || $input->getOption('delete-orphans')
            || $input->getOption('delete-missing')
        ) {
            $executeAll = false;
        }

        $this->computeFileSystemDifferences();

        if ($executeAll || $input->getOption('delete-extra')) {
            $this->executeDeleteExtra($input, $output);
        }

//        if ($executeAll || $input->getOption('delete-missing')) {
//            $this->executeDeleteMissing($input, $output);
//        }

        if ($executeAll || $input->getOption('delete-orphans')) {
            $this->executeDeleteOrphans($input, $output);
        }

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln('<info>Success</info>');
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function executeDeleteExtra(InputInterface $input, OutputInterface $output)
    {
        /** @var array $extraFiles */
        foreach ($this->extraFiles as $adapterReference => $extraFiles) {
            $count = \count($extraFiles);
            $files = implode(', ', $extraFiles);
            $m = '<error>NO FILE REMOVED : Please use the --force option in non-interactive mode to prevent';
            $m .= ' any mistake</error>';

            $configCodes = [];
            foreach ($this->adaptersByResourceType as $configCode => $adapterRef2) {
                if ($adapterReference === $adapterRef2) {
                    $configCodes[] = $configCode;
                }
            }
            $configs = implode("', '", $configCodes);

            $messages = [
                'no_item' => "<comment>No file to remove in fs '{$configs}'</comment>",
                'info' => "<comment>The following files will be deleted in fs '{$configs}': {$files}</comment>",
                'skipping' => '<comment>Skipping file removal.</comment>',
                'error' => $m,
                'question' => "Are you sure you want to remove {$count} files in fs '{$configs}' ? y/[n]\n",
            ];

            if (!$this->askQuestion($input, $output, $extraFiles, $messages)) {
                continue;
            }

            $adapter = $this->adapters[$adapterReference];
            foreach ($extraFiles as $extraFile) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $output->writeln("<comment>Removing file {$extraFile}</comment>");
                }
                if (!$input->getOption('simulate') && $adapter->has($extraFile)) {
                    $adapter->delete($extraFile);
                }
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("<comment>{$count} files deleted in fs '{$configs}'</comment>");
            }
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function executeDeleteMissing(InputInterface $input, OutputInterface $output)
    {
        // @todo Implement : Careful with the problematic of the different fs / different entities
        $output->writeln('<error>Deleting entities with missing files is not supported for the moment.</error>');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\Console\Exception\RuntimeException
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \RuntimeException
     */
    protected function executeDeleteOrphans(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->resourceManager->getResourceConfigurations() as $resourceConfiguration) {
            $className = $resourceConfiguration->getEntity();
            $entityManager = $this->doctrine->getManagerForClass($className);
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \UnexpectedValueException("No manager found for class {$className}");
            }

            $foundEntities = $this->findAssociatedEntities($entityManager, $className);

            $this->removeOrphanEntities($input, $output, $entityManager, $resourceConfiguration, $foundEntities);
        }
    }

    /**
     * @param EntityManagerInterface $manager
     * @param string                 $className
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws MappingException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws \BadMethodCallException
     * @throws \RuntimeException
     *
     * @return array
     */
    protected function findAssociatedEntities(
        EntityManagerInterface $manager,
        $className
    ) {
        /** @var ClassMetadata[] $metadatas */
        $metadatas = $manager->getMetadataFactory()->getAllMetadata();

        $foundEntities = [];
        foreach ($metadatas as $metadata) {
            if ($metadata->getName() === $className) {
                // Collecting all resource entities with associations to other entities
                foreach ($metadata->getAssociationMappings() as $fieldName => $association) {
                    // Check association carried by the Resource side (clearly not recommended)
                    // @todo Please test this code or contact the author:
                    // We never had this case in our data set, we can't be sure it's going to behave like expected
                    $className = $association['sourceEntity'];
                    $metadata = $manager->getClassMetadata($className);
                    /** @var EntityRepository $repository */
                    $repository = $manager->getRepository($className);
                    $qb = $repository
                        ->createQueryBuilder('e')
                        ->select("e.{$metadata->getSingleIdentifierColumnName()} AS id")
                        ->where("e.{$association['fieldName']} IS NOT NULL");

                    foreach ($qb->getQuery()->getArrayResult() as $result) {
                        $value = $result['id'];
                        $foundEntities[$className][$value] = $value;
                    }
                }
            }

            // Collecting all resource entities associated to other entities
            foreach ($metadata->getAssociationsByTargetClass($className) as $fieldName => $association) {
                $className = $association['targetEntity'];
                $metadata = $manager->getClassMetadata($className);
                /** @var EntityRepository $repository */
                $repository = $manager->getRepository($association['sourceEntity']);
                $qb = $repository
                    ->createQueryBuilder('e')
                    ->select("r.{$metadata->getSingleIdentifierColumnName()} AS id")
                    ->innerJoin("e.{$association['fieldName']}", 'r');

                foreach ($qb->getQuery()->getArrayResult() as $result) {
                    $value = $result['id'];
                    $foundEntities[$className][$value] = $value;
                }
            }
        }

        return $foundEntities;
    }

    /**
     * @param InputInterface            $input
     * @param OutputInterface           $output
     * @param EntityManagerInterface    $manager
     * @param ResourceTypeConfiguration $resourceConfiguration
     * @param array                     $foundEntities
     *
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws MappingException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws RuntimeException
     * @throws \BadMethodCallException
     */
    protected function removeOrphanEntities(
        InputInterface $input,
        OutputInterface $output,
        EntityManagerInterface $manager,
        ResourceTypeConfiguration $resourceConfiguration,
        array $foundEntities
    ) {
        $className = $resourceConfiguration->getEntity();
        $metadata = $manager->getClassMetadata($className);
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($className);
        $ids = isset($foundEntities[$className]) ? $foundEntities[$className] : [];
        $qb = $repository
            ->createQueryBuilder('e')
            ->where("e.{$metadata->getSingleIdentifierColumnName()} NOT IN (:ids)")
            ->setParameter('ids', $ids);

        $results = [];
        foreach ($qb->getQuery()->getResult() as $result) {
            if (!$result instanceof ResourceInterface) {
                throw new \UnexpectedValueException('Results should implement ResourceInterface');
            }
            // We filter the results based on their type, it's really important with single-table inheritance as
            // Doctrine will load all subtype for a current class and this cannot be done easily in the query.
            if ($result->getType() !== $resourceConfiguration->getCode()) {
                continue;
            }
            $results[] = $result;
        }

        $messages = $this->getEntityRemovalMessages($metadata, $results);
        if (!$this->askQuestion($input, $output, $results, $messages)) {
            return;
        }

        foreach ($results as $result) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $m = "<comment>Removing {$result->getType()}";
                $m .= " : {$result->getPath()} - {$result->getOriginalFileName()}</comment>";
                $output->writeln($m);
            }
            if (!$input->getOption('simulate')) {
                try {
                    $manager->remove($result);
                    $manager->flush($result);
                } catch (\Exception $e) {
                    $m = "<error>An error occured while trying to delete #{$result->getIdentifier()} ";
                    $m .= "{$result->getOriginalFileName()}: {$e->getMessage()}</error>";
                    $output->writeln($m);
                }
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param array         $results
     *
     * @throws \BadMethodCallException
     *
     * @return array
     */
    protected function getEntityRemovalMessages(ClassMetadata $metadata, array $results)
    {
        $className = $metadata->getName();

        $ids = [];
        $primaryKeyReflection = $metadata->getSingleIdReflectionProperty();
        foreach ($results as $result) {
            $ids[] = $primaryKeyReflection->getValue($result);
        }
        $list = implode(', ', $ids);
        $info = "<comment>The following entities of class '{$className}' will be deleted: {$list}</comment>";

        $error = '<error>NO ENTITY REMOVED : Please use the --force option in non-interactive mode to prevent';
        $error .= ' any mistake</error>';

        $count = \count($results);

        return [
            'no_item' => "<comment>No entity to remove of class '{$className}'</comment>",
            'info' => $info,
            'skipping' => '<comment>Skipping entity removal.</comment>',
            'error' => $error,
            'question' => "Are you sure you want to remove {$count} entities for class '{$className}' ? y/[n]\n",
        ];
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $items
     * @param array           $messages
     *
     * @throws RuntimeException
     * @throws LogicException
     * @throws InvalidArgumentException
     *
     * @return bool
     */
    protected function askQuestion(
        InputInterface $input,
        OutputInterface $output,
        array $items,
        array $messages
    ) {
        $count = \count($items);
        if (0 === $count) {
            if (isset($messages['no_item']) && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($messages['no_item']);
            }

            return false;
        }

        if (isset($messages['info']) && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln($messages['info']);
        }

        if (!$input->getOption('force') && $input->isInteractive()) {
            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $questionMessage = "Are you sure you wan't to do this action ? y/[n]\n";
            if (isset($messages['question'])) {
                $questionMessage = $messages['question'];
            }
            $question = new Question($questionMessage, 'n');
            if ('y' !== strtolower($questionHelper->ask($input, $output, $question))) {
                if (isset($messages['skipping']) && $output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln($messages['skipping']);
                }

                return false;
            }
        } elseif (!$input->getOption('force')) {
            if (isset($messages['error']) && $output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln($messages['error']);
            }

            return false;
        }

        return true;
    }

    /**
     * Compute de differences between what's in the storage system and what's in the database
     *
     * @throws \UnexpectedValueException
     */
    protected function computeFileSystemDifferences()
    {
        $entityPathByFilesystems = [];
        foreach ($this->resourceManager->getResourceConfigurations() as $resourceConfiguration) {
            $className = $resourceConfiguration->getEntity();
            $entityManager = $this->doctrine->getManagerForClass($className);
            if (!$entityManager instanceof EntityManagerInterface) {
                throw new \UnexpectedValueException("No manager found for class {$className}");
            }
            $repository = $entityManager->getRepository($className);
            if (!$repository instanceof ResourceRepositoryInterface) {
                throw new \UnexpectedValueException(
                    "Repository for class {$className} must implement ResourceRepositoryInterface"
                );
            }

            $paths = $repository->getPaths();
            $adapterReference = $this->adaptersByResourceType[$resourceConfiguration->getCode()];
            if (array_key_exists($adapterReference, $entityPathByFilesystems)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $entityPathByFilesystems[$adapterReference] = array_merge(
                    $entityPathByFilesystems[$adapterReference],
                    $paths
                );
            } else {
                $entityPathByFilesystems[$adapterReference] = $paths;
            }
        }

        foreach ($this->adapters as $adapterReference => $adapter) {
            $existingPaths = [];
            foreach ($adapter->listContents() as $metadata) {
                $entityPath = $metadata['path'];
                if ('.gitkeep' === $entityPath) {
                    continue;
                }
                $existingPaths[$entityPath] = $entityPath;
            }
            $entityPaths = $entityPathByFilesystems[$adapterReference];
            $this->extraFiles[$adapterReference] = array_diff_key($existingPaths, $entityPaths);
            $this->missingFiles[$adapterReference] = array_diff_key($entityPaths, $existingPaths);
        }
    }
}
