<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
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
	include ($phproot."/glpi/includes.php");
	include ($phproot."/glpi/includes_software.php");
	header("Content-Type: text/html; charset=UTF-8");

	checkAuthentication("post-only");

	// Make a select box
	$db = new DB;

	$rand=mt_rand();

	$where="";	
	if (strlen($_POST['searchSoft'])>0&&$_POST['searchSoft']!=$cfg_features["ajax_wildcard"])
		$where.=" AND name LIKE '%".$_POST['searchSoft']."%' ";
	
	$query = "SELECT * FROM glpi_software WHERE deleted='N' AND is_template='0' $where order by name";
	$result = $db->query($query);
	$number = $db->numrows($result);

	echo "<select name='sID' id='item_type$rand'>\n";
	echo "<option value='0'>-----</option>\n";
	$i=0;
	while ($i < $number) {
		$version = $db->result($result, $i, "version");
		$name = $db->result($result, $i, "name");
		$sID = $db->result($result, $i, "ID");
		
		if (empty($withtemplate)||isGlobalSoftware($sID)||isFreeSoftware($sID))
		echo  "<option value='$sID'>$name (v. $version)</option>";
		$i++;
	}	
	echo "</select>\n";
	
	
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('item_type$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('show_".$_POST["myname"]."$rand','".$cfg_install["root"]."/ajax/dropdownInstallSoftware.php',{asynchronous:true, evalScripts:true, \n";	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_".$_POST["myname"]."$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_".$_POST["myname"]."$rand');},\n";
	echo "           method:'post', parameters:'sID='+value+'&myname=".$_POST["myname"]."'\n";
	echo "})})\n";
	echo "</script>\n";
	
	echo "<div id='search_spinner_".$_POST["myname"]."$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>\n";
	echo "<span id='show_".$_POST["myname"]."$rand'>&nbsp;</span>\n";	
		
?>