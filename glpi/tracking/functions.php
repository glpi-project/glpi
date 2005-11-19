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

function commonTrackingListHeader(){
global $lang;
		echo "<tr><th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["common"][1]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
}

// Plus utilisé
/*
function searchFormTrackingOLD ($show,$contains,$containsID,$device,$category,$desc) {
	// Tracking Search Block
	
	GLOBAL $cfg_layout, $cfg_install,$lang;
	
	echo "\n<div align='center'>";
	echo "<form method=\"get\" action=\"".$cfg_install["root"]."/tracking/index.php\">\n";
	echo "<table class='tab_cadre'>\n";
	echo "<tr><th align='center' colspan='3'>".$lang["tracking"][7]."</th></tr>\n";

	echo "<tr class='tab_bg_1'>\n";
	echo "<td colspan='2' align='center'>\n";
	echo "<select name=\"show\" size='1'>\n";

	echo "<option "; if ($show == "all") { echo "selected"; }
	echo " value=\"all\">".$lang["tracking"][1]."</option>\n";

	echo "<option "; if ($show == "individual") { echo "selected"; }
	echo " value=\"individual\">".$lang["tracking"][2]."</option>\n";

	echo "<option "; if ($show == "unassigned") { echo "selected"; }
	echo " value=\"unassigned\">".$lang["tracking"][3]."</option>\n";

	echo "<option "; if ($show == "old") { echo "selected"; }
	echo " value=\"old\">".$lang["tracking"][4]."</option>\n";

	echo "</select>\n";
	echo "</td>\n";
	echo "<td align='center'>&nbsp;";
	//echo "<input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr class='tab_bg_1'>\n";
	echo "<td colspan='2' align='center'>\n";
	echo "<select name=\"device\" size='1'>\n";

	echo "<option "; if ($device == "-1") { echo "selected"; }
	echo " value=\"-1\">".$lang["tracking"][12]."</option>\n";

	echo "<option "; if ($device == '0') { echo "selected"; }
	echo " value='0'>".$lang["tracking"][19]."</option>\n";

	echo "<option "; if ($device == COMPUTER_TYPE) { echo "selected"; }
	echo " value=\"".COMPUTER_TYPE."\">".$lang["tracking"][13]."</option>\n";

	echo "<option "; if ($device == NETWORKING_TYPE) { echo "selected"; }
	echo " value=\"".NETWORKING_TYPE."\">".$lang["tracking"][14]."</option>\n";

	echo "<option "; if ($device == PRINTER_TYPE) { echo "selected"; }
	echo " value=\"".PRINTER_TYPE."\">".$lang["tracking"][15]."</option>\n";
	
	echo "<option "; if ($device == MONITOR_TYPE) { echo "selected"; }
	echo " value=\"".MONITOR_TYPE."\">".$lang["tracking"][16]."</option>\n";
	
	echo "<option "; if ($device == PERIPHERAL_TYPE) { echo "selected"; }
	echo " value=\"".PERIPHERAL_TYPE."\">".$lang["tracking"][17]."</option>\n";

	echo "<option "; if ($device == SOFTWARE_TYPE) { echo "selected"; }
	echo " value=\"".SOFTWARE_TYPE."\">".$lang["tracking"][18]."</option>\n";

	echo "</select>\n";
	echo "</td>\n";
	echo "<td align='center'>&nbsp;";
	//echo "<input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'>";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr class='tab_bg_1'>\n";
	echo "<td colspan='2' align='center'>\n";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>\n";
	echo "<td align='center'>&nbsp;";
	//echo "<input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'>";
	echo "</td>\n";
	echo "</tr>\n";

//	echo "</form>";
	//echo "<form method=\"get\" action=\"".$cfg_install["root"]."/tracking/index.php\">";
	echo "<tr class='tab_bg_1'>\n";
	echo "<td class='tab_bg_2'>\n";

 $elts=array("both"=>$lang["joblist"][6]." / ".$lang["job"][7],"contents"=>$lang["joblist"][6],"followup" => $lang["job"][7]);
 echo "<select name='desc'>\n";
 foreach ($elts as $key => $val){
 $selected="";
 if ($desc==$key) $selected="selected";
 echo "<option value=\"$key\" $selected>$val</option>\n";
 
 }
 echo "</select>\n";


	echo "<strong> ".$lang["search"][2].":</strong> <input type='text' name='contains' value=\"$contains\" size='15'></td>\n<td>";
	echo "<strong>".$lang["tracking"][23].":</strong> <input type='text' name='containsID' value=\"$containsID\" size='5'>";	echo "</td><td>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr>\n";

	echo "</table>\n";
	echo "</form>\n";
	echo "</div><br>\n";
}       
*/
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


	
	$query = "SELECT glpi_tracking.ID FROM glpi_tracking WHERE (glpi_tracking.status = 'new') AND (glpi_tracking.assign = '".$_SESSION["glpiID"]."') ORDER BY glpi_tracking.date ".$prefs["order"]."";
	
	$lim_query = " LIMIT ".$start.",".$cfg_features["list_limit"]."";	

	$db = new DB;
	$result = $db->query($query);
	$numrows = $db->numrows($result);

	$query .= $lim_query;
	
	$result = $db->query($query);
	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0) {

		echo "<div align='center'><br><table class='tab_cadre' width='400'>";
		
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

// Plus utilisé
/*
function showJobList($target,$username,$show,$contains,$item_type,$item,$start,$device='-1',$category='NULL',$containsID='',$desc="both") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;
		
	$prefs = getTrackingPrefs($_SESSION["glpiID"]);

	// Build where-clause
	if ($contains||$containsID)
	{
		$where= "  ( '0'='1' ";
		if ($contains){
			switch ($desc){
			case "both" :
			$where.= " OR (glpi_followups.contents LIKE '%".$contains."%' OR glpi_tracking.contents LIKE '%".$contains."%')";
			break;
			case "followup" :
			$where.= " OR (glpi_followups.contents LIKE '%".$contains."%')";
			break;
			case "contents" :
			$where.= " OR (glpi_tracking.contents LIKE '%".$contains."%')";
			break;
		}
		}

		if ($containsID)
			$where .= " OR (glpi_tracking.ID = '$containsID')";
			
		$where.=" ) ";
	}
	else  if ($show == "old")
	{
		$where = " (glpi_tracking.status = 'old')";
	}
	else if ($show !="user")
	{
		$where = " (glpi_tracking.status = 'new')";
	} else $where= " ('1'='1') ";
	
	
	if($device != -1) {
		$where .= " AND (glpi_tracking.device_type = '".$device."')";
	} 

	if($category!=0) {
		$where .= " AND (glpi_tracking.category = '".$category."')";
	} 

	// Build query, two completely different things here, need to be fixed
	// and made into a more featured query-parser

	$joinfollowups="";
	if ($contains!=""&&$desc!="contents") {
		$joinfollowups.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID)";
	}
	
	if ($show == "individual")
	{
		$query = "SELECT DISTINCT glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." and (glpi_tracking.assign = '".$username."') ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}
	else if ($show == "user")
	{
		$query = "SELECT DISTINCT  glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." and (glpi_tracking.author = '".$username."') ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}
	else if ($show == "enterprise")
	{
		$query = "SELECT DISTINCT  glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." and (glpi_tracking.assign = '".$username."' AND glpi_tracking.assign_type='".ENTERPRISE_TYPE."') ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}
	else if ($show == "unassigned")
	{
		$query = "SELECT DISTINCT  glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." and (glpi_tracking.assign ='' OR glpi_tracking.assign is null) ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}
	else
	{
		$query = "SELECT DISTINCT  glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}

	if ($item&&$item_type)
	{
		$query = "SELECT DISTINCT  glpi_tracking.ID FROM glpi_tracking ".$joinfollowups." WHERE ".$where." and (glpi_tracking.device_type = '".$item_type."' and glpi_tracking.computer = '".$item."') ORDER BY glpi_tracking.date ".$prefs["order"]."";
	}	
	
	$lim_query = " LIMIT ".$start.",".$cfg_features["list_limit"]."";	

	$db = new DB;
	$result = $db->query($query);
	$numrows = $db->numrows($result);

//	if ($show!="user")	
		$query .= $lim_query;
	
	$result = $db->query($query);
	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0) {
		// Pager
		if(empty($sort)) $sort = "";
		$parameters="show=".$show."&amp;contains=".$contains."&amp;sort=".$sort;
		if ($show!="user")
			$parameters.="&amp;ID=".$username;
		printPager($start,$numrows,$target,$parameters);

	// Form to delete old item
	if ($show=="old"&&isAdmin($_SESSION["glpitype"])){
		echo "<form method='post' action=\"$target\">";
		echo "<input type='hidden' name='show' value='$show'>";
		echo "<input type='hidden' name='contains' value='$contains'>";
		echo "<input type='hidden' name='item' value='$item'>";
		$newstart=$start;
		if (isset($_GET["select"])&&$_GET["select"]=="all"&&($numrows<=($start+$cfg_features["list_limit"])))
			$newstart=max(0,$start-$cfg_features["list_limit"]);
		echo "<input type='hidden' name='start' value='$newstart'>";
		}
		
		
		echo "<div align='center'><table class='tab_cadre' width='90%'>";
		echo "<tr><th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["common"][1]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			showJobShort($ID, 0);
			$i++;
		}
		if ($item)
		{
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' colspan='8' class='tab_bg_1'>";
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&amp;device_type=$item_type\"><strong>";
			echo $lang["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		echo "</table></div>";
		// Delete selected item
		if (isAdmin($_SESSION["glpitype"])&&$show == "old"){
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
	if ($show=="old"&&isAdmin($_SESSION["glpitype"]))
	echo "</form>";
			
			printPager($start,$numrows,$target,$parameters);
	}
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre' width='90%'>";
		echo "<tr><th>".$lang["joblist"][8]."</th></tr>";

		if ($item) 
		{
			echo "<tr><td align='center' class='tab_bg_1'>";
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&amp;device_type=$item_type\"><strong>";
			echo $lang["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}
*/

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
/*		if ($item)
		{
			echo "<tr class='tab_bg_2'>";
		        echo "<td align='center' colspan='8' class='tab_bg_1'>";

		}
		*/
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

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;

	if ($job->getfromDB($ID,0))
	{
		$bgcolor=$cfg_layout["priority_".$job->fields["priority"]];

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'  >ID: ".$job->ID."<br><strong>".getStatusName($job->fields["status"])."</strong>";

		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<td width='100'  ><small>".$lang["joblist"][11].":<br>&nbsp;".$job->fields["date"]."</small></td>";

		}
		else
		{
			if (isAdmin($_SESSION["glpitype"])){
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
			echo "<br><input type='checkbox' name='todel[".$job->ID."]' value='1' $sel>";
			}
			echo "</td>";
			echo "<td width='130' ><small>".$lang["joblist"][11].":<br>&nbsp;".$job->fields["date"]."<br>";
			echo "<i>".$lang["joblist"][12].":<br>&nbsp;".$job->fields["closedate"]."</i>";
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

//			$job->fields["assign"]
			
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
/*			if ($job->computerfound)	echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$job->fields["computer"]\">";
			echo "<strong>$job->computername ($job->fields["computer"])</strong>";
			if ($job->computerfound) echo "</a>";
*/			
			echo "</td>";
		}
		else
		echo "<td  align='center' ><strong>$job->computername (".$job->fields["computer"].")</strong></td>";


		echo "<td  align='center' ><strong>".getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"])."</strong></td>";
		
		$stripped_content=$job->fields["contents"];
		if (!$followups) $stripped_content =substr($job->contents,0,$cfg_features["cut"]);
		echo "<td ><strong>".$stripped_content."</strong>";
		if ($followups)
		{
			showFollowupsShort($job->ID);
		}

		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center' >";
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-followups.php?ID=$job->ID\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
		echo "<a href=\"".$cfg_install["root"]."/helpdesk.php?show=user&amp;ID=$job->ID\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
//		if ($job->fields["status"] == "new"&&strcmp($_SESSION["glpitype"],"post-only")!=0)
//		{
//			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-mark.php?ID=$job->ID\">".$lang["joblist"][14]."</a><br>";
//		}
//		if(strcmp($_SESSION["glpitype"],"post-only")!=0)
//		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-assign-form.php?ID=$job->ID\">".$lang["joblist"][15]."</a></strong></td>";

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
			if (isset($m->obj->fields["deleted"])&&$m->obj->fields["deleted"]=='Y')
			echo "class='tab_bg_1_2'";
			echo ">";
			echo $m->getType()."<br>";
			echo "<strong>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</strong>";
/*			if ($job->computerfound)	echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$job->fields["computer"]\">";
			echo "<strong>$job->computername ($job->fields["computer"])</strong>";
			if ($job->computerfound) echo "</a>";
*/			
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
		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-followups.php?ID=$job->ID\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
		echo "<a href=\"".$cfg_install["root"]."/helpdesk.php?show=user&amp;ID=$job->ID\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
//		if ($job->fields["status"] == "new"&&strcmp($_SESSION["glpitype"],"post-only")!=0)
//		{
//			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-mark.php?ID=$job->ID\">".$lang["joblist"][14]."</a><br>";
//		}
//		if(strcmp($_SESSION["glpitype"],"post-only")!=0)
//		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-assign-form.php?ID=$job->ID\">".$lang["joblist"][15]."</a></strong></td>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
    echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

/*
// Plus utilisé - remplacé par nouvelle fonctione
function showJobDetails($ID) {
	// Prints a job in long form with all followups and stuff

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang,$HTMLRel;
	
	// Make new job object and fill it from database, if success, print it

	$job = new Job;
	$db=new DB();
	if ($job->getfromDB($ID,0)) {

		$author=new User();
		$author->getFromDBbyID($job->fields["author"]);
		$assign=new User();
		$assign->getFromDBbyID($job->fields["assign"]);

		// test if the user if authorized to view this job
		if (strcmp($_SESSION["glpitype"],"post-only")==0&&$_SESSION["glpiID"]!=$job->fields["author"])
		   { echo "Warning !! ";return;}
		
		echo "<div align='center'>";
		echo "<form method='get' action=\"".$cfg_install["root"]."/tracking/tracking-edit-form.php\">\n";
		echo "<input type='hidden' name='ID' value='$ID'>";
		echo "<table class='tab_cadre' width='90%' cellpadding='5'>\n";
		echo "<tr><th colspan='3'>".$lang["job"][0]." $job->ID:</th></tr>\n";

		echo "<tr class='tab_bg_2'>\n";
		echo "<td width='33%' rowspan='1'>\n";

		echo "<table cellpadding='2' cellspacing='0' border='0' >\n";

		echo "<tr><td>".$lang["joblist"][0].":</td><td>";
		if ($job->fields["status"] == "new") { 
			echo "<font color=\"green\"><strong>".$lang["joblist"][9]."</strong></font>"; }
		else {
			echo "<strong>".$lang["joblist"][10]."</strong>";
		}
		echo "</td><td>&nbsp;</td></tr>\n";


		echo "<tr><td>".$lang["joblist"][3].":</td><td>\n";
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<strong><a href=\"".$cfg_install["root"]."/users/users-info.php?ID=$job->fields["author"]\">".$author->getName()."</a></strong>";
		else 
		echo "<strong>".$author->getName()."</strong>";
		echo "</td><td>";
		if (isAdmin($_SESSION['glpitype']))
		echo "<input type='submit' name='update_author' value=\"".$lang["buttons"][14]."\" class='submit'>";
		else "&nbsp;";

		echo "</td></tr>\n";

		$m= new CommonItem;
		$m->getfromDB($job->fields["device_type"],$job->fields["computer"]);

		echo "<tr><td>".$m->getType().":</td><td>\n";
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		{
			echo "<strong>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</strong>";
		}
		else
		echo "<strong>".$m->getNameID()."</strong>&nbsp;&nbsp;";
		echo "</td><td>";

		if (isAdmin($_SESSION['glpitype']))
		echo "<input type='submit' name='update_item' value=\"".$lang["buttons"][14]."\" class='submit'>";
		else "&nbsp;";
		echo "</td></tr>\n";

		echo "<tr><td>".$lang["joblist"][2].":</td><td>";
		if (isAdmin($_SESSION["glpitype"]))
		  priorityFormTracking($job->ID,$cfg_install["root"]."/tracking/tracking-priority-form.php");	
		else echo getPriorityName($job->fields["priority"])."</td><td>&nbsp;";	
		echo "</td></tr>\n";

		echo "</table>\n";

		echo "</td>\n";

		echo "<td>";
		echo "<table cellpadding='2' cellspacing='0' border='0'>\n";
		echo "<tr><td align='right'>".$lang["joblist"][11].":</td>\n";
		echo "<td><strong>".$job->fields["date"]."</strong></td></tr>\n";
		echo "<tr><td align='right'>".$lang["joblist"][12].":</td>\n";
//		if ($job->fields["closedate"] == "0000-00-00 00:00:00" || $job->fields["closedate"] == "")
		if ($job->fields["status"]=="new")
		{
			echo "<td><i>".$lang["job"][1]."</i></td></tr>\n";
		}
		else
		{
			echo "<td><strong>$job->fields["closedate"]</strong></tr>\n";
			if ($job->fields["realtime"]>0)
			echo "<tr><td align='right'>".$lang["job"][20].":</td><td><strong>".getRealtime($job->fields["realtime"])."</strong></td></tr>\n";
		}
		if ($cfg_features["mailing"]==1){
			if ($job->fields["emailupdates"]=='yes') $suivi=$lang["choice"][0];
			else $suivi=$lang["choice"][1];
			echo "<tr><td>".$lang["job"][19].":</td><td>$suivi</td></tr>\n";
			if (!empty($job->fields["uemail"]))
				echo "<tr><td align='right'>".$lang["joblist"][3].":</td><td><a href='mailto:".$job->fields["uemail"]."'>".$job->fields["uemail"]."</a></td></tr>\n";
		}
		// Print planning
		$planning_realtime=0;		

			echo "<tr><td colspan='2' align='center'>&nbsp;</td></tr>\n";
			if (isAdmin($_SESSION['glpitype'])){
				echo "<tr><td colspan='2' align='center'><b>\n";
				echo "<a href=\"".$cfg_install["root"]."/planning/planning-add-form.php?job=".$job->ID."\">".$lang["planning"][7]."</a></b>\n";
				echo "</td></tr>\n";
			} else if ($_SESSION["glpiID"]==$job->fields["author"]){
				echo "<tr><td colspan='2' align='center'><b>\n";
				echo $lang["planning"][11];
				echo "</td></tr>\n";
			}
			

			$query2="SELECT * from glpi_tracking_planning WHERE id_tracking='".$job->ID."'";
			$result2=$db->query($query2);
			if ($db->numrows($result2)>0)
			while ($data=$db->fetch_array($result2)){
				echo "<tr><td colspan='2' align='left'>\n";
				echo date("Y-m-d H:i",strtotime($data["begin"]))." -> ".date("Y-m-d H:i",strtotime($data["end"]))." - ".getUserName($data['id_assign']);
				if (isAdmin($_SESSION['glpitype']))
					echo "<a href='".$HTMLRel."planning/planning-add-form.php?edit=edit&amp;job=".$job->ID."&amp;ID=".$data["ID"]."'><img src='$HTMLRel/pics/edit.png' alt='Edit'></a>\n";

				echo "<br>";
				$tmp_beg=split(" ",$data["begin"]);
				$tmp_end=split(" ",$data["end"]);
				$tmp_dbeg=split("-",$tmp_beg[0]);
				$tmp_dend=split("-",$tmp_end[0]);
				$tmp_hbeg=split(":",$tmp_beg[1]);
				$tmp_hend=split(":",$tmp_end[1]);
				
				$dateDiff = mktime($tmp_hend[0],$tmp_hend[1],$tmp_hend[2],$tmp_dend[1],$tmp_dend[2],$tmp_dend[0]) 
						  - mktime($tmp_hbeg[0],$tmp_hbeg[1],$tmp_hbeg[2],$tmp_dbeg[1],$tmp_dbeg[2],$tmp_dbeg[0]);		

				$planning_realtime+=$dateDiff/60/60;
				echo "</td></tr>";
			}
		
		echo "</table>\n";
		echo "</td>\n";
		if ($job->fields["realtime"]==0) $job->fields["realtime"]=$planning_realtime;
	
		//echo "</tr><tr class='tab_bg_2'>";
		
		echo "<td align='center'>";	
		if (can_assign_job($_SESSION["glpiname"]))
			assignFormTracking($ID,$_SESSION["glpiname"],$cfg_install["root"]."/tracking/tracking-assign-form.php");
		else echo $lang["job"][5]." <strong>".($job->fields["assign"]==0?"[Nobody]":$assign->getName())."</strong>";
		echo "<br />";
		if (isAdmin($_SESSION["glpitype"]))
			categoryFormTracking($ID,$cfg_install["root"]."/tracking/tracking-category-form.php");
		else echo $lang["tracking"][20].": <strong>".getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"])."</strong>";
		
		
		echo "</td>";
		
		echo "</tr>";
		
		echo "</table></form>";
		echo "</div>";
		echo "<div align='center'>";

		echo "<table  class='tab_cadre' width='90%' cellpadding='5'>";
		echo "<tr class='tab_bg_2'>\n";
		
		echo "<td>\n";
		echo "<table><tr><td width='90'>\n";
		echo $lang["joblist"][6].":";
		echo "</td><td>\n";
		echo "<strong>".$job->fields["contents"]."</strong>";		
		echo "</td></tr>";
		
		// File associated ?
		$query2 = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$job->ID."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' ";
		$result2 = $db->query($query2);
		if ($db->numrows($result2)>0){
			echo "<tr><td>".$lang["tracking"][25].":</td>";
			echo "<td>";
			$con=new Document;
			$con->getFromDB($db->result($result2, 0, "FK_doc"));
			echo getDocumentLink($con->fields["filename"]);

			echo "</td></tr>";
		}

		

		echo "</table>\n";

		echo "</td>\n";
	
		echo "</tr>";
		
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		if ($job->fields["status"] == "new") {
			$hour=floor($job->fields["realtime"]);
			$minute=round(($job->fields["realtime"]-$hour)*60,0);
			echo "<tr class='tab_bg_1'>";
			echo "<td align='center'>";
			echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-mark.php\">";

			echo "<input type='hidden' name='ID' value=$job->ID>";			
			echo $lang["job"][20].":&nbsp;";
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
			echo "</select>".$lang["job"][22]."&nbsp;&nbsp;";

			echo "<input type='submit' name='close' value=\"".$lang["job"][3]."\" class='submit'>";
			echo "</form>";

			echo "</td></tr>";

		}
		else if ($job->fields["status"] == "old") {
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='3' align='center'>";
			echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-mark.php\">";
			echo "<input type='hidden' name='ID' value=$job->ID>";			
			echo "<input type='hidden' name='status' value='new'>";			
			echo "<input type='submit' name='restore' value=\"".$lang["job"][23]."\" class='submit'>";
			echo "</form>";

			echo "</td></tr>";


			}
		echo "</table>";
//		showDocumentAssociated(TRACKING_TYPE,$job->ID,2);

		echo "<br><br><table width='90%' class='tab_cadre'><tr><th>".$lang["job"][7].":</th></tr>";
		echo "</table></div>";

		showFollowups($job->ID);  
                

	} 
	else
	{
    		echo "<tr class='tab_bg_2'><td colspan=6><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}
*/
function postJob($device_type,$ID,$author,$status,$priority,$isgroup,$uemail,$emailupdates,$contents,$assign=0,$realtime=0,$assign_type=USER_TYPE) {
	// Put Job in database

	GLOBAL $cfg_install, $cfg_features, $cfg_layout,$lang;
	
	$job = new Job;

	if (!$isgroup) {
		$job->isgroup = "no";
	} else {
		$job->isgroup = "yes";
	}
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
	$job->fields["emailupdates"] = $emailupdates;
	$job->fields["assign"] = $assign;
	$job->fields["assign_type"] = $assign_type;
	$job->fields["realtime"] = $realtime;

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
	
	
	if ($tID=$job->putinDB()) {
		
		// add Document if exists
		if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
		$input=array();
		$input["name"]=$lang["tracking"][24]." $tID";
		//$input["TOCLEAN"]="CLEAN";
		$docID=addDocument($input);
		addDeviceDocument($docID,TRACKING_TYPE,$tID);
		}
		
		
		
		// Log this event
		logEvent($ID,$item,4,"tracking","$author added new job.");
		
		$aa=0;
		if ($cfg_features['auto_assign']){
			$ci=new CommonItem;
			$ci->getFromDB($device_type,$ID);
			if (isset($ci->obj->fields['tech_num'])&&$ci->obj->fields['tech_num']!=0){
				assignJob ($tID,USER_TYPE,$ci->obj->fields['tech_num'],$_SESSION["glpiname"]);
				$aa=1;
			}
		}
		
		// Processing Email
		if ($aa==0&&$cfg_features["mailing"])
		{
			$user=new User;
			$user->getfromDB($author);
			$mail = new Mailing("new",$job,$user);
			$mail->send();
		}
		return true;	
	} else {
		//echo "Couldn't post followup.";
		return false;
	}
}

