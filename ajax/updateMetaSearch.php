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

if (!($item = getItemForItemtype($_POST['itemtype']))) {
   exit();
}

$item->checkGlobal('r');

$first_group    = true;
$newgroup       = "";
$items_in_group = 0;
$searchopt      = Search::getCleanedOptions($_POST["itemtype"], 'r', false);
echo "<table width='100%'><tr><td class='right'>";
echo "<select id='Search2".$_POST["itemtype"].$_POST["num"]."' name='field2[".$_POST["num"]."]'
       size='1'>";

foreach ($searchopt as $key => $val) {

   // print groups
   $str_limit   = 28; // not use $_SESSION['glpidropdown_chars_limit'] because it came to too short
   if (!is_array($val)) {
      if (!empty($newgroup)
          && $items_in_group>0) {
         echo $newgroup;
         $first_group = false;
      }
      $items_in_group = 0;
      $newgroup       = "";
      if (!$first_group) {
         $newgroup .= "</optgroup>";
      }
      $val       = Toolbox::substr($val, 0, $str_limit);
      $newgroup .= "<optgroup label=\"$val\">";

   } else {
      // No search on plugins
      echo $key."--";
      if (!isPluginItemType($key) && !isset($val["nometa"])) {
         $newgroup .= "<option value='$key' title=\"".Html::cleanInputText($val["name"])."\"";
         if ($key == $_POST["field"]) {
            $newgroup .= "selected";
         }
         $newgroup .= ">". Toolbox::substr($val["name"], 0, $str_limit) ."</option>\n";
         $items_in_group++;
      }
   }
}

if (!empty($newgroup)
    && $items_in_group > 0) {
   echo $newgroup;
}
if (!$first_group) {
   echo "</optgroup>";
}
echo "</select>";

echo "</td><td class='left'>";

echo "<span id='Search2Span".$_POST["itemtype"].$_POST["num"]."'>\n";

$_POST['meta']       = 1;
$_POST['searchtype'] = $_POST["searchtype2"];

include (GLPI_ROOT."/ajax/searchoption.php");
echo "</span>\n";

$params = array('field'      => '__VALUE__',
                'itemtype'   => $_POST["itemtype"],
                'num'        => $_POST["num"],
                'value'      => $_POST["value"],
                'searchtype' => $_POST["searchtype2"],
                'meta'       => 1);

Ajax::updateItemOnSelectEvent("Search2".$_POST["itemtype"].$_POST["num"],
                              "Search2Span".$_POST["itemtype"].$_POST["num"],
                              $CFG_GLPI["root_doc"]."/ajax/searchoption.php", $params);
echo '</td></tr></table>';
?>
