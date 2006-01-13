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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS Tracking System


function titleTracking(){
           GLOBAL  $lang,$HTMLRel;
	// titre
        echo "<div align='center'><table border='0'><tr><td>\n";
        echo "<img src=\"".$HTMLRel."pics/suivi-intervention.png\" alt=''></td><td><span class='icon_nav'>".$lang["tracking"][0]."</span>\n";
        echo "</td></tr></table>&nbsp;</div>\n";

}

/**
* Print "onglets" (on the top of items forms)
*
* Print "onglets" for a better navigation.
*
*@param $target filename : The php file to display then
*@param $withtemplate bool : template or basic computers
*@param $actif witch of all the "onglets" is selected
*
*@return nothing (diplays)
*
**/
function showTrackingOnglets($target){
	global $lang,$HTMLRel;
	
	if (preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li class='actif'><span style='float: left;display: block;color: #666;text-decoration: none;padding: 3px;'>".$lang["job"][38]." $ID</span></li>";
	
	echo "<li class='invisible'>&nbsp;</li>";
	if (isAdmin($_SESSION['glpitype'])){
		echo "<li onClick=\"showAddFollowup(); Effect.Appear('viewfollowup');\" id='addfollowup'><a href='#'>".$lang["job"][29]."</span></a></li>";

		echo "<li class='invisible'>&nbsp;</li>";

		$next=getNextItem("glpi_tracking",$ID);
		$prev=getPreviousItem("glpi_tracking",$ID);
		$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
		if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
		}
	}
	echo "</ul></div>";
	
}


function commonTrackingListHeader(){
global $lang;
		echo "<tr><th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["common"][1]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
}

function getTrackingPrefs ($ID) {
	// Returns users preference settings for job tracking
	// Currently only supports sort order

	$db = new DB;
	$query = "SELECT tracking_order FROM glpi_users WHERE (ID = '$ID')";
	$result = $db->query($query);
	if ($result&&$db->numrows($result)==1)
	$tracking_order = $db->result($result, 0, "tracking_order");
	else $tracking_order="yes";

	if($tracking_order == "yes")
	{
		$prefs["order"] = "ASC";
	} 
	else
	{
		$prefs["order"] = "DESC";
	}

	return $prefs;
}

function showCentralJobList($target,$start) {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;
		
	$prefs = getTrackingPrefs($_SESSION["glpiID"]);


	
	$query = "SELECT ID FROM glpi_tracking WHERE (assign = '".$_SESSION["glpiID"]."' AND assign_type='".USER_TYPE."') AND status <> 'old_done' AND status <> 'old_notdone' ORDER BY date ".$prefs["order"]."";
	
	$lim_query = " LIMIT ".$start.",".$cfg_features["list_limit"]."";	

	$db = new DB;
	$result = $db->query($query);
	$numrows = $db->numrows($result);

	$query .= $lim_query;
	
	$result = $db->query($query);
	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0) {

		echo "<div align='center'><br><table class='tab_cadrehov' width='400'>";
		
		echo "<tr><th colspan='5'><b>".$lang["central"][9]."</b></th>";
		echo "<tr><th></th>";
		echo "<th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			showJobVeryShort($ID);
			$i++;
		}
		echo "</table></div>";
	}
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre' width='90%'>";
		echo "<tr><th>".$lang["joblist"][8]."</th></tr>";

		echo "</table>";
		echo "</div><br>";
	}
}


function showOldJobListForItem($username,$item_type,$item) {
	// $item is required
	// affiche toutes les vielles intervention pour un $item donné. 


	GLOBAL $cfg_layout, $cfg_install, $lang,$HTMLRel;
		
	// Form to delete old item
	if (isAdmin($_SESSION["glpitype"])){
		echo "<form method='post' action=\"".$_SERVER["PHP_SELF"]."?ID=$item\">";
		echo "<input type='hidden' name='ID' value='$item'>";
		}



	$prefs = getTrackingPrefs($_SESSION["glpiID"]);	
	
$where = "(status = 'old_done' OR status = 'old_notdone')";	
$query = "SELECT ID FROM glpi_tracking WHERE $where and (device_type = '$item_type' and computer = '$item') ORDER BY date ".$prefs["order"]."";
	

	$db = new DB;
	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%'>";
		echo "<tr><th colspan=9>".$number." ".$lang["job"][18]."  ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";
		while ($i < $number)
		{
			$ID = $db->result($result, $i, "ID");
			showJobShort($ID, 0);
			$i++;
		}

		echo "</table></div>";

		if (isAdmin($_SESSION["glpitype"])){
		echo "<br><div align='center'>";
		
		echo "<table class ='delete-old-job' cellpadding='5' width='90%'>";
		echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt='' ></td><td><a href='".$_SERVER["PHP_SELF"]."?select=all&amp;ID=$item'>".$lang["buttons"][18]."</a></td>";
			
		echo "<td>/</td><td><a href='".$_SERVER["PHP_SELF"]."?select=none&amp;ID=$item'>".$lang["buttons"][19]."</a>";
		echo "</td><td>";
		echo "<input type='submit' value=\"".$lang["buttons"][17]."\" name='delete_inter' class='submit'></td>";
		echo "<td width='75%'>&nbsp;</td></table></div>";
		}
	} 
	else
	{
		echo "<br><div align='center'>";
		echo "<table border='0' width='90%' class='tab_cadre'>";
		echo "<tr><th>".$lang["joblist"][22]."</th></tr>";

		if ($item)
		{
		          echo "<tr><td align='center' class='tab_bg_1'>";
		}
		echo "</table>";
		echo "</div><br>";
	}

	// End form for delete item
	if (isAdmin($_SESSION["glpitype"]))
	echo "</form>";

}

function showJobListForItem($username,$item_type,$item) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donné. 

	GLOBAL $cfg_layout, $cfg_install, $lang;
		
	$prefs = getTrackingPrefs($_SESSION["glpiID"]);
	
$where = "(status = 'new' OR status= 'assign' OR status='plan')";	
$query = "SELECT ID FROM glpi_tracking WHERE $where and (computer = '$item' and device_type= '$item_type') ORDER BY date ".$prefs["order"]."";

	$db = new DB;
	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%'>";
		echo "<tr><th colspan='9'>".$number." ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";

		if ($item)
		{
			echo "<tr><td align='center' class='tab_bg_2' colspan='9'>";
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&amp;device_type=$item_type\"><strong>";
			echo $lang["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}

		commonTrackingListHeader();

		while ($i < $number)
		{
			$ID = $db->result($result, $i, "ID");
			showJobShort($ID, 0);
			$i++;
		}
		echo "</table></div>";
	} 
	else
	{
		echo "<br><div align='center'>";
		echo "<table border='0' width='90%' class='tab_cadre'>";
		echo "<tr><th>".$lang["joblist"][8]."</th></tr>";

		if ($item)
		{
			 
			  echo "<tr><td align='center' class='tab_bg_2' colspan='8'>";
			  echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&amp;device_type=$item_type\"><strong>";
			  echo $lang["joblist"][7];
			  echo "</strong></a>";
			  echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}


function showJobShort($ID, $followups) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$isadmin=isAdmin($_SESSION['glpitype']);

	if ($job->getfromDB($ID,0))
	{
		$bgcolor=$cfg_layout["priority_".$job->fields["priority"]];

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'  >ID: ".$job->ID."<br>";
		echo "<img src=\"".$HTMLRel."pics/".$job->fields["status"].".png\" alt='".getStatusName($job->fields["status"])."' title='".getStatusName($job->fields["status"])."'>";
		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<td width='100'  ><small>".$lang["joblist"][11].":<br>&nbsp;".convDateTime($job->fields["date"])."</small></td>";

		}
		else
		{
			if ($isadmin){
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
			echo "<br><input type='checkbox' name='todel[".$job->ID."]' value='1' $sel>";
			//echo "<img src=\"".$HTMLRel."pics/delete2.png\">";
			}
			echo "</td>";
			echo "<td width='130' ><small>".$lang["joblist"][11].":<br>&nbsp;".convDateTime($job->fields["date"])."<br>";
			echo "<i>".$lang["joblist"][12].":<br>&nbsp;".convDateTime($job->fields["closedate"])."</i>";
			if ($job->fields["realtime"]>0) echo "<br>".$lang["job"][20].": <br>".getRealtime($job->fields["realtime"]);
			echo "</small></td>";
		}

		echo "<td align='center' bgcolor='$bgcolor'><strong>".getPriorityName($job->fields["priority"])."</strong></td>";
		
		echo "<td align='center'  >";

		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<strong>".$job->getAuthorName(1)."</strong>";
		else
		echo "<strong>".$job->getAuthorName()."</strong>";

		echo "</td>";

		if ($job->fields["assign"] == 0)
		{
			echo "<td align='center' >[Nobody]</td>"; 
	    	}
		else
		{
			echo "<td align='center' >";
			if (strcmp($_SESSION["glpitype"],"post-only")!=0)
			echo $job->getAssignName(1);
			else
			echo "<strong>".$job->getAssignName()."</strong>";

			echo "</td>";
		}    
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0){
			echo "<td align='center' ";
			$m= new CommonItem;
			if ($m->getfromDB($job->fields["device_type"],$job->fields["computer"]))
			if (isset($m->obj->fields["deleted"])&&$m->obj->fields["deleted"]=='Y')
			echo "class='tab_bg_1_2'";
			echo ">";
			echo $m->getType()."<br>";
			echo "<strong>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</strong>";

			echo "</td>";
		}
		else
		echo "<td  align='center' ><strong>$job->computername (".$job->fields["computer"].")</strong></td>";


		echo "<td  align='center' ><strong>".getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"])."</strong></td>";
		
		$stripped_content=$job->fields["contents"];
		if (!$followups) $stripped_content =substr($job->fields["contents"],0,$cfg_features["cut"]);
		echo "<td align='left'><strong>".$stripped_content."</strong>";
		if ($followups)
		{
			showFollowupsShort($job->ID);
		}

		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center' >";
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-info-form.php?ID=$job->ID\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
		echo "<a href=\"".$cfg_install["root"]."/helpdesk.php?show=user&amp;ID=$job->ID\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups($isadmin).")&nbsp;<br>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
    echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