/*
// Plus utilisé
function markJob ($ID,$status,$opt='') {
	// Mark Job with status
	GLOBAL $cfg_features;

	$job = new Job;
	$job->getFromDB($ID,1);
	$job->updateStatus($status);

	// Realtime intervention
	if ($status=="old"&&$opt!='')
		$job->updateRealtime($opt);	
	// Processing Email
	if ($status=="old"&&$cfg_features["mailing"])
		{
			$user=new User;
			$user->getfromDB($_SESSION["glpiname"]);
			$mail = new Mailing("finish",$job,$user);
			$mail->send();
		}

}
// Plus utilisé
function assignJob ($ID,$type,$user,$admin) {
	// Assign a job to someone

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$job = new Job;
	$job->getFromDB($ID,0);

	$newuser=$user;
	$olduser=$job->fields["assign"];
	$newuser_type=$type;
	$olduser_type=$job->fields["assign"]_type;
	$oldname=$job->getAssignName();
			
	$job->fields["assign"]To($user,$type);
	$newname=$job->getAssignName();
	// Add a Followup for a assignment change
	if ($newuser!=$olduser||$newuser_type!=$olduser_type){
	$content=$lang["mailing"][12].": ".$oldname." -> ".$newname;
	$sendmail=1;
	if ($type!=USER_TYPE) $sendmail=0;

	postFollowups ($ID,$_SESSION["glpiID"],addslashes($content),$sendmail);
	}
}
// Plus utilisé
function categoryJob ($ID,$category,$admin) {
	// Assign a category to a job

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$job = new Job;
	$job->getFromDB($ID,0);
	$oldcat=$job->fields["category"];
	$job->fields["category"]To($category);
	$newcat=$job->fields["category"];
	// Add a Followup for a category change
	if ($newcat!=$oldcat){
	$content=$lang["mailing"][14].": ".getDropdownName("glpi_dropdown_tracking_category",$oldcat)." -> ".getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"]);
	postFollowups ($ID,$_SESSION["glpiID"],addslashes(unhtmlentities($content)));
	}
	
}
// Plus utilisé
function itemJob ($tID,$device_type,$iID) {
	// Chage the item of a job

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$m= new CommonItem;

	$job = new Job;
	$job->getFromDB($tID,0);
	$oldtype=$job->fields["device_type"];
	$oldcomp=$job->fields["computer"];
	$m->getfromDB($job->fields["device_type"],$job->fields["computer"]);
	$oldname=$m->getName();
	$job->itemTo($device_type,$iID);
	$newtype=$job->fields["device_type"];
	$newcomp=$job->fields["computer"];

	// Add a Followup for a item change
	if ($newtype!=$oldtype||$newcomp!=$oldcomp){
	$m->getfromDB($job->fields["device_type"],$job->fields["computer"]);

	$content=$lang["mailing"][17].": $oldname -> ".$m->getName();
	postFollowups ($tID,$_SESSION["glpiID"],addslashes(unhtmlentities($content)));
	}
	
}
// Plus utilisé
function authorJob ($tID,$aID) {
	// Change the author of a job

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$u= new User;

	$job = new Job;
	$job->getFromDB($tID,0);
	$oldauthor=$job->fields["author"];
	$u->getfromDBbyID($job->fields["author"]);
	$oldname=$u->getName();
	$job->fields["author"]To($aID);
	$newauthor=$job->fields["author"];
	$u->getfromDBbyID($job->fields["author"]);
	$job->mailAuthorTo($u->fields['email']);


	// Add a Followup for a item change
	if ($newauthor!=$oldauthor){

	$u->getfromDBbyID($job->fields["author"]);

	$content=$lang["mailing"][18].": $oldname -> ".$u->getName();
	postFollowups ($tID,$_SESSION["glpiID"],addslashes(unhtmlentities($content)));
	}
	
}
// Plus utilisé
function priorityJob ($ID,$priority,$admin) {
	// Assign a category to a job

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$job = new Job;
	$job->getFromDB($ID,0);
	$oldprio=$job->fields["priority"];
	$job->fields["priority"]To($priority);
	$newprio=$job->fields["priority"];
	// Add a Followup for a priority change
	if ($newprio!=$oldprio){
	$content=$lang["mailing"][14].": ".getPriorityName($oldprio)." -> ".getPriorityName($job->fields["priority"]);
	postFollowups ($ID,$_SESSION["glpiID"],addslashes(unhtmlentities($content)));
	}
	
}
// Plus utilisé - remplacé par nouvelle fonction
function showFollowups($ID) {
	// Print Followups for a job

	GLOBAL $cfg_install, $cfg_layout, $lang;

	// Get Number of Followups

	$job = new Job;
	$job->getFromDB($ID,0);
	$nbfollow=$job->numberOfFollowups();
	if ($nbfollow) {
		echo "<div align='center'><table class='tab_cadre' width='90%' cellpadding='2'>\n";
		echo "<tr><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		for ($i=0; $i < $nbfollow; $i++) {
			$fup = new Followup;
			$fup->getFromDB($ID,$i);
			echo "<tr class='tab_bg_2'>\n";
			echo "<td align='center'>$fup->date</td>\n";
			echo "<td align='center'>".$fup->getAuthorName(1)."</td>\n";
			echo "<td width='70%'><strong>".$fup->contents."</strong></td>\n";
			echo "</tr>";
		}		

		echo "</table></div>\n";
	
	} else {
		echo "<div align='center'><strong>".$lang["job"][8]."</strong></div>\n";
	}

	// Show input field only if job is still open
	if(strcmp($_SESSION["glpitype"],"post-only")!=0)
	if ($job->fields["status"]=="new") {

		echo "<div align='center'>&nbsp;\n";
		echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-followups.php\">";
		echo "<table class='tab_cadre' width='90%'>\n";
		echo "<tr><th>";
		echo "<input type='hidden' name=ID value=$ID>";
		echo $lang["job"][9].":</th></tr>\n";
		echo "<tr class='tab_bg_1'><td width='100%' align='center'><textarea cols='60' rows='5' name='contents' ></textarea></td></tr>\n";
		echo "<tr><td align='center' class='tab_bg_1'>";
		echo "<input type='submit' name='add_followup' value=\"".$lang["buttons"][2]."\" class='submit'></td>\n";
		echo "</tr></table></form></div>\n";
	}

}

// Plus utilisé - remplacé par nouvelle fonction
function postFollowups ($ID,$author,$contents,$sendmail=1) {

	GLOBAL $cfg_install, $cfg_features, $cfg_layout;
	
	$fup = new Followup;
	
	$fup->tracking = $ID;
	$fup->author = $author;
	$fup->contents = $contents;

	if ($fup->putInDB()) {

		// Log this event
		$fup->logFupUpdate();
		// Processing Email
		if ($cfg_features["mailing"]&&$sendmail)
		{
			$job= new Job;
			$job->getfromDB($ID,0);
			$user=new User;
			$user->getfromDBbyID($author);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}
	} else {
		echo "Couldn't post followup.";
	}
}
*/
function addFormTracking ($device_type,$ID,$author,$assign,$target,$error,$searchauthor='') {
	// Prints a nice form to add jobs

	GLOBAL $cfg_layout, $lang,$cfg_features,$REFERER;

	if (!empty($error)) {
		echo "<div align='center'><strong>$error</strong></div>";
	}
	echo "<form method='get' action='$target'>";
	echo "<input type='hidden' name='referer' value='$REFERER'>";
	echo "<div align='center'>";
	echo "<p><a class='icon_consol' href='$REFERER'>".$lang["buttons"][13]."</a></p>";
	
	echo "<table class='tab_cadre'><tr><th colspan='4'>".$lang["job"][13].": <br>";
	$m=new CommonItem;
	$m->getfromDB($device_type,$ID);
	echo $m->getType()." - ".$m->getNameID();
	echo "</th></tr>";

	echo "<tr class='tab_bg_1' align='center'><td>".$lang["joblist"][1].":</td>";
	echo "<td align='center' colspan='3'>".date("Y-m-d H:i:s")."</td></tr>";

	echo "<tr><td class='tab_bg_2' align='center'>".$lang["joblist"][0].":</td>";
	echo "<td align='center' class='tab_bg_2' colspan='3'><select name='status'>";
	echo "<option value='new' ";
	if ($_GET["status"]=="new") echo "selected";
	echo ">".$lang["job"][14]."</option>";
	echo "<option value='old' ";
	if ($_GET["status"]=="old") echo "selected";	
	echo ">".$lang["job"][15]."</option>";
	echo "</select></td></tr>";

			echo "<tr>";
			echo "<td class='tab_bg_2' align='center'>";
			echo $lang["job"][20].":</td>";
			echo "<td align='center' colspan='3' class='tab_bg_2'><select name='hour'>";
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
	echo "<td align='center' class='tab_bg_2' colspan='3'>";
	dropdownPriority("priority",3);
	echo "</td></tr>";

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][3].":</td>";
	
	echo "<td align='center'>";

	dropdownAllUsers("user",$assign);
