<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("user","tracking","reservation","document","computer","device","printer","networking","peripheral","monitor","software","infocom","phone","link","ocsng","consumable","cartridge","contract","enterprise","contact","group","profile","search","mailgate","typedoc","admininfo","registry","setup","rulesengine","rule.softwarecategories");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();


if ($_POST["device_type"]==TRACKING_TYPE){
	checkSeveralRightsOr(array("delete_ticket"=>1,"update_ticket"=>1));
} else {
	checkTypeRight($_POST["device_type"],"w");
}
commonHeader($LANG["title"][42],$_SERVER['PHP_SELF']);
if (isset($_POST["action"])&&isset($_POST["device_type"])&&isset($_POST["item"])&&count($_POST["item"])){

// Save selection
$_SESSION['glpimassiveactionselected']=array();
foreach ($_POST["item"] as $key => $val){
	if ($val==1) {
		$_SESSION['glpimassiveactionselected'][$key]=$key;
	}
}
$REDIRECT=$_SERVER['HTTP_REFERER'];

	switch($_POST["action"]){
		case "connect":
			$ci=new CommonItem();
			$ci2=new CommonItem();

		if (isset($_POST["connect_item"])&&$_POST["connect_item"]){
			foreach ($_POST["item"] as $key => $val){
				if ($val==1&&$ci->getFromDB($_POST["device_type"],$key)) {
					// Items exists ?
					if ($ci2->getFromDB(COMPUTER_TYPE,$_POST["connect_item"])){
						// Entity security
						if ($ci->obj->fields["FK_entities"]==$ci2->obj->fields["FK_entities"]){
							if ($ci->obj->fields["is_global"]
							||(!$ci->obj->fields["is_global"]&&getNumberConnections($_POST["device_type"],$key)==0)){
								Connect($key,$_POST["connect_item"],$_POST["device_type"]);
							}
						}
					}
				}
			}
		}

		break;
		case "disconnect":
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$query="SELECT * FROM glpi_connect_wire WHERE type='".$_POST["device_type"]."' AND end1 = '$key'";
					$result=$DB->query($query);
					if ($DB->numrows($result)>0){
						while ($data=$DB->fetch_assoc($result)){
							Disconnect($data["ID"]);
						}
					}
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
			if ($_POST["device_type"]<1000&&
			(($_POST["id_field"]>=25&&$_POST["id_field"]<=28)
			||($_POST["id_field"]>=37&&$_POST["id_field"]<=38)
			||($_POST["id_field"]>=50&&$_POST["id_field"]<=58))){
				$ic=new Infocom();
				$ci=new CommonItem();
				$ci->getFromDB($_POST["device_type"],-1);

				$link_entity_type=-1;
				// Specific entity item
				if ($SEARCH_OPTION[$_POST["device_type"]][$_POST["id_field"]]["table"]=="glpi_enterprises_infocoms"){
					$ent=new Enterprise();
					if ($ent->getFromDB($_POST[$_POST["field"]])){
						$link_entity_type=$ent->fields["FK_entities"];
					}
					
				}
				
				foreach ($_POST["item"] as $key => $val){
					if ($val==1){
						if ($ci->getFromDB($_POST["device_type"],$key)){
							if ($link_entity_type<0
							||$link_entity_type==$ci->obj->fields["FK_entities"]){
								unset($ic->fields);
								$ic->update(array("device_type"=>$_POST["device_type"],"FK_device"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
							}
						}
					}
				}
			} else {
				$ci=new CommonItem();
				$ci->getFromDB($_POST["device_type"],-1);
				$link_entity_type=-1;
				// Specific entity item
				
				if ($SEARCH_OPTION[$_POST["device_type"]][$_POST["id_field"]]["table"]!=$LINK_ID_TABLE[$_POST["device_type"]]
				&& in_array($SEARCH_OPTION[$_POST["device_type"]][$_POST["id_field"]]["table"],$CFG_GLPI["specif_entities_tables"])
				&& in_array($LINK_ID_TABLE[$_POST["device_type"]],$CFG_GLPI["specif_entities_tables"])){
					
					$ci2=new CommonDBTM();
					$ci2->table=$SEARCH_OPTION[$_POST["device_type"]][$_POST["id_field"]]["table"];

					if ($ci2->getFromDB($_POST[$_POST["field"]])){
						if (isset($ci2->fields["FK_entities"])&&$ci2->fields["FK_entities"]>=0){
							$link_entity_type=$ci2->fields["FK_entities"];
						}

					}
					
				}
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						if ($ci->getFromDB($_POST["device_type"],$key)){
							if ($link_entity_type<0
							||$link_entity_type==$ci->obj->fields["FK_entities"]){
								$ci->obj->update(array("ID"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
							}
						} 
					}
				}
			}
		break;
		case "install":
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$comp=new Computer;
					if ($comp->getFromDB($key)&&$comp->fields["FK_entities"]==$_SESSION["glpiactive_entity"]){
						installSoftware($key,$_POST["lID"],$_POST["sID"]);
					}
				}
			}
		break;
		case "add_group":
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					addUserGroup($key,$_POST["group"]);
				}
			}
		break;
		case "add_document":
			$ci=new CommonItem();
			$ci2=new CommonItem();
			if ($ci->getFromDB(DOCUMENT_TYPE,$_POST['docID'])){
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						// Items exists ?
						if ($ci2->getFromDB($_POST["device_type"],$key)){
							// Entity security
							if (!isset($ci2->obj->fields["FK_entities"])
							||$ci->obj->fields["FK_entities"]==$ci2->obj->fields["FK_entities"]){
								addDeviceDocument($_POST['docID'],$_POST["device_type"],$key);
							}
						}
					}
				}
			}
		break;
		case "add_contact":
			$ci=new CommonItem();
			$ci2=new CommonItem();
			if ($ci->getFromDB(CONTACT_TYPE,$_POST['conID'])){
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						// Items exists ?
						if ($ci2->getFromDB($_POST["device_type"],$key)){
							// Entity security
							if (!isset($ci2->obj->fields["FK_entities"])
							||$ci->obj->fields["FK_entities"]==$ci2->obj->fields["FK_entities"]){
								addContactEnterprise($key,$_POST["conID"]);
							}
						}
					}
				}
			}
		break;
		case "add_contract":
			$ci=new CommonItem();
			$ci2=new CommonItem();
			if ($ci->getFromDB(CONTRACT_TYPE,$_POST['conID'])){
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						// Items exists ?
						if ($ci2->getFromDB($_POST["device_type"],$key)){
							// Entity security
							if (!isset($ci2->obj->fields["FK_entities"])
							||$ci->obj->fields["FK_entities"]==$ci2->obj->fields["FK_entities"]){
								addDeviceContract($_POST['conID'],$_POST["device_type"],$key);
							}
						}
					}
				}
			}

		break;
		case "add_enterprise":
			$ci=new CommonItem();
			$ci2=new CommonItem();
			if ($ci->getFromDB(ENTERPRISE_TYPE,$_POST['entID'])){
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						// Items exists ?
						if ($ci2->getFromDB($_POST["device_type"],$key)){
							// Entity security
							if (!isset($ci2->obj->fields["FK_entities"])
							||$ci->obj->fields["FK_entities"]==$ci2->obj->fields["FK_entities"]){
								addContactEnterprise($_POST["entID"],$key);
							}
						}
					}
				}
			}
		break;

		case "force_ocsng_update":
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					//Try to get the OCS server whose machine belongs
					$query = "SELECT ocs_server_id, ID 
						FROM glpi_ocs_link 
						WHERE glpi_id='".$key."'";
					$result = $DB->query($query);
					if ($DB->numrows($result) == 1) {					
						$data = $DB->fetch_assoc($result);
						if ($data['ocs_server_id'] != -1){
							//Force update of the machine
							ocsUpdateComputer($data['ID'],$data['ocs_server_id'],1,1);
						}
					}
				}
			}
		break;

		case "compute_software_category":
			$softcatrule = new SoftwareCategoriesRuleCollection;
			$soft = new Software;
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$params = array();
					//Get software name and manufacturer
					$soft->getFromDB($key);
					$params["name"]=$soft->fields["name"];
					$params["FK_glpi_enterprise"]=$soft->fields["FK_glpi_enterprise"];
					
					//Process rules
					$soft->update($softcatrule->processAllRules(null,$soft->fields,$params));
				}
			}
		break;
		case "add_transfer_list":
			if (!isset($_SESSION['glpi_transfer_list'])){
				$_SESSION['glpitransfer_list']=array();
			}
			if (!isset($_SESSION['glpi_transfer_list'][$_POST["device_type"]])){
				$_SESSION['glpitransfer_list'][$_POST["device_type"]]=array();
			}
			
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$_SESSION['glpitransfer_list'][$_POST["device_type"]][$key]=$key;
				}
			}
			$REDIRECT=$CFG_GLPI['root_doc'].'/front/transfer.action.php';
		break;
		case "add_followup":
			$fup=new Followup();
			foreach ($_POST["item"] as $key => $val){
				if ($val==1) {
					$_POST['tracking']=$key;
					unset($fup->fields);
					$fup->add($_POST);
				}
			}
		break;
		default :
			// Plugin specific actions
			if ($_POST["device_type"]>1000){
				if (isset($PLUGIN_HOOKS['plugin_types'][$_POST["device_type"]])){
					$function='plugin_'.$PLUGIN_HOOKS['plugin_types'][$_POST["device_type"]].'_MassiveActionsProcess';
					if (function_exists($function)){
						$function($_POST);
					} 
				} 
			}

		break;
	}

	$_SESSION['MESSAGE_AFTER_REDIRECT'].=$LANG["common"][23];
	glpi_header($REDIRECT);

} else {
	
	echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br>";
	echo "<b>".$LANG["common"][24]."</b></div>";
	
	displayBackLink();
}

commonFooter();

?>
