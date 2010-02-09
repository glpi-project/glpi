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

/// Update from 0.72 to 0.72.1

function update072to0721() {
	global $DB, $CFG_GLPI, $LANG;


	echo "<h3>".$LANG['install'][4]." -&gt; 0.72.1</h3>";
	displayMigrationMessage("0721"); // Start
         
   if (!isIndex("glpi_groups", "ldap_group_dn")) {
      $query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_group_dn` ( `ldap_group_dn` );";
      $DB->query($query) or die("0.72.1 add index on ldap_group_dn in glpi_groups" . $LANG['update'][140] . $DB->error());
   }

   if (!isIndex("glpi_groups", "ldap_value")) {
      $query = "ALTER TABLE `glpi_groups` ADD INDEX `ldap_value`  ( `ldap_value` );";
      $DB->query($query) or die("0.72.1 add index on ldap_value in glpi_groups" . $LANG['update'][140] . $DB->error());
   }

   if (!isIndex('glpi_tracking', 'date_mod')) {
      $query=" ALTER TABLE `glpi_tracking` ADD INDEX `date_mod` (`date_mod`)  ";
      $DB->query($query) or die("0.72.1 add date_mod index in glpi_tracking " . $LANG['update'][90] . $DB->error());
   }

	// Display "Work ended." message - Keep this as the last action.
	displayMigrationMessage("0721"); // End
} // fin 0.72.1 #####################################################################################
?>
