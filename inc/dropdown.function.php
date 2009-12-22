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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Functions Dropdown




/**
 * Make a select box for connections
 *
 * @param $itemtype type to connect
 * @param $fromtype from where the connection is
 * @param $myname select name
 * @param $entity_restrict Restrict to a defined entity
 * @param $onlyglobal display only global devices (used for templates)
 * @param $used Already used items ID: not to display in dropdown
 *
 * @return nothing (print out an HTML select box)
 */
function dropdownConnect($itemtype,$fromtype,$myname,$entity_restrict=-1,$onlyglobal=0,
                         $used=array()) {
   global $CFG_GLPI;

   $rand=mt_rand();

   $use_ajax=false;
   if ($CFG_GLPI["use_ajax"]) {
      $nb=0;
      if ($entity_restrict>=0) {
         $nb=countElementsInTableForEntity(getTableForItemType($itemtype),$entity_restrict);
      } else {
         $nb=countElementsInTableForMyEntities(getTableForItemType($itemtype));
      }
      if ($nb>$CFG_GLPI["ajax_limit_count"]) {
         $use_ajax=true;
      }
   }

   $params=array('searchText'       => '__VALUE__',
                 'fromtype'         => $fromtype,
                 'idtable'          => $itemtype,
                 'myname'           => $myname,
                 'onlyglobal'       => $onlyglobal,
                 'entity_restrict'  => $entity_restrict,
                 'used'             => $used);

   $default="<select name='$myname'><option value='0'>------</option></select>\n";
   ajaxDropdown($use_ajax,"/ajax/dropdownConnect.php",$params,$default,$rand);

   return $rand;
}

/**
 * Make a select box for  connected port
 *
 * @param $ID ID of the current port to connect
 * @param $myname select name
 * @param $entity_restrict Restrict to a defined entity (or an array of entities)
 * @return nothing (print out an HTML select box)
 */
