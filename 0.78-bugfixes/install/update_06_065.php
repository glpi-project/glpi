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

/// Update from 0.6 to 0.65
function update06to065(){
	global $DB,$LANG;

	echo "<p class='center'>Version 0.65 </p>";

	if(!isIndex("glpi_networking_ports", "on_device_2")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`on_device`) ";
		$DB->query($query) or die("0.65 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_ports", "device_type")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`device_type`) ";
		$DB->query($query) or die("0.65 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computer_device", "FK_device")) {
		$query = "ALTER TABLE `glpi_computer_device` ADD INDEX (`FK_device`) ";
		$DB->query($query) or die("0.65 ".$LANG['update'][90].$DB->error());
	}

	// Field for public FAQ
	if(!FieldExists("glpi_config","public_faq")) {
		$query="ALTER TABLE `glpi_config` ADD `public_faq` ENUM( '0', '1' ) NOT NULL AFTER `auto_assign` ;";
		$DB->query($query) or die("0.65 add public_faq in config".$LANG['update'][90].$DB->error());
	}

	// Optimize amort_type field
	if(FieldExists("glpi_infocoms","amort_type")) {
		$query2="UPDATE `glpi_infocoms` SET `amort_type`='0' WHERE `amort_type` = '';";
		$DB->query($query2) or die("0.65 update amort_type='' in tracking".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_infocoms` CHANGE `amort_type` `amort_type` tinyint(4) NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.65 alter amort_type in infocoms".$LANG['update'][90].$DB->error());
	}
	if(!TableExists("glpi_display")) {
		$query="CREATE TABLE glpi_display (
			ID int(11) NOT NULL auto_increment,
			   type smallint(6) NOT NULL default '0',
			   num smallint(6) NOT NULL default '0',
			   rank smallint(6) NOT NULL default '0',
			   PRIMARY KEY  (ID),
			   UNIQUE KEY `type_2` (`type`,`num`),
			   KEY type (type),
			   KEY rank (rank),
			   KEY num (num)
				   ) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add glpi_display table".$LANG['update'][90].$DB->error());

		// TEMPORARY : ADD ITEMS TO DISPLAY TABLE : TO DEL OR TO 

		$query="INSERT INTO `glpi_display` VALUES (32, 1, 4, 4),
			(34, 1, 6, 6),
			(33, 1, 5, 5),
			(31, 1, 8, 3),
			(30, 1, 23, 2),
			(86, 12, 3, 1),
			(49, 4, 31, 1),
			(50, 4, 23, 2),
			(51, 4, 3, 3),
			(52, 4, 4, 4),
			(44, 3, 31, 1),
			(38, 2, 31, 1),
			(39, 2, 23, 2),
			(45, 3, 23, 2),
			(46, 3, 3, 3),
			(63, 6, 4, 3),
			(62, 6, 5, 2),
			(61, 6, 23, 1),
			(83, 11, 4, 2),
			(82, 11, 3, 1),
			(57, 5, 3, 3),
			(56, 5, 23, 2),
			(55, 5, 31, 1),
			(29, 1, 31, 1),
			(35, 1, 3, 7),
			(36, 1, 19, 8),
			(37, 1, 17, 9),
			(40, 2, 3, 3),
			(41, 2, 4, 4),
			(42, 2, 11, 6),
			(43, 2, 9, 7),
			(47, 3, 4, 4),
			(48, 3, 9, 6),
			(53, 4, 9, 6),
			(54, 4, 7, 7),
			(58, 5, 4, 4),
			(59, 5, 9, 6),
			(60, 5, 7, 7),
			(64, 7, 3, 1),
			(65, 7, 4, 2),
			(66, 7, 5, 3),
			(67, 7, 6, 4),
			(68, 7, 9, 5),
			(69, 8, 9, 1),
			(70, 8, 3, 2),
			(71, 8, 4, 3),
			(72, 8, 5, 4),
			(73, 8, 10, 5),
			(74, 8, 6, 6),
			(75, 10, 4, 1),
			(76, 10, 3, 2),
			(77, 10, 5, 3),
			(78, 10, 6, 4),
			(79, 10, 7, 5),
			(80, 10, 11, 6),
			(84, 11, 5, 3),
			(85, 11, 6, 4),
			(88, 12, 6, 2),
			(89, 12, 4, 3),
			(90, 12, 5, 4),
			(91, 13, 3, 1),
			(92, 13, 4, 2),
			(93, 13, 7, 3),
			(94, 13, 5, 4),
			(95, 13, 6, 5),
			(96, 15, 3, 1),
			(97, 15, 4, 2),
			(98, 15, 5, 3),
			(99, 15, 6, 4),
			(100, 15, 7, 5),
			(101, 17, 3, 1),
			(102, 17, 4, 2),
			(103, 17, 5, 3),
			(104, 17, 6, 4),
			(105, 2, 40, 5),
			(106, 3, 40, 5),
			(107, 4, 40, 5),
			(108, 5, 40, 5),
			(109, 15, 8, 6),
			(110, 23, 31, 1),
			(111, 23, 23, 2),
			(112, 23, 3, 3),
			(113, 23, 4, 4),
			(114, 23, 40, 5),
			(115, 23, 9, 6),
			(116, 23, 7, 7);";

		$DB->query($query);
	}


	if(!FieldExists("glpi_config","ldap_login")) {
		$query="ALTER TABLE `glpi_config` ADD `ldap_login` VARCHAR( 200 ) NOT NULL DEFAULT 'uid' AFTER `ldap_condition`;";
		$DB->query($query) or die("0.65 add url in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","url_base")) {
		$query="ALTER TABLE `glpi_config` ADD `url_base` VARCHAR( 255 ) NOT NULL ;";
		$DB->query($query) or die("0.65 add url in config".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_config` ADD `url_in_mail` ENUM( '0', '1' ) NOT NULL ;";
		$DB->query($query) or die("0.65 add url_in_mail in config".$LANG['update'][90].$DB->error());

		$query="UPDATE glpi_config SET url_base='".str_replace("/install.php","",$_SERVER['HTTP_REFERER'])."' WHERE ID='1'";
		$DB->query($query) or die(" url ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","text_login")) {
		$query="ALTER TABLE `glpi_config` ADD `text_login` TEXT NOT NULL ;";
		$DB->query($query) or die("0.65 add text_login in config".$LANG['update'][90].$DB->error());
	}


	if(!FieldExists("glpi_config","auto_update_check")) {
		$query="ALTER TABLE `glpi_config` ADD `auto_update_check` SMALLINT DEFAULT '0' NOT NULL ,
			ADD `last_update_check` DATE DEFAULT '".date("Y-m-d")."' NOT NULL, ADD `founded_new_version` VARCHAR( 10 ) NOT NULL ;";
		$DB->query($query) or die("0.65 add auto_login_check in config".$LANG['update'][90].$DB->error());
	}

	//// Tracking 
	if(FieldExists("glpi_tracking","status")) {
		$already_done=false;
		if ($result = $DB->query("show fields from glpi_tracking"))
			while ($data=$DB->fetch_array($result)){
				if ($data["Field"]=="status"&&strstr($data["Type"],"done"))
					$already_done=true;
			}

		if (!$already_done)	{
			$query="ALTER TABLE `glpi_tracking` CHANGE `status` `status` ENUM( 'new', 'old', 'old_done', 'assign', 'plan', 'old_notdone', 'waiting' ) DEFAULT 'new' NOT NULL ;";
			$DB->query($query) or die("0.65 alter status in tracking".$LANG['update'][90].$DB->error());

			$query2=" UPDATE `glpi_tracking` SET status='old_done' WHERE status <> 'new';";
			$DB->query($query2) or die("0.65 update status=old in tracking".$LANG['update'][90].$DB->error());	

			$query3=" UPDATE `glpi_tracking` SET status='assign' WHERE status='new' AND assign <> '0';";
			$DB->query($query3) or die("0.65 update status=assign in tracking".$LANG['update'][90].$DB->error());	

			$query4="ALTER TABLE `glpi_tracking` CHANGE `status` `status` ENUM( 'new', 'old_done', 'assign', 'plan', 'old_notdone', 'waiting' ) DEFAULT 'new' NOT NULL ;";
			$DB->query($query4) or die("0.65 alter status in tracking".$LANG['update'][90].$DB->error());
		}
	}

	if(!isIndex("glpi_tracking_planning","id_assign")) {
		$query="ALTER TABLE `glpi_tracking_planning` ADD INDEX ( `id_assign` ) ;";
		$DB->query($query) or die("0.65 add index for id_assign in tracking_planning".$LANG['update'][90].$DB->error());
	}
	if(FieldExists("glpi_tracking","emailupdates")) {
		$query2=" UPDATE `glpi_tracking` SET `emailupdates`='no' WHERE `emailupdates`='';";
		$DB->query($query2) or die("0.65 update emailupdate='' in tracking".$LANG['update'][90].$DB->error());
		$query="ALTER TABLE `glpi_tracking` CHANGE `emailupdates` `emailupdates` ENUM( 'yes', 'no' ) DEFAULT 'no' NOT NULL;";
		$DB->query($query) or die("0.65 alter emailupdates in tracking".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_followups","private")) {
		$query="ALTER TABLE `glpi_followups` ADD `private` INT( 1 ) DEFAULT '0' NOT NULL;";
		$DB->query($query) or die("0.65 add private in followups".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_followups","realtime")) {
		$query="ALTER TABLE `glpi_followups` ADD `realtime` FLOAT DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.65 add realtime in followups".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_attrib_attrib")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_attrib_attrib` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_finish_user` ;";
		$DB->query($query) or die("0.65 add mailing_attrib_attrib in config".$LANG['update'][90].$DB->error());
	}



	if(!FieldExists("glpi_tracking_planning","id_followup")) {
		$query="ALTER TABLE `glpi_tracking_planning` ADD `id_followup` INT DEFAULT '0' NOT NULL AFTER `id_tracking` ;";
		$DB->query($query) or die("0.65 add id_followup in tracking_planning".$LANG['update'][90].$DB->error());
		$query=" ALTER TABLE `glpi_tracking_planning` ADD INDEX ( `id_followup` );";
		$DB->query($query) or die("0.65 add index for id_followup in tracking_planning".$LANG['update'][90].$DB->error());

		//// Move Planned item to followup
		// Get super-admin ID
		$suid=0;
		$query0="SELECT ID from glpi_users WHERE type='super-admin'";
		$result0=$DB->query($query0);
		if ($DB->numrows($result0)>0){
			$suid=$DB->result($result0,0,0);
		}
		$DB->free_result($result0);
		$query="SELECT * FROM glpi_tracking_planning order by id_tracking";
		$result = $DB->query($query);
		$used_followups=array();
		if ($DB->numrows($result)>0)
			while ($data=$DB->fetch_array($result)){
				$found=-1;
				// Is a followup existing ?
				$query2="SELECT * FROM glpi_followups WHERE tracking='".$data["id_tracking"]."'";
				$result2=$DB->query($query2);
				if ($DB->numrows($result2)>0)
					while ($found<0&&$data2=$DB->fetch_array($result2))
						if (!in_array($data2['ID'],$used_followups)){
							$found=$data2['ID'];
						}
				$DB->free_result($result2);
				// Followup not founded
				if ($found<0){
					$query3="INSERT INTO glpi_followups (tracking,date,author,contents) VALUES ('".$data["id_tracking"]."','".date("Y-m-d")."','$suid','Automatic Added followup for compatibility problem in update')";
					$DB->query($query3);
					$found=$DB->insert_id();
				} 
				array_push($used_followups,$found);
				$query4="UPDATE glpi_tracking_planning SET id_followup='$found' WHERE ID ='".$data['ID']."';";
				$DB->query($query4);
			}
		unset($used_followups);
		$DB->free_result($result);
		$query=" ALTER TABLE `glpi_tracking_planning` DROP `id_tracking` ;";
		$DB->query($query) or die("0.65 add index for id_followup in tracking_planning".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","use_ajax")) {
		$query="ALTER TABLE `glpi_config` ADD `dropdown_max` INT DEFAULT '100' NOT NULL ,
			ADD `ajax_wildcard` CHAR( 1 ) DEFAULT '*' NOT NULL ,
			ADD `use_ajax` SMALLINT DEFAULT '0' NOT NULL ,
			ADD `ajax_limit_count` INT DEFAULT '50' NOT NULL ; ";
		$DB->query($query) or die("0.65 add ajax fields in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","ajax_autocompletion")) {
		$query="ALTER TABLE `glpi_config` ADD `ajax_autocompletion` SMALLINT DEFAULT '1' NOT NULL ;";
		$DB->query($query) or die("0.65 add ajax_autocompletion field in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","auto_add_users")) {
		$query="ALTER TABLE `glpi_config` ADD `auto_add_users` SMALLINT DEFAULT '1' NOT NULL ;";
		$DB->query($query) or die("0.65 add auto_add_users field in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","dateformat")) {
		$query="ALTER TABLE `glpi_config` ADD `dateformat` SMALLINT DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.65 add dateformat field in config".$LANG['update'][90].$DB->error());
	}


	if(FieldExists("glpi_software","version")) {
		$query=" ALTER TABLE `glpi_software` CHANGE `version` `version` VARCHAR( 200 ) NOT NULL;";
		$DB->query($query) or die("0.65 alter version field in software".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","nextprev_item")) {
		$query="ALTER TABLE `glpi_config` ADD `nextprev_item` VARCHAR( 200 ) DEFAULT 'name' NOT NULL ;";
		$DB->query($query) or die("0.65 add nextprev_item field in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","view_ID")) {
		$query="ALTER TABLE `glpi_config` ADD `view_ID` SMALLINT DEFAULT '0' NOT NULL ;";
		$DB->query($query) or die("0.65 add nextprev_item field in config".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_infocoms","comments")) {
		$query=" ALTER TABLE `glpi_infocoms` CHANGE `comments` `comments` TEXT";
		$DB->query($query) or die("0.65 alter comments in glpi_infocoms".$LANG['update'][90].$DB->error());
	}

	$new_model=array("monitors","networking","peripherals","printers");

	foreach ($new_model as $model){
		if(!TableExists("glpi_dropdown_model_$model")) {
			// model=type pour faciliter la gestion en post mise �jour : ya plus qu'a deleter les elements non voulu
			// cela conviendra a tout le monde en fonction de l'utilisation du champ type

			$query = "CREATE TABLE `glpi_dropdown_model_$model` (
				`ID` int(11) NOT NULL auto_increment,
				`name` varchar(255) NOT NULL default '',
				PRIMARY KEY  (`ID`)
					) TYPE=MyISAM;";

			$DB->query($query) or die("0.6 add table glpi_dropdown_model_$model ".$LANG['update'][90].$DB->error());

			// copie type dans model
			$query="SELECT * FROM glpi_type_$model";
			$result=$DB->query($query);	
			if ($DB->numrows($result)>0)
				while ($data=$DB->fetch_array($result)){
					$query="INSERT INTO `glpi_dropdown_model_$model` (`ID`,`name`) VALUES ('".$data['ID']."','".addslashes($data['name'])."');";
					$DB->query($query) or die("0.6 insert value in glpi_dropdown_model_$model ".$LANG['update'][90].$DB->error());		
				}
			$DB->free_result($result);
		}

		if (!FieldExists("glpi_$model","model")){
			$query="ALTER TABLE `glpi_$model` ADD `model` INT(11) DEFAULT NULL AFTER `type` ;";
			$DB->query($query) or die("0.6 add model in $model".$LANG['update'][90].$DB->error());

			$query="UPDATE `glpi_$model` SET `model` = `type` ";
			$DB->query($query) or die("0.6 add model in $model".$LANG['update'][90].$DB->error());
		}
	}

	// Update pour les cartouches compatibles : type -> model
	if(FieldExists("glpi_cartridges_assoc","FK_glpi_type_printer")) {
		$query=" ALTER TABLE `glpi_cartridges_assoc` CHANGE `FK_glpi_type_printer` `FK_glpi_dropdown_model_printers` INT( 11 ) DEFAULT '0' NOT NULL ";
		$DB->query($query) or die("0.65 alter FK_glpi_type_printer field in cartridges_assoc ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_links","data")) {
		$query=" ALTER TABLE `glpi_links` ADD `data` TEXT NOT NULL ;";
		$DB->query($query) or die("0.65 create data in links ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_dropdown_auto_update")) {
		$query = "CREATE TABLE `glpi_dropdown_auto_update` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.65 add table glpi_dropdown_auto_update ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_computers","auto_update")) {
		$query="ALTER TABLE `glpi_computers` ADD `auto_update` INT DEFAULT '0' NOT NULL AFTER `os` ;";
		$DB->query($query) or die("0.65 alter computers add auto_update ".$LANG['update'][90].$DB->error());	
	}

	// Update specificity of computer_device
	$query="SELECT glpi_computer_device.ID as ID,glpi_device_processor.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_processor ON (glpi_computer_device.FK_device=glpi_device_processor.ID AND glpi_computer_device.device_type='".PROCESSOR_DEVICE."') WHERE glpi_computer_device.specificity =''";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){
			$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
			$DB->query($query2);
		}

	$query="SELECT glpi_computer_device.ID as ID,glpi_device_processor.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_processor ON (glpi_computer_device.FK_device=glpi_device_processor.ID AND glpi_computer_device.device_type='".PROCESSOR_DEVICE."') WHERE glpi_computer_device.specificity =''";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){
			$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
			$DB->query($query2);
		}

	$query="SELECT glpi_computer_device.ID as ID,glpi_device_ram.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_ram ON (glpi_computer_device.FK_device=glpi_device_ram.ID AND glpi_computer_device.device_type='".RAM_DEVICE."') WHERE glpi_computer_device.specificity =''";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){
			$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
			$DB->query($query2);
		}

	$query="SELECT glpi_computer_device.ID as ID,glpi_device_hdd.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_hdd ON (glpi_computer_device.FK_device=glpi_device_hdd.ID AND glpi_computer_device.device_type='".HDD_DEVICE."') WHERE glpi_computer_device.specificity =''";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){
			$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
			$DB->query($query2);
		}

	$query="SELECT glpi_computer_device.ID as ID,glpi_device_iface.specif_default as SPECIF FROM glpi_computer_device LEFT JOIN glpi_device_iface ON (glpi_computer_device.FK_device=glpi_device_iface.ID AND glpi_computer_device.device_type='".NETWORK_DEVICE."') WHERE glpi_computer_device.specificity =''";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){
			$query2="UPDATE glpi_computer_device SET specificity='".$data["SPECIF"]."' WHERE ID = '".$data["ID"]."'";
			$DB->query($query2);
		}

	// add field notes in tables
	$new_notes=array("computers","software","monitors","networking","peripherals","printers","cartridges_type","consumables_type","contacts","enterprises","contracts","docs");

	foreach ($new_notes as $notes)
		if(!FieldExists("glpi_$notes","notes")) {	
			$query="ALTER TABLE `glpi_$notes` ADD   `notes` LONGTEXT NULL ;";
			$DB->query($query) or die("0.65 add notes field in table".$LANG['update'][90].$DB->error());

		}

	if(!FieldExists("glpi_users","active")) {	
		$query="ALTER TABLE `glpi_users` ADD `active` INT( 2 ) DEFAULT '1' NOT NULL ";
		$DB->query($query) or die("0.65 add active in users ".$LANG['update'][90].$DB->error());
	}


	if(TableExists("glpi_type_docs")){
		$query="SELECT * from glpi_type_docs WHERE ext='odt' OR ext='ods' OR ext='odp' OR ext='otp' OR ext='ott' OR ext='ots' OR ext='odf' OR ext='odg' OR ext='otg' OR ext='odb' OR ext='oth' OR ext='odm' OR ext='odc' OR ext='odi'";
		$result=$DB->query($query);
		if ($DB->numrows($result)==0){
			$query2="INSERT INTO `glpi_type_docs` ( `name` , `ext` , `icon` , `mime` , `upload` , `date_mod` ) VALUES ('Oasis Open Office Writer', 'odt', 'odt-dist.png', '', 'Y', '2006-01-21 17:41:13'),
				( 'Oasis Open Office Calc', 'ods', 'ods-dist.png', '', 'Y', '2006-01-21 17:41:31'),
				('Oasis Open Office Impress', 'odp', 'odp-dist.png', '', 'Y', '2006-01-21 17:42:54'),
				('Oasis Open Office Impress Template', 'otp', 'odp-dist.png', '', 'Y', '2006-01-21 17:43:58'),
				('Oasis Open Office Writer Template', 'ott', 'odt-dist.png', '', 'Y', '2006-01-21 17:44:41'),
				('Oasis Open Office Calc Template', 'ots', 'ods-dist.png', '', 'Y', '2006-01-21 17:45:30'),
				('Oasis Open Office Math', 'odf', 'odf-dist.png', '', 'Y', '2006-01-21 17:48:05'),
				('Oasis Open Office Draw', 'odg', 'odg-dist.png', '', 'Y', '2006-01-21 17:48:31'),
				('Oasis Open Office Draw Template', 'otg', 'odg-dist.png', '', 'Y', '2006-01-21 17:49:46'),
				('Oasis Open Office Base', 'odb', 'odb-dist.png', '', 'Y', '2006-01-21 18:03:34'),
				('Oasis Open Office HTML', 'oth', 'oth-dist.png', '', 'Y', '2006-01-21 18:05:27'),
				('Oasis Open Office Writer Master', 'odm', 'odm-dist.png', '', 'Y', '2006-01-21 18:06:34'),
				('Oasis Open Office Chart', 'odc', NULL, '', 'Y', '2006-01-21 18:07:48'),
				('Oasis Open Office Image', 'odi', NULL, '', 'Y', '2006-01-21 18:08:18');";
			$DB->query($query2) or die("0.65 add new type docs ".$LANG['update'][90].$DB->error());
		}
	}



	///// BEGIN  MySQL Compatibility
	if(FieldExists("glpi_infocoms","warranty_value")) {	
		$query2=" UPDATE `glpi_infocoms` SET `warranty_value`='0' WHERE `warranty_value` IS NULL;";
		$DB->query($query2) or die("0.65 update warranty_value='' in tracking".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_infocoms` CHANGE `warranty_info` `warranty_info` VARCHAR( 255 ) NULL DEFAULT NULL,
			CHANGE `warranty_value` `warranty_value` FLOAT NOT NULL DEFAULT '0',
			CHANGE `num_commande` `num_commande` VARCHAR( 200 ) NULL DEFAULT NULL,
			CHANGE `bon_livraison` `bon_livraison` VARCHAR( 200 ) NULL DEFAULT NULL,
			CHANGE `facture` `facture` VARCHAR( 200 ) NULL DEFAULT NULL,
			CHANGE `num_immo` `num_immo` VARCHAR( 200 ) NULL DEFAULT NULL;";
		$DB->query($query) or die("0.65 alter various fields in infocoms ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_reservation_item","comments")) {	
		$query="ALTER TABLE `glpi_reservation_item` CHANGE `comments` `comments` TEXT NULL ";
		$DB->query($query) or die("0.65 alter comments in glpi_reservation_item ".$LANG['update'][90].$DB->error());
	}


	if(FieldExists("glpi_cartridges_type","comments")) {	
		$query="ALTER TABLE `glpi_cartridges_type` CHANGE `name` `name` VARCHAR( 255 ) NULL DEFAULT NULL,
			CHANGE `ref` `ref` VARCHAR( 255 ) NULL DEFAULT NULL ,
			CHANGE `comments` `comments` TEXT NULL DEFAULT NULL ";
		$DB->query($query) or die("0.65 alter various fields in cartridges_type ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_computer_device","specificity")) {	
		$query="ALTER TABLE `glpi_computer_device` CHANGE `specificity` `specificity` VARCHAR( 250 ) NULL ";
		$DB->query($query) or die("0.65 alter specificity in glpi_computer_device ".$LANG['update'][90].$DB->error());
	}

	$inv_table=array("computers","monitors","networking","peripherals","printers");

	foreach ($inv_table as $table)
		if(FieldExists("glpi_$table","comments")) {	
			$query="UPDATE glpi_$table SET location='0' WHERE location IS NULL;";
			$DB->query($query) or die("0.65 prepare data fro alter various fields in $table ".$LANG['update'][90].$DB->error());
			$query="ALTER TABLE `glpi_$table` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
				CHANGE `serial` `serial` VARCHAR( 200 ) NULL ,
				CHANGE `otherserial` `otherserial` VARCHAR( 200 ) NULL ,
				CHANGE `contact` `contact` VARCHAR( 200 ) NULL ,
				CHANGE `contact_num` `contact_num` VARCHAR( 200 ) NULL ,
				CHANGE `location` `location` INT( 11 ) NOT NULL DEFAULT '0',
				CHANGE `comments` `comments` TEXT NULL ";
			$DB->query($query) or die("0.65 alter various fields in $table ".$LANG['update'][90].$DB->error());
		}

	if(FieldExists("glpi_computers","os")) {	
		$query="UPDATE glpi_computers SET model='0' WHERE model IS NULL;";
		$DB->query($query) or die("0.65 prepare model for alter computers ".$LANG['update'][90].$DB->error());
		$query="UPDATE glpi_computers SET type='0' WHERE type IS NULL;";
		$DB->query($query) or die("0.65 prepare type for alter computers ".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_computers` CHANGE `os` `os` INT( 11 ) NOT NULL DEFAULT '0',
			CHANGE `model` `model` INT( 11 ) NOT NULL DEFAULT '0',
			CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.65 alter various fields in computers ".$LANG['update'][90].$DB->error());

	}
	if(FieldExists("glpi_networking","ram")) {	
		$query="ALTER TABLE `glpi_networking` CHANGE `ram` `ram` VARCHAR( 200 ) NULL ,
			CHANGE `ifmac` `ifmac` VARCHAR( 200 ) NULL ,
			CHANGE `ifaddr` `ifaddr` VARCHAR( 200 ) NULL";
		$DB->query($query) or die("0.65 alter 2 various fields in networking ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_peripherals","brand")) {	
		$query="ALTER TABLE `glpi_peripherals` CHANGE `brand` `brand` VARCHAR( 200 ) NULL ";
		$DB->query($query) or die("0.65 alter 2 various fields in peripherals ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_printers","ramSize")) {	
		$query="ALTER TABLE `glpi_printers` CHANGE `ramSize` `ramSize` VARCHAR( 200 ) NULL ";
		$DB->query($query) or die("0.65 alter 2 various fields in printers ".$LANG['update'][90].$DB->error());
	}
	if(FieldExists("glpi_consumables_type","comments")) {	
		$query="ALTER TABLE `glpi_consumables_type` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `ref` `ref` VARCHAR( 255 ) NULL ,
			CHANGE `comments` `comments` TEXT NULL  ";
		$DB->query($query) or die("0.65 alter various fields in consumables_type ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_contacts","comments")) {	
		$query="ALTER TABLE `glpi_contacts` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `phone` `phone` VARCHAR( 200 ) NULL ,
			CHANGE `phone2` `phone2` VARCHAR( 200 ) NULL ,
			CHANGE `fax` `fax` VARCHAR( 200 ) NULL ,
			CHANGE `email` `email` VARCHAR( 255 ) NULL ,
			CHANGE `comments` `comments` TEXT NULL  ";
		$DB->query($query) or die("0.65 alter various fields in contacts ".$LANG['update'][90].$DB->error());
	}


	if(FieldExists("glpi_contracts","comments")) {	
		$query="ALTER TABLE `glpi_contracts` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `num` `num` VARCHAR( 255 ) NULL ,
			CHANGE `comments` `comments` TEXT NULL ,
			CHANGE `compta_num` `compta_num` VARCHAR( 255 ) NULL ";
		$DB->query($query) or die("0.65 alter various fields in contracts ".$LANG['update'][90].$DB->error());
	}

	$device=array("case","control","drive","gfxcard","hdd","iface","moboard","pci","power","processor","ram","sndcard");

	foreach ($device as $dev){
		if(FieldExists("glpi_device_$dev","comment")) {	
			$query="ALTER TABLE `glpi_device_$dev` CHANGE `designation` `designation` VARCHAR( 255 ) NULL ,
				CHANGE `comment` `comment` TEXT NULL ,
				CHANGE `specif_default` `specif_default` VARCHAR( 250 ) NULL ";
			$DB->query($query) or die("0.65 alter various fields in device_$dev ".$LANG['update'][90].$DB->error());
		}
		if(!isIndex("glpi_device_$dev","designation")) {	
			$query="ALTER TABLE `glpi_device_$dev` ADD INDEX ( `designation` ); ";
			$DB->query($query) or die("0.65 alter various fields in device_$dev ".$LANG['update'][90].$DB->error());
		}
	}

	if(FieldExists("glpi_docs","comment")) {	
		$query="ALTER TABLE `glpi_docs` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `filename` `filename` VARCHAR( 255 ) NULL ,
			CHANGE `mime` `mime` VARCHAR( 30 ) NULL ,
			CHANGE `comment` `comment` TEXT NULL ,
			CHANGE `link` `link` VARCHAR( 255 ) NULL  ";
		$DB->query($query) or die("0.65 alter various fields in docs ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_enterprises","comments")) {	
		$query="ALTER TABLE `glpi_enterprises` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
			CHANGE `address` `address` TEXT NULL ,
			CHANGE `website` `website` VARCHAR( 200 ) NULL ,
			CHANGE `phonenumber` `phonenumber` VARCHAR( 200 ) NULL ,
			CHANGE `comments` `comments` TEXT NULL ,
			CHANGE `fax` `fax` VARCHAR( 255 ) NULL ,
			CHANGE `email` `email` VARCHAR( 255 ) NULL  ";
		$DB->query($query) or die("0.65 alter various fields in enterprises ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_event_log","message")) {	
		$query="ALTER TABLE `glpi_event_log` CHANGE `itemtype` `itemtype` VARCHAR( 200 ) NULL ,
			CHANGE `service` `service` VARCHAR( 200 ) NULL ,
			CHANGE `message` `message` TEXT NULL   ";
		$DB->query($query) or die("0.65 alter various fields in event_log ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_kbitems","question")) {	
		$query="ALTER TABLE `glpi_kbitems` CHANGE `question` `question` TEXT NULL ,
			CHANGE `answer` `answer` TEXT NULL ";
		$DB->query($query) or die("0.65 alter various fields in kbitems ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_licenses","serial")) {	
		$query="ALTER TABLE `glpi_licenses` CHANGE `serial` `serial` VARCHAR( 255 ) NULL";
		$DB->query($query) or die("0.65 alter serial in licenses ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_links","data")) {	
		$query="ALTER TABLE `glpi_links` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `data` `data` TEXT NULL";
		$DB->query($query) or die("0.65 alter various fields in links ".$LANG['update'][90].$DB->error());
	}


	if(FieldExists("glpi_networking_ports","ifmac")) {	
		$query="ALTER TABLE `glpi_networking_ports` CHANGE `name` `name` CHAR( 200 ) NULL ,
			CHANGE `ifaddr` `ifaddr` CHAR( 200 ) NULL ,
			CHANGE `ifmac` `ifmac` CHAR( 200 ) NULL";
		$DB->query($query) or die("0.65 alter various fields in networking_ports ".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_reservation_resa","comment")) {	
		$query="ALTER TABLE `glpi_reservation_resa` CHANGE `comment` `comment` TEXT NULL";
		$DB->query($query) or die("0.65 alter comment in reservation_resa ".$LANG['update'][90].$DB->error());
	} 

	if(FieldExists("glpi_software","version")) {	
		$query="ALTER TABLE `glpi_software` CHANGE `name` `name` VARCHAR( 200 ) NULL ,
			CHANGE `version` `version` VARCHAR( 200 ) NULL ";
		$DB->query($query) or die("0.65 alter various fields in software ".$LANG['update'][90].$DB->error());
	} 

	if(FieldExists("glpi_type_docs","name")) {	
		$query="ALTER TABLE `glpi_type_docs` CHANGE `name` `name` VARCHAR( 255 ) NULL ,
			CHANGE `ext` `ext` VARCHAR( 10 ) NULL ,
			CHANGE `icon` `icon` VARCHAR( 255 ) NULL ,
			CHANGE `mime` `mime` VARCHAR( 100 ) NULL ";
		$DB->query($query) or die("0.65 alter various fields in type_docs ".$LANG['update'][90].$DB->error());
	} 

	if(FieldExists("glpi_users","language")) {	
		$query="ALTER TABLE `glpi_users` CHANGE `name` `name` VARCHAR( 80 ) NULL ,
			CHANGE `password` `password` VARCHAR( 80 ) NULL ,
			CHANGE `password_md5` `password_md5` VARCHAR( 80 ) NULL ,
			CHANGE `email` `email` VARCHAR( 200 ) NULL ,
			CHANGE `realname` `realname` VARCHAR( 255 ) NULL ,
			CHANGE `language` `language` VARCHAR( 255 ) NULL  ";
		$DB->query($query) or die("0.65 alter various fields in users ".$LANG['update'][90].$DB->error());
	} 

	if(FieldExists("glpi_config","cut")) {	
		$query="ALTER TABLE `glpi_config` CHANGE `num_of_events` `num_of_events` VARCHAR( 200 ) NULL ,
			CHANGE `jobs_at_login` `jobs_at_login` VARCHAR( 200 ) NULL ,
			CHANGE `sendexpire` `sendexpire` VARCHAR( 200 ) NULL ,
			CHANGE `cut` `cut` VARCHAR( 200 ) NULL ,
			CHANGE `expire_events` `expire_events` VARCHAR( 200 ) NULL ,
			CHANGE `list_limit` `list_limit` VARCHAR( 200 ) NULL ,
			CHANGE `version` `version` VARCHAR( 200 ) NULL ,
			CHANGE `logotxt` `logotxt` VARCHAR( 200 ) NULL ,
			CHANGE `root_doc` `root_doc` VARCHAR( 200 ) NULL ,
			CHANGE `event_loglevel` `event_loglevel` VARCHAR( 200 ) NULL ,
			CHANGE `mailing` `mailing` VARCHAR( 200 ) NULL ,
			CHANGE `imap_auth_server` `imap_auth_server` VARCHAR( 200 ) NULL ,
			CHANGE `imap_host` `imap_host` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_host` `ldap_host` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_basedn` `ldap_basedn` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_rootdn` `ldap_rootdn` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_pass` `ldap_pass` VARCHAR( 200 ) NULL ,
			CHANGE `admin_email` `admin_email` VARCHAR( 200 ) NULL ,
			CHANGE `mailing_signature` `mailing_signature` VARCHAR( 200 ) NOT NULL DEFAULT '--' ,
			CHANGE `mailing_new_admin` `mailing_new_admin` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_followup_admin` `mailing_followup_admin` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_finish_admin` `mailing_finish_admin` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_new_all_admin` `mailing_new_all_admin` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_followup_all_admin` `mailing_followup_all_admin` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_finish_all_admin` `mailing_finish_all_admin` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_new_all_normal` `mailing_new_all_normal` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_followup_all_normal` `mailing_followup_all_normal` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_finish_all_normal` `mailing_finish_all_normal` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_new_attrib` `mailing_new_attrib` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_followup_attrib` `mailing_followup_attrib` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_finish_attrib` `mailing_finish_attrib` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_new_user` `mailing_new_user` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_followup_user` `mailing_followup_user` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_finish_user` `mailing_finish_user` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_resa_all_admin` `mailing_resa_all_admin` tinyint(4) NOT NULL DEFAULT '0' ,
			CHANGE `mailing_resa_user` `mailing_resa_user` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `mailing_resa_admin` `mailing_resa_admin` tinyint(4) NOT NULL DEFAULT '1' ,
			CHANGE `ldap_field_name` `ldap_field_name` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_field_email` `ldap_field_email` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_field_location` `ldap_field_location` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_field_realname` `ldap_field_realname` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_field_phone` `ldap_field_phone` VARCHAR( 200 ) NULL ,
			CHANGE `ldap_condition` `ldap_condition` VARCHAR( 255 ) NULL ,
			CHANGE `permit_helpdesk` `permit_helpdesk` VARCHAR( 200 ) NULL ,
			CHANGE `cas_host` `cas_host` VARCHAR( 255 ) NULL ,
			CHANGE `cas_port` `cas_port` VARCHAR( 255 ) NULL ,
			CHANGE `cas_uri` `cas_uri` VARCHAR( 255 ) NULL ,
			CHANGE `url_base` `url_base` VARCHAR( 255 ) NULL ,
			CHANGE `text_login` `text_login` TEXT NULL ,
			CHANGE `founded_new_version` `founded_new_version` VARCHAR( 10 ) NULL ";
		$DB->query($query) or die("0.65 alter various fields in config ".$LANG['update'][90].$DB->error());
	} 
	///// END  MySQL Compatibility


	if(!FieldExists("glpi_config","dropdown_limit")) {	
		$query="ALTER TABLE `glpi_config` ADD `dropdown_limit` INT( 11 ) DEFAULT '50' NOT NULL ";
		$DB->query($query) or die("0.65 add dropdown_limit in config ".$LANG['update'][90].$DB->error());
	}


	if(FieldExists("glpi_consumables_type","type")) {	
		$query="ALTER TABLE `glpi_consumables_type` CHANGE `type` `type` INT( 11 ) NOT NULL DEFAULT '0',
			CHANGE `alarm` `alarm` INT( 11 ) NOT NULL DEFAULT '10'";
		$DB->query($query) or die("0.65 alter type and alarm in consumables_type ".$LANG['update'][90].$DB->error());
	}


	if(!FieldExists("glpi_config","post_only_followup")) {	
		$query="ALTER TABLE `glpi_config` ADD `post_only_followup` tinyint( 4 ) DEFAULT '1' NOT NULL ";
		$DB->query($query) or die("0.65 add dropdown_limit in config ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_monitors","flags_dvi")) {	
		$query="ALTER TABLE `glpi_monitors` ADD `flags_dvi` tinyint( 4 ) DEFAULT '0' NOT NULL AFTER `flags_bnc`";
		$DB->query($query) or die("0.65 add dropdown_limit in config ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_history")) {
		$query="CREATE TABLE `glpi_history` (
			`ID` int(11) NOT NULL auto_increment,
			`FK_glpi_device` int(11) NOT NULL default '0',
			`device_type` tinyint(4) NOT NULL default '0',
			`device_internal_type` int(11) default '0',
			`device_internal_action` tinyint(4) default '0',
			`user_name` varchar(200) default NULL,
			`date_mod` datetime default NULL,
			`id_search_option` int(11) NOT NULL default '0',
			`old_value` varchar(255) default NULL,
			`new_value` varchar(255) default NULL,
			PRIMARY KEY  (`ID`),
			KEY `FK_glpi_device` (`FK_glpi_device`)
				) TYPE=MyISAM;";

		$DB->query($query) or die("0.65 add glpi_history table".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_tracking","assign_type")) {	

		$query="ALTER TABLE `glpi_tracking` ADD `assign_ent` INT NOT NULL DEFAULT '0' AFTER `assign` ";
		$DB->query($query) or die("0.65 add assign_ent in tracking ".$LANG['update'][90].$DB->error());

		$query="UPDATE `glpi_tracking` SET assign_ent=assign WHERE assign_type='".ENTERPRISE_TYPE."'";
		$DB->query($query) or die("0.65 update assign_ent in tracking ".$LANG['update'][90].$DB->error());

		$query="UPDATE `glpi_tracking` SET assign=0 WHERE assign_type='".ENTERPRISE_TYPE."'";
		$DB->query($query) or die("0.65 update assign_ent in tracking ".$LANG['update'][90].$DB->error());

		$query="ALTER TABLE `glpi_tracking` DROP `assign_type`";
		$DB->query($query) or die("0.65 drop assign_type in tracking ".$LANG['update'][90].$DB->error());



	}

	if(!FieldExists("glpi_config","mailing_update_admin")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_update_admin` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_new_admin` ;";
		$DB->query($query) or die("0.65 add mailing_update_admin in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_update_all_admin")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_update_all_admin` tinyint(4) NOT NULL DEFAULT '0' AFTER `mailing_new_all_admin` ;";
		$DB->query($query) or die("0.65 add mailing_update_all_admin in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_update_all_normal")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_update_all_normal` tinyint(4) NOT NULL DEFAULT '0' AFTER `mailing_new_all_normal` ;";
		$DB->query($query) or die("0.65 add mailing_update_all_normal in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_update_attrib")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_update_attrib` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_new_attrib` ;";
		$DB->query($query) or die("0.65 add mailing_update_attrib in config".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config","mailing_update_user")) {
		$query="ALTER TABLE `glpi_config` ADD `mailing_update_user` tinyint(4) NOT NULL DEFAULT '1' AFTER `mailing_new_user` ;";
		$DB->query($query) or die("0.65 add mailing_update_user in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","ldap_use_tls")) {
		$query="ALTER TABLE `glpi_config` ADD `ldap_use_tls` VARCHAR( 200 ) NOT NULL DEFAULT '0' AFTER `ldap_login` ";
		$DB->query($query) or die("0.65 add ldap_use_tls in config".$LANG['update'][90].$DB->error());
	}

	if(FieldExists("glpi_config","cut")) { // juste pour affichage identique sur toutes les versions.
		$query="UPDATE `glpi_config` SET `cut` = '255' WHERE `ID` =1";
		$DB->query($query) or die("0.65 update Cut in config".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_licenses","comments")) {
		$query="ALTER TABLE `glpi_licenses` ADD `comments` TEXT NULL ";
		$DB->query($query) or die("0.65 add comments in licenses".$LANG['update'][90].$DB->error());
	}

	///////////// MODE OCS

	// Delete plugin table
	if(TableExists("glpi_ocs_link")&&!FieldExists("glpi_ocs_link","import_device")) {
		$query = "DROP TABLE `glpi_ocs_link`";
		$DB->query($query) or die("0.65 MODE OCS drop plugin ocs_link ".$LANG['update'][90].$DB->error());
	}

	if(TableExists("glpi_ocs_config")&&!FieldExists("glpi_ocs_config","checksum")) {
		$query = "DROP TABLE `glpi_ocs_config`";
		$DB->query($query) or die("0.65 MODE OCS drop plugin ocs_config ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_ocs_link")) {
		$query = "CREATE TABLE `glpi_ocs_link` (
			`ID` int(11) NOT NULL auto_increment,
			`glpi_id` int(11) NOT NULL default '0',
			`ocs_id` varchar(255) NOT NULL default '',
			`auto_update` int(2) NOT NULL default '1',
			`last_update` datetime NOT NULL default '0000-00-00 00:00:00',
			`computer_update` LONGTEXT NULL,
			`import_device` LONGTEXT NULL,
			`import_software` LONGTEXT NULL,
			`import_monitor` LONGTEXT NULL,
			`import_peripheral` LONGTEXT NULL,
			`import_printers` LONGTEXT NULL,
			PRIMARY KEY  (`ID`),
			UNIQUE KEY `ocs_id_2` (`ocs_id`),
			KEY `ocs_id` (`ocs_id`),
			KEY `glpi_id` (`glpi_id`),
			KEY `auto_update` (`auto_update`),
			KEY `last_update` (`last_update`)
				) TYPE=MyISAM";
		$DB->query($query) or die("0.65 MODE OCS creation ocs_link ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_ocs_config")) {
		$query = "CREATE TABLE `glpi_ocs_config` (
			`ID` int(11) NOT NULL auto_increment,
			`ocs_db_user` varchar(255) NOT NULL default '',
			`ocs_db_passwd` varchar(255) NOT NULL default '',
			`ocs_db_host` varchar(255) NOT NULL default '',
			`ocs_db_name` varchar(255) NOT NULL default '',
			`checksum` int(11) NOT NULL default '0',
			`import_periph` int(2) NOT NULL default '0',
			`import_monitor` int(2) NOT NULL default '0',
			`import_software` int(2) NOT NULL default '0',
			`import_printer` int(2) NOT NULL default '0',
			`import_general_os` int(2) NOT NULL default '0',
			`import_general_serial` int(2) NOT NULL default '0',
			`import_general_model` int(2) NOT NULL default '0',
			`import_general_enterprise` int(2) NOT NULL default '0',
			`import_general_type` int(2) NOT NULL default '0',
			`import_general_domain` int(2) NOT NULL default '0',
			`import_general_contact` int(2) NOT NULL default '0',
			`import_general_comments` int(2) NOT NULL default '0',
			`import_device_processor` int(2) NOT NULL default '0',
			`import_device_memory` int(2) NOT NULL default '0',
			`import_device_hdd` int(2) NOT NULL default '0',
			`import_device_iface` int(2) NOT NULL default '0',
			`import_device_gfxcard` int(2) NOT NULL default '0',
			`import_device_sound` int(2) NOT NULL default '0',
			`import_device_drives` int(2) NOT NULL default '0',
			`import_device_ports` int(2) NOT NULL default '0',
			`import_device_modems` int(2) NOT NULL default '0',
			`import_ip` int(2) NOT NULL default '0',
			`default_state` int(11) NOT NULL default '0',
			`tag_limit` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM";

		$DB->query($query) or die("0.65 MODE OCS creation ocs_config ".$LANG['update'][90].$DB->error());
		$query = "INSERT INTO `glpi_ocs_config` VALUES (1, 'ocs', 'ocs', 'localhost', 'ocsweb', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');";
		$DB->query($query) or die("0.65 MODE OCS add default config ".$LANG['update'][90].$DB->error());

	}

	if(!FieldExists("glpi_computers","ocs_import")) {
		$query = "ALTER TABLE `glpi_computers` ADD `ocs_import` TINYINT NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.65 MODE OCS add default config ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","ocs_mode")) {
		$query = "ALTER TABLE `glpi_config` ADD `ocs_mode` TINYINT NOT NULL DEFAULT '0' ";
		$DB->query($query) or die("0.65 MODE OCS add ocs_mode in config ".$LANG['update'][90].$DB->error());
	}
	///////////// FIN MODE OCS


	if(!TableExists("glpi_dropdown_budget")) {
		$query = "CREATE TABLE `glpi_dropdown_budget` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add dropdown_budget ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_infocoms","budget")) {
		$query = "ALTER TABLE `glpi_infocoms` ADD `budget` INT NULL DEFAULT '0';";
		$DB->query($query) or die("0.65 add budget in infocoms ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_tracking","cost_time")) {
		$query = "ALTER TABLE `glpi_tracking` ADD `cost_time` FLOAT NOT NULL DEFAULT '0',
			ADD `cost_fixed` FLOAT NOT NULL DEFAULT '0',
			ADD `cost_material` FLOAT NOT NULL DEFAULT '0'";
		$DB->query($query) or die("0.65 add cost fields in tracking ".$LANG['update'][90].$DB->error());
	}

	// Global Printers
	if(!FieldExists("glpi_printers","is_global")) {
		$query="ALTER TABLE `glpi_printers` ADD `is_global` ENUM('0', '1') DEFAULT '0' NOT NULL AFTER `FK_glpi_enterprise` ;";
		$DB->query($query) or die("0.6 add is_global in printers ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","debug")) {
		$query="ALTER TABLE `glpi_config` ADD `debug` int(2) NOT NULL default '0' ";
		$DB->query($query) or die("0.65 add debug in config ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_dropdown_os_version")) {
		$query = "CREATE TABLE `glpi_dropdown_os_version` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add dropdown_os_version ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_dropdown_os_sp")) {
		$query = "CREATE TABLE `glpi_dropdown_os_sp` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add dropdown_os_sp ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_computers","os_version")) {
		$query="ALTER TABLE `glpi_computers` ADD `os_version` INT NOT NULL DEFAULT '0' AFTER `os` ,
			ADD `os_sp` INT NOT NULL DEFAULT '0' AFTER `os_version` ";
		$DB->query($query) or die("0.65 add os_version os_sp in computers ".$LANG['update'][90].$DB->error());
	}

	// ADD INDEX
	$tbl=array("cartridges_type","computers","consumables_type","contacts","contracts","docs","enterprises","monitors","networking","peripherals","printers","software","users");

	foreach ($tbl as $t)
		if(!isIndex("glpi_$t","name")) {	
			$query="ALTER TABLE `glpi_$t` ADD INDEX ( `name` ) ";
			$DB->query($query) or die("0.65 add index in name field $t ".$LANG['update'][90].$DB->error());
		}

	$result=$DB->list_tables();
	while ($line = $DB->fetch_array($result))
		if (strstr($line[0],"glpi_dropdown")||strstr($line[0],"glpi_type")){
			if(!isIndex($line[0],"name")) {	
				$query="ALTER TABLE `".$line[0]."` ADD INDEX ( `name` ) ";
				$DB->query($query) or die("0.65 add index in name field ".$line[0]." ".$LANG['update'][90].$DB->error());
			}
		}

	if(!isIndex("glpi_reservation_item","device_type_2")) {	
		$query="ALTER TABLE `glpi_reservation_item` ADD INDEX  `device_type_2` ( `device_type`,`id_device` ) ";
		$DB->query($query) or die("0.65 add index in reservation_item ".$line[0]." ".$LANG['update'][90].$DB->error());
	}


	if(!TableExists("glpi_dropdown_model_phones")) {

		$query = "CREATE TABLE `glpi_dropdown_model_phones` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add dropdown_model_phones ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_type_phones")) {

		$query = "CREATE TABLE `glpi_type_phones` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add type_phones ".$LANG['update'][90].$DB->error());
	}


	if(!TableExists("glpi_dropdown_phone_power")) {

		$query = "CREATE TABLE `glpi_dropdown_phone_power` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`ID`),
			KEY `name` (`name`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add dropdown_phone_power ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_phones")) {

		$query = "CREATE TABLE `glpi_phones` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255) default NULL,
			`date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
			`contact` varchar(255) default NULL,
			`contact_num` varchar(255) default NULL,
			`tech_num` int(11) NOT NULL default '0',
			`comments` text,
			`serial` varchar(255) default NULL,
			`otherserial` varchar(255) default NULL,
			`firmware` varchar(255) default NULL,
			`location` int(11) NOT NULL default '0',
			`type` int(11) NOT NULL default '0',
			`model` int(11) default NULL,
			`brand` varchar(255) default NULL,
			`power` tinyint(4) NOT NULL default '0',
			`number_line` varchar(255) NOT NULL default '',
			`flags_casque` tinyint(4) NOT NULL default '0',
			`flags_hp` tinyint(4) NOT NULL default '0',
			`FK_glpi_enterprise` int(11) NOT NULL default '0',
			`is_global` enum('0','1') NOT NULL default '0',
			`deleted` enum('Y','N') NOT NULL default 'N',
			`is_template` enum('0','1') NOT NULL default '0',
			`tplname` varchar(255) default NULL,
			`notes` longtext,
			PRIMARY KEY  (`ID`),
			KEY `type` (`type`),
			KEY `name` (`name`),
			KEY `location` (`location`),
			KEY `FK_glpi_enterprise` (`FK_glpi_enterprise`),
			KEY `deleted` (`deleted`),
			KEY `is_template` (`is_template`),
			KEY `tech_num` (`tech_num`)
				) TYPE=MyISAM;";
		$DB->query($query) or die("0.65 add phones ".$LANG['update'][90].$DB->error());

		$query="INSERT INTO `glpi_phones` VALUES (1, NULL, '0000-00-00 00:00:00', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, 0, '', 0, 0, 0, '0', 'N', '1', 'Blank Template', NULL);";
		$DB->query($query) or die("0.65 blank template in phones ".$LANG['update'][90].$DB->error());
	}



	if(!TableExists("glpi_reminder")) {
		$query="CREATE TABLE `glpi_reminder` (
			`ID` int(11) NOT NULL auto_increment,
			`date` datetime default NULL,
			`author` int(11) NOT NULL default '0',
			`title` text,
			`text` text,
			`type` varchar(50) NOT NULL default 'private',
			`begin` datetime default NULL,
			`end` datetime default NULL,
			`rv` enum('0','1') NOT NULL default '0',
			`date_mod` datetime default NULL,
			PRIMARY KEY  (`ID`),
			KEY `date` (`date`),
			KEY `author` (`author`),
			KEY `rv` (`rv`),
			KEY `type` (`type`)
				) TYPE=MyISAM ;";

		$DB->query($query) or die("0.65 add reminder ".$LANG['update'][90].$DB->error());
	}

	$result=$DB->list_tables();
	while ($line = $DB->fetch_array($result))
		if (strstr($line[0],"glpi_dropdown")||strstr($line[0],"glpi_type")){
			if ($line[0]!="glpi_type_docs"){
				if(!FieldExists($line[0],"comments")) {
					$query="ALTER TABLE `".$line[0]."` ADD `comments` TEXT NULL ";
					$DB->query($query) or die("0.65 add comments field in ".$line[0]." ".$LANG['update'][90].$DB->error());
				}
			}
		}
	if(!FieldExists("glpi_consumables","id_user")) {
		$query="ALTER TABLE `glpi_consumables` ADD `id_user` INT NOT NULL DEFAULT '0';";
		$DB->query($query) or die("0.65 add id_user field in consumables ".$LANG['update'][90].$DB->error());
	}



} // fin 0.65

?>
