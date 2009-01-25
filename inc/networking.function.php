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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

///// Manage Netdevices /////

///// Manage Ports on Devices /////

function showPorts($device, $device_type, $withtemplate = '') {

	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;
	$rand = mt_rand();
	$ci = new CommonItem();
	$ci->setType($device_type, true);
	if (!$ci->obj->can($device, 'r'))
		return false;
	$canedit = $ci->obj->can($device, 'w');

	$device_real_table_name = $LINK_ID_TABLE[$device_type];

	initNavigateListItems(NETWORKING_PORT_TYPE,$ci->getType()." = ".$ci->getName());

	$query = "SELECT ID FROM glpi_networking_ports 
		WHERE (on_device = '$device' AND device_type = '$device_type') 
		ORDER BY name, logical_number";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result) != 0) {
			$colspan = 9;
			if ($withtemplate != 2) {
				if ($canedit) {					
					$colspan++;
					echo "<form id='networking_ports$rand' name='networking_ports$rand' method='post' action=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php\">";				
				}	
			}

			echo "<div class='center'><table class='tab_cadrehov'>";
			echo "<tr>";
			echo "<th colspan='$colspan'>";
			echo $DB->numrows($result) . " ";
			if ($DB->numrows($result) < 2) {
				echo $LANG["networking"][37];
			} else {
				echo $LANG["networking"][13];
			}
			echo ":</th>";

			echo "</tr>";
			echo "<tr>";
			if ($withtemplate != 2 && $canedit) {
				echo "<th>&nbsp;</th>";
			}
			echo "<th>#</th><th>" . $LANG["common"][16] . "</th><th>" . $LANG["networking"][51] . "</th>";
			echo "<th>" . $LANG["networking"][14] . "<br>" . $LANG["networking"][15] . "</th>";
			echo "<th>" . $LANG["networking"][60] . "&nbsp;/&nbsp;" . $LANG["networking"][61];

			echo "<br>" . $LANG["networking"][59] . "</th>";

			echo "<th>" . $LANG["networking"][56] . "</th>";
			echo "<th>" . $LANG["common"][65] . "</th>";

			echo "<th>" . $LANG["networking"][17] . ":</th>\n";
			echo "<th>" . $LANG["networking"][14] . "<br>" . $LANG["networking"][15] . "</th></tr>";

			$i = 0;
			while ($devid = $DB->fetch_row($result)) {
				$netport = new Netport;
				$netport->getFromDB(current($devid));
				addToNavigateListItems(NETWORKING_PORT_TYPE,$netport->fields["ID"]);

				echo "<tr class='tab_bg_1'>";
				if ($withtemplate != 2 && $canedit) {
					echo "<td align='center' width='20'><input type='checkbox' name='del_port[" . $netport->fields["ID"] . "]' value='1'></td>";
				}
				echo "<td class='center'><strong>";
				if ($canedit && $withtemplate != 2)
					echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?ID=" . $netport->fields["ID"] . "\">";
				echo $netport->fields["logical_number"];
				if ($canedit && $withtemplate != 2)
					echo "</a>";
				echo "</strong></td>";
				echo "<td>" . $netport->fields["name"] . "</td>";
				echo "<td>" . getDropdownName("glpi_dropdown_netpoint", $netport->fields["netpoint"]) . "</td>";
				echo "<td>" . $netport->fields["ifaddr"] . "<br>";
				echo $netport->fields["ifmac"] . "</td>";
				echo "<td>" . $netport->fields["netmask"] . "&nbsp;/&nbsp;";
				echo $netport->fields["subnet"] . "<br>";
				echo $netport->fields["gateway"] . "</td>";
				// VLANs
				echo "<td>";
				showPortVLAN($netport->fields["ID"], $withtemplate);
				echo "</td>";
				echo "<td>" . getDropdownName("glpi_dropdown_iface", $netport->fields["iface"]) . "</td>";
				echo "<td width='300' class='tab_bg_2'>";
				showConnection($ci, $netport, $withtemplate);
				echo "</td>";
				
				echo "<td class='tab_bg_2'>";
				
				if ($netport->getContact($netport->fields["ID"])) {
					echo $netport->fields["ifaddr"] . "<br>";
					echo $netport->fields["ifmac"];
				}
				
				echo "</td></tr>";
			}
			echo "</table>";
			echo "</div>\n\n";

			if ($canedit && $withtemplate != 2) {
				echo "<div class='center'>";
				echo "<table width='80%' class='tab_glpi'>";
				echo "<tr><td><img src=\"" . $CFG_GLPI["root_doc"] . "/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('networking_ports$rand') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$device&amp;select=all'>" . $LANG["buttons"][18] . "</a></td>";

				echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('networking_ports$rand') ) return false;\" href='" . $_SERVER['PHP_SELF'] . "?ID=$device&amp;select=none'>" . $LANG["buttons"][19] . "</a>";

				//				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><span class='pointer' id='networking_ports_markall$rand'>".$LANG["buttons"][18]."</span></td>";

				//			echo "<td>/</td><td class='center'><span class='pointer' id='networking_ports_unmarkall$rand'>".$LANG["buttons"][19]."</span>";

				echo "</td>";
				echo "<td width='80%' align='left'>";
				dropdownMassiveActionPorts($device_type);
				echo "</td>";
				echo "</table>";

				echo "</div>";
			} else {
				echo "<br>";
			}
			if ($canedit && $withtemplate != 2) {
				echo "</form>";
			}
		}
	}
}

