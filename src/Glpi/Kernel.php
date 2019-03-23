<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi;

use Glpi\Database\AbstractDatabase;
use Glpi\Application\Router;
use Glpi\Controller\ControllerInterface;
use Glpi\DependencyInjection\RegisterControllersPass;
use Glpi\EventDispatcher\EventDispatcher;
use Glpi\EventDispatcher\EventSubscriberInterface;
use Slim\Http\Environment;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Plugin;
use Session;

/**
 * Glpi Kernel.
 *
 * @since 10.0.0
 */
class Kernel
{
    /**
     * Cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Configuration directory.
     *
     * @var string
     */
    private $configDir;

    /**
     * Container builder.
     *
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Project root directory.
     *
     * @var string
     */
    private $projectDir;

    /**
     * Constructor
     *
     * @param string $projectDir Project root dir
     * @param string $cacheDir   Cache directory
     */
    public function __construct(
        string $projectDir = GLPI_ROOT,
        string $cacheDir = GLPI_CACHE_DIR,
        string $configDir = GLPI_CONFIG_DIR
    ) {
        $this->cacheDir   = $cacheDir;
        $this->configDir  = $configDir;
        $this->projectDir = $projectDir;

        $this->initGlobalLogger();
        $this->buildContainer();
        $this->initGlobals();
    }

    /**
     * Build container.
     *
     * @return void
     */
    private function buildContainer()
    {
        $cacheFile = $this->cacheDir . '/container.php';

        if ((is_dir($this->cacheDir) && is_writable($this->cacheDir))
            || (is_file($cacheFile) && is_readable($cacheFile) && is_writable($cacheFile))) {
            // Use container caching feature when possible

            // Files checks is always used in order to be sure that container cache in invalidated when used resources
            // (yaml files, used classes, source directory structure, ...) changed.
            // Files checks could be removed in production environment if cache invalidation was done on action
            // that would alter services availability / parameters. For example:
            // - plugin activation/deactivation,
            // - any change on the "local" configuration file.
            $checkFiles = true;

            $containerConfigCache = new ConfigCache($cacheFile, $checkFiles);

            if (!$containerConfigCache->isFresh()) {
                $containerBuilder = $this->getConfiguredAndCompiledContainerBuilder();

                // Dump built container into cache file
                $dumper = new PhpDumper($containerBuilder);
                $containerConfigCache->write(
                    $dumper->dump(array('class' => 'GlpiCachedContainer')),
                    $containerBuilder->getResources()
                );
            }

            // Load and use cached container
            require_once $cacheFile;
            $container = new \GlpiCachedContainer();
        } else {
            $container = $this->getConfiguredAndCompiledContainerBuilder();
        }

        // Define synthetic services that cannot be cached
        $this->defineSyntheticServices($container);

        $this->container = $container;
    }

    /**
     * Configure container.
     *
     * Nota: This method was made to fits usage of Symfony\Component\HttpKernel\Kernel.
     *
     * @param ContainerBuilder $container DI container
     * @param LoaderInterface  $loader    Loader
     *
     * @return void
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->setParameter('kernel.project_dir', $this->getProjectDir());

        // Auto register events listeners and subscribers into the dispatcher
        $container->registerForAutoconfiguration(EventSubscriberInterface::class)
            ->addTag('glpi.event_subscriber');
        $container->addCompilerPass(
            new RegisterListenersPass(
                EventDispatcher::class,
                'glpi.event_listener',
                'glpi.event_subscriber'
            )
        );

        // Register routes
        $container->registerForAutoconfiguration(ControllerInterface::class)
            ->addTag('glpi.controller');
        $container->addCompilerPass(new RegisterControllersPass());

        $loader->load('services.yaml');

        $this->loadPluginsServices($container);
    }

    /**
     * Load local configuration.
     *
     * @param ContainerBuilder $container
     *
     * @return void
     */
    protected function loadLocalConfiguration(ContainerBuilder $container)
    {
        try {
            $configLoader = new YamlFileLoader($container, new FileLocator($this->configDir));
            $configLoader->load('parameters.yaml');
        } catch (FileLocatorFileNotFoundException $e) {
            // Add a file existence check for cache freshness checks
            $container->addResource(
                new FileExistenceResource($this->configDir . '/parameters.yaml')
            );
        }
    }

    /**
     * Get GLPI root directory.
     *
     * Nota: This method was made to fits usage of Symfony\Component\HttpKernel\Kernel.
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Get GLPI services container.
     *
     * Nota: This method was made to fits usage of Symfony\Component\HttpKernel\Kernel.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getContainer(): \Psr\Container\ContainerInterface
    {
        return $this->container;
    }

    /**
     * Initialize global logger.
     *
     * This has to be done prior to anything to be able to use the logger
     * if services instanciation fails.
     *
     * @return void
     */
    private function initGlobalLogger()
    {
        global $GLPI;
        if (!($GLPI instanceof \GLPI)) {
            $GLPI = new \GLPI();
            $GLPI->initLogger();
        }
    }

