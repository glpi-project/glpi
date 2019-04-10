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

namespace tests\units\Glpi\Cache;

/**
 * Test class for src/Glpi/Cache/SimpleCacheFactory.php.
 */
class SimpleCacheFactory extends \GLPITestCase {

   /**
    * Mapping between cache auto configuration and built adapter.
    *
    * @return array
    */
   protected function factoryAutoConfigProvider(): array {
      return [
         // Case: auto adapter without options
         [
            'config'   => [
                'adapter' => 'auto',
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Apcu::class,
               'options' => [
                  'namespace' => '_default',
               ],
               'plugins' => [],
            ],
         ],

         // Case: auto adapter with options
         [
            'config'   => [
                'adapter' => 'auto',
                'options' => [
                   'namespace' => 'app_cache',
                ],
            ],
            'expected_adapter' => [
               'class'   => \Zend\Cache\Storage\Adapter\Apcu::class,
               'options' => [
                  'namespace' => 'app_cache',
               ],
               'plugins' => [
               ],
            ],
         ],
      ];
   }

   /**
    * Test that built adapter matches configuration.
    *
    * @dataProvider factoryAutoConfigProvider
    */
   public function testFactoryAutoAdapter(array $config, array $expectedAdapter) {

      $this->newTestedInstance(
         GLPI_CACHE_DIR,
         true,
         new \mock\Glpi\Cache\CacheStorageFactory(GLPI_CACHE_DIR, '')
      );

      /* @var \Glpi\Cache\SimpleCache $simpleCache */
      $simpleCache = $this->testedInstance->factory($config);
      $this->object($simpleCache)->isInstanceOf(\Glpi\Cache\SimpleCache::class);

      $adapter = $simpleCache->getStorage();
      $this->object($adapter)->isInstanceOf($expectedAdapter['class']);

      if (!empty($expectedAdapter['options'])) {
         $adapterOptions = $adapter->getOptions()->toArray();
         foreach ($expectedAdapter['options'] as $key => $value) {
            $this->array($adapterOptions)->hasKey($key);
            $this->variable($adapterOptions[$key])->isEqualTo($value);
         }
      }

      if (!empty($expectedAdapter['plugins'])) {
         foreach ($expectedAdapter['plugins'] as $pluginClass) {
            $pluginFound = false;
            foreach ($adapter->getPluginRegistry() as $existingPlugin) {
               if ($existingPlugin instanceof $pluginClass) {
                  $pluginFound = true;
               }
            }
            $this->boolean($pluginFound)->isTrue();
         }
      }
   }

   /**
    * Test that factory fallback to filesystem adapter if requested adapter not working.
    */
   public function testFactoryFallbackToFilesystem() {

      $uniqId = uniqid();
      $namespace = 'app_cache';

      $this->newTestedInstance(
          GLPI_CACHE_DIR,
          true,
          new \mock\Glpi\Cache\CacheStorageFactory(GLPI_CACHE_DIR, $uniqId)
      );

      $self = $this;
      $simpleCache = null;

      $this->when(
         function() use ($self, &$simpleCache, $namespace) {
            $simpleCache = $self->testedInstance->factory(
               [
                  'adapter' => 'auto',
                  'options' => [
                     'ttl' => -15,
                     'namespace' => $namespace,
                  ],
               ]
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withPattern('/^Cache adapter instantiation failed, fallback to "filesystem" adapter./')
            ->exists();

      /* @var \Glpi\Cache\SimpleCache $simpleCache */
      $this->object($simpleCache)->isInstanceOf(\Glpi\Cache\SimpleCache::class);

      $adapter = $simpleCache->getStorage();
      $this->object($adapter)->isInstanceOf(\Zend\Cache\Storage\Adapter\Filesystem::class);

      $adapterOptions = $adapter->getOptions()->toArray();
      $this->array($adapterOptions)->hasKey('namespace');
      $this->variable($adapterOptions['namespace'])->isEqualTo($namespace . '_' . $uniqId);
      $this->array($adapterOptions)->hasKey('cache_dir');
      $this->variable($adapterOptions['cache_dir'])->isEqualTo(GLPI_CACHE_DIR . '/' . $namespace . '_' . $uniqId);
   }

   /**
    * Test that factory fallback to memory adapter if filesystem adapter not working.
    */
   public function testFactoryFallbackToMemory() {

      $uniqId = uniqid();
      $namespace = 'app_cache';

      $this->newTestedInstance(
         GLPI_CACHE_DIR,
         true,
         new \mock\Glpi\Cache\CacheStorageFactory(GLPI_CACHE_DIR, $uniqId)
      );

      $self = $this;
      $simpleCache = null;

      $this->when(
         function() use ($self, &$simpleCache, $namespace) {
            $simpleCache = $self->testedInstance->factory(
               [
                  'adapter' => 'filesystem',
                  'options' => [
                     'cache_dir' => '/this/directory/cannot/be/created',
                     'namespace' => $namespace,
                  ],
               ]
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('Cannot create "/this/directory/cannot/be/created" cache directory.')
            ->exists()
       ->error
         ->withType(E_USER_WARNING)
         ->withPattern('/^Cache adapter instantiation failed, fallback to "memory" adapter./')
            ->exists();

      /* @var \Glpi\Cache\SimpleCache $simpleCache */
      $this->object($simpleCache)->isInstanceOf(\Glpi\Cache\SimpleCache::class);

      $adapter = $simpleCache->getStorage();
      $this->object($adapter)->isInstanceOf(\Zend\Cache\Storage\Adapter\Memory::class);

      $adapterOptions = $adapter->getOptions()->toArray();
      $this->array($adapterOptions)->hasKey('namespace');
      $this->variable($adapterOptions['namespace'])->isEqualTo($namespace . '_' . $uniqId);
   }
}
