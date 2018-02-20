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

use Faker\Provider\Base;
use Hautelook\AliceBundle\HautelookAliceBundle;
use LogicException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $bundles = array_flip($container->getParameter('kernel.bundles'));

        if (false === array_key_exists('Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle', $bundles)) {
            throw new LogicException(
                sprintf(
                    'Cannot register "%s" without "Fidry\AliceDataFixtures\Bridge\Symfony\FidryAliceDataFixturesBundle".',
                    HautelookAliceBundle::class
                )
            );
        }
        if (false === array_key_exists('Doctrine\Bundle\DoctrineBundle\DoctrineBundle', $bundles)) {
            throw new LogicException(
                sprintf(
                    'Cannot register "%s" without "Doctrine\Bundle\DoctrineBundle\DoctrineBundle".',
                    HautelookAliceBundle::class
                )
            );
        }

        $this->loadConfig($configs, $container);
        $this->loadServices($container);

        // TODO: remove it in the future as we bump the minimal requirement of nelmio/alice
        // Register autoconfiguration rules for Symfony DI 3.3+
        if (method_exists($container, 'registerForAutoconfiguration')) {
            if ( 0 === count($container->findTaggedServiceIds('nelmio_alice.faker.provider')) ) {
                $container->registerForAutoconfiguration(Base::class)
                    ->addTag('nelmio_alice.faker.provider');
            }
        }
    }

    /**
     * Loads alice configuration and add the configuration values to the application parameters.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
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
     *
     * @param ContainerBuilder $container
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
