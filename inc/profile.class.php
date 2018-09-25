<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Profile class
**/
class Profile extends CommonDBTM {

   // Specific ones

   /// Helpdesk fields of helpdesk profiles
   static public $helpdesk_rights = ['create_ticket_on_login', 'followup',
                                          'knowbase', 'helpdesk_hardware', 'helpdesk_item_type',
                                          'password_update', 'reminder_public',
                                          'reservation', 'rssfeed_public',
                                          'show_group_hardware', 'task', 'ticket',
                                          'tickettemplates_id', 'ticket_cost',
                                          'ticketvalidation', 'ticket_status','personalization'];


   /// Common fields used for all profiles type
   static public $common_fields  = ['id', 'interface', 'is_default', 'name'];

   public $dohistory             = true;

   static $rightname             = 'profile';



   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb = 0) {
      return _n('Profile', 'Profiles', $nb);
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Profile_User', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               if ($item->fields['interface'] == 'helpdesk') {
                  $ong[3] = __('Assistance'); // Helpdesk
                  $ong[4] = __('Life cycles');
                  $ong[6] = __('Tools');
                  $ong[8] = __('Setup');
               } else {
                  $ong[2] = __('Assets');
                  $ong[3] = __('Assistance');
                  $ong[4] = __('Life cycles');
                  $ong[5] = __('Management');
                  $ong[6] = __('Tools');
                  $ong[7] = __('Administration');
                  $ong[8] = __('Setup');

               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $item->cleanProfile();
         switch ($tabnum) {
            case 2 :
               $item->showFormAsset();
               break;

            case 3 :
               if ($item->fields['interface'] == 'helpdesk') {
                  $item->showFormTrackingHelpdesk();
               } else {
                  $item->showFormTracking();
               }
               break;

            case 4 :
               if ($item->fields['interface'] == 'helpdesk') {
                  $item->showFormLifeCycleHelpdesk();
               } else {
                  $item->showFormLifeCycle();
               }
               break;

            case 5 :
               $item->showFormManagement();
               break;

            case 6 :
               if ($item->fields['interface'] == 'helpdesk') {
                  $item->showFormToolsHelpdesk();
               } else {
                  $item->showFormTools();
               }
               break;

            case 7 :
               $item->showFormAdmin();
               break;

            case 8 :
               if ($item->fields['interface'] == 'helpdesk') {
                  $item->showFormSetupHelpdesk();
               } else {
                  $item->showFormSetup();
               }
               break;
         }
      }
      return true;
   }


   function post_updateItem($history = 1) {
      global $DB;

      if (count($this->profileRight) > 0) {
         ProfileRight::updateProfileRights($this->getID(), $this->profileRight);
         unset($this->profileRight);
      }

      if (in_array('is_default', $this->updates) && ($this->input["is_default"] == 1)) {
         $DB->update(
            $this->getTable(), [
               'is_default' => 0
            ], [
               'id' => ['<>', $this->input['id']]
            ]
         );
      }

      // To avoid log out and login when rights change (very useful in debug mode)
      if (isset($_SESSION['glpiactiveprofile']['id'])
            && $_SESSION['glpiactiveprofile']['id'] == $this->input['id']) {

         if (in_array('helpdesk_item_type', $this->updates)) {
            $_SESSION['glpiactiveprofile']['helpdesk_item_type'] = importArrayFromDB($this->input['helpdesk_item_type']);
         }
         ///TODO other needed fields
      }
   }


   function post_addItem() {
      global $DB;

      $rights = ProfileRight::getAllPossibleRights();
      ProfileRight::updateProfileRights($this->fields['id'], $rights);
      unset($this->profileRight);

      if (isset($this->fields['is_default']) && ($this->fields["is_default"] == 1)) {
         $DB->update(
            $this->getTable(), [
               'is_default' => 0
            ], [
               'id' => ['<>', $this->fields['id']]
            ]
         );
      }
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            KnowbaseItem_Profile::class,
            Profile_Reminder::class,
            Profile_RSSFeed::class,
            Profile_User::class,
            ProfileRight::class,
         ]
      );

      Rule::cleanForItemAction($this);
      // PROFILES and UNIQUE_PROFILE in RuleMailcollector
      Rule::cleanForItemCriteria($this, 'PROFILES');
      Rule::cleanForItemCriteria($this, 'UNIQUE_PROFILE');
   }


   function prepareInputForUpdate($input) {

      if (isset($input["_helpdesk_item_types"])) {
         if ((!isset($input["helpdesk_item_type"])) || (!is_array($input["helpdesk_item_type"]))) {
            $input["helpdesk_item_type"] = [];
         }
         // Linear_HIT: $input["helpdesk_item_type"] = array_keys($input["helpdesk_item_type"]
         $input["helpdesk_item_type"] = exportArrayToDB($input["helpdesk_item_type"]);
      }

      if (isset($input['helpdesk_hardware']) && is_array($input['helpdesk_hardware'])) {
         $helpdesk_hardware = 0;
         foreach ($input['helpdesk_hardware'] as $right => $value) {
            if ($value) {
               $helpdesk_hardware += $right;
            }
         }
         $input['helpdesk_hardware'] = $helpdesk_hardware;
      }

      if (isset($input["_cycle_ticket"])) {
         $tab   = Ticket::getAllStatusArray();
         $cycle = [];
         foreach ($tab as $from => $label) {
            foreach ($tab as $dest => $label) {
               if (($from != $dest)
                   && (!isset($input["_cycle_ticket"][$from][$dest])
                      || ($input["_cycle_ticket"][$from][$dest] == 0))) {
                  $cycle[$from][$dest] = 0;
               }
            }
         }
         $input["ticket_status"] = exportArrayToDB($cycle);
      }

      if (isset($input["_cycle_problem"])) {
         $tab   = Problem::getAllStatusArray();
         $cycle = [];
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

      if (isset($input["_cycle_change"])) {
         $tab   = Change::getAllStatusArray();
         $cycle = [];
         foreach ($tab as $from => $label) {
            foreach ($tab as $dest => $label) {
               if (($from != $dest)
                   && ($input["_cycle_change"][$from][$dest] == 0)) {
                  $cycle[$from][$dest] = 0;
               }
            }
         }
         $input["change_status"] = exportArrayToDB($cycle);
      }

      $this->profileRight = [];
      foreach (ProfileRight::getAllPossibleRights() as $right => $default) {
         if (isset($input['_'.$right])) {
            if (!is_array($input['_'.$right])) {
               $input['_'.$right] = ['1' => $input['_'.$right]];
            }
            $newvalue = 0;
            foreach ($input['_'.$right] as $value => $valid) {
               if ($valid) {
                  if (($underscore_pos = strpos($value, '_')) !== false) {
                     $value = substr($value, 0, $underscore_pos);
                  }
                  $newvalue += $value;
               }
            }
            // Update rights only if changed
            if (!isset($this->fields[$right]) || ($this->fields[$right] != $newvalue)) {
               $this->profileRight[$right] = $newvalue;
            }
            unset($input['_'.$right]);
         }
      }

      // check if right if the last write profile on Profile object
      if (($this->fields['profile'] & UPDATE)
          && isset($input['profile']) && !($input['profile'] & UPDATE)
          && (countElementsInTable("glpi_profilerights",
                                   ['name' => 'profile', 'rights' => ['&',  UPDATE]]))) {
         Session::addMessageAfterRedirect(__("This profile is the last with write rights on profiles"),
         false, ERROR);
         Session::addMessageAfterRedirect(__("Deletion refused"), false, ERROR);
         unset($input["profile"]);
      }
      return $input;
   }


   /**
    * check right before delete
    *
    * @since 0.85
    *
    * @return boolean
   **/
   function pre_deleteItem() {
      global $DB;

      if (($this->fields['profile'] & DELETE)
          && (countElementsInTable("glpi_profilerights",
                                   ['name' => 'profile', 'rights' => ['&', DELETE]]))) {
          Session::addMessageAfterRedirect(__("This profile is the last with write rights on profiles"),
                                           false, ERROR);
          Session::addMessageAfterRedirect(__("Deletion refused"), false, ERROR);
          return false;
      }
      return true;
   }


   function prepareInputForAdd($input) {

      if (isset($input["helpdesk_item_type"])) {
         $input["helpdesk_item_type"] = exportArrayToDB($input["helpdesk_item_type"]);
      }

      $this->profileRight = [];
      foreach (ProfileRight::getAllPossibleRights() as $right => $default) {
         if (isset($input[$right])) {
            $this->profileRight[$right] = $input[$right];
            unset($input[$right]);
         }
      }

      return $input;
   }


   /**
    * Unset unused rights for helpdesk
   **/
   function cleanProfile() {

      if (isset($this->fields['interface']) && $this->fields["interface"] == "helpdesk") {
         foreach ($this->fields as $key=>$val) {
            if (!in_array($key, self::$common_fields)
                && !in_array($key, self::$helpdesk_rights)) {
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

         $this->fields["helpdesk_item_type"] = [];
      }

      // Decode status array
      $fields_to_decode = ['ticket_status', 'problem_status', 'change_status'];
      foreach ($fields_to_decode as $val) {
         if (isset($this->fields[$val]) && !is_array($this->fields[$val])) {
            $this->fields[$val] = importArrayFromDB($this->fields[$val]);
            // Need to be an array not a null value
            if (is_null($this->fields[$val])) {
               $this->fields[$val] = [];
            }
         }
      }
   }


   /**
    * Get SQL restrict criteria to determine profiles with less rights than the active one
    *
    * @since 9.3.1
    *
    * @return array
   **/
   static function getUnderActiveProfileRestrictCriteria() {

      // Not logged -> no profile to see
      if (!isset($_SESSION['glpiactiveprofile'])) {
         return [0];
      }

      // Profile right : may modify profile so can attach all profile
      if (Profile::canCreate()) {
         return [1];
      }

      $criteria = ['glpi_profiles.interface' => Session::getCurrentInterface()];

      // First, get all possible rights
      $right_subqueries = [];
      foreach (ProfileRight::getAllPossibleRights() as $key => $default) {
         $val = isset($_SESSION['glpiactiveprofile'][$key])?$_SESSION['glpiactiveprofile'][$key]:0;

         if (!is_array($val) // Do not include entities field added by login
             && (Session::getCurrentInterface() == 'central'
                 || in_array($key, self::$helpdesk_rights))) {
            $right_subqueries[] = [
               'glpi_profilerights.name'     => $key,
               'RAW'                         => [
                  '(' . DBmysql::quoteName('glpi_profilerights.rights') . ' | ' . DBmysql::quoteValue($val) . ')' => $val
               ]
            ];
         }
      }

      $dbiterator = new DBmysqlIterator(null);
      $dbiterator->buildQuery(
         'glpi_profilerights', [
            'COUNT'  => 'cpt',
            'WHERE'  => [
               'glpi_profilerights.profiles_id' => new \QueryExpression(\DBmysql::quoteName('glpi_profiles.id')),
               'OR'                             => $right_subqueries
            ]
         ]
      );
      $sub_query = $dbiterator->getSql();
      $criteria['RAW'] = [
         $sub_query => count($right_subqueries)
      ];

      if (Session::getCurrentInterface() == 'central') {
         return [
            'OR'  => [
               $criteria,
               'glpi_profiles.interface' => 'helpdesk'
            ]
         ];
      }
      return $criteria;
   }


   /**
    * Is the current user have more right than all profiles in parameters
    *
    * @param $IDs array of profile ID to test
    *
    * @return boolean true if have more right
   **/
   static function currentUserHaveMoreRightThan($IDs = []) {
      global $DB;

      if (Session::isCron()) {
         return true;
      }
      if (count($IDs) == 0) {
         // Check all profiles (means more right than all possible profiles)
         return (countElementsInTable('glpi_profiles')
                     == countElementsInTable('glpi_profiles',
                                             self::getUnderActiveProfileRestrictCriteria()));
      }
      $under_profiles = [];

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => self::getUnderActiveProfileRestrictCriteria()
      ]);

      while ($data = $iterator->next()) {
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
      global $GLPI_CACHE;

      $this->fields["interface"] = "helpdesk";
      $this->fields["name"]      = __('Without name');
      ProfileRight::cleanAllPossibleRights();
      $this->fields = array_merge($this->fields, ProfileRight::getAllPossibleRights());
   }


   function post_getFromDB() {
      $this->fields = array_merge($this->fields, ProfileRight::getProfileRights($this->getID()));
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
   function showForm($ID, $options = []) {

      $onfocus = "";
      $new     = false;
      $rowspan = 4;
      if ($ID > 0) {
         $rowspan++;
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE);
         $onfocus = "onfocus=\"if (this.value=='".$this->fields["name"]."') this.value='';\"";
         $new     = true;
      }

      $rand = mt_rand();

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td><input type='text' name='name' value=\"".$this->fields["name"]."\" $onfocus></td>";
      echo "<td rowspan='$rowspan' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='$rowspan'>";
      echo "<textarea cols='45' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Default profile')."</td><td>";
      Html::showCheckbox(['name'    => 'is_default',
                               'checked' => $this->fields['is_default']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__("Profile's interface")."</td>";
      echo "<td>";
      Dropdown::showFromArray('interface', self::getInterfaces(),
                              ['value'=>$this->fields["interface"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Update password')."</td><td>";
      Html::showCheckbox(['name'    => '_password_update',
                               'checked' => $this->fields['password_update']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Ticket creation form on login')."</td><td>";
      Html::showCheckbox(['name'    => 'create_ticket_on_login',
                               'checked' => $this->fields['create_ticket_on_login']]);
      echo "</td></tr>\n";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the helpdesk right form for the current profile
    *
    * @since 0.85
   **/
   function showFormTrackingHelpdesk() {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_2'];

      $rights = [['rights'     => Profile::getRightsFor('Ticket', 'helpdesk'),
                            'label'      => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                            'field'      => 'ticket'],
                      ['rights'     => Profile::getRightsFor('ITILFollowup', 'helpdesk'),
                            'label'      => _n('Followup', 'Followups', Session::getPluralNumber()),
                            'field'      => 'followup'],
                      ['rights'     => Profile::getRightsFor('TicketTask', 'helpdesk'),
                            'label'      => _n('Task', 'Tasks', Session::getPluralNumber()),
                            'field'      => 'task'],
                      ['rights'     => Profile::getRightsFor('TicketValidation', 'helpdesk'),
                            'label'      => _n('Validation', 'Validations', Session::getPluralNumber()),
                            'field'      => 'ticketvalidation']];

      $matrix_options['title'] = __('Assistance');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_5'><th colspan='2'>".__('Association')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td width=30%>".__('See hardware of my groups')."</td><td>";
      Html::showCheckbox(['name'    => '_show_group_hardware',
                               'checked' => $this->fields['show_group_hardware']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Link with items for the creation of tickets')."</td>";
      echo "<td>";
      self::getLinearRightChoice(self::getHelpdeskHardwareTypes(true),
                                 ['field' => 'helpdesk_hardware',
                                       'value' => $this->fields['helpdesk_hardware']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Associable items to a ticket')."</td>";
      echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
      self::dropdownHelpdeskItemtypes(['values' => $this->fields["helpdesk_item_type"]]);

      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Default ticket template')."</td><td>";
      // Only root entity ones and recursive
      $options = ['value'     => $this->fields["tickettemplates_id"],
                       'entity'    => 0];
      if (Session::isMultiEntitiesMode()) {
         $options['condition'] = '`is_recursive`';
      }
      // Only add profile if on root entity
      if (!isset($_SESSION['glpiactiveentities'][0])) {
         $options['addicon'] = false;
      }
      TicketTemplate::dropdown($options);
      echo "</td>";
      echo "</tr>\n";

      if ($canedit) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
      } else {
         echo "</table>\n";
      }
      echo "</div>";
   }


   /**
    * Print the helpdesk right form for the current profile
    *
    * @since 0.85
   **/
   function showFormToolsHelpdesk() {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_2'];

      $rights = [['rights'    => Profile::getRightsFor('KnowbaseItem', 'helpdesk'),
                            'label'     => __('FAQ'),
                            'field'     => 'knowbase'],
                      ['rights'  => Profile::getRightsFor('ReservationItem', 'helpdesk'),
                            'label'     => _n('Reservation', 'Reservations', Session::getPluralNumber()),
                            'field'     => 'reservation'],
                      ['rights'    => Profile::getRightsFor('Reminder', 'helpdesk'),
                            'label'     => _n('Public reminder', 'Public reminders', Session::getPluralNumber()),
                            'field'     => 'reminder_public'],
                      ['rights'    => Profile::getRightsFor('RSSFeed', 'helpdesk'),
                            'label'     => _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber()),
                            'field'     => 'rssfeed_public']];

      $matrix_options['title'] = __('Tools');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }



   /**
    * Print the Asset rights form for the current profile
    *
    * @since 0.85
    *
    * @param $openform  boolean open the form (true by default)
    * @param $closeform boolean close the form (true by default)
    *
   **/
   function showFormAsset($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $rights = [['itemtype'  => 'Computer',
                            'label'     => _n('Computer', 'Computers', Session::getPluralNumber()),
                            'field'     => 'computer'],
                      ['itemtype'  => 'Monitor',
                            'label'     => _n('Monitor', 'Monitors', Session::getPluralNumber()),
                            'field'     => 'monitor'],
                      ['itemtype'  => 'Software',
                            'label'     => _n('Software', 'Software', Session::getPluralNumber()),
                            'field'     => 'software'],
                      ['itemtype'  => 'NetworkEquipment',
                            'label'     => _n('Network', 'Networks', Session::getPluralNumber()),
                            'field'     => 'networking'],
                      ['itemtype'  => 'Printer',
                            'label'     => _n('Printer', 'Printers', Session::getPluralNumber()),
                            'field'     => 'printer'],
                      ['itemtype'  => 'Cartridge',
                            'label'     => _n('Cartridge', 'Cartridges', Session::getPluralNumber()),
                            'field'     => 'cartridge'],
                      ['itemtype'  => 'Consumable',
                            'label'     => _n('Consumable', 'Consumables', Session::getPluralNumber()),
                            'field'     => 'consumable'],
                      ['itemtype'  => 'Phone',
                            'label'     => _n('Phone', 'Phones', Session::getPluralNumber()),
                            'field'     => 'phone'],
                      ['itemtype'  => 'Peripheral',
                            'label'     => _n('Device', 'Devices', Session::getPluralNumber()),
                            'field'     => 'peripheral'],
                      ['itemtype'  => 'NetworkName',
                            'label'     => __('Internet'),
                            'field'     => 'internet'],
                      ['itemtype'  => 'DeviceSimcard',
                            'label'     => __('Simcard PIN/PUK'),
                            'field'     => 'devicesimcard_pinpuk',
                            'rights'    => [READ    => __('Read'),
                                            UPDATE  => __('Update')]]];

      $this->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Assets')]);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }

      echo "</div>";
   }


   /**
    * Print the Management rights form for the current profile
    *
    * @since 0.85 (before showFormInventory)
    *
    * @param $openform  boolean open the form (true by default)
    * @param $closeform boolean close the form (true by default)
   **/
   function showFormManagement($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_2'];

      $rights = [['itemtype'  => 'SoftwareLicense',
                            'label'     => _n('License', 'Licenses', Session::getPluralNumber()),
                            'field'     => 'license'],
                      ['itemtype'  => 'Contact',
                            'label'     => _n('Contact', 'Contacts', Session::getPluralNumber())." / ".
                                           _n('Supplier', 'Suppliers', Session::getPluralNumber()),
                            'field'     => 'contact_enterprise'],
                      ['itemtype'  => 'Document',
                            'label'     => _n('Document', 'Documents', Session::getPluralNumber()),
                            'field'     => 'document'],
                      ['itemtype'  => 'Contract',
                            'label'     => _n('Contract', 'Contracts', Session::getPluralNumber()),
                            'field'     => 'contract'],
                      ['itemtype'  => 'Infocom',
                            'label'     => __('Financial and administratives information'),
                            'field'     => 'infocom'],
                      ['itemtype'  => 'Budget',
                            'label'     => __('Budget'),
                            'field'     => 'budget'],
                      ['itemtype'  => 'Line',
                            'label'     => __('Line'),
                            'field'     => 'line'],
                      ['itemtype'  => 'Certificate',
                            'label'     => _n('Certificate', 'Certificates',
                                              Session::getPluralNumber()),
                            'field'     => 'certificate'],
                      ['itemtype'  => 'Datacenter',
                            'label'     => Datacenter::getTypeName(Session::getPluralNumber()),
                            'field'     => 'datacenter']
                  ];
      $matrix_options['title'] = __('Management');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Print the Tools rights form for the current profile
    *
    * @since 0.85
    *
    * @param $openform  boolean open the form (true by default)
    * @param $closeform boolean close the form (true by default)
   **/
   function showFormTools($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [UPDATE, CREATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_2'];

      $rights = [['itemtype'  => 'Reminder',
                            'label'     => _n('Public reminder', 'Public reminders', Session::getPluralNumber()),
                            'field'     => 'reminder_public'],
                      ['itemtype'  => 'RSSFeed',
                            'label'     => _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber()),
                            'field'     => 'rssfeed_public'],
                      ['itemtype'  => 'SavedSearch',
                            'label'     => _n('Public saved search', 'Public saved searches', Session::getPluralNumber()),
                            'field'     => 'bookmark_public'],
                      ['itemtype'  => 'Report',
                            'label'     => _n('Report', 'Reports', Session::getPluralNumber()),
                            'field'     => 'reports'],
                      ['itemtype'  => 'KnowbaseItem',
                            'label'     => __('Knowledge base'),
                            'field'     => 'knowbase'],
                      ['itemtype'  => 'ReservationItem',
                            'label'     => __('Administration of reservations'),
                            'field'     => 'reservation']];
      $matrix_options['title'] = __('Tools');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'   => 'Project',
                            'label'      => _n('Project', 'Projects', Session::getPluralNumber()),
                            'field'      => 'project'],
                      ['itemtype'   => 'ProjectTask',
                            'label'      => _n('Task', 'Task', Session::getPluralNumber()),
                            'field'      => 'projecttask']];
      $matrix_options['title'] = _n('Project', 'Projects', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Print the Tracking right form for the current profile
    *
    * @param $openform     boolean  open the form (true by default)
    * @param $closeform    boolean  close the form (true by default)
   **/
   function showFormTracking($openform = true, $closeform = true) {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      // Assistance / Tracking-helpdesk
      echo "<tr class='tab_bg_1'><th colspan='2'>".__('Assistance')."</th></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>"._n('Ticket', 'Tickets', Session::getPluralNumber()).': '.__('Default ticket template')."</td><td  width='30%'>";
      // Only root entity ones and recursive
      $options = ['value'     => $this->fields["tickettemplates_id"],
                       'entity'    => 0];
      if (Session::isMultiEntitiesMode()) {
         $options['condition'] = '`is_recursive`';
      }
      // Only add profile if on root entity
      if (!isset($_SESSION['glpiactiveentities'][0])) {
         $options['addicon'] = false;
      }

      TicketTemplate::dropdown($options);
      echo "</td></tr>\n";

      echo "</table>";

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_2'];

      $rights = [['itemtype'  => 'Ticket',
                            'label'     => _n('Ticket', 'Tickets', Session::getPluralNumber()),
                            'field'     => 'ticket'],
                      ['itemtype'  => 'TicketCost',
                            'label'     => _n('Ticket cost', 'Ticket costs', Session::getPluralNumber()),
                            'field'     => 'ticketcost'],
                      ['itemtype'  => 'TicketRecurrent',
                            'label'     => __('Recurrent tickets'),
                            'field'     => 'ticketrecurrent'],
                      ['itemtype'  => 'TicketTemplate',
                            'label'     => _n('Ticket template', 'Ticket templates', Session::getPluralNumber()),
                            'field'     => 'tickettemplate']];
      $matrix_options['title'] = _n('Ticket', 'Tickets', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'  => 'ITILFollowup',
                            'label'     => _n('Followup', 'Followups', Session::getPluralNumber()),
                            'field'     => 'followup'],
                      ['itemtype'  => 'TicketTask',
                            'label'     => _n('Task', 'Tasks', Session::getPluralNumber()),
                            'field'     => 'task']];
      $matrix_options['title'] = _n('Followup', 'Followups', Session::getPluralNumber())." / "._n('Task', 'Tasks', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'  => 'TicketValidation',
                            'label'     => _n('Validation', 'Validations', Session::getPluralNumber()),
                            'field'     => 'ticketvalidation']];
      $matrix_options['title'] = _n('Validation', 'Validations', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_5'><th colspan='2'>".__('Association')."</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('See hardware of my groups')."</td><td>";
      Html::showCheckbox(['name'    => '_show_group_hardware',
                               'checked' => $this->fields['show_group_hardware']]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Link with items for the creation of tickets')."</td>";
      echo "\n<td>";
      self::getLinearRightChoice(self::getHelpdeskHardwareTypes(true),
                                 ['field' => 'helpdesk_hardware',
                                       'value' => $this->fields['helpdesk_hardware']]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Associable items to a ticket')."</td>";
      echo "<td><input type='hidden' name='_helpdesk_item_types' value='1'>";
      self::dropdownHelpdeskItemtypes(['values' => $this->fields["helpdesk_item_type"]]);
      // Linear_HIT
      // self::getLinearRightChoice(self::getHelpdeskItemtypes(),
      //                               array('field'         => 'helpdesk_item_type',
      //                                     'value'         => $this->fields['helpdesk_item_type'],
      //                                     'check_all'     => true,
      //                                     'zero_on_empty' => false,
      //                                     'max_per_line'  => 4,
      //                                     'check_method'  =>
      //                                     function ($element, $field) {
      //                                        return in_array($element,$field);
      //                                     }));
      echo "</td>";
      echo "</tr>\n";
      echo "</table>";

      $rights = [['itemtype'   => 'Stat',
                            'label'      => __('Statistics'),
                            'field'      => 'statistic'],
                      ['itemtype'   => 'Planning',
                            'label'      => __('Planning'),
                            'field'      => 'planning']];
      $matrix_options['title'] = __('Visibility');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'   => 'Problem',
                            'label'      => _n('Problem', 'Problems', Session::getPluralNumber()),
                            'field'      => 'problem']];
      $matrix_options['title'] = _n('Problem', 'Problems', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'   => 'Change',
                            'label'      => _n('Change', 'Changes', Session::getPluralNumber()),
                            'field'      => 'change'],
                      ['itemtype'  => 'ChangeValidation',
                            'label'     => _n('Validation', 'Validations', Session::getPluralNumber()),
                            'field'     => 'changevalidation']];
      $matrix_options['title'] = _n('Change', 'Changes', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Display the matrix of the elements lifecycle of the elements
    *
    * @since 0.85
    *
    * @param $title          the kind of lifecycle
    * @param $html_field     field that is sent to _POST
    * @param $db_field       field inside the DB (to get current state)
    * @param $statuses       all available statuses for the given cycle (obj::getAllStatusArray())
    * @param $canedit        can we edit the elements ?
    *
    * @return nothing
   **/
   function displayLifeCycleMatrix($title, $html_field, $db_field, $statuses, $canedit) {

      $columns  = [];
      $rows     = [];

      foreach ($statuses as $index_1 => $status_1) {
         $columns[$index_1] = $status_1;
         $row               = ['label'      => $status_1,
                                    'columns'    => []];

         foreach ($statuses as $index_2 => $status_2) {
            $content = ['checked' => true];
            if (isset($this->fields[$db_field][$index_1][$index_2])) {
               $content['checked'] = $this->fields[$db_field][$index_1][$index_2];
            }
            if (($index_1 == $index_2) || (!$canedit)) {
               $content['readonly'] = true;
            }
            $row['columns'][$index_2] = $content;
         }
         $rows[$html_field."[$index_1]"] = $row;
      }
      Html::showCheckboxMatrix($columns, $rows,
                               ['title'         => $title,
                                     'row_check_all' => true,
                                     'col_check_all' => true,
                                     'first_cell'    => '<b>'.__("From \ To").'</b>']);
   }


   /**
   * Print the Life Cycles form for the current profile
   *
   * @param $openform   boolean  open the form (true by default)
   * @param $closeform  boolean  close the form (true by default)
   **/
   function showFormLifeCycle($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $this->displayLifeCycleMatrix(__('Life cycle of tickets'), '_cycle_ticket', 'ticket_status',
                                    Ticket::getAllStatusArray(), $canedit);

      $this->displayLifeCycleMatrix(__('Life cycle of problems'), '_cycle_problem',
                                    'problem_status', Problem::getAllStatusArray(), $canedit);

      $this->displayLifeCycleMatrix(__('Life cycle of changes'), '_cycle_change', 'change_status',
                                    Change::getAllStatusArray(), $canedit);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Display the matrix of the elements lifecycle of the elements
    *
    * @since 0.85
    *
    * @param $title          the kind of lifecycle
    * @param $html_field     field that is sent to _POST
    * @param $db_field       field inside the DB (to get current state)
    * @param $canedit        can we edit the elements ?
    *
    * @return nothing
   **/
   function displayLifeCycleMatrixTicketHelpdesk($title, $html_field, $db_field, $canedit) {

      $columns     = [];
      $rows        = [];
      $statuses    = [];
      $allstatuses = Ticket::getAllStatusArray();
      foreach ([Ticket::INCOMING, Ticket::SOLVED, Ticket::CLOSED] as $val) {
         $statuses[$val] = $allstatuses[$val];
      }
      $alwaysok     = [Ticket::INCOMING => [],
                            Ticket::SOLVED   => [Ticket::INCOMING],
                            Ticket::CLOSED   => []];

      $allowactions = [Ticket::INCOMING => [],
                            Ticket::SOLVED   => [Ticket::CLOSED],
                            Ticket::CLOSED   => [Ticket::CLOSED, Ticket::INCOMING]];

      foreach ($statuses as $index_1 => $status_1) {
         $columns[$index_1] = $status_1;
         $row               = ['label'      => $status_1,
                                    'columns'    => []];

         foreach ($statuses as $index_2 => $status_2) {
            $content = ['checked' => true];
            if (isset($this->fields[$db_field][$index_1][$index_2])) {
               $content['checked'] = $this->fields[$db_field][$index_1][$index_2];
            }

            if (in_array($index_2, $alwaysok[$index_1])) {
               $content['checked'] = true;
            }

            if (($index_1 == $index_2)
                || (!$canedit)
                || !in_array($index_2, $allowactions[$index_1])) {
               $content['readonly'] = true;
            }
            $row['columns'][$index_2] = $content;
         }
         $rows[$html_field."[$index_1]"] = $row;
      }
      Html::showCheckboxMatrix($columns, $rows,
                               ['title'         => $title,
                                     'first_cell'    => '<b>'.__("From \ To").'</b>']);
   }


   /**
   * Print the Life Cycles form for the current profile
   *
   *  @since 0.85
   *
   * @param $openform   boolean  open the form (true by default)
   * @param $closeform  boolean  close the form (true by default)
   **/
   function showFormLifeCycleHelpdesk($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $this->displayLifeCycleMatrixTicketHelpdesk(__('Life cycle of tickets'), '_cycle_ticket',
                                                  'ticket_status', $canedit);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Print the central form for a profile
    *
    * @param $openform     boolean  open the form (true by default)
    * @param $closeform    boolean  close the form (true by default)
   **/
   function showFormAdmin($openform = true, $closeform = true) {
      global $DB;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";

      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $matrix_options = ['canedit'       => $canedit,
                              'default_class' => 'tab_bg_4'];

      $rights = [['itemtype'  => 'User',
                            'label'     => _n('User', 'Users', Session::getPluralNumber()),
                            'field'     => 'user',
                            'row_class' => 'tab_bg_2'],
                      ['itemtype'  => 'Entity',
                            'label'     => _n('Entity', 'Entities', Session::getPluralNumber()),
                            'field'     => 'entity'],
                      ['itemtype'  => 'Group',
                            'label'     => _n('Group', 'Groups', Session::getPluralNumber()),
                            'field'     => 'group'],
                      ['itemtype'  => 'Profile',
                            'label'     => _n('Profile', 'Profiles', Session::getPluralNumber()),
                            'field'     => 'profile'],
                      ['itemtype'  => 'QueuedNotification',
                            'label'     => __('Notification queue'),
                            'field'     => 'queuednotification'],
                      ['itemtype'  => 'Backup',
                            'label'     => __('Maintenance'),
                            'field'     => 'backup'],
                      ['itemtype'  => 'Log',
                            'label'     => _n('Log', 'Logs', Session::getPluralNumber()),
                            'field'     => 'logs']];
      $matrix_options['title'] = __('Administration');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'  => 'Rule',
                            'label'     => __('Authorizations assignment rules'),
                            'field'     => 'rule_ldap'],
                      ['itemtype'  => 'RuleImportComputer',
                            'label'     => __('Rules for assigning a computer to an entity'),
                            'field'     => 'rule_import'],
                      ['itemtype'  => 'RuleMailCollector',
                            'label'     => __('Rules for assigning a ticket created through a mails receiver'),
                            'field'     => 'rule_mailcollector'],
                      ['itemtype'  => 'RuleSoftwareCategory',
                            'label'     => __('Rules for assigning a category to a software'),
                            'field'     => 'rule_softwarecategories'],
                      ['itemtype'  => 'RuleTicket',
                            'label'     => __('Business rules for tickets (entity)'),
                            'field'     => 'rule_ticket',
                            'row_class' => 'tab_bg_2'],
                      ['itemtype'  => 'RuleAsset',
                            'label'     => __('Business rules for assets'),
                            'field'     => 'rule_asset',
                            'row_class' => 'tab_bg_2'],
                      ['itemtype'  => 'Transfer',
                            'label'     => __('Transfer'),
                            'field'     => 'transfer']];
      $matrix_options['title'] = _n('Rule', 'Rules', Session::getPluralNumber());
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      $rights = [['itemtype'  => 'RuleDictionnaryDropdown',
                            'label'     => __('Dropdowns dictionary'),
                            'field'     => 'rule_dictionnary_dropdown'],
                      ['itemtype'  => 'RuleDictionnarySoftware',
                            'label'     => __('Software dictionary'),
                            'field'     => 'rule_dictionnary_software'],
                      ['itemtype'  => 'RuleDictionnaryPrinter',
                            'label'     => __('Printers dictionnary'),
                            'field'     => 'rule_dictionnary_printer']];
      $matrix_options['title'] = __('Dropdowns dictionary');
      $this->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
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
   function showFormSetup($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $dropdown_rights = CommonDBTM::getRights();
      unset($dropdown_rights[DELETE]);
      unset($dropdown_rights[UNLOCK]);

      $rights = [['itemtype'  => 'Config',
                            'label'     => __('General setup'),
                            'field'     => 'config'],
                      ['rights'  => [
                         READ    => __('Read'),
                         UPDATE  => __('Update')],
                        'label'  => __('Personalization'),
                        'field'  => 'personalization'],
                      ['itemtype'  => 'DisplayPreference',
                            'label'     => __('Search result display'),
                            'field'     => 'search_config'],
                      ['itemtype'  => 'Item_Devices',
                            'label'     => _n('Component', 'Components', Session::getPluralNumber()),
                            'field'     => 'device'],
                      ['rights'    => $dropdown_rights,
                            'label'     => _n('Global dropdown', 'Global dropdowns', Session::getPluralNumber()),
                            'field'     => 'dropdown'],
                      __('Entity dropdowns'),
                      ['itemtype'  => 'Domain',
                            'label'     => _n('Domain', 'Domains', Session::getPluralNumber()),
                            'field'     => 'domain'],
                      ['itemtype'  => 'Location',
                            'label'     => _n('Location', 'Locations', Session::getPluralNumber()),
                            'field'     => 'location'],
                      ['itemtype'  => 'ITILCategory',
                            'label'     => _n('Ticket category', 'Ticket categories', Session::getPluralNumber()),
                            'field'     => 'itilcategory'],
                      ['itemtype'  => 'KnowbaseItemCategory',
                            'label'     => _n('Knowledge base category', 'Knowledge base categories', Session::getPluralNumber()),
                            'field'     => 'knowbasecategory'],
                      ['itemtype'  => 'Netpoint',
                            'label'     => _n('Network outlet', 'Network outlets', Session::getPluralNumber()),
                            'field'     => 'netpoint'],
                      ['itemtype'  => 'TaskCategory',
                            'label'     => _n('Task category', 'Task categories', Session::getPluralNumber()),
                            'field'     => 'taskcategory'],
                      ['itemtype'  => 'State',
                            'label'     => _n('Status of items', 'Statuses of items', Session::getPluralNumber()),
                            'field'     => 'state'],
                      ['itemtype'  => 'SolutionTemplate',
                            'label'     => _n('Solution template', 'Solution templates', Session::getPluralNumber()),
                            'field'     => 'solutiontemplate'],
                      ['itemtype'  => 'Calendar',
                            'label'     => _n('Calendar', 'Calendars', Session::getPluralNumber()),
                            'field'     => 'calendar'],
                      ['itemtype'  => 'DocumentType',
                            'label'     => __('Document type'),
                            'field'     => 'typedoc'],
                      ['itemtype'  => 'Link',
                            'label'     => _n('External link', 'External links', Session::getPluralNumber()),
                            'field'     => 'link'],
                      ['itemtype'  => 'Notification',
                            'label'     => _n('Notification', 'Notifications', Session::getPluralNumber()),
                            'field'     => 'notification'],
                      ['itemtype'  => 'SLM',
                            'label'     => __('SLM'),
                            'field'     => 'slm'],
                      ['itemtype'  => 'LineOperator',
                       'label'     => _n('Line operator', 'Line operators', Session::getPluralNumber()),
                       'field'     => 'lineoperator']];

      $this->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Setup')]);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";

      $this->showLegend();
   }


   /**
    * Print the Setup rights form for a helpdesk profile
    *
    * @since 9.4.0
    *
    * @param boolean $openform  open the form (true by default)
    * @param boolean $closeform close the form (true by default)
    *
    * @return void
    *
   **/
   function showFormSetupHelpdesk($openform = true, $closeform = true) {

      if (!self::canView()) {
         return false;
      }

      echo "<div class='spaced'>";
      if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]))
          && $openform) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }

      $rights = [['rights'  => [
                     READ    => __('Read'),
                     UPDATE  => __('Update')],
                  'label'  => __('Personalization'),
                  'field'  => 'personalization']];

      $this->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                      'default_class' => 'tab_bg_2',
                                                      'title'         => __('Setup')]);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";

      $this->showLegend();
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'interface',
         'name'               => __("Profile's interface"),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'searchtype'         => ['equals', 'notequals']
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'is_default',
         'name'               => __('Default profile'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '118',
         'table'              => $this->getTable(),
         'field'              => 'create_ticket_on_login',
         'name'               => __('Ticket creation form on login'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab[] = [
         'id'                 => 'inventory',
         'name'               => __('Assets')
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Computer', 'Computers', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Computer',
         'rightname'          => 'computer',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'computer'"
         ]
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Monitor', 'Monitors', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Monitor',
         'rightname'          => 'monitor',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'monitor'"
         ]
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Software', 'Software', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Software',
         'rightname'          => 'software',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'software'"
         ]
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Network', 'Networks', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Network',
         'rightname'          => 'networking',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'networking'"
         ]
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Printer', 'Printers', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Printer',
         'rightname'          => 'printer',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'printer'"
         ]
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Device', 'Devices', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Peripheral',
         'rightname'          => 'peripheral',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'peripheral'"
         ]
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Cartridge', 'Cartridges', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Cartridge',
         'rightname'          => 'cartridge',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'cartridge'"
         ]
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Consumable', 'Consumables', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Consumable',
         'rightname'          => 'consumable',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'consumable'"
         ]
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Phone', 'Phones', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Phone',
         'rightname'          => 'phone',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'phone'"
         ]
      ];

      $tab[] = [
         'id'                 => '129',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Internet'),
         'datatype'           => 'right',
         'rightclass'         => 'NetworkName',
         'rightname'          => 'internet',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'internet'"
         ]
      ];

      $tab[] = [
         'id'                 => 'management',
         'name'               => __('Management')
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Contact')." / ".__('Supplier'),
         'datatype'           => 'right',
         'rightclass'         => 'Contact',
         'rightname'          => 'contact_entreprise',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'contact_enterprise'"
         ]
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Document', 'Documents', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Document',
         'rightname'          => 'document',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'document'"
         ]
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Contract', 'Contracts', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Contract',
         'rightname'          => 'contract',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'contract'"
         ]
      ];

      $tab[] = [
         'id'                 => '33',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Financial and administratives information'),
         'datatype'           => 'right',
         'rightclass'         => 'Infocom',
         'rightname'          => 'infocom',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'infocom'"
         ]
      ];

      $tab[] = [
         'id'                 => '101',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Budget'),
         'datatype'           => 'right',
         'rightclass'         => 'Budget',
         'rightname'          => 'budget',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'budget'"
         ]
      ];

      $tab[] = [
         'id'                 => 'tools',
         'name'               => __('Tools')
      ];

      $tab[] = [
         'id'                 => '34',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Knowledge base'),
         'datatype'           => 'right',
         'rightclass'         => 'KnowbaseItem',
         'rightname'          => 'knowbase',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'knowbase'"
         ]
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Reservation', 'Reservations', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'ReservationItem',
         'rightname'          => 'reservation',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'reservation'"
         ]
      ];

      $tab[] = [
         'id'                 => '38',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Report', 'Reports', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Report',
         'rightname'          => 'reports',
         'nowrite'            => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'reports'"
         ]
      ];

      $tab[] = [
         'id'                 => 'config',
         'name'               => __('Setup')
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Dropdown', 'Dropdowns', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'DropdownTranslation',
         'rightname'          => 'dropdown',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'dropdown'"
         ]
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Component', 'Components', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Item_Devices',
         'rightname'          => 'device',
         'noread'             => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'device'"
         ]
      ];

      $tab[] = [
         'id'                 => '106',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Notification', 'Notifications', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Notification',
         'rightname'          => 'notification',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'notification'"
         ]
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Document type'),
         'datatype'           => 'right',
         'rightclass'         => 'DocumentType',
         'rightname'          => 'typedoc',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'typedoc'"
         ]
      ];

      $tab[] = [
         'id'                 => '46',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('External link', 'External links', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Link',
         'rightname'          => 'link',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'link'"
         ]
      ];

      $tab[] = [
         'id'                 => '47',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('General setup'),
         'datatype'           => 'right',
         'rightclass'         => 'Config',
         'rightname'          => 'config',
         'noread'             => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'config'"
         ]
      ];

      $tab[] = [
         'id'                 => '109',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Personalization'),
         'datatype'           => 'right',
         'rightclass'         => 'Config',
         'rightname'          => 'personalization',
         'noread'             => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'personalization'"
         ]
      ];

      $tab[] = [
         'id'                 => '52',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Search result user display'),
         'datatype'           => 'right',
         'rightclass'         => 'DisplayPreference',
         'rightname'          => 'search_config',
         'noread'             => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'search_config'"
         ]
      ];

      $tab[] = [
         'id'                 => '107',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Calendar', 'Calendars', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Calendar',
         'rightname'          => 'calendar',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'calendar'"
         ]
      ];

      $tab[] = [
         'id'                 => 'admin',
         'name'               => __('Administration')
      ];

      $tab[] = [
         'id'                 => '48',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Business rules for tickets'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleTicket',
         'rightname'          => 'rule_ticket',
         'nowrite'            => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_ticket'"
         ]
      ];

      $tab[] = [
         'id'                 => '105',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Rules for assigning a ticket created through a mails receiver'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleMailCollector',
         'rightname'          => 'rule_mailcollector',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_mailcollector'"
         ]
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Rules for assigning a computer to an entity'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleImportComputer',
         'rightname'          => 'rule_import',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_import'"
         ]
      ];

      $tab[] = [
         'id'                 => '50',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Authorizations assignment rules'),
         'datatype'           => 'right',
         'rightclass'         => 'Rule',
         'rightname'          => 'rule_ldap',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_ldap'"
         ]
      ];

      $tab[] = [
         'id'                 => '51',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Rules for assigning a category to a software'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleSoftwareCategory',
         'rightname'          => 'rule_softwarecategories',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_softwarecategories'"
         ]
      ];

      $tab[] = [
         'id'                 => '90',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Software dictionary'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleDictionnarySoftware',
         'rightname'          => 'rule_dictionnary_software',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_dictionnary_software'"
         ]
      ];

      $tab[] = [
         'id'                 => '91',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Dropdowns dictionary'),
         'datatype'           => 'right',
         'rightclass'         => 'RuleDictionnaryDropdown',
         'rightname'          => 'rule_dictionnary_dropdown',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rule_dictionnary_dropdown'"
         ]
      ];

      $tab[] = [
         'id'                 => '55',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => self::getTypeName(Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Profile',
         'rightname'          => 'profile',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'profile'"
         ]
      ];

      $tab[] = [
         'id'                 => '56',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('User', 'Users', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'User',
         'rightname'          => 'user',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'user'"
         ]
      ];

      $tab[] = [
         'id'                 => '58',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Group', 'Groups', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Group',
         'rightname'          => 'group',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'group'"
         ]
      ];

      $tab[] = [
         'id'                 => '59',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Entity', 'Entities', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Entity',
         'rightname'          => 'entity',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'entity'"
         ]
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Transfer'),
         'datatype'           => 'right',
         'rightclass'         => 'Transfer',
         'rightname'          => 'transfer',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'transfer'"
         ]
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Log', 'Logs', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Log',
         'rightname'          => 'logs',
         'nowrite'            => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'logs'"
         ]
      ];

      $tab[] = [
         'id'                 => '62',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Maintenance'),
         'datatype'           => 'right',
         'rightclass'         => 'Backup',
         'rightname'          => 'backup',
         'noread'             => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'backup'"
         ]
      ];

      $tab[] = [
         'id'                 => 'ticket',
         'name'               => __('Assistance')
      ];

      $tab[] = [
         'id'                 => '102',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Create a ticket'),
         'datatype'           => 'right',
         'rightclass'         => 'Ticket',
         'rightname'          => 'ticket',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'ticket'"
         ]
      ];

      $newtab = [
         'id'                 => '108',
         'table'              => 'glpi_tickettemplates',
         'field'              => 'name',
         'name'               => __('Default ticket template'),
         'datatype'           => 'dropdown',
      ];
      if (Session::isMultiEntitiesMode()) {
         $newtab['condition']     = '`entities_id` = 0 AND `is_recursive`';
      } else {
         $newtab['condition']     = '`entities_id` = 0';
      }
      $tab[] = $newtab;

      $tab[] = [
         'id'                 => '103',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Ticket template', 'Ticket templates', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'TicketTemplate',
         'rightname'          => 'tickettemplate',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'tickettemplate'"
         ]
      ];

      $tab[] = [
         'id'                 => '79',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Planning'),
         'datatype'           => 'right',
         'rightclass'         => 'Planning',
         'rightname'          => 'planning',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'planning'"
         ]
      ];

      $tab[] = [
         'id'                 => '85',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Statistics'),
         'datatype'           => 'right',
         'rightclass'         => 'Stat',
         'rightname'          => 'statistic',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'statistic'"
         ]
      ];

      $tab[] = [
         'id'                 => '119',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Ticket cost', 'Ticket costs', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'TicketCost',
         'rightname'          => 'ticketcost',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'ticketcost'"
         ]
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'helpdesk_hardware',
         'name'               => __('Link with items for the creation of tickets'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '87',
         'table'              => $this->getTable(),
         'field'              => 'helpdesk_item_type',
         'name'               => __('Associable items to a ticket'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '89',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('See hardware of my groups'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'show_group_hardware'"
         ]
      ];

      $tab[] = [
         'id'                 => '100',
         'table'              => $this->getTable(),
         'field'              => 'ticket_status',
         'name'               => __('Life cycle of tickets'),
         'nosearch'           => true,
         'datatype'           => 'text',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '110',
         'table'              => $this->getTable(),
         'field'              => 'problem_status',
         'name'               => __('Life cycle of problems'),
         'nosearch'           => true,
         'datatype'           => 'text',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '112',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Problem', 'Problems', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Problem',
         'rightname'          => 'problem',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'problem'"
         ]
      ];

      $tab[] = [
         'id'                 => '111',
         'table'              => $this->getTable(),
         'field'              => 'change_status',
         'name'               => __('Life cycle of changes'),
         'nosearch'           => true,
         'datatype'           => 'text',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '115',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Change', 'Changes', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Change',
         'rightname'          => 'change',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'change'"
         ]
      ];

      $tab[] = [
         'id'                 => 'other',
         'name'               => __('Other')
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => __('Update password'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'password_update'"
         ]
      ];

      $tab[] = [
         'id'                 => '63',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Public reminder', 'Public reminders', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'Reminder',
         'rightname'          => 'reminder_public',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'reminder_public'"
         ]
      ];

      $tab[] = [
         'id'                 => '64',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Public saved search', 'Public saved searches', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'SavedSearch',
         'rightname'          => 'bookmark_public',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'bookmark_public'"
         ]
      ];

      $tab[] = [
         'id'                 => '120',
         'table'              => 'glpi_profilerights',
         'field'              => 'rights',
         'name'               => _n('Public RSS feed', 'Public RSS feeds', Session::getPluralNumber()),
         'datatype'           => 'right',
         'rightclass'         => 'RSSFeed',
         'rightname'          => 'rssfeed_public',
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => "AND `NEWTABLE`.`name`= 'rssfeed_public'"
         ]
      ];

      return $tab;
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
    **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'interface':
            return self::getInterfaceName($values[$field]);

         case 'helpdesk_hardware':
            return self::getHelpdeskHardwareTypeName($values[$field]);

         case "helpdesk_item_type":
            $types = explode(',', $values[$field]);
            $message = [];
            foreach ($types as $type) {
               if ($item = getItemForItemtype($type)) {
                  $message[] = $item->getTypeName();
               }
            }
            return implode(', ', $message);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
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
            $options['name']   = $name;
            return self::dropdownHelpdeskItemtypes($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Make a select box for rights
    *
    * @since 0.85
    *
    * @param $values    array    of values to display
    * @param $name      integer  name of the dropdown
    * @param $current   integer  value in database (sum of rights)
    * @param $options   array
   **/
   static function dropdownRights(array $values, $name, $current, $options = []) {

      foreach ($values as $key => $value) {
         if (is_array($value)) {
            $values[$key] = $value['long'];
         }
      }

      $param['multiple']= true;
      $param['display'] = true;
      $param['size']    = count($values);
      $tabselect = [];
      foreach ($values as $k => $v) {
         if ($current & $k) {
            $tabselect[] = $k;
         }
      }
      $param['values'] =  $tabselect;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // To allow dropdown with no value to be in prepareInputForUpdate
      // without this, you can't have an empty dropdown
      // done to avoid define NORIGHT value
      if ($param['multiple']) {
         echo "<input type='hidden' name='".$name."[]' value='0'>";
      }
      return Dropdown::showFromArray($name, $values, $param);
   }



   /**
    * Make a select box for a None Read Write choice
    *
    * @since 0.84
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
   static function dropdownRight($name, $options = []) {

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

      $values = [];
      if (!$param['nonone']) {
         $values[0] = __('No access');
      }
      if (!$param['noread']) {
         $values[READ] = __('Read');
      }
      if (!$param['nowrite']) {
         $values[CREATE] = __('Write');
      }
      return Dropdown::showFromArray($name, $values,
                                     ['value'   => $param['value'],
                                           'rand'    => $param['rand'],
                                           'display' => $param['display']]);
   }


   /**
    * Dropdown profiles which have rights under the active one
    *
    * @param $options array of possible options:
    *    - name : string / name of the select (default is profiles_id)
    *    - value : integer / preselected value (default 0)
    *
   **/
   static function dropdownUnder($options = []) {
      global $DB;

      $p['name']  = 'profiles_id';
      $p['value'] = '';
      $p['rand']  = mt_rand();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => self::getUnderActiveProfileRestrictCriteria(),
         'ORDER'  => 'name'
      ]);

      //New rule -> get the next free ranking
      while ($data = $iterator->next()) {
         $profiles[$data['id']] = $data['name'];
      }
      Dropdown::showFromArray($p['name'], $profiles,
                              ['value'               => $p['value'],
                               'rand'                => $p['rand'],
                               'display_emptychoice' => true]);
   }


   /**
    * Get the default Profile for new user
    *
    * @return integer profiles_id
   **/
   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_profiles', ['is_default'=>1]) as $data) {
         return $data['id'];
      }
      return 0;
   }


   /**
    * @since 0.84
   **/
   static function getInterfaces() {

      return ['central'  => __('Standard interface'),
                   'helpdesk' => __('Simplified interface')];
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
    * @since 0.84
    *
    * @param $rights   boolean   (false by default)
   **/
   static function getHelpdeskHardwareTypes($rights = false) {

      if ($rights) {
         return [pow(2, Ticket::HELPDESK_MY_HARDWARE)     => __('My devices'),
                      pow(2, Ticket::HELPDESK_ALL_HARDWARE)    => __('All items')];
      }

      return [0                                        => Dropdown::EMPTY_VALUE,
                   pow(2, Ticket::HELPDESK_MY_HARDWARE)     => __('My devices'),
                   pow(2, Ticket::HELPDESK_ALL_HARDWARE)    => __('All items'),
                   pow(2, Ticket::HELPDESK_MY_HARDWARE)
                    + pow(2, Ticket::HELPDESK_ALL_HARDWARE) => __('My devices and all items')];
   }


   /**
    * @since 0.84
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
    * @since 0.85
   **/
   static function getHelpdeskItemtypes() {
      global $CFG_GLPI;

      $values = [];
      foreach ($CFG_GLPI["ticket_types"] as $key => $itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            $values[$itemtype] = $item->getTypeName();
         } else {
            unset($CFG_GLPI["ticket_types"][$key]);
         }
      }
      return $values;
   }


   /**
    * Dropdown profiles which have rights under the active one
    *
    * @since 0.84
    *
    * @param $options array of possible options:
    *    - name : string / name of the select (default is profiles_id)
    *    - values : array of values
   **/
   static function dropdownHelpdeskItemtypes($options) {
      global $CFG_GLPI;

      $p['name']    = 'helpdesk_item_type';
      $p['values']  = [];
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $values = self::getHelpdeskItemtypes();

      $p['multiple'] = true;
      $p['size']     = 3;
      return Dropdown::showFromArray($p['name'], $values, $p);
   }


   /**
    * Check if user has given right.
    *
    * @since 0.84
    *
    * @param $user_id    integer  id of the user
    * @param $rightname  string   name of right to check
    * @param $rightvalue integer  value of right to check
    * @param $entity_id  integer  id of the entity
    *
    * @return boolean
    */
   static function haveUserRight($user_id, $rightname, $rightvalue, $entity_id) {
      global $DB;

      $result = $DB->request(
         [
            'COUNT'      => 'cpt',
            'FROM'       => 'glpi_profilerights',
            'INNER JOIN' => [
               'glpi_profiles' => [
                  'FKEY' => [
                     'glpi_profilerights' => 'profiles_id',
                     'glpi_profiles'      => 'id',
                  ]
               ],
               'glpi_profiles_users' => [
                  'FKEY' => [
                     'glpi_profiles_users' => 'profiles_id',
                     'glpi_profiles'       => 'id',
                     [
                        'AND' => ['glpi_profiles_users.users_id' => $user_id],
                     ],
                  ]
               ],
            ],
            'WHERE'      => [
               'glpi_profilerights.name'   => $rightname,
               'glpi_profilerights.rights' => ['&',  $rightvalue],
            ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $entity_id, true),
         ]
      );

      if (!$data = $result->next()) {
         return false;
      }

      return $data['cpt'] > 0;
   }


   /**
    * Get rights for an itemtype
    *
    * @since 0.85
    *
    * @param $itemtype   string   itemtype
    * @param $interface  string   (default 'central')
    *
    * @return rights
   **/
   static function getRightsFor($itemtype, $interface = 'central') {

      if (class_exists($itemtype)) {
         $item = new $itemtype();
         return $item->getRights($interface);
      }

   }


   /**
    * Display rights choice matrix
    *
    * @since 0.85
    *
    * @param $rights array    possible:
    *             'itemtype'   => the type of the item to check (as passed to self::getRightsFor())
    *             'rights'     => when use of self::getRightsFor() is impossible
    *             'label'      => the label for the right
    *             'field'      => the name of the field inside the DB and HTML form (prefixed by '_')
    *             'html_field' => when $html_field != '_'.$field
    * @param $options array   possible:
    *             'title'         the title of the matrix
    *             'canedit'
    *             'default_class' the default CSS class used for the row
    *
    * @return random value used to generate the ids
   **/
   function displayRightsChoiceMatrix(array $rights, array $options = []) {

      $param                  = [];
      $param['title']         = '';
      $param['canedit']       = true;
      $param['default_class'] = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      // To be completed before display to avoid non available rights in DB
       $availablerights = ProfileRight::getAllPossibleRights();

      $column_labels = [];
      $columns       = [];
      $rows          = [];

      foreach ($rights as $info) {

         if (is_string($info)) {
            $rows[] = $info;
            continue;
         }
         if (is_array($info)
             && ((!empty($info['itemtype'])) || (!empty($info['rights'])))
             && (!empty($info['label']))
             && (!empty($info['field']))) {
            // Add right if it does not exists : security for update
            if (!isset($availablerights[$info['field']])) {
               ProfileRight::addProfileRights([$info['field']]);
            }

            $row = ['label'   => $info['label'],
                         'columns' => []];
            if (!empty($info['row_class'])) {
               $row['class'] = $info['row_class'];
            } else {
               $row['class'] = $param['default_class'];
            }
            if (isset($this->fields[$info['field']])) {
               $profile_right = $this->fields[$info['field']];
            } else {
               $profile_right = 0;
            }

            if (isset($info['rights'])) {
               $itemRights = $info['rights'];
            } else {
               $itemRights = self::getRightsFor($info['itemtype']);
            }
            foreach ($itemRights as $right => $label) {
               if (!isset($column_labels[$right])) {
                  $column_labels[$right] = [];
               }
               if (is_array($label)) {
                  $long_label = $label['long'];
               } else {
                  $long_label = $label;
               }
               if (!isset($column_labels[$right][$long_label])) {
                  $column_labels[$right][$long_label] = count($column_labels[$right]);
               }
               $right_value                  = $right.'_'.$column_labels[$right][$long_label];

               $columns[$right_value]        = $label;

               $checked                      = ((($profile_right & $right) == $right) ? 1 : 0);
               $row['columns'][$right_value] = ['checked' => $checked];
               if (!$param['canedit']) {
                  $row['columns'][$right_value]['readonly'] = true;
               }
            }
            if (!empty($info['html_field'])) {
               $rows[$info['html_field']] = $row;
            } else {
               $rows['_'.$info['field']] = $row;
            }
         }
      }

      uksort($columns, function ($a, $b) {
         $a = explode('_', $a);
         $b = explode('_', $b);

         // For standard rights sort by right
         if (($a[0] < 1024)
             || ($b[0] < 1024)) {
            if ($a[0] > $b[0]) {
               return true;
            }
            if ($a[0] < $b[0]) {
               return false;
            }
            return ($a[1] > $b[1]);
            // For extra right sort by type
         }
         return ($a[1] > $b[1]);
      });

      return Html::showCheckboxMatrix($columns, $rows,
                                      ['title'                => $param['title'],
                                            'row_check_all'        => count($columns) > 1,
                                            'col_check_all'        => count($rows) > 1,
                                            'rotate_column_titles' => false]);
   }


   /**
    * Get right linear right choice.
    *
    * @since 0.85
    *
    * @param $elements  array   all pair identifier => label
    * @param $options   array   possible:
    *             'canedit'
    *             'field'         name of the HTML field
    *             'value'         the value inside the database
    *             'max_per_line'  maximum number of elements per line
    *             'check_all'     add a checkbox to check or uncheck every checkbox
    *             'rand'          random value used to generate the ids
    *             'zero_on_empty' do we send 0 when checkbox is not checked ?
    *             'display'
    *             'check_method'  method used to check the right
    *
    * @return content if !display
   **/
   static function getLinearRightChoice(array $elements, array $options = []) {

      $param                  = [];
      $param['canedit']       = true;
      $param['field']         = '';
      $param['value']         = '';
      $param['max_per_line']  = 10;
      $param['check_all']     = false;
      $param['rand']          = mt_rand();
      $param['zero_on_empty'] = true;
      $param['display']       = true;
      $param['check_method']  = function ($element, $field) {
         return (($field & $element) == $element);
      };

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      if (empty($param['field'])) {
         return;
      }

      $nb_cbs      = count($elements);
      $cb_options  = ['readonly' => !$param['canedit']];
      if ($param['check_all']) {
         $nb_cbs ++;
         $massive_tag                = 'checkall_'.$param['field'].'_'.$param['rand'];
         $cb_options['massive_tags'] = $massive_tag;
      }

      $nb_lines         = ceil($nb_cbs / $param['max_per_line']);
      $nb_item_per_line = ceil($nb_cbs / $nb_lines);

      $out              = '';

      $count            = 0;
      $nb_checked       = 0;
      foreach ($elements as $element => $label) {
         if ($count != 0) {
            if (($count % $nb_item_per_line) == 0) {
               $out .= "<br>\n";
            } else {
               $out .= "&nbsp;-\n\t\t&nbsp;";
            }
         } else {
            $out .= "\n\t\t";
         }
         $out                        .= $label.'&nbsp;';
         $cb_options['name']          = $param['field'].'['.$element.']';
         $cb_options['id']            = Html::cleanId('checkbox_linear_'.$cb_options['name'].
                                                      '_'.$param['rand']);
         $cb_options['zero_on_empty'] = $param['zero_on_empty'];

         $cb_options['checked']       = $param['check_method']($element,
                                                               $param['value']);

         $out                        .= Html::getCheckbox($cb_options);
         $count ++;
         if ($cb_options['checked']) {
            $nb_checked ++;
         }
      }

      if ($param['check_all']) {
         $cb_options = ['criterion' => ['tag_for_massive' => $massive_tag],
                             'id'        => Html::cleanId('checkbox_linear_'.$param['rand'])];
         if ($nb_checked > (count($elements) / 2)) {
            $cb_options['checked'] = true;
         }
         $out .= "&nbsp;-&nbsp;<i><b>".__('Select/unselect all')."</b></i>&nbsp;".
                  Html::getCheckbox($cb_options);
      }

      if (!$param['display']) {
         return $out;
      }

      echo $out;
   }

}
