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

namespace Glpi\Application;

use Glpi\Cache\SimpleCache;
use Glpi\EventDispatcher\EventDispatcher;
use Glpi\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * Glpi Kernel.
 *
 * @since x.x.x
 */
class Kernel {

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
    * @param string $projectDir  Project root dir
    * @param string $cacheDir    Cache directory
    * @param string $configDir   Configuration directory
    */
   public function __construct(string $projectDir = GLPI_ROOT, string $cacheDir = GLPI_CACHE_DIR, string $configDir = GLPI_CONFIG_DIR) {
      $this->cacheDir = $cacheDir;
      $this->configDir = $configDir;
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
   private function buildContainer() {
      // First get fresh core container.
      // 1) We want to be able to fallback to a core services only container if compilation fails when we add plugins.
      // 2) We want to use Plugin service from core container to fetch plugin list.
      $container = $this->getFreshContainer();

      // Then get container with active plugins
      try {
         /* @var $plugin \Plugin */
         $plugin = $container->get(\Plugin::class);
         $container = $this->getFreshContainer($plugin->getPlugins());
      } catch (\Exception $e) {
         trigger_error(
            'Unable to compile container including plugin services, only core services are available. Error was: '
               . "\n" . $e->getMessage(),
            E_USER_WARNING
         );
      }

      $this->container = $container;
   }

   /**
    * Get GLPI root directory.
    *
    * Nota: This method was made to fits usage of Symfony\Component\HttpKernel\Kernel.
    *
    * @return string
    */
   public function getProjectDir(): string {
      return $this->projectDir;
   }

   /**
    * Get GLPI services container.
    *
    * Nota: This method was made to fits usage of Symfony\Component\HttpKernel\Kernel.
    *
    * @return \Psr\Container\ContainerInterface
    */
   public function getContainer(): \Psr\Container\ContainerInterface {
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
   private function initGlobalLogger() {
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
   private function initGlobals() {
      global $CFG_GLPI;
      if (!isset($CFG_GLPI['root_doc'])) {
         \Config::detectRootDoc();
      }

      if (session_status() === PHP_SESSION_NONE) {
         if (!is_writable(GLPI_SESSION_DIR)) {
            throw new \RuntimeException(sprintf('Cannot write in "%s" directory.', GLPI_SESSION_DIR));
         }
         \Session::setPath();
         \Session::start();
      }

      $db = $this->container->get(\DBmysql::class, ContainerInterface::NULL_ON_INVALID_REFERENCE);
      if ($db instanceof \DBmysql && $db->connected) {
         \Config::loadLegacyConfiguration(false);
      }
   }

   /**
    * Define synthectic core services.
    *
    * @param ContainerInterface $container
    *
    * @return void
    */
   private function defineSyntheticCoreServices(ContainerInterface $container) {
      // Inject DB definition.
      $container->set(\DBmysql::class, $this->getDbInstance());

      // Inject cache definition
      $container->set(SimpleCache::class, $this->getCacheInstance());
   }

   /**
    * Get a fresh container.
    * Use cache if up to date or rebuild and cache a fresh version.
    *
    * @param array $plugins  Plugins (directory name) to add to container
    *
    * @return void
    */
   private function getFreshContainer(array $plugins = []) {
      $containerClass = sprintf('Glpi%sCachedContainer', md5(serialize($plugins)));
      $cacheFile = $this->cacheDir . '/' . $containerClass . '.php';

      if ((is_dir($this->cacheDir) && is_writable($this->cacheDir))
          || (is_file($cacheFile) && is_readable($cacheFile) && is_writable($cacheFile))) {
         // Use container caching feature when possible

         // Files checks is always used in order to be sure that container cache is invalidated
         // when used resources (yaml files, used classes, source directory structure, ...) changed.
         //
         // Files checks could be removed in production environment
         // but only if a if cache invalidation process is implemented on actions
         // that would alter services availability / parameters. For example:
         // - plugin activation/deactivation,
         // - any change on the "parameters.yaml" configuration file.
         $checkFiles = true;

         $containerConfigCache = new ConfigCache($cacheFile, $checkFiles);

         if (!$containerConfigCache->isFresh()) {
            $containerBuilder = $this->getConfiguredAndCompiledContainerBuilder($plugins);

            // Dump built container into cache file
            $dumper = new PhpDumper($containerBuilder);
            $containerConfigCache->write(
               $dumper->dump(['class' => $containerClass]),
               $containerBuilder->getResources()
            );
         }

         // Load and use cached container
         require_once $cacheFile;
         $container = new $containerClass();
      } else {
         $container = $this->getConfiguredAndCompiledContainerBuilder($plugins);
      }

      // Define synthetic services that cannot be cached
      $this->defineSyntheticCoreServices($container);

      return $container;
   }

   /**
    * Returns a configured and compiled instance of container builder.
    *
    * @param array $plugins  Plugins (directory name) to add to container
    *
    * @return ContainerBuilder
    */
   private function getConfiguredAndCompiledContainerBuilder(array $plugins = []): ContainerBuilder {
      $containerBuilder = new ContainerBuilder();
      $containerBuilder->setParameter('kernel.project_dir', $this->projectDir);

      // Auto register events listeners and subscribers into the dispatcher
      $containerBuilder->registerForAutoconfiguration(EventSubscriberInterface::class)
         ->addTag('glpi.event_subscriber');
      $containerBuilder->addCompilerPass(
         new RegisterListenersPass(EventDispatcher::class, 'glpi.event_listener', 'glpi.event_subscriber')
      );

      // Load services configuration files
      $servicesLoader = new YamlFileLoader($containerBuilder, new FileLocator($this->projectDir . '/resources'));
      $servicesLoader->load('services.yaml');
      foreach ($plugins as $plugin) {
         $pluginDir = $this->projectDir . '/plugins/' . $plugin . '/resources';
         try {
            $servicesLoader = new YamlFileLoader($containerBuilder, new FileLocator($pluginDir));
            $servicesLoader->load('services.yaml');
         } catch (\Symfony\Component\Config\Exception\FileLocatorFileNotFoundException $e) {
            // Add a file existence check for cache freshness checks
            $containerBuilder->addResource(new FileExistenceResource($pluginDir . '/services.yaml'));
         }
      }

      // Load configuration file
      try {
         $configLoader = new YamlFileLoader($containerBuilder, new FileLocator($this->configDir));
         $configLoader->load('parameters.yaml');
      } catch (\Symfony\Component\Config\Exception\FileLocatorFileNotFoundException $e) {
         // Add a file existence check for cache freshness checks
         $containerBuilder->addResource(new FileExistenceResource($this->configDir . '/parameters.yaml'));
      }

      $containerBuilder->compile();

      return $containerBuilder;
   }

   /**
    * Get application database connection instance.
    *
    * If already instanciated by GLPI legacy init process, use this instance.
    * Else, if configuration file is available, instanciate a configured DB.
    * Else, instanciate a non configured DB to be able to compile container.
    * Returns global variable reference that will be updated if a new connection is established.
    *
    * @return \DBmysql
    */
   private function getDbInstance(): \DBmysql {
      global $DB;
      if (!($DB instanceof \DBmysql)) {
         if (class_exists('DB', false)) {
            $DB = new \DB();
         } else {
            $DB = new \DBmysql(null, false);
         }
      }
      return $DB;
   }

   /**
    * Get application cache instance.
    *
    * Returns global variable reference to update service if variable is redefined.
    *
    * @return SimpleCache
    */
   private function getCacheInstance(): SimpleCache {
      global $GLPI_CACHE;
      if (!($GLPI_CACHE instanceof SimpleCache)) {
         $GLPI_CACHE = \Config::getCache('cache_db');
      }
      return $GLPI_CACHE;
   }
}
