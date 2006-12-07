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

if(ereg("dropdownUsersTracking.php",$_SERVER['PHP_SELF'])){
	include ("_relpos.php");
	$AJAX_INCLUDE=1;
	include ($phproot."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

}
checkCentralAccess();

// Make a select box with all glpi users
$where=" glpi_users.active='1' ";

if (isset($_POST['value']))
$where.=" AND  (glpi_users.ID <> '".$_POST['value']."' ";

if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_glpi["ajax_wildcard"])
$where.=" AND (glpi_users.name ".makeTextSearch($_POST['searchText'])." OR glpi_users.realname ".makeTextSearch($_POST['searchText'])." OR glpi_users.firstname ".makeTextSearch($_POST['searchText']).")";

$where.=")";	

$NBMAX=$cfg_glpi["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$cfg_glpi["ajax_wildcard"]) $LIMIT="";

$query = "SELECT DISTINCT glpi_users.ID, glpi_users.name, glpi_users.realname, glpi_users.firstname FROM glpi_tracking INNER JOIN glpi_users ON (glpi_users.ID=glpi_tracking.".$_POST['field']." AND glpi_tracking.".$_POST['field']." <> '') WHERE $where ORDER BY glpi_users.realname,glpi_users.firstname,glpi_users.name $LIMIT";
$result = $db->query($query);

echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".$_POST['myname']."\">";

if ($_POST['searchText']!=$cfg_glpi["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
echo "<option value=\"0\">--".$lang["common"][11]."--</option>";

echo "<option value=\"0\">[ ".$lang["search"][7]." ]</option>";

if (isset($_POST['value'])){
	$output=getUserName($_POST['value']);
	if (!empty($output)&&$output!="&nbsp;")
		echo "<option selected value='".$_POST['value']."' title=\"".$output."\">".substr($output,0,$cfg_glpi["dropdown_limit"])."</option>";
}	

if ($db->numrows($result)) {
	while ($data=$db->fetch_array($result)) {
		if (!empty($data["realname"])) {
			$output = $data["realname"];
			if (!empty($data["firstname"])) {
				$output .= " ".$data["firstname"];
			}
		}
		else $output = $data["name"];

		if ($data["ID"] == $value) {
			echo "<option value=\"".$data["ID"]."\" selected title=\"$output\">".substr($output,0,$cfg_glpi["dropdown_limit"])."</option>";
		} else {
			echo "<option value=\"".$data["ID"]."\" title=\"$output\">".substr($output,0,$cfg_glpi["dropdown_limit"])."</option>";
		}
	}
}
echo "</select>";

if (isset($_POST["comments"])&&$_POST["comments"]){
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('dropdown_".$_POST["myname"].$_POST["rand"]."', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('comments_".$_POST["myname"].$_POST["rand"]."','".$cfg_glpi["root_doc"]."/ajax/comments.php',{asynchronous:true, evalScripts:true, \n";
	echo "           method:'post', parameters:'value='+value+'&table=glpi_users'\n";
	echo "})})\n";
	echo "</script>\n";
}

?>
