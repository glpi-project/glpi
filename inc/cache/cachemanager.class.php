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

namespace Glpi\Cache;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\CacheItem;
use Toolbox;

class CacheManager {

   /**
    * Filesystem scheme.
    * @var string
    */
   public const SCHEME_FILESYSTEM = 'file';

   /**
    * Memcached scheme.
    * @var string
    */
   public const SCHEME_MEMCACHED  = 'memcached';

   /**
    * Redis scheme (TCP connection).
    * @var string
    */
   public const SCHEME_REDIS      = 'redis';

   /**
    * Redis scheme (TLS connection).
    * @var string
    */
   public const SCHEME_REDISS     = 'rediss';

   /**
    * Core cache configuration filename.
    * @var string
    */
   public const CONFIG_FILENAME = 'cache.php';

   /**
    * Configuration directory.
    *
    * @var string
    */
   private $config_dir;

   public function __construct(string $config_dir = GLPI_CONFIG_DIR) {
      $this->config_dir = $config_dir;
   }

   /**
    * Defines cache configuration for given context.
    *
    * @param string          $context
    * @param string|string[] $dsn
    * @param array           $options
    * @param string|null     $namespace
    *
    * @return bool
    */
   public function setConfiguration(string $context, $dsn, array $options = [], ?string $namespace = null): bool {
      if (!$this->isContextValid($context)) {
         throw new \InvalidArgumentException(sprintf('Invalid context: "%s".', $context));
      }
      if (!$this->isDsnValid($dsn)) {
         throw new \InvalidArgumentException(sprintf('Invalid DSN: %s.', json_encode($dsn, JSON_UNESCAPED_SLASHES)));
      }

      $config = $this->getRawConfig();
      $config[$context] = [
         'dsn'       => $dsn,
         'options'   => $options,
         'namespace' => $namespace,
      ];

      return $this->writeConfig($config);
   }

   /**
    * Unset cache configuration for given context.
    *
    * @param string $context
    *
    * @return bool
    */
   public function unsetConfiguration(string $context): bool {
      if (!$this->isContextValid($context)) {
         throw new \InvalidArgumentException(sprintf('Invalid context: "%s".', $context));
      }

      $config = $this->getRawConfig();
      unset($config[$context]);

      return $this->writeConfig($config);
   }

   /**
    * Test connection to given DSN. Conection failure will trigger an exception.
    *
    * @param string|string[] $dsn
    * @param array           $options
    *
    * @return array
    */
   public function testConnection($dsn, array $options = []): void {
      switch ($this->extractScheme($dsn)) {
         case self::SCHEME_MEMCACHED:
            // Init Memcached connection to find potential connection errors.
            $client = MemcachedAdapter::createConnection($dsn, $options);
            $stats = $client->getStats();
            if ($stats === false) {
               // Memcached::getStats() will return false if server cannot be reached.
               throw new \RuntimeException('Unable to connect to Memcached server.');
            }
            break;
         case self::SCHEME_REDIS:
         case self::SCHEME_REDISS:
            // Init Redis connection to find potential connection errors.
            $options['lazy'] = false; //force instant connection
            RedisAdapter::createConnection($dsn, $options);
            break;
         default:
            break;
      }
   }

   /**
    * Get cache instance for given context.
    *
    * @return SimpleCache|null
    */
   public function getCacheInstance(string $context): SimpleCache {
      if (!$this->isContextValid($context)) {
         throw new \InvalidArgumentException(sprintf('Invalid context: "%s".', $context));
      }

      $raw_config = $this->getRawConfig();

      if (array_key_exists($context, $raw_config)) {
         $config = $raw_config[$context];
      } else {
         // Default to filesystem, inside GLPI_CACHE_DIR/$context, with a generic namespace.
         $config = [
            'dsn'       => sprintf('file://%s', GLPI_CACHE_DIR),
            'namespace' => $context,
         ];
      }

      $dsn       = $config['dsn'];
      $options   = $config['options'] ?? [];
      $scheme    = $this->extractScheme($dsn);
      $namespace = preg_replace(
         '/[' . preg_quote(CacheItem::RESERVED_CHARACTERS, '/') . ']/',
         '_',
         $config['namespace'] ?? $context
      );

      switch ($scheme) {
         case self::SCHEME_FILESYSTEM:
            $path = preg_replace('/^file:\/\/(.+)$/', '$1', $dsn);
            $storage = new FilesystemAdapter($namespace, 0, $path);
         break;

         case self::SCHEME_MEMCACHED:
            $storage = new MemcachedAdapter(
               MemcachedAdapter::createConnection($dsn, $options),
               $namespace
            );
            break;

         case self::SCHEME_REDIS:
         case self::SCHEME_REDISS:
            $storage = new RedisAdapter(
               RedisAdapter::createConnection($dsn, $options),
               $namespace
            );
            break;

         default:
            throw new \RuntimeException(sprintf('Invalid cache DSN %s.', var_export($dsn, true)));
            break;
      }

      return new SimpleCache($storage);
   }

