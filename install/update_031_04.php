<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/**
 * Test if there is a user with superadmin rights
 *
 *
 * @returns boolean true if its ok, elsewhere false.
**/
function superAdminExists() {
   global $DB;

   $query = "SELECT `type`, `password`
             FROM `glpi_users`";
   $result = $DB->query($query);
   $var1 = false;
   while ($line = $DB->fetchArray($result)) {
      if ($line["type"] == "super-admin" && !empty($line["password"])) {
         $var1 = true;
      }
   }
   return $var1;
}


/// Update from 0.31 to 0.4
function update031to04() {
   global $DB;

   //0.4 Prefixage des tables :
   echo "<p class='center'>Version 0.4 </p>";

   if (!$DB->tableExists("glpi_computers")) {
      $query = "ALTER TABLE `computers` RENAME `glpi_computers`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `connect_wire` RENAME `glpi_connect_wire`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_gfxcard` RENAME `glpi_dropdown_gfxcard`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_hdtype` RENAME `glpi_dropdown_hdtype`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_iface` RENAME `glpi_dropdown_iface`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_locations` RENAME `glpi_dropdown_locations`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_moboard` RENAME `glpi_dropdown_moboard`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_network` RENAME `glpi_dropdown_network`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_os` RENAME `glpi_dropdown_os`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_processor` RENAME `glpi_dropdown_processor`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_ram` RENAME `glpi_dropdown_ram`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `dropdown_sndcard` RENAME `glpi_dropdown_sndcard`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `event_log` RENAME `glpi_event_log`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `followups` RENAME `glpi_followups`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `inst_software` RENAME `glpi_inst_software`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `licenses` RENAME `glpi_licenses`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `monitors` RENAME `glpi_monitors`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `networking` RENAME `glpi_networking`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `networking_ports` RENAME `glpi_networking_ports`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `networking_wire` RENAME `glpi_networking_wire`";
      $DB->queryOrDie($query);

      if ($DB->tableExists("prefs") && !$DB->tableExists("glpi_prefs")) {
         $query = "ALTER TABLE `prefs` RENAME `glpi_prefs`";
         $DB->queryOrDie($query);
      }

      $query = "ALTER TABLE `printers` RENAME `glpi_printers`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `software` RENAME `glpi_software`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `templates` RENAME `glpi_templates`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `tracking` RENAME `glpi_tracking`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `type_computers` RENAME `glpi_type_computers`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `type_monitors` RENAME `glpi_type_monitors`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `type_networking` RENAME `glpi_type_networking`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `type_printers` RENAME `glpi_type_printers`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `users` RENAME `glpi_users`";
      $DB->queryOrDie($query);
   }

   //Ajout d'un champs ID dans la table users
   if (!$DB->fieldExists("glpi_users", "ID", false)) {
      $query = "ALTER TABLE `glpi_users`
                DROP PRIMARY KEY";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_users`
                ADD UNIQUE (`name`)";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_users`
                ADD INDEX (`name`)";
      $DB->queryOrDie($query);

      $query = " ALTER TABLE `glpi_users`
                 ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
      $DB->queryOrDie($query);
   }

   //Mise a jour des ID pour les tables dropdown et type
   //cles primaires sur les tables dropdown et type, et mise a jour des champs lies
   if (!$DB->fieldExists("glpi_dropdown_os", "ID", false)) {
      changeVarcharToID("glpi_computers", "glpi_dropdown_os", "os");
      changeVarcharToID("glpi_computers", "glpi_dropdown_hdtype", "hdtype");
      changeVarcharToID("glpi_computers", "glpi_dropdown_sndcard", "sndcard");
      changeVarcharToID("glpi_computers", "glpi_dropdown_moboard", "moboard");
      changeVarcharToID("glpi_computers", "glpi_dropdown_gfxcard", "gfxcard");
      changeVarcharToID("glpi_computers", "glpi_dropdown_network", "network");
      changeVarcharToID("glpi_computers", "glpi_dropdown_ram", "ramtype");
      changeVarcharToID("glpi_computers", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_computers", "glpi_dropdown_processor", "processor");
      changeVarcharToID("glpi_computers", "glpi_type_computers", "type");

      changeVarcharToID("glpi_monitors", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_monitors", "glpi_type_monitors", "type");

      changeVarcharToID("glpi_networking", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_networking", "glpi_type_networking", "type");

      changeVarcharToID("glpi_networking_ports", "glpi_dropdown_iface", "iface");

      changeVarcharToID("glpi_printers", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_printers", "glpi_type_printers", "type");

      changeVarcharToID("glpi_software", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_software", "glpi_dropdown_os", "platform");

      changeVarcharToID("glpi_templates", "glpi_dropdown_os", "os");
      changeVarcharToID("glpi_templates", "glpi_dropdown_hdtype", "hdtype");
      changeVarcharToID("glpi_templates", "glpi_dropdown_sndcard", "sndcard");
      changeVarcharToID("glpi_templates", "glpi_dropdown_moboard", "moboard");
      changeVarcharToID("glpi_templates", "glpi_dropdown_gfxcard", "gfxcard");
      changeVarcharToID("glpi_templates", "glpi_dropdown_network", "network");
      changeVarcharToID("glpi_templates", "glpi_dropdown_ram", "ramtype");
      changeVarcharToID("glpi_templates", "glpi_dropdown_locations", "location");
      changeVarcharToID("glpi_templates", "glpi_dropdown_processor", "processor");
      changeVarcharToID("glpi_templates", "glpi_type_computers", "type");

      changeVarcharToID("glpi_users", "glpi_dropdown_locations", "location");
   }

   if (!$DB->tableExists("glpi_type_peripherals")) {
      $query = "CREATE TABLE `glpi_type_peripherals` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255),
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0A");
   }

   if (!$DB->tableExists("glpi_peripherals")) {
      $query = "CREATE TABLE `glpi_peripherals` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
                  `contact` varchar(255) NOT NULL default '',
                  `contact_num` varchar(255) NOT NULL default '',
                  `comments` text NOT NULL,
                  `serial` varchar(255) NOT NULL default '',
                  `otherserial` varchar(255) NOT NULL default '',
                  `date_fin_garantie` date default NULL,
                  `achat_date` date NOT NULL default '0000-00-00',
                  `maintenance` int(2) default '0',
                  `location` int(11) NOT NULL default '0',
                  `type` int(11) NOT NULL default '0',
                  `brand` varchar(255) NOT NULL default '',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0");
   }

   if ($DB->tableExists("glpi_prefs") && !$DB->fieldExists("glpi_prefs", "ID", false)) {
      $query = "ALTER TABLE `glpi_prefs`
                DROP PRIMARY KEY";
      $DB->queryOrDie($query, "1");

      $query = "ALTER TABLE `glpi_prefs`
                ADD `ID` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
      $DB->queryOrDie($query, "3");
   }

   if (!$DB->fieldExists("glpi_config", "ID", false)) {
      $query = "ALTER TABLE `glpi_config`
                CHANGE `config_id` `ID` INT(11) NOT NULL AUTO_INCREMENT";
      $DB->queryOrDie($query, "4");
   }

   if (!isIndex("glpi_computers", "location")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "5");
   }

   if (!isIndex("glpi_computers", "os")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`os`)";
      $DB->queryOrDie($query, "6");
   }

   if (!isIndex("glpi_computers", "type")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "7");
   }

   if (!isIndex("glpi_followups", "tracking")) {
      $query = "ALTER TABLE `glpi_followups`
                ADD INDEX (`tracking`)";
      $DB->queryOrDie($query, "12");
   }

   if (!isIndex("glpi_networking", "location")) {
      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "13");
   }

   if (!isIndex("glpi_networking_ports", "on_device")) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD INDEX (`on_device` , `device_type`)";
      $DB->queryOrDie($query, "14");
   }

   if (!isIndex("glpi_peripherals", "type")) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "14");
   }

   if (!isIndex("glpi_peripherals", "location")) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "15");
   }

   if (!isIndex("glpi_printers", "location")) {
      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`location`)";
      $DB->queryOrDie($query, "16");
   }

   if (!isIndex("glpi_tracking", "computer")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`computer`)";
      $DB->queryOrDie($query, "17");
   }

   if (!isIndex("glpi_tracking", "author")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`author`)";
      $DB->queryOrDie($query, "18");
   }

   if (!isIndex("glpi_tracking", "assign")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`assign`)";
      $DB->queryOrDie($query, "19");
   }

   if (!isIndex("glpi_tracking", "date")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`date`)";
      $DB->queryOrDie($query, "20");
   }

   if (!isIndex("glpi_tracking", "closedate")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`closedate`)";
      $DB->queryOrDie($query, "21");
   }

   if (!isIndex("glpi_tracking", "status")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`status`)";
      $DB->queryOrDie($query, "22");
   }

   if (!$DB->tableExists("glpi_dropdown_firmware")) {
      $query = "CREATE TABLE `glpi_dropdown_firmware` (
                  `ID` INT NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL,
                  PRIMARY KEY (`ID`))";
      $DB->queryOrDie($query, "23");
   }

   if (!$DB->fieldExists("glpi_networking", "firmware", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `firmware` INT(11)";
      $DB->queryOrDie($query, "24");
   }

   if (!$DB->fieldExists("glpi_tracking", "realtime", false)) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD `realtime` FLOAT NOT NULL";
      $DB->queryOrDie($query, "25");
   }

   if (!$DB->fieldExists("glpi_printers", "flags_usb", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `flags_usb` TINYINT DEFAULT '0' NOT NULL AFTER `flags_par`";
      $DB->queryOrDie($query, "26");
   }

   if (!$DB->fieldExists("glpi_licenses", "expire", false)) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD `expire` date default NULL";
      $DB->queryOrDie($query, "27");
   }

   if (!isIndex("glpi_licenses", "sID")) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD INDEX (`sID`) ";
      $DB->queryOrDie($query, "32");
   }

   if (!isIndex("glpi_followups", "author")) {
      $query = "ALTER TABLE `glpi_followups`
                ADD INDEX (`author`) ";
      $DB->queryOrDie($query, "33");
   }

   if (!isIndex("glpi_monitors", "type")) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`type`) ";
      $DB->queryOrDie($query, "34");
   }

   if (!isIndex("glpi_monitors", "location")) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`location`) ";
      $DB->queryOrDie($query, "35");
   }

   if (!isIndex("glpi_monitors", "type")) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "37");
   }

   if (!isIndex("glpi_networking", "type")) {
      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "38");
   }

   if (!isIndex("glpi_networking", "firmware")) {
      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`firmware`)";
      $DB->queryOrDie($query, "39");
   }

   if (!isIndex("glpi_printers", "type")) {
      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "42");
   }

   if (!isIndex("glpi_software", "platform")) {
      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`platform`)";
      $DB->queryOrDie($query, "44");
   }

   if (!isIndex("glpi_software", "location")) {
      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`location`) ";
      $DB->queryOrDie($query, "45");
   }

   if (!$DB->tableExists("glpi_dropdown_netpoint")) {
      $query = " CREATE TABLE `glpi_dropdown_netpoint` (
                     `ID` INT NOT NULL AUTO_INCREMENT ,
                     `location` INT NOT NULL ,
                     `name` VARCHAR(255) NOT NULL ,
                     PRIMARY KEY (`ID`))";
      $DB->queryOrDie($query, "46");
   }

   if (!isIndex("glpi_dropdown_netpoint", "location")) {
      $query = "ALTER TABLE `glpi_dropdown_netpoint`
                ADD INDEX (`location`) ";
      $DB->queryOrDie($query, "47");
   }

   if (!$DB->fieldExists("glpi_networking_ports", "netpoint", false)) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD `netpoint` INT default NULL";
      $DB->queryOrDie($query, "27");
   }

   if (!isIndex("glpi_networking_ports", "netpoint")) {
      $query = "ALTER TABLE `glpi_networking_ports`
                ADD INDEX (`netpoint`) ";
      $DB->queryOrDie($query, "47");
   }

   if (!isIndex("glpi_networking_wire", "end1")) {
      $query = "ALTER TABLE `glpi_networking_wire`
                ADD INDEX (`end1`) ";
      $DB->queryOrDie($query, "40");

      // Clean Table
      $query = "SELECT *
                FROM `glpi_networking_wire`
                ORDER BY `end1`, `end2`";
      $result = $DB->query($query);

      $curend1 = -1;
      $curend2 = -1;
      while ($line = $DB->fetchArray($result)) {
         if ($curend1==$line['end1'] && $curend2==$line['end2']) {
            $q2 = "DELETE
                   FROM `glpi_networking_wire`
                   WHERE `ID` = '".$line['ID']."' LIMIT 1";
            $DB->query($q2);
         } else {
            $curend1 = $line['end1'];
            $curend2 = $line['end2'];}
      }
      $DB->freeResult($result);

      $query = "ALTER TABLE `glpi_networking_wire`
                ADD UNIQUE end1_1 (`end1`,`end2`)";
      $DB->queryOrDie($query, "477");
   }

   if (!isIndex("glpi_networking_wire", "end2")) {
      $query = "ALTER TABLE `glpi_networking_wire`
                ADD INDEX (`end2`) ";
      $DB->queryOrDie($query, "41");
   }

   if (!isIndex("glpi_connect_wire", "end1")) {
      $query = "ALTER TABLE `glpi_connect_wire`
                ADD INDEX (`end1`) ";
      $DB->queryOrDie($query, "40");

      // Clean Table
      $query = "SELECT *
                FROM  `glpi_connect_wire`
                ORDER BY `type`, `end1`, `end2`";
      $result = $DB->query($query);

      $curend1 = -1;
      $curend2 = -1;
      $curtype = -1;
      while ($line = $DB->fetchArray($result)) {
         if ($curend1==$line['end1'] && $curend2==$line['end2'] && $curtype==$line['type']) {
            $q2 = "DELETE
                   FROM `glpi_connect_wire`
                   WHERE `ID`='".$line['ID']."'
                   LIMIT 1";
            $DB->query($q2);
         } else {
            $curend1 = $line['end1'];
            $curend2 = $line['end2'];
            $curtype = $line['type'];}
      }
      $DB->freeResult($result);
      $query = "ALTER TABLE `glpi_connect_wire`
                ADD UNIQUE end1_1 (`end1`,`end2`,`type`) ";
      $DB->queryOrDie($query, "478");
   }

   if (!isIndex("glpi_connect_wire", "end2")) {
      $query = "ALTER TABLE `glpi_connect_wire`
                ADD INDEX (`end2`) ";
      $DB->queryOrDie($query, "40");
   }

   if (!isIndex("glpi_connect_wire", "type")) {
      $query = "ALTER TABLE `glpi_connect_wire`
                ADD INDEX (`type`)";
      $DB->queryOrDie($query, "40");
   }

   if (!$DB->fieldExists("glpi_config", "ldap_condition", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `ldap_condition` VARCHAR(255) NOT NULL DEFAULT ''";
      $DB->queryOrDie($query, "48");
   }

   $query = "ALTER TABLE `glpi_users`
             CHANGE `type` `type` ENUM('normal', 'admin', 'post-only', 'super-admin') DEFAULT 'normal' NOT NULL";
   $DB->queryOrDie($query, "49");

   $ret["adminchange"] = false;
   //All "admin" users have to be set as "super-admin"
   if (!superAdminExists()) {
      $query = "UPDATE `glpi_users`
                SET `type` = 'super-admin'
                WHERE `type` = 'admin'";
      $DB->queryOrDie($query, "49");
      if ($DB->affectedRows() != 0) {
         $ret["adminchange"] = true;
      }
   }

   if (!$DB->fieldExists("glpi_users", "password_md5", false)) {
      $query = "ALTER TABLE `glpi_users`
                ADD `password_md5` VARCHAR(80) NOT NULL AFTER `password`";
      $DB->queryOrDie($query, "glpi_users.Password_md5");
   }

   if (!$DB->fieldExists("glpi_config", "permit_helpdesk", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `permit_helpdesk` VARCHAR(200) NOT NULL";
      $DB->queryOrDie($query, "glpi_config_permit_helpdesk");
   }

}
