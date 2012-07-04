<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// since version 0.84
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
// print_r($_GET);
if (isset($_GET['itemtype'])) {
   if (!($item = getItemForItemtype($_GET['itemtype']))) {
      exit();
   }
   if (!isset($_GET['is_deleted'])) {
      $_GET['is_deleted'] = 0;
   }
   $checkitem = NULL;
   if (isset($_GET['check_itemtype'])) {
      $checkitem = new $_GET['check_itemtype']();
      if (isset($_GET['check_items_id'])) {
         $checkitem->getFromDB($_GET['check_items_id']);
      }
   }
   echo "<div width='90%' class='center'><br>";

   $params = array('action'     => '__VALUE__',
                   'is_deleted' => $_GET['is_deleted'],
                   'itemtype'   => $_GET['itemtype']);

   if (isset($_GET['check_itemtype'])) {
      $params['check_itemtype'] = $_GET['check_itemtype'];
      if (isset($_GET['check_items_id'])) {
         $params['check_items_id'] = $_GET['check_items_id'];
      }
   }
   $rand    = mt_rand();
   $actions = $item->getAllMassiveActions($_GET['is_deleted'], $checkitem);
//    print_r($actions);
   if (count($actions)) {
      _e('Action');
      echo "&nbsp;";
      echo "<select name='massiveaction' id='massiveaction$rand'>";
      echo "<option value='-1' selected>".Dropdown::EMPTY_VALUE."</option>";
      foreach ($actions as $key => $val) {
         echo "<option value = '$key'>$val</option>";
      }
      echo "</select><br><br>";

      Ajax::updateItemOnSelectEvent("massiveaction$rand", "show_massiveaction$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveAction.php",
                                    $params);

      echo "<span id='show_massiveaction$rand'>&nbsp;</span>\n";
   }
   echo "</div>";
}
?>
