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
// Original Author of file: Mustapha Saddalah et Bazile Lebeau
// Purpose of file:
// ----------------------------------------------------------------------
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_setup.php");
require ("functions.php");


checkAuthentication("normal");

commonHeader($lang["title"][11],$_SERVER["PHP_SELF"]);

echo "<div align='center'><p><b>".$lang["stats"][19]."</p></b>";

if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

if(empty($_POST["dropdown"])) $_POST["dropdown"] = "glpi_type_computers";

echo "<form method=\"post\" name=\"form\" action=\"stat_lieux.php\">";

echo "<table class='tab_cadre'><tr class='tab_bg_2'><td rowspan='2'>";
echo "<select name=\"dropdown\">";
echo "<option value=\"glpi_type_computers\" ".($_POST["dropdown"]=="glpi_type_computers"?"selected":"").">".$lang["computers"][8]."</option>";
echo "<option value=\"glpi_dropdown_os\" ".($_POST["dropdown"]=="glpi_dropdown_os"?"selected":"").">".$lang["computers"][9]."</option>";
echo "<option value=\"glpi_dropdown_locations\" ".($_POST["dropdown"]=="glpi_dropdown_locations"?"selected":"").">".$lang["stats"][21]."</option>";
echo "<option value=\"glpi_device_moboard\" ".($_POST["dropdown"]=="glpi_device_moboard"?"selected":"").">".$lang["computers"][35]."</option>";
echo "<option value=\"glpi_device_processor\" ".($_POST["dropdown"]=="glpi_device_processor"?"selected":"").">".$lang["setup"][7]."</option>";
echo "<option value=\"glpi_device_gfxcard\" ".($_POST["dropdown"]=="glpi_device_gfxcard"?"selected":"").">".$lang["computers"][34]."</option>";
echo "<option value=\"glpi_device_hdd\" ".($_POST["dropdown"]=="glpi_device_hdd"?"selected":"").">".$lang["computers"][36]."</option>";
echo "</select></td>";

