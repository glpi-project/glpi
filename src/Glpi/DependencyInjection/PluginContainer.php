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
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Compiler\RemoveBuildParametersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Router;

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

    public function get(string $id)
    {
        $this->initializeContainer();

        try {
            return $this->internal_container->get($id);
        } catch (NotFoundExceptionInterface) {
        }

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
                $this->internal_container->set('kernel', $this->kernel);
                $this->internal_container->set('service_container', $this);
                \error_reporting($errorLevel);

                return;
            }
        } catch (\Throwable $e) {
            // rebuild the container if any error occurs
        }

        $container = new ContainerBuilder(new ParameterBag($this->container_parameters->all()));

        $this->configureContainerServices($container);

        $container->compile();

        $this->dumpContainer($cache, $container);

        $this->internal_container = $container;
    }

    private function configureContainerServices(ContainerBuilder $container): void
    {
        $container->setDefinition('routing.loader', (new Definition())
            ->setClass(PluginRoutesLoader::class)
            ->setBindings([
                '$env' => $this->kernel->getEnvironment(),
                '$projectDir' => $this->kernel->getProjectDir(),
            ])
        );

        $container->setDefinition('glpi_plugins_router', (new Definition())
            ->setClass(Router::class)
            ->setPublic(true)
            ->setBindings([
                '$loader' => new Reference('routing.loader'),
                '$resource' => 'glpi_routes',
                '$options' => ['cache_dir' => $this->kernel->getCacheDir() . '/glpi_routes/'],
            ])
        );
    }

    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container): void
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
        $dir = \dirname($cache->getPath()).'/';
        $fs = new Filesystem();

        foreach ($content as $file => $code) {
            $fs->dumpFile($dir.$file, $code);
            @chmod($dir.$file, 0666 & ~umask());
        }
        $legacyFile = \dirname($dir.key($content)).'.legacy';
        if (is_file($legacyFile)) {
            @unlink($legacyFile);
        }

        $cache->write($rootCode, $container->getResources());
    }
}