function showPortVLAN($ID, $withtemplate) {
	global $DB, $CFG_GLPI, $LANG;

	$canedit = haveRight("networking", "w");

	$used = array();
	
	$query = "SELECT * FROM glpi_networking_vlan WHERE FK_port='$ID'";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		echo "<table cellpadding='0' cellspacing='0'>";
		while ($line = $DB->fetch_array($result)) {
			$used[]=$line["FK_vlan"];
			echo "<tr><td>" . getDropdownName("glpi_dropdown_vlan", $line["FK_vlan"]);
			echo "</td><td>";
			if ($canedit) {
				echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?unassign_vlan=unassigned&amp;ID=" . $line["ID"] . "'>";
				echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete2.png\" alt='" . $LANG["buttons"][6] . "' title='" . $LANG["buttons"][6] . "'></a>";
			} else
				echo "&nbsp;";
			echo "</td></tr>";
		}
		echo "</table>";
	} else
		echo "&nbsp;";

	return $used;
}

function showPortVLANForm ($ID) {
	global $DB, $CFG_GLPI, $LANG;

	if ($ID) {
		echo "<div class='center'>";
		echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/front/networking.port.php'>";
		//echo "<input type='hidden' name='referer' value='$REFERER'>";
		echo "<input type='hidden' name='ID' value='$ID'>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th>" . $LANG["setup"][90] . "</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		$used=showPortVLAN($ID, 0);
		echo "</td></tr>";

		echo "<tr  class='tab_bg_2'><td>";
		echo $LANG["networking"][55] . ":&nbsp;";
		dropdown("glpi_dropdown_vlan", "vlan",1,-1,$used);
		echo "&nbsp;<input type='submit' name='assign_vlan' value='" . $LANG["buttons"][3] . "' class='submit'>";
		echo "</td></tr>";

		echo "</table>";

		echo "</form>";

		echo "</div>";

	}	
}

function assignVlan($port, $vlan) {
	global $DB;
	$query = "INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$port','$vlan')";
	$DB->query($query);

	$np = new NetPort();
	if ($np->getContact($port)) {
		$query = "INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('" . $np->contact_id . "','$vlan')";
		$DB->query($query);
	}

}

function unassignVlanbyID($ID) {
	global $DB;

	$query = "SELECT * FROM glpi_networking_vlan WHERE ID='$ID'";
	if ($result = $DB->query($query)) {
		$data = $DB->fetch_array($result);

		// Delete VLAN
		$query = "DELETE FROM glpi_networking_vlan WHERE ID='$ID'";
		$DB->query($query);

		// Delete Contact VLAN if set
		$np = new NetPort();
		if ($np->getContact($data['FK_port'])) {
			$query = "DELETE FROM glpi_networking_vlan WHERE FK_port='" . $np->contact_id . "' AND FK_vlan='" . $data['FK_vlan'] . "'";
			$DB->query($query);
		}
	}
}

