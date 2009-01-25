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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


// FUNCTIONS Planning


/**
 * Show the planning
 *
 *  
 * @param $type planning type : can be day, week, month
 * @param $date working date
 * @param $usertype type of planning to view : can be user or group
 * @param $uID ID of the user
 * @param $gID ID of the group
 * @return Display form
 *
 **/
function showFormPlanning($type,$date,$usertype,$uID,$gID){
	global $LANG, $CFG_GLPI;

	switch ($type){
		case "month":
			$split=explode("-",$date);
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
			$next=$year_next."-".sprintf("%02u",$month_next)."-".$split[2];
			$prev=$year_prev."-".sprintf("%02u",$month_prev)."-".$split[2];
		
		break;
		default :
			$time=strtotime($date);
		
			$step=0;
			switch ($type){
				case "week":
					$step=WEEK_TIMESTAMP;
				break;
				case "day":
					$step=DAY_TIMESTAMP;
				break;
			}
		
			$next=$time+$step+10;
			$prev=$time-$step;
		
			$next=strftime("%Y-%m-%d",$next);
			$prev=strftime("%Y-%m-%d",$prev);
		break;
	}

	echo "<div align='center'><form method=\"get\" name=\"form\" action=\"planning.php\">";
	echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
	echo "<td>";
	echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$type."&amp;uID=".$uID."&amp;date=$prev\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a>";
	echo "</td>";
	echo "<td>";
	if (haveRight("show_all_planning","1")){
		echo "<input type='radio' id='radio_user' name='usertype' value='user' ".($usertype=="user"?"checked":"").">";
		$rand_user=dropdownUsers("uID",$uID,"interface",1,1,$_SESSION["glpiactive_entity"]);
		echo "<hr>";
		echo "<input type='radio' id='radio_group' name='usertype' value='group' ".($usertype=="group"?"checked":"").">";
		$rand_group=dropdownValue("glpi_groups","gID",$gID,1,$_SESSION["glpiactive_entity"]);
		echo "<hr>";
		echo "<input type='radio' id='radio_user_group' name='usertype' value='user_group' ".($usertype=="user_group"?"checked":"").">";
		echo $LANG["joblist"][3];
	
	
		echo "<script type='text/javascript' >\n";
		echo "Ext.onReady(function() {";
		echo "	Ext.get('dropdown_uID".$rand_user."').on('change',function() {window.document.getElementById('radio_user').checked=true;});";
		echo "	Ext.get('dropdown_gID".$rand_group."').on('change',function() {window.document.getElementById('radio_group').checked=true;});";
		echo "});";
		echo "</script>\n";
	} else if (haveRight("show_group_planning","1")){
		echo "<select name='usertype'>";
		echo "<option value='user' ".($usertype=='user'?'selected':'').">".$LANG["joblist"][1]."</option>";
		echo "<option value='user_group' ".($usertype=='user_group'?'selected':'').">".$LANG["joblist"][3]."</option>";
		echo "</select>";
	}
	echo "</td>";
echo "<td>";
showDateFormItem("date",$date,false);

echo "</td>";
echo "<td><select name='type'>";
echo "<option value='day' ".($type=="day"?" selected ":"").">".$LANG["planning"][5]."</option>";
echo "<option value='week' ".($type=="week"?" selected ":"").">".$LANG["planning"][6]."</option>";
echo "<option value='month' ".($type=="month"?" selected ":"").">".$LANG["planning"][14]."</option>";
echo "</select></td>";
echo "<td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $LANG["buttons"][7] ."\" /></td>";
echo "<td>";

echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/planning.ical.php?uID=".$uID."\" title='".$LANG["planning"][12]."'><span style='font-size:10px'>-".$LANG["planning"][10]."</span></a>";
echo "<br>";
// Todo recup l'url complete de glpi proprement, ? nouveau champs table config ?
echo "<a href=\"webcal://".$_SERVER['HTTP_HOST'].$CFG_GLPI["root_doc"]."/front/planning.ical.php?uID=".$uID."\" title='".$LANG["planning"][13]."'><span style='font-size:10px'>-".$LANG["planning"][11]."</span></a>";

echo "</td>";	
echo "<td>";
echo "<a href=\"".$_SERVER['PHP_SELF']."?type=".$type."&amp;uID=".$uID."&amp;date=$next\"><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a>";
echo "</td>";

echo "</tr>";
echo "</table></form></div>";

}

