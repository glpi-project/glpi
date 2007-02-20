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


// FUNCTIONS Tracking System


function titleTracking(){
	global  $lang,$HTMLRel;
	// titre
	echo "<div align='center'><table border='0'><tr><td>\n";
	echo "<img src=\"".$HTMLRel."pics/suivi-intervention.png\" alt=''></td><td><span class='icon_sous_nav'>".$lang["tracking"][0]."</span>\n";
	echo "</td></tr></table>&nbsp;</div>\n";

}

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
	global $lang,$HTMLRel,$cfg_glpi;

	if (preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
		$ID=$ereg[1];

		$job=new Job();
		$job->getFromDB($ID);

		echo "<div id='barre_onglets'><ul id='onglet'>";

		if ($_SESSION["glpiprofile"]["interface"]=="central"){
			echo "<li class='actif'><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.form.php?ID=$ID&amp;onglet=1\">".$lang["job"][38]." $ID</a></li>";

			if (haveRight("show_ticket","1"))
				display_plugin_headings($target,TRACKING_TYPE,"","");

			echo "<li class='invisible'>&nbsp;</li>";

			// admin yes  
			if (haveRight("comment_ticket","1")||haveRight("comment_all_ticket","1")||$job->fields["assign"]==$_SESSION["glpiID"]){
				echo "<li onClick=\"showAddFollowup(); Effect.Appear('viewfollowup');\" id='addfollowup'><a href='#'>".$lang["job"][29]."</a></li>";
			}


			// Post-only could'nt see other item  but other user yes 
			if (haveRight("show_ticket","1")){
				echo "<li class='invisible'>&nbsp;</li>";

				$next=getNextItem("glpi_tracking",$ID);
				$prev=getPreviousItem("glpi_tracking",$ID);
				$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
				if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
				if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
			}
		}elseif (haveRight("comment_ticket","1")){

			// Postonly could post followup in helpdesk area	
			echo "<li class='actif'><span style='float: left;display: block;color: #666;text-decoration: none;padding: 3px;'><a href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=$ID\">".$lang["job"][38]." $ID</span></a></li>";

			if (!ereg("old_",$job->fields["status"])&&$job->fields["author"]==$_SESSION["glpiID"]){
				echo "<li class='invisible'>&nbsp;</li>";

				echo "<li onClick=\"showAddFollowup(); Effect.Appear('viewfollowup');\" id='addfollowup'><a href='#'>".$lang["job"][29]."</span></a></li>";
			}
		}

	}

	echo "</ul></div>";	 

}





function commonTrackingListHeader($output_type=HTML_OUTPUT,$target="",$parameters="",$sort="",$order=""){
	global $lang,$cfg_glpi;

	// New Line for Header Items Line
	echo displaySearchNewLine($output_type);
	// $show_sort if 
	$header_num=1;

	$items=array(
			$lang["joblist"][0]=>"glpi_tracking.status",
			$lang["common"][27]=>"glpi_tracking.date",
			$lang["joblist"][2]=>"glpi_tracking.priority",
			$lang["common"][37]=>"author.name",
			$lang["joblist"][4]=>"assign.name",
			$lang["common"][1]=>"glpi_tracking.device_type,glpi_tracking.computer",
			$lang["common"][36]=>"glpi_dropdown_tracking_category.completename",
			$lang["joblist"][6]=>"glpi_tracking.contents",
		    );

	foreach ($items as $key => $val){
		$issort=0;
		$link="";
		if ($sort==$val) $issort=1;
		$link=$target."?".$parameters."&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;sort=$val";
		if (ereg("helpdesk",$target)){
			$link.="&amp;show=user";
		}
		echo displaySearchHeaderItem($output_type,$key,$header_num,$link,$issort,$order);
	}

	echo displaySearchHeaderItem($output_type,"",$header_num,"",0,$order);

	// End Line for column headers		
	echo displaySearchEndLine($output_type);
}

function getTrackingOrderPrefs ($ID) {
	// Returns users preference settings for job tracking
	// Currently only supports sort order


	if($_SESSION["glpitracking_order"] == "yes")
	{
		return "DESC";
	} 
	else
	{
		return "ASC";
	}

}

function showCentralJobList($target,$start,$status="process") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.

	global $db,$cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("show_ticket","1")) return false;

	if($status=="waiting"){ // on affiche les tickets en attente
		$query = "SELECT ID FROM glpi_tracking WHERE (assign = '".$_SESSION["glpiID"]."') AND (status ='waiting' ) ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);

		$title=$lang["central"][11];

	}else{ // on affiche les tickets planifiés ou assignés à glpiID

		$query = "SELECT ID FROM glpi_tracking WHERE (assign = '".$_SESSION["glpiID"]."') AND (status ='plan' OR status = 'assign') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);

		$title=$lang["central"][9];
	}

	$lim_query = " LIMIT ".$start.",".$cfg_glpi["list_limit"]."";	

	$result = $db->query($query);
	$numrows = $db->numrows($result);

	$query .= $lim_query;

	$result = $db->query($query);
	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0) {
		echo "<div align='center'>";
		echo "<table class='tab_cadrehov'>";

		echo "<tr><th colspan='5'><b><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?assign=".$_SESSION["glpiID"]."&amp;status=$status&amp;reset=reset_before\">".$title."</a></b></th></tr>";
		echo "<tr><th></th>";
		echo "<th>".$lang["common"][37]."</th>";
		echo "<th>".$lang["common"][1]."</th>";
		echo "<th colspan='2'>".$lang["joblist"][6]."</th></tr>";
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			showJobVeryShort($ID);
			$i++;
		}
		echo "</table>";
		echo "<br><div align='center'>";
	}
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>".$title."</th></tr>";

		echo "</table>";
		echo "</div><br>";
	}
}

function showCentralJobCount(){
	// show a tab with count of jobs in the central and give link	

	global $db,$cfg_glpi, $lang, $HTMLRel;

	if (!haveRight("show_ticket","1")) return false;	

	$query="SELECT status, COUNT(*) AS COUNT FROM glpi_tracking GROUP BY status";



	$result = $db->query($query);


	$status=array("new"=>0, "assign"=>0, "plan"=>0, "waiting"=>0);

	if ($db->numrows($result)>0)
		while ($data=$db->fetch_assoc($result)){

			$status[$data["status"]]=$data["COUNT"];
		}

	echo "<div align='center'><table class='tab_cadrehov' style='text-align:center'>";

	echo "<tr><th colspan='2'><b><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?status=process&amp;reset=reset_before\">".$lang["tracking"][0]."</a></b></th></tr>";
	echo "<tr><th ><b>".$lang["tracking"][28]."</b></th><th>".$lang["tracking"][29]."</th></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?status=new&amp;reset=reset_before\">".$lang["tracking"][30]."</a> </td>";
	echo "<td>".$status["new"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?status=assign&amp;reset=reset_before\">".$lang["tracking"][31]."</a></td>";
	echo "<td>".$status["assign"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?status=plan&amp;reset=reset_before\">".$lang["tracking"][32]."</a></td>";
	echo "<td>".$status["plan"]."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/tracking.php?status=waiting&amp;reset=reset_before\">".$lang["tracking"][33]."</a></td>";
	echo "<td>".$status["waiting"]."</td></tr>";


	echo "</table></div><br>";


}




function showOldJobListForItem($username,$item_type,$item) {
	// $item is required
	// affiche toutes les vielles intervention pour un $item donn� 


	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("show_ticket","1")) return false;
	$candelete=haveRight("delete_ticket","1");

	// Form to delete old item
	if ($candelete){
		echo "<form method='post' action=\"".$_SERVER['PHP_SELF']."?ID=$item\" name='oldTrackingForm' id='oldTrackingForm'>";
		echo "<input type='hidden' name='ID' value='$item'>";
	}



	$where = "(status = 'old_done' OR status = 'old_notdone')";	
	$query = "SELECT ID FROM glpi_tracking WHERE $where and (device_type = '$item_type' and computer = '$item') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);


	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan=9>".$number." ".$lang["job"][18]."  ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";

		commonTrackingListHeader();

		while ($i < $number)
		{
			$ID = $db->result($result, $i, "ID");
			showJobShort($ID, 0);
			$i++;
		}

		echo "</table></div>";

		if ($candelete){
			echo "<br><div align='center'>";

			echo "<table class ='delete-old-job' cellpadding='5' width='950'>";
			echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt='' ></td><td><a  onclick= \"if ( markAllRows('oldTrackingForm') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=all&amp;ID=$item'>".$lang["buttons"][18]."</a></td>";

			echo "<td>/</td><td><a onclick= \"if ( unMarkAllRows('oldTrackingForm') ) return false;\" href='".$_SERVER['PHP_SELF']."?select=none&amp;ID=$item'>".$lang["buttons"][19]."</a>";
			echo "</td><td>";
			echo "<input type='submit' value=\"".$lang["buttons"][6]."\" name='delete_inter' class='submit'></td>";
			echo "<td width='75%'>&nbsp;</td></tr></table></div>";
		}
	} 
	else
	{
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$lang["joblist"][22]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
	}

	// End form for delete item
	if ($candelete)
		echo "</form>";

}

