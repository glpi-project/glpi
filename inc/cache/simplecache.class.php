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

namespace Glpi\Cache;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Zend\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\StorageFactory;

class SimpleCache extends SimpleCacheDecorator {

   /**
    * Determines if footprints must be checked.
    *
    * @var boolean
    */
   private $check_footprints;

   /**
    * Footprint storage.
    *
    * @var SimpleCacheDecorator
    */
   private $footprint_storage;

   /**
    * Footprint file path, if existing.
    *
    * @var string|null
    */
   private $footprint_file;

   /**
    * Footprint fallback storage used if footprint file is not available.
    *
    * @var array
    */
   private $footprint_fallback_storage = [];

   public function __construct(StorageInterface $storage, $cache_dir, $check_footprints = true) {
      parent::__construct($storage);

      $this->check_footprints = $check_footprints;
      if ($this->check_footprints) {
         $footprints_dir = GLPI_CACHE_DIR . '/cache_footprints';
         if (!is_dir($footprints_dir) && !mkdir($footprints_dir)) {
            trigger_error(
               sprintf('Cannot write into cache footprint directory "%s".', $footprints_dir),
               E_USER_WARNING
            );
         }
         $this->footprint_storage = new SimpleCacheDecorator(
            StorageFactory::factory(
               [
                  'adapter' => 'filesystem',
                  'options' => [
                     'cache_dir' => $footprints_dir,
                     'namespace' => $storage->getOptions()->getNamespace() . '_footprints',
                  ],
                  'plugins'   => ['serializer']
               ]
            )
         );
      }
   }

   public function get($key, $default = null) {
      $cached_value = parent::get($key, $default);

      if (!$this->check_footprints) {
         return $cached_value;
      }

      if ($this->getCachedFootprint($key) !== $this->computeFootprint($cached_value)) {
         // If footprint changed, value is no more valid.
         return $default;
      }

      return $cached_value;
   }

   public function set($key, $value, $ttl = null) {
      if ($this->check_footprints) {
         $this->setFootprint($key, $value);
      }

      return parent::set($key, $value, $ttl);
   }

   public function delete($key) {
      if ($this->check_footprints) {
         $this->setFootprint($key, null);
      }

      return parent::delete($key);
   }

   public function clear() {
      if ($this->check_footprints) {
         $this->footprint_storage->clear();
      }

      return parent::clear();
   }

   public function getMultiple($keys, $default = null) {
      $cached_values = parent::getMultiple($keys, $default);

      if ($this->check_footprints) {
         foreach ($cached_values as $key => $cached_value) {
            if ($this->getCachedFootprint($key) !== $this->computeFootprint($cached_value)) {
               // If footprint changed, value is no more valid.
               $cached_values[$key] = $default;
            }
         }
      }

      return $cached_values;
   }

   public function setMultiple($values, $ttl = null) {
      if ($this->check_footprints) {
         $this->setMultipleFootprints($values);
      }

      return parent::setMultiple($values, $ttl);
   }

   public function deleteMultiple($keys) {
      if ($this->check_footprints) {
         $this->footprint_storage->deleteMultiple($keys);
      }

      return parent::deleteMultiple($keys);
   }

   public function has($key) {
      if (!parent::has($key)) {
         return false;
      }

      if (!$this->check_footprints) {
         return true;
      }

      // Cache value is not usable if stale, consider it has not existing.
      return $this->getCachedFootprint($key) === $this->computeFootprint(parent::get($key));
   }

   /**
    * Returns the computed footprint of a value.
    *
    * @param mixed $value
    *
    * @return string
    */
   private function computeFootprint($value) {
      return sha1(serialize($value));
   }

   /**
    * Returns known footprint for a cached item.
    *
    * @param string $key
    *
    * @return string|null
    */
   private function getCachedFootprint($key) {
      return $this->footprint_storage->get($key);
   }

   /**
    * Defines footprint for cache item.
    *
    * @param string $key     Key of the cached item.
    * @param mixed  $values  Value of the cached item.
    *
    * @return void
    */
   private function setFootprint($key, $value) {
      $this->setMultipleFootprints([$key => $value]);
   }

   /**
    * Defines footprint for multiple cache items.
    *
    * @param array $values  Associative array of cached items, where keys corresponds to the
    *                       cache key of the item and value is its cached value.
    *
    * @return void
    */
   private function setMultipleFootprints(array $values) {
      $footprints = [];

      foreach ($values as $key => $value) {
         $footprints[$key] = $this->computeFootprint($value);
      }

      $this->footprint_storage->setMultiple($footprints);
   }
}
