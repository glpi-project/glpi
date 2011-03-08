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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownRules.php")) {
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

$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";

$sql = "SELECT `id`, `name`, `ranking`
        FROM `glpi_rules`
        WHERE `sub_type`='".$_POST["type"]."'";

if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT="";
} else {
   $sql .= " AND `name` ".makeTextSearch($_POST['searchText']);
}
if (isset($_POST['entity_restrict']) && $_POST['entity_restrict']!='') {
   $sql.=" AND `glpi_rules`.`entities_id`='".$_POST['entity_restrict']."'";
}
$sql .= " ORDER BY `ranking` ASC " .
          $LIMIT;
$result = $DB->query($sql);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
   echo "<option value='0'>--".$LANG['common'][11]."--</option>";
} else {
   echo "<option value='0'>".DROPDOWN_EMPTY_VALUE."</option>";
}

if ($DB->numrows($result)) {
   while ($data =$DB->fetch_array($result)) {
      $ID = $data['id'];
      $name = $data['name'];
      echo "<option value='$ID' title=\"".cleanInputText($name)."\">".
             utf8_substr($name,0,$_POST["limit"])."</option>";
   }
}
echo "</select>";

?>