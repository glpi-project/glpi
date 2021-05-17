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

if (!$DB->tableExists('glpi_networkportbnctypes')) {
   $query = "CREATE TABLE `glpi_networkportbnctypes` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_networkportbnctypes");
}

if (!$DB->tableExists('glpi_networkportbncs')) {
   $query = "CREATE TABLE `glpi_networkportbncs` (
      `id` int NOT NULL AUTO_INCREMENT,
      `networkports_id` int NOT NULL DEFAULT '0',
      `items_devicenetworkcards_id` int NOT NULL DEFAULT '0',
      `netpoints_id` int NOT NULL DEFAULT '0',
      `networkportbnctypes_id` int NOT NULL DEFAULT '0',
      `speed` int NOT NULL DEFAULT '10' COMMENT 'Mbit/s: 10, 100, 1000, 10000',
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `networkports_id` (`networkports_id`),
      KEY `card` (`items_devicenetworkcards_id`),
      KEY `netpoint` (`netpoints_id`),
      KEY `type` (`networkportbnctypes_id`),
      KEY `speed` (`speed`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_networkportbncs");
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
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_networkportfiberchanneltypes");

   $migration->addField('glpi_networkportfiberchannels', 'networkportfiberchanneltypes_id', 'int', [
      'after' => 'sockets_id'
   ]);

   $migration->addKey('glpi_networkportfiberchannels', 'networkportfiberchanneltypes_id', 'type');
}


if (!$DB->tableExists('glpi_sockets') && $DB->tableExists('glpi_netpoints')) {
   $migration->renameTable('glpi_netpoints', 'glpi_sockets');

   $migration->dropKey('glpi_networkportethernets', 'netpoint');
   $migration->changeField(
      'glpi_networkportethernets',
      'netpoints_id',
      'sockets_id',
      'integer'
   );
   $migration->addKey('glpi_networkportethernets', 'sockets_id', 'socket');

   $migration->dropKey('glpi_networkportbncs', 'netpoint');
   $migration->changeField(
      'glpi_networkportbncs',
      'netpoints_id',
      'sockets_id',
      'integer'
   );
   $migration->addKey('glpi_networkportbncs', 'sockets_id', 'socket');

   $migration->dropKey('glpi_networkportfiberchannels', 'netpoint');
   $migration->changeField(
      'glpi_networkportfiberchannels',
      'netpoints_id',
      'sockets_id',
      'integer'
   );
   $migration->addKey('glpi_networkportfiberchannels', 'sockets_id', 'socket');
}

if (!$DB->tableExists('glpi_connectormodels')) {
   $query = "CREATE TABLE `glpi_connectormodels` (
      `id` int NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET= {$default_charset} COLLATE = {$default_collation};";
   $DB->queryOrDie($query, "10.0 add table glpi_connectormodels");

   $migration->addField('glpi_sockets', 'connectormodels_id', 'int', [
      'after' => 'name'
   ]);
   $migration->addKey('glpi_sockets', 'connectormodels_id');

   $migration->addField("glpi_sockets", "wiring_side", "tinyint DEFAULT 0", [
      'after' => 'connectormodels_id'
   ]);
   $migration->addKey('glpi_sockets', 'wiring_side');

   $migration->addField('glpi_sockets', 'itemtype', 'string', [
      'after' => 'wiring_side'
   ]);

   $migration->addField('glpi_sockets', 'items_id', 'int', [
      'after' => 'itemtype'
   ]);
   $migration->addKey("glpi_sockets", ['itemtype','items_id'], 'item');


   $migration->addField('glpi_sockets', 'networkports_id', 'int', [
      'after' => 'items_id'
   ]);
   $migration->addKey('glpi_sockets', 'networkports_id');

   //migrate link between NetworkPort and Socket (with sockets_id on NetworkPortEthernet / NetworkPortFiberchannel)
   //to link between NetworkPort and Socket (link is now on socket side (itemtype, items_id, networkports_id))
   $classes = [NetworkPortEthernet::getType(), NetworkPortFiberchannel::getType()];
   foreach ($classes as $itemtype) {

      $item = new $itemtype();
      $datas = $item->find(['networkports_id' => ['<>', 0]]);

      foreach ($datas as $id => $values) {
         $sockets_id = $values['sockets_id'];
         $socket = new Socket();

         $networkport = new NetworkPort();
         $networkport->getFromDB($socket->fields['networkports_id']);


         $socket->getFromDB($values['sockets_id']);
         $socket->update([
            'id' =>  $values['sockets_id'],
            'itemtype' => $socket->fields['itemtype'],
            'items_id' => $socket->fields['items_id']
         ]);
      }
   }

   //remove "useless "sockets_id" field
   $migration->dropField('glpi_networkportethernets', 'sockets_id');
   $migration->dropField('glpi_networkportfiberchannels', 'sockets_id');
   $migration->dropField('glpi_networkportbncs', 'sockets_id');
}