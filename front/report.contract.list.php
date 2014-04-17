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

Session::checkRight("reports", "r");

Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

Report::title();

$items = $CFG_GLPI["contract_types"];

# Titre
echo "<div class='center'>";
echo "<span class='big b'>".__('List of the hardware under contract')."</span><br><br>";
echo "</div>";

# Request All
if ((isset($_POST["item_type"][0]) && ($_POST["item_type"][0] == '0'))
    || !isset($_POST["item_type"])) {
   $_POST["item_type"] = $items;
}

if (isset($_POST["item_type"]) && is_array($_POST["item_type"])) {
   $query = array();
   foreach ($_POST["item_type"] as $key => $val) {
      if (in_array($val,$items)) {
         $itemtable = getTableForItemType($val);

         $select = '';
         $where  = 1;
         $order  = '';
         if ($val != 'SoftwareLicense') {
            $select = "`$itemtable`.`is_deleted` AS itemdeleted,
                        `glpi_locations`.`completename` AS location,";
            $where  = "WHERE `$itemtable`.`is_template` ='0'";
            $order  = " itemdeleted DESC, ";
         }
         $query[$val] = "SELECT `$itemtable`.`name` AS itemname,
                                $select
                                `glpi_contracttypes`.`name` AS type,
                                `glpi_infocoms`.`buy_date`,
                                `glpi_infocoms`.`warranty_duration`,
                                `glpi_contracts`.`begin_date`,
                                `glpi_contracts`.`duration`,
                                `glpi_entities`.`completename` AS entname,
                                `glpi_entities`.`id` AS entID
                         FROM `glpi_contracts_items`
                         INNER JOIN `glpi_contracts`
                              ON (`glpi_contracts_items`.`contracts_id` = `glpi_contracts`.`id`)
                         INNER JOIN `$itemtable`
                              ON (`glpi_contracts_items`.`itemtype` = '$val'
                                  AND `$itemtable`.`id` = `glpi_contracts_items`.`items_id`)
                         LEFT JOIN `glpi_infocoms`
                              ON (`glpi_infocoms`.`itemtype` = '$val'
                                  AND `$itemtable`.`id` = `glpi_infocoms`.`items_id`)
                         LEFT JOIN `glpi_contracttypes`
                              ON (`glpi_contracts`.`contracttypes_id` = `glpi_contracttypes`.`id`)
                         LEFT JOIN `glpi_locations`
                              ON (`$itemtable`.`locations_id` = `glpi_locations`.`id`)
                         LEFT JOIN `glpi_entities`
                              ON (`$itemtable`.`entities_id` = `glpi_entities`.`id`)
                         $where ".
                               getEntitiesRestrictRequest("AND",$itemtable);

         if (isset($_POST["annee"][0]) && ($_POST["annee"][0] != 'toutes')) {
            $query[$val] .= " AND ( ";
            $first = true;
            foreach ($_POST["annee"] as $key2 => $val2) {
               if (!$first) {
                  $query[$val] .= " OR ";
               } else {
                  $first = false;
               }
               $query[$val] .= " YEAR(`glpi_infocoms`.`buy_date`) = '$val2'
                                OR YEAR(`glpi_contracts`.`begin_date`) = '$val2'";
            }
            $query[$val] .= ")";
         }
         $query[$val] .= " ORDER BY entname ASC, $order itemname ASC";
      }
   }
}

$display_entity = Session::isMultiEntitiesMode();

if (isset($query) && count($query)) {
   foreach ($query as $key => $val) {
      $result = $DB->query($val);
      if ($result && $DB->numrows($result)) {
         $item = new $key();
         echo "<div class='center'><span class='b'>".$item->getTypeName(1)."</span></div>";
         echo "<table class='tab_cadre_report'>";
         echo "<tr><th>".__('Name')."</th>";
         echo "<th>".__('Deleted')."</th>";
         if ($display_entity) {
            echo "<th>".__('Entity')."</th>";
         }
         echo "<th>".__('Location')."</th>";
         echo "<th>".__('Date of purchase')."</th>";
         echo "<th>".__('Warranty expiration date')."</th>";
         echo "<th>".__('Contract type')."</th>";
         echo "<th>".__('Start date')."</th>";
         echo "<th>".__('End date')."</th>";
         echo "</tr>";
         while ( $data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'>";
            if ($data['itemname']) {
               echo "<td> ".$data['itemname']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }
            echo "<td> ".Dropdown::getYesNo($data['itemdeleted'])." </td>";

            if ($display_entity) {
               echo "<td>".$data['entname']."</td>";
            }
            if ($key != 'SoftwareLicense') {
               $data['itemdeleted'] = 0;
               $data['location']    = '';
            }
            if ($data['location']) {
               echo "<td> ".$data['location']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['buy_date']) {
               echo "<td> ".Html::convDate($data['buy_date'])." </td>";
               if ($data["warranty_duration"]) {
                  echo "<td> ".Infocom::getWarrantyExpir($data["buy_date"],
                                                         $data["warranty_duration"])." </td>";
               } else {
                  echo "<td> ".NOT_AVAILABLE." </td>";
               }
            } else {
               echo "<td> ".NOT_AVAILABLE." </td><td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['type']) {
               echo "<td class='b'> ".$data['type']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['begin_date']) {
               echo "<td> ".Html::convDate($data['begin_date'])." </td>";
               if ($data["duration"]) {
                  echo "<td> ".Infocom::getWarrantyExpir($data["begin_date"],
                                                         $data["duration"])." </td>";
               } else {
                  echo "<td> ".NOT_AVAILABLE." </td>";
               }
            } else {
               echo "<td> ".NOT_AVAILABLE." </td><td> ".NOT_AVAILABLE." </td>";
            }
            echo "</tr>\n";
         }
         echo "</table><br><hr><br>";
      }
   }
}

Html::footer();
?>