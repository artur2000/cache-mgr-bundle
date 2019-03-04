<?php

namespace Clownfish\CacheMgrBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ClownfishCacheMgrExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->loadRedisAdapters($container);
    }

    /**
     * For each defined predis client in SncRedisBundle configuration
     * define a service for our proxies / adapters.
     * e.g. for the client "default", a service "@clownfish_cache_mgr.redis.default"
     * will be available which we can use instead of "@snc_redis.default"
     * @param $container
     */
    private function loadRedisAdapters($container) {

        /**
         * For each defined predis client in SncRedisBundle configuration
         * define a service for our proxies / adapters.
         */
        $sncRedisConfig = $container->getExtensionConfig('snc_redis');
        foreach ($sncRedisConfig[0]['clients'] as $client) {

            // add a new "app.number_generatoclownfish_cache_mgr.redis.%s" definitions
            $serviceId = sprintf('clownfish_cache_mgr.redis.%s', $client['alias']);
            $definition = new Definition(
                'Clownfish\CacheMgrBundle\Adapter\Redis',
                array(
                    new Reference(sprintf('snc_redis.%s', $client['alias'])), // a reference to another service
                    new Reference('kernel'), // a reference to another service
                )
            );
            $container->setDefinition($serviceId, $definition);

        }

    }
}
