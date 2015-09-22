<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/**
 * Entity class
 */
class Entity extends CommonTreeDropdown {

   public $must_be_replace              = true;
   public $dohistory                    = true;

   public $first_level_menu             = "admin";
   public $second_level_menu            = "entity";

   static $rightname                    = 'entity';
   protected $usenotepad                = true;

   const READHELPDESK                   = 1024;
   const UPDATEHELPDESK                 = 2048;

   const CONFIG_PARENT                  = -2;
   const CONFIG_NEVER                   = -10;

   const AUTO_ASSIGN_HARDWARE_CATEGORY  = 1;
   const AUTO_ASSIGN_CATEGORY_HARDWARE  = 2;

   // Array of "right required to update" => array of fields allowed
   // Missing field here couldn't be update (no right)
   private static $field_right = array('entity'
                                          => array(// Address
                                                   'address', 'country', 'email', 'fax', 'notepad',
                                                   'phonenumber', 'postcode', 'state', 'town',
                                                   'website',
                                                   // Advanced (could be user_authtype ?)
                                                   'authldaps_id', 'entity_ldapfilter', 'ldap_dn',
                                                   'mail_domain', 'tag',
                                                   // Inventory
                                                   'entities_id_software', 'level', 'name',
                                                   'completename', 'entities_id',
                                                   'ancestors_cache', 'sons_cache', 'comment'),
                                       // Inventory
                                       'infocom'
                                          => array('autofill_buy_date', 'autofill_delivery_date',
                                                   'autofill_order_date', 'autofill_use_date',
                                                   'autofill_warranty_date'),
                                       // Notification
                                       'notification'
                                          => array('admin_email', 'admin_reply', 'admin_email_name',
                                                   'admin_reply_name', 'delay_send_emails',
                                                   'is_notif_enable_default',
                                                   'default_cartridges_alarm_threshold',
                                                   'default_consumables_alarm_threshold',
                                                   'default_contract_alert', 'default_infocom_alert',
                                                   'mailing_signature', 'cartridges_alert_repeat',
                                                   'consumables_alert_repeat', 'notclosed_delay',
                                                   'use_licenses_alert',
                                                   'send_licenses_alert_before_delay',
                                                   'use_contracts_alert',
                                                   'send_contracts_alert_before_delay',
                                                   'use_reservations_alert', 'use_infocoms_alert',
                                                   'send_infocoms_alert_before_delay',
                                                   'notification_subject_tag'),
                                       // Helpdesk
                                       'entity_helpdesk'
                                          => array('calendars_id', 'tickettype', 'auto_assign_mode',
                                                   'autoclose_delay', 'inquest_config',
                                                   'inquest_rate', 'inquest_delay',
                                                   'inquest_duration','inquest_URL',
                                                   'max_closedate', 'tickettemplates_id'));


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'delete';
      $forbidden[] = 'purge';
      $forbidden[] = 'restore';
      $forbidden[] = 'CommonDropdown'.MassiveAction::CLASS_ACTION_SEPARATOR.'merge';
      return $forbidden;
   }


   /**
    * @since version 0.84
   **/
   function pre_deleteItem() {

      // Security do not delete root entity
      if ($this->input['id'] == 0) {
         return false;
      }
      return true;
   }


   static function getTypeName($nb=0) {
      return _n('Entity', 'Entities', $nb);
   }


   function canCreateItem() {
      // Check the parent
      return Session::haveRecursiveAccessToEntity($this->getField('entities_id'));
   }


  /**
   * @since version 0.84
   **/
   static function canUpdate() {

      return (Session::haveRightsOr(self::$rightname, array(UPDATE, self::UPDATEHELPDESK))
              || Session::haveRight('notification', UPDATE));
   }


   function canUpdateItem() {
      // Check the current entity
      return Session::haveAccessToEntity($this->getField('id'));
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBTM::canViewItem()
   **/
   function canViewItem() {
      // Check the current entity
      return Session::haveAccessToEntity($this->getField('id'));
   }


   /**
    * @see CommonDBTM::isNewID()
   **/
   static function isNewID($ID) {
      return (($ID < 0) || !strlen($ID));
   }


   /**
    * Check right on each field before add / update
    *
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $input array (form)
    *
    * @return array (filtered input)
   **/
   private function checkRightDatas($input) {

      $tmp = array();

      if (isset($input['id'])) {
         $tmp['id'] = $input['id'];
      }

      foreach (self::$field_right as $right => $fields) {

         if ($right == 'entity_helpdesk') {
            if (Session::haveRight(self::$rightname, self::UPDATEHELPDESK )) {
               foreach ($fields as $field) {
                  if (isset($input[$field])) {
                     $tmp[$field] = $input[$field];
                  }
               }
            }
         } else {
            if (Session::haveRight($right, UPDATE)) {
               foreach ($fields as $field) {
                  if (isset($input[$field])) {
                     $tmp[$field] = $input[$field];
                  }
               }
            }
         }
      }
      // Add framework  / internal ones
      foreach ($input as $key => $val) {
         if ($key[0] == '_') {
            $tmp[$key] = $input[$key];
         }
      }

      return $tmp;
   }


   /**
    * @since version 0.84 (before in entitydata.class)
   **/
   function prepareInputForAdd($input) {
      global $DB;

      $input = parent::prepareInputForAdd($input);

      $query = "SELECT MAX(`id`)+1 AS newID
                FROM `glpi_entities`";
      if ($result = $DB->query($query)) {
          $input['id'] = $DB->result($result,0,0);
      } else {
         return false;
      }
      $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      return $this->checkRightDatas($input);
   }


   /**
    * @since version 0.84 (before in entitydata.class)
   **/
   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);

      // Si on change le taux de déclenchement de l'enquête (enquête activée) ou le type de l'enquete,
      // cela s'applique aux prochains tickets - Pas à l'historique
      if ((isset($input['inquest_rate'])
           && (($this->fields['inquest_rate'] == 0)
               || is_null($this->fields['max_closedate']))
           && ($input['inquest_rate'] != $this->fields['inquest_rate']))
          || (isset($input['inquest_config'])
              && (($this->fields['inquest_config'] == self::CONFIG_PARENT)
                  || is_null($this->fields['max_closedate']))
              && ($input['inquest_config'] != $this->fields['inquest_config']))) {

         $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      }

      // Force entities_id = -1 for root entity
      if ($input['id'] == 0) {
         $input['entities_id'] = -1;
         $input['level']       = 1;
      }
      if (!Session::isCron()) { // Filter input for connected
         $input = $this->checkRightDatas($input);
      }
      return $input;
   }


   /**
    * @see CommonTreeDropdown::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Profile_User',$ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Document_Item',$ong, $options);
      $this->addStandardTab('Notepad',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }


   /**
    * @since version 0.84 (before in entitydata.class)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = array();
               $ong[1] = $this->getTypeName(Session::getPluralNumber());
               $ong[2] = __('Address');
               $ong[3] = __('Advanced information');
               if (Notification::canView()) {
                  $ong[4] = _n('Notification', 'Notifications', Session::getPluralNumber());
               }
               if (Session::haveRightsOr(self::$rightname,
                                         array(self::READHELPDESK, self::UPDATEHELPDESK))) {
                  $ong[5] = __('Assistance');
               }
               $ong[6] = __('Assets');

               return $ong;
         }
      }
      return '';
   }


   /**
    * @since version 0.84 (before in entitydata.class)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()==__CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showChildren();
               break;

            case 2 :
               self::showStandardOptions($item);
               break;

            case 3 :
               self::showAdvancedOptions($item);
               break;

            case 4 :
               self::showNotificationOptions($item);
               break;

            case 5 :
               self::showHelpdeskOptions($item);
               break;

            case 6 :
               self::showInventoryOptions($item);
               break;
         }
      }
      return true;
   }


   /**
    * Print a good title for entity pages
    *
    *@return nothing (display)
    **/
   function title() {
      // Empty title for entities
   }


   function displayHeader() {
      Html::header($this->getTypeName(1), '', "admin", "entity");
   }


   /**
    * Get the ID of entity assigned to the object
    *
    * simply return ID
    *
    * @return ID of the entity
   **/
   function getEntityID() {

      if (isset($this->fields["id"])) {
         return $this->fields["id"];
      }
      return -1;
   }


   function isEntityAssign() {
      return true;
   }


   function maybeRecursive() {
      return true;
   }


   /**
    * Is the object recursive
    *
    * Entity are always recursive
    *
    * @return integer (0/1)
   **/
   function isRecursive () {
      return true;
   }


   function post_addItem() {

      parent::post_addItem();

      // Add right to current user - Hack to avoid login/logout
      $_SESSION['glpiactiveentities'][$this->fields['id']] = $this->fields['id'];
      $_SESSION['glpiactiveentities_string']              .= ",'".$this->fields['id']."'";
   }


   function cleanDBonPurge() {
      global $DB;

      // most use entities_id, RuleDictionnarySoftwareCollection use new_entities_id
      Rule::cleanForItemAction($this, '%entities_id');
      Rule::cleanForItemCriteria($this);

      $gki = new Entity_KnowbaseItem();
      $gki->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $gr = new Entity_Reminder();
      $gr->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function getSearchOptions() {

      $tab                     = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'completename';
      $tab[1]['name']          = __('Complete name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']      = 'number';

      $tab[14]['table']         = $this->getTable();
      $tab[14]['field']         = 'name';
      $tab[14]['name']          = __('Name');
      $tab[14]['datatype']      = 'itemlink';
      $tab[14]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'address';
      $tab[3]['name']          = __('Address');
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'text';

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'website';
      $tab[4]['name']          = __('Website');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'string';

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'phonenumber';
      $tab[5]['name']          = __('Phone');
      $tab[5]['massiveaction'] = false;
      $tab[5]['datatype']      = 'string';

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'email';
      $tab[6]['name']          = _n('Email', 'Emails', 1);
      $tab[6]['datatype']      = 'email';
      $tab[6]['massiveaction'] = false;

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'fax';
      $tab[10]['name']          = __('Fax');
      $tab[10]['massiveaction'] = false;
      $tab[10]['datatype']      = 'string';

      $tab[25]['table']         = $this->getTable();
      $tab[25]['field']         = 'postcode';
      $tab[25]['name']          = __('Postal code');
      $tab[25]['datatype']      = 'string';

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'town';
      $tab[11]['name']          = __('City');
      $tab[11]['massiveaction'] = false;
      $tab[11]['datatype']      = 'string';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'state';
      $tab[12]['name']          = _x('location','State');
      $tab[12]['massiveaction'] = false;
      $tab[12]['datatype']      = 'string';

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'country';
      $tab[13]['name']          = __('Country');
      $tab[13]['massiveaction'] = false;
      $tab[13]['datatype']      = 'string';

      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'comment';
      $tab[16]['name']          = __('Comments');
      $tab[16]['datatype']      = 'text';

      $tab += Notepad::getSearchOptionsToAdd();

      $tab['advanced']         = __('Advanced information');

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'ldap_dn';
      $tab[7]['name']          = __('LDAP directory information attribute representing the entity');
      $tab[7]['massiveaction'] = false;
      $tab[7]['datatype']      = 'string';

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'tag';
      $tab[8]['name']          = __('Information in inventory tool (TAG) representing the entity');
      $tab[8]['massiveaction'] = false;
      $tab[8]['datatype']      = 'string';

      $tab[9]['table']         = 'glpi_authldaps';
      $tab[9]['field']         = 'name';
      $tab[9]['name']          = __('LDAP directory of an entity');
      $tab[9]['massiveaction'] = false;
      $tab[9]['datatype']      = 'dropdown';

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'entity_ldapfilter';
      $tab[17]['name']          = __('Search filter (if needed)');
      $tab[17]['massiveaction'] = false;
      $tab[17]['datatype']      = 'string';

      $tab[20]['table']         = $this->getTable();
      $tab[20]['field']         = 'mail_domain';
      $tab[20]['name']          = __('Mail domain');
      $tab[20]['massiveaction'] = false;
      $tab[20]['datatype']      = 'string';

      $tab['notif']             = __('Notification options');

      $tab[60]['table']         = $this->getTable();
      $tab[60]['field']         = 'delay_send_emails';
      $tab[60]['name']          = __('Delay to send email notifications');
      $tab[60]['massiveaction'] = false;
      $tab[60]['nosearch']      = true;
      $tab[60]['datatype']      = 'number';
      $tab[60]['min']           = 0;
      $tab[60]['max']           = 60;
      $tab[60]['step']          = 1;
      $tab[60]['unit']          = 'minute';
      $tab[60]['toadd']         = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));

      $tab[61]['table']         = $this->getTable();
      $tab[61]['field']         = 'is_notif_enable_default';
      $tab[61]['name']          = __('Enable notifications by default');
      $tab[61]['massiveaction'] = false;
      $tab[61]['nosearch']      = true;
      $tab[61]['datatype']      = 'string';

      $tab[18]['table']         = $this->getTable();
      $tab[18]['field']         = 'admin_email';
      $tab[18]['name']          = __('Administrator email');
      $tab[18]['massiveaction'] = false;
      $tab[18]['datatype']      = 'string';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'admin_reply';
      $tab[19]['name']          = __('Administrator reply-to email (if needed)');
      $tab[19]['massiveaction'] = false;
      $tab[19]['datatype']      = 'string';

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'notification_subject_tag';
      $tab[21]['name']          = __('Prefix for notifications');
      $tab[21]['datatype']      = 'string';

      $tab[22]['table']         = $this->getTable();
      $tab[22]['field']         = 'admin_email_name';
      $tab[22]['name']          = __('Administrator name');
      $tab[22]['datatype']      = 'string';

      $tab[23]['table']         = $this->getTable();
      $tab[23]['field']         = 'admin_reply_name';
      $tab[23]['name']          = __('Response address (if needed)');
      $tab[23]['datatype']      = 'string';

      $tab[24]['table']         = $this->getTable();
      $tab[24]['field']         = 'mailing_signature';
      $tab[24]['name']          = __('Email signature');
      $tab[24]['datatype']      = 'text';

      $tab[26]['table']         = $this->getTable();
      $tab[26]['field']         = 'cartridges_alert_repeat';
      $tab[26]['name']          = __('Alarms on cartridges');
      $tab[26]['massiveaction'] = false;
      $tab[26]['nosearch']      = true;
      $tab[26]['datatype']      = 'specific';

      $tab[27]['table']         = $this->getTable();
      $tab[27]['field']         = 'consumables_alert_repeat';
      $tab[27]['name']          = __('Alarms on consumables');
      $tab[27]['massiveaction'] = false;
      $tab[27]['nosearch']      = true;
      $tab[27]['datatype']      = 'specific';

      $tab[29]['table']         = $this->getTable();
      $tab[29]['field']         = 'use_licenses_alert';
      $tab[29]['name']          = __('Alarms on expired licenses');
      $tab[29]['massiveaction'] = false;
      $tab[29]['nosearch']      = true;
      $tab[29]['datatype']      = 'specific';

      $tab[53]['table']         = $this->getTable();
      $tab[53]['field']         = 'send_licenses_alert_before_delay';
      $tab[53]['name']          = __('Send license alarms before');
      $tab[53]['massiveaction'] = false;
      $tab[53]['nosearch']      = true;
      $tab[53]['datatype']      = 'specific';

      $tab[30]['table']         = $this->getTable();
      $tab[30]['field']         = 'use_contracts_alert';
      $tab[30]['name']          = __('Alarms on contracts');
      $tab[30]['massiveaction'] = false;
      $tab[30]['nosearch']      = true;
      $tab[30]['datatype']      = 'specific';

      $tab[54]['table']         = $this->getTable();
      $tab[54]['field']         = 'send_contracts_alert_before_delay';
      $tab[54]['name']          = __('Send contract alarms before');
      $tab[54]['massiveaction'] = false;
      $tab[54]['nosearch']      = true;
      $tab[54]['datatype']      = 'specific';

      $tab[31]['table']         = $this->getTable();
      $tab[31]['field']         = 'use_infocoms_alert';
      $tab[31]['name']          = __('Alarms on financial and administrative information');
      $tab[31]['massiveaction'] = false;
      $tab[31]['nosearch']      = true;
      $tab[31]['datatype']      = 'specific';

      $tab[55]['table']         = $this->getTable();
      $tab[55]['field']         = 'send_infocoms_alert_before_delay';
      $tab[55]['name']          = __('Send financial and administrative information alarms before');
      $tab[55]['massiveaction'] = false;
      $tab[55]['nosearch']      = true;
      $tab[55]['datatype']      = 'specific';

      $tab[32]['table']         = $this->getTable();
      $tab[32]['field']         = 'use_reservations_alert';
      $tab[32]['name']          = __('Alerts on reservations');
      $tab[32]['massiveaction'] = false;
      $tab[32]['nosearch']      = true;
      $tab[32]['datatype']      = 'specific';

      $tab[48]['table']         = $this->getTable();
      $tab[48]['field']         = 'default_contract_alert';
      $tab[48]['name']          =__('Default value for alarms on contracts');
      $tab[48]['massiveaction'] = false;
      $tab[48]['nosearch']      = true;
      $tab[48]['datatype']      = 'specific';

      $tab[49]['table']         = $this->getTable();
      $tab[49]['field']         = 'default_infocom_alert';
      $tab[49]['name']          = __('Default value for alarms on financial and administrative information');
      $tab[49]['massiveaction'] = false;
      $tab[49]['nosearch']      = true;
      $tab[49]['datatype']      = 'specific';

      $tab[50]['table']         = $this->getTable();
      $tab[50]['field']         = 'default_cartridges_alarm_threshold';
      $tab[50]['name']          = __('Default threshold for cartridges count');
      $tab[50]['massiveaction'] = false;
      $tab[50]['nosearch']      = true;
      $tab[50]['datatype']      = 'number';

      $tab[52]['table']         = $this->getTable();
      $tab[52]['field']         = 'default_consumables_alarm_threshold';
      $tab[52]['name']          = __('Default threshold for consumables count');
      $tab[52]['massiveaction'] = false;
      $tab[52]['nosearch']      = true;
      $tab[52]['datatype']      = 'number';

      $tab['helpdesk']          = __('Assistance');

      $tab[47]['table']         = $this->getTable();
      $tab[47]['field']         = 'tickettemplates_id';  // not a dropdown because of special value
      $tab[47]['name']          = _n('Ticket template', 'Ticket templates', 1);
      $tab[47]['massiveaction'] = false;
      $tab[47]['nosearch']      = true;
      $tab[47]['datatype']      = 'specific';

      $tab[33]['table']         = $this->getTable();
      $tab[33]['field']         = 'autoclose_delay';
      $tab[33]['name']          = __('Automatic closing of solved tickets after');
      $tab[33]['massiveaction'] = false;
      $tab[33]['nosearch']      = true;
      $tab[33]['datatype']      = 'number';
      $tab[33]['min']           = 1;
      $tab[33]['max']           = 99;
      $tab[33]['step']          = 1;
      $tab[33]['unit']          = 'day';
      $tab[33]['toadd']         = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                                        self::CONFIG_NEVER  => __('Never'),
                                        0                   => __('Immediatly'));

      $tab[34]['table']         = $this->getTable();
      $tab[34]['field']         = 'notclosed_delay';
      $tab[34]['name']          = __('Alerts on tickets which are not solved');
      $tab[34]['massiveaction'] = false;
      $tab[34]['nosearch']      = true;
      $tab[34]['datatype']      = 'specific';

      $tab[35]['table']         = $this->getTable();
      $tab[35]['field']         = 'auto_assign_mode';
      $tab[35]['name']          = __('Automatic assignment of tickets');
      $tab[35]['massiveaction'] = false;
      $tab[35]['nosearch']      = true;
      $tab[35]['datatype']      = 'specific';

      $tab[36]['table']         = $this->getTable();
      $tab[36]['field']         = 'calendars_id'; // not a dropdown because of special value
      $tab[36]['name']          = __('Calendar');
      $tab[36]['massiveaction'] = false;
      $tab[36]['nosearch']      = true;
      $tab[36]['datatype']      = 'specific';

      $tab[37]['table']         = $this->getTable();
      $tab[37]['field']         = 'tickettype';
      $tab[37]['name']          = __('Tickets default type');
      $tab[37]['massiveaction'] = false;
      $tab[37]['nosearch']      = true;
      $tab[37]['datatype']      = 'specific';

      $tab['helpdesk']          = __('Assets');

      $tab[38]['table']         = $this->getTable();
      $tab[38]['field']         = 'autofill_buy_date';
      $tab[38]['name']          = __('Date of purchase');
      $tab[38]['massiveaction'] = false;
      $tab[38]['nosearch']      = true;
      $tab[38]['datatype']      = 'specific';

      $tab[39]['table']         = $this->getTable();
      $tab[39]['field']         = 'autofill_order_date';
      $tab[39]['name']          = __('Order date');
      $tab[39]['massiveaction'] = false;
      $tab[39]['nosearch']      = true;
      $tab[39]['datatype']      = 'specific';

      $tab[40]['table']         = $this->getTable();
      $tab[40]['field']         = 'autofill_delivery_date';
      $tab[40]['name']          = __('Delivery date');
      $tab[40]['massiveaction'] = false;
      $tab[40]['nosearch']      = true;
      $tab[40]['datatype']      = 'specific';

      $tab[41]['table']         = $this->getTable();
      $tab[41]['field']         = 'autofill_use_date';
      $tab[41]['name']          = __('Startup date');
      $tab[41]['massiveaction'] = false;
      $tab[41]['nosearch']      = true;
      $tab[41]['datatype']      = 'specific';

      $tab[42]['table']         = $this->getTable();
      $tab[42]['field']         = 'autofill_warranty_date';
      $tab[42]['name']          = __('Start date of warranty');
      $tab[42]['massiveaction'] = false;
      $tab[42]['nosearch']      = true;
      $tab[42]['datatype']      = 'specific';

      $tab[43]['table']         = $this->getTable();
      $tab[43]['field']         = 'inquest_config';
      $tab[43]['name']          = __('Satisfaction survey configuration');
      $tab[43]['massiveaction'] = false;
      $tab[43]['nosearch']      = true;
      $tab[43]['datatype']      = 'specific';

      $tab[44]['table']         = $this->getTable();
      $tab[44]['field']         = 'inquest_rate';
      $tab[44]['name']          = __('Satisfaction survey trigger rate');
      $tab[44]['massiveaction'] = false;
      $tab[44]['datatype']      = 'number';

      $tab[45]['table']         = $this->getTable();
      $tab[45]['field']         = 'inquest_delay';
      $tab[45]['name']          = __('Create survey after');
      $tab[45]['massiveaction'] = false;
      $tab[45]['datatype']      = 'number';

      $tab[46]['table']         = $this->getTable();
      $tab[46]['field']         = 'inquest_URL';
      $tab[46]['name']          = __('URL');
      $tab[46]['massiveaction'] = false;
      $tab[46]['datatype']      = 'string';

      $tab[51]['table']         = $this->getTable();
      $tab[51]['field']         = 'entities_id_software';   // not a dropdown because of special value
                                  //TRANS: software in plural
      $tab[51]['name']          = __('Entity for software creation');
      $tab[51]['massiveaction'] = false;
      $tab[51]['nosearch']      = true;
      $tab[51]['datatype']      = 'specific';

      return $tab;
   }


   /**
    * Display entities of the loaded profile
    *
    * @param $target target for entity change action
    * @param $myname select name
   **/
   static function showSelector($target, $myname) {
      global $CFG_GLPI;

      $rand = mt_rand();

      echo "<div class='center'>";
      echo "<span class='b'>".__('Select the desired entity')."<br>( <img src='".$CFG_GLPI["root_doc"].
            "/pics/entity_all.png' alt=''> ".__s('to see the entity and its sub-entities').")</span>".
            "<br>";
      echo "<a style='font-size:14px;' href='".$target."?active_entity=all' title=\"".
             __s('Show all')."\">".str_replace(" ","&nbsp;",__('Show all'))."</a></div>";

      echo "<div class='left' style='width:100%'>";
      echo "<form id='entsearchform'>";
      echo Html::input('entsearchtext', array('id' => 'entsearchtext'));
      echo Html::submit(__('Search'), array('id' => 'entsearch'));
      echo "</form>";

      echo "<script type='text/javascript'>";
      echo Html::jsGetElementbyID("tree_projectcategory$rand")."
         // call `.jstree` with the options object
         .jstree({
            // the `plugins` array allows you to configure the active plugins on this instance
            'plugins' : ['themes','json_data', 'search'],
            'core': {
               'load_open': true,
               'html_titles': true,
               'animation': 0
            },
            'themes': {
               'theme': 'classic',
               'url'  : '".$CFG_GLPI["root_doc"]."/css/jstree/style.css'
            },
            'search': {
               'case_insensitive': true,
               'show_only_matches': true,
               'ajax': {
                  'type': 'POST',
                 'url': '".$CFG_GLPI["root_doc"]."/ajax/entitytreesearch.php'
               }
            },
            'json_data': {
               'ajax': {
                  'type': 'POST',
                  'url': function (node) {
                     var nodeId = '';
                     var url = '';
                     if (node == -1) {
                         url = '".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php?node=-1';
                     }
                     else {
                         nodeId = node.attr('id');
                         url = '".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php?node='+nodeId;
                     }

                     return url;
                  },
                  'success': function (new_data) {
                      //where new_data = node children
                      //e.g.: [{'data':'Hardware','attr':{'id':'child2'}},
                      //         {'data':'Software','attr':{'id':'child3'}}]
                      return new_data;
                  },
                  'progressive_render' : true
               }
            }
         }).bind('select_node.jstree', function (e, data) {
            document.location.href = data.rslt.obj.children('a').attr('href');
         });

         var searchTree = function() {
            ".Html::jsGetElementbyID("tree_projectcategory$rand").".jstree('close_all');;
            ".Html::jsGetElementbyID("tree_projectcategory$rand").
            ".jstree('search',".Html::jsGetDropdownValue('entsearchtext').");
         }

         $('#entsearchform').submit(function( event ) {
            // cancel submit of entity search form
            event.preventDefault();

            // search
            searchTree();
         });

         // delay function who reinit timer on each call
         var typewatch = (function(){
            var timer = 0;
            return function(callback, ms){
               clearTimeout (timer);
               timer = setTimeout(callback, ms);
            };
         })();

         // autosearch on keypress (delayed and with min length)
         $('#entsearchtext').keyup(function () {
            var inputsearch = $(this);
            typewatch(function () {
               if (inputsearch.val().length >= 3) {
                  searchTree();
               }
            }, 500);
         })
         .focus();
     ";


      echo "</script>";

      echo "<div id='tree_projectcategory$rand' ></div>";
      echo "</div>";
   }


   /**
    * @since version 0.83 (before addRule)
    *
    * @param $input array of values
   **/
   function executeAddRule($input) {

      $this->check($_POST["affectentity"], UPDATE);

      $collection = RuleCollection::getClassByType($_POST['sub_type']);
      $rule       = $collection->getRuleClass($_POST['sub_type']);
      $ruleid     = $rule->add($_POST);

      if ($ruleid) {
         //Add an action associated to the rule
         $ruleAction = new RuleAction();

         //Action is : affect computer to this entity
         $ruleAction->addActionByAttributes("assign", $ruleid, "entities_id",
                                            $_POST["affectentity"]);

         switch ($_POST['sub_type']) {
            case 'RuleRight' :
               if ($_POST["profiles_id"]) {
                  $ruleAction->addActionByAttributes("assign", $ruleid, "profiles_id",
                                                     $_POST["profiles_id"]);
               }
               $ruleAction->addActionByAttributes("assign", $ruleid, "is_recursive",
                                                  $_POST["is_recursive"]);
         }
      }

      Event::log($ruleid, "rules", 4, "setup",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds the item'), $_SESSION["glpiname"]));

      Html::back();
   }


   /**
    * get all entities with a notification option set
    * manage CONFIG_PARENT (or NULL) value
    *
    * @param $field  String name of the field to search (>0)
    *
    * @return Array of id => value
   **/
   static function getEntitiesToNotify($field) {
      global $DB, $CFG_GLPI;

      $entities = array();

      // root entity first
      $ent = new self();
      if ($ent->getFromDB(0)) {  // always exists
         $val = $ent->getField($field);
         if ($val > 0) {
            $entities[0] = $val;
         }
      }

      // Others entities in level order (parent first)
      $query = "SELECT `glpi_entities`.`id` AS `entity`,
                       `glpi_entities`.`entities_id` AS `parent`,
                       `glpi_entities`.`$field`
                FROM `glpi_entities`
                ORDER BY `glpi_entities`.`level` ASC";


      foreach ($DB->request($query) as $entitydatas) {
         if ((is_null($entitydatas[$field])
              || ($entitydatas[$field] == self::CONFIG_PARENT))
             && isset($entities[$entitydatas['parent']])) {

            // config inherit from parent
            $entities[$entitydatas['entity']] = $entities[$entitydatas['parent']];

         } else if ($entitydatas[$field] > 0) {

            // config found in entity
            $entities[$entitydatas['entity']] = $entitydatas[$field];
         }
      }

      return $entities;
   }


   /**
    * @since version 0.84
    *
    * @param $entity Entity object
   **/
   static function showStandardOptions(Entity $entity) {

      $con_spotted = false;
      $ID          = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      // Entity right applied
      $canedit = $entity->can($ID, UPDATE);

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('Address')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Phone')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "phonenumber");
      echo "</td>";
      echo "<td rowspan='7'>".__('Address')."</td>";
      echo "<td rowspan='7'>";
      echo "<textarea cols='45' rows='8' name='address'>". $entity->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Fax')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "fax");
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Website')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "website");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Email', 'Emails', 1)."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Postal code')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity,"postcode", array('size' => 7));
      echo "&nbsp;&nbsp;". __('City'). "&nbsp;";
      Html::autocompletionTextField($entity, "town", array('size' => 27));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('location','State')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Country')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "country");
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $entity Entity object
   **/
   static function showAdvancedOptions(Entity $entity) {
      global $DB;

      $con_spotted = false;
      $ID          = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      // Entity right applied (could be User::UPDATEAUTHENT)
      $canedit = $entity->can($ID, UPDATE);

      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>".__('Values for the generic rules for assignment to entities').
           "</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2' class='center'>".
             __('These parameters are used as actions in generic rules for assignment to entities').
           "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Information in inventory tool (TAG) representing the entity')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "tag", array('size' => 100));
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP directory information attribute representing the entity')."</td>";
         echo "<td>";
         Html::autocompletionTextField($entity, "ldap_dn", array('size' => 100));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Mail domain surrogates entity')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "mail_domain", array('size' => 100));
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr><th colspan='2'>".
                __('Values used in the interface to search users from a LDAP directory').
              "</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP directory of an entity')."</td>";
         echo "<td>";
         AuthLDAP::dropdown(array('value'      => $entity->fields['authldaps_id'],
                                  'emptylabel' => __('Default server'),
                                  'condition'  => "`is_active` = '1'"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP filter associated to the entity (if necessary)')."</td>";
         echo "<td>";
         Html::autocompletionTextField($entity, 'entity_ldapfilter', array('size' => 100));
         echo "</td></tr>";
      }

     if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $entity Entity object
   **/
   static function showInventoryOptions(Entity $entity) {

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      // Notification right applied
      $canedit = (Infocom::canUpdate() && Session::haveAccessToEntity($ID));

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('Autofill dates for financial and administrative information').
           "</th></tr>";


      $options[0] = __('No autofill');
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      foreach (getAllDatasFromTable('glpi_states') as $state) {
         $options[Infocom::ON_STATUS_CHANGE.'_'.$state['id']]
                     //TRANS: %s is the name of the state
            = sprintf(__('Fill when shifting to state %s'), $state['name']);
      }

      $options[Infocom::COPY_WARRANTY_DATE] = __('Copy the start date of warranty');
      //Buy date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Date of purchase') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('autofill_buy_date', $options,
                              array('value' => $entity->getField('autofill_buy_date')));
      echo "</td>";

      //Order date
      echo "<td> " . __('Order date') . "</td>";
      echo "<td>";
      $options[Infocom::COPY_BUY_DATE] = __('Copy the date of purchase');
      Dropdown::showFromArray('autofill_order_date', $options,
                              array('value' => $entity->getField('autofill_order_date')));
      echo "</td></tr>";

      //Delivery date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Delivery date') . "</td>";
      echo "<td>";
      $options[Infocom::COPY_ORDER_DATE] = __('Copy the order date');
      Dropdown::showFromArray('autofill_delivery_date', $options,
                              array('value' => $entity->getField('autofill_delivery_date')));
      echo "</td>";

      //Use date
      echo "<td> " . __('Startup date') . " </td>";
      echo "<td>";
      $options[Infocom::COPY_DELIVERY_DATE] = __('Copy the delivery date');
      Dropdown::showFromArray('autofill_use_date', $options,
                              array('value' => $entity->getField('autofill_use_date')));
      echo "</td></tr>";

      //Warranty date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Start date of warranty') . "</td>";
      echo "<td>";
      $options = array(0                           => __('No autofill'),
                       Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                       Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                       Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date'));
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      Dropdown::showFromArray('autofill_warranty_date', $options,
                              array('value' => $entity->getField('autofill_warranty_date')));
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr><th colspan='4'>"._n('Software', 'Software', Session::getPluralNumber())."</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Entity for software creation') . "</td>";
      echo "<td>";

      $toadd = array(self::CONFIG_NEVER => __('No change of entity')); // Keep software in PC entity
      if ($ID > 0) {
         $toadd[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }
      $entities = array($entity->fields['entities_id']);
      foreach (getAncestorsOf('glpi_entities',  $entity->fields['entities_id']) as $ent) {
         if (Session::haveAccessToEntity($ent)) {
            $entities[] = $ent;
         }
      }

      self::dropdown(array('name'     => 'entities_id_software',
                           'value'    => $entity->getField('entities_id_software'),
                           'toadd'    => $toadd,
                           'entity'   => $entities,
                           'comments' => false));

      if ($entity->fields['entities_id_software'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('entities_id_software', $entity->getField('entities_id'));
         echo "<font class='green'>&nbsp;&nbsp;";
         echo self::getSpecificValueToDisplay('entities_id_software', $tid);
         echo "</font>";
      }
      echo "</td><td colspan='2'></td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";

   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $entity Entity object
   **/
   static function showNotificationOptions(Entity $entity) {

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)
          || !Notification::canView()) {
         return false;
      }

      // Notification right applied
      $canedit = (Notification::canUpdate()
                  && Session::haveAccessToEntity($ID));

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('Notification options')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Administrator email')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "admin_email");
      echo "</td>";
      echo "<td>" . __('Administrator name') . "</td><td>";
      Html::autocompletionTextField($entity, "admin_email_name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Administrator reply-to email (if needed)')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "admin_reply");
      echo "</td>";
      echo "<td>" . __('Response address (if needed)') . "</td><td>";
      Html::autocompletionTextField($entity, "admin_reply_name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Prefix for notifications')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "notification_subject_tag");
      echo "</td>";
      echo "<td>".__('Delay to send email notifications')."</td>";
      echo "<td>";
      $toadd=array();
      if ($ID > 0) {
         $toadd = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));
      }
      Dropdown::showNumber('delay_send_emails', array('value' => $entity->fields["delay_send_emails"],
                                                      'min'   => 0,
                                                      'max'   => 100,
                                                      'unit'  => 'minute',
                                                      'toadd' => $toadd));

      if ($entity->fields['delay_send_emails'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('delay_send_emails', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo $entity->getValueToDisplay('delay_send_emails', $tid, array('html' => true));
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Enable notifications by default')."</td>";
      echo "<td>";

      Alert::dropdownYesNo(array('name'           => "is_notif_enable_default",
                                 'value'          =>  $entity->getField('is_notif_enable_default'),
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)));


      if ($entity->fields['is_notif_enable_default'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('is_notif_enable_default', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('is_notif_enable_default', $tid);
         echo "</font>";
      }
      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td>";

      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle right'>" . __('Email signature') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='60' rows='5' name='mailing_signature'>".
             $entity->fields["mailing_signature"]."</textarea>";
      echo "</td></tr>";
      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".__('Alarms options')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='2'>";
      echo _n('Cartridge', 'Cartridges', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Reminders frequency for alarms on cartridges') . "</td><td>";
      $default_value = $entity->fields['cartridges_alert_repeat'];
      Alert::dropdown(array('name'           => 'cartridges_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => (($ID > 0) ? 1 : 0)));

      if ($entity->fields['cartridges_alert_repeat'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('cartridges_alert_repeat', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('cartridges_alert_repeat', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Default threshold for cartridges count') ."</td><td>";
      if ($ID > 0) {
         $toadd = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                        self::CONFIG_NEVER => __('Never'));
      } else {
         $toadd = array(self::CONFIG_NEVER => __('Never'));
      }
      Dropdown::showNumber('default_cartridges_alarm_threshold',
                            array('value' => $entity->fields["default_cartridges_alarm_threshold"],
                                  'min'   => 0,
                                  'max'   => 100,
                                  'step'  => 1,
                                  'toadd' => $toadd));
      if ($entity->fields['default_cartridges_alarm_threshold'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('default_cartridges_alarm_threshold',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('default_cartridges_alarm_threshold', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='2'>";
      echo _n('Consumable', 'Consumables', Session::getPluralNumber());
      echo "</th>";

      echo "<td>" . __('Reminders frequency for alarms on consumables') . "</td><td>";
      $default_value = $entity->fields['consumables_alert_repeat'];
      Alert::dropdown(array('name'           => 'consumables_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['consumables_alert_repeat'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('consumables_alert_repeat', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('consumables_alert_repeat', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Default threshold for consumables count') ."</td><td>";
      if ($ID > 0) {
         $toadd = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                        self::CONFIG_NEVER => __('Never'));
      } else {
         $toadd = array(self::CONFIG_NEVER => __('Never'));
      }
      Dropdown::showNumber('default_consumables_alarm_threshold',
                            array('value' => $entity->fields["default_consumables_alarm_threshold"],
                                  'min'   => 0,
                                  'max'   => 100,
                                  'step'  => 1,
                                  'toadd' => $toadd));
         if ($entity->fields['default_consumables_alarm_threshold'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('default_consumables_alarm_threshold',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('default_consumables_alarm_threshold', $tid);
         echo "</font>";

      }
      echo "</td></tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='3'>";
      echo _n('Contract', 'Contracts', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Alarms on contracts') . "</td><td>";
      $default_value = $entity->fields['use_contracts_alert'];
      Alert::dropdownYesNo(array('name'           => "use_contracts_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['use_contracts_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_contracts_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_contracts_alert', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Default value') . "</td><td>";
      Contract::dropdownAlert(array('name'           => "default_contract_alert",
                                    'value'          => $entity->fields["default_contract_alert"],
                                    'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['default_contract_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('default_contract_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('default_contract_alert', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Send contract alarms before')."</td><td>";
      Alert::dropdownIntegerNever('send_contracts_alert_before_delay',
                                  $entity->fields['send_contracts_alert_before_delay'],
                                  array('max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')));
      if ($entity->fields['send_contracts_alert_before_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('send_contracts_alert_before_delay',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('send_contracts_alert_before_delay', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='3'>";
      _e('Financial and administrative information');
      echo "</th>";
      echo "<td>" . __('Alarms on financial and administrative information') . "</td><td>";
      $default_value = $entity->fields['use_infocoms_alert'];
      Alert::dropdownYesNo(array('name'           => "use_infocoms_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['use_infocoms_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_infocoms_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_infocoms_alert', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Default value')."</td><td>";
      Infocom::dropdownAlert(array('name'           => 'default_infocom_alert',
                                   'value'          => $entity->fields["default_infocom_alert"],
                                   'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['default_infocom_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('default_infocom_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('default_infocom_alert', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Send financial and administrative information alarms before')."</td><td>";
      Alert::dropdownIntegerNever('send_infocoms_alert_before_delay',
                                  $entity->fields['send_infocoms_alert_before_delay'],
                                  array('max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')));
      if ($entity->fields['send_infocoms_alert_before_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('send_infocoms_alert_before_delay',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('send_infocoms_alert_before_delay', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='2'>";
      echo _n('License', 'Licenses', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Alarms on expired licenses') . "</td><td>";
      $default_value = $entity->fields['use_licenses_alert'];
      Alert::dropdownYesNo(array('name'           => "use_licenses_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)));
      if ($entity->fields['use_licenses_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_licenses_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_licenses_alert', $tid);
         echo "</font>";
      }
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Send license alarms before')."</td><td>";
      Alert::dropdownIntegerNever('send_licenses_alert_before_delay',
                                  $entity->fields['send_licenses_alert_before_delay'],
                                  array('max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')));
      if ($entity->fields['send_licenses_alert_before_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('send_licenses_alert_before_delay',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('send_licenses_alert_before_delay', $tid);
         echo "</font>";
      }

      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='1'>";
      echo _n('Reservation', 'Reservations', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Alerts on reservations') . "</td><td>";
      Alert::dropdownIntegerNever('use_reservations_alert',
                                  $entity->fields['use_reservations_alert'],
                                  array('max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'hour'));
      if ($entity->fields['use_reservations_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_reservations_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_reservations_alert', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='1'>";
      echo _n('Ticket', 'Tickets', Session::getPluralNumber());
      echo "</th>";
      echo "<td >". __('Alerts on tickets which are not solved since'). "</td><td>";
      Alert::dropdownIntegerNever('notclosed_delay', $entity->fields["notclosed_delay"],
                                  array('max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day'));
      if ($entity->fields['notclosed_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('notclosed_delay', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('notclosed_delay', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $field
    * @param $value must be addslashes
   **/
   private static function getEntityIDByField($field,$value) {
      global $DB;

      $sql = "SELECT `id`
              FROM `glpi_entities`
              WHERE `".$field."` = '".$value."'";
      $result = $DB->query($sql);

      if ($DB->numrows($result) == 1) {
         return $DB->result($result, 0, "id");
      }
      return -1;
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByDN($value) {
      return self::getEntityIDByField("ldap_dn", $value);
   }


   /**
    * @since version 0.84
    *
    * @param $value
   **/
   static function getEntityIDByCompletename($value) {
      return self::getEntityIDByField("completename", $value);
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByTag($value) {
      return self::getEntityIDByField("tag", $value);
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByDomain($value) {
      return self::getEntityIDByField("mail_domain", $value);
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $entities_id
   **/
   static function isEntityDirectoryConfigured($entities_id) {

      $entity = new self();

      if ($entity->getFromDB($entities_id)
          && ($entity->getField('authldaps_id') > 0)) {
         return true;
      }

      //If there's a directory marked as default
      if (AuthLdap::getDefault()) {
         return true;
      }
      return false;
   }


   /**
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $entity Entity object
   **/
   static function showHelpdeskOptions(Entity $entity) {
      global $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)
          || !Session::haveRightsOr(self::$rightname,
                                    array(self::READHELPDESK, self::UPDATEHELPDESK))) {
         return false;
      }
      $canedit = (Session::haveRight(self::$rightname, self::UPDATEHELPDESK)
                  && Session::haveAccessToEntity($ID));

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><td colspan='2'>"._n('Ticket template', 'Ticket templates', 1).
           "</td>";
      echo "<td colspan='2'>";
      $toadd = array();
      if ($ID != 0) {
         $toadd = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));
      }

      $options = array('value'  => $entity->fields["tickettemplates_id"],
                       'entity' => $ID,
                       'toadd'  => $toadd);

      TicketTemplate::dropdown($options);

      if (($entity->fields["tickettemplates_id"] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         echo "<font class='green'>&nbsp;&nbsp;";

         $tt  = new TicketTemplate();
         $tid = self::getUsedConfig('tickettemplates_id', $ID, '', 0);
         if (!$tid) {
            echo Dropdown::EMPTY_VALUE;
         } else if ($tt->getFromDB($tid)) {
            echo $tt->getLink();
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".__('Calendar')."</td>";
      echo "<td colspan='2'>";
      $options = array('value'      => $entity->fields["calendars_id"],
                       'emptylabel' => __('24/7'));

      if ($ID != 0) {
         $options['toadd'] = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));
      }
      Calendar::dropdown($options);

      if (($entity->fields["calendars_id"] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         echo "<font class='green'>&nbsp;&nbsp;";
         $calendar = new Calendar();
         $cid = self::getUsedConfig('calendars_id', $ID, '', 0);
         if (!$cid) {
            _e('24/7');
         } else if ($calendar->getFromDB($cid)) {
            echo $calendar->getLink();
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".__('Tickets default type')."</td>";
      echo "<td colspan='2'>";
      $toadd = array();
      if ($ID != 0) {
         $toadd = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));
      }
      Ticket::dropdownType('tickettype', array('value' => $entity->fields["tickettype"],
                                               'toadd' => $toadd));

      if (($entity->fields['tickettype'] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         echo "<font class='green'>&nbsp;&nbsp;";
         echo Ticket::getTicketTypeName(self::getUsedConfig('tickettype', $ID, '',
                                                            Ticket::INCIDENT_TYPE));
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td  colspan='2'>".__('Automatic assignment of tickets')."</td>";
      echo "<td colspan='2'>";
      $autoassign = self::getAutoAssignMode();

      if ($ID == 0) {
         unset($autoassign[self::CONFIG_PARENT]);
      }

      Dropdown::showFromArray('auto_assign_mode', $autoassign,
                              array('value' => $entity->fields["auto_assign_mode"]));

      if (($entity->fields['auto_assign_mode'] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         $auto_assign_mode = self::getUsedConfig('auto_assign_mode', $entity->fields['entities_id']);
         echo "<font class='green'>&nbsp;&nbsp;";
         echo $autoassign[$auto_assign_mode];
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr><th colspan='4'>".__('Automatic closing configuration')."</th></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td colspan='2'>".__('Automatic closing of solved tickets after')."</td>";
      echo "<td colspan='2'>";
      $autoclose = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                         self::CONFIG_NEVER  => __('Never'),
                         0                   => __('Immediatly'));
      if ($ID == 0) {
         unset($autoclose[self::CONFIG_PARENT]);
      }

      Dropdown::showNumber('autoclose_delay',
                           array('value' => $entity->fields['autoclose_delay'],
                                 'min'   => 1,
                                 'max'   => 99,
                                 'step'  => 1,
                                 'toadd' => $autoclose,
                                 'unit'  => 'day'));

      if (($entity->fields['autoclose_delay'] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         $autoclose_mode = self::getUsedConfig('autoclose_delay', $entity->fields['entities_id'],
                                               '', self::CONFIG_NEVER);

         echo "<br><font class='green'>&nbsp;&nbsp;";
         if ($autoclose_mode >= 0) {
            printf(_n('%d day','%d days',$autoclose_mode), $autoclose_mode);
         } else {
            echo $autoclose[$autoclose_mode];
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr><th colspan='4'>".__('Configuring the satisfaction survey')."</th></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td colspan='2'>".__('Configuring the satisfaction survey')."</td>";
      echo "<td colspan='2'>";

      /// no inquest case = rate 0
      $typeinquest = array(self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                           1                    => __('Internal survey'),
                           2                    => __('External survey'));

      // No inherit from parent for root entity
      if ($ID == 0) {
         unset($typeinquest[self::CONFIG_PARENT]);
         if ($entity->fields['inquest_config'] == self::CONFIG_PARENT) {
            $entity->fields['inquest_config'] = 1;
         }
      }
      $rand = Dropdown::showFromArray('inquest_config', $typeinquest,
                                      $options = array('value' => $entity->fields['inquest_config']));
      echo "</td></tr>\n";

      // Do not display for root entity in inherit case
      if (($entity->fields['inquest_config'] == self::CONFIG_PARENT)
          && ($ID !=0)) {
         $inquestconfig = self::getUsedConfig('inquest_config', $entity->fields['entities_id']);
         $inquestrate   = self::getUsedConfig('inquest_config', $entity->fields['entities_id'],
                                              'inquest_rate');
         echo "<tr class='tab_bg_1'><td colspan='4' class='green center'>";

         if ($inquestrate == 0) {
            _e('Disabled');
         } else {
            echo $typeinquest[$inquestconfig].'<br>';
            $inqconf = self::getUsedConfig('inquest_config', $entity->fields['entities_id'],
                                           'inquest_delay');

            printf(_n('%d day','%d days',$inqconf), $inqconf);
            echo "<br>";
            //TRANS: %d is the percentage. %% to display %
            printf(__('%d%%'), $inquestrate);

            if ($inquestconfig == 2) {
               echo "<br>";
               echo self::getUsedConfig('inquest_config', $entity->fields['entities_id'],
                                        'inquest_URL');
            }
         }
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td colspan='4'>";

      $_POST  = array('inquest_config' => $entity->fields['inquest_config'],
                      'entities_id'    => $ID);
      $params = array('inquest_config' => '__VALUE__',
                      'entities_id'    => $ID);
      echo "<div id='inquestconfig'>";
      include GLPI_ROOT.'/ajax/ticketsatisfaction.php';
      echo "</div>\n";

      echo "</td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan='4'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button','Save')."\"
                  class='submit'>";

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";

      Ajax::updateItemOnSelectEvent("dropdown_inquest_config$rand", "inquestconfig",
                                    $CFG_GLPI["root_doc"]."/ajax/ticketsatisfaction.php", $params);
   }


   /**
    * Retrieve data of current entity or parent entity
    *
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $fieldref        string   name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval        string   name of the field that we want value (default '')
    * @param $default_value            value to return (default -2)
   **/
   static function getUsedConfig($fieldref, $entities_id, $fieldval='', $default_value=-2) {

      // for calendar
      if (empty($fieldval)) {
         $fieldval = $fieldref;
      }

      $entity = new self();
      // Search in entity data of the current entity
      if ($entity->getFromDB($entities_id)) {
         // Value is defined : use it
         if (isset($entity->fields[$fieldref])) {
            // Numerical value
            if (is_numeric($default_value)
                && ($entity->fields[$fieldref] != self::CONFIG_PARENT)) {
               return $entity->fields[$fieldval];
            }
            // String value
            if (!is_numeric($default_value)
                && $entity->fields[$fieldref]) {
               return $entity->fields[$fieldval];
            }
         }
      }

      // Entity data not found or not defined : search in parent one
      if ($entities_id > 0) {

         if ($entity->getFromDB($entities_id)) {
            $ret = self::getUsedConfig($fieldref, $entity->fields['entities_id'], $fieldval,
                                       $default_value);
            return $ret;

         }
      }
/*
      switch ($fieldval) {
         case "tickettype" :
            // Default is Incident if not set
            return Ticket::INCIDENT_TYPE;
      }
      */
      return $default_value;
   }


   /**
    * Generate link for ticket satisfaction
    *
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $ticket ticket object
    *
    * @return url contents
   **/
   static function generateLinkSatisfaction($ticket) {
      global $DB;

      $url = self::getUsedConfig('inquest_config', $ticket->fields['entities_id'], 'inquest_URL');

      if (strstr($url,"[TICKET_ID]")) {
         $url = str_replace("[TICKET_ID]", $ticket->fields['id'], $url);
      }

      if (strstr($url,"[TICKET_NAME]")) {
         $url = str_replace("[TICKET_NAME]", urlencode($ticket->fields['name']), $url);
      }

      if (strstr($url,"[TICKET_CREATEDATE]")) {
         $url = str_replace("[TICKET_CREATEDATE]", $ticket->fields['date'], $url);
      }

      if (strstr($url,"[TICKET_SOLVEDATE]")) {
         $url = str_replace("[TICKET_SOLVEDATE]", $ticket->fields['solvedate'], $url);
      }

      if (strstr($url,"[REQUESTTYPE_ID]")) {
         $url = str_replace("[REQUESTTYPE_ID]", $ticket->fields['requesttypes_id'], $url);
      }

      if (strstr($url,"[REQUESTTYPE_NAME]")) {
         $url = str_replace("[REQUESTTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_requesttypes',
                                                                $ticket->fields['requesttypes_id'])),
                            $url);
      }

      if (strstr($url,"[ITEMTYPE]")
          && $ticket->fields['itemtype']
          && ($objet = getItemForItemtype($ticket->fields['itemtype']))) {
         $url = str_replace("[ITEMTYPE]", urlencode($objet->getTypeName(1)), $url);
      }

      if (strstr($url,"[ITEM_ID]")) {
         $url = str_replace("[ITEM_ID]", $ticket->fields['items_id'], $url);
      }

      if (strstr($url,"[ITEM_NAME]")
          && $ticket->fields['itemtype']
          && ($objet = getItemForItemtype($ticket->fields['itemtype']))) {
         if ($objet->getFromDB($ticket->fields['items_id'])) {
            $url = str_replace("[ITEM_NAME]", urlencode($objet->getName()), $url);
         }
      }

      if (strstr($url,"[TICKET_PRIORITY]")) {
         $url = str_replace("[TICKET_PRIORITY]", $ticket->fields['priority'], $url);
      }

      if (strstr($url,"[TICKETCATEGORY_ID]")) {
         $url = str_replace("[TICKETCATEGORY_ID]", $ticket->fields['itilcategories_id'], $url);
      }

      if (strstr($url,"[TICKETCATEGORY_NAME]")) {
         $url = str_replace("[TICKETCATEGORY_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_itilcategories',
                                                                $ticket->fields['itilcategories_id'])),
                            $url);
      }

      if (strstr($url,"[TICKETTYPE_ID]")) {
         $url = str_replace("[TICKETTYPE_ID]", $ticket->fields['type'], $url);
      }

      if (strstr($url,"[TICKET_TYPENAME]")) {
         $url = str_replace("[TICKET_TYPENAME]",
                            Ticket::getTicketTypeName($ticket->fields['type']), $url);
      }

      if (strstr($url,"[SOLUTIONTYPE_ID]")) {
         $url = str_replace("[SOLUTIONTYPE_ID]", $ticket->fields['solutiontypes_id'], $url);
      }

      if (strstr($url,"[SOLUTIONTYPE_NAME]")) {
         $url = str_replace("[SOLUTIONTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                $ticket->fields['solutiontypes_id'])),
                            $url);
      }

      if (strstr($url,"[SLA_ID]")) {
         $url = str_replace("[SLA_ID]", $ticket->fields['slas_id'], $url);
      }

      if (strstr($url,"[SLA_NAME]")) {
         $url = str_replace("[SLA_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slas',
                                                                $ticket->fields['slas_id'])),
                            $url);
      }

      if (strstr($url,"[SLALEVEL_ID]")) {
         $url = str_replace("[SLALEVEL_ID]", $ticket->fields['slalevels_id'], $url);
      }

      if (strstr($url,"[SLALEVEL_NAME]")) {
         $url = str_replace("[SLALEVEL_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slalevels',
                                                                $ticket->fields['slalevels_id'])),
                            $url);
      }

      return $url;
   }

   /**
    * get value for auto_assign_mode
    *
    * @since version 0.84 (created in version 0.83 in entitydata.class)
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getAutoAssignMode($val=NULL) {

      $tab = array(self::CONFIG_PARENT                  => __('Inheritance of the parent entity'),
                   self::CONFIG_NEVER                   => __('No'),
                   self::AUTO_ASSIGN_HARDWARE_CATEGORY  => __('Based on the item then the category'),
                   self::AUTO_ASSIGN_CATEGORY_HARDWARE  => __('Based on the category then the item'));

      if (is_null($val)) {
         return $tab;
      }
      if (isset($tab[$val])) {
         return $tab[$val];
      }
      return NOT_AVAILABLE;
   }

   /**
    * @since version 0.84
    *
    * @param $options array
   **/
   static function dropdownAutoAssignMode(array $options) {

      $p['name']    = 'auto_assign_mode';
      $p['value']   = 0;
      $p['display'] = true;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = self::getAutoAssignMode();
      return Dropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * @since version 0.84 (before in entitydata.class)
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
         case 'use_licenses_alert' :
         case 'use_contracts_alert' :
         case 'use_infocoms_alert' :
         case 'is_notif_enable_default' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            return Dropdown::getYesNo($values[$field]);

         case 'use_reservations_alert' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case 0 :
                  return __('Never');
            }
            return sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]);

         case 'default_cartridges_alarm_threshold' :
         case 'default_consumables_alarm_threshold' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case 0 :
                  return __('Never');
            }
            return $values[$field];

         case 'send_contracts_alert_before_delay' :
         case 'send_infocoms_alert_before_delay' :
         case 'send_licenses_alert_before_delay' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case 0 :
                  return __('No');
            }
            return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);

         case 'cartridges_alert_repeat' :
         case 'consumables_alert_repeat' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case self::CONFIG_NEVER :
               case 0 : // For compatibility issue
                  return __('Never');

               case DAY_TIMESTAMP :
                  return __('Each day');

               case WEEK_TIMESTAMP :
                  return __('Each week');

               case MONTH_TIMESTAMP :
                  return __('Each month');

               default :
                  // Display value if not defined
                  return $values[$field];
            }
            break;

         case 'notclosed_delay' :   // 0 means never
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case 0 :
                  return __('Never');
             }
           return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);

         case 'auto_assign_mode' :
            return self::getAutoAssignMode($values[$field]);

         case 'tickettype' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            return Ticket::getTicketTypeName($values[$field]);

         case 'autofill_buy_date' :
         case 'autofill_order_date' :
         case 'autofill_delivery_date' :
         case 'autofill_use_date' :
         case 'autofill_warranty_date' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case Infocom::COPY_WARRANTY_DATE :
                  return __('Copy the start date of warranty');

               case Infocom::COPY_BUY_DATE :
                  return __('Copy the date of purchase');

               case Infocom::COPY_ORDER_DATE :
                  return __('Copy the order date');

               case Infocom::COPY_DELIVERY_DATE :
                  return __('Copy the delivery date');

               default :
                  if (strstr($values[$field], '_')) {
                     list($type,$sid) = explode('_', $values[$field], 2);
                     if ($type == Infocom::ON_STATUS_CHANGE) {
                                       // TRANS %s is the name of the state
                        return sprintf(__('Fill when shifting to state %s'),
                                       Dropdown::getDropdownName('glpi_states', $sid));
                     }
                  }
            }
            return __('No autofill');

         case 'inquest_config' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            return TicketSatisfaction::getTypeInquestName($values[$field]);

         case 'default_contract_alert' :
            return Contract::getAlertName($values[$field]);

         case 'default_infocom_alert' :
            return Infocom::getAlertName($values[$field]);

         case 'entities_id_software' :
            if ($values[$field] == self::CONFIG_NEVER) {
               return __('No change of entity');
            }
            if ($values[$field] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            return Dropdown::getDropdownName('glpi_entities', $values[$field]);

         case 'tickettemplates_id' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            return Dropdown::getDropdownName('glpi_tickettemplates', $values[$field]);

         case 'calendars_id' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return __('Inheritance of the parent entity');

               case 0 :
                  return __('24/7');
            }
            return Dropdown::getDropdownName('glpi_calendars', $values[$field]);

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
      global $DB;

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'use_licenses_alert' :
         case 'use_contracts_alert' :
         case 'use_infocoms_alert' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return Alert::dropdownYesNo($options);

         case 'cartridges_alert_repeat' :
         case 'consumables_alert_repeat' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return Alert::dropdown($options);

         case 'send_contracts_alert_before_delay' :
         case 'send_infocoms_alert_before_delay' :
         case 'send_licenses_alert_before_delay' :
            $options['unit']         = 'day';
            $options['never_string'] = __('No');
            return Alert::dropdownIntegerNever($name, $values[$field], $options);

         case 'use_reservations_alert' :
            $options['unit']  = 'hour';
            return Alert::dropdownIntegerNever($name, $values[$field], $options);

         case 'notclosed_delay' :
            $options['unit']  = 'hour';
            return Alert::dropdownIntegerNever($name, $values[$field], $options);

         case 'auto_assign_mode' :
            $options['name']  = $name;
            $options['value'] = $values[$field];

            return self::dropdownAutoAssignMode($options);

         case 'tickettype' :
            $options['value'] = $values[$field];
            $options['toadd'] = array(self::CONFIG_PARENT => __('Inheritance of the parent entity'));
            return Ticket::dropdownType($name, $options);

         case 'autofill_buy_date' :
         case 'autofill_order_date' :
         case 'autofill_delivery_date' :
         case 'autofill_use_date' :
            $tab[0]                   = __('No autofill');
            $tab[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
            foreach (getAllDatasFromTable('glpi_states') as $state) {
               $tab[Infocom::ON_STATUS_CHANGE.'_'.$state['id']]
                           //TRANS: %s is the name of the state
                  = sprintf(__('Fill when shifting to state %s'), $state['name']);
            }
            $tab[Infocom::COPY_WARRANTY_DATE] = __('Copy the start date of warranty');
            if ($field != 'autofill_buy_date') {
               $tab[Infocom::COPY_BUY_DATE] = __('Copy the date of purchase');
               if ($field != 'autofill_order_date') {
                  $tab[Infocom::COPY_ORDER_DATE] = __('Copy the order date');
                  if ($field != 'autofill_delivery_date') {
                     $options[Infocom::COPY_DELIVERY_DATE] = __('Copy the delivery date');
                  }
               }
            }
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, $tab, $options);

         case 'autofill_warranty_date' :
            $tab = array(0                           => __('No autofill'),
                         Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                         Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                         Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date'),
                         self::CONFIG_PARENT         => __('Inheritance of the parent entity'));
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, $tab, $options);

         case 'inquest_config' :
            $typeinquest = array(self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                                 1                    => __('Internal survey'),
                                 2                    => __('External survey'));
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, $typeinquest, $options);

         case 'default_contract_alert' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return Contract::dropdownAlert($options);

         case 'default_infocom_alert' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return Infocom::dropdownAlert($options);

         case 'entities_id_software' :
            $options['toadd'] = array(self::CONFIG_NEVER => __('No change of entity')); // Keep software in PC entity
            $options['toadd'][self::CONFIG_PARENT] = __('Inheritance of the parent entity');

            return self::dropdown($options);

      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      $values = parent::getRights();
      $values[self::READHELPDESK]   = array('short' => __('Read parameters'),
                                            'long'  => __('Read helpdesk parameters'));
      $values[self::UPDATEHELPDESK] = array('short' => __('Update parameters'),
                                            'long'  => __('Update helpdesk parameters'));

      return $values;
   }

}
?>
