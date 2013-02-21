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

   
   /**
    * Show report title
    *
    * @param none
   **/
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
   
   /**
    * Show Default Report
    *
    * @param none
   **/
   static function showDefaultReport() {
      global $DB;
      
      # Title
      echo "<span class='big b'>GLPI ".Report::getTypeName(2)."</span><br><br>";

      # 1. Get counts of itemtype
      $items = array('Computer',
                     'Printer',
                     'NetworkEquipment',
                     'Software',
                     'Monitor',
                     'Peripheral',
                     'Phone');

      $linkitems = array('Printer',
                     'Monitor',
                     'Peripheral',
                     'Phone');

      echo "<table class='tab_cadrehov'>";

      foreach ($items as $itemtype) {
         
         $table_item = getTableForItemType($itemtype);
         
         $where = "WHERE `".$table_item."`.`is_deleted` = '0'
                      AND `".$table_item."`.`is_template` = '0' ";
                      
         $join ="";
         if (in_array($itemtype, $linkitems)) {
            $join =  "LEFT JOIN `glpi_computers_items`
                        ON (`glpi_computers_items`.`itemtype` = '".$itemtype."'
                       AND `glpi_computers_items`.`items_id` = `".$table_item."`.`id`)";

         }
         $query = "SELECT COUNT(*)
                FROM `".$table_item."`
                $join
                $where ".
                  getEntitiesRestrictRequest("AND",$table_item);
         $result              = $DB->query($query);
         $number = $DB->result($result,0,0);
         
         echo "<tr class='tab_bg_2'><td>".$itemtype::getTypeName(2)."</td>";
         echo "<td class='numeric'>$number</td></tr>";

      }

      echo "<tr class='tab_bg_1'><td colspan='2' class='b'>".__('Operating system')."</td></tr>";


      # 2. Get some more number data (operating systems per computer)

      $where = "WHERE `is_deleted` = '0'
                      AND `is_template` = '0' ";
                      
      $query = "SELECT COUNT(*) AS count, `glpi_operatingsystems`.`name` AS name
                FROM `glpi_computers`
                LEFT JOIN `glpi_operatingsystems`
                   ON (`glpi_computers`.`operatingsystems_id` = `glpi_operatingsystems`.`id`)
                $where ".
                  getEntitiesRestrictRequest("AND","glpi_computers")."
                GROUP BY `glpi_operatingsystems`.`name`";
      $result = $DB->query($query);

      while ($data=$DB->fetch_assoc($result)) {
         if (empty($data['name'])) {
            $data['name'] = Dropdown::EMPTY_VALUE;
         }
         echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
         echo "<td class='numeric'>".$data['count']."</td></tr>";
      }

      # Get counts of types
      
      $val = array_flip($items);
      unset($val["Software"]);
      $items = array_flip($val);

      foreach ($items as $itemtype) {
         
         echo "<tr class='tab_bg_1'><td colspan='2' class='b'>".$itemtype::getTypeName(2)."</td></tr>";

         $table_item = getTableForItemType($itemtype);
         $typeclass = $itemtype."Type";
         $type_table = getTableForItemType($typeclass);
         $typefield = getForeignKeyFieldForTable(getTableForItemType($typeclass));
         
         $where = "WHERE `".$table_item."`.`is_deleted` = '0'
                      AND `".$table_item."`.`is_template` = '0' ";
                      
         $join ="";
         if (in_array($itemtype, $linkitems)) {
            $join =  "LEFT JOIN `glpi_computers_items`
                        ON (`glpi_computers_items`.`itemtype` = '".$itemtype."'
                       AND `glpi_computers_items`.`items_id` = `".$table_item."`.`id`)";

         }
         
         $query = "SELECT COUNT(*) AS count, `".$type_table."`.`name` AS name
                FROM `".$table_item."`
                LEFT JOIN `".$type_table."`
                   ON (`".$table_item."`.`".$typefield."`
                        = `".$type_table."`.`id`)
                $join
                $where ".
                    getEntitiesRestrictRequest("AND",$table_item)."
                GROUP BY `".$type_table."`.`name`";
         $result = $DB->query($query);

         while ($data=$DB->fetch_assoc($result)) {
            if (empty($data['name'])) {
               $data['name'] = Dropdown:: EMPTY_VALUE;
            }
            echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
            echo "<td class='numeric'>".$data['count']."</td></tr>";
         }
      }
      echo "</table>";
   }
}

?>