function dropdownConnectPort($ID,$myname,$entity_restrict=-1) {
   global $LANG,$CFG_GLPI;

   $rand=mt_rand();
   echo "<select name='itemtype[$ID]' id='itemtype$rand'>";
   echo "<option value='0'>-----</option>";

   foreach ($CFG_GLPI["netport_types"] as $key => $itemtype) {
      if (class_exists($itemtype)) {
         $item = new $itemtype();
         echo "<option value='".$itemtype."'>".$item->getTypeName()."</option>";
      } else {
         unset($CFG_GLPI["netport_types"][$key]);
      }
   }
   echo "</select>";

   $params=array('itemtype'         => '__VALUE__',
                 'entity_restrict'  => $entity_restrict,
                 'current'          => $ID,
                 'myname'           => $myname);

   ajaxUpdateItemOnSelectEvent("itemtype$rand","show_$myname$rand",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownConnectPortDeviceType.php",$params);

   echo "<span id='show_$myname$rand'>&nbsp;</span>\n";

   return $rand;
}





/**
 * Dropdown of actions for massive action
 *
 * @param $itemtype item type
 * @param $is_deleted massive action for deleted items ?
 * @param $extraparams array of extra parameters
 */
function dropdownMassiveAction($itemtype,$is_deleted=0,$extraparams=array()) {
   /// TODO include in CommonDBTM defining only getAdditionalMassiveAction in sub classes
   /// for specific actions (return a array of action name and title)
   global $LANG,$CFG_GLPI,$PLUGIN_HOOKS;

   if (!class_exists($itemtype)) {
      return false;
   }
   $item = new $itemtype();
   $infocom= new Infocom();

   $isadmin=$item->canUpdate();

   echo '<select name="massiveaction" id="massiveaction">';
   echo '<option value="-1" selected>-----</option>';
   if (!in_array($itemtype,$CFG_GLPI["massiveaction_noupdate_types"])
       && ($isadmin ||(in_array($itemtype,$CFG_GLPI["infocom_types"])&& $infocom->canUpdate())
                    || ($itemtype == 'Ticket' && haveRight('update_ticket',1)))) {

      echo "<option value='update'>".$LANG['buttons'][14]."</option>";
   }

   if ($is_deleted) {
      if ($isadmin) {
         echo "<option value='purge'>".$LANG['buttons'][22]."</option>";
         echo "<option value='restore'>".$LANG['buttons'][21]."</option>";
      }
   } else {
      // No delete for entities and tracking of not have right
      if (!in_array($itemtype,$CFG_GLPI["massiveaction_nodelete_types"])
          && (($isadmin && $itemtype != 'Ticket')
              || ($itemtype == 'Ticket' && haveRight('delete_ticket',1)))) {

         echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
      }
      if ($isadmin && in_array($itemtype,array('Phone', 'Printer', 'Peripheral', 'Monitor'))) {

         echo "<option value='connect'>".$LANG['buttons'][9]."</option>";
         echo "<option value='disconnect'>".$LANG['buttons'][10]."</option>";
      }
      if (in_array($itemtype,$CFG_GLPI["doc_types"])) {
         $doc = new Document();
         if ($doc->canUpdate()) {
            echo "<option value='add_document'>".$LANG['document'][16]."</option>";
         }
      }

      if (in_array($itemtype,$CFG_GLPI["contract_types"])) {
         $contract = new Contract();
         if ($contract->canUpdate()) {
            echo "<option value='add_contract'>".$LANG['financial'][36]."</option>";
         }
      }
      if (haveRight('transfer','r') && isMultiEntitiesMode()
          && in_array($itemtype, array('CartridgeItem', 'Computer', 'ConsumableItem', 'Contact',
                                       'Contract', 'Supplier', 'Monitor', 'NetworkEquipment',
                                       'Peripheral', 'Phone', 'Printer', 'Software',
                                       'SoftwareLicense', 'Ticket', 'Document', 'Group', 'Link'))
          && $isadmin) {

         echo "<option value='add_transfer_list'>".$LANG['buttons'][48]."</option>";
      }
      switch ($itemtype) {
         case 'Software' :
            if ($isadmin
                && countElementsInTable("glpi_rules","sub_type='".RULE_SOFTWARE_CATEGORY."'") > 0) {
               echo "<option value=\"compute_software_category\">".$LANG['rulesengine'][38]." ".
                      $LANG['rulesengine'][40]."</option>";
            }
            if (haveRight("rule_dictionnary_software","w")
                && countElementsInTable("glpi_rules","sub_type='".RULE_DICTIONNARY_SOFTWARE."'") > 0) {
               echo "<option value=\"replay_dictionnary\">".$LANG['rulesengine'][76]."</option>";
            }
            break;

         case 'Computer' :
            if ($isadmin) {
               echo "<option value='connect_to_computer'>".$LANG['buttons'][9]."</option>";
               echo "<option value='install'>".$LANG['buttons'][4]."</option>";
               if ($CFG_GLPI['use_ocs_mode']) {
                  if (haveRight("ocsng","w") || haveRight("sync_ocsng","w")) {
                     echo "<option value='force_ocsng_update'>".$LANG['ocsng'][24]."</option>";
                  }
                  echo "<option value='unlock_ocsng_field'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][16]."</option>";
                  echo "<option value='unlock_ocsng_monitor'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][30]."</option>";
                  echo "<option value='unlock_ocsng_peripheral'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][32]."</option>";
                  echo "<option value='unlock_ocsng_printer'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][34]."</option>";
                  echo "<option value='unlock_ocsng_software'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][52]."</option>";
                  echo "<option value='unlock_ocsng_ip'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][50]."</option>";
                  echo "<option value='unlock_ocsng_disk'>".$LANG['buttons'][38]." ".
                         $LANG['Menu'][33]." - ".$LANG['ocsng'][55]."</option>";
               }
            }
            break;

         case 'Supplier' :
            if ($isadmin) {
               echo "<option value='add_contact'>".$LANG['financial'][24]."</option>";
            }
            break;

         case 'Contact' :
            if ($isadmin) {
               echo "<option value='add_enterprise'>".$LANG['financial'][25]."</option>";
            }
            break;

         case 'User' :
            if ($isadmin) {
               echo "<option value='add_group'>".$LANG['setup'][604]."</option>";
               echo "<option value='add_userprofile'>".$LANG['setup'][607]."</option>";
               echo "<option value='change_authtype'>".$LANG['login'][30]."</option>";
            }
            if (haveRight("user","w")) {
               echo "<option value='force_user_ldap_update'>".$LANG['ocsng'][24]."</option>";
            }
            break;

         case 'Ticket' :
            if (haveRight("comment_all_ticket","1")) {
               echo "<option value='add_followup'>".$LANG['job'][29]."</option>";
            }
            break;

         case 'CronTask' :
            echo "<option value='reset'>".$LANG['buttons'][16].
               " (".$LANG['crontask'][40].")</option>";
            break;

         case 'TicketCategory' :
         case 'TaskCategory' :
         case 'Location' :
            if ($isadmin) {
               echo "<option value='move_under'>".$LANG['buttons'][20]."</option>";
            }
            break;
      }

      // Plugin Specific actions
      if (isset($PLUGIN_HOOKS['use_massive_action'])) {
         foreach ($PLUGIN_HOOKS['use_massive_action'] as $plugin => $val) {
            $actions=doOneHook($plugin,'MassiveActions',$itemtype);
            if (count($actions)) {
               foreach ($actions as $key => $val) {
                  echo "<option value=\"$key\">$val</option>";
               }
            }
         }
      }
   }
   echo "</select>";

   $params=array('action'     => '__VALUE__',
                 'is_deleted' => $is_deleted,
                 'itemtype'   => $itemtype);

   if (count($extraparams)) {
      foreach ($extraparams as $key => $val) {
         $params['extra_'.$key]=$val;
      }
   }

   ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownMassiveAction.php",$params);

   echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

/**
 * Dropdown of actions for massive action of networking ports
 *
 * @param $itemtype item type
 */
function dropdownMassiveActionPorts($itemtype) {
   /// TODO try to include it in common dropdownMasiveAction management
   global $LANG,$CFG_GLPI;

   echo "<select name='massiveaction' id='massiveaction'>";

   echo "<option value='-1' selected>-----</option>";
   echo "<option value='delete'>".$LANG['buttons'][6]."</option>";
   echo "<option value='assign_vlan'>".$LANG['networking'][55]."</option>";
   echo "<option value='unassign_vlan'>".$LANG['networking'][58]."</option>";
   echo "<option value='move'>".$LANG['buttons'][20]."</option>";
   echo "</select>";

   $params=array('action'     => '__VALUE__',
                 'itemtype'   => $itemtype);

   ajaxUpdateItemOnSelectEvent("massiveaction","show_massiveaction",$CFG_GLPI["root_doc"].
                               "/ajax/dropdownMassiveActionPorts.php",$params);

   echo "<span id='show_massiveaction'>&nbsp;</span>\n";
}

/**
 * Dropdown for global item management
 *
 * @param $target target for actions
 * @param $withtemplate template or basic computer
 * @param $ID item ID
 * @param $value value of global state
 * @param $management_restrict global management restrict mode
 */
function globalManagementDropdown($target,$withtemplate,$ID,$value,$management_restrict=0) {
   global $LANG,$CFG_GLPI;

   if ($value && empty($withtemplate)) {
      echo $LANG['peripherals'][31];

      if ($management_restrict == 2) {
         echo "&nbsp;<a title=\"".$LANG['common'][39]."\" href=\"javascript:confirmAction('".addslashes($LANG['common'][40])."\\n".
                      addslashes($LANG['common'][39])."','$target?unglobalize=unglobalize&amp;id=$ID')\">".
                      $LANG['common'][38]."</a>&nbsp;";
         echo "<img alt=\"".$LANG['common'][39]."\" title=\"".$LANG['common'][39]."\" src=\"".
                $CFG_GLPI["root_doc"]."/pics/aide.png\">";
      }
   } else {

      if ($management_restrict == 2) {
         echo "<select name='is_global'>";
         echo "<option value='0' ".(!$value?" selected":"").">".$LANG['peripherals'][32]."</option>";
         echo "<option value='1' ".($value?" selected":"").">".$LANG['peripherals'][31]."</option>";
         echo "</select>";
      } else {
         // Templates edition
         if (!empty($withtemplate)) {
            echo "<input type='hidden' name='is_global' value=\"".$management_restrict."\">";
            echo (!$management_restrict?$LANG['peripherals'][32]:$LANG['peripherals'][31]);
         } else {
            echo (!$value?$LANG['peripherals'][32]:$LANG['peripherals'][31]);
         }
      }
   }
}



?>
