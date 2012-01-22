<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

function logNetworkPortError($origin, $id, $itemtype, $items_id, $error) {
   global $migration_log_file;

   if ($migration_log_file) {
      fwrite($migration_log_file,
             $origin . " - " . $id . "=" . $itemtype . "[" . $items_id . "] : " . $error . "\n");
   }
}


function logMessage($msg, $andDisplay) {
   global $migration, $migration_log_file;

   if ($migration_log_file) {
      fwrite($migration_log_file, "** $msg\n");
   }

   if ($andDisplay) {
      $migration->displayMessage ($msg);
   }
}


function createNetworkNamesFromItems($itemtype, $itemtable) {
   global $DB, $migration;

   // Retrieve all the networks from the current network ports and add them to the IPNetworks
   $query = "SELECT `ip`, `id`, `entities_id`, `itemtype`, `items_id`
             FROM `$itemtable`
             WHERE `ip` <> ''";

   $networkName = new NetworkName();
   $IPaddress   = new IPAddress();

   foreach ($DB->request($query) as $entry) {
      if (empty($entry["ip"])) {
         continue;
      }

      $IP = $entry["ip"];
      // Using gethostbyaddr() allows us to define its reald internet name according to its IP.
      //   But each gethostbyaddr() may reach several milliseconds. With very large number of
      //   Networkports or NetworkeEquipment, the migration may take several minutes or hours ...
      //$computerName = gethostbyaddr($IP);
      /// TODO moyo : with several private networks gethostbyaddr may get wrong information
      $computerName = $IP;
      if ($computerName != $IP) {
         $position = strpos($computerName, ".");
         $name     = substr($computerName, 0, $position);
         $domain   = substr($computerName, $position + 1);
         $query    = "SELECT `id`
                      FROM `glpi_fqdns`
                      WHERE `fqdn` = '$domain'";
         $result = $DB->query($query);

         if ($DB->numrows($result) == 1) {
            $data     =$DB->fetch_array($result);
            $domainID = $data['id'];
         }

      } else {
         $name     = "migration-".str_replace('.','-',$computerName);
         $domainID = 0;
      }

      if ($IPaddress->setAddressFromString($IP)) {

         $input = array('name'         => $name,
                        'ip_addresses' => $IPaddress->getTextual(),
                        'fqdns_id'     => $domainID,
                        'entities_id'  => $entry['entities_id'],
                        'items_id'     => $entry['id'],
                        'itemtype'     => $itemtype);

         $networkNameID = $migration->insertInTable($networkName->getTable(), $input);

         $input = $IPaddress->setArrayFromAddress(array('entities_id'   => $entry['entities_id'],
                                                        'itemtype'      => $networkName->getType(),
                                                        'items_id'      => $networkNameID),
                                                  "version", "name", "binary");

         $migration->insertInTable($IPaddress->getTable(), $input);
      } else {
         addNetworkPortMigrationError($entry["id"], 'invalid_address');
         logNetworkPortError('invalid IP address', $entry["id"], $entry["itemtype"],
                             $entry["items_id"], "$IP");
      }
   }
}


function updateNetworkPortInstantiation($port, $fields, $setNetworkCard) {
   global $DB, $migration;

   $query = "SELECT `name`, `id`, `mac`, ";

   foreach ($fields as $SQL_field => $field) {
      $query .= "$SQL_field AS $field, ";
   }
   $query .= "    `itemtype`, `items_id`
              FROM `origin_glpi_networkports`
              WHERE `id` IN (SELECT `id`
                             FROM `glpi_networkports`
                             WHERE `instantiation_type` = '".$port->getType()."')";

   foreach ($DB->request($query) as $portInformation) {
      $input = array('networkports_id' => $portInformation['id']);
      foreach ($fields as $field) {
         $input[$field] = $portInformation[$field];
      }

      if (($setNetworkCard) && ($portInformation['itemtype'] == 'Computer')) {
         $query = "SELECT link.`id` AS link_id,
                          device.`designation` AS name
                   FROM `glpi_devicenetworkcards` as device,
                        `glpi_computers_devicenetworkcards` as link
                   WHERE link.`computers_id` = ".$portInformation['items_id']."
                         AND device.`id` = link.`devicenetworkcards_id`
                         AND link.`specificity` = '".$portInformation['mac']."'";
         $result = $DB->query($query);

        if ($DB->numrows($result) > 0) {
            $set_first = ($DB->numrows($result) == 1);
            while ($link = $DB->fetch_assoc($result)) {
               if (($set_first) || ($link['name'] == $portInformation['name'])) {
                  $input['computers_devicenetworkcards_id'] = $link['link_id'];
                  break;
               }
            }
         }
      }
      $migration->insertInTable($port->getTable(), $input);
   }
}


