<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi-project.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------


	include ("_relpos.php");
	$AJAX_INCLUDE=1;
	$NEEDED_ITEMS=array("software");
	include ($phproot."/inc/includes.php");

	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkRight("software","w");

	// Make a select box

	$rand=mt_rand();

	$where="";	
	if (strlen($_POST['searchSoft'])>0&&$_POST['searchSoft']!=$cfg_glpi["ajax_wildcard"])
		$where.=" AND name ".makeTextSearch($_POST['searchSoft'])." ";
	
	$query = "SELECT * FROM glpi_software WHERE deleted='N' AND is_template='0' $where order by name";
	$result = $db->query($query);

	echo "<select name='sID' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	if ($db->numrows($result))
	while ($data=$db->fetch_array($result)) {
		$sID = $data["ID"];
		
		if (empty($withtemplate)||isGlobalSoftware($sID)||isFreeSoftware($sID)){
			$output=$data["name"]." (v. ".$data["version"].")";
			echo  "<option value='$sID' title=\"$output\">".substr($output,0,$cfg_glpi["dropdown_limit"])."</option>";
		}
	}	
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_".$_POST["myname"]."$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownInstallLicense.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_".$_POST["myname"]."$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_".$_POST["myname"]."$rand');},\n";
	echo "           method:'post', parameters:'sID='+value+'&myname=".$_POST["myname"]."&massiveaction=".$_POST["massiveaction"]."'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_".$_POST["myname"]."$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_".$_POST["myname"]."$rand'>&nbsp;</span>\n";	
		
?>