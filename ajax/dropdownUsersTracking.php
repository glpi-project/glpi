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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (strpos($_SERVER['PHP_SELF'],"dropdownUsersTracking.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkCentralAccess();

// Security
if ( ! FieldExists("glpi_tickets",$_POST['field']) ) {
   exit();
}

// Make a select box with all glpi users
$where = "WHERE `glpi_users`.`is_deleted` = '0'
                AND `glpi_users`.`is_active` = '1' ";

if (strlen($_POST['searchText'])>0 && $_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
   $where .= "AND (`glpi_users`.`name` ".makeTextSearch($_POST['searchText'])."
                   OR `glpi_users`.`realname` ".makeTextSearch($_POST['searchText'])."
                   OR `glpi_users`.`firstname` ".makeTextSearch($_POST['searchText']).") ";
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0, $NBMAX";

if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$query = "SELECT `glpi_users`.`id`,
                 `glpi_users`.`name`,
                 `glpi_users`.`realname`,
                 `glpi_users`.`firstname`
          FROM `glpi_users`
          $where
                AND `id` IN (SELECT DISTINCT(`".$_POST['field']."`)
                             FROM `glpi_tickets` ".
                             getEntitiesRestrictRequest("WHERE", "glpi_tickets").") ";

if ($_SESSION["glpinames_format"]==FIRSTNAME_BEFORE) {
   $query .= " ORDER BY `glpi_users`.`firstname`,
                        `glpi_users`.`realname`,
                        `glpi_users`.`name` ";
} else {
   $query .= " ORDER BY `glpi_users`.`realname`,
                        `glpi_users`.`firstname`,
                        `glpi_users`.`name` ";
}

$query .= $LIMIT;

$result = $DB->query($query);

$users = array();

if ($DB->numrows($result)) {
   while ($data=$DB->fetch_array($result)) {
      $users[$data["id"]] = formatUserName($data["id"], $data["name"], $data["realname"],
                                           $data["firstname"]);
      $logins[$data["id"]] = $data["name"];
   }
}

asort($users);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name='".$_POST['myname']."'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
   echo "<option value='0'>--".$LANG['common'][11]."--</option>";
}

echo "<option value='0'>[ ".$LANG['common'][66]." ]</option>";

if (isset($_POST['value'])) {
   $output = getUserName($_POST['value']);

   if (!empty($output) && $output!="&nbsp;") {
      echo "<option selected value='".$_POST['value']."' title='".cleanInputText($output)."'>".
            utf8_substr($output, 0, $_SESSION["glpidropdown_chars_limit"])."</option>";
   }
}

if (count($users)) {
   foreach ($users as $ID => $output) {
      echo "<option value='$ID' ".($ID == $_POST['value']?"selected":"")." title='".
            cleanInputText($output)."'>".utf8_substr($output, 0, $_SESSION["glpidropdown_chars_limit"]).
            "</option>";
   }
}
echo "</select>";

?>