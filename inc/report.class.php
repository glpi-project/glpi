<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Report class
 *
 * @ since version 0.84
**/
class Report {

   static protected $notable = false;


   static function getTypeName($nb=0) {
      return _n('Report', 'Reports', $nb);
   }


   static function title() {
      global $PLUGIN_HOOKS, $CFG_GLPI;

      // Report generation
      // Default Report included
      $report_list["default"]["name"] = __('Default report');
      $report_list["default"]["file"] = "report.default.php";

      if (Session::haveRight("contract","r")) {
         // Rapport ajoute par GLPI V0.2
         $report_list["Contrats"]["name"] = __('By contract');
         $report_list["Contrats"]["file"] = "report.contract.php";
      }
      if (Session::haveRight("infocom","r")) {
         $report_list["Par_annee"]["name"] = __('By year');
         $report_list["Par_annee"]["file"] = "report.year.php";
         $report_list["Infocoms"]["name"]  = __('Hardware financial and administrative information');
         $report_list["Infocoms"]["file"]  = "report.infocom.php";
         $report_list["Infocoms2"]["name"] = __('Other financial and administrative information (licenses, cartridges, consumables)');
         $report_list["Infocoms2"]["file"] = "report.infocom.conso.php";
      }
      if (Session::haveRight("networking","r")) {
         $report_list["Rapport prises reseau"]["name"] = __('Network report');
         $report_list["Rapport prises reseau"]["file"] = "report.networking.php";
      }
      if (Session::haveRight("reservation_central","r")) {
         $report_list["reservation"]["name"] = __('Loan');
         $report_list["reservation"]["file"] = "report.reservation.php";
      }
      if (Session::haveRight("computer","r")
          || Session::haveRight("monitor","r")
          || Session::haveRight("networking","r")
          || Session::haveRight("peripheral","r")
          || Session::haveRight("printer","r")
          || Session::haveRight("phone","r")) {
         $report_list["state"]["name"] = _n('Status', 'Statuses', 2);
         $report_list["state"]["file"] = "report.state.php";
      }
      //Affichage du tableau de presentation des stats
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".__('Select the report you want to generate')."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo "<select name='statmenu' onchange='window.location.href=this.options
             [this.selectedIndex].value'>";
      echo "<option value='-1' selected>".Dropdown::EMPTY_VALUE."</option>";

      $i     = 0;
      $count = count($report_list);
      while ($data = each($report_list)) {
         $val  = $data[0];
         $name = $report_list["$val"]["name"];
         $file = $report_list["$val"]["file"];
         echo "<option value='".$CFG_GLPI["root_doc"]."/front/".$file."'>".$name."</option>";
         $i++;
      }

      $names    = array();
      $optgroup = array();
      if (isset($PLUGIN_HOOKS["reports"]) && is_array($PLUGIN_HOOKS["reports"])) {
         foreach ($PLUGIN_HOOKS["reports"] as $plug => $pages) {
            if (is_array($pages) && count($pages)) {
               foreach ($pages as $page => $name) {
                  $names[$plug.'/'.$page] = array("name" => $name,
                                                  "plug" => $plug);
                  $optgroup[$plug] = Plugin::getInfo($plug, 'name');
               }
            }
         }
         asort($names);
      }

      foreach ($optgroup as $opt => $title) {
         echo "<optgroup label=\"". $title ."\">";

         foreach ($names as $key => $val) {
             if ($opt == $val["plug"]) {
               echo "<option value='".$CFG_GLPI["root_doc"]."/plugins/".$key."'>".$val["name"].
                    "</option>";
             }
         }
          echo "</optgroup>";
      }

      echo "</select>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }
}
?>