function unassignVlan($portID, $vlanID) {
	global $DB;
	$query = "DELETE FROM glpi_networking_vlan WHERE FK_port='$portID' AND FK_vlan='$vlanID'";
	$DB->query($query);

	// Delete Contact VLAN if set
	$np = new NetPort();
	if ($np->getContact($portID)) {
		$query = "DELETE FROM glpi_networking_vlan WHERE FK_port='" . $np->contact_id . "' AND FK_vlan='$vlanID'";
		$DB->query($query);
	}

}

function showNetportForm($target, $ID, $ondevice, $devtype, $several) {

	global $CFG_GLPI, $LANG;

	if (!haveRight("networking", "r"))
		return false;

	$netport = new Netport;
	if ($ID) {
		$netport->getFromDB($ID);
		$netport->getDeviceData($ondevice=$netport->fields["on_device"], $devtype=$netport->fields["device_type"]);
	} else {
		$netport->getDeviceData($ondevice, $devtype);
		$netport->getEmpty();
	}

	// Ajout des infos d��remplies
	if (isset ($_POST) && !empty ($_POST)) {
		foreach ($netport->fields as $key => $val)
			if ($key != 'ID' && isset ($_POST[$key]))
				$netport->fields[$key] = $_POST[$key];
	}

	
	$netport->showTabs($ID, false, $_SESSION['glpi_tab'],array(),"device_type=$devtype AND on_device=$ondevice");
	
	echo "<div class='center' id='tabsbody'><form method='post' action=\"$target\">";

	echo "<table class='tab_cadre_fixe'><tr>";

	echo "<th colspan='4'>" . $LANG["networking"][20] . ":</th>";
	echo "</tr>";
	
	$ci=new CommonItem();
	if ($ci->getFromDB($netport->device_type,$netport->device_ID)) {
		echo "<tr class='tab_bg_1'><td>" . $ci->getType() . ":</td><td colspan='2'>";
		echo $ci->getLink(). "</td></tr>\n";
	}

	if ($several != "yes") {
		echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][21] . ":</td>";
		echo "<td colspan='2'>";
		autocompletionTextField("logical_number", "glpi_networking_ports", "logical_number", $netport->fields["logical_number"], 5);
		echo "</td></tr>";
	} else {
		echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][21] . ":</td>";
		echo "<input type='hidden' name='several' value='yes'>";
		echo "<input type='hidden' name='logical_number' value=''>";
		echo "<td colspan='2'>";
		echo $LANG["networking"][47] . ":&nbsp;";
		dropdownInteger('from_logical_number', 0, 0, 100);
		echo $LANG["networking"][48] . ":&nbsp;";
		dropdownInteger('to_logical_number', 0, 0, 100);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'><td>" . $LANG["common"][16] . ":</td>";
	echo "<td colspan='2'>";
	autocompletionTextField("name", "glpi_networking_ports", "name", $netport->fields["name"], 80);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>" . $LANG["common"][65] . ":</td><td colspan='2'>";
	dropdownValue("glpi_dropdown_iface", "iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][14] . ":</td><td colspan='2'>";
	autocompletionTextField("ifaddr", "glpi_networking_ports", "ifaddr", $netport->fields["ifaddr"], 40);
	echo "</td></tr>\n";

	// Show device MAC adresses
	if ((!empty ($netport->device_type) && $netport->device_type == COMPUTER_TYPE) || ($several != "yes" && $devtype == COMPUTER_TYPE)) {
		$comp = new Computer();

		if (!empty ($netport->device_type))
			$comp->getFromDBwithDevices($netport->device_ID);
		else
			$comp->getFromDBwithDevices($ondevice);

		$macs = array ();
		$i = 0;
		// Get MAC adresses :
		if (count($comp->devices) > 0)
			foreach ($comp->devices as $key => $val)
				if ($val['devType'] == NETWORK_DEVICE && !empty ($val['specificity'])) {
					$macs[$i] = $val['specificity'];
					$i++;
				}
		if (count($macs) > 0) {
			echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][15] . ":</td><td colspan='2'>";
			echo "<select name='pre_mac'>";
			echo "<option value=''>------</option>";
			foreach ($macs as $key => $val) {
				echo "<option value='" . $val . "' >$val</option>";
			}
			echo "</select>";

			echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>&nbsp;</td>";
			echo "<td colspan='2'>" . $LANG["networking"][57];
			echo "</td></tr>\n";

		}
	}

	echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][15] . ":</td><td colspan='2'>";
	autocompletionTextField("ifmac", "glpi_networking_ports", "ifmac", $netport->fields["ifmac"], 40);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][60] . ":</td><td colspan='2'>";
	autocompletionTextField("netmask", "glpi_networking_ports", "netmask", $netport->fields["netmask"], 40);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][59] . ":</td><td colspan='2'>";
	autocompletionTextField("gateway", "glpi_networking_ports", "gateway", $netport->fields["gateway"], 40);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][61] . ":</td><td colspan='2'>";
	autocompletionTextField("subnet", "glpi_networking_ports", "subnet", $netport->fields["subnet"], 40);
	echo "</td></tr>\n";

	if ($several != "yes") {
		echo "<tr class='tab_bg_1'><td>" . $LANG["networking"][51] . ":</td>";

		echo "<td  colspan='2'>";
		dropdownNetpoint("netpoint", $netport->fields["netpoint"], $netport->location, 1, $netport->FK_entities, ($ID ? $netport->fields["device_type"] : $devtype));
		echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_2'>";

		echo "<td class='center'>&nbsp;</td>";
		echo "<td class='center'>";
		echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit'>";
		echo "</td>";

		echo "<td class='center'>";
		echo "<input type='hidden' name='ID' value=" . $netport->fields["ID"] . ">";
		echo "<input type='submit' name='delete' value=\"" . $LANG["buttons"][6] . "\" class='submit' " .
		"OnClick='return window.confirm(\"" . $LANG["common"][50] . "\");'>";
		echo "</td></tr>\n";

	} else {

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='3'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";
	
	echo "<div id='tabcontent'></div>";
	echo "<script type='text/javascript'>loadDefaultTab();</script>";
}

