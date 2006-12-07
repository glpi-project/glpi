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

//return if the $postfromselect if a dropdown or not
function is_dropdown_stat($postfromselect) {
	$dropdowns = array ("locations","os","model");
	if(in_array(str_replace("glpi_dropdown_","",$postfromselect),$dropdowns)) return true;
	elseif(strcmp("glpi_type_computers",$postfromselect) == 0) return true;
	else return false; 
}


function getStatsItems($date1,$date2,$type){
	global $HTMLRel,$db;
	$val=array();

	switch ($type){
		case "technicien":
			$nomTech = getNbIntervTech($date1,$date2);


		$i=0;
		if (is_array($nomTech))
			foreach($nomTech as $key){
				$val[$i]["ID"]=$key["assign"];
				$val[$i]["link"]="<a href='".$HTMLRel."front/user.info.php?ID=".$key["assign"]."'>";
				if (empty($key["realname"]))
					$val[$i]["link"].=$key["name"];
				else {
					$val[$i]["link"].=$key["realname"];
					if (!empty($key["firstname"]))	
						$val[$i]["link"].=" ".$key["firstname"];
				}
				$val[$i]["link"].="</a>";
				$i++;
			}
		break;
		case "technicien_followup":
			$nomTech = getNbIntervTechFollowup($date1,$date2);


		$i=0;
		if (is_array($nomTech))
			foreach($nomTech as $key){
				$val[$i]["ID"]=$key["author"];
				$val[$i]["link"]="<a href='".$HTMLRel."front/user.info.php?ID=".$key["author"]."'>";
				if (empty($key["realname"]))
					$val[$i]["link"].=$key["name"];
				else {
					$val[$i]["link"].=$key["realname"];
					if (!empty($key["firstname"]))	
						$val[$i]["link"].=" ".$key["firstname"];
				}
				$val[$i]["link"].="</a>";
				$i++;
			}
		break;
		case "enterprise":
			$nomEnt = getNbIntervEnterprise($date1,$date2);

		$i=0;
		if (is_array($nomEnt))
			foreach($nomEnt as $key){
				$val[$i]["ID"]=$key["assign_ent"];
				$val[$i]["link"]="<a href='".$HTMLRel."front/enterprise.form.php?ID=".$key["assign_ent"]."'>";
				$val[$i]["link"].=$key["name"];
				$val[$i]["link"].="</a>";
				$i++;
			}

		break;
		case "user":
			$nomUsr = getNbIntervAuthor($date1,$date2);

		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]="<a href='".$HTMLRel."front/user.info.php?ID=".$key["ID"]."'>";
				if (empty($key["realname"]))
					$val[$i]["link"].=$key["name"];
				else {
					$val[$i]["link"].=$key["realname"];
					if (!empty($key["firstname"]))	
						$val[$i]["link"].=" ".$key["firstname"];
				}
				$val[$i]["link"].="</a>";
				$i++;
			}

		break;
		case "category":
			$nomUsr = getNbIntervCategory();
		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]=$key["category"];
				$i++;
			}

		break;
		case "group":
			$nomUsr = getNbIntervGroup();
		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]=$key["name"];
				$i++;
			}

		break;

		case "priority":
			$nomUsr = getNbIntervPriority();
		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]=$key["priority"];
				$i++;
			}

		break;
		case "request_type":
			$nomUsr = getNbIntervRequestType();
		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]=$key["request_type"];
				$i++;
			}

		break;
		case "glpi_type_computers":
			case "glpi_dropdown_model":
			case "glpi_dropdown_os":
			case "glpi_dropdown_locations":
			$nomUsr = getNbIntervDropdown($type);

		$i=0;
		if (is_array($nomUsr))
			foreach($nomUsr as $key){
				$val[$i]["ID"]=$key["ID"];
				$val[$i]["link"]=$key["name"];
				$i++;
			}
		break;
		// DEVICE CASE
		default :
		$device_table = getDeviceTable($type);

		//select devices IDs (table row)
		$query = "select ID, designation from ".$device_table." order by designation";
		$result = $db->query($query);

		if($db->numrows($result) >=1) {
			$i = 0;
			while($line = $db->fetch_assoc($result)) {
				$val[$i]['ID'] = $line['ID'];
				$val[$i]['link'] = $line['designation'];
				$i++;
			}
		}

		break;
	}
	return $val;
}

