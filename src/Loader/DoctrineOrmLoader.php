<?php

/*
 * This file is part of the Hautelook\AliceBundle package.
 *
 * (c) Baldur Rensch <brensch@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\AliceBundle\Loader;

use Doctrine\DBAL\Sharding\PoolingShardConnection;
use Doctrine\ORM\EntityManagerInterface;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Persister\ObjectManagerPersister;
use Fidry\AliceDataFixtures\Bridge\Doctrine\Purger\Purger;
use Fidry\AliceDataFixtures\Loader\FileResolverLoader;
use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Fidry\AliceDataFixtures\LoaderInterface;
use Fidry\AliceDataFixtures\Persistence\PersisterAwareInterface;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Fidry\AliceDataFixtures\Persistence\Purger\NullPurger;
use Hautelook\AliceBundle\BundleResolverInterface;
use Hautelook\AliceBundle\FixtureLocatorInterface;
use Hautelook\AliceBundle\LoaderInterface as AliceBundleLoaderInterface;
use Hautelook\AliceBundle\LoggerAwareInterface;
use Hautelook\AliceBundle\Resolver\File\KernelFileResolver;
use InvalidArgumentException;
use LogicException;
use Nelmio\Alice\IsAServiceTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

final class DoctrineOrmLoader implements AliceBundleLoaderInterface, LoggerAwareInterface
{
    use IsAServiceTrait;

    private $bundleResolver;
    private $fixtureLocator;
    /** @var LoaderInterface|PersisterAwareInterface */
    private $purgeLoader;
    /** @var LoaderInterface|PersisterAwareInterface */
    private $appendLoader;
    private $logger;

    public function __construct(
        BundleResolverInterface $bundleResolver,
        FixtureLocatorInterface $fixtureLocator,
        LoaderInterface $purgeLoader,
        LoaderInterface $appendLoader,
        LoggerInterface $logger = null
    ) {
        $this->bundleResolver = $bundleResolver;
        $this->fixtureLocator = $fixtureLocator;

        if (false === $purgeLoader instanceof PersisterAwareInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected loader to be an instance of "%s".',
                    PersisterAwareInterface::class
                )
            );
        }

        if (false === $appendLoader instanceof PersisterAwareInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Expected loader to be an instance of "%s".',
                    PersisterAwareInterface::class
                )
            );
        }

        $this->purgeLoader = $purgeLoader;
        $this->appendLoader = $appendLoader;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public function withLogger(LoggerInterface $logger): self
    {
        return new self($this->bundleResolver, $this->fixtureLocator, $this->purgeLoader, $logger);
    }

    /**
     * @inheritdoc
     */
    public function load(
        Application $application,
        EntityManagerInterface $manager,
        array $bundles,
        string $environment,
        bool $append,
        bool $purgeWithTruncate,
        string $shard = null
    ) {
        $bundles = $this->bundleResolver->resolveBundles($application, $bundles);
        $fixtureFiles = $this->fixtureLocator->locateFiles($bundles, $environment);

        $this->logger->info('fixtures found', ['files' => $fixtureFiles]);

        if (null !== $shard) {
            $this->connectToShardConnection($manager, $shard);
        }

        $fixtures = $this->loadFixtures(
            $this->purgeLoader,
            $manager,
            $fixtureFiles,
            $application->getKernel()->getContainer()->getParameterBag()->all(),
            $append,
            $purgeWithTruncate
        );

        $this->logger->info('fixtures loaded');

        return $fixtures;
    }

    private function connectToShardConnection(EntityManagerInterface $manager, string $shard)
    {
        $connection = $manager->getConnection();
        if ($connection instanceof PoolingShardConnection) {
            $connection->connect($shard);

            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Could not establish a shard connection for the shard "%s". The connection must be an instance'
                .' of "%s", got "%s" instead.',
                $shard,
                PoolingShardConnection::class,
                get_class($connection)
            )
        );
    }

    /**
     * @param LoaderInterface|PersisterAwareInterface $loader
     * @param EntityManagerInterface                  $manager
     * @param string[]                                $files
     * @param array                                   $parameters
     * @param bool                                    $append
     * @param bool|null                               $purgeWithTruncate
     *
     * @return object[]
     */
    private function loadFixtures(
        LoaderInterface $loader,
        EntityManagerInterface $manager,
        array $files,
        array $parameters,
        bool $append,
        bool $purgeWithTruncate
    ) {
        if ($append && $purgeWithTruncate) {
            throw new LogicException(
                'Cannot append loaded fixtures and at the same time purge the database. Choose one.'
            );
        }

        $persister = new ObjectManagerPersister($manager);

        if (true === $append) {
            $loader = $this->appendLoader->withPersister($persister);

            return $loader->load($files, $parameters);
        }

        $loader = $this->purgeLoader->withPersister($persister);

        $purgeMode = (true === $purgeWithTruncate)
            ? PurgeMode::createTruncateMode()
            : PurgeMode::createDeleteMode()
        ;

        return $loader->load($files, $parameters, [], $purgeMode);
    }
}