function showPortsAdd($ID, $devtype) {

	global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

	$ci = new CommonItem();
	$ci->setType($devtype, true);
	if (!$ci->obj->can($ID, 'w'))
		return false;

	$device_real_table_name = $LINK_ID_TABLE[$devtype];

	echo "<div class='center'><table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?on_device=$ID&amp;device_type=$devtype\"><strong>";
	echo $LANG["networking"][19];
	echo "</strong></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?on_device=$ID&amp;device_type=$devtype&amp;several=yes\"><strong>";
	echo $LANG["networking"][46];
	echo "</strong></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

/**
 * Display a connection of a networking port 
 * 
 * @param $device1 the device of the port
 * @param $netport to be displayed
 * @param $withtemplate 
 * 
 */
function showConnection(& $device1, & $netport, $withtemplate = '') {

	global $CFG_GLPI, $LANG, $INFOFORM_PAGES;

	if (!$device1->obj->can($device1->obj->fields["ID"], 'r'))
		return false;

	$contact = new Netport;
	$device2 = new CommonItem();

	$canedit = $device1->obj->can($device1->obj->fields["ID"], 'w');
	$ID = $netport->fields["ID"];

	if ($contact->getContact($ID)) {
		$netport->getFromDB($contact->contact_id);
		$device2->getFromDB($netport->fields["device_type"], $netport->fields["on_device"]);

		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr " . ($device2->obj->fields["deleted"] ? "class='tab_bg_2_2'" : "") . ">";
		echo "<td><strong>";

		if ($device2->obj->can($device2->obj->fields["ID"], 'r')) {
			echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?ID=" . $netport->fields["ID"] . "\">";
			if (rtrim($netport->fields["name"]) != "")
				echo $netport->fields["name"];
			else
				echo $LANG["common"][0];
			echo "</a></strong> " . $LANG["networking"][25] . " <strong>";

			echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/" . $INFOFORM_PAGES[$netport->fields["device_type"]] . "?ID=" . $device2->obj->fields["ID"] . "\">";

			echo $device2->obj->fields["name"];
			if ($_SESSION["glpiview_ID"])
				echo " (" . $netport->device_ID . ")";
			echo "</a></strong>";
			if ($device1->obj->fields["FK_entities"] != $device2->obj->fields["FK_entities"]) {
				echo "<br>(" . getDropdownName("glpi_entities", $device2->obj->fields["FK_entities"]) . ")";
			}

			// 'w' on dev1 + 'r' on dev2 OR 'r' on dev1 + 'w' on dev2
			if ($canedit || $device2->obj->can($device2->obj->fields["ID"], 'w')) {
				echo "</td><td class='right'><strong>";
				if ($withtemplate != 2)
					echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networking.port.php?disconnect=disconnect&amp;ID=$ID\">" . $LANG["buttons"][10] . "</a>";
				else
					"&nbsp;";
				echo "</strong>";
			}
		} else {
			if (rtrim($netport->fields["name"]) != "")
				echo $netport->fields["name"];
			else
				echo $LANG["common"][0];
			echo "</strong> " . $LANG["networking"][25] . " <strong>";
			echo $device2->obj->fields["name"];
			echo "</strong><br>(" . getDropdownName("glpi_entities", $device2->obj->fields["FK_entities"]) . ")";
		}
		echo "</td></tr></table>";

	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		if ($canedit) {
			echo "<td class='left'>";
			if ($withtemplate != 2 && $withtemplate != 1) {
				if (isset ($device1->obj->fields["recursive"]) && $device1->obj->fields["recursive"]) {
					dropdownConnectPort($ID, $device1->obj->type, "dport", getEntitySons($device1->obj->fields["FK_entities"]));
				} else {
					dropdownConnectPort($ID, $device1->obj->type, "dport", $device1->obj->fields["FK_entities"]);
				}
			} else
				echo "&nbsp;";
			echo "</td>";
		}
		echo "<td><div id='not_connected_display$ID'>" . $LANG["connect"][1] . "</div></td>";

		echo "</tr></table>";
	}
}

/**
 * Wire the Ports
 *
 *@param $sport : source port ID
 *@param $dport : destination port ID
 *@param $dohistory : add event in the history
 *@param $addmsg : display HTML message on success
 * 
 *@return true on success 
**/
function makeConnector($sport, $dport, $dohistory = true, $addmsg = false) {

	global $DB, $CFG_GLPI, $LANG;

	// Get netpoint for $sport and $dport
	$ps = new Netport;
	if (!$ps->getFromDB($sport)) {
		return false;
	}
	$pd = new Netport;
	if (!$pd->getFromDB($dport)) {
		return false;
	}

/*	$items_to_check = array (
		'ifmac' => $LANG["networking"][15],
		'ifaddr' => $LANG["networking"][14],
		'netpoint' => $LANG["networking"][51],
		'subnet' => $LANG["networking"][61],
		'netmask' => $LANG["networking"][60],
		'gateway' => $LANG["networking"][59]
	);

	$update_items = array ();
	$conflict_items = array ();

	foreach ($items_to_check as $item => $name) {
		$source = "";
		$destination = "";
		switch ($item) {
			case 'netpoint' :
				if (isset ($ps->fields["netpoint"]) && $ps->fields["netpoint"] != 0) {
					$source = $ps->fields["netpoint"];
				}
				if (isset ($pd->fields["netpoint"]) && $pd->fields["netpoint"] != 0) {
					$destination = $pd->fields["netpoint"];
				}
				break;
			default :
				if (isset ($ps->fields[$item])) {
					$source = $ps->fields[$item];
				}
				if (isset ($pd->fields[$item])) {
					$destination = $pd->fields[$item];
				}
				break;
		}
		
		// Update Item
		$updates[0] = $item;
		if (empty ($source) && !empty ($destination)) {
			$ps->fields[$item] = $destination;
			$ps->updateInDB($updates);
			$update_items[] = $item;
		} else
			if (!empty ($source) && empty ($destination)) {
				$pd->fields[$item] = $source;
				$pd->updateInDB($updates);
				$update_items[] = $item;
			} else
				if ($source != $destination) {
					$conflict_items[] = $item;
				}
	}
	if (count($update_items)) {
		$message = $LANG["connect"][15] . ": ";
		$first = true;
		foreach ($update_items as $item) {
			if ($first)
				$first = false;
			else
				$message .= " - ";
			$message .= $items_to_check[$item];
		}
		addMessageAfterRedirect($message);
	}
	if (count($conflict_items)) {
		$message = $LANG["connect"][16] . ": ";
		$first = true;
		foreach ($conflict_items as $item) {
			if ($first)
				$first = false;
			else
				$message .= " - ";
			$message .= $items_to_check[$item];
		}
		addMessageAfterRedirect($message);
	}
*/
	
	// Manage VLAN : use networkings one as defaults
	$npnet = -1;
	$npdev = -1;
	if ($ps->fields["device_type"] != NETWORKING_TYPE && $pd->fields["device_type"] == NETWORKING_TYPE) {
		$npnet = $dport;
		$npdev = $sport;
	}
	if ($pd->fields["device_type"] != NETWORKING_TYPE && $ps->fields["device_type"] == NETWORKING_TYPE) {
		$npnet = $sport;
		$npdev = $dport;
	}
	if ($npnet > 0 && $npdev > 0) {
		// Get networking VLAN
		// Unset MAC and IP from networking device
		$query = "SELECT * FROM glpi_networking_vlan WHERE FK_port='$npnet'";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) > 0) {
				// Found VLAN : clean vlan device and add found ones
				$query = "DELETE FROM glpi_networking_vlan WHERE FK_port='$npdev' ";
				$DB->query($query);
				while ($data = $DB->fetch_array($result)) {
					$query = "INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$npdev','" . $data['FK_vlan'] . "')";
					$DB->query($query);
				}
			}
		}
	}
	// end manage VLAN

	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,'$sport','$dport')";
	if ($result = $DB->query($query)) {
		$source = new CommonItem;
		$source->getFromDB($ps->fields['device_type'], $ps->fields['on_device']);
		$dest = new CommonItem;
		$dest->getFromDB($pd->fields['device_type'], $pd->fields['on_device']);

		if ($dohistory) {
			$changes[0] = 0;
			$changes[1] = "";
			$changes[2] = $dest->getName();
			if ($ps->fields["device_type"] == NETWORKING_TYPE) {
				$changes[2] = "#" . $ps->fields["name"] . " > " . $changes[2];
			}
			if ($pd->fields["device_type"] == NETWORKING_TYPE) {
				$changes[2] = $changes[2] . " > #" . $pd->fields["name"];
			}
			historyLog($ps->fields["on_device"], $ps->fields["device_type"], $changes, $pd->fields["device_type"], HISTORY_CONNECT_DEVICE);

			$changes[2] = $source->getName();
			if ($pd->fields["device_type"] == NETWORKING_TYPE) {
				$changes[2] = "#" . $pd->fields["name"] . " > " . $changes[2];
			}
			if ($ps->fields["device_type"] == NETWORKING_TYPE) {
				$changes[2] = $changes[2] . " > #" . $ps->fields["name"];
			}
			historyLog($pd->fields["on_device"], $pd->fields["device_type"], $changes, $ps->fields["device_type"], HISTORY_CONNECT_DEVICE);
		}

		if ($addmsg) {
			echo "<br><div class='center'><strong>" . $LANG["networking"][44] . " " . $source->getName() . " - " . $ps->fields['logical_number'] . "  (" . $ps->fields['ifaddr'] . " - " . $ps->fields['ifmac'] . ") " . $LANG["networking"][45] . " " . $dest->getName() . " - " . $pd->fields['logical_number'] . " (" . $pd->fields['ifaddr'] . " - " . $pd->fields['ifmac'] . ") </strong></div>";
		}
		return true;
	} else {
		return false;
	}

}

