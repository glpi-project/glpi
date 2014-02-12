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

include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Make a select box
if (isset($_POST["itemtype"])
    && CommonITILObject::isPossibleToAssignType($_POST["itemtype"])) {
   $table = getTableForItemType($_POST["itemtype"]);
   $rand  = mt_rand();

   // Message for post-only
   if (!isset($_POST["admin"]) || ($_POST["admin"] == 0)) {
      echo "<br>".__('Enter the first letters (user, item name, serial or asset number)');
   }

   echo "<br>";
   Ajax::displaySearchTextForDropdown($_POST['myname'].$rand,8);

   $paramstrackingdt = array('searchText'      => '__VALUE__',
                             'myname'          => $_POST["myname"],
                             'table'           => $table,
                             'itemtype'        => $_POST["itemtype"],
                             'entity_restrict' => $_POST['entity_restrict']);

   Ajax::updateItemOnInputTextEvent("search_".$_POST['myname'].$rand, "results_ID$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownFindNum.php",
                                    $paramstrackingdt);

   echo "<span id='results_ID$rand'>";
   echo "<select name='".$_POST["myname"]."'><option value='0'>".Dropdown::EMPTY_VALUE."</option>";
   echo "</select></span>\n";

}
?>
