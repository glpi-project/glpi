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

/**
* Test if there is an user with superadmin rights
*
*
* @returns boolean true if its ok, elsewhere false.
*/
function superAdminExists() {
        global $DB;
        $query = "select type, password from glpi_users";
        $result = $DB->query($query);
        $var1 = false;
        while($line = $DB->fetch_array($result)) {
                if($line["type"] == "super-admin" && !empty($line["password"])) $var1 = true;
        }
        return $var1;
}


/// Update from 0.31 to 0.4
function update031to04(){
	global $DB,$LANG;

	//0.4 Prefixage des tables : 
	echo "<p class='center'>Version 0.4 </p>";

	if(!TableExists("glpi_computers")) {

		$query = "ALTER TABLE computers RENAME glpi_computers";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE connect_wire RENAME glpi_connect_wire";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_gfxcard RENAME glpi_dropdown_gfxcard";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_hdtype RENAME glpi_dropdown_hdtype";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_iface RENAME glpi_dropdown_iface";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_locations RENAME glpi_dropdown_locations";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_moboard RENAME glpi_dropdown_moboard";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_network RENAME glpi_dropdown_network";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_os RENAME glpi_dropdown_os";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_processor RENAME glpi_dropdown_processor";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_ram RENAME glpi_dropdown_ram";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE dropdown_sndcard RENAME glpi_dropdown_sndcard";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE event_log RENAME glpi_event_log";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE followups RENAME glpi_followups";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE inst_software RENAME glpi_inst_software";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE licenses RENAME glpi_licenses";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE monitors RENAME glpi_monitors";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE networking RENAME glpi_networking";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE networking_ports RENAME glpi_networking_ports";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE networking_wire RENAME glpi_networking_wire";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		if(TableExists("prefs")&&!TableExists("glpi_prefs")) {
			$query = "ALTER TABLE prefs RENAME glpi_prefs";
			$DB->query($query) or die($LANG['update'][90].$DB->error());
		}
		$query = "ALTER TABLE printers RENAME glpi_printers";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE software RENAME glpi_software";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE templates RENAME glpi_templates";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE tracking RENAME glpi_tracking";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE type_computers RENAME glpi_type_computers";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE type_monitors RENAME glpi_type_monitors";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE type_networking RENAME glpi_type_networking";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE type_printers RENAME glpi_type_printers";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE users RENAME glpi_users";
		$DB->query($query) or die($LANG['update'][90].$DB->error()); 

	}	

	//Ajout d'un champs ID dans la table users
	if(!FieldExists("glpi_users", "ID")) {
		$query = "ALTER TABLE `glpi_users` DROP PRIMARY KEY";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_users` ADD UNIQUE (`name`)";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = "ALTER TABLE `glpi_users` ADD INDEX (`name`)";
		$DB->query($query) or die($LANG['update'][90].$DB->error());
		$query = " ALTER TABLE `glpi_users` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
		$DB->query($query) or die($LANG['update'][90].$DB->error());

	}
	//Mise a jour des ID pour les tables dropdown et type. cl� primaires sur les tables dropdown et type, et mise a jour des champs li�
	if(!FieldExists("glpi_dropdown_os", "ID")) {
		changeVarcharToID("glpi_computers", "glpi_dropdown_os", "os");
		changeVarcharToID("glpi_computers", "glpi_dropdown_hdtype", "hdtype");
		changeVarcharToID("glpi_computers", "glpi_dropdown_sndcard", "sndcard");
		changeVarcharToID("glpi_computers", "glpi_dropdown_moboard", "moboard");
		changeVarcharToID("glpi_computers", "glpi_dropdown_gfxcard", "gfxcard");
		changeVarcharToID("glpi_computers", "glpi_dropdown_network", "network");
		changeVarcharToID("glpi_computers", "glpi_dropdown_ram", "ramtype");
		changeVarcharToID("glpi_computers", "glpi_dropdown_locations", "location");
		changeVarcharToID("glpi_computers", "glpi_dropdown_processor", "processor");
		changeVarcharToID("glpi_monitors", "glpi_dropdown_locations", "location");
		changeVarcharToID("glpi_networking", "glpi_dropdown_locations", "location");
		changeVarcharToID("glpi_networking_ports", "glpi_dropdown_iface", "iface");
		changeVarcharToID("glpi_printers", "glpi_dropdown_locations", "location");
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
		changeVarcharToID("glpi_users", "glpi_dropdown_locations", "location");

		changeVarcharToID("glpi_monitors", "glpi_type_monitors", "type");
		changeVarcharToID("glpi_printers", "glpi_type_printers", "type");
		changeVarcharToID("glpi_networking", "glpi_type_networking", "type");
		changeVarcharToID("glpi_computers", "glpi_type_computers", "type");
		changeVarcharToID("glpi_templates", "glpi_type_computers", "type");

	}

	if(!TableExists("glpi_type_peripherals")) {

		$query = "CREATE TABLE `glpi_type_peripherals` (
			`ID` int(11) NOT NULL auto_increment,
			`name` varchar(255),
			PRIMARY KEY  (`ID`)
				) TYPE=MyISAM;";
		$DB->query($query)or die("0A ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_peripherals")) {

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
				) TYPE=MyISAM;";

		$DB->query($query) or die("0 ".$LANG['update'][90].$DB->error());
	}

	if(TableExists("glpi_prefs")&&!FieldExists("glpi_prefs", "ID")) {
		$query = "Alter table glpi_prefs drop primary key";
		$DB->query($query) or die("1 ".$LANG['update'][90].$DB->error());
		$query = "Alter table glpi_prefs add ID INT(11) not null auto_increment primary key";
		$DB->query($query) or die("3 ".$LANG['update'][90].$DB->error());
	}
	if(!FieldExists("glpi_config", "ID")) {

		$query = "ALTER TABLE `glpi_config` CHANGE `config_id` `ID` INT(11) NOT NULL AUTO_INCREMENT ";
		$DB->query($query) or die("4 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "location")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX (`location`) ";
		$DB->query($query) or die("5 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "os")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX (`os`) ";
		$DB->query($query) or die("6 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_computers", "type")) {
		$query = "ALTER TABLE `glpi_computers` ADD INDEX (`type`) ";
		$DB->query($query) or die("7 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_followups", "tracking")) {
		$query = "ALTER TABLE `glpi_followups` ADD INDEX (`tracking`) ";
		$DB->query($query) or die("12 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking", "location")) {
		$query = "ALTER TABLE `glpi_networking` ADD INDEX (`location`) ";
		$DB->query($query) or die("13 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_ports", "on_device")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`on_device` , `device_type`)";
		$DB->query($query) or die("14 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_peripherals", "type")) {
		$query = "ALTER TABLE `glpi_peripherals` ADD INDEX (`type`) ";
		$DB->query($query) or die("14 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_peripherals", "location")) {
		$query = "ALTER TABLE `glpi_peripherals` ADD INDEX (`location`) ";
		$DB->query($query) or die("15 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_printers", "location")) {
		$query = "ALTER TABLE `glpi_printers` ADD INDEX (`location`) ";
		$DB->query($query) or die("16 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "computer")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`computer`) ";
		$DB->query($query) or die("17 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "author")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`author`) ";
		$DB->query($query) or die("18 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "assign")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`assign`) ";
		$DB->query($query) or die("19 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "date")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`date`) ";
		$DB->query($query) or die("20 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "closedate")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`closedate`) ";
		$DB->query($query) or die("21 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_tracking", "status")) {
		$query = "ALTER TABLE `glpi_tracking` ADD INDEX (`status`) ";
		$DB->query($query) or die("22 ".$LANG['update'][90].$DB->error());
	}


	if(!TableExists("glpi_dropdown_firmware")) {
		$query = " CREATE TABLE `glpi_dropdown_firmware` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR(255) NOT NULL ,PRIMARY KEY (`ID`))";
		$DB->query($query) or die("23 ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_networking","firmware")) {
		$query = "ALTER TABLE `glpi_networking` ADD `firmware` INT(11);";
		$DB->query($query) or die("24 ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_tracking","realtime")) {
		$query = "ALTER TABLE `glpi_tracking` ADD `realtime` FLOAT NOT NULL;";
		$DB->query($query) or die("25 ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_printers","flags_usb")) {
		$query = "ALTER TABLE `glpi_printers` ADD `flags_usb` TINYINT DEFAULT '0' NOT NULL AFTER `flags_par`";
		$DB->query($query) or die("26 ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_licenses","expire")) {
		$query = "ALTER TABLE `glpi_licenses` ADD `expire` date default NULL";
		$DB->query($query) or die("27 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_licenses", "sID")) {
		$query = "ALTER TABLE `glpi_licenses` ADD INDEX (`sID`) ";
		$DB->query($query) or die("32 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_followups", "author")) {
		$query = "ALTER TABLE `glpi_followups` ADD INDEX (`author`) ";
		$DB->query($query) or die("33 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_monitors", "type")) {
		$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`type`) ";
		$DB->query($query) or die("34 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_monitors", "location")) {
		$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`location`) ";
		$DB->query($query) or die("35 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_monitors", "type")) {
		$query = "ALTER TABLE `glpi_monitors` ADD INDEX (`type`) ";
		$DB->query($query) or die("37 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking", "type")) {
		$query = "ALTER TABLE `glpi_networking` ADD INDEX (`type`) ";
		$DB->query($query) or die("38 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking", "firmware")) {
		$query = "ALTER TABLE `glpi_networking` ADD INDEX (`firmware`) ";
		$DB->query($query) or die("39 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_printers", "type")) {
		$query = "ALTER TABLE `glpi_printers` ADD INDEX (`type`) ";
		$DB->query($query) or die("42 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_software", "platform")) {
		$query = "ALTER TABLE `glpi_software` ADD INDEX (`platform`) ";
		$DB->query($query) or die("44 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_software", "location")) {
		$query = "ALTER TABLE `glpi_software` ADD INDEX (`location`) ";
		$DB->query($query) or die("45 ".$LANG['update'][90].$DB->error());
	}

	if(!TableExists("glpi_dropdown_netpoint")) {
		$query = " CREATE TABLE `glpi_dropdown_netpoint` (`ID` INT NOT NULL AUTO_INCREMENT ,`location` INT NOT NULL ,`name` VARCHAR(255) NOT NULL ,PRIMARY KEY (`ID`))";
		$DB->query($query) or die("46 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_dropdown_netpoint", "location")) {
		$query = "ALTER TABLE `glpi_dropdown_netpoint` ADD INDEX (`location`) ";
		$DB->query($query) or die("47 ".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_networking_ports","netpoint")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD `netpoint` INT default NULL";
		$DB->query($query) or die("27 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_ports", "netpoint")) {
		$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX (`netpoint`) ";
		$DB->query($query) or die("47 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_wire", "end1")) {
		$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX (`end1`) ";
		$DB->query($query) or die("40 ".$LANG['update'][90].$DB->error());


		// Clean Table
		$query = "SELECT * FROM  `glpi_networking_wire` ORDER BY end1, end2 ";
		$result=$DB->query($query);
		$curend1=-1;
		$curend2=-1;
		while($line = $DB->fetch_array($result)) {
			if ($curend1==$line['end1']&&$curend2==$line['end2']){
				$q2="DELETE FROM `glpi_networking_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
				$DB->query($q2);
			}
			else {$curend1=$line['end1'];$curend2=$line['end2'];}
		}	
		$DB->free_result($result);

		$query = "ALTER TABLE `glpi_networking_wire` ADD UNIQUE end1_1 (`end1`,`end2`) ";
		$DB->query($query) or die("477 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_networking_wire", "end2")) {
		$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX (`end2`) ";
		$DB->query($query) or die("41 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_connect_wire", "end1")) {
		$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`end1`) ";
		$DB->query($query) or die("40 ".$LANG['update'][90].$DB->error());

		// Clean Table
		$query = "SELECT * FROM  `glpi_connect_wire` ORDER BY type, end1, end2 ";
		$result=$DB->query($query);
		$curend1=-1;
		$curend2=-1;
		$curtype=-1;
		while($line = $DB->fetch_array($result)) {
			if ($curend1==$line['end1']&&$curend2==$line['end2']&&$curtype==$line['type']){
				$q2="DELETE FROM `glpi_connect_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
				$DB->query($q2);
			}
			else{ $curend1=$line['end1'];$curend2=$line['end2'];$curtype=$line['type'];}
		}	
		$DB->free_result($result);	
		$query = "ALTER TABLE `glpi_connect_wire` ADD UNIQUE end1_1 (`end1`,`end2`,`type`) ";
		$DB->query($query) or die("478 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_connect_wire", "end2")) {
		$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`end2`) ";
		$DB->query($query) or die("40 ".$LANG['update'][90].$DB->error());
	}

	if(!isIndex("glpi_connect_wire", "type")) {
		$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX (`type`) ";
		$DB->query($query) or die("40 ".$LANG['update'][90].$DB->error());
	}



	if(!FieldExists("glpi_config","ldap_condition")) {
		$query = "ALTER TABLE `glpi_config` ADD `ldap_condition` varchar(255) NOT NULL default ''";
		$DB->query($query) or die("48 ".$LANG['update'][90].$DB->error());
	}

	$query = "ALTER TABLE `glpi_users` CHANGE `type` `type` ENUM('normal', 'admin', 'post-only', 'super-admin') DEFAULT 'normal' NOT NULL";
	$DB->query($query) or die("49 ".$LANG['update'][90].$DB->error());

	$ret["adminchange"] = false;
	//All "admin" users have to be set as "super-admin"
	if(!superAdminExists()) {
		$query = "update glpi_users set type = 'super-admin' where type = 'admin'";
		$DB->query($query) or die("49 ".$LANG['update'][90].$DB->error());
		if($DB->affected_rows() != 0) {
			$ret["adminchange"] = true;
		}
	}

	if(!FieldExists("glpi_users","password_md5")) {
		$query = "ALTER TABLE `glpi_users` ADD `password_md5` VARCHAR(80) NOT NULL AFTER `password` ";
		$DB->query($query) or die("glpi_users.Password_md5".$LANG['update'][90].$DB->error());
	}

	if(!FieldExists("glpi_config","permit_helpdesk")) {
		$query = "ALTER TABLE `glpi_config` ADD `permit_helpdesk` varchar(200) NOT NULL";
		$DB->query($query) or die("glpi_config_permit_helpdesk ".$LANG['update'][90].$DB->error());
	}

}
?>
