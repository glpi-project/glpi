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
* @brief List of device for tracking.
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

if ($item->maybeDeleted()) {
   $where .= " AND `is_deleted` = '0' ";
}

if ($item->maybeTemplate()) {
   $where .= " AND `is_template` = '0' ";
}

if ((strlen($_POST['searchText']) > 0)
    && ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"])) {
   $search = Search::makeTextSearch($_POST['searchText']);

   $where .= " AND (`name` ".$search."
                    OR `id` = '".$_POST['searchText']."'";

   if ($_POST['table']!="glpi_softwares" && !$itemtypeisplugin) {
      $where .= " OR `contact` ".$search."
                  OR `serial` ".$search."
                  OR `otherserial` ".$search;
   }
   $where .= ")";
}

//If software or plugins : filter to display only the objects that are allowed to be visible in Helpdesk
if (in_array($_POST['itemtype'],$CFG_GLPI["helpdesk_visible_types"])) {
   $where .= " AND `is_helpdesk_visible` = '1' ";
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0,$NBMAX";

if ($_POST['searchText'] == $CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$query = "SELECT *
          FROM `".$_POST['table']."`
          $where
          ORDER BY `name`
          $LIMIT";
$result = $DB->query($query);

echo "<select id='dropdown_find_num' name='".$_POST['myname']."' size='1'>";

if (isset($_POST['searchText'])
    && ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"])
    && ($DB->numrows($result) == $NBMAX)) {
   echo "<option value='0'>--".__('Limited view')."--</option>";
}

echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

if ($DB->numrows($result)) {
   while ($data = $DB->fetch_assoc($result)) {
      $output = $data['name'];

      if (($_POST['table'] != "glpi_softwares")
          && !$itemtypeisplugin) {
         if (!empty($data['contact'])) {
            $output = sprintf(__('%1$s - %2$s'), $output, $data['contact']);
         }
         if (!empty($data['serial'])) {
            $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
         }
         if (!empty($data['otherserial'])) {
            $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
         }
      }

      if (empty($output)
          || $_SESSION['glpiis_ids_visible']) {
         $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
      }
      echo "<option value='".$data['id']."' title=\"".Html::cleanInputText($output)."\">".
            Toolbox::substr($output, 0, $_SESSION["glpidropdown_chars_limit"])."</option>";
   }
}

echo "</select>";


// Auto update summary of active or just solved tickets
$params = array('items_id' => '__VALUE__',
                'itemtype' => $_POST['itemtype']);

Ajax::updateItemOnSelectEvent("dropdown_find_num","item_ticket_selection_information",
                              $CFG_GLPI["root_doc"]."/ajax/ticketiteminformation.php",
                              $params);
?>