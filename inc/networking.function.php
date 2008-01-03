<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

///// Manage Netdevices /////


///// Manage Ports on Devices /////

function showPorts ($device,$device_type,$withtemplate='') {

	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE;

	if (!haveRight("networking","r")) return false;
	$canedit=haveRight("networking","w");

	$device_real_table_name = $LINK_ID_TABLE[$device_type];

	$query = "SELECT ID FROM glpi_networking_ports WHERE (on_device = $device AND device_type = $device_type) ORDER BY name, logical_number";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)!=0) { 
			$colspan=8;
			if ($withtemplate!=2){
				echo "<form id='networking_ports' name='networking_ports' method='post' action=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php\">";
				if ($canedit)
					$colspan++;
			}

			echo "<div class='center'><table class='tab_cadrehov'>";
			echo "<tr>";
			echo "<th colspan='$colspan'>";
			echo $DB->numrows($result)." ";
			if ($DB->numrows($result)<2) {
				echo $LANG["networking"][37];
			} else {
				echo $LANG["networking"][13];
			}
			echo ":</th>";

			echo "</tr>";        
			echo "<tr>";
			if ($withtemplate!=2&&$canedit){
				echo "<th>&nbsp;</th>";
			}
			echo "<th>#</th><th>".$LANG["common"][16]."</th><th>".$LANG["networking"][51]."</th>";
			echo "<th>".$LANG["networking"][14]."<br>".$LANG["networking"][15]."</th>";
			echo "<th>".$LANG["networking"][60]."&nbsp;/&nbsp;".$LANG["networking"][61];

			echo "<br>".$LANG["networking"][59]."</th>";

			echo "<th>".$LANG["networking"][56]."</th>";
			echo "<th>".$LANG["networking"][16]."</th>";
			
			echo"<th>".$LANG["networking"][17].":</th></tr>\n";
			$i=0;
			while ($devid=$DB->fetch_row($result)) {
				$netport = new Netport;
				$netport->getFromDB(current($devid));
				echo "<tr class='tab_bg_1'>";
				if ($withtemplate!=2&&$canedit){
					echo "<td align='center' width='20'><input type='checkbox' name='del_port[".$netport->fields["ID"]."]' value='1'></td>";
				}
				echo "<td class='center'><strong>";
				if ($withtemplate!=2) echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?ID=".$netport->fields["ID"]."\">";
				echo $netport->fields["logical_number"];
				if ($withtemplate!=2) echo "</a>";
				echo "</strong></td>";
				echo "<td>".$netport->fields["name"]."</td>";
				echo "<td>".getDropdownName("glpi_dropdown_netpoint",$netport->fields["netpoint"])."</td>";
				echo "<td>".$netport->fields["ifaddr"]."<br>";
				echo $netport->fields["ifmac"]."</td>";
				echo "<td>".$netport->fields["netmask"]."&nbsp;/&nbsp;";
				echo $netport->fields["subnet"]."<br>";
				echo $netport->fields["gateway"]."</td>";
				// VLANs
				echo "<td>";
				showPortVLAN($netport->fields["ID"],$withtemplate);
				echo "</td>";
				echo "<td>".getDropdownName("glpi_dropdown_iface",$netport->fields["iface"])."</td>";
				echo "<td width='300'>";
				showConnection($netport->fields["ID"],$withtemplate,$device_type);
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "</div>\n\n";

			if ($canedit&&$withtemplate!=2){
				echo "<div class='center'>";
				echo "<table width='80%'>";
				echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markAllRows('networking_ports') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$device&amp;select=all'>".$LANG["buttons"][18]."</a></td>";

				echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkAllRows('networking_ports') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$device&amp;select=none'>".$LANG["buttons"][19]."</a>";
				echo "</td>";
				echo "<td width='80%' align='left'>";
				dropdownMassiveActionPorts($device_type);
				echo "</td>";
				echo "</table>";

				echo "</div>";
			} else {
				echo "<br>";
			}
			if ($withtemplate!=2){
				echo "</form>";
			}
		}
	}
}

