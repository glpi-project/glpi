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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// FUNCTIONS Tracking System

/**
 * Print "onglets" (on the top of items forms)
 *
 * Print "onglets" for a better navigation.
 *
 *@param $target filename : The php file to display then
 *
 *@return nothing (diplays)
 *
 **/
function showTrackingOnglets($target){
	global $LANG,$CFG_GLPI;

	if (preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
		$ID=$ereg[1];

		$job=new Job();
		$job->getFromDB($ID);

		echo "<div id='barre_onglets'><ul id='onglet'>";

		if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
			echo "<li class='actif'><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=$ID&amp;onglet=1\">".$LANG["job"][38]." $ID</a></li>";

			if (haveRight("show_all_ticket","1")){
				displayPluginHeadings($target,TRACKING_TYPE,"","");
			}

			echo "<li class='invisible'>&nbsp;</li>";


			// admin yes  
			if ($job->canAddFollowups()){
				echo "<li onClick=\"showAddFollowup();\" id='addfollowup'><a href='#viewfollowup' class='fake'>".$LANG["job"][29]."</a></li>";
			}


			// Post-only could'nt see other item  but other user yes 
			if (haveRight("show_all_ticket","1")){
				echo "<li class='invisible'>&nbsp;</li>";

				$next=getNextItem("glpi_tracking",$ID,"","ID");
				$prev=getPreviousItem("glpi_tracking",$ID,"","ID");
				$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
				if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a></li>";
				if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a></li>";
			}
		}elseif (haveRight("comment_ticket","1")){

			// Postonly could post followup in helpdesk area	
			echo "<li class='actif'><a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=$ID\">".$LANG["job"][38]." $ID</a></li>";

			if (!strstr($job->fields["status"],"old_")&&$job->fields["author"]==$_SESSION["glpiID"]){
				echo "<li class='invisible'>&nbsp;</li>";

				echo "<li onClick=\"showAddFollowup();\" id='addfollowup'><a href='#viewfollowup' class='fake'>".$LANG["job"][29]."</a></li>";
			}
		}

	}

	echo "</ul></div>";	 

}

/**
 * get the allowed Soft options for the tickets list
 * 
 * @return array of options (title => field)
 */
function &getTrackingSortOptions() {
	global $LANG,$CFG_GLPI;
	static $items=array();
	
	if (!count($items)) {		
		$items[$LANG["joblist"][0]]="glpi_tracking.status";
		$items[$LANG["common"][27]]="glpi_tracking.date";
		$items[$LANG["common"][26]]="glpi_tracking.date_mod";
		if (count($_SESSION["glpiactiveentities"])>1){
			$items[$LANG["Menu"][37]]="glpi_entities.completename";
		}
		$items[$LANG["joblist"][2]]="glpi_tracking.priority";
		$items[$LANG["job"][4]]="glpi_tracking.author";
		$items[$LANG["joblist"][4]]="glpi_tracking.assign";
		$items[$LANG["common"][1]]="glpi_tracking.device_type,glpi_tracking.computer";
		$items[$LANG["common"][36]]="glpi_dropdown_tracking_category.completename";
		$items[$LANG["common"][57]]="glpi_tracking.name";
	}
	return ($items);
}

function commonTrackingListHeader($output_type=HTML_OUTPUT,$target="",$parameters="",$sort="",$order="",$nolink=false){
	global $LANG,$CFG_GLPI;

	// New Line for Header Items Line
	echo displaySearchNewLine($output_type);
	// $show_sort if 
	$header_num=1;


	foreach (getTrackingSortOptions() as $key => $val){
		$issort=0;
		$link="";
		if (!$nolink){
			if ($sort==$val) $issort=1;
			$link=$target."?".$parameters."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;sort=$val";
			if (strpos($target,"helpdesk.public.php")){
				$link.="&amp;show=user";
			}
		}
		
		echo displaySearchHeaderItem($output_type,$key,$header_num,$link,$issort,$order);
	}

	// End Line for column headers		
	echo displaySearchEndLine($output_type);
}

function getTrackingOrderPrefs ($ID) {
	// Returns users preference settings for job tracking
	// Currently only supports sort order


	if($_SESSION["glpitracking_order"])
	{
		return "DESC";
	} 
	else
	{
		return "ASC";
	}

}

function showCentralJobList($target,$start,$status="process",$showgrouptickets=true) {
	

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_all_ticket","1")&&!haveRight("show_assign_ticket","1")) return false;

	$search_assign="assign = '".$_SESSION["glpiID"]."'";
	if ($showgrouptickets){
		if (count($_SESSION['glpigroups'])){
			$groups=implode("','",$_SESSION['glpigroups']);
			$search_assign.= " OR assign_group IN ('$groups') ";
		}
	}


	if($status=="waiting"){ // on affiche les tickets en attente
		$query = "SELECT ID FROM glpi_tracking " .
				" WHERE ( $search_assign ) AND status ='waiting' ".getEntitiesRestrictRequest("AND","glpi_tracking").
				" ORDER BY date_mod ".getTrackingOrderPrefs($_SESSION["glpiID"]);
		
		if($showgrouptickets){
			$title=$LANG["central"][16];
		}else{
 			$title=$LANG["central"][11];
		}

	}else{ // on affiche les tickets planifiés ou assignés à glpiID

		$query = "SELECT ID FROM glpi_tracking " .
				" WHERE ( $search_assign ) AND (status ='plan' OR status = 'assign') ".getEntitiesRestrictRequest("AND","glpi_tracking").
				" ORDER BY date_mod ".getTrackingOrderPrefs($_SESSION["glpiID"]);
		
		if($showgrouptickets){
			$title=$LANG["central"][15];
		}else{
			$title=$LANG["central"][9];
		}
	}

	$lim_query = " LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);	

	$result = $DB->query($query);
	$numrows = $DB->numrows($result);

	$query .= $lim_query;

	$result = $DB->query($query);
	$i = 0;
	$number = $DB->numrows($result);

	if ($number > 0) {
		echo "<table class='tab_cadrehov'>";

		$link="assign=mine&amp;status=$status&amp;reset=reset_before";
		// Only mine
		if (!$showgrouptickets&&(haveRight("show_all_ticket","1")||haveRight("show_assign_ticket",'1'))){
			$link="assign=".$_SESSION["glpiID"]."&amp;status=$status&amp;reset=reset_before";
		}

		echo "<tr><th colspan='5'><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?$link\">".$title."</a></th></tr>";
		echo "<tr><th></th>";
		echo "<th>".$LANG["job"][4]."</th>";
		echo "<th>".$LANG["common"][1]."</th>";
		echo "<th>".$LANG["joblist"][6]."</th></tr>";
		while ($i < $number) {
			$ID = $DB->result($result, $i, "ID");
			showJobVeryShort($ID);
			$i++;
		}
		echo "</table>";
	}
	else
	{
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>".$title."</th></tr>";

		echo "</table>";
	}
}

