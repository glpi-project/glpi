<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Cache\SimpleCache;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheManager extends \GLPITestCase {

   protected function contextProvider(): iterable {
      yield [
         'context'  => 'tempcache',
         'is_valid' => false,
      ];
      yield [
         'context'  => 'core',
         'is_valid' => true,
      ];
      yield [
         'context'  => 'plugin:tester',
         'is_valid' => true,
      ];
   }

   /**
    * @dataProvider contextProvider
    */
   public function testIsContextValid(string $context, bool $is_valid): void {
      vfsStream::setup('glpi', null, ['config' => []]);

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      $this->boolean($this->testedInstance->isContextValid($context))->isEqualTo($is_valid);

      // Also test argument checks on other methods
      if (!$is_valid) {
         $exception_msg = sprintf('Invalid context: "%s".', $context);

         if (extension_loaded('memcached')) {
            $this->exception(
               function () use ($context) {
                  $this->testedInstance->setConfiguration($context, 'memcached://localhost');
               }
            )->message->isEqualTo($exception_msg);
         }

         $this->exception(
            function () use ($context) {
               $this->testedInstance->unsetConfiguration($context);
            }
         )->message->isEqualTo($exception_msg);

         $this->exception(
            function () use ($context) {
               $this->testedInstance->getCacheInstance($context);
            }
         )->message->isEqualTo($exception_msg);

         return;
      }

      if (extension_loaded('memcached')) {
         $this->boolean($this->testedInstance->setConfiguration($context, 'memcached://localhost'))->isTrue();
      }

      $this->boolean($this->testedInstance->unsetConfiguration($context))->isTrue();
      $this->object($this->testedInstance->getCacheInstance($context))->isInstanceOf(SimpleCache::class);
   }

   protected function configurationProvider(): iterable {
      foreach (['core', 'plugin:tester'] as $context) {
         // Invalid unique DSN
         yield [
            'context'          => $context,
            'dsn'              => 'whoot://invalid',
            'options'          => [],
            'namespace'        => null,
            'expected_error'   => 'Invalid DSN: "whoot://invalid".',
            'expected_adapter' => FilesystemAdapter::class, // Fallback adapter
         ];

         // Invalid multiple DSN
         yield [
            'context'          => $context,
            'dsn'              => ['redis://cache1.glpi-project.org', 'redis://cache2.glpi-project.org'],
            'options'          => [],
            'namespace'        => null,
            'expected_error'   => 'Invalid DSN: ["redis://cache1.glpi-project.org","redis://cache2.glpi-project.org"].',
            'expected_adapter' => FilesystemAdapter::class, // Fallback adapter
         ];

         if (extension_loaded('memcached')) {
            // Memcached config (unique DSN)
            yield [
               'context'          => $context,
               'dsn'              => 'memcached://cache.glpi-project.org',
               'options'          => [
                  'libketama_compatible' => true,
               ],
               'namespace'        => null,
               'expected_error'   => null,
               'expected_adapter' => MemcachedAdapter::class,
            ];

            // Memcached config (multiple DSN)
            yield [
               'context'          => $context,
               'dsn'              => ['memcached://cache1.glpi-project.org', 'memcached://cache2.glpi-project.org'],
               'options'          => [],
               'namespace'        => 'glpi1',
               'expected_error'   => null,
               'expected_adapter' => MemcachedAdapter::class,
            ];
         }

         if (extension_loaded('redis')) {
            // Redis config
            yield [
               'context'          => $context,
               'dsn'              => 'redis://cache.glpi-project.org',
               'options'          => [
                  'lazy'       => true,
                  'persistent' => 1,
               ],
               'namespace'        => null,
               'expected_error'   => null,
               'expected_adapter' => RedisAdapter::class,
            ];
         }
      }
   }

   /**
    * @dataProvider configurationProvider
    */
   public function testSetConfiguration(
      string $context,
      $dsn,
      array $options,
      ?string $namespace,
      ?string $expected_error,
      ?string $expected_adapter
   ): void {
      vfsStream::setup('glpi', null, ['config' => []]);

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      if ($expected_error !== null) {
         $this->exception(
            function () use ($context, $dsn, $options, $namespace) {
               $this->testedInstance->setConfiguration($context, $dsn, $options, $namespace);
            }
         )->message->isEqualTo($expected_error);
         return;
      }

      $this->boolean($this->testedInstance->setConfiguration($context, $dsn, $options, $namespace))->isTrue();

      $config_file = vfsStream::url('glpi/config/' . \Glpi\Cache\CacheManager::CONFIG_FILENAME);

      $expected_config = [
         $context => [
            'dsn'       => $dsn,
            'options'   => $options,
            'namespace' => $namespace,
         ],
      ];

      $this->boolean(file_exists($config_file))->isTrue();
      $this->array(include($config_file))->isEqualTo($expected_config);
   }

   public function testUnsetConfiguration(): void {
      $config_filename = \Glpi\Cache\CacheManager::CONFIG_FILENAME;

      $expected_config = [
         'core' => [
            'dsn'       => 'memcached://localhost',
         ],
         'plugin:tester' => [
            'dsn'       => 'redis://cache.glpi-project.org/glpi',
            'options'   => ['lazy' => true],
            'namespace' => 'tester',
         ],
         'plugin:another' => [
            'dsn'       => 'redis://cache.glpi-project.org/glpi',
            'options'   => [],
            'namespace' => 'another',
         ],
      ];

      vfsStream::setup(
         'glpi',
         null,
         [
            'config' => [
               $config_filename => '<?php' . "\n" . 'return ' . var_export($expected_config, true) . ';',
            ]
         ]
      );

      $config_file = vfsStream::url('glpi/config/' . $config_filename);

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      // Unsetting an invalid context does not alter config file
      $this->exception(
         function () {
            $this->testedInstance->unsetConfiguration('notavalidcontext');
         }
      )->message->isEqualTo('Invalid context: "notavalidcontext".');
      $this->boolean(file_exists($config_file))->isTrue();
      $this->array(include($config_file))->isEqualTo($expected_config);

      // Unsetting core config only removes core entry in config file
      $this->boolean($this->testedInstance->unsetConfiguration('core'))->isTrue();
      unset($expected_config['core']);
      $this->boolean(file_exists($config_file))->isTrue();
      $this->array(include($config_file))->isEqualTo($expected_config);

      // Unsetting a plugin config only removes this plugin entry in config file
      $this->boolean($this->testedInstance->unsetConfiguration('plugin:tester'))->isTrue();
      unset($expected_config['plugin:tester']);
      $this->boolean(file_exists($config_file))->isTrue();
      $this->array(include($config_file))->isEqualTo($expected_config);
   }

   /**
    * @dataProvider configurationProvider
    */
   public function testGetCacheInstance(
      string $context,
      $dsn,
      array $options,
      ?string $namespace,
      ?string $expected_error,
      ?string $expected_adapter
   ): void {

      $config = [
         $context => [
            'dsn'       => $dsn,
            'options'   => $options,
            'namespace' => $namespace,
         ],
      ];

      vfsStream::setup(
         'glpi',
         null,
         [
            'config' => [
               \Glpi\Cache\CacheManager::CONFIG_FILENAME => '<?php' . "\n" . 'return ' . var_export($config, true) . ';',
            ]
         ]
      );

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      if ($expected_error !== null) {
         $this->when(
            function () use ($context) {
               $this->testedInstance->getCacheInstance($context);
            }
         )->error()
            ->withType(E_USER_WARNING)
            ->withMessage(sprintf('Invalid configuration for cache context "%s".', $context, $expected_error))
            ->exists();
         return;
      }

      $cache_instance = $this->testedInstance->getCacheInstance($context);
      $this->object($cache_instance)->isInstanceOf(SimpleCache::class);
      $this->object($cache_instance->getStorage())->isInstanceOf($expected_adapter);

      if ($context === 'core') {
         // test CacheManager::getCoreCacheInstance()
         $core_cache_instance = $this->testedInstance->getCoreCacheInstance();
         $this->object($core_cache_instance)->isInstanceOf(SimpleCache::class);
         $this->object($core_cache_instance->getStorage())->isInstanceOf($expected_adapter);
      }
   }

   /**
    * @dataProvider contextProvider
    */
   public function testGetCacheInstanceDefault(string $context, bool $is_valid): void {
      if (!$is_valid) {
         return;
      }

      // No config file
      vfsStream::setup('glpi', null, []);

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      $cache_instance = $this->testedInstance->getCacheInstance($context);
      $this->object($cache_instance)->isInstanceOf(SimpleCache::class);
      $this->object($cache_instance->getStorage())->isInstanceOf(FilesystemAdapter::class);

      if ($context === 'core') {
         // test CacheManager::getCoreCacheInstance()
         $core_cache_instance = $this->testedInstance->getCoreCacheInstance();
         $this->object($core_cache_instance)->isInstanceOf(SimpleCache::class);
         $this->object($core_cache_instance->getStorage())->isInstanceOf(FilesystemAdapter::class);
      }
   }

   protected function dsnProvider(): iterable {
      yield [
         'dsn'      => 'memcached://user:pass@127.0.0.1:1015?weight=20',
         'is_valid' => true,
         'scheme'   => 'memcached',
      ];
      yield [
         'dsn'      => ['memcached://user:pass@127.0.0.1:1015?weight=20', 'memcached://user:pass@127.0.0.1:1016?weight=30'],
         'is_valid' => true,
         'scheme'   => 'memcached',
      ];
      yield [
         'dsn'      => 'redis://localhost/glpi',
         'is_valid' => true,
         'scheme'   => 'redis',
      ];
      yield [
         'dsn'      => 'rediss://192.168.0.15',
         'is_valid' => true,
         'scheme'   => 'rediss',
      ];
      yield [
         'dsn'      => 'memcached:/localhost', // missing /
         'is_valid' => false,
         'scheme'   => null,
      ];
      yield [
         'dsn'      => 'donotknowit://127.0.0.1', // unknown scheme
         'is_valid' => false,
         'scheme'   => null,
      ];
      yield [
         'dsn'      => ['redis:///tmp/cache', 'redis://localhost/glpi'], // invalid multiple DSN
         'is_valid' => false,
         'scheme'   => null,
      ];
   }

   /**
    * @dataProvider dsnProvider
    */
   public function testIsDsnValid($dsn, bool $is_valid, ?string $scheme = null): void {

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      $this->boolean($this->testedInstance->isDsnValid($dsn))->isIdenticalTo($is_valid);
   }

   /**
    * @dataProvider dsnProvider
    */
   public function testExtractScheme($dsn, bool $is_valid, ?string $scheme = null): void {

      $this->newTestedInstance(vfsStream::url('glpi/config'));

      $this->variable($this->testedInstance->extractScheme($dsn))->isIdenticalTo($scheme);
   }
}
