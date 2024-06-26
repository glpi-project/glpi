<?php

use Glpi\Kernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Closures $_route and $_import are here to provide defaults.
 * These are necessary for prefixing and for locale.
 */
return static function (RoutingConfigurator $routes, Kernel $kernel) {
    $environment = $kernel->getEnvironment();

    if ($environment !== 'development') {
        throw new \RuntimeException(\sprintf(
            'File "%s" must not be loaded in an environment different than "%s". (current environment: "%s")',
            __FILE__,
            'development',
            $environment,
        ));
    }

    $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
    $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');
};
