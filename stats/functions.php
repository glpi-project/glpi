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
// Original Author of file: Mustapha Saddalah et Bazile Lebeau et Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

//return if the $postfromselect if a dropdown or not
function is_dropdown_stat($postfromselect) {
	$dropdowns = array ("locations","os");
	if(in_array(str_replace("glpi_dropdown_","",$postfromselect),$dropdowns)) return true;
	elseif(strcmp("glpi_type_computers",$postfromselect) == 0) return true;
	else return false; 
}


//return an array from tracking
//it contains the distinct users witch have any intervention assigned to.
function getNbIntervTech()
{
	$db = new DB;
	$query = "SELECT distinct(glpi_tracking.assign) as assign FROM glpi_tracking where glpi_tracking.assign != '' order by assign";
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
	$db = new DB;
	$query = "SELECT ID from ". $dropdown ." order by name";
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
//it contains the distinct authors of interventions.
function getNbIntervAuthor()
{	
	$db = new DB;
	$query = "SELECT distinct(glpi_tracking.author) as author FROM glpi_tracking order by author";
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

//Return a counted number of intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getNbInter($quoi, $chps, $value, $date1 = '', $date2 = '')
{
	$dropdowns = array ("location", "type", "os");
	$db = new DB;
	if($quoi == 1) {
		$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query .= ", glpi_computers where glpi_tracking.computer = glpi_computers.ID and $chps = '$value' ";
			}
			else {
				$query .= " where $chps = '$value'";
			}
		}
		
	}
	elseif($quoi == 2) {
		$query = "select count(ID) as total from glpi_tracking where YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select count(ID) as total from glpi_tracking where YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	elseif($quoi == 4) {
		$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query .= ", glpi_computers where glpi_tracking.computer = glpi_computers.ID and $chps = '$value' ";
			}
			else {
				$query .= " where $chps = '$value'";
			}
		} else {
			$query .= " where '1'= '1' ";
		}
			if ($date1!="") $query.= " and date >= '". $date1 ."' ";
			if ($date2!="") $query.= " and date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";

	}

	$result = $db->query($query);
	return $db->result($result,0,"total");
}

//Return a counted number of resolved/old intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getNbResol($quoi, $chps, $value, $date1 = '', $date2= '')
{
	$db = new DB;
	$dropdowns = array ("location", "type", "os");
	if($quoi == 1) {
		$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query .= ", glpi_computers where glpi_tracking.status = 'old' and glpi_tracking.computer = glpi_computers.ID and $chps = '$value'";
			}
			else {
				$query .= " where $chps = '$value' and glpi_tracking.status = 'old'";
			}
		}
		else {
			$query.= " where glpi_tracking.status = 'old'";
		}	
	}
	elseif($quoi == 2) {
		$query = "select count(ID) as total from glpi_tracking where glpi_tracking.status = 'old' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select count(ID) as total from glpi_tracking where glpi_tracking.status = 'old' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	elseif($quoi == 4) {
		$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query .= ", glpi_computers where glpi_tracking.status = 'old' and glpi_tracking.computer = glpi_computers.ID and $chps = '$value'";
			}
			else {
				$query .= " where $chps = '$value' and glpi_tracking.status = 'old'";
			}
		}
		else {
			$query.= " where '1'='1' ";
		}
		if ($date1!="") $query.= " and date >= '". $date1 ."' ";
		if ($date2!="") $query.= " and date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		
	}

	$result = $db->query($query);
	return $db->result($result,0,"total");
	
}

//Return the average time to reslove an intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getResolAvg($quoi, $chps, $value, $date1 = '', $date2 = '')
{
	$dropdowns = array ("location", "type", "os");
	$db = new DB;
	if($quoi == 1) {
	if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
			}
			else {
				$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
		}
		else {
			$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
		}
	}
	elseif($quoi == 2) {
		$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where glpi_tracking.status = 'old' and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	elseif($quoi == 4) {
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
				
			}
			else {
				$query = "select AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
		}
		else {
			$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
		}
		if ($date1!="") $query.= " and date >= '". $date1 ."' ";
		if ($date2!="") $query.= " and date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		
	}
		
	$result = $db->query($query);
	if($db->numrows($result) == 1)
	{
		$sec = $db->result($result,0,"total");
	}
	if(empty($sec)) $sec = 0;
	$temps = toTimeStr($sec);
	return $temps;
	
}

