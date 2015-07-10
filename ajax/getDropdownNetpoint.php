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
if (strpos($_SERVER['PHP_SELF'],"getDropdownNetpoint.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

// Make a select box with preselected values
$datas             = array();
$location_restrict = false;


if (!isset($_GET['page'])) {
   $_GET['page']       = 1;
   $_GET['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$start = ($_GET['page']-1)*$_GET['page_limit'];
$limit = $_GET['page_limit'];

$LIMIT = "LIMIT $start,$limit";

$one_item = -1;
if (isset($_GET['_one_id'])) {
   $one_item = $_GET['_one_id'];
}

if ($one_item >= 0) {
   $where .= " AND `glpi_netpoints`.`id` = '$one_item'";
} else {
   if (strlen($_GET['searchText']) > 0) {
      $where = " WHERE (`glpi_netpoints`.`name` ".Search::makeTextSearch($_GET['searchText'])."
                        OR `glpi_locations`.`completename` ".Search::makeTextSearch($_GET['searchText']).")";
   } else {
      $where = " WHERE 1 ";
   }
}

if (!(isset($_GET["devtype"])
      && ($_GET["devtype"] != 'NetworkEquipment')
      && isset($_GET["locations_id"])
      && ($_GET["locations_id"] > 0))) {

   if (isset($_GET["entity_restrict"]) && ($_GET["entity_restrict"] >= 0)) {
      $where .= " AND `glpi_netpoints`.`entities_id` = '".$_GET["entity_restrict"]."'";
   } else {
      $where .= getEntitiesRestrictRequest(" AND ", "glpi_locations");
   }
}

$query = "SELECT `glpi_netpoints`.`comment` AS comment,
                 `glpi_netpoints`.`id`,
                 `glpi_netpoints`.`name` AS netpname,
                 `glpi_locations`.`completename` AS loc
          FROM `glpi_netpoints`
          LEFT JOIN `glpi_locations` ON (`glpi_netpoints`.`locations_id` = `glpi_locations`.`id`) ";

if (isset($_GET["devtype"]) && !empty($_GET["devtype"])) {
   $query .= "LEFT JOIN `glpi_networkportethernets`
                  ON (`glpi_netpoints`.`id` = `glpi_networkportethernets`.`netpoints_id`)
              LEFT JOIN `glpi_networkports`
                  ON (`glpi_networkports`.`id` = `glpi_networkportethernets`.`id`
                      AND `glpi_networkports`.`instantiation_type` = 'NetworkPortEthernet'
                      AND `glpi_networkports`.`itemtype`";

   if ($_GET["devtype"] == 'NetworkEquipment') {
      $query .= " = 'NetworkEquipment' )";
   } else {
      $query .= " != 'NetworkEquipment' )";
      if (isset($_GET["locations_id"]) && ($_GET["locations_id"] >= 0)) {
         $location_restrict = true;
         $where .= " AND `glpi_netpoints`.`locations_id` = '".$_GET["locations_id"]."' ";
      }
   }
   $where .= " AND `glpi_networkportethernets`.`netpoints_id` IS NULL ";

} else if (isset($_GET["locations_id"]) && ($_GET["locations_id"] >= 0)) {
   $location_restrict = true;
   $where .= " AND `glpi_netpoints`.`locations_id` = '".$_GET["locations_id"]."' ";
}

$query .= $where ."
          ORDER BY `glpi_locations`.`completename`,
                   `glpi_netpoints`.`name`
          $LIMIT";

$result = $DB->query($query);

// Display first if no search
if (empty($_GET['searchText']) && ($one_item < 0) || ($one_item == 0)) {
   if ($_GET['page'] == 1) {
      array_push($datas, array('id'   => 0,
                              'text' => Dropdown::EMPTY_VALUE));
   }
}

$count = 0;
if ($DB->numrows($result)) {
   while ($data = $DB->fetch_assoc($result)) {
      $output     = $data['netpname'];
      $loc        = $data['loc'];
      $ID         = $data['id'];
      $title      = $output;
      if (isset($data["comment"])) {
         //TRANS: %1$s is the location, %2$s is the comment
         $title = sprintf(__('%1$s - %2$s'), $title, $loc);
         $title = sprintf(__('%1$s - %2$s'), $title, $data["comment"]);
      }
      if (!$location_restrict) {
         $output = sprintf(__('%1$s (%2$s)'), $output, $loc);
      }

      array_push($datas, array('id'    => $ID,
                               'text'  => $output,
                               'title' => $title));
      $count++;
   }
}


if (($one_item >= 0) && isset($datas[0])) {
   echo json_encode($datas[0]);
} else {
   $ret['count']   = $count;
   $ret['results'] = $datas;
   echo json_encode($ret);
}
?>
