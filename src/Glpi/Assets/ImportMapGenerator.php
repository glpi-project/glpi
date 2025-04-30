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
use Symfony\Component\Filesystem\Path;

/**
 * Generates an import map for JavaScript modules with cache busting parameters
 *
 * @final
 */
class ImportMapGenerator
{
    /**
     * @var string
     */
    private $root_doc;

    /**
     * @var string
     */
    private $glpi_root;

    /**
     * @var CacheInterface|null
     */
    private $cache;

    /**
     * @param string $root_doc Root document URL path
     * @param CacheInterface|null $cache Optional cache instance
     */
    public function __construct(string $root_doc, string $glpi_root, ?CacheInterface $cache = null)
    {
        $this->root_doc  = $root_doc;
        $this->glpi_root = $glpi_root;
        $this->cache     = $cache;
    }

    /**
     * Get the list of active plugins
     *
     * @return array Array of plugin names
     */
    protected function getPluginDirList(): array
    {
        return array_map(
            fn($plugin_key) => Plugin::getPhpDir($plugin_key, true),
            Plugin::getPlugins()
        );
    }

    /**
     * Generate the import map data
     *
     * @return array{imports: array<string, string>} The import map data with module names as keys and URLs as values
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
            'imports' => [],
        ];

        // Scan GLPI core directories
        $this->addModulesToImportMap($import_map, $this->glpi_root . '/js/modules');
        $this->addModulesToImportMap($import_map, $this->glpi_root . '/public/lib');
        $this->addModulesToImportMap($import_map, $this->glpi_root . '/public/build');

        // Scan plugin directories
        foreach ($this->getPluginDirList() as $plugin_dir) {
            // Check all the possible module directories for plugins
            $plugin_module_dirs = [
                $plugin_dir . '/js/modules',
                $plugin_dir . '/public/lib',
                $plugin_dir . '/public/build',
            ];

            foreach ($plugin_module_dirs as $module_dir) {
                if (is_dir($module_dir)) {
                    $this->addModulesToImportMap(
                        $import_map,
                        $module_dir,
                        Path::getFilenameWithoutExtension($plugin_dir) . '/'
                    );
                }
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
     * @param array{imports: array<string, string>} $import_map Reference to the import map array
     * @param string $dir Directory to scan
     * @param string $path_prefix Path prefix to use for the module names
     */
    private function addModulesToImportMap(array &$import_map, string $dir, string $path_prefix = ""): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $file_path = $file->getPathname();
                $relative_path = Path::makeRelative($file_path, $this->glpi_root);

                // Calculate module name - remove module base dir and extension
                $module_path = Path::makeRelative($file_path, $dir);
                $module_name = preg_replace('/\.js$/', '', $module_path);

                // Add plugin prefix for plugin modules
                $import_key = $path_prefix . $module_name;

                // Generate version parameter
                $version_param = $this->generateVersionParam($file_path);

                // Add to import map
                $import_map['imports'][$import_key] = $this->root_doc . '/' . $relative_path . '?v=' . $version_param;
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

        return hash_file('CRC32c', $file_path);
    }
}
