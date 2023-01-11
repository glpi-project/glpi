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

namespace Glpi\System;

/**
 * @since 9.5.4
 */
class Variables
{
    /**
     * Returns list of constants corresponding to directories that contains custom data.
     *
     * @return string[]
     */
    public static function getDataDirectoriesConstants(): array
    {
        return [
            'GLPI_CACHE_DIR',
            'GLPI_CONFIG_DIR',
            'GLPI_CRON_DIR',
            'GLPI_DOC_DIR',
            'GLPI_DUMP_DIR',
            'GLPI_GRAPH_DIR',
            'GLPI_LOCK_DIR',
            'GLPI_LOG_DIR',
            'GLPI_PICTURE_DIR',
            'GLPI_PLUGIN_DOC_DIR',
            'GLPI_RSS_DIR',
            'GLPI_SESSION_DIR',
            'GLPI_TMP_DIR',
            'GLPI_UPLOAD_DIR',
        ];
    }

    /**
     * Returns list of directories that contains custom data.
     *
     * @return string[]
     */
    public static function getDataDirectories()
    {
        return array_map(
            function (string $constant) {
                return constant($constant);
            },
            self::getDataDirectoriesConstants()
        );
    }
}
