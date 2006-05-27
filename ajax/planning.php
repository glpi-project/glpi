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

//print_r($_GET);
	include ("_relpos.php");
	$AJAX_INCLUDE=1;
	include ($phproot."/inc/includes.php");

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();

	checkCentralAccess();

	$split=split(":",$cfg_glpi["planning_begin"]);
	$global_begin=intval($split[0]);
	$split=split(":",$cfg_glpi["planning_end"]);
	$global_end=intval($split[0]);
	
	
	if (isset($_GET["begin_date"])&&!empty($_GET["begin_date"]))
		$begin=strtotime($_GET["begin_date"]);
	else $begin=strtotime(date("Y-m-d")." 12:00:00");
	if (isset($_GET["end_date"])&&!empty($_GET["end_date"]))
		$end=strtotime($_GET["end_date"]);
	else $end=strtotime(date("Y-m-d")." 13:00:00");
	
	$begin_date=date("Y-m-d",$begin);
	$end_date=date("Y-m-d",$end);
	$begin_hour=date("H",$begin);
	$end_hour=date("H",$end);
	$begin_min=date("i",$begin);
	$end_min=date("i",$end);
	echo "<table class='tab_cadre' cellpadding='2'>";
	if (isset($_GET["author"])){
		echo "<tr class='tab_bg_2'><td>".$lang["planning"][9].":	</td>";
		echo "<td>";
		dropdownUsers("plan[id_assign]",$_GET["author"],"own_ticket",-1);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_2'><td>".$lang["search"][8].":	</td><td>";
	showCalendarForm($_GET['form'],"plan[begin_date]",$begin_date);
    	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";
	echo "<select name='plan[begin_hour]'>";
	for ($i=$global_begin;$i<$global_end;$i++){
	echo "<option value='$i'";
	if ($i==$begin_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='plan[begin_min]'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$begin_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["search"][9].":	</td><td>";
	showCalendarForm($_GET['form'],"plan[end_date]",$end_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	echo "<select name='plan[end_hour]'>";
	for ($i=$global_begin;$i<$global_end;$i++){
	echo "<option value='$i'";
	if ($i==$end_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='plan[end_min]'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$end_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";
	echo "</table>";
	

?>