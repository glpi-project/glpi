<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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


function searchFormTracking ($show,$contains,$device,$category) {
	// Tracking Search Block
	
	GLOBAL $cfg_layout, $cfg_install,$lang;
	
	echo "\n<div align='center'>";
	echo "<form method=\"get\" action=\"".$cfg_install["root"]."/tracking/index.php\">";
	echo "<table class='tab_cadre'>";
	echo "<tr><th align='center' colspan='3'>".$lang["tracking"][7]."</th></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>";
	echo "<select name=\"show\" size='1'>";

	echo "<option "; if ($show == "all") { echo "selected"; }
	echo " value=\"all\">".$lang["tracking"][1]."</option>";

	echo "<option "; if ($show == "individual") { echo "selected"; }
	echo " value=\"individual\">".$lang["tracking"][2]."</option>";

	echo "<option "; if ($show == "unassigned") { echo "selected"; }
	echo " value=\"unassigned\">".$lang["tracking"][3]."</option>";

	echo "<option "; if ($show == "old") { echo "selected"; }
	echo " value=\"old\">".$lang["tracking"][4]."</option>";

	echo "</select>";
	echo "</td>";
	echo "<td align='center'><input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>";
	echo "<select name=\"device\" size='1'>";

	echo "<option "; if ($device == "-1") { echo "selected"; }
	echo " value=\"-1\">".$lang["tracking"][12]."</option>";

	echo "<option "; if ($device == '0') { echo "selected"; }
	echo " value='0'>".$lang["tracking"][19]."</option>";

	echo "<option "; if ($device == "1") { echo "selected"; }
	echo " value=\"1\">".$lang["tracking"][13]."</option>";

	echo "<option "; if ($device == "2") { echo "selected"; }
	echo " value=\"2\">".$lang["tracking"][14]."</option>";

	echo "<option "; if ($device == "3") { echo "selected"; }
	echo " value=\"3\">".$lang["tracking"][15]."</option>";
	
	echo "<option "; if ($device == "4") { echo "selected"; }
	echo " value=\"4\">".$lang["tracking"][16]."</option>";
	
	echo "<option "; if ($device == "5") { echo "selected"; }
	echo " value=\"5\">".$lang["tracking"][17]."</option>";

	echo "<option "; if ($device == "6") { echo "selected"; }
	echo " value=\"6\">".$lang["tracking"][18]."</option>";

	echo "</select>";
	echo "</td>";
	echo "<td align='center'><input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";
	echo "<td align='center'><input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'></td>";
	echo "</tr>";

//	echo "</form>";
	//echo "<form method=\"get\" action=\"".$cfg_install["root"]."/tracking/index.php\">";
	echo "<tr class='tab_bg_1'>";
	echo "<td class='tab_bg_2'>";
	echo "<b>".$lang["tracking"][5].":</b> </td><td><input type='text' name='contains' value=\"$contains\" size='15'>";
	echo "</td><td>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr>";

	echo "</table>\n";
	echo "</form>";
	echo "</div><br>\n";
}       