/**
 * Unwire the Ports
 *
 *@param $ID : ID a network port
 *@param $dohistory : add event in the history
 * 
 *@return true on success 
**/
function removeConnector($ID, $dohistory = true) {

	global $DB, $CFG_GLPI;

	// Update to blank networking item
	$nw = new Netwire;
	if ($ID2 = $nw->getOppositeContact($ID)) {
		$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result = $DB->query($query)) {

			// clean datas of linked ports if network one
			$np1 = new Netport;
			$np2 = new Netport;
			if ($np1->getFromDB($ID) && $np2->getFromDB($ID2)) {
				$npnet = -1;
				$npdev = -1;
				if ($np1->fields["device_type"] != NETWORKING_TYPE && $np2->fields["device_type"] == NETWORKING_TYPE) {
					$npnet = $ID2;
					$npdev = $ID;
				}
				if ($np2->fields["device_type"] != NETWORKING_TYPE && $np1->fields["device_type"] == NETWORKING_TYPE) {
					$npnet = $ID;
					$npdev = $ID2;
				}
				
				/*
				if ($npnet != -1 && $npdev != -1) {
					// Unset MAC and IP from networking device
					if ($np1->fields["ifmac"] == $np2->fields["ifmac"]) {
						$query = "UPDATE glpi_networking_ports SET ifmac='' WHERE ID='$npnet'";
						$DB->query($query);
					}
					if ($np1->fields["ifaddr"] == $np2->fields["ifaddr"]) {
						$query = "UPDATE glpi_networking_ports SET ifaddr='',netmask='', subnet='',gateway='' WHERE ID='$npnet'";
						$DB->query($query);
					}
					// Unset netpoint from common device
					$query = "UPDATE glpi_networking_ports SET netpoint=0 WHERE ID='$npdev'";
					$DB->query($query);
				}
				*/
				
				if ($dohistory) {
					$device = new CommonItem();

					$device->getFromDB($np2->fields["device_type"], $np2->fields["on_device"]);
					$changes[0] = 0;
					$changes[1] = $device->getName();
					$changes[2] = "";
					if ($np1->fields["device_type"] == NETWORKING_TYPE) {
						$changes[1] = "#" . $np1->fields["name"] . " > " . $changes[1];
					}
					if ($np2->fields["device_type"] == NETWORKING_TYPE) {
						$changes[1] = $changes[1] . " > #" . $np2->fields["name"];
					}
					historyLog($np1->fields["on_device"], $np1->fields["device_type"], $changes, $np2->fields["device_type"], HISTORY_DISCONNECT_DEVICE);

					$device->getFromDB($np1->fields["device_type"], $np1->fields["on_device"]);
					$changes[1] = $device->getName();
					if ($np2->fields["device_type"] == NETWORKING_TYPE) {
						$changes[1] = "#" . $np2->fields["name"] . " > " . $changes[1];
					}
					if ($np1->fields["device_type"] == NETWORKING_TYPE) {
						$changes[1] = $changes[1] . " > #" . $np1->fields["name"];
					}
					historyLog($np2->fields["on_device"], $np2->fields["device_type"], $changes, $np1->fields["device_type"], HISTORY_DISCONNECT_DEVICE);
				}
			}

			return true;
		} else {
			return false;
		}
	} else
		return false;
}