function showCentralJobCount(){
	// show a tab with count of jobs in the central and give link	

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_all_ticket","1")) return false;	

	$query="SELECT status, COUNT(*) AS COUNT FROM glpi_tracking ".getEntitiesRestrictRequest("WHERE","glpi_tracking")." GROUP BY status";



	$result = $DB->query($query);


	$status=array("new"=>0, "assign"=>0, "plan"=>0, "waiting"=>0);

	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_assoc($result)){

			$status[$data["status"]]=$data["COUNT"];
		}

	echo "<table class='tab_cadrehov' >";

	echo "<tr><th colspan='2'><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=process&amp;reset=reset_before\">".$LANG["title"][10]."</a></th></tr>";
	echo "<tr><th>".$LANG["title"][28]."</th><th>".$LANG["tracking"][29]."</th></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=new&amp;reset=reset_before\">".$LANG["tracking"][30]."</a> </td>";
	echo "<td>".$status["new"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=assign&amp;reset=reset_before\">".$LANG["tracking"][31]."</a></td>";
	echo "<td>".$status["assign"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=plan&amp;reset=reset_before\">".$LANG["tracking"][32]."</a></td>";
	echo "<td>".$status["plan"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php?status=waiting&amp;reset=reset_before\">".$LANG["joblist"][26]."</a></td>";
	echo "<td>".$status["waiting"]."</td></tr>";


	echo "</table><br>";


}




function showJobListForItem($item_type,$item) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donn� 

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_all_ticket","1")) return false;

	$where = "";	

	$query = "SELECT ".getCommonSelectForTrackingSearch()." 
			FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." 
			WHERE (computer = '$item' and device_type= '$item_type') 
				ORDER BY glpi_tracking.date_mod DESC LIMIT ".intval($_SESSION['glpilist_limit']);

	$result = $DB->query($query);

	$number = $DB->numrows($result);

	if ($number > 0)
	{
		$ci = new CommonItem();
		$ci->getFromDB($item_type,$item);
		initNavigateListItems(TRACKING_TYPE,$ci->getType()." = ".$ci->getName());

		echo "<div class='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='10'>".$number." ".$LANG["job"][8].": &nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&amp;status=all&amp;item=$item&amp;type=$item_type'>".$LANG["buttons"][40]."</a>";
		echo "</th></tr>";

		if ($item)
		{
			echo "<tr><td align='center' class='tab_bg_2' colspan='10'>";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
			echo $LANG["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		
		commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"ID=$item","","",true);

		while ($data=$DB->fetch_assoc($result)){
			addToNavigateListItems(TRACKING_TYPE,$data["ID"]);
			showJobShort($data, 0);
		}
		echo "</table></div>";
	} 
	else
	{
		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["joblist"][8]."</th></tr>";

		if ($item)
		{

			echo "<tr><td align='center' class='tab_bg_2'>";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
			echo $LANG["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}

function showJobListForEnterprise($entID) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donn� 

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_all_ticket","1")) return false;

	$where = "";	

	$query = "SELECT ".getCommonSelectForTrackingSearch()." 
			FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." 
			WHERE (assign_ent = '$entID') 
				ORDER BY glpi_tracking.date_mod DESC LIMIT ".intval($_SESSION['glpilist_limit']);

	$result = $DB->query($query);

	$number = $DB->numrows($result);

	if ($number > 0)
	{
		$ent=new Enterprise();
		$ent->getFromDB($entID);
		initNavigateListItems(TRACKING_TYPE,$LANG["financial"][26]." = ".$ent->fields['name']);

		echo "<div class='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='10'>".$number." ".$LANG["job"][8].": &nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&amp;status=all&amp;assign_ent=$entID'>".$LANG["buttons"][40]."</a>";
		echo "</th></tr>";

		
		commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"","","",true);

		while ($data=$DB->fetch_assoc($result)){
			addToNavigateListItems(TRACKING_TYPE,$data["ID"]);
			showJobShort($data, 0);
		}
		echo "</table></div>";
	} 
	else
	{
		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["joblist"][8]."</th></tr>";

		echo "</table>";
		echo "</div><br>";
	}
}


function showJobListForUser($userID) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donn� 

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("show_all_ticket","1")) return false;

	$where = "";	

	$query = "SELECT ".getCommonSelectForTrackingSearch()." 
			FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch()." 
			WHERE (author = '$userID') 
				ORDER BY glpi_tracking.date_mod DESC LIMIT ".intval($_SESSION['glpilist_limit']);

	$result = $DB->query($query);

	$number = $DB->numrows($result);

	if ($number > 0)
	{
		$user=new User();
		$user->getFromDB($userID);
		initNavigateListItems(TRACKING_TYPE,$LANG["common"][34]." = ".$user->getName());

		echo "<div class='center'><table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='10'>".$number." ".$LANG["job"][8].": &nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.php?reset=reset_before&amp;status=all&amp;author=$userID'>".$LANG["buttons"][40]."</a>";
		echo "</th></tr>";

		
		commonTrackingListHeader(HTML_OUTPUT,$_SERVER['PHP_SELF'],"","","",true);

		while ($data=$DB->fetch_assoc($result)){
			addToNavigateListItems(TRACKING_TYPE,$data["ID"]);
			showJobShort($data, 0);
		}
		echo "</table></div>";
	} 
	else
	{
		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["joblist"][8]."</th></tr>";

		echo "</table>";
		echo "</div><br>";
	}
}

function showJobShort($data, $followups,$output_type=HTML_OUTPUT,$row_num=0) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	global $CFG_GLPI, $LANG;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	
	$job->fields = $data;
	$candelete=haveRight("delete_ticket","1");
	$canupdate=haveRight("update_ticket","1");
//	$viewusers=haveRight("user","r");
	$align="align='center'";
	$align_desc="align='left'";
	if ($followups) { 
		$align.=" valign='top' ";
		$align_desc.=" valign='top' ";
	}
	if ($data["ID"])
	{
		$item_num=1;
		$bgcolor=$_SESSION["glpipriority_".$data["priority"]];

		echo displaySearchNewLine($output_type,$row_num%2);



		// First column
		$first_col= "ID: ".$data["ID"];
		if ($output_type==HTML_OUTPUT)
			$first_col.="<br><img src=\"".$CFG_GLPI["root_doc"]."/pics/".$data["status"].".png\" alt='".getStatusName($data["status"])."' title='".getStatusName($data["status"])."'>";
		else $first_col.=" - ".getStatusName($data["status"]);
		if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
			$sel="";
			if (isset($_GET["select"])&&$_GET["select"]=="all") {
				$sel="checked";
			}
			if (isset($_SESSION['glpimassiveactionselected'][$data["ID"]])){
				$sel="checked";
			}
			$first_col.="&nbsp;<input type='checkbox' name='item[".$data["ID"]."]' value='1' $sel>";
		}


		echo displaySearchItem($output_type,$first_col,$item_num,$row_num,$align);

		// Second column
		$second_col="";	
		if (!strstr($data["status"],"old_"))
		{
			$second_col.="<span class='tracking_open'>".$LANG["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.= "&nbsp;".convDateTime($data["date"])."</span>";
		}
		else
		{	$second_col.="<div class='tracking_hour'>";
			$second_col.="".$LANG["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;<span class='tracking_bold'>".convDateTime($data["date"]);
			$second_col.="</span><br>";
			$second_col.="".$LANG["joblist"][12].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;<span class='tracking_bold'>".convDateTime($data["closedate"])."</span>";
			$second_col.="<br>";
			if ($data["realtime"]>0) $second_col.=$LANG["job"][20].": ";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".getRealtime($data["realtime"]);
			$second_col.="</div>";
		}

		echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=130");

		// Second BIS column 
		$second_col=convDateTime($data["date_mod"]);

		echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=90");

		// Second TER column
		if (count($_SESSION["glpiactiveentities"])>1){

			if ($data['entityID']==0){
				$second_col=$LANG["entity"][2];
			} else {
				$second_col=$data['entityname'];
			}
	
			echo displaySearchItem($output_type,$second_col,$item_num,$row_num,$align." width=100");
		}

		// Third Column
		echo displaySearchItem($output_type,"<strong>".getPriorityName($data["priority"])."</strong>",$item_num,$row_num,"$align bgcolor='$bgcolor'");

		// Fourth Column
		$fourth_col="";
		if ($data['author']){
			$userdata=getUserName($data['author'],2);
	
			$comments_display="";
			if ($output_type==HTML_OUTPUT){
				$comments_display="<a href='".$userdata["link"]."'>";
				$comments_display.="<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_trackauthor".$data['ID']."')\" onmouseover=\"cleandisplay('comments_trackauthor".$data['ID']."')\">";
				$comments_display.="</a>";
				$comments_display.="<span class='over_link' id='comments_trackauthor".$data['ID']."'>".$userdata["comments"]."</span>";
			} 
	
			$fourth_col.="<strong>".$userdata['name']."&nbsp;".$comments_display."</strong>";
		}

		if ($data["FK_group"])
			$fourth_col.="<br>".$data["groupname"];

		echo displaySearchItem($output_type,$fourth_col,$item_num,$row_num,$align);

		// Fifth column
		$fifth_col="";
		if ($data["assign"]>0){
			$userdata=getUserName($data['assign'],2);

			$comments_display="";
			if ($output_type==HTML_OUTPUT){
				$comments_display="<a href='".$userdata["link"]."'>";
				$comments_display.="<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_trackassign".$data['ID']."')\" onmouseover=\"cleandisplay('comments_trackassign".$data['ID']."')\">";
				$comments_display.="</a>";
				$comments_display.="<span class='over_link' id='comments_trackassign".$data['ID']."'>".$userdata["comments"]."</span>";
			}
	
			$fifth_col="<strong>".$userdata['name']."&nbsp;".$comments_display."</strong>";
		}

		if ($data["assign_group"]>0){
			if (!empty($fifth_col)){
				$fifth_col.="<br>";
			}
			$fifth_col.=getAssignName($data["assign_group"],GROUP_TYPE,1);
		}

		if ($data["assign_ent"]>0){
			if (!empty($fifth_col)){
				$fifth_col.="<br>";
			}
			$fifth_col.=getAssignName($data["assign_ent"],ENTERPRISE_TYPE,1);
		}
		echo displaySearchItem($output_type,$fifth_col,$item_num,$row_num,$align);

		$ci=new CommonItem();
		$ci->getFromDB($data["device_type"],$data["computer"]);
		// Sixth Colum
		$sixth_col="";

		$sixth_col.=$ci->getType();
		if ($data["device_type"]>0&&$data["computer"]>0){
			$sixth_col.="<br><strong>";
			if (haveTypeRight($data["device_type"],"r")){
				$sixth_col.=$ci->getLink($output_type==HTML_OUTPUT);
			} else {
				$sixth_col.=$ci->getNameID();
			}
			$sixth_col.="</strong>";
		} 

		echo displaySearchItem($output_type,$sixth_col,$item_num,$row_num,$align." ".($ci->getField("deleted")?" class='deleted' ":""));

		// Seventh column
		echo displaySearchItem($output_type,"<strong>".$data["catname"]."</strong>",$item_num,$row_num,$align);

		// Eigth column

		$eigth_column="<strong>".$data["name"]."</strong>&nbsp;";

		if ($output_type==HTML_OUTPUT){
			$eigth_column.= "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_tracking".$data["ID"]."')\" onmouseover=\"cleandisplay('comments_tracking".$data["ID"]."')\" >";
			$eigth_column.="<span class='over_link' id='comments_tracking".$data["ID"]."'>".nl2br($data['contents'])."</span>";
		}
		

		// Add link
		if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
			if ($job->canView()) {
				$eigth_column="<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$data["ID"]."\">$eigth_column</a>";

				if ($followups&&$output_type==HTML_OUTPUT){
					$eigth_column.=showFollowupsShort($data["ID"]);
				} else {
					$eigth_column.="&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket","1")).")";
				}

			}
		}
		else {
			$eigth_column="<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$data["ID"]."\">$eigth_column</a>";
			if ($followups&&$output_type==HTML_OUTPUT){
				$eigth_column.=showFollowupsShort($data["ID"]);
			} else {
				$eigth_column.="&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket","1")).")";
			}
		}


		echo displaySearchItem($output_type,$eigth_column,$item_num,$row_num,$align_desc."width='300'");

		// Finish Line
		echo displaySearchEndLine($output_type);
	}
	else
	{
		echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG["joblist"][16]."</i></td></tr>";
	}
}

function showJobVeryShort($ID) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	global $CFG_GLPI, $LANG;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$viewusers=haveRight("user","r");
	if ($job->getFromDBwithData($ID,0))
	{
		$bgcolor=$_SESSION["glpipriority_".$job->fields["priority"]];
		
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->fields["ID"]."</td>";
		echo "<td class='center'>";

		if ($viewusers){
				$userdata=getUserName($job->fields['author'],2);
	
				$comments_display="";
				$comments_display="<a href='".$userdata["link"]."'>";
				$comments_display.="<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_trackauthor".$ID."')\" onmouseover=\"cleandisplay('comments_trackauthor".$ID."')\">";
				$comments_display.="</a>";
				$comments_display.="<span class='over_link' id='comments_trackauthor".$ID."'>".$userdata["comments"]."</span>";
	
				echo "<strong>".$userdata['name']."&nbsp;".$comments_display."</strong>";
		} else {
			echo "<strong>".$job->getAuthorName()."</strong>";
		}

		if ($job->fields["FK_group"])
			echo "<br>".getDropdownName("glpi_groups",$job->fields["FK_group"]);


		echo "</td>";

		if (haveTypeRight($job->fields["device_type"],"r")){
			echo "<td align='center' ";
			if ($job->hardwaredatas->getField("deleted")){
				echo "class='tab_bg_1_2'";
			}
			echo ">";
			echo $job->hardwaredatas->getType()."<br>";
			echo "<strong>";
			echo $job->hardwaredatas->getLink();
			echo "</strong>";

			echo "</td>";
		}
		else {
			echo "<td  align='center' >".$job->hardwaredatas->getType()."<br><strong>".$job->hardwaredatas->getNameID()."</strong></td>";
		}

		echo "<td>";

		if ($_SESSION["glpiactiveprofile"]["interface"]=="central")
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$job->fields["ID"]."\"><strong>";
		else
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$job->fields["ID"]."\"><strong>";

		echo $job->fields["name"];
		echo "</strong>&nbsp;<img alt='".$LANG["joblist"][6]."' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' onmouseout=\"cleanhide('comments_tracking".$job->fields["ID"]."')\" onmouseover=\"cleandisplay('comments_tracking".$job->fields["ID"]."')\" >";
		echo "<span class='over_link' id='comments_tracking".$job->fields["ID"]."'>".nl2br($job->fields['contents'])."</span>";
		echo "</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;";

		echo "</td>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
		echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG["joblist"][16]."</i></td></tr>";
	}
}

