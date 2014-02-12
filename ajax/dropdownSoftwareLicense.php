<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (strpos($_SERVER['PHP_SELF'],"dropdownSoftwareLicense.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRight("software", "w");

if ($_POST['softwares_id'] > 0) {
   if (!isset($_POST['value'])) {
      $_POST['value'] = 0;
   }

   $restrict = getEntitiesRestrictRequest(' AND', 'glpi_softwarelicenses', 'entities_id',
                                          $_POST['entity_restrict'], true);

   // Make a select box
   $query = "SELECT DISTINCT *
             FROM `glpi_softwarelicenses`
             WHERE `glpi_softwarelicenses`.`softwares_id` = '".$_POST['softwares_id']."'
                   $restrict
             ORDER BY `name`";
   $result = $DB->query($query);
   $number = $DB->numrows($result);

   echo "<select name='".$_POST['myname']."' size='1'>";
   echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

   $today = date("Y-m-d");

   if ($number) {
      while ($data = $DB->fetch_assoc($result)) {
         $ID     = $data['id'];
         $output = $data['name'];

         if (empty($output) || $_SESSION['glpiis_ids_visible']) {
            $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
         }
         echo "<option ".($ID==$_POST['value']?"selected":"")." value='$ID' title=\"".
                Html::cleanInputText($output)."\">".$output."</option>";
      }
   }
   echo "</select>&nbsp;";
}
?>