function showJobListForItem($username,$item_type,$item) {
	// $item is required
	//affiche toutes les vielles intervention pour un $item donn� 

	global $db,$cfg_glpi, $lang;

	if (!haveRight("show_ticket","1")) return false;


	$where = "(status = 'new' OR status= 'assign' OR status='plan' OR status='waiting')";	
	$query = "SELECT ID FROM glpi_tracking WHERE $where and (computer = '$item' and device_type= '$item_type') ORDER BY date ".getTrackingOrderPrefs($_SESSION["glpiID"]);


	$result = $db->query($query);

	$i = 0;
	$number = $db->numrows($result);

	if ($number > 0)
	{
		echo "<div align='center'>&nbsp;<table class='tab_cadre_fixe'>";
		echo "<tr><th colspan='9'>".$number." ".$lang["job"][17]."";
		if ($number > 1) { echo "s"; }
		echo " ".$lang["job"][16].":</th></tr>";

		if ($item)
		{
			echo "<tr><td align='center' class='tab_bg_2' colspan='9'>";
			echo "<a href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
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
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$lang["joblist"][8]."</th></tr>";

		if ($item)
		{

			echo "<tr><td align='center' class='tab_bg_2' colspan='8'>";
			echo "<a href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.php?computer=$item&amp;device_type=$item_type\"><strong>";
			echo $lang["joblist"][7];
			echo "</strong></a>";
			echo "</td></tr>";
		}
		echo "</table>";
		echo "</div><br>";
	}
}


function showJobShort($ID, $followups,$output_type=HTML_OUTPUT,$row_num=0) {
	// Prints a job in short form
	// Should be called in a <table>-segment
	// Print links or not in case of user view

	global $cfg_glpi, $lang, $HTMLRel;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$candelete=haveRight("delete_ticket","1");
	$viewusers=haveRight("user","r");
	$align="align='center'";
	$align_desc="align='left'";
	if ($followups) { 
		$align.=" valign='top' ";
		$align_desc.=" valign='top' ";
	}
	if ($job->getfromDBwithData($ID,0))
	{
		$item_num=1;
		$bgcolor=$cfg_glpi["priority_".$job->fields["priority"]];

		echo displaySearchNewLine($output_type);

		// First column
		$first_col= "ID: ".$job->fields["ID"];
		if ($output_type==HTML_OUTPUT)
			$first_col.="<br><img src=\"".$HTMLRel."pics/".$job->fields["status"].".png\" alt='".getStatusName($job->fields["status"])."' title='".getStatusName($job->fields["status"])."'>";
		else $first_col.=" - ".getStatusName($job->fields["status"]);

		if ($candelete&&$output_type==HTML_OUTPUT&&ereg("old_",$job->fields["status"])){
			$sel="";
			if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
			$first_col.="<input type='checkbox' name='todel[".$job->fields["ID"]."]' value='1' $sel>";
		}

		echo displaySearchItem($output_type,$first_col,$item_num,$row_num,0,$align);

		// Second column
		$second_col="";	
		if (!ereg("old_",$job->fields["status"]))
		{
			$second_col.="<small>".$lang["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.= "&nbsp;".convDateTime($job->fields["date"])."</small>";
		}
		else
		{
			$second_col.="<small>".$lang["joblist"][11].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".convDateTime($job->fields["date"]);
			$second_col.="<br>";
			$second_col.="<i>".$lang["joblist"][12].":";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".convDateTime($job->fields["closedate"])."</i>";
			$second_col.="<br>";
			if ($job->fields["realtime"]>0) $second_col.=$lang["job"][20].": ";
			if ($output_type==HTML_OUTPUT) $second_col.="<br>";
			$second_col.="&nbsp;".getRealtime($job->fields["realtime"]);
			$second_col.="</small>";
		}

		echo displaySearchItem($output_type,$second_col,$item_num,$row_num,0,$align." width=130");

		// Third Column
		echo displaySearchItem($output_type,"<strong>".getPriorityName($job->fields["priority"])."</strong>",$item_num,$row_num,0,"$align bgcolor='$bgcolor'");

		// Fourth Column

		if ($viewusers)
			$fourth_col="<strong>".$job->getAuthorName(1)."</strong>";
		else
			$fourth_col="<strong>".$job->getAuthorName()."</strong>";

		if ($job->fields["FK_group"])
			$fourth_col.="<br>".getDropdownName("glpi_groups",$job->fields["FK_group"]);

		echo displaySearchItem($output_type,$fourth_col,$item_num,$row_num,0,$align);

		// Fifth column
		$fifth_col="";
		if ($viewusers)
			$fifth_col.=getAssignName($job->fields["assign"],USER_TYPE,1);
		else
			$fifth_col.="<strong>".getAssignName($job->fields["assign"],USER_TYPE)."</strong>";

		if ($job->fields["assign_ent"]>0){
			$fifth_col.="<br>";
			if ($viewusers)
				$fifth_col.=getAssignName($job->fields["assign_ent"],ENTERPRISE_TYPE,1);
			else
				$fifth_col.="<strong>".getAssignName($job->fields["assign_ent"],ENTERPRISE_TYPE)."</strong>";

		}
		echo displaySearchItem($output_type,$fifth_col,$item_num,$row_num,0,$align);


		// Sixth Colum
		$sixth_col="";
		$deleted=0;
		$m= new CommonItem;
		if ($m->getfromDB($job->fields["device_type"],$job->fields["computer"]))

			if (haveTypeRight($job->fields["device_type"],"r")){

				if (isset($m->obj->fields["deleted"])&&$m->obj->fields["deleted"]=='Y')
					$deleted=1;
				$sixth_col.=$m->getType();

				if ($job->fields["device_type"]>0){
					$sixth_col.="<br><strong>";
					if ($job->computerfound) $sixth_col.=$m->getLink();
					else $sixth_col.=$m->getNameID();
					$sixth_col.="</strong>";
				} 

			}
			else {
				$m= new CommonItem;
				if ($m->getfromDB($job->fields["device_type"],$job->fields["computer"]))
					if (isset($m->obj->fields["deleted"])&&$m->obj->fields["deleted"]=='Y')
						$deleted=1;
				$sixth_col.=$m->getType();
				$sixth_col.="<br><strong>".$job->computername;
				if ($cfg_glpi["view_ID"])
					$sixth_col.=" (".$job->fields["computer"].")";
				$sixth_col.="</strong>";
			}
		echo displaySearchItem($output_type,$sixth_col,$item_num,$row_num,$deleted,$align);

		// Seventh column
		echo displaySearchItem($output_type,"<strong>".getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"])."</strong>",$item_num,$row_num,0,$align);

		// Eigth column

		$stripped_content=resume_text($job->fields["contents"],400);
		if ($followups){$stripped_content=resume_text($job->fields["contents"],$cfg_glpi["cut"]);}

		$eigth_column="<strong>".$stripped_content."</strong>";
		if ($followups&&$output_type==HTML_OUTPUT)
		{
			$eigth_column.=showFollowupsShort($job->fields["ID"]);
		}


		echo displaySearchItem($output_type,$eigth_column,$item_num,$row_num,0,$align_desc."width='300'");


		// Nineth column
		$nineth_column="";
		// Job Controls

		if ($_SESSION["glpiprofile"]["interface"]=="central"){
			if (!haveRight("show_ticket","1")&&$job->fields["author"]!=$_SESSION["glpiID"]&&$job->fields["assign"]!=$_SESSION["glpiID"]&&(!haveRight("show_group_ticket",1)||!in_array($job->fields["FK_group"],$_SESSION["glpigroups"]))) 
				$nineth_column.="&nbsp;";
			else 
				$nineth_column.="<a href=\"".$cfg_glpi["root_doc"]."/front/tracking.form.php?ID=".$job->fields["ID"]."\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")";
		}
		else
			$nineth_column.="<a href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$job->fields["ID"]."\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups(haveRight("show_full_ticket","1")).")";

		echo displaySearchItem($output_type,$nineth_column,$item_num,$row_num,0,$align." width='40'");

		// Finish Line
		echo displaySearchEndLine($output_type);
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

	global $cfg_glpi, $lang;

	// Make new job object and fill it from database, if success, print it
	$job = new Job;
	$viewusers=haveRight("user","r");
	if ($job->getfromDBwithData($ID,0))
	{
		$bgcolor=$cfg_glpi["priority_".$job->fields["priority"]];
		if ($job->fields["status"] == "new")
		{
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->fields["ID"]."</td>";

		}
		else
		{
			echo "<tr class='tab_bg_2'>";
			echo "<td align='center' bgcolor='$bgcolor' >ID: ".$job->fields["ID"];
			echo "</td>";
		}


		echo "<td align='center'>";

		if ($viewusers)
			echo "<strong>".$job->getAuthorName(1)."</strong>";
		else
			echo "<strong>".$job->getAuthorName()."</strong>";

		if ($job->fields["FK_group"])
			echo "<br>".getDropdownName("glpi_groups",$job->fields["FK_group"]);


		echo "</td>";

		$m= new CommonItem;
		$m->getfromDB($job->fields["device_type"],$job->fields["computer"]);

		if (haveTypeRight($job->fields["device_type"],"r")){
			echo "<td align='center' ";
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
			echo "<td  align='center' >".$m->getType()."<br><strong>$job->computername (".$job->fields["computer"].")</strong></td>";

		$stripped_content =resume_text($job->fields["contents"],100);
		echo "<td ><strong>".$stripped_content."</strong>";
		echo "</td>";

		// Job Controls
		echo "<td width='40' align='center'>";

		if ($_SESSION["glpiprofile"]["interface"]=="central")
			echo "<a href=\"".$cfg_glpi["root_doc"]."/front/tracking.form.php?ID=".$job->fields["ID"]."\"><strong>".$lang["joblist"][13]."</strong></a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";
		else
			echo "<a href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user&amp;ID=".$job->fields["ID"]."\">".$lang["joblist"][13]."</a>&nbsp;(".$job->numberOfFollowups().")&nbsp;<br>";

		// Finish Line
		echo "</tr>";
	}
	else
	{
		echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$lang["joblist"][16]."</i></td></tr>";
	}
}

function addFormTracking ($device_type=0,$ID=0,$author,$assign,$target,$error,$searchauthor='') {
	// Prints a nice form to add jobs

	global $cfg_glpi, $lang,$cfg_glpi,$REFERER,$db;
	if (!haveRight("create_ticket","1")) return false;

	if (!empty($error)) {
		echo "<div align='center'><strong>$error</strong></div>";
	}
	echo "<form name='form_ticket' method='post' action='$target' enctype=\"multipart/form-data\">";
	echo "<div align='center'>";

	//	if ($device_type!=0){
	echo "<input type='hidden' name='_referer' value='$REFERER'>";
	echo "<p><a class='icon_consol' href='$REFERER'>".$lang["buttons"][13]."</a></p>";
	//	}	
	echo "<table class='tab_cadre'><tr><th><a href='$target'>".$lang["buttons"][16]."</a></th><th colspan='3'>".$lang["job"][13].": <br>";
	if ($device_type!=0){
		$m=new CommonItem;
		$m->getfromDB($device_type,$ID);
		echo $m->getType()." - ".$m->getNameID();
	}
	echo "</th></tr>";

	$author_rand=0;
	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2' align='center'><td>".$lang["common"][37].":</td>";
		echo "<td align='center' colspan='3'>";
		$author_rand=dropdownAllUsers("author",$author,1,1);

		echo "</td></tr>";
	} 

	if ($device_type==0&&$_SESSION["glpiprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_2'>";
		echo "<td align='center'>".$lang["help"][24].": </td>";
		echo "<td align='center' colspan='3'>";
		dropdownTrackingDeviceType("device_type",$device_type,$_SESSION["glpiID"]);
		echo "</td></tr>";
	} else {
		echo "<tr class='tab_bg_2'><td colspan='4'>";
		echo "<input type='hidden' name='device_type' value='0'>";
		echo "</td></tr>";
	}


	if (haveRight("update_ticket","1")){
		echo "<tr class='tab_bg_2'><td align='center'>".$lang["common"][27].":</td>";
		echo "<td align='center' class='tab_bg_2'>";
		showCalendarForm("form_ticket","date",date("Y-m-d H:i"),0,1);	
		echo "</td>";

		echo "<td align='center'>".$lang["job"][44].":</td>";
		echo "<td align='center'>";
		$request_type=1;
		if (isset($_POST["request_type"])) $request_type=$_POST["request_type"];
		dropdownRequestType("request_type",$request_type);
		echo "</td></tr>";
	}


	// Need comment right to add a followup with the realtime
	if (haveRight("comment_all_ticket","1")){
		echo "<tr  class='tab_bg_2'>";
		echo "<td align='center'>";
		echo $lang["job"][20].":</td>";
		echo "<td align='center' colspan='3'><select name='hour'>";
		for ($i=0;$i<100;$i++){
			$selected="";
			if (isset($_POST["hour"])&&$_POST["hour"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
		}			

		echo "</select>".$lang["job"][21]."&nbsp;&nbsp;";
		echo "<select name='minute'>";
		for ($i=0;$i<60;$i++){
			$selected="";
			if (isset($_POST["minute"])&&$_POST["minute"]==$i) $selected="selected";
			echo "<option value='$i' $selected>$i</option>";
		}
		echo "</select>".$lang["job"][22]."&nbsp;&nbsp;";
		echo "</td></tr>";
	}


	echo "<tr class='tab_bg_2'>";

	echo "<td class='tab_bg_2' align='center'>".$lang["joblist"][2].":</td>";
	echo "<td align='center' class='tab_bg_2'>";
	$priority=3;
	if (isset($_POST["priority"])) $priority=$_POST["priority"];
	dropdownPriority("priority",$priority);
	echo "</td>";

	echo "<td>".$lang["common"][36].":</td>";
	echo "<td align='center'>";
	$category=0;
	if (isset($_POST["category"])) $category=$_POST["category"];
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td></tr>";


	if (haveRight("update_ticket","1")||haveRight("assign_ticket","1")){
		echo "<tr class='tab_bg_2'><td>".$lang["buttons"][3].":</td>";
		echo "<td align='center' colspan='3'>";
		dropdownUsers("assign",$assign,"own_ticket");
		echo "</td></tr>";
	} else if (haveRight("steal_ticket","1")) {
		echo "<tr class='tab_bg_2'><td>".$lang["buttons"][3].":</td>";
		echo "<td align='center' colspan='3'>";
		dropdownUsers("assign",$assign,"ID");
		echo "</td></tr>";
	}




	if($cfg_glpi["mailing"] == 1){

		$query="SELECT email from glpi_users WHERE ID='$author'";
		
		$result=$db->query($query);
		$email="";
		if ($result&&$db->numrows($result))
			$email=$db->result($result,0,"email");

		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".$lang["help"][8].":</td>";
		echo "<td align='center'>	<select name='emailupdates'>";
		echo "<option value='no'>".$lang["choice"][0]."";
		echo "<option value='yes' selected>".$lang["choice"][1]."";
		echo "</select>";
		echo "</td>";
		echo "<td align='center'>".$lang["help"][11].":</td>";
		echo "<td><span id='uemail_result'>";
		echo "<input type='text' size='30' name='uemail' value='$email'>";
		echo "</span>";

		echo "</td></tr>";

	}

	echo "<tr><th colspan='4' align='center'>".$lang["job"][11].":";
	if ($device_type!=0){
		echo "<input type='hidden' name='computer' value=\"$ID\">";
		echo "<input type='hidden' name='device_type' value=\"$device_type\">";
	}

	echo "</th></tr>";

	echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='80' rows='8'  name='contents'></textarea></td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size." ".$lang["common"][45]."):	";
	echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/aide.png\" style='cursor:pointer;' alt=\"aide\"onClick=\"window.open('".$cfg_glpi["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
	echo "</td>";
	echo "<td colspan='3'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td colspan='2' align='center'>";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][2]."\" class='submit'>";
	echo "</td><td colspan='2' align='center'>";
	if (haveRight("comment_all_ticket","1"))
		echo "<input type='submit' name='add_close' value=\"".$lang["buttons"][26]."\" class='submit'>";
	else echo "&nbsp;";
	echo "</td></tr>";

	if (haveRight("comment_all_ticket","1")){
		echo "<tr><th colspan='4' align='center'>".$lang["job"][45].":</th></tr>";
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'><textarea cols='80' rows='8'  name='_followup'></textarea></td></tr>";
	}

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

function searchSimpleFormTracking($target,$status="all"){

global $cfg_glpi,  $lang,$HTMLRel,$phproot;


	echo "<div align='center' >";

	echo "<form method='get' name=\"form\" action=\"".$target."\">";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='1' align='center'>".$lang["joblist"][0].":&nbsp;";
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
	echo "<td align='center' colspan='1'><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'></td>";
	echo "</tr>";
	echo "</table>";
	echo "<input type='hidden' name='start' value='0'>";
	// helpdesk case
	if (ereg("helpdesk.public.php",$target)){
		echo "<input type='hidden' name='show' value='user'>";
	}
	echo "</form>";
	echo "</div>";

}

function searchFormTracking($extended=0,$target,$start="",$status="new",$author=0,$group=0,$assign=0,$assign_ent=0,$category=0,$priority=0,$request_type=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Print Search Form

	global $cfg_glpi,  $lang,$HTMLRel,$phproot;

	if (!haveRight("show_ticket","1")) {
		if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1"))
				$author=$_SESSION["glpiID"];
			else $assign=$_SESSION["glpiID"];
	}

	if ($extended==1){
		$option["comp.ID"]				= $lang["common"][2];
		$option["comp.name"]				= $lang["common"][16];
		$option["glpi_dropdown_locations.name"]		= $lang["common"][15];
		$option["glpi_type_computers.name"]		= $lang["common"][17];
		$option["glpi_dropdown_model.name"]		= $lang["common"][22];
		$option["glpi_dropdown_os.name"]		= $lang["computers"][9];
		$option["processor.designation"]		= $lang["computers"][21];
		$option["comp.serial"]				= $lang["common"][19];
		$option["comp.otherserial"]			= $lang["common"][20];
		$option["ram.designation"]			= $lang["computers"][23];
		$option["iface.designation"]			= $lang["setup"][9];
		$option["sndcard.designation"]			= $lang["devices"][7];
		$option["gfxcard.designation"]			= $lang["devices"][2];
		$option["moboard.designation"]			= $lang["devices"][5];
		$option["hdd.designation"]			= $lang["computers"][36];
		$option["comp.comments"]			= $lang["common"][25];
		$option["comp.contact"]				= $lang["common"][18];
		$option["comp.contact_num"]		        = $lang["common"][21];
		$option["comp.date_mod"]			= $lang["common"][26];
		$option["glpi_networking_ports.ifaddr"] 	= $lang["networking"][14];
		$option["glpi_networking_ports.ifmac"] 		= $lang["networking"][15];
		$option["glpi_dropdown_netpoint.name"]		= $lang["networking"][51];
		$option["glpi_enterprises.name"]		= $lang["common"][5];
		$option["resptech.name"]			=$lang["common"][10];
	}
	echo "<form method='get' name=\"form\" action=\"".$target."\">";


	echo "<div align='center' >";

	echo "<table class='tab_cadre_fixe'>";


	echo "<tr><th colspan='6' style='vertical-align:middle' ><div style='position: relative'><span><strong>".$lang["search"][0]."</strong></span>";
	if ($extended)
		echo "<span style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href='$target?extended=0'><img src=\"".$HTMLRel."pics/deplier_up.png\" alt=''>".$lang["buttons"][36]."</a></span>";
	else echo "<span  style='  position:absolute; right:0; margin-right:5px; font-size:10px;'><a href='$target?extended=1'><img src=\"".$HTMLRel."pics/deplier_down.png\" alt=''>".$lang["buttons"][35]."</a></span>";
	echo "</div></th></tr>";



	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='1' align='center'>".$lang["joblist"][0].":<br>";
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
	echo "<td  colspan='1' align='center'>".$lang["common"][37].":<br>";
	dropdownUsersTracking("author",$author,"author");
	echo "</td>";

	echo "<td  colspan='1' align='center'>".$lang["common"][35].":<br>";
	dropdownValue("glpi_groups","group",$group);
	echo "</td>";

	echo "<td colspan='1' align='center'>".$lang["joblist"][2].":<br>";
	dropdownPriority("priority",$priority,1);
	echo "</td>";

	echo "<td colspan='2' align='center'>".$lang["common"][36].":<br>";
	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";

	echo "</tr>";
	echo "<tr class='tab_bg_1'>";

	echo "<td align='center' colspan='2'>";
	echo "<table border='0'><tr><td>".$lang["common"][1].":</td><td>";
	dropdownAllItems("item",$type,$item);
	echo "</td></tr></table>";
	echo "</td>";
	echo "<td colspan='3' align='center'>".$lang["job"][5].":<br>";

	echo $lang["job"][27].":&nbsp;";
	dropdownUsers("assign",$assign,"own_ticket",1);
	echo "<br>";
	echo $lang["job"][28].":&nbsp;";
	dropdownValue("glpi_enterprises","assign_ent",$assign_ent);

	echo "</td>";
	echo "<td align='center'>".$lang["job"][44].":<br>";
	dropdownRequestType("request_type",$request_type);
	echo "</td>";
	echo "</tr>";

	if ($extended){
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center' colspan='6'>";
		$selected="";
		if ($computers_search) $selected="checked";
		echo "<input type='checkbox' name='only_computers' value='1' $selected>".$lang["reports"][24].":&nbsp;";

		echo "<input type='text' size='15' name=\"contains\" value=\"". stripslashes($contains) ."\" >";
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
	if($extended)	{
		echo "<tr class='tab_bg_1'><td colspan='2' align='right'>".$lang["reports"][60].":</td><td align='center' colspan='2'>".$lang["search"][8].":&nbsp;";
		showCalendarForm("form","date1",$date1);
		echo "</td><td align='center' colspan='2'>";
		echo $lang["search"][9].":&nbsp;";
		showCalendarForm("form","date2",$date2);
		echo "</td></tr>";

		echo "<tr class='tab_bg_1'><td colspan='2' align='right'>".$lang["reports"][61].":</td><td align='center' colspan='2'>".$lang["search"][8].":&nbsp;";
		showCalendarForm("form","enddate1",$enddate1);
		echo "</td><td align='center' colspan='2'>";
		echo $lang["search"][9].":&nbsp;";
		showCalendarForm("form","enddate2",$enddate2);
		echo "</td></tr>";
	}
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
	echo "<input type='text' size='15' name=\"contains2\" value=\"".stripslashes($contains2)."\">";
	echo "</td>";

	echo "<td align='center' colspan='1'><input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'></td>";
	echo "<td align='center'  colspan='1'><input type='submit' name='reset' value=\"".$lang["buttons"][16]."\" class='submit'></td>";

	echo "<td align='center' colspan='2'>".$lang["reports"][59].":<select name='showfollowups'>";
	echo "<option value='1' ".($showfollowups=="1"?"selected":"").">".$lang["choice"][1]."</option>";
	echo "<option value='0' ".($showfollowups=="0"?"selected":"").">".$lang["choice"][0]."</option>";	
	echo "</select></td>";
	echo "</tr>";

	echo "</table></div>";
	echo "<input type='hidden' name='start' value='0'>";
	echo "</form>";


}


function showTrackingList($target,$start="",$sort="",$order="",$status="new",$author=0,$group=0,$assign=0,$assign_ent=0,$category=0,$priority=0,$request_type=0,$item=0,$type=0,$showfollowups="",$field2="",$contains2="",$field="",$contains="",$date1="",$date2="",$computers_search="",$enddate1="",$enddate2="") {
	// Lists all Jobs, needs $show which can have keywords 
	// (individual, unassigned) and $contains with search terms.
	// If $item is given, only jobs for a particular machine
	// are listed.
	// group = 0 : not use
	// group = -1 : groups of the author if session variable OK
	// group > 0 : specific group

	global $db,$cfg_glpi, $lang,$HTMLRel;

	$candelete=haveRight("delete_ticket","1");
	if (!haveRight("show_ticket","1")) {
		if ($author==0&&$assign==0)
			if (!haveRight("own_ticket","1"))
				$author=$_SESSION["glpiID"];
			else $assign=$_SESSION["glpiID"];
	}

	// Reduce computer list
	if ($computers_search){
		$SEARCH=makeTextSearch($contains);
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
			foreach($cfg_glpi["devices_tables"] as $key => $val) {
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
	$query = "select DISTINCT glpi_tracking.ID as ID from glpi_tracking";
	if ($computers_search){
		$query.= " LEFT JOIN glpi_computers as comp on ( comp.ID=glpi_tracking.computer AND glpi_tracking.device_type='".COMPUTER_TYPE."' )";
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

	if ($sort=="author.name"){
		$query.= " LEFT JOIN glpi_users as author ON ( glpi_tracking.author = author.ID) ";
	}
	if ($sort=="assign.name"){
		$query.= " LEFT JOIN glpi_users as assign ON ( glpi_tracking.assign = assign.ID) ";
	}
	if ($sort=="glpi_dropdown_tracking_category.completename"){
		$query.= " LEFT JOIN glpi_dropdown_tracking_category ON ( glpi_tracking.category = glpi_dropdown_tracking_category.ID) ";
	}

	$where=" WHERE '1' = '1'";

	if ($computers_search)
		$where.=" AND glpi_tracking.device_type= '1'";
	if ($category > 0){
		$where.=" AND ".getRealQueryForTreeItem("glpi_dropdown_tracking_category",$category,"glpi_tracking.category");
	}

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
		case "notold": $where.=" AND (glpi_tracking.status = 'new' OR glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' OR glpi_tracking.status = 'waiting')"; break;
		case "old": $where.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone')"; break;
		case "process": $where.=" AND ( glpi_tracking.status = 'plan' OR glpi_tracking.status = 'assign' )"; break;
		case "waiting": $where.=" AND ( glpi_tracking.status = 'waiting' )"; break;
		case "old_done": $where.=" AND ( glpi_tracking.status = 'old_done' )"; break;
		case "old_notdone": $where.=" AND ( glpi_tracking.status = 'old_notdone' )"; break;
		case "assign": $where.=" AND ( glpi_tracking.status = 'assign' )"; break;
		case "plan": $where.=" AND ( glpi_tracking.status = 'plan' )"; break;
	}

	if ($assign_ent!=0) $where.=" AND glpi_tracking.assign_ent = '$assign_ent'";
	if ($assign!=0) $where.=" AND glpi_tracking.assign = '$assign'";



	if ($request_type!=0) $where.=" AND glpi_tracking.request_type = '$request_type'";

	if ($priority>0) $where.=" AND glpi_tracking.priority = '$priority'";
	if ($priority<0) $where.=" AND glpi_tracking.priority >= '".abs($priority)."'";

	$search_author=false;
	if ($group>0) $where.=" AND glpi_tracking.FK_group = '$group'";
	else if ($group==-1&&$author!=0&&haveRight("show_group_ticket",1)){
		// Get Author group's
		if (count($_SESSION["glpigroups"])){
			$where.=" AND ( ";
			$i=0;
			foreach ($_SESSION["glpigroups"] as $gp){
				if ($i>0) $where.=" OR ";
				$where.=" glpi_tracking.FK_group = '$gp' ";
				$i++;
			}

			if ($author!=0) {
				if ($i>0) $where.=" OR ";
				$where.=" glpi_tracking.author = '$author'";
				$search_author=true;
			}

			
			$where.=")";
		}
	}

	if ($author!=0&&!$search_author) {
		$where.=" AND glpi_tracking.author = '$author'";
	}


	if ($contains2!=""){
		$SEARCH2=makeTextSearch($contains2);
		switch ($field2){
			case "both" :
				$where.= " AND (glpi_followups.contents $SEARCH2 OR glpi_tracking.contents $SEARCH2)";
			break;
			case "followup" :
				$where.= " AND (glpi_followups.contents $SEARCH2)";
			break;
			case "contents" :
				$where.= " AND (glpi_tracking.contents $SEARCH2)";
			break;
			case "ID" :
				$where= " WHERE (glpi_tracking.ID = '".$contains2."')";
			break;

		}
	}



	if ($sort=="")
		$sort="glpi_tracking.date";
	if ($order=="")
		$order=getTrackingOrderPrefs($_SESSION["glpiID"]);

	$query.=$where." ORDER BY $sort $order";
	//echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {

		$numrows= $db->numrows($result);

		if ($start<$numrows) {

			// Set display type for export if define
			$output_type=HTML_OUTPUT;
			if (isset($_GET["display_type"]))
				$output_type=$_GET["display_type"];


			// Pager
			$parameters2="field=$field&amp;contains=$contains&amp;date1=$date1&amp;date2=$date2&amp;only_computers=$computers_search&amp;field2=$field2&amp;contains2=$contains2&amp;assign=$assign&amp;assign_ent=$assign_ent&amp;author=$author&amp;group=$group&amp;start=$start&amp;status=$status&amp;category=$category&amp;priority=$priority&amp;type=$type&amp;showfollowups=$showfollowups&amp;enddate1=$enddate1&amp;enddate2=$enddate2&amp;item=$item&amp;request_type=$request_type";
			$parameters=$parameters2."&amp;sort=$sort&amp;order=$order";
			if (ereg("user.info.php",$_SERVER['PHP_SELF'])) $parameters.="&amp;ID=$author";
			// Manage helpdesk
			if (ereg("helpdesk",$target)) 
				$parameters.="&amp;show=user";
			if ($output_type==HTML_OUTPUT){
				if (!ereg("helpdesk",$target)) 
					printPager($start,$numrows,$target,$parameters,TRACKING_TYPE);
				else printPager($start,$numrows,$target,$parameters);
			}

			$nbcols=9;

			// Form to delete old item
			if ($candelete&&$output_type==HTML_OUTPUT&&($status=="old"||$status=="all"||ereg("old_",$status))){
				echo "<form method='post' id='TrackingForm' name='TrackingForm' action=\"$target\">";
			}

			$i=$start;
			if (isset($_GET['export_all']))
				$i=0;

			$end_display=$start+$cfg_glpi["list_limit"];
			if (isset($_GET['export_all']))
				$end_display=$numrows;
			// Display List Header
			echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols,1);

			commonTrackingListHeader($output_type,$target,$parameters2,$sort,$order);


			while ($i < $numrows && $i<$end_display){
				$ID = $db->result($result, $i, "ID");
				showJobShort($ID, $showfollowups,$output_type,$i-$start+1);
				$i++;
			}
			$title="";
			// Title for PDF export
			if ($output_type==PDF_OUTPUT){
				$title.=$lang["joblist"][0]." = ";
				switch($status){
					case "new": $title.=$lang["joblist"][9];break;
					case "assign": $title.=$lang["joblist"][18];break;
					case "plan": $title.=$lang["joblist"][19];break;
					case "waiting": $title.=$lang["joblist"][26];break;
					case "old_done": $title.=$lang["joblist"][10];break;
					case "old_notdone": $title.=$lang["joblist"][17];break;
					case "notold": $title.=$lang["joblist"][24];break;
					case "process": $title.=$lang["joblist"][21];break;
					case "old": $title.=$lang["joblist"][25];break;
					case "all": $title.=$lang["joblist"][20];break;
				}
				if ($author!=0) $title.=" - ".$lang["common"][37]." = ".getUserName($author);
				if ($group>0) $title.=" - ".$lang["common"][35]." = ".getDropdownName("glpi_groups",$group);
				if ($assign!=0) $title.=" - ".$lang["job"][27]." = ".getUserName($assign);
				if ($request_type!=0) $title.=" - ".$lang["job"][44]." = ".getRequestTypeName($request_type);
				if ($category!=0) $title.=" - ".$lang["common"][36]." = ".getDropdownName("glpi_dropdown_tracking_category",category);
				if ($assign_ent!=0) $title.=" - ".$lang["job"][27]." = ".getDropdownName("glpi_enterprises",$assign_ent);
				if ($priority!=0) $title.=" - ".$lang["joblist"][2]." = ".getPriorityName($priority);
				if ($type!=0&&$item!=0){
					$ci=new CommonItem();
					$ci->getFromDB($type,$item);
					$title.=" - ".$lang["common"][1]." = ".$ci->getType()." / ".$ci->getNameID();

				}
			}
			// Display footer
			echo displaySearchFooter($output_type,$title);

			// Delete selected item
			if ($candelete&&$output_type==HTML_OUTPUT&&($status=="old"||$status=="all"||ereg("old_",$status))){
				echo "<div align='center'>";
				echo "<table cellpadding='5' width='900'>";
				echo "<tr><td><img src=\"".$HTMLRel."pics/arrow-left.png\" alt=''></td><td><a onclick= \"if ( markAllRows('TrackingForm') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=all&amp;start=$start'>".$lang["buttons"][18]."</a></td>";

				echo "<td>/</td><td><a onclick=\"if ( unMarkAllRows('TrackingForm') ) return false;\" href='".$_SERVER['PHP_SELF']."?$parameters&amp;select=none&amp;start=$start'>".$lang["buttons"][19]."</a>";
				echo "</td><td>";
				echo "<td width='75%'>&nbsp;</td></table></div>";
				// End form for delete item
				echo "</form>";
			}


			// Pager
			if ($output_type==HTML_OUTPUT) // In case of HTML display
				printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><strong>".$lang["joblist"][8]."</strong></div>";

		}
	}
}

function showFollowupsShort($ID) {
	// Print Followups for a job

	global $db,$cfg_glpi, $lang;

	// Get Number of Followups

	$query="SELECT * FROM glpi_followups WHERE tracking='$ID' ORDER BY date DESC";
	$result=$db->query($query);

	$out="";
	if ($db->numrows($result)>0) {
		$out.="<div align='center'><table class='tab_cadre' width='100%' cellpadding='2'>\n";
		$out.="<tr><th>".$lang["common"][27]."</th><th>".$lang["common"][37]."</th><th>".$lang["joblist"][6]."</th></tr>\n";

		while ($data=$db->fetch_array($result)) {

			$out.="<tr class='tab_bg_3'>";
			$out.="<td align='center'>".convDateTime($data["date"])."</td>";
			$out.="<td align='center'>".getUserName($data["author"],1)."</td>";
			$out.="<td width='70%'><strong>".resume_text($data["contents"],$cfg_glpi["cut"])."</strong></td>";
			$out.="</tr>";
		}		

		$out.="</table></div>";

	}
	return $out;
}

function dropdownPriority($name,$value=0,$complete=0){
	global $lang;

	echo "<select name='$name'>";
	if ($complete){
		echo "<option value='0' ".($value==1?" selected ":"").">".$lang["search"][7]."</option>";
		echo "<option value='-5' ".($value==-5?" selected ":"").">".$lang["search"][16]." ".$lang["help"][3]."</option>";
		echo "<option value='-4' ".($value==-4?" selected ":"").">".$lang["search"][16]." ".$lang["help"][4]."</option>";
		echo "<option value='-3' ".($value==-3?" selected ":"").">".$lang["search"][16]." ".$lang["help"][5]."</option>";
		echo "<option value='-2' ".($value==-2?" selected ":"").">".$lang["search"][16]." ".$lang["help"][6]."</option>";
		echo "<option value='-1' ".($value==-1?" selected ":"").">".$lang["search"][16]." ".$lang["help"][7]."</option>";
	}
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["help"][3]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["help"][4]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["help"][5]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["help"][6]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["help"][7]."</option>";

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

function getRequestTypeName($value){
	global $lang;

	switch ($value){
		case 1 :
			return $lang["Menu"][31];
			break;
		case 2 :
			return $lang["setup"][14];
			break;
		case 3 :
			return $lang["title"][41];
			break;
		case 4 :
			return $lang["tracking"][34];
			break;
		case 5 :
			return $lang["tracking"][35];
			break;
		case 6 :
			return $lang["tracking"][36];
			break;
		default : return "";
	}	
}

function dropdownRequestType($name,$value=0){
	global $lang;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-----</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["Menu"][31]."</option>"; // Helpdesk
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["setup"][14]."</option>"; // mail
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["title"][41]."</option>"; // phone
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["tracking"][34]."</option>"; // direct
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["tracking"][35]."</option>"; // writing
	echo "<option value='6' ".($value==6?" selected ":"").">".$lang["tracking"][36]."</option>"; // other

	echo "</select>";	
}


function getAssignName($ID,$type,$link=0){
	global $cfg_glpi;

	if ($type==USER_TYPE){
		if ($ID==0) return "[Nobody]";
		return getUserName($ID,$link);

	} else if ($type==ENTERPRISE_TYPE){
		$ent=new Enterprise();
		if ($ent->getFromDB($ID)){
			$before="";
			$after="";
			if ($link){
				$before="<a href=\"".$cfg_glpi["root_doc"]."/front/enterprise.form.php?ID=".$ID."\">";
				$after="</a>";
			}

			return $before.$ent->fields["name"].$after;
		} else return "";
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


function showJobDetails ($target,$ID){
	global $db,$cfg_glpi,$lang,$HTMLRel;
	$job=new Job();

	$canupdate=haveRight("update_ticket","1");


	if ($job->getfromDB($ID)) {

		if (!haveRight("show_ticket","1")
			&&$job->fields["author"]!=$_SESSION["glpiID"]
			&&$job->fields["assign"]!=$_SESSION["glpiID"]
			&&!($_SESSION["glpiprofile"]["show_group_ticket"]&&in_array($job->fields["FK_group"],$_SESSION["glpigroups"])) ){
			return false;
		}

		$canupdate_descr=$canupdate||($job->numberOfFollowups()==0&&$job->fields["author"]==$_SESSION["glpiID"]);
		$author=new User();
		$author->getFromDB($job->fields["author"]);
		$assign=new User();
		$assign->getFromDB($job->fields["assign"]);
		$item=new CommonItem();
		$item->getFromDB($job->fields["device_type"],$job->fields["computer"]);

		showTrackingOnglets($_SERVER['PHP_SELF']."?ID=".$ID);

		echo "<div align='center'>";
		echo "<form method='post' action='$target'  enctype=\"multipart/form-data\">\n";
		echo "<table class='tab_cadre_fixe' cellpadding='5'>";
		// Premi�e ligne
		echo "<tr ><th colspan='2' style='font-size:10px'>";
		echo $lang["joblist"][11].": <strong>".convDateTime($job->fields["date"])."</strong>";"</th>";
		echo "<th style='font-size:10px'>".$lang["joblist"][12].":\n";
		if (!ereg("old_",$job->fields["status"]))
		{
			echo "<i>".$lang["job"][1]."</i>\n";
		}
		else
		{
			echo "<strong>".convDateTime($job->fields["closedate"])."</strong>\n";
		}
		echo "</th></tr>";
		echo "<tr class='tab_bg_2'>";
		// Premier Colonne
		echo "<td valign='top' width='27%'>";
		echo "<table cellpadding='3'>";
		echo "<tr class='tab_bg_2'><td align='right'>";
		echo $lang["joblist"][0].":</td><td>";
		if ($canupdate)
			dropdownStatus("status",$job->fields["status"]);
		else echo getStatusName($job->fields["status"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["common"][37].":</td><td>";
		if ($canupdate)
			dropdownAllUsers("author",$job->fields["author"]);
		else echo $author->getName();
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["common"][35].":</td><td>";
		if ($canupdate)
			dropdownValue("glpi_groups","FK_group",$job->fields["FK_group"]);
		else echo getDropdownName("glpi_groups",$job->fields["FK_group"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["joblist"][2].":</td><td>";
		if ($canupdate)
			dropdownPriority("priority",$job->fields["priority"]);
		else echo getPriorityName($job->fields["priority"]);
		echo "</td></tr>";

		echo "<tr><td>";
		echo $lang["common"][36].":</td><td>";
		if ($canupdate)
			dropdownValue("glpi_dropdown_tracking_category","category",$job->fields["category"]);
		else echo getDropdownName("glpi_dropdown_tracking_category",$job->fields["category"]);
		echo "</td></tr>";

		echo "</table></td>";

		// Deuxi�e colonne
		echo "<td valign='top' width='33%'>";

		echo "<table border='0'>";

		echo "<tr><td align='right'>";
		echo $lang["job"][44].":</td><td>";
		if ($canupdate)
			dropdownRequestType("request_type",$job->fields["request_type"]);
		else echo getRequestTypeName($job->fields["request_type"]);
		echo "</td></tr>";

		echo "<tr><td align='right'>";
		echo $lang["common"][1].":</td><td>";
		if ($canupdate){
			echo $item->getType()." - ".$item->getLink()."<br>";
			dropdownAllItems("item",0);
		}
		else echo $item->getType()." ".$item->getNameID();

		echo "</td></tr>";


		echo "<tr><td align='right'>";
		echo $lang["job"][5].":</td><td>&nbsp;</td></tr>";

		if ($canupdate||haveRight("assign_ticket","1")){
			echo "<tr><td align='right'>";
			echo $lang["job"][27].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"own_ticket");
			echo "</td></tr>";
		} else if (haveRight("steal_ticket","1")) {
			echo "<tr><td align='right'>";
			echo $lang["job"][27].":</td><td>";
			dropdownUsers("assign",$job->fields["assign"],"ID");
			echo "</td></tr>";
		}else {
			echo "<tr><td align='right'>";
			echo $lang["job"][27].":</td><td>";
			echo getUserName($job->fields["assign"]);
			echo "</td></tr>";
		}
		if ($canupdate||haveRight("assign_ticket","1")){
			echo "<tr><td align='right'>";
			echo $lang["job"][28].":</td><td>";
			dropdownValue("glpi_enterprises","assign_ent",$job->fields["assign_ent"]);
			echo "</td></tr>";
		} else {
			echo "<tr><td align='right'>";
			echo $lang["job"][28].":</td><td>";
			echo getDropdownName("glpi_enterprises",$job->fields["assign_ent"]);
			echo "</td></tr>";

		}
		echo "</table>";









		echo "</td>";

		// Troisi�e Colonne
		echo "<td valign='top' width='20%'>";

		if(haveRight("contract_infocom","r")){  // admin = oui on affiche les couts liés à l'interventions
			echo "<table border='0'>";
			if ($job->fields["realtime"]>0){
				echo "<tr><td align='right'>";
				echo $lang["job"][20].":</td><td>";
				echo "<strong>".getRealtime($job->fields["realtime"])."</strong>";
				echo "</td></tr>";
			}
			echo "<tr><td align='right'>";
			// cout
			echo $lang["job"][40].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_time' value=\"".$job->fields["cost_time"]."\"></td></tr>";

			echo "<tr><td align='right'>";

			echo $lang["job"][41].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_fixed' value=\"".$job->fields["cost_fixed"]."\">";

			echo "</td></tr>\n";

			echo "<tr><td align='right'>";

			echo $lang["job"][42].": ";
			echo "</td><td><input type='text' maxlength='100' size='15' name='cost_material' value=\"".$job->fields["cost_material"]."\">";

			echo "</td></tr>\n";

			echo "<tr><td align='right'>";

			echo $lang["job"][43].": ";
			echo "</td><td><strong>";
			echo trackingTotalCost($job->fields["realtime"],$job->fields["cost_time"],$job->fields["cost_fixed"],$job->fields["cost_material"]);
			echo "</strong></td></tr>\n</table>";
		}

		echo "</td></tr>";


		// Deuxi�e Ligne
		// Colonnes 1 et 2
		echo "<tr class='tab_bg_1'><td colspan='2'>";
		echo "<table width='99%' >";
		echo "<tr  class='tab_bg_2'><td width='15%'>".$lang["joblist"][6]."<br><br></td>";
		echo "<td  width='85%' align='left'>";

		if ($canupdate_descr){ // Admin =oui on autorise la modification de la description
			$rand=mt_rand();
			echo "<script type='text/javascript' >\n";
			echo "function showDesc$rand(){\n";
			echo "Element.hide('desc$rand');";
			echo "var a=new Ajax.Updater('viewdesc$rand','".$cfg_glpi["root_doc"]."/ajax/textarea.php' , {asynchronous:true, evalScripts:true, method: 'post',parameters: 'rows=6&cols=60&name=contents&data=".urlencode($job->fields["contents"])."'});";
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

		echo "<td valign='top'>";

		// Mailing ? Y or no ?

		if ($cfg_glpi["mailing"]==1){
			echo "<table><tr><td align='right'>";
			echo $lang["job"][19].":</td><td>";
			if ($canupdate){
				echo "<select name='emailupdates'>";
				echo "<option value='no'>".$lang["choice"][0]."</option>";
				echo "<option value='yes' ".($job->fields["emailupdates"]=="yes"?" selected ":"").">".$lang["choice"][1]."</option>";
				echo "</select>";
			} else {
				if ($job->fields["emailupdates"]=="yes") echo $lang["choice"][1];
				else $lang["choice"][0];
			}
			echo "</td></tr>";

			echo "<tr><td align='right'>";
			echo $lang["joblist"][27].":";
			echo "</td><td>";
			if ($canupdate){
				autocompletionTextField("uemail","glpi_tracking","uemail",$job->fields["uemail"],15);

				if (!empty($job->fields["uemail"]))
					echo "<a href='mailto:".$job->fields["uemail"]."'><img src='".$HTMLRel."pics/edit.png' alt='Mail'></a>";
			} else if (!empty($job->fields["uemail"]))
				echo "<a href='mailto:".$job->fields["uemail"]."'>".$job->fields["uemail"]."</a>";
			else echo "&nbsp;";
			echo "</td></tr></table>";


		}




		// File associated ?
		$query2 = "SELECT * FROM glpi_doc_device WHERE glpi_doc_device.FK_device = '".$job->fields["ID"]."' AND glpi_doc_device.device_type = '".TRACKING_TYPE."' ";
		$result2 = $db->query($query2);
		$numfiles=$db->numrows($result2);
		echo "<table width='100%'><tr><th colspan='2'>".$lang["tracking"][25]."</th></tr>";			

		if ($numfiles>0){
			$doc=new Document;
			while ($data=$db->fetch_array($result2)){
				$doc->getFromDB($data["FK_doc"]);

				echo "<tr><td>";
				echo getDocumentLink($doc->fields["filename"],"&tracking=$ID");
				if (haveRight("document","w"))
					echo "<a href='".$HTMLRel."front/document.form.php?deleteitem=delete&amp;ID=".$data["ID"]."'><img src='".$HTMLRel."pics/delete.png' alt='".$lang["buttons"][6]."'></a>";
				echo "</td></tr>";
			}
		}
		if ($canupdate||haveRight("comment_all_ticket","1")||haveRight("comment_ticket","1")){
			echo "<tr><td colspan='2'>";
			echo "<input type='file' name='filename' size='20'>";
			if ($canupdate&&haveRight("document","r")){
				echo "<br>";
				dropdown("glpi_docs","document");
			}
			echo "</td></tr>";
		}
		echo "</table>";

		echo "</td></tr>";
		// Troisi�e Ligne
		if ($canupdate||$canupdate_descr||haveRight("comment_all_ticket","1")||haveRight("comment_ticket","1")||haveRight("assign_ticket","1")||haveRight("steal_ticket","1")){
			echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
			echo "<input type='submit' class='submit' name='update' value='".$lang["buttons"][14]."'></td></tr>";
		}

		echo "</table>";
		echo "<input type='hidden' name='ID' value='$ID'>";
		echo "</form>";
		echo "</div>";

		echo "<script type='text/javascript' >\n";
		echo "function showPlan(){\n";
		echo "Element.hide('plan');";
		echo "var a=new Ajax.Updater('viewplan','".$cfg_glpi["root_doc"]."/ajax/planning.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'form=followups&author=".$job->fields["assign"]."'});";
		echo "};";
		echo "function showAddFollowup(){\n";
		echo "Element.hide('viewfollowup');";
		echo "var a=new Ajax.Updater('viewfollowup','".$cfg_glpi["root_doc"]."/ajax/addfollowup.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'tID=$ID'});";
		echo "};";
		echo "</script>";

		echo "<div id='viewfollowup'>\n";
		echo "</div>\n";	


		return true;
	}

	return false;
}

function showFollowupsSummary($tID){
	global $db,$lang,$cfg_glpi,$HTMLRel;


	if (!haveRight("observe_ticket","1")&&!haveRight("show_full_ticket","1")) return false;

	// Display existing Followups
	$showprivate=haveRight("show_full_ticket","1");

	$RESTRICT="";
	if (!$showprivate)  $RESTRICT=" AND ( private='0' OR author ='".$_SESSION["glpiID"]."' ) ";

	$query = "SELECT * FROM glpi_followups WHERE (tracking = $tID) $RESTRICT ORDER BY date DESC";
	$result=$db->query($query);



	$rand=mt_rand();


	echo "<div align='center'>";
	echo "<h3>".$lang["job"][37]."</h3>";

	if ($db->numrows($result)==0){
		echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>";
		echo "<strong>".$lang["job"][12]."</strong>";
		echo "</th></tr></table>";
	}
	else {	

		echo "<table class='tab_cadrehov_pointer'>";
		echo "<tr><th>&nbsp;</th><th>".$lang["common"][27]."</th><th>".$lang["joblist"][6]."</th><th>".$lang["job"][31]."</th><th>".$lang["job"][35]."</th><th>".$lang["common"][37]."</th>";
		if ($showprivate)
			echo "<th>".$lang["job"][30]."</th>";
		echo "</tr>";
		while ($data=$db->fetch_array($result)){

			echo "<tr class='tab_bg_2' onClick=\"viewEditFollowup".$data["ID"]."$rand();\" id='viewfollowup".$data["ID"]."$rand'>";
			echo "<td>".$data["ID"]."</td>";

			echo "<td>";

			echo "<script type='text/javascript' >\n";
			echo "function viewEditFollowup".$data["ID"]."$rand(){\n";
			//			echo "Element.hide('viewfollowup');";
			echo "var a=new Ajax.Updater('viewfollowup','".$cfg_glpi["root_doc"]."/ajax/viewfollowup.php' , {asynchronous:true, evalScripts:true, method: 'get',parameters: 'ID=".$data["ID"]."'});";
			echo "};";

			echo "</script>\n";


			echo convDateTime($data["date"])."</td>";
			echo "<td align='left'>".nl2br($data["contents"])."</td>";

			$hour=floor($data["realtime"]);
			$minute=round(($data["realtime"]-$hour)*60,0);
			echo "<td>";
			if ($hour) echo "$hour ".$lang["job"][21]."<br>";
			if ($minute||!$hour)
				echo "$minute ".$lang["job"][22]."</td>";

			echo "<td>";
			$query2="SELECT * from glpi_tracking_planning WHERE id_followup='".$data['ID']."'";
			$result2=$db->query($query2);
			if ($db->numrows($result2)==0)
				echo $lang["job"][32];	
			else {
				$data2=$db->fetch_array($result2);
				echo convDateTime($data2["begin"])."<br>".convDateTime($data2["end"])."<br>".getUserName($data2["id_assign"]);
			}
			echo "</td>";

			echo "<td>".getUserName($data["author"])."</td>";
			if ($showprivate){
				echo "<td>";
				if ($data["private"])
					echo $lang["choice"][1];
				else echo $lang["choice"][0];
				echo "</td>";
			}

			echo "</tr>";
		}
		echo "</table>";
	}	
	echo "</div>";
}

// Formulaire d'ajout de followup
function showAddFollowupForm($tID){
	global $db,$lang,$cfg_glpi,$HTMLRel;

	$job=new Job;
	$job->getFromDB($tID);

	if (!haveRight("comment_ticket","1")&&!haveRight("comment_all_ticket","1")&&$job->fields["assign"]!=$_SESSION["glpiID"]) return false;


	$commentall=(haveRight("comment_all_ticket","1")||$job->fields["assign"]==$_SESSION["glpiID"]);

	if ($_SESSION["glpiprofile"]["interface"]=="central"){
		$target=$cfg_glpi["root_doc"]."/front/tracking.form.php";
	} else {
		$target=$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user";
	}
	// Display Add Table
	echo "<div align='center'>";
	echo "<form name='followups' method='post' action=\"$target\">\n";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'>";
	echo $lang["job"][29];
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
	echo "<tr><td>".$lang["joblist"][6]."</td>";
	echo "<td><textarea name='contents' rows=8 cols=$cols></textarea>";
	echo "</td></tr>";
	echo "</table>";
	echo "</td>";

	echo "<td width='$width_right' valign='top'>";
	echo "<table width='100%'>";

	if ($commentall){
		echo "<tr>";
		echo "<td>".$lang["job"][30].":</td>";
		echo "<td>";
		echo "<select name='private'>";
		echo "<option value='0'>".$lang["choice"][0]."</option>";
		echo "<option value='1'>".$lang["choice"][1]."</option>";
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

		if (haveRight("show_planning","1")){
			echo "<tr>";
			echo "<td>".$lang["job"][35]."</td>";

			echo "<td>";
			echo "<div id='plan'  onClick='showPlan()'>\n";
			echo "<span class='showplan'>".$lang["job"][34]."</span>";
			echo "</div>\n";	

			echo "<div id='viewplan'>\n";
			echo "</div>\n";	


			echo "</td>";

			echo "</tr>";
		}
	}
	echo "<tr class='tab_bg_2'>";
	echo "<td align='center'>";
	echo "<input type='submit' name='add' value='".$lang["buttons"][8]."' class='submit'>";
	echo "</td>";
	if ($commentall){
		echo "<td align='center'>";
		echo "<input type='submit' name='add_close' value='".$lang["buttons"][26]."' class='submit'>";
		echo "</td>";
	}
	echo "</tr>";


	echo "</table>";
	echo "</td></tr>";
	echo "</table>";
	echo "<input type='hidden' name='tracking' value='$tID'>";
	echo "</form></div>";

}


// Formulaire d'ajout de followup
function showUpdateFollowupForm($ID){
	global $db,$lang,$cfg_glpi,$HTMLRel;

	if (!haveRight("comment_ticket","1")&&!haveRight("comment_all_ticket","1")) return false;

	$commentall=haveRight("comment_all_ticket","1");

	// Display existing Followups

	$query = "SELECT * FROM glpi_followups WHERE (ID = '$ID')";
	$result=$db->query($query);


	if ($db->numrows($result)==1){
		echo "<div align='center'>";
		$data=$db->fetch_array($result);
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>";
		echo $lang["job"][39];
		echo "</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/tracking.form.php\">\n";

		echo "<table width='100%'>";
		echo "<tr class='tab_bg_2'><td width='50%'>";
		echo "<table width='100%' bgcolor='#FFFFFF'>";
		echo "<tr class='tab_bg_1'><td align='center' width='10%'>".$lang["joblist"][6]."<br><br>".$lang["common"][27].":<br>".convDateTime($data["date"])."</td>";
		echo "<td width='90%'>";

		if ($commentall){
			echo "<textarea name='contents' cols='50' rows='6'>".$data["contents"]."</textarea>";
		} else echo nl2br($data["contents"]);


		echo "</td></tr>";
		echo "</table>";
		echo "</td>";

		echo "<td width='50%' valign='top'>";
		echo "<table width='100%'>";


		if ($commentall){
			echo "<tr>";
			echo "<td>".$lang["job"][30].":</td>";
			echo "<td>";
			echo "<select name='private'>";
			echo "<option value='0' ".(!$data["private"]?" selected":"").">".$lang["choice"][0]."</option>";
			echo "<option value='1' ".($data["private"]?" selected":"").">".$lang["choice"][1]."</option>";
			echo "</select>";
			echo "</td>";
			echo "</tr>";
		} 



		echo "<tr><td>".$lang["job"][31].":</td><td>";
		$hour=floor($data["realtime"]);
		$minute=round(($data["realtime"]-$hour)*60,0);

		if ($commentall){

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
			if ($commentall)
				echo "<a href='".$HTMLRel."front/planning.form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=-1'>".$lang["buttons"][8]."</a>";
			else echo $lang["job"][32];	
		else {
			$data2=$db->fetch_array($result2);
			echo convDateTime($data2["begin"])."<br>".convDateTime($data2["end"])."<br>".getUserName($data2["id_assign"]);
			if ($commentall)
				echo "<a href='".$HTMLRel."front/planning.form.php?edit=edit&amp;fup=".$data["ID"]."&amp;ID=".$data2["ID"]."'><img src='".$HTMLRel."pics/edit.png'></a>";

		}

		echo "</td>";
		echo "</tr>";

		if ($commentall){
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
		if ($commentall){
			echo "<input type='hidden' name='ID' value='".$data["ID"]."'>";
			echo "<input type='hidden' name='tracking' value='".$data["tracking"]."'>";
			echo "</form>";
		}
		echo "</td></tr>";
		echo "</table>";
		echo "</div>";
	}
}

// fonction calcul de cout total d'un ticket
function trackingTotalCost($realtime,$cost_time,$cost_fixed,$cost_material){
	$totalcost=($realtime*$cost_time)+$cost_fixed+$cost_material;
	return $totalcost;
}

?>