/**
 * Get an Object ID by his IP address (only if one result is found in the entity)
 * @param ip the ip address
 * @param entity the entity to look for
 * @return an array containing the object ID or an empty array is no value of serverals ID where found
 */
function getUniqueObjectIDByIPAddressOrMac($value, $type = 'IP', $entity) {
	global $DB;

	switch ($type) {
		default :
		case "IP" :
			$field = "ifaddr";
			break;
		case "MAC" :
			$field = "ifmac";
			break;
	}

	//Try to get all the object (not deleted, and not template) with a network port having the specified IP, in a given entity
	$query = "SELECT gnp.on_device as ID, gnp.ID as portID, gnp.device_type as device_type 
		FROM `glpi_networking_ports` as gnp
		LEFT JOIN  `glpi_computers` as gc ON (gnp.on_device=gc.ID AND gc.FK_entities=$entity AND gc.deleted=0 
							AND gc.is_template=0 AND device_type=" . COMPUTER_TYPE . ") 
		LEFT JOIN  `glpi_printers` as gp ON (gnp.on_device=gp.ID AND gp.FK_entities=$entity AND gp.deleted=0 
							AND gp.is_template=0 AND device_type=" . PRINTER_TYPE . ")
		LEFT JOIN  `glpi_networking` as gn ON (gnp.on_device=gn.ID AND gn.FK_entities=$entity AND gn.deleted=0 
							AND gn.is_template=0 AND device_type=" . NETWORKING_TYPE . ")  
		LEFT JOIN  `glpi_phones` as gph ON (gnp.on_device=gph.ID AND gph.FK_entities=$entity AND gph.deleted=0 
							AND gph.is_template=0 AND device_type=" . PHONE_TYPE . ") 
		LEFT JOIN  `glpi_peripherals` as gpe ON (gnp.on_device=gpe.ID AND gpe.FK_entities=$entity AND gpe.deleted=0 
							AND gpe.is_template=0 AND device_type=" . PERIPHERAL_TYPE . ") 
	 	WHERE gnp.$field='" . $value . "'";

	$result = $DB->query($query);

	//3 possibilities :
	//0 found : no object with a network port have this ip. Look into networkings object to see if,maybe, one have it
	//1 found : one object have a network port with the ip -> good, possible to link
	//2 found : one object have a network port with this ip, and the port is link to another one -> get the object by removing the port connected to a network device
	switch ($DB->numrows($result)) {
		case 0 :
			//No result found with the previous request. Try to look for IP in the glpi_networking table directly
			$query = "SELECT ID FROM glpi_networking WHERE UPPER($field)=UPPER('$value') AND FK_entities='$entity'";
			$result = $DB->query($query);
			if ($DB->numrows($result) == 1)
				return array (
					"ID" => $DB->result($result, 0, "ID"),
					"device_type" => NETWORKING_TYPE
				);
			else
				return array ();
		case 1 :
			$port = $DB->fetch_array($result);
			return array (
				"ID" => $port["ID"],
				"device_type" => $port["device_type"]
			);

		case 2 :
			//2 ports found with the same IP
			//We can face different configurations :
			//the 2 ports aren't linked -> can do nothing (how to know which one is the good one)
			//the 2 ports are linked but no ports are connected on a network device (for example 2 computers connected)-> can do nothin (how to know which one is the good one)
			//thez 2 ports are linked and one port in connected on a network device -> use the port not connected on the network device as the good one 
			$port1 = $DB->fetch_array($result);
			$port2 = $DB->fetch_array($result);

			//Get the 2 ports informations and try to see if one port is connected on a network device
			$network_port = -1;
			if ($port1["device_type"] == NETWORKING_TYPE)
				$network_port = 1;
			elseif ($port2["device_type"] == NETWORKING_TYPE) $network_port = 2;

			//If one port is connected on a network device
			if ($network_port != -1) {
				//If the 2 ports are linked each others
				$query = "SELECT ID FROM glpi_networking_wire 
					WHERE (end1='".$port1["portID"]."' AND end2='".$port2["portID"]."') 
						OR (end1='".$port2["portID"]."' AND end2='".$port1["portID"]."')";
				$query = $DB->query($query);
				if ($DB->numrows($query) == 1)
					return array (
						"ID" => ($network_port == 1 ? $port2["ID"] : $port1["ID"]),
						"device_type" => ($network_port == 1 ? $port2["device_type"] : $port1["device_type"])
					);
			}
			return array ();
		default :
			return array ();

	}
}

