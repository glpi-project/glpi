<?php
/*
 * @version $Id$
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

// Update from 0.68.1 to 0.7
function update0681to07(){
	global $DB,$LANG,$CFG_GLPI;

	if(!FieldExists("glpi_config", "cas_logout")) {
		$query = "ALTER TABLE `glpi_config` ADD `cas_logout` VARCHAR( 255 ) NULL AFTER `cas_uri`;";
		$DB->query($query) or die("0.7 add cas_logout in glpi_config ".$LANG["update"][90].$DB->error());
	}


	if(!TableExists("glpi_entities")) {
		$query = "CREATE TABLE `glpi_entities` (
		`ID` int(11) NOT NULL auto_increment,
		`name` varchar(255) NOT NULL,
		`parentID` int(11) NOT NULL default '0',
		`completename` text NOT NULL,
		`comments` text,
		`level` int(11) default NULL,
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `name` (`name`,`parentID`),
		KEY `parentID` (`parentID`)
		) ENGINE=MyISAM;";
		$DB->query($query) or die("0.7 create glpi_entities ".$LANG["update"][90].$DB->error());
		// TODO : ADD other fields
	}

	if(!FieldExists("glpi_users_profiles", "FK_entities")) {
		$query = " ALTER TABLE `glpi_users_profiles` ADD `FK_entities` INT NOT NULL DEFAULT '0',
					ADD `recursive` TINYINT NOT NULL DEFAULT '1',
					ADD `active` TINYINT NOT NULL DEFAULT '1' ";
		$DB->query($query) or die("0.7 alter glpi_users_profiles ".$LANG["update"][90].$DB->error());

		// Manage inactive users
		$query="SELECT ID FROM glpi_users WHERE active='0'";
		$result=$DB->query($query);
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_array($result)){
				$query2="UPDATE glpi_users_profiles SET active = '0' WHERE FK_users='".$data['ID']."'";
				$DB->query($query2);
			}
		}

		$query="ALTER TABLE `glpi_users` DROP `active` ";
		$DB->query($query) or die("0.7 drop active from glpi_users ".$LANG["update"][90].$DB->error());

		$query="DELETE FROM glpi_display WHERE type='".USER_TYPE."' AND num='8';";
		$DB->query($query) or die("0.7 delete active field items for user search ".$LANG["update"][90].$DB->error());
	}

	// Add entity tags to tables
	$tables=array("glpi_cartridges_type","glpi_computers","glpi_consumables_type","glpi_contacts","glpi_contracts","glpi_docs",
			"glpi_dropdown_locations","glpi_dropdown_kbcategories","glpi_enterprises","glpi_groups",
			"glpi_kbitems","glpi_monitors","glpi_networking","glpi_peripherals","glpi_phones","glpi_printers","glpi_software",
			"glpi_tracking");
	// ,"glpi_followups","glpi_licenses","glpi_infocoms", "glpi_links","glpi_reminder","glpi_reservation_item", "glpi_state_item" ?
	foreach ($tables as $tbl){
		if(!FieldExists($tbl, "FK_entities")) {
			$query = "ALTER TABLE `".$tbl."` ADD `FK_entities` INT NOT NULL DEFAULT '0' AFTER `ID`";
			$DB->query($query) or die("0.7 add FK_entities in $tbl ".$LANG["update"][90].$DB->error());
		}

		if (!isIndex($tbl,"FK_entities")){			
			$query = "ALTER TABLE `".$tbl."` ADD INDEX (`FK_entities`)";
			$DB->query($query) or die("0.7 add index FK_entities in $tbl ".$LANG["update"][90].$DB->error());
		}
	}
	
	// Regenerate Indexes :
	$tables=array("glpi_dropdown_locations","glpi_dropdown_kbcategories");
	foreach ($tables as $tbl){
		if (isIndex($tbl,"name")){
			$query = "ALTER TABLE `$tbl` DROP INDEX `name`;";
			$DB->query($query) or die("0.7 drop index name in $tbl ".$LANG["update"][90].$DB->error());
		}
		if (isIndex($tbl,"parentID_2")){
			$query = "ALTER TABLE `$tbl` DROP INDEX `parentID_2`;";
			$DB->query($query) or die("0.7 drop index name in $tbl ".$LANG["update"][90].$DB->error());
		}
		$query = "ALTER TABLE `$tbl` ADD UNIQUE(`name`,`parentID`,`FK_entities`);";
		$DB->query($query) or die("0.7 add index name in $tbl ".$LANG["update"][90].$DB->error());

	}

	if (isIndex("glpi_users_profiles","FK_users_profiles")){
		$query = "ALTER TABLE `glpi_users_profiles` DROP INDEX `FK_users_profiles`;";
		$DB->query($query) or die("0.7 drop index FK_users_profiles in glpi_users_profiles ".$LANG["update"][90].$DB->error());
	}

	if (!isIndex("glpi_users_profiles","active")){
		$query = "ALTER TABLE `glpi_users_profiles` ADD INDEX (`active`);";
		$DB->query($query) or die("0.7 add index active in glpi_users_profiles ".$LANG["update"][90].$DB->error());
	}
	if (!isIndex("glpi_users_profiles","FK_entities")){
		$query = "ALTER TABLE `glpi_users_profiles` ADD INDEX (`FK_entities`);";
		$DB->query($query) or die("0.7 add index FK_entities in glpi_users_profiles ".$LANG["update"][90].$DB->error());
	}

	// TODO Enterprises -> dropdown manufacturer + update import OCS
	// TODO Split Config -> config general + config entity
	// TODO AUto assignment profile based on rules
	// TODO Add default profile to user + update data from preference

} // fin 0.7 #####################################################################################

?>
