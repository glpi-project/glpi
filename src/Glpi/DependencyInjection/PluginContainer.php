<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\DependencyInjection;

use Glpi\Kernel\Kernel;
use Glpi\Routing\PluginRoutesLoader;
use Glpi\Routing\PluginsRouter;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Compiler\RemoveBuildParametersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader as ContainerPhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\Psr4DirectoryLoader;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class PluginContainer implements ContainerInterface
{
    private ?ContainerInterface $internal_container = null;
    private Container $symfony_container;
    private ParameterBag $container_parameters;
    private Kernel $kernel;

    public function __construct(
        #[Autowire(service: 'service_container')] Container $symfony_container,
        #[Autowire(service: 'parameter_bag')] ParameterBag $container_parameters,
        #[Autowire(service: 'kernel')] Kernel $kernel,
    ) {
        $this->symfony_container = $symfony_container;
        $this->container_parameters = $container_parameters;
        $this->kernel = $kernel;
    }

    public function set(string $id, ?object $service): void
    {
        $this->internal_container->set($id, $service);
    }

    public function initialized(string $id): bool
    {
        return $this->internal_container->initialized($id);
    }

    public function getParameter(string $name): \UnitEnum|float|array|bool|int|string|null
    {
        return $this->internal_container->getParameter($name) ?? $this->symfony_container->getParameter($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->internal_container->hasParameter($name) || $this->symfony_container->hasParameter($name);
    }

    public function setParameter(string $name, \UnitEnum|float|array|bool|int|string|null $value): void
    {
        $this->internal_container->setParameter($name, $value);
    }

    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
        $this->initializeContainer();

        try {
            return $this->internal_container->get($id, $invalidBehavior);
        } catch (NotFoundExceptionInterface) {
        }

        return $this->symfony_container->get($id);
    }

    public function getSymfonyService(string $id): mixed
    {
        return $this->symfony_container->get($id);
    }

    public function has(string $id): bool
    {
        $this->initializeContainer();

        if ($this->internal_container->has($id)) {
            return true;
        }

        return $this->symfony_container->has($id);
    }

    public function initializeContainer(): void
    {
        if ($this->internal_container) {
            return;
        }

        $cache = new ConfigCache(sprintf("%s/%s.php", $this->kernel->getCacheDir(), \str_replace('\\', '_', self::class)), $this->kernel->isDebug());
        $cachePath = $cache->getPath();

        // Silence E_WARNING to ignore "include" failures - don't use "@" to prevent silencing fatal errors
        $errorLevel = \error_reporting(\E_ALL ^ \E_WARNING);

        try {
            if (
                \is_file($cachePath)
                && \is_object($this->internal_container = include $cachePath)
                && (!$this->kernel->isDebug() || $cache->isFresh())
            ) {
                $this->internal_container->set('service_container', $this->internal_container);

                return;
            }
        } catch (\Throwable $e) {
            // rebuild the container if any error occurs
        } finally {
            \error_reporting($errorLevel);
        }

        $container = new ContainerBuilder(new ParameterBag($this->container_parameters->all()));

        $this->configureContainerServices($container);

        $container->compile();

        $this->dumpContainer($cache, $container);

        $this->setRuntimeServices($container);

        $this->internal_container = $container;
    }

    private function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $env = $this->kernel->getEnvironment();
        $locator = new FileLocator($this->kernel);
        $resolver = new LoaderResolver([
//            new XmlFileLoader($container, $locator, $env),
//            new YamlFileLoader($container, $locator, $env),
//            new IniFileLoader($container, $locator, $env),
            new PhpFileLoader($container, $locator, $env, class_exists(ConfigBuilderGenerator::class) ? new ConfigBuilderGenerator($this->kernel->getBuildDir()) : null),
            new GlobFileLoader($container, $locator, $env),
            new DirectoryLoader($container, $locator, $env),
            new ClosureLoader($container, $env),
            new Psr4DirectoryLoader($locator),
        ]);

        return new DelegatingLoader($resolver);
    }

    private function dumpContainer(ConfigCache $cache, ContainerBuilder $container): void
    {
        $dumper = new PhpDumper($container);

        $buildParameters = [];
        foreach ($container->getCompilerPassConfig()->getPasses() as $pass) {
            if ($pass instanceof RemoveBuildParametersPass) {
                $buildParameters = \array_merge($buildParameters, $pass->getRemovedParameters());
            }
        }

        $content = $dumper->dump([
            'file' => $cache->getPath(),
            'as_files' => true,
            'debug' => $this->kernel->isDebug(),
            'inline_factories' => true,
            'inline_class_loader' => true,
            'build_time' => $container->hasParameter('kernel.container_build_time') ? $container->getParameter('kernel.container_build_time') : \time(),
        ]);

        $rootCode = array_pop($content);
        $dir = \dirname($cache->getPath()) . '/';
        $fs = new Filesystem();

        foreach ($content as $file => $code) {
            $fs->dumpFile($dir . $file, $code);
            @chmod($dir . $file, 0666 & ~umask());
        }
        $legacyFile = \dirname($dir . key($content)) . '.legacy';
        if (is_file($legacyFile)) {
            @unlink($legacyFile);
        }

        $cache->write($rootCode, $container->getResources());
    }

    private function configureContainerServices(ContainerBuilder $container): void
    {
        $loader = $this->getContainerLoader($container);


        // Hacks coming from Symfony's way of handling custom configurators
        $file = (new \ReflectionObject($this->kernel))->getFileName();
        /* @var ContainerPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file);
        $instanceof = \Closure::bind(fn &() => $this->instanceof, $kernelLoader, $kernelLoader)();
        $configurator = new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file, $this->kernel->getEnvironment());


        // Handy configurator (just like Symfony config files)
        $services = $configurator->services();
        $services
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->instanceof(PublicService::class)->public()
        ;


        // Autoconfig
        $container->registerForAutoconfiguration(PublicService::class)->setPublic(true);


        // Services that will be set *after* the container's compilation
        $services->set(self::class)->synthetic();
        $services->set('symfony_router')->synthetic();


        // Routes loader
        $route_loader = $services->set(PluginRoutesLoader::class);
        $route_loader->bind('$env', $this->kernel->getEnvironment());
        $route_loader->bind('$container', new Reference(self::class));
        $services->alias('routing.loader', PluginRoutesLoader::class);


        // Internal Routing service used by GLPI's PluginsRouter
        $router = $services->set('glpi_internal_router', Router::class)->public();
        $router->bind('$loader', new Reference(PluginRoutesLoader::class));
        $router->bind('$resource', 'glpi_routes');
        $router->bind('$options', ['cache_dir' => $this->kernel->getCacheDir() . '/glpi_routes/']);


        // Plugins routing (with runtime checks)
        $router = $services->set(PluginsRouter::class)->public();
        $router->arg(0, new Reference('glpi_internal_router'));
        $router->arg(1, new Reference('symfony_router'));
        $services->alias(UrlGeneratorInterface::class, PluginsRouter::class);
        $services->alias(UrlMatcherInterface::class, PluginsRouter::class);
        $services->alias(RequestMatcherInterface::class, PluginsRouter::class);
        $services->alias(RouterInterface::class, PluginsRouter::class);

        // Plugins services
        foreach (\Plugin::getPlugins() as $key) {
            $path = \Plugin::getPhpDir($key);
            if (!\is_dir($path . '/src/Controller/')) {
                continue;
            }
            $services->load(\NS_PLUG . \ucfirst($key) . '\\Controller\\', $path . '/src/Controller/');
        }
    }

    private function setRuntimeServices(ContainerBuilder $container): void
    {
        // Container service is available for autowiring.
        $container->set(self::class, $this);

        $container->set('symfony_router', $this->symfony_container->get('router'));
    }
}