//Return the real time to reslove an intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getRealAvg($quoi, $chps, $value, $date1 = '', $date2 = '')
{
	$db = new DB;
	$dropdowns = array ("location", "type", "os");
	if($quoi == 1) {
			
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select AVG(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value' and glpi_tracking.realtime > 0";
			}
			else {
				$query = "select AVG(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
			}
		}
		else {
			$query = "select AVG(glpi_tracking.realtime) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
		}
	}
	elseif($quoi == 2) {
		$query = "select AVG(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and glpi_tracking.realtime > 0";
	}
	elseif($quoi == 3) {
		$query = "select AVG(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status = 'old' and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW()) and glpi_tracking.realtime > 0";
	}
	elseif($quoi == 4) {
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select AVG(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value' and glpi_tracking.realtime > 0";
				
			}
			else {
				$query = "select AVG(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
			}
		}
		else {
			$query = "select AVG(glpi_tracking.realtime) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and glpi_tracking.realtime > 0";
		}
		if ($date1!="") $query.= " and date >= '". $date1 ."' ";
		if ($date2!="") $query.= " and date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		
	}
		
	$result = $db->query($query);
	if($db->numrows($result) == 1)
	{
		$realtime = $db->result($result,0,"total");
	}
	if(empty($realtime)) $realtime = 0;
	$temps = getRealtime($realtime);
	return $temps;
	
}

//Return the sum real time to reslove an intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getRealTotal($quoi, $chps, $value, $date1 = '', $date2 = '')
{
	$db = new DB;
	$dropdowns = array ("location", "type", "os");
	if($quoi == 1) {
			
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select SUM(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value' and glpi_tracking.realtime > 0";
			}
			else {
				$query = "select SUM(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
			}
		}
		else {
			$query = "select SUM(glpi_tracking.realtime) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
		}
	}
	elseif($quoi == 2) {
		$query = "select SUM(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and glpi_tracking.realtime > 0";
	}
	elseif($quoi == 3) {
		$query = "select SUM(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status = 'old' and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW()) and glpi_tracking.realtime > 0";
	}
	elseif($quoi == 4) {
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select SUM(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value' and glpi_tracking.realtime > 0";
				
			}
			else {
				$query = "select SUM(glpi_tracking.realtime)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
			}
		}
		else {
			$query = "select SUM(glpi_tracking.realtime) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' and glpi_tracking.realtime > 0";
		}
		if ($date1!="") $query.= " and date >= '". $date1 ."' ";
		if ($date2!="") $query.= " and date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		
	}
		
	$result = $db->query($query);
	if($db->numrows($result) == 1)
	{
		$realtime = $db->result($result,0,"total");
	}
	if(empty($realtime)) $realtime = 0;
	$temps = getRealtime($realtime);
	return $temps;
	
}



//Make a good string from the unix timestamp $sec
//
function toTimeStr($sec)
{
	global $lang;
	$sec=floor($sec);
	if($sec < 60) {
		return $sec." ".$lang["stats"][34];
	}
	elseif($sec < 3600) {
		$min = (int)($sec/60);
		$sec = $sec%60;
		return $min." ".$lang["stats"][33]." ".$sec." ".$lang["stats"][34];
	}
	elseif($sec <  86400) {
		$heure = (int)($sec/3600);
		$min = (int)(($sec%60)/(60));
		$sec = (int)$sec%60;
		return $heure." ".$lang["stats"][32]." ".$min." ".$lang["stats"][33]." ".$sec." ".$lang["stats"][34];
	}
	else {
		$jour = (int)($sec/86400);
		$heure = (int)(($sec%60)/(3600));
		$min = (int)(($sec%60)/(60));
		$sec = $sec%60;
		return $jour." ".$lang["stats"][31]." ".$heure." ".$lang["stats"][32]." ".$min." ".$lang["stats"][33]." ".$sec." ".$lang["stats"][34];
	}
}

