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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

/**
 * Update from 0.802 to 0.80.1
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
**/
function update080to0801($output='HTML') {
   global $DB, $LANG, $migration;

   $updateresult     = true;
   $ADDTODISPLAYPREF = array();

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   }


   if ($migration->addField("glpi_slalevels", "entities_id", "INT( 11 ) NOT NULL DEFAULT 0")) {
      $migration->addField("glpi_slalevels", "is_recursive", "TINYINT( 1 ) NOT NULL DEFAULT 0");
      $migration->migrationOneTable('glpi_slalevels');

      $entities    = getAllDatasFromTable('glpi_entities');
      $entities[0] = "Root";


      foreach ($entities as $entID => $val) {
         // Non recursive ones
         $query3 = "UPDATE `glpi_slalevels`
                    SET `entities_id` = $entID, `is_recursive` = 0
                    WHERE `slas_id` IN (SELECT `id`
                                        FROM `glpi_slas`
                                        WHERE `entities_id` = $entID
                                              AND `is_recursive` = 0)";
         $DB->query($query3)
         or die("0.80.1 update entities_id and is_recursive=0 in glpi_slalevels ".$LANG['update'][90].
                $DB->error());

         // Recursive ones
         $query3 = "UPDATE `glpi_slalevels`
                    SET `entities_id` = $entID, `is_recursive` = 1
                    WHERE `slas_id` IN (SELECT `id`
                                        FROM `glpi_slas`
                                        WHERE `entities_id` = $entID
                                              AND `is_recursive` = 1)";
         $DB->query($query3)
         or die("0.80.1 update entities_id and is_recursive=1 in glpi_slalevels ".$LANG['update'][90].
                $DB->error());
      }
   }

   // must always be at the end
   $migration->executeMigration();

   return $updateresult;
}
?>
