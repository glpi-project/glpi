<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


if(strpos($_SERVER['PHP_SELF'],"dropdownConnect.php")){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};

if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}

checkTypeRight($_POST["fromtype"],"w");


if (isset($_POST["used"])&&!is_numeric($_POST["used"])&&!is_array($_POST["used"]))
	$used = unserialize(stripslashes($_POST["used"]));
else
	$used = $_POST["used"];

if (isset($_POST["entity_restrict"])&&!is_numeric($_POST["entity_restrict"])&&!is_array($_POST["entity_restrict"])){
	$_POST["entity_restrict"]=unserialize(stripslashes($_POST["entity_restrict"]));
}
// Make a select box

$table=$LINK_ID_TABLE[$_POST["idtable"]];

$where="";		
if (in_array($table,$CFG_GLPI["deleted_tables"]))
$where.=" AND $table.deleted=0 ";
if (in_array($table,$CFG_GLPI["template_tables"]))
$where.=" AND $table.is_template='0' ";		

if (!empty($used))
	$where.=" AND $table.ID NOT IN (".implode(',',$used).")";

if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"])
$where.=" AND ( $table.name ".makeTextSearch($_POST['searchText'])." OR $table.serial ".makeTextSearch($_POST['searchText'])." )";


$multi=in_array($table,$CFG_GLPI["recursive_type"]);
if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"]<0)){
	$where.=getEntitiesRestrictRequest(" AND ",$table,'',$_POST["entity_restrict"],$multi);
	if (is_array($_POST["entity_restrict"]) && count($_POST["entity_restrict"])>1) {
		$multi=true;	
	}
} else {
	$where.=getEntitiesRestrictRequest(" AND ",$table,'',$_SESSION['glpiactiveentities'],$multi);
	if (count($_SESSION['glpiactiveentities'])>1) {
		$multi=true;	
	}
}

$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";

if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";


if ($_POST["onlyglobal"]&&$_POST["idtable"]!=COMPUTER_TYPE){
	$CONNECT_SEARCH=" WHERE ( $table.is_global='1' ) ";
} else {
	if ($_POST["idtable"]==COMPUTER_TYPE)
		$CONNECT_SEARCH=" WHERE 1 ";
	else {
		$CONNECT_SEARCH=" WHERE (glpi_connect_wire.ID IS NULL OR $table.is_global='1' )";	
	}
}	

$LEFTJOINCONNECT="";
if ($_POST["idtable"]!=COMPUTER_TYPE&&!$_POST["onlyglobal"]){
	$LEFTJOINCONNECT="LEFT JOIN glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = '".$_POST['idtable']."')";
}
$query = "SELECT DISTINCT $table.ID AS ID,$table.name AS name,$table.serial AS serial,$table.otherserial AS otherserial, $table.FK_entities as FK_entities FROM $table $LEFTJOINCONNECT $CONNECT_SEARCH $where ORDER BY FK_entities, name ASC";
echo $query;

$result = $DB->query($query);
echo "<select name=\"".$_POST['myname']."\" size='1'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";

echo "<option value=\"0\">-----</option>";
if ($DB->numrows($result)) {
	$prev=-1;
	while ($data = $DB->fetch_array($result)) {
		if ($multi && $data["FK_entities"]!=$prev) {
			if ($prev>=0) {
				echo "</optgroup>";
			}
			$prev=$data["FK_entities"];
			echo "<optgroup label=\"". getDropdownName("glpi_entities", $prev) ."\">";
		}
		
		$output = $data['name'];
		if (!empty($data['serial'])) $output.=" - ".$data["serial"];
		if (!empty($data['otherserial'])) $output.=" - ".$data["otherserial"];
		$ID = $data['ID'];
		if (empty($output)) $output="($ID)";
	
		echo "<option value=\"$ID\" title=\"".cleanInputText($output)."\">".substr($output,0,$_SESSION["glpidropdown_limit"])."</option>";
	}

	if ($multi && $prev>=0) {
		echo "</optgroup>";
	}
		
}
echo "</select>";


?>