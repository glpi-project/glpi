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
*/

// Include plugin if it is a plugin table
if (!strstr($_GET['itemtype'],"Plugin")) {
   $AJAX_INCLUDE = 1;
}
include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// Security
if (!isset($_GET['itemtype']) || !($item = getItemForItemtype($_GET['itemtype']))) {
   exit();
}

$item->getEmpty();
$table = $item->getTable();
// Security
if (!isset($item->fields[$_GET['field']]) || !$item->canView()) {
   exit();
}

// Security : blacklist fields
if (in_array($table.'.'.$_GET['field'],
             array('glpi_authldaps.rootdn', 'glpi_authldaps.rootdn_passwd',
                   'glpi_configs.value', 'glpi_mailcollectors.login',
                   'glpi_mailcollectors.passwd', 'glpi_users.name', 'glpi_users.password'))) {
   exit();
}


$entity = "";
if (isset($_GET['entity_restrict']) && $_GET['entity_restrict']>=0) {
   if ($item->isEntityAssign()) {
      $entity = " AND `entities_id` = '".$_GET['entity_restrict']."' ";
   }
}

if (isset($_GET['user_restrict']) && $_GET['user_restrict']>0) {
   $entity = " AND `users_id` = '".$_GET['user_restrict']."' ";
}

$query = "SELECT COUNT(`".$_GET['field']."`)
          FROM `$table`
          WHERE `".$_GET['field']."` LIKE '".$_GET['term']."%'
                AND `".$_GET['field']."` <> '".$_GET['term']."'
                $entity ";
$result = $DB->query($query);
$totnum = $DB->result($result,0,0);

$query = "SELECT DISTINCT `".$_GET['field']."` AS VAL
          FROM `$table`
          WHERE `".$_GET['field']."` LIKE '".$_GET['term']."%'
                AND `".$_GET['field']."` <> '".$_GET['term']."'
                $entity
          ORDER BY `".$_GET['field']."`";

$values = array();
if ($result=$DB->query($query)) {


   if ($DB->numrows($result)>0) {
      $first = true;
      while ($data=$DB->fetch_assoc($result)) {
         $values[]=$data['VAL'];
      }
   }
}
if (count($values)) {
   echo json_encode($values);
}
?>
