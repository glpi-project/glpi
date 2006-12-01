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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("planning","tracking","user","computer","printer","monitor","peripheral","networking","software","enterprise","reminder","phone");
include ($phproot . "/inc/includes.php");

commonHeader($lang["title"][31],$_SERVER['PHP_SELF']);

checkSeveralRightsOr(array("show_all_planning"=>"1","show_planning"=>"1"));

if (!isset($_GET["date"])||$_GET["date"]=="0000-00-00") $_GET["date"]=strftime("%Y-%m-%d");
if (!isset($_GET["type"])) $_GET["type"]="week";
if (!isset($_GET["uID"])||!haveRight("show_all_planning","1")) $_GET["uID"]=$_SESSION["glpiID"];


if ($_GET["type"]!="month"){
	$time=strtotime($_GET["date"]);

	$step=0;
	switch ($_GET["type"]){
		case "week":
			$step=7*60*60*24;
		break;
		case "day":
			$step=60*60*24;
		break;
	}

	$next=$time+$step+10;
	$prev=$time-$step;

	$next=strftime("%Y-%m-%d",$next);
	$prev=strftime("%Y-%m-%d",$prev);
} else {
	$split=split("-",$_GET["date"]);
	$year_next=$split[0];
	$month_next=$split[1]+1;
	if ($month_next>12) {
		$year_next++;
		$month_next-=12;
	}

	$year_prev=$split[0];
	$month_prev=$split[1]-1;

	if ($month_prev==0) {
		$year_prev--;
		$month_prev+=12;
	}
	$next=$year_next."-".$month_next."-".$split[2];
	$prev=$year_prev."-".$month_prev."-".$split[2];

}

titleTrackingPlanning();

echo "<div align='center'><form method=\"get\" name=\"form\" action=\"planning.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$_GET["type"]."&amp;uID=".$_GET["uID"]."&amp;date=$prev\"><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a>";
echo "</td>";
echo "<td>";
if (haveRight("show_all_planning","1"))
dropdownUsers("uID",$_GET['uID'],"interface",1);
else echo "&nbsp;";
echo "</td>";
echo "<td align='right'>";
echo $lang["common"][27].":</td><td>";
echo showCalendarForm("form","date",$_GET["date"]);
echo "</td>";
echo "<td><select name='type'>";
echo "<option value='day' ".($_GET["type"]=="day"?" selected ":"").">".$lang["planning"][5]."</option>";
echo "<option value='week' ".($_GET["type"]=="week"?" selected ":"").">".$lang["planning"][6]."</option>";
echo "<option value='month' ".($_GET["type"]=="month"?" selected ":"").">".$lang["planning"][14]."</option>";
echo "</select></td>";
echo "<td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td>";
echo "<td>";
urlIcal ($_GET['uID']);
echo "</td>";	
echo "<td>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$_GET["type"]."&amp;uID=".$_GET["uID"]."&amp;date=$next\"><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a>";
echo "</td>";

echo "</tr>";
echo "</table></form></div>";

showPlanning($_GET['uID'],$_GET["date"],$_GET["type"]);



commonFooter();

?>
