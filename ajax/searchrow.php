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
if (strpos($_SERVER['PHP_SELF'],"searchrow.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

// Non define case
if (isset($_POST["itemtype"])
    && isset($_POST["num"]) ) {

   $options  = Search::getCleanedOptions($_POST["itemtype"]);

   $randrow  = mt_rand();
   $rowid    = 'searchrow'.$_POST['itemtype'].$randrow;

   $addclass = '';
   if ($_POST["num"] == 0) {
      $addclass = ' headerRow';
   }
   echo "<tr class='normalcriteria$addclass' id='$rowid'><td class='left' width='45%'>";
   // First line display add / delete images for normal and meta search items
   if ($_POST["num"] == 0) {
      $linked = Search::getMetaItemtypeAvailable($_POST["itemtype"]);
      echo "<img class='pointer' src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title=\"".
             __s('Add a search criterion')."\" id='addsearchcriteria$randrow'>";

      $js = Html::jsGetElementbyID("addsearchcriteria$randrow").".on('click', function(e) {
               $.post( '".$CFG_GLPI['root_doc']."/ajax/searchrow.php',
                     { itemtype: '".$_POST["itemtype"]."', num: $nbsearchcountvar })
                        .done(function( data ) {
                        $('#".$searchcriteriatableid." .normalcriteria:last').after(data);
                        });
            $nbsearchcountvar = $nbsearchcountvar +1;});";
      echo Html::scriptBlock($js);

      echo "&nbsp;&nbsp;&nbsp;&nbsp;";

      if (is_array($linked) && (count($linked) > 0)) {
         echo "<img class='pointer' src=\"".$CFG_GLPI["root_doc"]."/pics/meta_plus.png\" 
                alt='+' title=\"". __s('Add a global search criterion').
                "\" id='addmetasearchcriteria$randrow'>";

         $js = Html::jsGetElementbyID("addmetasearchcriteria$randrow").".on('click', function(e) {
                  $.post( '".$CFG_GLPI['root_doc']."/ajax/searchmetarow.php',
                        { itemtype: '".$_POST["itemtype"]."', num: $nbmetasearchcountvar })
                           .done(function( data ) {
                           $('#".$searchcriteriatableid."').append(data);
                           });
               $nbmetasearchcountvar = $nbmetasearchcountvar +1;});";
         echo Html::scriptBlock($js);
         echo "&nbsp;&nbsp;&nbsp;&nbsp;";
      }

      // Instanciate an object to access method
      $item = NULL;
      if ($_POST["itemtype"] != 'AllAssets') {
         $item = getItemForItemtype($_POST["itemtype"]);
      }
      if ($item && $item->maybeDeleted()) {
         echo "<input type='hidden' id='is_deleted' name='is_deleted' value='".$p['is_deleted']."'>";
      }
   } else {
      echo "<img class='pointer' src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title=\"".
             __s('Delete a search criterion')."\" onclick=\"".
             Html::jsGetElementbyID($rowid).".remove();\">&nbsp;&nbsp;";
   }

   $criteria = array();

   if (isset($_SESSION['glpisearch'][$_POST["itemtype"]]['criteria'][$_POST["num"]])
       && is_array($_SESSION['glpisearch'][$_POST["itemtype"]]['criteria'][$_POST["num"]])) {
      $criteria = $_SESSION['glpisearch'][$_POST["itemtype"]]['criteria'][$_POST["num"]];
   } else {
      foreach ($options as $key => $val) {
         if (is_array($val)) {
            $criteria['field'] = $key;
            break;
         }
      }
   }

   // Display link item
   if ($_POST["num"] > 0) {
      $value = '';
      if (isset($criteria["link"])) {
         $value = $criteria["link"];
      }
      Dropdown::showFromArray("criteria[".$_POST["num"]."][link]",
                              Search::getLogicalOperators(),
                              array('value' => $value));
   }

   $selected = $first = '';
   $values   = array();
   // display select box to define search item
   if ($CFG_GLPI['allow_search_view'] == 2) {
      $values['view'] = __('Items seen');
   }

   reset($options);
   $group = '';

   foreach ($options as $key => $val) {
      // print groups
      if (!is_array($val)) {
         $group = $val;
      } else {
         if (!isset($val['nosearch']) || ($val['nosearch'] == false)) {
            $values[$group][$key] = $val["name"];
         }
      }
   }
   if ($CFG_GLPI['allow_search_view'] == 1) {
      $values['view'] = __('Items seen');
   }
   if ($CFG_GLPI['allow_search_all']) {
      $values['all'] = __('All');
   }
   $value = '';

   if (isset($criteria['field'])) {
      $value = $criteria['field'];
   } 

   $rand     = Dropdown::showFromArray("criteria[".$_POST["num"]."][field]", $values,
                                       array('value' => $value));
   $field_id = Html::cleanId("dropdown_criteria[".$_POST["num"]."][field]$rand");
   echo "</td><td class='left'>";
   $spanid= 'SearchSpan'.$_POST["itemtype"].$_POST["num"];
   echo "<div id='$spanid'>\n";

   $used_itemtype = $_POST["itemtype"];

   // Force Computer itemtype for AllAssets to permit to show specific items
   if ($_POST["itemtype"] == 'AllAssets') {
      $used_itemtype = 'Computer';
   }

   $_POST['itemtype']   = $used_itemtype;
   $_POST['field']      = $value;
   $_POST['searchtype'] = (isset($criteria['searchtype'])?$criteria['searchtype']:"" );
   $_POST['value']      = (isset($criteria['value'])?stripslashes($criteria['value']):"" );
   include (GLPI_ROOT."/ajax/searchoption.php");
   echo "</div>\n";

   $params = array('field'      => '__VALUE__',
                   'itemtype'   => $used_itemtype,
                   'num'        => $_POST["num"],
                   'value'      => $_POST["value"],
                   'searchtype' => $_POST["searchtype"]);

   Ajax::updateItemOnSelectEvent($field_id, $spanid,
                                 $CFG_GLPI["root_doc"]."/ajax/searchoption.php", $params);

   echo "</td></tr>\n";
}
?>