function addFormTracking ($device_type=0,$ID=0, $target, $author, $group=0, $assign=0, $assign_group=0, $name='',$contents='',$category=0, $priority=3,$request_type=1,$hour=0,$minute=0,$entity_restrict,$status=1) {
	// Prints a nice form to add jobs

	global $CFG_GLPI, $LANG,$CFG_GLPI,$REFERER,$DB;
	if (!haveRight("create_ticket","1")) return false;

	$add_url="";
	if ($device_type>0){
		$add_url="?device_type=$device_type&amp;computer=$ID";
	}
	echo "<br><form name='form_ticket' method='post' action='$target$add_url' enctype=\"multipart/form-data\">";
	echo "<div class='center'>";
	
	echo "<table class='tab_cadre_fixe'><tr><th colspan='4'>".$LANG["job"][13];
	if (haveRight("comment_all_ticket","1")){
		echo "&nbsp;&nbsp;";
		dropdownStatus("status",$status);		
	}

	echo '<br>';

	if ($device_type>0){
		$m=new CommonItem;
		$m->getFromDB($device_type,$ID);
		echo $m->getType()." - ".$m->getLink();
	}

	echo "<input type='hidden' name='computer' value=\"$ID\">";
	echo "<input type='hidden' name='device_type' value=\"$device_type\">";

	echo "</th></tr>";

	$author_rand=0;
	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["job"][4].":</td>";
		echo "<td colspan='3' align='center'>";
		
		//Check if the user have access to this entity only, or subentities too
		if (haveAccessToEntity($_SESSION["glpiactive_entity"],true))
			$entities = getEntitySons($_SESSION["glpiactive_entity"]);
		else
			$entities = $_SESSION["glpiactive_entity"];	
			
		//List all users in the active entity (and all it's sub-entities if needed)
		$author_rand=dropdownAllUsers("author",$author,1,$entities,1);

		//Get all the user's entities
		$all_entities = getUserEntities($author, true);
		$values = array();
		
		//For each user's entity, check if the technician which creates the ticket have access to it
		foreach ($all_entities as $tmp => $ID)
			if (haveAccessToEntity($ID))
				$values[]=$ID;
				
		$count = count($values);					
		
		if (!empty($values))
			//If entity is not in the list of user's entities, then display as default value the first value of the user's entites list
			$first_entity = (in_array($entity_restrict,$values)?$entity_restrict:$values[0]);
		else
			$first_entity = $entity_restrict; 
		
		//If user have access to more than one entity, then display a combobox
		if ($count > 1) {
			$rand = dropdownValue("glpi_entities", "FK_entities", $first_entity, 1, $values,'',array(),1);
		} else {
			echo "<input type='hidden' name='FK_entities' value='".$entity_restrict."'>";
		}
		echo "</tr>";
	} 

	//If multi-entity environment, display the name of the entity on which the ticket will be created
	if (isMultiEntitiesMode()){
		echo "<tr class='tab_bg_2' align='center'>";
		echo "<th colspan='4'>";
		echo $LANG["job"][46].":&nbsp;".getDropdownName("glpi_entities",$first_entity);
		echo "</th></tr>";
	}

	$author_rand=0;
	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'>";
		echo "<td>".$LANG["common"][35].":</td>";
		echo "<td align='center' colspan='3'><span id='span_group'>";
		
		//Look for group in the entities. If it's not present, then do not use default combobox value
		if (isGroupVisibleInEntity($group,$entity_restrict))
			$group_visible = $group;
		else
			$group_visible = '';
		dropdownValue("glpi_groups","FK_group",$group_visible,1,$entity_restrict);
		echo "</span></td></tr>";
	} 


	if ($device_type==0 && $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>".$LANG["help"][24].": </td>";
		echo "<td align='center' colspan='3'>";
		dropdownMyDevices($author,$entity_restrict);
		dropdownTrackingAllDevices("device_type",$device_type,0,$entity_restrict);
		echo "</td></tr>";
	} 


	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2'><td class='center'>".$LANG["common"][27].":</td>";
		echo "<td align='center' class='tab_bg_2'>";
		showDateTimeFormItem("date",date("Y-m-d H:i"),1);
		echo "</td>";

		echo "<td class='center'>".$LANG["job"][44].":</td>";
		echo "<td class='center'>";
		dropdownRequestType("request_type",$request_type);
		echo "</td></tr>";
	}


	// Need comment right to add a followup with the realtime
	if (haveRight("comment_all_ticket","1")){
		echo "<tr  class='tab_bg_2'>";
		echo "<td class='center'>";
		echo $LANG["job"][20].":</td>";
		echo "<td align='center' colspan='3'>";
		dropdownInteger('hour',$hour,0,100);

		echo $LANG["job"][21]."&nbsp;&nbsp;";
		dropdownInteger('minute',$minute,0,59);

		echo $LANG["job"][22]."&nbsp;&nbsp;";
		echo "</td></tr>";
	}


	echo "<tr class='tab_bg_2'>";

	echo "<td class='tab_bg_2' align='center'>".$LANG["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2'>";

	dropdownPriority("priority",$priority);
	echo "</td>";

	echo "<td>".$LANG["common"][36].":</td>";
	echo "<td class='center'>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td></tr>";

	if (haveRight("assign_ticket","1")||haveRight("steal_ticket","1")||haveRight("own_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$LANG["buttons"][3].":</td>";
		echo "<td colspan='3'>";

		//Try to assign the ticket to an user. Look if it's visible in the entites
		$assign_entities = getUserEntities($assign,true);
		if (in_array($entity_restrict,$assign_entities))
			$assign_tech = $assign;
		else
			$assign_tech = 0;	

		if (haveRight("assign_ticket","1")){
			echo $LANG["job"][6].": ";
			dropdownUsers("assign",$assign_tech,"own_ticket",0,1,$entity_restrict,0);
			echo "<br>".$LANG["common"][35].": <span id='span_group_assign'>";

			//Look for group in the entities. If it's not present, then do not use default combobox value
			if (isGroupVisibleInEntity($assign_group,$entity_restrict))
				$group_visible = $assign_group;
			else
				$group_visible = '';

			dropdownValue("glpi_groups", "assign_group", $group_visible,1,$entity_restrict);
			echo "</span>";
		} else { // own or steal active
			echo $LANG["job"][6].":";
			dropdownUsers("assign",$assign_tech,"ID",0,1,$entity_restrict,0);
		}
		echo "</td></tr>";

	}


	if(isAuthorMailingActivatedForHelpdesk()){

		$query="SELECT email from glpi_users WHERE ID='$author'";
		
		$result=$DB->query($query);
		$email="";
		if ($result&&$DB->numrows($result))
			$email=$DB->result($result,0,"email");
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center'>".$LANG["help"][8].":</td>";
		echo "<td class='center'>";
		dropdownYesNo('emailupdates',!empty($email));
		echo "</td>";
		echo "<td class='center'>".$LANG["help"][11].":</td>";
		echo "<td><span id='uemail_result'>";
		echo "<input type='text' size='30' name='uemail' value='$email'>";
		echo "</span>";

		echo "</td></tr>";

	}

	echo "</table><br><table class='tab_cadre_fixe'>";
	echo "<tr><th class='center'>".$LANG["common"][57].":";
	echo "</th><th colspan='3' class='left'>";

	echo "<input type='text' size='80' name='name' value='$name'>";
	echo "</th> </tr>";

	
	echo "<tr><th colspan='4' align='center'>".$LANG["job"][11].":";
	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='100' rows='6'  name='contents'>$contents</textarea></td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." ".$LANG["common"][45]."):	";
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\"class='pointer;' alt=\"aide\"onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=500')\">";
	echo "</td>";
	echo "<td colspan='3'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";

	echo "<td colspan='2' class='center'><a href='$target'><img title=\"".$LANG["buttons"][16]."\" alt=\"".$LANG["buttons"][16]."\" src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a></td>";



	echo "<td colspan='2' align='center'><input type='submit' name='add' value=\"".$LANG["buttons"][2]."\" class='submit'>";

	echo "</td></tr></table>";



	if (haveRight("comment_all_ticket","1")){
	echo "<br>";

		showAddFollowupForm(-1);
	}

	echo "</div></form>";

}

function getTrackingFormFields($_POST)
{
	$params = array(
	//"userID"=>(($userID!=-1)?$userID:$_POST["userID"]),
	//"entity_restrict"=>(($entity_restrict!=-1)?$entity_restrict:$_POST["entity_restrict"]),
	"group"=>0,"device_type"=>0,
	"assign"=>0,"assign_group"=>0,"category"=>0,
	"priority"=>3,"hour"=>0,"minute"=>0,"request_type"=>1,
	"name"=>'',"contents"=>'',"target"=>"");
	
	$params_ajax = array();
	foreach ($params as $param => $default_value)
		$params_ajax[$param] = (isset($_POST[$param])?$_POST[$param]:$default_value);
	 
	 return $params_ajax;
}

function getRealtime($realtime){
	global $LANG;	
	$output="";
	$hour=floor($realtime);
	if ($hour>0) $output.=$hour." ".$LANG["job"][21]." ";
	$output.=round((($realtime-floor($realtime))*60))." ".$LANG["job"][22];
	return $output;
}

function searchSimpleFormTracking($extended=0,$target,$status="all",$tosearch='',$search='',$group=-1,$showfollowups=0,$category=0){

global $CFG_GLPI,  $LANG;


	echo "<div align='center' >";

	echo "<form method='get' name=\"form\" action=\"".$target."\">";
	echo "<table class='tab_cadre_fixe'>";

	echo "<tr><th colspan='5' class='middle' ><div class='relative'><span>".$LANG["search"][0]."</span>";
	$parm="";
	if ($_SESSION["glpiactiveprofile"]["interface"]=="helpdesk"){
		$parm="show=user&amp;";
	}

	if ($extended){
		echo "<span class='tracking_right'><a href='$target?".$parm."extended=0'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''>".$LANG["buttons"][36]."</a></span>";
	} else {
		echo "<span   class='tracking_right'><a href='$target?".$parm."extended=1'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''>".$LANG["buttons"][35]."</a></span>";
	}
	echo "</div></th></tr>";


	echo "<tr class='tab_bg_1' align='center'>";
	echo "<td colspan='1' >".$LANG["joblist"][0].":&nbsp;";
	echo "<select name='status'>";
	echo "<option value='new' ".($status=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($status=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($status=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($status=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($status=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($status=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$LANG["joblist"][24]."</option>";	
	echo "<option value='process' ".($status=="process"?"selected":"").">".$LANG["joblist"][21]."</option>";
	echo "<option value='old' ".($status=="old"?"selected":"").">".$LANG["joblist"][25]."</option>";	
	echo "<option value='all' ".($status=="all"?"selected":"").">".$LANG["common"][66]."</option>";
	echo "</select></td>";

	if (haveRight("show_group_ticket",1)){
		echo "<td class='center'>";
		echo "<select name='group'>";
		echo "<option value='-1' ".($group==-1?" selected ":"").">".$LANG["common"][66]."</option>";
		echo "<option value='0' ".($group==0?" selected ":"").">".$LANG["joblist"][1]."</option>";
		echo "</select>";
		echo "</td>";
	} else {
		echo '<td>&nbsp;</td>';
	}

	echo "<td class='center' colspan='2'>".$LANG["reports"][59].":&nbsp;";
	dropdownYesNo('showfollowups',$showfollowups);
	echo "</td>";

	if ($extended){
		echo "<td>".$LANG["common"][36].":&nbsp;";
		dropdownValue("glpi_dropdown_tracking_category","category",$category);
		echo "</td></tr>";
		echo "<tr class='tab_bg_1' align='center'>";
		echo "<td class='center' colspan='2'>";
		$elts=array("name"=>$LANG["common"][57],
			"contents"=>$LANG["joblist"][6],
			"followup"=>$LANG["Menu"][5],
			"name_contents"=>$LANG["common"][57]." / ".$LANG["joblist"][6],
			"name_contents_followup"=>$LANG["common"][57]." / ".$LANG["joblist"][6]." / ".$LANG["Menu"][5],
			"ID"=>"ID");
		echo "<select name='tosearch'>";
		foreach ($elts as $key => $val){
			$selected="";
			if ($tosearch==$key) $selected="selected";
			echo "<option value=\"$key\" $selected>$val</option>";
		}
		echo "</select>";
	
	
	
		echo "&nbsp;".$LANG["search"][2]."&nbsp;";
		echo "<input type='text' size='15' name=\"search\" value=\"".stripslashes($search)."\">";
		echo "</td>";
		echo "<td colspan='2'>&nbsp;</td>";
				
	}


	echo "<td align='center' colspan='1'><input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<input type='hidden' name='start' value='0'>";
	echo "<input type='hidden' name='extended' value='$extended'>";
	// helpdesk case
	if (strpos($target,"helpdesk.public.php")){
		echo "<input type='hidden' name='show' value='user'>";
	}
	echo "</form>";
	echo "</div>";

}

function searchFormTracking($extended=0,$target,$start="",$status="new",$tosearch="",$search="",$author=0,$group=0,$showfollowups=0,$category=0,$assign=0,$assign_ent=0,$assign_group=0,$priority=0,$request_type=0,$item=0,$type=0,$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="",$datemod1="",$datemod2="",$recipient=0) {
	// Print Search Form

	global $CFG_GLPI,  $LANG, $DB;

	if (!haveRight("show_all_ticket","1")) {
		
		if (haveRight("show_assign_ticket","1")) {
			$assign='mine';
		} else if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1")){
				$author=$_SESSION["glpiID"];
			} else {
				$assign=$_SESSION["glpiID"];
			}
	}

	if ($extended){
		$option["comp.ID"]				= $LANG["common"][2];
		$option["comp.name"]				= $LANG["common"][16];
		$option["glpi_dropdown_locations.name"]		= $LANG["common"][15];
		$option["glpi_type_computers.name"]		= $LANG["common"][17];
		$option["glpi_dropdown_model.name"]		= $LANG["common"][22];
		$option["glpi_dropdown_os.name"]		= $LANG["computers"][9];
		$option["processor.designation"]		= $LANG["computers"][21];
		$option["comp.serial"]				= $LANG["common"][19];
		$option["comp.otherserial"]			= $LANG["common"][20];
		$option["ram.designation"]			= $LANG["computers"][23];
		$option["iface.designation"]			= $LANG["setup"][9];
		$option["sndcard.designation"]			= $LANG["devices"][7];
		$option["gfxcard.designation"]			= $LANG["devices"][2];
		$option["moboard.designation"]			= $LANG["devices"][5];
		$option["hdd.designation"]			= $LANG["computers"][36];
		$option["comp.comments"]			= $LANG["common"][25];
		$option["comp.contact"]				= $LANG["common"][18];
		$option["comp.contact_num"]		        = $LANG["common"][21];
		$option["comp.date_mod"]			= $LANG["common"][26];
		$option["glpi_networking_ports.ifaddr"] 	= $LANG["networking"][14];
		$option["glpi_networking_ports.ifmac"] 		= $LANG["networking"][15];
		$option["glpi_dropdown_netpoint.name"]		= $LANG["networking"][51];
		$option["glpi_enterprises.name"]		= $LANG["common"][5];
		$option["resptech.name"]			=$LANG["common"][10];
	}
	echo "<form method='get' name=\"form\" action=\"".$target."\">";


	

	echo "<table class='tab_cadre_fixe'>";


	echo "<tr><th colspan='6' class='middle' ><div class='relative'><span>".$LANG["search"][0]."</span>";
	if ($extended){
		echo "<span class='tracking_right'><a href='$target?extended=0'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" alt=''>".$LANG["buttons"][36]."</a></span>";
	} else {
		echo "<span   class='tracking_right'><a href='$target?extended=1'><img src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_down.png\" alt=''>".$LANG["buttons"][35]."</a></span>";
	}
	echo "</div></th></tr>";



	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='1' align='center'>".$LANG["joblist"][0].":<br>";
	echo "<select name='status'>";
	echo "<option value='new' ".($status=="new"?" selected ":"").">".$LANG["joblist"][9]."</option>";
	echo "<option value='assign' ".($status=="assign"?" selected ":"").">".$LANG["joblist"][18]."</option>";
	echo "<option value='plan' ".($status=="plan"?" selected ":"").">".$LANG["joblist"][19]."</option>";
	echo "<option value='waiting' ".($status=="waiting"?" selected ":"").">".$LANG["joblist"][26]."</option>";
	echo "<option value='old_done' ".($status=="old_done"?" selected ":"").">".$LANG["joblist"][10]."</option>";
	echo "<option value='old_notdone' ".($status=="old_notdone"?" selected ":"").">".$LANG["joblist"][17]."</option>";
	echo "<option value='notold' ".($status=="notold"?"selected":"").">".$LANG["joblist"][24]."</option>";	
	echo "<option value='process' ".($status=="process"?"selected":"").">".$LANG["joblist"][21]."</option>";
	echo "<option value='old' ".($status=="old"?"selected":"").">".$LANG["joblist"][25]."</option>";	
	echo "<option value='all' ".($status=="all"?"selected":"").">".$LANG["common"][66]."</option>";
	echo "</select></td>";


	echo "<td colspan='1' class='center'>".$LANG["joblist"][2].":<br>";
	dropdownPriority("priority",$priority,1);
	echo "</td>";

	echo "<td colspan='2' class='center'>".$LANG["common"][36].":<br>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";

	echo "<td colspan='2' class='center'>".$LANG["job"][44].":<br>";
	dropdownRequestType("request_type",$request_type);
	echo "</td>";

	echo "</tr>";
	echo "<tr class='tab_bg_1'>";

	echo "<td class='center' colspan='2'>";
	echo "<table border='0'><tr><td>".$LANG["common"][1].":</td><td>";
	dropdownAllItems("item",$type,$item);
	echo "</td></tr></table>";
	echo "</td>";

	echo "<td  colspan='2' class='center'>".$LANG["job"][4].":<br>";
	dropdownUsersTracking("author",$author,"author");

	echo "<br>".$LANG["common"][35].": ";
	dropdownValue("glpi_groups","group",$group);
	echo "</td>";


	echo "<td colspan='2' align='center'>".$LANG["job"][5].":<br>";
	if (strcmp($assign,"mine")==0){
		echo formatUserName($_SESSION["glpiID"],$_SESSION["glpiname"],$_SESSION["glpirealname"],$_SESSION["glpifirstname"]);
		// Display the group if unique
		if (count($_SESSION['glpigroups'])==1){
			echo "<br>".getDropdownName("glpi_groups",current($_SESSION['glpigroups']));
		} else if (count($_SESSION['glpigroups'])>1){ // Display limited dropdown
			echo "<br>";
			$groups[0]='-----';
			$groups=array_merge($groups,getDropdownArrayNames('glpi_groups',$_SESSION['glpigroups']));
			dropdownArrayValues('assign_group',$groups,$assign_group);
		}
	} else {
		dropdownUsers("assign",$assign,"own_ticket",1);
		echo "<br>".$LANG["common"][35].": ";
		dropdownValue("glpi_groups","assign_group",$assign_group);
	
		echo "<br>";
		echo $LANG["financial"][26].":&nbsp;";
		dropdownValue("glpi_enterprises","assign_ent",$assign_ent);
	}

	echo "</td>";

	echo "</tr>";

	if ($extended){
		echo "<tr class='tab_bg_1'><td  colspan='6' class='center'>".$LANG["job"][3].":";
		dropdownUsersTracking("recipient",$recipient,"recipient");
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'>";
		echo "<td class='center' colspan='6'>";
		$selected="";
		if ($computers_search) $selected="checked";
		echo "<input type='checkbox' name='only_computers' value='1' $selected>".$LANG["reports"][24].":&nbsp;";

		echo "<input type='text' size='15' name=\"contains\" value=\"". stripslashes($contains) ."\" >";
		echo "&nbsp;";
		echo $LANG["search"][10]."&nbsp;";

		echo "<select name='field' size='1'>";
		echo "<option value='all' ";
		if($field == "all") echo "selected";
		echo ">".$LANG["common"][66]."</option>";
		reset($option);
		foreach ($option as $key => $val) {
			echo "<option value=\"".$key."\""; 
			if($key == $field) echo "selected";
			echo ">". $val ."</option>\n";
		}
		echo "</select>&nbsp;";

		echo "</td></tr>";
	}
	if($extended)	{
		echo "<tr class='tab_bg_1'><td class='right'>".$LANG["reports"][60].":</td><td class='center' colspan='2'>".$LANG["search"][8].":</td><td>";
		showDateFormItem("date1",$date1);
		echo "</td><td class='center'>";
		echo $LANG["search"][9].":</td><td>";
		showDateFormItem("date2",$date2);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td class='right'>".$LANG["reports"][61].":</td><td class='center' colspan='2'>".$LANG["search"][8].":</td><td>";
		showDateFormItem("enddate1",$enddate1);
		echo "</td><td class='center'>";
		echo $LANG["search"][9].":</td><td>";
		showDateFormItem("enddate2",$enddate2);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td class='right'>".$LANG["common"][26].":</td><td class='center' colspan='2'>".$LANG["search"][8].":</td><td>";
		showDateFormItem("datemod1",$datemod1);
		echo "</td><td class='center'>";
		echo $LANG["search"][9].":</td><td>";
		showDateFormItem("datemod2",$datemod2);
		echo "</td></tr>";
	}
	echo "<tr  class='tab_bg_1'>";

	echo "<td class='center' colspan='2'>";
	$elts=array("name"=>$LANG["common"][57],
		    "contents"=>$LANG["joblist"][6],
		    "followup"=>$LANG["Menu"][5],
		    "name_contents"=>$LANG["common"][57]." / ".$LANG["joblist"][6],
		    "name_contents_followup"=>$LANG["common"][57]." / ".$LANG["joblist"][6]." / ".$LANG["Menu"][5],
		    "ID"=>"ID");
	echo "<select name='tosearch'>";
	foreach ($elts as $key => $val){
		$selected="";
		if ($tosearch==$key) $selected="selected";
		echo "<option value=\"$key\" $selected>$val</option>";
	}
	echo "</select>";



	echo "&nbsp;".$LANG["search"][2]."&nbsp;";
	echo "<input type='text' size='15' name=\"search\" value=\"".stripslashes($search)."\">";
	echo "</td>";

	echo "<td class='center' colspan='2'>".$LANG["reports"][59].":&nbsp;";
	dropdownYesNo('showfollowups',$showfollowups);
	echo "</td>";


	echo "<td class='center' colspan='1'><input type='submit' value=\"".$LANG["buttons"][0]."\" class='submit'></td>";
	
	echo "<td class='center'  colspan='1'><input type='submit' name='reset' value=\"".$LANG["buttons"][16]."\" class='submit'>&nbsp;";
	showSaveBookmarkButton(BOOKMARK_SEARCH,TRACKING_TYPE);
	// Needed for bookmark
	echo "<input type='hidden' name=\"extended\" value=\"$extended\">";
	echo "</td>";

	echo "</tr>";

	echo "</table>";
	echo "<input type='hidden' name='start' value='0'>";
	echo "</form>";


}


function getCommonSelectForTrackingSearch(){
	$SELECT="";
	if (count($_SESSION["glpiactiveentities"])>1){
		$SELECT.= ", glpi_entities.completename as entityname, glpi_tracking.FK_entities as entityID";
	}


return " DISTINCT glpi_tracking.*,
		glpi_dropdown_tracking_category.completename AS catname,
		glpi_groups.name as groupname ".$SELECT;

		//, author.name AS authorname, author.realname AS authorrealname, author.firstname AS authorfirstname,	
		//glpi_tracking.assign as assignID, assign.name AS assignname, assign.realname AS assignrealname, assign.firstname AS assignfirstname,
}

function getCommonLeftJoinForTrackingSearch(){

	$FROM="";

	if (count($_SESSION["glpiactiveentities"])>1){
		$FROM.= " LEFT JOIN glpi_entities ON ( glpi_entities.ID = glpi_tracking.FK_entities)";
	}

	return //" LEFT JOIN glpi_users as author ON ( glpi_tracking.author = author.ID) "
	//." LEFT JOIN glpi_users as assign ON ( glpi_tracking.assign = assign.ID) "
	" LEFT JOIN glpi_groups ON ( glpi_tracking.FK_group = glpi_groups.ID) "
	." LEFT JOIN glpi_dropdown_tracking_category ON ( glpi_tracking.category = glpi_dropdown_tracking_category.ID) ".$FROM;
}


function showTrackingList($target,$start="",$sort="",$order="",$status="new",$tosearch="",$search="",$author=0,$group=0,$showfollowups=0,$category=0,$assign=0,$assign_ent=0,$assign_group=0,$priority=0,$request_type=0,$item=0,$type=0,$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="",$datemod1="",$datemod2="",$recipient=0) {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.
	// group = 0 : not use
	// group = -1 : groups of the author if session variable OK
	// group > 0 : specific group

	global $DB,$CFG_GLPI, $LANG;

	$candelete=haveRight("delete_ticket","1");
	$canupdate=haveRight("update_ticket","1");

	if (!haveRight("show_all_ticket","1")) {
		if (haveRight("show_assign_ticket","1")) {
			$assign='mine';
		} else if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1")){
				$author=$_SESSION["glpiID"];
			} else {
				$assign=$_SESSION["glpiID"];
			}
	}

	// Reduce computer list
	$wherecomp="";
	if ($computers_search&&!empty($contains)){
		$SEARCH=makeTextSearch($contains);
		// Build query
		if($field == "all") {
			$wherecomp = " (";
			$query = "SHOW COLUMNS FROM glpi_computers";
			$result = $DB->query($query);
			$i = 0;

			while($line = $DB->fetch_array($result)) {
				if($i != 0) {
					$wherecomp .= " OR ";
				}
				if(IsDropdown($line["Field"])) {
					$wherecomp .= " glpi_dropdown_". $line["Field"] .".name $SEARCH" ;
				}
				elseif($line["Field"] == "location") {
					$wherecomp .= " glpi_dropdown_locations.name $SEARCH";
				}
				else {
					$wherecomp .= "comp.".$line["Field"] . $SEARCH;
				}
				$i++;
			}
			foreach($CFG_GLPI["devices_tables"] as $key => $val) {
				if ($val!="drive"&&$val!="control"&&$val!="pci"&&$val!="case"&&$val!="power")
					$wherecomp .= " OR ".$val.".designation ".makeTextSearch($contains,0);
			}
			$wherecomp .= " OR glpi_networking_ports.ifaddr $SEARCH";
			$wherecomp .= " OR glpi_networking_ports.ifmac $SEARCH";
			$wherecomp .= " OR glpi_dropdown_netpoint.name $SEARCH";
			$wherecomp .= " OR glpi_enterprises.name $SEARCH";
			$wherecomp .= " OR resptech.name $SEARCH";

			$wherecomp .= ")";
		}
		else {
			if(IsDevice($field)) {
				$wherecomp = "(glpi_device_".$field." $SEARCH )";
			}
			else {
				$wherecomp = "($field $SEARCH)";
			}
		}
	}
	if (!$start) {
		$start = 0;
	}
	$SELECT = "SELECT ".getCommonSelectForTrackingSearch();


	$FROM = " FROM glpi_tracking ".getCommonLeftJoinForTrackingSearch();

	if ($search!=""&&strpos($tosearch,"followup")!==false) {
		$FROM.= " LEFT JOIN glpi_followups ON ( glpi_followups.tracking = glpi_tracking.ID)";
	}


	$where=" WHERE ";


	switch ($status){
		case "new": $where.=" glpi_tracking.status = 'new'"; break;
		case "notold": $where.=" (glpi_tracking.status = 'new' OR glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' OR glpi_tracking.status = 'waiting')"; break;
		case "old": $where.=" ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone')"; break;
		case "process": $where.=" ( glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' )"; break;
		case "waiting": $where.=" ( glpi_tracking.status = 'waiting' )"; break;
		case "old_done": $where.=" ( glpi_tracking.status = 'old_done' )"; break;
		case "old_notdone": $where.=" ( glpi_tracking.status = 'old_notdone' )"; break;
		case "assign": $where.=" ( glpi_tracking.status = 'assign' )"; break;
		case "plan": $where.=" ( glpi_tracking.status = 'plan' )"; break;
		default : $where.=" ( 1 )";;break;
	}


	if ($category > 0){
		$where.=" AND ".getRealQueryForTreeItem("glpi_dropdown_tracking_category",$category,"glpi_tracking.category");
	}

	if (!empty($date1)) $where.=" AND glpi_tracking.date >= '$date1'";
	if (!empty($date2)) $where.=" AND glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
	if (!empty($enddate1)) $where.=" AND glpi_tracking.closedate >= '$enddate1'";
	if (!empty($enddate2)) $where.=" AND glpi_tracking.closedate <= adddate( '". $enddate2 ."' , INTERVAL 1 DAY ) ";
	if (!empty($datemod1)) $where.=" AND glpi_tracking.date_mod >= '$datemod1'";
	if (!empty($datemod2)) $where.=" AND glpi_tracking.date_mod <= adddate( '". $datemod2 ."' , INTERVAL 1 DAY ) ";

	if ($recipient!=0)
		$where.=" AND glpi_tracking.recipient='$recipient'";	


	if ($type!=0)
		$where.=" AND glpi_tracking.device_type='$type";	

	if ($item!=0&&$type!=0)
		$where.=" AND glpi_tracking.computer = '$item'";	

	$search_author=false;
	if ($group>0) $where.=" AND glpi_tracking.FK_group = '$group'";
	else if ($group==-1&&$author!=0&&haveRight("show_group_ticket",1)){
		// Get Author group's
		if (count($_SESSION["glpigroups"])){
			$groups=implode("','",$_SESSION['glpigroups']);
			$where.=" AND ( glpi_tracking.FK_group IN ('$groups') ";

			if ($author!=0) {
				$where.=" OR ";
				$where.=" glpi_tracking.author = '$author'";
				$search_author=true;
			}
			
			$where.=")";
		}
	}


	if ($author!=0&&!$search_author) {
		$where.=" AND glpi_tracking.author = '$author' ";
	}

	if (strcmp($assign,"mine")==0){
		// Case : central acces with show_assign_ticket but without show_all_ticket

		$search_assign=" glpi_tracking.assign = '".$_SESSION["glpiID"]."' ";
		if (count($_SESSION['glpigroups'])){
			if ($assign_group>0){
				$search_assign.= " OR glpi_tracking.assign_group = '$assign_group' ";
			} else {
				$groups=implode("','",$_SESSION['glpigroups']);
				$search_assign.= " OR glpi_tracking.assign_group IN ('$groups') ";
			}
		}

		// Display mine but also the ones which i am the author
		$author_part="";
		if (!$search_author&&isset($_SESSION['glpiID'])){
			$author_part.=" OR glpi_tracking.author = '".$_SESSION['glpiID']."'";

			// Get Author group's
			if (haveRight("show_group_ticket",1)&&count($_SESSION["glpigroups"])){
				$groups=implode("','",$_SESSION['glpigroups']);
				$author_part.=" OR glpi_tracking.FK_group IN ('$groups') ";
	
			}
		}

		$where.=" AND ($search_assign $author_part ) ";


	} else {
		if ($assign_ent!=0) $where.=" AND glpi_tracking.assign_ent = '$assign_ent'";
		if ($assign!=0) $where.=" AND glpi_tracking.assign = '$assign'";
		if ($assign_group!=0) $where.=" AND glpi_tracking.assign_group = '$assign_group'";
	}



	if ($request_type!=0) $where.=" AND glpi_tracking.request_type = '$request_type'";

	if ($priority>0) $where.=" AND glpi_tracking.priority = '$priority'";
	if ($priority<0) $where.=" AND glpi_tracking.priority >= '".abs($priority)."'";


	if ($search!=""){
		$SEARCH2=makeTextSearch($search);
		if ($tosearch=="ID"){
			$where.= " AND (glpi_tracking.ID = '".$search."')";
		}
		$TMPWHERE="";
		$first=true;
		if (strpos($tosearch,"followup")!== false){
			if ($first){
				$first=false;
			} else {
				$TMPWHERE.= " OR ";
			}
			$TMPWHERE.= "glpi_followups.contents $SEARCH2 ";
		}
		if (strpos($tosearch,"name")!== false){
			if ($first){
				$first=false;
			} else {
				$TMPWHERE.= " OR ";
			}
			$TMPWHERE.= "glpi_tracking.name $SEARCH2 ";
		}
		if (strpos($tosearch,"contents")!== false){
			if ($first){
				$first=false;
			} else {
				$TMPWHERE.= " OR ";
			}
			$TMPWHERE.= "glpi_tracking.contents $SEARCH2 ";
		}

		if (!empty($TMPWHERE)){
			$where.=" AND ($TMPWHERE) ";
		}
	}

	$where.=getEntitiesRestrictRequest(" AND","glpi_tracking");
	
	if (!empty($wherecomp)){
		$where.=" AND glpi_tracking.device_type= '1'";
		$where.= " AND glpi_tracking.computer IN (SELECT comp.ID FROM glpi_computers as comp ";
		$where.= " LEFT JOIN glpi_computer_device as gcdev ON (comp.ID = gcdev.FK_computers) ";
		$where.= "LEFT JOIN glpi_device_moboard as moboard ON (moboard.ID = gcdev.FK_device AND gcdev.device_type = '".MOBOARD_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_processor as processor ON (processor.ID = gcdev.FK_device AND gcdev.device_type = '".PROCESSOR_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_gfxcard as gfxcard ON (gfxcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".GFX_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_hdd as hdd ON (hdd.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".HDD_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_iface as iface ON (iface.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".NETWORK_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_ram as ram ON (ram.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".RAM_DEVICE."') ";
		$where.= "LEFT JOIN glpi_device_sndcard as sndcard ON (sndcard.ID = gcdev.FK_DEVICE AND gcdev.device_type = '".SND_DEVICE."') ";
		$where.= "LEFT JOIN glpi_networking_ports on (comp.ID = glpi_networking_ports.on_device AND  glpi_networking_ports.device_type='1')";
		$where.= "LEFT JOIN glpi_dropdown_netpoint on (glpi_dropdown_netpoint.ID = glpi_networking_ports.netpoint)";
		$where.= "LEFT JOIN glpi_dropdown_os on (glpi_dropdown_os.ID = comp.os)";
		$where.= "LEFT JOIN glpi_dropdown_locations on (glpi_dropdown_locations.ID = comp.location)";
		$where.= "LEFT JOIN glpi_dropdown_model on (glpi_dropdown_model.ID = comp.model)";
		$where.= "LEFT JOIN glpi_type_computers on (glpi_type_computers.ID = comp.type)";
		$where.= " LEFT JOIN glpi_enterprises ON (glpi_enterprises.ID = comp.FK_glpi_enterprise ) ";
		$where.= " LEFT JOIN glpi_users as resptech ON (resptech.ID = comp.tech_num ) ";
		$where.=" WHERE $wherecomp) ";
	}

	if (!in_array($sort,getTrackingSortOptions())) {
		$sort="glpi_tracking.date_mod";
	}
	if ($order!="ASC" && $order!="DESC") {
		$order=getTrackingOrderPrefs($_SESSION["glpiID"]);		
	}


	$query=$SELECT.$FROM.$where." ORDER BY $sort $order";
	//echo $query;
	// Get it from database	
	if ($result = $DB->query($query)) {

		$numrows=$DB->numrows($result);		

		if ($start<$numrows) {

			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];


			// Pager
			$parameters2="field=$field&amp;contains=$contains&amp;date1=$date1&amp;date2=$date2&amp;only_computers=$computers_search&amp;tosearch=$tosearch&amp;search=$search&amp;assign=$assign&amp;assign_ent=$assign_ent&amp;assign_group=$assign_group&amp;author=$author&amp;group=$group&amp;start=$start&amp;status=$status&amp;category=$category&amp;priority=$priority&amp;type=$type&amp;showfollowups=$showfollowups&amp;enddate1=$enddate1&amp;enddate2=$enddate2&amp;datemod1=$datemod1&amp;datemod2=$datemod2&amp;item=$item&amp;request_type=$request_type";
			
			// Specific case of showing tracking of an item
			if (isset($_GET["ID"])){
				$parameters2.="&amp;ID=".$_GET["ID"];
			}

			$parameters=$parameters2."&amp;sort=$sort&amp;order=$order";
			if (strpos($_SERVER['PHP_SELF'],"user.form.php")) $parameters.="&amp;ID=$author";
			// Manage helpdesk
			if (strpos($target,"helpdesk.public.php")) 
				$parameters.="&amp;show=user";
			if ($output_type==HTML_OUTPUT){
				if (!strpos($target,"helpdesk.public.php")) 
					printPager($start,$numrows,$target,$parameters,TRACKING_TYPE);
				else printPager($start,$numrows,$target,$parameters);
			}

			$nbcols=9;

			// Form to delete old item
			if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
				echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"".$CFG_GLPI["root_doc"]."/front/massiveaction.php\">";

			}

			$i=$start;
			if (isset($_GET['export_all'])){
				$i=0;
			}

			if ($i>0){
				$DB->data_seek($result,$i);
			}

			$end_display=$start+$_SESSION['glpilist_limit'];
			if (isset($_GET['export_all'])){
				$end_display=$numrows;
			}
			// Display List Header
			echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols);
			
			commonTrackingListHeader($output_type,$target,$parameters2,$sort,$order);
			if ($output_type==HTML_OUTPUT){
				initNavigateListItems(TRACKING_TYPE,$LANG["search"][21]);
			}

			while ($i < $numrows && $i<$end_display&&$data=$DB->fetch_array($result)){
				addToNavigateListItems(TRACKING_TYPE,$data["ID"]);
//				$ID = $DB->result($result, $i, "ID");
				showJobShort($data, $showfollowups,$output_type,$i-$start+1);
				$i++;
			}
			$title="";
			// Title for PDF export
			if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT){
				$title.=$LANG["joblist"][0]." = ";
				switch($status){
					case "new": $title.=$LANG["joblist"][9];break;
					case "assign": $title.=$LANG["joblist"][18];break;
					case "plan": $title.=$LANG["joblist"][19];break;
					case "waiting": $title.=$LANG["joblist"][26];break;
					case "old_done": $title.=$LANG["joblist"][10];break;
					case "old_notdone": $title.=$LANG["joblist"][17];break;
					case "notold": $title.=$LANG["joblist"][24];break;
					case "process": $title.=$LANG["joblist"][21];break;
					case "old": $title.=$LANG["joblist"][25];break;
					case "all": $title.=$LANG["common"][66];break;
				}
				if ($author!=0) $title.=" - ".$LANG["job"][4]." = ".getUserName($author);
				if ($group>0) $title.=" - ".$LANG["common"][35]." = ".getDropdownName("glpi_groups",$group);
				if ($assign!=0||$assign_ent!=0||$assign_group!=0){
					$title.=" - ".$LANG["job"][5]." =";
					if ($assign!=0) $title.=" ".$LANG["job"][6]." = ".getUserName($assign);
					if ($assign_group!=0) $title.=" ".$LANG["common"][35]." = ".getDropdownName("glpi_groups",$assign_group);
					if ($assign_ent!=0) $title.=" ".$LANG["financial"][26]." = ".getDropdownName("glpi_enterprises",$assign_ent);
				}
				if ($request_type!=0) $title.=" - ".$LANG["job"][44]." = ".getRequestTypeName($request_type);
				if ($category!=0) $title.=" - ".$LANG["common"][36]." = ".getDropdownName("glpi_dropdown_tracking_category",$category);
				if ($priority!=0) $title.=" - ".$LANG["joblist"][2]." = ".getPriorityName($priority);
				if ($type!=0&&$item!=0){
					$ci=new CommonItem();
					$ci->getFromDB($type,$item);
					$title.=" - ".$LANG["common"][1]." = ".$ci->getType()." / ".$ci->getNameID();

				}
			}
			// Display footer
			echo displaySearchFooter($output_type,$title);

			// Delete selected item
			if (($candelete||$canupdate)&&$output_type==HTML_OUTPUT){
				echo "<div>";
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markCheckboxes('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=all&amp;start=$start'>".$LANG["buttons"][18]."</a></td>";

				echo "<td>/</td><td ><a onclick=\"if ( unMarkCheckboxes('massiveaction_form') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=none&amp;start=$start'>".$LANG["buttons"][19]."</a>";
				echo "</td><td class='left' width='80%'>";
				dropdownMassiveAction(TRACKING_TYPE);
				echo "</td></table></div>";
				// End form for delete item
				echo "</form>";
			}

			// Pager
			if ($output_type==HTML_OUTPUT){ // In case of HTML display
				echo "<br>";
				printPager($start,$numrows,$target,$parameters);
			}

		} else {
			echo "<div class='center'><strong>".$LANG["joblist"][8]."</strong></div>";

		}
	}
	// Clean selection 
	$_SESSION['glpimassiveactionselected']=array();
}

function showFollowupsShort($ID) {
	// Print Followups for a job

	global $DB,$CFG_GLPI, $LANG;

	$showprivate=haveRight("show_full_ticket","1");
	
	$RESTRICT="";
	if (!$showprivate)  $RESTRICT=" AND ( private='0' OR author ='".$_SESSION["glpiID"]."' ) ";


	// Get Number of Followups

	$query="SELECT * FROM glpi_followups WHERE tracking='$ID' $RESTRICT ORDER BY date DESC";
	$result=$DB->query($query);

	$out="";
	if ($DB->numrows($result)>0) {
		$out.="<div class='center'><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		$out.="<tr><th>".$LANG["common"][27]."</th><th>".$LANG["job"][4]."</th><th>".$LANG["joblist"][6]."</th></tr>\n";

		while ($data=$DB->fetch_array($result)) {

			$out.="<tr class='tab_bg_3'>";
			$out.="<td class='center'>".convDateTime($data["date"])."</td>";
			$out.="<td class='center'>".getUserName($data["author"],1)."</td>";
			$out.="<td width='70%'><strong>".resume_text($data["contents"],$CFG_GLPI["cut"])."</strong></td>";
			$out.="</tr>";
		}		

		$out.="</table></div>";

	}
	return $out;
}




function getAssignName($ID,$type,$link=0){
	global $CFG_GLPI;

	switch ($type){
		case USER_TYPE :
			if ($ID==0) return "[Nobody]";
			return getUserName($ID,$link);
			break;
		case ENTERPRISE_TYPE :
		case GROUP_TYPE :
			$ci=new CommonItem();
			if ($ci->getFromDB($type,$ID)){
				$before="";
				$after="";
				if ($link&&haveTypeRight($type,'r')){
					$ci->getLink(1);
				}
				return $ci->getNameID();
			} else return "";
			break;
	}

}



function showJobDetails ($target,$ID){
	global $DB,$CFG_GLPI,$LANG;
	$job=new Job();

	$canupdate=haveRight("update_ticket","1");
	$showuserlink=0;
	if (haveRight('user','r')){
		$showuserlink=1;	
	}
	if ($job->getFromDB($ID)&&haveAccessToEntity($job->fields["FK_entities"])) {

		if (!$job->canView()){
			return false;
		}
		
		$canupdate_descr=$canupdate||($job->numberOfFollowups()==0&&$job->fields["author"]==$_SESSION["glpiID"]);
		$item=new CommonItem();
		$item->getFromDB($job->fields["device_type"],$job->fields["computer"]);

		//echo "<div class='center'>";
		echo "<form method='post' name='form_ticket' action='$target'  enctype=\"multipart/form-data\">\n";
		echo "<div class='center' id='tabsbody'>";
		echo "<table class='tab_cadre_fixe' cellpadding='5'>";

		// OPtional line 
		if (isMultiEntitiesMode()){
			echo "<tr><th colspan='3'>";
			echo getDropdownName("glpi_entities",$job->fields["FK_entities"]);
			echo "</th></tr>";
		}

		// First line
		echo "<tr><th colspan='2' style='text-align:left;'><table><tr><td><span class='tracking_small'>";
		echo $LANG["joblist"][11].": </span></td><td>";
		showDateTimeFormItem("date",$job->fields["date"],1,false,$canupdate);

		echo "</td><td><span class='tracking_small'>&nbsp;&nbsp; ".$LANG["job"][2]." &nbsp; </span></td><td>";
		if ($canupdate){
			dropdownAllUsers("recipient",$job->fields["recipient"],1,$job->fields["FK_entities"]);
		} else {
			echo getUserName($job->fields["recipient"],$showuserlink);
		}

		echo "</td></tr></table>";

		if (strstr($job->fields["status"],"old_")){
			echo "<table><tr><td>";
			echo "<span class='tracking_small'>".$LANG["joblist"][12].": </td><td>";
			
			showDateTimeFormItem("closedate",$job->fields["closedate"],1,false,$canupdate);
			echo "</span></td></tr></table>\n";
		}

		echo "</th>";
		echo "<th><span class='tracking_small'>".$LANG["common"][26].":<br>";
		
		echo convDateTime($job->fields["date_mod"])."\n";
	
		echo "</span></th></tr>";
		echo "<tr class='tab_bg_2'>";
		// Premier Colonne
		echo "<td class='top' width='27%'>";
		echo "<table cellpadding='3'>";
		echo "<tr class='tab_bg_2'><td class='left'>";
		echo $LANG["joblist"][0].":</td><td>";
		if ($canupdate){
			dropdownStatus("status",$job->fields["status"]);
		} else {
			echo getStatusName($job->fields["status"]);
		}
		echo "</td></tr>";


		echo "<tr><td class='left'>";
		echo $LANG["joblist"][2].":</td><td>";
		if ($canupdate)
			dropdownPriority("priority",$job->fields["priority"]);
		else echo getPriorityName($job->fields["priority"]);
		echo "</td></tr>";

		echo "<tr><td class='left'>";
		echo $LANG["common"][36].":</td><td >";
		if ($canupdate)
			dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
		else echo getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"]);
		echo "</td></tr>";

		echo "<tr><td class='center' colspan='2'><strong>";
		echo $LANG["job"][4].":</strong></td></tr>";

		echo "<tr><td class='left'>";
		echo $LANG["common"][34].":</td><td>";
		if ($canupdate){
			dropdownAllUsers("author",$job->fields["author"],1,$job->fields["FK_entities"]);
		} else {
			echo getUserName($job->fields["author"],$showuserlink);
		}
		echo "</td></tr>";

		echo "<tr><td class='left'>";
		echo $LANG["common"][35].":</td><td>";
		if ($canupdate){
			dropdownValue("glpi_groups","FK_group",$job->fields["FK_group"],1,$job->fields["FK_entities"]);
		} else {
			echo getDropdownName("glpi_groups",$job->fields["FK_group"]);
		}
		echo "</td></tr>";


		echo "</table></td>";

		// Deuxieme colonne
		echo "<td class='top' width='33%'>";

		echo "<table>";

		echo "<tr><td class='left'>";
		echo $LANG["job"][44].":</td><td>";
		if ($canupdate)
			dropdownRequestType("request_type",$job->fields["request_type"]);
		else echo getRequestTypeName($job->fields["request_type"]);
		echo "</td></tr>";

		echo "<tr><td class='left'>";
		echo $LANG["common"][1].":</td><td>";
		if ($canupdate){
			if (haveTypeRight($job->fields["device_type"],'r')){
				echo $item->getType()." - ".$item->getLink(1);
			} else {
				echo $item->getType()." ".$item->getNameID();
			}
			dropdownTrackingAllDevices("device_type",$job->fields["device_type"],1,$job->fields["FK_entities"]);
		}
		else {
			echo $item->getType()." ".$item->getNameID();
		}

		echo "</td></tr>";


		echo "<tr><td class='center' colspan='2'><strong>";
		echo $LANG["job"][5].":</strong></td></tr>";

		if (haveRight("assign_ticket","1")){
			echo "<tr><td class='left'>";
			echo $LANG["job"][6].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"own_ticket",0,1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		} else if (haveRight("steal_ticket","1")) {
			echo "<tr><td class='right'>";
			echo $LANG["job"][6].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"ID",0,1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		} else if (haveRight("own_ticket","1") && $job->fields["assign"]==0){
                        echo "<tr><td class='right'>";
                        echo $LANG["job"][6].":</td><td>";
                        dropdownUsers("assign",$job->fields["assign"],"ID",0,1,$job->fields["FK_entities"]);
                        echo "</td></tr>";
                } else {
			echo "<tr><td class='left'>";
			echo $LANG["job"][6].":</td><td>";
			echo getUserName($job->fields["assign"],$showuserlink);
			echo "</td></tr>";
		}

		if (haveRight("assign_ticket","1")){
			echo "<tr><td class='left'>";
			echo $LANG["common"][35].":</td><td>";
			dropdownValue("glpi_groups","assign_group",$job->fields["assign_group"],1,$job->fields["FK_entities"]);
			echo "</td></tr>";
			echo "<tr><td class='left'>";
			echo $LANG["financial"][26].":</td><td>";
			dropdownValue("glpi_enterprises","assign_ent",$job->fields["assign_ent"],1,$job->fields["FK_entities"]);
			echo "</td></tr>";
		} else {
			echo "<tr><td class='left'>";
			echo $LANG["common"][35].":</td><td>";
			echo getDropdownName("glpi_groups",$job->fields["assign_group"]);
			echo "</td></tr>";
			echo "<tr><td class='left'>";
			echo $LANG["financial"][26].":</td><td>";
			echo getDropdownName("glpi_enterprises",$job->fields["assign_ent"]);
			echo "</td></tr>";
		}
		echo "</table>";


		echo "</td>";

		// Troisieme Colonne
		echo "<td class='top' width='20%'>";
		echo "<table border='0'>";

		echo "<tr><td class='left'>";
		echo $LANG["job"][20].":</td><td>";
		echo "<strong>".getRealtime($job->fields["realtime"])."</strong>";
		echo "</td></tr>";

		if(haveRight("contract","r")){  // admin = oui on affiche les couts liés à l'interventions

			echo "<tr><td class='left'>";
			// cout
			echo $LANG["job"][40].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_time' value=\"".formatNumber($job->fields["cost_time"],true)."\"></td></tr>";

			echo "<tr><td class='left'>";

			echo $LANG["job"][41].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_fixed' value=\"".formatNumber($job->fields["cost_fixed"],true)."\">";

			echo "</td></tr>\n";

			echo "<tr><td class='left'>";

			echo $LANG["job"][42].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_material' value=\"".formatNumber($job->fields["cost_material"],true)."\">";

			echo "</td></tr>\n";

			echo "<tr><td class='left'>";

			echo $LANG["job"][43].": ";
			echo "</td><td><strong>";
			echo trackingTotalCost($job->fields["realtime"],$job->fields["cost_time"],$job->fields["cost_fixed"],$job->fields["cost_material"]);
			echo "</strong></td></tr>\n";
		}
		echo '</table>';
		echo "</td></tr>";


		// Deuxieme Ligne
		// Colonnes 1 et 2
		echo "<tr class='tab_bg_1'><td colspan='2'>";
		echo "<table width='99%' >";
		echo "<tr class='tab_bg_2'><th colspan='2'>";
		if ($canupdate_descr){
			$rand=mt_rand();
			echo "<script type='text/javascript' >\n";
			echo "function showName$rand(){\n";
				echo "Ext.get('name$rand').setDisplayed('none');";
				$params=array('maxlength'=>250,
					'size'=>80,
					'name'=>'name',
					'data'=>rawurlencode($job->fields["name"]),
				);
				ajaxUpdateItemJsCode("viewname$rand",$CFG_GLPI["root_doc"]."/ajax/inputtext.php",$params,false);
			echo "}";
			echo "</script>\n";
			echo "<div id='name$rand' class='tracking' onClick='showName$rand()'>\n";
			if (empty($job->fields["name"])){
				echo $LANG["reminder"][15];
			} else {
				echo $job->fields["name"];
			}
			echo "</div>\n";	

			echo "<div id='viewname$rand'>\n";
			echo "</div>\n";
			//echo "<input type='text' maxlength='250' size='80' name='name' value=\"".$job->fields["name"]."\">";
		} else {
			if (empty($job->fields["name"])){
				echo $LANG["reminder"][15];
			} else {
				echo $job->fields["name"];
			}
		}
		echo "</th></tr>";
		echo "<tr  class='tab_bg_2'><td width='15%'>".$LANG["joblist"][6]."</td>";
		echo "<td  width='85%' class='left'>";

		if ($canupdate_descr){ // Admin =oui on autorise la modification de la description
			$rand=mt_rand();
			echo "<script type='text/javascript' >\n";
			echo "function showDesc$rand(){\n";

				echo "Ext.get('desc$rand').setDisplayed('none');";
				$params=array('rows'=>6,
					'cols'=>60,
					'name'=>'contents',
					'data'=>rawurlencode($job->fields["contents"]),
				);
				ajaxUpdateItemJsCode("viewdesc$rand",$CFG_GLPI["root_doc"]."/ajax/textarea.php",$params,false);

			echo "}";
			echo "</script>\n";
			echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
			if (!empty($job->fields["contents"]))
				echo nl2br($job->fields["contents"]);
			else echo $LANG["job"][33];

			echo "</div>\n";	

			echo "<div id='viewdesc$rand'>\n";
			echo "</div>\n";	
		} else echo nl2br($job->fields["contents"]);

		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		// Colonne 3

		echo "<td class='top'>";

		// Mailing ? Y or no ?

		if ($CFG_GLPI["mailing"]==1){
			echo "<table><tr><td class='right'>";
			echo $LANG["job"][19].":</td><td>";
			if ($canupdate){
				dropdownYesNo('emailupdates',$job->fields["emailupdates"]);
			} else {
				if ($job->fields["emailupdates"]) echo $LANG["choice"][1];
				else $LANG["choice"][0];
			}
			echo "</td></tr>";

			echo "<tr><td class='right'>";
			echo $LANG["joblist"][27].":";
			echo "</td><td>";
			if ($canupdate){
				autocompletionTextField("uemail","glpi_tracking","uemail",$job->fields["uemail"],15,$job->fields["FK_entities"]);

				if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' alt='Mail'></a>";
			} else if (!empty($job->fields["uemail"]))
				echo "<a href='mailto:".$job->fields["uemail"]."'>".$job->fields["uemail"]."</a>";
			else echo "&nbsp;";
			echo "</td></tr></table>";


		}




		// File associated ?
		$query2 = "SELECT * 
			FROM glpi_doc_device 
			WHERE glpi_doc_device.FK_device = '".$job->fields["ID"]."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' ";
		$result2 = $DB->query($query2);
		$numfiles=$DB->numrows($result2);
		echo "<table width='100%'><tr><th colspan='2'>".$LANG["document"][21]."</th></tr>";			

		if ($numfiles>0){
			$doc=new Document;
			while ($data=$DB->fetch_array($result2)){
				$doc->getFromDB($data["FK_doc"]);
				
				echo "<tr><td>";
				
				if (empty($doc->fields["filename"])){
					if (haveRight("document","r")){
						echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?ID=".$data["FK_doc"]."'>".$doc->fields["name"]."</a>";
					} else {
						echo $LANG["document"][37];
					}
				} else {
					echo getDocumentLink($doc->fields["filename"],"&tracking=$ID");
				}
				if (haveRight("document","w"))
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/document.form.php?deleteitem=delete&amp;ID=".$data["ID"]."&amp;devtype=".TRACKING_TYPE."&amp;devid=".$ID."&amp;docid=".$data["FK_doc"]."'><img src='".$CFG_GLPI["root_doc"]."/pics/delete.png' alt='".$LANG["buttons"][6]."'></a>";
				echo "</td></tr>";
			}
		}
		if ($canupdate||haveRight("comment_all_ticket","1")
			||(haveRight("comment_ticket","1")&&!strstr($job->fields["status"],'old_'))
		){
			echo "<tr><td colspan='2'>";
			echo "<input type='file' name='filename' size='20'>";
			if ($canupdate&&haveRight("document","r")){
				echo "<br>";
				dropdownDocument("document",$job->fields["FK_entities"]);
			}
			echo "</td></tr>";
		}
		echo "</table>";

		echo "</td></tr>";
		// Troisi�e Ligne
		if ($canupdate||$canupdate_descr||haveRight("comment_all_ticket","1")
			||(haveRight("comment_ticket","1")&&!strstr($job->fields["status"],'old_'))
			||haveRight("assign_ticket","1")||haveRight("steal_ticket","1")
			
			){
			echo "<tr class='tab_bg_1'><td colspan='3' class='center'>";
			echo "<input type='submit' class='submit' name='update' value='".$LANG["buttons"][14]."'></td></tr>";
		}

		echo "</table>";
		echo "<input type='hidden' name='ID' value='$ID'>";
		echo "</div>";
		echo "</form>";

		return true;
	} else {
		echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
		return false;
	}
}

function showFollowupsSummary($tID){
	global $DB,$LANG,$CFG_GLPI;


	if (!haveRight("observe_ticket","1")&&!haveRight("show_full_ticket","1")) return false;

	$job=new Job;
	$job->getFromDB($tID);
	// Display existing Followups
	$showprivate=haveRight("show_full_ticket","1");
	$caneditall=haveRight("update_followups","1");
	
	$RESTRICT="";
	if (!$showprivate)  $RESTRICT=" AND ( private='0' OR author ='".$_SESSION["glpiID"]."' ) ";

	$query = "SELECT * FROM glpi_followups WHERE (tracking = '$tID') $RESTRICT ORDER BY date DESC";
	$result=$DB->query($query);
	
	$rand=mt_rand();
	
	echo "<div id='viewfollowup".$tID."$rand'>\n";
	echo "</div>\n";

	echo "<div class='center'>";
	echo "<h3>".$LANG["job"][37]."</h3>";

	if ($DB->numrows($result)==0){
		echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>";
		echo "<strong>".$LANG["job"][12]."</strong>";
		echo "</th></tr></table>";
	}
	else {	
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>&nbsp;</th><th>".$LANG["common"][27]."</th><th>".$LANG["joblist"][6]."</th><th>".$LANG["job"][31]."</th><th>".$LANG["job"][35]."</th><th>".$LANG["common"][37]."</th>";
		if ($showprivate)
			echo "<th>".$LANG["common"][77]."</th>";
		echo "</tr>";
		while ($data=$DB->fetch_array($result)){
			$canedit=($caneditall||$data['author']==$_SESSION['glpiID']);

			echo "<tr class='tab_bg_2' ".($canedit?"style='cursor:pointer' onClick=\"viewEditFollowup".$tID.$data["ID"]."$rand();\"":"style='cursor:none'")
				." id='viewfollowup".$tID.$data["ID"]."$rand'>";
			echo "<td>".$data["ID"]."</td>";

			echo "<td>";
			if ($canedit){
				echo "<script type='text/javascript' >\n";
				echo "function viewEditFollowup".$tID.$data["ID"]."$rand(){\n";
					//echo "window.document.getElementById('viewfollowup').style.display='none';";
					$params=array('ID'=>$data["ID"],
					);
					ajaxUpdateItemJsCode("viewfollowup".$tID."$rand",$CFG_GLPI["root_doc"]."/ajax/viewfollowup.php",$params,false);
				echo "};";
	
				echo "</script>\n";
			}


			echo convDateTime($data["date"])."</td>";
			echo "<td class='left'>".nl2br($data["contents"])."</td>";

			$hour=floor($data["realtime"]);
			$minute=round(($data["realtime"]-$hour)*60,0);
			echo "<td>";
			if ($hour) echo "$hour ".$LANG["job"][21]."<br>";
			if ($minute||!$hour)
				echo "$minute ".$LANG["job"][22]."</td>";

			echo "<td>";
			$query2="SELECT * 
				FROM glpi_tracking_planning 
				WHERE id_followup='".$data['ID']."'";
			$result2=$DB->query($query2);
			if ($DB->numrows($result2)==0){
				echo $LANG["job"][32];	
			} else {
				$data2=$DB->fetch_array($result2);
				echo "<script type='text/javascript' >\n";
				echo "function showPlan".$data['ID']."(){\n";
					
					echo "Ext.get('plan').setDisplayed('none');";
					$params=array('form'=>'followups',
						'author'=>$data2["id_assign"],
						'ID'=>$data2["ID"],
						'state'=>$data2["state"],
						'begin'=>$data2["begin"],
						'end'=>$data2["end"],
						'entity'=>$job->fields["FK_entities"],
						);
					ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,false);
					echo "}";
				echo "</script>\n";

				echo getPlanningState($data2["state"])."<br>".convDateTime($data2["begin"])."<br>->".convDateTime($data2["end"])."<br>".getUserName($data2["id_assign"]);
			}
			echo "</td>";

			echo "<td>".getUserName($data["author"])."</td>";
			if ($showprivate){
				echo "<td>";
				if ($data["private"])
					echo $LANG["choice"][1];
				else echo $LANG["choice"][0];
				echo "</td>";
			}

			echo "</tr>";
		}
		echo "</table>";
	}	
	echo "</div>";
}

/** Form to add a followup to a ticket
* @param $tID integer : ticket ID
* @param $massiveaction boolean : add followup using massive action
*/
function showAddFollowupForm($tID,$massiveaction=false){
	global $DB,$LANG,$CFG_GLPI;

	$job=new Job;

	if ($tID>0){
		$job->getFromDB($tID);
	} else {
		$job->getEmpty();
	}
	$prefix="";
	$postfix="";
	// Add followup at creating ticket : prefix values
	if ($tID<0&&!$massiveaction){
		$prefix="_followup[";
		$postfix="]";
	}
	if (!haveRight("comment_ticket","1")&&!haveRight("comment_all_ticket","1")&&$job->fields["assign"]!=$_SESSION["glpiID"]&&!in_array($job->fields["assign_group"],$_SESSION["glpigroups"])) return false;


	$commentall=(haveRight("comment_all_ticket","1")||$job->fields["assign"]==$_SESSION["glpiID"]||in_array($job->fields["assign_group"],$_SESSION["glpigroups"]));

	if ($_SESSION["glpiactiveprofile"]["interface"]=="central"){
		$target=$CFG_GLPI["root_doc"]."/front/tracking.form.php";
	} else {
		$target=$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user";
	}
	// Display Add Table
	echo "<div class='center'>";
	if ($tID>0){
		echo "<form name='followups' method='post' action=\"$target\">\n";
	}
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'>";
	echo $LANG["job"][29];
	echo "</th></tr>";

	if ($commentall){
		$width_left=$width_right="50%";
		$cols=50;
	} else {
		$width_left="80%";
		$width_right="20%";
		$cols=80;
	}

	echo "<tr class='tab_bg_2'><td width='$width_left'>";
	echo "<table width='100%'>";
	echo "<tr><td>".$LANG["joblist"][6]."</td>";
	echo "<td><textarea name='".$prefix."contents".$postfix."' rows='12' cols='$cols'></textarea>";
	echo "</td></tr>";
	echo "</table>";
	echo "</td>";

	echo "<td width='$width_right' valign='top'>";
	echo "<table width='100%'>";

	if ($commentall){
		echo "<tr>";
		echo "<td>".$LANG["common"][77].":</td>";
		echo "<td>";
		echo "<select name='".$prefix."private".$postfix."'>";
		echo "<option value='0' ".(!$_SESSION['glpifollowup_private']?"selected":"").">".$LANG["choice"][0]."</option>";
		echo "<option value='1' ".($_SESSION['glpifollowup_private']?"selected":"").">".$LANG["choice"][1]."</option>";
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		if ($tID>0){
			echo "<tr><td>".$LANG["job"][31].":</td><td>";
			dropdownInteger('hour',0,0,100);
			echo $LANG["job"][21]."&nbsp;&nbsp;";
			dropdownInteger('minute',0,0,59);
			echo $LANG["job"][22];
			echo "</tr>";
		}

		if (haveRight("show_planning","1")&&!$massiveaction){
			echo "<tr>";
			echo "<td>".$LANG["job"][35]."</td>";

			echo "<td>";

			echo "<script type='text/javascript' >\n";
			echo "function showPlanAdd(){\n";
		
				echo "Ext.get('plan').setDisplayed('none');";
				$params=array('form'=>'followups',
					'state'=>1,
					'author'=>$_SESSION['glpiID'],
					'entity'=>$_SESSION["glpiactive_entity"],
				);
				ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,false);
		
			echo "};";
			echo "</script>";

			echo "<div id='plan'  onClick='showPlanAdd()'>\n";
			echo "<span class='showplan'>".$LANG["job"][34]."</span>";
			echo "</div>\n";	

			echo "<div id='viewplan'>\n";
			echo "</div>\n";	


			echo "</td>";

			echo "</tr>";
		}
	}
	if ($tID>0||$massiveaction){
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>";
		echo "<input type='submit' name='add' value='".$LANG["buttons"][8]."' class='submit'>";
		echo "</td>";
		if ($commentall&&$tID>0){
			echo "<td class='center'>";
			// closed ticket 
			if (strstr($job->fields['status'],'old_')){
				echo "<input type='submit' name='add_reopen' value='".$LANG["buttons"][54]."' class='submit'>";
			}else { // not closed ticket
				echo "<input type='submit' name='add_close' value='".$LANG["buttons"][26]."' class='submit'>";
			}
			echo "</td>";
		}
		echo "</tr>";
	} else {

	}


	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
	if ($tID>0){
		echo "<input type='hidden' name='tracking' value='$tID'>";
		echo "</form>";
	}
	echo "</div>";

}