//      echo "<td><input type='text' size='10'  name='search'></td>";
//	echo "<td><input type='submit' value=\"".$lang["buttons"][0]."\" name='Modif_Interne' class='submit'>";
	echo "</td></tr>";
	

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][15].":</td>";
	
	echo "<td align='center' colspan='3'>";
	dropdownUsers("assign",$assign);
	echo "</td></tr>";

	if($cfg_features["mailing"] == 1)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".$lang["help"][8].":</td>";
		echo "<td align='center' colspan='3'>	<select name='emailupdates'>";
		echo "<option value='no' selected>".$lang["help"][9]."";
		echo "<option value='yes'>".$lang["help"][10]."";
		echo "</select>";
		echo "</td></tr>";
	}


	echo "<tr class='tab_bg_1' align='center'><td></td>";
	echo "<td align='center' colspan='3'>";
	echo "<input type='hidden' name='ID' value=\"$ID\">";
	echo "<input type='hidden' name='device_type' value=\"$device_type\">";
	echo "</td></tr>";

	echo "<tr><td colspan='4' height='5'></td></tr>";
	echo "<tr><th colspan='4' align='center'>".$lang["job"][11].":</th></tr>";

	echo "<tr><td colspan='4'><textarea cols='60' rows='14'  name='contents'></textarea></td></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
	echo "<input type='submit' value=\"".$lang["buttons"][2]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></div></form>";

}
/* 
// Plus utilisé
function assignFormTracking ($ID,$admin,$target) {
	// Print a nice form to assign jobs if user is allowed

	GLOBAL $cfg_layout, $lang;


  if (can_assign_job($admin))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<table class='tab_cadre'>";
	echo "<tr><th>".$lang["job"][4]." $ID:</th></tr>";
//	echo "<form method=get action=\"".$target."\">";

	echo "<tr><td align='center' class='tab_bg_1'>";

	echo "<table border='0'>";
	echo "<tr>";
	echo "<td colspan='2'>";//.$lang["job"][5].":</td><td>";
		dropdownAssign('','', "assign_id");
//	echo "<input type='hidden' name='update' value=\"1\">";
//	echo "<input type='hidden' name='ID' value='$job->ID'>";
	echo "</td><td><input type='submit' name='update' value=\"".$lang["job"][6]."\" class='submit'></td>";
	echo "</tr>";
	echo "<tr><td colspan='3'>";
	echo $lang["job"][5]." ";
	echo getAssignName($job->fields["assign"],$job->fields["assign"]_type,1);
	echo "</td></tr>";

	echo "</table>";

	echo "</td>";
//	echo "</form>";
	echo "</tr></table>";
	}
	else
	{
	 echo $lang["tracking"][6];
	}
}

// Plus utilisé
function categoryFormTracking ($ID,$target) {
	// Print a nice form to assign jobs if user is allowed

	GLOBAL $cfg_layout, $lang;

  if (isAdmin($_SESSION["glpitype"]))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<table class='tab_cadre'>";
	echo "<tr><th>".$lang["job"][24]." $ID:</th></tr>";
//	echo "<form method=get action=\"".$target."\">";
	echo "<tr><td align='center' class='tab_bg_1'>";

	echo "<table border='0'>";
	echo "<tr>";
	echo "<td>".$lang["tracking"][20].":</td><td>";
		dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
//	echo "<input type='hidden' name='update' value=\"1\">";
//	echo "<input type='hidden' name='ID' value='$job->ID'>";
	echo "</td><td><input type='submit' name='update' value=\"".$lang["buttons"][14]."\" class='submit'></td>";
	echo "</tr></table>";

	echo "</td>";
//	echo "</form>";
	echo "</tr></table>";
	}
	else
	{
	 echo $lang["tracking"][21];
	}
}
// Plus utilisé
function itemFormTracking ($ID,$target) {
	GLOBAL $cfg_layout, $lang,$cfg_install;

  if (isAdmin($_SESSION["glpitype"]))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<form method=get name='helpdeskform' action=\"".$target."\">";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["job"][25]."</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][12]." <img src=\"".$cfg_install["root"]."/pics/aide.png\" style='cursor:pointer;' alt=\"help\"onClick=\"window.open('".$cfg_install["root"]."/find_num.php','Help','scrollbars=1,resizable=1,width=600,height=600')\"></td>";
	echo "<td><input name='computer' size='10' value='$job->fields["computer"]'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][24].": </td>";
	echo "<td>";
	dropdownTrackingDeviceType("device_type",$job->fields["device_type"]);
	
	echo "</td></tr>";
	echo "<tr class='tab_bg_1'><td colspan='2' align='center'>";
	echo "<input type='hidden' name='ID' value='$ID'>";
	echo "<input type='submit' name='update_item_ok' value=\"".$lang["buttons"][14]."\" class='submit'>";
	
	echo "</td></tr>";
	echo "</table>";

	echo "</form>";
	}
	else
	{
	 echo $lang["tracking"][26];
	}
}
//Plus utilisé
function authorFormTracking ($ID,$target) {
	GLOBAL $cfg_layout, $lang,$cfg_install;

  if (isAdmin($_SESSION["glpitype"]))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<form method=get name='helpdeskform' action=\"".$target."\">";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["job"][26]."</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["joblist"][3].":</td>";
	echo "<td>";
	dropdownAllUsers("author",$job->fields["author"]);
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td colspan='2' align='center'>";
	echo "<input type='hidden' name='ID' value='$ID'>";
	echo "<input type='submit' name='update_author_ok' value=\"".$lang["buttons"][14]."\" class='submit'>";
	
	echo "</td></tr>";
	echo "</table>";

	echo "</form>";
	}
	else
	{
	 echo $lang["tracking"][27];
	}
}
// Plus utilisé
function priorityFormTracking ($ID,$target) {
	// Print a nice form to assign jobs if user is allowed

	GLOBAL $cfg_layout, $lang;

  if (isAdmin($_SESSION["glpitype"]))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

//	echo "<form method=get action=\"".$target."\">";
	dropdownPriority("priority",$job->fields["priority"]);
	echo "</td><td>";
//	echo "<input type='hidden' name='update' value=\"1\">";
	echo "<input type='submit' name='update' value=\"".$lang["buttons"][14]."\" class='submit'>";
//	echo "</form>";
	}
	else
	{
	 echo $lang["tracking"][21];
	}
}
*/
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
		//$option["comp.osver"]			= $lang["computers"][20];
		$option["processor.designation"]			= $lang["computers"][21];
		//$option["processorspeed"]		= $lang["computers"][22];
		$option["comp.serial"]			= $lang["computers"][17];
		$option["comp.otherserial"]			= $lang["computers"][18];
		$option["ram.designation"]			= $lang["computers"][23];
		//$option["comp.ram"]				= $lang["computers"][24];
		$option["iface.designation"]			= $lang["computers"][26];
		//$option["comp.hdspace"]			= $lang["computers"][25];
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
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$lang["joblist"][24]."</option>";	
	echo "<option value='new' ".($status=="new"?"selected":"").">".$lang["joblist"][9]."</option>";
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
 $elts=array("both"=>$lang["joblist"][6]." / ".$lang["job"][7],"contents"=>$lang["joblist"][6],"followup" => $lang["job"][7]);
 echo "<select name='field2'>";
 foreach ($elts as $key => $val){
 $selected="";
 if ($field2==$key) $selected="selected";
 echo "<option value=\"$key\" $selected>$val</option>";
 
 }
 echo "</select>";
 //echo " </td><td align='center'>";
 
 
 
	 echo "&nbsp;".$lang["search"][2]."&nbsp;";
