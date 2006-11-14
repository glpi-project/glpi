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
$NEEDED_ITEMS=array("device");
include ($phproot."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

if (isset($_POST["idtable"])){
	$table=getDeviceTable($_POST["idtable"]);


	$rand=mt_rand();
	displaySearchTextAjaxDropdown($_POST['myname'].$rand); 

	echo "<script type='text/javascript' >";
	echo "   new Form.Element.Observer('search_".$_POST['myname']."$rand', 1, ";
	echo "      function(element, value) {";
	echo "      	new Ajax.Updater('results_ID$rand','".$cfg_glpi["root_doc"]."/ajax/dropdownValue.php',{asynchronous:true, evalScripts:true, ";
	echo "           onComplete:function(request)";
	echo "            {Element.hide('search_spinner$rand');}, ";
	echo "           onLoading:function(request)";
	echo "            {Element.show('search_spinner$rand');},";
	echo "           method:'post', parameters:'searchText=' + value+'&table=$table&myname=".$_POST["myname"]."&value=0&rand=$rand'";
	echo "})})";
	echo "</script>";	

	echo "<div id='search_spinner$rand' style=' position:absolute;  filter:alpha(opacity=70); -moz-opacity:0.7; opacity: 0.7; display:none;'><img src=\"".$HTMLRel."pics/wait.png\" title='Processing....' alt='Processing....' /></div>";	

	$nb=0;
	if ($cfg_glpi["use_ajax"])
		$nb=countElementsInTable($table);

	if (!$cfg_glpi["use_ajax"]||$nb<$cfg_glpi["ajax_limit_count"]){
		echo "<script type='text/javascript' >\n";
		echo "document.getElementById('search_spinner$rand').style.visibility='hidden';";
		echo "Element.hide('search_".$_POST['myname']."$rand');";
		echo "document.getElementById('search_".$_POST['myname']."$rand').value='".$cfg_glpi["ajax_wildcard"]."';";
		echo "</script>\n";
	}

	echo "<span id='results_ID$rand'>";
	echo "<select name='ID'><option value='0'>------</option></select>";
	echo "</span>";	
}		
?>
