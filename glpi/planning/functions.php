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
// FUNCTIONS Planning


function titleTrackingPlanning(){
           GLOBAL  $lang,$HTMLRel;
	     
	     echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/reservation.png\" alt='' title=''></td><td><b><span class='icon_nav'>".$lang["planning"][3]."</span>";
		 echo "</b></td></tr></table>&nbsp;</div>";
}


function showTrackingPlanningForm($device_type,$id_device){

GLOBAL $cfg_install,$lang;

$query="select * from glpi_reservation_item where (device_type='$device_type' and id_device='$id_device')";
$db=new DB;
if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);
//echo "<form name='resa_form' method='post' action=".$cfg_install["root"]."/reservation/index.php>";
echo "<a href=\"".$cfg_install["root"]."/reservation/index.php?";
// Ajouter le matériel
if ($numrows==0){
echo "id_device=$id_device&amp;device_type=$device_type&amp;add=add\">".$lang["reservation"][7]."</a>";
}
// Supprimer le matériel
else {
echo "ID=".$db->result($result,0,"ID")."&amp;delete=delete\">".$lang["reservation"][6]."</a>";
}

}
}



function showAddPlanningTrackingForm($target,$job,$planID=-1){
	global $lang,$HTMLRel;
	
	$planning= new PlanningTracking;

	if ($planID!=-1)
		$planning->getFromDB($planID);
	else {
		$planning->getEmpty();
		$planning->fields["begin"]=date("Y-m-d")." 12:00:00";
		$planning->fields["end"]=date("Y-m-d")." 13:00:00";
	}

	
	$begin=strtotime($planning->fields["begin"]);
	$end=strtotime($planning->fields["end"]);
	
	$begin_date=date("Y-m-d",$begin);
	$end_date=date("Y-m-d",$end);
	$begin_hour=date("H",$begin);
	$end_hour=date("H",$end);
	$begin_min=date("i",$begin);
	$end_min=date("i",$end);

	echo "<div align='center'><form method='post' name=form action=\"$target\">";
	if ($planID!=-1)
	echo "<input type='hidden' name='ID' value='$planID'>";
	
	echo "<input type='hidden' name='id_tracking' value='$job'>";

	echo "<table class='tab_cadre' cellpadding='2'>";
	echo "<tr><th colspan='2'><b>";
	echo $lang["planning"][7];
	echo "</b></th></tr>";
	// Ajouter le job
	$j=new Job;
	$j->getfromDB($job,0);
	echo "<tr class='tab_bg_1'><td>".$lang["planning"][8].":	</td>";
	echo "<td>";
	echo "<b>".$j->contents."</b>";
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td>".$lang["planning"][10].":	</td>";
	echo "<td>";
	echo "<b>".$j->computername."</b>";
	echo "</td></tr>";

	if (!isAdmin($_SESSION["glpitype"]))
	echo "<input type='hidden' name='id_assign' value='".$_SESSION["glpiID"]."'>";
	else {
	echo "<tr class='tab_bg_2'><td>".$lang["planning"][9].":	</td>";
	echo "<td>";
	if ($planID==-1)
	dropdownValue("glpi_users","id_assign",$_SESSION["glpiID"]);
	else dropdownValue("glpi_users","id_assign",$planning->fields["id_assign"]);

	echo "</td></tr>";
	
	}


	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][10].":	</td><td>";
	showCalendarForm("form","begin_date",$begin_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][12].":	</td>";
	echo "<td>";
	echo "<select name='begin_hour'>";
	for ($i=0;$i<24;$i++){
	echo "<option value='$i'";
	if ($i==$begin_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='begin_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$begin_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][11].":	</td><td>";
	showCalendarForm("form","end_date",$end_date);
    echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["reservation"][13].":	</td>";
	echo "<td>";
	echo "<select name='end_hour'>";
	for ($i=0;$i<24;$i++){
	echo "<option value='$i'";
	if ($i==$end_hour) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo ":";
	echo "<select name='end_min'>";
	for ($i=0;$i<60;$i+=5){
	echo "<option value='$i'";
	if ($i==$end_min) echo " selected ";
	echo ">$i</option>";
	}
	echo "</select>";
	echo "</td></tr>";



	if ($planID==-1){
	echo "<tr class='tab_bg_2'>";
	echo "<td colspan='2'  valign='top' align='center'>";
	echo "<input type='submit' name='add_planning' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>\n";
	} else {
	echo "<tr class='tab_bg_2'>";
	echo "<td valign='top' align='center'>";
	echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
	echo "</td><td valign='top' align='center'>";
	echo "<input type='submit' name='edit_planning' value=\"".$lang["buttons"][14]."\" class='submit'>";
	echo "</td></tr>\n";
	}

	echo "</table>";
	echo "</form></div>";
}

function showPlanning($who,$when,$type){
	global $lang,$cfg_features;
	
	$date=split("-",$when);
	$time=mktime(1,0,0,$date[1],$date[2],$date[0]);
	$dayofweek=date("w",$time);

	echo "<div align='center'><table class='tab_cadre' width='800'>";
	// Print Headers
	echo "<tr>";
	switch ($type){
	case "week":
		for ($i=1;$i<=7;$i++){
		echo "<th>".$lang["calendarDay"][$i%7]."</th>";
		}
		
		
		break;
	case "day":
		echo "<th>".$lang["calendarDay"][$dayofweek%7]."</th>";
		break;
	}
	echo "</tr>";
	
	// Print Calendar by 15 mns
	$tmp=split(":",$cfg_features["planning_begin"]);
	$hour_begin=$tmp[0];
	$tmp=split(":",$cfg_features["planning_end"]);
	$hour_end=$tmp[0];
	for ($hour=$hour_begin;$hour<=$hour_end;$hour++){
		echo "<tr>";
	
		switch ($type){
		case "week":
			for ($i=1;$i<=7;$i++){
			displayplanning($who,date("Y-m-d",strtotime($when)+mktime(0,0,0,0,$i,0)-mktime(0,0,0,0,$dayofweek,0))." $hour:00:00",$type);
			}
		
			break;
		case "day":
			displayplanning($who,$when." $hour:00:00",$type);
			break;
		}
	echo "</tr>\n";
	
	}
	
	
	echo "</table></div>";

}

function displayplanning($who,$when,$type){
global $cfg_features,$HTMLRel,$lang;
$db=new DB;
$debut=$when;
$tmp=split(" ",$when);
$hour=split(":",$tmp[1]);

$ASSIGN="";
if ($who!=0)
$ASSIGN="id_assign='$who' AND";



$query="SELECT * from glpi_tracking_planning WHERE $ASSIGN (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL 59 MINUTE ) >= begin) OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL 59 MINUTE ) >= end) OR (begin <= '".$debut."' AND end > '".$debut."') OR (begin <= adddate( '". $debut ."' , INTERVAL 59 MINUTE ) AND end > adddate( '". $debut ."' , INTERVAL 59 MINUTE ))) ORDER BY begin";

$result=$db->query($query);

$job=new Job();

$interv=array();
$i=0;
if ($db->numrows($result)>0)
while ($data=$db->fetch_array($result)){
	$job->getFromDB($data["id_tracking"],0);
	
	
	$interv[$i]["id_tracking"]=$data["id_tracking"];
	$interv[$i]["id_assign"]=$data["id_assign"];
	$interv[$i]["ID"]=$data["ID"];
	$interv[$i]["begin"]=$data["begin"];
	$interv[$i]["end"]=$data["end"];
	$interv[$i]["content"]=substr($job->contents,0,$cfg_features["cut"]);
	$interv[$i]["device"]=$job->computername;
	$i++;
}

echo "<td class='tab_bg_3' width='12%' valign='top' >";
echo "<b>".display_time($hour[0]).":00</b><br>";


if (count($interv)>0)
foreach ($interv as $key => $val){
if($type=='day'){
echo "<div style=' margin:auto; text-align:center; border:1px dashed #cccccc; background-color: #d7d7d2; font-size:9px; width:80%;'>";
echo "<a  href='".$HTMLRel."planning/planning-add-form.php?edit=edit&amp;job=".$val["id_tracking"]."&amp;ID=".$val["ID"]."'><img src='$HTMLRel/pics/edit.png' alt='edit'></a>";
echo "<a  href='".$HTMLRel."tracking/tracking-followups.php?ID=".$val["id_tracking"]."'>";
echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ".$val["device"];
if ($who==0){
echo "<br>";
echo $lang["planning"][9]." ".getUserName($val["id_assign"]);
} 
echo "</a>";
echo "<br>";
echo $val["content"];
echo "";

echo "</div><br>";

}else{
echo "<div class='planning' >";
echo "<a  href='".$HTMLRel."tracking/tracking-followups.php?ID=".$val["id_tracking"]."'>";
echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": <br>".$val["device"];
if ($who==0){
echo "<br>";
echo $lang["planning"][9]." ".getUserName($val["id_assign"]);
} 
echo "</a>";
echo "</div>";
}


}

echo "</td>";

}

function display_time($time){

$time=round($time);
if ($time<10&&strlen($time)) return "0".$time;
else return $time;

}


function deletePlanningTracking($ID){
	// Delete a Planning Tracking

	$resa = new PlanningTracking;
	if ($resa->getfromDB($ID))
	if (isset($resa->fields["id_assign"])&&($resa->fields["id_assign"]==$_SESSION["glpiID"]||isAdmin($_SESSION["glpitype"])))
	return $resa->deleteFromDB($ID);

	return false;
}


function addPlanningTracking($input,$target){
	// Add a Planning
	$resa = new PlanningTracking;
	
  // set new date.
   $resa->fields["id_tracking"] = $input["id_tracking"];
   $resa->fields["id_assign"] = $input["id_assign"];
   $resa->fields["begin"] = $input["begin_date"]." ".$input["begin_hour"].":".$input["begin_min"].":00";
   $resa->fields["end"] = $input["end_date"]." ".$input["end_hour"].":".$input["end_min"].":00";

	if (!$resa->test_valid_date()){
		$resa->displayError("date",$input["id_assign"],$target);
		return false;
	}
	
	if ($resa->is_alreadyplanned()){
		$resa->displayError("is_res",$input["id_assign"],$target);
		return false;
	}

	if ($input["id_tracking"]>0)
		return $resa->addToDB();
	else return true;
}



function updatePlanningTracking($input,$target,$item){
global $lang;
	// Update a Planning Tracking

	$ri = new PlanningTracking;
	$ri->getFromDB($input["ID"]);

	// Get all flags and fill with 0 if unchecked in form
	foreach ($ri->fields as $key => $val) {
		if (eregi("\.*flag\.*",$key)) {
			if (!isset($input[$key])) {
				$input[$key]=0;
			}
		}
	}	

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$ri->fields) && $ri->fields[$key] != $input[$key]) {
			$ri->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	$ri->fields["begin"]=$_POST["begin"];
	$ri->fields["end"]=$_POST["end"];

	if (!$ri->test_valid_date()){
		$ri->displayError("date",$item,$target);
		return false;
	}

	if ($ri->is_alreadyplanned()){
		$ri->displayError("is_res",$item,$target);
		return false;
	}

	if (isset($updates))
		$ri->updateInDB($updates);
	return true;
}

?>