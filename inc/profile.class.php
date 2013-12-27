<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Profile class
class Profile extends CommonDBTM {

   // Specific ones

   /// Helpdesk fields of helpdesk profiles
   static public $helpdesk_rights = array('add_followups', 'create_ticket',
                                          'create_ticket_on_login', 'create_request_validation',
                                          'create_incident_validation',
                                          'faq', 'group_add_followups',
                                          'helpdesk_hardware', 'helpdesk_item_type',
                                          'observe_ticket', 'password_update', 'reminder_public',
                                          'reservation_helpdesk', 'rssfeed_public',
                                          'show_group_hardware', 'show_group_ticket',
                                          'ticketrecurrent',  'tickettemplates_id', 'ticket_cost',
                                          'update_own_followups', 'validate_incident',
                                          'validate_request');


   /// Common fields used for all profiles type
   static public $common_fields = array('id', 'interface', 'is_default', 'name');


   /// Fields not related to a basic right
   static public $noright_fields = array('comment', /*'change_status',*/ 'date_mod',
                                         'helpdesk_hardware','helpdesk_item_type', 'own_ticket',
                                         'problem_status', 'show_group_hardware',
                                         'show_group_ticket', 'ticket_status');



   var $dohistory = true;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Profile', 'Profiles', $nb);
   }


   static function canCreate() {
      return Session::haveRight('profile', 'w');
   }


   static function canView() {
      return Session::haveRight('profile', 'r');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Profile_User', $ong, $options);
      $this->addStandardTab('Log',$ong, $options);
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               if ($item->fields['interface'] == 'helpdesk') {
                  $ong[1] = __('Simplified interface'); // Helpdesk

               } else {
                  $ong[2] = sprintf(__('%1$s/%2$s'), sprintf(__('%1$s/%2$s'),__('Assets'), __('Management')), __('Tools'));
                  $ong[3] = __('Assistance');
                  $ong[4] = __('Life cycles');
                  $ong[5] = __('Administration');
                  $ong[6] = __('Setup');
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         $item->cleanProfile();
         switch ($tabnum) {
            case 1 :
               $item->showFormHelpdesk();
               break;

            case 2 :
               $item->showFormInventory();
               break;

            case 3 :
               $item->showFormTracking();
               break;

            case 4 :
               $item->showFormLifeCycle();
               break;

            case 5 :
               $item->showFormAdmin();
               break;
            case 6 :
               $item->showFormSetup();
               break;

         }
      }
      return true;
   }


   function post_updateItem($history=1) {
      global $DB;

      if (in_array('is_default',$this->updates) && ($this->input["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'";
         $DB->query($query);
      }
   }


   function post_addItem() {
      global $DB;

      if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
         $query = "UPDATE ". $this->getTable()."
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->fields['id']."'";
         $DB->query($query);
      }
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_profiles_users`
                WHERE `profiles_id` = '".$this->fields['id']."'";
      $DB->query($query);

      Rule::cleanForItemAction($this);
      // PROFILES and UNIQUE_PROFILE in RuleMailcollector
      Rule::cleanForItemCriteria($this, 'PROFILES');
      Rule::cleanForItemCriteria($this, 'UNIQUE_PROFILE');

      $gki = new KnowbaseItem_Profile();
      $gki->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gr = new Profile_Reminder();
      $gr->cleanDBonItemDelete($this->getType(), $this->fields['id']);

   }


   /**
    * check right before delete
    *
    * @since version 0.84
    *
    * @return boolean
   **/
   function pre_deleteItem() {
      global $DB;

      if (($this->fields['profile'] == 'w')
          && (countElementsInTable($this->getTable(), "`profile` = 'w'") == 1)) {
         Session::addMessageAfterRedirect(__("This profile is the last with write rights on profiles"),
                                          false, ERROR);
         Session::addMessageAfterRedirect(__("Deletion refused"),
                                          false, ERROR);
         return false;
      }
      return true;
   }


   function prepareInputForUpdate($input) {

      // Check for faq
      if (isset($input["interface"]) && ($input["interface"] == 'helpdesk')) {
         if (isset($input["faq"]) && ($input["faq"] == 'w')) {
            $input["faq"] == 'r';
         }
      }

      if (isset($input["_helpdesk_item_types"])) {
         if (isset($input["helpdesk_item_type"])) {
            $input["helpdesk_item_type"] = exportArrayToDB($input["helpdesk_item_type"]);
         } else {
            $input["helpdesk_item_type"] = exportArrayToDB(array());
         }
      }

      if (isset($input["_cycles_ticket"])) {
         $tab   = Ticket::getAllStatusArray();
         $cycle = array();
         foreach ($tab as $from => $label) {
            foreach ($tab as $dest => $label) {
               if (($from != $dest)
                   && ($input["_cycle_ticket"][$from][$dest] == 0)) {
                  $cycle[$from][$dest] = 0;
               }
            }
         }
         $input["ticket_status"] = exportArrayToDB($cycle);
      }

      if (isset($input["_cycles_problem"])) {
         $tab   = Problem::getAllStatusArray();
         $cycle = array();
         foreach ($tab as $from => $label) {
            foreach ($tab as $dest => $label) {
               if (($from != $dest)
                   && ($input["_cycle_problem"][$from][$dest] == 0)) {
                  $cycle[$from][$dest] = 0;
               }
            }
         }
         $input["problem_status"] = exportArrayToDB($cycle);
      }

//       if (isset($input["_cycles_change"])) {
//          $tab   = Change::getAllStatusArray();
//          $cycle = array();
//          foreach ($tab as $from => $label) {
//             foreach ($tab as $dest => $label) {
//                if (($from != $dest)
//                    && ($input["_cycle_change"][$from][$dest] == 0)) {
//                   $cycle[$from][$dest] = 0;
//                }
//             }
//          }
//          $input["change_status"] = exportArrayToDB($cycle);
//       }


      // check if right if the last write profile on Profile object
      if (($this->fields['profile'] == 'w')
          && isset($input['profile']) && ($input['profile'] != 'w')
          && (countElementsInTable($this->getTable(), "`profile` = 'w'") == 1)) {
         Session::addMessageAfterRedirect(__("This profile is the last with write rights on profiles"),
                                          false, ERROR);
         Session::addMessageAfterRedirect(__("Deletion refused"), false, ERROR);
         unset($input["profile"]);
      }



      return $input;
   }


   function prepareInputForAdd($input) {

      if (isset($input["helpdesk_item_type"])) {
         $input["helpdesk_item_type"] = exportArrayToDB($input["helpdesk_item_type"]);
      }
      return $input;
   }


   /**
    * Unset unused rights for helpdesk
   **/
   function cleanProfile() {

      if ($this->fields["interface"] == "helpdesk") {
         foreach ($this->fields as $key=>$val) {
            if (!in_array($key,self::$common_fields)
                && !in_array($key,self::$helpdesk_rights)) {
               unset($this->fields[$key]);
            }
         }
      }

      // decode array
      if (isset($this->fields["helpdesk_item_type"])
          && !is_array($this->fields["helpdesk_item_type"])) {

         $this->fields["helpdesk_item_type"] = importArrayFromDB($this->fields["helpdesk_item_type"]);
      }

      // Empty/NULL case
      if (!isset($this->fields["helpdesk_item_type"])
          || !is_array($this->fields["helpdesk_item_type"])) {

         $this->fields["helpdesk_item_type"] = array();
      }

      // Decode status array
      $fields_to_decode = array('ticket_status','problem_status'/*,'change_status'*/);
      foreach ($fields_to_decode as $val) {
         if (isset($this->fields[$val]) && !is_array($this->fields[$val])) {
            $this->fields[$val] = importArrayFromDB($this->fields[$val]);
            // Need to be an array not a null value
            if (is_null($this->fields[$val])) {
               $this->fields[$val] = array();
            }
         }
      }
   }


   /**
    * Get SQL restrict request to determine profiles with less rights than the active one
    *
    * @param $separator string   Separator used at the beginning of the request (default 'AND')
    *
    * @return SQL restrict string
   **/
   static function getUnderActiveProfileRetrictRequest($separator="AND") {

      $query = $separator ." ";

      // Not logged -> no profile to see
      if (!isset($_SESSION['glpiactiveprofile'])) {
         return $query." 0 ";
      }

      // Profile right : may modify profile so can attach all profile
      if (Session::haveRight("profile","w")) {
         return $query." 1 ";
      }

      if ($_SESSION['glpiactiveprofile']['interface']=='central') {
         $query .= " (`glpi_profiles`.`interface` = 'helpdesk') " ;
      }

      $query .= " OR (`glpi_profiles`.`interface` = '".$_SESSION['glpiactiveprofile']['interface']."' ";
      foreach ($_SESSION['glpiactiveprofile'] as $key => $val) {
         if (!is_array($val) // Do not include entities field added by login
             && !in_array($key,self::$common_fields)
             && !in_array($key,self::$noright_fields)
             && (($_SESSION['glpiactiveprofile']['interface'] == 'central')
                 || in_array($key,self::$helpdesk_rights))) {

            switch ($val) {
               case '0' :
                  $query .= " AND (`glpi_profiles`.`$key` IS NULL
                                   OR `glpi_profiles`.`$key` IN ('0', '')) ";
                  break;

               case '1' :
                  $query .= " AND (`glpi_profiles`.`$key` IS NULL
                                   OR `glpi_profiles`.`$key` IN ('0', '1', '')) ";
                  break;

               case 'r' :
                  $query .= " AND (`glpi_profiles`.`$key` IS NULL
                                   OR `glpi_profiles`.`$key` IN ('r', '')) ";
                  break;

               case 'w' :
                  $query .= " AND (`glpi_profiles`.`$key` IS NULL
                                   OR `glpi_profiles`.`$key` IN ('w', 'r', '')) ";
                  break;

               default :
                  $query .= " AND (`glpi_profiles`.`$key` IS NULL
                                   OR `glpi_profiles`.`$key` IN ('0', '')) ";
            }
         }
      }
      $query .= ")";
      return $query;
   }


   /**
    * Is the current user have more right than all profiles in parameters
    *
    * @param $IDs array of profile ID to test
    *
    * @return boolean true if have more right
   **/
   static function currentUserHaveMoreRightThan($IDs=array()) {
      global $DB;

      if (count($IDs) == 0) {
         // Check all profiles (means more right than all possible profiles)
         return (countElementsInTable('glpi_profiles')
                     == countElementsInTable('glpi_profiles',
                                             self::getUnderActiveProfileRetrictRequest('')));
      }
      $under_profiles = array();
      $query          = "SELECT *
                         FROM `glpi_profiles` ".
                         self::getUnderActiveProfileRetrictRequest("WHERE");
      $result         = $DB->query($query);

      while ($data = $DB->fetch_assoc($result)) {
         $under_profiles[$data['id']] = $data['id'];
      }

      foreach ($IDs as $ID) {
         if (!isset($under_profiles[$ID])) {
            return false;
         }
      }
      return true;
   }


   function showLegend() {

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><td width='70' style='text-decoration:underline' class='b'>";
      echo __('Caption')."</td>";
      echo "<td class='tab_bg_4' width='15' style='border:1px solid black'></td>";
      echo "<td class='b'>".__('Global right')."</td></tr>\n";
      echo "<tr class='tab_bg_2'><td></td>";
      echo "<td class='tab_bg_2' width='15' style='border:1px solid black'></td>";
      echo "<td class='b'>".__('Entity right')."</td></tr>";
      echo "</table></div>\n";
   }


   function post_getEmpty() {

      $this->fields["interface"] = "helpdesk";
      $this->fields["name"]      = __('Without name');
   }


   /**
    * Print the profile form headers
    *
    * @param $ID        integer : Id of the item to print
    * @param $options   array of possible options
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
    **/
   function showForm($ID, $options=array()) {

      $onfocus = "";
      $new     = false;
      $rowspan = 5;
      if ($ID > 0) {
         $rowspan++;
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $onfocus = "onfocus=\"if (this.value=='".$this->fields["name"]."') this.value='';\"";
         $new     = true;
      }

      $rand = mt_rand();

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td><input type='text' name='name' value=\"".$this->fields["name"]."\" $onfocus></td>";
      echo "<td rowspan='$rowspan' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='$rowspan'>";
      echo "<textarea cols='45' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Default profile')."</td><td>";
      Dropdown::showYesNo("is_default", $this->fields["is_default"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__("Profile's interface")."</td>";
      echo "<td>";
      Dropdown::showFromArray('interface', self::getInterfaces(),
                              array('value'=>$this->fields["interface"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Update password')."</td><td>";
      Dropdown::showYesNo("password_update", $this->fields["password_update"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Ticket creation form on login')."</td><td>";
      Dropdown::showYesNo("create_ticket_on_login", $this->fields["create_ticket_on_login"]);
      echo "</td></tr>\n";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'><td>".__('Last update')."</td>";
         echo "<td>";
         echo ($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                         : __('Never'));
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print the helpdesk right form for the current profile
   **/
   function showFormHelpdesk() {
      global $CFG_GLPI;

      if (!Session::haveRight("profile","r")) {
         return false;
      }
      if ($canedit = Session::haveRight("profile","w")) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>".__('Assistance')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Create a ticket')."</td><td>";
      Dropdown::showYesNo("create_ticket", $this->fields["create_ticket"]);
      echo "</td>";
      echo "<td>".__('Add a followup to tickets (requester)')."</td><td>";
      Dropdown::showYesNo("add_followups", $this->fields["add_followups"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Default ticket template')."</td><td>";
      // Only root entity ones and recursive

      $options = array('value'     => $this->fields["tickettemplates_id"],
                       'entity'    => 0);
      if (Session::isMultiEntitiesMode()) {
         $options['condition'] = '`is_recursive` = 1';
      }
      TicketTemplate::dropdown($options);
      echo "</td>";
      echo "<td>".__('Add a followup to tickets of associated groups')."</td><td>";
      Dropdown::showYesNo("group_add_followups", $this->fields["group_add_followups"]);
      echo "</td>";

      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See public followups and tasks')."</td><td>";
      Dropdown::showYesNo("observe_ticket", $this->fields["observe_ticket"]);
      echo "</td>";
      echo "<td>".__('See tickets created by my groups')."</td><td>";
      Dropdown::showYesNo("show_group_ticket", $this->fields["show_group_ticket"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See hardware of my group(s)')."</td><td>";
      Dropdown::showYesNo("show_group_hardware", $this->fields["show_group_hardware"]);
      echo "</td>";
      echo "<td>".__('Update followups (author)')."</td><td>";
      Dropdown::showYesNo("update_own_followups", $this->fields["update_own_followups"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Link with items for the creation of tickets')."</td>";
      echo "<td>";
      Dropdown::showFromArray('helpdesk_hardware', self::getHelpdeskHardwareTypes(),
                              array('value' => $this->fields["helpdesk_hardware"]));
      echo "</td>\n";
      echo "<td>".__('Associable items to a ticket')."</td>";
      echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
      self::dropdownHelpdeskItemtypes(array('values' => $this->fields["helpdesk_item_type"]));
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Create a validation request for a request')."</td><td>";
      Dropdown::showYesNo("create_request_validation", $this->fields["create_request_validation"]);
      echo "<td>".__('Create a validation request for an incident')."</td><td>";
      Dropdown::showYesNo("create_incident_validation", $this->fields["create_incident_validation"]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Validate a request')."</td><td>";
      Dropdown::showYesNo("validate_request", $this->fields["validate_request"]);
      echo "<td>".__('Validate an incident')."</td><td>";
      Dropdown::showYesNo("validate_incident", $this->fields["validate_incident"]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'><th colspan='4'>".__('Tools')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('FAQ')."</td><td>";
      if (($this->fields["interface"] == "helpdesk")
          && ($this->fields["faq"] == 'w')) {
         $this->fields["faq"] = 'r';
      }
      self::dropdownRight("faq", array('value'   => $this->fields["faq"],
                                       'nowrite' => true));
      echo "</td>";
      echo "<td>"._n('Reservation', 'Reservations', 2)."</td><td>";
      Dropdown::showYesNo("reservation_helpdesk", $this->fields["reservation_helpdesk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Public reminder', 'Public reminders', 2)."</td><td>";
      self::dropdownRight("reminder_public", array('value'   => $this->fields["reminder_public"],
                                                   'nowrite' => true));
      echo "</td>";
      echo "<td>"._n('Public RSS feed', 'Public RSS feeds', 2)."</td><td>";
      self::dropdownRight("rssfeed_public", array('value'   => $this->fields["rssfeed_public"],
                                                  'nowrite' => true));
      echo "</td>";
      echo "</td></tr>\n";

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\"".__s('Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
   }


   /**
    * Print the Inventory/Management/Toolsd right form for the current profile
    *
    * @param $openform  boolean open the form (true by default)
    * @param $closeform boolean close the form (true by default)
   **/
   function showFormInventory($openform=true, $closeform=true) {

      if (!Session::haveRight("profile","r")) {
         return false;
      }
      if (($canedit=Session::haveRight("profile","w")) && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";

      // Inventory
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Assets')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Computer', 'Computers', 2)."</td><td>";
      self::dropdownRight("computer", array('value' => $this->fields["computer"]));
      echo "</td>";
      echo "<td>"._n('Monitor', 'Monitors', 2)."</td><td>";
      self::dropdownRight("monitor", array('value' => $this->fields["monitor"]));
      echo "</td>";
      echo "<td>"._n('Software', 'Software', 2)."</td><td>";
      self::dropdownRight("software", array('value' => $this->fields["software"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Network', 'Networks', 2)."</td><td>";
      self::dropdownRight("networking", array('value' => $this->fields["networking"]));
      echo "</td>";
      echo "<td>"._n('Printer', 'Printers', 2)."</td><td>";
      self::dropdownRight("printer", array('value' => $this->fields["printer"]));
      echo "</td>";
      echo "<td>"._n('Cartridge', 'Cartridges', 2)."</td><td>";
      self::dropdownRight("cartridge", array('value' => $this->fields["cartridge"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Consumable', 'Consumables', 2)."</td><td>";
      self::dropdownRight("consumable",  array('value' => $this->fields["consumable"]));

      echo "</td>";
      echo "<td>"._n('Phone', 'Phones', 2)."</td><td>";
      self::dropdownRight("phone", array('value' => $this->fields["phone"]));
      echo "</td>";
      echo "<td>"._n('Device', 'Devices', 2)."</td><td>";
      self::dropdownRight("peripheral", array('value' => $this->fields["peripheral"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Internet')."</td><td>";
      self::dropdownRight("internet", array('value' => $this->fields["internet"]));
      echo "</td>\n";
      echo "<td colspan='4'>&nbsp;</td></tr>";

      // Gestion / Management
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Management')."</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Contacts', 'Contacts', 2)." / "._n('Supplier', 'Suppliers', 2)."</td><td>";
      self::dropdownRight("contact_enterprise",
                          array('value' => $this->fields["contact_enterprise"]));
      echo "</td>";
      echo "<td>"._n('Document', 'Documents', 2)."</td><td>";
      self::dropdownRight("document", array('value' => $this->fields["document"]));
      echo "</td>";
      echo "<td>"._n('Contract', 'Contracts', 2)."</td><td>";
      self::dropdownRight("contract", array('value' => $this->fields["contract"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>".__('Financial and administratives information')."</td><td>";
      self::dropdownRight("infocom", array('value' => $this->fields["infocom"]));
      echo "</td>";
      echo "<td>".__('Budget')."</td><td colspan='3'>";
      self::dropdownRight("budget", array('value' => $this->fields["budget"]));
      echo "</td></tr>\n";

      // Outils / Tools
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Tools')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Public reminder', 'Public reminders', 2)."</td><td>";
      self::dropdownRight("reminder_public", array('value' => $this->fields["reminder_public"]));
      echo "</td>";
      echo "<td>"._n('Public RSS feed', 'Public RSS feeds', 2)."</td><td>";
      self::dropdownRight("rssfeed_public", array('value' => $this->fields["rssfeed_public"]));
      echo "</td>";
      echo "<td>"._n('Public bookmark', 'Public bookmarks', 2)."</td><td>";
      self::dropdownRight("bookmark_public", array('value' => $this->fields["bookmark_public"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('FAQ')."</td><td>";
      self::dropdownRight("faq", array('value' => $this->fields["faq"]));
      echo "</td>";
      echo "<td>"._n('Report', 'Reports', 2)."</td><td>";
      self::dropdownRight("reports", array('value'   => $this->fields["reports"],
                                           'nowrite' => true));
      echo "</td>";
      echo "<td>"._n('Reservation', 'Reservations', 2)."</td><td>";
      Dropdown::showYesNo("reservation_helpdesk", $this->fields["reservation_helpdesk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Knowledge base')."</td><td>";
      self::dropdownRight("knowbase", array('value' => $this->fields["knowbase"]));
      echo "</td>";
      echo "<td>".__('Knowledge base administration')."</td><td>";
      Dropdown::showYesNo("knowbase_admin", $this->fields["knowbase_admin"]);
      echo "</td>";
      echo "<td>".__('Administration of reservations')."</td><td colspan='3'>";
      self::dropdownRight("reservation_central",
                          array('value' => $this->fields["reservation_central"]));
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Item notes')."</td><td>";
      self::dropdownRight("notes", array('value' => $this->fields["notes"]));
      echo "</td>";
      echo "<td colspan='4'>";
      echo "</td></tr>";

      if ($canedit
          && $closeform) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='6' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";
   }


   /**
    * Print the Tracking right form for the current profile
    *
    * @param $openform     boolean  open the form (true by default)
    * @param $closeform    boolean  close the form (true by default)
   **/
   function showFormTracking($openform=true, $closeform=true) {
      global $CFG_GLPI;

      if (!Session::haveRight("profile","r")) {
         return false;
      }
      if (($canedit = Session::haveRight("profile","w"))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixe'>";

      // Assistance / Tracking-helpdesk
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Assistance')."</th></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Creation')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Create a ticket')."</td><td>";
      Dropdown::showYesNo("create_ticket", $this->fields["create_ticket"]);
      echo "</td>";
      echo "<td>".__('Add a followup to tickets (requester)')."</td><td>";
      Dropdown::showYesNo("add_followups", $this->fields["add_followups"]);
      echo "</td>";
      echo "<td>".__('Add a followup to all tickets')."</td><td>";
      Dropdown::showYesNo("global_add_followups", $this->fields["global_add_followups"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Add a followup to tickets of associated groups')."</td><td>";
      Dropdown::showYesNo("group_add_followups", $this->fields["group_add_followups"]);
      echo "</td>";
      echo "<td>".__('Add a task to all tickets')."</td><td>";
      Dropdown::showYesNo("global_add_tasks", $this->fields["global_add_tasks"]);
      echo "</td>";
      echo "<td>".__('Default ticket template')."</td><td>";
      // Only root entity ones and recursive
      $options = array('value'     => $this->fields["tickettemplates_id"],
                       'entity'    => 0);

      if (Session::isMultiEntitiesMode()) {
         $options['condition'] = '`is_recursive` = 1';
      }
      $entity = implode(",", $_SESSION['glpiactiveentities']);
      if ($entity != 0) {
         $options['addicon'] = false;
      }

      TicketTemplate::dropdown($options);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Ticket cost', 'Ticket costs', 2)."</td><td>";
      self::dropdownRight("ticketcost",
                          array('value'   => $this->fields["ticketcost"]));

      echo "</td>";
      echo "<td>"._n('Ticket template', 'Ticket templates', 2)."</td><td>";
      self::dropdownRight("tickettemplate",
                          array('value'   => $this->fields["tickettemplate"]));
      echo "</td>";
      echo "<td>".__('Recurrent tickets')."</td><td>";
      self::dropdownRight("ticketrecurrent",
                          array('value'   => $this->fields["ticketrecurrent"]));
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>"._x('noun','Update')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Update a ticket')."</td><td>";
      Dropdown::showYesNo("update_ticket", $this->fields["update_ticket"]);
      echo "</td>";
      echo "<td>".__('Change the priority')."</td><td>";
      Dropdown::showYesNo("update_priority", $this->fields["update_priority"]);
      echo "</td>";
      echo "<td>".__('Edit all tasks')."</td><td>";
      Dropdown::showYesNo("update_tasks", $this->fields["update_tasks"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Update followups (author)')."</td><td>";
      Dropdown::showYesNo("update_own_followups", $this->fields["update_own_followups"]);
      echo "</td>";
      echo "<td>".__('Update all followups')."</td><td>";
      Dropdown::showYesNo("update_followups", $this->fields["update_followups"]);
      echo "</td>\n";
      echo "<td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Deletion')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__("Ticket's deletion")."</td><td>";
      Dropdown::showYesNo("delete_ticket", $this->fields["delete_ticket"]);
      echo "</td>";
      echo "<td>".__('Delete all followups')."</td><td>";
      Dropdown::showYesNo("delete_followups", $this->fields["delete_followups"]);
      echo "</td>\n";
      echo "<td>".__('Delete all validations')."</td><td>";
      Dropdown::showYesNo("delete_validations", $this->fields["delete_validations"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Approval')."</th><";
      echo "/tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Create a validation request for a request')."</td><td>";
      Dropdown::showYesNo("create_request_validation", $this->fields["create_request_validation"]);
      echo "<td>".__('Create a validation request for an incident')."</td><td>";
      Dropdown::showYesNo("create_incident_validation", $this->fields["create_incident_validation"]);
      echo "</td>";
      echo "<td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Validate a request')."</td><td>";
      Dropdown::showYesNo("validate_request", $this->fields["validate_request"]);
      echo "<td>".__('Validate an incident')."</td><td>";
      Dropdown::showYesNo("validate_incident", $this->fields["validate_incident"]);
      echo "<td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Assignment')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('To be in charge of a ticket')."</td><td>";
      Dropdown::showYesNo("own_ticket", $this->fields["own_ticket"]);
      echo "<td>".__('Steal a ticket')."</td><td>";
      Dropdown::showYesNo("steal_ticket", $this->fields["steal_ticket"]);
      echo "</td>";
      echo "<td>".__('Assign a ticket')."</td><td>";
      Dropdown::showYesNo("assign_ticket", $this->fields["assign_ticket"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Association')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See hardware of my groups')."</td><td>";
      Dropdown::showYesNo("show_group_hardware", $this->fields["show_group_hardware"]);
      echo "</td>";
      echo "<td>".__('Link with items for the creation of tickets')."</td>";
      echo "\n<td>";
      Dropdown::showFromArray('helpdesk_hardware', self::getHelpdeskHardwareTypes(),
                              array('value' => $this->fields["helpdesk_hardware"]));

      echo "</td>\n";
      echo "<td>".__('Associable items to a ticket')."</td>";
      echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
      self::dropdownHelpdeskItemtypes(array('values' => $this->fields["helpdesk_item_type"]));
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>".__('Visibility')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See assigned tickets (personnal + group associated)')."</td><td>";
      Dropdown::showYesNo("show_assign_ticket", $this->fields["show_assign_ticket"]);
      echo "</td>";
      echo "<td>".__('See tickets created by my groups')."</td><td>";
      Dropdown::showYesNo("show_group_ticket", $this->fields["show_group_ticket"]);
      echo "</td>";
      echo "<td>".__('See all tickets')."</td><td>";
      Dropdown::showYesNo("show_all_ticket", $this->fields["show_all_ticket"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See public followups and tasks')."</td><td>";
      Dropdown::showYesNo("observe_ticket", $this->fields["observe_ticket"]);
      echo "</td>";
      echo "<td>".__('See all followups and tasks (public and private)')."</td><td>";
      Dropdown::showYesNo("show_full_ticket", $this->fields["show_full_ticket"]);
      echo "</td>";
      echo "<td>".__('Statistics')."</td><td>";
      Dropdown::showYesNo("statistic", $this->fields["statistic"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See personnal planning')."</td><td>";
      Dropdown::showYesNo("show_planning", $this->fields["show_planning"]);
      echo "</td>";
      echo "<td>".__('See schedule of people in my groups')."</td><td>";
      Dropdown::showYesNo("show_group_planning", $this->fields["show_group_planning"]);
      echo "</td>";
      echo "<td>".__('See all plannings')."</td><td>";
      Dropdown::showYesNo("show_all_planning", $this->fields["show_all_planning"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_5'><th colspan='6'>"._n('Problem', 'Problems', 2);
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See all problems')."</td><td>";
      Dropdown::showYesNo("show_all_problem", $this->fields["show_all_problem"]);
      echo "</td>";
      echo "<td>".__('See problems (author)')."</td><td>";
      Dropdown::showYesNo("show_my_problem", $this->fields["show_my_problem"]);
      echo "</td>";
      echo "<td>".__('Update all problems')."</td><td>";
      Dropdown::showYesNo("edit_all_problem", $this->fields["edit_all_problem"]);
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Delete all problems')."</td><td>";
      Dropdown::showYesNo("delete_problem", $this->fields["delete_problem"]);
      echo "</td>";
      echo "<td colspan='4'>";
      echo "</td>";
      echo "</tr>\n";

//       echo "<tr class='tab_bg_2'>";
//       echo "<td>".__('Update all changes')."</td><td>";
//       Dropdown::showYesNo("edit_all_change", $this->fields["edit_all_change"]);
//       echo "</td>";
//       echo "<td>".__('See all changes')."</td><td>";
//       Dropdown::showYesNo("show_all_change", $this->fields["show_all_change"]);
//       echo "</td>";
//       echo "<td>".__('See changes (author)')."</td><td>";
//       Dropdown::showYesNo("show_my_change", $this->fields["show_my_change"]);
//       echo "</td>";
//       echo "</tr>\n";

      if ($canedit
          && $closeform) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='6' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";
   }


   /**
   * Print the Life Cycles form for the current profile
   *
   * @param $openform   boolean  open the form (true by default)
   * @param $closeform  boolean  close the form (true by default)
   **/
   function showFormLifeCycle($openform=true, $closeform=true) {

      if (!Session::haveRight("profile","r")) {
         return false;
      }

      if (($canedit = Session::haveRight("profile","w"))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<div class='spaced'>";

      echo "<table class='tab_cadre_fixe'>";
      $tabstatus = Ticket::getAllStatusArray();

      echo "<th colspan='".(count($tabstatus)+1)."'>".__('Life cycle of tickets')."</th>";
      //TRANS: \ to split row heading (From) and colums headin (To) for life cycles
      echo "<tr class='tab_bg_1'><td class='b center'>".__("From \ To");
      echo "<input type='hidden' name='_cycles_ticket' value='1'</td>";
      foreach ($tabstatus as $label) {
         echo "<td class='center'>$label</td>";
      }
      echo "</tr>\n";

      foreach ($tabstatus as $from => $label) {
         echo "<tr class='tab_bg_2'><td class='tab_bg_1'>$label</td>";
         foreach ($tabstatus as $dest => $label) {
            echo "<td class='center'>";
            if ($dest == $from) {
               echo Dropdown::getYesNo(1);
            } else {
               Dropdown::showYesNo("_cycle_ticket[$from][$dest]",
                                   (!isset($this->fields['ticket_status'][$from][$dest])
                                    || $this->fields['ticket_status'][$from][$dest]));
            }
            echo "</td>";
         }
         echo "</tr>\n";
      }
      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      $tabstatus = Problem::getAllStatusArray();

      echo "<th colspan='".(count($tabstatus)+1)."'>".__('Life cycle of problems')."</th>";
      echo "<tr class='tab_bg_1'><td class='b center'>".__('From \ To');
      echo "<input type='hidden' name='_cycles_problem' value='1'</td>";
      foreach ($tabstatus as $label) {
         echo "<td class='center'>$label</td>";
      }
      echo "</tr>\n";

      foreach ($tabstatus as $from => $label) {
         echo "<tr class='tab_bg_2'><td class='tab_bg_1'>$label</td>";
         foreach ($tabstatus as $dest => $label) {
            echo "<td class='center'>";
            if ($dest == $from) {
               echo Dropdown::getYesNo(1);
            } else {
               Dropdown::showYesNo("_cycle_problem[$from][$dest]",
                                   (!isset($this->fields['problem_status'][$from][$dest])
                                    || $this->fields['problem_status'][$from][$dest]));
            }
            echo "</td>";
         }
         echo "</tr>\n";
      }

      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      $tabstatus = Change::getAllStatusArray();

//       echo "<th colspan='".(count($tabstatus)+1)."'>".__('Life cycle of changes')."</th>";
//       echo "<tr class='tab_bg_1'><td class='b center'>".__('From \ To');
//       echo "<input type='hidden' name='_cycles_change' value='1'</td>";
//       foreach ($tabstatus as $label) {
//          echo "<td class='center'>$label</td>";
//       }
//       echo "</tr>\n";
//
//       foreach ($tabstatus as $from => $label) {
//          echo "<tr class='tab_bg_2'><td class='tab_bg_1'>$label</td>";
//          foreach ($tabstatus as $dest => $label) {
//             echo "<td class='center'>";
//             if ($dest == $from) {
//                echo Dropdown::getYesNo(1);
//             } else {
//                Dropdown::showYesNo("_cycle_change[$from][$dest]",
//                                    (!isset($this->fields['change_status'][$from][$dest])
//                                     || $this->fields['change_status'][$from][$dest]));
//             }
//             echo "</td>";
//          }
//          echo "</tr>\n";
//       }

      if ($canedit
          && $closeform) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='".(count($tabstatus)+1)."' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";
   }


   /**
    * Print the central form for a profile
    *
    * @param $openform     boolean  open the form (true by default)
    * @param $closeform    boolean  close the form (true by default)
   **/
   function showFormAdmin($openform=true, $closeform=true) {

      if (!Session::haveRight("profile","r")) {
         return false;
      }

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRight("profile","w"))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<table class='tab_cadre_fixe'><tr>";

      // Administration
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Administration')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('User', 'Users', 2)."</td><td>";
      self::dropdownRight("user", array('value' => $this->fields["user"]));

      echo "</td>";
      echo "<td>"._n('Group', 'Groups', 2)."</td><td>";
      self::dropdownRight("group", array('value' => $this->fields["group"]));
      echo "</td>";
      echo "<td>".__('Method for user authentication and synchronization')."</td><td>";
      self::dropdownRight("user_authtype", array('value' => $this->fields["user_authtype"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>"._n('Entity', 'Entities', 2)."</td><td>";
      self::dropdownRight("entity", array('value' => $this->fields["entity"]));
      echo "</td>";
      echo "<td>".__('Transfer')."</td><td>";
      self::dropdownRight("transfer", array('value' => $this->fields["transfer"]));
      echo "</td>";
      echo "<td>".self::getTypeName(2)."</td><td>";
      self::dropdownRight("profile", array('value' => $this->fields["profile"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('Maintenance')."</td><td>";
      self::dropdownRight("backup", array('value'   => $this->fields["backup"],
                                          'noread'  => true));
      echo "</td>";
      echo "<td>"._n('Log', 'Logs', 2)."</td><td>";
      self::dropdownRight("logs", array('value'   => $this->fields["logs"],
                                        'nowrite' => true));
      echo "</td>";

      echo "<td class='tab_bg_2'>".__('Add users from an external source')."</td>";
      echo "<td class='tab_bg_2'>";
      self::dropdownRight("import_externalauth_users",
                          array('value'   => $this->fields["import_externalauth_users"],
                                'noread'  => true));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><th colspan='6'>"._n('Rule', 'Rules', 2)."</th>";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('Authorizations assignment rules')."</td><td>";
      self::dropdownRight("rule_ldap", array('value' => $this->fields["rule_ldap"]));
      echo "</td>";
      echo "<td>".__('Rules for assigning a computer to an entity')."</td><td>";
      self::dropdownRight("rule_import", array('value' => $this->fields["rule_import"]));
      echo "</td>";
      echo "<td>".__('Rules for assigning a ticket created through a mails receiver')."</td><td>";
      self::dropdownRight("rule_mailcollector",
                          array('value' => $this->fields["rule_mailcollector"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('Rules for assigning a category to a software')."</td><td>";
      self::dropdownRight("rule_softwarecategories",
                          array('value' => $this->fields["rule_softwarecategories"]));
      echo "</td>";
      echo "<td>".__('Business rules for tickets')."</td><td>";
      self::dropdownRight("rule_ticket", array('value'   => $this->fields["rule_ticket"],
                                               'nowrite' => true));
      echo "</td>";
      echo "<td class='tab_bg_1'>".__('Business rules for tickets (entity)')."</td>";
      echo "<td class='tab_bg_1'>";
      self::dropdownRight("entity_rule_ticket",
                          array('value' => $this->fields["entity_rule_ticket"]));
      echo"</td></tr>\n";

      echo "<tr class='tab_bg_1'><th colspan='6'>"._n('Dictionary', 'Dictionaries', 2)."</th></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('Dropdowns dictionary')."</td><td>";
      self::dropdownRight("rule_dictionnary_dropdown",
                          array('value' => $this->fields["rule_dictionnary_dropdown"]));
      echo"</td>";
      //TRANS: software in plural
      echo "<td>".__('Software dictionary')."</td><td>";
      self::dropdownRight("rule_dictionnary_software",
                          array('value' => $this->fields["rule_dictionnary_software"]));
      echo "</td>";
      echo "<td>".__('Printers dictionnary')."</td><td>";
      self::dropdownRight("rule_dictionnary_printer",
                          array('value' => $this->fields["rule_dictionnary_printer"]));
      echo "</td></tr>";


      if ($canedit
          && $closeform) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='6' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";

      $this->showLegend();
   }

   /**
    * Print the central form for a profile
    *
    * @param $openform     boolean  open the form (true by default)
    * @param $closeform    boolean  close the form (true by default)
   **/
   function showFormSetup($openform=true, $closeform=true) {

      if (!Session::haveRight("profile","r")) {
         return false;
      }

      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRight("profile","w"))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<table class='tab_cadre_fixe'>";

      // Setup
      echo "<tr class='tab_bg_1'><th colspan='6'>".__('Setup')."</th></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('General setup')."</td><td>";
      self::dropdownRight("config", array('value'   => $this->fields["config"],
                                          'noread'  => true));

      echo "</td>";
      echo "<td>".__('Search result default display')."</td><td>";
      self::dropdownRight("search_config_global",
                          array('value'   => $this->fields["search_config_global"],
                                'noread'  => true));
      echo "</td>";
      echo "<td class='tab_bg_2'>".__('Search result user display')."</td>";
      echo "<td class='tab_bg_2'>";
      self::dropdownRight("search_config", array('value'   => $this->fields["search_config"],
                                                 'noread'  => true));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>"._n('Component', 'Components', 2)."</td><td>";
      self::dropdownRight("device", array('value'   => $this->fields["device"],
                                          'noread'  => true));
      echo "</td>";
      echo "<td>"._n('Dropdown', 'Dropdowns', 2)."</td><td>";
      self::dropdownRight("dropdown", array('value' => $this->fields["dropdown"]));
      echo "</td>";
      echo "<td class='tab_bg_2'>".__('Entity dropdowns')."</td>";
      echo "<td class='tab_bg_2'>";
      self::dropdownRight("entity_dropdown", array('value' => $this->fields["entity_dropdown"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_4'>";
      echo "<td>".__('Document type')."</td><td>";
      self::dropdownRight("typedoc", array('value' => $this->fields["typedoc"]));
      echo "</td>";
      echo "<td>"._n('External link', 'External links',2)."</td><td>";
      self::dropdownRight("link", array('value' => $this->fields["link"]));
      echo "</td>";
      echo "<td>".__('Check for upgrade')."</td><td>";
      self::dropdownRight("check_update", array('value'   => $this->fields["check_update"],
                                                'nowrite' => true));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Notification', 'Notifications',2)."</td><td>";
      self::dropdownRight("notification", array('value' => $this->fields["notification"]));
      echo "</td>";
      echo "<td>"._n('Calendar', 'Calendars', 2)."</td><td>";
      self::dropdownRight("calendar", array('value' => $this->fields["calendar"]));
      echo "</td>\n";
      echo "<td>".__('Assistance')."</td><td>";
      self::dropdownRight("entity_helpdesk", array('value' => $this->fields["entity_helpdesk"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('SLA')."</td><td>";
      self::dropdownRight("sla", array('value' => $this->fields["sla"]));
      echo "</td>";
      echo "<td colspan='4'>&nbsp;";
      echo "</td></tr>\n";

      if ($canedit
          && $closeform) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='6' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";

      $this->showLegend();
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'interface';
      $tab[2]['name']            = __("Profile's interface");
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'specific';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'is_default';
      $tab[3]['name']            = __('Default profile');
      $tab[3]['datatype']        = 'bool';
      $tab[3]['massiveaction']   = false;

      $tab[118]['table']         = $this->getTable();
      $tab[118]['field']         = 'create_ticket_on_login';
      $tab[118]['name']          = __('Ticket creation form on login');
      $tab[118]['datatype']      = 'bool';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab['inventory']          = __('Assets');

      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'computer';
      $tab[20]['name']           = _n('Computer', 'Computers', 2);
      $tab[20]['datatype']       = 'right';

      $tab[21]['table']          = $this->getTable();
      $tab[21]['field']          = 'monitor';
      $tab[21]['name']           = _n('Monitor', 'Monitors', 2);
      $tab[21]['datatype']       = 'right';

      $tab[22]['table']          = $this->getTable();
      $tab[22]['field']          = 'software';
      $tab[22]['name']           = _n('Software', 'Software', 2);
      $tab[22]['datatype']       = 'right';

      $tab[23]['table']          = $this->getTable();
      $tab[23]['field']          = 'networking';
      $tab[23]['name']           = _n('Network', 'Networks', 2);
      $tab[23]['datatype']       = 'right';

      $tab[24]['table']          = $this->getTable();
      $tab[24]['field']          = 'printer';
      $tab[24]['name']           = _n('Printer', 'Printers',2);
      $tab[24]['datatype']       = 'right';

      $tab[25]['table']          = $this->getTable();
      $tab[25]['field']          = 'peripheral';
      $tab[25]['name']           = _n('Device', 'Devices', 2);
      $tab[25]['datatype']       = 'right';

      $tab[26]['table']          = $this->getTable();
      $tab[26]['field']          = 'cartridge';
      $tab[26]['name']           = _n('Cartridge', 'Cartridges', 2);
      $tab[26]['datatype']       = 'right';

      $tab[27]['table']          = $this->getTable();
      $tab[27]['field']          = 'consumable';
      $tab[27]['name']           = _n('Consumable', 'Consumables', 2);
      $tab[27]['datatype']       = 'right';

      $tab[28]['table']          = $this->getTable();
      $tab[28]['field']          = 'phone';
      $tab[28]['name']           = _n('Phone', 'Phones', 2);
      $tab[28]['datatype']       = 'right';

      $tab[29]['table']          = $this->getTable();
      $tab[29]['field']          = 'notes';
      $tab[29]['name']           = __('Notes');
      $tab[29]['datatype']       = 'right';

      $tab[129]['table']         = $this->getTable();
      $tab[129]['field']         = 'internet';
      $tab[129]['name']          = __('Internet');
      $tab[129]['datatype']      = 'right';

      $tab['management']         = __('Management');

      $tab[30]['table']          = $this->getTable();
      $tab[30]['field']          = 'contact_enterprise';
      $tab[30]['name']           = __('Contact')." / ".__('Supplier');
      $tab[30]['datatype']       = 'right';

      $tab[31]['table']          = $this->getTable();
      $tab[31]['field']          = 'document';
      $tab[31]['name']           = _n('Document', 'Documents', 2);
      $tab[31]['datatype']       = 'right';

      $tab[32]['table']          = $this->getTable();
      $tab[32]['field']          = 'contract';
      $tab[32]['name']           = _n('Contract', 'Contracts', 2);
      $tab[32]['datatype']       = 'right';

      $tab[33]['table']          = $this->getTable();
      $tab[33]['field']          = 'infocom';
      $tab[33]['name']           = __('Financial and administratives information');
      $tab[33]['datatype']       = 'right';

      $tab[101]['table']         = $this->getTable();
      $tab[101]['field']         = 'budget';
      $tab[101]['name']          = __('Budget');
      $tab[101]['datatype']      = 'right';

      $tab['tools']              = __('Tools');

      $tab[34]['table']          = $this->getTable();
      $tab[34]['field']          = 'knowbase';
      $tab[34]['name']           = __('Knowledge base');
      $tab[34]['datatype']       = 'right';

      $tab[35]['table']          = $this->getTable();
      $tab[35]['field']          = 'faq';
      $tab[35]['name']           = __('FAQ');
      $tab[35]['datatype']       = 'right';

      $tab[36]['table']          = $this->getTable();
      $tab[36]['field']          = 'reservation_helpdesk';
      $tab[36]['name']           = _n('Reservation', 'Reservations', 2);
      $tab[36]['datatype']       = 'bool';

      $tab[37]['table']          = $this->getTable();
      $tab[37]['field']          = 'reservation_central';
      $tab[37]['name']           = __('Administration of reservations');
      $tab[37]['datatype']       = 'bool';

      $tab[38]['table']          = $this->getTable();
      $tab[38]['field']          = 'reports';
      $tab[38]['name']           = _n('Report', 'Reports', 2);
      $tab[38]['datatype']       = 'right';
      $tab[38]['nowrite']        = true;

      $tab['config']             = __('Setup');

      $tab[42]['table']          = $this->getTable();
      $tab[42]['field']          = 'dropdown';
      $tab[42]['name']           = _n('Dropdown', 'Dropdowns', 2);
      $tab[42]['datatype']       = 'right';

      $tab[43]['table']          = $this->getTable();
      $tab[43]['field']          = 'entity_dropdown';
      $tab[43]['name']           = __('Entity dropdowns');
      $tab[43]['datatype']       = 'right';

      $tab[44]['table']          = $this->getTable();
      $tab[44]['field']          = 'device';
      $tab[44]['name']           = _n('Component', 'Components', 2);
      $tab[44]['datatype']       = 'right';
      $tab[44]['noread']         = true;

      $tab[106]['table']         = $this->getTable();
      $tab[106]['field']         = 'notification';
      $tab[106]['name']          = _n('Notification', 'Notifications',2);
      $tab[106]['datatype']      = 'right';

      $tab[45]['table']          = $this->getTable();
      $tab[45]['field']          = 'typedoc';
      $tab[45]['name']           = __('Document type');
      $tab[45]['datatype']       = 'right';

      $tab[46]['table']          = $this->getTable();
      $tab[46]['field']          = 'link';
      $tab[46]['name']           = _n('External link', 'External links',2);
      $tab[46]['datatype']       = 'right';

      $tab[47]['table']          = $this->getTable();
      $tab[47]['field']          = 'config';
      $tab[47]['name']           = __('General setup');
      $tab[47]['datatype']       = 'right';
      $tab[47]['noread']         = true;

      $tab[52]['table']          = $this->getTable();
      $tab[52]['field']          = 'search_config';
      $tab[52]['name']           = __('Search result user display');
      $tab[52]['datatype']       = 'right';
      $tab[52]['noread']         = true;

      $tab[53]['table']          = $this->getTable();
      $tab[53]['field']          = 'search_config_global';
      $tab[53]['name']           = __('Search result default display');
      $tab[53]['datatype']       = 'right';
      $tab[53]['noread']         = true;

      $tab[107]['table']         = $this->getTable();
      $tab[107]['field']         = 'calendar';
      $tab[107]['name']          = _n('Calendar', 'Calendars', 2);
      $tab[107]['datatype']      = 'right';

      $tab['admin']              = __('Administration');

      $tab[48]['table']          = $this->getTable();
      $tab[48]['field']          = 'rule_ticket';
      $tab[48]['name']           = __('Business rules for tickets');
      $tab[48]['datatype']       = 'right';
      $tab[48]['nowrite']        = true;

      $tab[105]['table']         = $this->getTable();
      $tab[105]['field']         = 'rule_mailcollector';
      $tab[105]['name']          = __('Rules for assigning a ticket created through a mails receiver');
      $tab[105]['datatype']      = 'right';

      $tab[49]['table']          = $this->getTable();
      $tab[49]['field']          = 'rule_import';
      $tab[49]['name']           = __('Rules for assigning a computer to an entity');
      $tab[49]['datatype']       = 'right';

      $tab[50]['table']          = $this->getTable();
      $tab[50]['field']          = 'rule_ldap';
      $tab[50]['name']           = __('Authorizations assignment rules');
      $tab[50]['datatype']       = 'right';

      $tab[51]['table']          = $this->getTable();
      $tab[51]['field']          = 'rule_softwarecategories';
      $tab[51]['name']           = __('Rules for assigning a category to a software');
      $tab[51]['datatype']       = 'right';

      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'rule_dictionnary_software';
      $tab[90]['name']           = __('Software dictionary');
      $tab[90]['datatype']       = 'right';

      $tab[91]['table']          = $this->getTable();
      $tab[91]['field']          = 'rule_dictionnary_dropdown';
      $tab[91]['name']           =__('Dropdowns dictionary');
      $tab[91]['datatype']       = 'right';

      $tab[93]['table']          = $this->getTable();
      $tab[93]['field']          = 'entity_rule_ticket';
      $tab[93]['name']           = __('Business rules for tickets (entity)');
      $tab[93]['datatype']       = 'right';

      $tab[54]['table']          = $this->getTable();
      $tab[54]['field']          = 'check_update';
      $tab[54]['name']           = __('Check for upgrade');
      $tab[54]['datatype']       = 'right';
      $tab[54]['nowrite']        = true;

      $tab[55]['table']          = $this->getTable();
      $tab[55]['field']          = 'profile';
      $tab[55]['name']           = self::getTypeName(2);
      $tab[55]['datatype']       = 'right';

      $tab[56]['table']          = $this->getTable();
      $tab[56]['field']          = 'user';
      $tab[56]['name']           = _n('User', 'Users', 2);
      $tab[56]['datatype']       = 'right';

      $tab[57]['table']          = $this->getTable();
      $tab[57]['field']          = 'user_authtype';
      $tab[57]['name']           = __('Method for user authentication and synchronization');
      $tab[57]['datatype']       = 'right';

      $tab[104]['table']         = $this->getTable();
      $tab[104]['field']         = 'import_externalauth_users';
      $tab[104]['name']          = __('Add users from an external source');
      $tab[104]['datatype']      = 'right';
      $tab[104]['noread']        = true;

      $tab[58]['table']          = $this->getTable();
      $tab[58]['field']          = 'group';
      $tab[58]['name']           = _n('Group', 'Groups', 2);
      $tab[58]['datatype']       = 'right';

      $tab[59]['table']          = $this->getTable();
      $tab[59]['field']          = 'entity';
      $tab[59]['name']           = _n('Entity', 'Entities', 2);
      $tab[59]['datatype']       = 'right';

      $tab[60]['table']          = $this->getTable();
      $tab[60]['field']          = 'transfer';
      $tab[60]['name']           = __('Transfer');
      $tab[60]['datatype']       = 'right';

      $tab[61]['table']          = $this->getTable();
      $tab[61]['field']          = 'logs';
      $tab[61]['name']           = _n('Log', 'Logs', 2);
      $tab[61]['datatype']       = 'right';
      $tab[61]['nowrite']        = true;

      $tab[62]['table']          = $this->getTable();
      $tab[62]['field']          = 'backup';
      $tab[62]['name']           = __('Maintenance');
      $tab[62]['datatype']       = 'right';
      $tab[62]['noread']         = true;

      $tab['ticket']             = __('Assistance');

      $tab[102]['table']         = $this->getTable();
      $tab[102]['field']         = 'create_ticket';
      $tab[102]['name']          = __('Create a ticket');
      $tab[102]['datatype']      = 'bool';

      $tab[108]['table']         = 'glpi_tickettemplates';
      $tab[108]['field']         = 'name';
      $tab[108]['name']          = __('Default ticket template');
      $tab[108]['datatype']      = 'dropdown';
      if (Session::isMultiEntitiesMode()) {
         $tab[108]['condition']     = '`entities_id` = 0 AND `is_recursive` = 1';
      } else {
         $tab[108]['condition']     = '`entities_id` = 0';
      }

      $tab[103]['table']         = $this->getTable();
      $tab[103]['field']         = 'tickettemplate';
      $tab[103]['name']          = _n('Ticket template', 'Ticket templates', 2);
      $tab[103]['datatype']      = 'right';

      $tab[65]['table']          = $this->getTable();
      $tab[65]['field']          = 'delete_ticket';
      $tab[65]['name']           = __("Ticket's deletion");
      $tab[65]['datatype']       = 'bool';

      $tab[66]['table']          = $this->getTable();
      $tab[66]['field']          = 'add_followups';
      $tab[66]['name']           = __('Add a followup to tickets (requester)');
      $tab[66]['datatype']       = 'bool';

      $tab[67]['table']          = $this->getTable();
      $tab[67]['field']          = 'global_add_followups';
      $tab[67]['name']           = __('Add a followup to all tickets');
      $tab[67]['datatype']       = 'bool';

      $tab[68]['table']          = $this->getTable();
      $tab[68]['field']          = 'update_ticket';
      $tab[68]['name']           = __('Update a ticket');
      $tab[68]['datatype']       = 'bool';

      $tab[69]['table']          = $this->getTable();
      $tab[69]['field']          = 'own_ticket';
      $tab[69]['name']           = __('To be in charge of a ticket');
      $tab[69]['datatype']       = 'bool';

      $tab[70]['table']          = $this->getTable();
      $tab[70]['field']          = 'steal_ticket';
      $tab[70]['name']           = __('Steal a ticket');
      $tab[70]['datatype']       = 'bool';

      $tab[71]['table']          = $this->getTable();
      $tab[71]['field']          = 'assign_ticket';
      $tab[71]['name']           = __('Assign a ticket');
      $tab[71]['datatype']       = 'bool';

      $tab[72]['table']          = $this->getTable();
      $tab[72]['field']          = 'show_all_ticket';
      $tab[72]['name']           = __('See all tickets');
      $tab[72]['datatype']       = 'bool';

      $tab[73]['table']          = $this->getTable();
      $tab[73]['field']          = 'show_assign_ticket';
      $tab[73]['name']           = __('See assigned tickets (personnal + group associated)');
      $tab[73]['datatype']       = 'bool';

      $tab[74]['table']          = $this->getTable();
      $tab[74]['field']          = 'show_full_ticket';
      $tab[74]['name']           = __('See all followups and tasks (public and private)');
      $tab[74]['datatype']       = 'bool';

      $tab[75]['table']          = $this->getTable();
      $tab[75]['field']          = 'observe_ticket';
      $tab[75]['name']           = __('See public followups and tasks');
      $tab[75]['datatype']       = 'bool';

      $tab[76]['table']          = $this->getTable();
      $tab[76]['field']          = 'update_followups';
      $tab[76]['name']           = __('Update all followups');
      $tab[76]['datatype']       = 'bool';

      $tab[77]['table']          = $this->getTable();
      $tab[77]['field']          = 'show_planning';
      $tab[77]['name']           = __('See personnal planning');
      $tab[77]['datatype']       = 'bool';

      $tab[78]['table']          = $this->getTable();
      $tab[78]['field']          = 'show_group_planning';
      $tab[78]['name']           = __('See schedule of people in my groups');
      $tab[78]['datatype']       = 'bool';

      $tab[79]['table']          = $this->getTable();
      $tab[79]['field']          = 'show_all_planning';
      $tab[79]['name']           = __('See all plannings');
      $tab[79]['datatype']       = 'bool';

      $tab[80]['table']          = $this->getTable();
      $tab[80]['field']          = 'update_own_followups';
      $tab[80]['name']           = __('Update followups (author)');
      $tab[80]['datatype']       = 'bool';

      $tab[81]['table']          = $this->getTable();
      $tab[81]['field']          = 'delete_followups';
      $tab[81]['name']           = __('Delete all followups');
      $tab[81]['datatype']       = 'bool';

      $tab[121]['table']          = $this->getTable();
      $tab[121]['field']          = 'delete_validations';
      $tab[121]['name']           = __('Delete all validations');
      $tab[121]['datatype']       = 'bool';

      $tab[85]['table']          = $this->getTable();
      $tab[85]['field']          = 'statistic';
      $tab[85]['name']           = __('Statistics');
      $tab[85]['datatype']       = 'bool';

      $tab[119]['table']         = $this->getTable();
      $tab[119]['field']         = 'ticketcost';
      $tab[119]['name']          = _n('Ticket cost', 'Ticket costs', 2);
      $tab[119]['datatype']      = 'right';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'helpdesk_hardware';
      $tab[86]['name']           = __('Link with items for the creation of tickets');
      $tab[86]['massiveaction']  = false;
      $tab[86]['datatype']       = 'specific';

      $tab[87]['table']          = $this->getTable();
      $tab[87]['field']          = 'helpdesk_item_type';
      $tab[87]['name']           = __('Associable items to a ticket');
      $tab[87]['massiveaction']  = false;
      $tab[87]['datatype']       = 'specific';

      $tab[88]['table']          = $this->getTable();
      $tab[88]['field']          = 'show_group_ticket';
      $tab[88]['name']           = __('See tickets created by my groups');
      $tab[88]['datatype']       = 'bool';

      $tab[89]['table']          = $this->getTable();
      $tab[89]['field']          = 'show_group_hardware';
      $tab[89]['name']           = __('See hardware of my groups');
      $tab[89]['datatype']       = 'bool';

      $tab[94]['table']          = $this->getTable();
      $tab[94]['field']          = 'group_add_followups';
      $tab[94]['name']           = __('Add a followup to tickets of associated groups');
      $tab[94]['datatype']       = 'bool';

      $tab[95]['table']          = $this->getTable();
      $tab[95]['field']          = 'global_add_tasks';
      $tab[95]['name']           = __('Add a task to all tickets');
      $tab[95]['datatype']       = 'bool';

      $tab[96]['table']          = $this->getTable();
      $tab[96]['field']          = 'update_priority';
      $tab[96]['name']           = __('Change the priority');
      $tab[96]['datatype']       = 'bool';

      $tab[97]['table']          = $this->getTable();
      $tab[97]['field']          = 'update_tasks';
      $tab[97]['name']           = __('Edit all tasks');
      $tab[97]['datatype']       = 'bool';

      $tab[98]['table']          = $this->getTable();
      $tab[98]['field']          = 'validate_request';
      $tab[98]['name']           = __('Validate a request');
      $tab[98]['datatype']       = 'bool';

      $tab[123]['table']          = $this->getTable();
      $tab[123]['field']          = 'validate_incident';
      $tab[123]['name']           = __('Validate an incident');
      $tab[123]['datatype']       = 'bool';

      $tab[99]['table']          = $this->getTable();
      $tab[99]['field']          = 'create_request_validation';
      $tab[99]['name']           = __('Create a validation request for a request');
      $tab[99]['datatype']       = 'bool';

      $tab[122]['table']          = $this->getTable();
      $tab[122]['field']          = 'create_incident_validation';
      $tab[122]['name']           = __('Create a validation request for an incident');
      $tab[122]['datatype']       = 'bool';

      $tab[100]['table']         = $this->getTable();
      $tab[100]['field']         = 'ticket_status';
      $tab[100]['name']          = __('Life cycle of tickets');
      $tab[100]['nosearch']      = true;
      $tab[100]['datatype']      = 'text';
      $tab[100]['massiveaction'] = false;

      $tab[110]['table']         = $this->getTable();
      $tab[110]['field']         = 'problem_status';
      $tab[110]['name']          = __('Life cycle of problems');
      $tab[110]['nosearch']      = true;
      $tab[110]['datatype']      = 'text';
      $tab[110]['massiveaction'] = false;

      $tab[112]['table']         = $this->getTable();
      $tab[112]['field']         = 'show_my_problem';
      $tab[112]['name']          = __('See problems (author)');
      $tab[112]['datatype']      = 'bool';

      $tab[113]['table']         = $this->getTable();
      $tab[113]['field']         = 'show_all_problem';
      $tab[113]['name']          = __('See all problems');
      $tab[113]['datatype']      = 'bool';

      $tab[114]['table']         = $this->getTable();
      $tab[114]['field']         = 'edit_all_problem';
      $tab[114]['name']          = __('Update all problems');
      $tab[114]['datatype']      = 'bool';

      $tab[115]['table']         = $this->getTable();
      $tab[115]['field']         = 'delete_problem';
      $tab[115]['name']          = __('Delete all problems');
      $tab[115]['datatype']      = 'bool';

//       $tab[111]['table']         = $this->getTable();
//       $tab[111]['field']         = 'change_status';
//       $tab[111]['name']          = __('Life cycle of changes');
//       $tab[111]['nosearch']      = true;
//       $tab[111]['datatype']      = 'text';
//       $tab[111]['massiveaction'] = false;
//
//       $tab[115]['table']         = $this->getTable();
//       $tab[115]['field']         = 'show_my_change';
//       $tab[115]['name']          =__('See changes (author)');
//       $tab[115]['datatype']      = 'bool';
//
//       $tab[116]['table']         = $this->getTable();
//       $tab[116]['field']         = 'show_all_change';
//       $tab[116]['name']          = __('See all changes');
//       $tab[116]['datatype']      = 'bool';
//
//       $tab[117]['table']         = $this->getTable();
//       $tab[117]['field']         = 'edit_all_change';
//       $tab[117]['name']          = __('Update all changes');
//       $tab[117]['datatype']      = 'bool';

      $tab['other']              = __('Other');

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'password_update';
      $tab[4]['name']            = __('Update password');
      $tab[4]['datatype']        = 'bool';

      $tab[63]['table']          = $this->getTable();
      $tab[63]['field']          = 'reminder_public';
      $tab[63]['name']           = _n('Public reminder', 'Public reminders', 2);
      $tab[63]['datatype']       = 'right';

      $tab[64]['table']          = $this->getTable();
      $tab[64]['field']          = 'bookmark_public';
      $tab[64]['name']           = _n('Public bookmark', 'Public bookmarks', 2);
      $tab[64]['datatype']       = 'right';

      $tab[120]['table']          = $this->getTable();
      $tab[120]['field']          = 'rssfeed_public';
      $tab[120]['name']           = _n('Public RSS feed', 'Public RSS feeds', 2);
      $tab[120]['datatype']       = 'right';

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
    **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'interface':
            return self::getInterfaceName($values[$field]);

         case 'helpdesk_hardware':
            return self::getHelpdeskHardwareTypeName($values[$field]);

         case "helpdesk_item_type":
            $types = explode(',', $values[$field]);
            $message = array();
            foreach ($types as $type) {
               if ($item = getItemForItemtype($type)) {
                  $message[] = $item->getTypeName();
               }
            }
            return implode(', ',$message);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'interface' :
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getInterfaces(), $options);

         case 'helpdesk_hardware' :
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getHelpdeskHardwareTypes(), $options);

         case "helpdesk_item_type":
            $options['values'] = explode(',', $values[$field]);
            $options['name']  = $name;
            return self::dropdownHelpdeskItemtypes($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Make a select box for a None Read Write choice
    *
    * @param $name      select name
    * @param $value     preselected value.
    * @param $none      display none choice ? (default 1)
    * @param $read      display read choice ? (default 1)
    * @param $write     display write choice ? (default 1)
    *
    * @return nothing (print out an HTML select box)
    * \deprecated use dropdownRight instead
   **/
   static function dropdownNoneReadWrite($name, $value, $none=1, $read=1, $write=1) {

      return self::dropdownRight($name, array('value'     => $value,
                                              'nonone'  => !$none,
                                              'noread'  => !$read,
                                              'nowrite' => !$write));
   }


   /**
    * Make a select box for a None Read Write choice
    *
    * @since version 0.84
    *
    * @param $name          select name
    * @param $options array of possible options:
    *       - value   : preselected value.
    *       - nonone  : hide none choice ? (default false)
    *       - noread  : hide read choice ? (default false)
    *       - nowrite : hide write choice ? (default false)
    *       - display : display or get string (default true)
    *       - rand    : specific rand (default is generated one)
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownRight($name, $options=array()) {

      $param['value']   = '';
      $param['display'] = true;
      $param['nonone']  = false;
      $param['noread']  = false;
      $param['nowrite'] = false;
      $param['rand']    = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }
      $values = array();
      if (!$param['nonone']) {
         $values['NULL'] = __('No access');
      }
      if (!$param['noread']) {
         $values['r'] = __('Read');
      }
      if (!$param['nowrite']) {
         $values['w'] = __('Write');
      }
      return Dropdown::showFromArray($name, $values,
                                     array('value'   => $param['value'],
                                           'rand'    => $param['rand'],
                                           'display' => $param['display']));
   }


   /**
    * Dropdown profiles which have rights under the active one
    *
    * @param $options array of possible options:
    *    - name : string / name of the select (default is profiles_id)
    *    - value : integer / preselected value (default 0)
    *
   **/
   static function dropdownUnder($options=array()) {
      global $DB;

      $p['name']  = 'profiles_id';
      $p['value'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $profiles[0] = Dropdown::EMPTY_VALUE;

      $query = "SELECT *
                FROM `glpi_profiles` ".
                self::getUnderActiveProfileRetrictRequest("WHERE")."
                ORDER BY `name`";
      $res = $DB->query($query);

      //New rule -> get the next free ranking
      if ($DB->numrows($res)) {
         while ($data = $DB->fetch_assoc($res)) {
            $profiles[$data['id']] = $data['name'];
         }
      }
      Dropdown::showFromArray($p['name'], $profiles, array('value' => $p['value']));
   }


   /**
    * Get the default Profile for new user
    *
    * @return integer profiles_id
   **/
   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_profiles', array('is_default'=>1)) as $data) {
         return $data['id'];
      }
      return 0;
   }


   /**
    * @param $value
   **/
   static function getRightValue($value) {

      switch ($value) {
         case '' :
            return __('No access');

         case 'r' :
            return __('Read');

         case 'w' :
            return __('Write');

         default :
            return '';
      }
   }


   /**
    * @since version 0.84
   **/
   static function getInterfaces() {

     return array('central'  => __('Standard interface'),
                  'helpdesk' => __('Simplified interface'));
   }


   /**
    * @param $value
   **/
   static function getInterfaceName($value) {

      $tab = self::getInterfaces();
      if (isset($tab[$value])) {
         return $tab[$value];
      }
      return NOT_AVAILABLE;
   }


   /**
    * @since version 0.84
   **/
   static function getHelpdeskHardwareTypes() {

      return array(0                                        => Dropdown::EMPTY_VALUE,
                   pow(2, Ticket::HELPDESK_MY_HARDWARE)     => __('My devices'),
                   pow(2, Ticket::HELPDESK_ALL_HARDWARE)    => __('All items'),
                   pow(2, Ticket::HELPDESK_MY_HARDWARE)
                    + pow(2, Ticket::HELPDESK_ALL_HARDWARE) => __('My devices and all items'));
   }


   /**
    * @since version 0.84
    *
    * @param $value
   **/
   static function getHelpdeskHardwareTypeName($value) {

      $tab = self::getHelpdeskHardwareTypes();
      if (isset($tab[$value])) {
         return $tab[$value];
      }
      return NOT_AVAILABLE;
   }


   /**
    * Dropdown profiles which have rights under the active one
    *
    * @since ersin 0.84
    *
    * @param $options array of possible options:
    *    - name : string / name of the select (default is profiles_id)
    *    - values : array of values
   **/
   static function dropdownHelpdeskItemtypes($options) {
      global $CFG_GLPI;

      $p['name']    = 'helpdesk_item_type';
      $p['values']  = array();
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      $values = array();
      foreach ($CFG_GLPI["ticket_types"] as $key => $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            if (!isPluginItemType($itemtype)) { // No Plugin for the moment
               $values[$itemtype] = $item->getTypeName();
            }
         } else {
            unset($CFG_GLPI["ticket_types"][$key]);
         }
      }

      $p['multiple'] = true;
      $p['size']     = 3;
      return Dropdown::showFromArray($p['name'], $values, $p);
   }


   /**
    * function to check one right of a user
    *
    * @since version 0.84
    *
    * @param $user       integer                id of the user to check rights
    * @param $right      string                 right to check
    * @param $valright   integer/string/array   value of the rights searched
    * @param $entity     integer                id of the entity
    *
    * @return boolean
    */
   static function haveUserRight($user, $right, $valright, $entity) {
      global $DB;

      $query = "SELECT $right
                FROM `glpi_profiles`
                INNER JOIN `glpi_profiles_users`
                   ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`)
                WHERE `glpi_profiles_users`.`users_id` = '$user'
                      AND $right IN ('$valright') ".
                      getEntitiesRestrictRequest(" AND ", "glpi_profiles_users", '', $entity, true);

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            return true;
         }
      }
      return false;
   }

}
?>