//Return the maximal time to reslove an intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
function getResolMax($quoi)
{
	$db = new DB;
	if($quoi == 1) {
		$query = "select MAX(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where glpi_tracking.status = 'old'";	
	}
	elseif($quoi == 2) {
		$query = "select MAX(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where glpi_tracking.status ='old' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select MAX(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where glpi_tracking.status = 'old' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	$result = $db->query($query);
	$sec = $db->result($result,0,"total");
	if(empty($sec)) $sec = 0;
	$temps = toTimeStr($sec);
	return $temps;
}
//Return the maximal time to reslove an intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
function getRealResolMax($quoi)
{
	$db = new DB;
	if($quoi == 1) {
		$query = "select MAX(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status = 'old'";	
	}
	elseif($quoi == 2) {
		$query = "select MAX(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status ='old' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select MAX(glpi_tracking.realtime) as total from glpi_tracking where glpi_tracking.status = 'old' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
//	echo $query;
	$result = $db->query($query);
	$sec = $db->result($result,0,"total");
	if (empty($sec)) $sec=0;
	$temps = getRealtime($sec);
	return $temps;
}
//Return the maximal time to the first action of each intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
function getFirstActionMin($quoi)
{
	$db = new DB;
	if($quoi == 1) {
		$query = "select MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status = 'old' AND glpi_tracking.closedate <> '0000-00-00 00:00:00'";	
	}
	elseif($quoi == 2) {
		$query = "select MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status ='old' and YEAR(glpi_tracking.date) = YEAR(NOW()) AND glpi_tracking.closedate <> '0000-00-00 00:00:00'";
	}
	elseif($quoi == 3) {
		$query = "select MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status = 'old' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW()) AND glpi_tracking.closedate <> '0000-00-00 00:00:00'";
	}
	$result = $db->query($query);
	$total = $db->result($result,0,"total");
	$first = $db->result($result,0,"total");
	$sec=min($total,$first);
	if (empty($sec)) $sec=0;
	$temps = toTimeStr($sec);
	return $temps;
}

//Return the first action on each intervention
//$quoi == 1 it return the number at all
//$quoi == 2 it return the number for current year
//$quoi == 3 it return the number for current mounth
//build the query with the params $chps and $value (only for the "at all" result)
//$chps contains the table where we apply the where clause
//$value contains the value to parse in the table
//common usage in query  "where $chps = '$value'";
function getFirstActionAvg($quoi, $chps, $value, $date1 = '', $date2 = '')
{
	$db = new DB;
	$dropdowns = array ("location", "type", "os");
	if($quoi == 1) {
			
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first";
				$query .= " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID), glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
			}
			else {
				$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first";
				$query .= " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
		}
		else {
			$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID)";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
		}
	}
	elseif($quoi == 2) {
		$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status = 'old' and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	elseif($quoi == 4) {
		if(!empty($chps) && !empty($value)) {
			if(in_array(ereg_replace("glpi_computers.","",$chps),$dropdowns)) {
				$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first";
				$query .= " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID), glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
				
			}
			else {
				$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first";
				$query .= " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
		}
		else {
			$query = "select glpi_tracking.ID AS ID, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID)";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
		}
		if ($date1!="") $query.= " and glpi_tracking.date >= '". $date1 ."' ";
		if ($date2!="") $query.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		
	}
		$query.=" GROUP BY glpi_tracking.ID";
	$result = $db->query($query);
	$numrows=$db->numrows($result);
	$total=0;
	if ($numrows>0){
		while($line = $db->fetch_array($result)) {
		$actu=$line['total'];
		if (!empty($line['first'])&&$line['first']<$actu)
		$actu=$line['first'];
		$total+=$actu;
		}
		$total/=$numrows;
	}
	$temps=toTimeStr(floor($total));
	return $temps;
	
}

function constructEntryValues($type,$begin="",$end=""){
	$db=new DB();

if (empty($end)) $end=date("Y-m-d");
$end.=" 23:59:59";
// 1 an par defaut
if (empty($begin)) $begin=date("Y-m-d",mktime(0,0,0,date("m"),date("d"),date("Y")-1));
$begin.=" 00:00:00";

	$query="";
	switch($type)	{
	
		case "inter_total": 
			$WHERE="WHERE '1' = '1'";
			if (!empty($begin)) $WHERE.= " AND date >= '$begin' ";
			 if (!empty($end)) $WHERE.= " AND date <= '$end' ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m') AS date_unix, COUNT(ID) AS total_visites  FROM glpi_tracking ".
				$WHERE.
				" GROUP BY date_unix ORDER BY date";
				break;
		case "inter_solved": 
			$WHERE="WHERE status = 'old' AND closedate <> '0000-00-00 00:00:00' ";
			if (!empty($begin)) $WHERE.= " AND closedate >= '$begin' ";
			 if (!empty($end)) $WHERE.= " AND closedate <= '$end' ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(closedate),'%Y-%m') AS date_unix, COUNT(ID) AS total_visites  FROM glpi_tracking ".
				$WHERE.
				" GROUP BY date_unix ORDER BY closedate";
				break;
	case "inter_avgsolvedtime" :
			$WHERE="WHERE status = 'old' AND closedate <> '0000-00-00 00:00:00' ";
			if (!empty($begin)) $WHERE.= " AND closedate >= '$begin' ";
			 if (!empty($end)) $WHERE.= " AND closedate <= '$end' ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(closedate),'%Y-%m') AS date_unix, 24*AVG(TO_DAYS(closedate)-TO_DAYS(date)) AS total_visites  FROM glpi_tracking ".
				$WHERE.
				" GROUP BY date_unix ORDER BY closedate";
				break;
	case "inter_avgrealtime" :
			$WHERE="WHERE realtime > '0' ";
			if (!empty($begin)) $WHERE.= " AND closedate >= '$begin' ";
			 if (!empty($end)) $WHERE.= " AND closedate <= '$end' ";

			$query="SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(closedate),'%Y-%m') AS date_unix, 60*AVG(realtime) AS total_visites  FROM glpi_tracking ".
				$WHERE.
				" GROUP BY date_unix ORDER BY closedate";
				break;
	case "inter_avgtakeaccount" :
			$WHERE="WHERE glpi_tracking.status = 'old' AND closedate <> '0000-00-00 00:00:00' ";
			if (!empty($begin)) $WHERE.= " AND glpi_tracking.date >= '$begin' ";
			 if (!empty($end)) $WHERE.= " AND glpi_tracking.date <= '$end' ";

			$query="SELECT glpi_tracking.ID AS ID, FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tracking.closedate),'%Y-%m') AS date_unix, MIN(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) AS OPEN, MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) AS FIRST FROM glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) ".
				$WHERE.
				" GROUP BY ID";
				break;
//		$query = " from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW())";	
			
	}
//echo $query;
$entrees=array();
if (empty($query)) return array();

	$result=$db->query($query);
	while ($row = $db->fetch_array($result)) {
			$date = $row['date_unix'];
			if ($type=="inter_avgtakeaccount"){
				$min=$row["OPEN"];	
				if (!empty($row["FIRST"])&&$row["FIRST"]<$min) $min=$row["FIRST"];
				if (!isset($entrees["$date"])) {$entrees["$date"]=$min; $count["$date"]=1;}
				else if ($min<$entrees["$date"]) {$entrees["$date"]+=$min;$count["$date"]++;}
			} else {
				$visites = round($row['total_visites']);
				$entrees["$date"] = $visites;
			}
		}

if ($type=="inter_avgtakeaccount"){
	foreach ($entrees as $key => $val){
		$entrees[$key]=round($entrees[$key]/$count[$key]/3600);

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
function graphByMonth($entrees,$titre="",$unit="",$showtotal=1){
	global $HTMLRel,$lang;
		// Couleurs cf. bas du fichier
		/// Bleu
		$couleur_foncee="#3874B0";
		$couleur_claire="#EDF3FE";
		$couleur_lien="#814E1B";
		$couleur_lien_off="#435E79";

		$db =new DB();
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
		
		echo "<table cellpadding=0 cellspacing=0 border=0><tr><td background='".$HTMLRel."pics/fond-stats.gif'>";
		echo "<table cellpadding=0 cellspacing=0 border=0><tr>";
		echo "<td bgcolor='black'><img src='".$HTMLRel."pics/rien.gif' width=1 height=200></td>";
	
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
			
			$hauteur_moyenne = round($moyenne * $rapport) - 1;
			$hauteur = round($value * $rapport)	- 1;
			echo "<td valign='bottom' width=$largeur>";
			
			if ($hauteur > 0){
				if ($hauteur_moyenne > $hauteur) {
					$difference = ($hauteur_moyenne - $hauteur) -1;
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$difference>";
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;'>";
					} 
					else {
						echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;'>";
					}
				}
				else if ($hauteur_moyenne < $hauteur) {
					$difference = ($hauteur - $hauteur_moyenne) -1;
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						$couleur =  $couleur_foncee;
					} 
					else {
						$couleur = $couleur_claire;
					}
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$difference style='background-color:$couleur;'>";
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:#333333;'>";
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$hauteur_moyenne style='background-color:$couleur;'>";
				}
				else {
					echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:$couleur_foncee;'>";
					if (ereg("-01",$key)){ // janvier en couleur foncee
						echo "<img alt=\"$key: $val_tab\" title=\"$key: $value\" src='".$HTMLRel."pics/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_foncee;'>";
					} 
					else {
						echo "<img alt=\"$key: $value\" title=\"$key: $value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=$hauteur style='background-color:$couleur_claire;'>";
					}
				}
			}
			
			echo "<img alt=\"$value\" title=\"$value\"  src='".$HTMLRel."pics/rien.gif' width=$largeur height=1 style='background-color:black;'>";
			echo "</td>\n";
			
		}
		echo "<td bgcolor='black'><img src='".$HTMLRel."pics/rien.gif' width=1 height=1></td>";
		echo "</tr>";
		if ($largeur>10){
			echo "<tr><td></td>";
			foreach ($entrees as $key => $val){
				$splitter=split("-",$key);
				echo "<td align='center'>".substr($lang["calendarM"][$splitter[1]-1],0,1)."</td>";
			}
		echo "</tr>";
		}
		
		if ($maxgraph<=10) $r=2;
		else if ($maxgraph<=100) $r=1;
		else $r=0;
		echo "</table>";
		echo "</td>";
		echo "<td background='".$HTMLRel."pics/fond-stats.gif' valign='bottom'><img src='".$HTMLRel."pics/rien.gif' style='background-color:black;' width=3 height=1></td>";
		echo "<td><img src='".$HTMLRel."pics/rien.gif' width=5 height=1></td>";
		echo "<td valign='top'><font face='Verdana,Arial,Sans,sans-serif' size=2>";
		echo "<table cellpadding=0 cellspacing=0 border=0>";
		echo "<tr><td height=15 valign='top'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph,$r)."</b></font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(7*($maxgraph/8),$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1>".round(3*($maxgraph/4),$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(5*($maxgraph/8),$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>".round($maxgraph/2,$r)."</b></font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(3*($maxgraph/8),$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1>".round($maxgraph/4,$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=25 valign='middle'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1 color='#999999'>".round(1*($maxgraph/8),$r)."</font>";
		echo "</td></tr>";
		echo "<tr><td height=10 valign='bottom'>";		
		echo "<font face='arial,helvetica,sans-serif' size=1><b>0</b></font>";
		echo "</td>";

		echo "</tr></table>";
		echo "</td></tr></table>";
		echo "</div>";
	
	
	
}


/*

switch ($prefs['couleur']) {
	case 1:
		/// Vert
		$couleur_foncee="#02531B";
		$couleur_claire="#CFFEDE";
		$couleur_lien_off="#304C38";
		$couleur_lien="#854270";
		break;
	case 2:
		/// Rouge
		$couleur_foncee="#640707";
		$couleur_claire="#FFE0E0";
		$couleur_lien="#346868";
		$couleur_lien_off="#684747";
		break;
	case 3:
		/// Jaune
		$couleur_foncee="#666500";
		$couleur_claire="#FFFFE0";
		$couleur_lien="#65659C";
		$couleur_lien_off="#6A6A43";
		break;
	case 4:
		/// Violet
		$couleur_foncee="#340049";
		$couleur_claire="#F9EBFF";
		$couleur_lien="#396B25";
		$couleur_lien_off="#472854";
		break;
	case 5:
		/// Gris
		$couleur_foncee="#3F3F3F";
		$couleur_claire="#F2F2F2";
		$couleur_lien="#854270";
		$couleur_lien_off="#666666";
		break;
	case 6:
		/// Bleu
		$couleur_foncee="#3874B0";
		$couleur_claire="#EDF3FE";
		$couleur_lien="#814E1B";
		$couleur_lien_off="#435E79";
		break;
	case 7:
		/// Bleu pastelle
		$couleur_foncee="#766CF6";
		$couleur_claire="#EBE9FF";
		$couleur_lien="#869100";
		$couleur_lien_off="#5B55A0";
		break;
	case 8:
		/// Vert pastelles
		$couleur_foncee="#009F3C";
		$couleur_claire="#E2FDEC";
		$couleur_lien="#EE0094";
		$couleur_lien_off="#02722C";
		break;
	case 9:
		/// Rouge vif
		$couleur_foncee="#FF0000";
		$couleur_claire="#FFEDED";
		$couleur_lien="#D302CE";
		$couleur_lien_off="#D40202";
		break;
	case 10:
		/// Orange
		$couleur_foncee="#E95503";
		$couleur_claire="#FFF2EB";
		$couleur_lien="#81A0C1";
		$couleur_lien_off="#FF5B00";
		break;
	case 11:
		/// Violet clair
		$couleur_foncee="#CD006F";
		$couleur_claire="#FDE5F2";
		$couleur_lien="#E95503";
		$couleur_lien_off="#8F004D";
		break;
	case 12:
		/// Marron
		$couleur_foncee="#8C6635";
		$couleur_claire="#F5EEE5";
		$couleur_lien="#1A64DF";
		$couleur_lien_off="#955708";
		break;
	default:
		/// Bleu
		$couleur_foncee="#3874B0";
		$couleur_claire="#EDF3FE";
		$couleur_lien="#814E1B";
		$couleur_lien_off="#435E79";
}
*/

?>