function addNetworkPortMigrationError($networkports_id, $motive) {
   global $DB;

   if (countElementsInTable("glpi_networkportmigrations", "`id` = '$networkports_id'") == 0) {
      $query = "INSERT INTO `glpi_networkportmigrations`
                       (SELECT *" . str_repeat(', 0',  count(NetworkPortMigration::getMotives())) ."
                       FROM `origin_glpi_networkports`
                       WHERE `id` = '$networkports_id')";
      $DB->queryOrDie($query, "0.84 copy of NetworkPort during migration error");
   }

   $query = "UPDATE `glpi_networkportmigrations`
             SET `$motive` = '1'
             WHERE `id`='$networkports_id'";
   $DB->queryOrDie($query, "0.84 append of motive to migration of NetworkPort error");

}

/**
 * Update from 0.83 to 0.84
 *
 * @return bool for success (will die for most error)
**/
function update083to084() {
   global $DB, $migration;

   $GLOBALS['migration_log_file'] = fopen(GLPI_LOG_DIR."/migration_083_084.log", "w");

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(_('Update to %s'), '0.84'));
   $migration->setVersion('0.84');


   // Add the internet field and copy rights from networking
   $migration->addField('glpi_profiles', 'internet', 'char', array('after'  => 'networking',
                                                                   'update' => '`networking`'));

   $backup_tables = false;
   $newtables     = array('glpi_changes', 'glpi_changes_groups', 'glpi_changes_items',
                          'glpi_changes_problems', 'glpi_changes_tickets', 'glpi_changes_users',
                          'glpi_changetasks', 'glpi_fqdns', 'glpi_ipaddresses', 'glpi_ipnetworks',
                          'glpi_networkaliases', 'glpi_networknames',
                          'glpi_networknames_ipnetworks', 'glpi_networkportaggregates',
                          'glpi_networkportdialups', 'glpi_networkportethernets',
                          'glpi_networkportlocals', 'glpi_networkportmigrations',
                          'glpi_networkportwifis', 'glpi_wifinetworks');

   foreach ($newtables as $new_table) {
      // rename new tables if exists ?
      if (TableExists($new_table)) {
         $migration->dropTable("backup_$new_table");
         $migration->displayWarning("$new_table table already exists. ".
                                    "A backup have been done to backup_$new_table.");
         $backup_tables = true;
         $query         = $migration->renameTable("$new_table", "backup_$new_table");
      }
   }
   if ($backup_tables) {
      $migration->displayWarning("You can delete backup tables if you have no need of them.", true);
   }

   $originTables = array();
   foreach (array('glpi_networkports', 'glpi_networkequipments') as $copyTable) {
      $originTable = 'origin_'.$copyTable;
      if (!TableExists($originTable) && TableExists($copyTable)) {
         $migration->copyTable($copyTable, $originTable);
         $originTables[] = $originTable;
         $migration->displayWarning("To be safe, we are working on $originTable. ".
                                    "It is a copy of $copyTable", false);
      }
   }

   // Create the glpi_networkportmigrations that is a copy of origin_glpi_networkports
   $query = "CREATE TABLE `glpi_networkportmigrations` LIKE `origin_glpi_networkports`";
   $DB->queryOrDie($query, "0.84 create glpi_networkportmigrations");

   // And add the error motive fields
   $optionIndex = 10;
   $ADDTODISPLAYPREF['NetworkPortMigration'] = array();
   foreach (NetworkPortMigration::getMotives() as $key => $name) {

      $ADDTODISPLAYPREF['NetworkPortMigration'][] = $optionIndex ++;
      $query = "ALTER TABLE `glpi_networkportmigrations` ADD $key int(1) default 0";
      $DB->queryOrDie($query, "0.84 add $key to glpi_networkportmigrations");

   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Data migration - %s'), "glpi_fqdns"), true);

   // Adding FQDN table
   if (!TableExists('glpi_fqdns')) {
      $query = "CREATE TABLE `glpi_fqdns` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `fqdn` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `fqdn` (`fqdn`),
                  KEY `is_recursive` (`is_recursive`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      //TRANS: %1$s is the title of the update, %2$s is the DB error
      $DB->queryOrDie($query, "0.84 create glpi_fqdns");

      $fqdn = new FQDN();

      // Then, populate it from domains (beware that "domains" can be FQDNs and Windows workgroups)
      $query = "SELECT DISTINCT LOWER(`name`) AS name, `comment`
                FROM `glpi_domains`";
      foreach ($DB->request($query) as $domain) {
         $domainName = $domain['name'];
         // We ensure that domains have at least 1 dote to be sure it is not a Windows workgroup
         if ((strpos($domainName, '.') !== false) && (FQDN::checkFQDN($domainName))) {
            $migration->insertInTable($fqdn->getTable(),
                                      array('entities_id' => 0,
                                            'name'        => $domainName,
                                            'fqdn'        => $domainName,
                                            'comment'     => $domain['comment']));
         }
      }
      $ADDTODISPLAYPREF['FQDN'] = array(11);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Data migration - %s'), "glpi_ipaddresses"), true);

   // Adding IPAddress table
   if (!TableExists('glpi_ipaddresses')) {
      $query = "CREATE TABLE `glpi_ipaddresses` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `version` tinyint unsigned DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `binary_0`  int unsigned NOT NULL DEFAULT '0',
                  `binary_1`  int unsigned NOT NULL DEFAULT '0',
                  `binary_2`  int unsigned NOT NULL DEFAULT '0',
                  `binary_3`  int unsigned NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `textual` (`name`),
                  KEY `binary` (`binary_0`, `binary_1`, `binary_2`, `binary_3`),
                  KEY `item` (`items_id`,`itemtype`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, "0.84 create glpi_ipaddresses");
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_wifinetworks"), true);

   // Adding WifiNetwork table
   if (!TableExists('glpi_wifinetworks')) {
      $query = "CREATE TABLE `glpi_wifinetworks` (
                 `id` int(11) NOT NULL AUTO_INCREMENT,
                 `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `essid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `mode` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
                        COMMENT 'ad-hoc, access_point',
                 `comment` text COLLATE utf8_unicode_ci,
                 PRIMARY KEY (`id`),
                 KEY `essid` (`essid`),
                 KEY `name` (`name`)
               ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_wifinetworks");

      $ADDTODISPLAYPREF['WifiNetwork'] = array(10);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Data migration - %s'), "glpi_ipnetworks"), true);

   // Adding IPNetwork table
   if (!TableExists('glpi_ipnetworks')) {
      $query = "CREATE TABLE `glpi_ipnetworks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `ipnetworks_id` int(11) NOT NULL DEFAULT '0',
                  `completename` text COLLATE utf8_unicode_ci,
                  `level` int(11) NOT NULL DEFAULT '0',
                  `ancestors_cache` longtext COLLATE utf8_unicode_ci,
                  `sons_cache` longtext COLLATE utf8_unicode_ci,
                  `addressable` tinyint(1) NOT NULL DEFAULT '0',
                  `version` tinyint unsigned DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `address` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `address_0`  int unsigned NOT NULL DEFAULT '0',
                  `address_1`  int unsigned NOT NULL DEFAULT '0',
                  `address_2`  int unsigned NOT NULL DEFAULT '0',
                  `address_3`  int unsigned NOT NULL DEFAULT '0',
                  `netmask` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `netmask_0`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_1`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_2`  int unsigned NOT NULL DEFAULT '0',
                  `netmask_3`  int unsigned NOT NULL DEFAULT '0',
                  `gateway` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `gateway_0`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_1`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_2`  int unsigned NOT NULL DEFAULT '0',
                  `gateway_3`  int unsigned NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `network_definition` (`entities_id`,`address`,`netmask`),
                  KEY `address` (`address_0`, `address_1`, `address_2`, `address_3`),
                  KEY `netmask` (`netmask_0`, `netmask_1`, `netmask_2`, `netmask_3`),
                  KEY `gateway` (`gateway_0`, `gateway_1`, `gateway_2`, `gateway_3`),
                  KEY `name` (`name`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_ipnetworks");

      // Retrieve all the networks from the current network ports and add them to the IPNetworks
      $query = "SELECT DISTINCTROW INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) AS address,
                     `netmask`, `gateway`, `entities_id`
                FROM `origin_glpi_networkports`
                ORDER BY `gateway` DESC";
      $address = new IPAddress();
      $netmask = new IPNetmask();
      $gateway = new IPAddress();
      $network = new IPNetwork();
      foreach ($DB->request($query) as $entry) {

         $address = $entry['address'];
         $netmask = $entry['netmask'];
         $gateway = $entry['gateway'];
         $entities_id = $entry['entities_id'];

         if ((empty($address)) || ($address == '0.0.0.0') || (empty($netmask))
             || ($netmask == '0.0.0.0') || ($netmask == '255.255.255.255')) {
            continue;
         }

         if ($gateway == '0.0.0.0') {
            $gateway = '';
         }

         $networkDefinition = "$address/$netmask";
         $networkName   = $networkDefinition . (empty($gateway) ? "" : " - ".$gateway);

         $input         = array('entities_id'   => $entities_id,
                                'name'          => $networkName,
                                'network'       => $networkDefinition,
                                'gateway'       => $gateway,
                                'ipnetworks_id' => 0,
                                'addressable'   => 1,
                                'completename'  => $networkName,
                                'level'         => 1);

         $preparedInput = $network->prepareInput($input);

         if (is_array($preparedInput['input'])) {
            $input = $preparedInput['input'];
            if (isset($preparedInput['error'])) {
               $query = "SELECT id, items_id, itemtype
                         FROM origin_glpi_networkports
                         WHERE INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) = '$address'
                               AND `netmask` = '$netmask'
                               AND `gateway` = '$gateway'
                               AND `entities_id` = '$entities_id'";
               $result = $DB->query($query);
               foreach ($DB->request($query) as $data) {
                  addNetworkPortMigrationError($data['id'], 'invalid_gateway');
                  logNetworkPortError('network warning', $data['id'], $data['itemtype'],
                                      $data['items_id'], $preparedInput['error']);
               }
            }
            $migration->insertInTable($network->getTable(), $input);
         } else if (isset($preparedInput['error'])) {
            $query = "SELECT id, items_id, itemtype
                      FROM origin_glpi_networkports
                      WHERE INET_NTOA(INET_ATON(`ip`)&INET_ATON(`netmask`)) = '".$entry['address']."'
                            AND `netmask` = '$netmask'
                            AND `gateway` = '$gateway'
                            AND `entities_id` = '$entities_id'";
            $result = $DB->query($query);
            foreach ($DB->request($query) as $data) {
               addNetworkPortMigrationError($data['id'], 'invalid_network');
               logNetworkPortError('network error', $data['id'], $data['itemtype'],
                                   $data['items_id'], $preparedInput['error']);
            }
         }
      }
      $ADDTODISPLAYPREF['IPNetwork'] = array(14, 10, 11, 12, 13);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Data migration - %s'), "glpi_networknames"), true);

   // Adding NetworkName table
   if (!TableExists('glpi_networknames')) {
      $query = "CREATE TABLE `glpi_networknames` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `fqdns_id` int(11) NOT NULL DEFAULT '0',
                  `ip_addresses` TEXT COLLATE utf8_unicode_ci COMMENT 'caching value of IPAddress',
                  PRIMARY KEY (`id`),
                  KEY `FQDN` (`name`,`fqdns_id`),
                  KEY `name` (`name`),
                  KEY `item` (`items_id`, `itemtype`),
                  KEY `fqdns_id` (`fqdns_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networknames");

      $ADDTODISPLAYPREF['NetworkName'] = array(12, 13);

      createNetworkNamesFromItems("NetworkPort", "origin_glpi_networkports");
      createNetworkNamesFromItems("NetworkEquipment", "origin_glpi_networkequipments");

   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkaliases"), true);

   // Adding NetworkAlias table
   if (!TableExists('glpi_networkaliases')) {
      $query = "CREATE TABLE `glpi_networkaliases` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `networknames_id` int(11) NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `fqdns_id` int(11) NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `networknames_id` (`networknames_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkaliases");
   }


   logMessage(sprintf(__('Data migration - %s'), "glpi_networknames_ipnetworks"), true);

   // Adding NetworkName_IPNetwork table
   if (!TableExists('glpi_networknames_ipnetworks')) {
      $query = "CREATE TABLE `glpi_networknames_ipnetworks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networknames_id` int(11) NOT NULL DEFAULT '0',
                  `ipnetworks_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`networknames_id`,`ipnetworks_id`),
                  KEY `ipnetworks_id` (`ipnetworks_id`),
                  KEY `networknames_id` (`networknames_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->queryOrDie($query, "0.84 create glpi_networknames_ipnetworks");
   }


   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkinterfaces"), true);

   // Update NetworkPorts
   $migration->addField('glpi_networkports', 'instantiation_type', 'string',
                        array('after'  => 'name',
                              'update' => "'NetworkPortEthernet'"));

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Data migration - %s'), "glpi_networkports"), true);

   // Retrieve all the networks from the current network ports and add them to the IPNetworks
   $query = "SELECT *
             FROM `glpi_networkinterfaces`";

   foreach ($DB->request($query) as $entry) {
      switch ($entry['name']) {
         case 'Local' :
            $instantiation_type = "NetworkPortLocal";
            break;

         case 'Ethernet' :
            $instantiation_type = "NetworkPortEthernet";
            break;

         case 'Wifi' :
            $instantiation_type = "NetworkPortWifi";
            break;

         case 'Dialup' :
            $instantiation_type = "NetworkPortDialup";
            break;

         default:
            $instantiation_type = "";
            break;

       }
      if (isset($instantiation_type)) {
         $query = "UPDATE `glpi_networkports`
                   SET `instantiation_type` = '$instantiation_type'
                   WHERE `id` IN (SELECT `id`
                                  FROM `origin_glpi_networkports`
                                  WHERE `networkinterfaces_id` = '".$entry['id']."')";
         $DB->queryOrDie($query, "0.84 update instantiation_type field of glpi_networkports");
         // Clear $instantiation_type for next check inside the loop
         unset($instantiation_type);
      }
   }

   foreach (array('ip', 'gateway', 'netmask', 'netpoints_id', 'networkinterfaces_id',
                  'subnet') as $field) {
      $migration->dropField('glpi_networkports', $field);
   }

   logMessage(__('Transform address mac to lower'), true);
   $query = "UPDATE glpi_networkports
             SET `mac`=LOWER(`mac`)";
   $DB->queryOrDie($query, "0.84 transforme MAC to lower case");

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('update migration of interfaces errors - %s'), "glpi_networkportmigrations"), true);

   $query = "SELECT id
             FROM `glpi_networkports`
             WHERE `instantiation_type` = ''";

   foreach ($DB->request($query) as $networkPortID) {
      addNetworkPortMigrationError($networkPortID['id'], 'unknown_interface_type');
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportethernets"), true);

   // Adding NetworkPortEthernet table
   if (!TableExists('glpi_networkportethernets')) {
      $query = "CREATE TABLE `glpi_networkportethernets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `computers_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
                  `netpoints_id` int(11) NOT NULL DEFAULT '0',
                  `type` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'T, LX, SX',
                  `speed` int(11) NOT NULL DEFAULT '10' COMMENT '10, 100, 1000, 10000',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `card` (`computers_devicenetworkcards_id`),
                  KEY `netpoint` (`netpoints_id`),
                  KEY `type` (`type`),
                  KEY `speed` (`speed`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportethernets");

      $port = new NetworkPortEthernet();
      updateNetworkPortInstantiation($port, array('`netpoints_id`' => 'netpoints_id'),
                                     true);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportwifis"), true);

  // Adding NetworkPortWifi table
   if (!TableExists('glpi_networkportwifis')) {
      $query = "CREATE TABLE `glpi_networkportwifis` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `computers_devicenetworkcards_id` int(11) NOT NULL DEFAULT '0',
                  `wifinetworks_id` int(11) NOT NULL DEFAULT '0',
                  `networkportwifis_id` int(11) NOT NULL DEFAULT '0'
                                        COMMENT 'only usefull in case of Managed node',
                  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
                            COMMENT 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y',
                  `mode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL
                         COMMENT 'ad-hoc, managed, master, repeater, secondary, monitor, auto',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `card` (`computers_devicenetworkcards_id`),
                  KEY `essid` (`wifinetworks_id`),
                  KEY `version` (`version`),
                  KEY `mode` (`mode`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportwifis");

      $port = new NetworkPortWifi();
      updateNetworkPortInstantiation($port, array(), true);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportlocals"), true);

   // Adding NetworkPortLocal table
   if (!TableExists('glpi_networkportlocals')) {
      $query = "CREATE TABLE `glpi_networkportlocals` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportlocals");

      $port = new NetworkPortLocal();
      updateNetworkPortInstantiation($port, array(), false);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportdialups"), true);

   // Adding NetworkPortDialup table
   if (!TableExists('glpi_networkportdialups')) {
      $query = "CREATE TABLE `glpi_networkportdialups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportdialups");

      $port = new NetworkPortDialup();
      updateNetworkPortInstantiation($port, array(), true);
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportaggregates"), true);

   // Adding NetworkPortAggregate table
   if (!TableExists('glpi_networkportaggregates')) {
      $query = "CREATE TABLE `glpi_networkportaggregates` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `links_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
                             COMMENT 'array of associated networkports_id',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportaggregates");

      // New element, so, we don't need to create items
   }

   //TRANS: %s is the name of the table
   logMessage(sprintf(__('Change of the database layout - %s'), "glpi_networkportaliases"), true);

   // Adding NetworkPortAlias table
   if (!TableExists('glpi_networkportaliases')) {
      $query = "CREATE TABLE `glpi_networkportaliases` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `networkports_id` int(11) NOT NULL DEFAULT '0',
                  `links_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `networkports_id` (`networkports_id`),
                  KEY `links_id` (`links_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.84 create glpi_networkportaliases");

      // New element, so, we don't need to create items
   }

   $migration->addField('glpi_networkports_vlans', 'tagged', 'char', array('value' => '0'));

   $migration->addField('glpi_mailcollectors', 'accepted', 'string');
   $migration->addField('glpi_mailcollectors', 'refused', 'string');

   // Clean display prefs
   $query = "UPDATE `glpi_displaypreferences`
             SET `num` = 160
             WHERE `itemtype` = 'Software'
                   AND `num` = 7";
   $DB->query($query);

   $migration->displayWarning("You should have a look at the \"migration cleaner\" tool !", true);


   $lang_to_update = array('ca_CA' => 'ca_ES',
                           'dk_DK' => 'da_DK',
                           'ee_ET' => 'et_EE',
                           'el_EL' => 'el_GR',
                           'he_HE' => 'he_IL',
                           'no_NB' => 'nb_NO',
                           'no_NN' => 'nn_NO',
                           'ua_UA' => 'uk_UA',);
   foreach ($lang_to_update as $old => $new) {
      $query = "UPDATE `glpi_configs`
               SET `language` = '$new'
               WHERE `language` = '$old';";
      $DB->queryOrDie($query, "0.74 language in config $old to $new");

      $query = "UPDATE `glpi_users`
               SET `language` = '$new'
               WHERE `language` = '$old';";
      $DB->queryOrDie($query, "0.74 language in users $old to $new");
   }

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'Change'));

   // changes management
   if (!TableExists('glpi_changes')) {
      $query = "CREATE TABLE `glpi_changes` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `entities_id` int(11) NOT NULL DEFAULT '0',
                  `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
                  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
                  `status` varchar(255) DEFAULT NULL,
                  `content` longtext DEFAULT NULL,
                  `date_mod` DATETIME DEFAULT NULL,
                  `date` DATETIME DEFAULT NULL,
                  `solvedate` DATETIME DEFAULT NULL,
                  `closedate` DATETIME DEFAULT NULL,
                  `due_date` DATETIME DEFAULT NULL,
                  `users_id_recipient` int(11) NOT NULL DEFAULT '0',
                  `users_id_lastupdater` int(11) NOT NULL DEFAULT '0',
                  `suppliers_id_assign` int(11) NOT NULL DEFAULT '0',
                  `urgency` int(11) NOT NULL DEFAULT '1',
                  `impact` int(11) NOT NULL DEFAULT '1',
                  `priority` int(11) NOT NULL DEFAULT '1',
                  `itilcategories_id` int(11) NOT NULL DEFAULT '0',
                  `impactcontent` longtext DEFAULT NULL,
                  `controlistcontent` longtext DEFAULT NULL,
                  `rolloutplancontent` longtext DEFAULT NULL,
                  `backoutplancontent` longtext DEFAULT NULL,
                  `checklistcontent` longtext DEFAULT NULL,
                  `solutiontypes_id` int(11) NOT NULL DEFAULT '0',
                  `solution` text COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  `notepad` LONGTEXT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `date` (`date`),
                  KEY `closedate` (`closedate`),
                  KEY `status` (`status`(1)),
                  KEY `priority` (`priority`),
                  KEY `date_mod` (`date_mod`),
                  KEY `suppliers_id_assign` (`suppliers_id_assign`),
                  KEY `itilcategories_id` (`itilcategories_id`),
                  KEY `users_id_recipient` (`users_id_recipient`),
                  KEY `solvedate` (`solvedate`),
                  KEY `solutiontypes_id` (`solutiontypes_id`),
                  KEY `urgency` (`urgency`),
                  KEY `impact` (`impact`),
                  KEY `due_date` (`due_date`),
                  KEY `users_id_lastupdater` (`users_id_lastupdater`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 create glpi_changes");
   }

   if (!TableExists('glpi_changes_users')) {
      $query = "CREATE TABLE `glpi_changes_users` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
                  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`type`,`users_id`,`alternative_email`),
                  KEY `user` (`users_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changes_users");
   }

   if (!TableExists('glpi_changes_groups')) {
      $query = "CREATE TABLE `glpi_changes_groups` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `groups_id` int(11) NOT NULL DEFAULT '0',
                  `type` int(11) NOT NULL DEFAULT '1',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`type`,`groups_id`),
                  KEY `group` (`groups_id`,`type`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changes_groups");
   }

   if (!TableExists('glpi_changes_items')) {
      $query = "CREATE TABLE `glpi_changes_items` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `itemtype` varchar(100) default NULL,
                  `items_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`itemtype`,`items_id`),
                  KEY `item` (`itemtype`,`items_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changes_items");
   }

   if (!TableExists('glpi_changes_tickets')) {
      $query = "CREATE TABLE `glpi_changes_tickets` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `tickets_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`tickets_id`),
                  KEY `tickets_id` (`tickets_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changes_tickets");
   }

   if (!TableExists('glpi_changes_problems')) {
      $query = "CREATE TABLE `glpi_changes_problems` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `problems_id` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`changes_id`,`problems_id`),
                  KEY `problems_id` (`problems_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changes_problems");
   }

   if (!TableExists('glpi_changetasks')) {
      $query = "CREATE TABLE `glpi_changetasks` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `changes_id` int(11) NOT NULL DEFAULT '0',
                  `changetasks_id` int(11) NOT NULL DEFAULT '0',
                  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
                  `taskcategories_id` int(11) NOT NULL DEFAULT '0',
                  `status` varchar(255) DEFAULT NULL,
                  `priority` int(11) NOT NULL DEFAULT '1',
                  `percentdone` int(11) NOT NULL DEFAULT '0',
                  `date` datetime DEFAULT NULL,
                  `begin` datetime DEFAULT NULL,
                  `end` datetime DEFAULT NULL,
                  `users_id` int(11) NOT NULL DEFAULT '0',
                  `users_id_tech` int(11) NOT NULL DEFAULT '0',
                  `content` longtext COLLATE utf8_unicode_ci,
                  `actiontime` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `changes_id` (`changes_id`),
                  KEY `changetasks_id` (`changetasks_id`),
                  KEY `is_blocked` (`is_blocked`),
                  KEY `priority` (`priority`),
                  KEY `status` (`status`),
                  KEY `percentdone` (`percentdone`),
                  KEY `users_id` (`users_id`),
                  KEY `users_id_tech` (`users_id_tech`),
                  KEY `date` (`date`),
                  KEY `begin` (`begin`),
                  KEY `end` (`end`),
                  KEY `taskcategories_id` (taskcategories_id)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, "0.83 add table glpi_changetasks");
   }

   /// TODO add changetasktypes table as dropdown
   /// TODO review users linked to changetask
   /// TODO add display prefs

   $migration->addField("glpi_profiles", "show_my_change", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `own_ticket` = 1"));

   $migration->addField("glpi_profiles", "show_all_change", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `show_all_ticket` = 1"));

   $migration->addField("glpi_profiles", "edit_all_change", "char",
                        array('update'    => "1",
                              'condition' => " WHERE `update_ticket` = 1"));

   $migration->addField('glpi_profiles', 'change_status', "text",
                        array('comment' => "json encoded array of from/dest allowed status change"));

   $migration->displayMessage(sprintf(__('Change of the database layout - %s'), 'various fields'));

   $migration->addField("glpi_reservationitems", "is_deleted", "bool");
   $migration->addKey("glpi_reservationitems", "is_deleted");


   // ************ Keep it at the end **************
   //TRANS: %s is the table or item to migrate
   $migration->displayMessage(sprintf(__('Data migration - %s'),'glpi_displaypreferences'));

   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $query = "SELECT DISTINCT `users_id`
                FROM `glpi_displaypreferences`
                WHERE `itemtype` = '$type'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query = "SELECT MAX(`rank`)
                         FROM `glpi_displaypreferences`
                         WHERE `users_id` = '".$data['users_id']."'
                               AND `itemtype` = '$type'";
               $result = $DB->query($query);
               $rank   = $DB->result($result,0,0);
               $rank++;

               foreach ($tab as $newval) {
                  $query = "SELECT *
                            FROM `glpi_displaypreferences`
                            WHERE `users_id` = '".$data['users_id']."'
                                  AND `num` = '$newval'
                                  AND `itemtype` = '$type'";
                  if ($result2=$DB->query($query)) {
                     if ($DB->numrows($result2)==0) {
                        $query = "INSERT INTO `glpi_displaypreferences`
                                         (`itemtype` ,`num` ,`rank` ,`users_id`)
                                  VALUES ('$type', '$newval', '".$rank++."',
                                          '".$data['users_id']."')";
                        $DB->query($query);
                     }
                  }
               }
            }

         } else { // Add for default user
            $rank = 1;
            foreach ($tab as $newval) {
               $query = "INSERT INTO `glpi_displaypreferences`
                                (`itemtype` ,`num` ,`rank` ,`users_id`)
                         VALUES ('$type', '$newval', '".$rank++."', '0')";
               $DB->query($query);
            }
         }
      }
   }


   if ($GLOBALS['migration_log_file']) {
      fclose($GLOBALS['migration_log_file']);
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
?>
