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
	header("Content-Type: text/html; charset=UTF-8");

	checkAuthentication("post-only");

	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	);

if (isset($items[$_POST["type"]])&&$_POST["type"]>0){
	$table=$items[$_POST["type"]];

		$rand=mt_rand();
		if (!isset($_POST['searchText']))$_POST['searchText']="";
		
		$where="WHERE '1'='1' ";
		$where.=" AND deleted='N' ";
		$where.=" AND is_template='0' ";		
		
		if (strlen($_POST['searchText'])>0&&$_POST['searchText']!=$cfg_features["ajax_wildcard"])
			$where.=" AND name LIKE '%".$_POST['searchText']."%' ";

		$NBMAX=$cfg_layout["dropdown_max"];
		
		$LIMIT="LIMIT 0,$NBMAX";
		if ($_POST['searchText']==$cfg_features["ajax_wildcard"]) $LIMIT="";
						
		$query = "SELECT * FROM ".$table." $where ORDER BY name $LIMIT";

		$result = $db->query($query);

		echo "<select id='item$rand' name=\"item\" size='1'>";
		
		if ($_POST['searchText']!=$cfg_features["ajax_wildcard"]&&$db->numrows($result)==$NBMAX)
			echo "<option value=\"0\">--".$lang["common"][11]."--</option>";
	
		echo "<option value=\"0\">-----</option>";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($data = $db->fetch_array($result)) {
				$output = $data['name'];
				$ID = $data['ID'];
				if (empty($output)) $output="($ID)";
				echo "<option value=\"$ID\">$output</option>";
				$i++;
			}
		}
		echo "</select>";

	echo "<script type='text/javascript' >\n";
	echo " new Form.Element.Observer('item$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_item_$rand','".$cfg_install["root"]."/ajax/dropdownConnectPort.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$rand');},\n";
	echo "           method:'post', parameters:'item=' + value+'&type=".$_POST['type']."&myname=".$_POST['myname']."&current=".$_POST['current']."'\n";
	echo "})});\n";
	echo "</script>\n";


	echo "<span id='results_item_$rand'>\n";
	echo "</span>\n";	



}		
?>