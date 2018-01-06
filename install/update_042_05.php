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

/// Update from 0.42 to 0.5
function update042to05() {
   global $DB;

   echo "<p class='center'>Version 0.5 </p>";

   // Augmentation taille itemtype
   $query = "ALTER TABLE `glpi_event_log`
             CHANGE `itemtype` `itemtype` VARCHAR(20) NOT NULL";
   $DB->queryOrDie($query, "4204");

   // Correction des itemtype tronque
   $query = "UPDATE `glpi_event_log`
             SET `itemtype` = 'reservation'
             WHERE `itemtype` = 'reservatio'";
   $DB->queryOrDie($query, "4204");

   /*******************************GLPI 0.5***********************************************/
   //pass all templates to computers
   if (!$DB->fieldExists("glpi_computers", "is_template", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `is_template` ENUM('0','1') DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 alter computers add is_template");

      $query = "ALTER TABLE `glpi_computers`
                ADD `tplname` VARCHAR(200) DEFAULT NULL ";
      $DB->queryOrDie($query, "0.5 alter computers add tplname");

      $query = "SELECT *
                FROM `glpi_templates`";
      $result = $DB->query($query);

      while ($line = $DB->fetch_array($result)) {
         $line = Toolbox::addslashes_deep($line);
         $query2 = "INSERT INTO `glpi_computers`
                        (`name`, `osver`, `processor_speed`, `serial`, `otherserial`, `ram`,
                         `hdspace`, `contact`, `contact_num`, `comments`, `achat_date`,
                         `date_fin_garantie`, `maintenance`, `os`, `hdtype`, `sndcard`, `moboard`,
                         `gfxcard`, `network`, `ramtype`, `location`, `processor`, `type`,
                         `is_template`, `tplname`)";

         if (empty($line["location"])) {
            $line["location"] = 0;
         }

         $query2 .= " VALUES ('".$line["name"]."', '".$line["osver"]."',
                              '".$line["processor_speed"]."', '".$line["serial"]."',
                              '".$line["otherserial"]."', '".$line["ram"]."',
                              '".$line ["hdspace"]."', '".$line["contact"]."',
                              '".$line["contact_num"]."', '".$line["comments"]."',
                              '".$line["achat_date"]."', '".$line["date_fin_garantie"]."',
                              '".$line["maintenance"]."', '".$line["os"]."', '".$line["hdtype"]."',
                              '".$line["sndcard"]."', '".$line["moboard"]."', '".$line["gfxcard"]."',
                              '".$line["network"]."', '".$line["ramtype"]."',
                              '".$line["location"]."', '".$line["processor"]."', '".$line["type"]."',
                              '1', '".$line["templname"]."')";
         $DB->queryOrDie($query2, "0.5-convert template 2 computers");
      }

      $DB->free_result($result);
      $query = "DROP TABLE `glpi_templates`";
      $DB->queryOrDie($query, "0.5 drop table templates");

      $query = "SELECT `ID`
                FROM `glpi_computers`
                WHERE `tplname` = 'Blank Template'";
      $result = $DB->query($query);

      if ($DB->numrows($result)==0) {
         $query = "INSERT INTO `glpi_computers`
                          (`is_template`, `tplname`, `name`, `comment`, `contact`, `contact_num`,
                           `serial`, `otherserial`)
                   VALUES ('1', 'Blank Template', '', '', '', '', '', '')";
         $DB->queryOrDie($query, "0.5 add blank template");
      }
      $DB->free_result($result);
   }

   //New internal peripherals ( devices ) config

   if (!$DB->tableExists("glpi_computer_device")) {
      $query = "CREATE TABLE `glpi_computer_device` (
                  `ID` int(11) NOT NULL auto_increment,
                  `specificity` varchar(250) NOT NULL default '',
                  `device_type` tinyint(4) NOT NULL default '0',
                  `FK_device` int(11) NOT NULL default '0',
                  `FK_computers` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY (`device_type`),
                  KEY (`device_type`,`FK_device`),
                  KEY (`FK_computers`)
               ) TYPE=MyISAM;";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_computer_device`");
   }

   if (!$DB->tableExists("glpi_device_gfxcard")) {
      $query = "CREATE TABLE `glpi_device_gfxcard` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(120) NOT NULL default '',
                  `ram` varchar(10) NOT NULL default '',
                  `interface` enum('AGP','PCI','PCI-X','Other') NOT NULL default 'AGP',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM;";
      $DB->queryOrDie($query, "0.5 create table `glpi_device_gfxcard`");
      compDpd2Device(GFX_DEVICE, "gfxcard", "gfxcard", "gfxcard");
   }

   if (!$DB->tableExists("glpi_device_hdd")) {
      $query = "CREATE TABLE `glpi_device_hdd` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(100) NOT NULL default '',
                  `rpm` varchar(20) NOT NULL default '',
                  `interface` enum('IDE','SATA','SCSI') NOT NULL default 'IDE',
                  `cache` varchar(20) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_device_hdtype`");
      compDpd2Device(HDD_DEVICE, "hdd", "hdtype", "hdtype", "hdspace");
   }

   if (!$DB->tableExists("glpi_device_iface")) {
      $query = "CREATE TABLE `glpi_device_iface` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(120) NOT NULL default '',
                  `bandwidth` varchar(20) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5- CREATE TABLE `glpi_device_iface`");
      compDpd2Device(NETWORK_DEVICE, "iface", "network", "network");
   }

   if (!$DB->tableExists("glpi_device_moboard")) {
      $query = "CREATE TABLE `glpi_device_moboard` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(100) NOT NULL default '',
                  `chipset` varchar(120) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_device_moboard`");
      compDpd2Device(MOBOARD_DEVICE, "moboard", "moboard", "moboard");
   }

   if (!$DB->tableExists("glpi_device_processor")) {
      $query = "CREATE TABLE `glpi_device_processor` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(120) NOT NULL default '',
                  `frequence` int(11) NOT NULL default '0',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_device_processor`");
      compDpd2Device(PROCESSOR_DEVICE, "processor", "processor", "processor", "processor_speed");
   }

   if (!$DB->tableExists("glpi_device_ram")) {
      $query = "CREATE TABLE `glpi_device_ram` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(100) NOT NULL default '',
                  `type` enum('EDO','DDR','SDRAM','SDRAM-2') NOT NULL default 'EDO',
                  `frequence` varchar(8) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_device_ram`");
      compDpd2Device(RAM_DEVICE, "ram", "ram", "ramtype", "ram");
   }

   if (!$DB->tableExists("glpi_device_sndcard")) {
      $query = "CREATE TABLE `glpi_device_sndcard` (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` varchar(120) NOT NULL default '',
                  `type` varchar(100) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_device_sndcard");
      compDpd2Device(SND_DEVICE, "sndcard", "sndcard", "sndcard");
   }

   if (!$DB->tableExists("glpi_device_power")) {
      $query = "CREATE TABLE glpi_device_power (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` VARCHAR(255) NOT NULL default '',
                  `power` VARCHAR(20) NOT NULL default '',
                  `atx` enum('Y','N') NOT NULL default 'Y',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY (`ID`),
                  KEY FK_glpi_enterprise (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query);
   }

   if (!$DB->tableExists("glpi_device_case")) {
      $query = "CREATE TABLE glpi_device_case(
                  `ID` int(11) NOT NULL AUTO_INCREMENT ,
                  `designation` VARCHAR(255) NOT NULL default '',
                  `format` enum('Grand', 'Moyen', 'Micro') NOT NULL default 'Moyen',
                  `comment` text NOT NULL ,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY (`ID`) ,
                  KEY FK_glpi_enterprise(`FK_glpi_enterprise`)
                ) TYPE = MyISAM";
      $DB->queryOrDie($query);
   }

   if (!$DB->tableExists("glpi_device_drive")) {
      $query = "CREATE TABLE `glpi_device_drive` (
                  `ID` INT NOT NULL AUTO_INCREMENT ,
                  `designation` VARCHAR(255) NOT NULL ,
                  `is_writer` ENUM('Y', 'N') DEFAULT 'Y' NOT NULL ,
                  `speed` VARCHAR(30) NOT NULL ,
                  `interface` ENUM('IDE', 'SATA', 'SCSI') NOT NULL ,
                  `comment` TEXT NOT NULL ,
                  `FK_glpi_enterprise` INT NOT NULL ,
                  `specif_default` VARCHAR(250) NOT NULL,
                  KEY FK_glpi_enterprise(`FK_glpi_enterprise`),
                  PRIMARY KEY (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query);
   }

   if (!$DB->tableExists("glpi_device_pci")) {
      $query = "CREATE TABLE glpi_device_pci (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` VARCHAR(255) NOT NULL default '',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY (`ID`),
                  KEY FK_glpi_enterprise (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query);
   }

   if (!$DB->tableExists("glpi_device_control")) {
      $query = "CREATE TABLE glpi_device_control (
                  `ID` int(11) NOT NULL auto_increment,
                  `designation` VARCHAR(255) NOT NULL default '',
                  `interface` enum('IDE','SATA','SCSI','USB') NOT NULL default 'IDE',
                  `raid` enum('Y','N') NOT NULL default 'Y',
                  `comment` text NOT NULL,
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `specif_default` VARCHAR(250) NOT NULL,
                  PRIMARY KEY (`ID`),
                  KEY FK_glpi_enterprise (`FK_glpi_enterprise`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query);
   }

   // END new internal devices.

   if (!$DB->tableExists("glpi_enterprises")) {
      $query = "CREATE TABLE `glpi_enterprises` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(50) NOT NULL default '',
                  `type` int(11) NOT NULL default '0',
                  `address` text NOT NULL,
                  `website` varchar(100) NOT NULL default '',
                  `phonenumber` varchar(20) NOT NULL default '',
                  `comments` text NOT NULL,
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  PRIMARY KEY  (`ID`),
                  KEY `deleted` (`deleted`),
                  KEY `type` (`type`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_enterprise");
   }

   /// Base connaissance
   if (!$DB->tableExists("glpi_dropdown_kbcategories")) {
      $query = "CREATE TABLE `glpi_dropdown_kbcategories` (
                  `ID` int(11) NOT NULL auto_increment,
                  `parentID` int(11) NOT NULL default '0',
                  `name` varchar(255) NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`parentID`),
                  UNIQUE KEY (`parentID`, `name`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_dropdown_kbcategories");

      $query = "CREATE TABLE `glpi_kbitems` (
                  `ID` int(11) NOT NULL auto_increment,
                  `categoryID` int(11) NOT NULL default '0',
                  `question` text NOT NULL,
                  `answer` text NOT NULL,
                  `faq` enum('yes','no') NOT NULL default 'no',
                  PRIMARY KEY  (`ID`),
                  KEY (`categoryID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_kbitems");
   }

   // Comment reservation
   if (!$DB->fieldExists("glpi_reservation_resa", "comment", false)) {
      $query = "ALTER TABLE `glpi_reservation_resa`
                ADD `comment` VARCHAR(255) NOT NULL ";
      $DB->queryOrDie($query, "0.5 alter reservation add comment");
   }

   // Tracking categorie
   if (!$DB->tableExists("glpi_dropdown_tracking_category")) {
      $query = "CREATE TABLE `glpi_dropdown_tracking_category` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE `glpi_dropdown_tracking_category");
   }

   if (!$DB->fieldExists("glpi_tracking", "category", false)) {
      $query= "ALTER TABLE `glpi_tracking`
               ADD `category` INT(11) ";
      $DB->queryOrDie($query, "0.5 alter tracking add categorie");
   }

   // Nouvelle gestion des software et licenses
   if (!$DB->fieldExists("glpi_licenses", "oem", false)) {
      $query = "ALTER TABLE `glpi_licenses`
                ADD `oem` ENUM('N', 'Y') DEFAULT 'N' NOT NULL ,
                ADD `oem_computer` INT(11) NOT NULL,
                ADD `buy` ENUM('Y', 'N') DEFAULT 'Y' NOT NULL";
      $DB->queryOrDie($query, "0.5 alter licenses add oem + buy");

      $query = "ALTER TABLE `glpi_software`
                ADD `is_update` ENUM('N', 'Y') DEFAULT 'N' NOT NULL ,
                ADD `update_software` INT(11) NOT NULL DEFAULT '-1'";
      $DB->queryOrDie($query, "0.5 alter software add update");
   }

   // Couleur pour les priorites
   if (!$DB->fieldExists("glpi_config", "priority_1", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `priority_1` VARCHAR(200) DEFAULT '#fff2f2' NOT NULL,
                ADD `priority_2` VARCHAR(200) DEFAULT '#ffe0e0' NOT NULL,
                ADD `priority_3` VARCHAR(200) DEFAULT '#ffcece' NOT NULL,
                ADD `priority_4` VARCHAR(200) DEFAULT '#ffbfbf' NOT NULL,
                ADD `priority_5` VARCHAR(200) DEFAULT '#ffadad' NOT NULL ";
      $DB->queryOrDie($query, "0.5 alter config add priority_X");
   }

   // Gestion des cartouches
   if (!$DB->tableExists("glpi_cartridges")) {
      $query = "CREATE TABLE `glpi_cartridges` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
                  `FK_glpi_printers` int(11) NOT NULL default '0',
                  `date_in` date default NULL,
                  `date_use` date default NULL,
                  `date_out` date default NULL,
                  `pages` int(11)  NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_cartridges_type`),
                  KEY (`FK_glpi_printers`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_cartridges");

      $query = "CREATE TABLE `glpi_cartridges_type` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `ref` varchar(255) NOT NULL default '',
                  `location` int(11) NOT NULL default '0',
                  `type` tinyint(4) NOT NULL default '0',
                  `FK_glpi_enterprise` int(11) NOT NULL default '0',
                  `tech_num` int(11) default '0',
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  `comments` text NOT NULL,
                  PRIMARY KEY  (`ID`),
                  KEY (`FK_glpi_enterprise`),
                  KEY (`tech_num`),
                  KEY (`deleted`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_cartridges_type");

      $query = "CREATE TABLE `glpi_cartridges_assoc` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
                  `FK_glpi_type_printer` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_glpi_type_printer` (`FK_glpi_type_printer`,`FK_glpi_cartridges_type`),
                  KEY (`FK_glpi_cartridges_type`),
                  KEY (`FK_glpi_type_printer`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_cartridges_assoc");
   }

   //// DEBUT INSERTION PARTIE GESTION
   if (!$DB->tableExists("glpi_contracts")) {
      $query = "CREATE TABLE `glpi_contacts` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `phone` varchar(30) NOT NULL default '',
                  `phone2` varchar(30) NOT NULL default '',
                  `fax` varchar(30) NOT NULL default '',
                  `email` varchar(255) NOT NULL default '',
                  `type` tinyint(4) NOT NULL default '1',
                  `comments` text NOT NULL,
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_contact");

      $query = "CREATE TABLE `glpi_dropdown_enttype` (
                  `ID` INT NOT NULL AUTO_INCREMENT ,
                  `name` VARCHAR(255) NOT NULL ,
                  PRIMARY KEY (`ID`)
                )";
      $DB->queryOrDie($query, "23");

      $query = "CREATE TABLE `glpi_contact_enterprise` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_enterprise` int(11) NOT NULL default '0',
                  `FK_contact` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contact`),
                  KEY (`FK_enterprise`),
                  KEY (`FK_contact`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_contact_enterprise");

      $query = "CREATE TABLE `glpi_contracts` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `num` varchar(255) NOT NULL default '',
                  `cost` float NOT NULL default '0',
                  `contract_type` int(11) NOT NULL default '0',
                  `begin_date` date default NULL,
                  `duration` tinyint(4) NOT NULL default '0',
                  `notice` tinyint(4) NOT NULL default '0',
                  `periodicity` tinyint(4) NOT NULL default '0',
                  `facturation` tinyint(4) NOT NULL default '0',
                  `bill_type` int(11) NOT NULL default '0',
                  `comments` text NOT NULL,
                  `compta_num` varchar(255) NOT NULL default '',
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  `week_begin_hour` time NOT NULL default '00:00:00',
                  `week_end_hour` time NOT NULL default '00:00:00',
                  `saturday_begin_hour` time NOT NULL default '00:00:00',
                  `saturday_end_hour` time NOT NULL default '00:00:00',
                  `saturday` enum('Y','N') NOT NULL default 'N',
                  `monday_begin_hour` time NOT NULL default '00:00:00',
                  `monday_end_hour` time NOT NULL default '00:00:00',
                  `monday` enum('Y','N') NOT NULL default 'N',
                  PRIMARY KEY  (`ID`),
                  KEY `contract_type` (`contract_type`),
                  KEY `begin_date` (`begin_date`),
                  KEY `bill_type` (`bill_type`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_contract");

      $query = "CREATE TABLE `glpi_contract_device` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_contract` int(11) NOT NULL default '0',
                  `FK_device` int(11) NOT NULL default '0',
                  `device_type` tinyint(4) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_contract` (`FK_contract`,`FK_device`,`device_type`),
                  KEY (`FK_contract`),
                  KEY (`FK_device`,`device_type`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_contract_device");

      $query = "CREATE TABLE `glpi_contract_enterprise` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_enterprise` int(11) NOT NULL default '0',
                  `FK_contract` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contract`),
                  KEY (`FK_enterprise`),
                  KEY (`FK_contract`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_contrat_enterprise");

      $query = "CREATE TABLE `glpi_infocoms` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_device` int(11) NOT NULL default '0',
                  `device_type` tinyint(4) NOT NULL default '0',
                  `buy_date` date NOT NULL default '0000-00-00',
                  `use_date` date NOT NULL default '0000-00-00',
                  `warranty_duration` tinyint(4) NOT NULL default '0',
                  `warranty_info` varchar(255) NOT NULL default '',
                  `FK_enterprise` int(11) default NULL,
                  `num_commande` varchar(50) NOT NULL default '',
                  `bon_livraison` varchar(50) NOT NULL default '',
                  `num_immo` varchar(50) NOT NULL default '',
                  `value` float NOT NULL default '0',
                  `warranty_value` float default NULL,
                  `amort_time` tinyint(4) NOT NULL default '0',
                  `amort_type` varchar(20) NOT NULL default '',
                  `amort_coeff` float NOT NULL default '0',
                  `comments` text NOT NULL,
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_device` (`FK_device`,`device_type`),
                  KEY `FK_enterprise` (`FK_enterprise`),
                  KEY `buy_date` (`buy_date`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 CREATE TABLE glpi_infocom");

      ///// Move warranty infos from item to infocoms.

      // Update Warranty Infos
      updateWarrantyInfos("glpi_computers", COMPUTER_TYPE);
      updateWarrantyInfos("glpi_printers", PRINTER_TYPE);
      updateWarrantyInfos("glpi_networking", NETWORKING_TYPE);
      updateWarrantyInfos("glpi_monitors", MONITOR_TYPE);
      updateWarrantyInfos("glpi_peripherals", PERIPHERAL_TYPE);

      // Update Maintenance Infos
      if (isMaintenanceUsed()) {
         $query = "INSERT INTO `glpi_contracts`
                   VALUES (1, 'Maintenance', '', '0', 5, '2005-01-01', 120, 0, 0, 0, 0, '', '', 'N',
                           '00:00:00', '00:00:00', '00:00:00', '00:00:00', 'N', '00:00:00',
                           '00:00:00', 'N')";
         $result = $DB->queryOrDie($query, "0.5 insert_init for update maintenance");

         if ($result) {
            $query = "SELECT `ID`
                      FROM `glpi_contracts`";
            $result = $DB->queryOrDie($query, "0.5 select_init for update maintenance");

            if ($result) {
               $data       = $DB->fetch_array($result);
               $IDcontract = $data["ID"];
               updateMaintenanceInfos("glpi_computers", COMPUTER_TYPE, $IDcontract);
               updateMaintenanceInfos("glpi_printers", PRINTER_TYPE, $IDcontract);
               updateMaintenanceInfos("glpi_networking", NETWORKING_TYPE, $IDcontract);
               updateMaintenanceInfos("glpi_monitors", MONITOR_TYPE, $IDcontract);
               updateMaintenanceInfos("glpi_peripherals", PERIPHERAL_TYPE, $IDcontract);
            }
         }

      } else {
         dropMaintenanceField();
      }

   }
   //// FIN INSERTION PARTIE GESTION

   // Merge de l'OS et de la version
   if ($DB->fieldExists("glpi_computers", "osver", false)) {
      // Recuperation des couples existants
      $query = "SELECT DISTINCT `glpi_computers`.`os` AS ID ,
                                `glpi_computers`.`osver` AS VERS,
                                `glpi_dropdown_os`.`name` AS NAME
                FROM `glpi_computers`
                LEFT JOIN `glpi_dropdown_os` ON `glpi_dropdown_os`.`ID` = `glpi_computers`.`os`
                ORDER BY `glpi_computers`.`os`, `glpi_computers`.`osver`";
      $result = $DB->queryOrDie($query, "0.5 select for update OS");

      $valeur   = [];
      $curros   = -1;
      $currvers = "-------------------------";
      while ($data=$DB->fetch_array($result)) {
         // Nouvel OS -> update de l'element de dropdown
         if ($data["ID"]!=$curros) {
            $curros = $data["ID"];

            if (!empty($data["VERS"])) {
               $query_update = "UPDATE `glpi_dropdown_os`
                                SET `name` = '".$data["NAME"]." - ".$data["VERS"]."'
                                WHERE `ID` = '".$data["ID"]."'";
               $DB->queryOrDie($query_update, "0.5 update for update OS");
            }

         } else { // OS deja mis a jour -> creation d'un nouvel OS et mise a jour des elements
            $newname      = $data["NAME"]." - ".$data["VERS"];
            $query_insert = "INSERT INTO `glpi_dropdown_os`
                                    (`name`)
                             VALUES ('$newname')";
            $DB->queryOrDie($query_insert, "0.5 insert for update OS");

            $query_select = "SELECT `ID`
                             FROM `glpi_dropdown_os`
                             WHERE `name` = '$newname'";
            $res = $DB->queryOrDie($query_select, "0.5 select for update OS");

            if ($DB->numrows($res)==1) {
               $query_update = "UPDATE `glpi_computers`
                                SET `os` . ='".$DB->result($res, 0, "ID")."'
                                WHERE `os` = '".$data["ID"]."'
                                      AND `osver` = '".$data["VERS"]."'";
               $DB->queryOrDie($query_update, "0.5 update2 for update OS");
            }
         }
      }

      $DB->free_result($result);
      $query_alter = "ALTER TABLE `glpi_computers`
                      DROP `osver` ";
      $DB->queryOrDie($query_alter, "0.5 alter for update OS");
   }

   // Ajout Fabriquant computer
   if (!$DB->fieldExists("glpi_computers", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout Fabriquant printer
   if (!$DB->fieldExists("glpi_printers", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout Fabriquant networking
   if (!$DB->fieldExists("glpi_networking", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout Fabriquant monitor
   if (!$DB->fieldExists("glpi_monitors", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout Fabriquant software
   if (!$DB->fieldExists("glpi_software", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout Fabriquant peripheral
   if (!$DB->fieldExists("glpi_peripherals", "FK_glpi_enterprise", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD `FK_glpi_enterprise` INT(11) DEFAULT '0' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field manufacturer");

      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`FK_glpi_enterprise`)";
      $DB->queryOrDie($query, "0.5 alter field manufacturer");
   }

   // Ajout deleted peripheral
   if (!$DB->fieldExists("glpi_peripherals", "deleted", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout deleted software
   if (!$DB->fieldExists("glpi_software", "deleted", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout deleted monitor
   if (!$DB->fieldExists("glpi_monitors", "deleted", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout deleted networking
   if (!$DB->fieldExists("glpi_networking", "deleted", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout deleted printer
   if (!$DB->fieldExists("glpi_printers", "deleted", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout deleted computer
   if (!$DB->fieldExists("glpi_computers", "deleted", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `deleted` ENUM('Y', 'N') DEFAULT 'N' NOT NULL ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`deleted`)";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   // Ajout template peripheral
   if (!$DB->fieldExists("glpi_peripherals", "is_template", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ,
                ADD `tplname` VARCHAR(255) ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "INSERT INTO `glpi_peripherals`
                       (`is_template`, `tplname`, `name`, `contact`, `contact_num`, `comments`,
                        `serial`, `otherserial`, `brand`)
                VALUES ('1', 'Blank Template', '', '', '', '', '', '', '')";
      $DB->queryOrDie($query, "0.5 add blank template");

      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.5 alter field is_template");
   }

   // Ajout template software
   if (!$DB->fieldExists("glpi_software", "is_template", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ,
                ADD `tplname` VARCHAR(255) ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "INSERT INTO `glpi_software`
                       (`is_template`, `tplname`, `name`, `comments`, `version`)
                VALUES ('1', 'Blank Template', '', '', '')";
      $DB->queryOrDie($query, "0.5 add blank template");

      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.5 alter field is_template");
   }

   // Ajout template monitor
   if (!$DB->fieldExists("glpi_monitors", "is_template", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ,
                ADD `tplname` VARCHAR(255) ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "INSERT INTO `glpi_monitors`
                       (`is_template`, `tplname`, `name`, `contact`, `contact_num`, `comments`,
                        `serial`, `otherserial`)
                VALUES ('1', 'Blank Template', '', '', '', '', '', '')";
      $DB->queryOrDie($query, "0.5 add blank template");

      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.5 alter field is_template");
   }

   if (!isIndex("glpi_computers", "is_template")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`is_template`) ";
      $DB->queryOrDie($query, "5");
   }

   // Ajout template networking
   if (!$DB->fieldExists("glpi_networking", "is_template", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ,
                ADD `tplname` VARCHAR(255) ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "INSERT INTO `glpi_networking`
                       (`is_template`, `tplname`, `name`, `contact`, `contact_num`, `comments`,
                        `ram`, `otherserial`, `serial`)
                VALUES ('1', 'Blank Template', '', '', '', '', '', '', '')";
      $DB->queryOrDie($query, "0.5 add blank template");

      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.5 alter field is_template");
   }

   // Ajout template printer
   if (!$DB->fieldExists("glpi_printers", "is_template", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ,
                ADD `tplname` VARCHAR(255) ";
      $DB->queryOrDie($query, "0.5 add field deleted");

      $query = "INSERT INTO `glpi_printers`
                       (`is_template`, `tplname`, `name`, `contact`, `contact_num`, `comments`,
                        `serial`, `otherserial`, `ramSize`)
                VALUES ('1', 'Blank Template', '', '', '', '', '', '', '')";
      $DB->queryOrDie($query, "0.5 add blank template");

      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`is_template`)";
      $DB->queryOrDie($query, "0.5 alter field is_template");
   }

   // Ajout date_mod
   if (!$DB->fieldExists("glpi_printers", "date_mod", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `date_mod` DATETIME DEFAULT NULL";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`date_mod`)";
      $DB->queryOrDie($query, "0.5 alter field date_mod");
   }

   if (!isIndex("glpi_computers", "date_mod")) {
      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`date_mod`) ";
      $DB->queryOrDie($query, "5");
   }

   // Ajout date_mod
   if (!$DB->fieldExists("glpi_monitors", "date_mod", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `date_mod` DATETIME DEFAULT NULL";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`date_mod`)";
      $DB->queryOrDie($query, "0.5 alter field date_mod");
   }

   // Ajout date_mod
   if (!$DB->fieldExists("glpi_software", "date_mod", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `date_mod` DATETIME DEFAULT NULL";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`date_mod`)";
      $DB->queryOrDie($query, "0.5 alter field date_mod");
   }

   // Ajout date_mod
   if (!$DB->fieldExists("glpi_networking", "date_mod", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `date_mod` DATETIME DEFAULT NULL";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`date_mod`)";
      $DB->queryOrDie($query, "0.5 alter field date_mod");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_computers", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_computers`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_computers`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_networking", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_networking`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_printers", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_printers`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_printers`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_monitors", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_monitors`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_monitors`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_software", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `location`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_software`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_peripherals", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_peripherals`
                ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_peripherals`
                ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->fieldExists("glpi_software", "tech_num", false)) {
      $query = "ALTER TABLE `glpi_software`
                ADD `tech_num` int(11) NOT NULL default '0'";
      $DB->queryOrDie($query);

      $query = "ALTER TABLE `glpi_software` ADD INDEX (`tech_num`)";
      $DB->queryOrDie($query, "0.5 alter field tech_num");
   }

   // Ajout tech_num
   if (!$DB->tableExists("glpi_type_docs")) {
      $query = "CREATE TABLE glpi_type_docs (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `ext` varchar(10) NOT NULL default '',
                  `icon` varchar(255) NOT NULL default '',
                  `mime` varchar(100) NOT NULL default '',
                  `upload` enum('Y','N') NOT NULL default 'Y',
                  `date_mod` datetime default NULL,
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `extension` (`ext`),
                  KEY (`upload`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "Error creating table typedoc");

      $query = "INSERT INTO `glpi_type_docs`
                       (`ID`, `name`, `ext`, `icon`, `mime`, `upload`, `date_mod`)
                VALUES (1, 'JPEG', 'jpg', 'jpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (2, 'PNG', 'png', 'png-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (3, 'GIF', 'gif', 'gif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (4, 'BMP', 'bmp', 'bmp-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (5, 'Photoshop', 'psd', 'psd-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (6, 'TIFF', 'tif', 'tif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (7, 'AIFF', 'aiff', 'aiff-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (8, 'Windows Media', 'asf', 'asf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (9, 'Windows Media', 'avi', 'avi-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (44, 'C source', 'c', '', '', 'Y', '2004-12-13 19:47:22'),
                       (27, 'RealAudio', 'rm', 'rm-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (16, 'Midi', 'mid', 'mid-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (17, 'QuickTime', 'mov', 'mov-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (18, 'MP3', 'mp3', 'mp3-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (19, 'MPEG', 'mpg', 'mpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (20, 'Ogg Vorbis', 'ogg', 'ogg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (24, 'QuickTime', 'qt', 'qt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (10, 'BZip', 'bz2', 'bz2-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (25, 'RealAudio', 'ra', 'ra-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (26, 'RealAudio', 'ram', 'ram-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (11, 'Word', 'doc', 'doc-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (12, 'DjVu', 'djvu', '', '', 'Y', '2004-12-13 19:47:21'),
                       (42, 'MNG', 'mng', '', '', 'Y', '2004-12-13 19:47:22'),
                       (13, 'PostScript', 'eps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (14, 'GZ', 'gz', 'gz-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (37, 'WAV', 'wav', 'wav-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (15, 'HTML', 'html', 'html-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (34, 'Flash', 'swf', '', '', 'Y', '2004-12-13 19:47:22'),
                       (21, 'PDF', 'pdf', 'pdf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (22, 'PowerPoint', 'ppt', 'ppt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (23, 'PostScript', 'ps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (40, 'Windows Media', 'wmv', '', '', 'Y', '2004-12-13 19:47:22'),
                       (28, 'RTF', 'rtf', 'rtf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
                       (29, 'StarOffice', 'sdd', 'sdd-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (30, 'StarOffice', 'sdw', 'sdw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (31, 'Stuffit', 'sit', 'sit-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (43, 'Adobe Illustrator', 'ai', 'ai-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (32, 'OpenOffice Impress', 'sxi', 'sxi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (33, 'OpenOffice', 'sxw', 'sxw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (46, 'DVI', 'dvi', 'dvi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (35, 'TGZ', 'tgz', 'tgz-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (36, 'texte', 'txt', 'txt-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (49, 'RedHat/Mandrake/SuSE', 'rpm', 'rpm-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (38, 'Excel', 'xls', 'xls-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (39, 'XML', 'xml', 'xml-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (41, 'Zip', 'zip', 'zip-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (45, 'Debian', 'deb', 'deb-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (47, 'C header', 'h', '', '', 'Y', '2004-12-13 19:47:22'),
                       (48, 'Pascal', 'pas', '', '', 'Y', '2004-12-13 19:47:22'),
                       (50, 'OpenOffice Calc', 'sxc', 'sxc-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (51, 'LaTeX', 'tex', 'tex-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (52, 'GIMP multi-layer', 'xcf', 'xcf-dist.png', '', 'Y', '2004-12-13 19:47:22'),
                       (53, 'JPEG', 'jpeg', 'jpg-dist.png', '', 'Y', '2005-03-07 22:23:17')";

      $DB->queryOrDie($query, "Error inserting elements in table typedoc");
   }

   if (!$DB->tableExists("glpi_docs")) {
      $query = "CREATE TABLE `glpi_docs` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL default '',
                  `filename` varchar(255) NOT NULL default '',
                  `rubrique` int(11) NOT NULL default '0',
                  `mime` varchar(30) NOT NULL default '',
                  `date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
                  `comment` text NOT NULL,
                  `deleted` enum('Y','N') NOT NULL default 'N',
                  PRIMARY KEY  (`ID`),
                  KEY `rubrique` (`rubrique`),
                  KEY `deleted` (`deleted`),
                  KEY `date_mod` (`date_mod`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "Error creating table docs");
   }

   if (!$DB->tableExists("glpi_doc_device")) {
      $query = "CREATE TABLE `glpi_doc_device` (
                  `ID` int(11) NOT NULL auto_increment,
                  `FK_doc` int(11) NOT NULL default '0',
                  `FK_device` int(11) NOT NULL default '0',
                  `device_type` tinyint(4) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  UNIQUE KEY `FK_doc` (`FK_doc`,`FK_device`,`device_type`),
                  KEY FK_`doc_2` (`FK_doc`),
                  KEY FK_`device` (`FK_device`,`device_type`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "Error creating table docs");
   }

   if (!$DB->tableExists("glpi_dropdown_rubdocs")) {
      $query = "CREATE TABLE `glpi_dropdown_rubdocs` (
                  `ID` int(11) NOT NULL auto_increment,
                  `name` varchar(255) default NULL,
                  PRIMARY KEY  (`ID`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "Error creating table docs");
   }

   if (!isIndex("glpi_contacts", "deleted")) {
      $query = "ALTER TABLE `glpi_contacts`
                ADD INDEX `deleted` (`deleted`) ";
      $DB->queryOrDie($query, "0.5 alter field deleted");
   }

   if (!isIndex("glpi_contacts", "type")) {
      $query = "ALTER TABLE `glpi_contacts`
                ADD INDEX `type` (`type`) ";
      $DB->queryOrDie($query, "0.5 alter field type");
   }

   if (!isIndex("glpi_event_log", "itemtype")) {
      $query = "ALTER TABLE `glpi_event_log`
                ADD INDEX (`itemtype`) ";
      $DB->queryOrDie($query, "0.5 alter field itemtype");
   }

   if (!isIndex("glpi_followups", "date")) {
      $query = "ALTER TABLE `glpi_followups`
                ADD INDEX (`date`) ";
      $DB->queryOrDie($query, "0.5 alter field date");
   }

   if (!isIndex("glpi_tracking", "category")) {
      $query = "ALTER TABLE `glpi_tracking`
                ADD INDEX (`category`) ";
      $DB->queryOrDie($query, "0.5 alter field category");
   }

   if (!$DB->fieldExists("glpi_config", "date_fiscale", false)) {
      $query = "ALTER TABLE `glpi_config`
                ADD `date_fiscale` date NOT NULL default '2005-12-31'";
      $DB->queryOrDie($query, "0.5 add field date_fiscale");
   }

   if (!$DB->fieldExists("glpi_networking", "ifmac", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `ifmac` char(30) NOT NULL default ''";
      $DB->queryOrDie($query, "0.5 add field ifmac");
   }

   if (!$DB->fieldExists("glpi_networking", "ifaddr", false)) {
      $query = "ALTER TABLE `glpi_networking`
                ADD `ifaddr` char(30) NOT NULL default ''";
      $DB->queryOrDie($query, "0.5 add field ifaddr");
   }

   if (!$DB->tableExists("glpi_repair_item")) {
      $query = "CREATE TABLE `glpi_repair_item` (
                  `ID` int(11) NOT NULL auto_increment,
                  `device_type` tinyint(4) NOT NULL default '0',
                  `id_device` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`ID`),
                  KEY `device_type` (`device_type`),
                  KEY `device_type_2` (`device_type`,`id_device`)
                ) TYPE=MyISAM";
      $DB->queryOrDie($query, "0.5 create glpirepair_item table");
   }

   if ($DB->tableExists("glpi_prefs")&&!$DB->fieldExists("glpi_prefs", "username", false)) {
      if (isIndex("glpi_prefs", "user")) {
         $query = " ALTER TABLE `glpi_prefs`
                    DROP INDEX `user`";
         $DB->queryOrDie($query, "0.5 drop key user");
      }

      $query = " ALTER TABLE `glpi_prefs`
                 CHANGE `user` `username` VARCHAR(80) NOT NULL";
      $DB->queryOrDie($query, "0.5 change user to username");

      $query = "ALTER TABLE `glpi_prefs`
                ADD UNIQUE (`username`) ";
      $DB->queryOrDie($query, "0.5 alter field username");
   }

   //Mise a jour 0.5 verification des prefs pour chaque user.
   if ($DB->tableExists("glpi_prefs")) {
      $query   = "SELECT `ID`, `name`
                  FROM `glpi_users`";

      $query2 = "SELECT `ID`, `username`
                 FROM `glpi_prefs`";

      $result  = $DB->query($query);
      $result2 = $DB->query($query2);

      if ($DB->numrows($result) != $DB->numrows($result2)) {
         $users = [];
         $i     = 0;
         while ($line = $DB->fetch_array($result2)) {
            $prefs[$i] = $line["username"];
            $i++;
         }
         while ($line = $DB->fetch_array($result)) {
            if (!in_array($line["name"], $prefs)) {
               $query_insert = "INSERT INTO `glpi_prefs`
                                       (`username` , `tracking_order` , `language`)
                                VALUES ('".$line["name"]."', 'no', 'english')";
               $DB->queryOrDie($query_insert, "glpi maj prefs");
            }
         }
      }
      $DB->free_result($result);
      $DB->free_result($result2);
   }

}


/// Update to 0.5 : date difference used for update
function date_diff050($from, $to) {

   $from = strtotime($from);
   $to   = strtotime($to);
   if ($from > $to) {
      $t    = $to;
      $to   = $from;
      $from = $t;
   }

   $year1  = date("Y", $from);
   $year2  = date("Y", $to);
   $month1 = date("n", $from);
   $month2 = date("n", $to);

   if ($month2 < $month1) {
      $month2 += 12;
      $year2 --;
   }

   $months = $month2 - $month1;
   $years  = $year2 - $year1;
   return (12*$years+$months);
}


/** Update to 0.5 : Update maintenance information
 * @param $table table name
 * @param $type item type
 * @param $ID item ID
**/
function updateMaintenanceInfos($table, $type, $ID) {
   global $DB;

   $elements = [];
   $query    = "SELECT `ID`
                FROM `$table`
                WHERE `maintenance` = '1'";
   $result = $DB->query($query);

   while ($data=$DB->fetch_array($result)) {
      $query_insert = "INSERT INTO `glpi_contract_device`
                              (`FK_contract`, `FK_device` ,`device_type`)
                       VALUES ('$ID', '".$data["ID"]."', '$type')";
      $result_insert = $DB->queryOrDie($query_insert, "0.5 insert for update maintenance");
   }
   $DB->free_result($result);

   $query_drop  =  "ALTER TABLE `$table`
                    DROP `maintenance`";
   $result_drop = $DB->queryOrDie($query_drop, "0.5 drop for update maintenance");
}


/** Update to 0.5 : Update warranty information
 * @param $table table name
 * @param $type item type
**/
function updateWarrantyInfos($table, $type) {
   global $DB;

   $elements = [];
   $query    = "SELECT `ID`, `achat_date`, `date_fin_garantie`
                FROM `$table`
                ORDER BY `achat_date`, `date_fin_garantie`";
   $result = $DB->queryOrDie($query, "0.5 select for update warranty");

   while ($data=$DB->fetch_array($result)) {
      if (($data['achat_date']!="0000-00-00" && !empty($data['achat_date']))
          ||($data['date_fin_garantie']!="0000-00-00" && !empty($data['date_fin_garantie']))) {

         $IDitem = $data['ID'];

         if ($data['achat_date']=="0000-00-00" && !empty($data['achat_date'])) {
            $achat_date = date("Y-m-d");
         } else {
            $achat_date = $data['achat_date'];
         }

         $duration = 0;
         if ($data['date_fin_garantie']!="0000-00-00" && !empty($data['date_fin_garantie'])) {
            $duration = round(date_diff050($achat_date, $data['date_fin_garantie']), 2);
         }
         $query_insert  = "INSERT INTO `glpi_infocoms`
                                  (`device_type`, `FK_device`, `buy_date`, `warranty_duration`,
                                   `comments`)
                           VALUES ('$type', '$IDitem', '".$achat_date."', '$duration', '')";
         $result_insert = $DB->queryOrDie($query_insert, "0.5 insert for update warranty");
      }
   }
   $DB->free_result($result);

   $query_drop  =  "ALTER TABLE `$table`
                    DROP `achat_date`";
   $result_drop = $DB->queryOrDie($query_drop, "0.5 drop1 for update warranty");

   $query_drop  = "ALTER TABLE `$table`
                   DROP `date_fin_garantie`";
   $result_drop = $DB->queryOrDie($query_drop, "0.5 drop2 for update warranty");
}


/// Update to 0.5 :Is maintenance used ?
function isMaintenanceUsed() {
   global $DB;

   $tables = ["glpi_computers", "glpi_monitors", "glpi_networking", "glpi_peripherals",
                   "glpi_printers"];

   foreach ($tables as $key => $table) {
      $query  = "SELECT `ID`
                 FROM `$table`
                 WHERE `maintenance` = '1'";
      $result = $DB->queryOrDie($query, "0.5 find for update maintenance");

      if ($DB->numrows($result)>0) {
         return true;
      }
   }
   return false;
}


/// Update to 0.5 :drop maintenance field ?
function dropMaintenanceField() {
   global $DB;

   $tables = ["glpi_computers", "glpi_monitors", "glpi_networking", "glpi_peripherals",
                   "glpi_printers"];

   foreach ($tables as $key => $table) {
      $query  = "ALTER TABLE `$table`
                 DROP `maintenance`";
      $result = $DB->queryOrDie($query, "0.5 alter for update maintenance");
   }
}


/**
 * Update to 0.5 : Get data from old dropdowns to new devices
 *
 * This function assure to keep clean data and integrity, during the change from
 * computers-dropdown to computers devices. Then delete the unused old elements.
 *
 * @param $devtype integer the devtype number
 * @param $devname string the device table name (end of the name (glpi_device_thisparam))
 * @param $dpdname string the dropdown table name (end of the name (glpi_dropdown_thisparam))
 * @param $compDpdName string the name of the dropdown foreign key on glpi_computers (eg : hdtype, processor)
 * @param $specif string the name of the dropdown value entry on glpi_computer (eg : hdspace, processor_speed) optionnal argument.
 *
 * @return nothing if everything is good, else display mysql query and error.
 */
function compDpd2Device($devtype, $devname, $dpdname, $compDpdName, $specif = '') {
   global $DB;

   $query = "SELECT *
             FROM `glpi_dropdown_".$dpdname."`";

   $result = $DB->query($query);
   while ($lndropd = $DB->fetch_array($result)) {
      $query2 = "INSERT INTO `glpi_device_".$devname."`
                        (`designation`, `comment`, `specif_default`)
                 VALUES ('".addslashes($lndropd["name"])."', '', '')";
      $DB->queryOrDie($query2, "unable to transfer ".$dpdname." to ".$devname." ");

      $devid   = $DB->insert_id();
      $query3  = "SELECT *
                  FROM `glpi_computers`
                  WHERE `".$compDpdName."` = '".$lndropd["ID"]."'";
      $result3 = $DB->query($query3);

      while ($lncomp = $DB->fetch_array($result3)) {
         $query4 = "INSERT INTO `glpi_computer_device`
                           (`device_type`, `FK_device`, `FK_computers`)
                    VALUES ('$devtype', '".$devid."', '".$lncomp["ID"]."')";

         if (!empty($specif)) {
            $queryspecif = "SELECT `".$specif."`
                            FROM `glpi_computers`
                            WHERE `ID` = '".$lncomp["ID"]."'";

            if ($resultspecif = $DB->query($queryspecif)) {
               $query4 = "INSERT INTO `glpi_computer_device`
                                 (`specificity`, `device_type`, `FK_device`, `FK_computers`)
                          VALUES ('".$DB->result($resultspecif, 0, $specif)."', '$devtype',
                                  '".$devid."', '".$lncomp["ID"]."')";
            }
         }
         $DB->queryOrDie($query4, "unable to migrate from ".$dpdname." to ".$devname." for item computer:".$lncomp["ID"]);
      }
   }
   $DB->free_result($result);

   //Delete unused elements (dropdown on the computer table, dropdown table and specif)
   $query = "ALTER TABLE `glpi_computers`
             DROP `".$compDpdName."`";
   $DB->queryOrDie($query);

   $query = "DROP TABLE `glpi_dropdown_".$dpdname."`";
   $DB->queryOrDie($query);

   if (!empty($specif)) {
      $query = "ALTER TABLE `glpi_computers`
                DROP `".$specif."`";
      $DB->queryOrDie($query);
   }
}