function showJobVeryShort($ID) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;

	if ($job->getfromDB($ID,0))
	{
		$bgcolor=$cfg_layout["priority_".$job->fields["priority"]];
		if ($job->fields["status"] == "new")
		{
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->ID."</td>";

		}
		else
		{
 			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->ID;
			echo "</td>";
		}

	
		echo "<td align='center'  >";

		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<strong>".$job->getAuthorName(1)."</strong>";
		else
		echo "<strong>".$job->getAuthorName()."</strong>";

		echo "</td>";

		if (strcmp($_SESSION["glpitype"],"post-only")!=0){
			echo "<td align='center' ";
			$m= new CommonItem;
			$m->getfromDB($job->fields["device_type"],$job->fields["computer"]);
			if (isset($m->obj)&&isset($m->obj->fields["deleted"])&&$m->obj->fields["deleted"]=='Y')
			echo "class='tab_bg_1_2'";
			echo ">";
			echo $m->getType()."<br>";
			echo "<strong>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</strong>";

			echo "</td>";
		}
		else
		echo "<td  align='center' ><strong>$job->computername (".$job->fields["computer"].")</strong></td>";

		$stripped_content =substr($job->fields["contents"],0,$cfg_features["cut"]);
		echo "<td ><strong>".$stripped_content."</strong>";
		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center' >";
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-info-form.php?ID=$job->ID\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
		echo "<a href=\"".$cfg_install["root"]."/helpdesk.php?show=user&amp;ID=$job->ID\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
    echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

function postJob($device_type,$ID,$author,$status,$priority,$isgroup,$uemail,$emailupdates,$contents,$assign=0,$realtime=0,$assign_type=USER_TYPE) {
	// Put Job in database

	GLOBAL $cfg_install, $cfg_features, $cfg_layout,$lang;
	
	$job = new Job;

	if (!$isgroup) {
		$job->isgroup = "no";
	} else {
		$job->isgroup = "yes";
	}
	if ($assign>0&&$status=="new")
		$job->fields["status"] = "assign";
	else 
		$job->fields["status"] = $status;
	$job->fields["author"] = $author;
	$job->fields["device_type"] = $device_type;
	if ($device_type==0)
	$job->fields["computer"]=0;
	else 
	$job->fields["computer"] = $ID;
	$job->fields["contents"] = $contents;
	$job->fields["priority"] = $priority;
	$job->fields["uemail"] = $uemail;
	if (empty($emailupdates)) $emailupdates="no";
	$job->fields["emailupdates"] = $emailupdates;

	$user=new User;
	$user->getfromDBbyID($author);
	if ($emailupdates=="yes"&&empty($uemail)){
		$job->fields["uemail"]=$user->fields["email"];
	}

	$job->fields["assign"] = $assign;
	$job->fields["assign_type"] = $assign_type;

	if ($cfg_features['auto_assign']&&$assign==0){
		$ci=new CommonItem;
		$ci->getFromDB($device_type,$ID);
		if (isset($ci->obj->fields['tech_num'])&&$ci->obj->fields['tech_num']!=0){
			$job->fields["assign"] = $ci->obj->fields['tech_num'];
			$job->fields["assign_type"] = USER_TYPE;
		}
	}

	$job->fields["realtime"] = $realtime;
	$job->fields["date"] = date("Y-m-d H:i:s");

	// ajout suite  à tracking sur tous les items 

	switch ($device_type) {
	case GENERAL_TYPE :
	$item = "";
	break;
	case COMPUTER_TYPE :
	$item = "computers";
	break;
	case NETWORKING_TYPE :
	$item = "networking";
	break;
	case PRINTER_TYPE :
	$item = "printers";
	break;
	case MONITOR_TYPE :
	$item = "monitors";
	break;
	case PERIPHERAL_TYPE :
	$item = "peripherals";
	break;
	case SOFTWARE_TYPE :
	$item = "software";
	break;
	}
	
	
	if ($tID=$job->addToDB()) {
		$job->fields["ID"]=$tID;
		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
		$input=array();
		$input["name"]=$lang["tracking"][24]." $tID";
		
		$docID=addDocument($input);
		addDeviceDocument($docID,TRACKING_TYPE,$tID);
		}
		
		
		
		// Log this event
		logEvent($tID,"tracking",4,"tracking",getUserName($author)." ".$lang["log"][20]);
		
		// Processing Email
		if ($cfg_features["mailing"])
		{
			$job->fields=stripslashes_deep($job->fields);
			$mail = new Mailing("new",$job,$user);
			$mail->send();
		}
		return true;	
	} else {
		return false;
	}
}


