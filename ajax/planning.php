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

	$AJAX_INCLUDE=1;
	include (GLPI_ROOT."/inc/includes.php");
	
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
	
	checkCentralAccess();
	
	$split=explode(":",$CFG_GLPI["planning_begin"]);
	$global_begin=$split[0].":".$split[1];
	$split=explode(":",$CFG_GLPI["planning_end"]);
	$global_end=$split[0].":".$split[1];
	
	if (isset($_POST["ID"])&&$_POST["ID"]>0){
		echo "<input type='hidden' name='plan[ID]' value='".$_POST["ID"]."'>";
	}

	if (isset($_POST["begin"])&&!empty($_POST["begin"])){
		$begin=$_POST["begin"];
	} else {
		$begin=date("Y-m-d")." 12:00:00";
	}
	if (isset($_POST["end"])&&!empty($_POST["end"])){
		$end=$_POST["end"];
	} else {
		$end=date("Y-m-d")." 13:00:00";
	}

	$state=0;
	if (isset($_POST["state"])){
		$state=$_POST["state"];
	} 
	
	echo "<table class='tab_cadre' cellpadding='2'>";
	if (isset($_POST["author"])&&isset($_POST["entity"])){
		echo "<tr class='tab_bg_2'><td>".$LANG["planning"][9].":	</td>";
		echo "<td>";
		dropdownUsers("plan[id_assign]",$_POST["author"],"own_ticket",-1,1,$_POST["entity"]);
		echo "</td></tr>";
	}
	
	echo "<tr class='tab_bg_2'><td>".$LANG["search"][8].":	</td><td>";
	showDateTimeFormItem("plan[begin]",$begin,-1,false,true,'','',$global_begin,$global_end);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'><td>".$LANG["search"][9].":	</td><td>";
	showDateTimeFormItem("plan[end]",$end,-1,false,true,'','',$global_begin,$global_end);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_2'><td>".$LANG["state"][0].":	</td>";
	echo "<td>";
	dropdownPlanningState("plan[state]",$state);
	echo "</td></tr>";
	
	echo "</table>";

	ajaxFooter();
?>
