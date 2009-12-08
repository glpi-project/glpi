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


$NEEDED_ITEMS = array ('ocsadmininfoslink', 'cartridge', 'computer', 'consumable', 'contact',
   'contract', 'crontask', 'device', 'document', 'enterprise', 'entity', 'group', 'infocom',
   'ldap', 'link', 'mailgate', 'monitor', 'networking', 'ocsng', 'peripheral', 'phone', 'printer',
   'profile', 'registry', 'reservation', 'rulesengine', 'rule.dictionnary.dropdown',
   'rule.dictionnary.software', 'rule.right', 'rule.softwarecategories', 'search', 'setup',
   'software', 'tracking', 'transfer', 'typedoc', 'user');

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
            if (isset($_POST["connect_item"])&&$_POST["connect_item"]){
               $conn = new Computer_Item();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1) {
                     $input = array('computers_id' => $key,
                                    'itemtype'     => $_POST["itemtype"],
                                    'items_id'     => $_POST["connect_item"]);
                     if ($conn->can(-1, 'w', $input)) {
                        $conn->add($input);
                     }
                  }
               }
            }
            break;

         case "connect":
            if (isset($_POST["connect_item"])&&$_POST["connect_item"]){
               $conn = new Computer_Item();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1) {
                     $input = array('computers_id' => $_POST["connect_item"],
                                    'itemtype'     => $_POST["itemtype"],
                                    'items_id'     => $key);
                     if ($conn->can(-1, 'w', $input)) {
                        $conn->add($input);
                     }
                  }
               }
            }
            break;

			case "disconnect":
            $conn = new Computer_Item();
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
                  // TODO move this request in class
						$query="SELECT `id`
							FROM glpi_computers_items
							WHERE itemtype='".$_POST["itemtype"]."' AND items_id = '$key'";
						$result=$DB->query($query);
						if ($DB->numrows($result)>0){
							while ($data=$DB->fetch_assoc($result)){
                        if ($conn->can($data["id"],'w')) {
                           $conn->delete($data);
                        }
							}
						}
					}
				}
			break;
			case "delete":
            if (class_exists($_POST["itemtype"])) {
               $item=new $_POST["itemtype"]();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1) {
                     $item->delete(array("id"=>$key));
                  }
               }
            }
			break;
			case "purge":
            if (class_exists($_POST["itemtype"])) {
               $item=new $_POST["itemtype"]();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1) {
                     $item->delete(array("id"=>$key),1);
                  }
               }
            }
			break;
			case "restore":
            if (class_exists($_POST["itemtype"])) {
               $item=new $_POST["itemtype"]();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1) {
                     $item->restore(array("id"=>$key));
                  }
               }
            }
			break;
			case "update":
				$searchopt=Search::getCleanedOptions($_POST["itemtype"],'w');
				if (isset($searchopt[$_POST["id_field"]]) && class_exists($_POST["itemtype"])){
					/// Infocoms case
					if (!isPluginItem($_POST["itemtype"])
                     && Search::isInfocomOption($_POST["itemtype"],$_POST["id_field"])){
						$ic=new Infocom();
						$item =new $_POST["itemtype"]();

						$link_entity_type=-1;
						/// Specific entity item
						if ($searchopt[$_POST["id_field"]]["table"]=="glpi_suppliers_infocoms"){
							$ent=new Supplier();
							if ($ent->getFromDB($_POST[$_POST["field"]])){
								$link_entity_type=$ent->fields["entities_id"];
							}

						}

						foreach ($_POST["item"] as $key => $val){
							if ($val==1){
								if ($item->getFromDB($key)){
									if ($link_entity_type<0
										||$link_entity_type==$item->getEntityID()
										||($ent->fields["is_recursive"]
                                 && in_array($link_entity_type, getAncestorsOf("glpi_entities",$item->getEntityID())))){
										unset($ic->fields);
										$ic->update(array("itemtype"=>$_POST["itemtype"],"items_id"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
									}
								}
							}
						}
					} else { /// Not infocoms
						$item=new $_POST["itemtype"]();
						$link_entity_type=array();
						/// Specific entity item

						if ($searchopt[$_POST["id_field"]]["table"]!=$LINK_ID_TABLE[$_POST["itemtype"]]
						&& in_array($searchopt[$_POST["id_field"]]["table"],$CFG_GLPI["specif_entities_tables"])
						&& in_array($LINK_ID_TABLE[$_POST["itemtype"]],$CFG_GLPI["specif_entities_tables"])){

                     /// TODO : clean this / maybe need to review searchopt table field
							$ci2=new CommonDBTM();
							$ci2->table=$searchopt[$_POST["id_field"]]["table"];

							if ($ci2->getFromDB($_POST[$_POST["field"]])){
								if (isset($ci2->fields["entities_id"])&&$ci2->fields["entities_id"]>=0){
									if (isset($ci2->fields["is_recursive"])&&$ci2->fields["is_recursive"]){
                              $link_entity_type=getSonsOf("glpi_entities",$ci2->fields["entities_id"]);
									} else {
										$link_entity_type[]=$ci2->fields["entities_id"];
									}
								}

							}

						}
						foreach ($_POST["item"] as $key => $val){
							if ($val==1) {
								if ($item->getFromDB($key)){
									if (count($link_entity_type)==0
										|| in_array($item->fields["entities_id"], $link_entity_type)){
										$item->update(array("id"=>$key,$_POST["field"] => $_POST[$_POST["field"]]));
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
							installSoftwareVersion($key,$_POST["softwareversions_id"]);
						}
					}
				}
			break;

         case "add_group":
            $groupuser = new Group_User();
            foreach ($_POST["item"] as $key => $val){
               if ($val==1) {
                  $input = array('groups_id' => $key,
                                 'users_id'  => $_POST["group"]);
                  if ($groupuser->can(-1,'w',$input)) {
                     $groupuser->add($input);
                  }
               }
            }
            break;

			case "add_userprofile":
				$input['entities_id']=$_POST['entities_id'];
				$input['profiles_id']=$_POST['profiles_id'];
				$input['is_recursive']=$_POST['is_recursive'];
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$input['users_id']=$key;
						addUserProfileEntity($input);
					}
				}
			break;
			case "add_document":
            $documentitem=new Document_Item();
            foreach ($_POST["item"] as $key => $val){
               $input=array(
                  'itemtype' => $_POST["itemtype"],
                  'items_id' => $key,
                  'documents_id' => $_POST['docID']
               );
               if ($documentitem->can(-1,'w',$input)) {
                  $documentitem->add($input);
               }
            }
            break;
         case "add_contact":
            if ($_POST["itemtype"] == ENTERPRISE_TYPE) {
               $contactsupplier=new Contact_Supplier();
               foreach ($_POST["item"] as $key => $val){
                  $input=array(
                     'suppliers_id' => $key,
                     'contacts_id' => $_POST['conID']
                  );
                  if ($contactsupplier->can(-1,'w',$input)) {
                     $contactsupplier->add($input);
                  }
               }
            }
            break;
         case "add_contract":
            $contractitem=new Contract_Item();
            foreach ($_POST["item"] as $key => $val){
               $input=array(
                  'itemtype' => $_POST["itemtype"],
                  'items_id' => $key,
                  'contracts_id' => $_POST['conID']
               );
               if ($contractitem->can(-1,'w',$input)) {
                  $contractitem->add($input);
               }
            }
            break;
         case "add_enterprise":
            if ($_POST["itemtype"] == CONTACT_TYPE) {
               $contactsupplier=new Contact_Supplier();
               foreach ($_POST["item"] as $key => $val){
                  $input=array(
                     'suppliers_id' => $_POST['entID'],
                     'contacts_id' => $key
                  );
                  if ($contactsupplier->can(-1,'w',$input)) {
                     $contactsupplier->add($input);
                  }
               }
            }
            break;
			case "change_authtype":
				foreach ($_POST["item"] as $key => $val){
					if ($val==1)
						$ids[]=$key;
				}
			changeUserAuthMethod($ids,$_POST["authtype"],$_POST["auth_server"]);

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
								ocsUnlockItems($key,"import_printer");
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
						$query = "SELECT ocsservers_id, id
							FROM glpi_ocslinks
							WHERE computers_id='".$key."'";
						$result = $DB->query($query);
						if ($DB->numrows($result) == 1) {
							$data = $DB->fetch_assoc($result);
							if ($data['ocsservers_id'] != -1){
							//Force update of the machine
							ocsUpdateComputer($data['id'],$data['ocsservers_id'],1,1);
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
				$softcatrule = new RuleSoftwareCategoryCollection;
				$soft = new Software;
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$params = array();
						//Get software name and manufacturer
						$soft->getFromDB($key);
						$params["name"]=$soft->fields["name"];
						$params["manufacturers_id"]=$soft->fields["manufacturers_id"];
						$params["comment"]=$soft->fields["comment"];

						//Process rules
						$soft->update($softcatrule->processAllRules(null,$soft->fields,$params));
					}
				}
			break;

			case "replay_dictionnary":
				$softdictionnayrule = new RuleSoftwareCategoryCollection;
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
						if (($user->fields["authtype"] == AUTH_LDAP) || ($user->fields["authtype"] == AUTH_EXTERNAL))
							ldapImportUserByServerId($user->fields["name"],1,$user->fields["auths_id"]);
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
				$fup=new TicketFollowup();
				foreach ($_POST["item"] as $key => $val){
					if ($val==1) {
						$_POST['tickets_id']=$key;
						unset($fup->fields);
						$fup->add($_POST);
					}
				}
            break;

         case 'reset':
            if ($_POST["itemtype"]=='CronTask') {
               checkRight('config','w');
               $crontask=new CronTask();
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1 && $crontask->getFromDB($key)) {
                      $crontask->resetDate();
                  }
               }
            }
            break;

         case 'move_under':
            if (isset($_POST['parent']) && class_exists($_POST["itemtype"])) {
               $item = new $_POST["itemtype"]();

               $fk = getForeignKeyFieldForTable($item->table);
               foreach ($_POST["item"] as $key => $val){
                  if ($val==1 && $item->can($key,'w')) {
                      $item->update(array('id' => $key,
                                              $fk  => $_POST['parent']));
                  }
               }
            }
            break;

			default :

				// Plugin specific actions
				$split=explode('_',$_POST["action"]);
				if ($split[0]=='plugin' && isset($split[1])) {
					// Normalized name plugin_name_action
					// Allow hook from any plugin on any (core or plugin) type
					doOneHook($split[1],
						'MassiveActionsProcess',
						$_POST);
				} else if (isPluginItem($_POST["itemtype"])
					&& isset($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]])) {
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