function getTrackingPrefs ($username) {
	// Returns users preference settings for job tracking
	// Currently only supports sort order

	$db = new DB;
	$query = "SELECT tracking_order FROM glpi_prefs WHERE (user = '$username')";
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

function showJobList($target,$username,$show,$contains,$item_type,$item,$start,$device='-1',$category=NULL) {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;
		
	$prefs = getTrackingPrefs($username);

	// Build where-clause
	if ($contains)
	{
		$where = "(contents LIKE \"%$contains%\")";
	}
	else if ($show == "old")
	{
		$where = "(status = 'old')";
	}
	else
	{
		$where = "(status = 'new')";
	}
	if($device != -1) {
		$where .= " AND (device_type = '".$device."')";
	} 

	if($category!='NULL') {
		$where .= " AND (category = '".$category."')";
	} 

	// Build query, two completely different things here, need to be fixed
	// and made into a more featured query-parser

	if ($show == "individual")
	{
		$query = "SELECT ID FROM glpi_tracking WHERE ".$where." and (assign = '".$username."') ORDER BY date ".$prefs["order"]."";
	}
	else if ($show == "user")
	{
		$query = "SELECT ID FROM glpi_tracking WHERE ".$where." and (author = '".$username."') ORDER BY date ".$prefs["order"]."";
	}
	else if ($show == "unassigned")
	{
		$query = "SELECT ID FROM glpi_tracking WHERE ".$where." and (assign ='' OR assign is null) ORDER BY date ".$prefs["order"]."";
	}
	else
	{
		$query = "SELECT ID FROM glpi_tracking WHERE ".$where." ORDER BY date ".$prefs["order"]."";
	}

	if ($item&&$item_type)
	{
		$query = "SELECT ID FROM glpi_tracking WHERE ".$where." and (device_type = '".$item_type."' and computer = '".$item."') ORDER BY date ".$prefs["order"]."";
	}	
	$lim_query = " LIMIT ".$start.",".$cfg_features["list_limit"]."";	

	$db = new DB;
	$result = $db->query($query);
	$numrows = $db->numrows($result);

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

	
	$query .= $lim_query;
	$result = $db->query($query);
	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0) {
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
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&device_type=$item_type\"><b>";
			echo $lang["joblist"][7];
			echo "</b></a>";
			echo "</td></tr>";
		}
		echo "</table></div>";
		// Pager
		if(empty($sort)) $sort = "";
		$parameters="show=".$show."&contains=".$contains."&sort=".$sort."&ID=".$username;
		// Delete selected item
		if (isAdmin($_SESSION["glpitype"])&&$show == "old"){
			echo "<br><div align='center'>";
			echo "<table cellpadding='5' width='90%'>";
			echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt=''></td><td><a href='".$_SERVER["PHP_SELF"]."?$parameters&select=all&start=$start'>".$lang["buttons"][18]."</a></td>";
			
			echo "<td>/</td><td><a href='".$_SERVER["PHP_SELF"]."?$parameters&select=none&start=$start'>".$lang["buttons"][19]."</a>";
			echo "</td><td>";
			echo "<input type='submit' value=\"".$lang["buttons"][17]."\" name='delete' class='submit'></td>";
			echo "<td width='75%'>&nbsp;</td></table></div>";
		}
		
		printPager($start,$numrows,$target,$parameters);
	}
	else
	{
		echo "<br><div align='center'>";
		echo "<table border='0' width='90%'>";
		echo "<tr><th>".$lang["joblist"][8]."</th></tr>";

		if ($item) 
		{
			echo "<tr><td align='center' class='tab_bg_1'>";
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&device_type=$item_type\"><b>";
			echo $lang["joblist"][7];
			echo "</b></a>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
	// End form for delete item
	if ($show=="old"&&isAdmin($_SESSION["glpitype"]))
	echo "</form>";
}

function showOldJobListForItem($username,$item_type,$item) {
	// $item is required
	// affiche toutes les vielles intervention pour un $item donné. 


	GLOBAL $cfg_layout, $cfg_install, $lang,$HTMLRel;
		
	// Form to delete old item
	if (isAdmin($_SESSION["glpitype"])){
		echo "<form method='post' action=\"computers-info-form.php\">";
		echo "<input type='hidden' name='ID' value='$item'>";
		}



	$prefs = getTrackingPrefs($username);
	
$where = "(status = 'old')";	
$query = "SELECT ID FROM glpi_tracking WHERE $where and (device_type = '$item_type' and computer = '$item') ORDER BY date ".$prefs["order"]."";
	

	$db = new DB;
	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%'>";
		echo "<tr><th colspan=8>".$number." ".$lang["job"][18]."  ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";
		echo "<tr><th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["common"][1]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
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
		echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt='' ></td><td><a href='".$_SERVER["PHP_SELF"]."?select=all&ID=$item'>".$lang["buttons"][18]."</a></td>";
			
		echo "<td>/</td><td><a href='".$_SERVER["PHP_SELF"]."?select=none&ID=$item'>".$lang["buttons"][19]."</a>";
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
		
	$prefs = getTrackingPrefs($username);
	
$where = "(status = 'new')";	
$query = "SELECT ID FROM glpi_tracking WHERE $where and (computer = '$item' and device_type= '$item_type') ORDER BY date ".$prefs["order"]."";

	$db = new DB;
	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%'>";
		echo "<tr><th colspan=8>".$number." ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";
		echo "<tr><th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["common"][1]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
		while ($i < $number)
		{
			$ID = $db->result($result, $i, "ID");
			showJobShort($ID, 0);
			$i++;
		}
		if ($item)
		{
			echo "<tr><td align='center' class='tab_bg_2' colspan='8'>";
			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&device_type=$item_type\"><b>";
			echo $lang["joblist"][7];
			echo "</b></a>";
			echo "</td></tr>";
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
			  echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-add-form.php?ID=$item&device_type=$item_type\"><b>";
			  echo $lang["joblist"][7];
			  echo "</b></a>";
			  echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}


function showJobShort($ID, $followups	) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;

	if ($job->getfromDB($ID,0))
	{
		$bgcolor=$cfg_layout["priority_".$job->priority];
		if ($job->status == "new")
		{
			echo "<tr class='tab_bg_1'>";
			echo "<td align='center'  bgcolor='$bgcolor'><font color=\"green\"><b>".$lang["joblist"][9]."</b></font></td>";
			echo "<td width='100'  bgcolor='$bgcolor'><small>".$lang["joblist"][11].":<br>&nbsp;$job->date</small></td>";

		}
		else
		{
 			echo "<tr class='tab_bg_1'>";
			echo "<td align='center'  bgcolor='$bgcolor'><b>".$lang["joblist"][10]."</b>";
			if (isAdmin($_SESSION["glpitype"])){
				$sel="";
				if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
			echo "<br><input type='checkbox' name='todel[".$job->ID."]' value='1' $sel>";
			}
			echo "</td>";
			echo "<td width='130' bgcolor='$bgcolor'><small>".$lang["joblist"][11].":<br>&nbsp;$job->date<br>";
			echo "<i>".$lang["joblist"][12].":<br>&nbsp;$job->closedate</i>";
			if ($job->realtime>0) echo "<br>".$lang["job"][20].": <br>".getRealtime($job->realtime);
			echo "</small></td>";
		}

		echo "<td align='center' bgcolor='$bgcolor'><b>$job->priority</b></td>";
		
		echo "<td align='center'  bgcolor='$bgcolor'>";

		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<a href=\"".$cfg_install["root"]."/setup/users-info.php?ID=$job->author\"><b>$job->author</b></a>";
		else
		echo "<b>$job->author</b>";

		echo "</td>";

		if ($job->assign == "")
		{
			echo "<td align='center' bgcolor='$bgcolor'>[Nobody]</td>"; 
	    	}
		else
		{
			echo "<td align='center' bgcolor='$bgcolor'>";
			if (strcmp($_SESSION["glpitype"],"post-only")!=0)
			echo "<a href=\"".$cfg_install["root"]."/setup/users-info.php?ID=$job->assign\"><b>$job->assign</b></a>";
			else
			echo "<b>$job->assign</b>";

//			$job->assign
			
			echo "</td>";
		}    
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0){
			echo "<td align='center' bgcolor='$bgcolor'>";
			$m= new CommonItem;
			$m->getfromDB($job->device_type,$job->computer);
			echo $m->getType()."<br>";
			echo "<b>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</b>";
/*			if ($job->computerfound)	echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$job->computer\">";
			echo "<b>$job->computername ($job->computer)</b>";
			if ($job->computerfound) echo "</a>";
*/			
			echo "</td>";
		}
		else
		echo "<td  align='center' bgcolor='$bgcolor'><b>$job->computername ($job->computer)</b></td>";


		echo "<td  align='center' bgcolor='$bgcolor'><b>".getDropdownName("glpi_dropdown_tracking_category",$job->category)."</b></td>";
		
		$stripped_content=$job->contents;
		if (!$followups) $stripped_content =substr(unhtmlentities_deep($job->contents),0,$cfg_features["cut"]);
		echo "<td bgcolor='$bgcolor'><b>$stripped_content</b>";
		if ($followups)
		{
			showFollowupsShort($job->ID);
		}

		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center' bgcolor='$bgcolor'>";
		
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-followups.php?ID=$job->ID\"><b>".$lang["joblist"][13]."</b></a>&nbsp;($job->num_of_followups)&nbsp;<br>";
		else
		echo "<a href=\"".$cfg_install["root"]."/helpdesk.php?show=user&ID=$job->ID\">".$lang["joblist"][13]."</a>&nbsp;($job->num_of_followups)&nbsp;<br>";
//		if ($job->status == "new"&&strcmp($_SESSION["glpitype"],"post-only")!=0)
//		{
//			echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-mark.php?ID=$job->ID\">".$lang["joblist"][14]."</a><br>";
//		}
//		if(strcmp($_SESSION["glpitype"],"post-only")!=0)
//		echo "<a href=\"".$cfg_install["root"]."/tracking/tracking-assign-form.php?ID=$job->ID\">".$lang["joblist"][15]."</a></b></td>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
    echo "<tr class='tab_bg_2'><td colspan=6 bgcolor='$bgcolor'><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

function showJobDetails($ID) {
	// Prints a job in long form with all followups and stuff

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang;
	
	// Make new job object and fill it from database, if success, print it

	$job = new Job;
	
	if ($job->getfromDB($ID,0)) {

		// test if the user if authorized to view this job
		if (strcmp($_SESSION["glpitype"],"post-only")==0&&!strcmp($_SESSION["glpiname"],$job->author)==0)
		   { echo "Warning !! ";return;}

		echo "<div align='center'><table class='tab_cadre' width='90%' cellpadding='5'>\n";
		echo "<tr><th colspan='3'>".$lang["job"][0]." $job->ID:</th></tr>";
		echo "<tr class='tab_bg_2'>";
		echo "<td width='33%' rowspan='1'>";

		echo "<table cellpadding='2' cellspacing='0' border='0' >";

		echo "<tr><td>".$lang["joblist"][0].":</td><td>";
		if ($job->status == "new") { 
			echo "<font color=\"green\"><b>".$lang["joblist"][9]."</b></font>"; }
		else {
			echo "<b>".$lang["joblist"][10]."</b>";
		}
		echo "</td></tr>";

		echo "<tr><td>".$lang["joblist"][2].":</td><td><b>";
		echo "<b>".$job->priority."</b></td></tr>";	

		echo "<tr><td>".$lang["joblist"][3].":</td><td>";
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		echo "<b><a href=\"".$cfg_install["root"]."/setup/users-info.php?ID=$job->author\">$job->author</a></b>";
		else 
		echo "<b>$job->author</b>";
		echo "</td></tr>";

		$m= new CommonItem;
		$m->getfromDB($job->device_type,$job->computer);

		echo "<tr><td>".$m->getType().":</td><td>";
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		{
			echo "<b>";
			if ($job->computerfound) echo $m->getLink();
			else echo $m->getNameID();
			echo "</b>";

/*			if ($job->computerfound)	echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=$job->computer\">";
			echo "<b>$job->computername ($job->computer)</b>";
			if ($job->computerfound) echo "</a>";
*/			
		}
		else
		echo "<b>$job->computername ($job->computer)</b>";
		echo "</td></tr>";
		echo "</table>";

		echo "</td>";

		echo "<td>";
		echo "<table cellpadding='2' cellspacing='0' border='0'>";
		echo "<tr><td align='right'>".$lang["joblist"][11].":</td>";
		echo "<td><b>".$job->date."</b></td></tr>";
		echo "<tr><td align='right'>".$lang["joblist"][12].":</td>";
//		if ($job->closedate == "0000-00-00 00:00:00" || $job->closedate == "")
		if ($job->status=="new")
		{
			echo "<td><i>".$lang["job"][1]."</i></td></tr>";
		}
		else
		{
			echo "<td><b>$job->closedate</b></tr>";
			if ($job->realtime>0)
			echo "<tr><td align='right'>".$lang["job"][20].":</td><td><b>".getRealtime($job->realtime)."</b></td></tr>";
		}
		if ($cfg_features["mailing"]==1){
		if ($job->emailupdates=='yes') $suivi=$lang["choice"][0];
		else $suivi=$lang["choice"][1];
		echo "<tr><td>".$lang["job"][19].":</td><td>$suivi</td></tr>";
		}
		echo "</table>";
		echo "</td>";
	
		//echo "</tr><tr class='tab_bg_2'>";
		
		echo "<td align='center'>";	
		if (can_assign_job($_SESSION["glpiname"]))
			assignFormTracking($ID,$_SESSION["glpiname"],$cfg_install["root"]."/tracking/tracking-assign-form.php");
		else echo $lang["job"][5]." <b>".($job->assign==""?"[Nobody]":$job->assign)."</b>";
		
		if (can_assign_job($_SESSION["glpiname"]))
			categoryFormTracking($ID,$cfg_install["root"]."/tracking/tracking-category-form.php");
		else echo $lang["job"][5]." <b>".($job->assign==""?"[Nobody]":$job->assign)."</b>";
		
		
		echo "</td>";
		
		echo "</tr><tr class='tab_bg_2'>";
		
		echo "<td colspan='3'>";
		echo "<table><tr><td width='90'>";
		echo $lang["joblist"][6].":";
		echo "</td><td>";
		echo "<b>$job->contents</b>";		
		echo "</td></tr></table>";


		echo "</td>";
		
		echo "</tr>";
	
		if (strcmp($_SESSION["glpitype"],"post-only")!=0)
		if ($job->status == "new") {
			$hour=floor($job->realtime);
			$minute=round(($job->realtime-$hour)*60,0);
			echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-mark.php\">";
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='3' align='center'>";
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
			echo "</td></tr>";
			echo "</form>";

		}
		else if ($job->status == "old") {
			echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-mark.php\">";
			echo "<tr class='tab_bg_1'>";
			echo "<td colspan='3' align='center'>";

			echo "<input type='hidden' name='ID' value=$job->ID>";			
			echo "<input type='hidden' name='status' value='new'>";			
			echo "<input type='submit' name='restore' value=\"".$lang["job"][23]."\" class='submit'>";
			echo "</td></tr>";

			echo "</form>";

			}
		echo "</table>";
		echo "<br><br><table width='90%' class='tab_cadre'><tr><th>".$lang["job"][7].":</th></tr>";
		echo "</table></div>";

		showFollowups($job->ID);  
                

	} 
	else
	{
    		echo "<tr class='tab_bg_2'><td colspan=6><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

function postJob($device_type,$ID,$author,$status,$priority,$isgroup,$uemail,$emailupdates,$contents,$assign="",$realtime=0) {
	// Put Job in database

	GLOBAL $cfg_install, $cfg_features, $cfg_layout;
	
	$job = new Job;

	if (!$isgroup) {
		$job->isgroup = "no";
	} else {
		$job->isgroup = "yes";
	}
	$job->status = $status;
	$job->author = $author;
	$job->device_type = $device_type;
	if ($device_type==0)
	$job->computer=0;
	else 
	$job->computer = $ID;
	$job->contents = $contents;
	$job->priority = $priority;
	$job->uemail = $uemail;
	$job->emailupdates = $emailupdates;
	$job->assign = $assign;
	$job->realtime = $realtime;

	// ajout suite  à tracking sur tous les items 
			
	switch ($device_type) {
	case 1 :
	$item = "computers";
	break;
	case 2 :
	$item = "networking";
	break;
	case 3 :
	$item = "printers";
	break;
	case 4 :
	$item = "monitors";
	break;
	case 5 :
	$item = "peripherals";
	break;
	case 6 :
	$item = "software";
	}
	
	
	if ($job->putinDB()) {
		// Log this event
		logEvent($ID,$item,4,"tracking","$author added new job.");
		
		// Processing Email
		if ($cfg_features["mailing"])
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

function assignJob ($ID,$user,$admin) {
	// Assign a job to someone

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$job = new Job;
	$job->getFromDB($ID,0);

	$newuser=$user;
	$olduser=$job->assign;
			
	$job->assignTo($user);

	// Add a Followup for a assignment change
	if (strcmp($newuser,$olduser)!=0){
	$content=date("Y-m-d H:i:s").": ".$lang["mailing"][12].": ".$olduser." -> ".$newuser." (".$_SESSION["glpiname"].")";
	postFollowups ($ID,$_SESSION["glpiname"],addslashes($content));
	}
}

function categoryJob ($ID,$category,$admin) {
	// Assign a category to a job

	GLOBAL $cfg_features, $cfg_layout,$lang;	
	$job = new Job;
	$job->getFromDB($ID,0);

	$job->categoryTo($category);

	// Add a Followup for a assignment change
/*	if (strcmp($newuser,$olduser)!=0){
	$content=date("Y-m-d H:i:s").": ".$lang["mailing"][12].": ".$olduser." -> ".$newuser." (".$_SESSION["glpiname"].")";
	postFollowups ($ID,$_SESSION["glpiname"],addslashes($content));
	}
*/	
}


function showFollowups($ID) {
	// Print Followups for a job

	GLOBAL $cfg_install, $cfg_layout, $lang;

	// Get Number of Followups

	$job = new Job;
	$job->getFromDB($ID,0);

	if ($job->num_of_followups) {
		echo "<div align='center'><table class='tab_cadre' width='90%' cellpadding='2'>\n";
		echo "<tr><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		for ($i=0; $i < $job->num_of_followups; $i++) {
			$fup = new Followup;
			$fup->getFromDB($ID,$i);
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>$fup->date</td>";
			echo "<td align='center'>$fup->author</td>";
			echo "<td width=70%><b>$fup->contents</b></td>";
			echo "</tr>";
		}		

		echo "</table></div>";
	
	} else {
		echo "<div align='center'><b>".$lang["job"][8]."</b></div>";
	}

	// Show input field only if job is still open
	if(strcmp($_SESSION["glpitype"],"post-only")!=0)
	if ($job->status=="new") {
		echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%'>\n\n";
		echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-followups.php\">";
		echo "<input type='hidden' name=ID value=$ID>";
		echo "<tr><th>".$lang["job"][9].":</th></tr>";
		echo "<tr class='tab_bg_1'><td width='100%' align='center'><textarea cols='60' rows='5' name='contents' ></textarea></td></tr>";
		echo "<tr><td align='center' class='tab_bg_1'>";
		echo "<input type='submit' name='add_followup' value=\"".$lang["buttons"][2]."\" class='submit'></td>";
		echo "</tr></form></table></div>";
	}

}

function postFollowups ($ID,$author,$contents) {

	GLOBAL $cfg_install, $cfg_features, $cfg_layout;
	
	$fup = new Followup;
	
	$fup->tracking = $ID;
	$fup->author = $author;
	$fup->contents = $contents;
	
	if ($fup->putInDB()) {
		// Log this event
		$fup->logFupUpdate();
		// Processing Email
		if ($cfg_features["mailing"])
		{
			$job= new Job;
			$job->getfromDB($ID,0);
			$user=new User;
			$user->getfromDB($author);
			$mail = new Mailing("followup",$job,$user);
			$mail->send();
		}
	} else {
		echo "Couldn't post followup.";
	}
}

function addFormTracking ($device_type,$ID,$author,$assign,$target,$error,$searchauthor='') {
	// Prints a nice form to add jobs

	GLOBAL $cfg_layout, $lang,$cfg_features;

	if (!empty($error)) {
		echo "<div align='center'><b>$error</b></div>";
	}
	echo "<form method='get' action='$target'>";
	echo "<div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='4'>".$lang["job"][13].": <br>";
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
			if ($_GET["hour"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
			}			
		
			echo "</select>".$lang["job"][21]."&nbsp;&nbsp;";
			echo "<select name='minute'>";
			for ($i=0;$i<60;$i++){
			$selected="";
			if ($_GET["minute"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
			}
			echo "</select>".$lang["job"][22]."&nbsp;&nbsp;";
			echo "</td></tr>";


	echo "<tr><td class='tab_bg_2' align='center'>".$lang["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2' colspan='3'><select name='priority'>";
	echo "<option value='5' ";
	echo ">".$lang["joblist"][17]."</option>";
	if (isset($_GET["priority"])&&$_GET["priority"]==5) echo "selected";
	echo "<option value='4' ";
	if (isset($_GET["priority"])&&$_GET["priority"]==4) echo "selected";
	echo ">".$lang["joblist"][18]."</option>";
	echo "<option value='3' ";
	if (!isset($_GET["priority"])||$_GET["priority"]==3) echo "selected";	
	echo ">".$lang["joblist"][19]."</option>";
	echo "<option value='2'";
	if (isset($_GET["priority"])&&$_GET["priority"]==2) echo "selected";	
	echo ">".$lang["joblist"][20]."</option>";
	echo "<option value='1'";
	if (isset($_GET["priority"])&&$_GET["priority"]==1) echo "selected";	
	echo ">".$lang["joblist"][21]."</option>";
	echo "</select></td></tr>";

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][3].":</td>";
	
	echo "<td align='center'>";

	dropdownValueSearch("glpi_users","user",$author,$searchauthor);
	echo "</td>";
        echo "<td><input type='text' size='10'  name='search'></td>";
	echo "<td><input type='submit' value=\"".$lang["buttons"][0]."\" name='Modif_Interne' class='submit'>";
	echo "</td></tr>";
	

	echo "<tr class='tab_bg_2' align='center'><td>".$lang["joblist"][15].":</td>";
	
	echo "<td align='center' colspan='3'>";
	dropdownUsers($assign,"assign");
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

function assignFormTracking ($ID,$admin,$target) {
	// Print a nice form to assign jobs if user is allowed

	GLOBAL $cfg_layout, $lang;


  if (can_assign_job($admin))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<table class='tab_cadre'>";
	echo "<tr><th>".$lang["job"][4]." $ID:</th></tr>";
	echo "<form method=get action=\"".$target."\">";
	echo "<td align='center' class='tab_bg_1'>";

	echo "<table border='0'>";
	echo "<tr>";
	echo "<td>".$lang["job"][5].":</td><td>";
		dropdownUsers($job->assign, "user");
	echo "<input type='hidden' name='update' value=\"1\">";
	echo "<input type='hidden' name='ID' value=$job->ID>";
	echo "</td><td><input type='submit' value=\"".$lang["job"][6]."\" class='submit'></td>";
	echo "</tr></table>";

	echo "</td>";
	echo "</form>";
	echo "</tr></table>";
	}
	else
	{
	 echo $lang["tracking"][6];
	}
}
function categoryFormTracking ($ID,$target) {
	// Print a nice form to assign jobs if user is allowed

	GLOBAL $cfg_layout, $lang;

  if (isAdmin($_SESSION["glpitype"]))
  {

	$job = new Job;
	$job->getFromDB($ID,0);

	echo "<table class='tab_cadre'>";
	echo "<tr><th>".$lang["job"][24]." $ID:</th></tr>";
	echo "<form method=get action=\"".$target."\">";
	echo "<td align='center' class='tab_bg_1'>";

	echo "<table border='0'>";
	echo "<tr>";
	echo "<td>".$lang["tracking"][20].":</td><td>";
		dropdownValue("glpi_dropdown_tracking_category","category",$job->category);
	echo "<input type='hidden' name='update' value=\"1\">";
	echo "<input type='hidden' name='ID' value=$job->ID>";
	echo "</td><td><input type='submit' value=\"".$lang["buttons"][14]."\" class='submit'></td>";
	echo "</tr></table>";

	echo "</td>";
	echo "</form>";
	echo "</tr></table>";
	}
	else
	{
	 echo $lang["tracking"][21];
	}
}

function getRealtime($realtime){
		global $lang;	
		$output="";
		$hour=floor($realtime);
		if ($hour>0) $output.=$hour." ".$lang["job"][21]." ";
		$output.=round((($realtime-floor($realtime))*60))." ".$lang["job"][22];
		return $output;
		}

function searchFormTrackingReport() {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	
	$option["comp.ID"]				= $lang["computers"][31];
	$option["comp.name"]				= $lang["computers"][7];
	$option["glpi_dropdown_locations.name"]			= $lang["computers"][10];
	$option["glpi_type_computers.name"]				= $lang["computers"][8];
	$option["glpi_dropdown_os.name"]				= $lang["computers"][9];
	$option["comp.osver"]			= $lang["computers"][20];
	$option["comp.processor"]			= $lang["computers"][21];
	$option["comp.processor_speed"]		= $lang["computers"][22];
	$option["comp.serial"]			= $lang["computers"][17];
	$option["comp.otherserial"]			= $lang["computers"][18];
	$option["glpi_dropdown.ram.name"]			= $lang["computers"][23];
	$option["comp.ram"]				= $lang["computers"][24];
	$option["glpi_dropdown_network.name"]			= $lang["computers"][26];
	$option["comp.hdspace"]			= $lang["computers"][25];
	$option["glpi_dropdown_sndcard.name"]			= $lang["computers"][33];
	$option["glpi_dropdown_gfxcard.name"]			= $lang["computers"][34];
	$option["glpi_dropdown_moboard.name"]			= $lang["computers"][35];
	$option["glpi_dropdown_hdtype.name"]			= $lang["computers"][36];
	$option["comp.comments"]			= $lang["computers"][19];
	$option["comp.contact"]			= $lang["computers"][16];
	$option["comp.contact_num"]		        = $lang["computers"][15];
	$option["comp.date_mod"]			= $lang["computers"][11];

	echo "<form method=get name=\"form\" action=\"".$_SERVER["PHP_SELF"]."\">";
	
	echo "<div align='center'><p><b>".$lang["reports"][25]."</b></p></div>";
	echo "<div align='center'>";
				
	echo "<table border='0' width='760' class='tab_cadre'>";

	
	echo "<tr><th colspan='7'><b>".$lang["search"][0].":</b></th></tr>";



	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
 $elts=array("both"=>$lang["joblist"][6]." / ".$lang["job"][7],"contents"=>$lang["joblist"][6],"followup" => $lang["job"][7]);
 echo "<select name='field2'>";
 foreach ($elts as $key => $val){
 $selected="";
 if ($_GET["field2"]==$key) $selected="selected";
 echo "<option value='$key' $selected>$val</option>";
 
 }
 echo "</select>";
 echo " </td><td align='center'><select name='phrasetype2' size='1' >";
	$selected="";
	if ($_GET["phrasetype2"]=="contains") $selected="selected";
	echo "<option value='contains' $selected>".$lang["search"][2]."</option>";
	$selected="";
	if ($_GET["phrasetype2"]=="exact") $selected="selected";
	echo "<option value='exact' $selected>".$lang["search"][3]."</option>";
	echo "</select></td><td align='center'>";
	echo "<input type='text' size='15' name=\"contains2\" value=\"".$_GET["contains2"]."\">";
	echo "</td><td  colspan='2' align='center'>".$lang["job"][5]."&nbsp;:&nbsp;";
	dropdownUsersTracking($_GET["attrib"],"attrib","assign");
	echo "</td>";
	echo "<td  colspan='1' align='center'>".$lang["joblist"][3]."&nbsp;:&nbsp;";
	dropdownUsersTracking($_GET["author"],"author","author");
	echo "</td><td align='center'>".$lang["tracking"][20]."&nbsp;:&nbsp;";
	dropdownValue("glpi_dropdown_tracking_category","category",$_GET["category"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'>";
	echo "<td align='center' colspan='2'>";
	$selected="";
	if ($_GET["only_computers"]) $selected="checked";
	echo "<input type='checkbox' name='only_computers' value='1' $selected>".$lang["reports"][24].":</td><td align='center'>";

	echo "<select name=\"field\" size='1'>";
		$selected="";
		if (!isset($_GET["field"])||$_GET["field"]=="all") $selected="selected";
        echo "<option value='all' $selected>".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		$selected="";
		if (isset($_GET["field"])&&$_GET["field"]==$key) $selected="selected";
		
		echo "<option value=$key $selected>$val\n";
	}
	echo "</select></td><td align='center' colspan='3'>";
	echo $lang["search"][1];
	echo "&nbsp;:&nbsp;<select name='phrasetype' size='1' >";
	$selected="";
	if ($_GET["phrasetype"]=="contains") $selected="selected";
	echo "<option value='contains' $selected>".$lang["search"][2]."</option>";
	$selected="";
	if ($_GET["phrasetype"]=="exact") $selected="selected";
	echo "<option value='exact' $selected>".$lang["search"][3]."</option>";
	echo "</select></td><td align='center' colspan='2'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"".$_GET["contains"]."\">";
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td align='center' colspan='2'>".$lang["search"][8].":&nbsp;<input type=\"texte\" readonly size='10' name=\"date1\" value=\"". $_GET["date1"] ."\" />";
echo "&nbsp;&nbsp;<input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date1&amp;value=".$_GET["date1"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>";
echo "&nbsp;&nbsp;<input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date1.value=''\" value='".$lang["buttons"][16]."'></td><td align='center' colspan='4'>";
echo $lang["search"][9].":&nbsp;<input type=\"texte\" readonly  size='10' name=\"date2\" value=\"". $_GET["date2"] ."\" />";
echo "&nbsp;&nbsp;<input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=date2&amp;value=".$_GET["date2"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."'>&nbsp;&nbsp;";
echo "<input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].date2.value=''\" value='".$lang["buttons"][16]."'>";
echo "</td><td align='center'><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'></td></tr></table></div></form>";


}


function showTrackingListReport($target,$username,$field,$phrasetype,$contains,$start,$date1,$date2,$computers_search,$field2,$phrasetype2,$contains2,$author,$assign,$category) {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang;
		
	$prefs = getTrackingPrefs($username);
	$db=new DB;
	// Reduce computer list
	if ($computers_search){
	if($field == "all") {
		$wherecomp = " (";
		$fields = $db->list_fields("glpi_computers");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$wherecomp .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			if(IsDropdown($coco)) {
				$wherecomp .= " glpi_dropdown_". $coco .".name LIKE '%".$contains."%'";
			}
			elseif($coco == "ramtype") {
				$wherecomp .= " glpi_dropdown_ram.name LIKE '%".$contains."%'";
			}
			elseif($coco == "location") {
				$wherecomp .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			elseif($coco == "type") {
				$wherecomp .= " glpi_type_computers.name LIKE '%".$contains."%'";
			}
			else {
   				$wherecomp .= "comp.".$coco . " LIKE '%".$contains."%'";
			}
		}
		$wherecomp .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$wherecomp = "($field LIKE '%".$contains."%')";
		}
		else {
			$wherecomp = "($field LIKE '".$contains."')";
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
	$query.= " LEFT JOIN glpi_dropdown_locations on comp.location=glpi_dropdown_locations.ID ";
	$query .= "LEFT JOIN glpi_dropdown_os on comp.os=glpi_dropdown_os.ID LEFT JOIN glpi_type_computers on comp.type = glpi_type_computers.ID ";
	$query .= "LEFT JOIN glpi_dropdown_hdtype on comp.hdtype = glpi_dropdown_hdtype.ID LEFT JOIN glpi_dropdown_processor on comp.processor = glpi_dropdown_processor.ID ";
	$query .= "LEFT JOIN glpi_dropdown_ram on comp.ramtype = glpi_dropdown_ram.ID LEFT JOIN glpi_dropdown_network on comp.network = glpi_dropdown_network.ID ";
	$query .= "LEFT JOIN glpi_dropdown_gfxcard on comp.gfxcard = glpi_dropdown_gfxcard.ID LEFT JOIN glpi_dropdown_moboard on comp.moboard = glpi_dropdown_moboard.ID ";
	$query .= "LEFT JOIN glpi_dropdown_sndcard on comp.sndcard = glpi_dropdown_sndcard.ID ";
	}

	if ($contains2!=""&&$field2!="content") {
		if ($phrasetype2 == "contains") {
			$query.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID AND (glpi_tracking.contents LIKE '%".$contains2."%'))";
		}
		else {
			$query.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID AND (glpi_tracking.contents LIKE '".$contains2."'))";
		}
	}


	$query.=" WHERE '1' = '1'";
	
	if ($computers_search)
	$query.=" AND glpi_tracking.device_type= '1'";
	if ($category > 0)
	$query.=" AND glpi_tracking.category = '$category'";
	
	if ($computers_search) $query .= "AND $wherecomp";
	if ($date1!="") $query.=" AND glpi_tracking.date >= '$date1'";
	if ($date2!="") $query.=" AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
	
	if ($contains2!=""&&$field2!="followup") {
		if ($phrasetype2 == "contains") {
			$query.= " AND (glpi_tracking.contents LIKE '%".$contains2."%')";
		}
		else {
			$query.= " AND (glpi_tracking.contents LIKE '".$contains2."')";
		}
	}

	if ($assign!="all") $query.=" AND glpi_tracking.assign = '$assign'";
	if ($author!="all") $query.=" AND glpi_tracking.author = '$author'";
	
   $query.=" ORDER BY ID";

	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows= $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query. " LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		
		if ($numrows_limit>0) {
			// Produce headline
			
						
			echo "<div align='center'><table border='0' class='tab_cadre' width='90%'><tr>";

echo "<th>".$lang["joblist"][0]."</th><th>".$lang["joblist"][1]."</th>";
		echo "<th width=5>".$lang["joblist"][2]."</th><th>".$lang["joblist"][3]."</th>";
		echo "<th>".$lang["joblist"][4]."</th><th>".$lang["joblist"][5]."</th>";
		echo "<th>".$lang["tracking"][20]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th>";
			echo "</tr>";
			for ($i=0; $i < $numrows_limit; $i++) {
				
				$ID = $db->result($result_limit, $i, "ID");
				showJobShort($ID, 1);
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&date1=$date1&date2=$date2&only_computers=$computers_search&field2=$field2&phrasetype2=$phrasetype2&contains2=$contains2&attrib=$assign&author=$author";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["joblist"][8]."</b></div>";
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

	if ($job->num_of_followups) {
		echo "<center><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		echo "<tr><th>".$lang["joblist"][1]."</th><th>".$lang["joblist"][3]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		for ($i=0; $i < $job->num_of_followups; $i++) {
			$fup = new Followup;
			$fup->getFromDB($ID,$i);
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center'>$fup->date</td>";
			echo "<td align='center'>$fup->author</td>";
			echo "<td width=70%><b>$fup->contents</b></td>";
			echo "</tr>";
		}		

		echo "</center></table>";
	
	}
}

?>
