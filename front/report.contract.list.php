<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

$items = array('Computer', 'Printer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Software',
               'Phone');
# Titre
echo "<big><strong>".$LANG['reports'][4]."</strong></big><br><br>";

# Request All
if ((isset($_POST["item_type"][0]) && $_POST["item_type"][0] == '0')
    || !isset($_POST["item_type"])) {
   $_POST["item_type"] = $items;
}

if (isset($_POST["item_type"]) && is_array($_POST["item_type"])) {
   $query = array();
   foreach ($_POST["item_type"] as $key => $val) {
      if (in_array($val,$items)) {
         $itemtable = getTableForItemType($val);

         $query[$val] = "SELECT `$itemtable`.`name` AS itemname,
                                `$itemtable`.`is_deleted` AS itemdeleted,
                                `glpi_locations`.`completename` AS location,
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
                         WHERE `$itemtable`.`is_template` ='0' ".
                               getEntitiesRestrictRequest("AND",$itemtable);

         if (isset($_POST["annee"][0]) && $_POST["annee"][0] != 'toutes') {
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
         $query[$val] .= " ORDER BY entname ASC, itemdeleted DESC, itemname ASC";
      }
   }
}

$display_entity = isMultiEntitiesMode();

if (isset($query) && count($query)) {
   foreach ($query as $key => $val) {
      $result = $DB->query($val);
      if ($result && $DB->numrows($result)) {
         $item = new $key();
         echo "<strong>".$item->getTypeName()."</strong>";
         echo "<table class='tab_cadre_report'>";
         echo "<tr><th>".$LANG['common'][16]."</th>";
         echo "<th>".$LANG['common'][28]."</th>";
         if ($display_entity) {
            echo "<th>".$LANG['entity'][0]."</th>";
         }
         echo "<th>".$LANG['common'][15]."</th>";
         echo "<th>".$LANG['financial'][14]."</th>";
         echo "<th>".$LANG['financial'][80]."</th>";
         echo "<th>".$LANG['financial'][6]."</th>";
         echo "<th>".$LANG['search'][8]."</th>";
         echo "<th>".$LANG['search'][9]."</th>";
         echo "</tr>";
         while ( $data = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_1'>";
            if ($data['itemname']) {
               echo "<td> ".$data['itemname']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }
            echo "<td> ".Dropdown::getYesNo($data['itemdeleted'])." </td>";

            if ($display_entity) {
               if ($data['entID'] == 0) {
                  echo "<td>".$LANG['entity'][2]."</td>";
               } else {
                  echo "<td>".$data['entname']."</td>";
               }
            }

            if ($data['location']) {
               echo "<td> ".$data['location']." </td>";
            } else {
               echo "<td> ".NOT_AVAILABLE." </td>";
            }

            if ($data['buy_date']) {
               echo "<td> ".convDate($data['buy_date'])." </td>";
               if ($data["warranty_duration"]) {
                  echo "<td> ".getWarrantyExpir($data["buy_date"],$data["warranty_duration"])." </td>";
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
               echo "<td> ".convDate($data['begin_date'])." </td>";
               if ($data["duration"]) {
                  echo "<td> ".getWarrantyExpir($data["begin_date"],$data["duration"])." </td>";
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

commonFooter();

?>