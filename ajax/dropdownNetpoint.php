<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
if(ereg("dropdownNetpoint.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};

if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}

checkLoginUser();

// Make a select box with preselected values
if (!isset($_POST["limit"])) $_POST["limit"]=$CFG_GLPI["dropdown_limit"];

	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]){
		$where=" WHERE (glpi_dropdown_netpoint.name ".makeTextSearch($_POST['searchText'])." OR glpi_dropdown_locations.completename ".makeTextSearch($_POST['searchText']).")";
	} else {
		$where=" WHERE 1 ";		
	}

	$NBMAX=$CFG_GLPI["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";
	if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
		$where.= " AND glpi_dropdown_netpoint.FK_entities='".$_POST["entity_restrict"]."'";
	} else {
		$where.=getEntitiesRestrictRequest(" AND ","glpi_dropdown_locations");
	}

	$query = "SELECT glpi_dropdown_netpoint.comments as comments, glpi_dropdown_netpoint.ID as ID, glpi_dropdown_netpoint.name as netpname, glpi_dropdown_locations.completename as loc from glpi_dropdown_netpoint";
	$query .= " LEFT JOIN glpi_dropdown_locations ON (glpi_dropdown_netpoint.location = glpi_dropdown_locations.ID)";

	if (isset($_POST["devtype"])&&$_POST["devtype"]>0){
		$query .= " LEFT JOIN glpi_networking_ports ON (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint";
	
		if ($_POST["devtype"]==NETWORKING_TYPE){
			$query .= " AND  glpi_networking_ports.device_type =" . NETWORKING_TYPE .")";
		}
		else {
			$query .= " AND  glpi_networking_ports.device_type !=" . NETWORKING_TYPE .")";

			if (isset($_POST["location"]) && $_POST["location"]>=0){
				$where.=" AND glpi_dropdown_netpoint.location='".$_POST["location"]."' ";
			}
		}
		$where.=" AND glpi_networking_ports.netpoint IS NULL ";

	} else	if (isset($_POST["location"]) && $_POST["location"]>=0){
		$where.=" AND glpi_dropdown_netpoint.location='".$_POST["location"]."' ";
	}

	$query .= $where . " ORDER BY glpi_dropdown_locations.completename, glpi_dropdown_netpoint.name $LIMIT"; 

	//logInFile("debug","SQL:".$query."\n\n");
	$result = $DB->query($query);

	echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

	if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";
	else echo "<option value=\"0\">-----</option>";

	$output=getDropdownName('glpi_dropdown_netpoint',$_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";


	if ($DB->numrows($result)) {
		while ($data =$DB->fetch_array($result)) {
			$output = $data['netpname'];
			$loc=$data['loc'];
			$ID = $data['ID'];
			$addcomment="";
			if (isset($data["comments"])) $addcomment=" - ".$data["comments"];
			echo "<option value=\"$ID\" title=\"$output$addcomment\"";
			//if ($ID==$_POST['value']) echo " selected ";
			echo ">".$output." ($loc)</option>";
		}
	}
	echo "</select>";

if (isset($_POST["comments"])&&$_POST["comments"]){
	$params=array('value'=>'__VALUE__','table'=>"glpi_dropdown_netpoint");
	ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],"comments_".$_POST["myname"].$_POST["rand"],$CFG_GLPI["root_doc"]."/ajax/comments.php",$params,false);
}

?>
