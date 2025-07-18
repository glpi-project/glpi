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
 * @var DBmysql $DB
 * @var Migration $migration
 */
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_dropdownvisibilities')) {
    $known_visibilities = [
        'computer',
        'monitor',
        'networkequipment',
        'peripheral',
        'phone',
        'printer',
        'softwareversion',
        'softwarelicense',
        'line',
        'certificate',
        'rack',
        'passivedcequipment',
        'enclosure',
        'pdu',
        'cluster',
        'contract',
        'appliance',
        'databaseinstance',
        'cable',
        'unmanaged',
    ];

    $query = "CREATE TABLE `glpi_dropdownvisibilities` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `itemtype` varchar(100) NOT NULL DEFAULT '',
        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `visible_itemtype` varchar(100) NOT NULL DEFAULT '',
        `is_visible` tinyint NOT NULL DEFAULT '1',
        PRIMARY KEY (`id`),
        KEY `visible_itemtype` (`visible_itemtype`),
        KEY `item` (`itemtype`,`items_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);

    $states = $DB->request(['FROM' => 'glpi_states']);
    foreach ($states as $state) {
        $insert_data = [
            'itemtype' => 'State',
            'items_id' => $state['id'],
        ];

        foreach ($known_visibilities as $known_visibility) {
            if (isset($state['is_visible_' . $known_visibility])) {
                $insert_data['visible_itemtype'] = $known_visibility;
                $insert_data['is_visible'] = $state['is_visible_' . $known_visibility];
                $DB->doQuery($DB->buildInsert('glpi_dropdownvisibilities', $insert_data));
            }
        }
    }

    foreach ($known_visibilities as $known_visibility) {
        if ($DB->fieldExists('glpi_states', 'is_visible_' . $known_visibility)) {
            $migration->dropField('glpi_states', 'is_visible_' . $known_visibility);
        }
    }
}
$migration->addInfoMessage(
    'States dropdown in devices items forms are now filtered, and, by default, existing states are not visible.'
);

// Add missing field
$migration->addField('glpi_items_devicecameras', 'states_id', 'fkey');
$migration->addKey('glpi_items_devicecameras', 'states_id');

// Drop unexpected fields
$migration->dropField('glpi_devicegenerics', 'states_id');
$migration->dropField('glpi_devicesensors', 'states_id');
