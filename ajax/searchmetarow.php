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
* @since version 0.85
*/

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"searchmetarow.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

// Non define case
if (isset($_POST["itemtype"])
    && isset($_POST["num"]) ) {

   $metacriteria = array();

   if (isset($_SESSION['glpisearch'][$_POST["itemtype"]]['metacriteria'][$_POST["num"]])
       && is_array($_SESSION['glpisearch'][$_POST["itemtype"]]['metacriteria'][$_POST["num"]])) {
      $metacriteria = $_SESSION['glpisearch'][$_POST["itemtype"]]['metacriteria'][$_POST["num"]];
   } else {
      // Set default field
      $options  = Search::getCleanedOptions($_POST["itemtype"]);

      foreach ($options as $key => $val) {
         if (is_array($val)) {
            $metacriteria['field'] = $key;
            break;
         }
      }
   }
   $linked =  Search::getMetaItemtypeAvailable($_POST["itemtype"]);
   $rand   = mt_rand();

   $rowid  = 'metasearchrow'.$_POST['itemtype'].$rand;

   echo "<tr class='metacriteria' id='$rowid'><td class='left' colspan='2'>";
   
   echo "<table class='tab_format'><tr class='left'>";
   echo "<td width='30%'>";
   echo "<img class='pointer' src=\"".$CFG_GLPI["root_doc"]."/pics/meta_moins.png\" alt='-' title=\"".
          __s('Delete a global search criterion')."\" onclick=\"".
          Html::jsGetElementbyID($rowid).".remove();\">";
   echo "&nbsp;&nbsp;";

   // Display link item (not for the first item)
   $value = '';
   if (isset($metacriteria["link"])) {
      $value = $metacriteria["link"];
   }
   Dropdown::showFromArray("metacriteria[".$_POST["num"]."][link]",
                           Search::getLogicalOperators(),
                           array('value' => $value,
                                 'width' => '40%'));

   // Display select of the linked item type available
   foreach ($linked as $key) {
      if (!isset($metanames[$key])) {
         if ($linkitem = getItemForItemtype($key)) {
            $metanames[$key] = $linkitem->getTypeName();
         }
      }
   }
   $value = '';
   if (isset($metacriteria['itemtype'])
       && !empty($metacriteria['itemtype'])) {
      $value = $metacriteria['itemtype'];
   }

   $rand = Dropdown::showItemTypes("metacriteria[".$_POST["num"]."][itemtype]", $linked,
                                    array('width' => '50%',
                                          'value' => $value));
   $field_id = Html::cleanId("dropdown_metacriteria[".$_POST["num"]."][itemtype]$rand");
   echo "</td><td>";
   // Ajax script for display search met& item
   echo "<span id='show_".$_POST["itemtype"]."_".$_POST["num"]."_$rand'>&nbsp;</span>\n";

   $params = array('itemtype'   => '__VALUE__',
                   'num'        => $_POST["num"],
                   'field'      => (isset($metacriteria['field']) ? $metacriteria['field'] : ""),
                   'value'      => (isset($metacriteria['value'])
                                    ? stripslashes($metacriteria['value']) : ""),
                   'searchtype' => (isset($metacriteria['searchtype'])
                                    ? $metacriteria['searchtype'] : ""));

   Ajax::updateItemOnSelectEvent($field_id,
                                 "show_".$_POST["itemtype"]."_".$_POST["num"]."_$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",
                                 $params);

   if (isset($metacriteria['itemtype'])
       && !empty($metacriteria['itemtype'])) {

      $params['itemtype'] = $metacriteria['itemtype'];

      Ajax::updateItem("show_".$_POST["itemtype"]."_".$_POST["num"]."_$rand",
                       $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php", $params);

   }
   echo "</td></tr></table>";

   echo "</td></tr>\n";
}
?>
