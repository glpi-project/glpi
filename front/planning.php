<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("planning","tracking","user","computer","printer","monitor","peripheral","networking","software","enterprise","reminder","phone");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG["Menu"][29],$_SERVER['PHP_SELF'],"maintain","planning");

checkSeveralRightsOr(array("show_all_planning"=>"1","show_planning"=>"1"));

if (!isset($_GET["date"])||empty($_GET["date"])) $_GET["date"]=strftime("%Y-%m-%d");
if (!isset($_GET["type"])) $_GET["type"]="week";
if (!isset($_GET["uID"])||!haveRight("show_all_planning","1")) $_GET["uID"]=$_SESSION["glpiID"];
if (!isset($_GET["gID"])) $_GET["gID"]=0;
if (!isset($_GET["usertype"])) $_GET["usertype"]="user";

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
	$split=explode("-",$_GET["date"]);
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

echo "<div align='center'><form method=\"get\" name=\"form\" action=\"planning.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
echo "<td>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$_GET["type"]."&amp;uID=".$_GET["uID"]."&amp;date=$prev\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a>";
echo "</td>";
echo "<td>";
if (haveRight("show_all_planning","1")){
	echo "<input type='radio' id='radio_user' name='usertype' value='user' ".($_GET["usertype"]=="user"?"checked":"").">";
	$rand_user=dropdownUsers("uID",$_GET['uID'],"interface",1,1,$_SESSION["glpiactive_entity"]);
	echo "<hr>";
	echo "<input type='radio' id='radio_group' name='usertype' value='group' ".($_GET["usertype"]=="group"?"checked":"").">";
	$rand_group=dropdownValue("glpi_groups","gID",$_GET['gID'],1,$_SESSION["glpiactive_entity"]);
	echo "<hr>";
	echo "<input type='radio' id='radio_user_group' name='usertype' value='user_group' ".($_GET["usertype"]=="user_group"?"checked":"").">";
	echo $LANG["joblist"][3];


	echo "<script type='text/javascript' >\n";
	echo "Ext.onReady(function() {";
	echo "	Ext.get('dropdown_uID".$rand_user."').on('change',function() {window.document.getElementById('radio_user').checked=true;});";
	echo "	Ext.get('dropdown_gID".$rand_group."').on('change',function() {window.document.getElementById('radio_group').checked=true;});";
	echo "});";
	echo "</script>\n";
} else if (haveRight("show_group_planning","1")){
	echo "<select name='usertype'>";
	echo "<option value='user' ".($_GET['usertype']=='user'?'selected':'').">".$LANG["joblist"][1]."</option>";
	echo "<option value='user_group' ".($_GET['usertype']=='user_group'?'selected':'').">".$LANG["joblist"][3]."</option>";
	echo "</select>";
}
echo "</td>";
echo "<td align='right'>";
echo $LANG["common"][27].":</td><td>";
showDateFormItem("date",$_GET["date"],false);

echo "</td>";
echo "<td><select name='type'>";
echo "<option value='day' ".($_GET["type"]=="day"?" selected ":"").">".$LANG["planning"][5]."</option>";
echo "<option value='week' ".($_GET["type"]=="week"?" selected ":"").">".$LANG["planning"][6]."</option>";
echo "<option value='month' ".($_GET["type"]=="month"?" selected ":"").">".$LANG["planning"][14]."</option>";
echo "</select></td>";
echo "<td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $LANG["buttons"][7] ."\" /></td>";
echo "<td>";

echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.ical.php?uID=".$_GET['uID']."\"><span style='font-size:10px'>-".$LANG["planning"][12]."</span></a>";
echo "<br>";
// Todo recup l'url complete de glpi proprement, ? nouveau champs table config ?
echo "<a href=\"webcal://".$_SERVER['HTTP_HOST'].$CFG_GLPI["root_doc"]."/front/planning.ical.php?uID=".$_GET['uID']."\"><span style='font-size:10px'>-".$LANG["planning"][13]."</span></a>";

echo "</td>";	
echo "<td>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$_GET["type"]."&amp;uID=".$_GET["uID"]."&amp;date=$next\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a>";
echo "</td>";

echo "</tr>";
echo "</table></form></div>";

switch ($_GET["usertype"]){
	case "user" :
		$_GET['gID']=-1;
		break;
	case "group" :	
		$_GET['uID']=-1;
		break;
	case "user_group":
		$_GET['gID']="mine";
		break;
}
showPlanning($_GET['uID'],$_GET['gID'],$_GET["date"],$_GET["type"]);



commonFooter();

?>
