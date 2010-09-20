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

if (strpos($_SERVER['PHP_SELF'],"dropdownInstallVersion.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

checkRight("software","w");

if ($_POST['softwares_id']>0) {
   if (!isset($_POST['value'])) {
      $_POST['value'] = 0;
   }

   // Make a select box
   $query = "SELECT DISTINCT `glpi_softwareversions`.*,
                             `glpi_states`.`name` AS sname
             FROM `glpi_softwareversions`
             LEFT JOIN `glpi_states` ON (`glpi_softwareversions`.`states_id` = `glpi_states`.`id`)
             WHERE `glpi_softwareversions`.`softwares_id` = '".$_POST['softwares_id']."'
             ORDER BY `name`";
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   echo "<select name='".$_POST['myname']."' size='1'>";
   echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";

   $today = date("Y-m-d");

   if ($number) {
      while ($data = $DB->fetch_assoc($result)) {
         $ID = $data['id'];
         $output = $data['name'];

         if (empty($output) || $_SESSION['glpiis_ids_visible']) {
            $output .= " ($ID)";
         }
         if (!empty($data['sname'])) {
            $output .= " - " . $data['sname'];
         }
         echo "<option ".($ID==$_POST['value']?"selected":"")." value='$ID' title='".
               cleanInputText($output)."'>".$output."</option>";
      }
   }
   echo "</select>&nbsp;";
}

?>