function showPortVLAN($ID,$withtemplate,$referer=''){
	global $DB,$CFG_GLPI,$LANG;

	$canedit=haveRight("networking","w");



	$query="SELECT * from glpi_networking_vlan WHERE FK_port='$ID'";
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		echo "<table cellpadding='0' cellspacing='0'>";	
		while ($line=$DB->fetch_array($result)){
			echo "<tr><td>".getDropdownName("glpi_dropdown_vlan",$line["FK_vlan"]);
			echo "</td><td>";
			if ($canedit){
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/networking.port.php?unassign_vlan=unassigned&amp;ID=".$line["ID"]."&amp;referer=$referer'>";
				echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/delete2.png\" alt='".$LANG["buttons"][6]."' title='".$LANG["buttons"][6]."'></a>";
			} else echo "&nbsp;";
			echo "</td></tr>";
		}
		echo "</table>";
	} else echo "&nbsp;";


}

function assignVlan($port,$vlan){
	global $DB;
	$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$port','$vlan')";
	$DB->query($query);

	$np=new NetPort();
	if ($np->getContact($port)){
		$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('".$np->contact_id."','$vlan')";
		$DB->query($query);
	}

}

function unassignVlanbyID($ID){
	global $DB;

	$query="SELECT * FROM glpi_networking_vlan WHERE ID='$ID'";
	if ($result=$DB->query($query)){
		$data=$DB->fetch_array($result);
		
		// Delete VLAN
		$query="DELETE FROM glpi_networking_vlan WHERE ID='$ID'";
		$DB->query($query);
	
		// Delete Contact VLAN if set
		$np=new NetPort();
		if ($np->getContact($data['FK_port'])){
			$query="DELETE FROM glpi_networking_vlan WHERE FK_port='".$np->contact_id."' AND FK_vlan='".$data['FK_vlan']."'";
			$DB->query($query);
		}
	}
}

function unassignVlan($portID,$vlanID){
	global $DB;
	$query="DELETE FROM glpi_networking_vlan WHERE FK_port='$portID' AND FK_vlan='$vlanID'";
	$DB->query($query);

	// Delete Contact VLAN if set
	$np=new NetPort();
	if ($np->getContact($portID)){
		$query="DELETE FROM glpi_networking_vlan WHERE FK_port='".$np->contact_id."' AND FK_vlan='$vlanID'";
		$DB->query($query);
	}

}

