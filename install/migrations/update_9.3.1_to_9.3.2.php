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

/** @file
 * @brief
 */

/**
 * Update from 9.3.1 to 9.3.2
 *
 * @return bool for success (will die for most error)
 **/
function update931to932()
{
    /**
     * @var \DBmysql $DB
     * @var \Migration $migration
     */
    global $DB, $migration;

    $current_config   = Config::getConfigurationValues('core');
    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

   //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), '9.3.2'));
    $migration->setVersion('9.3.2');

    /** Clean rack/enclosure items corrupted relations */
    $corrupted_criteria = [
        'OR' => [
            'itemtype' => 0,
            'items_id' => 0,
        ],
    ];
    $DB->deleteOrDie(Item_Rack::getTable(), $corrupted_criteria);
    $DB->deleteOrDie(Item_Enclosure::getTable(), $corrupted_criteria);
    /** /Clean rack/enclosure items corrupted relations */

   // limit state visibility for enclosures and pdus
    $migration->addField('glpi_states', 'is_visible_enclosure', 'bool', [
        'value' => 1,
        'after' => 'is_visible_rack'
    ]);
    $migration->addField('glpi_states', 'is_visible_pdu', 'bool', [
        'value' => 1,
        'after' => 'is_visible_enclosure'
    ]);
    $migration->addKey('glpi_states', 'is_visible_enclosure');
    $migration->addKey('glpi_states', 'is_visible_pdu');

   // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
