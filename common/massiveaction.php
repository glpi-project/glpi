<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot."/glpi/includes.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_devices.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_reservation.php");
include ($phproot . "/glpi/includes_state.php");
include ($phproot . "/glpi/includes_financial.php");
include ($phproot . "/glpi/includes_documents.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_links.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkAuthentication("admin");
commonHeader("MASSIVE ACTION HEADER",$_SERVER["PHP_SELF"]);
if (isset($_POST["action"])&&isset($_POST["device_type"])&&isset($_POST["item"])&&count($_POST["item"])){

	switch($_POST["action"]){
		case "delete":
			$ci=new CommonItem();
			$ci->getFromDB($_POST["device_type"],-1);
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) $ci->obj->deleteFromDB($key);
			}
		break;
		case "purge":
			$ci=new CommonItem();
			$ci->getFromDB($_POST["device_type"],-1);
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) $ci->obj->deleteFromDB($key,1);
			}
		break;
		case "restore":
			$ci=new CommonItem();
			$ci->getFromDB($_POST["device_type"],-1);
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) $ci->obj->restoreInDB($key);
			}
		break;
		case "update":
			$input[$_POST["field"]]=$_POST[$_POST["field"]];
			foreach ($_POST["item"] as $key => $val)
			if ($val==1){
				$input["ID"]=$key;
				switch($_POST["device_type"]){
					case COMPUTER_TYPE:
						updateComputer($input);
						break;
					case NETWORKING_TYPE:
						updateNetdevice($input);
						break;
					case PRINTER_TYPE:
						updatePrinter($input);
						break;
					case MONITOR_TYPE:
						updateMonitor($input);
						break;
					case PERIPHERAL_TYPE:
						updatePeripheral($input);
						break;
					case SOFTWARE_TYPE:
						updateSoftware($input);
						break;
					case CONTACT_TYPE:
						updateContact($input);
						break;
					case ENTERPRISE_TYPE:
						updateEnterprise($input);
						break;
					case CONTRACT_TYPE:
						updateContract($input);
						break;
					case CARTRIDGE_TYPE:
						updateCartridgeType($input);
						break;
					case TYPEDOC_TYPE:
						updateTypeDoc($input);
						break;
					case DOCUMENT_TYPE:
						updateDocument($input);
						break;
					case USER_TYPE:
						updateUser($input);
						break;
					case CONSUMABLE_TYPE:
						updateConsumableType($input);
						break;
					case LINK_TYPE:
						updateLink($input);
						break;
					
				}
			}
		break;
	}

	echo "<div align='center'><strong>Action réalisée avec succès<br>";
	echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$lang["buttons"][13]."</a>";
	echo "</strong></div>";



} else echo "Action définie incorrectement ou aucun élément sélectionné";

commonFooter();

?>