/**
 * Show the planning
 *
 *  
 * @param $who ID of the user (0 = undefined)
 * @param $who_group ID of the group of users (0 = undefined)
 * @param $when Date of the planning to display
 * @param $type type of planning to display (day, week, month) 
 * @return Nothing (display function)
 *
 **/
function showPlanning($who,$who_group,$when,$type){
	global $LANG,$CFG_GLPI,$DB;

	if (!haveRight("show_planning","1")&&!haveRight("show_all_planning","1")) return false;

	// Define some constants

	$date=explode("-",$when);
	$time=mktime(0,0,0,$date[1],$date[2],$date[0]);

	// Check bisextile years
	list($current_year,$current_month,$current_day)=explode("-",$when);
	if (($current_year%4)==0) $feb=29; else $feb=28;
	$nb_days= array(31,$feb,31,30,31,30,31,31,30,31,30,31);
	// Begin of the month
	$begin_month_day=strftime("%w",mktime(0,0,0,$current_month,1,$current_year));
	if ($begin_month_day==0) $begin_month_day=7;
	$end_month_day=strftime("%w",mktime(0,0,0,$current_month,$nb_days[$current_month-1],$current_year));
	// Day of the week
	$dayofweek=date("w",$time);
	// Cas du dimanche
	if ($dayofweek==0) $dayofweek=7;




	// Print Headers
	echo "<div class='center'><table class='tab_cadre_fixe'>";
	// Print Headers
	echo "<tr>";
	switch ($type){
		case "month":
		case "week":
			for ($i=1;$i<=7;$i++){
				echo "<th width='12%'>".$LANG["calendarDay"][$i%7]."</th>";
			}
			break;
		case "day":
			echo "<th width='12%'>".$LANG["calendarDay"][$dayofweek%7]."</th>";
			break;
	}
	echo "</tr>";

	// Get begin and duration
	$begin=0;
	$end=0;
	switch ($type){
		case "month":
			$begin=strtotime($current_year."-".$current_month."-01 00:00:00");
			$end=$begin+DAY_TIMESTAMP*$nb_days[$current_month-1];
			break;
		case "week":
			$begin=$time+mktime(0,0,0,0,1,0)-mktime(0,0,0,0,$dayofweek,0);
			$end=$begin+WEEK_TIMESTAMP;
			break;
		case "day":
			$add="";
			$begin=$time;
			$end=$begin+DAY_TIMESTAMP;
			break;
	}
	$begin=date("Y-m-d H:i:s",$begin);
	$end=date("Y-m-d H:i:s",$end);

	// Get items to print
	$ASSIGN="";

	if ($who_group=="mine"){
		if (count($_SESSION["glpigroups"])){
			$groups=implode("','",$_SESSION['glpigroups']);
			$ASSIGN="id_assign IN (SELECT DISTINCT FK_users FROM glpi_users_groups WHERE FK_groups IN ('$groups')) AND";
		} else { // Only personal ones
			$ASSIGN="id_assign='$who' AND ";
		}
	} else {

		if ($who>0){
			$ASSIGN="id_assign='$who' AND ";
		}

		if ($who_group>0){
			$ASSIGN="id_assign IN (SELECT FK_users FROM glpi_users_groups WHERE FK_groups = '$who_group') AND";
		}
	}
	if (empty($ASSIGN)){
		$ASSIGN=" id_assign IN (SELECT DISTINCT glpi_users_profiles.FK_users 
					FROM glpi_profiles 
					LEFT JOIN glpi_users_profiles ON (glpi_profiles.ID = glpi_users_profiles.FK_profiles)
					WHERE glpi_profiles.interface='central' ";

		$ASSIGN.=getEntitiesRestrictRequest("AND","glpi_users_profiles", '',$_SESSION["glpiactive_entity"],1);	
		$ASSIGN.=") AND ";


	}
	// ---------------Tracking
	$query="SELECT * 
		FROM glpi_tracking_planning 
		WHERE $ASSIGN 
			(('$begin' <= begin AND '$end' >= begin) 
				OR ('$begin' < end AND '$end' >= end) 
				OR (begin <= '$begin' AND end > '$begin') 
				OR (begin <= '$end' AND end > '$end')) 
		ORDER BY begin";
	
	$result=$DB->query($query);

	$fup=new Followup();
	$job=new Job();

	$interv=array();
	$i=0;
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_array($result)){
			$fup->getFromDB($data["id_followup"]);
			$job->getFromDBwithData($fup->fields["tracking"],0);
			if (haveAccessToEntity($job->fields["FK_entities"])){
				$interv[$data["begin"]."$$$".$i]["id_followup"]=$data["id_followup"];
				$interv[$data["begin"]."$$$".$i]["state"]=$data["state"];
				$interv[$data["begin"]."$$$".$i]["id_tracking"]=$fup->fields["tracking"];
				$interv[$data["begin"]."$$$".$i]["id_assign"]=$data["id_assign"];
				$interv[$data["begin"]."$$$".$i]["ID"]=$data["ID"];
				if (strcmp($begin,$data["begin"])>0){
					$interv[$data["begin"]."$$$".$i]["begin"]=$begin;
				} else {
					$interv[$data["begin"]."$$$".$i]["begin"]=$data["begin"];
				}
				if (strcmp($end,$data["end"])<0){
					$interv[$data["begin"]."$$$".$i]["end"]=$end;
				} else {
					$interv[$data["begin"]."$$$".$i]["end"]=$data["end"];
				}
				$interv[$data["begin"]."$$$".$i]["name"]=$job->fields["name"];
				$interv[$data["begin"]."$$$".$i]["content"]=resume_text($job->fields["contents"],$CFG_GLPI["cut"]);
				$interv[$data["begin"]."$$$".$i]["device"]=$job->hardwaredatas->getName();
				$interv[$data["begin"]."$$$".$i]["status"]=$job->fields["status"];
				$interv[$data["begin"]."$$$".$i]["priority"]=$job->fields["priority"];
				$i++;
			}
		}
	// ---------------reminder 
	$readpub=$readpriv="";

	// See public reminder ?
	if (haveRight("reminder_public","r")) {
		$readpub="(private=0 AND".getEntitiesRestrictRequest("","glpi_reminder",'','',true).")";
	}
	
	// See my private reminder ?
	if ($who_group=="mine" || $who==$_SESSION["glpiID"]){
		$readpriv="(private=1 AND FK_users='".$_SESSION["glpiID"]."')";		
	}
	
	if ($readpub && $readpriv) {
		$ASSIGN	= "($readpub OR $readpriv)";
	} else if ($readpub) {
		$ASSIGN	= $readpub;	
	} else {
		$ASSIGN	= $readpriv;	
	}		
	if ($ASSIGN) {
		$query2="SELECT * 
			FROM glpi_reminder 
			WHERE rv=1 AND $ASSIGN  AND begin < '$end' AND end > '$begin' 
			ORDER BY begin";
		$result2=$DB->query($query2);
	
		if ($DB->numrows($result2)>0) {	
			while ($data=$DB->fetch_array($result2)){
	
				$interv[$data["begin"]."$$".$i]["id_reminder"]=$data["ID"];
				if (strcmp($begin,$data["begin"])>0)
					$interv[$data["begin"]."$$".$i]["begin"]=$begin;
				else $interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
				if (strcmp($end,$data["end"])<0)
					$interv[$data["begin"]."$$".$i]["end"]=$end;
				else $interv[$data["begin"]."$$".$i]["end"]=$data["end"];
	
				$interv[$data["begin"]."$$".$i]["name"]=resume_text($data["name"],$CFG_GLPI["cut"]);
				$interv[$data["begin"]."$$".$i]["text"]=resume_text($data["text"],$CFG_GLPI["cut"]);
				$interv[$data["begin"]."$$".$i]["FK_users"]=$data["FK_users"];
				$interv[$data["begin"]."$$".$i]["private"]=$data["private"];
				$interv[$data["begin"]."$$".$i]["state"]=$data["state"];
	
				$i++;
			} //
		}
	}

	// --------------- Plugins
	$data=doHookFunction("planning_populate",array("begin"=>$begin,"end"=>$end,"who"=>$who,"who_group"=>$who_group));


	if (isset($data["items"])&&count($data["items"])){
		$interv=array_merge($data["items"],$interv);
	}

	// Display Items
	$tmp=explode(":",$CFG_GLPI["planning_begin"]);
	$hour_begin=$tmp[0];
	$tmp=explode(":",$CFG_GLPI["planning_end"]);
	$hour_end=$tmp[0];

	switch ($type){
		case "week":
			for ($hour=$hour_begin;$hour<=$hour_end;$hour++){
				echo "<tr>";
				for ($i=1;$i<=7;$i++){
					echo "<td class='tab_bg_3' width='12%' valign='top' >";
					echo "<strong>".displayUsingTwoDigits($hour).":00</strong><br>";
					
					// From midnight
					if ($hour==$hour_begin){
						$begin_time=date("Y-m-d H:i:s",strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP);
					} else {
						$begin_time=date("Y-m-d H:i:s",strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+$hour*HOUR_TIMESTAMP);
					}
					// To midnight
					if($hour==$hour_end){
						$end_time=date("Y-m-d H:i:s",strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+24*HOUR_TIMESTAMP);
					} else {
						$end_time=date("Y-m-d H:i:s",strtotime($when)+($i-$dayofweek)*DAY_TIMESTAMP+($hour+1)*HOUR_TIMESTAMP);
					}
					
					reset($interv);
					while ($data=current($interv)){
						$type="";

						if ($data["begin"]>=$begin_time&&$data["end"]<=$end_time){
							$type="in";
						} else if ($data["begin"]<$begin_time&&$data["end"]>$end_time){
							$type="through";
						} else if ($data["begin"]>=$begin_time&&$data["begin"]<$end_time){
							$type="begin";
						} else if ($data["end"]>$begin_time&&$data["end"]<=$end_time){
							$type="end";
						} 
						
						if (empty($type)){
							next($interv);
						} else {
							displayPlanningItem($data,$who,$type);
							if ($type=="in"){
								unset($interv[key($interv)]);
							} else {
								next($interv);
							}
						}
					}
					echo "</td>";
				}
	
				echo "</tr>\n";
	
			}

			break;
		case "day":
			for ($hour=$hour_begin;$hour<=$hour_end;$hour++){
				echo "<tr>";
				$begin_time=date("Y-m-d H:i:s",strtotime($when)+($hour)*HOUR_TIMESTAMP);
				$end_time=date("Y-m-d H:i:s",strtotime($when)+($hour+1)*HOUR_TIMESTAMP);
				echo "<td class='tab_bg_3' width='12%' valign='top' >";
				echo "<strong>".displayUsingTwoDigits($hour).":00</strong><br>";
				reset($interv);
				while ($data=current($interv)){
					$type="";
					if ($data["begin"]>=$begin_time&&$data["end"]<=$end_time){
						$type="in";
					} else if ($data["begin"]<$begin_time&&$data["end"]>$end_time){
						$type="through";
					} else if ($data["begin"]>=$begin_time&&$data["begin"]<$end_time){
						$type="begin";
					} else if ($data["end"]>$begin_time&&$data["end"]<=$end_time){
						$type="end";
					} 
						
					if (empty($type)){
						next($interv);
					} else {
						displayPlanningItem($data,$who,$type,1);
						if ($type=="in"){
							unset($interv[key($interv)]);
						} else {
							next($interv);
						}
					}
				}
				echo "</td>";
				echo "</tr>";
			}
			break;
		case "month":
			echo "<tr class='tab_bg_3'>";
			// Display first day out of the month
			for ($i=1;$i<$begin_month_day;$i++){
				echo "<td style='background-color:#ffffff'>&nbsp;</td>";
			}
			// Print real days
			if ($current_month<10&&strlen($current_month)==1) $current_month="0".$current_month;
			
			$begin_time=strtotime($begin);
			$end_time=strtotime($end);
		
			for ($time=$begin_time;$time<$end_time;$time+=DAY_TIMESTAMP){

				// Add 6 hours for midnight problem
				$day=date("d",$time+6*HOUR_TIMESTAMP);

				echo "<td  valign='top' height='100'  class='tab_bg_3'>";
				echo "<table align='center' ><tr><td align='center' ><span style='font-family: arial,helvetica,sans-serif; font-size: 14px; color: black'>".$day."</span></td></tr>";

				echo "<tr class='tab_bg_3'>";
				echo "<td class='tab_bg_3' width='12%' valign='top' >";
				$begin_day=date("Y-m-d H:i:s",$time);
				$end_day=date("Y-m-d H:i:s",$time+DAY_TIMESTAMP);
				reset($interv);
				while ($data=current($interv)){
					$type="";

					if ($data["begin"]>=$begin_day&&$data["end"]<=$end_day){
						$type="in";
					} else if ($data["begin"]<$begin_day&&$data["end"]>$end_day){
						$type="through";
					} else if ($data["begin"]>=$begin_day&&$data["begin"]<$end_day){
						$type="begin";
					} else if ($data["end"]>$begin_day&&$data["end"]<=$end_day){
						$type="end";
					} 

					if (empty($type)){
						next($interv);
					} else {
						displayPlanningItem($data,$who,$type);
						if ($type=="in"){
							unset($interv[key($interv)]);
						} else {
							next($interv);
						}
					}
				}

				echo "</td>";
	
				echo "</tr>";
				echo "</table>";
				echo "</td>";
				
				// Add break line
				if (($day+$begin_month_day)%7==1)	{
					echo "</tr>";
					if ($day!=$nb_days[$current_month-1]){
						echo "<tr>";
					}
				}

			}
			if ($end_month_day!=0){
				for ($i=0;$i<7-$end_month_day;$i++) 	{
					echo "<td style='background-color:#ffffff'>&nbsp;</td>";
				}
			}
			echo "</tr>";


			break;

	}
	
	echo "</table></div>";

}

