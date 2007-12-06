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

if(ereg("dropdownValue.php",$_SERVER['PHP_SELF'])){
	define('GLPI_ROOT','..');
	// Include plugin if it is a plugin table
	if (!ereg("plugin",$_POST['table'])){
		$AJAX_INCLUDE=1;
	}
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};

if (!defined('GLPI_ROOT')){
	die("Can not acces directly to this file");
	}

checkLoginUser();


if (isset($_POST["entity_restrict"])&&!is_numeric($_POST["entity_restrict"])&&!is_array($_POST["entity_restrict"])){
	$_POST["entity_restrict"]=unserialize($_POST["entity_restrict"]);
}

// Make a select box with preselected values
if (!isset($_POST["limit"])) $_POST["limit"]=$CFG_GLPI["dropdown_limit"];
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
		if (in_array($_POST['table'],$CFG_GLPI["specif_entities_tables"])||$_POST['table']=='glpi_entities'){
			$field='FK_entities';
			$add_order=" FK_entities, ";
			if ($_POST['table']=='glpi_entities'){
				$field='ID';
				$add_order=" ";

			}
			

			if (!$first) $where.=" AND ";
			else $first=false;

			if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
				$where.=getEntitiesRestrictRequest("",$_POST['table'],$field,$_POST["entity_restrict"]);
			} else {
				$where.=getEntitiesRestrictRequest("",$_POST['table'],$field);
			}
		}


		if ($where=="WHERE ") $where="";


		$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY $add_order completename $LIMIT";

		$result = $DB->query($query);

		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
			echo "<option class='tree' value=\"0\">--".$LANG["common"][11]."--</option>";
		$display_selected=true;
		switch ($_POST["table"]){
			case "glpi_dropdown_kbcategories" :
				echo "<option class='tree' value=\"0\">--".$LANG["knowbase"][12]."--</option>";
				break;
			case "glpi_entities" :
				// If entity=0 allowed 
				if (isset($_POST["entity_restrict"])&&  
					(($_POST["entity_restrict"]<0 && in_array(0,$_SESSION['glpiactiveentities'])) 
					|| (is_array($_POST["entity_restrict"]) && in_array(0,$_POST["entity_restrict"])))) 
				{    
					echo "<option class='tree' value=\"0\">--".$LANG["entity"][2]."--</option>";
				} 

				// Entity=0 already add above 
				if ($_POST['value']==0){ 
					$display_selected=false; 
				} 

				break;
			default :
				echo "<option class='tree' value=\"0\">-----</option>";
				break;
		}

		if ($display_selected){
			$outputval=getDropdownName($_POST['table'],$_POST['value']);
			if (!empty($outputval)&&$outputval!="&nbsp;")
				echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";
		}

		if ($DB->numrows($result)) {
			while ($data =$DB->fetch_array($result)) {

				$ID = $data['ID'];
				$level = $data['level'];
	
				$output=$data['name'];

				$class=" class='tree' ";
				$raquo="&raquo;";
				if ($level==1){
					$class=" class='treeroot' ";
					$raquo="";
				}

				if ($CFG_GLPI['flat_dropdowntree']){
					$output=$data['completename'];
					if ($level>1){
						$class="";
						$raquo="";
						$level=0;
					}
				}
				
				if (empty($output)) {
					$output="($ID)";
				}

				$style=$class;
				$addcomment="";
				if (isset($data["comments"])) $addcomment=" - ".$data["comments"];

				echo "<option value=\"$ID\" $style title=\"".$data['completename']."$addcomment\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.utf8_substr($output,0,$_POST["limit"])."</option>";
			}

		}
		echo "</select>";

	} else {
		if (!$first) $where.=" AND ";
		else $first=false;
		$where .=" ID <> '".$_POST['value']."' ";

		if (in_array($_POST['table'],$CFG_GLPI["specif_entities_tables"])){

			if (isset($_POST["entity_restrict"])&&$_POST["entity_restrict"]>=0){
				$where.=getEntitiesRestrictRequest("AND",$_POST['table'],"FK_entities",$_POST["entity_restrict"]);
			} else {
				$where.=getEntitiesRestrictRequest("AND",$_POST['table']);
			}
		}

		$field="name";
		if (ereg("glpi_device",$_POST['table'])) $field="designation";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"])
			$where.=" AND $field ".makeTextSearch($_POST['searchText']);


		switch ($_POST['table']){
			case "glpi_contacts":
				$query = "SELECT CONCAT(name,' ',firstname) as $field, ".$_POST['table'].".comments, ".$_POST['table'].".ID FROM ".$_POST['table']." $where ORDER BY $field $LIMIT";
			break;
			default :
				$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY $field $LIMIT";
			break;
		}
//		echo $query;
		$result = $DB->query($query);

		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]&&$DB->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$LANG["common"][11]."--</option>";

		echo "<option value=\"0\">-----</option>";

		$output=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($output)&&$output!="&nbsp;"){
			echo "<option selected value='".$_POST['value']."'>".$output."</option>";
		}

		if ($DB->numrows($result)) {
			while ($data =$DB->fetch_array($result)) {
				$output = $data[$field];
				if (isset($_POST['withserial'])&&isset($data["serial"])) $output.=" - ".$data["serial"];
				if (isset($_POST['withotherserial'])&&isset($data["otherserial"])) $output.=" - ".$data["otherserial"];
				$ID = $data['ID'];
				$addcomment="";
				if (isset($data["comments"])) $addcomment=" - ".$data["comments"];

				if (empty($output)) $output="($ID)";
 				echo "<option value=\"$ID\" title=\"$output$addcomment\">".utf8_substr($output,0,$_POST["limit"])."</option>";
			}
		}
		echo "</select>";
	}

if (isset($_POST["comments"])&&$_POST["comments"]){
	$params=array('value'=>'__VALUE__','table'=>$_POST["table"]);
	ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],"comments_".$_POST["myname"].$_POST["rand"],$CFG_GLPI["root_doc"]."/ajax/comments.php",$params,false);
}

if (isset($_POST["update_item"])&&
	(is_array($_POST["update_item"])||strlen($_POST["update_item"])>0)){
	if (!is_array($_POST["update_item"])){
		$data=unserialize(stripslashes($_POST["update_item"]));
	} else $data=$_POST["update_item"];
	
	if (is_array($data)&&count($data)){
		$params=array();
		if (isset($data['value_fieldname'])){
			$params=array($data['value_fieldname']=>'__VALUE__');
		}
		if (isset($data["moreparams"])&&is_array($data["moreparams"])&&count($data["moreparams"])){
			foreach ($data["moreparams"] as $key => $val){
				$params[$key]=$val;
			}
		}
		ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],$data['to_update'],$data['url'],$params,false);
	}
}


?>