function displayStats($type,$field,$date1,$date2,$start,$value,$value2=""){
	global $lang,$cfg_glpi,$HTMLRel;

	// Set display type for export if define
	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"]))
		$output_type=$_GET["display_type"];

	if ($output_type==HTML_OUTPUT) // HTML display
		echo "<div align ='center'>";

	if (is_array($value)){


		$end_display=$start+$cfg_glpi["list_limit"];
		$numrows=count($value);
		if (isset($_GET['export_all'])) {
			$start=0;
			$end_display=$numrows;
		}
		$nbcols=8;
		if ($output_type!=HTML_OUTPUT) // not HTML display
			$nbcols--;
		echo displaySearchHeader($output_type,$end_display-$start+1,$nbcols);
		echo displaySearchNewLine($output_type);
		$header_num=1;
		echo displaySearchHeaderItem($output_type,"&nbsp;",$header_num);
		if ($output_type==HTML_OUTPUT) // HTML display
			echo displaySearchHeaderItem($output_type,"",$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][13],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][14],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][15],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][25],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][27],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][30],$header_num);
		// End Line for column headers		
		echo displaySearchEndLine($output_type);
		$row_num=1;
		for ($i=$start;$i< $numrows && $i<($end_display);$i++){
			$row_num++;
			$item_num=1;
			echo displaySearchNewLine($output_type);
			echo displaySearchItem($output_type,$value[$i]['link'],$item_num,$row_num);
			if ($output_type==HTML_OUTPUT) // HTML display
				echo displaySearchItem($output_type,"<a href='stat.graph.php?ID=".$value[$i]['ID']."&amp;type=$type".(!empty($value2)?"&amp;champ=$value2":"")."'><img src=\"".$HTMLRel."pics/stats_item.png\" alt='' title=''></a>",$item_num,$row_num);

			//le nombre d'intervention
			//the number of intervention
			$opened=constructEntryValues("inter_total",$date1,$date2,$type,$value[$i]["ID"],$value2);
			$nb_opened=array_sum($opened);
			echo displaySearchItem($output_type,$nb_opened,$item_num,$row_num);
			//le nombre d'intervention resolues
			//the number of resolved intervention
			$solved=constructEntryValues("inter_solved",$date1,$date2,$type,$value[$i]["ID"],$value2);
			$nb_solved=array_sum($solved);
			echo displaySearchItem($output_type,$nb_solved,$item_num,$row_num);
			//Le temps moyen de resolution
			//The average time to resolv
			$data=constructEntryValues("inter_avgsolvedtime",$date1,$date2,$type,$value[$i]["ID"],$value2);
			foreach ($data as $key2 => $val2){
				$data[$key2]*=$solved[$key2];
			}
			if ($nb_solved>0)
				$nb=array_sum($data)/$nb_solved;
			else $nb=0;
			echo displaySearchItem($output_type,toTimeStr($nb*HOUR_TIMESTAMP,0),$item_num,$row_num);
			//Le temps moyen de l'intervention réelle
			//The average realtime to resolv
			$data=constructEntryValues("inter_avgrealtime",$date1,$date2,$type,$value[$i]["ID"],$value2);
			foreach ($data as $key2 => $val2){
				$data[$key2]*=$solved[$key2];
			}
			$total_realtime=array_sum($data);
			if ($nb_solved>0)
				$nb=$total_realtime/$nb_solved;
			else $nb=0;
			echo displaySearchItem($output_type,toTimeStr($nb*MINUTE_TIMESTAMP,0),$item_num,$row_num);
			//Le temps total de l'intervention réelle
			//The total realtime to resolv
			echo displaySearchItem($output_type,toTimeStr($total_realtime*MINUTE_TIMESTAMP,0),$item_num,$row_num);				
			//Le temps moyen de prise en compte du ticket
			//The average time to take a ticket into account
			$data=constructEntryValues("inter_avgtakeaccount",$date1,$date2,$type,$value[$i]["ID"],$value2);

			foreach ($data as $key2 => $val2){
				$data[$key2]*=$solved[$key2];
			}
			if ($nb_solved>0)
				$nb=array_sum($data)/$nb_solved;
			else $nb=0;
			echo displaySearchItem($output_type,toTimeStr($nb*HOUR_TIMESTAMP,0),$item_num,$row_num);

			echo displaySearchEndLine($output_type);
		}
		// Display footer
		echo displaySearchFooter($output_type);
	} else {
		echo $lang["stats"][23];
	}
	if ($output_type==HTML_OUTPUT) // HTML display
		echo "</div>";
}


