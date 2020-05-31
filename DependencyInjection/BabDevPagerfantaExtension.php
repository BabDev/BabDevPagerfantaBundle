<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\KernelEvents;

final class BabDevPagerfantaExtension extends Extension
{
    public function getAlias()
    {
        return 'babdev_pagerfanta';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->setParameter('babdev_pagerfanta.default_twig_template', $config['default_twig_template']);
        $container->setParameter('babdev_pagerfanta.default_view', $config['default_view']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('pagerfanta.xml');
        if (method_exists(Alias::class, 'getDeprecation')) {
            $loader->load('deprecated_51.xml');
        } else {
            $loader->load('deprecated.xml');
        }

        if (Configuration::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND === $config['exceptions_strategy']['out_of_range_page']) {
            $container->getDefinition('pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found')
                ->addTag(
                    'kernel.event_listener',
                    [
                        'event' => KernelEvents::EXCEPTION,
                        'method' => 'onKernelException',
                        'priority' => 512,
                    ]
                );
        } else {
            $container->removeDefinition('pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found');
        }

        if (Configuration::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND === $config['exceptions_strategy']['not_valid_current_page']) {
            $container->getDefinition('pagerfanta.event_listener.convert_not_valid_current_page_to_not_found')
                ->addTag(
                    'kernel.event_listener',
                    [
                        'event' => KernelEvents::EXCEPTION,
                        'method' => 'onKernelException',
                        'priority' => 512,
                    ]
                );
        } else {
            $container->removeDefinition('pagerfanta.event_listener.convert_not_valid_current_page_to_not_found');
        }
    }
}