function addFormTracking ($device_type=0,$ID=0,$author,$assign,$target,$error,$searchauthor='') {
	// Prints a nice form to add jobs

	GLOBAL $cfg_layout, $lang,$cfg_features,$REFERER;

	if (!empty($error)) {
		echo "<div align='center'><strong>$error</strong></div>";
	}
	echo "<form method='get' action='$target'>";
	echo "<div align='center'>";

	if ($device_type!=0){
		echo "<input type='hidden' name='referer' value='$REFERER'>";
		echo "<p><a class='icon_consol' href='$REFERER'>".$lang["buttons"][13]."</a></p>";
	}	
	echo "<table class='tab_cadre'><tr><th colspan='2'>".$lang["job"][13].": <br>";
	if ($device_type!=0){
		$m=new CommonItem;
		$m->getfromDB($device_type,$ID);
		echo $m->getType()." - ".$m->getNameID();
	}
	echo "</th></tr>";

	echo "<tr class='tab_bg_1' align='center'><td>".$lang["joblist"][1].":</td>";
	echo "<td align='center'>".convDateTime(date("Y-m-d H:i:s"))."</td></tr>";

	if ($device_type==0){
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>".$lang["help"][24].": </td>";
		echo "<td align='center'>";
		dropdownTrackingDeviceType("device_type",$device_type);
		echo "</td></tr>";
	}


	echo "<tr><td class='tab_bg_2' align='center'>".$lang["joblist"][0].":</td>";
	echo "<td align='center' class='tab_bg_2'><select name='status'>";
	echo "<option value='new' ";
	if ($_GET["status"]=="new") echo "selected";
	echo ">".$lang["job"][14]."</option>";
	echo "<option value='old_done' ";
	if ($_GET["status"]=="old_done") echo "selected";	
	echo ">".$lang["job"][15]."</option>";
	echo "</select></td></tr>";

			echo "<tr>";
			echo "<td class='tab_bg_2' align='center'>";
			echo $lang["job"][20].":</td>";
			echo "<td align='center' class='tab_bg_2'><select name='hour'>";
			for ($i=0;$i<100;$i++){
			$selected="";
			if (isset($_GET["hour"])&&$_GET["hour"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
			}			
		
			echo "</select>".$lang["job"][21]."&nbsp;&nbsp;";
			echo "<select name='minute'>";
			for ($i=0;$i<60;$i++){
			$selected="";
			if (isset($_GET["minute"])&&$_GET["minute"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
			}
			echo "</select>".$lang["job"][22]."&nbsp;&nbsp;";
			echo "</td></tr>";


	echo "<tr><td class='tab_bg_2' align='center'>".$lang["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2'>";
	dropdownPriority("priority",3);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][3].":</td>";
	
	echo "<td align='center'>";

	dropdownAllUsers("user",$author);
	echo "</td></tr>";
	

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][15].":</td>";
	
	echo "<td align='center'>";
	dropdownUsers("assign",$assign);
	echo "</td></tr>";

	if($cfg_features["mailing"] == 1)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".$lang["help"][8].":</td>";
		echo "<td align='center'>	<select name='emailupdates'>";
		echo "<option value='no'>".$lang["help"][9]."";
		echo "<option value='yes' selected>".$lang["help"][10]."";
		echo "</select>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".$lang["help"][11].":</td>";
		echo "<td>	";
		echo "<input type='text' size='30' name='uemail'>";
		echo "</td></tr>";
		
	}

	echo "<tr><th colspan='2' align='center'>".$lang["job"][11].":";
	if ($device_type!=0){
	echo "<input type='hidden' name='ID' value=\"$ID\">";
	echo "<input type='hidden' name='device_type' value=\"$device_type\">";
	}

	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='2' align='center'><textarea cols='60' rows='14'  name='contents'></textarea></td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='2' align='center'>";
	echo "<input type='submit' value=\"".$lang["buttons"][2]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></div></form>";

}

function getRealtime($realtime){
		global $lang;	
		$output="";
		$hour=floor($realtime);
		if ($hour>0) $output.=$hour." ".$lang["job"][21]." ";
		$output.=round((($realtime-floor($realtime))*60))." ".$lang["job"][22];
		return $output;
		}

function searchFormTracking($report=0,$target,$start="",$status="new",$author=0,$assign=0,$assign_type=0,$category=0,$priority=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel,$phproot;

	if ($report==1){
		$option["comp.ID"]				= $lang["computers"][31];
		$option["comp.name"]				= $lang["computers"][7];
		$option["glpi_dropdown_locations.name"]			= $lang["computers"][10];
		$option["glpi_type_computers.name"]				= $lang["computers"][8];
		$option["glpi_dropdown_model.name"]				= $lang["computers"][50];
		$option["glpi_dropdown_os.name"]				= $lang["computers"][9];
		$option["processor.designation"]			= $lang["computers"][21];
		$option["comp.serial"]			= $lang["computers"][17];
		$option["comp.otherserial"]			= $lang["computers"][18];
		$option["ram.designation"]			= $lang["computers"][23];
		$option["iface.designation"]			= $lang["computers"][26];
		$option["sndcard.designation"]			= $lang["computers"][33];
		$option["gfxcard.designation"]			= $lang["computers"][34];
		$option["moboard.designation"]			= $lang["computers"][35];
		$option["hdd.designation"]			= $lang["computers"][36];
		$option["comp.comments"]			= $lang["computers"][19];
		$option["comp.contact"]			= $lang["computers"][16];
		$option["comp.contact_num"]		        = $lang["computers"][15];
		$option["comp.date_mod"]			= $lang["computers"][11];
		$option["glpi_networking_ports.ifaddr"] = $lang["networking"][14];
		$option["glpi_networking_ports.ifmac"] = $lang["networking"][15];
		$option["glpi_dropdown_netpoint.name"]			= $lang["networking"][51];
		$option["glpi_enterprises.name"]			= $lang["common"][5];
		$option["resptech.name"]			=$lang["common"][10];
	}
	echo "<form method=get name=\"form\" action=\"".$_SERVER["PHP_SELF"]."\">";
	
	
	echo "<div align='center'>";
				
	echo "<table border='0' width='900' class='tab_cadre'>";

	
	echo "<tr><th colspan='5'><strong>".$lang["search"][0].":</strong></th></tr>";



	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$lang["joblist"][0].":";
	echo "<select name='status'>";
	echo "<option value='new' ".($status=="new"?" selected ":"").">".$lang["joblist"][9]."</option>";
	echo "<option value='assign' ".($status=="assign"?" selected ":"").">".$lang["joblist"][18]."</option>";
	echo "<option value='plan' ".($status=="plan"?" selected ":"").">".$lang["joblist"][19]."</option>";
	echo "<option value='waiting' ".($status=="waiting"?" selected ":"").">".$lang["joblist"][26]."</option>";
	echo "<option value='old_done' ".($status=="old_done"?" selected ":"").">".$lang["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($status=="old_notdone"?" selected ":"").">".$lang["joblist"][17]."</option>";
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$lang["joblist"][24]."</option>";	
	echo "<option value='process' ".($status=="process"?"selected":"").">".$lang["joblist"][21]."</option>";
	echo "<option value='old' ".($status=="old"?"selected":"").">".$lang["joblist"][25]."</option>";	
	echo "<option value='all' ".($status=="all"?"selected":"").">".$lang["joblist"][20]."</option>";
	echo "</select></td>";

	echo "<td colspan='2' align='center'>".$lang["joblist"][2].":&nbsp;";
	dropdownPriority("priority",$priority,1);
	echo "</td>";

	echo "<td colspan='1' align='center'>".$lang["tracking"][20]."&nbsp;:&nbsp;";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";

	echo "</tr>";
	echo "<tr class='tab_bg_1'>";

	echo "<td align='center' colspan='2'>";
	echo "<table border='0'><tr><td>".$lang["common"][1].":</td><td>";
	dropdownAllItems("item",$type);
	echo "</td></tr></table>";
	echo "</td>";
	echo "<td  colspan='2' align='center'>".$lang["job"][5]."&nbsp;:&nbsp;";
	dropdownAssign($assign,$assign_type,"attrib");
	echo "</td>";
	echo "<td  colspan='1' align='center'>".$lang["joblist"][3]."&nbsp;:&nbsp;";
	dropdownUsersTracking("author",$author,"author");
	echo "</td>";

	echo "</tr>";

	if ($report){
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center' colspan='5'>";
		$selected="";
		if ($_GET["only_computers"]) $selected="checked";
		echo "<input type='checkbox' name='only_computers' value='1' $selected>".$lang["reports"][24].":&nbsp;";

		echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
		echo "&nbsp;";
		echo $lang["search"][10]."&nbsp;";
	
		echo "<select name='field' size='1'>";
        	echo "<option value='all' ";
		if($field == "all") echo "selected";
		echo ">".$lang["search"][7]."</option>";
        	reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if($key == $field) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		echo "</td></tr>";
	}
if($report)	{
	echo "<tr class='tab_bg_1'><td>".$lang["reports"][60].":</td><td align='center' colspan='2'>".$lang["search"][8].":&nbsp;";
	showCalendarForm("form","date1",$date1);
	echo "</td><td align='center' colspan='1'>";
	echo $lang["search"][9].":&nbsp;";
	showCalendarForm("form","date2",$date2);
	echo "</td><td align='center'>&nbsp;</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["reports"][61].":</td><td align='center' colspan='2'>".$lang["search"][8].":&nbsp;";
	showCalendarForm("form","enddate1",$enddate1);
	echo "</td><td align='center' colspan='1'>";
	echo $lang["search"][9].":&nbsp;";
	showCalendarForm("form","enddate2",$enddate2);
	echo "</td><td align='center'><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'></td></tr>";
}
else {
	echo "<tr  class='tab_bg_1'>";

	echo "<td align='center' colspan='2'>";
 $elts=array("both"=>$lang["joblist"][6]." / ".$lang["job"][7],"contents"=>$lang["joblist"][6],"followup" => $lang["job"][7],"ID"=>"ID");
 echo "<select name='field2'>";
 foreach ($elts as $key => $val){
 $selected="";
 if ($field2==$key) $selected="selected";
 echo "<option value=\"$key\" $selected>$val</option>";
 
 }
 echo "</select>";
 
 
 
	 echo "&nbsp;".$lang["search"][2]."&nbsp;";
	echo "<input type='text' size='15' name=\"contains2\" value=\"".$contains2."\">";
	echo "</td>";

	echo "<td align='center' colspan='1'><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'></td>";
	echo "<td align='center'  colspan='1'><input type='submit' name='reset' value=\"".$lang["buttons"][16]."\" class='submit'></td>";

	echo "<td align='center' colspan='1'>".$lang["reports"][59].":<select name='showfollowups'>";
	echo "<option value='1' ".($showfollowups=="1"?"selected":"").">".$lang["choice"][0]."</option>";
	echo "<option value='0' ".($showfollowups=="0"?"selected":"").">".$lang["choice"][1]."</option>";	
	echo "</select></td>";
	echo "</tr>";

}
	echo "</table></div>";
	echo "<input type='hidden' name='start' value='0'>";
	echo "</form>";


}


function showTrackingList($target,$start="",$status="new",$author=0,$assign=0,$assign_type=0,$category=0,$priority=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang,$cfg_devices_tables,$HTMLRel;
		
	$prefs = getTrackingPrefs($_SESSION["glpiID"]);

	$isadmin=isAdmin($_SESSION['glpitype']);
	
	$db=new DB;
	// Reduce computer list
	if ($computers_search){
	// Build query
	if($field == "all") {
		$wherecomp = " (";
		$query = "SHOW COLUMNS FROM glpi_computers";
		$result = $db->query($query);
		$i = 0;
		while($line = $db->fetch_array($result)) {
			if($i != 0) {
				$wherecomp .= " OR ";
			}
			if(IsDropdown($line["Field"])) {
				$wherecomp .= " glpi_dropdown_". $line["Field"] .".name LIKE '%".$contains."%'";
			}
			elseif($line["Field"] == "location") {
				$wherecomp .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			else {
   			$wherecomp .= "comp.".$line["Field"] . " LIKE '%".$contains."%'";
			}
			$i++;
		}
		foreach($cfg_devices_tables as $key => $val) {
			if ($val!="drive"&&$val!="control"&&$val!="pci"&&$val!="case"&&$val!="power")
			$wherecomp .= " OR ".$val.".designation LIKE '%".$contains."%'";
		}
		$wherecomp .= " OR glpi_networking_ports.ifaddr LIKE '%".$contains."%'";
		$wherecomp .= " OR glpi_networking_ports.ifmac LIKE '%".$contains."%'";
		$wherecomp .= " OR glpi_dropdown_netpoint.name LIKE '%".$contains."%'";
		$wherecomp .= " OR glpi_enterprises.name LIKE '%".$contains."%'";
		$wherecomp .= " OR resptech.name LIKE '%".$contains."%'";
		
		$wherecomp .= ")";
	}
	else {
		if(IsDevice($field)) {
			$wherecomp = "(glpi_device_".$field." LIKE '%".$contains."')";
		}
		else {
			$wherecomp = "($field LIKE '%".$contains."%')";
		}
	}
	}
	if (!$start) {
		$start = 0;
	}
	$query = "select DISTINCT glpi_tracking.ID as ID from glpi_tracking";
	if ($computers_search){
	$query.= " LEFT JOIN glpi_computers as comp on comp.ID=glpi_tracking.computer ";
	$query.= " LEFT JOIN glpi_computer_device as gcdev ON (comp.ID = gcdev.FK_computers) ";
	$query.= "LEFT JOIN glpi_device_moboard as moboard ON (moboard.ID = gcdev.FK_device AND gcdev.device_type = '".MOBOARD_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_processor as processor ON (processor.ID = gcdev.FK_device AND gcdev.device_type = '".PROCESSOR_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_gfxcard as gfxcard ON (gfxcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".GFX_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_hdd as hdd ON (hdd.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".HDD_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_iface as iface ON (iface.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".NETWORK_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_ram as ram ON (ram.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".RAM_DEVICE."') ";
	$query.= "LEFT JOIN glpi_device_sndcard as sndcard ON (sndcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".SND_DEVICE."') ";
	$query.= "LEFT JOIN glpi_networking_ports on (comp.ID = glpi_networking_ports.on_device AND  glpi_networking_ports.device_type='1')";
	$query.= "LEFT JOIN glpi_dropdown_netpoint on (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint)";
	$query.= "LEFT JOIN glpi_dropdown_os on (glpi_dropdown_os.ID = comp.os)";
	$query.= "LEFT JOIN glpi_dropdown_locations on (glpi_dropdown_locations.ID = comp.location)";
	$query.= "LEFT JOIN glpi_dropdown_model on (glpi_dropdown_model.ID = comp.model)";
	$query.= "LEFT JOIN glpi_type_computers on (glpi_type_computers.ID = comp.type)";
	$query.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = comp.FK_glpi_enterprise ) ";
	$query.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = comp.tech_num ) ";
	}

	if ($contains2!=""&&$field2!="contents"&&$field2!="ID") {
		$query.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID)";
	}

	$where=" WHERE '1' = '1'";

	if ($computers_search)
	$where.=" AND glpi_tracking.device_type= '1'";
	if ($category > 0)
	$where.=" AND glpi_tracking.category = '$category'";
	
	if ($computers_search) $where .= " AND $wherecomp";
	if (!empty($date1)&&$date1!="0000-00-00") $where.=" AND glpi_tracking.date >= '$date1'";
	if (!empty($date2)&&$date2!="0000-00-00") $where.=" AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
	if (!empty($enddate1)&&$enddate1!="0000-00-00") $where.=" AND glpi_tracking.closedate >= '$enddate1'";
	if (!empty($enddate2)&&$enddate2!="0000-00-00") $where.=" AND glpi_tracking.closedate <= adddate( '". $enddate2 ."' , INTERVAL 1 DAY ) ";


	if ($type!=0)
		$where.=" AND glpi_tracking.device_type='$type'";	
	
	if ($item!=0&&$type!=0)
		$where.=" AND glpi_tracking.computer = '$item'";	
	
	switch ($status){
	case "new": $where.=" AND glpi_tracking.status = 'new'"; break;
	case "notold": $where.=" AND (glpi_tracking.status = 'new' OR glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign')"; break;
	case "old": $where.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone')"; break;
	case "process": $where.=" AND ( glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' )"; break;
	case "waiting": $where.=" AND ( glpi_tracking.status = 'waiting' )"; break;
	case "old_done": $where.=" AND ( glpi_tracking.status = 'old_done' )"; break;
	case "old_notdone": $where.=" AND ( glpi_tracking.status = 'old_notdone' )"; break;
	case "assign": $where.=" AND ( glpi_tracking.status = 'assign' )"; break;
	case "plan": $where.=" AND ( glpi_tracking.status = 'plan' )"; break;
		
	}
	
	if ($assign_type!=0) $where.=" AND glpi_tracking.assign_type = '$assign_type'";
	if ($assign!=0&&$assign_type!=0) $where.=" AND glpi_tracking.assign = '$assign'";
	if ($author!=0) $where.=" AND glpi_tracking.author = '$author'";

	if ($priority>0) $where.=" AND glpi_tracking.priority = '$priority'";
	if ($priority<0) $where.=" AND glpi_tracking.priority >= '".abs($priority)."'";

	if ($contains2!=""){
		switch ($field2){
			case "both" :
			$where.= " AND (glpi_followups.contents LIKE '%".$contains2."%' OR glpi_tracking.contents LIKE '%".$contains2."%')";
			break;
			case "followup" :
			$where.= " AND (glpi_followups.contents LIKE '%".$contains2."%')";
			break;
			case "contents" :
			$where.= " AND (glpi_tracking.contents LIKE '%".$contains2."%')";
			break;
			case "ID" :
			$where= " WHERE (glpi_tracking.ID = '".$contains2."')";
			break;
			
		}
	}

   $query.=$where." ORDER BY glpi_tracking.date ".$prefs["order"];
	// Get it from database	
	if ($result = $db->query($query)) {
		
		$numrows= $db->numrows($result);

		if ($start<$numrows) {
			// Pager
			$parameters="field=$field&amp;contains=$contains&amp;date1=$date1&amp;date2=$date2&amp;only_computers=$computers_search&amp;field2=$field2&amp;contains2=$contains2&amp;attrib=$assign&amp;author=$author";
			// Manage helpdesk
			if (ereg("helpdesk",$target)) 
				$parameters.="&show=user";
			printPager($start,$numrows,$target,$parameters);
			
			// Produce headline

			// Form to delete old item
			if ($isadmin){
			echo "<form method='post' action=\"$target\">";
			}
			
									
			echo "<div align='center'><table border='0' class='tab_cadrehov' width='90%'>";

			commonTrackingListHeader();

			$i=$start;
			while ($i < $numrows && $i<($start+$cfg_features["list_limit"])){
				$ID = $db->result($result, $i, "ID");
				showJobShort($ID, $showfollowups);
				$i++;
			}

			// Close Table
			echo "</table></div>";

		// Delete selected item
			if ($isadmin){
				echo "<br><div align='center'>";
				echo "<table cellpadding='5' width='90%'>";
				echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt=''></td><td><a href='".$_SERVER["PHP_SELF"]."?$parameters&amp;select=all&amp;start=$start'>".$lang["buttons"][18]."</a></td>";
			
				echo "<td>/</td><td><a href='".$_SERVER["PHP_SELF"]."?$parameters&amp;select=none&amp;start=$start'>".$lang["buttons"][19]."</a>";
				echo "</td><td>";
				echo "<input type='submit' value=\"".$lang["buttons"][17]."\" name='delete' class='submit'></td>";
				echo "<td width='75%'>&nbsp;</td></table></div>";
			}
		
			echo "<br>";
			
			// End form for delete item
			if ($isadmin)
				echo "</form>";
			
			
			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><strong>".$lang["joblist"][8]."</strong></div>";
			echo "<hr noshade>";
		}
	}
}

function showFollowupsShort($ID) {
	// Print Followups for a job

	GLOBAL $cfg_install, $cfg_layout, $lang;

	// Get Number of Followups
	$db=new DB;
	$query="SELECT * FROM glpi_followups WHERE tracking='$ID' ORDER BY date";
	$result=$db->query($query);
	
	
	if ($db->numrows($result)>0) {
		echo "<div align='center'><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		echo "<tr><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		while ($data=$db->fetch_array($result)) {
			
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>".convDateTime($data["date"])."</td>";
			echo "<td align='center'>".getUserName($data["author"],1)."</td>";
			echo "<td width='70%'><strong>".$data["contents"]."</strong></td>";
			echo "</tr>";
		}		

		echo "</table></div>";
	
	}
}

function dropdownPriority($name,$value=0,$complete=0){
	global $lang;
	
	echo "<select name='$name'>";
	if ($complete){
	echo "<option value='0' ".($value==1?" selected ":"").">".$lang["search"][7]."";
	echo "<option value='-5' ".($value==-5?" selected ":"").">".$lang["search"][16]." ".$lang["help"][3]."";
	echo "<option value='-4' ".($value==-4?" selected ":"").">".$lang["search"][16]." ".$lang["help"][4]."";
	echo "<option value='-3' ".($value==-3?" selected ":"").">".$lang["search"][16]." ".$lang["help"][5]."";
	echo "<option value='-2' ".($value==-2?" selected ":"").">".$lang["search"][16]." ".$lang["help"][6]."";
	echo "<option value='-1' ".($value==-1?" selected ":"").">".$lang["search"][16]." ".$lang["help"][7]."";
	}
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["help"][3]."";
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["help"][4]."";
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["help"][5]."";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["help"][6]."";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["help"][7]."";

	echo "</select>";	
}

function getPriorityName($value){
	global $lang;
	
	switch ($value){
	case 5 :
		return $lang["help"][3];
		break;
	case 4 :
		return $lang["help"][4];
		break;
	case 3 :
		return $lang["help"][5];
		break;
	case 2 :
		return $lang["help"][6];
		break;
	case 1 :
		return $lang["help"][7];
		break;
	}	
}

function getAssignName($ID,$type,$link=0){
	global $cfg_install;
	$job=new Job;
	$job->fields["assign"]=$ID;
	$job->fields["assign_type"]=$type;
	
	if ($job->fields["assign_type"]==USER_TYPE){
		if ($job->fields["assign"]==0) echo "[Nobody]";
		return getUserName($job->fields["assign"],$link);
		
	} else if ($job->fields["assign_type"]==ENTERPRISE_TYPE){
		$ent=new Enterprise();
		$ent->getFromDB($job->fields["assign"]);
		$before="";
		$after="";
		if ($link){
			$before="<a href=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php?ID=".$job->fields["assign"]."\">";
			$after="</a>";
		}
		
		return $before.$ent->fields["name"].$after;
	}
	
}
function dropdownStatus($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='new' ".($value=="new"?" selected ":"").">".$lang["joblist"][9]."</option>";
	echo "<option value='assign' ".($value=="assign"?" selected ":"").">".$lang["joblist"][18]."</option>";
	echo "<option value='plan' ".($value=="plan"?" selected ":"").">".$lang["joblist"][19]."</option>";
	echo "<option value='waiting' ".($value=="waiting"?" selected ":"").">".$lang["joblist"][26]."</option>";
	echo "<option value='old_done' ".($value=="old_done"?" selected ":"").">".$lang["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($value=="old_notdone"?" selected ":"").">".$lang["joblist"][17]."</option>";
	echo "</select>";	
}

function getStatusName($value){
	global $lang;
	
	switch ($value){
	case "new" :
		return $lang["joblist"][9];
		break;
	case "assign" :
		return $lang["joblist"][18];
		break;
	case "plan" :
		return $lang["joblist"][19];
		break;
	case "waiting" :
		return $lang["joblist"][26];
		break;
	case "old_done" :
		return $lang["joblist"][10];
		break;
	case "old_notdone" :
		return $lang["joblist"][17];
		break;
	}	
}

function updateTracking($input){
	global $lang,$cfg_features,$cfg_mailing;
	$job = new Job;
	$job->getFromDB($input["ID"],0);

	if (isset($input["item"])&& $input["item"]!=0){
		$input["computer"]=$input["item"];
		$input["device_type"]=$input["type"];
		}
	else if ($input["type"]!=0)
		$input["device_type"]=0;

	if ($input["assign_ext"]>0){
		$input["assign_type"]=ENTERPRISE_TYPE;
		$input["assign"]=$input["assign_ext"];
	} else {
		$input["assign_type"]=USER_TYPE;
		$input["assign"]=$input["assign_int"];
		}


	$updates=array();
	// add Document if exists
	if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
		$input2=array();
		$input2["name"]=$lang["tracking"][24]." ".$input["ID"];
		$docID=addDocument($input2);
		addDeviceDocument($docID,TRACKING_TYPE,$input["ID"]);
	}
		
	// Old values for add followup in change
	$old_assign_name=$job->getAssignName();
	$old_category=$job->fields["category"];
	$old_item=$job->fields["computer"];
	$old_item_type=$job->fields["device_type"];
	$old_author=$job->fields["author"];
	$old_priority=$job->fields["priority"];
	$old_status=$job->fields["status"];

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$job->fields) && $job->fields[$key] != $input[$key]) {
			$job->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if ((in_array("assign",$updates)||in_array("assign_type",$updates))&&$job->fields["status"]=="new"){
		$updates[]="status";
		$job->fields["status"]="assign";
	
	}

	if (in_array("status",$updates)&&ereg("old_",$input["status"])){
		$updates[]="closedate";
		$job->fields["closedate"]=date("Y-m-d H:i:s");
	}

	if (in_array("author",$updates)){
		$user=new User;
		$user->getfromDBbyID($input["author"]);
		if (!empty($user->fields["email"])){
			$updates[]="uemail";
			$job->fields["uemail"]=$user->fields["email"];
		}
	}

	// Update Job
	if(count($updates)>0)
		$job->updateInDB($updates);

	// New values for add followup in change
	$change_followup_content="";
	$global_mail_change_count=0;
	if (in_array("assign",$updates)||in_array("assign_type",$updates)){
		$new_assign_name=$job->getAssignName();
		$change_followup_content.=$lang["mailing"][12].": ".$old_assign_name." -> ".$new_assign_name."\n";
		if (in_array("assign",$updates)) $global_mail_change_count++;
		if (in_array("assign_type",$updates)) $global_mail_change_count++;
	}
	if (in_array("category",$updates)){
		$new_category=$job->fields["category"];
		$old_category_name=ereg_replace("&nbsp;","N/A",getDropdownName("glpi_dropdown_tracking_category",$old_category));
		$new_category_name=ereg_replace("&nbsp;","N/A",getDropdownName("glpi_dropdown_tracking_category",$new_category));
		$change_followup_content.=$lang["mailing"][14].": ".$old_category_name." -> ".$new_category_name."\n";
		$global_mail_change_count++;
	}
	if (in_array("computer",$updates)||in_array("device_type",$updates)){	
		$ci=new CommonItem;
		$ci->getfromDB($old_item_type,$old_item);
		$old_item_name=$ci->getName();
		$ci->getfromDB($job->fields["device_type"],$job->fields["computer"]);
		$new_item_name=$ci->getName();
		
		$change_followup_content.=$lang["mailing"][17].": $old_item_name -> ".$new_item_name."\n";
		if (in_array("computer",$updates)) $global_mail_change_count++;
		if (in_array("device_type",$updates)) $global_mail_change_count++;
	}
	if (in_array("author",$updates)){
		$author=new User;
		$author->getFromDBbyID($old_author);
		$old_author_name=$author->getName();
		$author->getFromDBbyID($job->fields["author"]);
		$new_author_name=$author->getName();
		$change_followup_content.=$lang["mailing"][18].": $old_author_name -> ".$new_author_name."\n";

		$global_mail_change_count++;
	}
	if (in_array("priority",$updates)){
		$new_priority=$job->fields["priority"];
		$change_followup_content.=$lang["mailing"][14].": ".getPriorityName($old_priority)." -> ".getPriorityName($new_priority)."\n";
		$global_mail_change_count++;		
	}
	if (in_array("status",$updates)){
		$new_status=$job->fields["status"];
		$change_followup_content.=$lang["mailing"][27].": ".getStatusName($old_status)." -> ".getStatusName($new_status)."\n";

		if (ereg("old_",$new_status))
			$newinput["add_close"]="add_close";
		if (in_array("closedate",$updates))	
			$global_mail_change_count++; // Manage closedate
			
			$global_mail_change_count++;
	}
	if (in_array("emailupdates",$updates)){
	        if ($job->fields["emailupdates"]=="yes")
		        $change_followup_content.=$lang["mailing"][101]."\n";
        	else if ($job->fields["emailupdates"]=="no")
         		$change_followup_content.=$lang["mailing"][102]."\n";
	        $global_mail_change_count++;
	}

	$mail_send=0;
	if (!empty($change_followup_content)){ // Add followup if not empty

		$newinput["contents"]=addslashes($change_followup_content);
		$newinput["author"]=$_SESSION['glpiID'];
		$newinput["private"]=$newinput["hour"]=$newinput["minute"]=0;
		$newinput["tracking"]=$job->fields["ID"];
		addFollowup($newinput);
		$mail_send++;
	}

	$job->updateRealtime();		
	
	
	if ($mail_send==0&&count($updates)>$global_mail_change_count&&$cfg_features["mailing"])
		{
			$user=new User;
			$user->getfromDB($_SESSION["glpiname"]);
			$mailtype="followup";
			if (in_array("status",$updates)&&ereg("old_",$input["status"]))
				$mailtype="finish";
			else $mail_send++;

			$mail = new Mailing($mailtype,$job,$user);
			$mail->send();
		}

	// Send mail to attrib if attrib change	
	if (($mail_send==0||!$cfg_mailing["followup"]["attrib"])&&$cfg_features["mailing"]&&in_array("assign",$updates)&&$job->fields["assign_type"]==USER_TYPE&&$job->fields["assign"]>0){
			$user=new User;
			$user->getfromDB($_SESSION["glpiname"]);
			$mail = new Mailing("attrib",$job,$user);
			$mail->send();
	}

}

