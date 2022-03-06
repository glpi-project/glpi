<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Psr\SimpleCache\CacheInterface;
use Laminas\Cache\Psr\SimpleCache\SimpleCacheDecorator;
use Laminas\Cache\Storage\StorageInterface;

class SimpleCache extends SimpleCacheDecorator implements CacheInterface {

   /**
    * Determines if footprints must be checked.
    *
    * @var boolean
    */
   private $check_footprints;

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
         $this->footprint_file = $cache_dir . '/' . $storage->getOptions()->getNamespace() . '.json';
         $this->checkFootprintFileIntegrity();
      }
   }

   public function get($key, $default = null) {
      $normalized_key = $this->getNormalizedKey($key);

      $cached_value = parent::get($normalized_key, $default);

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
      $normalized_key = $this->getNormalizedKey($key);

      if ($this->check_footprints) {
         $this->setFootprint($key, $value);
      }

      return parent::set($normalized_key, $value, $ttl);
   }

   public function delete($key) {
      $normalized_key = $this->getNormalizedKey($key);

      if ($this->check_footprints) {
         $this->setFootprint($key, null);
      }

      return parent::delete($normalized_key);
   }

   public function clear() {
      if ($this->check_footprints) {
         $this->setAllCachedFootprints([]);
      }

      return parent::clear();
   }

   public function getMultiple($keys, $default = null) {
      $normalized_keys = array_map([$this, 'getNormalizedKey'], $keys);

      $cached_values = parent::getMultiple($normalized_keys, $default);
      $footprints = $this->check_footprints ? $this->getMultipleCachedFootprints($keys) : [];

      $result = [];
      foreach ($keys as $key) {
         $normalized_key = $this->getNormalizedKey($key);
         $result[$key] = $cached_values[$normalized_key];
         if ($this->check_footprints) {
            if ($footprints[$key] !== $this->computeFootprint($cached_values[$normalized_key])) {
               // If footprint changed, value is no more valid.
               $result[$key] = $default;
            }
         }
      }
      return $result;
   }

   public function setMultiple($values, $ttl = null) {
      if ($this->check_footprints) {
         $this->setMultipleFootprints($values);
      }

      $values_with_normalized_keys = [];
      foreach ($values as $key => $value) {
         $normalized_key = $this->getNormalizedKey($key);
         $values_with_normalized_keys[$normalized_key] = $value;
      }

      return parent::setMultiple($values_with_normalized_keys, $ttl);
   }

   public function deleteMultiple($keys) {
      $normalized_keys = array_map([$this, 'getNormalizedKey'], $keys);

      if ($this->check_footprints) {
         $values = array_combine($keys, array_fill(0, count($keys), null));
         $this->setMultipleFootprints($values);
      }

      return parent::deleteMultiple($normalized_keys);
   }

   public function has($key) {
      $normalized_key = $this->getNormalizedKey($key);

      if (!parent::has($normalized_key)) {
         return false;
      }

      if (!$this->check_footprints) {
         return true;
      }

      // Cache value is not usable if stale, consider it has not existing.
      return $this->getCachedFootprint($key) === $this->computeFootprint(parent::get($normalized_key));
   }

   /**
    * Returns all known cache keys.
    *
    * @return array
    */
   public function getAllKnownCacheKeys() {
      $footprints = $this->getAllCachedFootprints();
      return array_keys($footprints);
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
      $footprints = $this->getAllCachedFootprints();

      return array_key_exists($key, $footprints) ? $footprints[$key] : null;
   }

   /**
    * Return known footprints for multiple cached items.
    *
    * @param array $keys
    *
    * @return array
    */
   private function getMultipleCachedFootprints(array $keys) {
      $footprints = $this->getAllCachedFootprints();

      $result = [];
      foreach ($keys as $key) {
         $result[$key] = $footprints[$key] ?? null;
      }

      return $result;
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
      $footprints = $this->getAllCachedFootprints();

      foreach ($values as $key => $value) {
         $footprints[$key] = $this->computeFootprint($value);
      }

      $this->setAllCachedFootprints($footprints);
   }

   /**
    * Check footprint file integrity, to ensure that it can be used securely.
    *
    * @return void
    */
   private function checkFootprintFileIntegrity() {
      if ((file_exists($this->footprint_file) && !is_writable($this->footprint_file))
          || (!file_exists($this->footprint_file) && !is_writable(dirname($this->footprint_file)))) {
         trigger_error(
            sprintf('Cannot write "%s" cache footprint file. Cache performance can be lowered.', $this->footprint_file),
            E_USER_WARNING
         );
         $this->footprint_file = null;
         return;
      }

      if (!file_exists($this->footprint_file)) {
         // Create empty array in file if not exists.
         $this->setAllCachedFootprints([]);
         return;
      }

      // It may potentially happen that a writable file may be unreadable.
      if (!is_readable($this->footprint_file)) {
         trigger_error(
            sprintf('Cannot read "%s" cache footprint file. Cache performance can be lowered.', $this->footprint_file),
            E_USER_WARNING
         );
         $this->footprint_file = null;
         return;
      }

      $file_contents = $this->getFootprintFileContents();
      if (empty($file_contents)) {
         // Create empty array in file if empty.
         $this->setAllCachedFootprints([]);
         return;
      }

      $footprints = json_decode($file_contents, true);
      if (json_last_error() !== JSON_ERROR_NONE || !is_array($footprints)) {
         // Clear footprint file if not a valid JSON.
         trigger_error(
            sprintf('Cache footprint file "%s" contents was invalid, it has been cleaned.', $this->footprint_file),
            E_USER_WARNING
         );
         $this->setAllCachedFootprints([]);
      }
   }

   /**
    * Get footprint file contents.
    *
    * @return string|null
    */
   private function getFootprintFileContents() {
      if (!$handle = fopen($this->footprint_file, 'rb')) {
         return null;
      }

      // Lock the file, if possible (depends on used FS).
      // Use a share lock to not make readers wait each oher.
      $is_locked = flock($handle, LOCK_SH);

      $file_contents = '';
      while (!feof($handle)) {
         $file_contents .= fread($handle, 8192);
      }

      if ($is_locked) {
         // Unlock the file if it has been locked
         flock($handle, LOCK_UN);
      }

      fclose($handle);

      return $file_contents;
   }

   /**
    * Returns all cache footprints.
    *
    * @return array  Associative array of cached items footprints, where keys corresponds to the
    *                cache key of the item and value is its footprint.
    */
   private function getAllCachedFootprints() {
      if (null !== $this->footprint_file) {
         $file_contents = $this->getFootprintFileContents();

         $footprints = !empty($file_contents) ? json_decode($file_contents, true) : null;

         if (json_last_error() !== JSON_ERROR_NONE || !is_array($footprints)) {
            // Should happen only if file has been corrupted/deleted/truncated after cache instanciation,
            // launch integrity tests again to trigger warnings and fix file contents.
            $this->checkFootprintFileIntegrity();
            return [];
         }

         return $footprints;
      }

      return $this->footprint_fallback_storage;
   }

   /**
    * Save all cache footprints.
    *
    * @param array $footprints  Associative array of cached items footprints, where keys corresponds to the
    *                           cache key of the item and value is its footprint.
    *
    * @return void
    */
   private function setAllCachedFootprints($footprints) {
      if (null !== $this->footprint_file) {
         // Remove null values to prevent storage of deleted footprints
         array_filter(
            $footprints,
            function($val) {
               return null !== $val;
            }
         );

         $json = json_encode($footprints, JSON_PRETTY_PRINT);

         $handle = fopen($this->footprint_file, 'c');

         // Lock the file, if possible (depends on used FS).
         // Use an exclusive lock as noone should open the file while it is updated.
         $is_locked = flock($handle, LOCK_EX);

         $result = ftruncate($handle, 0)
            && fwrite($handle, $json)
            && fflush($handle);

         if ($is_locked) {
            // Unlock the file if it has been locked
            flock($handle, LOCK_UN);
         }

         fclose($handle);

         if ($result !== false) {
            return;
         } else {
            // Should happen only if file is not writable anymore (rights problems or no more disk space),
            // fallback to singleton storage.
            $this->footprint_file = null;
         }
      }

      $this->footprint_fallback_storage = $footprints;
   }

   /**
    * Returns normalized key to ensure compatibility with cache storage.
    *
    * @param string $key
    *
    * @return string
    */
   private function getNormalizedKey(string $key): string {
      return sha1($key);
   }
}
