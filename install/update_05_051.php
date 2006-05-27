<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
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

// Update from 0.5 to 0.51
function update05to051(){
global $db,$lang;

	 echo "<p class='center'>Version 0.51 </p>";

/*******************************GLPI 0.51***********************************************/

if(!FieldExists("glpi_infocoms","facture")) {
	$query = "ALTER TABLE `glpi_infocoms` ADD `facture` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field facture ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_enterprises","fax")) {
	$query = "ALTER TABLE `glpi_enterprises` ADD `fax` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field fax ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_docs","link")) {
	$query = "ALTER TABLE `glpi_docs` ADD `link` char(255) NOT NULL default ''";
	$db->query($query) or die("0.51 add field fax ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_contact_type")) {

$query = "CREATE TABLE glpi_dropdown_contact_type (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";

$db->query($query) or die("0.51 add table dropdown_contact_type ".$lang["update"][90].$db->error());

$query="INSERT INTO glpi_dropdown_contact_type (name) VALUES ('".$lang["financial"][43]."');";
$db->query($query) or die("0.51 add entries to dropdown_contact_type ".$lang["update"][90].$db->error());
$query="INSERT INTO glpi_dropdown_contact_type (name) VALUES ('".$lang["financial"][42]."');";
$db->query($query) or die("0.51 add entries to dropdown_contact_type ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","cartridges_alarm")) {
	$query = "ALTER TABLE `glpi_config` ADD `cartridges_alarm` int(11) NOT NULL default '10'";
	$db->query($query) or die("0.51 add field cartridges_alarm ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_state_item")) {

	$query = "ALTER TABLE `glpi_repair_item` RENAME `glpi_state_item`;";
	$db->query($query) or die("0.51 alter glpi_state_item table name ".$lang["update"][90].$db->error());

	$query = "ALTER TABLE `glpi_state_item` ADD `state` INT DEFAULT '1';";
	$db->query($query) or die("0.51 add state field ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_state")) {
	$query = "CREATE TABLE glpi_dropdown_state (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";
	$db->query($query) or die("0.51 add state field ".$lang["update"][90].$db->error());

}

}

?>