/** Form to update a followup to a ticket
* @param $ID integer : followup ID
*/
function showUpdateFollowupForm($ID){
	global $DB,$LANG,$CFG_GLPI;

	$fup=new Followup();
	
	if ($fup->getFromDB($ID)){
		if ($fup->fields["author"]!=$_SESSION['glpiID']&&!haveRight("update_followups","1")) {
			return false;
		}

		$commentall=haveRight("update_followups","1");

		$job=new Job();
		$job->getFromDB($fup->fields["tracking"]);

		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>";
		echo $LANG["job"][39];
		echo "</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/tracking.form.php\">\n";

		echo "<table width='100%'>";
		echo "<tr class='tab_bg_2'><td width='50%'>";
		echo "<table width='100%' bgcolor='#FFFFFF'>";
		echo "<tr class='tab_bg_1'><td align='center' width='10%'>".$LANG["joblist"][6]."<br><br>".$LANG["common"][27].":<br>".convDateTime($fup->fields["date"])."</td>";
		echo "<td width='90%'>";

		if ($commentall){
			echo "<textarea name='contents' cols='50' rows='6'>".$fup->fields["contents"]."</textarea>";
		} else echo nl2br($fup->fields["contents"]);


		echo "</td></tr>";
		echo "</table>";
		echo "</td>";

		echo "<td width='50%' valign='top'>";
		echo "<table width='100%'>";


		if ($commentall){
			echo "<tr>";
			echo "<td>".$LANG["common"][77].":</td>";
			echo "<td>";
			echo "<select name='private'>";
			echo "<option value='0' ".(!$fup->fields["private"]?" selected":"").">".$LANG["choice"][0]."</option>";
			echo "<option value='1' ".($fup->fields["private"]?" selected":"").">".$LANG["choice"][1]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
		} 



		echo "<tr><td>".$LANG["job"][31].":</td><td>";
		$hour=floor($fup->fields["realtime"]);
		$minute=round(($fup->fields["realtime"]-$hour)*60,0);

		if ($commentall){

			dropdownInteger('hour',$hour,0,100);
			echo $LANG["job"][21]."&nbsp;&nbsp;";
			dropdownInteger('minute',$minute,0,59);
			echo $LANG["job"][22];
		} else {
			echo $hour." ".$LANG["job"][21]." ".$minute." ".$LANG["job"][22];

		}

		echo "</tr>";

		echo "<tr>";
		echo "<td>".$LANG["job"][35]."</td>";
		echo "<td>";

		$query2="SELECT * 
			FROM glpi_tracking_planning 
			WHERE id_followup='".$fup->fields['ID']."'";
		$result2=$DB->query($query2);
		if ($DB->numrows($result2)==0){
			if ($commentall){

				echo "<script type='text/javascript' >\n";
				echo "function showPlanUpdate(){\n";
		
					echo "Ext.get('plan').setDisplayed('none');";
					$params=array('form'=>'followups',
						'state'=>1,
						'author'=>$_SESSION['glpiID'],
						'entity'=>$_SESSION["glpiactive_entity"],
					);
					ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,false);
		
				echo "};";
				echo "</script>";


				echo "<div id='plan'  onClick='showPlanUpdate()'>\n";
				echo "<span class='showplan'>".$LANG["job"][34]."</span>";
				echo "</div>\n";	
				echo "<div id='viewplan'></div>\n";
			} else {
				echo $LANG["job"][32];	
			}
		 } else {
			$fup->fields2=$DB->fetch_array($result2);
			if ($commentall){

				echo "<div id='plan'  onClick='showPlan".$ID."()'>\n";
				echo "<span class='showplan'>";
			}
			echo getPlanningState($fup->fields2["state"])."<br>".convDateTime($fup->fields2["begin"])."<br>->".convDateTime($fup->fields2["end"])."<br>".getUserName($fup->fields2["id_assign"]);
			if ($commentall){
				echo "</span>";
				echo "</div>\n";	
				echo "<div id='viewplan'></div>\n";
			}
		}

		echo "</td>";
		echo "</tr>";

		if ($commentall){
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' colspan='2'>";
			echo "<table width='100%'><tr><td class='center'>";
			echo "<input type='submit' name='update_followup' value='".$LANG["buttons"][14]."' class='submit'>";
			echo "</td><td class='center'>";
			echo "<input type='submit' name='delete_followup' value='".$LANG["buttons"][6]."' class='submit'>";
			echo "</td></tr></table>";
			echo "</td>";
			echo "</tr>";
		}


		echo "</table>";
		echo "</td></tr>";

		echo "</table>";
		if ($commentall){
			echo "<input type='hidden' name='ID' value='".$fup->fields["ID"]."'>";
			echo "<input type='hidden' name='tracking' value='".$fup->fields["tracking"]."'>";
			echo "</form>";
		}
		echo "</td></tr>";
		echo "</table>";
		echo "</div>";


	}
}

