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

namespace Glpi\Toolbox;

use Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

class ClassHandler
{
    /**
     * Get proper class name, i.e. with the correct case and in the correct namespace.
     *
     * @param string $classname
     *
     * @return string|null
     */
    public static function getProperClassname(string $classname): ?string
    {
        // Both potential classname (with or without namespace) are pointing to the same file, so it will trigger a
        // "Cannot declare class XXX, because the name is already in use" error if the "wrong" version is
        // required by the autoloader when the good version is already loaded.
        // i.e. `class_exists('Glpi\Computer')` will load `src/Computer.php` which may redeclare the `Computer class`
        //
        // To prevent this, we check existence of both without allowing the autoloader to be used.
        // If none exists yet, we try to trigger the autoloader to load the class file.
        //
        // Then we check again existence of both without allowing the autoloader to be used,
        // in order to find the good class to use.

        $classname = preg_replace('/\\\+/', '\\', $classname); // Unescape slashes (may trigger errors)

        if (($class_specs = isPluginItemType($classname)) !== false) {
            $plugin               = $class_specs['plugin'];
            $base_classname       = 'Plugin' . $plugin . str_replace('\\', '_', $class_specs['class']);
            $namespaced_classname = NS_PLUG . $plugin . '\\' . str_replace('_', '\\', $class_specs['class']);
        } else {
            $plugin = null;
            if (str_starts_with($classname, NS_GLPI)) {
                $base_classname       = str_replace('\\', '_', str_replace(NS_GLPI, '', $classname));
                $namespaced_classname = $classname;
            } else {
                $base_classname       = $classname;
                $namespaced_classname = NS_GLPI . str_replace('_', '\\', $classname);
            }
        }

        $base_classname = self::fixClassnameCase($base_classname, $plugin);
        $namespaced_classname = self::fixClassnameCase($namespaced_classname, $plugin);

        if (!class_exists($base_classname, false) && !class_exists($namespaced_classname, false)) {
            // Try to trigger loading using classname without namespace.
            if (class_exists($base_classname)) {
                // Class has been loaded without a namespace.
                // Check if classname was loaded from a GLPI source file.
                // If it is not the case, then the proper classname is probably the namespaced one.
                $reflection = new ReflectionClass($base_classname);
                $try_namespaced = $reflection->getFileName() === false
                    || !str_starts_with(realpath($reflection->getFileName()), realpath(GLPI_ROOT));
            } else {
                // Retry with namespaced classname if neither form has been loaded yet.
                $try_namespaced = !class_exists($namespaced_classname, false);
            }
            if ($try_namespaced) {
                class_exists($namespaced_classname); // Try to trigger loading using namespaced classname
            }
        }

        if (class_exists($namespaced_classname, false)) {
            return $namespaced_classname;
        }

        if (class_exists($base_classname, false)) {
            return $base_classname;
        }

        return null;
    }

    /**
     * Try to fix classname case.
     * PSR-4 loading requires classnames to be used with their correct case.
     *
     * @param string      $classname
     * @param string|null $plugin
     *
     * @return string
     */
    private static function fixClassnameCase(string $classname, ?string $plugin = null)
    {
        // If a class exists for this itemtype, just return the declared class name.
        $matches = preg_grep('/^' . preg_quote($classname) . '$/i', get_declared_classes());
        if (count($matches) === 1) {
            return current($matches);
        }

        static $files = [];

        $context = $plugin === null ? 'glpi-core' : strtolower($plugin);

        $namespace      = $plugin === null ? NS_GLPI : NS_PLUG . $plugin . '\\';
        $uses_namespace = preg_match('/^(' . preg_quote($namespace) . ')/i', $classname) === 1;

        if (!array_key_exists($context, $files)) {
            // Fetch filenames from "src" directory of context (GLPI core or given plugin).
            $files[$context] = [];

            $srcdir = ($context === 'glpi-core' ? GLPI_ROOT : Plugin::getPhpDir($context)) . '/src';
            if (!is_dir($srcdir)) {
                // Cannot search in files if dir not exists (can correspond to a deleted plugin)
                return $classname;
            }
            $files_iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcdir),
                RecursiveIteratorIterator::SELF_FIRST
            );
            /** @var SplFileInfo $file */
            foreach ($files_iterator as $file) {
                if (!$file->isReadable() || !$file->isFile() || '.php' === !$file->getExtension()) {
                    continue;
                }
                $relative_path = str_replace($srcdir . DIRECTORY_SEPARATOR, '', $file->getPathname());

                // Store into files list:
                // - key is the lowercased filename;
                // - value is the classname with correct case.
                $files[$context][strtolower($relative_path)] = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\',                ''],
                    $relative_path
                );
            }
        }

        $expected_lc_path = strtolower($classname) . '.php';
        if ($uses_namespace) {
            // File path does not contains PSR4 namespace prefix
            $expected_lc_path = str_ireplace($namespace, '', $expected_lc_path);
        }
        $expected_lc_path = str_replace('\\', DIRECTORY_SEPARATOR, $expected_lc_path); // Use platform directory separator

        if (array_key_exists($expected_lc_path, $files[$context])) {
            $classname = ($uses_namespace ? $namespace : '') . $files[$context][$expected_lc_path];
        }

        return $classname;
    }
}