echo "<td align='right'>";
echo $lang["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";


if(is_dropdown_stat($_POST["dropdown"])) {
	//recuperation des differents lieux d'interventions
	//Get the distincts intervention location
	$type = getNbIntervDropdown($_POST["dropdown"]);

	echo "<div align ='center'>";

	if (is_array($type))
	{
 //affichage du tableau
		 echo "<table class='tab_cadre2' cellpadding='5' >";
		 $champ=str_replace("locations","location",str_replace("glpi_","",str_replace("dropdown_","",str_replace("_computers","",$_POST["dropdown"]))));
		 echo "<tr><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";

		//Pour chaque lieu on affiche
		//for each location displays
		foreach($type as $key) {
			$query="SELECT count(*) FROM glpi_computers WHERE $champ='".$key["ID"]."'";
			$db=new DB;
			if ($result=$db->query($query))
				$count=$db->result($result,0,0);
			else $count=0; 
			echo "<tr class='tab_bg_1'>";
			echo "<td>".getDropdownName($_POST["dropdown"],$key["ID"]) ."&nbsp;($count)</td>";
			//le nombre d'intervention
			//the number of intervention
			echo "<td>".getNbinter(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"] )."</td>";
			//le nombre d'intervention resolues
			//the number of resolved intervention
			echo "<td>".getNbresol(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//Le temps moyen de resolution
			//The average time to resolv
			echo "<td>".getResolAvg(4,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"],$_POST["date1"],$_POST["date2"])."</td>";
			//Le temps moyen de l'intervention réelle
			//The average realtime to resolv
			echo "<td>".getRealAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
			//Le temps total de l'intervention réelle
			//The total realtime to resolv
			echo "<td>".getRealTotal(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";
			//
			//
			echo "<td>".getFirstActionAvg(1,"glpi_computers.".getDropdownNameFromTable($_POST["dropdown"]),$key["ID"])."</td>";

			echo "</tr>";
		}
	echo "</table>";
	} else {
		echo $lang["stats"][23];
	}
} else {

//---------------------- DEVICE ------------------------------------------------------
	echo "<table class='tab_cadre2' cellpadding='5' >";
	echo "<tr><th>&nbsp;</th><th>".$lang["stats"][22]."</th><th>".$lang["stats"][14]."</th><th>".$lang["stats"][15]."</th><th>".$lang["stats"][25]."</th><th>".$lang["stats"][27]."</th><th>".$lang["stats"][30]."</th></tr>";
	//print_r($_POST["dropdown"]);
	$device_type = constant(strtoupper($_POST["dropdown"]));
	//select devices IDs (table row)
	$query = "select ID, designation from ".$_POST["dropdown"]." order by designation";
	$result = $db->query($query);
	while($line = $db->fetch_array($result)) {
		
		//select computers IDs that are using this device;
		$query2 = "SELECT glpi_computers.ID as compid FROM glpi_computers, glpi_computer_device WHERE glpi_computers.ID = glpi_computer_device.FK_computers  ";
		$query2.= " AND is_template <> '1' AND ";
		$query2.= "glpi_computer_device.device_type = '".$device_type."' ";
		$query2.=  "AND glpi_computer_device.FK_device = '".$line["ID"]."'";
		$result2 = $db->query($query2);
		$designation = $line["designation"];
		$i = 0;
		$j = 0;
		while($line2 = $db->fetch_array($result2)) {
			//select ID of tracking using this computer id
			//nbintervresolv
			$query3 = "select ID from glpi_tracking where device_type = '".COMPUTER_TYPE."' and computer = '".$line2["compid"]."'";
			if(!empty($_POST["date1"]) && $date1!="") $query3.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query3.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$result3 = $db->query($query3);
			while ($line3 = $db->fetch_array($result3)) {
				$nbinterv[$i] = $line3["ID"];
				$i++;
			}
			//nbinterv
			$query4 = $query3." AND status = 'old'";
			if(!empty($_POST["date1"]) && $date1!="") $query4.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query4.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$result4 = $db->query($query4);
			while($line4 = $db->fetch_array($result4)) {
				$nbintervresolv[$j] = $line4["ID"];
				$j++;
			}
			//resolvavg
			$query5 = "SELECT AVG(UNIX_TIMESTAMP(glpi_tracking.closedate)-UNIX_TIMESTAMP(glpi_tracking.date)) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and computer = '".$line2["compid"]."' AND status = 'old' AND glpi_tracking.closedate != '0000-00-00'";
			if(!empty($_POST["date1"]) && $date1!="") $query5.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query5.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$result5 = $db->query($query5);
			$resolvavg=0;
			while($line5 = $db->fetch_array($result5)) {
				$resolvavg += $line5["total"];
			}
			//realavg
			$query6 = "SELECT AVG(glpi_tracking.realtime) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and computer = '".$line2["compid"]."' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0";
			if(!empty($_POST["date1"]) && $date1!="") $query6.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query6.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$result6 = $db->query($query6);
			$realavg=0;
			while($line6 = $db->fetch_array($result6)) {
				$realavg += $line6["total"];
			}
			//realtotal
			$query7 = "select SUM(glpi_tracking.realtime) as total from glpi_tracking where device_type = '".COMPUTER_TYPE."' and computer = '".$line2["compid"]."' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0";
			if(!empty($_POST["date1"]) && $date1!="") $query7.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query7.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$result7 = $db->query($query7);
			$realtotal=0;
			while($line7 = $db->fetch_array($result7)) {
				$realtotal += $line7["total"];
			}
			//realfirst 
			$query8 = "select glpi_tracking.ID,  MIN(UNIX_TIMESTAMP(glpi_followups.date)-UNIX_TIMESTAMP(glpi_tracking.date)) as first from glpi_tracking LEFT JOIN glpi_followups ON (glpi_followups.tracking = glpi_tracking.ID) where device_type = '".COMPUTER_TYPE."' and computer = '".$line2["compid"]."' and glpi_tracking.status = 'old' and glpi_tracking.closedate != '0000-00-00' AND glpi_tracking.realtime > 0 ";
			if(!empty($_POST["date1"]) && $date1!="") $query8.= " and glpi_tracking.date >= '". $date1 ."' ";
			if(!empty($_POST["date2"]) && $date2!="") $query8.= " and glpi_tracking.date <= adddate( '". $date2 ."' , INTERVAL 1 DAY ) ";
			$query8 .= "group by glpi_tracking.id";
			$result8 = $db->query($query8);
			$realfirst=0;
			while($line8 = $db->fetch_array($result8)) {
				$realfirst += $line8["first"];
			}
		}
		//print row
		echo "<tr class='tab_bg_1'>";
		//first column name of the device
		echo "<td>".$designation."</td>";
		//second column count nb interv
		echo "<td>".count($nbinterv)."</td>";
		//third column nb resolved interventions
		echo "<td>".count($nbintervresolv)."</td>";
		//forth column
		echo "<td>".toTimeStr(floor($resolvavg))."</td>";
		//5th column
		echo "<td>".toTimeStr(floor($realavg))."</td>";
		//6th column
		echo "<td>".toTimeStr(floor($realtotal))."</td>";
		//7th collumn
		if($realfirst < $realtotal && $realfirst != 0) { 
			echo "<td>".toTimeStr(floor($realfirst))."</td>";
		} else {
			echo "<td>".toTimeStr(floor($realtotal))."</td>";
		}
		$nbintervresolv = array();
		$nbinterv = array();
		$resolvavg = 0;
		$realavg = 0;
		$realtotal = 0;
		$realfirst = 0;
		echo "</tr>";
		
		
	}
	echo "</table>";
}


echo "</div>"; 


commonFooter();
?>
