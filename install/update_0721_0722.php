<?php


/*
 * @version $Id: update_072_0721.php 8616 2009-08-04 00:13:39Z moyo $
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

/// Update from 0.72.1 to 0.72.2

function update0721to0722() {
	global $DB, $CFG_GLPI, $LANG;


	echo "<h3>".$LANG['install'][4]." -&gt; 0.72.2</h3>";
	displayMigrationMessage("0722"); // Start
         
   // Delete state from reservation search
   $query = "DELETE FROM `glpi_display` WHERE type=".RESERVATION_TYPE." AND num=31;";
   $DB->query($query) or die("0.72.2 delete search of state from reservations" . $LANG['update'][90] . $DB->error());
   
      // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0722"); // End
} // fin 0.72.2 
?>
