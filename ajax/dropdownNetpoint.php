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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownNetpoint.php")) {
   $AJAX_INCLUDE=1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();

// Make a select box with preselected values
if (!isset($_POST["limit"])) {
   $_POST["limit"]=$_SESSION["glpidropdown_chars_limit"];
}

if (strlen($_POST['searchText'])>0 && $_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
   $where=" WHERE (`glpi_netpoints`.`name` ".makeTextSearch($_POST['searchText'])."
                   OR `glpi_locations`.`completename` ".makeTextSearch($_POST['searchText']).")";
} else {
   $where=" WHERE 1 ";
}

$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT="";
}
$location_restrict=false;
if (!(isset($_POST["devtype"]) && $_POST["devtype"] != 'NetworkEquipment'
    && isset($_POST["locations_id"]) && $_POST["locations_id"]>0)) {
   
   if (isset($_POST["entity_restrict"]) && $_POST["entity_restrict"]>=0) {
      $where.= " AND `glpi_netpoints`.`entities_id`='".$_POST["entity_restrict"]."'";
   } else {
      $where.=getEntitiesRestrictRequest(" AND ","glpi_locations");
   }
}

$query = "SELECT `glpi_netpoints`.`comment` AS comment, `glpi_netpoints`.`id`,
                 `glpi_netpoints`.`name` AS netpname, `glpi_locations`.`completename` AS loc
          FROM `glpi_netpoints`
          LEFT JOIN `glpi_locations` ON (`glpi_netpoints`.`locations_id` = `glpi_locations`.`id`) ";

if (isset($_POST["devtype"]) && $_POST["devtype"]>0) {
   $query .= "LEFT JOIN `glpi_networkports`
                 ON (`glpi_netpoints`.`id` = `glpi_networkports`.`netpoints_id`
                     AND `glpi_networkports`.`itemtype`";

   if ($_POST["devtype"] == 'NetworkEquipment') {
      $query .= " = 'NetworkEquipment' )";
   } else {
      $query .= " != 'NetworkEquipment' )";
      if (isset($_POST["locations_id"]) && $_POST["locations_id"]>=0) {
         $location_restrict=true;
         $where.=" AND `glpi_netpoints`.`locations_id`='".$_POST["locations_id"]."' ";
      }
   }
   $where.=" AND `glpi_networkports`.`netpoints_id` IS NULL ";

} else if (isset($_POST["locations_id"]) && $_POST["locations_id"]>=0) {
   $location_restrict=true;
   $where.=" AND `glpi_netpoints`.`locations_id`='".$_POST["locations_id"]."' ";
}

$query .= $where ."
          ORDER BY `glpi_locations`.`completename`, `glpi_netpoints`.`name`
          $LIMIT";
$result = $DB->query($query);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
   echo "<option value='0'>--".$LANG['common'][11]."--</option>";
} else {
   echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";
}
$output=Dropdown::getDropdownName('glpi_netpoints',$_POST['value']);
if (!empty($output) && $output!="&nbsp;") {
   echo "<option selected value='".$_POST['value']."'>".$output."</option>";
}

if ($DB->numrows($result)) {
   while ($data =$DB->fetch_array($result)) {
      $output = $data['netpname'];
      $loc=$data['loc'];
      $ID = $data['id'];
      $addcomment="";
      if (isset($data["comment"])) {
         $addcomment=" - ".$loc." - ".$data["comment"];
      }
      if (!$location_restrict) {
         $output.=" ($loc)";
      }

      echo "<option value='$ID' title=\"".cleanInputText($output.$addcomment)."\"";
      echo ">".$output."</option>";
   }
}
echo "</select>\n";

if (isset($_POST["comment"]) && $_POST["comment"]) {
   $paramscomment=array('value' => '__VALUE__',
                        'table' => "glpi_netpoints");
   ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],
                               "comment_".$_POST["myname"].$_POST["rand"],
                               $CFG_GLPI["root_doc"]."/ajax/comments.php",$paramscomment,false);
}

?>
