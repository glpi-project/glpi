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

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : rename tables'); // Updating schema

   $glpi_tables=array(
      'glpi_alerts' => 'glpi_alerts',
      'glpi_auth_ldap' => 'glpi_authldaps',
      'glpi_auth_ldap_replicate' => 'glpi_authldapsreplicates',
      'glpi_auth_mail' => 'glpi_authmails',
      'glpi_bookmark' => 'glpi_bookmarks',
      'glpi_cartridges' => 'glpi_cartridges',
      'glpi_cartridges_type' => 'glpi_cartridgesitems',
      'glpi_cartridges_assoc' => 'glpi_cartridges_printersmodels',
      'glpi_dropdown_cartridge_type' => 'glpi_cartridgesitemstypes',

      'glpi_computer_device' => 'glpi_computers_devices',
      'glpi_computerdisks' => 'glpi_computersdisks',
      'glpi_computers' => 'glpi_computers',
      'glpi_config' => 'glpi_configs',
      'glpi_connect_wire' => 'glpi_computers_items',
   );
   $backup_tables=false;
	foreach ($glpi_tables as $original_table => $new_table) {
      if (strcmp($original_table,$new_table)!=0) {
         // Original table exists ?
            if (TableExists($original_table)) {
               // rename new tables if exists ?
               if (TableExists($new_table)) {
                  if (TableExists("backup_$new_table")) {
                     $query="DROP TABLE `backup_".$new_table."`";
                     $DB->query($query) or die("0.80 drop backup table backup_$new_table ". $LANG['update'][90] . $DB->error());
                  }
                  echo "<p><b>$new_table table already exists. ";
                  echo "A backup have been done to backup_NAME.</b></p>";
                  $backup_tables=true;
                  $query="RENAME TABLE `$new_table` TO `backup_$new_table`";
                  $DB->query($query) or die("0.80 backup table $new_table " . $LANG['update'][90] . $DB->error());

               }
               // rename original table
               $query="RENAME TABLE `$original_table` TO `$new_table`";
               $DB->query($query) or die("0.80 rename $original_table to $new_table " . $LANG['update'][90] . $DB->error());
            }
      }
   }
   if ($backup_tables){
      echo "<div class='red'><p>You can delete backup tables if you have no need of them.</p></div>";
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema
	if (FieldExists('glpi_configs', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_configs` DROP `license_deglobalisation`;";
      $DB->query($query) or die("0.80 alter clean glpi_configs table " . $LANG['update'][90] . $DB->error());
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


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema

   if (FieldExists("glpi_configs","use_cache")){
      $query = "ALTER TABLE `glpi_configs`  DROP `use_cache`;";
      $DB->query($query) or die("0.80 drop use_cache in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_configs","cache_max_size")){
      $query = "ALTER TABLE `glpi_configs`  DROP `cache_max_size`;";
      $DB->query($query) or die("0.80 drop cache_max_size in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

	if (!FieldExists("glpi_configs","request_type")){
		$query = "ALTER TABLE `glpi_configs` ADD `request_type` INT( 1 ) NOT NULL DEFAULT 1";
      $DB->query($query) or die("0.80 add request_type index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_users","request_type")){
		$query = "ALTER TABLE `glpi_users` ADD `request_type` INT( 1 ) NULL";
      $DB->query($query) or die("0.80 add request_type index in glpi_users " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_configs","add_norights_users")){
		$query = "ALTER TABLE `glpi_configs` ADD `add_norights_users` INT( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.80 add add_norights_users index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_budgets'); // Updating schema

	if (!FieldExists("glpi_profiles","budget")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `budget` VARCHAR( 1 ) NULL ";
		$DB->query($query) or die("0.80 add budget index in glpi_profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='w' WHERE `name` IN ('super-admin','admin')";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='r' WHERE `name`='normal'";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

	}


	if (TableExists("glpi_dropdown_budget")) {
      if (!FieldExists("glpi_dropdown_budget","FK_entities")) {
            $query = "ALTER TABLE `glpi_dropdown_budget` ADD `FK_entities` int(11) NOT NULL default '0'";
            $DB->query($query) or die("0.80 add FK_entities field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }

      if (!FieldExists("glpi_dropdown_budget","recursive")) {
         $query = "ALTER TABLE `glpi_dropdown_budget` ADD `recursive` tinyint(1) NOT NULL DEFAULT '0'";
			$DB->query($query) or die("0.80 add recursive field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!FieldExists("glpi_dropdown_budget","deleted")) {
         $query = "ALTER TABLE `glpi_dropdown_budget` ADD `deleted` tinyint(1) NOT NULL DEFAULT '0'";
			$DB->query($query) or die("0.80 add deleted field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!FieldExists("glpi_dropdown_budget","begin_date")) {
         $query = "ALTER TABLE `glpi_dropdown_budget` ADD `begin_date` DATE NULL";
			$DB->query($query) or die("0.80 add begin_date field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!FieldExists("glpi_dropdown_budget","end_date")) {   
			$query = "ALTER TABLE `glpi_dropdown_budget` ADD `end_date` DATE NULL";
			$DB->query($query) or die("0.80 add end_date field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!FieldExists("glpi_dropdown_budget","value")) {
         $query = "ALTER TABLE `glpi_dropdown_budget` ADD `value` DECIMAL( 20, 4 )  NOT NULL default '0.0000'";
			$DB->query($query) or die("0.80 add value field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!FieldExists("glpi_dropdown_budget","is_template")) {
         $query = "ALTER TABLE `glpi_dropdown_budget` ADD `is_template` tinyint(1) NOT NULL default '0'";
			$DB->query($query) or die("0.80 add is_template field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }

      if (!FieldExists("glpi_dropdown_budget","tplname")) {
         $query = "ALTER TABLE `glpi_dropdown_budget`  ADD `tplname` varchar(255) default NULL";
			$DB->query($query) or die("0.80 add tplname field in glpi_dropdown_budget" . $LANG['update'][90] . $DB->error());
      }
      if (!TableExists("glpi_budgets")) {
         $query = "RENAME TABLE `glpi_dropdown_budget`  TO `glpi_budgets` ;";
         $DB->query($query) or die("0.80 rename glpi_dropdown_budget to glpi_budgets" . $LANG['update'][90] . $DB->error());
      }
	}

	// Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End
}
?>
