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

namespace Glpi\Application;

use Plugin;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Path;

use function Safe\preg_replace;

/**
 * Generates an import map for JavaScript modules with cache busting parameters
 *
 * @final
 */
class ImportMapGenerator
{
    /**
     * @var ImportMapGenerator|null
     */
    private static $instance = null;

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
     * @var array<string, array<string>> Dictionary of plugin module paths by plugin key
     */
    private $registered_plugin_modules = [];

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
     * Get the singleton instance of the generator
     *
     * @return ImportMapGenerator
     */
    public static function getInstance(): ImportMapGenerator
    {
        global $CFG_GLPI, $GLPI_CACHE;

        if (self::$instance === null) {
            self::$instance = new self($CFG_GLPI['root_doc'], GLPI_ROOT, $GLPI_CACHE);
        }

        return self::$instance;
    }

    /**
     * Register a module path for a specific plugin
     *
     * @param string $plugin_key The plugin key
     * @param string $path The path relative to the plugin directory
     * @return void
     */
    public function registerModulesPath(string $plugin_key, string $path): void
    {
        if (!isset($this->registered_plugin_modules[$plugin_key])) {
            $this->registered_plugin_modules[$plugin_key] = [];
        }

        $this->registered_plugin_modules[$plugin_key][] = $path;
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
        $import_map = [
            'imports' => [],
        ];

        // Try to get GLPI core modules from cache first
        $core_cache_key = 'js_import_map_core_' . \sha1($this->root_doc);
        $core_modules = [];

        if ($should_use_cache) {
            $core_modules = $this->cache->get($core_cache_key);
        }

        if (!$core_modules) {
            // Scan GLPI core directories
            $core_modules = ['imports' => []];
            $this->addModulesToImportMap($core_modules, $this->glpi_root . '/js/modules', $this->glpi_root);
            $this->addModulesToImportMap($core_modules, $this->glpi_root . '/public/js/modules', $this->glpi_root);
            $this->addModulesToImportMap($core_modules, $this->glpi_root . '/public/lib', $this->glpi_root);
            $this->addModulesToImportMap($core_modules, $this->glpi_root . '/public/build', $this->glpi_root);

            // Cache core modules
            if ($should_use_cache) {
                $this->cache->set($core_cache_key, $core_modules);
            }
        }

        // Add core modules to the import map
        $import_map['imports'] = array_merge($import_map['imports'], $core_modules['imports']);

        // Process plugin modules
        foreach ($this->getPluginDirList() as $plugin_dir) {
            $plugin_key = Path::getFilenameWithoutExtension($plugin_dir);

            if (
                isset($this->registered_plugin_modules[$plugin_key])
                && !empty($this->registered_plugin_modules[$plugin_key])
            ) {
                $plugin_cache_key = 'js_import_map_plugin_' . $plugin_key . '_' . \sha1($this->root_doc);
                $plugin_modules = null;

                // Try to get plugin modules from cache
                if ($should_use_cache) {
                    $plugin_modules = $this->cache->get($plugin_cache_key);
                }

                if (!$plugin_modules) {
                    $plugin_modules = ['imports' => []];

                    foreach ($this->registered_plugin_modules[$plugin_key] as $module_path) {
                        $full_path = $plugin_dir . '/' . ltrim($module_path, '/');
                        if (is_dir($full_path)) {
                            $this->addModulesToImportMap(
                                $plugin_modules,
                                $full_path,
                                $plugin_dir,
                                $plugin_key
                            );
                        } else {
                            trigger_error(sprintf('`%s` is not a valid directory.', $full_path), E_USER_WARNING);
                        }
                    }

                    // Cache plugin modules
                    if ($should_use_cache) {
                        $this->cache->set($plugin_cache_key, $plugin_modules);
                    }
                }

                // Add plugin modules to the import map
                $import_map['imports'] = array_merge($import_map['imports'], $plugin_modules['imports']);
            }
        }

        return $import_map;
    }

    /**
     * Add modules from a directory to the import map
     *
     * @param array{imports: array<string, string>} $import_map Reference to the import map array
     * @param string $dir Directory to scan
     * @param string $base_path Base path for generating relative paths
     * @param string|null $plugin_key Plugin key for module prefixing (null for core modules)
     */
    private function addModulesToImportMap(
        array &$import_map,
        string $dir,
        string $base_path,
        ?string $plugin_key = null
    ): void {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'js') {
                $file_path = $file->getPathname();

                // Make the path relative and remove the `public/` prefix
                $relative_path = Path::makeRelative($file_path, $base_path);
                $relative_path = preg_replace('~^public/~', '', $relative_path);
                if ($plugin_key !== null) {
                    $relative_path = sprintf('plugins/%s/', $plugin_key) . $relative_path;
                }

                // Get the path that would be used for a GLPI located at the server root dir (e.g. `/js/modules/Foo.js`)
                $clean_path = '/' . $relative_path;

                // Generate version parameter
                $version_param = $this->generateVersionParam($file_path);

                // Add to import map
                $import_map['imports'][$clean_path] = $this->root_doc . $clean_path . '?v=' . $version_param;
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