/**
 * Look for a computer or a network device with a fully qualified domain name in an entity
 * @param fqdn fully qualified domain name
 * @param entity the entity
 * @return an array with the ID and device_type or an empty array if no unique object is found
 */
function getUniqueObjectIDByFQDN($fqdn, $entity) {
	$types = array (
		COMPUTER_TYPE,
		NETWORKING_TYPE,
		PRINTER_TYPE
	);
	foreach ($types as $type) {
		$result = getUniqueObjectByFDQNAndType($fqdn, $type, $entity);
		if (!empty ($result))
			return $result;
	}
	return array ();
}

/**
 * Look for a specific type of device with a fully qualified domain name in an entity
 * @param fqdn fully qualified domain name
 * @param type the type of object to look for
 * @param entity the entity
 * @return an array with the ID and device_type or an empty array if no unique object is found
 */

function getUniqueObjectByFDQNAndType($fqdn, $type, $entity) {
	global $DB;
	$commonitem = new CommonItem;
	$commonitem->setType($type, true);

	$query = "SELECT obj.ID AS ID
		FROM " . $commonitem->obj->table . " AS obj, glpi_dropdown_domain AS gdd
		WHERE obj.FK_entities='$entity' AND obj.domain = gdd.ID
			AND LOWER( '$fqdn' ) = ( CONCAT( LOWER( obj.name ) , '.', LOWER( gdd.name ) ) )";

	$result = $DB->query($query);
	if ($DB->numrows($result) == 1) {
		$datas = $DB->fetch_array($result);
		return array (
			"ID" => $datas["ID"],
			"device_type" => $type
		);
	} else
		return array ();

}
?>
