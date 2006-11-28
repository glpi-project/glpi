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


include ("_relpos.php");
$AJAX_INCLUDE=1;
$NEEDED_ITEMS=array("search");
include ($phproot."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (isset($_POST["action"])&&isset($_POST["type"])&&!empty($_POST["type"])){

	checkTypeRight($_POST["type"],"w");

	echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
	echo "<input type='hidden' name='device_type' value='".$_POST["type"]."'>";
	switch($_POST["action"]){

		case "delete":
			case "purge":
			case "restore":
			echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		break;
		case "install":
			dropdownSoftwareToInstall("lID",0,1);
		break;
		case "connect":
			dropdownConnect(COMPUTER_TYPE,$_POST["type"],"connect_item");
		echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		break;
		case "disconnect":
			echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		break;
		case "add_group":
			dropdownValue("glpi_groups","group",0);
		echo "<input type=\"submit\" name=\"massiveaction\" class=\"submit\" value=\"".$lang["buttons"][2]."\" >";
		break;
		case "update":
			$first_group=true;
		$newgroup="";
		$items_in_group=0;
		echo "<select name='id_field' id='massiveaction_field'>";
		echo "<option value='0' selected>------</option>";
		foreach ($SEARCH_OPTION[$_POST["type"]] as $key => $val){
			if (!is_array($val)){
				if (!empty($newgroup)&&$items_in_group>0) {
					echo $newgroup;
					$first_group=false;
				}
				$items_in_group=0;
				$newgroup="";
				if (!$first_group) $newgroup.="</optgroup>";
				$newgroup.="<optgroup label=\"$val\">";
			} else {
				if ($key>1){ // No ID
					if (!empty($val["linkfield"])
							||$val["table"]=="glpi_dropdown_state"
							||$val["table"]=="glpi_infocoms"
							||$val["table"]=="glpi_enterprises_infocoms"
							||$val["table"]=="glpi_dropdown_budget"
							||($val["table"]=="glpi_ocs_link"&&$key==101 // auto_update_ocs
							  )){
						$newgroup.= "<option value='$key'>".$val["name"]."</option>";
						$items_in_group++;
					}
				}
			}
		}
		if (!empty($newgroup)&&$items_in_group>0) echo $newgroup;
		if (!$first_group)
			echo "</optgroup>";

		echo "</select>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('massiveaction_field', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('show_massiveaction_field','".$cfg_glpi["root_doc"]."/ajax/dropdownMassiveActionField.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
		echo "            {Element.hide('search_spinner_massiveaction_field');}, \n";
		echo "           onLoading:function(request)\n";
		echo "            {Element.show('search_spinner_massiveaction_field');},\n";
		echo "           method:'post', parameters:'id_field='+value+'&device_type=".$_POST["type"]."'\n";
		echo "})})\n";
		echo "</script>\n";

		echo "<div id='search_spinner_massiveaction_field' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
		echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";

		break;

	}
}

?>
