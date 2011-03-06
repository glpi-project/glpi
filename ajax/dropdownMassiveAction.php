<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (isset($_POST["action"]) && isset($_POST["itemtype"]) && !empty($_POST["itemtype"])) {

   if (!class_exists($_POST['itemtype']) ) {
      exit();
   }

   if (isset($_POST['sub_type'])) {
      if (!class_exists($_POST['sub_type']) ) {
         exit();
      }
      $item = new $_POST["sub_type"]();
   } else {
      $item = new $_POST["itemtype"]();
   }

   if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])) {
      checkSeveralRightsOr(array($_POST["itemtype"] => "w",
                                 "infocom"          => "w"));
   } else {
      $item->checkGlobal("w");
   }

   echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
   echo "<input type='hidden' name='itemtype' value='".$_POST["itemtype"]."'>";
   echo '&nbsp;';

   switch($_POST["action"]) {
      case "activate_rule" :
         Dropdown::showYesNo("activate_rule");
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>\n";
         break;

      case 'move_under' :
         echo $LANG['setup'][75];
         Dropdown::show($_POST['itemtype'], array('name'     => 'parent',
                                                  'comments' => 0));
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>\n";
         break;

      case 'merge' :
         echo "&nbsp;".$_SESSION['glpiactive_entity_shortname'];
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>\n";
         break;

      case "move_rule" :
         echo "<select name='move_type'>";
         echo "<option value='after' selected>".$LANG['buttons'][47]."</option>";
         echo "<option value='before'>".$LANG['buttons'][46]."</option>";
         echo "</select>&nbsp;";

         if (isset($_POST['entity_restrict'])) {
            $condition = $_POST['entity_restrict'];
         } else {
            $condition = "";
         }
         Rule::dropdown(array('sub_type'        => $_POST['sub_type'],
                              'name'            => "ranking",
                              'entity_restrict' => $condition));
         echo "<input type='submit' name='massiveaction' class='submit' value='".
                $LANG['buttons'][2]."'>\n";
         break;

      case "add_followup" :
         TicketFollowup::showFormMassiveAction();
         break;

      case "add_task" :
         TicketTask::showFormMassiveAction();
         break;

      case "add_actor" :
         $types            = array(0                 => DROPDOWN_EMPTY_VALUE,
                                   Ticket::REQUESTER => $LANG['job'][4],
                                   Ticket::OBSERVER  => $LANG['common'][104],
                                   Ticket::ASSIGN    => $LANG['job'][5]);
         $rand             = Dropdown::showFromArray('actortype', $types);

         $paramsmassaction = array('actortype' => '__VALUE__');

         ajaxUpdateItemOnSelectEvent("dropdown_actortype$rand", "show_massiveaction_field",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionAddActor.php",
                                     $paramsmassaction);
         echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";

         break;

      case "submit_validation" :
         TicketValidation::showFormMassiveAction();
         break;

      case "validate_ticket" :
         TicketValidation::dropdownStatus("status");
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>\n";
         break;

      case "change_authtype" :
         $rand             = Auth::dropdown(array('name' => 'authtype'));
         $paramsmassaction = array('authtype' => '__VALUE__');

         ajaxUpdateItemOnSelectEvent("dropdown_authtype$rand", "show_massiveaction_field",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionAuthMethods.php",
                                     $paramsmassaction);
         echo "<span id='show_massiveaction_field'>";
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'></span>\n";
         break;

      case "compute_software_category" :
      case "replay_dictionnary" :
      case "force_ocsng_update" :
      case "force_user_ldap_update" :
      case "delete" :
      case "purge" :
      case "restore" :
      case "add_transfer_list" :
      case "activate_infocoms" :
      case "delete_email" :
      case 'reset':
         echo "<input type='submit' name='massiveaction' class='submit' value='".
                $LANG['buttons'][2]."'>\n";
         break;

      case "unlock_ocsng_field" :
         $fields['all'] = $LANG['common'][66];
         $fields       += OcsServer::getLockableFields();
         Dropdown::showFromArray("field", $fields);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "unlock_ocsng_monitor" :
      case "unlock_ocsng_peripheral" :
      case "unlock_ocsng_software" :
      case "unlock_ocsng_printer" :
      case "unlock_ocsng_disk" :
      case "unlock_ocsng_ip" :
         echo "<input type='submit' name='massiveaction' class='submit' value='".
                $LANG['buttons'][2]."'>";
         break;

      case "install" :
         Software::dropdownSoftwareToInstall("softwareversions_id",
                                             $_SESSION["glpiactive_entity"], 1);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][4]."'>";
         break;

      case "connect" :
         Computer_Item::dropdownConnect('Computer', $_POST["itemtype"], "connect_item");
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "connect_to_computer" :
         Dropdown::showAllItems("connect_item", 0, 0, $_SESSION["glpiactive_entity"],
                                array('Monitor', 'Peripheral', 'Phone',  'Printer'),
                                true);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "disconnect" :
         echo "<input type='submit' name='massiveaction' class='submit' value='".
                $LANG['buttons'][2]."'>";
         break;

      case "add_group" :
         Dropdown::show('Group');
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_userprofile" :
         Dropdown::show('Entity', array('entity' => $_SESSION['glpiactiveentities']));
         echo ".&nbsp;".$LANG['profiles'][22]."&nbsp;:&nbsp;";
         Profile::dropdownUnder();
         echo ".&nbsp;".$LANG['profiles'][28]."&nbsp;:&nbsp;";
         Dropdown::showYesNo("is_recursive", 0);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_document" :
         Document::dropdown(array('name' => 'docID'));
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_contract" :
         Contract::dropdown(array('name' => "conID"));
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_contact" :
         Dropdown::show('Contact', array('name' => "conID"));
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_enterprise" :
         Dropdown::show('Supplier', array('name' => "conID"));
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "import_email" :
         Dropdown::show('Entity');
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "add_user_to_email" :
         User::dropdown();
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "duplicate" :
         if ($item->isEntityAssign()) {
            Dropdown::show('Entity');
         }
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value='".
                      $LANG['buttons'][2]."'>";
         break;

      case "update" :
         $first_group    = true;
         $newgroup       = "";
         $items_in_group = 0;
         $show_all       = true;
         $show_infocoms  = true;

         $ic = new Infocom();
         if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])
             && (!$item->canUpdate() || !$ic->canUpdate())) {

            $show_all      = false;
            $show_infocoms = $ic->canUpdate();
         }
         $searchopt = Search::getCleanedOptions($_POST["itemtype"], 'w');

         echo "<select name='id_field' id='massiveaction_field'>";
         echo "<option value='0' selected>".DROPDOWN_EMPTY_VALUE."</option>";

         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               if (!empty($newgroup) && $items_in_group>0) {
                  echo $newgroup;
                  $first_group = false;
               }
               $items_in_group = 0;
               $newgroup       = "";
               if (!$first_group) {
                  $newgroup .= "</optgroup>";
               }
               $newgroup .= "<optgroup label=\"$val\">";

            } else {
               // No id and no entities_id massive action and no first item
               if ($val["field"]!='id'
                   && $key != 1
                   // Permit entities_id is explicitly activate
                   && ($val["linkfield"]!='entities_id'
                       || (isset($val['massiveaction']) && $val['massiveaction']))) {

                  if (!isset($val['massiveaction']) || $val['massiveaction']) {

                     if ($show_all) {
                        $newgroup .= "<option value='$key'>".$val["name"]."</option>";
                        $items_in_group++;

                     } else {
                        // Do not show infocom items
                        if (($show_infocoms && Search::isInfocomOption($_POST["itemtype"],$key))
                            || (!$show_infocoms && !Search::isInfocomOption($_POST["itemtype"],
                                                                            $key))) {

                           $newgroup .= "<option value='$key'>".$val["name"]."</option>";
                           $items_in_group++;
                        }
                     }
                  }
               }
            }
         }

         if (!empty($newgroup) && $items_in_group>0) {
            echo $newgroup;
         }
         if (!$first_group) {
            echo "</optgroup>";
         }
         echo "</select>";

         $paramsmassaction = array('id_field' => '__VALUE__',
                                   'itemtype' => $_POST["itemtype"]);

         foreach ($_POST as $key => $val) {
            if (preg_match("/extra_/",$key,$regs)) {
               $paramsmassaction[$key] = $val;
            }
         }
         ajaxUpdateItemOnSelectEvent("massiveaction_field", "show_massiveaction_field",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionField.php",
                                     $paramsmassaction);

         echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
         break;

      default :
         // Plugin specific actions
         $split = explode('_',$_POST["action"]);

         if ($split[0]=='plugin' && isset($split[1])) {
            // Normalized name plugin_name_action
            // Allow hook from any plugin on any (core or plugin) type
            doOneHook($split[1], 'MassiveActionsDisplay', array('itemtype' => $_POST["itemtype"],
                                                                'action'   => $_POST["action"]));

         } else if ($plug=isPluginItemType($_POST["itemtype"])) {
            // non-normalized name
            // hook from the plugin defining the type
            doOneHook($plug['plugin'], 'MassiveActionsDisplay', $_POST["itemtype"],
                      $_POST["action"]);
         }
   }
}

?>
