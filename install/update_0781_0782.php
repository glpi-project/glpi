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

/**
 * Update from 0.78.1 to 0.78.2
 *
 * @param $output string for format
 *       HTML (default) for standard upgrade
 *       empty = no ouput for PHPUnit
 *
 * @return bool for success (will die for most error)
 */
function update0781to0782($output='HTML') {
   global $DB, $LANG;

   $updateresult = true;

   if ($output) {
      echo "<h3>".$LANG['install'][4]." -&gt; 0.78.2</h3>";
   }
   displayMigrationMessage("0782"); // Start

   displayMigrationMessage("0782", $LANG['update'][142]); // Updating schema

   /// Add document types
   $types = array('docx' => array('name' => 'Word XML',
                                  'icon' => 'doc-dist.png'),
                  'xlsx' => array('name' => 'Excel XML',
                                  'icon' => 'xls-dist.png'),
                  'pptx' => array('name' => 'PowerPoint XML',
                                  'icon' => 'ppt-dist.png'));

   foreach ($types as $ext => $data) {

      $query = "SELECT *
                FROM `glpi_documenttypes`
                WHERE `ext` = '$ext'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result) == 0) {
            $query = "INSERT INTO `glpi_documenttypes`
                             (`name`, `ext`, `icon`, `is_uploadable`, `date_mod`)
                      VALUES ('".$data['name']."', '$ext', '".$data['icon']."', '1', NOW())";
            $DB->query($query)
            or die("0.80 add document type $ext ".$LANG['update'][90] .$DB->error());
         }
      }
   }


   // Drop nl_be langage
   $query = "UPDATE `glpi_configs`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->query($query) or die("0.80 drop nl_be langage " . $LANG['update'][90] . $DB->error());

   $query = "UPDATE `glpi_users`
             SET `language` = 'nl_NL'
             WHERE `language` = 'nl_BE';";
   $DB->query($query) or die("0.80 drop nl_be langage " . $LANG['update'][90] . $DB->error());

   // CLean sl_SL
   $query = "UPDATE `glpi_configs`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->query($query) or die("0.80 clean sl_SL langage " . $LANG['update'][90] . $DB->error());

   $query = "UPDATE `glpi_users`
             SET `language` = 'sl_SI'
             WHERE `language` = 'sl_SL';";
   $DB->query($query) or die("0.80 clean sl_SL langage " . $LANG['update'][90] . $DB->error());


   if (isIndex('glpi_computers_items', 'unicity')) {
      $query = "ALTER TABLE `glpi_computers_items` DROP INDEX `unicity`";
      $DB->query($query) or die("0.78.2 drop unicity index for glpi_computers_items " .
                                 $LANG['update'][90] . $DB->error());

      $query = "ALTER TABLE `glpi_computers_items` ADD INDEX `item` ( `itemtype` , `items_id` ) ";
      $DB->query($query) or die("0.78.2 add index for glpi_computers_items " .
                                 $LANG['update'][90] . $DB->error());
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("0782"); // End

   return $updateresult;
}
?>