//	echo "</td><td align='center'>";
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
	echo "</table></div></form>";


}


function showTrackingList($target,$start="",$status="new",$author=0,$assign=0,$assign_type=0,$category=0,$priority=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang,$cfg_devices_tables,$HTMLRel;
		
	$prefs = getTrackingPrefs($_SESSION["glpiID"]);
	
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
//	$query = "select comp.ID from glpi_computers as comp";
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

	if ($contains2!=""&&$field2!="contents") {
		$query.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID)";
	}

	$query.=" WHERE '1' = '1'";

	if ($computers_search)
	$query.=" AND glpi_tracking.device_type= '1'";
	if ($category > 0)
	$query.=" AND glpi_tracking.category = '$category'";
	
	if ($computers_search) $query .= " AND $wherecomp";
	if (!empty($date1)&&$date1!="0000-00-00") $query.=" AND glpi_tracking.date >= '$date1'";
	if (!empty($date2)&&$date2!="0000-00-00") $query.=" AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
	if (!empty($enddate1)&&$enddate1!="0000-00-00") $query.=" AND glpi_tracking.closedate >= '$enddate1'";
	if (!empty($enddate2)&&$enddate2!="0000-00-00") $query.=" AND glpi_tracking.closedate <= adddate( '". $enddate2 ."' , INTERVAL 1 DAY ) ";


	if ($type!=0)
		$query.=" AND glpi_tracking.device_type='$type'";	
	
	if ($item!=0&&$type!=0)
		$query.=" AND glpi_tracking.computer = '$item'";	
	
	if ($contains2!=""){
		switch ($field2){
			case "both" :
			$query.= " AND (glpi_followups.contents LIKE '%".$contains2."%' OR glpi_tracking.contents LIKE '%".$contains2."%')";
			break;
			case "followup" :
			$query.= " AND (glpi_followups.contents LIKE '%".$contains2."%')";
			break;
			case "contents" :
			$query.= " AND (glpi_tracking.contents LIKE '%".$contains2."%')";
			break;
		}
	}


	switch ($status){
	case "new": $query.=" AND glpi_tracking.status = 'new'"; break;
	case "notold": $query.=" AND (glpi_tracking.status = 'new' OR glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign')"; break;
	case "old": $query.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone')"; break;
	case "process": $query.=" AND ( glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' )"; break;

		
	}
	
	if ($assign_type!=0) $query.=" AND glpi_tracking.assign_type = '$assign_type'";
	if ($assign!=0&&$assign_type!=0) $query.=" AND glpi_tracking.assign = '$assign'";
	if ($author!=0) $query.=" AND glpi_tracking.author = '$author'";

	if ($priority>0) $query.=" AND glpi_tracking.priority = '$priority'";
	if ($priority<0) $query.=" AND glpi_tracking.priority >= '".abs($priority)."'";
	
   $query.=" ORDER BY glpi_tracking.date ".$prefs["order"];
