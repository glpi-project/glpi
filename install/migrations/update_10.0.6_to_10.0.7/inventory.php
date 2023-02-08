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

/**
 * @var DB $DB
 * @var Migration $migration
 */
$migration->addField('glpi_unmanageds', 'remote_addr', 'string');

$assets = ['Computer', 'Phone', 'Printer', 'NetworkEquipment'];

foreach ($assets as $itemtype) {
    if ($DB->fieldExists($itemtype::getTable(), 'remote_addr')) {
        continue;
    }
    $migration->addField($itemtype::getTable(), 'remote_addr', 'string');
    //try to find unique NetworkPortAggregate, not dynamic for an asset
    //with NetworkName and then with IPAddress
    $iterator = $DB->request([
        'SELECT'       => [
            'COUNT'  => '* AS cpt',
            'netports.id',
            'netports.itemtype',
            'netports.items_id',
            'ips.name AS ipaddress',
        ],
        'FROM'         => 'glpi_networkports AS netports',
        'INNER JOIN'   => [
            'glpi_networknames' . ' AS netnames' => [
                'ON'  => [
                    'netnames'  => 'items_id',
                    'netports'  => 'id', [
                        'AND' => [
                            'netnames.itemtype'  => 'NetworkPort'
                        ]
                    ]
                ]
            ],
            'glpi_ipaddresses' . ' AS ips' => [
                'ON'  => [
                    'ips'       => 'items_id',
                    'netnames'  => 'id', [
                        'AND' => [
                            'ips.itemtype' => 'NetworkName'
                        ]
                    ]
                ]
            ]
        ],
        'WHERE'        => [
            'netports.is_dynamic'  => 0,
            'netports.instantiation_type'  => 'NetworkPortAggregate',
            'netports.itemtype'  => $itemtype
        ],
        'GROUPBY'      => ['netports.itemtype', 'netports.items_id'],
        'HAVING'       => ['cpt' => 1]
    ]);

    foreach ($iterator as $data) {
         //update 'remote_addr' with ipaddress found
         $migration->addPostQuery(
             $DB->buildUpdate(
                 $data['itemtype']::getTable(),
                 [
                     'remote_addr' => $data['ipaddress'],
                 ],
                 [
                     'id' => $data['items_id'],
                 ]
             )
         );
    }
}