    /**
     * Initialize global variables used by legacy code.
     *
     * @return void
     */
    private function initGlobals()
    {
        global $CFG_GLPI;
        if (!isset($CFG_GLPI["root_doc"])) {
            \Config::detectRootDoc();
        }

        // Force base path on router. This is required for legacy code that use router to
        // generate routes while Slim app is not "runned".
        $router = $this->container->get('router', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($router instanceof Router && $router->getBasePath() == '') {
            $router->setBasePath($CFG_GLPI["root_doc"] . '/public/index.php');
        }

        if (session_status() === PHP_SESSION_NONE) {
            if (!is_writable(GLPI_SESSION_DIR)) {
                throw new \RuntimeException(
                    sprintf('Cannot write in "%s" directory.', GLPI_SESSION_DIR)
                );
            }
            Session::setPath();
            Session::start();
        }
    }

    /**
     * Define synthectic services.
     *
     * @param ContainerInterface $container DI container
     *
     * @return void
     */
    private function defineSyntheticServices(ContainerInterface $container)
    {
        $container->set('database', $this->getDbInstance());
        $container->set(ConfigParams::class, $this->getConfigParamsInstance());
        $container->set('environment', $this->getEnvironmentInstance());
    }

    /**
     * Returns a configured and compiled instance of container builder.
     *
     * @return ContainerBuilder
     */
    private function getConfiguredAndCompiledContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $resourcesDir = $this->getProjectDir() . '/src/resources';
        $baseLoader = new YamlFileLoader($containerBuilder, new FileLocator($resourcesDir));
        $this->configureContainer($containerBuilder, $baseLoader);

        $this->loadLocalConfiguration($containerBuilder);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    /**
    * Load plugin services.
    *
    * @param ContainerBuilder $container DI container
    *
    * @return void
    */
    private function loadPluginsServices(ContainerBuilder $container)
    {
        // FIXME
        // Following logic will not work for many reasons:
        //
        // 1. Active state of plugins dependes on DB informations, which are verfified by container caching logic.
        // This mean that container cache will not be considered as outdated when a plugin state changed.
        //
        // 2. Plugin state check requires to have a cache and a db service up, prior to container compilation.
        // This is risky as it chain services instanciations before container config is fully loaded.
        //
        // 3. Loading plugin files directly in container will give them ability to override container parameters. Not sur it is wanted.
        // To prevent this, maybe we should use a `use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;`
        // to load plugins.
        return;

        $db = $this->getDbInstance();
        if (!$db->isConnected()) {
            return;
        }

        $projectDir = $this->getProjectDir();

        $plugin = new Plugin();
        $activePlugins = $plugin->getPlugins();
        foreach ($activePlugins as $plugin) {
            $pluginDir = $projectDir . '/plugins/' . $plugin;
            $setupFile  = $pluginDir . '/setup.php';

            if (!file_exists($setupFile)) {
                continue;
            }

            // Includes are made inside a function to prevent included files to override
            // variables used in this function.
            // For example, if the included files contains a $loader variable, it will
            // replace the $loader variable used here.
            $include_fct = function () use ($setupFile) {
                include_once($setupFile);
            };
            $include_fct();

            $diConfigFiles = Plugin::getInfo($plugin, 'di-container-config');

            $loader = new YamlFileLoader($container, new FileLocator($pluginDir));
            foreach ($diConfigFiles as $configFile) {
                $configFilePath = $pluginDir . '/' . $configFile;
                if (!file_exists($configFilePath)) {
                    trigger_error(
                        sprintf(
                            'Unable to find file "%1$s" defined in plugin "%2$s" configuration.',
                            $configFilePath,
                            $plugin
                        )
                    );
                    continue;
                }

                $loader->load($configFilePath);
            }
        }
    }

    /**
     * Get application database connection instance.
     *
     * If already instanciated by GLPI legacy init process, use this instance.
     * Else, if configuration file is available, instanciate a configured DB.
     * Else, instanciate a non configure DB to be able to compile container.
     * Returns global variable reference that will be updated if a new connection is established.
     *
     * @return AbstractDatabase
     */
    private function getDbInstance(): AbstractDatabase
    {
        global $DB;
        if (!($DB instanceof AbstractDatabase)) {
            $DB = \Glpi\DatabaseFactory::create();
        }
        return $DB;
    }

    /**
     * Get application config instance.
     *
     * Returns global variable reference to update service if variable is redefined.
     *
     * @return ConfigParams
     */
    private function getConfigParamsInstance(): ConfigParams
    {
        global $CFG_GLPI;

        $db = $this->getDbInstance();
        if ($db->isConnected()) {
            \Config::loadLegacyConfiguration();
        }

        return new ConfigParams($CFG_GLPI);
    }

    /**
     * Get application environment instance.
     *
     * @return Environment
     */
    private function getEnvironmentInstance(): Environment
    {
        return new Environment($_SERVER);
    }
}
