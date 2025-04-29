<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Assets;

use Glpi\Application\Environment;
use Plugin;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Generates an import map for JavaScript modules with cache busting parameters
 */
class ImportMapGenerator
{
    /**
     * @var string
     */
    private $root_doc;

    /**
     * @var CacheInterface|null
     */
    private $cache;

    /**
     * @param string $root_doc Root document URL path
     * @param CacheInterface|null $cache Optional cache instance
     */
    public function __construct(string $root_doc, ?CacheInterface $cache = null)
    {
        $this->root_doc = $root_doc;
        $this->cache = $cache;
    }

    /**
     * Get the GLPI root directory
     *
     * @return string The GLPI root directory
     */
    protected function getGlpiRoot(): string
    {
        return GLPI_ROOT;
    }

    /**
     * Get the list of active plugins
     *
     * @return array Array of plugin names
     */
    protected function getPluginList(): array
    {
        return Plugin::getPlugins();
    }

    /**
     * Generate the import map data
     *
     * @return array The import map data
     */
    public function generate(): array
    {
        $should_use_cache = $this->cache !== null && !Environment::get()->shouldExpectResourcesToChange();

        if ($should_use_cache) {
            $cached_map = $this->cache->get('js_import_map');
            if ($cached_map !== null) {
                return $cached_map;
            }
        }

        $import_map = [
            'imports' => []
        ];

        // Scan GLPI core modules
        $this->addModulesToImportMap($import_map, $this->getGlpiRoot() . '/js/modules');

        // Scan plugin modules
        foreach ($this->getPluginList() as $plugin_key) {
            $plugin_dir = $this->getGlpiRoot() . '/plugins/' . $plugin_key;
            if (is_dir($plugin_dir . '/js/modules')) {
                $this->addModulesToImportMap($import_map, $plugin_dir . '/js/modules');
            }
        }

        if ($should_use_cache) {
            $this->cache->set('js_import_map', $import_map);
        }

        return $import_map;
    }

    /**
     * Add modules from a directory to the import map
     *
     * @param array $import_map Reference to the import map array
     * @param string $dir Directory to scan
     */
    private function addModulesToImportMap(array &$import_map, string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        // Determine if we're in a plugin directory and extract plugin name
        $plugin_prefix = '';
        if (preg_match('#' . preg_quote($this->getGlpiRoot() . '/plugins/', '#') . '([^/]+)#', $dir, $matches)) {
            $plugin_prefix = $matches[1] . '/';
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $file_path = $file->getPathname();
                $relative_path = str_replace($this->getGlpiRoot(), '', $file_path);

                // Calculate module name - remove module base dir and extension
                $module_path = str_replace($dir . '/', '', $file_path);
                $module_path = str_replace('\\', '/', $module_path); // Normalize for Windows paths
                $module_name = preg_replace('/\.js$/', '', $module_path);

                // Add plugin prefix for plugin modules
                $import_key = $plugin_prefix . $module_name;

                // Generate version parameter
                $version_param = $this->generateVersionParam($file_path);

                // Add to import map
                $import_map['imports'][$import_key] = $this->root_doc . $relative_path . '?v=' . $version_param;
            }
        }
    }

    /**
     * Generate a version parameter based on the file content
     *
     * @param string $file_path Path to the file
     * @return string Version parameter
     */
    private function generateVersionParam(string $file_path): string
    {
        if (!file_exists($file_path)) {
            return 'missing';
        }

        return substr(md5_file($file_path), 0, 8);
    }
}
