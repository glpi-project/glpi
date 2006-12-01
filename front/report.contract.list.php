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


include ("_relpos.php");

$NEEDED_ITEMS=array("contract","infocom");
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

commonHeader($lang["title"][16],$_SERVER['PHP_SELF']);

$item_db_name[COMPUTER_TYPE] = "glpi_computers";
$item_db_name[PRINTER_TYPE] = "glpi_printers";
$item_db_name[MONITOR_TYPE] = "glpi_monitors";
$item_db_name[NETWORKING_TYPE] = "glpi_networking";
$item_db_name[PERIPHERAL_TYPE] = "glpi_peripherals";
$item_db_name[SOFTWARE_TYPE] = "glpi_software";
$item_db_name[PHONE_TYPE] = "glpi_phones";


# Titre
echo "<div align='center'><big><strong>".$lang["reports"][4]."</strong></big><br><br>";

# Construction  la requete, et appel de la fonction affichant les valeurs.
if(isset($_POST["item_type"][0])&&$_POST["item_type"][0] != '0')
{

	foreach($_POST["item_type"] as $key => $val){
		$query = "select ".$item_db_name[$val].".name as itemname, ".$item_db_name[$val].".deleted as itemdeleted, ".$item_db_name[$val].".*, glpi_contracts.*, glpi_infocoms.* from glpi_contract_device ";
		$query.= "INNER JOIN glpi_contracts ON glpi_contract_device.FK_contract=glpi_contracts.ID ";
		$query.= "INNER JOIN ".$item_db_name[$val]." ON glpi_contract_device.device_type='$val' AND ".$item_db_name[$val].".ID =  glpi_contract_device.FK_device ";
		$query.= "LEFT JOIN glpi_infocoms ON glpi_infocoms.device_type='$val' AND ".$item_db_name[$val].".ID =  glpi_infocoms.FK_device ";


		$query.=" WHERE ".$item_db_name[$val].".is_template ='0' ";

		if(isset($_POST["annee"][0])&&$_POST["annee"][0] != 'toutes')
		{
			$query.=" AND ('1'='0' ";
			foreach ($_POST["annee"] as $key2 => $val2){
				$query.= " OR YEAR(glpi_infocoms.buy_date) = '".$val2."'";
				$query.= " OR YEAR(glpi_contracts.begin_date) = '".$val2."'";
			}
			$query.= ")";
		}
		$query.= " order by ".$item_db_name[$val].".name asc";
		//		echo $query;
		report_perso($item_db_name[$val],$query);
	}
}
else
{
	$query=array();
	foreach ($item_db_name as $key => $val)
	{
		$query[$key] = "select  ".$item_db_name[$key].".name as itemname, ".$item_db_name[$key].".deleted as itemdeleted, ".$item_db_name[$key].".*, glpi_contracts.*, glpi_infocoms.* from glpi_contract_device ";
		$query[$key].= "INNER JOIN glpi_contracts ON glpi_contract_device.FK_contract=glpi_contracts.ID ";
		$query[$key].= "INNER JOIN ".$item_db_name[$key]." ON glpi_contract_device.device_type='$key' AND ".$item_db_name[$key].".ID =  glpi_contract_device.FK_device ";
		$query[$key].= "LEFT JOIN glpi_infocoms ON glpi_infocoms.device_type='$key' AND ".$item_db_name[$key].".ID =  glpi_infocoms.FK_device ";

		$query[$key].=" WHERE ".$item_db_name[$key].".is_template ='0' ";

		if(isset($_POST["annee"][0])&&$_POST["annee"][0] != 'toutes')
		{
			$query[$key].=" AND ('1'='0' ";
			foreach ($_POST["annee"] as $key2 => $val2){
				$query[$key].= " OR YEAR(glpi_infocoms.buy_date) = '".$val2."'";
				$query[$key].= " OR YEAR(glpi_contracts.begin_date) = '".$val2."'";
			}
			$query[$key].= ")";
		}
		//		echo $query[$key];
		report_perso($item_db_name[$key],$query[$key]);
	}		
}

echo "</div>";

commonFooter();
?>
