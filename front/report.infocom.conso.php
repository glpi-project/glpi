<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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




$NEEDED_ITEMS=array("contract","infocom","software","cartridge","consumable","stat");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG["Menu"][6],$_SERVER['PHP_SELF'],"utils","report");


if(empty($_POST["date1"])&&empty($_POST["date2"])) {
	$year=date("Y")-1;
	$_POST["date1"]=date("Y-m-d",mktime(1,0,0,date("m"),date("d"),$year));

	$_POST["date2"]=date("Y-m-d");
}
if ($_POST["date1"]!=""&&$_POST["date2"]!=""&&strcmp($_POST["date2"],$_POST["date1"])<0){
	$tmp=$_POST["date1"];
	$_POST["date1"]=$_POST["date2"];
	$_POST["date2"]=$tmp;
}

echo "<div align='center'><form method=\"post\" name=\"form\" action=\"".$_SERVER['PHP_SELF']."\">";
echo "<table class='tab_cadre'><tr class='tab_bg_2'><td align='right'>";
echo $LANG["search"][8]." :</td><td>";
showCalendarForm("form","date1",$_POST["date1"]);
echo "</td><td rowspan='2' align='center'><input type=\"submit\" class='button' name=\"submit\" Value=\"". $LANG["buttons"][7] ."\" /></td></tr>";
echo "<tr class='tab_bg_2'><td align='right'>".$LANG["search"][9]." :</td><td>";
showCalendarForm("form","date2",$_POST["date2"]);
echo "</td></tr>";
echo "</table></form></div>";




$valeurtot=0;
$valeurnettetot=0;
$valeurnettegraphtot=array();
$valeurgraphtot=array();


function display_infocoms_report($device_type,$begin,$end){
	global $DB,$valeurtot,$valeurnettetot, $valeurnettegraphtot, $valeurgraphtot,$LANG,$CFG_GLPI,$LINK_ID_TABLE;

	$query="SELECT glpi_infocoms.* FROM glpi_infocoms INNER JOIN ".$LINK_ID_TABLE[$device_type]." ON (".$LINK_ID_TABLE[$device_type].".ID = glpi_infocoms.FK_device AND glpi_infocoms.device_type='".$device_type."') ";
	
	switch ($device_type){
		case CONSUMABLE_ITEM_TYPE :
			$query.=" INNER JOIN glpi_consumables_type ON (glpi_consumables.FK_glpi_consumables_type = glpi_consumables_type.ID) ".getEntitiesRestrictRequest("WHERE","glpi_consumables_type");
		break;
		case CARTRIDGE_ITEM_TYPE :
			$query.=" INNER JOIN glpi_cartridges_type ON (glpi_cartridges.FK_glpi_cartridges_type = glpi_cartridges_type.ID) ".getEntitiesRestrictRequest("WHERE","glpi_cartridges_type");
		break;
		case LICENSE_TYPE :
			$query.=" INNER JOIN glpi_software ON (glpi_licenses.sID = glpi_software.ID) ".getEntitiesRestrictRequest("WHERE","glpi_software");
		break;
	
	}

	if (!empty($begin)) $query.= " AND (glpi_infocoms.buy_date >= '".$begin."' OR glpi_infocoms.use_date >= '".$begin."' )";
	if (!empty($end)) $query.= " AND (glpi_infocoms.buy_date <= '".$end."' OR glpi_infocoms.use_date <= '".$end."' )";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		$comp=new CommonItem();
		$comp->getFromDB($device_type,0);

		echo "<h2>".$comp->getType()."</h2>";

		echo "<table class='tab_cadre'>";	

		$valeursoustot=0;
		$valeurnettesoustot=0;
		$valeurnettegraph=array();
		$valeurgraph=array();

		while ($line=$DB->fetch_array($result)){

			if ($device_type==LICENSE_TYPE){
				$comp->getFromDB($device_type,$line["FK_device"]);
				if ($comp->obj->fields["serial"]=="global"){
					$line["value"]*=getInstallionsForLicense($line["FK_device"]);
				}
			}
			if ($line["value"]>0) $valeursoustot+=$line["value"];	

			$valeurnette=TableauAmort($line["amort_type"],$line["value"],$line["amort_time"],$line["amort_coeff"],$line["buy_date"],$line["use_date"],$CFG_GLPI["date_fiscale"],"n");
			$tmp=TableauAmort($line["amort_type"],$line["value"],$line["amort_time"],$line["amort_coeff"],$line["buy_date"],$line["use_date"],$CFG_GLPI["date_fiscale"],"all");

			if (is_array($tmp)&&count($tmp)>0)
				foreach ($tmp["annee"] as $key => $val){
					if ($tmp["vcnetfin"][$key]>0){
						if (!isset($valeurnettegraph[$val])) $valeurnettegraph[$val]=0;
						$valeurnettegraph[$val]+=$tmp["vcnetdeb"][$key];
					}
				}
			if ($line["buy_date"]!="0000-00-00"){
				$year=substr($line["buy_date"],0,4);
				if ($line["value"]>0){
					if (!isset($valeurgraph[$year])) $valeurgraph[$year]=0;
					$valeurgraph[$year]+=$line["value"];
				}
			}


			$valeurnettesoustot+=str_replace(" ","",$valeurnette);	


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

			graphBy($valeurnettegraphdisplay,$LANG["financial"][81],"",0,"year");

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

			graphBy($valeurgraphdisplay,$LANG["financial"][21],"",0,"year");

			echo "</td></tr>";
		}
		echo "</table>";

	}
}

echo "<table width='90%'>";
echo "<tr><td align='center' valign='top'>";
display_infocoms_report(CONSUMABLE_ITEM_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td><td  align='center' valign='top'>";
display_infocoms_report(CARTRIDGE_ITEM_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td></tr>";
echo "<tr><td>";
display_infocoms_report(LICENSE_TYPE,$_POST["date1"],$_POST["date2"]);
echo "</td><td valign='top'>&nbsp;";

echo "</td></tr>";
echo "</table>";



echo "<div align='center'><h3>".$LANG["common"][33].": ".$LANG["financial"][21]."=".number_format($valeurtot,$CFG_GLPI["decimal_number"],"."," ")." - ".$LANG["financial"][81]."=".number_format($valeurnettetot,$CFG_GLPI["decimal_number"],"."," ")."</h3></div>";

if (count($valeurnettegraphtot)>0){
	$valeurnettegraphtotdisplay=array_map('round',$valeurnettegraphtot);
	graphBy($valeurnettegraphtotdisplay,$LANG["financial"][81],"",0,"year");
}
if (count($valeurgraphtot)>0){	
	$valeurgraphtotdisplay=array_map('round',$valeurgraphtot);
	graphBy($valeurgraphtotdisplay,$LANG["financial"][21],"",0,"year");
}

commonFooter();
?>
