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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------



$NEEDED_ITEMS = array('device', 'enterprise', 'stat', 'tracking', 'user');


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['Menu'][13],$_SERVER['PHP_SELF'],"maintain","stat");

checkRight("statistic","1");

if(empty($_POST["date1"])&&empty($_POST["date2"])) {

	if (isset($_GET["date1"])) {
		$_POST["date1"]=$_GET["date1"];
	}
	if (isset($_GET["date2"])) {
		$_POST["date2"]=$_GET["date2"];
	}
}

if (!empty($_POST["date1"])&&!empty($_POST["date2"])&&strcmp($_POST["date2"],$_POST["date1"])<0){
	$tmp=$_POST["date1"];
	$_POST["date1"]=$_POST["date2"];
	$_POST["date2"]=$tmp;
}

$cleantarget=preg_replace("/[&]date[12]=[0-9-]*/","",$_SERVER['QUERY_STRING']);
$cleantarget=preg_replace("/[&]*id=([0-9]+[&]{0,1})/","",$cleantarget);
$cleantarget=preg_replace("/&/","&amp;",$cleantarget);

$job=new Job();
$next=0;
$prev=0;
$title="";
switch($_GET["type"]){
	case "technicien":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_users",$_GET["id"]);
		$prev=getPreviousItem("glpi_users",$_GET["id"]);
		$title=$LANG['stats'][16].": ".getAssignName($_GET["id"],USER_TYPE,1);
		break;
	case "technicien_followup":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_users",$_GET["id"]);
		$prev=getPreviousItem("glpi_users",$_GET["id"]);
		$title=$LANG['stats'][16].": ".getAssignName($_GET["id"],USER_TYPE,1);
		break;
	case "enterprise":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_suppliers",$_GET["id"]);
		$prev=getPreviousItem("glpi_suppliers",$_GET["id"]);
		$title=$LANG['stats'][44].": ".getAssignName($_GET["id"],ENTERPRISE_TYPE,1);
		break;
	case "user":
		$val1=$_GET["id"];
		$val2="";
		$job->fields["users_id"]=$_GET["id"];

		$next=getNextItem("glpi_users",$_GET["id"]);
		$prev=getPreviousItem("glpi_users",$_GET["id"]);
		$title=$LANG['stats'][20].": ".$job->getAuthorName(1);
		break;
	case "users_id_recipient":
		$val1=$_GET["id"];
		$val2="";
		$job->fields["users_id"]=$_GET["id"];

		$next=getNextItem("glpi_users",$_GET["id"]);
		$prev=getPreviousItem("glpi_users",$_GET["id"]);
		$title=$LANG['stats'][20].": ".$job->getAuthorName(1);
		break;
	case "ticketcategories_id":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_ticketcategories",$_GET["id"]);
		$prev=getPreviousItem("glpi_ticketcategories",$_GET["id"]);
		$title=$LANG['common'][36].": ".getDropdownName("glpi_ticketcategories",$_GET["id"]);
		break;
	case "group":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_groups",$_GET["id"]);
		$prev=getPreviousItem("glpi_groups",$_GET["id"]);
		$title=$LANG['common'][35].": ".getDropdownName("glpi_groups",$_GET["id"]);
		break;
	case "groups_id_assign":
		$val1=$_GET["id"];
		$val2="";

		$next=getNextItem("glpi_groups",$_GET["id"]);
		$prev=getPreviousItem("glpi_groups",$_GET["id"]);
		$title=$LANG['common'][35].": ".getDropdownName("glpi_groups",$_GET["id"]);
		break;
	case "priority":
		$val1=$_GET["id"];
		$val2="";
		$next=$prev=0;
		if ($val1<5) $next=$val1+1;
		if ($val1>1) $prev=$val1-1;
		$title=$LANG['joblist'][2].": ".getPriorityName($_GET["id"]);
		break;
	case "usertitles_id":
		$val1=$_GET["id"];
		$val2="";
		$next=$prev=0;
		$next=getNextItem("glpi_usertitles",$_GET["id"]);
		$prev=getPreviousItem("glpi_usertitles",$_GET["id"]);
		$title=$LANG['users'][1].": ".getDropdownName("glpi_usertitles",$_GET["id"]);
		break;
	case "usercategories_id":
		$val1=$_GET["id"];
		$val2="";
		$next=$prev=0;
		$next=getNextItem("glpi_usercategories",$_GET["id"]);
		$prev=getPreviousItem("glpi_usercategories",$_GET["id"]);
		$title=$LANG['users'][2].": ".getDropdownName("glpi_usercategories",$_GET["id"]);
		break;
	case "requesttypes_id":
		$val1=$_GET["id"];
		$val2="";
		$next=$prev=0;
		if ($val1<6) $next=$val1+1;
		if ($val1>0) $prev=$val1-1;
		$title=$LANG['job'][44].": ".getDropdownName('glpi_requesttypes', $_GET["id"]);
		break;
	case "device":
		$val1=$_GET["id"];
		$val2=$_GET["champ"];

		$device_table = getDeviceTable($_GET["champ"]);

		$next=getNextItem($device_table,$_GET["id"],'','designation');
		$prev=getPreviousItem($device_table,$_GET["id"],'','designation');

		$query = "SELECT designation
			FROM ".$device_table."
			WHERE id='".$_GET['id']."'";
		$result=$DB->query($query);

		$title=$LANG['Menu'][13].": ".$DB->result($result,0,"designation");

		break;
	case "comp_champ":
		$val1=$_GET["id"];
		$val2=$_GET["champ"];

		$table=getTableNameForForeignKeyField($_GET["champ"]);


		$next=getNextItem($table,$_GET["id"]);
		$prev=getPreviousItem($table,$_GET["id"]);
		$title=$LANG['stats'][26].": ".getDropdownName($table,$_GET["id"]);
	break;

}


	echo "<div align='center'>";
	echo "<table class='tab_cadre_navigation'>";
	echo "<tr>";
	echo "<td>";
	if ($prev>0) echo "<a href='".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;id=$prev'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG['buttons'][12]."' title='".$LANG['buttons'][12]."'></a>";
	echo "</td>";

	echo "<td width='400' align='center'><b>$title</b></td>";
	echo "<td>";
	if ($next>0) echo "<a href='".$_SERVER['PHP_SELF']."?$cleantarget&amp;date1=".$_POST["date1"]."&amp;date2=".$_POST["date2"]."&amp;id=$next'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG['buttons'][11]."' title='".$LANG['buttons'][11]."'></a>";
	echo "</td>";

	echo "</tr>";
	echo "</table></div><br>";


