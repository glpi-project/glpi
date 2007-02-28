<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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
if(ereg("dropdownValue.php",$_SERVER['PHP_SELF'])){
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
if($_POST['table'] == "glpi_dropdown_netpoint") {

	$where="";
	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"])
		$where=" WHERE (t1.name ".makeTextSearch($_POST['searchText'])." OR t2.completename ".makeTextSearch($_POST['searchText']).")";

	$NBMAX=$CFG_GLPI["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";
	if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
		if (!empty($where)) $where.= " AND glpi_dropdown_netpoint.FK_entities='".$_POST["entity_restrict"]."'";
		else $where.=" WHERE glpi_dropdown_netpoint.FK_entities='".$_POST["entity_restrict"]."'";
	} else {
		$link="";
		if (!empty($where)) $link= " AND ";
		else $link=" WHERE ";
		$where.=getEntitiesRestrictRequest($link,"glpi_dropdown_locations");
	}

	$query = "select glpi_dropdown_netpoint.comments as comments, glpi_dropdown_netpoint.ID as ID, glpi_dropdown_netpoint.name as netpname, glpi_dropdown_locations.completename as loc from glpi_dropdown_netpoint";
	$query .= " left join glpi_dropdown_locations on (glpi_dropdown_netpoint.location = glpi_dropdown_locations.ID)";
	$query.=$where;
	$query .= " order by glpi_dropdown_netpoint.name,glpi_dropdown_locations.completename $LIMIT"; 
//	echo $query;
	$result = $DB->query($query);

	echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

	if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";
	else echo "<option value=\"0\">-----</option>";

	$output=getDropdownName($_POST['table'],$_POST['value']);
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
} else {


	
	$first=true;
	$where="WHERE ";
	
	if (in_array($_POST['table'],$CFG_GLPI["deleted_tables"])){
		if (!$first) $where.=" AND ";
		else $first=false;
		$where.=" deleted='N' ";
	}
	if (in_array($_POST['table'],$CFG_GLPI["template_tables"])){
		if (!$first) $where.=" AND ";
		else $first=false;
		$where.=" is_template='0' ";
	}


	$NBMAX=$CFG_GLPI["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) $LIMIT="";


	if (in_array($_POST['table'],$CFG_GLPI["dropdowntree_tables"])){

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]){
			if (!$first) $where.=" AND ";
			else $first=false;
			$where.=" completename ".makeTextSearch($_POST['searchText']);
		}


		// Manage multiple Entities dropdowns
		$add_order="";
		if (in_array($_POST['table'],$CFG_GLPI["specif_entities_tables"])){
			$add_order=" FK_entities, ";

			if (!$first) $where.=" AND ";
			else $first=false;
		
			if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
				$where.= $_POST['table'].".FK_entities='".$_POST["entity_restrict"]."'";
			} else {
				$where.=getEntitiesRestrictRequest("",$_POST['table']);
			}
		}


		if ($where=="WHERE ") $where="";


		$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY $add_order completename $LIMIT";
		//echo $query;

		$result = $DB->query($query);

		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
			echo "<option class='tree' value=\"0\">--".$LANG["common"][11]."--</option>";

		switch ($_POST["table"]){
			case "glpi_dropdown_kbcategories" :
				echo "<option class='tree' value=\"0\">--".$LANG["knowbase"][12]."--</option>";
				break;
			case "glpi_entities" :
				echo "<option class='tree' value=\"0\">--".$LANG["entity"][2]."--</option>";
				break;
			default :
				echo "<option class='tree' value=\"0\">-----</option>";
				break;
		}

		$outputval=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($outputval)&&$outputval!="&nbsp;")
			echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";

		if ($DB->numrows($result)) {
			while ($data =$DB->fetch_array($result)) {

				$ID = $data['ID'];
				$level = $data['level'];

				if (empty($data['name'])) $output="($ID)";
				else $output=$data['name'];

				$class=" class='tree' ";
				$raquo="&raquo;";
				if ($level==1){
					$class=" class='treeroot' ";
					$raquo="";
				}
				$style=$class;
				$addcomment="";
				if (isset($data["comments"])) $addcomment=" - ".$data["comments"];

				echo "<option value=\"$ID\" $style title=\"".$data['completename']."$addcomment\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.substr($output,0,$_POST["limit"])."</option>";
			}

		}
		echo "</select>";

	} else {
		if (!$first) $where.=" AND ";
		else $first=false;
		$where .=" ID <> '".$_POST['value']."' ";

		if (in_array($_POST['table'],$CFG_GLPI["specif_entities_tables"])){
			if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
				$where.= " AND ".$_POST['table'].".FK_entities='".$_POST["entity_restrict"]."'";
			} else {
				$where.=getEntitiesRestrictRequest("AND",$_POST['table']);
			}
		}

		$field="name";
		if (ereg("glpi_device",$_POST['table'])) $field="designation";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"])
			$where.=" AND $field ".makeTextSearch($_POST['searchText']);


		switch ($_POST['table']){
			case "glpi_software":
				$query = "SELECT CONCAT(name,' (v. ',version,')') as $field, ".$_POST['table'].".comments, ".$_POST['table'].".ID FROM ".$_POST['table']." $where ORDER BY $field $LIMIT";
			break;
			case "glpi_contacts":
				$query = "SELECT CONCAT(name,' ',firstname) as $field, ".$_POST['table'].".comments, ".$_POST['table'].".ID FROM ".$_POST['table']." $where ORDER BY $field $LIMIT";
			break;
			default :
				$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY $field $LIMIT";
			break;
		}
		//echo $query;
		$result = $DB->query($query);

		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";

		echo "<option value=\"0\">-----</option>";

		$output=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($output)&&$output!="&nbsp;")
			echo "<option selected value='".$_POST['value']."'>".$output."</option>";

		if ($DB->numrows($result)) {
			while ($data =$DB->fetch_array($result)) {
				$output = $data[$field];
				$ID = $data['ID'];
				$addcomment="";
				if (isset($data["comments"])) $addcomment=" - ".$data["comments"];

				if (empty($output)) $output="($ID)";
				echo "<option value=\"$ID\" title=\"$output$addcomment\">".substr($output,0,$_POST["limit"])."</option>";
			}
		}
		echo "</select>";
	}
}

if (isset($_POST["comments"])&&$_POST["comments"]){
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('dropdown_".$_POST["myname"].$_POST["rand"]."', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('comments_".$_POST["myname"].$_POST["rand"]."','".$CFG_GLPI["root_doc"]."/ajax/comments.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'value='+value+'&table=".$_POST["table"]."'\n";
	echo "})})\n";
	echo "</script>\n";
}

?>
