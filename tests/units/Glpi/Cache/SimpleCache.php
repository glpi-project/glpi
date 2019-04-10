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

use org\bovigo\vfs\vfsStream;

/* Test for inc/cache/simplecache.class.php */

class SimpleCache extends \GLPITestCase {

   /**
    * Test case: cache dir is empty and writable, footprint file should be created and used.
    */
   public function testCacheWithEmptyWritableCacheDir() {
      $cache_dir = vfsStream::url('glpi/cache');
      $cache_namespace = uniqid(true);

      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [],
         ]
      );

      $footprint_file = vfsStream::url('glpi/cache/' . $cache_namespace . '.json');

      $this->newTestedInstance(
         new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
         $cache_dir
      );

      // File has been initialized
      $this->string(file_get_contents($footprint_file))->isEqualTo('[]');

      $this->testOperationsOnCache($footprint_file);
   }

   /**
    * Test case: footprint file exists, is writable and empty, it should be initialized and used.
    */
   public function testCacheWithEmptyFootprintFile() {
      $cache_dir = vfsStream::url('glpi/cache');
      $cache_namespace = uniqid(true);

      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [
               $cache_namespace . '.json' => ''
            ],
         ]
      );

      $footprint_file = vfsStream::url('glpi/cache/' . $cache_namespace . '.json');

      $this->newTestedInstance(
         new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
         $cache_dir
      );

      // File has initialized
      $this->string(file_get_contents($footprint_file))->isEqualTo('[]');

      $this->testOperationsOnCache($footprint_file);
   }

   /**
    * Test case: footprint file exists and is writable, it should be used.
    */
   public function testCacheWithExistingFootprintFile() {
      $cache_dir = vfsStream::url('glpi/cache');
      $cache_namespace = uniqid(true);

      $existing_footprint = '{"existing_key":"752c14ea195c460bac3c3b7896975ee9fd15eeb7"}';

      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [
               $cache_namespace . '.json' => $existing_footprint
            ],
         ]
      );

      $footprint_file = vfsStream::url('glpi/cache/' . $cache_namespace . '.json');

      $this->newTestedInstance(
         new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
         $cache_dir
      );

      // File has not been erased
      $this->string(file_get_contents($footprint_file))->isEqualTo($existing_footprint);

      $this->testOperationsOnCache($footprint_file);
   }

   /**
    * Test case: footprint file is corrupted, it should be regenerated.
    */
   public function testCacheWithCorruptedFootprintFile() {
      $self = $this;
      $cache_dir = vfsStream::url('glpi/cache');
      $cache_namespace = uniqid(true);

      vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [
               $cache_namespace . '.json' => 'invalid json'
            ],
         ]
      );

      $footprint_file = vfsStream::url('glpi/cache/' . $cache_namespace . '.json');

      $this->when(
         function() use ($self, $cache_dir, $cache_namespace) {
            $self->newTestedInstance(
               new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
               $cache_dir
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('Cache footprint file "' . $footprint_file . '" contents was invalid, it has been cleaned.')
            ->exists();

      // File has been regenerated
      $this->string(file_get_contents($footprint_file))->isEqualTo('[]');

      $this->testOperationsOnCache($footprint_file);
   }

   /**
    * Test case: cache dir is not writable, footprint file cannot be used.
    */
   public function testCacheWithoutFootprintFile() {
      $cache_dir = vfsStream::url('glpi/cache');
      $cache_namespace = uniqid(true);

      $root_directory = vfsStream::setup(
         'glpi',
         null,
         [
            'cache' => [],
         ]
      );

      // Simulate existing cache with footprint.
      $this->newTestedInstance(
         new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
         $cache_dir
      );

      $this->boolean($this->testedInstance->set('footprinted', 'some value'))->isTrue();
      $this->boolean($this->testedInstance->has('footprinted'))->isTrue();

      $root_directory->getChild('cache/' . $cache_namespace . '.json')->chmod(0500); // Make file not writable

      $footprint_file = vfsStream::url('glpi/cache/' . $cache_namespace . '.json');

      $self = $this;
      $this->when(
         function() use ($self, $cache_dir, $cache_namespace) {
            $self->newTestedInstance(
               new \mock\Zend\Cache\Storage\Adapter\Memory(['namespace' => $cache_namespace]),
               $cache_dir
            );
         }
      )->error()
         ->withType(E_USER_WARNING)
         ->withMessage('Cannot write "' . $footprint_file . '" cache footprint file. Cache performance can be lowered.')
            ->exists();

      // Previously set value is not valid anymore as footprint file is not usable and cannot be trusted.
      $this->boolean($this->testedInstance->has('footprinted'))->isFalse();

      $this->testOperationsOnCache(null);
   }

   /**
    * Test all possible cache operations.
    *
    * @param string|null $footprint_file
    */
   private function testOperationsOnCache($footprint_file) {
      // Different scalar types to test.
      $values = [
         'null'         => null,
         'string'       => 'some value',
         'true'         => true,
         'false'        => false,
         'negative int' => -10,
         'positive int' => 15,
         'zero'         => 0,
         'float'        => 15.358,
         'simple array' => ['a', 'b', 'c'],
         'assoc array'  => ['some' => 'value', 'from' => 'assoc', 'array' => null]
      ];

      // Test single set/get/has/delete
      foreach ($values as $key => $value) {
         // Not yet existing
         $this->boolean($this->testedInstance->has($key))->isFalse();

         // Can be set if not existing
         $this->boolean($this->testedInstance->set($key, $value))->isTrue();

         // Is existing after being set
         $this->boolean($this->testedInstance->has($key))->isTrue();

         // Cached value is equal to value that was set
         $this->variable($this->testedInstance->get($key))->isEqualTo($value);

         // Overwriting an existing value works
         $rand = mt_rand();
         $this->boolean($this->testedInstance->set($key, $rand))->isTrue();
         $this->variable($this->testedInstance->get($key))->isEqualTo($rand);

         // Can delete a value
         $this->boolean($this->testedInstance->delete($key))->isTrue();
      }

      // Test multiple set/get
      $this->testedInstance->setMultiple($values);
      foreach ($values as $key => $value) {
         // Cached value exists and is equal to value that was set
         $this->boolean($this->testedInstance->has($key))->isTrue();
         $this->variable($this->testedInstance->get($key))->isEqualTo($value);
      }

      // Test only on partial result to be sure that "*Multiple" methods acts only on targetted elements
      $some_keys = array_rand($values, 4);
      $some_values = array_intersect_key($values, array_fill_keys($some_keys, null));

      $this->array($this->testedInstance->getMultiple($some_keys))->isEqualTo($some_values);

      $this->testedInstance->deleteMultiple($some_keys);
      foreach ($some_keys as $key) {
         // Cached value should not exists as it has been deleted
         $this->boolean($this->testedInstance->has($key))->isFalse();
      }

      // Test global clear
      $this->testedInstance->clear();
      foreach (array_keys($values) as $key) {
         // Cached value should not exists as it has been deleted
         $this->boolean($this->testedInstance->has($key))->isFalse();
      }

      // Test that footprint changes made cache stale
      if (null !== $footprint_file) {
         $this->boolean($this->testedInstance->set('another_key', 'another value'))->isTrue();
         $this->boolean($this->testedInstance->has('another_key'))->isTrue();
         file_put_contents($footprint_file, '{"another_key":"752c14ea195c460bac3c3b7896975ee9fd15eeb7"}');
         $this->boolean($this->testedInstance->has('another_key'))->isFalse();
      }
   }
}
