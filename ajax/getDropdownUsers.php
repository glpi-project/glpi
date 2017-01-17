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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"getDropdownUsers.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
} else if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

Session::checkLoginUser();

if (!isset($_POST['right'])) {
   $_POST['right'] = "all";
}

// Default view : Nobody
if (!isset($_POST['all'])) {
   $_POST['all'] = 0;
}

$used = array();

if (isset($_POST['used'])) {
   $used = $_POST['used'];
}

if (!isset($_POST['value'])) {
   $_POST['value'] = 0;
}

$one_item = -1;
if (isset($_POST['_one_id'])) {
   $one_item = $_POST['_one_id'];
}

if (!isset($_POST['page'])) {
   $_POST['page']       = 1;
   $_POST['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$entity_restrict = -1;
if (isset($_POST['entity_restrict'])) {
   $entity_restrict = Toolbox::jsonDecode($_POST['entity_restrict']);
}

if ($one_item < 0) {
   $start  = intval(($_POST['page']-1)*$_POST['page_limit']);
   $searchText = (isset($_POST['searchText']) ? $_POST['searchText'] : null);
   $result = User::getSqlSearchResult(false, $_POST['right'], $entity_restrict,
                                      $_POST['value'], $used, $searchText, $start,
                                      intval($_POST['page_limit']));
} else {
   $query = "SELECT DISTINCT `glpi_users`.*
             FROM `glpi_users`
             WHERE `glpi_users`.`id` = '$one_item';";
   $result = $DB->query($query);
}
$users = array();

// Count real items returned
$count = 0;
if ($DB->numrows($result)) {
   while ($data = $DB->fetch_assoc($result)) {
      $users[$data["id"]] = formatUserName($data["id"], $data["name"], $data["realname"],
                                           $data["firstname"]);
      $logins[$data["id"]] = $data["name"];
   }
}

/*
if (!function_exists('dpuser_cmp')) {
   function dpuser_cmp($a, $b) {
      return strcasecmp($a, $b);
   }
}

// Sort non case sensitive
uasort($users, 'dpuser_cmp');
*/

$datas = array();

// Display first if empty search
if ($_POST['page'] == 1 && empty($_POST['searchText'])) {
   if (($one_item < 0) || ($one_item == 0)) {
      if ($_POST['all'] == 0) {
         array_push($datas, array('id'   => 0,
                                  'text' => Dropdown::EMPTY_VALUE));
      } else if ($_POST['all'] == 1) {
         array_push($datas, array('id'   => 0,
                                  'text' => __('All')));
      }
   }
}

if (count($users)) {
   foreach ($users as $ID => $output) {
      $title = sprintf(__('%1$s - %2$s'), $output, $logins[$ID]);

      array_push($datas, array('id'    => $ID,
                               'text'  => $output,
                               'title' => $title));
      $count++;
   }
}


if (($one_item >= 0)
    && isset($datas[0])) {
   echo json_encode($datas[0]);
} else {
   $ret['results'] = $datas;
   $ret['count']   = $count;
   echo json_encode($ret);
}
?>
