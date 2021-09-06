<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

if (!$DB->tableExists('glpi_cabletypes')) {
   $query = "CREATE TABLE `glpi_cabletypes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
   $DB->queryOrDie($query, "10.0 add table glpi_cabletypes");
}

if (!$DB->tableExists('glpi_cablestrands')) {
   $query = "CREATE TABLE `glpi_cablestrands` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
   $DB->queryOrDie($query, "10.0 add table glpi_cablestrands");
}

if (!$DB->tableExists('glpi_socketmodels')) {
   $query = "CREATE TABLE `glpi_socketmodels` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET= {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
   $DB->queryOrDie($query, "10.0 add table glpi_socketmodels");
}

if (!$DB->tableExists('glpi_cables')) {
   $query = "CREATE TABLE `glpi_cables` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `entities_id` int NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      `itemtype_endpoint_a` varchar(255) DEFAULT NULL,
      `itemtype_endpoint_b` varchar(255) DEFAULT NULL,
      `items_id_endpoint_a` int NOT NULL DEFAULT '0',
      `items_id_endpoint_b` int NOT NULL DEFAULT '0',
      `socketmodels_id_endpoint_a` int NOT NULL DEFAULT '0',
      `socketmodels_id_endpoint_b` int NOT NULL DEFAULT '0',
      `sockets_id_endpoint_a` int NOT NULL DEFAULT '0',
      `sockets_id_endpoint_b` int NOT NULL DEFAULT '0',
      `cablestrands_id` int NOT NULL DEFAULT '0',
      `color` varchar(255) DEFAULT NULL,
      `otherserial` varchar(255) DEFAULT NULL,
      `states_id` int NOT NULL DEFAULT '0',
      `users_id_tech` int NOT NULL DEFAULT '0',
      `cabletypes_id` int NOT NULL DEFAULT '0',
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `item_endpoint_a` (`itemtype_endpoint_a`,`items_id_endpoint_a`),
      KEY `item_endpoint_b` (`itemtype_endpoint_b`,`items_id_endpoint_b`),
      KEY `items_id_endpoint_b` (`items_id_endpoint_b`),
      KEY `items_id_endpoint_a` (`items_id_endpoint_a`),
      KEY `socketmodels_id_endpoint_a` (`socketmodels_id_endpoint_a`),
      KEY `socketmodels_id_endpoint_b` (`socketmodels_id_endpoint_b`),
      KEY `sockets_id_endpoint_a` (`sockets_id_endpoint_a`),
      KEY `sockets_id_endpoint_b` (`sockets_id_endpoint_b`),
      KEY `cablestrands_id` (`cablestrands_id`),
      KEY `states_id` (`states_id`),
      KEY `complete` (`entities_id`,`name`),
      KEY `is_recursive` (`is_recursive`),
      KEY `users_id_tech` (`users_id_tech`),
      KEY `cabletypes_id` (`cabletypes_id`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
   $DB->queryOrDie($query, "10.0 add table glpi_cables");

   $migration->addField('glpi_states', 'is_visible_cable', 'bool', [
      'value' => 1,
      'after' => 'is_visible_appliance'
   ]);
   $migration->addKey('glpi_states', 'is_visible_cable');
}

if (!$DB->tableExists('glpi_sockets') && $DB->tableExists('glpi_netpoints')) {

   //Rename table
   $migration->renameTable('glpi_netpoints', 'glpi_sockets');

   //add missing fields / keys
   $migration->addField('glpi_sockets', 'position', 'int', ['value' => '0', 'after' => 'name']);

   $migration->addfield('glpi_sockets', 'is_recursive', 'bool', ['value' => '0', 'after' => 'entities_id']);
   $migration->addKey('glpi_sockets', 'is_recursive');

   $migration->addField('glpi_sockets', 'socketmodels_id', 'int', ['after' => 'name']);
   $migration->addKey('glpi_sockets', 'socketmodels_id');

   $migration->addField("glpi_sockets", "wiring_side", "tinyint DEFAULT 1", ['after' => 'socketmodels_id']);
   $migration->addKey('glpi_sockets', 'wiring_side');

   $migration->addField('glpi_sockets', 'itemtype', 'string', ['after' => 'wiring_side']);
   $migration->addField('glpi_sockets', 'items_id', 'int', ['after' => 'itemtype']);
   $migration->addKey("glpi_sockets", ['itemtype','items_id'], 'item');

   $migration->addField('glpi_sockets', 'networkports_id', 'int', ['after' => 'items_id']);
   $migration->addKey('glpi_sockets', 'networkports_id');

   //migrate link between NetworkPort and Socket
   // BEFORE : supported by NetworkPortEthernet / NetworkPortFiberchannel with 'sockets_id' foreign key
   // AFTER  : supported by Socket with (itemtype, items_id, networkports_id)
   $classes = [NetworkPortEthernet::getType(), NetworkPortFiberchannel::getType()];
   foreach ($classes as $itemtype) {

      $item = new $itemtype();
      $datas = $item->find(['networkports_id'   => ['<>', 0],
                            'netpoints_id'      => ['<>', 0]]);

      foreach ($datas as $id => $values) {

         //Load NetworkPort to get associated item
         $networkport = new NetworkPort();
         if ($networkport->getFromDB($values['networkports_id'])) {
            $sockets_id = $values['netpoints_id'];
            $socket = new Socket();
            if ($socket->getFromDB($sockets_id)) {
               $socket->update([
                  'id'              => $sockets_id,
                  'position'        => $networkport->fields['logical_number'],
                  'itemtype'        => $networkport->fields['itemtype'],
                  'items_id'        => $networkport->fields['items_id'],
                  'networkports_id' => $networkport->getID()
               ]);
            }
         }
      }
   }

   //remove "useless "netpoints_id" field
   $migration->dropField('glpi_networkportethernets', 'netpoints_id');
   $migration->dropField('glpi_networkportfiberchannels', 'netpoints_id');
   $migration->dropKey('glpi_networkportethernets', 'netpoint');
   $migration->dropKey('glpi_networkportfiberchannels', 'netpoint');
}

if (!$DB->tableExists('glpi_networkportfiberchanneltypes')) {
   $query = "CREATE TABLE `glpi_networkportfiberchanneltypes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
   $DB->queryOrDie($query, "10.0 add table glpi_networkportfiberchanneltypes");
}

$migration->addField('glpi_networkportfiberchannels', 'networkportfiberchanneltypes_id', 'int', ['after' => 'items_devicenetworkcards_id']);
$migration->addKey('glpi_networkportfiberchannels', 'networkportfiberchanneltypes_id', 'type');

$ADDTODISPLAYPREF['Socket'] = [5, 6, 9, 8, 7];
$ADDTODISPLAYPREF['Cable'] = [4, 31, 6, 15, 24, 8, 10, 13, 14];

//rename profilerights values ('netpoint' to 'cable_management')
$migration->addPostQuery(
   $DB->buildUpdate(
      'glpi_profilerights',
      ['name' => 'cable_management'],
      ['name' => 'netpoint']
   )
);