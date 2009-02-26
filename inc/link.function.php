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

	$link = new Link();
	if ($instID > 0){
		$link->check($instID,'r');
	} else {
		// Create item 
		$link->check(-1,'w');
		$use_cache=false;
		$link->getEmpty();
	} 

	$canedit=$link->can($instID,'w');
	$canrecu=$link->can($instID,'recursive');

	if (!haveRight("link","r")) return false;
	//$canedit= haveRight("link","w");
	$ci = new CommonItem();
	$query = "SELECT * FROM glpi_links_device WHERE FK_links='$instID' ORDER BY device_type";
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;


	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/link.form.php\">";
	echo "<div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='2'>".$LANG["links"][4].":</th></tr>";
	echo "<tr><th>".$LANG["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$DB->result($result, $i, "ID");
		$ci->setType($DB->result($result, $i, "device_type"));
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center'>".$ci->getType()."</td>";
		echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER['PHP_SELF']."?deletedevice=deletedevice&amp;ID=$ID&amp;lID=$instID'><strong>".$LANG["buttons"][6]."</strong></a></td></tr>";
		$i++;
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
		echo "<div class='software-instal'><input type='hidden' name='lID' value='$instID'>";

		$types=$CFG_GLPI["helpdesk_types"];
		$types[]=SOFTWARE_TYPE;
		$types[]=CARTRIDGE_TYPE;
		$types[]=CONSUMABLE_TYPE;
		$types[]=ENTERPRISE_TYPE;
		$types[]=CONTACT_TYPE;
		$types[]=CONTRACT_TYPE;
		dropdownDeviceTypes("device_type",0,$types);
	
		echo "&nbsp;&nbsp;<input type='submit' name='adddevice' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</div>";
		echo "</td>";
	
		echo "</tr>";
	}

	echo "</table></div></form>"    ;

}

/**
 * Delete an item type for a link
 *
 * @param $ID integer : glpi_links_device ID
 */
function deleteLinkDevice($ID){

	global $DB;
	$query="DELETE FROM glpi_links_device WHERE ID= '$ID';";
	$result = $DB->query($query);
}

/**
 * Add an item type to a link
 *
 * @param $tID integer : item type
 * @param $lID integer : link ID
 */
function addLinkDevice($tID,$lID){
	global $DB;
	if ($tID>0&&$lID>0){

		$query="INSERT INTO glpi_links_device (device_type,FK_links ) VALUES ('$tID','$lID');";
		$result = $DB->query($query);
	}
}

/**
 * Show Links for an item
 *
 * @param $type integer : item type
 * @param $ID integer : item ID
 */