function updateFollowup($input){
	global $lang,$cfg_features;
	$fup = new Followup;
	$fup->getFromDB($input["ID"]);

	$input["realtime"]=$input["hour"]+$input["minute"]/60;
	// Fill the update-array with changes

	$x=0;
	$updates=array();
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$fup->fields) && $fup->fields[$key] != $input[$key]) {
			$fup->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	if(count($updates)>0)
		$fup->updateInDB($updates);

	$job=new Job;
	$job->getFromDB($input["tracking"],0);

	if (in_array("contents",$updates)&&$cfg_features["mailing"])
		{
			$user=new User;
			$user->getfromDB($_SESSION["glpiname"]);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}

}

function addFollowup($input){
	global $cfg_features;
	$fup = new Followup;

	if (isset($input['plan'])){
	$plan=$input['plan'];
	unset($input['plan']);
	}	

	$close=0;
	if (isset($input["add_close"])) $close=1;
	unset($input["add"]);
	unset($input["add_close"]);
	
	if ($input["hour"]>0||$input["minute"]>0)
	$input["realtime"]=$input["hour"]+$input["minute"]/60;
	unset($input["minute"]);
	unset($input["hour"]);

	$input["date"] = date("Y-m-d H:i:s");
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($fup->fields[$key]) || $fup->fields[$key] != $input[$key])) {
			$fup->fields[$key] = $input[$key];
		}
	}

	$newID=$fup->addToDB();	

	if (isset($plan)){
		$plan['id_followup']=$newID;
		$plan['id_tracking']=$input['tracking'];
		$plan['id_assign']=$input['author'];
		if (!addPlanningTracking($plan,"",1)){
			return false;
		}
	}

	$job=new Job;
	$job->getFromDB($input["tracking"],0);

	if ($close){
		$updates[]="status";
		$updates[]="closedate";
		$job->fields["status"]="old_done";
		$job->fields["closedate"] = date("Y-m-d H:i:s");
		$job->updateInDB($updates);
	}

	$job->updateRealtime();		

	if ($cfg_features["mailing"])
		{
			$type="followup";
			if ($close) $type="finish";
			$user=new User;
			$user->getfromDB($_SESSION["glpiname"]);
			$mail = new Mailing($type,$job,$user);
			$mail->send();
		}
	
	return $newID;
}

