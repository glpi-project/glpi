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
 * Notification Class
**/
class Notification extends CommonDBTM {

   // MAILING TYPE
   //Notification to a user (sse mailing users type below)
   const USER_TYPE             = 1;
   //Notification to users of a profile
   const PROFILE_TYPE          = 2;
   //Notification to users of a group
   const GROUP_TYPE            = 3;
   //Notification to the people in charge of the database synchronisation
   const MAILING_TYPE          = 4;
   //Notification to the supervisor of a group
   const SUPERVISOR_GROUP_TYPE = 5;
   //Notification to all users of a group except supervisor
   const GROUP_WITHOUT_SUPERVISOR_TYPE = 6;

   // MAILING USERS TYPE

   //Notification to the GLPI global administrator
   const GLOBAL_ADMINISTRATOR                = 1;
   //Notification to the technicial who's assign to a ticket
   const ASSIGN_TECH                         = 2;
   //Notification to the owner of the item
   const AUTHOR                              = 3;
   //Notification to the technician previously in charge of the ticket
   const OLD_TECH_IN_CHARGE                  = 4;
   //Notification to the technician in charge of the item
   const ITEM_TECH_IN_CHARGE                 = 5;
   //Notification to the item's user
   const ITEM_USER                           = 6;
   //Notification to the ticket's recipient
   const RECIPIENT                           = 7;
   //Notificartion to the ticket's assigned supplier
   const SUPPLIER                            = 8;
   //Notification to the ticket's assigned group
   const ASSIGN_GROUP                        = 9;
   //Notification to the supervisor of the ticket's assigned group
   const SUPERVISOR_ASSIGN_GROUP             = 10;
   //Notification to the entity administrator
   const ENTITY_ADMINISTRATOR                = 11;
   //Notification to the supervisor of the ticket's requester group
   const SUPERVISOR_REQUESTER_GROUP          = 12;
   //Notification to the ticket's requester group
   const REQUESTER_GROUP                     = 13;
   //Notification to the ticket's validation approver
   const VALIDATION_APPROVER                 = 14;
   //Notification to the ticket's validation requester
   const VALIDATION_REQUESTER                = 15;
   //Notification to the task assigned user
   const TASK_ASSIGN_TECH                    = 16;
   //Notification to the task author
   const TASK_AUTHOR                         = 17;
   //Notification to the followup author
   const FOLLOWUP_AUTHOR                     = 18;
   //Notification to the user
   const USER                                = 19;
   //Notification to the ticket's observer group
   const OBSERVER_GROUP                      = 20;
   //Notification to the ticket's observer user
   const OBSERVER                            = 21;
   //Notification to the supervisor of the ticket's observer group
   const SUPERVISOR_OBSERVER_GROUP           = 22;
   //Notification to the group of technicians in charge of the item
   const ITEM_TECH_GROUP_IN_CHARGE           = 23;
   // Notification to the ticket's assigned group without supervisor
   const ASSIGN_GROUP_WITHOUT_SUPERVISOR     = 24;
   //Notification to the ticket's requester group without supervisor
   const REQUESTER_GROUP_WITHOUT_SUPERVISOR  = 25;
   //Notification to the ticket's observer group without supervisor
   const OBSERVER_GROUP_WITHOUT_SUPERVISOR   = 26;
   // Notification to manager users
   const MANAGER_USER                        = 27;
   // Notification to manager groups
   const MANAGER_GROUP                       = 28;
   // Notification to supervisor of manager group
   const MANAGER_GROUP_SUPERVISOR            = 29;
   // Notification to manager group without supervisor
   const MANAGER_GROUP_WITHOUT_SUPERVISOR    = 30;
   // Notification to team users
   const TEAM_USER                           = 31;
   // Notification to team groups
   const TEAM_GROUP                          = 32;
   // Notification to supervisor of team groups
   const TEAM_GROUP_SUPERVISOR               = 33;
   // Notification to team groups without supervisor
   const TEAM_GROUP_WITHOUT_SUPERVISOR       = 34;
   // Notification to team contacts
   const TEAM_CONTACT                        = 35;
   // Notification to team suppliers
   const TEAM_SUPPLIER                       = 36;
   //Notification to the task assigned group
   const TASK_ASSIGN_GROUP                   = 37;

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'notification';



