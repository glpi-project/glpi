<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
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
// Original Author of file: 
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


$NEEDED_ITEMS=array("user","stat","tracking");
include ($phproot . "/inc/includes.php");

commonHeader($lang["title"][11],$_SERVER['PHP_SELF']);

checkRight("statistic","1");


if (isset($_GET["date1"])) $_POST["date1"] = $_GET["date1"];
if (isset($_GET["date2"])) $_POST["date2"] = $_GET["date2"];
if (isset($_GET["type"])) $_POST["type"] = $_GET["type"];
if (empty($_POST["type"])) $_POST["type"]="user";

if(empty($_POST["date1"])&&empty($_POST["date2"])) {
	$year=date("Y")-1;
	$_POST["date1"]=date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));

	$_POST["date2"]=date("Y-m-d");
}

if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
	$tmp=$_POST["date1"];
	$_POST["date1"]=$_POST["date2"];
	$_POST["date2"]=$tmp;
}

if(!isset($_GET["start"])) $_GET["start"] = 0;


$type=$_POST["type"];

$items=array(
		"user"=>array(	
			"title"=>$lang["common"][37],
			"field"=>"glpi_tracking.author"
			),
		"group"=>array(	
			"title"=>$lang["common"][35],
			"field"=>"glpi_tracking.FK_group"
			),
		"category"=>array(	
			"title"=>$lang["common"][36],
			"field"=>"glpi_tracking.category"
			),
		"priority"=>array(	
			"title"=>$lang["joblist"][2],
			"field"=>"glpi_tracking.priority"
			),
		"request_type"=>array(	
			"title"=>$lang["job"][44],
			"field"=>"glpi_tracking.request_type"
			),
		"technicien"=>array(	
			"title"=>$lang["stats"][2]." ".$lang["stats"][48],
			"field"=>"glpi_tracking.assign"
			),
		"technicien_followup"=>array(	
				"title"=>$lang["stats"][2]." ".$lang["stats"][49],
				"field"=>"glpi_followup.author"
				),
		"enterprise"=>array(	
				"title"=>$lang["financial"][26],
				"field"=>"glpi_tracking.assign_ent"
				),

		);

		$field=$items[$type]["field"];

		echo "<div align ='center'><p><b><span class='icon_sous_nav'>".$items[$type]["title"]."</span></b></p></div>";


		echo "<div align='center'><form method=\"post\" name=\"form\" action=\"stat.tracking.php\">";
		echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
		echo "<td rowspan='2' align='center'>";
		echo "<select name='type'>";
foreach ($items as $key => $val)
	echo "<option value='$key' ".($key==$type?"selected":"").">".$val["title"]."</option>";
	echo "</select>";
	echo "</td>";
	echo "<td align='right'>";
	echo $lang["search"][8]." :</td><td>";
	showCalendarForm("form","date1",$_POST["date1"]);
	echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
	echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
	showCalendarForm("form","date2",$_POST["date2"]);
	echo "</td></tr>";
	echo "</table></form></div>";


	$val=getStatsItems($_POST["date1"],$_POST["date2"],$type);
	$params=array("type"=>$type,"field"=>$field,"date1"=>$_POST["date1"],"date2"=>$_POST["date2"],"start"=>$_GET["start"]);
	printPager($_GET['start'],count($val),$_SERVER['PHP_SELF'],"date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;type=".$_POST["type"],STAT_TYPE,$params);

	displayStats($type,$field,$_POST["date1"],$_POST["date2"],$_GET['start'],$val);

	commonFooter();
	?>