function deleteFollowup($input) {
	// Delete Contact
	
	$con = new Followup;
	$con->deleteInDB($input["ID"]);

	$job=new Job();
	$job->getFromDB($input['tracking'],0);
	$job->updateRealtime();		

} 


function showJobDetails ($ID){
	global $cfg_install,$cfg_features,$lang,$HTMLRel;
	$job=new Job();
	$db=new DB();
	$isadmin=isAdmin($_SESSION['glpitype']);
	
	if ($job->getfromDB($ID,1)) {

		showTrackingOnglets($_SERVER["PHP_SELF"]."?ID=".$ID);

		$author=new User();
		$author->getFromDBbyID($job->fields["author"]);
		$assign=new User();
		$assign->getFromDBbyID($job->fields["assign"]);
		$item=new CommonItem();
		$item->getFromDB($job->fields["device_type"],$job->fields["computer"]);

		// test if the user if authorized to view this job
		if (strcmp($_SESSION["glpitype"],"post-only")==0&&$_SESSION["glpiID"]!=$job->fields["author"])
		   { echo "Warning !! ";return;}

		echo "<div align='center'>";
		echo "<form method='post' action=\"".$cfg_install["root"]."/tracking/tracking-info-form.php\"  enctype=\"multipart/form-data\">\n";
		echo "<table class='tab_cadre' width='800' cellpadding='5'>";
		// Première ligne
		echo"<tr><th colspan='3'><span style='font-size:1px'>&nbsp;</span></th></tr>";
		echo "<tr class='tab_bg_2'>";
		// Premier Colonne
		echo "<td valign='top' width='27%'>";
		echo "<table cellpadding='3'>";
		echo "<tr class='tab_bg_2'><td align='right'>";
		echo $lang["joblist"][0].":</td><td>";
		if ($isadmin)
			dropdownStatus("status",$job->fields["status"]);
		else echo getStatusName($job->fields["status"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][3].":</td><td>";
		if ($isadmin)
			dropdownAllUsers("author",$job->fields["author"]);
		else echo $author->getName();
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][2].":</td><td>";
		if ($isadmin)
			dropdownPriority("priority",$job->fields["priority"]);
		else echo getPriorityName($job->fields["priority"]);
		echo "</td></tr>";

		echo "<tr><td>";
		echo $lang["tracking"][20].":</td><td>";
		if ($isadmin)
			dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
		else getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"]);
		echo "</td></tr>";

		echo "</table></td>";

		// Deuxième colonne
		echo "<td valign='top' width='33%'><table border='0'>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][11].":</td><td><strong>".convDateTime($job->fields["date"])."</strong>";
		echo "</td></tr>";
		echo "<tr><td align='right'>".$lang["joblist"][12].":</td>\n";
		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<td><i>".$lang["job"][1]."</i></td>\n";
		}
		else
		{
			echo "<td><strong>".convDateTime($job->fields["closedate"])."</strong>\n";
		}

		echo "</tr>\n";

		if ($job->fields["realtime"]>0){
			echo "<tr><td align='right'>";
			echo $lang["job"][20].":</td><td>";
			echo "<strong>".getRealtime($job->fields["realtime"])."</strong>";
			echo "</td></tr>";
		}

		if ($cfg_features["mailing"]==1){
			echo "<tr><td align='right'>";
			echo $lang["job"][19].":</td><td>";
			if ($isadmin){
				echo "<select name='emailupdates'>";
				echo "<option value='no'>".$lang["choice"][1]."</option>";
				echo "<option value='yes' ".($job->fields["emailupdates"]=="yes"?" selected ":"").">".$lang["choice"][0]."</option>";
				echo "</select>";
			} else {
				if ($job->fields["emailupdates"]=="yes") echo $lang["choice"][0];
				else $lang["choice"][1];
			}
			echo "</td></tr>";

				echo "<tr><td align='right'>";
				echo $lang["joblist"][27].":";
				echo "</td><td>";
				if ($isadmin){
					autocompletionTextField("uemail","glpi_tracking","uemail",$job->fields["uemail"],15);

						if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'><img src='".$HTMLRel."pics/edit.png' alt='Mail'></a>";
				} else if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'>".$job->fields["uemail"]."</a>";
				else echo "&nbsp;";
				echo "</td></tr>";
			

		}


		echo "</table></td>";

		// Troisième Colonne
		echo "<td valign='top' width='40%'><table border='0'>";

		echo "<tr><td align='right'>";
		echo $lang["common"][1].":</td><td>";
		if ($isadmin){
			echo $item->getType()." - ".$item->getLink()."<br>";
			dropdownAllItems("item",0);
			}
		else echo $item->getType()." ".$item->getNameID();

		echo "</td></tr>";


		echo "<tr><td align='right'>";
		echo $lang["job"][5].":</td><td>";
		echo getAssignName($job->fields["assign"],$job->fields["assign_type"],$isadmin);
		if ($isadmin)
		if ($job->fields["assign_type"]==USER_TYPE) 
			echo " (".$lang["job"][27].")";
		else echo " (".$lang["job"][28].")";

		echo "</td></tr>";
		
		if ($isadmin&&can_assign_job($_SESSION["glpiname"])){
			echo "<tr><td align='right'>";
			echo $lang["job"][27].":</td><td>";
			$val=0;
			if ($job->fields["assign_type"]==USER_TYPE) $val=$job->fields["assign"];
			dropdownUsers("assign_int",$val);
			echo "</td></tr>";

			echo "<tr><td align='right'>";
			echo $lang["job"][28].":</td><td>";
			$val=0;
			if ($job->fields["assign_type"]==ENTERPRISE_TYPE) $val=$job->fields["assign"];

			dropdown("glpi_enterprises","assign_ext",$val);
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</td></tr>";
		

		// Deuxième Ligne
		// Colonnes 1 et 2
		echo "<tr class='tab_bg_1'><td colspan='2'>";
		echo "<table width='99%' >";
		echo "<tr  class='tab_bg_2'><td width='15%'>".$lang["joblist"][6]."<br><br></td>";
		echo "<td  width='85%' align='left'>";

		if ($isadmin){
			$rand=mt_rand();
			echo "<script type='text/javascript' >\n";
			echo "function showDesc$rand(){\n";
			echo "Element.hide('desc$rand');";
			echo "var a=new Ajax.Updater('viewdesc$rand','".$cfg_install["root"]."/ajax/textarea.php' , {method: 'get',parameters: 'rows=6&cols=60&name=contents&data=".urlencode($job->fields["contents"])."'});";
			echo "}";
			echo "</script>\n";
			echo "<div id='desc$rand' class='div_tracking' onClick='showDesc$rand()'>\n";
				if (!empty($job->fields["contents"]))
					echo nl2br($job->fields["contents"]);
				else echo $lang["job"][33];

			echo "</div>\n";	
		
			echo "<div id='viewdesc$rand'>\n";
			echo "</div>\n";	
		} else echo nl2br($job->fields["contents"]);

		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		// Colonne 3

		echo "<td>";

		// File associated ?
		if ($isadmin){

			$query2 = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$job->ID."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' ";
			$result2 = $db->query($query2);
			$numfiles=$db->numrows($result2);
			$colspan=1;
			if ($numfiles>1) $colspan=2;
			echo "<table width='100%'><tr><th colspan='$colspan'>".$lang["tracking"][25]."</th></tr>";			

			if ($numfiles>0){
				$i=0;
				$con=new Document;
				while ($data=$db->fetch_array($result2)){
					if ($i%2==0&&$i>0) echo "</tr><tr>";
					echo "<td>";
					$con->getFromDB($data["FK_doc"]);
					echo getDocumentLink($con->fields["filename"]);
					echo "<a href='".$HTMLRel."documents/documents-info-form.php?deleteitem=delete&ID=".$data["ID"]."'><img src='".$HTMLRel."pics/delete.png'></a>";
					echo "</td>";
					$i++;
				}
				if ($i%2==1) echo "<td>&nbsp;</td>";
				echo "</tr>";
			}
			echo "<tr><td colspan='2'>";
			echo "<input type='file' name='filename' size='25'>";
			echo "</td></tr></table>";
		} else echo "&nbsp;";

			echo "</td></tr>";
		// Troisième Ligne
		if ($isadmin){
			echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
			echo "<input type='submit' class='submit' name='update' value='".$lang["buttons"][14]."'></td></tr>";
		}
		
echo "</table>";
echo "<input type='hidden' name='ID' value='$ID'>";
echo "</form>";
echo "</div>";

	showFollowupsSummary($ID);
	}
	
	
}

