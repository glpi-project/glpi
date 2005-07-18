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

// Original Author of file: Bazile Lebeau :wq
// Purpose of file:
// ----------------------------------------------------------------------
include ("_relpos.php");
include ($phproot."/glpi/includes.php");
include ($phproot."/plugins/ocs/functions/functions.php");
checkAuthentication("admin");
include($phproot."/plugins/ocs/dicts/".$_SESSION["glpilanguage"]."Ocs.php");
include ($phproot."/plugins/ocs/DB_ocs.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_devices.php");

commonHeader($langOcs["title"][0],$_SERVER["PHP_SELF"]);

$dbocs = new DBocs;
$query = "select * from hardware";
$result = $dbocs->query($query) or die($dbocs->error());
while($line = $dbocs->fetch_array($result)) {
		// Insertion des infos générales d'un computer glpi_computers.name et glpi_computers.os
		$idlink = ocs_link($line["DEVICEID"], ocsImportNewComputer($line["NAME"],ocsImportDropdown('glpi_dropdown_os','name',$line["OSNAME"]." ".$line["OSVERSION"])));
		$db = new DB;
		$query2 = "select glpi_id from glpi_ocs_link where ID = '".$idlink."'";
		$result2 = $db->query($query2);
		//print_r($query2)
		//$result2 = $db->query($query2);
		$computer_id = $db->result($result2,0,"glpi_id");
		 
		//Insertion des periphs internes
		//Processeurs : 
		for($i = 0;$i < $line["PROCESSORN"]; $i++) {
			$processor = array();
			$processor["designation"] = $line["PROCESSORT"];
			$proc_id = ocsAddDevice(PROCESSOR_DEVICE,$processor);
			compdevice_add($computer_id,PROCESSOR_DEVICE,$proc_id,$line["PROCESSORS"]);
		}
		
		//Memoire
			$dbocs = new DBocs;
			$query2 = "select * from memories where DEVICEID = '".$line["DEVICEID"]."'";
			$result2 = $dbocs->query($query2);
			if($dbocs->numrows($result2) > 0) {
				while($line2 = $db->fetch_array($result2)) {
					if(!empty($line2["CAPACITY"])) {
						if($line2["DESCRIPTION"]) $ram["designation"] = $line2["DESCRIPTION"];
						else $ram["designation"] = "Unknown";
						$ram["frequence"] =  $line2["SPEED"];
						//TODO when glpi_device_ram.type would be dropdowned
						//ocsImportDropdown();
						//$ram["type"] = $line2["TYPE"];
						//en attendant : 
						$ram["type"] = "SDRAM";
						$ram_id = ocsAddDevice(RAM_DEVICE,$ram);
						compdevice_add($computer_id,RAM_DEVICE,$ram_id,$line2["CAPACITY"]);
					}
				}
			}
		//Carte reseau
			$dbocs = new DBocs;
			$query2 = "select * from networks where DEVICEID = '".$line["DEVICEID"]."'";
			$result2 = $dbocs->query($query2);
			if($dbocs->numrows($result2) > 0) {
				while($line2 = $db->fetch_array($result2)) {
						$network["designation"] = $line2["DESCRIPTION"];
						if(!empty($line2["SPEED"])) $network["bandwidth"] =  $line2["SPEED"];
						$net_id = ocsAddDevice(NETWORK_DEVICE,$network);
						compdevice_add($computer_id,NETWORK_DEVICE,$net_id,$line2["MACADDR"]);
				}
			}
}
glpi_header("list_checked.php");
commonFooter();
?>