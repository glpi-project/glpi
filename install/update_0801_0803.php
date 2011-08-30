<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Update from 0.80.1 to 0.80.3
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
**/
function update0801to0803($output='HTML') {
   global $DB, $LANG, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.80.3</h3>";
   }

   $migration->changeField("glpi_fieldunicities", 'fields', 'fields', "text");

   $migration->dropKey('glpi_ocslinks', 'unicity');
   $migration->migrationOneTable('glpi_ocslinks');
   $migration->addKey("glpi_ocslinks", array('ocsid', 'ocsservers_id'),
                        "unicity", "UNIQUE");

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
?>
