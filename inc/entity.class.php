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

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
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
   private static $field_right = ['entity'
                                          => [// Address
                                                   'address', 'country', 'email', 'fax', 'notepad',
                                                   'phonenumber', 'postcode', 'state', 'town',
                                                   'website',
                                                   // Advanced (could be user_authtype ?)
                                                   'authldaps_id', 'entity_ldapfilter', 'ldap_dn',
                                                   'mail_domain', 'tag',
                                                   // Inventory
                                                   'entities_id_software', 'level', 'name',
                                                   'completename', 'entities_id',
                                                   'ancestors_cache', 'sons_cache', 'comment'],
                                          // Inventory
                                          'infocom'
                                          => ['autofill_buy_date', 'autofill_delivery_date',
                                                   'autofill_order_date', 'autofill_use_date',
                                                   'autofill_warranty_date',
                                                   'autofill_decommission_date'],
                                          // Notification
                                          'notification'
                                          => ['admin_email', 'admin_reply', 'admin_email_name',
                                                   'admin_reply_name', 'delay_send_emails',
                                                   'is_notif_enable_default',
                                                   'default_cartridges_alarm_threshold',
                                                   'default_consumables_alarm_threshold',
                                                   'default_contract_alert', 'default_infocom_alert',
                                                   'mailing_signature', 'cartridges_alert_repeat',
                                                   'consumables_alert_repeat', 'notclosed_delay',
                                                   'use_licenses_alert', 'use_certificates_alert',
                                                   'send_licenses_alert_before_delay',
                                                   'send_certificates_alert_before_delay',
                                                   'use_contracts_alert',
                                                   'send_contracts_alert_before_delay',
                                                   'use_reservations_alert', 'use_infocoms_alert',
                                                   'send_infocoms_alert_before_delay',
                                                   'notification_subject_tag'],
                                          // Helpdesk
                                          'entity_helpdesk'
                                          => ['calendars_id', 'tickettype', 'auto_assign_mode',
                                                   'autoclose_delay', 'inquest_config',
                                                   'inquest_rate', 'inquest_delay',
                                                   'inquest_duration','inquest_URL',
                                                   'max_closedate', 'tickettemplates_id']];


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'delete';
      $forbidden[] = 'purge';
      $forbidden[] = 'restore';
      $forbidden[] = 'CommonDropdown'.MassiveAction::CLASS_ACTION_SEPARATOR.'merge';
      return $forbidden;
   }


   /**
    * @since 0.84
   **/
   function pre_deleteItem() {
      global $GLPI_CACHE;

      // Security do not delete root entity
      if ($this->input['id'] == 0) {
         return false;
      }

      //Cleaning sons calls getAncestorsOf and thus... Re-create cache. Call it before clean.
      $this->cleanParentsSons();
      if (Toolbox::useCache()) {
         $ckey = $this->getTable() . '_ancestors_cache_' . $this->getID();
         if ($GLPI_CACHE->has($ckey)) {
            $GLPI_CACHE->delete($ckey);
         }
      }
      return true;
   }


   static function getTypeName($nb = 0) {
      return _n('Entity', 'Entities', $nb);
   }


   function canCreateItem() {
      // Check the parent
      return Session::haveRecursiveAccessToEntity($this->getField('entities_id'));
   }


   /**
   * @since 0.84
   **/
   static function canUpdate() {

      return (Session::haveRightsOr(self::$rightname, [UPDATE, self::UPDATEHELPDESK])
              || Session::haveRight('notification', UPDATE));
   }


   function canUpdateItem() {
      // Check the current entity
      return Session::haveAccessToEntity($this->getField('id'));
   }


   /**
    * @since 0.84
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
    * @since 0.84 (before in entitydata.class)
    *
    * @param $input array (form)
    *
    * @return array (filtered input)
   **/
   private function checkRightDatas($input) {

      $tmp = [];

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
    * @since 0.84 (before in entitydata.class)
   **/
   function prepareInputForAdd($input) {
      global $DB;

      $input['name'] = isset($input['name']) ? trim($input['name']) : '';
      if (empty($input["name"])) {
         Session::addMessageAfterRedirect(__("You can't add an entity without name"),
                                          false, ERROR);
         return false;
      }

      $input = parent::prepareInputForAdd($input);

      $result = $DB->request([
         'SELECT' => new \QueryExpression(
            'MAX('.$DB->quoteName('id').')+1 AS newID'
         ),
         'FROM'   => self::getTable()
      ])->next();
      $input['id'] = $result['newID'];

      $input['max_closedate'] = $_SESSION["glpi_currenttime"];

      if (!Session::isCron()) { // Filter input for connected
         $input = $this->checkRightDatas($input);
      }
      return $input;
   }


   /**
    * @since 0.84 (before in entitydata.class)
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
   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Profile_User', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @since 0.84 (before in entitydata.class)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = [];
               $ong[1] = $this->getTypeName(Session::getPluralNumber());
               $ong[2] = __('Address');
               $ong[3] = __('Advanced information');
               if (Notification::canView()) {
                  $ong[4] = _n('Notification', 'Notifications', Session::getPluralNumber());
               }
               if (Session::haveRightsOr(self::$rightname,
                                         [self::READHELPDESK, self::UPDATEHELPDESK])) {
                  $ong[5] = __('Assistance');
               }
               $ong[6] = __('Assets');

               return $ong;
         }
      }
      return '';
   }


   /**
    * @since 0.84 (before in entitydata.class)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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

      // most use entities_id, RuleDictionnarySoftwareCollection use new_entities_id
      Rule::cleanForItemAction($this, '%entities_id');
      Rule::cleanForItemCriteria($this);

      $this->deleteChildrenAndRelationsFromDb(
         [
            Entity_KnowbaseItem::class,
            Entity_Reminder::class,
            Entity_RSSFeed::class,
         ]
      );
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
         'field'              => 'completename',
         'name'               => __('Complete name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'address',
         'name'               => __('Address'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'website',
         'name'               => __('Website'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'phonenumber',
         'name'               => __('Phone'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'email',
         'name'               => _n('Email', 'Emails', 1),
         'datatype'           => 'email',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'fax',
         'name'               => __('Fax'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => $this->getTable(),
         'field'              => 'postcode',
         'name'               => __('Postal code'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'town',
         'name'               => __('City'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => _x('location', 'State'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'country',
         'name'               => __('Country'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '122',
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

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => 'advanced',
         'name'               => __('Advanced information')
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'ldap_dn',
         'name'               => __('LDAP directory information attribute representing the entity'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'tag',
         'name'               => __('Information in inventory tool (TAG) representing the entity'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_authldaps',
         'field'              => 'name',
         'name'               => __('LDAP directory of an entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'entity_ldapfilter',
         'name'               => __('Search filter (if needed)'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'mail_domain',
         'name'               => __('Mail domain'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => 'notif',
         'name'               => __('Notification options')
      ];

      $tab[] = [
         'id'                 => '60',
         'table'              => $this->getTable(),
         'field'              => 'delay_send_emails',
         'name'               => __('Delay to send email notifications'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'number',
         'min'                => 0,
         'max'                => 60,
         'step'               => 1,
         'unit'               => 'minute',
         'toadd'              => [self::CONFIG_PARENT => __('Inheritance of the parent entity')]
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'is_notif_enable_default',
         'name'               => __('Enable notifications by default'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'admin_email',
         'name'               => __('Administrator email'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'admin_reply',
         'name'               => __('Administrator reply-to email (if needed)'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'notification_subject_tag',
         'name'               => __('Prefix for notifications'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'admin_email_name',
         'name'               => __('Administrator name'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'admin_reply_name',
         'name'               => __('Response address (if needed)'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => $this->getTable(),
         'field'              => 'mailing_signature',
         'name'               => __('Email signature'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => $this->getTable(),
         'field'              => 'cartridges_alert_repeat',
         'name'               => __('Alarms on cartridges'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => $this->getTable(),
         'field'              => 'consumables_alert_repeat',
         'name'               => __('Alarms on consumables'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => $this->getTable(),
         'field'              => 'use_licenses_alert',
         'name'               => __('Alarms on expired licenses'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '53',
         'table'              => $this->getTable(),
         'field'              => 'send_licenses_alert_before_delay',
         'name'               => __('Send license alarms before'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'use_contracts_alert',
         'name'               => __('Alarms on contracts'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '54',
         'table'              => $this->getTable(),
         'field'              => 'send_contracts_alert_before_delay',
         'name'               => __('Send contract alarms before'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => $this->getTable(),
         'field'              => 'use_infocoms_alert',
         'name'               => __('Alarms on financial and administrative information'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '55',
         'table'              => $this->getTable(),
         'field'              => 'send_infocoms_alert_before_delay',
         'name'               => __('Send financial and administrative information alarms before'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => $this->getTable(),
         'field'              => 'use_reservations_alert',
         'name'               => __('Alerts on reservations'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '48',
         'table'              => $this->getTable(),
         'field'              => 'default_contract_alert',
         'name'               => __('Default value for alarms on contracts'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => $this->getTable(),
         'field'              => 'default_infocom_alert',
         'name'               => __('Default value for alarms on financial and administrative information'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '50',
         'table'              => $this->getTable(),
         'field'              => 'default_cartridges_alarm_threshold',
         'name'               => __('Default threshold for cartridges count'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '52',
         'table'              => $this->getTable(),
         'field'              => 'default_consumables_alarm_threshold',
         'name'               => __('Default threshold for consumables count'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '57',
         'table'              => $this->getTable(),
         'field'              => 'use_certificates_alert',
         'name'               => __('Alarms on expired certificates'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '58',
         'table'              => $this->getTable(),
         'field'              => 'send_certificates_alert_before_delay',
         'name'               => __('Send Certificate alarms before'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => 'helpdesk',
         'name'               => __('Assistance')
      ];

      $tab[] = [
         'id'                 => '47',
         'table'              => $this->getTable(),
         'field'              => 'tickettemplates_id', // not a dropdown because of special value
         'name'               => _n('Ticket template', 'Ticket templates', 1),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '33',
         'table'              => $this->getTable(),
         'field'              => 'autoclose_delay',
         'name'               => __('Automatic closing of solved tickets after'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'number',
         'min'                => 1,
         'max'                => 99,
         'step'               => 1,
         'unit'               => 'day',
         'toadd'              => [
            self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
            self::CONFIG_NEVER   => __('Never'),
            0                  => __('Immediatly')
         ]
      ];

      $tab[] = [
         'id'                 => '34',
         'table'              => $this->getTable(),
         'field'              => 'notclosed_delay',
         'name'               => __('Alerts on tickets which are not solved'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '35',
         'table'              => $this->getTable(),
         'field'              => 'auto_assign_mode',
         'name'               => __('Automatic assignment of tickets'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => $this->getTable(),
         'field'              => 'calendars_id',// not a dropdown because of special valu
         'name'               => __('Calendar'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '37',
         'table'              => $this->getTable(),
         'field'              => 'tickettype',
         'name'               => __('Tickets default type'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => 'assets',
         'name'               => __('Assets')
      ];

      $tab[] = [
         'id'                 => '38',
         'table'              => $this->getTable(),
         'field'              => 'autofill_buy_date',
         'name'               => __('Date of purchase'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '39',
         'table'              => $this->getTable(),
         'field'              => 'autofill_order_date',
         'name'               => __('Order date'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => $this->getTable(),
         'field'              => 'autofill_delivery_date',
         'name'               => __('Delivery date'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '41',
         'table'              => $this->getTable(),
         'field'              => 'autofill_use_date',
         'name'               => __('Startup date'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => $this->getTable(),
         'field'              => 'autofill_warranty_date',
         'name'               => __('Start date of warranty'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => $this->getTable(),
         'field'              => 'inquest_config',
         'name'               => __('Satisfaction survey configuration'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => $this->getTable(),
         'field'              => 'inquest_rate',
         'name'               => __('Satisfaction survey trigger rate'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => $this->getTable(),
         'field'              => 'inquest_delay',
         'name'               => __('Create survey after'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '46',
         'table'              => $this->getTable(),
         'field'              => 'inquest_URL',
         'name'               => __('URL'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '51',
         'table'              => $this->getTable(),
         'field'              => 'entities_id_software', // not a dropdown because of special value
                                 //TRANS: software in plural
         'name'               => __('Entity for software creation'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '56',
         'table'              => $this->getTable(),
         'field'              => 'autofill_decommission_date',
         'name'               => __('Decommission date'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific'
      ];

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

      if (Session::getCurrentInterface() == 'helpdesk') {
         $actionurl = $CFG_GLPI["root_doc"]."/front/helpdesk.public.php?active_entity=";
      } else {
         $actionurl = $CFG_GLPI["root_doc"]."/front/central.php?active_entity=";
      }

      echo "<div class='center'>";
      echo "<span class='b'>".__('Select the desired entity')."<br>( <img src='".$CFG_GLPI["root_doc"].
            "/pics/entity_all.png' alt=''> ".__s('to see the entity and its sub-entities').")</span>".
            "<br>";
      echo "<a style='font-size:14px;' href='".$target."?active_entity=all' title=\"".
             __s('Show all')."\">".str_replace(" ", "&nbsp;", __('Show all'))."</a></div>";

      echo "<div class='left' style='width:100%'>";
      echo "<form id='entsearchform'>";
      echo Html::input('entsearchtext', ['id' => 'entsearchtext']);
      echo Html::submit(__('Search'), ['id' => 'entsearch']);
      echo "</form>";

      echo "<script type='text/javascript'>";
      echo "   $(function() {
                  $.getScript('{$CFG_GLPI["root_doc"]}/lib/jqueryplugins/jstree/jstree.min.js').done(function(data, textStatus, jqxhr) {
                     $('#tree_projectcategory$rand')
                     // call `.jstree` with the options object
                     .jstree({
                        // the `plugins` array allows you to configure the active plugins on this instance
                        'plugins' : ['search', 'qload', 'conditionalselect'],
                        'search': {
                           'case_insensitive': true,
                           'show_only_matches': true,
                           'ajax': {
                              'type': 'POST',
                              'url': '".$CFG_GLPI["root_doc"]."/ajax/entitytreesearch.php'
                           }
                        },
                        'qload': {
                           'prevLimit': 50,
                           'nextLimit': 30,
                           'moreText': '".__s('Load more entities...')."'
                        },
                        'conditionalselect': function (node, event) {
                           if (node === false) {
                              return false;
                           }
                           var url = '$actionurl'+node.id;
                           if (event.target.tagName == 'I'
                               && event.target.className == '') {
                              url += '&is_recursive=1';
                           }
                           document.location.href = url;
                           return false;
                        },
                        'core': {
                           'themes': {
                              'name': 'glpi'
                           },
                           'animation': 0,
                           'data': {
                              'url': function(node) {
                                 return node.id === '#' ?
                                    '".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php?node=-1' :
                                    '".$CFG_GLPI["root_doc"]."/ajax/entitytreesons.php?node='+node.id;
                              }
                           }
                        }
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
                  });
               });";

      echo "</script>";

      echo "<div id='tree_projectcategory$rand' class='entity_tree' ></div>";
      echo "</div>";
   }


   /**
    * @since 0.83 (before addRule)
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

      $entities = [];

      // root entity first
      $ent = new self();
      if ($ent->getFromDB(0)) {  // always exists
         $val = $ent->getField($field);
         if ($val > 0) {
            $entities[0] = $val;
         }
      }

      // Others entities in level order (parent first)
      $iterator = $DB->request([
         'SELECT' => [
            'id AS entity',
            'entities_id AS parent',
            $field
         ],
         'FROM'   => self::getTable(),
         'ORDER'  => 'level ASC'
      ]);

      while ($entitydata = $iterator->next()) {
         if ((is_null($entitydata[$field])
              || ($entitydata[$field] == self::CONFIG_PARENT))
             && isset($entities[$entitydata['parent']])) {

            // config inherit from parent
            $entities[$entitydata['entity']] = $entities[$entitydata['parent']];

         } else if ($entitydata[$field] > 0) {

            // config found in entity
            $entities[$entitydata['entity']] = $entitydata[$field];
         }
      }

      return $entities;
   }


   /**
    * @since 0.84
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

      Plugin::doHook("pre_item_form", ['item' => $entity, 'options' => []]);

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
      Html::autocompletionTextField($entity, "postcode", ['size' => 7]);
      echo "&nbsp;&nbsp;". __('City'). "&nbsp;";
      Html::autocompletionTextField($entity, "town", ['size' => 27]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('location', 'State')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Country')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "country");
      echo "</td></tr>";
      Plugin::doHook("post_item_form", ['item' => $entity, 'options' => []]);
      echo "</table>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";

   }


   /**
    * @since 0.84 (before in entitydata.class)
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

      Plugin::doHook("pre_item_form", ['item' => $entity, 'options' => []]);

      echo "<tr><th colspan='2'>".__('Values for the generic rules for assignment to entities').
           "</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2' class='center'>".
             __('These parameters are used as actions in generic rules for assignment to entities').
           "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Information in inventory tool (TAG) representing the entity')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "tag", ['size' => 100]);
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP directory information attribute representing the entity')."</td>";
         echo "<td>";
         Html::autocompletionTextField($entity, "ldap_dn", ['size' => 100]);
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Mail domain surrogates entity')."</td>";
      echo "<td>";
      Html::autocompletionTextField($entity, "mail_domain", ['size' => 100]);
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr><th colspan='2'>".
                __('Values used in the interface to search users from a LDAP directory').
              "</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP directory of an entity')."</td>";
         echo "<td>";
         AuthLDAP::dropdown(['value'      => $entity->fields['authldaps_id'],
                                  'emptylabel' => __('Default server'),
                                  'condition'  => "`is_active` = 1"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('LDAP filter associated to the entity (if necessary)')."</td>";
         echo "<td>";
         Html::autocompletionTextField($entity, 'entity_ldapfilter', ['size' => 100]);
         echo "</td></tr>";
      }

      Plugin::doHook("post_item_form", ['item' => $entity, 'options' => &$options]);

      echo "</table>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
         Html::closeForm();
      }
   }


   /**
    * @since 0.84 (before in entitydata.class)
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

      Plugin::doHook("pre_item_form", ['item' => $entity, 'options' => []]);

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
                              ['value' => $entity->getField('autofill_buy_date')]);
      echo "</td>";

      //Order date
      echo "<td> " . __('Order date') . "</td>";
      echo "<td>";
      $options[Infocom::COPY_BUY_DATE] = __('Copy the date of purchase');
      Dropdown::showFromArray('autofill_order_date', $options,
                              ['value' => $entity->getField('autofill_order_date')]);
      echo "</td></tr>";

      //Delivery date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Delivery date') . "</td>";
      echo "<td>";
      $options[Infocom::COPY_ORDER_DATE] = __('Copy the order date');
      Dropdown::showFromArray('autofill_delivery_date', $options,
                              ['value' => $entity->getField('autofill_delivery_date')]);
      echo "</td>";

      //Use date
      echo "<td> " . __('Startup date') . " </td>";
      echo "<td>";
      $options[Infocom::COPY_DELIVERY_DATE] = __('Copy the delivery date');
      Dropdown::showFromArray('autofill_use_date', $options,
                              ['value' => $entity->getField('autofill_use_date')]);
      echo "</td></tr>";

      //Warranty date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Start date of warranty') . "</td>";
      echo "<td>";
      $options = [0                           => __('No autofill'),
                       Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                       Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                       Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date')];
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      Dropdown::showFromArray('autofill_warranty_date', $options,
                              ['value' => $entity->getField('autofill_warranty_date')]);
      echo "</td>";

      //Decommission date
      echo "<td> " . __('Decommission date') . "</td>";
      echo "<td>";

      $options = [0                           => __('No autofill'),
                       Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                       Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                       Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date')];
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      foreach (getAllDatasFromTable('glpi_states') as $state) {
         $options[Infocom::ON_STATUS_CHANGE.'_'.$state['id']]
                     //TRANS: %s is the name of the state
            = sprintf(__('Fill when shifting to state %s'), $state['name']);
      }

      Dropdown::showFromArray('autofill_decommission_date', $options,
                              ['value' => $entity->getField('autofill_decommission_date')]);

      echo "</td></tr>";

      echo "<tr><th colspan='4'>"._n('Software', 'Software', Session::getPluralNumber())."</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Entity for software creation') . "</td>";
      echo "<td>";

      $toadd = [self::CONFIG_NEVER => __('No change of entity')]; // Keep software in PC entity
      if ($ID > 0) {
         $toadd[self::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }
      $entities = [$entity->fields['entities_id']];
      foreach (getAncestorsOf('glpi_entities', $entity->fields['entities_id']) as $ent) {
         if (Session::haveAccessToEntity($ent)) {
            $entities[] = $ent;
         }
      }

      self::dropdown(['name'     => 'entities_id_software',
                           'value'    => $entity->getField('entities_id_software'),
                           'toadd'    => $toadd,
                           'entity'   => $entities,
                           'comments' => false]);

      if ($entity->fields['entities_id_software'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('entities_id_software', $entity->getField('entities_id'));
         echo "<font class='green'>&nbsp;&nbsp;";
         echo self::getSpecificValueToDisplay('entities_id_software', $tid);
         echo "</font>";
      }
      echo "</td><td colspan='2'></td></tr>";

      Plugin::doHook("post_item_form", ['item' => $entity, 'options' => &$options]);

      echo "</table>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";

   }


   /**
    * @since 0.84 (before in entitydata.class)
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

      Plugin::doHook("pre_item_form", ['item' => $entity, 'options' => []]);

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
      $toadd=[];
      if ($ID > 0) {
         $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
      }
      Dropdown::showNumber('delay_send_emails', ['value' => $entity->fields["delay_send_emails"],
                                                      'min'   => 0,
                                                      'max'   => 100,
                                                      'unit'  => 'minute',
                                                      'toadd' => $toadd]);

      if ($entity->fields['delay_send_emails'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('delay_send_emails', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo $entity->getValueToDisplay('delay_send_emails', $tid, ['html' => true]);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Enable notifications by default')."</td>";
      echo "<td>";

      Alert::dropdownYesNo(['name'           => "is_notif_enable_default",
                                 'value'          =>  $entity->getField('is_notif_enable_default'),
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)]);

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

      echo "<table class='tab_cadre_fixe tab_spaced'>";
      echo "<tr><th colspan='4'>".__('Alarms options')."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='2'>";
      echo _n('Cartridge', 'Cartridges', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Reminders frequency for alarms on cartridges') . "</td><td>";
      $default_value = $entity->fields['cartridges_alert_repeat'];
      Alert::dropdown(['name'           => 'cartridges_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => (($ID > 0) ? 1 : 0)]);

      if ($entity->fields['cartridges_alert_repeat'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('cartridges_alert_repeat', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('cartridges_alert_repeat', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Default threshold for cartridges count') ."</td><td>";
      if ($ID > 0) {
         $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                        self::CONFIG_NEVER => __('Never')];
      } else {
         $toadd = [self::CONFIG_NEVER => __('Never')];
      }
      Dropdown::showNumber('default_cartridges_alarm_threshold',
                            ['value' => $entity->fields["default_cartridges_alarm_threshold"],
                                  'min'   => 0,
                                  'max'   => 100,
                                  'step'  => 1,
                                  'toadd' => $toadd]);
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
      Alert::dropdown(['name'           => 'consumables_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => (($ID > 0) ? 1 : 0)]);
      if ($entity->fields['consumables_alert_repeat'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('consumables_alert_repeat', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('consumables_alert_repeat', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Default threshold for consumables count') ."</td><td>";
      if ($ID > 0) {
         $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                        self::CONFIG_NEVER => __('Never')];
      } else {
         $toadd = [self::CONFIG_NEVER => __('Never')];
      }
      Dropdown::showNumber('default_consumables_alarm_threshold',
                            ['value' => $entity->fields["default_consumables_alarm_threshold"],
                                  'min'   => 0,
                                  'max'   => 100,
                                  'step'  => 1,
                                  'toadd' => $toadd]);
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
      Alert::dropdownYesNo(['name'           => "use_contracts_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)]);
      if ($entity->fields['use_contracts_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_contracts_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_contracts_alert', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Default value') . "</td><td>";
      Contract::dropdownAlert(['name'           => "default_contract_alert",
                                    'value'          => $entity->fields["default_contract_alert"],
                                    'inherit_parent' => (($ID > 0) ? 1 : 0)]);
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
                                  ['max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')]);
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
      echo __('Financial and administrative information');
      echo "</th>";
      echo "<td>" . __('Alarms on financial and administrative information') . "</td><td>";
      $default_value = $entity->fields['use_infocoms_alert'];
      Alert::dropdownYesNo(['name'           => "use_infocoms_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)]);
      if ($entity->fields['use_infocoms_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_infocoms_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_infocoms_alert', $tid);
         echo "</font>";
      }

      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Default value')."</td><td>";
      Infocom::dropdownAlert(['name'           => 'default_infocom_alert',
                                   'value'          => $entity->fields["default_infocom_alert"],
                                   'inherit_parent' => (($ID > 0) ? 1 : 0)]);
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
                                  ['max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')]);
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
      Alert::dropdownYesNo(['name'           => "use_licenses_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => (($ID > 0) ? 1 : 0)]);
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
                                  ['max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day',
                                        'never_string'   => __('No')]);
      if ($entity->fields['send_licenses_alert_before_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('send_licenses_alert_before_delay',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('send_licenses_alert_before_delay', $tid);
         echo "</font>";
      }

      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='2' rowspan='2'>";
      echo _n('Certificate', 'Certificates', Session::getPluralNumber());
      echo "</th>";
      echo "<td>" . __('Alarms on expired certificates') . "</td><td>";
      $default_value = $entity->fields['use_certificates_alert'];
      Alert::dropdownYesNo(['name'           => "use_certificates_alert",
                            'value'          => $default_value,
                            'inherit_parent' => (($ID > 0) ? 1 : 0)]);
      if ($entity->fields['use_certificates_alert'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('use_certificates_alert', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('use_certificates_alert', $tid);
         echo "</font>";
      }
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>" . __('Send certificates alarms before')."</td><td>";
      Alert::dropdownIntegerNever('send_certificates_alert_before_delay',
                                  $entity->fields['send_certificates_alert_before_delay'],
                                  ['max'            => 99,
                                   'inherit_parent' => (($ID > 0) ? 1 : 0),
                                   'unit'           => 'day',
                                   'never_string'   => __('No')]);
      if ($entity->fields['send_certificates_alert_before_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('send_certificates_alert_before_delay',
                                    $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('send_certificates_alert_before_delay', $tid);
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
                                  ['max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'hour']);
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
                                  ['max'            => 99,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0),
                                        'unit'           => 'day']);
      if ($entity->fields['notclosed_delay'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('notclosed_delay', $entity->getField('entities_id'));
         echo "<font class='green'><br>";
         echo self::getSpecificValueToDisplay('notclosed_delay', $tid);
         echo "</font>";
      }
      echo "</td></tr>";

      Plugin::doHook("post_item_form", ['item' => $entity, 'options' => &$options]);

      echo "</table>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";
   }


   /**
    * @since 0.84 (before in entitydata.class)
    *
    * @param $field
    * @param $value must be addslashes
   **/
   private static function getEntityIDByField($field, $value) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => [$field => $value]
      ]);

      if ($count($iterator) == 1) {
         $result = $iterator->next();
         return $result['id'];
      }
      return -1;
   }


   /**
    * @since 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByDN($value) {
      return self::getEntityIDByField("ldap_dn", $value);
   }


   /**
    * @since 0.84
    *
    * @param $value
   **/
   static function getEntityIDByCompletename($value) {
      return self::getEntityIDByField("completename", $value);
   }


   /**
    * @since 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByTag($value) {
      return self::getEntityIDByField("tag", $value);
   }


   /**
    * @since 0.84 (before in entitydata.class)
    *
    * @param $value
   **/
   static function getEntityIDByDomain($value) {
      return self::getEntityIDByField("mail_domain", $value);
   }


   /**
    * @since 0.84 (before in entitydata.class)
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
    * @since 0.84 (before in entitydata.class)
    *
    * @param $entity Entity object
   **/
   static function showHelpdeskOptions(Entity $entity) {
      global $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)
          || !Session::haveRightsOr(self::$rightname,
                                    [self::READHELPDESK, self::UPDATEHELPDESK])) {
         return false;
      }
      $canedit = (Session::haveRight(self::$rightname, self::UPDATEHELPDESK)
                  && Session::haveAccessToEntity($ID));

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";

      Plugin::doHook("pre_item_form", ['item' => $entity, 'options' => []]);

      echo "<tr class='tab_bg_1'><td colspan='2'>"._n('Ticket template', 'Ticket templates', 1).
           "</td>";
      echo "<td colspan='2'>";
      $toadd = [];
      if ($ID != 0) {
         $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
      }

      $options = ['value'  => $entity->fields["tickettemplates_id"],
                       'entity' => $ID,
                       'toadd'  => $toadd];

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
      $options = ['value'      => $entity->fields["calendars_id"],
                       'emptylabel' => __('24/7')];

      if ($ID != 0) {
         $options['toadd'] = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
      }
      Calendar::dropdown($options);

      if (($entity->fields["calendars_id"] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         echo "<font class='green'>&nbsp;&nbsp;";
         $calendar = new Calendar();
         $cid = self::getUsedConfig('calendars_id', $ID, '', 0);
         if (!$cid) {
            echo __('24/7');
         } else if ($calendar->getFromDB($cid)) {
            echo $calendar->getLink();
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".__('Tickets default type')."</td>";
      echo "<td colspan='2'>";
      $toadd = [];
      if ($ID != 0) {
         $toadd = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
      }
      Ticket::dropdownType('tickettype', ['value' => $entity->fields["tickettype"],
                                               'toadd' => $toadd]);

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
                              ['value' => $entity->fields["auto_assign_mode"]]);

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
      $autoclose = [self::CONFIG_PARENT => __('Inheritance of the parent entity'),
                         self::CONFIG_NEVER  => __('Never'),
                         0                   => __('Immediatly')];
      if ($ID == 0) {
         unset($autoclose[self::CONFIG_PARENT]);
      }

      Dropdown::showNumber('autoclose_delay',
                           ['value' => $entity->fields['autoclose_delay'],
                                 'min'   => 1,
                                 'max'   => 99,
                                 'step'  => 1,
                                 'toadd' => $autoclose,
                                 'unit'  => 'day']);

      if (($entity->fields['autoclose_delay'] == self::CONFIG_PARENT)
          && ($ID != 0)) {
         $autoclose_mode = self::getUsedConfig('autoclose_delay', $entity->fields['entities_id'],
                                               '', self::CONFIG_NEVER);

         echo "<br><font class='green'>&nbsp;&nbsp;";
         if ($autoclose_mode >= 0) {
            printf(_n('%d day', '%d days', $autoclose_mode), $autoclose_mode);
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
      $typeinquest = [self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                           1                    => __('Internal survey'),
                           2                    => __('External survey')];

      // No inherit from parent for root entity
      if ($ID == 0) {
         unset($typeinquest[self::CONFIG_PARENT]);
         if ($entity->fields['inquest_config'] == self::CONFIG_PARENT) {
            $entity->fields['inquest_config'] = 1;
         }
      }
      $rand = Dropdown::showFromArray('inquest_config', $typeinquest,
                                      $options = ['value' => $entity->fields['inquest_config']]);
      echo "</td></tr>\n";

      // Do not display for root entity in inherit case
      if (($entity->fields['inquest_config'] == self::CONFIG_PARENT)
          && ($ID !=0)) {
         $inquestconfig = self::getUsedConfig('inquest_config', $entity->fields['entities_id']);
         $inquestrate   = self::getUsedConfig('inquest_config', $entity->fields['entities_id'],
                                              'inquest_rate');
         echo "<tr class='tab_bg_1'><td colspan='4' class='green center'>";

         if ($inquestrate == 0) {
            echo __('Disabled');
         } else {
            echo $typeinquest[$inquestconfig].'<br>';
            $inqconf = self::getUsedConfig('inquest_config', $entity->fields['entities_id'],
                                           'inquest_delay');

            printf(_n('%d day', '%d days', $inqconf), $inqconf);
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

      $_POST  = ['inquest_config' => $entity->fields['inquest_config'],
                      'entities_id'    => $ID];
      $params = ['inquest_config' => '__VALUE__',
                      'entities_id'    => $ID];
      echo "<div id='inquestconfig'>";
      include GLPI_ROOT.'/ajax/ticketsatisfaction.php';
      echo "</div>\n";

      echo "</td></tr>";

      Plugin::doHook("post_item_form", ['item' => $entity, 'options' => &$options]);

      echo "</table>";

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value='".$entity->fields["id"]."'>";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\"
                  class='submit'>";
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";

      Ajax::updateItemOnSelectEvent("dropdown_inquest_config$rand", "inquestconfig",
                                    $CFG_GLPI["root_doc"]."/ajax/ticketsatisfaction.php", $params);
   }


   /**
    * Retrieve data of current entity or parent entity
    *
    * @since 0.84 (before in entitydata.class)
    *
    * @param $fieldref        string   name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval        string   name of the field that we want value (default '')
    * @param $default_value            value to return (default -2)
   **/
   static function getUsedConfig($fieldref, $entities_id, $fieldval = '', $default_value = -2) {

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

      return $default_value;
   }


   /**
    * Generate link for ticket satisfaction
    *
    * @since 0.84 (before in entitydata.class)
    *
    * @param $ticket ticket object
    *
    * @return url contents
   **/
   static function generateLinkSatisfaction($ticket) {
      global $DB;

      $url = self::getUsedConfig('inquest_config', $ticket->fields['entities_id'], 'inquest_URL');

      if (strstr($url, "[TICKET_ID]")) {
         $url = str_replace("[TICKET_ID]", $ticket->fields['id'], $url);
      }

      if (strstr($url, "[TICKET_NAME]")) {
         $url = str_replace("[TICKET_NAME]", urlencode($ticket->fields['name']), $url);
      }

      if (strstr($url, "[TICKET_CREATEDATE]")) {
         $url = str_replace("[TICKET_CREATEDATE]", $ticket->fields['date'], $url);
      }

      if (strstr($url, "[TICKET_SOLVEDATE]")) {
         $url = str_replace("[TICKET_SOLVEDATE]", $ticket->fields['solvedate'], $url);
      }

      if (strstr($url, "[REQUESTTYPE_ID]")) {
         $url = str_replace("[REQUESTTYPE_ID]", $ticket->fields['requesttypes_id'], $url);
      }

      if (strstr($url, "[REQUESTTYPE_NAME]")) {
         $url = str_replace("[REQUESTTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_requesttypes',
                                                                $ticket->fields['requesttypes_id'])),
                            $url);
      }

      if (strstr($url, "[TICKET_PRIORITY]")) {
         $url = str_replace("[TICKET_PRIORITY]", $ticket->fields['priority'], $url);
      }

      if (strstr($url, "[TICKET_PRIORITYNAME]")) {
         $url = str_replace("[TICKET_PRIORITYNAME]",
               urlencode(CommonITILObject::getPriorityName($ticket->fields['priority'])),
               $url);
      }

      if (strstr($url, "[TICKETCATEGORY_ID]")) {
         $url = str_replace("[TICKETCATEGORY_ID]", $ticket->fields['itilcategories_id'], $url);
      }

      if (strstr($url, "[TICKETCATEGORY_NAME]")) {
         $url = str_replace("[TICKETCATEGORY_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_itilcategories',
                                                                $ticket->fields['itilcategories_id'])),
                            $url);
      }

      if (strstr($url, "[TICKETTYPE_ID]")) {
         $url = str_replace("[TICKETTYPE_ID]", $ticket->fields['type'], $url);
      }

      if (strstr($url, "[TICKET_TYPENAME]")) {
         $url = str_replace("[TICKET_TYPENAME]",
                            Ticket::getTicketTypeName($ticket->fields['type']), $url);
      }

      if (strstr($url, "[SOLUTIONTYPE_ID]")) {
         $url = str_replace("[SOLUTIONTYPE_ID]", $ticket->fields['solutiontypes_id'], $url);
      }

      if (strstr($url, "[SOLUTIONTYPE_NAME]")) {
         $url = str_replace("[SOLUTIONTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                $ticket->fields['solutiontypes_id'])),
                            $url);
      }

      if (strstr($url, "[SLA_TTO_ID]")) {
         $url = str_replace("[SLA_TTO_ID]", $ticket->fields['slas_id_tto'], $url);
      }

      if (strstr($url, "[SLA_TTO_NAME]")) {
         $url = str_replace("[SLA_TTO_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slas',
                                                                $ticket->fields['slas_id_tto'])),
                            $url);
      }

      if (strstr($url, "[SLA_TTR_ID]")) {
         $url = str_replace("[SLA_TTR_ID]", $ticket->fields['slas_id_ttr'], $url);
      }

      if (strstr($url, "[SLA_TTR_NAME]")) {
         $url = str_replace("[SLA_TTR_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slas',
                                                                $ticket->fields['slas_id_ttr'])),
                            $url);
      }

      if (strstr($url, "[SLALEVEL_ID]")) {
         $url = str_replace("[SLALEVEL_ID]", $ticket->fields['slalevels_id_ttr'], $url);
      }

      if (strstr($url, "[SLALEVEL_NAME]")) {
         $url = str_replace("[SLALEVEL_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slalevels',
                                                                $ticket->fields['slalevels_id_ttr'])),
                            $url);
      }

      return $url;
   }

   /**
    * get value for auto_assign_mode
    *
    * @since 0.84 (created in version 0.83 in entitydata.class)
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getAutoAssignMode($val = null) {

      $tab = [self::CONFIG_PARENT                  => __('Inheritance of the parent entity'),
                   self::CONFIG_NEVER                   => __('No'),
                   self::AUTO_ASSIGN_HARDWARE_CATEGORY  => __('Based on the item then the category'),
                   self::AUTO_ASSIGN_CATEGORY_HARDWARE  => __('Based on the category then the item')];

      if (is_null($val)) {
         return $tab;
      }
      if (isset($tab[$val])) {
         return $tab[$val];
      }
      return NOT_AVAILABLE;
   }

   /**
    * @since 0.84
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
    * @since 0.84 (before in entitydata.class)
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
         case 'use_licenses_alert' :
         case 'use_certificates_alert' :
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
         case 'send_certificates_alert_before_delay' :
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
         case 'autofill_decommission_date' :
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
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      global $DB;

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'use_licenses_alert' :
         case 'use_certificates_alert' :
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
         case 'send_certificates_alert_before_delay' :
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
            $options['toadd'] = [self::CONFIG_PARENT => __('Inheritance of the parent entity')];
            return Ticket::dropdownType($name, $options);

         case 'autofill_buy_date' :
         case 'autofill_order_date' :
         case 'autofill_delivery_date' :
         case 'autofill_use_date' :
         case 'autofill_decommission_date' :
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
            $tab = [0                           => __('No autofill'),
                         Infocom::COPY_BUY_DATE      => __('Copy the date of purchase'),
                         Infocom::COPY_ORDER_DATE    => __('Copy the order date'),
                         Infocom::COPY_DELIVERY_DATE => __('Copy the delivery date'),
                         self::CONFIG_PARENT         => __('Inheritance of the parent entity')];
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, $tab, $options);

         case 'inquest_config' :
            $typeinquest = [self::CONFIG_PARENT  => __('Inheritance of the parent entity'),
                                 1                    => __('Internal survey'),
                                 2                    => __('External survey')];
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
            $options['toadd'] = [self::CONFIG_NEVER => __('No change of entity')]; // Keep software in PC entity
            $options['toadd'][self::CONFIG_PARENT] = __('Inheritance of the parent entity');

            return self::dropdown($options);

      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      $values[self::READHELPDESK]   = ['short' => __('Read parameters'),
                                            'long'  => __('Read helpdesk parameters')];
      $values[self::UPDATEHELPDESK] = ['short' => __('Update parameters'),
                                            'long'  => __('Update helpdesk parameters')];

      return $values;
   }

}