//return an array from tracking
//it contains the distinct users witch have any intervention assigned to.
function getNbIntervTech($date1,$date2)
{
	global $db;
	$query = "SELECT distinct glpi_tracking.assign as assign, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_users  ON (glpi_users.ID=glpi_tracking.assign) ";

	$query.= " WHERE glpi_tracking.assign != 0 ";
	if ($date1!="") $query.= " and glpi_tracking.date >= '". $date1 ."' ";
	if ($date2!="") $query.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";

	$query.= " order by realname, firstname, name";
	$result = $db->query($query);
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i] = $line;
			$i++;
		}
		return $tab;
	}
	else return 0;	
}

//return an array from tracking
//it contains the distinct users witch have any intervention assigned to.
function getNbIntervTechFollowup($date1,$date2)
{
	global $db;
	$query = "SELECT distinct glpi_followups.author as author, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_followups ON (glpi_tracking.ID = glpi_followups.tracking) ";
	$query.= " LEFT JOIN glpi_users  ON (glpi_users.ID=glpi_followups.author) ";

	$query.= " WHERE glpi_followups.author != 0 ";
	if ($date1!="") $query.= " and glpi_tracking.date >= '". $date1 ."' ";
	if ($date2!="") $query.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";

	$query.= " order by firstname, realname, name";
	$result = $db->query($query);
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i] = $line;
			$i++;
		}
		return $tab;
	}
	else return 0;	
}


//return an array from tracking
//it contains the distinct users witch have any intervention assigned to.
function getNbIntervEnterprise($date1,$date2)
{
	global $db;
	$query = "SELECT distinct glpi_tracking.assign_ent as assign_ent, glpi_enterprises.name as name";
	$query.= " FROM glpi_tracking ";
	$query.= " LEFT JOIN glpi_enterprises  ON (glpi_enterprises.ID=glpi_tracking.assign_ent) ";

	$query.= " WHERE glpi_tracking.assign_ent != 0 ";
	if ($date1!="") $query.= " and glpi_tracking.date >= '". $date1 ."' ";
	if ($date2!="") $query.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";

	$query.= " order by name";

	$result = $db->query($query);
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i] = $line;
			$i++;
		}
		return $tab;
	}
	else return 0;	
}

//return an array from tracking
//it contains the distinct location where there is/was an intervention
function getNbIntervDropdown($dropdown)
{
	global $db,$cfg_glpi;
	$field="name";
	if (in_array($dropdown,$cfg_glpi["dropdowntree_tables"])) $field="completename";
	$query = "SELECT * from ". $dropdown ." order by $field";

	$result = $db->query($query);
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i]['ID'] = $line['ID'];
			$tab[$i]['name'] = $line[$field];
			$i++;
		}
		return $tab;
	}
	else return 0;

}

//return an array from tracking
//it contains the distinct authors of interventions.
function getNbIntervAuthor($date1,$date2)
{	
	global $db;
	$query = "SELECT DISTINCT glpi_tracking.author as ID, glpi_users.name as name, glpi_users.realname as realname, glpi_users.firstname as firstname FROM glpi_tracking INNER JOIN glpi_users ON (glpi_users.ID=glpi_tracking.author)";
	$query.= " WHERE '1'='1' ";
	if ($date1!="") $query.= " and glpi_tracking.date >= '". $date1 ."' ";
	if ($date2!="") $query.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";

	$query.= " order by realname, firstname, name";
	$result = $db->query($query);
	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i] = $line;
			$i++;
		}
		return $tab;
	}
	else return 0;	

}

//return an array from tracking
//it contains the distinct priority of interventions.
function getNbIntervPriority()
{	
	global $db;
	$query = "SELECT DISTINCT priority FROM glpi_tracking order by priority";
	$result = $db->query($query);

	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i]["ID"] = $line["priority"];
			$tab[$i]["priority"] = getPriorityName($line["priority"]);
			$i++;
		}

		return $tab;
	}
	else return 0;	

}

function getNbIntervRequestType()
{	
	global $db;
	$query = "SELECT DISTINCT request_type FROM glpi_tracking order by request_type";
	$result = $db->query($query);

	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i]["ID"] = $line["request_type"];
			$tab[$i]["request_type"] = getRequestTypeName($line["request_type"]);
			$i++;
		}

		return $tab;
	}
	else return 0;	

}

