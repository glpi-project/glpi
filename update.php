<?php
/*
 
  ----------------------------------------------------------------------
 GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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

 ----------------------------------------------------------------------
 Original Author of file: Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

//Verifie si la table $tablename existe
function TableExists($tablename) {
  
	$db = new DB;
   // Get a list of tables contained within the database.
   $result = $db->list_tables($db);
   $rcount = $db->numrows($result);

   // Check each in list for a match.
   for ($i=0;$i<$rcount;$i++) {
       if (mysql_tablename($result, $i)==$tablename) return true;
   }
   return false;
}

//Verifie que le champs $field existe bien dans la table $table
function FieldExists($table, $field) {
	$db = new DB;
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = mysql_num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = mysql_field_name($result, $i);
		if($name == $field) {
			$var1 = true;
		}
	}
	return $var1;
}
// return true if the field $field of the table $table is a mysql index
// else return false
function isIndex($table, $field) {
	$db = new DB;
	$result = $db->query("select ". $field ." from ". $table ."");
	$flags = mysql_field_flags($result,$field);
	if(eregi("multiple_key",$flags) || eregi("primary_key",$flags)) {
		return true;
	}
	else return false;
}

//test la connection a la base de donnée.
function test_connect() {
$db = new DB;
if($db->error == 0) return true;
else return false;
}

//Change table2 from varchar to ID+varchar and update table1.chps with depends
function changeVarcharToID($table1, $table2, $chps)
{
$db = new DB;

if(!FieldExists($table2, "ID")) {
	$query = " ALTER TABLE `". $table2 ."` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}
$query = "ALTER TABLE $table1 ADD `temp` INT";
$db->query($query) or die("erreur lors de la migration".$db->error());

$query = "select ". $table1 .".ID as row1, ". $table2 .".ID as row2 from ". $table1 .",". $table2 ." where ". $table2 .".name = ". $table1 .".". $chps." ";
$result = $db->query($query) or die("erreur lors de la migration".$db->error());
while($line = $db->fetch_array($result)) {
	$query = "update ". $table1 ." set temp = ". $line["row2"] ." where ID = '". $line["row1"] ."'";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

$query = "Alter table ". $table1 ." drop ". $chps."";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE ". $table1 ." CHANGE `temp` `". $chps ."` INT";
$db->query($query) or die("erreur lors de la migration".$db->error());
}

//update the database to the 0.31 version
function updateDbTo031()
{
$db = new DB;


//amSize ramSize
 $query = "Alter table users drop can_assign_job";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 $query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 $query = "Update users set can_assign_job = 'yes' where type = 'admin'";
 $db->query($query) or die("erreur lors de la migration".$db->error());
 
 echo "<br>Version 0.2 et inferieures Changement du champs can_assign_job <br />";

//Version 0.21 ajout du champ ramSize a la table printers si non existant.


if(!FieldExists("printers", "ramSize")) {
	$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

 echo "Version 0.21 ajout du champ ramSize a la table printers si non existant. <br/>";

//Version 0.3
//Ajout de NOT NULL et des valeurs par defaut.

$query = "ALTER TABLE computers MODIFY name VARCHAR(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY type VARCHAR(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY os VARCHAR(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY osver VARCHAR(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY processor VARCHAR(30) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY processor_speed VARCHAR(30) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY hdspace VARCHAR(6) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY contact VARCHAR(90) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY contact_num VARCHAR(90) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE networking MODIFY ram varchar(10) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY serial varchar(50) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY otherserial varchar(50) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE software MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY platform varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY version varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE software MODIFY comments text NOT NULL";


$query = "ALTER TABLE templates MODIFY templname varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY os varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY osver varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY processor varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY processor_speed varchar(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY serial varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY otherserial varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY ramtype varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY ram varchar(20) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY network varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY hdspace varchar(10) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY contact varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY contact_num varchar(200) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY comments text NOT NULL";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE users MODIFY password varchar(80) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY email varchar(80) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY location varchar(100) NOT NULL default ''";
$db->query($query) or die("erreur lors de la migration".$db->error());
$query = "ALTER TABLE users MODIFY phone varchar(100) NOT NULL default ''";

 echo "Version 0.3 Ajout de NOT NULL et des valeurs par defaut. <br />";

 
}
 

//update database up to 0.31
function updatedbUpTo031()
{

$db = new DB;
if(!TableExists("glpi_config"))
{
$query = "CREATE TABLE `glpi_config` (
  `ID` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_new_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=2 ";
$db->query($query) or die("erreur lors de la migration".$db->error());

$query = "INSERT INTO `glpi_config` VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.3', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1', '1', '1', '1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
$db->query($query) or die("erreur lors de la migration".$db->error());

  echo "Version Superieur à 0.31 ajout de la table glpi_config <br />";
}



//0.4 Prefixage des tables : 

if(!TableExists("glpi_computers")) {

	$query = "ALTER TABLE computers RENAME glpi_computers";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE connect_wire RENAME glpi_connect_wire";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_gfxcard RENAME glpi_dropdown_gfxcard";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_hdtype RENAME glpi_dropdown_hdtype";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_iface RENAME glpi_dropdown_iface";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_locations RENAME glpi_dropdown_locations";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_moboard RENAME glpi_dropdown_moboard";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_network RENAME glpi_dropdown_network";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_os RENAME glpi_dropdown_os";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_processor RENAME glpi_dropdown_processor";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_ram RENAME glpi_dropdown_ram";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE dropdown_sndcard RENAME glpi_dropdown_sndcard";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE event_log RENAME glpi_event_log";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE followups RENAME glpi_followups";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE inst_software RENAME glpi_inst_software";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE licenses RENAME glpi_licenses";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE monitors RENAME glpi_monitors";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE networking RENAME glpi_networking";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE networking_ports RENAME glpi_networking_ports";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE networking_wire RENAME glpi_networking_wire";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE prefs RENAME glpi_prefs";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE printers RENAME glpi_printers";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE software RENAME glpi_software";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE templates RENAME glpi_templates";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE tracking RENAME glpi_tracking";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE type_computers RENAME glpi_type_computers";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE type_monitors RENAME glpi_type_monitors";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE type_networking RENAME glpi_type_networking";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE type_printers RENAME glpi_type_printers";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE users RENAME glpi_users";
	$db->query($query) or die("erreur lors de la migration".$db->error()); 

	echo "Version 0.4 Prefixage des tables  <br/>";
}	

//Ajout d'un champs ID dans la table users
if(!FieldExists("glpi_users", "ID")) {
	$query = "ALTER TABLE `glpi_users` DROP PRIMARY KEY";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE `glpi_users` ADD UNIQUE (`name`)";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE `glpi_users` ADD INDEX (`name`)";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = " ALTER TABLE `glpi_users` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die("erreur lors de la migration".$db->error());

}
//Mise a jour des ID pour les tables dropdown et type.
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
	
echo "Version 0.4 Ajout de clés primaires sur les tables dropdown et type, et mise a jour des champs liés.<br />";
}

if(!TableExists("glpi_type_peripherals")) {

$query = "CREATE TABLE `glpi_type_peripherals` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default '',
	 PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
$db->query($query)or die("erreur lors de la migration".$db->error());
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

$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!FieldExists("glpi_prefs", "ID")) {
	$query = "Alter table glpi_prefs drop primary key";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "ALTER TABLE `glpi_prefs` ADD UNIQUE (`user`)";
	$db->query($query) or die("erreur lors de la migration".$db->error());
	$query = "Alter table glpi_prefs add ID INT(11) not null auto_increment primary key";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}
if(!FieldExists("glpi_config", "ID")) {

	$query = "ALTER TABLE `glpi_config` CHANGE `config_id` `ID` INT( 11 ) NOT NULL AUTO_INCREMENT ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_computers", "location")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `location` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_followups", "tracking")) {
	$query = "ALTER TABLE `glpi_followups` ADD INDEX ( `tracking` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_networking", "location")) {
	$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `location` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_networking_ports", "on_device")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX ( `on_device` , `device_type` )";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_peripherals", "type")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX ( `type` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_peripherals", "location")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX ( `location` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_printers", "location")) {
	$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `location` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_tracking", "computer")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `computer` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_tracking", "author")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `author` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_tracking", "assign")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `assign` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!isIndex("glpi_tracking", "status")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `status` ) ";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!TableExists("glpi_dropdown_firmware")) {
	$query = " CREATE TABLE `glpi_dropdown_firmware` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY ( `ID` ))";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!FieldExists("glpi_networking","firmware")) {
	$query = "ALTER TABLE `glpi_networking` ADD `firmware` INT(11);";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

if(!FieldExists("glpi_tracking","realtime")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `realtime` FLOAT NOT NULL;";
	$db->query($query) or die("erreur lors de la migration".$db->error());
}

}

//Debut du script
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
echo "<head><title>GLPI Update</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" />\n";
echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />\n";
// Include CSS
echo "<style type=\"text/css\">\n";
include ($phproot . "/glpi/config/styles.css");
echo "</style>\n";

echo "</head>";

// Body with configured stuff

echo "<body>";

// step 1    avec bouton de confirmation
if(empty($_POST["continuer"])) {
	echo "Attention ! Vous allez mettre à jour votre base de données GLPI<br />";
	echo "Be carreful ! Your are going to update your GLPI database<br/>";
	echo "<form action=\"update.php\" method=\"post\">";
	echo "<input type=\"submit\" name=\"continuer\" value=\"Mettre à jour / Continue \" />";
	echo "</form>";
}
// Step 2  avec message d'erreur en cas d'echec de connexion
else {
	if(test_connect()) {
		echo "Connexion &agrave; la base de données réussie <br />";
		echo "Connection to the database  sucessful";
		if(!TableExists("glpi_config")) {
			updateDbTo031();
			updateDbUpTo031();
		}
		else 
		{
			updateDbUpTo031();
		}
		echo "<br/>La mise &agrave; jour &agrave; réussie, votre base de données est actualisée \n<br /> vous pouvez supprimer le fichier update.php de votre repertoire";
	        echo "<br/>The update with successful, your data base is update \n<br /> you can remove the file update.php from your directory";
        }
	else {
		echo "<br /> <br />";
		echo "La connexion à la base de données a échouée, verifiez les paramètres de connexion figurant dans le fichier config_db.php <br />";
	
        echo "Connection to the database failed, you should verify the parameters of connection  in the file config_db.php";
        }
}

echo "</body></html>";
?>