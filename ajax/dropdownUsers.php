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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownUsers.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
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
   if (is_array($_POST['used'])) {
      $used = $_POST['used'];
   } else {
      $used = Toolbox::decodeArrayFromInput($_POST['used']);
   }
}
if (isset($_POST["entity_restrict"])
    && !is_numeric($_POST["entity_restrict"])
    && !is_array($_POST["entity_restrict"])) {

   $_POST["entity_restrict"] = Toolbox::decodeArrayFromInput($_POST["entity_restrict"]);
}

// for multi rights
if (isset($_POST["right"])
    && !is_array($_POST["right"])) {
   $temp = Toolbox::decodeArrayFromInput($_POST["right"]);
   if (count($temp)) {
      $_POST['right'] = $temp;
   }
}

$result = User::getSqlSearchResult(false, $_POST['right'], $_POST["entity_restrict"],
                                   $_POST['value'], $used, $_POST['searchText']);

$users = array();

if ($DB->numrows($result)) {
   while ($data=$DB->fetch_assoc($result)) {
      $users[$data["id"]] = formatUserName($data["id"], $data["name"], $data["realname"],
                                           $data["firstname"]);
      $logins[$data["id"]] = $data["name"];
   }
}

if (!function_exists('dpuser_cmp')) {
   function dpuser_cmp($a, $b) {
      return strcasecmp($a, $b);
   }
}

// Sort non case sensitive
uasort($users, 'dpuser_cmp');

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name='".$_POST['myname']."'";

if (isset($_POST["on_change"]) && !empty($_POST["on_change"])) {
   echo " onChange='".$_POST["on_change"]."'";
}

echo ">";

if (isset($_POST['searchText'])
    && ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"])
    && ($DB->numrows($result) == $CFG_GLPI["dropdown_max"])) {

   echo "<option value='0'>--".__('Limited view')."--</option>";
}

if ($_POST['all']==0) {
   echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";
} else if ($_POST['all']==1) {
   echo "<option value='0'>[".__('All')."]</option>";
}

if (isset($_POST['value'])) {
   $default_value = getUserName($_POST['value']);

   if (!empty($default_value) && ($default_value != "&nbsp;")) {
      echo "<option selected value='".$_POST['value']."'>".$default_value."</option>";
   }
}

if (count($users)) {
   foreach ($users as $ID => $output) {
      $title = sprintf(__('%1$s - %2$s'), $output, $logins[$ID]);
      echo "<option value='$ID' title=\"".Html::cleanInputText($title)."\">".
             Toolbox::substr($output, 0, $_SESSION["glpidropdown_chars_limit"])."</option>";
   }
}
echo "</select>";

if (isset($_POST["comment"]) && $_POST["comment"]) {
   $paramscomment = array('value' => '__VALUE__',
                          'table' => "glpi_users");

   if (isset($_POST['update_link'])) {
      $paramscomment['withlink'] = "comment_link_".$_POST["myname"].$_POST["rand"];
   }
   Ajax::updateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],
                                 "comment_".$_POST["myname"].$_POST["rand"],
                                 $CFG_GLPI["root_doc"]."/ajax/comments.php", $paramscomment);
}

Ajax::commonDropdownUpdateItem($_POST);
?>