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


$NEEDED_ITEMS=array("user","tracking","reservation","document","computer","device","printer","networking",
					"peripheral","monitor","software","infocom","phone","link","ocsng","consumable","cartridge",
					"contract","enterprise","contact","group","profile","search","mailgate","typedoc","admininfo",
					"registry","setup","rulesengine","rule.right", "rule.softwarecategories","rule.dictionnary.software","rule.dictionnary.dropdown","entity","ldap","transfer");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();


if (isset($_GET['multiple_actions'])){
	if (isset($_SESSION['glpi_massiveaction'])&&isset($_SESSION['glpi_massiveaction']['POST'])){
		$_POST=$_SESSION['glpi_massiveaction']['POST'];
	}
}

if (isset($_POST["itemtype"])){

	/// Right check
	switch ($_POST["itemtype"]){
		case TRACKING_TYPE :
			switch ($_POST["action"]){
				case "delete":
					checkTypeRight("delete","1");
					break;
				case "add_followup":
					checkTypeRight("comment_all_ticket","1");
					break;
				default:
					checkTypeRight("update_ticket","1");
					break;
			}
			break;
		default :
			if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])){
				checkSeveralRightsOr(array($_POST["itemtype"]=>"w","infocom"=>"w"));
			} else {
				checkTypeRight($_POST["itemtype"],"w");
			}
			break;
	}

	commonHeader($LANG['title'][42],$_SERVER['PHP_SELF']);
	
	
	if (isset($_GET['multiple_actions'])){
		if (isset($_SESSION['glpi_massiveaction'])&&isset($_SESSION['glpi_massiveaction']['items'])){
			$percent=min(100,round(100*($_SESSION['glpi_massiveaction']['item_count']-count($_SESSION['glpi_massiveaction']['items']))/$_SESSION['glpi_massiveaction']['item_count'],0));
			displayProgressBar(400,$percent);
		}
	}
	
	
	if (isset($_POST["action"])&&isset($_POST["itemtype"])&&isset($_POST["item"])&&count($_POST["item"])){

	
	/// Save selection
	if (!isset($_SESSION['glpimassiveactionselected'])||count($_SESSION['glpimassiveactionselected'])==0){
		$_SESSION['glpimassiveactionselected']=array();
		foreach ($_POST["item"] as $key => $val){
			if ($val==1) {
				$_SESSION['glpimassiveactionselected'][$key]=$key;
			}
		}
	}

	if (isset($_SERVER['HTTP_REFERER'])){
		$REDIRECT=$_SERVER['HTTP_REFERER'];
	} else { /// Security : not used if no problem
		$REDIRECT=$CFG_GLPI['root_doc']."/front/central.php";
	}
	
		switch($_POST["action"]){
			case "connect_to_computer":
				$ci=new CommonItem();
				$ci2=new CommonItem();

				if (isset($_POST["connect_item"])&&$_POST["connect_item"]){
					foreach ($_POST["item"] as $key => $val){
						if ($val==1&&$ci->getFromDB($_POST["type"],$_POST["connect_item"])) {
							/// Items exists ?
							if ($ci2->getFromDB(COMPUTER_TYPE,$key)){
								/// Entity security
								if ($ci->obj->fields["entities_id"]==$ci2->obj->fields["entities_id"]){
									if ($ci->obj->fields["is_global"]
									||(!$ci->obj->fields["is_global"]&&getNumberConnections($_POST["itemtype"],$key)==0)){
										Connect($_POST["connect_item"],$key,$_POST["type"]);
									}
								}
							}
						}
					}
				}
	
			break;

			case "connect":
				$ci=new CommonItem();
				$ci2=new CommonItem();
	
				if (isset($_POST["connect_item"])&&$_POST["connect_item"]){
					foreach ($_POST["item"] as $key => $val){
						if ($val==1&&$ci->getFromDB($_POST["itemtype"],$key)) {
							/// Items exists ?
							if ($ci2->getFromDB(COMPUTER_TYPE,$_POST["connect_item"])){
								/// Entity security
								if ($ci->obj->fields["entities_id"]==$ci2->obj->fields["entities_id"]){
									if ($ci->obj->fields["is_global"]
                              ||(!$ci->obj->fields["is_global"]
                                 &&getNumberConnections($_POST["itemtype"],$key)==0)){
										Connect($key,$_POST["connect_item"],$_POST["itemtype"]);
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
						$query="SELECT * 
							FROM glpi_computers_items 
							WHERE type='".$_POST["itemtype"]."' AND end1 = '$key'";
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
				$ci->setType($_POST["itemtype"],1);
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$ci->obj->delete(array("ID"=>$key));
					}
				}
			break;
			case "purge":
				$ci=new CommonItem();
				$ci->setType($_POST["itemtype"],1);
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$ci->obj->delete(array("ID"=>$key),1);
					}
				}
			break;
			case "restore":
				$ci=new CommonItem();
				$ci->setType($_POST["itemtype"],1);
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$ci->obj->restore(array("ID"=>$key));
					}
				}
			break;
			case "update":
				$searchopt=cleanSearchOption($_POST["itemtype"],'w');
				
				if (isset($searchopt[$_POST["id_field"]])){
					/// Infocoms case
					if ($_POST["itemtype"]<1000
                     && isInfocomSearch($_POST["itemtype"],$_POST["id_field"])){
						$ic=new Infocom();
						$ci=new CommonItem();
						$ci->setType($_POST["itemtype"],1);
		
						$link_entity_type=-1;
						/// Specific entity item
						if ($searchopt[$_POST["id_field"]]["table"]=="glpi_suppliers_infocoms"){
							$ent=new Enterprise();
							if ($ent->getFromDB($_POST[$_POST["field"]])){
								$link_entity_type=$ent->fields["entities_id"];
							}
							
						}
						
						foreach ($_POST["item"] as $key => $val){
							if ($val==1){
								if ($ci->getFromDB($_POST["itemtype"],$key)){
									if ($link_entity_type<0
										||$link_entity_type==$ci->obj->fields["entities_id"]
										||($ent->fields["recursive"]
                                 && in_array($link_entity_type, getAncestorsOf("glpi_entities",$ci->obj->fields["entities_id"])))){
										unset($ic->fields);
										$ic->update(array("itemtype"=>$_POST["itemtype"],"items_id"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
									}
								}
							}
						}
					} else { /// Not infocoms
						$ci=new CommonItem();
						$ci->setType($_POST["itemtype"],1);
						$link_entity_type=array();
						/// Specific entity item
						
						if ($searchopt[$_POST["id_field"]]["table"]!=$LINK_ID_TABLE[$_POST["itemtype"]]
						&& in_array($searchopt[$_POST["id_field"]]["table"],$CFG_GLPI["specif_entities_tables"])
						&& in_array($LINK_ID_TABLE[$_POST["itemtype"]],$CFG_GLPI["specif_entities_tables"])){
							
							$ci2=new CommonDBTM();
							$ci2->table=$searchopt[$_POST["id_field"]]["table"];
		
							if ($ci2->getFromDB($_POST[$_POST["field"]])){
								if (isset($ci2->fields["entities_id"])&&$ci2->fields["entities_id"]>=0){
									if (isset($ci2->fields["recursive"])&&$ci2->fields["recursive"]){
                              $link_entity_type=getSonsOf("glpi_entities",$ci2->fields["entities_id"]);
									} else {
										$link_entity_type[]=$ci2->fields["entities_id"];
									}
								}
		
							}
							
						}
						foreach ($_POST["item"] as $key => $val){
							if ($val==1) {
								if ($ci->getFromDB($_POST["itemtype"],$key)){
									if (count($link_entity_type)==0
										|| in_array($ci->obj->fields["entities_id"], $link_entity_type)){
										$ci->obj->update(array("ID"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
									}
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
						if ($comp->getFromDB($key)&&$comp->fields["entities_id"]==$_SESSION["glpiactive_entity"]){
							installSoftwareVersion($key,$_POST["vID"]);
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
			case "add_userprofile":
				$input['entities_id']=$_POST['entities_id'];
				$input['FK_profiles']=$_POST['FK_profiles'];
				$input['recursive']=$_POST['recursive'];
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$input['users_id']=$key;
						addUserProfileEntity($input);
					}
				}
			break;
			case "add_document":
				$ci=new CommonItem();
				$ci2=new CommonItem();
				if ($ci->getFromDB(DOCUMENT_TYPE,$_POST['docID'])){
					foreach ($_POST["item"] as $key => $val){
						if ($val==1) {
							/// Items exists ?
							if ($ci2->getFromDB($_POST["itemtype"],$key)){
								/// Entity security
								if ($_POST["itemtype"]==ENTITY_TYPE) {
								   $destentity = $ci2->obj->fields["ID"];
								} else if (isset($ci2->obj->fields["entities_id"])) {
								   $destentity = $ci2->obj->fields["entities_id"];
								} else {
								   $destentity = -1;
								}
								if ($destentity<0
								|| $ci->obj->fields["entities_id"]==$destentity
                        || ($ci->obj->fields["recursive"] && in_array($ci->obj->fields["entities_id"], getAncestorsOf("glpi_entities",$destentity)))){
									addDeviceDocument($_POST['docID'],$_POST["itemtype"],$key);
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
							/// Items exists ?
							if ($ci2->getFromDB($_POST["itemtype"],$key)){
								if ($_POST["itemtype"]==ENTITY_TYPE) {
								   $destentity = $ci2->obj->fields["ID"];
								} else if (isset($ci2->obj->fields["entities_id"])) {
								   $destentity = $ci2->obj->fields["entities_id"];
								} else {
								   $destentity = -1;
								}
								/// Entity security
								if ($destentity<0
                           ||$ci->obj->fields["entities_id"]==$destentity
                           ||($ci->obj->fields["recursive"] && in_array($ci->obj->fields["entities_id"], getAncestorsOf("glpi_entities",$destentity)))){
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
							/// Items exists ?
							if ($ci2->getFromDB($_POST["itemtype"],$key)){
								if ($_POST["itemtype"]==ENTITY_TYPE) {
								   $destentity = $ci2->obj->fields["ID"];
								} else if (isset($ci2->obj->fields["entities_id"])) {
								   $destentity = $ci2->obj->fields["entities_id"];
								} else {
								   $destentity = -1;
								}
								/// Entity security
								if ($destentity<0
                           ||$ci->obj->fields["entities_id"]==$destentity
                           ||($ci->obj->fields["recursive"]
                              && in_array($ci->obj->fields["entities_id"], getAncestorsOf("glpi_entities",$destentity)))){
									addDeviceContract($_POST['conID'],$_POST["itemtype"],$key);
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
							if ($ci2->getFromDB($_POST["itemtype"],$key)){
								// Entity security
								if (!isset($ci2->obj->fields["entities_id"])
								||$ci->obj->fields["entities_id"]==$ci2->obj->fields["entities_id"]
                        ||($ci->obj->fields["recursive"] && in_array($ci->obj->fields["entities_id"], getAncestorsOf("glpi_entities",$ci2->obj->fields["entities_id"])))){
									addContactEnterprise($_POST["entID"],$key);
								}
							}
						}
					}
				}
			break;
			case "change_auth_method":
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) 
						$ids[]=$key;
				}
			changeUserAuthMethod($ids,$_POST["auth_method"],$_POST["auth_server"]);

			break;
			case "unlock_ocsng_field":
				$fields=getOcsLockableFields();
				if ($_POST['field']=='all'||isset($fields[$_POST['field']])){
					foreach ($_POST["item"] as $key => $val){
						if ($val==1) {
							if ($_POST['field']=='all'){
								replaceOcsArray($key,array(),"computer_update");
							} else {
								deleteInOcsArray($key,$_POST['field'],"computer_update",true);
							}
						}
					}
				}
				break;
			case "unlock_ocsng_monitor":
			case "unlock_ocsng_printer":
			case "unlock_ocsng_peripheral":
			case "unlock_ocsng_software":
			case "unlock_ocsng_ip":
			case "unlock_ocsng_disk":
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						switch ($_POST["action"]){
							case "unlock_ocsng_monitor":
								ocsUnlockItems($key,"import_monitor");
								break;
							case "unlock_ocsng_printer":
								ocsUnlockItems($key,"import_printers");
								break;
							case "unlock_ocsng_peripheral":
								ocsUnlockItems($key,"import_peripheral");
								break;
							case "unlock_ocsng_software":
								ocsUnlockItems($key,"import_software");
								break;
							case "unlock_ocsng_ip":
								ocsUnlockItems($key,"import_ip");
								break;
							case "unlock_ocsng_disk":
								ocsUnlockItems($key,"import_disk");
								break;
						}
					}
				}
				break;

			case "force_ocsng_update":
				// First time
				if (!isset($_GET['multiple_actions'])){
					$_SESSION['glpi_massiveaction']['POST']=$_POST;
					$_SESSION['glpi_massiveaction']['REDIRECT']=$REDIRECT;
					$_SESSION['glpi_massiveaction']['items']=array();
					foreach ($_POST["item"] as $key => $val){
						if ($val==1) {
							$_SESSION['glpi_massiveaction']['items'][$key]=$key;
						}
					}
					$_SESSION['glpi_massiveaction']['item_count']=count($_SESSION['glpi_massiveaction']['items']);
					glpi_header($_SERVER['PHP_SELF'].'?multiple_actions=1');
				} else {
					if (count($_SESSION['glpi_massiveaction']['items'])>0){
						$key=array_pop($_SESSION['glpi_massiveaction']['items']);
						//Try to get the OCS server whose machine belongs
						$query = "SELECT ocs_server_id, ID
							FROM glpi_ocslinks
							WHERE glpi_id='".$key."'";
						$result = $DB->query($query);
						if ($DB->numrows($result) == 1) {                   
							$data = $DB->fetch_assoc($result);
							if ($data['ocs_server_id'] != -1){
							//Force update of the machine
							ocsUpdateComputer($data['ID'],$data['ocs_server_id'],1,1);
							}
						}
						glpi_header($_SERVER['PHP_SELF'].'?multiple_actions=1');
					} else {
						$REDIRECT=$_SESSION['glpi_massiveaction']['REDIRECT'];
						unset($_SESSION['glpi_massiveaction']);
						glpi_header($REDIRECT);
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
						$params["comments"]=$soft->fields["comments"];
						
						//Process rules
						$soft->update($softcatrule->processAllRules(null,$soft->fields,$params));
					}
				}
			break;

			case "replay_dictionnary":
				$softdictionnayrule = new DictionnarySoftwareCollection;
				$ids=array();
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) 
						$ids[]=$key;
				}
				$softdictionnayrule->replayRulesOnExistingDB(0,0,$ids);
			break;

			case "force_user_ldap_update":
				checkRight("user","w");

				$user = new User;
				$ids=array();
				foreach ($_POST["item"] as $key => $val){
					if ($val==1)
					{
						$user->getFromDB($key);
						if (($user->fields["auth_method"] == AUTH_LDAP) || ($user->fields["auth_method"] == AUTH_EXTERNAL))
							ldapImportUserByServerId($user->fields["name"],1,$user->fields["id_auth"]);
					} 
				}
			break;
			
			case "add_transfer_list":
				if (!isset($_SESSION['glpitransfer_list'])){
					$_SESSION['glpitransfer_list']=array();
				}
				if (!isset($_SESSION['glpitransfer_list'][$_POST["itemtype"]])){
					$_SESSION['glpitransfer_list'][$_POST["itemtype"]]=array();
				}
				
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$_SESSION['glpitransfer_list'][$_POST["itemtype"]][$key]=$key;
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
				$split=explode('_',$_POST["action"]);
				if ($split[0]=='plugin' && isset($split[1])){
					// Normalized name plugin_name_action
					// Allow hook from any plugin on any (core or plugin) type
					doOneHook($split[1],
						'MassiveActionsProcess',
						$_POST);
				}
				else if ($_POST["itemtype"]>1000
					&& isset($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]])){
					// non-normalized name
					// hook from the plugin defining the type
					doOneHook($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]],
						'MassiveActionsProcess',
						$_POST);
				} 
	
			break;
		}
	
		addMessageAfterRedirect($LANG['common'][23]);
		glpi_header($REDIRECT);
	
	} else { //action, itemtype or item not defined
		
		echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br>";
		echo "<b>".$LANG['common'][24]."</b></div>";
		
		displayBackLink();
	}
	
	commonFooter();
} // itemtype defined

?>
