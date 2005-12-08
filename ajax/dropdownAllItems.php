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

	// Make a select box
	$db = new DB;

		$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	MONITOR_TYPE=>"glpi_monitors",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	SOFTWARE_TYPE=>"glpi_software",
	ENTERPRISE_TYPE=>"glpi_enterprises",
	CARTRIDGE_TYPE=>"glpi_cartridges_type",
	CONSUMABLE_TYPE=>"glpi_consumables_type",
	USER_TYPE=>"glpi_users",
	CONTRACT_TYPE=>"glpi_contracts",
	);

if (isset($items[$_POST["idtable"]])){
	$table=$items[$_POST["idtable"]];
	
	// Link to user for search only > normal users
	$link="dropdown.php";
	if ($_POST["idtable"]==USER_TYPE)
		$link="dropdownUsers.php";
	
	$rand=mt_rand();
	echo "<input id='search_".$_POST['myname']."$rand' name='____data_".$_POST['myname']."$rand' size='4'>";	
	$moreparam="";
	if(isset($_POST['value'])) $moreparam="&value=".$_POST['value'];

	echo "<script type='text/javascript' >";
	echo "   new Form.Element.Observer('search_".$_POST['myname']."$rand', 1, ";
	echo "      function(element, value) {";
	echo "      	new Ajax.Updater('results_ID$rand','".$cfg_install["root"]."/ajax/$link',{asynchronous:true, evalScripts:true, ";
	echo "           onComplete:function(request)";
	echo "            {Element.hide('search_spinner$rand');}, ";
	echo "           onLoading:function(request)";
	echo "            {Element.show('search_spinner$rand');},";
	echo "           method:'post', parameters:'searchText=' + value+'&table=$table&myname=".$_POST["myname"]."$moreparam'";
	echo "})})";
	echo "</script>";	
	
	echo "<div id='search_spinner$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>";	
	
	echo "<span id='results_ID$rand'>";
	echo "<select name='ID'><option value='0'>------</option></select>";
	echo "</span>";	
}		
?>