function showFollowupsSummary($tID){
	global $lang,$cfg_install,$HTMLRel;
	$db=new DB();

	$isadmin=isAdmin($_SESSION['glpitype']);
	// Display existing Followups

	$RESTRICT="";
	if (!$isadmin)  $RESTRICT=" AND private='0' ";

	$query = "SELECT * FROM glpi_followups WHERE (tracking = $tID) $RESTRICT ORDER BY date DESC";
	$result=$db->query($query);
	



		$rand=mt_rand();

		echo "<script type='text/javascript' >\n";
		echo "function showAddFollowup(){\n";
		echo "Element.hide('viewfollowup');";
		echo "var a=new Ajax.Updater('viewfollowup','".$cfg_install["root"]."/ajax/addfollowup.php' , {method: 'get',parameters: 'tID=$tID'});";
		echo "};";

		echo "function showPlan(){\n";
		echo "Element.hide('plan');";
		echo "var a=new Ajax.Updater('viewplan','".$cfg_install["root"]."/ajax/planning.php' , {method: 'get',parameters: 'form=followups'});";
		echo "}";
		echo "</script>\n";

		echo "<div id='viewfollowup'>\n";
		echo "</div>\n";	


	echo "<div align='center'>";
	echo "<h3>".$lang["job"][37]."</h3>";
	
	if ($db->numrows($result)==0){
		echo "<table class='tab_cadre' width='800'><tr class='tab_bg_2'><th>";
		echo "<strong>".$lang["job"][12]."</strong>";
		echo "</th></tr></table>";
	}
	else {	

		echo "<table class='tab_cadrehov2' width='800'>";
		echo "<tr><th>&nbsp;</th><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][6]."</th><th>".$lang["job"][31]."</th><th>".$lang["job"][35]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["job"][30]."</th></tr>";
		while ($data=$db->fetch_array($result)){

			echo "<tr class='tab_bg_2' onClick=\"viewEditFollowup".$data["ID"]."$rand();\" id='viewfollowup".$data["ID"]."$rand'>";
			echo "<td>".$data["ID"]."</td>";

			echo "<td>";

			echo "<script type='text/javascript' >\n";
			echo "function viewEditFollowup".$data["ID"]."$rand(){\n";
//			echo "Element.hide('viewfollowup');";
			echo "var a=new Ajax.Updater('viewfollowup','".$cfg_install["root"]."/ajax/viewfollowup.php' , {method: 'get',parameters: 'ID=".$data["ID"]."'});";
			echo "};";
			
			echo "</script>\n";

			
			 echo convDateTime($data["date"])."</td>";
			echo "<td align='left'>".nl2br($data["contents"])."</td>";

			$hour=floor($data["realtime"]);
			$minute=round(($data["realtime"]-$hour)*60,0);
			echo "<td>$hour ".$lang["job"][21]." $minute ".$lang["job"][22]."</td>";

			echo "<td>";
			$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
			$result2=$db->query($query2);
			if ($db->numrows($result2)==0)
				echo $lang["job"][32];	
			else {
				$data2=$db->fetch_array($result2);
				echo convDateTime($data2["begin"])." -> ".convDateTime($data2["end"]);
			}
			echo "</td>";
			
			echo "<td>".getUserName($data["author"])."</td>";
			
			echo "<td>";
			if ($data["private"])
				echo $lang["choice"][0];
			else echo $lang["choice"][1];
			echo "</td>";
					
			echo "</tr>";
		}
		echo "</table>";
	}	
	echo "</div>";
}

