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

use Zend\Cache\StorageFactory;

/**
 * Glpi cache storage factory.
 *
 * @since 10.0.0
 */
class CacheStorageFactory extends StorageFactory
{
    /**
     * Cache directory.
     *
     * @var string
     */
    private static $cacheDir;

    /**
     * Cache unique identifier.
     *
     * @var string
     */
    private static $cacheUniqId;

    /**
     * @param string $cacheDir     Cache directory.
     * @param string $cacheUniqId  Cache unique identifier.
     */
    public function __construct(string $cacheDir, string $cacheUniqId)
    {
        self::$cacheDir = $cacheDir;
        self::$cacheUniqId = $cacheUniqId;
    }

    public static function factory($cfg)
    {
        // Compute prefered adapter if 'auto' value or no value is used
        if (!isset($cfg['adapter']) || 'auto' === $cfg['adapter']) {
            if (function_exists('wincache_ucache_add')) {
                $cfg['adapter'] = 'wincache';
            } elseif (function_exists('apcu_fetch')) {
                $cfg['adapter'] = 'apcu';
            } else {
                $cfg['adapter'] = 'filesystem';
                if (!array_key_exists('plugins', $cfg)) {
                    $cfg['plugins'] = [
                        'serializer'
                    ];
                } elseif (!in_array('serializer', $cfg['plugins'])) {
                    $cfg['plugins'][] = 'serializer';
                }
            }
        }

        // Add unique id to namespace
        if (!array_key_exists('options', $cfg) || !is_array($cfg['options'])) {
            $cfg['options'] = [];
        }
        $baseNamespace = isset($cfg['options']['namespace']) ? $cfg['options']['namespace'] : '_default';
        $cfg['options']['namespace'] = $baseNamespace . (empty(self::$cacheUniqId) ? '' : '_' . self::$cacheUniqId);

        // Handle pathname for dba adapter
        if ('dba' === $cfg['adapter']) {
            // Assign default value for pathname
            if (!isset($cfg['options']['pathname'])) {
                $namespace = $cfg['options']['namespace'];
                $cfg['options']['pathname'] = self::$cacheDir . '/' . $namespace . '.data';
            }
        }

        // Handle cache dir for filesystem adapter
        if ('filesystem' === $cfg['adapter']) {
            // Assign default value for cache dir
            if (!isset($cfg['options']['cache_dir'])) {
                $namespace = $cfg['options']['namespace'];
                $cfg['options']['cache_dir'] = self::$cacheDir . '/' . $namespace;
            }

            // Create cache dir if not existing
            if (!is_dir($cfg['options']['cache_dir'])
               && !@mkdir($cfg['options']['cache_dir'], 0700, true)) {
                trigger_error(
                    sprintf('Cannot create "%s" cache directory.', $cfg['options']['cache_dir']),
                    E_USER_WARNING
                );
            }
        }

        // Some know adapters require data serialization plugin, force its presence
        if (in_array($cfg['adapter'], ['dba', 'filesystem', 'redis'])) {
            if (!array_key_exists('plugins', $cfg) || !is_array($cfg['plugins'])) {
                $cfg['plugins'] = [
                    'serializer'
                ];
            } elseif (!in_array('serializer', $cfg['plugins'])) {
                $cfg['plugins'][] = 'serializer';
            }
        }

        try {
            return parent::factory($cfg);
        } catch (\Exception $e) {
            if ('filesystem' !== $cfg['adapter']) {
                // Fallback to 'filesystem' adapter
                trigger_error(
                    sprintf(
                        'Cache adapter instantiation failed, fallback to "filesystem" adapter. Error was "%s".',
                        $e->getMessage()
                    ),
                    E_USER_WARNING
                );
                return self::factory(
                    [
                        'adapter' => 'filesystem',
                        'options' => [
                            'namespace' => $baseNamespace . '_fallback'
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
                return self::factory(
                    [
                        'adapter' => 'memory',
                        'options' => [
                            'namespace' => $baseNamespace . '_fallback'
                        ],
                    ]
                );
            }
        }
    }
}