/** Computer total cost of a ticket
* @param $realtime float : ticket realtime 
* @param $cost_time float : ticket time cost
* @param $cost_fixed float : ticket fixed cost
* @param $cost_material float : ticket material cost 
* @return total cost formatted string
*/
function trackingTotalCost($realtime,$cost_time,$cost_fixed,$cost_material){
	return formatNumber(($realtime*$cost_time)+$cost_fixed+$cost_material);
}

/**
 * Calculate Ticket TCO for a device
 *
 * 
 *
 *@param $item_type device type
 *@param $item ID of the device
 *
 *@return float
 *
 **/
function computeTicketTco($item_type,$item){
	global $DB;
	$totalcost=0;

	$query="SELECT * 
		FROM glpi_tracking 
		WHERE (device_type = '$item_type' 
				AND computer = '$item') 
			AND (cost_time>0 
				OR cost_fixed>0
				OR cost_material>0)";
	$result = $DB->query($query);

	$i = 0;
	if ($DB->numrows($result)){
		while ($data=$DB->fetch_array($result)){
			$totalcost+=trackingTotalCost($data["realtime"],$data["cost_time"],$data["cost_fixed"],$data["cost_material"]); 
		}
	}
	return $totalcost;
}

	function showPreviewAssignAction($output)
	{
		global $LANG,$INFOFORM_PAGES,$CFG_GLPI;
		print_r($output);
		//If ticket is assign to an object, display this information first
		if (isset($output["FK_entities"]) && isset($output["computer"]) && isset($output["device_type"]))
		{
			echo "<tr  class='tab_bg_2'>";
			echo "<td class='tab_bg_2'>".$LANG["rulesengine"][48]."</td>";

			$commonitem = new CommonItem;
			$commonitem->getFromDB($output["device_type"],$output["computer"]);
			echo "<td class='tab_bg_2'>";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$output["device_type"]]."?ID=".$output["computer"]."\">".$commonitem->obj->fields["name"]."</a>";				
			echo "</td>";
			echo "</tr>";
			
			//Clean output of unnecessary fields (already processed)
			
			unset($output["computer"]);
			unset($output["device_type"]);
		}
		
		unset($output["FK_entities"]);
		return $output;
	}
?>
