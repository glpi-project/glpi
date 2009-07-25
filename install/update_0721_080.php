<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

// Update from 0.721 to 0.80

function update0721to080() {
	global $DB, $LANG;

	echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   displayMigrationMessage("080"); // Start

//	displayMigrationMessage("080", $LANG['update'][140]); // Index creation
	

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_config'); // Updating schema
	if (FieldExists('glpi_config', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_config` DROP `license_deglobalisation`;";
      $DB->query($query) or die("0.80 alter clean glpi_config table " . $LANG['update'][90] . $DB->error());
	}	

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_mailgate'); // Updating schema

	if (!FieldExists("glpi_mailgate", "active")) {
		$query = "ALTER TABLE `glpi_mailgate` ADD `active` INT( 1 ) NOT NULL DEFAULT '1' ;";
      $DB->query($query) or die("0.80 add active in glpi_mailgate " . $LANG['update'][90] . $DB->error());
	}

   // Change mailgate search pref : add ative
	$query="SELECT DISTINCT FK_users FROM glpi_display WHERE type=".MAILGATE_TYPE.";";
	if ($result = $DB->query($query)){
		if ($DB->numrows($result)>0){
			while ($data = $DB->fetch_assoc($result)){
				$query="SELECT max(rank) FROM glpi_display WHERE FK_users='".$data['FK_users']."' AND type=".MAILGATE_TYPE.";";
				$result=$DB->query($query);
				$rank=$DB->result($result,0,0);
				$rank++;
				$query="SELECT * FROM glpi_display WHERE FK_users='".$data['FK_users']."' AND num=2 AND type=".MAILGATE_TYPE.";";
				if ($result2=$DB->query($query)){
					if ($DB->numrows($result2)==0){
						$query="INSERT INTO glpi_display (`type` ,`num` ,`rank` ,`FK_users`) VALUES ('".MAILGATE_TYPE."', '2', '".$rank++."', '".$data['FK_users']."');";
						$DB->query($query);
					}
				}
			}
		}
	}
   
   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_device_xxxx'); // Updating schema
         

	if (FieldExists("glpi_device_control", "interface")) {
		$query="ALTER TABLE `glpi_device_control` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_device_control " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_device_control", "interface")) {
			$query="ALTER TABLE `glpi_device_control` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
         $DB->query($query) or die("0.80 alter interface index in glpi_device_control " . $LANG['update'][90] . $DB->error());
		}
	}

	if (FieldExists("glpi_device_hdd", "interface")) {
		$query="ALTER TABLE `glpi_device_hdd` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_device_hdd " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_device_hdd", "interface")) {
			$query="ALTER TABLE `glpi_device_hdd` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
			$DB->query($query) or die("0.v alter interface index in glpi_device_control " . $LANG['update'][90] . $DB->error());
		}
	}

	if (FieldExists("glpi_device_drive", "interface")) {
		$query="ALTER TABLE `glpi_device_drive` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_device_drive " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_device_drive", "interface")) {
			$query="ALTER TABLE `glpi_device_drive` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
			$DB->query($query) or die("0.v alter interface index in glpi_device_drive " . $LANG['update'][90] . $DB->error());
		}
	}

	if (!isIndex("glpi_device_gfxcard", "FK_interface")) {
		$query="ALTER TABLE `glpi_device_gfxcard` ADD INDEX `FK_interface` ( `FK_interface` ) ";
      $DB->query($query) or die("0.80 add interface index in glpi_device_gfxcard " . $LANG['update'][90] . $DB->error());
	}
	
   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_rule_cache_software'); // Updating schema
	
	if (FieldExists("glpi_rule_cache_software","ignore_ocs_import")){
		$query = "ALTER TABLE `glpi_rule_cache_software` CHANGE `ignore_ocs_import` `ignore_ocs_import` CHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ";
      $DB->query($query) or die("0.80 alter table glpi_rule_cache_software " . $LANG['update'][90] . $DB->error());
	}
	if (!FieldExists("glpi_rule_cache_software","helpdesk_visible")){
		$query = "ALTER TABLE `glpi_rule_cache_software` ADD `helpdesk_visible` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.80 add helpdesk_visible index in glpi_rule_cache_software " . $LANG['update'][90] . $DB->error());
	}

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_entities'); // Updating schema
   
   if (!FieldExists("glpi_entities","cache_sons")){
      $query = "ALTER TABLE `glpi_entities` ADD `cache_sons` LONGTEXT NOT NULL ; ";
      $DB->query($query) or die("0.80 add cache_sons field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }
   
   if (!FieldExists("glpi_entities","cache_ancestors")){
      $query = "ALTER TABLE `glpi_entities` ADD `cache_ancestors` LONGTEXT NOT NULL ; ";
      $DB->query($query) or die("0.80 add cache_ancestors field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_config'); // Updating schema

   if (FieldExists("glpi_config","use_cache")){
      $query = "ALTER TABLE `glpi_config`  DROP `use_cache`;";
      $DB->query($query) or die("0.80 drop use_cache in glpi_config " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_config","cache_max_size")){
      $query = "ALTER TABLE `glpi_config`  DROP `cache_max_size`;";
      $DB->query($query) or die("0.80 drop cache_max_size in glpi_config " . $LANG['update'][90] . $DB->error());
   }

	if (!FieldExists("glpi_config","request_type")){
		$query = "ALTER TABLE `glpi_config` ADD `request_type` INT( 1 ) NOT NULL DEFAULT 1";
      $DB->query($query) or die("0.80 add request_type index in glpi_config " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_users","request_type")){
		$query = "ALTER TABLE `glpi_users` ADD `request_type` INT( 1 ) NULL";
      $DB->query($query) or die("0.80 add request_type index in glpi_config " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_config","add_norights_users")){
		$query = "ALTER TABLE `glpi_config` ADD `add_norights_users` INT( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.80 add add_norights_users index in glpi_config " . $LANG['update'][90] . $DB->error());
	}
	
	// Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End
}
