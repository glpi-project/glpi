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

if (strpos($_SERVER['PHP_SELF'],"getDropdownConnect.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

if (!isset($_GET['fromtype']) || !($fromitem = getItemForItemtype($_GET['fromtype']))) {
   exit();
}

$fromitem->checkGlobal(UPDATE);
$used = array();
if (isset( $_GET["used"])) {
   $used = $_GET["used"];

   if (isset($used[$_GET['itemtype']])) {
      $used = $used[$_GET['itemtype']];
   } else {
      $used = array();
   }
}

// Make a select box
$table = getTableForItemType($_GET["itemtype"]);
if (!$item = getItemForItemtype($_GET['itemtype'])) {
   exit;
}

$datas = array();

$where = "";

if ($item->maybeDeleted()) {
   $where .= " AND `$table`.`is_deleted` = '0' ";
}
if ($item->maybeTemplate()) {
   $where .= " AND `$table`.`is_template` = '0' ";
}

if (isset($_GET['searchText']) && (strlen($_GET['searchText']) > 0)) {
   $where .= " AND (`$table`.`name` ".Search::makeTextSearch($_GET['searchText'])."
                    OR `$table`.`otherserial` ".Search::makeTextSearch($_GET['searchText'])."
                    OR `$table`.`serial` ".Search::makeTextSearch($_GET['searchText'])." )";
}

$multi = $item->maybeRecursive();

if (isset($_GET["entity_restrict"]) && !($_GET["entity_restrict"] < 0)) {
   $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_GET["entity_restrict"], $multi);
   if (is_array($_GET["entity_restrict"]) && (count($_GET["entity_restrict"]) > 1)) {
      $multi = true;
   }

} else {
   $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_SESSION['glpiactiveentities'],
                                        $multi);
   if (count($_SESSION['glpiactiveentities']) > 1) {
      $multi = true;
   }
}

if (!isset($_GET['page'])) {
   $_GET['page']       = 1;
   $_GET['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$start = ($_GET['page']-1)*$_GET['page_limit'];
$limit = $_GET['page_limit'];
$LIMIT = "LIMIT $start,$limit";


$where_used = '';
if (!empty($used)) {
   $where_used = " AND `$table`.`id` NOT IN ('".implode("','",$used)."')";
}

if ($_GET["onlyglobal"]
    && ($_GET["itemtype"] != 'Computer')) {
   $CONNECT_SEARCH = " WHERE `$table`.`is_global` = '1' ";
} else {
   if ($_GET["itemtype"] == 'Computer') {
      $CONNECT_SEARCH = " WHERE 1
                                $where_used";
   } else {
      $CONNECT_SEARCH = " WHERE ((`glpi_computers_items`.`id` IS NULL
                                  $where_used)
                                 OR `$table`.`is_global` = '1') ";
   }
}

$LEFTJOINCONNECT = "";

if (($_GET["itemtype"] != 'Computer')
     && !$_GET["onlyglobal"]) {
   $LEFTJOINCONNECT = " LEFT JOIN `glpi_computers_items`
                           ON (`$table`.`id` = `glpi_computers_items`.`items_id`
                               AND `glpi_computers_items`.`itemtype` = '".$_GET['itemtype']."')";
}

$query = "SELECT DISTINCT `$table`.`id`,
                          `$table`.`name` AS name,
                          `$table`.`serial` AS serial,
                          `$table`.`otherserial` AS otherserial,
                          `$table`.`entities_id` AS entities_id
          FROM `$table`
          $LEFTJOINCONNECT
          $CONNECT_SEARCH
                $where
          ORDER BY entities_id,
                   name ASC
          $LIMIT";

$result = $DB->query($query);

// Display first if no search
if (empty($_GET['searchText'])) {
   array_push($datas, array('id'   => 0,
                            'text' => Dropdown::EMPTY_VALUE));
}
if ($DB->numrows($result)) {
   $prev       = -1;
   $datastoadd = array();

   while ($data = $DB->fetch_assoc($result)) {
      if ($multi && ($data["entities_id"] != $prev)) {
         if (count($datastoadd)) {
            array_push($datas, array('text'    => Dropdown::getDropdownName("glpi_entities", $prev),
                                     'children' => $datastoadd));
         }
         $prev = $data["entities_id"];
         // Reset last level displayed :
         $datastoadd = array();
      }
      $output = $data['name'];
      $ID     = $data['id'];

      if ($_SESSION["glpiis_ids_visible"]
          || empty($output)) {
         $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
      }
      if (!empty($data['serial'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data["serial"]);
      }
      if (!empty($data['otherserial'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data["otherserial"]);
      }
      array_push($datastoadd, array('id'    => $ID,
                                    'text'  => $output));
   }

   if ($multi) {
      if (count($datastoadd)) {
         array_push($datas, array('text'     => Dropdown::getDropdownName("glpi_entities", $prev),
                                  'children' => $datastoadd));
      }
   } else {
      if (count($datastoadd)) {
         $datas = array_merge($datas, $datastoadd);
      }
   }
}

$ret['results'] = $datas;

echo json_encode($ret);
?>
