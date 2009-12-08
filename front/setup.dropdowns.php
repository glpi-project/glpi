<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



if(!defined('GLPI_ROOT')){
   define('GLPI_ROOT', '..');

   include (GLPI_ROOT . "/inc/includes.php");
}

checkSeveralRightsOr(array("dropdown"=>"r","entity_dropdown"=>"r"));



//if(isset($_SERVER['HTTP_REFERER']))
//$httpreferer=preg_replace("/\?which=\w*/","",$_SERVER['HTTP_REFERER']);
if (isset($_POST["which"])) {
   $which=$_POST["which"];
} else if (isset($_GET["which"])) {
   $which=$_GET["which"];
} else {
   $which="";
}

// TODO Temporary hack form dropdown which are manage as Object
if (is_numeric($which)) {
   glpi_header(GLPI_ROOT."/front/dropdown.php?itemtype=$which");
}

// Security
if (!empty($which) && ! TableExists($which) ){
	exit();
}

// Security
if (isset($_POST["tablename"]) && ! TableExists($_POST["tablename"]) ){
	exit();
}


if (isset($_GET["where"]))$where=$_GET["where"];
else if (isset($_POST["value_where"]))$where=$_POST["value_where"];
else $where="";
if (isset($_GET["tomove"])) $tomove=$_GET["tomove"];
else if (isset($_POST["value_to_move"])) $tomove=$_POST["value_to_move"];
else $tomove="";
if (isset($_GET["value2"]))$value2=$_GET["value2"];
else if (isset($_POST["value2"]))$value2=$_POST["value2"];
else $value2="";
if (isset($_GET["type"]))$type=$_GET["type"];
else if (isset($_POST["type"]))$type=$_POST["type"];
else $type="";
// Selected Item
if (isset($_POST["id"])) $ID=$_POST["id"];
elseif (isset($_GET["id"])) $ID=$_GET["id"];
else $ID="";

if (isset($_POST["entities_id"])) $entities_id=$_POST["entities_id"];
elseif (isset($_GET["entities_id"])) $entities_id=$_GET["entities_id"];
else $entities_id="";

if (isset($_POST['mass_delete'])){
	$input['tablename']=$_POST['which'];
	foreach ($_POST["item"] as $key => $val){
		if ($val==1) {
			$input['id']=$key;
			deleteDropdown($input);
		}
	}
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&entities_id=$entities_id");

}else if (isset($_POST["several_add"])) {

	for ($i=$_POST["from"];$i<=$_POST["to"];$i++){
		$_POST["value"]=$_POST["before"].$i.$_POST["after"];
		addDropdown($_POST);
	}

	Event::log(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&entities_id=$entities_id");

}else if (isset($_POST["move"])) {
	Event::log(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]." ".CommonDropdown::getDropdownName($_POST['tablename'],$_POST['value_to_move']));
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&entities_id=$entities_id");

}else if (isset($_POST["add"])) {
	addDropdown($_POST);
	Event::log(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["value"]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type&entities_id=$entities_id");

} else if (isset($_POST["delete"]) && $_POST["id"]>0) {
	if(dropdownUsed($_POST["tablename"], $_POST["id"]) && empty($_POST["forcedelete"])) {
		if (!strpos($_SERVER['PHP_SELF'],"popup")){
			commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"config","dropdowns");
		}
		showDeleteConfirmForm($_SERVER['PHP_SELF'],$_POST["tablename"], $_POST["id"],$_POST["entities_id"]);
		if (!strpos($_SERVER['PHP_SELF'],"popup")){
			commonFooter();
		}
	} else {
		Event::log(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][22]." ".CommonDropdown::getDropdownName($_POST['which'],$_POST['id']));
		deleteDropdown($_POST);
		glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&entities_id=$entities_id");
	}

} else if (isset($_POST["update"])) {
	updateDropdown($_POST);
	Event::log(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&id=$ID&entities_id=$entities_id");

} else if (isset($_POST["replace"])) {
	replaceDropDropDown($_POST);
	Event::log(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG['log'][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&entities_id=$entities_id");

} else {
	if (!strpos($_SERVER['PHP_SELF'],"popup")){
		commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"config","dropdowns");
	}

   $optgroup = getAllDropdowns();

	if (!strpos($_SERVER['PHP_SELF'],"popup")){
		echo "<div align='center'><form method='get' action=\"".$_SERVER['PHP_SELF']."\">";
		echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
		echo $LANG['setup'][72].": </th></tr><tr class='tab_bg_1'><td><select name='which'>";

		foreach($optgroup as $label=>$dp){

			echo "<optgroup label=\"$label\">";

			foreach ($dp as $key => $val){
				$sel="";
				if ($which==$key) $sel="selected";
				echo "<option value='$key' $sel>".$val."</option>";
			}
			echo "</optgroup>";
		}
		echo "</select></td>";
		echo "<td><input type='submit' value=\"".$LANG['buttons'][2]."\" class='submit' ></td></tr>";
		echo "</table></form></div>";
	}

	if ($which=="glpi_netpoints"){
		if (haveRight("entity_dropdown","w")){
			$title=$LANG['setup'][73];

			if (strpos($_SERVER['PHP_SELF'],"popup")){

				if ($value2>0) {
					$title .= " (" . $LANG['common'][15] . ":&nbsp;" . CommonDropdown::getDropdownName("glpi_locations", $value2) . ")";
				}

			} else {
				echo "<div align='center'><form method='get' action=\"".$_SERVER['PHP_SELF']."\">";
				echo "<table class='tab_cadre' cellpadding='5'><tr><th colspan='2'>";
				echo $LANG['setup'][77].": </th></tr><tr class='tab_bg_1'><td>";
				echo "<input type='hidden' name='which' value='glpi_netpoints' />";
				CommonDropdown::dropdownValue("glpi_locations", "value2", $value2, $entities_id);
				echo "</td><td><input type='submit' value=\"".$LANG['buttons'][2]."\" class='submit' ></td></tr>";
				echo "</table></form></div>";
			}
			if (strlen($value2) > 0) {
				if (isset($_GET['mass_deletion'])){
					showDropdownList($_SERVER['PHP_SELF'],$which,$entities_id,$value2);
				} else {
					showFormNetpoint($_SERVER['PHP_SELF'],$title,$ID,$entities_id,$value2);
				}
			}
		}
	} else if ($which){
		// Search title
		$title="";
		foreach ($optgroup as $key => $val){
			if (isset($val[$which])){
				$title=$val[$which];
			}
		}
      if (empty($title)){
         echo "<div class='center'><br><strong>".$LANG['setup'][66]."</strong><br>";
         echo "<a href='javascript:window.close()'>".$LANG['buttons'][13]."</a>";
         echo "</div>";
      }
		if (isset($_GET['mass_deletion'])){
			showDropdownList($_SERVER['PHP_SELF'],$which,$entities_id);
		} else {
			if (!empty($title)){
				if (in_array($which,$CFG_GLPI["dropdowntree_tables"])){
					showFormTreeDown($_SERVER['PHP_SELF'],$which,$title,$ID,$value2,$where,$tomove,$type,$entities_id);
				} else {
					showFormDropDown($_SERVER['PHP_SELF'],$which,$title,$ID,$entities_id);
				}
			}
		}
	}

	if (!strpos($_SERVER['PHP_SELF'],"popup")){
		commonFooter();
	}
}


?>
