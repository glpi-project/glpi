<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file: Mustapha Saddalah et Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/

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
function getNbIntervLieux()
{
	$db = new DB;
	$query = "SELECT ID from glpi_dropdown_locations order by name";
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
	$db = new DB;
	if($quoi == 1) {
				$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		if(!empty($chps) && !empty($value)) {
			if($chps == "glpi_computers.location") {
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
			if($chps == "glpi_computers.location") {
				$query .= ", glpi_computers where glpi_tracking.computer = glpi_computers.ID and $chps = '$value' ";
			}
			else {
				$query .= " where $chps = '$value'";
			}
			$query .= " and date BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
		} else {
			$query .= " where date BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY )";
		}
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
	if($quoi == 1) {
		$query = "select count(glpi_tracking.ID) as total from glpi_tracking";
		if(!empty($chps) && !empty($value)) {
			if($chps == "glpi_computers.location") {
				$query .= ", glpi_computers where glpi_tracking.computer = glpi_computers.ID and $chps = '$value'";
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
			if($chps == "glpi_computers.location") {
				$query .= ", glpi_computers where glpi_tracking.computer = glpi_computers.ID and $chps = '$value'";
			}
			else {
				$query .= " where $chps = '$value' and glpi_tracking.status = 'old'";
			}
			$query .= " and closedate BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY )";
		}
		else {
			$query.= " where glpi_tracking.status = 'old' and closedate BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY )";
		}
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
	$db = new DB;
	if($quoi == 1) {
			
		if(!empty($chps) && !empty($value)) {
			if($chps == "glpi_computers.location") {
				$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_computers.location)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
			}
			else {
				$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.ID)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
		}
		else {
			$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.id) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
		}
	}
	elseif($quoi == 2) {
		$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.ID) as total from glpi_tracking where glpi_tracking.status ='old'  and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW())";
	}
	elseif($quoi == 3) {
		$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.ID) as total from glpi_tracking where glpi_tracking.status = 'old' and closedate != '0000-00-00' and YEAR(glpi_tracking.date) = YEAR(NOW()) and MONTH(glpi_tracking.date) = MONTH(NOW())";
	}
	elseif($quoi == 4) {
		if(!empty($chps) && !empty($value)) {
			if($chps == "glpi_computers.location") {
				$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_computers.location)";
				$query .= " as total from glpi_tracking, glpi_computers where glpi_tracking.computer = glpi_computers.ID and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'  and $chps = '$value'";
				
			}
			else {
				$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.ID)";
				$query .= " as total from glpi_tracking where $chps = '$value' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			}
			$query .= "  and closedate BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY )";
		}
		else {
			$query = "select SUM(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date))/count(glpi_tracking.id) as total from glpi_tracking";
			$query .= " where glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00'";
			$query .= "  and closedate BETWEEN '". $date1 ."' and adddate( '". $date2 ."' , INTERVAL 1 DAY )";
		}
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

//Make a good string from the unix timestamp $sec
//
function toTimeStr($sec)
{
	if($sec < 60) {
		return $sec." Sec";
	}
	elseif($sec < 3600) {
		$min = (int)($sec/60);
		$sec = $sec%60;
		return $min." min ".$sec." Sec";
	}
	elseif($sec <  86400) {
		$heure = (int)($sec/3600);
		$min = (int)(($sec%60)/(60));
		$sec = (int)$sec%60;
		return $heure." Heure ".$min." min ".$sec." Sec";
	}
	else {
		$jour = (int)($sec/86400);
		$heure = (int)(($sec%60)/(3600));
		$min = (int)(($sec%60)/(60));
		$sec = $sec%60;
		return $jour." Jours ".$heure." Heure ".$min." min ".$sec." Sec";
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
?>
