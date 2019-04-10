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
     * @param string $cacheDir      Cache directory.
     * @param string $cacheUniqId   Cache unique identifier.
     */
    public function __construct(string $cacheDir, string $cacheUniqId)
    {
        self::$cacheDir = $cacheDir;
        self::$cacheUniqId = $cacheUniqId;
    }

    public static function factory($cfg)
    {
        if (!array_key_exists('options', $cfg) || !is_array($cfg['options'])) {
            $cfg['options'] = [];
        }

        // Add unique id to namespace
        $namespace = isset($cfg['options']['namespace']) ? $cfg['options']['namespace'] : '_default';
        $namespace .= (empty(self::$cacheUniqId) ? '' : '_' . self::$cacheUniqId);
        $cfg['options']['namespace'] = $namespace;

        // Handle pathname for dba adapter
        if ('dba' === $cfg['adapter']) {
            // Assign default value for pathname
            if (!isset($cfg['options']['pathname'])) {
                $cfg['options']['pathname'] = self::$cacheDir . '/' . $namespace . '.data';
            }
        }

        // Handle cache dir for filesystem adapter
        if ('filesystem' === $cfg['adapter']) {
            // Assign default value for cache dir
            if (!isset($cfg['options']['cache_dir'])) {
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

        return parent::factory($cfg);
    }
}
