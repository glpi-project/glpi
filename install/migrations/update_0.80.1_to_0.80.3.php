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

/**
 * Update from 0.80.1 to 0.80.3
 *
 * @return bool for success (will die for most error)
 **/
function update0801to0803()
{
    /**
     * @var \Migration $migration
     */
    global $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '0.80.3'));
    $migration->setVersion('0.80.3');

    $migration->changeField("glpi_fieldunicities", 'fields', 'fields', "text");

    $migration->dropKey('glpi_ocslinks', 'unicity');
    $migration->migrationOneTable('glpi_ocslinks');
    $migration->addKey(
        "glpi_ocslinks",
        ['ocsid', 'ocsservers_id'],
        "unicity",
        "UNIQUE"
    );

   // must always be at the end
    $migration->executeMigration();

    return $updateresult;
}
