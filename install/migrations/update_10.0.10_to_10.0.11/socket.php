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

/*use Glpi\Socket;
use Glpi\Toolbox\Sanitizer;

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

/*$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_sockets')) {
   //create socket table
    $query = "CREATE TABLE `glpi_sockets` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `position` int NOT NULL DEFAULT '0',
      `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `socketmodels_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `wiring_side` tinyint DEFAULT '1',
      `itemtype` varchar(255) DEFAULT NULL,
      `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `networkports_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `socketmodels_id` (`socketmodels_id`),
      KEY `location_name` (`locations_id`,`name`),
      KEY `item` (`itemtype`,`items_id`),
      KEY `networkports_id` (`networkports_id`),
      KEY `wiring_side` (`wiring_side`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQueryOrDie($query, "10.0 add table glpi_sockets");
}

if ($DB->tableExists('glpi_netpoints')) {

    $criteria = [
        'SELECT' => [
            "glpi_netpoints.id",
            "glpi_netpoints.name",
            "glpi_netpoints.comment",
            "glpi_netpoints.entities_id",
            "glpi_netpoints.locations_id",
            "glpi_netpoints.date_creation",
            "glpi_netpoints.date_mod",
        ],
        'FROM'   => 'glpi_netpoints',
    ];


    $iterator = $DB->request($criteria);
    foreach ($iterator as $data) {
        $socket = new Socket();

        if ($data['locations_id'] == '-1' || $data['locations_id'] == null || $data['locations_id'] == 'null') {
            $data['locations_id'] = 0;
        }

        //try to load related networkportethernet
        $data['networkports_id'] = 0;
        $np_ethernet = new NetworkPortEthernet();
        if (
            $np_ethernet->getFromDBByCrit([
                'netpoints_id' => $data['id'],
            ])
        ) {
            $data['networkports_id'] = $np_ethernet->getField('networkports_id');
        }

        if (!$data['networkports_id']) {
            //try to load related networkportethernet
            $np_fiber = new NetworkPortFiberchannel();
            if (
                $np_fiber->getFromDBByCrit([
                    'netpoints_id' => $data['id'],
                ])
            ) {
                $data['networkports_id'] = $np_fiber->getField('networkports_id');
            }
        }



        $data['itemtype'] = null;
        $data['items_id'] = null;
        $data['logical_number'] = 0;
        if ($data['networkports_id']) {
            $np = new NetworkPort();
            $np->getFromDB($data['networkports_id']);
            $data['itemtype'] = $np->fields['itemtype'];
            $data['items_id'] = $np->fields['items_id'];
            $data['logical_number'] = $np->fields['logical_number'] ?? 0;
        }

        //default value
        if ($data['itemtype'] == null || $data['itemtype'] == '' || is_null($data['itemtype'])) {
            $data['itemtype'] = 'Computer';
            $data['items_id'] = 0;
        }

        $name = Sanitizer::sanitize($data['name']);

        if (empty($name) || $name == '') {
            $name = "Empty name";
        }

        $input = [
            'name'            => $name,
            'locations_id'    => $data['locations_id'] ?? 0,
            'position'        => $data['logical_number'],
            'itemtype'        => $data['itemtype'],
            'items_id'        => $data['items_id'],
            'comment'         => Sanitizer::sanitize($data['comment']),
            'networkports_id' => $data['networkports_id'],
            'date_creation'   => $data['date_creation'],
            'date_mod'        => $data['date_mod'],
        ];

        $socket->add($input);
    }
}

   //remove "useless "netpoints_id" field
   //$migration->dropField('glpi_networkportethernets', 'netpoints_id');
   //$migration->dropField('glpi_networkportfiberchannels', 'netpoints_id');
*/
