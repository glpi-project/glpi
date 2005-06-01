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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/stats/functions.php");


checkAuthentication("normal");
commonHeader($lang["Menu"][6],$_SERVER["PHP_SELF"]);


if(empty($_POST["date1"])) $_POST["date1"] = "";
if(empty($_POST["date2"])) $_POST["date2"] = "";
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
$tmp=$_POST["date1"];
$_POST["date1"]=$_POST["date2"];
$_POST["date2"]=$tmp;
}

echo "<div align='center'><form method=\"post\" name=\"form\" action=\"infocoms.php\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $lang["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name\"submit\" Value=\"". $lang["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$lang["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";




$valeurtot=0;
$valeurnettetot=0;
$valeurnettegraphtot=array();
$valeurgraphtot=array();


function display_infocoms_report($device_type,$begin,$end){
	global $valeurtot,$valeurnettetot, $valeurnettegraphtot, $valeurgraphtot,$lang,$cfg_install;

	$db=new DB;

	$query="SELECT * FROM glpi_infocoms WHERE device_type='".$device_type."'";

	if (!empty($begin)) $query.= " AND (glpi_infocoms.buy_date >= '".$begin."' OR glpi_infocoms.use_date >= '".$begin."' )";
	if (!empty($end)) $query.= " AND (glpi_infocoms.buy_date <= '".$end."' OR glpi_infocoms.use_date <= '".$end."' )";


	$result=$db->query($query);
	if ($db->numrows($result)>0){
		$comp=new CommonItem();
		$comp->getFromDB($device_type,0);
		
		echo "<h2>".$comp->getType()."</h2>";
		
		echo "<table class='tab_cadre'><tr><th>".$lang["computers"][7]."</th><th>".$lang["financial"][21]."</th><th>".$lang["financial"][81]."</th><th>".$lang["financial"][14]."</th><th>".$lang["financial"][76]."</th></tr>";
	
	
		$valeursoustot=0;
		$valeurnettesoustot=0;
		$valeurnettegraph=array();
		$valeurgraph=array();
		
		while ($line=$db->fetch_array($result)){
			
			$comp->getFromDB($device_type,$line["FK_device"]);

			if ($comp->obj->fields["is_template"]==0){
				$valeursoustot+=$line["value"];	
				$valeurnette=TableauAmort($line["amort_type"],$line["value"],$line["amort_time"],$line["amort_coeff"],$line["buy_date"],$line["use_date"],$cfg_install["date_fiscale"],"n");
				$tmp=TableauAmort($line["amort_type"],$line["value"],$line["amort_time"],$line["amort_coeff"],$line["buy_date"],$line["use_date"],$cfg_install["date_fiscale"],"all");
			
				if (is_array($tmp)&&count($tmp)>0)
				foreach ($tmp["annee"] as $key => $val){
					if (!isset($valeurnettegraph[$val])) $valeurnettegraph[$val]=0;
					$valeurnettegraph[$val]+=$tmp["vcnetfin"][$key];
				}
				if ($line["buy_date"]!="0000-00-00"){
					$year=substr($line["buy_date"],0,4);
					if (!isset($valeurgraph[$year])) $valeurgraph[$year]=0;
					$valeurgraph[$year]+=$line["value"];
				}
				
			
				$valeurnettesoustot+=$valeurnette;	
				echo "<tr class='tab_bg_1'><td>".$comp->getName()."</td><td>".$line["value"]."</td><td>$valeurnette</td><td>".$line["buy_date"]."</td><td>".$line["use_date"]."</td></tr>";
	
			}

		}	
	$valeurtot+=$valeursoustot;
	$valeurnettetot+=$valeurnettesoustot;

	if (count($valeurnettegraph)>0){
	
		echo "<tr><td colspan='5'  align='center'>";
		ksort($valeurnettegraph); 

		$valeurnettegraphdisplay=array_map('round',$valeurnettegraph);

		foreach ($valeurnettegraph as $key => $val) {
			if (!isset($valeurnettegraphtot[$key])) $valeurnettegraphtot[$key]=0;
			$valeurnettegraphtot[$key]+=$valeurnettegraph[$key];
		}

		graphBy($valeurnettegraphdisplay,$lang["financial"][81],"",0,"year");

		echo "</td></tr>";
	}
	
	if (count($valeurgraph)>0){
		echo "<tr><td colspan='5' align='center'>";
	
		ksort($valeurgraph); 

		$valeurgraphdisplay=array_map('round',$valeurgraph);

		foreach ($valeurgraph as $key => $val) {
			if (!isset($valeurgraphtot[$key])) $valeurgraphtot[$key]=0;
			$valeurgraphtot[$key]+=$valeurgraph[$key];
		}

		graphBy($valeurgraphdisplay,$lang["financial"][21],"",0,"year");

		echo "</td></tr>";
	}
	echo "</table>";

	}
}

echo "<table>";
echo "<tr><td>";
display_infocoms_report(COMPUTER_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td><td valign='top'>";
display_infocoms_report(MONITOR_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td></tr>";
echo "<tr><td>";
display_infocoms_report(NETWORKING_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td><td valign='top'>";
display_infocoms_report(PRINTER_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td></tr>";
echo "<tr><td>";
display_infocoms_report(SOFTWARE_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td><td valign='top'>";
display_infocoms_report(PERIPHERAL_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td></tr>";
echo "</table>";


if (count($valeurgraphtot)>0){

	echo "<div align='center'><h1>".$lang["software"][21].": ".$lang["financial"][21]."=$valeurtot - ".$lang["financial"][81]."=$valeurnettetot</h1></div>";

	$valeurnettegraphtotdisplay=array_map('round',$valeurnettegraphtot);
	graphBy($valeurnettegraphtotdisplay,$lang["financial"][81],"",0,"year");
	$valeurgraphtotdisplay=array_map('round',$valeurgraphtot);
	graphBy($valeurgraphtotdisplay,$lang["financial"][21],"",0,"year");
}
?>