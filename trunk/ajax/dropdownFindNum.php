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
// Purpose of file: List of device for tracking.
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("create_ticket", "1");

// Security
if (!TableExists($_POST['table'])) {
   exit();
}

$itemtypeisplugin = isPluginItemType($_POST['itemtype']);
$item             = new $_POST['itemtype'];

if ($item->isEntityAssign()) {
   if (isset ($_POST["entity_restrict"]) && $_POST["entity_restrict"] >= 0) {
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

if (strlen($_POST['searchText'])>0 && $_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
   $search = makeTextSearch($_POST['searchText']);

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

if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$query = "SELECT *
          FROM `".$_POST['table']."`
          $where
          ORDER BY `name`
          $LIMIT";
$result = $DB->query($query);

echo "<select name='".$_POST['myname']."' size='1'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
   echo "<option value='0'>--".$LANG['common'][11]."--</option>";
}

echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";

if ($DB->numrows($result)) {
   while ($data = $DB->fetch_array($result)) {
      $output = $data['name'];

      if ($_POST['table']!="glpi_softwares" && !$itemtypeisplugin) {
         if (!empty($data['contact'])) {
            $output .= " - ".$data['contact'];
         }
         if (!empty($data['serial'])) {
            $output .= " - ".$data['serial'];
         }
         if (!empty($data['otherserial'])) {
            $output .= " - ".$data['otherserial'];
         }
      }

      if (empty($output) || $_SESSION['glpiis_ids_visible']) {
         $output .= " (".$data['id'].")";
      }
      echo "<option value='".$data['id']."' title=\"".cleanInputText($output)."\">".
            utf8_substr($output, 0, $_SESSION["glpidropdown_chars_limit"])."</option>";
   }
}

echo "</select>";

?>
