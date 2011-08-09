<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

/**
 * Complete Dropdown system using ajax to get datas
 *
 * @param $use_ajax Use ajax search system (if not display a standard dropdown)
 * @param $relativeurl Relative URL to the root directory of GLPI
 * @param $params Parameters to send to ajax URL
 * @param $default Default datas t print in case of $use_ajax
 * @param $rand Random parameter used
 *
 **/
function ajaxDropdown($use_ajax, $relativeurl, $params=array(), $default="&nbsp;", $rand=0) {
   global $CFG_GLPI, $DB, $LANG;

   $initparams = $params;
   if ($rand==0) {
      $rand = mt_rand();
   }

   if ($use_ajax) {
      Ajax::displaySearchTextForDropdown($rand);
      Ajax::updateItemOnInputTextEvent("search_$rand", "results_$rand",
                                     $CFG_GLPI["root_doc"].$relativeurl, $params,
                                     $CFG_GLPI['ajax_min_textsearch_load']);
   }
   echo "<span id='results_$rand'>\n";
   if (!$use_ajax) {
      // Save post datas if exists
      $oldpost = array();
      if (isset($_POST) && count($_POST)) {
         $oldpost = $_POST;
      }
      $_POST = $params;
      $_POST["searchText"] = $CFG_GLPI["ajax_wildcard"];
      include (GLPI_ROOT.$relativeurl);
      // Restore $_POST datas
      if (count($oldpost)) {
         $_POST = $oldpost;
      }
   } else {
      echo $default;
   }
   echo "</span>\n";
   echo "<script type='text/javascript'>";
   echo "function update_results_$rand() {";
   if ($use_ajax) {
      Ajax::updateItemJsCode("results_$rand", $CFG_GLPI['root_doc'].$relativeurl, $initparams,
                           "search_$rand");
   } else {
      $initparams["searchText"]=$CFG_GLPI["ajax_wildcard"];
      Ajax::updateItemJsCode("results_$rand", $CFG_GLPI['root_doc'].$relativeurl, $initparams);
   }
   echo "}";
   echo "</script>";
}



/**
 * Javascript code for update an item when a select item changed
 *
 * @param $toobserve id of the select to observe
 * @param $toupdate id of the item to update
 * @param $url Url to get datas to update the item
 * @param $parameters Parameters to send to ajax URL
 *
 **/
function ajaxUpdateItemOnSelectEvent($toobserve, $toupdate, $url, $parameters=array()) {

   Ajax::updateItemOnEvent($toobserve, $toupdate, $url, $parameters, array("change"));
}



/**
 * Javascript code for update an item
 *
 * @param $toupdate id of the item to update
 * @param $url Url to get datas to update the item
 * @param $parameters Parameters to send to ajax URL
 * @param $toobserve id of another item used to get value in case of __VALUE__ used
 *
 **/
function ajaxUpdateItem($toupdate, $url, $parameters=array(), $toobserve="") {

   echo "<script type='text/javascript'>";
   Ajax::updateItemJsCode($toupdate,$url,$parameters,$toobserve);
   echo "</script>";
}


?>