   /**
    * Get core cache instance.
    *
    * @return SimpleCache
    */
   public function getCoreCacheInstance(): SimpleCache {

      return $this->getCacheInstance('core');
   }

   /**
    * Extract scheme from DSN.
    *
    * @param string|string[] $dsn
    *
    * @return string|null
    */
   public function extractScheme($dsn): ?string {
      if (is_array($dsn)) {
         if (count($dsn) === 0) {
            return null;
         }

         $schemes = [];
         foreach ($dsn as $entry) {
            $schemes[] = $this->extractScheme($entry);
         }
         $schemes = array_unique($schemes);

         if (count($schemes) !== 1) {
            return null; // Mixed schemes are not allowed
         }
         $scheme = reset($schemes);
         // Only Memcached system accept multiple DSN.
         return $scheme === self::SCHEME_MEMCACHED ? $scheme : null;
      }

      if (!is_string($dsn)) {
         return null;
      }

      $matches = [];
      if (preg_match('/^(?<scheme>[a-z]+):\/\//', $dsn, $matches) !== 1) {
         return null;
      }
      $scheme = $matches['scheme'];

      return in_array($scheme, array_keys($this->getAvailableAdapters())) ? $scheme : null;
   }

   /**
    * Returns raw configuration from configuration file.
    *
    * @return array
    */
   private function getRawConfig(): array {
      $config_file = $this->config_dir . DIRECTORY_SEPARATOR . self::CONFIG_FILENAME;

      $configs = [];
      if (file_exists($config_file)) {
         $configs = include($config_file);
         foreach ($configs as $context => $config) {
            if (!$this->isContextValid($context)
                || !is_array($config)
                || !array_key_exists('dsn', $config)
                || !$this->isDsnValid($config['dsn'])
                || (array_key_exists('options', $config) && !is_array($config['options']))) {
               trigger_error(sprintf('Invalid configuration for cache context "%s".', $context), E_USER_WARNING);
               unset($configs[$context]);
               continue;
            }
         }
         if (false) {
            $configs = null;
         }
      }

      return $configs;
   }

   /**
    * Write cache configuration to disk.
    *
    * @param array $config
    *
    * @return bool
    */
   private function writeConfig(array $config): bool {
      $config_export = var_export($config, true);

      $config_file_contents = <<<PHP
<?php
return {$config_export};
PHP;

      return Toolbox::writeConfig(self::CONFIG_FILENAME, $config_file_contents, $this->config_dir);
   }

   /**
    * Check if configuration context is valid.
    *
    * @param string $context
    *
    * @return bool
    */
   public function isContextValid(string $context): bool {
      return $context === 'core' || preg_match('/^plugin:\w+$/', $context) === 1;
   }

   /**
    * Check if DSN is valid.
    *
    * @param string|string[] $dsn
    *
    * @return bool
    */
   public function isDsnValid($dsn): bool {
      if (is_array($dsn)) {
         if (count($dsn) === 0) {
            return false;
         }

         $schemes = [];
         foreach ($dsn as $entry) {
            $schemes[] = $this->extractScheme($entry);
         }
         $schemes = array_unique($schemes);

         if (count($schemes) !== 1) {
            return false; // Mixed schemes are not allowed
         }

         // Only Memcached system accept multiple DSN.
         return reset($schemes) === self::SCHEME_MEMCACHED;
      }

      return in_array($this->extractScheme($dsn), array_keys($this->getAvailableAdapters()));
   }

   /**
    * Returns a list of available adapters.
    * Keys are adapter schemes (see self::SCHEME_*).
    * Values are translated names.
    *
    * @return array
    */
   public static function getAvailableAdapters(): array {
      return [
         self::SCHEME_FILESYSTEM => __('File system cache'),
         self::SCHEME_MEMCACHED  => __('Memcached'),
         self::SCHEME_REDIS      => __('Redis (TCP)'),
         self::SCHEME_REDISS     => __('Redis (TLS)'),
      ];
   }
}
