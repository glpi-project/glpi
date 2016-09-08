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
* @brief List of device for tracking.
* @since version 0.85
*/

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Security
if (!TableExists($_POST['table'])) {
   exit();
}

$itemtypeisplugin = isPluginItemType($_POST['itemtype']);

if (!$item = getItemForItemtype($_POST['itemtype'])) {
   exit;
}

if ($item->isEntityAssign()) {
   if (isset($_POST["entity_restrict"]) && ($_POST["entity_restrict"] >= 0)) {
      $entity = $_POST["entity_restrict"];
   } else {
      $entity = '';
   }

   // allow opening ticket on recursive object (printer, software, ...)
   $recursive = $item->maybeRecursive();
   $where     = getEntitiesRestrictRequest("WHERE", $_POST['table'], '', $entity, $recursive);

} else {
   $where = "WHERE 1";
}

if(isset($_POST['used']) && !empty($_POST['used'])){
   $where .= " AND `id` NOT IN ('".implode("','" ,$_POST['used'])."') ";
}

if ($item->maybeDeleted()) {
   $where .= " AND `is_deleted` = '0' ";
}

if ($item->maybeTemplate()) {
   $where .= " AND `is_template` = '0' ";
}

if ((strlen($_POST['searchText']) > 0)) {
   $search = Search::makeTextSearch($_POST['searchText']);

   $where .= " AND (`name` ".$search."
                    OR `id` = '".$_POST['searchText']."'";

   if (FieldExists($_POST['table'],"contact")) {
      $where .= " OR `contact` ".$search;
   }
   if (FieldExists($_POST['table'],"serial")) {
      $where .= " OR `serial` ".$search;
   }
   if (FieldExists($_POST['table'],"otherserial")) {
      $where .= " OR `otherserial` ".$search;
   }
   $where .= ")";
}

//If software or plugins : filter to display only the objects that are allowed to be visible in Helpdesk
if (in_array($_POST['itemtype'],$CFG_GLPI["helpdesk_visible_types"])) {
   $where .= " AND `is_helpdesk_visible` = '1' ";
}

if (!isset($_POST['page'])) {
   $_POST['page']       = 1;
   $_POST['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$start = intval(($_POST['page']-1)*$_POST['page_limit']);
$limit = intval($_POST['page_limit']);
$LIMIT = "LIMIT $start,$limit";

$query = "SELECT *
          FROM `".$_POST['table']."`
          $where
          ORDER BY `name`
          $LIMIT";
$result = $DB->query($query);

$datas = array();

// Display first if no search
if ($_POST['page'] == 1 && empty($_POST['searchText'])) {
   array_push($datas, array('id'   => 0,
                            'text' => Dropdown::EMPTY_VALUE));
}
$count = 0;
if ($DB->numrows($result)) {
   while ($data = $DB->fetch_assoc($result)) {
      $output = $data['name'];

      if (isset($data['contact']) && !empty($data['contact'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data['contact']);
      }
      if (isset($data['serial']) && !empty($data['serial'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
      }
      if (isset($data['otherserial']) && !empty($data['otherserial'])) {
         $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
      }

      if (empty($output)
          || $_SESSION['glpiis_ids_visible']) {
         $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
      }

      array_push($datas, array('id'   => $data['id'],
                               'text' => $output));
      $count++;
   }
}

$ret['count']   = $count;
$ret['results'] = $datas;
echo json_encode($ret);
?>