// Formulaire d'ajout de followup
function showAddFollowupForm($tID){
	global $lang,$cfg_install,$HTMLRel;
	$db=new DB();

	$isadmin=isAdmin($_SESSION['glpitype']);

	// Display Add Table
	if ($isadmin){
		echo "<div align='center'>";
		echo "<form name='followups' method='post' action=\"".$cfg_install["root"]."/tracking/tracking-info-form.php\">\n";
		echo "<table class='tab_cadre' width='800'>";
		echo "<tr><th colspan='2'>";
		echo $lang["job"][29];
		echo "</th></tr>";

		echo "<tr class='tab_bg_2'><td width='50%'>";
		echo "<table width='100%'>";
		echo "<tr><td>".$lang["joblist"][6]."</td>";
		echo "<td><textarea name='contents' rows=8 cols=50></textarea>";
		echo "</td></tr>";
		echo "</table>";
		echo "</td>";

		echo "<td width='50%' valign='top'>";
		echo "<table width='100%'>";

		echo "<tr>";
		echo "<td>".$lang["joblist"][3].":</td>";
		echo "<td>";
		dropdownUsers("author",$_SESSION["glpiID"]);
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>".$lang["job"][30].":</td>";
		echo "<td>";
		echo "<select name='private'>";
		echo "<option value='0'>".$lang["choice"][1]."</option>";
		echo "<option value='1'>".$lang["choice"][0]."</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr><td>".$lang["job"][31].":</td><td>";
	
		echo "<select name='hour'>";
		for ($i=0;$i<100;$i++){
			echo "<option value='$i' ";
			echo " >$i</option>";
		}
		echo "</select>".$lang["job"][21]."&nbsp;&nbsp;";
		echo "<select name='minute'>";
		for ($i=0;$i<60;$i++){
			echo "<option value='$i' ";
			echo " >$i</option>";
		}
		echo "</select>".$lang["job"][22];
		echo "</tr>";

		echo "<tr>";
		echo "<td>".$lang["job"][35]."</td>";
		echo "<td>";

		echo "<div id='plan'  onClick='showPlan()'>\n";
		echo "<span style='font-weight: bold;text-decoration: none; color : #009966;'>".$lang["job"][34]."</span>";
		echo "</div>\n";	

		echo "<div id='viewplan'>\n";
		echo "</div>\n";	
		
		
		echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>";
		echo "<input type='submit' name='add' value='".$lang["buttons"][8]."' class='submit'>";
		echo "</td>";
		
		echo "<td align='center'>";
		echo "<input type='submit' name='add_close' value='".$lang["buttons"][26]."' class='submit'>";
		echo "</td>";
		echo "</tr>";


		echo "</table>";
		echo "</td></tr>";
		echo "</table>";
		echo "<input type='hidden' name='tracking' value='$tID'>";
		echo "</form></div>";
	}
}


