<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Toolbox;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

use function Safe\preg_match;

final class ArrayPathAccessor
{
    /**
     * Check an element exists within the array at the given path.
     * @param array $array The array to check
     * @param string $path The path to the element
     * @param string $path_delimiter The delimiter used in the path
     * @return bool
     */
    public static function hasElementByArrayPath(array $array, string $path, string $path_delimiter = '.'): bool
    {
        if (empty($path)) {
            return false;
        }
        $path_array = explode($path_delimiter, $path);
        $current = $array;
        foreach ($path_array as $key) {
            if (!isset($current[$key])) {
                return false;
            }
            $current = $current[$key];
        }
        return true;
    }

    /**
     * Get an element within the array at the given path.
     * @param array $array The array to check
     * @param string $path The path to the element
     * @param string $path_delimiter The delimiter used in the path
     * @return mixed
     */
    public static function getElementByArrayPath(array $array, string $path, string $path_delimiter = '.'): mixed
    {
        if (empty($path)) {
            return null;
        }
        $path_array = explode($path_delimiter, $path);
        $current = $array;
        foreach ($path_array as $key) {
            if (!isset($current[$key])) {
                return null;
            }
            $current = $current[$key];
        }
        return $current;
    }

    /**
     * Set an element within the array at the given path.
     * @param array $array The array to check
     * @param string $path The path to the element
     * @param mixed $value The value to set
     * @param string $path_delimiter The delimiter used in the path
     * @return void
     */
    public static function setElementByArrayPath(array &$array, string $path, mixed $value, string $path_delimiter = '.'): void
    {
        if (empty($path)) {
            return;
        }
        $path_array = explode($path_delimiter, $path);
        $current = &$array;
        foreach ($path_array as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    /**
     * Get all paths of an array matching a regex pattern
     * @param array $array The array to check
     * @param string $regex The regex pattern to match against the full paths
     * @param string $path_delimiter The delimiter used in the path
     * @return array
     */
    public static function getArrayPaths(array $array, string $regex = '/.*/', string $path_delimiter = '.'): array
    {
        // Get all paths including intermediate paths
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $leafValue) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            // Implode keys to get all paths. For example, ['a', 'b', 'c'] will give ['a', 'a.b', 'a.b.c']
            $temp = '';
            foreach ($keys as $key) {
                $temp .= $path_delimiter . $key;
                $path = substr($temp, 1);
                if (!empty($path) && preg_match($regex, $path) === 1) {
                    $paths[] = $path;
                }
            }
        }
        return array_unique($paths);
    }
}
