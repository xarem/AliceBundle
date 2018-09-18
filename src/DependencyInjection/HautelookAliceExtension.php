<?php

/*
 * This file is part of the Hautelook\AliceBundle package.
 *
 * (c) Baldur Rensch <brensch@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\AliceBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle;
use Hautelook\AliceBundle\Console\Command\Doctrine\DoctrineOrmMissingBundleInformationCommand;
use Hautelook\AliceBundle\HautelookAliceBundle;
use LogicException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @private
 *
 * @author Baldur Rensch <brensch@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class HautelookAliceExtension extends Extension
{
    const SERVICES_DIR = __DIR__.'/../../resources/config';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = array_flip($container->getParameter('kernel.bundles'));

        if (false === array_key_exists(FidryAliceDataFixturesBundle::class, $bundles)) {
            throw new LogicException(
                sprintf(
                    'Cannot register "%s" without "%s".',
                    HautelookAliceBundle::class,
                    FidryAliceDataFixturesBundle::class
                )
            );
        }

        $this->loadConfig($configs, $container);
        $this->loadServices($container);

        if (false === array_key_exists(DoctrineBundle::class, $bundles)) {
            $container->removeDefinition('hautelook_alice.console.command.doctrine.doctrine_orm_load_data_fixtures_command');

            $definition = new Definition(DoctrineOrmMissingBundleInformationCommand::class);
            $definition->addTag('console.command');
            $definition->setPublic(true);
            $container->setDefinition('hautelook_alice.console.command.doctrine.doctrine_orm_bundle_missing_command', $definition);
        }
    }

    /**
     * Loads alice configuration and add the configuration values to the application parameters.
     *
     *
     * @throws \InvalidArgumentException
     */
    private function loadConfig(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $processedConfiguration = $this->processConfiguration($configuration, $configs);

        foreach ($processedConfiguration as $key => $value) {
            $container->setParameter(
                $this->getAlias().'.'.$key,
                $value
            );
        }
    }

    /**
     * Loads all the services declarations.
     */
    private function loadServices(ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(self::SERVICES_DIR));
        $finder = new Finder();

        $finder->files()->in(self::SERVICES_DIR);

        foreach ($finder as $file) {
            $loader->load(
                $file->getRelativePathname()
            );
        }
    }
}
