<?php
/*
 * @version $Id: update.php 3336 2006-04-24 14:37:40Z moyo $
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Update from 0.65 to 0.65
function update065to068(){
	global $db;

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
  PRIMARY KEY  (`ID`),
  KEY `interface` (`interface`)
) TYPE=MyISAM;";

	$db->query($query) or die("0.68 add profiles ".$lang["update"][90].$db->error());

	$query="INSERT INTO `glpi_profiles` VALUES (1, 'post-only', 'helpdesk', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'r', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, NULL);";
	$db->query($query) or die("0.68 add post-only profile ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_profiles` VALUES (2, 'normal', 'central', '0', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', 'r', '1', 'r', 'r', NULL, NULL, NULL, 'r', 'r', NULL, NULL, 'r', NULL, 'r', NULL, NULL, NULL, '1', '1', '0', '0', '0', '1', '0', '0', '1', '0', '1', '1', '0', '1');";
	$db->query($query) or die("0.68 add normal profile ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_profiles` VALUES (3, 'admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w', NULL, 'w', 'r', 'r', 'w', NULL, NULL, NULL, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1');";
	$db->query($query) or die("0.68 add admin profile ".$lang["update"][90].$db->error());
	$query="INSERT INTO `glpi_profiles` VALUES (4, 'super-admin', 'central', '0', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'w', '1', 'w', 'r', 'w', 'w', 'w', 'w', 'w', 'w', 'w', 'r', 'w', 'w', 'r', 'w', 'w', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1');";
	$db->query($query) or die("0.68 add super-admin profile ".$lang["update"][90].$db->error());

}


	if (FieldExists("glpi_config","`post_only_followup`")){

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
	
	if(!TableExists("glpi_mailing_profiles")) {	
		$query="CREATE TABLE `glpi_mailing_profiles` (
		`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`type`  varchar(255) default NULL,
		`FK_profiles` INT NOT NULL DEFAULT '0',
		KEY `type` (`type`),
		KEY `FK_profiles` (`FK_profiles`),
		UNIQUE `type_profiles` (`type`,`FK_profiles`)
		) TYPE = MYISAM ;";
		$db->query($query) or die("0.68 create mailing_profiles table ".$lang["update"][90].$db->error());

		$query="SELECT * from glpi_config WHERE ID='1'";
		$result=$db->query($query);
		if ($result){
			$data=$db->fetch_assoc($result);
			if ($data["mailing_resa_all_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('resa','".$profiles["admin"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_resa_user"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('resa','-3');";
				$db->query($query2) or die("0.68 populate mailing_profiles resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_resa_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('resa','-1');";
				$db->query($query2) or die("0.68 populate mailing_profiles resa all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_all_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('new','".$profiles["admin"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles new all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_all_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('update','".$profiles["admin"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles update all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_all_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('followup','".$profiles["admin"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles followup all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_all_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('finish','".$profiles["admin"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish all admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_all_normal"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('new','".$profiles["normal"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles new all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_all_normal"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('update','".$profiles["normal"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles update all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_all_normal"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('followup','".$profiles["normal"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles followup all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_all_normal"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('finish','".$profiles["normal"]."');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish all normal ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('new','-1');";
				$db->query($query2) or die("0.68 populate mailing_profiles new admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('update','-1');";
				$db->query($query2) or die("0.68 populate mailing_profiles update admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('followup','-1');";
				$db->query($query2) or die("0.68 populate mailing_profiles followup admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_admin"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('finish','-1');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish admin ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_new_attrib"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('new','-2');";
				$db->query($query2) or die("0.68 populate mailing_profiles new attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_attrib"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('update','-2');";
				$db->query($query2) or die("0.68 populate mailing_profiles update attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_attrib"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('followup','-2');";
				$db->query($query2) or die("0.68 populate mailing_profiles followup attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_attrib"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('finish','-2');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish attrib ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_attrib_attrib"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('attrib','-2');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish attrib ".$lang["update"][90].$db->error());
			}	
			if ($data["mailing_new_user"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('new','-3');";
				$db->query($query2) or die("0.68 populate mailing_profiles new user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_update_user"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('update','-3');";
				$db->query($query2) or die("0.68 populate mailing_profiles update user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_followup_user"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('followup','-3');";
				$db->query($query2) or die("0.68 populate mailing_profiles followup user ".$lang["update"][90].$db->error());
			}
			if ($data["mailing_finish_user"]){
				$query2="INSERT INTO `glpi_mailing_profiles` (type,FK_profiles) VALUES ('finish','-3');";
				$db->query($query2) or die("0.68 populate mailing_profiles finish user ".$lang["update"][90].$db->error());
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
				mysql_free_result($result);
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
		$query="ALTER TABLE `glpi_config` DROP `ldap_field_name` ";
		$db->query($query) or die("0.68 drop ldap_field_name in config ".$lang["update"][90].$db->error());
	}

	// Security user Helpdesk
	$query="UPDATE glpi_users SET password='', active='0' WHERE name='Helpdesk';";
	$db->query($query) or die("0.68 security update for user Helpdesk ".$lang["update"][90].$db->error());
} // fin 0.68 #####################################################################################

?>