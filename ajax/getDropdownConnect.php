<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

if (!isset($_POST['fromtype']) || !($fromitem = getItemForItemtype($_POST['fromtype']))) {
   exit();
}

$fromitem->checkGlobal(UPDATE);
$used = array();
if (isset( $_POST["used"])) {
   $used = $_POST["used"];

   if (isset($used[$_POST['itemtype']])) {
      $used = $used[$_POST['itemtype']];
   } else {
      $used = array();
   }
}

// Make a select box
$table = getTableForItemType($_POST["itemtype"]);
if (!$item = getItemForItemtype($_POST['itemtype'])) {
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

if (isset($_POST['searchText']) && (strlen($_POST['searchText']) > 0)) {
   $where .= " AND (`$table`.`name` ".Search::makeTextSearch($_POST['searchText'])."
                    OR `$table`.`otherserial` ".Search::makeTextSearch($_POST['searchText'])."
                    OR `$table`.`serial` ".Search::makeTextSearch($_POST['searchText'])." )";
}

$multi = $item->maybeRecursive();

if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"] < 0)) {
   $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_POST["entity_restrict"], $multi);
   if (is_array($_POST["entity_restrict"]) && (count($_POST["entity_restrict"]) > 1)) {
      $multi = true;
   }

} else {
   $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_SESSION['glpiactiveentities'],
                                        $multi);
   if (count($_SESSION['glpiactiveentities']) > 1) {
      $multi = true;
   }
}

if (!isset($_POST['page'])) {
   $_POST['page']       = 1;
   $_POST['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$start = intval(($_POST['page']-1)*$_POST['page_limit']);
$limit = intval($_POST['page_limit']);
$LIMIT = "LIMIT $start,$limit";


$where_used = '';
if (!empty($used)) {
   $where_used = " AND `$table`.`id` NOT IN ('".implode("','",$used)."')";
}

if ($_POST["onlyglobal"]
    && ($_POST["itemtype"] != 'Computer')) {
   $CONNECT_SEARCH = " WHERE `$table`.`is_global` = '1' ";
} else {
   if ($_POST["itemtype"] == 'Computer') {
      $CONNECT_SEARCH = " WHERE 1
                                $where_used";
   } else {
      $CONNECT_SEARCH = " WHERE ((`glpi_computers_items`.`id` IS NULL
                                  $where_used)
                                 OR `$table`.`is_global` = '1') ";
   }
}

$LEFTJOINCONNECT = "";

if (($_POST["itemtype"] != 'Computer')
     && !$_POST["onlyglobal"]) {
   $LEFTJOINCONNECT = " LEFT JOIN `glpi_computers_items`
                           ON (`$table`.`id` = `glpi_computers_items`.`items_id`
                               AND `glpi_computers_items`.`itemtype` = '".$_POST['itemtype']."')";
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
if (empty($_POST['searchText'])) {
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
