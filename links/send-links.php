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

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_links.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_knowbase.php");
include ($phproot . "/glpi/includes_cartridges.php");
include ($phproot . "/glpi/includes_consumables.php");

checkAuthentication("normal");

if (isset($_GET["lID"])){
	$db=new DB;
	$query="SELECT glpi_links.ID as ID, glpi_links.name as name,glpi_links.data as data from glpi_links WHERE glpi_links.ID='".$_GET["lID"]."'";

	$result=$db->query($query);
	if ($db->numrows($result)==1){
		$file=$db->result($result,0,"data");
		$link=$db->result($result,0,"name");

		$ci=new CommonItem;

		$ci->getFromDB($_GET["type"],$_GET["ID"]);

			if (ereg("\[NAME\]",$file)){
				$file=ereg_replace("\[NAME\]",$ci->getName(),$file);
			}

			if (ereg("\[ID\]",$file)){
				$file=ereg_replace("\[ID\]",$_GET["ID"],$file);
			}
		
			if (ereg("\[LOCATIONID\]",$file)){
				if (isset($ci->obj->fields["location"]))
					$file=ereg_replace("\[LOCATIONID\]",$ci->obj->fields["location"],$file);
			}
			if (ereg("\[LOCATION\]",$file)){
				if (isset($ci->obj->fields["location"]))
					$file=ereg_replace("\[LOCATION\]",getDropdownName("glpi_dropdown_locations",$ci->obj->fields["location"]),$file);
			}
			if (ereg("\[NETWORK\]",$file)){
				if (isset($ci->obj->fields["network"]))
					$file=ereg_replace("\[NETWORK\]",getDropdownName("glpi_dropdown_network",$ci->obj->fields["network"]),$file);
			}
			if (ereg("\[DOMAIN\]",$file)){
			if (isset($ci->obj->fields["domain"]))
				$file=ereg_replace("\[DOMAIN\]",getDropdownName("glpi_dropdown_domain",$ci->obj->fields["domain"]),$file);
			}
			$ipmac=array();
			$i=0;
			if (ereg("\[IP\]",$file)||ereg("\[MAC\]",$file)){
				$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = ".$_GET["ID"]." AND device_type = ".$_GET["type"].") ORDER BY logical_number";
				$result2=$db->query($query2);
				if ($db->numrows($result2)>0){
					$data2=$db->fetch_array($result2);
					$ipmac[$i]['ifaddr']=$data2["ifaddr"];
					$ipmac[$i]['ifmac']=$data2["ifmac"];
				}
			}

			if (ereg("\[IP\]",$file)||ereg("\[MAC\]",$file)){
		
			if (count($ipmac)>0){
				foreach ($ipmac as $key => $val){
					$file=ereg_replace("\[IP\]",$val['ifaddr'],$file);
					$file=ereg_replace("\[MAC\]",$val['ifmac'],$file);
				}
			}
			}
			header("Content-disposition: filename=\"$link\"");
			$mime="application/scriptfile";

	     	header("Content-type: ".$mime);
	     	header('Pragma: no-cache');
	     	header('Expires: 0');

			// Pour que les \x00 ne devienne pas \0
			$mc=get_magic_quotes_runtime();
			if ($mc) @set_magic_quotes_runtime(0); 

			echo $file;

			if ($mc) @set_magic_quotes_runtime($mc); 

	}
}	
?>