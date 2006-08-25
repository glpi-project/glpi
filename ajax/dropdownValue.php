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
	include ("_relpos.php");
	$AJAX_INCLUDE=1;
	include ($phproot."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};
checkLoginUser();

// Make a select box with preselected values
if (!isset($_POST["limit"])) $_POST["limit"]=$cfg_glpi["dropdown_limit"];
if($_POST['table'] == "glpi_dropdown_netpoint") {

	$where="";
	if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
		$where=" WHERE (t1.name ".makeTextSearch($_POST['searchText'])." OR t2.completename ".makeTextSearch($_POST['searchText']).")";

	$NBMAX=$cfg_glpi["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";


	$query = "select t1.comments as comments, t1.ID as ID, t1.name as netpname, t2.completename as loc from glpi_dropdown_netpoint as t1";
	$query .= " left join glpi_dropdown_locations as t2 on (t1.location = t2.ID)";
	$query.=$where;
	$query .= " order by t1.name,t2.name $LIMIT"; 

	$result = $db->query($query);

	echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

	if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
		echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	else echo "<option value=\"0\">-----</option>";

	$output=getDropdownName($_POST['table'],$_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."'>".$output."</option>";


	if ($db->numrows($result)) {
		while ($data =$db->fetch_array($result)) {
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
	if (in_array($_POST['table'],$cfg_glpi["deleted_tables"])){
		if (!$first) $where.=" AND ";
		else $first=false;
		$where.=" deleted='N' ";
	}
	if (in_array($_POST['table'],$cfg_glpi["template_tables"])){
		if (!$first) $where.=" AND ";
		else $first=false;
		$where.=" is_template='0' ";
	}


	$NBMAX=$cfg_glpi["dropdown_max"];
	$LIMIT="LIMIT 0,$NBMAX";
	if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";


	if (in_array($_POST['table'],$cfg_glpi["dropdowntree_tables"])){

		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]){
			if (!$first) $where.=" AND ";
			else $first=false;
			$where.=" completename ".makeTextSearch($_POST['searchText']);
		}

		if ($where=="WHERE ") $where="";

		$query = "SELECT * FROM ".$_POST['table']." $where ORDER BY completename $LIMIT";

		$result = $db->query($query);

		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option class='tree' value=\"0\">--".$lang["common"][11]."--</option>";

		if ($_POST["table"]=="glpi_dropdown_kbcategories")
			echo "<option class='tree' value=\"0\">--".$lang["knowbase"][12]."--</option>";
		else echo "<option class='tree' value=\"0\">-----</option>";

		$outputval=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($outputval)&&$outputval!="&nbsp;")
			echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";

		if ($db->numrows($result)) {
			while ($data =$db->fetch_array($result)) {

				$ID = $data['ID'];
				$level = $data['level'];

				if (empty($data['name'])) $output="($ID)";
				else $output=$data['name'];
				if ($ID==$_POST["value"]) {
					$output=$outputval;
					echo "<option class='tree' value='".$ID."'>".$output."</option>";
				} else {
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

		}
		echo "</select>";

	} else {
		if (!$first) $where.=" AND ";
		else $first=false;
		$where .=" (ID <> '".$_POST['value']."' ";


		$field="name";
		if (ereg("glpi_device",$_POST['table'])) $field="designation";

		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
			$where.=" AND name ".makeTextSearch($_POST['searchText']);
		$where.=")";

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

		$result = $db->query($query);
		echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\" size='1'>";

		if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$lang["common"][11]."--</option>";

		echo "<option value=\"0\">-----</option>";

		$output=getDropdownName($_POST['table'],$_POST['value']);
		if (!empty($output)&&$output!="&nbsp;")
			echo "<option selected value='".$_POST['value']."'>".$output."</option>";

		if ($db->numrows($result)) {
			while ($data =$db->fetch_array($result)) {
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
	echo "      	new Ajax.Updater('comments_".$_POST["myname"].$_POST["rand"]."','".$cfg_glpi["root_doc"]."/ajax/comments.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'value='+value+'&table=".$_POST["table"]."'\n";
	echo "})})\n";
	echo "</script>\n";
}

?>