//	echo $query;
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
			if (isAdmin($_SESSION["glpitype"])){
			echo "<form method='post' action=\"$target\">";
			}
			
									
			echo "<div align='center'><table border='0' class='tab_cadre' width='90%'>";

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
			if (isAdmin($_SESSION["glpitype"])){
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
			if (isAdmin($_SESSION["glpitype"]))
				echo "</form>";
			
			
			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><strong>".$lang["joblist"][8]."</strong></div>";
			echo "<hr noshade>";
		//	searchFormComputers();
		}
	}
}

function showFollowupsShort($ID) {
	// Print Followups for a job

	GLOBAL $cfg_install, $cfg_layout, $lang;

	// Get Number of Followups

	$job = new Job;
	$job->getFromDB($ID,0);
	$nbfollow=$job->numberOfFollowups();
	if ($nbfollow) {
		echo "<center><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		echo "<tr><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		for ($i=0; $i < $nbfollow; $i++) {
			$fup = new Followup;
			$fup->getFromDB($ID,$i);
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>".$fup->fields["date"]."</td>";
			echo "<td align='center'>".$fup->fields["author"]."</td>";
			echo "<td width='70%'><strong>".$fup->fields["contents"]."</strong></td>";
			echo "</tr>";
		}		

		echo "</table></center>";
	
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
	echo "<option value='new' ".($value=="new"?" selected ":"").">".$lang["joblist"][9]."";
	echo "<option value='assign' ".($value=="assign"?" selected ":"").">".$lang["joblist"][18]."";
	echo "<option value='plan' ".($value=="plan"?" selected ":"").">".$lang["joblist"][19]."";
	echo "<option value='old_done' ".($value=="old_done"?" selected ":"").">".$lang["joblist"][10]."";
	echo "<option value='old_notdone' ".($value=="old_notdone"?" selected ":"").">".$lang["joblist"][17]."";
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
	case "old_done" :
		return $lang["joblist"][10];
		break;
	case "old_notdone" :
		return $lang["joblist"][17];
		break;
	}	
}

