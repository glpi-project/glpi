<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class Filesystem
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

        // If the file does not exists, try to create it.
        $file = @fopen($path, 'c');
        if ($file === false) {
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
     * @param string[] $path
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
}