/**
 * Display a Planning Item
 *
 *  
 * @param $val Array of the item to display
 * @param $who ID of the user (0 if all)
 * @param $type position of the item in the time block (in, through, begin or end)
 * @param $complete complete display (more details)
 * @return Nothing (display function)
 *
 **/
function displayPlanningItem($val,$who,$type="",$complete=0){
	global $CFG_GLPI,$LANG,$PLUGIN_HOOKS;

	$author="";  // show author reminder
	$img="rdv_private.png"; // default icon for reminder
	$color="#e4e4e4";
	$styleText="";
	if (isset($val["state"])){
		switch ($val["state"]){
			case 0:
				$color="#efefe7"; // Information
				break;
			case 1:
				$color="#fbfbfb"; // To be done
				break;
			case 2:
				$color="#e7e7e2"; // Done
				$styleText="color:#747474;";
				
				break;
			
		}
	}
	
	echo "<div style=' margin:auto; text-align:left; border:1px dashed #cccccc; background-color: $color; font-size:9px; width:98%; '>";
	$rand=mt_rand(); 

	// Plugins case
	if (isset($val["plugin"])&&isset($PLUGIN_HOOKS['display_planning'][$val["plugin"]])){
			$function=$PLUGIN_HOOKS['display_planning'][$val["plugin"]];
		if (function_exists($function)) {
			$val["type"]=$type;
			$function($val);
		}
	} else if(isset($val["id_tracking"])){  // show tracking

		echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv_interv.png' alt='' title='".$LANG["planning"][8]."'>&nbsp;";
		echo "&nbsp;<img src=\"".$CFG_GLPI["root_doc"]."/pics/".$val["status"].".png\" alt='".getStatusName($val["status"])."' title='".getStatusName($val["status"])."'>&nbsp;";

		echo "<a href='".$CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=".$val["id_tracking"]."' style='$styleText'";
		if (!$complete){
			echo "onmouseout=\"cleanhide('content_tracking_".$val["ID"].$rand."')\" onmouseover=\"cleandisplay('content_tracking_".$val["ID"].$rand."')\"";
		}
		echo ">";
		switch ($type){
			case "in":
				echo date("H:i",strtotime($val["begin"]))."/".date("H:i",strtotime($val["end"])).": ";
				break;
			case "through":
				break;
			case "begin";
				echo $LANG["buttons"][33]." ".date("H:i",strtotime($val["begin"])).": ";
				break;
			case "end";
				echo $LANG["buttons"][32]." ".date("H:i",strtotime($val["end"])).": ";
				break;

		}
		echo "<br>- #".$val["id_tracking"]." ";
		echo  resume_text($val["name"],80). " ";
		if (!empty($val["device"])){
			echo "<br>- ".$val["device"];
		}
		
		if ($who<=0){ // show tech for "show all and show group"
			echo "<br>- ";
			echo $LANG["planning"][9]." ".getUserName($val["id_assign"]);
		} 
		echo "</a>";
		if ($complete){
			echo "<br>";
			echo "<strong>".getPlanningState($val["state"])."</strong><br>";
			echo "<strong>".$LANG["joblist"][2].":</strong> ".getPriorityName($val["priority"])."<br>";
			echo "<strong>".$LANG["joblist"][6].":</strong><br>".$val["content"];
		} else {
			echo "<div class='over_link' id='content_tracking_".$val["ID"].$rand."'>";
			echo "<strong>".getPlanningState($val["state"])."</strong><br>";
			echo "<strong>".$LANG["joblist"][2].":</strong> ".getPriorityName($val["priority"])."<br>";
			echo "<strong>".$LANG["joblist"][6].":</strong><br>".$val["content"]."</div>";
		}

	}else{  // show Reminder
		if (!$val["private"]){
			$author="<br>".$LANG["planning"][9]." : ".getUserName($val["FK_users"]);
			$img="rdv_public.png";
		} 
		echo "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title='".$LANG["title"][37]."'>&nbsp;";
		echo "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?ID=".$val["id_reminder"]."'";
			if (!$complete){
			echo "onmouseout=\"cleanhide('content_reminder_".$val["id_reminder"].$rand."')\" onmouseover=\"cleandisplay('content_reminder_".$val["id_reminder"].$rand."')\"";
		}
		echo ">";

		switch ($type){
			case "in":
				echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ";
				break;
			case "through":
				break;
			case "begin";
				echo $LANG["buttons"][33]." ".date("H:i",strtotime($val["begin"])).": ";
				break;
			case "end";
				echo $LANG["buttons"][32]." ".date("H:i",strtotime($val["end"])).": ";
				break;

		}
		echo $val["name"];
		echo $author;
		echo "</a>";
		if ($complete){
			echo "<br><strong>".getPlanningState($val["state"])."</strong><br>";
			echo $val["text"];
		} else {
			echo "<div class='over_link' id='content_reminder_".$val["id_reminder"].$rand."'><strong>".getPlanningState($val["state"])."</strong><br>".$val["text"]."</div>";
		}

		echo "";
	}
	echo "</div><br>";

}


