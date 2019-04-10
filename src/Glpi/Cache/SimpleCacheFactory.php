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

use Zend\Cache\Exception\ExceptionInterface;

/**
 * Glpi simple cache factory.
 *
 * @since 10.0.0
 */
class SimpleCacheFactory
{
    /**
     * Cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Force skipping of integrity checks.
     *
     * @var bool
     */
    private $disableIntegrityChecks;

    /**
     * Cache storage factory class.
     *
     * @var CacheStorageFactory
     */
    private $storageFactory;

    /**
     * @param string              $cacheDir                Cache directory.
     * @param bool                $disableIntegrityChecks  Force skipping of integrity checks.
     * @param CacheStorageFactory $storageFactory          Cache storage factory class.
     */
    public function __construct(string $cacheDir, bool $disableIntegrityChecks, CacheStorageFactory $storageFactory)
    {
        $this->cacheDir = $cacheDir;
        $this->disableIntegrityChecks = $disableIntegrityChecks;
        $this->storageFactory = $storageFactory;
    }

    /**
     * Create a simple cache instance.
     *
     * @param array $cfg  Cache storage configuration, see Zend\Cache\StorageFactory::factory()
     *
     * @return SimpleCache
     */
    public function factory($cfg): SimpleCache
    {
        $isAdapterComputed      = !isset($cfg['adapter']) || 'auto' === $cfg['adapter'];
        $skipIntegrityChecks = $this->disableIntegrityChecks
            || (!$isAdapterComputed && $this->canSkipIntegrityChecks($cfg['adapter']));

        // Compute prefered adapter if 'auto' value or no value is used
        if ($isAdapterComputed) {
            if (function_exists('wincache_ucache_add')) {
                $cfg['adapter'] = 'wincache';
            } elseif (function_exists('apcu_fetch')) {
                $cfg['adapter'] = 'apcu';
            } else {
                $cfg['adapter'] = 'filesystem';
            }
        }

        $namespace = isset($cfg['options']) && isset($cfg['options']['namespace'])
            ? $cfg['options']['namespace']
            : '_default';

        try {
            $storage = $this->storageFactory->factory($cfg);
        } catch (ExceptionInterface $e) {
            if ($isAdapterComputed && 'filesystem' !== $cfg['adapter']) {
                // Fallback to 'filesystem' adapter if adapter was not explicitely defined in config
                trigger_error(
                    sprintf(
                        'Cache adapter instantiation failed, fallback to "filesystem" adapter. Error was "%s".',
                        $e->getMessage()
                    ),
                    E_USER_WARNING
                );
                $storage = $this->storageFactory->factory(
                    [
                        'adapter' => 'filesystem',
                        'options' => [
                            'namespace' => $namespace
                        ],
                    ]
                );
            } else {
                // Fallback to 'memory' adapter
                trigger_error(
                    sprintf(
                        'Cache adapter instantiation failed, fallback to "memory" adapter. Error was "%s".',
                        $e->getMessage()
                    ),
                    E_USER_WARNING
                );
                $storage = $this->storageFactory->factory(
                    [
                        'adapter' => 'memory',
                        'options' => [
                            'namespace' => $namespace
                        ],
                    ]
                );
            }
        }

        return new SimpleCache($storage, $this->cacheDir, !$skipIntegrityChecks);
    }

    /**
     * Check if adapter can be used without integrity checks.
     *
     * @param string $adapter
     *
     * @return boolean
     */
    private function canSkipIntegrityChecks(string $adapter)
    {
        // Adapter names can be written using case variations.
        // see Zend\Cache\Storage\AdapterPluginManager::$aliases
        $adapter = strtolower($adapter);

        switch ($adapter) {
            // Cache adapters that can share their data accross processes
            case 'dba':
            case 'ext_mongo_db':
            case 'extmongodb':
            case 'filesystem':
            case 'memcache':
            case 'memcached':
            case 'mongo_db':
            case 'mongodb':
            case 'redis':
                return true;
                break;

            // Cache adapters that cannot share their data accross processes
            case 'apc':
            case 'apcu':
            case 'memory':
            case 'session':
            // wincache activation uses different configuration variable for CLI and web server
            // so it may not be available for all contexts
            case 'win_cache':
            case 'wincache':
            // zend server adapters are not available for CLI context
            case 'zend_server_disk':
            case 'zendserverdisk':
            case 'zend_server_shm':
            case 'zendservershm':
            default:
                return false;
                break;
        }
    }
}