   static function getTypeName($nb = 0) {
      return _n('Notification', 'Notifications', $nb);
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = [];

      if (Notification::canView()
          || Config::canView()) {
         $menu['title']                                      = _n('Notification', 'Notifications', Session::getPluralNumber());
         $menu['page']                                       = '/front/setup.notification.php';
         $menu['options']['notification']['title']           = _n('Notification', 'Notifications', Session::getPluralNumber());
         $menu['options']['notification']['page']            = Notification::getSearchURL(false);
         $menu['options']['notification']['links']['add']    = Notification::getFormURL(false);
         $menu['options']['notification']['links']['search'] = Notification::getSearchURL(false);

         $menu['options']['notificationtemplate']['title']
                        = _n('Notification template', 'Notification templates', Session::getPluralNumber());
         $menu['options']['notificationtemplate']['page']
                        = NotificationTemplate::getSearchURL(false);
         $menu['options']['notificationtemplate']['links']['add']
                        = NotificationTemplate::getFormURL(false);
         $menu['options']['notificationtemplate']['links']['search']
                        = NotificationTemplate::getSearchURL(false);

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Notification_NotificationTemplate', $ong, $options);
      $this->addStandardTab('NotificationTarget', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td rowspan='4' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='4'><textarea cols='45' rows='9' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Active') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Type') . "</td>";
      echo "<td>";
      if (!Session::haveRight(static::$rightname, UPDATE)) {
         $itemtype = $this->fields['itemtype'];
         echo $itemtype::getTypeName(1);
         $rand ='';
      } else if (Config::canUpdate()
          && ($this->getEntityID() == 0)) {
         $rand = Dropdown::showItemTypes('itemtype', $CFG_GLPI["notificationtemplates_types"],
                                          ['value' => $this->fields['itemtype']]);
      } else {
         $rand = Dropdown::showItemTypes('itemtype',
                                         array_diff($CFG_GLPI["notificationtemplates_types"],
                                                    ['Crontask', 'DBConnection', 'User']),
                                         ['value' => $this->fields['itemtype']]);
      }

      $params = ['itemtype' => '__VALUE__'];
      Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_events",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownNotificationEvent.php",
                                    $params);
      Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_templates",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownNotificationTemplate.php",
                                    $params);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . NotificationEvent::getTypeName(1) . "</td>";
      echo "<td><span id='show_events'>";
      NotificationEvent::dropdownEvents($this->fields['itemtype'],
                                        ['value'=>$this->fields['event']]);
      echo "</span></td></tr>";

