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

/// Update from 0.72.1 to 0.72.2

function update0721to0722() {
	global $DB, $CFG_GLPI, $LANG;


	echo "<h3>".$LANG['install'][4]." -&gt; 0.72.2</h3>";
	displayMigrationMessage("0722"); // Start
         
   // Delete state from reservation search
   $query = "DELETE FROM `glpi_display` WHERE type=".RESERVATION_TYPE." AND num=31;";
   $DB->query($query) or die("0.72.2 delete search of state from reservations" . $LANG['update'][90] . $DB->error());

   // Clean licences alerts
   $query = "DELETE FROM `glpi_alerts` WHERE device_type=".SOFTWARELICENSE_TYPE.";";
   $DB->query($query) or die("0.72.2 delete search of state from reservations" . $LANG['update'][90] . $DB->error());


   //// Correct search.constant numbers
   $updates=array();
   // location :
   $updates[]=array('type'=>CARTRIDGEITEM_TYPE,'from'=>3,'to'=>34);
   $updates[]=array('type'=>CARTRIDGEITEM_TYPE,'from'=>6,'to'=>3);
   $updates[]=array('type'=>CONSUMABLEITEM_TYPE,'from'=>3,'to'=>34);
   $updates[]=array('type'=>CONSUMABLEITEM_TYPE,'from'=>6,'to'=>3);
   $updates[]=array('type'=>USER_TYPE,'from'=>3,'to'=>34);
   $updates[]=array('type'=>USER_TYPE,'from'=>7,'to'=>3);
   // serial / otherserial
   $updates[]=array('type'=>COMPUTER_TYPE,'from'=>40,'to'=>46);
   $updates[]=array('type'=>COMPUTER_TYPE,'from'=>5,'to'=>40);
   $updates[]=array('type'=>COMPUTER_TYPE,'from'=>8,'to'=>5);
   $updates[]=array('type'=>COMPUTER_TYPE,'from'=>6,'to'=>45);
   $updates[]=array('type'=>COMPUTER_TYPE,'from'=>9,'to'=>6);
   $updates[]=array('type'=>STATE_TYPE,'from'=>9,'to'=>6);
   $updates[]=array('type'=>STATE_TYPE,'from'=>8,'to'=>5);
   // Manufacturer
   $updates[]=array('type'=>CONSUMABLEITEM_TYPE,'from'=>5,'to'=>23);
   $updates[]=array('type'=>CARTRIDGEITEM_TYPE,'from'=>5,'to'=>23);
   // tech_num
   $updates[]=array('type'=>CONSUMABLEITEM_TYPE,'from'=>7,'to'=>24);
   $updates[]=array('type'=>CARTRIDGEITEM_TYPE,'from'=>7,'to'=>24);
   // date_mod
   $updates[]=array('type'=>NETWORKING_TYPE,'from'=>9,'to'=>19);
   $updates[]=array('type'=>PRINTER_TYPE,'from'=>9,'to'=>19);
   $updates[]=array('type'=>MONITOR_TYPE,'from'=>9,'to'=>19);
   $updates[]=array('type'=>PERIPHERAL_TYPE,'from'=>9,'to'=>19);
   $updates[]=array('type'=>SOFTWARE_TYPE,'from'=>9,'to'=>19);
   $updates[]=array('type'=>PHONE_TYPE,'from'=>9,'to'=>19);
   // comments
   $updates[]=array('type'=>NETWORKING_TYPE,'from'=>10,'to'=>16);
   $updates[]=array('type'=>PRINTER_TYPE,'from'=>10,'to'=>16);
   $updates[]=array('type'=>MONITOR_TYPE,'from'=>10,'to'=>16);
   $updates[]=array('type'=>PERIPHERAL_TYPE,'from'=>10,'to'=>16);
   $updates[]=array('type'=>SOFTWARE_TYPE,'from'=>6,'to'=>16);
   $updates[]=array('type'=>CONTACT_TYPE,'from'=>7,'to'=>16);
   $updates[]=array('type'=>ENTERPRISE_TYPE,'from'=>7,'to'=>16);
   $updates[]=array('type'=>CARTRIDGEITEM_TYPE,'from'=>10,'to'=>16);
   $updates[]=array('type'=>DOCUMENT_TYPE,'from'=>6,'to'=>16);
   $updates[]=array('type'=>USER_TYPE,'from'=>12,'to'=>16);
   $updates[]=array('type'=>PHONE_TYPE,'from'=>10,'to'=>16);


   foreach ($updates as $data){
      $query = "UPDATE `glpi_display` SET num=".$data['to']." WHERE num=".$data['from']." AND type=".$data['type'].";";
      $DB->query($query) or die("0.72.2 reorder search.constant " . $LANG['update'][90] . $DB->error());
   }

      // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0722"); // End
} // fin 0.72.2 
?>
