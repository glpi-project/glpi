<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

// Update from 0.65 to 0.68
function update065to068(){
	global $db,$lang;

	if(!TableExists("glpi_profiles")) {
		$query="CREATE TABLE `glpi_profiles` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) default NULL,
			`interface` varchar(50) NOT NULL default 'helpdesk',
			`is_default` enum('0','1') NOT NULL default '0',
			`computer` char(1) default NULL,
			`monitor` char(1) default NULL,
			`software` char(1) default NULL,
			`networking` char(1) default NULL,
			`printer` char(1) default NULL,
			`peripheral` char(1) default NULL,
			`cartridge` char(1) default NULL,
			`consumable` char(1) default NULL,
			`phone` char(1) default NULL,
			`notes` char(1) default NULL,
			`contact_enterprise` char(1) default NULL,
			`document` char(1) default NULL,
			`contract_infocom` char(1) default NULL,
			`knowbase` char(1) default NULL,
			`faq` char(1) default NULL,
			`reservation_helpdesk` char(1) default NULL,
			`reservation_central` char(1) default NULL,
			`reports` char(1) default NULL,
			`ocsng` char(1) default NULL,
			`dropdown` char(1) default NULL,
			`device` char(1) default NULL,
			`typedoc` char(1) default NULL,
			`link` char(1) default NULL,
			`config` char(1) default NULL,
			`search_config` char(1) default NULL,
			`update` char(1) default NULL,
			`profile` char(1) default NULL,
			`user` char(1) default NULL,
			`group` char(1) default NULL,
			`logs` char(1) default NULL,
			`reminder_public` char(1) default NULL,
			`backup` char(1) default NULL,
			`create_ticket` char(1) default NULL,
			`delete_ticket` char(1) default NULL,
			`comment_ticket` char(1) default NULL,
			`comment_all_ticket` char(1) default NULL,
			`update_ticket` char(1) default NULL,
			`own_ticket` char(1) default NULL,
			`steal_ticket` char(1) default NULL,
			`assign_ticket` char(1) default NULL,
			`show_ticket` char(1) default NULL,
			`show_full_ticket` char(1) default NULL,
			`observe_ticket` char(1) default NULL,
			`show_planning` char(1) default NULL,
			`show_all_planning` char(1) default NULL,
			`statistic` char(1) default NULL,
			`password_update` char(1) default NULL,
			`helpdesk_hardware` tinyint(2) NOT NULL  DEFAULT '0',
			`helpdesk_hardware_type` int(11) NOT NULL  DEFAULT '0',
			PRIMARY KEY  (`ID`),
			KEY `interface` (`interface`)
				) TYPE=MyISAM;";

		$db->query($query) or die("0.68 add profiles ".$lang["update"][90].$db->error());

		$helpdesk_link_type=array(COMPUTER_TYPE,PRINTER_TYPE,MONITOR_TYPE,PERIPHERAL_TYPE,NETWORKING_TYPE,PHONE_TYPE,NETWORKING_TYPE);
		$checksum=0;
		foreach ($helpdesk_link_type as $val)
			$checksum+=pow(2,$val);

		$query="INSERT INTO `glpi_profiles` VALUES (1, 'post-only', 'helpdesk', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'r', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL, '1', '1', '$checksum');";
		$db->query($query) or die("0.68 add post-only profile ".$lang["update"][90].$db->error());
		$query="INSERT INTO `glpi_profiles` VALUES (2, 'normal', 'central', '0', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', '1', 'r', 'r', NULL, NULL, NULL, 'r', 'r', NULL, NULL, 'r', NULL, 'r', 'r', NULL, NULL, NULL, '1', '1', '1', '0', '0', '1', '0', '0', '1', '0', '1', '1', '0', '1', '1', '1', '$checksum');";
		$db->query($query) or die("0.68 add normal profile ".$lang["update"][90].$db->error());
		$query="INSERT INTO `glpi_profiles` VALUES (3, 'admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w', NULL, 'w', 'r', 'r', 'w', 'w', NULL, NULL, NULL, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '3', '$checksum');";
		$db->query($query) or die("0.68 add admin profile ".$lang["update"][90].$db->error());
		$query="INSERT INTO `glpi_profiles` VALUES (4, 'super-admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'r', 'w', 'w', 'w', 'r', 'w', 'w', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '3', '$checksum');";
		$db->query($query) or die("0.68 add super-admin profile ".$lang["update"][90].$db->error());

	}


	if (FieldExists("glpi_config","post_only_followup")){

		$query="SELECT post_only_followup FROM glpi_config WHERE ID='1' ";
		$result=$db->query($query);
		if ($db->result($result,0,0)){
			$query="UPDATE glpi_profiles SET comment_ticket='1';";
			$db->query($query) or die("0.68 update default glpi_profiles ".$lang["update"][90].$db->error());
		}

		// TO DO : drop post_only_followup field of glpi_config
		$query="ALTER TABLE `glpi_config` DROP `post_only_followup`;";
		$db->query($query) or die("0.68 drop post_only_followup in glpi_config ".$lang["update"][90].$db->error());
	}

	$profiles=array("post-only"=>1,"normal"=>2,"admin"=>3,"super-admin"=>4);
	if(!TableExists("glpi_users_profiles")) {	
		$query="CREATE TABLE `glpi_users_profiles` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`FK_users` INT NOT NULL DEFAULT '0',
			`FK_profiles` INT NOT NULL DEFAULT '0',
			KEY `FK_users` (`FK_users`),
			KEY `FK_profiles` (`FK_profiles`),
			UNIQUE `FK_users_profiles` (`FK_users`,`FK_profiles`)
				) TYPE = MYISAM ;";
		$db->query($query) or die("0.68 create users_profiles table ".$lang["update"][90].$db->error());


		$query="SELECT ID, type FROM glpi_users";
		$result=$db->query($query);
		if ($db->numrows($result)){
			while ($data=$db->fetch_array($result)){
				$query2="INSERT INTO glpi_users_profiles (FK_users,FK_profiles) VALUES ('".$data['ID']."','".$profiles[$data['type']]."')";
				$db->query($query2) or die("0.68 insert new users_profiles ".$lang["update"][90].$db->error());
			}
		}
		$query="ALTER TABLE `glpi_users` DROP `type`, DROP `can_assign_job`;";
		$db->query($query) or die("0.68 drop type and can_assign_job from users ".$lang["update"][90].$db->error());
	}

	if(!TableExists("glpi_mailing")) {	
		$query="CREATE TABLE `glpi_mailing` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`type`  varchar(255) default NULL,
			`FK_item` INT NOT NULL DEFAULT '0',
			`item_type` INT NOT NULL DEFAULT '0',
			KEY `type` (`type`),
			KEY `FK_item` (`FK_item`),
			KEY `item_type` (`item_type`),
			KEY `items` (`item_type`,`FK_item`),
			UNIQUE `mailings` (`type`,`FK_item`,`item_type`)
				) TYPE = MYISAM ;";
		$db->query($query) or die("0.68 create mailing table ".$lang["update"][90].$db->error());

		// MAILING TYPE
		define("USER_MAILING_TYPE","1");
		define("PROFILE_MAILING_TYPE","2");
		define("GROUP_MAILING_TYPE","3");

		// MAILING USERS TYPE
		define("ADMIN_MAILING","1");
		define("ASSIGN_MAILING","2");
		define("AUTHOR_MAILING","3");
		define("OLD_ASSIGN_MAILING","4");


		$query="SELECT * from glpi_config WHERE ID='1'";
		$result=$db->query($query);
		if ($result){
			$data=$db->fetch_assoc($result);
			if ($data["mailing_resa_all_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('resa','".$profiles["admin"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_resa_user"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('resa','".AUTHOR_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_resa_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('resa','".ADMIN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_all_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('new','".$profiles["admin"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing new all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_all_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".$profiles["admin"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing update all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_all_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('followup','".$profiles["admin"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing followup all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_all_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('finish','".$profiles["admin"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_all_normal"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('new','".$profiles["normal"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing new all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_all_normal"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".$profiles["normal"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing update all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_all_normal"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('followup','".$profiles["normal"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing followup all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_all_normal"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('finish','".$profiles["normal"]."','".PROFILE_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('new','".ADMIN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing new admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".ADMIN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing update admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('followup','".ADMIN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing followup admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_admin"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('finish','".ADMIN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_attrib"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('new','".ASSIGN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing new attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_attrib"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".ASSIGN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing update attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_attrib"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('followup','".ASSIGN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing followup attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_attrib"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('finish','".ASSIGN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_attrib_attrib"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".OLD_ASSIGN_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish attrib ".$lang["update"][90].$db->error());
			}	
			if ($data["mailing_new_user"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('new','".AUTHOR_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing new user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_user"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('update','".AUTHOR_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing update user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_user"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('followup','".AUTHOR_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing followup user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_user"]){
				$query2="INSERT INTO `glpi_mailing` (type,FK_item,item_type) VALUES ('finish','".AUTHOR_MAILING."','".USER_MAILING_TYPE."');";
				$db->query($query2) or die("0.68 populate mailing finish user ".$lang["update"][90].$db->error());
			}

		}

		$query=" ALTER TABLE `glpi_config`
			DROP `mailing_resa_all_admin`,
			     DROP `mailing_resa_user`,
			     DROP `mailing_resa_admin`,
			     DROP `mailing_new_admin`,
			     DROP `mailing_update_admin`,
			     DROP `mailing_followup_admin`,
			     DROP `mailing_finish_admin`,
			     DROP `mailing_new_all_admin`,
			     DROP `mailing_update_all_admin`,
			     DROP `mailing_followup_all_admin`,
			     DROP `mailing_finish_all_admin`,
			     DROP `mailing_new_all_normal`,
			     DROP `mailing_update_all_normal`,
			     DROP `mailing_followup_all_normal`,
			     DROP `mailing_finish_all_normal`,
			     DROP `mailing_new_attrib`,
			     DROP `mailing_update_attrib`,
			     DROP `mailing_followup_attrib`,
			     DROP `mailing_finish_attrib`,
			     DROP `mailing_new_user`,
			     DROP `mailing_update_user`,
			     DROP `mailing_followup_user`,
			     DROP `mailing_finish_user`,
			     DROP `mailing_attrib_attrib`;";
		$db->query($query) or die("0.68 delete mailing config from config ".$lang["update"][90].$db->error());
	}


	// Convert old content of knowbase in HTML And add new fields
	if(TableExists("glpi_kbitems")){

		if(!FieldExists("glpi_kbitems","author")) {
			// convert
			$query="SELECT * FROM glpi_kbitems ";
			$result=$db->query($query);

			if ($db->numrows($result)>0){
				while($line = $db->fetch_array($result)) {
					$query="UPDATE glpi_kbitems SET answer='".addslashes(rembo($line["answer"]))."' WHERE ID='".$line["ID"]."'";
					$db->query($query) 	 or die("0.68 convert knowbase to xhtml ".$lang["update"][90].$db->error());
				}
				$db->free_result($result);
			}
			// add new fields
			$query="ALTER TABLE `glpi_kbitems` ADD `author` INT( 11 ) NOT NULL DEFAULT '0' AFTER `faq` ,
				ADD `view` INT( 11 ) NOT NULL DEFAULT '0' AFTER `author` ,
				ADD `date` DATETIME NULL DEFAULT NULL AFTER `view` ,
				ADD `date_mod` DATETIME NULL DEFAULT NULL AFTER `date` ;";
			$db->query($query) or die("0.68 add  fields in knowbase ".$lang["update"][90].$db->error());

		}
	} // fin convert

	// Add Level To Dropdown 
	$dropdowntree_tables=array("glpi_dropdown_locations","glpi_dropdown_kbcategories");
	foreach ($dropdowntree_tables as $t)
		if(!FieldExists($t,"level")) {	
			$query="ALTER TABLE `$t` ADD `level` INT(11)";
			$db->query($query) or die("0.68 add level to $t ".$lang["update"][90].$db->error());
			regenerateTreeCompleteName($t);
		}

	if(FieldExists("glpi_config","root_doc")) {	
		$query="ALTER TABLE `glpi_config` DROP  `root_doc`";
		$db->query($query) or die("0.68 drop root_doc ".$lang["update"][90].$db->error());
		regenerateTreeCompleteName($t);
	}
	// add smtp config
	if(!FieldExists("glpi_config","smtp_mode")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `smtp_mode` tinyint(4) DEFAULT '0' NOT NULL,
			ADD  `smtp_host` varchar(255),
			ADD  `smtp_port` int(11) DEFAULT '25' NOT NULL,
			ADD  `smtp_username` varchar(255),
			ADD  `smtp_password` varchar(255);";
		$db->query($query) or die("0.68 add smtp config ".$lang["update"][90].$db->error());
	}

	$map_lang=array("french"=>"fr_FR","english"=>"en_GB","deutsch"=>"de_DE","italian"=>"it_IT","castillano"=>"es_ES","portugese"=>"pt_PT","dutch"=>"nl_NL","hungarian"=>"hu_HU","polish"=>"po_PO","rumanian"=>"ro_RO","russian"=>"ru_RU");
	foreach ($map_lang as $old => $new){
		$query="UPDATE glpi_users SET language='$new' WHERE language='$old';";
		$db->query($query) or die("0.68 update $new lang user setting ".$lang["update"][90].$db->error());

	}
	$query="SELECT default_language FROM glpi_config WHERE ID='1';";
	$result=$db->query($query);
	$def_lang=$db->result($result,0,0);
	if (isset($map_lang[$def_lang])){
		$query="UPDATE glpi_config SET default_language='".$map_lang[$def_lang]."' WHERE ID='1';";
		$db->query($query) or die("0.68 update default_language in config ".$lang["update"][90].$db->error());
	}

	// Improve link management
	if(!FieldExists("glpi_links","link")) {	
		$query="ALTER TABLE `glpi_links` CHANGE `name` `link` VARCHAR( 255 ) NULL DEFAULT NULL ";
		$db->query($query) or die("0.68 rename name in link ".$lang["update"][90].$db->error());
		$query="ALTER TABLE `glpi_links` ADD `name` VARCHAR( 255 ) NULL AFTER `ID`";
		$db->query($query) or die("0.68 add name in link ".$lang["update"][90].$db->error());
		$query="UPDATE glpi_links SET name=link";
		$db->query($query) or die("0.68 init name field in link ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_config","ldap_field_name")) {
		$query="UPDATE `glpi_config` SET  ldap_login = ldap_field_name ";
		$db->query($query);
		$query="ALTER TABLE `glpi_config` DROP `ldap_field_name` ";
		$db->query($query) or die("0.68 drop ldap_field_name in config ".$lang["update"][90].$db->error());
	}

	// Security user Helpdesk
	$query="UPDATE glpi_users SET password='', active='0' WHERE name='Helpdesk';";
	$db->query($query) or die("0.68 security update for user Helpdesk ".$lang["update"][90].$db->error());

	if(!FieldExists("glpi_ocs_config","import_general_name")) {	
		$query = "ALTER TABLE `glpi_ocs_config` ADD `import_general_name` INT( 2 ) NOT NULL DEFAULT '0' AFTER `import_printer`"; 
		$db->query($query) or die("0.68 add import_name in ocs_config ".$lang["update"][90].$db->error());
	}
	// Clean default values for devices
	if(FieldExists("glpi_device_drive","speed")) {	
		$query = "ALTER TABLE `glpi_device_drive` CHANGE `speed` `speed` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter speed in device_drive ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_gfxcard","ram")) {	
		$query = "ALTER TABLE `glpi_device_gfxcard` CHANGE `ram` `ram` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter ram in device_gfxcard ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_hdd","rpm")) {	
		$query = "ALTER TABLE `glpi_device_hdd` CHANGE `rpm` `rpm` VARCHAR( 255 ) NULL , CHANGE `cache` `cache` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter rpm and cache in device_hdd ".$lang["update"][90].$db->error());
	}	

	if(FieldExists("glpi_device_iface","bandwidth")) {	
		$query = "ALTER TABLE `glpi_device_iface` CHANGE `bandwidth` `bandwidth` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter bandwidth in device_iface ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_moboard","chipset")) {	
		$query = "ALTER TABLE `glpi_device_moboard` CHANGE `chipset` `chipset` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter chipset in device_moboard ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_drive","speed")) {	
		$query = "ALTER TABLE `glpi_device_drive` CHANGE `speed` `speed` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter speed in device_drive ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_power","power")) {	
		$query = "ALTER TABLE `glpi_device_power` CHANGE `power` `power` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter power in device_power ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_ram","frequence")) {	
		$query = "ALTER TABLE `glpi_device_ram` CHANGE `frequence` `frequence` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter frequence in device_ram ".$lang["update"][90].$db->error());
	}

	if(FieldExists("glpi_device_sndcard","type")) {	
		$query = "ALTER TABLE `glpi_device_sndcard` CHANGE `type` `type` VARCHAR( 255 ) NULL "; 
		$db->query($query) or die("0.68 alter type in device_sndcard ".$lang["update"][90].$db->error());
	}	

	if(!FieldExists("glpi_display","FK_users")) {	
		$query = "ALTER TABLE `glpi_display` ADD `FK_users` INT NOT NULL DEFAULT '0'"; 
		$db->query($query) or die("0.68 alter display add FK_users".$lang["update"][90].$db->error());
		$query="ALTER TABLE `glpi_display` DROP INDEX `type_2`, ADD UNIQUE `type_2` ( `type` , `num` , `FK_users` )";
		$db->query($query) or die("0.68 alter display update unique key".$lang["update"][90].$db->error());
	}	

	// Proxy configuration

	if(!FieldExists("glpi_config","proxy_name")) {	
		$query = "ALTER TABLE `glpi_config` ADD `proxy_name` VARCHAR( 255 ) NULL, ADD `proxy_port` VARCHAR( 255 ) DEFAULT '8080' NOT NULL, ADD `proxy_user` VARCHAR( 255 ) NULL, ADD `proxy_password` VARCHAR( 255 ) NULL"; 
		$db->query($query) or die("0.68 add proxy fields to glpi_config".$lang["update"][90].$db->error());
	}	
	// Log update with followups
	if(!FieldExists("glpi_config","followup_on_update_ticket")) {	
		$query = "ALTER TABLE `glpi_config` ADD `followup_on_update_ticket` tinyint(4) DEFAULT '1' NOT NULL;"; 
		$db->query($query) or die("0.68 add followup_on_update_ticket to glpi_config".$lang["update"][90].$db->error());
	}	


	// Ticket Category -> Tree mode
	if(!FieldExists("glpi_dropdown_tracking_category","completename")) {	
		$query = "ALTER TABLE glpi_dropdown_tracking_category ADD `parentID` INT NOT NULL DEFAULT '0' AFTER `ID`,
			ADD `completename` TEXT NOT NULL DEFAULT '' AFTER `name`,
			ADD `level` INT NULL AFTER `comments` "; 
				$db->query($query) or die("0.68 glpi_dropdown_tracking_category to dropdown tree".$lang["update"][90].$db->error());
		regenerateTreeCompleteName("glpi_dropdown_tracking_category");
	}
	// User to Document
	if(!FieldExists("glpi_docs","FK_users")) {	
		$query = "ALTER TABLE `glpi_docs` ADD `FK_users` int DEFAULT '0' NOT NULL, ADD `FK_tracking` int DEFAULT '0' NOT NULL;"; 
		$db->query($query) or die("0.68 add FK_users to docs".$lang["update"][90].$db->error());
	}	
	// Import Ocs TAG
	if(!FieldExists("glpi_ocs_config","import_tag_field")) {	
		$query = "ALTER TABLE `glpi_ocs_config` ADD `import_tag_field` varchar( 255 ) NULL;"; 
		$db->query($query) or die("0.68 add import_tag_field to ocs_config".$lang["update"][90].$db->error());
	}
	// Use ocs soft dict
	if(!FieldExists("glpi_ocs_config","use_soft_dict")) {	
		$query = "ALTER TABLE `glpi_ocs_config` ADD `use_soft_dict` char( 1 ) DEFAULT '1';"; 
		$db->query($query) or die("0.68 add use_soft_dict to ocs_config".$lang["update"][90].$db->error());
	}

	// Link user and group to hardware
	$new_link=array("computers","software","monitors","networking","peripherals","printers","phones");

	foreach ($new_link as $table)
		if(!FieldExists("glpi_$table","FK_users")) {	
			$query="ALTER TABLE `glpi_$table` ADD `FK_users` INT(11) DEFAULT '0', ADD `FK_groups` INT(11) DEFAULT '0';";
			$db->query($query) or die("0.65 add link user group field in $table ".$lang["update"][90].$db->error());

			if ($table != "software"){
				// Update using name field of users
				$query2="SELECT glpi_users.ID AS USER, glpi_$table.ID AS ID FROM glpi_$table LEFT JOIN glpi_users ON (glpi_$table.contact = glpi_users.name AND glpi_$table.contact <> '') WHERE glpi_users.ID IS NOT NULL";
				$result2=$db->query($query2);
				if ($db->numrows($result2)>0){
					while ($data=$db->fetch_assoc($result2)){
						$query3="UPDATE glpi_$table SET FK_users='".$data["USER"]."' WHERE ID='".$data["ID"]."'";
						$db->query($query3);
					}
				}
				// Update using realname field of users
				$query2="SELECT glpi_users.ID AS USER, glpi_$table.ID AS ID FROM glpi_$table LEFT JOIN glpi_users ON (glpi_$table.contact = glpi_users.realname AND glpi_$table.contact <> '') WHERE glpi_users.ID IS NOT NULL AND glpi_$table.FK_users ='0' ";
				$result2=$db->query($query2);
				if ($db->numrows($result2)>0){
					while ($data=$db->fetch_assoc($result2)){
						$query3="UPDATE glpi_$table SET FK_users='".$data["USER"]."' WHERE ID='".$data["ID"]."'";
						$db->query($query3);
					}
				}
			}

		}




	//// Group management
	// Manage old plugin table
	if(FieldExists("glpi_groups","extend")){
		$query= "ALTER TABLE `glpi_groups` RENAME `glpi_plugin_droits_groups`;";
		$db->query($query) or die("0.68 rename plugin groups table ".$lang["update"][90].$db->error());
	}

	if(!TableExists("glpi_groups")) {
		$query="CREATE TABLE `glpi_groups` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) default NULL,
			`comments` text,
			`ldap_field` varchar(255) default NULL,
			`ldap_value` varchar(255) default NULL,
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`),
			KEY `ldap_field` (`ldap_field`)
				) TYPE=MyISAM;";
		$db->query($query) or die("0.68 add groups ".$lang["update"][90].$db->error());

		$query="INSERT INTO `glpi_display` (`type`, `num`, `rank`, `FK_users`) VALUES ('".GROUP_TYPE."', '16', '1', '0')";
		$db->query($query) or die("0.68 add groups search config".$lang["update"][90].$db->error());
	}

	if(!TableExists("glpi_users_groups")) {

		$query="CREATE TABLE `glpi_users_groups` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_users` int(11) default '0',
			`FK_groups` int(11) default '0',
			PRIMARY KEY  (`ID`),
			UNIQUE KEY `FK_users` (`FK_users`,`FK_groups`),
			KEY `FK_users_2` (`FK_users`),
			KEY `FK_groups` (`FK_groups`)
				) TYPE=MyISAM;";
		$db->query($query) or die("0.68 add users_groups ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","ldap_field_group")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `ldap_field_group` varchar(255) NULL;";
		$db->query($query) or die("0.68 add ldap_field_group in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_tracking","request_type")) {	
		$query="ALTER TABLE  `glpi_tracking` ADD  `request_type` tinyint(2) DEFAULT '0' AFTER `author`;";
		$db->query($query) or die("0.68 add request_type in tracking ".$lang["update"][90].$db->error());
	}

	// History update for software
	if(FieldExists("glpi_history","device_internal_action")) {	
		$query="ALTER TABLE `glpi_history` CHANGE `device_internal_action` `linked_action` TINYINT( 4 ) NULL DEFAULT '0'";
		$db->query($query) or die("0.68 alater glpi_history ".$lang["update"][90].$db->error());
	}

	if(!TableExists("glpi_alerts")) {

		$query="CREATE TABLE `glpi_alerts` (
			`ID` int(11) NOT NULL auto_increment,
			`device_type` int(11) default '0',
			`FK_device` int(11) default '0',
			`type` int(11) default '0',
			`date` timestamp NULL default CURRENT_TIMESTAMP,
			PRIMARY KEY  (`ID`),
			UNIQUE KEY `alert` (`device_type`,`FK_device`,`type`),
			KEY `item` (`device_type`,`FK_device`),
			KEY `device_type` (`device_type`),
			KEY `FK_device` (`FK_device`),
			KEY `type` (`type`),
			KEY `date` (`date`)
				) TYPE=MyISAM;";
		$db->query($query) or die("0.68 add alerts ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_contracts","alert")) {	
		$query="ALTER TABLE  `glpi_contracts` ADD  `alert` tinyint(2) NOT NULL DEFAULT '0';";
		$db->query($query) or die("0.68 add alert in contracts ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_infocoms","alert")) {	
		$query="ALTER TABLE  `glpi_infocoms` ADD  `alert` tinyint(2) NOT NULL DEFAULT '0';";
		$db->query($query) or die("0.68 add alert in infocoms ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","contract_alerts")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `contract_alerts` tinyint(2) NOT NULL  DEFAULT '0';";
		$db->query($query) or die("0.68 add contract_alerts in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","infocom_alerts")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `infocom_alerts` tinyint(2) NOT NULL DEFAULT '0';";
		$db->query($query) or die("0.68 add infocom_alerts in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_tracking","FK_group")) {	
		$query="ALTER TABLE  `glpi_tracking` ADD  `FK_group` int(11) NOT NULL  DEFAULT '0' AFTER `author`;";
		$db->query($query) or die("0.68 add FK_group in tracking ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","cartridges_alert")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `cartridges_alert` int(11) NOT NULL  DEFAULT '0';";
		$db->query($query) or die("0.68 add cartridges_alert in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","consumables_alert")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `consumables_alert` int(11) NOT NULL  DEFAULT '0';";
		$db->query($query) or die("0.68 add consumables_alert in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_contacts","firstname")) {	
		$query="ALTER TABLE  `glpi_contacts` ADD  `firstname` varchar(255)  DEFAULT '' AFTER `name`;";
		$db->query($query) or die("0.68 add firstname in contacts ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_contacts","mobile")) {	
		$query="ALTER TABLE  `glpi_contacts` ADD  `mobile` varchar(255)  DEFAULT '' AFTER `phone2`;";
		$db->query($query) or die("0.68 add mobile in contacts ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_enterprises","country")) {	
		$query="ALTER TABLE  `glpi_enterprises` ADD  `country` varchar(255)  DEFAULT '' AFTER `address`;";
		$db->query($query) or die("0.68 add country in enterprises ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_enterprises","state")) {	
		$query="ALTER TABLE  `glpi_enterprises` ADD  `state` varchar(255)  DEFAULT '' AFTER `address`;";
		$db->query($query) or die("0.68 add state in enterprises ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_enterprises","town")) {	
		$query="ALTER TABLE  `glpi_enterprises` ADD  `town` varchar(255)  DEFAULT '' AFTER `address`;";
		$db->query($query) or die("0.68 add town in enterprises ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_enterprises","postcode")) {	
		$query="ALTER TABLE  `glpi_enterprises` ADD  `postcode` varchar(255)  DEFAULT '' AFTER `address`;";
		$db->query($query) or die("0.68 add postcode in enterprises ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_contracts","renewal")) {	
		$query="ALTER TABLE  `glpi_contracts` ADD  `renewal` tinyint(2) NOT NULL DEFAULT '0';";
		$db->query($query) or die("0.68 add renewal in contracts ".$lang["update"][90].$db->error());
	}


	// Update contract periodicity and facturation
	$values=array("4"=>"6","5"=>"12","6"=>"24");

	foreach ($values as $key => $val){
		$query="UPDATE glpi_contracts SET periodicity='$val' WHERE periodicity='$key';";
		$db->query($query) or die("0.68 update contract periodicity value ".$lang["update"][90].$db->error());
		$query="UPDATE glpi_contracts SET facturation='$val' WHERE facturation='$key';";
		$db->query($query) or die("0.68 update contract facturation value ".$lang["update"][90].$db->error());
	}

	// Add user fields
	if(!FieldExists("glpi_users","mobile")) {	
		$query="ALTER TABLE  `glpi_users` ADD  `mobile` varchar(255)  DEFAULT '' AFTER `phone`;";
		$db->query($query) or die("0.68 add mobile in users ".$lang["update"][90].$db->error());
	}
	if(!FieldExists("glpi_users","phone2")) {	
		$query="ALTER TABLE  `glpi_users` ADD  `phone2` varchar(255)  DEFAULT '' AFTER `phone`;";
		$db->query($query) or die("0.68 add phone2 in users ".$lang["update"][90].$db->error());
	}
	if(!FieldExists("glpi_users","firstname")) {	
		$query="ALTER TABLE  `glpi_users` ADD  `firstname` varchar(255)  DEFAULT '' AFTER `realname`;";
		$db->query($query) or die("0.68 add firstname in users ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_users","comments")) {	
		$query="ALTER TABLE  `glpi_users` ADD  `comments` TEXT ;";
		$db->query($query) or die("0.68 add comments in users ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","ldap_field_firstname")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `ldap_field_firstname` varchar(200)  DEFAULT 'givenname' AFTER `ldap_field_realname`;";
		$db->query($query) or die("0.68 add ldap_field_firstname in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","ldap_field_mobile")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `ldap_field_mobile` varchar(200)  DEFAULT 'mobile' AFTER `ldap_field_phone`;";
		$db->query($query) or die("0.68 add ldap_mobile in config ".$lang["update"][90].$db->error());
	}

	if(!FieldExists("glpi_config","ldap_field_phone2")) {	
		$query="ALTER TABLE  `glpi_config` ADD  `ldap_field_phone2` varchar(200)  DEFAULT 'homephone' AFTER `ldap_field_phone`;";
		$db->query($query) or die("0.68 add ldap_field_phone2 in config ".$lang["update"][90].$db->error());
	}



} // fin 0.68 #####################################################################################

?>