//return an array from tracking
//it contains the distinct category of interventions.
function getNbIntervCategory()
{	
	global $db;
	$query = "SELECT id as ID, completename as category FROM glpi_dropdown_tracking_category order by completename";
	$result = $db->query($query);

	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i]["ID"] = $line["ID"];
			$tab[$i]["category"] = $line["category"];
			$i++;
		}

		return $tab;
	}
	else return 0;	

}

//return an array from tracking
//it contains the distinct group of interventions.
function getNbIntervGroup()
{	
	global $db;
	$query = "SELECT id as ID, name FROM glpi_groups order by name";
	$result = $db->query($query);

	if($db->numrows($result) >=1) {
		$i = 0;
		while($line = $db->fetch_assoc($result)) {
			$tab[$i]["ID"] = $line["ID"];
			$tab[$i]["name"] = $line["name"];
			$i++;
		}

		return $tab;
	}
	else return 0;	

}

//Make a good string from the unix timestamp $sec
//
function toTimeStr($sec,$display_sec=1)
{
	global $lang;
	$sec=floor($sec);
	if ($sec<0) $sec=0;

	if($sec < MINUTE_TIMESTAMP) {

		return $sec." ".$lang["stats"][34];
	}
	elseif($sec < HOUR_TIMESTAMP) {
		$min = floor($sec/MINUTE_TIMESTAMP);
		$sec = $sec%MINUTE_TIMESTAMP;

		$out=$min." ".$lang["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$lang["stats"][34];
		return $out;
	}
	elseif($sec <  DAY_TIMESTAMP) {
		$heure = floor($sec/HOUR_TIMESTAMP);
		$min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
		$sec = $sec%MINUTE_TIMESTAMP;
		$out=$heure." ".$lang["stats"][32]." ".$min." ".$lang["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$lang["stats"][34];
		return $out;
	}
	else {
		$jour = floor($sec/DAY_TIMESTAMP);
		$heure = floor(($sec%DAY_TIMESTAMP)/(HOUR_TIMESTAMP));
		$min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
		$sec = $sec%MINUTE_TIMESTAMP;
		$out=$jour." ".$lang["stats"][31]." ".$heure." ".$lang["stats"][32]." ".$min." ".$lang["stats"][33];
		if ($display_sec) $out.=" ".$sec." ".$lang["stats"][34];
		return $out;

	}
}



function constructEntryValues($type,$begin="",$end="",$param="",$value="",$value2=""){
	global $db;

	if (empty($end)) $end=date("Y-m-d");
	$end.=" 23:59:59";
	// 1 an par defaut
	if (empty($begin)) $begin=date("Y-m-d",mktime(0,0,0,date("m"),date("d"),date("Y")-1));
	$begin.=" 00:00:00";

	$query="";


	$WHERE=" WHERE '1'='1' ";
	$LEFTJOIN="";
	switch ($param){

		case "technicien":
			$WHERE.=" AND glpi_tracking.assign='$value'";
		break;
		case "technicien_followup":
			$WHERE.=" AND glpi_followups.author='$value'";
		$LEFTJOIN= "LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID)";
		break;	
		case "enterprise":
			$WHERE.=" AND glpi_tracking.assign_ent='$value'";
		break;
		case "user":
			$WHERE.=" AND glpi_tracking.author='$value'";
		break;
		case "category":
			$WHERE.=" AND glpi_tracking.category='$value'";
		break;
		case "group":
			$WHERE.=" AND glpi_tracking.FK_group='$value'";
		break;
		case "priority":
			$WHERE.=" AND glpi_tracking.priority='$value'";
		break;
		case "request_type":
			$WHERE.=" AND glpi_tracking.request_type='$value'";
		break;

		case "device":
			//select computers IDs that are using this device;
			$query2 = "SELECT distinct(glpi_computers.ID) as compid FROM glpi_computers INNER JOIN glpi_computer_device ON ( glpi_computers.ID = glpi_computer_device.FK_computers AND glpi_computer_device.device_type = '".$value2."' AND glpi_computer_device.FK_device = '".$value."') WHERE glpi_computers.is_template <> '1'";

		$result2 = $db->query($query2);
		$WHERE.=" AND (device_type = '".COMPUTER_TYPE."' AND ('0'='1'";
		while($line2 = $db->fetch_array($result2)) {
			$WHERE.=" OR glpi_tracking.computer='".$line2["compid"]."'";
		}
		$WHERE.=") )";

		break;
		case "comp_champ":
			//select computers IDs that are using this field;
			$query2 = "SELECT distinct(ID) as compid FROM glpi_computers WHERE  $value2='$value'";

		$result2 = $db->query($query2);
		$WHERE.=" AND (device_type = '".COMPUTER_TYPE."' AND ('0'='1'";
		while($line2 = $db->fetch_array($result2)) {
			$WHERE.=" OR glpi_tracking.computer='".$line2["compid"]."'";
		}
		$WHERE.=") )";

		break;
	}
	switch($type)	{

		case "inter_total": 
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.date >= '$begin' ";
		if (!empty($end)) $WHERE.= " AND glpi_tracking.date <= '$end' ";

		$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.date),'%Y-%m') AS date_unix, COUNT(glpi_tracking.ID) AS total_visites  FROM glpi_tracking ".$LEFTJOIN.
			$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.date";
		break;
		case "inter_solved": 
			$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate <> '0000-00-00 00:00:00' ";
		if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
		if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= '$end' ";

		$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, COUNT(glpi_tracking.ID) AS total_visites  FROM glpi_tracking ".$LEFTJOIN.
			$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
		break;
		case "inter_avgsolvedtime" :
			$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate <> '0000-00-00 00:00:00' ";
		if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
		if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= '$end' ";

		$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, 24*AVG(TO_DAYS(glpi_tracking.closedate)-TO_DAYS(glpi_tracking.date)) AS total_visites  FROM glpi_tracking ".
			$LEFTJOIN.$WHERE.
			" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
		break;
		case "inter_avgrealtime" :
			if ($param=="technicien_followup")
				$realtime_table="glpi_followups";
			else $realtime_table="glpi_tracking";
			$WHERE.=" AND $realtime_table.realtime > '0' ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= '$end' ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, ".MINUTE_TIMESTAMP."*AVG($realtime_table.realtime) AS total_visites  FROM glpi_tracking ".
				$LEFTJOIN.$WHERE.
				" GROUP BY date_unix ORDER BY glpi_tracking.closedate";
			break;
			case "inter_avgtakeaccount" :
				$WHERE.=" AND ( glpi_tracking.status = 'old_done' OR glpi_tracking.status = 'old_notdone') AND glpi_tracking.closedate <> '0000-00-00 00:00:00' ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.closedate >= '$begin' ";
			if (!empty($end)) $WHERE.= " AND glpi_tracking.closedate <= '$end' ";

			$query="SELECT glpi_tracking.ID AS ID, FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) AS OPEN, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) AS FIRST FROM glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) ".
				$WHERE.
				" GROUP BY glpi_tracking.ID";
			break;

			//		$query = " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW())";	

	}
	//echo $query."<br><br>";
	$entrees=array();
	if (empty($query)) return array();

	$result=$db->query($query);
	if ($result&&$db->numrows($result)>0)
		while ($row = $db->fetch_array($result)) {
			$date = $row['date_unix'];
			if ($type=="inter_avgtakeaccount"){
				$min=$row["OPEN"];	
				if (!empty($row["FIRST"])&&!is_null($row["FIRST"])&&$row["FIRST"]<$min) $min=$row["FIRST"];
				if (!isset($entrees["$date"])) {$entrees["$date"]=$min; $count["$date"]=1;}
				else if ($min<$entrees["$date"]) {$entrees["$date"]+=$min;$count["$date"]++;}
			} else {
				$visites = round($row['total_visites']);
				$entrees["$date"] = $visites;
			}
		}



	if ($type=="inter_avgtakeaccount"){

		foreach ($entrees as $key => $val){
			$entrees[$key]=round($entrees[$key]/$count[$key]/HOUR_TIMESTAMP);

		}
	}

	// Remplissage de $entrees pour les mois ou il n'y a rien

	$min=-1;		
	$max=0;		
	if (count($entrees)==0) return $entrees;

	foreach ($entrees as $key => $val){
		$time=strtotime($key."-01");
		if ($min>$time||$min<0) $min=$time;
		if ($max<$time) $max=$time;
	}

	$end_time=strtotime(date("Y-m",strtotime($end))."-01");
	$begin_time=strtotime(date("Y-m",strtotime($begin))."-01");

	if ($max<$end_time) $max=$end_time;
	if ($min>$begin_time) $min=$begin_time;
	$current=$min;
	//print_r($entrees);
	while ($current<=$max){
		$curentry=date("Y-m",$current);
		if (!isset($entrees["$curentry"])) $entrees["$curentry"]=0;
		$month=date("m",$current);
		$year=date("Y",$current);

		$current=mktime(0,0,0,intval($month)+1,1,intval($year));
	}

	// Tri pour un affichage correct
	ksort($entrees);

	//print_r($entrees);
	return $entrees;
}