      $this->showFormButtons($options);
      return true;
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
         case 'event':
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               return NotificationEvent::getEventName($values['itemtype'], $values[$field]);
            }
            break;
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
         case 'event' :
            if (isset($values['itemtype'])
                && !empty($values['itemtype'])) {
               $options['value'] = $values[$field];
               $options['name']  = $name;
               return NotificationEvent::dropdownEvents($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
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
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'event',
         'name'               => _n('Event', 'Events', 1),
         'massiveaction'      => false,
         'datatype'           => 'specific',
         'additionalfields'   => [
            '0'                  => 'itemtype'
         ]
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => Notification_NotificationTemplate::getTable(),
         'field'              => 'mode',
         'name'               => __('Notification method'),
         'massiveaction'      => false,
         'searchequalsonfield'=> true,
         'datatype'           => 'specific',
         'joinparams'         => [
            'jointype'  => 'child'
         ],
         'searchtype'         => [
            '0'                  => 'equals',
            '1'                  => 'notequals'
         ]
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_notificationtemplates',
         'field'              => 'name',
         'name'               => _n('Notification template', 'Notification templates', Session::getPluralNumber()),
         'datatype'           => 'itemlink',
         'forcegroupby'       => true,
         'joinparams'         => [
            'beforejoin'  => [
               'table'        => Notification_NotificationTemplate::getTable(),
               'joinparams'   => [
                  'jointype'  => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Type'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'notificationtemplates_types',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   function canViewItem() {

      if ((($this->fields['itemtype'] == 'Crontask')
           || ($this->fields['itemtype'] == 'DBConnection'))
          && !Config::canView()) {
          return false;
      }
      return Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive());
   }


   /**
    * Is the current user have right to update the current notification ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if ((($this->fields['itemtype'] == 'Crontask')
           || ($this->fields['itemtype'] == 'DBConnection'))
          && !Config::canUpdate()) {
          return false;
      }
      return Session::haveAccessToEntity($this->getEntityID());
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Notification_NotificationTemplate::class,
            NotificationTarget::class,
         ]
      );
   }


   /**
    * Send notification
    *
    * @param array $options Options
    *
    * @return void
   **/
   static function send($options) {
      $classname = Notification_NotificationTemplate::getModeClass($options['mode']);
      $notif = new $classname();
      $notif->sendNotification($options);
   }


   /**
    * Get the mailing signature for the entity
    *
    * @param $entity
   **/
   static function getMailingSignature($entity) {
      global $DB, $CFG_GLPI;

      foreach ($DB->request('glpi_entities', ['id' => $entity]) as $data) {
         if (!empty($data['mailing_signature'])) {
            return $data['mailing_signature'];
         }
      }
      return $CFG_GLPI['mailing_signature'];
   }


   /**
    * @param string $event    Event name
    * @param string $itemtype Item type
    * @param int    $entity   Restrict to entity
    *
    * @return ResultSet
   **/
   static function getNotificationsByEventAndType($event, $itemtype, $entity) {
      global $DB, $CFG_GLPI;

      $query = "SELECT `glpi_notifications`.*,
                  `glpi_notifications_notificationtemplates`.`mode`,
                  `glpi_notifications_notificationtemplates`.`notificationtemplates_id`
                FROM `glpi_notifications`
                LEFT JOIN `glpi_entities`
                  ON (`glpi_entities`.`id` = `glpi_notifications`.`entities_id`)
                LEFT JOIN `glpi_notifications_notificationtemplates`
                  ON (`glpi_notifications`.`id`=`glpi_notifications_notificationtemplates`.`notifications_id`)
                WHERE `glpi_notifications`.`itemtype` = '$itemtype'
                      AND `glpi_notifications`.`event` = '$event' ".
                      getEntitiesRestrictRequest("AND", "glpi_notifications", 'entities_id',
                                                 $entity, true) ."
                      AND `glpi_notifications`.`is_active`='1'";

      $modes = Notification_NotificationTemplate::getModes();
      $restrict_modes = null;
      foreach ($modes as $mode => $conf) {
         $count = 0;
         if ($CFG_GLPI['notifications_' . $mode]) {
            if ($restrict_modes === null) {
               $restrict_modes = ' AND (';
            } else {
               $restrict_modes .= ' OR ';
            }
            $restrict_modes .= "`glpi_notifications_notificationtemplates`.`mode` = '$mode'";
         }
      }
      if ($restrict_modes !== null) {
         $restrict_modes .= ')';
         $query .= $restrict_modes;
      }

      $query .= " ORDER BY `glpi_entities`.`level` DESC";

      return $DB->request($query);
   }


   /**
    * @since 0.90.4
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (isset($input["itemtype"]) && empty($input["itemtype"])) {
         $message = __('Field itemtype is mandatory');
         Session::addMessageAfterRedirect($message, false, ERROR);
         return false;
      }

      return $input;
   }


   /**
    * @since 0.90.4
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if (isset($input["itemtype"]) && empty($input["itemtype"])) {
         $message = __('Field itemtype is mandatory');
         Session::addMessageAfterRedirect($message, false, ERROR);
         return false;
      }

      return $input;
   }
}
