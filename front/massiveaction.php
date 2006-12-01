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
$NEEDED_ITEMS=array("user","tracking","reservation","document","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","state","link","ocsng","consumable","cartridge","contract","enterprise","contact","group","profile");
include ($phproot . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkTypeRight($_POST["device_type"],"w");

commonHeader($lang["title"][42],$_SERVER['PHP_SELF']);


if (isset($_POST["action"])&&isset($_POST["device_type"])&&isset($_POST["item"])&&count($_POST["item"])){

	switch($_POST["action"]){
		case "connect":
			$ci=new CommonItem();
		if (isset($_POST["connect_item"])&&$_POST["connect_item"])
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					if ($ci->getFromDB($_POST["device_type"],$key))
						if ($ci->obj->fields["is_global"]||(!$ci->obj->fields["is_global"]&&getNumberConnections($_POST["device_type"],$key)==0)){
							Connect($key,$_POST["connect_item"],$_POST["device_type"]);
						}
				}
			}

		break;
		case "disconnect":
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$query="DELETE FROM glpi_connect_wire WHERE type='".$_POST["device_type"]."' AND end1 = '$key'";
					$db->query($query);
				}
			}
		break;
		case "delete":
			$ci=new CommonItem();
		$ci->getFromDB($_POST["device_type"],-1);
		foreach ($_POST["item"] as $key => $val){
			if ($val==1) {
				$ci->obj->delete(array("ID"=>$key));
			}
		}
		break;
		case "purge":
			$ci=new CommonItem();
		$ci->getFromDB($_POST["device_type"],-1);
		foreach ($_POST["item"] as $key => $val){
			if ($val==1) {
				$ci->obj->delete(array("ID"=>$key),1);
			}
		}
		break;
		case "restore":
			$ci=new CommonItem();
		$ci->getFromDB($_POST["device_type"],-1);
		foreach ($_POST["item"] as $key => $val){
			if ($val==1) {
				$ci->obj->restore(array("ID"=>$key));
			}
		}
		break;
		case "update":

			// Infocoms case
			if (($_POST["id_field"]>=25&&$_POST["id_field"]<=28)||($_POST["id_field"]>=37&&$_POST["id_field"]<=38)||($_POST["id_field"]>=50&&$_POST["id_field"]<=58)){
				$ic=new Infocom();
				foreach ($_POST["item"] as $key => $val)
					if ($val==1){
						unset($ic->fields);
						$ic->update(array("device_type"=>$_POST["device_type"],"FK_device"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
					}
			} else {
				$ci=new CommonItem();
				$ci->getFromDB($_POST["device_type"],-1);
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$ci->obj->update(array("ID"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
					}
				}
			}
		break;
		case "install":
			foreach ($_POST["item"] as $key => $val){
				installSoftware($key,$_POST["lID"],$_POST["sID"]);
			}
		break;
		case "add_group":
			foreach ($_POST["item"] as $key => $val){
				addUserGroup($key,$_POST["group"]);
			}
		break;
	}

	echo "<div align='center'><strong>".$lang["common"][23]."<br>";
	echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$lang["buttons"][13]."</a>";
	echo "</strong></div>";



} else echo $lang["common"][24];

commonFooter();

?>