$target=preg_replace("/&/","&amp;",$_SERVER["REQUEST_URI"]);

echo "<div align='center'><form method=\"post\" name=\"form\" action=\"".$target."\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $LANG['search'][8]." :</td><td>";
showDateFormItem("date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" value=\"". $LANG['buttons'][7] ."\"></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$LANG['search'][9]." :</td><td>";
showDateFormItem("date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";



///////// Stats nombre intervention
// Total des interventions
$entrees_total=constructEntryValues("inter_total",$_POST["date1"],$_POST["date2"],$_GET["type"],$val1,$val2);
if (count($entrees_total)>0)
	graphBy($entrees_total,$LANG['stats'][5],$LANG['stats'][35],1,"month");

	// Total des interventions r�olues
	$entrees_solved=constructEntryValues("inter_solved",$_POST["date1"],$_POST["date2"],$_GET["type"],$val1,$val2);
if (count($entrees_solved)>0)
	graphBy($entrees_solved,$LANG['stats'][11],$LANG['stats'][35],1,"month");

	//Temps moyen de resolution d'intervention
	$entrees_avgtime=constructEntryValues("inter_avgsolvedtime",$_POST["date1"],$_POST["date2"],$_GET["type"],$val1,$val2);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG['stats'][6],$LANG['job'][21],0,"month");

	//Temps moyen d'intervention r�l
	$entrees_avgtime=constructEntryValues("inter_avgrealtime",$_POST["date1"],$_POST["date2"],$_GET["type"],$val1,$val2);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG['stats'][25],$LANG['stats'][33],0,"month");

	//Temps moyen de prise en compte de l'intervention
	$entrees_avgtime=constructEntryValues("inter_avgtakeaccount",$_POST["date1"],$_POST["date2"],$_GET["type"],$val1,$val2);
if (count($entrees_avgtime)>0)
	graphBy($entrees_avgtime,$LANG['stats'][30],$LANG['job'][21],0,"month");

	commonFooter();

?>