function showNetportForm($target,$ID,$ondevice,$devtype,$several) {

	global $CFG_GLPI, $LANG, $REFERER;

	if (!haveRight("networking","r")) return false;

	$netport = new Netport;
	if($ID)
	{
		$netport->getFromDB($ID);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
	}
	else
	{
		$netport->getDeviceData($ondevice,$devtype);
		$netport->getEmpty();
	}

	// Ajout des infos d��remplies
	if (isset($_POST)&&!empty($_POST)){
		foreach ($netport->fields as $key => $val)
			if ($key!='ID'&&isset($_POST[$key]))
				$netport->fields[$key]=$_POST[$key];
	}

	displayTitle("","","",array($REFERER=>$LANG["buttons"][13]));

	echo "<br><div class='center'>";

	echo "<form method='post' action=\"$target\">";

	echo "<input type='hidden' name='referer' value='".urlencode($REFERER)."'>";
	echo "<table class='tab_cadre'><tr>";

	echo "<th colspan='4'>".$LANG["networking"][20].":</th>";
	echo "</tr>";

	if ($several!="yes"){
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][21].":</td>";
		echo "<td>";
		autocompletionTextField("logical_number","glpi_networking_ports","logical_number",$netport->fields["logical_number"],5);	
		echo "</td></tr>";
	}
	else {
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][21].":</td>";
		echo "<input type='hidden' name='several' value='yes'>";
		echo "<input type='hidden' name='logical_number' value=''>";
		echo "<td>";
		echo $LANG["networking"][47].":&nbsp;";
		dropdownInteger('from_logical_number',0,0,100);
		echo $LANG["networking"][48].":&nbsp;";
		dropdownInteger('to_logical_number',0,0,100);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'><td>".$LANG["common"][16].":</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_networking_ports","name",$netport->fields["name"],80);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][16].":</td><td>";
	dropdownValue("glpi_dropdown_iface","iface", $netport->fields["iface"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][14].":</td><td>";
	autocompletionTextField("ifaddr","glpi_networking_ports","ifaddr",$netport->fields["ifaddr"],20);	
	echo "</td></tr>\n";

	// Show device MAC adresses
	if ((!empty($netport->device_type)&&$netport->device_type==COMPUTER_TYPE)||($several!="yes"&&$devtype==COMPUTER_TYPE)){
		$comp=new Computer();

		if (!empty($netport->device_type))
			$comp->getFromDBwithDevices($netport->device_ID);
		else 
			$comp->getFromDBwithDevices($ondevice);

		$macs=array();
		$i=0;
		// Get MAC adresses :
		if (count($comp->devices)>0)	
			foreach ($comp->devices as $key => $val)
				if ($val['devType']==NETWORK_DEVICE&&!empty($val['specificity'])){
					$macs[$i]=$val['specificity'];
					$i++;
				}
		if (count($macs)>0){
			echo "<tr class='tab_bg_1'><td>".$LANG["networking"][15].":</td><td>";
			echo "<select name='pre_mac'>";
			echo "<option value=''>------</option>";
			foreach ($macs as $key => $val){
				echo "<option value='".$val."' >$val</option>";	
			}
			echo "</select>";

			echo "</td></tr>\n";

			echo "<tr class='tab_bg_2'><td>&nbsp;</td>";
			echo "<td>".$LANG["networking"][57];
			echo "</td></tr>\n";

		}
	}

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][15].":</td><td>";
	autocompletionTextField("ifmac","glpi_networking_ports","ifmac",$netport->fields["ifmac"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][60].":</td><td>";
	autocompletionTextField("netmask","glpi_networking_ports","netmask",$netport->fields["netmask"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][59].":</td><td>";
	autocompletionTextField("gateway","glpi_networking_ports","gateway",$netport->fields["gateway"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$LANG["networking"][61].":</td><td>";
	autocompletionTextField("subnet","glpi_networking_ports","subnet",$netport->fields["subnet"],25);	
	echo "</td></tr>\n";

	if ($several!="yes"){
		echo "<tr class='tab_bg_1'><td>".$LANG["networking"][51].":</td>";

		echo "<td align='center' >";
		dropdownNetpoint("netpoint", $netport->fields["netpoint"],$netport->location,1,$netport->FK_entities,
						($ID?$netport->fields["device_type"]: $devtype));		
		echo "</td></tr>";
	}
	if ($ID) {
		echo "<tr class='tab_bg_2'>";
		echo "<td class='center'>";
		echo "<input type='hidden' name='ID' value=".$netport->fields["ID"].">";
		echo "<input type='submit' name='delete' value=\"".$LANG["buttons"][6]."\" class='submit' " .
				"OnClick='return window.confirm(\"".$LANG["common"][50]."\");'>";
		echo "</td>";

		echo "<td class='center'>";
		echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
		echo "</td></tr>";
	} else 
	{

		echo "<tr class='tab_bg_2'>";
		echo "<td align='center' colspan='2'>";
		echo "<input type='hidden' name='on_device' value='$ondevice'>";
		echo "<input type='hidden' name='device_type' value='$devtype'>";
		echo "<input type='submit' name='add' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";
	}

	echo "</table></form></div>";	
	// SHOW VLAN 
	if ($ID){
		echo "<div class='center'>";
		echo "<form method='post' action=\"$target\">";
		echo "<input type='hidden' name='referer' value='$REFERER'>";
		echo "<input type='hidden' name='ID' value='$ID'>";

		echo "<table class='tab_cadre'>";
		echo "<tr><th>".$LANG["setup"][90]."</th></tr>";
		echo "<tr class='tab_bg_2'><td>";
		showPortVLAN($netport->fields["ID"],0,$REFERER);
		echo "</td></tr>";

		echo "<tr  class='tab_bg_2'><td>";
		echo $LANG["networking"][55].":&nbsp;";
		dropdown("glpi_dropdown_vlan","vlan");
		echo "&nbsp;<input type='submit' name='assign_vlan' value='".$LANG["buttons"][3]."' class='submit'>";
		echo "</td></tr>";

		echo "</table>";

		echo "</form>";




		echo "</div>";	


	}
}


function showPortsAdd($ID,$devtype) {

	global $DB,$CFG_GLPI, $LANG,$LINK_ID_TABLE;

	if (!haveTypeRight($devtype,"w")) return false;

	$device_real_table_name = $LINK_ID_TABLE[$devtype];


	echo "<div class='center'><table class='tab_cadre_fixe' cellpadding='2'>";
	echo "<tr>";
	echo "<td align='center' class='tab_bg_2'  >";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?on_device=$ID&amp;device_type=$devtype\"><strong>";
	echo $LANG["networking"][19];
	echo "</strong></a></td>";
	echo "<td align='center' class='tab_bg_2' width='50%'>";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?on_device=$ID&amp;device_type=$devtype&amp;several=yes\"><strong>";
	echo $LANG["networking"][46];
	echo "</strong></a></td>";

	echo "</tr>";
	echo "</table></div><br>";
}

function showConnection($ID,$withtemplate='',$type=COMPUTER_TYPE) {

	global $CFG_GLPI, $LANG,$INFOFORM_PAGES;

	if (!haveTypeRight($type,"r")) return false;
	$canedit=haveRight("networking","w");

	$contact = new Netport;
	$netport = new Netport;

	if ($contact->getContact($ID)) {
		$netport->getFromDB($contact->contact_id);
		$netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"]);
		echo "\n\n<table border='0' cellspacing='0' width='100%'><tr ".($netport->deleted?"class='tab_bg_2_2'":"").">";
		echo "<td><strong>";
		echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?ID=".$netport->fields["ID"]."\">";
		if (rtrim($netport->fields["name"])!="")
			echo $netport->fields["name"];
		else echo $LANG["common"][0];
		echo "</a></strong>";
		echo " ".$LANG["networking"][25]." <strong>";

		echo "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$netport->fields["device_type"]]."?ID=".$netport->device_ID."\">";

		echo $netport->device_name;
		if ($CFG_GLPI["view_ID"]) echo " (".$netport->device_ID.")";
		echo "</a>";
		echo "</strong></td>";
		if ($canedit){
			echo "<td class='right'><strong>";
			if ($withtemplate!=2)
				echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/networking.port.php?disconnect=disconnect&amp;ID=$ID\">".$LANG["buttons"][10]."</a>";
			else "&nbsp;";
			echo "</strong></td>";
		}
		echo "</tr></table>";

	} else {
		echo "<table border='0' cellspacing='0' width='100%'><tr>";
		if ($canedit){
			echo "<td class='left'>";
			if ($withtemplate!=2&&$withtemplate!=1){
				$netport->getFromDB($ID);

				if ($netport->getDeviceData($netport->fields["on_device"],$netport->fields["device_type"])){
					dropdownConnectPort($ID,$type,"dport",$netport->FK_entities);
				}
			}
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "<td><div id='not_connected_display$ID'>".$LANG["connect"][1]."</div></td>";

		echo "</tr></table>";
	}
}	


///// Wire the Ports /////


function makeConnector($sport,$dport) {

	global $DB,$CFG_GLPI, $LANG;

	// Get netpoint for $sport and $dport
	$ps=new Netport;
	if (!$ps->getFromDB($sport)){
		return false;
	}
	$pd=new Netport;
	if (!$pd->getFromDB($dport)){
		return false;
	}

	$items_to_check=array('ifmac'=>$LANG["networking"][15],'ifaddr'=>$LANG["networking"][14],'netpoint'=>$LANG["networking"][51],'subnet'=>$LANG["networking"][61],'netmask'=>$LANG["networking"][60],'gateway'=>$LANG["networking"][59]);

	$update_items=array();
	$conflict_items=array();

	foreach ($items_to_check as $item=>$name){
		$source="";
		$destination="";
		switch ($item){
			case 'netpoint':
				if (isset($ps->fields["netpoint"])&&$ps->fields["netpoint"]!=0){
					$source=$ps->fields["netpoint"];
				}
				if (isset($pd->fields["netpoint"])&&$pd->fields["netpoint"]!=0){
					$destination=$pd->fields["netpoint"];
				}
			break;
			default:
				if (isset($ps->fields[$item])){
					$source=$ps->fields[$item];
				}
				if (isset($pd->fields[$item])){
					$destination=$pd->fields[$item];
				}
			break;
		}
		// Update Item
		$updates[0]=$item;
		if (empty($source)&&!empty($destination)){
			$ps->fields[$item]=$destination;
			$ps->updateInDB($updates);
			$update_items[]=$item;
		}
		else if (!empty($source)&&empty($destination)){
			$pd->fields[$item]=$source;		
			$pd->updateInDB($updates);
			$update_items[]=$item;
		}
		else if ($source!=$destination){
			$conflict_items[]=$item;
		}
	}
	if (count($update_items)){
		$message=$LANG["connect"][15].": ";
		$first=true;
		foreach ($update_items as $item){
			if ($first) $first=false;
			else $message.=" - ";
			$message.=$items_to_check[$item];
		}
		addMessageAfterRedirect($message);
	}
	if (count($conflict_items)){
		$message=$LANG["connect"][16].": ";
		$first=true;
		foreach ($conflict_items as $item){
			if ($first) $first=false;
			else $message.=" - ";
			$message.=$items_to_check[$item];
		}
		addMessageAfterRedirect($message);
	}

	// Manage VLAN : use networkings one as defaults
	$npnet=-1;
	$npdev=-1;
	if ($ps->fields["device_type"]!=NETWORKING_TYPE && $pd->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$dport;
		$npdev=$sport;
	}
	if ($pd->fields["device_type"]!=NETWORKING_TYPE && $ps->fields["device_type"]==NETWORKING_TYPE){
		$npnet=$sport;
		$npdev=$dport;
	}
	if ($npnet>0&&$npdev>0){
		// Get networking VLAN
		// Unset MAC and IP from networking device
		$query = "SELECT * FROM glpi_networking_vlan WHERE FK_port='$npnet'";	
		if ($result=$DB->query($query)){
			if (count($DB->numrows($result))){
				// Found VLAN : clean vlan device and add found ones
				$query="DELETE FROM glpi_networking_vlan WHERE FK_port='$npdev' ";
				$DB->query($query);
				while ($data=$DB->fetch_array($result)){
					$query="INSERT INTO glpi_networking_vlan (FK_port,FK_vlan) VALUES ('$npdev','".$data['FK_vlan']."')";	
					$DB->query($query);
				}
			}
		}
	}
	// end manage VLAN
	
	$query = "INSERT INTO glpi_networking_wire VALUES (NULL,$sport,$dport)";
	if ($result = $DB->query($query)) {
		$source=new CommonItem;
		$source->getFromDB($ps->fields['device_type'],$ps->fields['on_device']);
		$dest=new CommonItem;
		$dest->getFromDB($pd->fields['device_type'],$pd->fields['on_device']);
		echo "<br><div class='center'><strong>".$LANG["networking"][44]." ".$source->getName()." - ".$ps->fields['logical_number']."  (".$ps->fields['ifaddr']." - ".$ps->fields['ifmac'].") ".$LANG["networking"][45]." ".$dest->getName()." - ".$pd->fields['logical_number']." (".$pd->fields['ifaddr']." - ".$pd->fields['ifmac'].") </strong></div>";
		return true;
	} else {
		return false;
	}

}

function removeConnector($ID) {

	global $DB,$CFG_GLPI;

	// Update to blank networking item
	$nw=new Netwire;
	if ($ID2=$nw->getOppositeContact($ID)){

		$np1=new Netport;
		$np2=new Netport;
		$np1->getFromDB($ID);
		$np2->getFromDB($ID2);
		$npnet=-1;
		$npdev=-1;
		if ($np1->fields["device_type"]!=NETWORKING_TYPE&&$np2->fields["device_type"]==NETWORKING_TYPE){
			$npnet=$ID2;
			$npdev=$ID;
		}
		if ($np2->fields["device_type"]!=NETWORKING_TYPE&&$np1->fields["device_type"]==NETWORKING_TYPE){
			$npnet=$ID;
			$npdev=$ID2;
		}
		if ($npnet!=-1&&$npdev!=-1){
			// Unset MAC and IP from networking device
			$query = "UPDATE glpi_networking_ports SET ifaddr='', ifmac='',netmask='', subnet='',gateway='' WHERE ID='$npnet'";	
			$DB->query($query);
			// Unset netpoint from common device
			$query = "UPDATE glpi_networking_ports SET netpoint=NULL WHERE ID='$npdev'";	
			$DB->query($query);
		}

		$query = "DELETE FROM glpi_networking_wire WHERE (end1 = '$ID' OR end2 = '$ID')";
		if ($result=$DB->query($query)) {
			return true;
		} else {
			return false;
		}
	} else return false;
}


?>
