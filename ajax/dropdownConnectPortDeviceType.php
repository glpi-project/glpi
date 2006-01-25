<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
	header_nocache();

	checkAuthentication("post-only");

	// Make a select box
	$db = new DB;

	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	);

if (isset($items[$_POST["type"]])&&$_POST["type"]>0){
	$table=$items[$_POST["type"]];
	
	$rand=mt_rand();
	
	echo "<input type='text' id='search_$rand' name='____data_$rand' size='4'>\n";

	echo "<script type='text/javascript' >\n";
	echo " new Form.Element.Observer('search_$rand', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('results_$rand','".$cfg_install["root"]."/ajax/dropdownConnectPortDevice.php',{asynchronous:true, evalScripts:true, \n";
	echo "           onComplete:function(request)\n";
	echo "            {Element.hide('search_spinner_$rand');}, \n";
	echo "           onLoading:function(request)\n";
	echo "            {Element.show('search_spinner_$rand');},\n";
	echo "           method:'post', parameters:'searchText=' + value+'&type=".$_POST['type']."&myname=".$_POST['myname']."&current=".$_POST['current']."'\n";
	echo "})});\n";

	echo "</script>\n";

	echo "<div id='search_spinner_$rand' style=' position:absolute;   filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='' /></div>\n";

	echo "<span id='results_$rand'>\n";
	echo "<select name='item$rand'><option value='0'>------</option></select>\n";
	echo "</span>\n";	


}		

?>