function showLinkOnDevice($type,$ID){
	global $DB,$LANG,$CFG_GLPI;

	$commonitem = new CommonItem;
	$commonitem->getFromDB($type,$ID);
	
	if (!haveRight("link","r")) return false;

	$query="SELECT glpi_links.ID as ID, glpi_links.link as link, glpi_links.name as name , glpi_links.data as data 
		FROM glpi_links 
		INNER JOIN glpi_links_device ON glpi_links.ID= glpi_links_device.FK_links
		WHERE glpi_links_device.device_type='$type' " .
			getEntitiesRestrictRequest(" AND","glpi_links","FK_entities",$commonitem->obj->fields["FK_entities"],true).
		" ORDER BY glpi_links.name";

	$result=$DB->query($query);
	
	$ci=new CommonItem;
	if ($DB->numrows($result)>0){
		echo "<div class='center'><table class='tab_cadre'><tr><th>".$LANG["title"][33]."</th></tr>";

		while ($data=$DB->fetch_assoc($result)){

			$name=$data["name"];
			if (empty($name))
				$name=$data["link"];

			$link=$data["link"];
			$file=trim($data["data"]);
			if (empty($file)){

				$ci->getFromDB($type,$ID);
				if (strstr($link,"[NAME]")){
					$link=str_replace("[NAME]",$ci->getName(),$link);
				}
				if (strstr($link,"[ID]")){
					$link=str_replace("[ID]",$ID,$link);
				}

				if (strstr($link,"[LOGIN]")){
					if (isset($_SESSION["glpiname"])){
						$link=str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
					}
				}

				if (strstr($link,"[SERIAL]")){
					if ($tmp=$ci->getField('serial')){
						$link=str_replace("[SERIAL]",$tmp,$link);
					}
				}
				if (strstr($link,"[OTHERSERIAL]")){
					if ($tmp=$ci->getField('otherserial')){
						$link=str_replace("[OTHERSERIAL]",$tmp,$link);
					}
				}

				if (strstr($link,"[LOCATIONID]")){
					if ($tmp=$ci->getField('location')){
						$link=str_replace("[LOCATIONID]",$tmp,$link);
					}
				}

				if (strstr($link,"[LOCATION]")){
					if ($tmp=$ci->getField('location')){
						$link=str_replace("[LOCATION]",getDropdownName("glpi_dropdown_locations",$tmp),$link);
					}
				}
				if (strstr($link,"[NETWORK]")){
					if ($tmp=$ci->getField('network')){
						$link=str_replace("[NETWORK]",getDropdownName("glpi_dropdown_network",$tmp),$link);
					}
				}
				if (strstr($link,"[DOMAIN]")){
					if ($tmp=$ci->getField('domain'))
						$link=str_replace("[DOMAIN]",getDropdownName("glpi_dropdown_domain",$tmp),$link);
				}
				if (strstr($link,"[USER]")){
					if ($tmp=$ci->getField('FK_users'))
						$link=str_replace("[USER]",getDropdownName("glpi_users",$tmp),$link);
				}
				if (strstr($link,"[GROUP]")){
					if ($tmp=$ci->getField('FK_groups'))
						$link=str_replace("[GROUP]",getDropdownName("glpi_groups",$tmp),$link);
				}
				$ipmac=array();
				$i=0;
				if (strstr($link,"[IP]")||strstr($link,"[MAC]")){
					$query2 = "SELECT ifaddr, ifmac, logical_number 
						FROM glpi_networking_ports 
						WHERE on_device = '$ID' AND device_type = '$type' 
						ORDER BY logical_number";
					$result2=$DB->query($query2);
					if ($DB->numrows($result2)>0)
						while ($data2=$DB->fetch_array($result2)){
							$ipmac[$i]['ifaddr']=$data2["ifaddr"];
							$ipmac[$i]['ifmac']=$data2["ifmac"];
							$ipmac[$i]['number']=$data2["logical_number"];
							$i++;
						}
				}

				if (strstr($link,"[IP]")||strstr($link,"[MAC]")){
					// Add IP/MAC internal switch
					if ($type==NETWORKING_TYPE){
						$tmplink=$link;
						$tmplink=str_replace("[IP]",$ci->getField('ifaddr'),$tmplink);
						$tmplink=str_replace("[MAC]",$ci->getField('ifmac'),$tmplink);
						echo "<tr class='tab_bg_2'><td><a target='_blank' href='$tmplink'>$name - $tmplink</a></td></tr>";
					}

					if (count($ipmac)>0){
						foreach ($ipmac as $key => $val){
							$tmplink=$link;
							$disp=1;
							if (strstr($link,"[IP]")) {
								if (empty($val['ifaddr'])) {
									$disp=0;
								} else {
									$tmplink=str_replace("[IP]",$val['ifaddr'],$tmplink);
								}
							}
							if (strstr($link,"[MAC]")) {
								if (empty($val['ifmac'])) {
									$disp=0;
								} else {
									$tmplink=str_replace("[MAC]",$val['ifmac'],$tmplink);
								}
							}
							if ($disp) {
								echo "<tr class='tab_bg_2'><td><a target='_blank' href='$tmplink'>$name #" . $val['number'] . " - $tmplink</a></td></tr>";
							}
						}
					}
				} else 
					echo "<tr class='tab_bg_2'><td><a target='_blank' href='$link'>$name</a></td></tr>";

			} else {// File Generated Link
				$link=$data['name'];		
				$ci->getFromDB($type,$ID);

				// Manage Filename
				if (strstr($link,"[NAME]")){
					$link=str_replace("[NAME]",$ci->getName(),$link);
				}

				if (strstr($link,"[LOGIN]")){
					if (isset($_SESSION["glpiname"])){
						$link=str_replace("[LOGIN]",$_SESSION["glpiname"],$link);
					}
				}

				if (strstr($link,"[ID]")){
					$link=str_replace("[ID]",$_GET["ID"],$link);
				}

				echo "<tr class='tab_bg_2'><td><a href='".$CFG_GLPI["root_doc"]."/front/link.send.php?lID=".$data['ID']."&amp;type=$type&amp;ID=$ID' target='_blank'>".$name."</a></td></tr>";
			}


		}
		echo "</table></div>";
	} else echo "<div class='center'><strong>".$LANG["links"][7]."</strong></div>";

}

?>
