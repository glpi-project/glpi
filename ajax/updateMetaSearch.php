<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

if (!($item = getItemForItemtype($_POST['itemtype']))) {
   exit();
}

$item->checkGlobal(READ);

$group     = "";
$values    = array();
$searchopt = Search::getCleanedOptions($_POST["itemtype"], READ, false);
echo "<table width='100%'><tr><td width='40%'>";

foreach ($searchopt as $key => $val) {

   // print groups
   $str_limit   = 28;
   if (!is_array($val)) {
      $group = $val;
   } else {
      // No search on plugins
      if (!isPluginItemType($key) && !isset($val["nometa"])) {
         $values[$group][$key] = $val["name"];
      }
   }
}
$rand     = Dropdown::showFromArray("metacriteria[".$_POST["num"]."][field]", $values,
                                    array('value' => $_POST["field"]));
$field_id = Html::cleanId("dropdown_metacriteria[".$_POST["num"]."][field]".$rand);

echo "</td><td class='left'>";

echo "<span id='Search2Span".$_POST["itemtype"].$_POST["num"]."'>\n";

$_POST['meta'] = 1;

include (GLPI_ROOT."/ajax/searchoption.php");
echo "</span>\n";

$params = array('field'      => '__VALUE__',
                'itemtype'   => $_POST["itemtype"],
                'num'        => $_POST["num"],
                'value'      => $_POST["value"],
                'searchtype' => $_POST["searchtype"],
                'meta'       => 1);

Ajax::updateItemOnSelectEvent($field_id,
                              "Search2Span".$_POST["itemtype"].$_POST["num"],
                              $CFG_GLPI["root_doc"]."/ajax/searchoption.php", $params);
echo '</td></tr></table>';
?>
