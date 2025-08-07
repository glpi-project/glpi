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

/**
 * Update from 9.1 to 9.1.1
 *
 * @return bool
 **/
function update910to911()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;

    $migration->setVersion('9.1.1');

    // rectify missing right in 9.1 update
    if (countElementsInTable("glpi_profilerights", ['name' => 'license']) == 0) {
        $prights = $DB->request(['FROM' => 'glpi_profilerights', 'WHERE' => ['name' => 'software']]);
        foreach ($prights as $profrights) {
            $DB->insert(
                "glpi_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "license",
                    'rights'       => $profrights['rights'],
                ]
            );
        }
    }

    //put you migration script here

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
