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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


if(strpos($_SERVER['PHP_SELF'],"dropdownSelectSoftware.php")){
	define('GLPI_ROOT','..');
	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
};


checkRight("software","w");

// Make a select box

$rand=mt_rand();

$where="";
$leftjoin=""; 

	 	
if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$CFG_GLPI["ajax_wildcard"])
	$where.=" AND name ".makeTextSearch($_POST['searchText'])." ";

//$where.=" AND FK_entities='".$_POST["entity_restrict"]."' ";
$where .= getEntitiesRestrictRequest(' AND', 'glpi_software','FK_entities',$_POST["entity_restrict"],true);


$query = "SELECT DISTINCT glpi_software.ID, glpi_software.name FROM glpi_software $leftjoin WHERE glpi_software.deleted=0 AND glpi_software.is_template=0 $where ORDER BY glpi_software.name";
$result = $DB->query($query);

echo "<select name='sID' id='item_type$rand'>\n";
echo "<option value='0'>-----</option>\n";
if ($DB->numrows($result)) {
	while ($data=$DB->fetch_array($result)) {
		$sID = $data["ID"];
		$output=$data["name"];
		echo  "<option value='$sID' title=\"".cleanInputText($output)."\">".substr($output,0,$_SESSION["glpidropdown_limit"])."</option>";
	}	
}
echo "</select>\n";


$paramsselsoft=array('sID'=>'__VALUE__',
		'myname'=>$_POST["myname"],
		);
ajaxUpdateItemOnSelectEvent("item_type$rand","show_".$_POST["myname"].$rand,$CFG_GLPI["root_doc"]."/ajax/dropdownInstallVersion.php",$paramsselsoft,false);

echo "<span id='show_".$_POST["myname"]."$rand'>&nbsp;</span>\n";	

?>