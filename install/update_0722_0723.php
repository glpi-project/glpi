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

/// Update from 0.72.2 to 0.72.3

function update0722to0723() {
	global $DB, $CFG_GLPI, $LANG,$LINK_ID_TABLE;


	echo "<h3>".$LANG['install'][4]." -&gt; 0.72.3</h3>";
	displayMigrationMessage("0723"); // Start
         
   //// Correct search.constant numbers : problem in previous update
   $updates=array();
   // serial / otherserial
   $updates[]=array('type'=>STATE_TYPE,'from'=>9,'to'=>6);
   $updates[]=array('type'=>STATE_TYPE,'from'=>8,'to'=>5);

   foreach ($updates as $data) {
      $query = "UPDATE `glpi_display` SET num=".$data['to']." WHERE num=".$data['from']." AND type=".$data['type'].";";
      $DB->query($query) or die("0.72.3 reorder search.constant " . $LANG['update'][90] . $DB->error());
   }

   $query="SELECT DISTINCT device_type FROM glpi_doc_device";
	if ($result = $DB->query($query)) {
      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            if (isset($LINK_ID_TABLE[$data['device_type']])) {
               $table=$LINK_ID_TABLE[$data['device_type']];
               $query2="DELETE FROM glpi_doc_device
                        WHERE device_type=".$data['device_type']."
                           AND FK_device NOT IN (SELECT ID FROM $table)";
               $DB->query($query2) or die("0.72.3 clean doc_device table " . $LANG['update'][90] . $DB->error());
            }
         }
      }
   }

	if (FieldExists("glpi_auth_ldap", "ldap_group_condition")) {
		$query = "ALTER TABLE `glpi_auth_ldap` CHANGE `ldap_group_condition` `ldap_group_condition` TEXT NULL DEFAULT NULL;";
		$DB->query($query) or die("0.72.3 alter ldap_group_condition in glpi_auth_ldap" . $LANG['update'][90] . $DB->error());
	}	  	


 

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0723"); // End
} // fin 0.72.3 
?>
