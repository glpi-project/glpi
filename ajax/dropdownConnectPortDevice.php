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


define('GLPI_ROOT','..');
$AJAX_INCLUDE=1;
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("networking","w");


if (isset($LINK_ID_TABLE[$_POST["type"]])&&$_POST["type"]>0){
	$table=$LINK_ID_TABLE[$_POST["type"]];

	$rand=mt_rand();
	if (!isset($_POST['searchText']))$_POST['searchText']="";

	$where="WHERE deleted=0 ";
	$where.=" AND is_template='0' ";		

	if (isset($_POST["entity_restrict"])&&!is_numeric($_POST["entity_restrict"])&&!is_array($_POST["entity_restrict"])){
		$_POST["entity_restrict"]=unserialize(stripslashes($_POST["entity_restrict"]));
	}
	$multi=in_array($table,$CFG_GLPI["recursive_type"]);
	
	if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"]<0)){
		$where.=getEntitiesRestrictRequest(" AND ",$table,'',$_POST["entity_restrict"],$multi);
		if (is_array($_POST["entity_restrict"]) && count($_POST["entity_restrict"])>1) {
			$multi=true;
		}	
	} else {
		$where.=getEntitiesRestrictRequest(" AND ",$table,'','',$multi);
		if (count($_SESSION['glpiactiveentities'])>1) {
			$multi=true;	
		}
	}

	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$_SESSION["glpiajax_wildcard"])
		$where.=" AND name ".makeTextSearch($_POST['searchText'])." ";

	$NBMAX=$_SESSION["glpidropdown_max"];

	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$_SESSION["glpiajax_wildcard"]) $LIMIT="";

	$order = ($multi ? "FK_entities,name" : "name");
	$query = "SELECT * FROM ".$table." $where ORDER BY $order $LIMIT";
	$result = $DB->query($query);

	echo "<select id='item$rand' name=\"item\" size='1'>";

	if ($_POST['searchText']!=$_SESSION["glpiajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";

	echo "<option value=\"0\">-----</option>";
	if ($DB->numrows($result)) {
		$prev = -1;
		while ($data = $DB->fetch_array($result)) {
			if ($multi && $data["FK_entities"]!=$prev) {
				if ($prev>=0) {
					echo "</optgroup>";
				}
				$prev=$data["FK_entities"];
				echo "<optgroup label=\"". getDropdownName("glpi_entities", $prev) ."\">";
			}
			$output = $data['name'];
			$ID = $data['ID'];
			if (empty($output)) $output="($ID)";
			echo "<option value=\"$ID\" title=\"".cleanInputText($output)."\">".substr($output,0,$_SESSION["glpidropdown_limit"])."</option>";
		}
		if ($multi && $prev>=0) {
			echo "</optgroup>";
		}		
	}
	echo "</select>";


        $paramsconnectpd=array('item'=>'__VALUE__',
                        'type'=>$_POST['type'],
                        'current'=>$_POST['current'],
                        'myname'=>$_POST["myname"],
                        );

	ajaxUpdateItemOnSelectEvent("item$rand","results_item_$rand",$CFG_GLPI["root_doc"]."/ajax/dropdownConnectPort.php",$paramsconnectpd);

	echo "<span id='results_item_$rand'>\n";
	echo "</span>\n";	



}		
?>