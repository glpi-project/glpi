<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

class DatabaseSchema
{
    /**
     * Return empty schema file path for given version.
     *
     * @param string $version
     *
     * @return null|string
     */
    public static function getEmptySchemaPath(string $version): ?string
    {
        $normalized_version = VersionParser::getNormalizedVersion($version, false, true);
        $latest_version     = VersionParser::getNormalizedVersion(GLPI_VERSION, false);

        $schema_path = $normalized_version === $latest_version
            ? sprintf('%s/install/mysql/glpi-empty.sql', realpath(GLPI_ROOT))
            : sprintf('%s/install/mysql/glpi-%s-empty.sql', realpath(GLPI_ROOT), $normalized_version);

        return file_exists($schema_path) ? $schema_path : null;
    }
}
