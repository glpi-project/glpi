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

use Safe\Exceptions\FilesystemException;

use function Safe\fclose;
use function Safe\fopen;
use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\realpath;
use function Safe\unlink;

final class Filesystem
{
    /**
     * Checks if the file with given path can be written.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function canWriteFile(string $path): bool
    {
        if (file_exists($path)) {
            return is_writable($path);
        }

        // If the file does not exist, try to create it.
        try {
            $file = @fopen($path, 'c');
        } catch (FilesystemException $e) {
            return false;
        }
        @fclose($file);

        // Remove the file, as presence of an empty file may not be handled properly.
        @unlink($path);

        return true;
    }

    /**
     * Checks if the files with given paths can be written.
     *
     * @param string[] $paths
     *
     * @return bool
     */
    public static function canWriteFiles(array $paths): bool
    {
        foreach ($paths as $path) {
            if (!self::canWriteFile($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the given file path is safe.
     *
     * @param string $path
     * @param string $restricted_directory
     *
     * @return bool
     */
    public static function isFilepathSafe(string $path, ?string $restricted_directory = null): bool
    {
        $parsed_scheme = parse_url($path, PHP_URL_SCHEME);

        if ($parsed_scheme === 'file') {
            // If scheme is `file://`, parse the path again to validate that it does not contains itself
            // an unexpected scheme.
            $path = parse_url($path, PHP_URL_PATH);
            $parsed_scheme = parse_url($path, PHP_URL_SCHEME);
        }

        if ($parsed_scheme !== null && preg_match('/^[a-z]$/i', $parsed_scheme) !== 1) {
            // As soon as the path contains a scheme, it is not considered as safe,
            // unless the scheme is 1 letter (corresponds to a drive letter on Windows system).
            return false;
        }

        if ($restricted_directory === null) {
            // All directories are allowed.
            return true;
        }

        $restricted_directory = self::normalizePath($restricted_directory);
        if (!str_ends_with($restricted_directory, '/')) {
            // Ensure directory ends with a `/`, to prevent false positives:
            // - /path/to/dir/file is inside /path/to/dir
            // - /path/to/dir_file is not inside /path/to/dir
            $restricted_directory .= '/';
        }

        return str_starts_with(self::normalizePath($path), $restricted_directory);
    }

    /**
     * Normalize a path, to make comparisons and relative paths computation easier.
     *
     * @param string $path
     * @return string
     */
    private static function normalizePath(string $path): string
    {
        try {
            // Use realpath if possible (not always possible, for instance when file not exists).
            $path = realpath($path);
        } catch (FilesystemException $e) {
            //no error handling here, just continue with the original path
        }

        // Normalize all directory separators to `/`.
        $path = preg_replace('/\\\/', '/', $path);
        return $path;
    }
}
