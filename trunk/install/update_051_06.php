<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/// Update from 0.51x to 0.6
function update051to06(){
	global $DB,$LANG;

	echo "<p class='center'>Version 0.6 </p>";

	/*******************************GLPI 0.6***********************************************/
	$query= "UPDATE `glpi_tracking` SET `category`='0' WHERE `category` IS NULL;";
	$DB->query($query) or die("0.6 prepare for alter category tracking ".$LANG['update'][90].$DB->error());	

	$query= "ALTER TABLE `glpi_tracking` CHANGE `category` `category` INT(11) DEFAULT '0' NOT NULL";
	$DB->query($query) or die("0.6 alter category tracking ".$LANG['update'][90].$DB->error());	

	// state pour les template 
	if(!FieldExists("glpi_state_item","is_template")) {
		$query= "ALTER TABLE `glpi_state_item` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add is_template in state_item ".$LANG['update'][90].$DB->error());	
	}


	if(!TableExists("glpi_dropdown_cartridge_type")) {

		$query = "CREATE TABLE glpi_dropdown_cartridge_type (
			ID int(11) NOT NULL auto_increment,
			   name varchar(255) NOT NULL default '',
			   PRIMARY KEY  (ID)
				   ) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table dropdown_cartridge_type ".$LANG['update'][90].$DB->error());

		$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$LANG['cartridges'][11]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$LANG['cartridges'][10]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_cartridge_type (name) VALUES ('".$LANG['cartridges'][37]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_cartridge_type ".$LANG['update'][90].$DB->error());
	}

	// specific alarm pour les cartouches
	if(!FieldExists("glpi_cartridges_type","alarm")) {
		$query= "ALTER TABLE `glpi_cartridges_type` ADD `alarm` TINYINT DEFAULT '10' NOT NULL ;";
		$DB->query($query) or die("0.6 add alarm in cartridges_type ".$LANG['update'][90].$DB->error());	
	}

	// email for enterprises
	if(!FieldExists("glpi_enterprises","email")) {
		$query= "ALTER TABLE `glpi_enterprises` ADD `email` VARCHAR(255) NOT NULL;";
		$DB->query($query) or die("0.6 add email in enterprises ".$LANG['update'][90].$DB->error());	
	}

	// ldap_port for config
	if(!FieldExists("glpi_config","ldap_port")) {
		$query= "ALTER TABLE `glpi_config` ADD `ldap_port` VARCHAR(10) DEFAULT '389' NOT NULL AFTER `ID` ;";
		$DB->query($query) or die("0.6 add ldap_port in config ".$LANG['update'][90].$DB->error());	
	}

	// CAS configuration
	if(!FieldExists("glpi_config","cas_host")) {
		$query= "ALTER TABLE `glpi_config` ADD `cas_host` VARCHAR(255) NOT NULL ,
			ADD `cas_port` VARCHAR(255) NOT NULL ,
			ADD `cas_uri` VARCHAR(255) NOT NULL ;";
		$DB->query($query) or die("0.6 add cas config in config ".$LANG['update'][90].$DB->error());	
	}

	// Limit Item for contracts and correct template bug 
	if(!FieldExists("glpi_contracts","device_countmax")) {
		$query= "ALTER TABLE `glpi_contracts` ADD `device_countmax` INT DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add device_countmax in contracts ".$LANG['update'][90].$DB->error());	
	}

	if(!FieldExists("glpi_contract_device","is_template")) {
		$query= "ALTER TABLE `glpi_contract_device` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add is_template in contract_device ".$LANG['update'][90].$DB->error());
		//$query= " ALTER TABLE `glpi_contract_device` ADD INDEX (`is_template `) ";
		//$DB->query($query) or die("0.6 alter is_template in contract_device ".$LANG['update'][90].$DB->error());	
	}

	if(!FieldExists("glpi_doc_device","is_template")) {
		$query= "ALTER TABLE `glpi_doc_device` ADD `is_template` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add is_template in doc_device ".$LANG['update'][90].$DB->error());	
		$query= "ALTER TABLE `glpi_doc_device` ADD INDEX (`is_template`) ;";
		$DB->query($query) or die("0.6 alter is_template in doc_device ".$LANG['update'][90].$DB->error());	
	}

	// Contract Type to dropdown
	if(!TableExists("glpi_dropdown_contract_type")) {

		$query = "CREATE TABLE glpi_dropdown_contract_type (
			ID int(11) NOT NULL auto_increment,
			   name varchar(255) NOT NULL default '',
			   PRIMARY KEY  (ID)
				   ) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table dropdown_contract_type ".$LANG['update'][90].$DB->error());

		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][50]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][51]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][52]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][53]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][54]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][55]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO glpi_dropdown_contract_type (name) VALUES ('".$LANG['financial'][56]."');";
		$DB->query($query) or die("0.6 add entries to dropdown_contract_type ".$LANG['update'][90].$DB->error());

	}

	//// Update author and assign from tracking / followups
	if(!FieldExists("glpi_tracking","assign_type")) {

		// Create assin_type field
		$query= "ALTER TABLE `glpi_tracking` ADD `assign_type` TINYINT DEFAULT '0' NOT NULL AFTER `assign` ;";
		$DB->query($query) or die("0.6 add assign_type in tracking ".$LANG['update'][90].$DB->error());	

		$users=array();
		// Load All users
		$query="SELECT ID, name FROM glpi_users";
		$result=$DB->query($query);
		while($line = $DB->fetch_array($result)) {
			$users[$line["name"]]=$line["ID"];
		}
		$DB->free_result($result);

		// Update authors tracking
		$query= "UPDATE `glpi_tracking` SET `author`='0' WHERE `author` IS NULL;";
		$DB->query($query) or die("0.6 prepare for alter category tracking ".$LANG['update'][90].$DB->error());	

		// Load tracking authors tables
		$authors=array();
		$query="SELECT ID, author FROM glpi_tracking";
		$result=$DB->query($query);
		while($line = $DB->fetch_array($result)) {
			$authors[$line["ID"]]=$line["author"];
		}
		$DB->free_result($result);

		if (count($authors)>0)
			foreach ($authors as $ID => $val){
				if (isset($users[$val])){
					$query="UPDATE glpi_tracking SET author='".$users[$val]."' WHERE ID='$ID'";
					$DB->query($query);
				}
			}
		unset($authors);

		$query= "ALTER TABLE `glpi_tracking` CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
		$DB->query($query) or die("0.6 alter author in tracking ".$LANG['update'][90].$DB->error());	

		$assign=array();


		// Load tracking assign tables
		$query="SELECT ID, assign FROM glpi_tracking";
		$result=$DB->query($query);
		while($line = $DB->fetch_array($result)) {
			$assign[$line["ID"]]=$line["assign"];
		}
		$DB->free_result($result);


		if (count($assign)>0)
			foreach ($assign as $ID => $val){
				if (isset($users[$val])){
					$query="UPDATE glpi_tracking SET assign='".$users[$val]."', assign_type='".USER_TYPE."' WHERE ID='$ID'";
					$DB->query($query);
				}
			}
		unset($assign);

		// Update assign tracking
		$query= "ALTER TABLE `glpi_tracking` CHANGE `assign` `assign` INT(11) DEFAULT '0' NOT NULL";
		$DB->query($query) or die("0.6 alter assign in tracking ".$LANG['update'][90].$DB->error());	

		$authors=array();
		// Load followup authors tables
		$query="SELECT ID, author FROM glpi_followups";
		$result=$DB->query($query);
		while($line = $DB->fetch_array($result)) {
			$authors[$line["ID"]]=$line["author"];
		}
		$DB->free_result($result);


		if (count($authors)>0)
			foreach ($authors as $ID => $val){
				if (isset($users[$val])){
					$query="UPDATE glpi_followups SET author='".$users[$val]."' WHERE ID='$ID'";
					$DB->query($query);
				}
			}
		unset($authors);

		// Update authors tracking
		$query= "ALTER TABLE `glpi_followups` CHANGE `author` `author` INT(11) DEFAULT '0' NOT NULL";
		$DB->query($query) or die("0.6 alter author in followups ".$LANG['update'][90].$DB->error());	

		// Update Enterprise Tracking
		$query="SELECT computer, ID FROM glpi_tracking WHERE device_type='".ENTERPRISE_TYPE."'";
		$result=$DB->query($query);

		if ($DB->numrows($result)>0)
			while($line = $DB->fetch_array($result)) {
				$query="UPDATE glpi_tracking SET assign='".$line["computer"]."', assign_type='".ENTERPRISE_TYPE."', device_type='0', computer='0' WHERE ID='".$line["ID"]."'";
				$DB->query($query);
			}
		$DB->free_result($result);
	}

	// Add planning feature 

	if(!TableExists("glpi_tracking_planning")) {

		$query = "CREATE TABLE `glpi_tracking_planning` (
			`ID` bigint(20) NOT NULL auto_increment,
			`id_tracking` int(11) NOT NULL default '0',
			`id_assign` int(11) NOT NULL default '0',
			`begin` datetime NOT NULL default '0000-00-00 00:00:00',
			`end` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (`ID`),
			KEY `id_tracking` (`id_tracking`),
			KEY `begin` (`begin`),
			KEY `end` (`end`)
				) TYPE=MyISAM ;";

		$DB->query($query) or die("0.6 add table glpi_tracking_planning ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","planning_begin")) {
		$query="ALTER TABLE `glpi_config` ADD `planning_begin` TIME DEFAULT '08:00:00' NOT NULL";

		$DB->query($query) or die("0.6 add planning begin in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","planning_end")) {
		$query="ALTER TABLE `glpi_config` ADD `planning_end` TIME DEFAULT '20:00:00' NOT NULL";

		$DB->query($query) or die("0.6 add planning end in config".$LANG['update'][90].$DB->error());
	}

	// Merge glpi_users and glpi_prefs
	if(!FieldExists("glpi_users","language")) {
		// Create fields
		$query="ALTER TABLE `glpi_users` ADD `tracking_order` ENUM('yes', 'no') DEFAULT 'no' NOT NULL ;";
		$DB->query($query) or die("0.6 add tracking_order in users".$LANG['update'][90].$DB->error());
		$query="ALTER TABLE `glpi_users` ADD `language` VARCHAR(255) NOT NULL DEFAULT 'english';";
		$DB->query($query) or die("0.6 add language in users".$LANG['update'][90].$DB->error());

		// Move data
		$query="SELECT * from glpi_prefs";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$query2="UPDATE glpi_users SET language='".$data['language']."', tracking_order='".$data['tracking_order']."' WHERE name='".$data['username']."';";	
				$DB->query($query2) or die("0.6 move pref to users".$LANG['update'][90].$DB->error());	
			}
		$DB->free_result($result);
		// Drop glpi_prefs
		$query="DROP TABLE `glpi_prefs`;";
		$DB->query($query) or die("0.6 drop glpi_prefs".$LANG['update'][90].$DB->error());


	}

	// Create glpi_dropdown_ram_type
	if(!TableExists("glpi_dropdown_ram_type")) {
		$query = "CREATE TABLE `glpi_dropdown_ram_type` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_dropdown_ram_type ".$LANG['update'][90].$DB->error());
		$query="ALTER TABLE `glpi_device_ram` ADD `new_type` INT(11) DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 create new type field for glpi_device_ram ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('EDO');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('DDR');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('SDRAM');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_ram_type` (`name`) VALUES ('SDRAM-2');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());

		// Get values
		$query="SELECT * from glpi_dropdown_ram_type";
		$result=$DB->query($query);
		$val=array();
		while ($data=$DB->fetch_array($result)){
			$val[$data['name']]=$data['ID'];
		}	
		$DB->free_result($result);

		// Update glpi_device_ram
		$query="SELECT * from glpi_device_ram";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$query2="UPDATE glpi_device_ram SET new_type='".$val[$data['type']]."' WHERE ID ='".$data['ID']."';";
				$DB->query($query2);
			}
		$DB->free_result($result);
		// ALTER glpi_device_ram
		$query="ALTER TABLE `glpi_device_ram` DROP `type`;";
		$DB->query($query) or die("0.6 drop type in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());
		$query="ALTER TABLE `glpi_device_ram` CHANGE `new_type` `type` INT(11) DEFAULT '0' NOT NULL ";
		$DB->query($query) or die("0.6 rename new_type in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());


	}

	// Create external links
	if(!TableExists("glpi_links")) {
		$query = "CREATE TABLE `glpi_links` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_links ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_links_device")) {

		$query = "CREATE TABLE `glpi_links_device` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_links` int(11) NOT NULL default '0',
			`device_type` int(11) NOT NULL default '0',
			PRIMARY KEY  (`ID`),
			KEY `device_type` (`device_type`),
			KEY `FK_links` (`FK_links`),
			UNIQUE `device_type_2` (`device_type`,`FK_links`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_links_device ".$LANG['update'][90].$DB->error());
	}

	// Initial count page for printer
	if(!FieldExists("glpi_printers","initial_pages")) {
		$query="ALTER TABLE `glpi_printers` ADD `initial_pages` VARCHAR(30) DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add initial_pages in printers".$LANG['update'][90].$DB->error());
	}

	// Auto assign intervention
	if(!FieldExists("glpi_config","auto_assign")) {
		$query="ALTER TABLE `glpi_config` ADD `auto_assign` ENUM('0', '1') DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.6 add auto_assign in config".$LANG['update'][90].$DB->error());
	}

	// Create glpi_dropdown_network
	if(!TableExists("glpi_dropdown_network")) {
		$query = "CREATE TABLE `glpi_dropdown_network` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.6 add table glpi_dropdown_network ".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_computers","network")) {
		$query="ALTER TABLE `glpi_computers` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 a network in computers".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_printers","network")) {
		$query="ALTER TABLE `glpi_printers` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 add network in printers".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_networking","network")) {	
		$query="ALTER TABLE `glpi_networking` ADD `network` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 a network in networking".$LANG['update'][90].$DB->error());
	}

	// Create glpi_dropdown_domain
	if(!TableExists("glpi_dropdown_domain")) {
		$query = "CREATE TABLE `glpi_dropdown_domain` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.6 add table glpi_dropdown_domain ".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_computers","domain")) {
		$query="ALTER TABLE `glpi_computers` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 a domain in computers".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_printers","domain")) {
		$query="ALTER TABLE `glpi_printers` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 a domain in printers".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_networking","domain")) {
		$query="ALTER TABLE `glpi_networking` ADD `domain` INT(11) DEFAULT '0' NOT NULL AFTER `location` ;";
		$DB->query($query) or die("0.6 a domain in networking".$LANG['update'][90].$DB->error());
	}

	// Create glpi_dropdown_vlan
	if(!TableExists("glpi_dropdown_vlan")) {
		$query = "CREATE TABLE `glpi_dropdown_vlan` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.6 add table glpi_dropdown_vlan ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_networking_vlan")) {
		$query = "CREATE TABLE `glpi_networking_vlan` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_port` int(11) NOT NULL default '0',
			`FK_vlan` int(11) NOT NULL default '0',
			PRIMARY KEY  (`ID`),
			KEY `FK_port` (`FK_port`),
			KEY `FK_vlan` (`FK_vlan`),
			UNIQUE `FK_port_2` (`FK_port`,`FK_vlan`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.6 add table glpi_networking_vlan ".$LANG['update'][90].$DB->error());
	}

	// Global Peripherals
	if(!FieldExists("glpi_peripherals","is_global")) {
		$query="ALTER TABLE `glpi_peripherals` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
		$DB->query($query) or die("0.6 add is_global in peripherals".$LANG['update'][90].$DB->error());
	}

	// Global Monitors
	if(!FieldExists("glpi_monitors","is_global")) {
		$query="ALTER TABLE `glpi_monitors` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
		$DB->query($query) or die("0.6 add is_global in peripherals".$LANG['update'][90].$DB->error());
	}

	// Mailing Resa
	if(!FieldExists("glpi_config","mailing_resa_admin")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_resa_admin` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ;";
		$DB->query($query) or die("0.6 add mailing_resa_admin in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_resa_user")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_resa_user` VARCHAR(200) NOT NULL DEFAULT '1' AFTER `admin_email` ;";
		$DB->query($query) or die("0.6 add mailing_resa_user in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_resa_all_admin")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_resa_all_admin` VARCHAR(200) NOT NULL DEFAULT '0' AFTER `admin_email` ;";
		$DB->query($query) or die("0.6 add mailing_resa_all_admin in config".$LANG['update'][90].$DB->error());
	}

	// Mod�e ordinateurs
	if(!TableExists("glpi_dropdown_model")) {
		// model=type pour faciliter la gestion en post mise �jour : ya plus qu'a deleter les elements non voulu
		// cela conviendra a tout le monde en fonction de l'utilisation du champ type

		$query="ALTER TABLE `glpi_type_computers` RENAME `glpi_dropdown_model` ;";
		$DB->query($query) or die("0.6 rename table glpi_type_computers ".$LANG['update'][90].$DB->error());

		$query = "CREATE TABLE `glpi_type_computers` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_type_computers ".$LANG['update'][90].$DB->error());

		// copie model dans type
		$query="SELECT * FROM glpi_dropdown_model";
		$result=$DB->query($query);	
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$query="INSERT INTO `glpi_type_computers` (`ID`,`name`) VALUES ('".$data['ID']."','".addslashes($data['name'])."');";
				$DB->query($query) or die("0.6 insert value in glpi_type_computers ".$LANG['update'][90].$DB->error());		
			}
		$DB->free_result($result);

		$query="INSERT INTO `glpi_type_computers` (`name`) VALUES ('".$LANG['common'][52]."');";
		$DB->query($query) or die("0.6 insert value in glpi_type_computers ".$LANG['update'][90].$DB->error());
		$serverid=$DB->insert_id();

		// Type -> mod�e
		$query="ALTER TABLE `glpi_computers` CHANGE `type` `model` INT(11) DEFAULT NULL ";
		$DB->query($query) or die("0.6 add model in computers".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_computers` ADD `type` INT(11) DEFAULT NULL AFTER `model` ;";
		$DB->query($query) or die("0.6 add model in computers".$LANG['update'][90].$DB->error());

		// Update server values and drop flags_server
		$query="UPDATE glpi_computers SET type='$serverid' where flags_server='1'";
		$DB->query($query) or die("0.6 update type of computers".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_computers` DROP `flags_server`;";
		$DB->query($query) or die("0.6 drop type in glpi_dropdown_ram ".$LANG['update'][90].$DB->error());

	}

	if(!TableExists("glpi_consumables_type")) {

		$query = "CREATE TABLE `glpi_consumables_type` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			`ref` varchar(255) NOT NULL default '',
			`location` int(11) NOT NULL default '0',
			`type` tinyint(4) NOT NULL default '0',
			`FK_glpi_enterprise` int(11) NOT NULL default '0',
			`tech_num` int(11) default '0',
			`deleted` enum('Y','N') NOT NULL default 'N',
			`comments` text NOT NULL,
			`alarm` tinyint(4) NOT NULL default '10',
			PRIMARY KEY  (`ID`),
			KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
			KEY `tech_num` (`tech_num`),
			KEY `deleted` (`deleted`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_consumables_type ".$LANG['update'][90].$DB->error());

		$query = "CREATE TABLE `glpi_consumables` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_glpi_consumables_type` int(11) default NULL,
			`date_in` date default NULL,
			`date_out` date default NULL,
			PRIMARY KEY  (`ID`),
			KEY `FK_glpi_cartridges_type` (`FK_glpi_consumables_type`),
			KEY `date_in` (`date_in`),
			KEY `date_out` (`date_out`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_consumables ".$LANG['update'][90].$DB->error());

		$query = "CREATE TABLE `glpi_dropdown_consumable_type` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_dropdown_consumable_type ".$LANG['update'][90].$DB->error());

	}

	// HDD connect type
	if(!TableExists("glpi_dropdown_hdd_type")) {
		$query = "CREATE TABLE `glpi_dropdown_hdd_type` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.6 add table glpi_dropdown_hdd_type ".$LANG['update'][90].$DB->error());

		$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('IDE');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('SATA');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$LANG['update'][90].$DB->error());
		$query="INSERT INTO `glpi_dropdown_hdd_type` (`name`) VALUES ('SCSI');";
		$DB->query($query) or die("0.6 insert value in glpi_dropdown_hdd_type ".$LANG['update'][90].$DB->error());

		// Insertion des enum dans l'ordre - le alter garde donc les bonne valeurs
		$query="ALTER TABLE `glpi_device_hdd` CHANGE `interface` `interface` INT(11) DEFAULT '0' NOT NULL";
		$DB->query($query) or die("0.6 alter interface of  glpi_device_hdd".$LANG['update'][90].$DB->error());
	}

}

?>