/**
 * Display an integer using 2 digits
 *
 *  
 * @param $time value to display
 * @return string return the 2 digits item
 *
 **/
function displayUsingTwoDigits($time){

	$time=round($time);
	if ($time<10&&strlen($time)) return "0".$time;
	else return $time;
}

/**
 * Show the planning for the central page of a user
 *
 *  
 * @param $who ID of the user
 * @return Nothing (display function)
 *
 **/
function showPlanningCentral($who){

	global $DB,$CFG_GLPI,$LANG;

	if (!haveRight("show_planning","1")) return false;

	$when=strftime("%Y-%m-%d");
	$debut=$when;

	// followup
	$ASSIGN="";
	if ($who!=0){
		$ASSIGN="id_assign='$who' AND";
	} else {
		return false;
	} 


	$INTERVAL=" 1 DAY "; // we want to show planning of the day

	$query="SELECT * 
		FROM glpi_tracking_planning 
		WHERE $ASSIGN 
			(('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) 
				OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) 
				OR (begin <= '".$debut."' AND end > '".$debut."') 
				OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) 
					AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) 
		ORDER BY begin";

	$result=$DB->query($query);

	$fup=new Followup();
	$job=new Job();

	$interv=array();
	$i=0;

	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_array($result)){
			if ($fup->getFromDB($data["id_followup"])){
				if ($job->getFromDBwithData($fup->fields["tracking"],0)){
					if (haveAccessToEntity($job->fields["FK_entities"])){
						$interv[$data["begin"]."$$".$i]["id_followup"]=$data["id_followup"];
						$interv[$data["begin"]."$$".$i]["id_tracking"]=$fup->fields["tracking"];
						$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
						$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
						$interv[$data["begin"]."$$".$i]["state"]=$data["state"];
						$interv[$data["begin"]."$$".$i]["content"]=resume_text($job->fields["contents"],$CFG_GLPI["cut"]);
						$interv[$data["begin"]."$$".$i]["device"]=$job->hardwaredatas->getName();
						$interv[$data["begin"]."$$".$i]["status"]=$job->fields['status'];
						$interv[$data["begin"]."$$".$i]["id_assign"]=$data["id_assign"];
						$interv[$data["begin"]."$$".$i]["ID"]=$job->fields['ID'];
						$interv[$data["begin"]."$$".$i]["name"]=$job->fields['name'];
						$interv[$data["begin"]."$$".$i]["priority"]=$job->fields['priority'];
						$i++;
					}
				}
			}
		}


	// reminder 
	$read_public="";
	if (haveRight("reminder_public","r")) $read_public=" OR ( private=0 ".getEntitiesRestrictRequest("AND","glpi_reminder").") ";

	$query2="SELECT * 
		FROM glpi_reminder 
		WHERE rv='1' 
			AND (FK_users='$who' $read_public)    
			AND (('".$debut."' <= begin AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= begin) 
				OR ('".$debut."' < end AND adddate( '". $debut ."' , INTERVAL $INTERVAL ) >= end) 
				OR (begin <= '".$debut."' AND end > '".$debut."') 
				OR (begin <= adddate( '". $debut ."' , INTERVAL $INTERVAL ) 
					AND end > adddate( '". $debut ."' , INTERVAL $INTERVAL ))) 
		ORDER BY begin";

	$result2=$DB->query($query2);


	$remind=new Reminder();

	$i=0;

	if ($DB->numrows($result2)>0)
		while ($data=$DB->fetch_array($result2)){
			if ($remind->getFromDB($data["ID"])){
				$interv[$data["begin"]."$$".$i]["id_reminder"]=$data["ID"];
				$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
				$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
				$interv[$data["begin"]."$$".$i]["private"]=$data["private"];
				$interv[$data["begin"]."$$".$i]["state"]=$data["state"];
				$interv[$data["begin"]."$$".$i]["FK_users"]=$data["FK_users"];
				$interv[$data["begin"]."$$".$i]["name"]=resume_text($remind->fields["name"],$CFG_GLPI["cut"]);
				$interv[$data["begin"]."$$".$i]["text"]=resume_text($remind->fields["text"],$CFG_GLPI["cut"]);
				$i++;
			}
		}

	// Get begin and duration
	$date=explode("-",$when);
	$time=mktime(0,0,0,$date[1],$date[2],$date[0]);
	$begin=$time;
	$end=$begin+DAY_TIMESTAMP;
	$begin=date("Y-m-d H:i:s",$begin);
	$end=date("Y-m-d H:i:s",$end);


	$data=doHookFunction("planning_populate",array("begin"=>$begin,"end"=>$end,"who"=>$who,"who_group"=>-1));

	if (isset($data["items"])&&count($data["items"])){
		$interv=array_merge($data["items"],$interv);
	}

	ksort($interv);

	echo "<table class='tab_cadrehov'><tr><th><a href='".$CFG_GLPI["root_doc"]."/front/planning.php'>".$LANG["planning"][15]."</a></th></tr>";
	$type='';
	if (count($interv)>0){
		foreach ($interv as $key => $val){

			echo "<tr class='tab_bg_1'>";
			echo "<td>";		

			if ($val["begin"]<$begin){
				$val["begin"]=$begin;
			}
			if ($val["end"]>$end){
				$val["end"]=$end;
			}

			displayPlanningItem($val,$who,'in');

			echo "</td></tr>";

		}

	}
	echo "</table>";

}
















