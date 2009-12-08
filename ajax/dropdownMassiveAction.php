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

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (isset($_POST["action"]) && isset($_POST["itemtype"]) && !empty($_POST["itemtype"])) {
   switch ($_POST["itemtype"]){
      case TRACKING_TYPE :
         checkTypeRight("update_ticket","1");
         break;

      default :
         if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])) {
            checkSeveralRightsOr(array($_POST["itemtype"]=>"w","infocom"=>"w"));
         } else {
            checkTypeRight($_POST["itemtype"],"w");
         }
         break;
   }

   echo "<input type='hidden' name='action' value='".$_POST["action"]."'>";
   echo "<input type='hidden' name='itemtype' value='".$_POST["itemtype"]."'>";
   switch($_POST["action"]) {
      case "activate_rule" :
         echo dropdownYesNo("activate_rule");
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >\n";
         break;

      case 'move_under' :
         echo '&nbsp;'.$LANG['setup'][75];
         CommonDropdown::dropdownValue($LINK_ID_TABLE[$_POST["itemtype"]], 'parent', '', 0);
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >\n";
         break;

      case "move_rule" :
         echo "<select name='move_type'>";
         echo "<option value='after' selected>".$LANG['buttons'][47]."</option>";
         echo "<option value='before'>".$LANG['buttons'][46]."</option>";
         echo "</select>&nbsp;";
         dropdownRules($_POST['sub_type'],"ranking");
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >\n";
         break;

      case "add_followup" :
         showAddFollowupForm(-1,true);
         break;

      case "change_authtype" :
         $rand = Auth::dropdown("authtype");
         $paramsmassaction=array('authtype'=>'__VALUE__');
         ajaxUpdateItemOnSelectEvent("dropdown_authtype$rand","show_massiveaction_field",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionAuthMethods.php",
                                     $paramsmassaction);
         echo "<span id='show_massiveaction_field'>";
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" ></span>\n";
         break;

      case "compute_software_category" :
      case "replay_dictionnary" :
      case "force_ocsng_update" :
      case "force_user_ldap_update" :
      case "delete" :
      case "purge" :
      case "restore" :
      case "add_transfer_list" :
      case 'reset':
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >\n";
         break;

      case "unlock_ocsng_field" :
         $fields['all']=$LANG['common'][66];
         $fields+=getOcsLockableFields();
         dropdownArrayValues("field",$fields);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "unlock_ocsng_monitor" :
      case "unlock_ocsng_peripheral" :
      case "unlock_ocsng_software" :
      case "unlock_ocsng_printer" :
      case "unlock_ocsng_disk" :
      case "unlock_ocsng_ip" :
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "install" :
         Software::dropdownSoftwareToInstall("softwareversions_id",$_SESSION["glpiactive_entity"],1);
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][4]."\" >";
         break;

      case "connect" :
         dropdownConnect(COMPUTER_TYPE,$_POST["itemtype"],"connect_item");
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "connect_to_computer" :
         dropdownAllItems("connect_item",0,0,$_SESSION["glpiactive_entity"],array(PHONE_TYPE,
                                                                                  MONITOR_TYPE,
                                                                                  PRINTER_TYPE,
                                                                                  PERIPHERAL_TYPE),
                          true);
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "disconnect" :
         echo "<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_group" :
         CommonDropdown::dropdownValue("glpi_groups","groups_id",0);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_userprofile" :
         CommonDropdown::dropdownValue("glpi_entities","entities_id",0,1,$_SESSION['glpiactiveentities']);
         echo ".&nbsp;".$LANG['profiles'][22]."&nbsp;:";
         dropdownUnderProfiles("profiles_id");
         echo ".&nbsp;".$LANG['profiles'][28]."&nbsp;:";
         dropdownYesNo("is_recursive",0);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_document" :
         Document::dropdown("docID");
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_contract" :
         CommonDropdown::dropdown("glpi_contracts","conID",1);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_contact" :
         CommonDropdown::dropdown("glpi_contacts","conID",1);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "add_enterprise" :
         CommonDropdown::dropdown("glpi_suppliers","entID",1);
         echo "&nbsp;<input type='submit' name='massiveaction' class='submit' value=\"".
               $LANG['buttons'][2]."\" >";
         break;

      case "update" :
         $first_group=true;
         $newgroup="";
         $items_in_group=0;
         $show_all=true;
         $show_infocoms=true;
         if (in_array($_POST["itemtype"],$CFG_GLPI["infocom_types"])
             && (!haveTypeRight($_POST["itemtype"],"w") || !haveTypeRight(INFOCOM_TYPE,"w"))) {
            $show_all=false;
            $show_infocoms=haveTypeRight(INFOCOM_TYPE,"w");
         }
         echo "<select name='id_field' id='massiveaction_field'>";
         echo "<option value='0' selected>------</option>";
         $searchopt=Search::getCleanedOptions($_POST["itemtype"],'w');
         foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
               if (!empty($newgroup) && $items_in_group>0) {
                  echo $newgroup;
                  $first_group=false;
               }
               $items_in_group=0;
               $newgroup="";
               if (!$first_group) {
                  $newgroup.="</optgroup>";
               }
               $newgroup.="<optgroup label='$val'>";
            } else {
               if ($key>1 && $key!=80) {// No entities_id massive action
                  // No ID
                  if (!empty($val["linkfield"])
                      ||($val["table"]=="glpi_infocoms" && $key!=120) // no end_warranty
                      ||$val["table"]=="glpi_suppliers_infocoms"
                      ||$val["table"]=="glpi_budgets"
                      ||($val["table"]=="glpi_ocslinks" && $key==101)) { // auto_update_ocs
                     if ($show_all) {
                        $newgroup.= "<option value='$key'>".$val["name"]."</option>";
                        $items_in_group++;
                     } else {
                        // Do not show infocom items
                        if (($show_infocoms && Search::isInfocomOption($_POST["itemtype"],$key))
                            || (!$show_infocoms && !Search::isInfocomOption($_POST["itemtype"],$key))) {
                           $newgroup.= "<option value='$key'>".$val["name"]."</option>";
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

         $paramsmassaction=array('id_field'=>'__VALUE__',
                                 'itemtype'=>$_POST["itemtype"]);

         foreach ($_POST as $key => $val) {
            if (preg_match("/extra_/",$key,$regs)) {
               $paramsmassaction[$key]=$val;
            }
         }
         ajaxUpdateItemOnSelectEvent("massiveaction_field","show_massiveaction_field",
                                     $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionField.php",
                                     $paramsmassaction);

         echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
         break;

      default :
         // Plugin specific actions
         $split=explode('_',$_POST["action"]);
         if ($split[0]=='plugin' && isset($split[1])) {
            // Normalized name plugin_name_action
            // Allow hook from any plugin on any (core or plugin) type
            doOneHook($split[1],'MassiveActionsDisplay',$_POST["itemtype"],$_POST["action"]);
         } else if (isPluginItemType($_POST["itemtype"])
                    && isset($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]])) {
            // non-normalized name
            // hook from the plugin defining the type
            doOneHook($PLUGIN_HOOKS['plugin_types'][$_POST["itemtype"]],'MassiveActionsDisplay',
                      $_POST["itemtype"],$_POST["action"]);
         }
         break;
   }
}

?>