function updateTracking($input){
	global $lang;
	$job = new Job;
	$job->getFromDB($input["ID"],0);

	// OK status + author + priority + category + contents 
	// OK emailupdates si defini + uemail
	// type + item si item defini et diff 0
	// assign+assign_type si assign_int ou assign_ext défini
	if (isset($input["item"])&& $input["item"]!=0){
		$input["computer"]=$input["item"];
		$input["device_type"]=$input["type"];
		}
	else if ($input["type"]!=0)
		$input["device_type"]=0;

	if ($input["assign_int"]>0){
		$input["assign_type"]=USER_TYPE;
		$input["assign"]=$input["assign_int"];
		}
	else if ($input["assign_ext"]>0){
		$input["assign_type"]=ENTERPRISE_TYPE;
		$input["assign"]=$input["assign_ext"];
	}

	// add Document if exists
	if (isset($_FILES['filename'])&&count($_FILES['filename'])>0&&$_FILES['filename']["size"]>0){
		$input2=array();
		$input2["name"]=$lang["tracking"][24]." ".$input["ID"];
//		$input2["TOCLEAN"]="CLEAN";
		$docID=addDocument($input2);
		addDeviceDocument($docID,TRACKING_TYPE,$input["ID"]);
	}
		
	// Fill the update-array with changes

	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$job->fields) && $job->fields[$key] != $input[$key]) {
			$job->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	if (in_array("status",$updates)&&ereg("old_",$input["status"])){
		$updates[]="closedate";
		$job->fields["closedate"]=date("Y-m-d H:i:s");
	}
	if(isset($updates))
		$job->updateInDB($updates);




	
}

