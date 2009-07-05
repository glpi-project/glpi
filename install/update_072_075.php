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

/// Update from 0.72 to 0.75

function update072to075() {
	global $DB, $LANG;

	echo "<h3>".$LANG['install'][4]." -&gt; 0.75</h3>";
	displayMigrationMessage("075"); // Start


	displayMigrationMessage("075", $LANG['update'][140]); // Index creation
	if (!isIndex('glpi_groups', 'ldap_group_dn')) {
		$query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_group_dn` ( `ldap_group_dn` );";
		$DB->query($query) or die("0.75 add index on ldap_group_dn in glpi_groups" . $LANG['update'][90] . $DB->error());
	}	  	
	if (!isIndex('glpi_groups', 'ldap_value')) {
		$query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_value` ( `ldap_value` );";
		$DB->query($query) or die("0.75 add index on ldap_value in glpi_groups" . $LANG['update'][90] . $DB->error());
	}	  	


	displayMigrationMessage("075", $LANG['update'][141] . ' - glpi_config'); // Updating schema
	if (FieldExists('glpi_config', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_config` DROP `license_deglobalisation`;";
		$DB->query($query) or die("0.72 alter clean glpi_config table" . $LANG['update'][90] . $DB->error());
	}	

	displayMigrationMessage("075", $LANG['update'][141] . ' - glpi_mailgate'); // Updating schema

	if (!FieldExists("glpi_mailgate", "active")) {
		$query = "ALTER TABLE `glpi_mailgate` ADD `active` INT( 1 ) NOT NULL DEFAULT '1' ;";
		$DB->query($query) or die("0.72 add active in glpi_mailgate" . $LANG['update'][90] . $DB->error());
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


	// Display "Work ended." message - Keep this as the last action.
	displayMigrationMessage("075"); // End
}