//*******************************************************************************************************************************
// *********************************** Implementation ICAL ***************************************************************
//*******************************************************************************************************************************




/**
 *  Generate ical file content
 *  
 * @param $who
 * @return icalendar string
 **/      
function generateIcal($who){
	global  $DB,$CFG_GLPI, $LANG;

	include_once (GLPI_ROOT . "/lib/icalcreator/iCalcreator.class.php");
	$v = new vcalendar(); 

	if ( ! empty ( $CFG_GLPI["version"]) ) {
		$v->setConfig( 'unique_id', "GLPI-Planning-".trim($CFG_GLPI["version"]) ); 
	} else {
		$v->setConfig( 'unique_id', "GLPI-Planning-UnknownVersion" ); 
	}
	$v->setProperty( "method", "PUBLISH" );
	$v->setProperty( "version", "2.0" );
	$v->setProperty( "x-wr-calname", "GLPI - ".getUserName($who) );
	$v->setProperty( "calscale", "GREGORIAN" ); 
	$interv=array();


	$begin=time()-MONTH_TIMESTAMP*12;
	$end=time()+MONTH_TIMESTAMP*12;
	$begin=date("Y-m-d H:i:s",$begin); 
	$end=date("Y-m-d H:i:s",$end); 

	// export job
	$query="SELECT * 
		FROM glpi_tracking_planning 
		WHERE id_assign='$who' 
			AND end > '$begin' 
			AND begin < '$end' ";

	$result=$DB->query($query);

	$job=new Job();
	$fup=new Followup();
	$i=0;
	if ($DB->numrows($result)>0)
		while ($data=$DB->fetch_array($result)){

			if ($fup->getFromDB($data["id_followup"])){
				if ($job->getFromDBwithData($fup->fields["tracking"],0)){
					$interv[$data["begin"]."$$".$i]["content"]=substr($job->fields['contents'],0,$CFG_GLPI["cut"]);
					$interv[$data["begin"]."$$".$i]["device"]=$job->hardwaredatas->getName();
				}
			}

			$interv[$data["begin"]."$$".$i]["id_tracking"]=$data['id_followup'];
			$interv[$data["begin"]."$$".$i]["id_assign"]=$data['id_assign'];
			$interv[$data["begin"]."$$".$i]["ID"]=$data['ID'];
			$interv[$data["begin"]."$$".$i]["begin"]=$data['begin'];
			$interv[$data["begin"]."$$".$i]["end"]=$data['end'];
			$interv[$data["begin"]."$$".$i]["content"]="";
			$interv[$data["begin"]."$$".$i]["device"]="";
			//$interv[$i]["content"]=substr($job->contents,0,$CFG_GLPI["cut"]);
			if ($fup->getFromDB($data["id_followup"])){
				if ($job->getFromDBwithData($fup->fields["tracking"],0)){
					$interv[$data["begin"]."$$".$i]["content"]=substr($job->fields['contents'],0,$CFG_GLPI["cut"]);
					$interv[$data["begin"]."$$".$i]["device"]=$job->hardwaredatas->getName();
				}
			}
			$i++;
		}


	// reminder 

	$query2="SELECT * 
		FROM glpi_reminder 
		WHERE rv='1' 
			AND (FK_users='$who' OR private=0) 
			AND end > '$begin' AND begin < '$end'";

	$result2=$DB->query($query2);


	$remind=new Reminder();

	$i=0;
	if ($DB->numrows($result2)>0)
		while ($data=$DB->fetch_array($result2)){
			$remind->getFromDB($data["ID"]);


			$interv[$data["begin"]."$$".$i]["id_reminder"]=$remind->fields["ID"];
			$interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
			$interv[$data["begin"]."$$".$i]["end"]=$data["end"];
			$interv[$data["begin"]."$$".$i]["name"]=$remind->fields["name"];
			$interv[$data["begin"]."$$".$i]["content"]=$remind->fields["text"];

			$i++;
		}


	$data=doHookFunction("planning_populate",array("begin"=>$begin,"end"=>$end,"who"=>$who));

	if (isset($data["items"])&&count($data["items"])){
		$interv=array_merge($data["items"],$interv);
	}


	if (count($interv)>0) {

		

		foreach ($interv as $key => $val){

			$vevent = new vevent(); //initiate EVENT 
			
			if(isset($val["id_tracking"])){
				$vevent->setProperty("uid","Job#".$val["id_tracking"]);
			}else if (isset($val["id_reminder"])){
				$vevent->setProperty("uid","Event#".$val["id_reminder"]);
			} else {
				$vevent->setProperty("uid","Plugin#".$key);
			}	
			$vevent->setProperty( "dstamp" , $val["begin"] ); 
			$vevent->setProperty( "dtstart" , $val["begin"] ); 
			$vevent->setProperty( "dtend" , $val["end"] ); 
			
			if(isset($val["id_tracking"])){
				$vevent->setProperty( "summary" , $LANG["planning"][8]." # ".$val["id_tracking"]." ".$LANG["common"][1]." # ".$val["device"] ); 
			}else if (isset($val["name"])){
				$vevent->setProperty( "summary" , $val["name"] ); 
			}

			if (isset($val["content"])){
				$vevent->setProperty( "description" , html_clean($val["content"]) ); 
			} else if (isset($val["name"])){
				$vevent->setProperty( "description" , $val["name"] ); 
			}

			if(isset($val["id_tracking"])){
				$vevent->setProperty( "url", $CFG_GLPI["url_base"]."/index.php?redirect=tracking_".$val["id_tracking"] );
			} 

			$v->setComponent( $vevent );
		}
	}
	$v->sort();
	$v->parse();

	return  $v->createCalendar(); 
}


?>