function showJobDetails ($ID){
	global $cfg_install,$cfg_features,$lang,$HTMLRel;
	$job=new Job();
	$db=new DB();
	if ($job->getfromDB($ID,0)) {

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
		echo "<form method='post' action=\"".$cfg_install["root"]."/tracking/tracking-followups.php\"  enctype=\"multipart/form-data\">\n";
		echo "<table class='tab_cadre' width='90%' cellpadding='5'>";
		// Première ligne
		echo "<tr><th colspan=3>".$lang["job"][0]." ".$job->ID."</th></tr>";
		echo "<tr class='tab_bg_2'>";
		// Premier Colonne
		echo "<td valign='top' width='27%'>";
		echo "<table cellpadding='3'>";
		echo "<tr class='tab_bg_2'><td align='right'>";
		echo $lang["joblist"][0].":</td><td>";
		if (isAdmin($_SESSION['glpitype']))
			dropdownStatus("status",$job->fields["status"]);
		else getStatusName($job->fields["status"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][3].":</td><td>";
		if (isAdmin($_SESSION['glpitype']))
			dropdownAllUsers("author",$job->fields["author"]);
		else $author->getName();
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][2].":</td><td>";
		if (isAdmin($_SESSION['glpitype']))
			dropdownPriority("priority",$job->fields["priority"]);
		else echo getPriorityName($job->fields["priority"]);
		echo "</td></tr>";

		echo "<tr><td>";
		echo $lang["tracking"][20].":</td><td>";
		dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
		echo "</td></tr>";

		echo "</table></td>";

		// Deuxième colonne
		echo "<td valign='top' width='33%'><table border='0'>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][11].":</td><td><strong>".$job->fields["date"]."</strong>";
		echo "</td></tr>";
		echo "<tr><td align='right'>".$lang["joblist"][12].":</td>\n";
		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<td><i>".$lang["job"][1]."</i></td>\n";
		}
		else
		{
			echo "<td><strong>".$job->fields["closedate"]."</strong>\n";
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
			if (isAdmin($_SESSION['glpitype'])){
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
				echo $lang["joblist"][3].":";
				echo "</td><td>";
				if (isAdmin($_SESSION['glpitype'])){
					echo "<input type='text' name='uemail' size='15' value='".$job->fields["uemail"]."'>";
						if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'><img src='".$HTMLRel."pics/edit.png' alt='Mail'></img></a>";
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
		if (isAdmin($_SESSION['glpitype'])){
			echo $item->getType()." - ".$item->getLink()."<br>";
			dropdownAllItems("item",0);
			}
		else echo $item->getType()." ".$item->getNameID();

		echo "</td></tr>";


		echo "<tr><td align='right'>";
		echo $lang["job"][5].":</td><td>";
		echo getAssignName($job->fields["assign"],$job->fields["assign_type"],1);
		if ($job->fields["assign_type"]==USER_TYPE) 
			echo " (".$lang["job"][27].")";
		else echo " (".$lang["job"][28].":)";

		echo "</td></tr>";
		
		if (can_assign_job($_SESSION["glpiname"])){
			echo "<tr><td align='right'>";
			echo $lang["job"][27].":</td><td>";
			dropdownUsers("assign_int",0);
			echo "</td></tr>";

			echo "<tr><td align='right'>";
			echo $lang["job"][28].":</td><td>";
			dropdown("glpi_enterprises","assign_ext",0);
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</td></tr>";
		

		// Deuxième Ligne
		// Colonnes 1 et 2
		echo "<tr class='tab_bg_2'><td colspan='2'>";
		echo "<table width='100%'>";
		echo "<tr><td>".$lang["joblist"][6]."</td>";
		echo "<td><textarea rows='8' cols='60' name='contents'>";
		echo $job->fields["contents"];
		echo "</textarea>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		// Colonne 3
		echo "<td>";

		// File associated ?

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
				echo "</td>";
				$i++;
			}
			if ($i%2==1) echo "<td>&nbsp;></td>";
			echo "</tr>";
		}
		echo "<tr><td colspan='2'>";
		echo "<input type='file' name='filename' size='25'>";
		echo "</td></tr></table>";
		echo "</td></tr>";
		
		// Troisième Ligne
		echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
		echo "<input type='submit' name='update' value='Modifier'></td></tr>";
		
//		echo "</table></td></tr>";
echo "</table>";
echo "<input type='hidden' name='ID' value='$ID'>";
echo "</form>";
echo "<br>";
/*
<table border='1' width='100%'>
<tr><th colspan='2'>Ajouter un nouveau suivi</th></tr>

<tr><td width='70%'>
<table border='1' width='100%'>
<tr><td>Description</td>
<td><textarea rows=8 cols=60></textarea>
</td></tr>
</table>
</td>

<td width='30%'>
<table border='1' width='100%'>

<tr>
<td>Privé</td>
<td><select name='private'>
<option value='no'>Non</option>
<option value='yes'>Oui</option>
</select></td>
</tr>

<tr>
<td>Durée</td>
<td><select name='hour'></select>Heures<select name='minutes'></select>Minutes</td>
</tr>

<tr>
<td>Plannification</td>
<td>Formulaire de plannification</td>
</tr>

<tr>
<td align='center'>
<input type='submit' name='add' value='Ajouter'>
</td>
<td align='center'>
<input type='submit' name='add_and_close' value='Ajouter et fermer intervention'>
</td>
</tr>

</table>
</td></tr>
</table>

<br>

<table border='1' width='100%'>
<tr><th colspan='2'>Suivis</th></tr>

<tr><td width='70%'>
<table border='1' width='100%'>
<tr><td>Description</td>
<td><textarea rows=8 cols=60></textarea>
</td></tr>
</table>
</td>

<td width='30%'>
<table border='1' width='100%'>

<tr>
<td>Privé</td>
<td><select name='private'>
<option value='no'>Non</option>
<option value='yes'>Oui</option>
</select></td>
</tr>

<tr>
<td>Durée</td>
<td><select name='hour'></select>Heures<select name='minutes'></select>Minutes</td>
</tr>

<tr>
<td>Plannification</td>
<td>Lien vers la plannifcation en cours ou petit bouton ajouter une plannification</td>
</tr>

<tr>
<td align='center'>
<input type='submit' name='modify' value='Modifier'>
</td>
<td>
<input type='submit' name='delete' value='Supprimer'>
</td>
</tr>

</table>
</td></tr>

<tr><td width='70%'>
<table border='1' width='100%'>
<tr><td>Description</td>
<td><textarea rows=8 cols=60></textarea>
</td></tr>
</table>
</td>

<td width='30%'>
<table border='1' width='100%'>

<tr>
<td>Privé</td>
<td><select name='private'>
<option value='no'>Non</option>
<option value='yes'>Oui</option>
</select></td>
</tr>

<tr>
<td>Durée</td>
<td><select name='hour'></select>Heures<select name='minutes'></select>Minutes</td>
</tr>

<tr>
<td>Plannification</td>
<td>Lien vers la plannifcation en cours ou petit bouton ajouter une plannification</td>
</tr>

<tr>
<td align='center'>
<input type='submit' name='modify' value='Modifier'>
</td>
<td>
<input type='submit' name='delete' value='Supprimer'>
</td>
</tr>

</table>
</td></tr>

*/
		echo "</table>";
	
	}
	
	
}

?>