// BASED ON SPIP DISPLAY GRAPH : www.spip.net
// $type = "month" or "year"
function graphBy($entrees,$titre="",$unit="",$showtotal=1,$type="month"){

	global $db,$HTMLRel,$lang;
	
	ksort($entrees);

	$total="";
	if ($showtotal==1) $total=array_sum($entrees);

	echo "<p align='center'>";
	echo "<font face='verdana,arial,helvetica,sans-serif' size='2'><b>$titre - $total $unit</b></font>";

	echo "<div align='center'>";

	if (count($entrees)>0){

		$max = max($entrees);
		$maxgraph = substr(ceil(substr($max,0,2) / 10)."000000000000", 0, strlen($max));

		if ($maxgraph < 10) $maxgraph = 10;
		if (1.1 * $maxgraph < $max) $maxgraph.="0";	
		if (0.8*$maxgraph > $max) $maxgraph = 0.8 * $maxgraph;
		$rapport = 200 / $maxgraph;

		$largeur = floor(420 / (count($entrees)));
		if ($largeur < 1) $largeur = 1;
		if ($largeur > 50) $largeur = 50;
	}

	echo "<table cellpadding='0' cellspacing='0' border='0' ><tr><td style='background-image:url(".$HTMLRel."pics/fond-stats.gif)' >";
	echo "<table cellpadding='0' cellspacing='0' border='0'><tr>";
	echo "<td bgcolor='black'><img src='".$HTMLRel."pics/noir.png' width='1' height='200' alt=''></td>";

	// Presentation graphique
	$n = 0;
	$decal = 0;
	$tab_moyenne = "";
	$total_loc=0;
	while (list($key, $value) = each($entrees)) {
		$n++;

		if ($decal == 30) $decal = 0;
		$decal ++;
		$tab_moyenne[$decal] = $value;

		$total_loc = $total_loc + $value;
		reset($tab_moyenne);

		$moyenne = 0;
		while (list(,$val_tab) = each($tab_moyenne))
			$moyenne += $val_tab;
		$moyenne = $moyenne / count($tab_moyenne);

		$hauteur_moyenne = round($moyenne * $rapport) ;
		$hauteur = round($value * $rapport)	;
		echo "<td valign='bottom' width=".$largeur.">";

		if ($hauteur >= 0){
			if ($hauteur_moyenne > $hauteur) {
				$difference = ($hauteur_moyenne - $hauteur) -1;
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/moyenne.png' width=".$largeur." height='1' >";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=".$largeur." height=".$difference." >";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/noir.png' width=".$largeur." height='1' >";
				if (ereg("-01",$key)){ // janvier en couleur foncee
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/fondgraph1.png' width=".$largeur." height=".$hauteur." >";
				} 
				else {
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/fondgraph2.png' width=".$largeur." height=".$hauteur." >";
				}
			}
			else if ($hauteur_moyenne < $hauteur) {
				$difference = ($hauteur - $hauteur_moyenne) -1;
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/noir.png' width=".$largeur." height='1'>";
				if (ereg("-01",$key)){ // janvier en couleur foncee
					$couleur =  "1";
					$couleur2 =  "2";
				} 
				else {
					$couleur = "2";
					$couleur2 = "1";
				}
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/fondgraph$couleur.png' width=".$largeur." height=".$difference.">";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/moyenne.png' width=".$largeur." height='1'>";
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/fondgraph$couleur.png' width=".$largeur." height=".$hauteur_moyenne.">";
			}
			else {
				echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/noir.png' width=".$largeur." height='1'>";
				if (ereg("-01",$key)){ // janvier en couleur foncee
					echo "<img alt=\"$key: $val_tab\" title=\"$key: $value\" src='".$HTMLRel."pics/fondgraph1.png' width=".$largeur." height=".$hauteur.">";
				} 
				else {
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/fondgraph2.png' width=".$largeur." height=".$hauteur.">";
				}
			}
		}

		echo "<img alt=\"$value\" title=\"$value\"  src='".$HTMLRel."pics/rien.gif' width=".$largeur." height='1'>";
		echo "</td>\n";

	}
	echo "<td bgcolor='black'><img src='".$HTMLRel."pics/noir.png' width='1' height='1' alt=''></td>";
	echo "</tr>";
	if ($largeur>10){
		echo "<tr><td></td>";
		foreach ($entrees as $key => $val){
			if ($type=="month"){
				$splitter=split("-",$key);
				echo "<td align='center'>".substr($lang["calendarM"][$splitter[1]-1],0,1)."</td>";
			} else if ($type=="year"){
				echo "<td align='center'>".substr($key,2,2)."</td>";
			}
		}
		echo "</tr>";
	}

	if ($maxgraph<=10) $r=2;
	else if ($maxgraph<=100) $r=1;
	else $r=0;
	echo "</table>";
	echo "</td>";
	echo "<td style='background-image:url(".$HTMLRel."pics/fond-stats.gif)' valign='bottom'><img src='".$HTMLRel."pics/rien.gif' style='background-color:black;' width='3' height='1' alt=''></td>";
	echo "<td><img src='".$HTMLRel."pics/rien.gif' width='5' height='1' alt=''></td>";
	echo "<td valign='top'>";
	echo "<table cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr><td height='15' valign='top'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><b>".round($maxgraph,$r)."</b></font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".round(7*($maxgraph/8),$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'>".round(3*($maxgraph/4),$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".round(5*($maxgraph/8),$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><b>".round($maxgraph/2,$r)."</b></font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".round(3*($maxgraph/8),$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'>".round($maxgraph/4,$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='25' valign='middle'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1' color='#999999'>".round(1*($maxgraph/8),$r)."</font>";
	echo "</td></tr>";
	echo "<tr><td height='10' valign='bottom'>";		
	echo "<font face='arial,helvetica,sans-serif' size='1'><b>0</b></font>";
	echo "</td>";

	echo "</tr></table>";
	echo "</td></tr></table>";
	echo "</div>";



}


function showItemStats($target,$date1,$date2,$start){
	global $db,$cfg_glpi,$lang;


	$output_type=HTML_OUTPUT;
	if (isset($_GET["display_type"]))
		$output_type=$_GET["display_type"];


	$query="SELECT device_type,computer,COUNT(*) AS NB FROM glpi_tracking WHERE date<= '".$date2."' AND date>= '".$date1."' GROUP BY device_type,computer ORDER BY NB DESC";
	$result=$db->query($query);
	$numrows=$db->numrows($result);

	if ($numrows>0){
		if ($output_type==HTML_OUTPUT){
			printPager($start,$numrows,$target,"date1=".$date1."&amp;date2=".$date2."&amp;type=hardwares&amp;start=$start",STAT_TYPE);
			echo "<div align='center'>";
		}

		$i=$start;
		if (isset($_GET['export_all']))
			$i=0;

		$end_display=$start+$cfg_glpi["list_limit"];
		if (isset($_GET['export_all']))
			$end_display=$numrows;
		echo displaySearchHeader($output_type,$end_display-$start+1,2,1);
		$header_num=1;
		echo displaySearchNewLine($output_type);
		echo displaySearchHeaderItem($output_type,$lang["common"][1],$header_num);
		echo displaySearchHeaderItem($output_type,$lang["stats"][13],$header_num);
		echo displaySearchEndLine($output_type);

		$db->data_seek($result,$start);

		$ci=new CommonItem();
		while ($i < $numrows && $i<($end_display)){
			$item_num=1;
			// Get data and increment loop variables
			$data=$db->fetch_assoc($result);
			if ($ci->getFromDB($data["device_type"],$data["computer"])){
				$del=false;
				if (isset($ci->obj->fields["deleted"])&&$ci->obj->fields["deleted"]=='Y') $del=true;
				//echo "<tr class='tab_bg_2$del'><td>".$ci->getLink()."</td><td>".$data["NB"]."</td></tr>";
				echo displaySearchNewLine($output_type);
				echo displaySearchItem($output_type,$ci->getLink(),$item_num,$i-$start+1,$del,"align='center'");
				echo displaySearchItem($output_type,$data["NB"],$item_num,$i-$start+1,$del,"align='center'");
			}
			$i++;
		}

		echo displaySearchFooter($output_type);
		if ($output_type==HTML_OUTPUT)
			echo "</div>";
	}

}


?>
