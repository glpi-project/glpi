<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Closure;
use Glpi\Kernel\Kernel;
use Glpi\Routing\PluginRoutesLoader;
use Plugin;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\Psr4DirectoryLoader;
use Symfony\Component\Routing\Router;
use UnitEnum;

class PluginContainer implements ContainerInterface
{
    private ?ContainerInterface $internal_container = null;
    private Container $symfony_container;
    private ParameterBag $container_parameters;
    private Kernel $kernel;

    public function __construct(
        #[Autowire(service: 'service_container')]
        Container $symfony_container,
        #[Autowire(service: 'parameter_bag')]
        ParameterBag $container_parameters,
        #[Autowire(service: 'kernel')]
        Kernel $kernel,
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

    public function getParameter(string $name): UnitEnum|float|array|bool|int|string|null
    {
        return $this->internal_container->getParameter($name) ?? $this->symfony_container->getParameter($name);
    }

    public function hasParameter(string $name): bool
    {
        return $this->internal_container->hasParameter($name) || $this->symfony_container->hasParameter($name);
    }

    public function setParameter(string $name, UnitEnum|float|array|bool|int|string|null $value): void
    {
        $this->internal_container->setParameter($name, $value);
    }

    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
        $this->initializeContainer();

        try {
            return $this->internal_container->get($id, self::EXCEPTION_ON_INVALID_REFERENCE);
        } catch (NotFoundExceptionInterface) {
        }

        return $this->symfony_container->get($id, $invalidBehavior);
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

        $container = new ContainerBuilder(new ParameterBag($this->container_parameters->all()));

        $this->configureContainerServices($container);

        $container->compile();

        $this->internal_container = $container;
    }

    private function configureContainerServices(ContainerBuilder $container): void
    {
        $configurator = $this->getContainerConfigurator($container);

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


        // Routes loader
        $route_loader = $services->set(PluginRoutesLoader::class);
        $route_loader->bind('$env', $this->kernel->getEnvironment());
        $services->alias('routing.loader', PluginRoutesLoader::class);


        // Plugins routing
        $router = $services->set('glpi_plugin_router', Router::class)->public();
        $router->bind('$loader', new Reference(PluginRoutesLoader::class));
        $router->bind('$resource', 'plugins_routes');
        $services->alias(UrlGeneratorInterface::class, 'glpi_plugin_router');

        // Plugins services
        foreach (Plugin::getPlugins() as $key) {
            $path = Plugin::getPhpDir($key);
            if (!\is_dir($path . '/src/Controller/')) {
                continue;
            }
            $services->load(\NS_PLUG . \ucfirst($key) . '\\Controller\\', $path . '/src/Controller/');
        }
    }
    /**
     * Hacks coming from Symfony's way of handling custom configurators.
     *
     * @see https://github.com/symfony/symfony/blob/e61a0146e746a4f75498c1cb1c2aa2be5faf8d05/src/Symfony/Component/DependencyInjection/Extension/ExtensionTrait.php
     */
    private function getContainerConfigurator(ContainerBuilder $container): ContainerConfigurator
    {
        $env = $this->kernel->getEnvironment();
        $locator = new FileLocator($this->kernel);
        $resolver = new LoaderResolver([
            new PhpFileLoader($container, $locator, $env, class_exists(ConfigBuilderGenerator::class) ? new ConfigBuilderGenerator($this->kernel->getBuildDir()) : null),
            new Psr4DirectoryLoader($locator),
        ]);

        $loader = new DelegatingLoader($resolver);

        $file = (new ReflectionObject($this->kernel))->getFileName();
        $kernelLoader = $loader->getResolver()->resolve($file);

        // Should never happen in theory but it helps with static analysis
        if (!$kernelLoader instanceof PhpFileLoader) {
            throw new RuntimeException();
        }

        $instanceof = Closure::bind(fn&() => $this->instanceof, $kernelLoader, $kernelLoader)();
        $configurator = new ContainerConfigurator($container, $kernelLoader, $instanceof, $file, $file, $this->kernel->getEnvironment());

        return $configurator;
    }
}
