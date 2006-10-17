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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// FUNCTIONS links



/**
 * Print the HTML array for device on link
 *
 * Print the HTML array for device on link for link $instID
 *
 *@param $instID array : Link identifier.
 *
 *@return Nothing (display)
 *
 **/
function showLinkDevice($instID) {
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("link","r")) return false;
	$canedit= haveRight("link","w");

	$query = "SELECT * from glpi_links_device WHERE FK_links='$instID' ORDER BY device_type";
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/link.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'>".$LANG["links"][4].":</th></tr>";
	echo "<tr><th>".$LANG["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$DB->result($result, $i, "ID");
		$device_type=$DB->result($result, $i, "device_type");
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'>".getDeviceTypeName($device_type)."</td>";
		echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletedevice=deletedevice&amp;ID=$ID'><b>".$LANG["buttons"][6]."</b></a></td></tr>";
		$i++;
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='lID' value='$instID'>";
		dropdownDeviceType("device_type",0);
	
		echo "&nbsp;&nbsp;<input type='submit' name='adddevice' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
	
		echo "</tr>";
	}

	echo "</table></div></form>"    ;

}

function deleteLinkDevice($ID){

	global $DB;
	$query="DELETE FROM glpi_links_device WHERE ID= '$ID';";
	$result = $DB->query($query);
}

function addLinkDevice($tID,$lID){
	global $DB;
	if ($tID>0&&$lID>0){

		$query="INSERT INTO glpi_links_device (device_type,FK_links ) VALUES ('$tID','$lID');";
		$result = $DB->query($query);
	}
}

function showLinkOnDevice($type,$ID){
	global $DB,$LANG,$CFG_GLPI;

	if (!haveRight("link","r")) return false;

	$query="SELECT glpi_links.ID as ID, glpi_links.link as link, glpi_links.name as name , glpi_links.data as data from glpi_links INNER JOIN glpi_links_device ON glpi_links.ID= glpi_links_device.FK_links WHERE glpi_links_device.device_type='$type' ORDER BY glpi_links.name";

	$result=$DB->query($query);

	echo "<br>";

	$ci=new CommonItem;
	if ($DB->numrows($result)>0){
		echo "<div align='center'><table class='tab_cadre'><tr><th>".$LANG["title"][33]."</th></tr>";

		while ($data=$DB->fetch_assoc($result)){

			$name=$data["name"];
			if (empty($name))
				$name=$data["link"];

			$link=$data["link"];
			$file=trim($data["data"]);
			if (empty($file)){

				$ci->getFromDB($type,$ID);
				if (ereg("\[NAME\]",$link)){
					$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
				}
				if (ereg("\[ID\]",$link)){
					$link=ereg_replace("\[ID\]",$ID,$link);
				}

				if (ereg("\[SERIAL\]",$link)){
					if (isset($ci->obj->fields["serial"]))
						$link=ereg_replace("\[SERIAL\]",$ci->obj->fields["serial"],$link);
				}
				if (ereg("\[OTHERSERIAL\]",$link)){
					if (isset($ci->obj->fields["otherserial"]))
						$link=ereg_replace("\[OTHERSERIAL\]",$ci->obj->fields["otherserial"],$link);
				}

				if (ereg("\[LOCATIONID\]",$link)){
					if (isset($ci->obj->fields["location"]))
						$link=ereg_replace("\[LOCATIONID\]",$ci->obj->fields["location"],$link);
				}

				if (ereg("\[LOCATION\]",$link)){
					if (isset($ci->obj->fields["location"]))
						$link=ereg_replace("\[LOCATION\]",getDropdownName("glpi_dropdown_locations",$ci->obj->fields["location"]),$link);
				}
				if (ereg("\[NETWORK\]",$link)){
					if (isset($ci->obj->fields["network"]))
						$link=ereg_replace("\[NETWORK\]",getDropdownName("glpi_dropdown_network",$ci->obj->fields["network"]),$link);
				}
				if (ereg("\[DOMAIN\]",$link)){
					if (isset($ci->obj->fields["domain"]))
						$link=ereg_replace("\[DOMAIN\]",getDropdownName("glpi_dropdown_domain",$ci->obj->fields["domain"]),$link);
				}
				$ipmac=array();
				$i=0;
				if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
					$query2 = "SELECT ifaddr,ifmac FROM glpi_networking_ports WHERE (on_device = $ID AND device_type = $type) ORDER BY logical_number";
					$result2=$DB->query($query2);
					if ($DB->numrows($result2)>0)
						while ($data2=$DB->fetch_array($result2)){
							$ipmac[$i]['ifaddr']=$data2["ifaddr"];
							$ipmac[$i]['ifmac']=$data2["ifmac"];
							$i++;
						}
				}

				if (ereg("\[IP\]",$link)||ereg("\[MAC\]",$link)){
					// Add IP/MAC internal switch
					if ($type==NETWORKING_TYPE){
						$tmplink=$link;
						$tmplink=ereg_replace("\[IP\]",$ci->obj->fields["ifaddr"],$tmplink);
						$tmplink=ereg_replace("\[MAC\]",$ci->obj->fields['ifmac'],$tmplink);
						echo "<tr class='tab_bg_2'><td><a target='_blank' href='$tmplink'>$tmplink</a></td></tr>";
					}

					if (count($ipmac)>0){
						foreach ($ipmac as $key => $val){
							$tmplink=$link;
							$tmplink=ereg_replace("\[IP\]",$val['ifaddr'],$tmplink);
							$tmplink=ereg_replace("\[MAC\]",$val['ifmac'],$tmplink);
							echo "<tr class='tab_bg_2'><td><a target='_blank' href='$tmplink'>$tmplink</a></td></tr>";
						}
					}
				} else 
					echo "<tr class='tab_bg_2'><td><a target='_blank' href='$link'>$name</a></td></tr>";

			} else {// File Generated Link
				$link=$data['name'];		
				$ci->getFromDB($type,$ID);

				// Manage Filename
				if (ereg("\[NAME\]",$link)){
					$link=ereg_replace("\[NAME\]",$ci->getName(),$link);
				}

				if (ereg("\[ID\]",$link)){
					$link=ereg_replace("\[ID\]",$_GET["ID"],$link);
				}

				echo "<tr class='tab_bg_2'><td><a href='".$CFG_GLPI["root_doc"]."/front/link.send.php?lID=".$data['ID']."&type=$type&ID=$ID' target='_blank'>".$name."</a></td></tr>";
			}


		}
		echo "</table></div>";
	} else echo "<div align='center'><b>".$LANG["links"][7]."</b></div>";

}

?>
