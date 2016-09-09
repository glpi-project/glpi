<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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


 // Ensure current directory when run from crontab
 chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

/** @file
* @brief
*/
ini_set("memory_limit","-1");
ini_set("max_execution_time", "0");


include ('../inc/includes.php');

// No debug mode
$_SESSION['glpi_use_mode'] == Session::NORMAL_MODE;

$types = array('Computer', 'Monitor', 'Printer', 'Phone', 'Software', 'SoftwareVersion',
               'SoftwareLicense', 'Peripheral', 'NetworkEquipment', 'User', 'Group', 'Entity',
               'Profile', 'Budget', 'Contact', 'Contract', 'Netpoint', 'NetworkPort', 'Rule',
               'Cartridge', 'CartridgeItem', 'Consumable', 'ConsumableItem', 'Ticket', 'Problem',
               'Change', 'Supplier', 'Document', 'AuthLDAP', 'MailCollector', 'Location',
               'State', 'Manufacturer', 'Blacklist', 'BlacklistedMailContent', 'ITILCategory',
               'TaskCategory', 'TaskTemplate', 'Project', 'Reminder', 'RSSFeed',
               'SolutionType', 'RequestType', 'SolutionTemplate', 'ProjectState', 'ProjectType',
               'ProjectTaskType', 'SoftwareLicenseType', 'CartridgeItemType', 'ConsumableItemType',
               'ContractType', 'ContactType', 'DeviceMemoryType', 'SupplierType', 'InterfaceType',
               'DeviceCaseType', 'PhonePowerSupply', 'Filesystem', 'VirtualMachineType',
               'VirtualMachineSystem', 'VirtualMachineState', 'DocumentCategory', 'DocumentType',
               'KnowbaseItemCategory', 'Calendar', 'Holiday', 'NetworkEquipmentFirmware',
               'Network', 'Domain', 'Vlan', 'IPNetwork', 'FQDN', 'WifiNetwork', 'NetworkName',
               'UserTitle', 'UserCategory', 'RuleRightParameter', 'Fieldblacklist', 'SsoVariable',
               'NotificationTemplate', 'Notification', 'SLA', 'FieldUnicity', 'Crontask', 'Link',
               'ComputerDisk', 'ComputerVirtualMachine', 'Infocom');
$types = array_merge($types, $CFG_GLPI["dictionnary_types"]);
$types = array_merge($types, $CFG_GLPI["device_types"]);
$types = array_merge($types, $CFG_GLPI['networkport_instantiations']);

foreach ($types as $type) {
   $table       = getTableForItemType($type);
   //Set last modificaton date by looking for the corresponding entry in glpi_logs
   echo "Fill $table.date_mod\n";
   $query = "UPDATE $table
             LEFT JOIN (
               SELECT max(`id`), `date_mod`, `itemtype`, `items_id`
               FROM  glpi_logs
               GROUP BY itemtype, items_id
             ) as logs
               ON `logs`.`itemtype` = '$type'
               AND `logs`.`items_id` = `$table`.`id`
            SET  `$table`.`date_mod` = `logs`.`date_mod` WHERE `$table`.`date_mod` IS NULL";
   $DB->queryOrDie($query, "Error filling items last modification date");

   //Set creation date by looking for the corresponding entry in glpi_logs
   echo "Fill $table.date_creation\n";
   $query = "UPDATE $table
             LEFT JOIN (
               SELECT min(`id`), `date_mod`, `itemtype`, `items_id`
               FROM  glpi_logs
               GROUP BY itemtype, items_id
             ) as logs
               ON `logs`.`itemtype` = '$type'
               AND `logs`.`items_id` = `$table`.`id`
            SET  `$table`.`date_creation` = `logs`.`date_mod` WHERE `$table`.`date_creation` IS NULL";
   $DB->queryOrDie($query, "Error filling items creation date");
}
echo "Done !\n";
