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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT','..');
$AJAX_INCLUDE=1;
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("create_ticket","1");

$where="";

if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
	$where.= "WHERE ".$_POST['table'].".FK_entities='".$_POST["entity_restrict"]."'";
} else {
	$where.=getEntitiesRestrictRequest("WHERE",$_POST['table']);
}

if (empty($where)){
	$where="WHERE 1 ";
}
if (in_array($_POST['table'],$CFG_GLPI["deleted_tables"]))
	$where.=" AND deleted=0 ";
if (in_array($_POST['table'],$CFG_GLPI["template_tables"]))
	$where.=" AND is_template=0 ";		

if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]){
	$search=makeTextSearch($_POST['searchText']);
	$WWHERE="";
	$FWHERE="";
	if ($_POST['table']!="glpi_software"){
		$WWHERE=" OR contact ".$search." OR serial ".$search." OR otherserial ".$search;
	} 
	 	
	$where.=" AND (name ".$search." OR ID = '".$_POST['searchText']."' $WWHERE)";
}
//If software : filter to display only the softwares that are allowed to be visible in Helpdesk
if ($_POST['table']=="glpi_software"){
	$where.= " AND helpdesk_visible=1 ";
}
$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";

$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY name $LIMIT";

$result = $DB->query($query);

echo "<select name=\"".$_POST['myname']."\" size='1'>";

if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";

echo "<option value=\"0\">-----</option>";
if ($DB->numrows($result)) {
	while ($data = $DB->fetch_array($result)) {

		$output = $data['name'];
		if ($_POST['table']!="glpi_software"){
			if (!empty($data['contact'])){
				$output.=" - ".$data['contact'];
			}
			if (!empty($data['serial'])){
				$output.=" - ".$data['serial'];
			}
			if (!empty($data['otherserial'])){
				$output.=" - ".$data['otherserial'];
			}
		}
		if (empty($output)||$CFG_GLPI["view_ID"]) $output.=" (".$data['ID'].")";
		echo "<option value=\"".$data['ID']."\" title=\"$output\">".substr($output,0,$CFG_GLPI["dropdown_limit"])."</option>";
	}
}
echo "</select>";


?>	
