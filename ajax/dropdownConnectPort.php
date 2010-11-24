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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE = 1;

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("networking","w");

// Make a select box
if (class_exists($_POST["itemtype"]) && isset($_POST["item"])) {
   $table = getTableForItemType($_POST["itemtype"]);

   $query = "SELECT DISTINCT `glpi_networkports_networkports`.`id` AS wid,
                             `glpi_networkports`.`id` AS did,
                             `$table`.`name` AS cname,
                             `glpi_networkports`.`name` AS nname,
                             `glpi_netpoints`.`name` AS npname
             FROM `$table`
             LEFT JOIN `glpi_networkports`
               ON (`glpi_networkports`.`items_id` = '".$_POST['item']."'
                   AND `glpi_networkports`.`itemtype` = '".$_POST["itemtype"]."'
                   AND `glpi_networkports`.`items_id` = `$table`.`id`)
             LEFT JOIN `glpi_networkports_networkports`
               ON (`glpi_networkports_networkports`.`networkports_id_1` = `glpi_networkports`.`id`
                   OR `glpi_networkports_networkports`.`networkports_id_2`=`glpi_networkports`.`id`)
             LEFT JOIN `glpi_netpoints`
               ON (`glpi_netpoints`.`id`=`glpi_networkports`.`netpoints_id`)
             WHERE `glpi_networkports_networkports`.`id` IS NULL
                   AND `glpi_networkports`.`id` IS NOT NULL
                   AND `glpi_networkports`.`id` <> '".$_POST['current']."'
                   AND `$table`.`is_deleted` = '0'
                   AND `$table`.`is_template` = '0'
             ORDER BY `glpi_networkports`.`id`";
   $result = $DB->query($query);

   echo "<br>";
   echo "<select name='".$_POST['myname']."[".$_POST["current"]."]' size='1'>";
   echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";

   if ($DB->numrows($result)) {
      while ($data = $DB->fetch_array($result)) {
         // Device name + port name
         $output = $output_long = $data['cname'];

         if (!empty($data['nname'])) {
            $output      .= " - ".$data['nname'];
            $output_long .= " - " . $LANG['networking'][44] . " " . $data['nname'];
         }

         // display netpoint (which will be copied)
         if (!empty($data['npname'])) {
            $output      .= " - ".$data['npname'];
            $output_long .= " - " . $LANG['networking'][51] . " " . $data['npname'];
         }
         $ID = $data['did'];

         if ($_SESSION["glpiis_ids_visible"] || empty($output)) {
            $output      .= " ($ID)";
            $output_long .= " ($ID)";
         }
         $output = utf8_substr($output, 0, $_SESSION["glpidropdown_chars_limit"]);
         echo "<option value='$ID' title=\"".cleanInputText($output_long)."\">".$output;
         echo "</option>";
      }
   }
   echo "</select>";

   echo "<input type='submit' name='connect' value=\"".$LANG['buttons'][9]."\" class='submit'>";
}

?>