// Formulaire d'ajout de followup
function showUpdateFollowupForm($ID){
	global $lang,$cfg_install,$HTMLRel;
	$db=new DB();

	$isadmin=isAdmin($_SESSION['glpitype']);

	// Display existing Followups

	$RESTRICT="";
	if (!$isadmin)  $RESTRICT=" AND private='0' ";

	$query = "SELECT * FROM glpi_followups WHERE (ID = $ID)";
	$result=$db->query($query);
	

	if ($db->numrows($result)==1){
			echo "<div align='center'>";
			$data=$db->fetch_array($result);
			echo "<table class='tab_cadre' width='800'>";
			echo "<tr><th>";
			echo $lang["job"][39];
			echo "</th></tr>";
			echo "<tr class='tab_bg_2'><td>";
			echo "<form method='post' action=\"".$cfg_install["root"]."/tracking/tracking-info-form.php\">\n";

			echo "<table width='100%'>";
			echo "<tr class='tab_bg_2'><td width='50%'>";
			echo "<table width='100%' bgcolor='#FFFFFF'>";
			echo "<tr class='tab_bg_1'><td align='center' width='10%'>".$lang["joblist"][6]."<br><br>".$lang["joblist"][1].":<br><strong>".convDateTime($data["date"])."</strong></td>";
			echo "<td width='90%'>";
			
			if ($isadmin){
				echo "<textarea name='contents' cols='50' rows='6'>".$data["contents"]."</textarea>";
			} else echo nl2br($data["contents"]);
			
			
			echo "</td></tr>";
			echo "</table>";
			echo "</td>";
	
			echo "<td width='50%' valign='top'>";
			echo "<table width='100%'>";

			echo "<tr>";
			echo "<td>".$lang["joblist"][3].":</td>";
			echo "<td>";
			if ($isadmin)
				dropdownUsers("author",$data["author"]);
			else echo getUserName($data["author"]);
			echo "</td>";
			echo "</tr>";

			if ($isadmin){
				echo "<tr>";
				echo "<td>".$lang["job"][30].":</td>";
				echo "<td>";
				echo "<select name='private'>";
				echo "<option value='0' ".(!$data["private"]?" selected":"").">".$lang["choice"][1]."</option>";
				echo "<option value='1' ".($data["private"]?" selected":"").">".$lang["choice"][0]."</option>";
				echo "</select>";
				echo "</td>";
				echo "</tr>";
			} 



			echo "<tr><td>".$lang["job"][31].":</td><td>";
			$hour=floor($data["realtime"]);
			$minute=round(($data["realtime"]-$hour)*60,0);
			
			if ($isadmin){
	
				echo "<select name='hour'>";
				for ($i=0;$i<100;$i++){
					echo "<option value='$i' ";
					if ($hour==$i) echo "selected";
					echo " >$i</option>";
				}
				echo "</select>".$lang["job"][21]."&nbsp;&nbsp;";
				echo "<select name='minute'>";
				for ($i=0;$i<60;$i++){
					echo "<option value='$i' ";
					if ($minute==$i) echo "selected";
					echo " >$i</option>";
				}
				echo "</select>".$lang["job"][22];
			} else {
				echo $hour." ".$lang["job"][21]." ".$minute." ".$lang["job"][22];
				
			}
			
			echo "</tr>";

			echo "<tr>";
			echo "<td>".$lang["job"][35]."</td>";
			echo "<td>";
			$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
			$result2=$db->query($query2);
			if ($db->numrows($result2)==0)
				if ($isadmin)
					echo "<a href='".$HTMLRel."planning/planning-add-form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=-1'>".$lang["buttons"][8]."</a>";
				else echo $lang["job"][32];	
			else {
				$data2=$db->fetch_array($result2);
				echo convDateTime($data2["begin"])." -> ".convDateTime($data2["end"]);
				if ($isadmin)
					echo "<a href='".$HTMLRel."planning/planning-add-form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=".$data2["ID"]."'><img src='".$HTMLRel."pics/edit.png'></a>";
					
			}
			
			echo "</td>";
			echo "</tr>";

			if ($isadmin){
				echo "<tr class='tab_bg_2'>";
				echo "<td align='center' colspan='2'>";
				echo "<table width='100%'><tr><td align='center'>";
				echo "<input type='submit' name='update_followup' value='".$lang["buttons"][14]."' class='submit'>";
				echo "</td><td align='center'>";
				echo "<input type='submit' name='delete_followup' value='".$lang["buttons"][6]."' class='submit'>";
				echo "</td></tr></table>";
				echo "</td>";
				echo "</tr>";
			}


			echo "</table>";
			echo "</td></tr>";
	
			echo "</table>";
			if ($isadmin){
				echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
				echo "<input type='hidden' name='tracking' value='".$data["tracking"]."'>";
				echo "</form>";
			}
			echo "</td></tr>";
			echo "</table>";
			echo "</div>";
		}
}

?>
