<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


define('GLPI_ROOT','..');
if ($_POST['idtable']<1000){
		$AJAX_INCLUDE=1;
	}
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkCentralAccess();

// Make a select box

if (isset($LINK_ID_TABLE[$_POST["idtable"]])){
	$table=$LINK_ID_TABLE[$_POST["idtable"]];

	// Link to user for search only > normal users
	$link="dropdownValue.php";
	if ($_POST["idtable"]==USER_TYPE){
		$link="dropdownUsers.php";
	}

	$rand=mt_rand();

	$use_ajax=false;
	if ($_SESSION["glpiuse_ajax"]&&countElementsInTable($table)>$_SESSION["glpiajax_limit_count"]){
		$use_ajax=true;
	}

        $paramsallitems=array('searchText'=>'__VALUE__',
                        'table'=>$table,
                        'rand'=>$rand,
                        'myname'=>$_POST["myname"],
			'withserial'=>1,
			'withotherserial'=>1,
                        );

	if(isset($_POST['value'])) {
		$paramsallitems['value']=$_POST['value'];
	}
	if(isset($_POST['entity_restrict'])) {
		$paramsallitems['entity_restrict']=$_POST['entity_restrict'];
	}
	if(isset($_POST['onlyglobal'])) {
		$paramsallitems['onlyglobal']=$_POST['onlyglobal'];
	}
	
	$default="<select name='".$_POST["myname"]."'><option value='0'>------</option></select>";
	ajaxDropdown($use_ajax,"/ajax/$link",$paramsallitems,$default,$rand);

//	if(isset($_POST['value'])&&$_POST['value']>0){
//		$paramsallitems['searchText']=$_SESSION["glpiajax_wildcard"];
//		echo "<script type='text/javascript' >\n";
//		echo "	Ext.get('search_$rand').value='".$_SESSION["glpiajax_wildcard"]."';";
//		echo "</script>\n";
//		ajaxUpdateItem("results_$rand",$CFG_GLPI["root_doc"]."/ajax/$link",$paramsallitems);
//	}

}		
?>