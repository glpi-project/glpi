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


$NEEDED_ITEMS=array("link","knowbase","computer","printer","networking","peripheral","monitor","software","infocom","phone","cartridge","consumable","contract","contact","enterprise");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("link","r");

if (isset($_GET["lID"])){
	$query="SELECT glpi_links.ID as ID, glpi_links.link as link,glpi_links.data as data from glpi_links WHERE glpi_links.ID='".$_GET["lID"]."'";

	$result=$DB->query($query);
	if ($DB->numrows($result)==1){
		$file=$DB->result($result,0,"data");
		$link=$DB->result($result,0,"link");

		$ci=new CommonItem;

		$ci->getFromDB($_GET["type"],$_GET["ID"]);

		// Manage Filename
		if (ereg("\[NAME\]",$link)){
			$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
		}

		if (ereg("\[ID\]",$link)){
			$link=ereg_replace("\[ID\]",$_GET["ID"],$link);
		}


		// Manage File Content

		if (ereg("\[NAME\]",$file)){
			$file=ereg_replace("\[NAME\]",$ci->getName(),$file);
		}

		if (ereg("\[ID\]",$file)){
			$file=ereg_replace("\[ID\]",$_GET["ID"],$file);
		}

		if (ereg("\[SERIAL\]",$file)){
			if (isset($ci->obj->fields["serial"]))
				$file=ereg_replace("\[SERIAL\]",$ci->obj->fields["serial"],$file);
		}
		if (ereg("\[OTHERSERIAL\]",$file)){
			if (isset($ci->obj->fields["otherserial"]))
				$file=ereg_replace("\[OTHERSERIAL\]",$ci->obj->fields["otherserial"],$file);
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
			$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = '".$_GET["ID"]."' AND device_type = '".$_GET["type"]."') ORDER BY logical_number";
			$result2=$DB->query($query2);
			if ($DB->numrows($result2)>0){
				$data2=$DB->fetch_array($result2);
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
