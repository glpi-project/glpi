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

abstract class NotificationTargetCommonITILObject extends NotificationTarget {

   public $private_profiles = [];

   public $html_tags        = [
      '##change.solution.description##',
      '##followup.description##',
      '##linkedticket.content##',
      '##problem.solution.description##',
      '##ticket.content##',
      '##ticket.description##',
      '##ticket.solution.description##'
   ];

   /**
    * @param $entity          (default '')
    * @param $event           (default '')
    * @param $object          (default null)
    * @param $options   array
   **/
   function __construct($entity = '', $event = '', $object = null, $options = []) {

      parent::__construct($entity, $event, $object, $options);

      if (isset($options['followup_id'])) {
         $this->options['sendprivate'] = $options['is_private'];
      }

      if (isset($options['task_id'])) {
         $this->options['sendprivate'] = $options['is_private'];
      }

   }


   function validateSendTo($event, array $infos, $notify_me = false) {

      // Check global ones for notification to myself
      if (!parent::validateSendTo($event, $infos, $notify_me)) {
         return false;
      }

      // Private object and no right to see private items : do not send
      if ($this->isPrivate()
          && (!isset($infos['additionnaloption']['show_private'])
              || !$infos['additionnaloption']['show_private'])) {
         return false;
      }

      return true;
   }

   /**
    * Get notification subject prefix
    *
    * @param $event Event name (default '')
    *
    * @return string
    **/
   function getSubjectPrefix($event = '') {

      $perso_tag = trim(Entity::getUsedConfig('notification_subject_tag', $this->getEntity(),
                                              '', ''));

      if (empty($perso_tag)) {
         $perso_tag = 'GLPI';
      }
      return sprintf("[$perso_tag #%07d] ", $this->obj->getField('id'));
   }

   /**
    * Get events related to Itil Object
    *
    * @since 9.2
    *
    * @return array of events (event key => event label)
   **/
   function getEvents() {

      $events = [
         'requester_user'    => __('New user in requesters'),
         'requester_group'   => __('New group in requesters'),
         'observer_user'     => __('New user in observers'),
         'observer_group'    => __('New group in observers'),
         'assign_user'       => __('New user in assignees'),
         'assign_group'      => __('New group in assignees'),
         'assign_supplier'   => __('New supplier in assignees'),
         'add_task'          => __('New task'),
         'update_task'       => __('Update of a task'),
         'delete_task'       => __('Deletion of a task'),
         'add_followup'      => __("New followup"),
         'update_followup'   => __('Update of a followup'),
         'delete_followup'   => __('Deletion of a followup'),
      ];

      asort($events);
      return $events;
   }


   /**
    * Add linked users to the notified users list
    *
    * @param integer $type type of linked users
    *
    * @return void
    */
   function addLinkedUserByType($type) {
      global $DB, $CFG_GLPI;

      $userlinktable = getTableForItemType($this->obj->userlinkclass);
      $fkfield       = $this->obj->getForeignKeyField();

      //Look for the user by his id
      $criteria = ['LEFT JOIN' => [
         User::getTable() => [
            'ON' => [
               $userlinktable    => 'users_id',
               User::getTable()  => 'id'
            ]
         ]
      ]] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
      $criteria['FROM'] = $userlinktable;
      $criteria['FIELDS'] = array_merge(
         $criteria['FIELDS'], [
            "$userlinktable.use_notification AS notif",
            "$userlinktable.alternative_email AS altemail"
         ]
      );
      $criteria['WHERE']["$userlinktable.$fkfield"] = $this->obj->fields['id'];
      $criteria['WHERE']["$userlinktable.type"] = $type;

      $iterator = $DB->request($criteria);
      while ($data = $iterator->next()) {
         //Add the user email and language in the notified users list
         if ($data['notif']) {
            $author_email = UserEmail::getDefaultForUser($data['users_id']);
            $author_lang  = $data["language"];
            $author_id    = $data['users_id'];

            if (!empty($data['altemail'])
                && ($data['altemail'] != $author_email)
                && NotificationMailing::isUserAddressValid($data['altemail'])) {
               $author_email = $data['altemail'];
            }
            if (empty($author_lang)) {
               $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
               $author_id = -1;
            }

            $user = [
               'language' => $author_lang,
               'users_id' => $author_id
            ];
            if ($this->isMailMode()) {
               $user['email'] = $author_email;
            }
            $this->addToRecipientsList($user);
         }
      }

      // Anonymous user
      $query = "SELECT `alternative_email`
                FROM `$userlinktable`
                WHERE `$userlinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$userlinktable`.`users_id` = 0
                      AND `$userlinktable`.`use_notification` = 1
                      AND `$userlinktable`.`type` = '$type'";
      foreach ($DB->request($query) as $data) {
         if ($this->isMailMode()) {
            if (NotificationMailing::isUserAddressValid($data['alternative_email'])) {
               $this->addToRecipientsList([
                  'email'    => $data['alternative_email'],
                  'language' => $CFG_GLPI["language"],
                  'users_id' => -1
               ]);
            }
         }
      }
   }


   /**
    * Add linked group to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      //Look for the user by his id
      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(0, $data['groups_id']);
      }
   }



   /**
    * Add linked group without supervisor to the notified user list
    *
    * @since 0.84.1
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupWithoutSupervisorByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(2, $data['groups_id']);
      }
   }


   /**
    * Add linked group supervisor to the notified user list
    *
    * @param integer $type type of linked groups
    *
    * @return void
    */
   function addLinkedGroupSupervisorByType($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield        = $this->obj->getForeignKeyField();

      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addForGroup(1, $data['groups_id']);
      }
   }


   /**
    * Get the email of the item's user : Overloaded manual address used
   **/
   function addItemAuthor() {
      $this->addLinkedUserByType(CommonITILActor::REQUESTER);
   }


   /**
    * Add previous technician in charge (before reassign)
    *
    * @return void
    */
   function addOldAssignTechnician() {
      global $CFG_GLPI;

      if (isset($this->options['_old_user'])
           && ($this->options['_old_user']['type'] == CommonITILActor::ASSIGN)
           && $this->options['_old_user']['use_notification']) {

         $user = new User();
         $user->getFromDB($this->options['_old_user']['users_id']);

         $author_email = UserEmail::getDefaultForUser($user->fields['id']);
         $author_lang  = $user->fields["language"];
         $author_id    = $user->fields['id'];

         if (!empty($this->options['_old_user']['alternative_email'])
             && ($this->options['_old_user']['alternative_email'] != $author_email)
             && NotificationMailing::isUserAddressValid($this->options['_old_user']['alternative_email'])) {

            $author_email = $this->options['_old_user']['alternative_email'];
         }
         if (empty($author_lang)) {
            $author_lang = $CFG_GLPI["language"];
         }
         if (empty($author_id)) {
            $author_id = -1;
         }

         $user = [
            'language' => $author_lang,
            'users_id' => $author_id
         ];
         if ($this->isMailMode()) {
            $user['email'] = $author_email;
         }
         $this->addToRecipientsList($user);
      }
   }


   /**
    * Add recipient
    *
    * @return void
    */
   function addRecipientAddress() {
      return $this->addUserByField("users_id_recipient");
   }


   /**
    * Get supplier related to the ITIL object
    *
    * @param boolean $sendprivate (false by default)
    *
    * @return void
    */
   function addSupplier($sendprivate = false) {
      global $DB;

      if (!$sendprivate
         && $this->obj->countSuppliers(CommonITILActor::ASSIGN)
         && $this->isMailMode()) {

         $supplierlinktable = getTableForItemType($this->obj->supplierlinkclass);
         $fkfield           = $this->obj->getForeignKeyField();

         $query = "SELECT DISTINCT `glpi_suppliers`.`email` AS email,
                                   `glpi_suppliers`.`name` AS name
                   FROM `$supplierlinktable`
                   LEFT JOIN `glpi_suppliers`
                     ON (`$supplierlinktable`.`suppliers_id` = `glpi_suppliers`.`id`)
                   WHERE `$supplierlinktable`.`$fkfield` = '".$this->obj->getID()."'";

         foreach ($DB->request($query) as $data) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add approver related to the ITIL object validation
    *
    * @param $options array
    *
    * @return void
    */
   function addValidationApprover($options = []) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemType($this->obj->getType().'Validation');

         $criteria = ['LEFT JOIN' => [
            User::getTable() => [
               'ON' => [
                  $validationtable  => 'users_id_validate',
                  User::getTable()  => 'id'
               ]
            ]
         ]] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
         $criteria['FROM'] = $validationtable;
         $criteria['WHERE']["$validationtable.id"] = $options['validation_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }

   /**
    * Add requester related to the ITIL object validation
    *
    * @param array $options Options
    *
    * @return void
   **/
   function addValidationRequester($options = []) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemType($this->obj->getType().'Validation');

         $criteria = ['LEFT JOIN' => [
            User::getTable() => [
               'ON' => [
                  $validationtable  => 'users_id',
                  User::getTable()  => 'id'
               ]
            ]
         ]] + $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
         $criteria['FROM'] = $validationtable;
         $criteria['WHERE']["$validationtable.id"] = $options['validation_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add author related to the followup
    *
    * @param array $options Options
    *
    * @return void
    */
   function addFollowupAuthor($options = []) {
      global $DB;

      if (isset($options['followup_id'])) {
         $followuptable = getTableForItemType($this->obj->getType().'Followup');

         $criteria = array_merge_recursive(
            ['INNER JOIN' => [
               User::getTable() => [
                  'ON' => [
                     $followuptable    => 'users_id',
                     User::getTable()  => 'id'
                  ]
               ]
            ]],
            $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
         );
         $criteria['FROM'] = $followuptable;
         $criteria['WHERE']["$followuptable.id"] = $options['followup_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add task author
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAuthor($options = []) {
      global $DB;

      // In case of delete task pass user id
      if (isset($options['task_users_id'])) {
         $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
         $criteria['FROM'] = User::getTable();
         $criteria['WHERE'][User::getTable() . '.id'] = $options['task_users_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');

         $criteria = array_merge_recursive(
            ['INNER JOIN' => [
               User::getTable() => [
                  'ON' => [
                     $tasktable        => 'users_id',
                     User::getTable()  => 'id'
                  ]
               ]
            ]],
            $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
         );
         $criteria['FROM'] = $tasktable;
         $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add user assigned to task
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAssignUser($options = []) {
      global $DB;

      // In case of delete task pass user id
      if (isset($options['task_users_id_tech'])) {
         $criteria = $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria();
         $criteria['FROM'] = User::getTable();
         $criteria['WHERE'][User::getTable() . '.id'] = $options['task_users_id_tech'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');

         $criteria = array_merge_recursive(
            ['INNER JOIN' => [
               User::getTable() => [
                  'ON' => [
                     $tasktable        => 'users_id_tech',
                     User::getTable()  => 'id'
                  ]
               ]
            ]],
            $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
         );
         $criteria['FROM'] = $tasktable;
         $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add group assigned to the task
    *
    * @since 9.1
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAssignGroup($options = []) {
      global $DB;

      // In case of delete task pass user id
      if (isset($options['task_groups_id_tech'])) {
         $this->addForGroup(0, $options['task_groups_id_tech']);

      } else if (isset($options['task_id'])) {
         $tasktable = getTableForItemType($this->obj->getType().'Task');
         foreach ($DB->request([$tasktable, 'glpi_groups'],
                               "`glpi_groups`.`id` = `$tasktable`.`groups_id_tech`
                                AND `$tasktable`.`id` = '".$options['task_id']."'") as $data) {
            $this->addForGroup(0, $data['groups_id_tech']);
         }
      }
   }


   function addAdditionnalInfosForTarget() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['profiles_id'],
         'FROM'   => 'glpi_profilerights',
         'WHERE'  => [
            'name'   => 'followup',
            'rights' => ['&', ITILFollowup::SEEPRIVATE]
         ]
      ]);

      while ($data = $iterator->next()) {
         $this->private_profiles[$data['profiles_id']] = $data['profiles_id'];
      }
   }


   function addAdditionnalUserInfo(array $data) {
      global $DB;

      if (!isset($data['users_id'])) {
         return ['show_private' => 0];
      }

      $result = $DB->request([
         'COUNT'  => 'cpt',
         'FROM'   => 'glpi_profiles_users',
         'WHERE'  => [
            'users_id'     => $data['users_id'],
            'profiles_id'  => $this->private_profiles
         ] + getEntitiesRestrictCriteria('glpi_profiles_users', 'entities_id', $this->getEntity(), true)
      ])->next();

      if ($result['cpt']) {
         return ['show_private' => 1];
      }
      return ['show_private' => 0];
   }


   public function getProfileJoinSql() {

      Toolbox::deprecated('Use getProfileJoinCriteria');

      $query = parent::getProfileJoinSql();

      if ($this->isPrivate()) {
         $query .= " INNER JOIN `glpi_profiles`
                     ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                         AND `glpi_profiles`.`interface` = 'central')
                     INNER JOIN `glpi_profilerights`
                     ON (`glpi_profiles`.`id` = `glpi_profilerights`.`profiles_id`
                         AND `glpi_profilerights`.`name` = 'followup'
                         AND `glpi_profilerights`.`rights` & ".
                            ITILFollowup::SEEPRIVATE.") ";

      }
      return $query;
   }


   public function getProfileJoinCriteria() {
      $criteria = parent::getProfileJoinCriteria();

      if ($this->isPrivate()) {
         $criteria['INNER JOIN'][Profile::getTable()] = [
            'ON' => [
               Profile::getTable()        => 'id',
               Profile_User::getTable()   => 'profiles_id'
            ]
         ];
         $criteria['INNER JOIN'][ProfileRight::getTable()] = [
            'ON' => [
               ProfileRight::getTable()   => 'profiles_id',
               Profile::getTable()        => 'id'
            ]
         ];
         $criteria['WHERE'][ProfileRight::getTable() . '.name'] = 'followup';
         $criteria['WHERE'][ProfileRight::getTable() . '.rights'] = ['&', ITILFollowup::SEEPRIVATE];
         $criteria['WHERE'][Profile::getTable() . '.interface'] = 'central';
      }
      return $criteria;
   }



   function isPrivate() {

      if (isset($this->options['sendprivate']) && ($this->options['sendprivate'] == 1)) {
         return true;
      }
      return false;
   }


   /**
    * Add additionnals targets for ITIL objects
    *
    * @param string $event specif event to get additional targets (default '')
    *
    * @return void
   **/
   function addAdditionalTargets($event = '') {

      if ($event=='update') {
         $this->addTarget(Notification::OLD_TECH_IN_CHARGE,
                          __('Former technician in charge of the ticket'));
      }

      if ($event=='satisfaction') {
         $this->addTarget(Notification::AUTHOR, __('Requester'));
         $this->addTarget(Notification::RECIPIENT, __('Writer'));
      } else if ($event!='alertnotclosed') {
         $this->addTarget(Notification::RECIPIENT, __('Writer'));
         $this->addTarget(Notification::SUPPLIER, __('Supplier'));
         $this->addTarget(Notification::SUPERVISOR_ASSIGN_GROUP,
                          __('Manager of the group in charge of the ticket'));
         $this->addTarget(Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR,
                          __("Group in charge of the ticket except manager users"));
         $this->addTarget(Notification::SUPERVISOR_REQUESTER_GROUP, __('Requester group manager'));
         $this->addTarget(Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR,
                          __("Requester group except manager users"));
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE,
                          __('Technician in charge of the hardware'));
         $this->addTarget(Notification::ITEM_TECH_GROUP_IN_CHARGE,
                          __('Group in charge of the hardware'));
         $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
         $this->addTarget(Notification::REQUESTER_GROUP, __('Requester group'));
         $this->addTarget(Notification::AUTHOR, __('Requester'));
         $this->addTarget(Notification::ITEM_USER, __('Hardware user'));
         $this->addTarget(Notification::ASSIGN_GROUP, __('Group in charge of the ticket'));
         $this->addTarget(Notification::OBSERVER_GROUP, __('Watcher group'));
         $this->addTarget(Notification::OBSERVER, __('Watcher'));
         $this->addTarget(Notification::SUPERVISOR_OBSERVER_GROUP, __('Watcher group manager'));
         $this->addTarget(Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR,
                          __("Watcher group except manager users"));
      }

      if (($event == 'validation') || ($event == 'validation_answer')) {
         $this->addTarget(Notification::VALIDATION_REQUESTER, __('Approval requester'));
         $this->addTarget(Notification::VALIDATION_APPROVER, __('Approver'));
      }

      if (($event == 'update_task') || ($event == 'add_task') || ($event == 'delete_task')) {
         $this->addTarget(Notification::TASK_ASSIGN_TECH, __('Technician in charge of the task'));
         $this->addTarget(Notification::TASK_ASSIGN_GROUP, __('Group in charge of the task'));
         $this->addTarget(Notification::TASK_AUTHOR, __('Task author'));
      }

      if (($event == 'update_followup')
          || ($event == 'add_followup')
          || ($event == 'delete_followup')) {
         $this->addTarget(Notification::FOLLOWUP_AUTHOR, __('Followup author'));
      }
   }


   /**
    * Get specifics targets for ITIL objects
    *
    * @param array $data    Data
    * @param array $options Options
    *
    * @return void
   **/
   function addSpecificTargets($data, $options) {

      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

            switch ($data['items_id']) {
               case Notification::ASSIGN_TECH :
                  $this->addLinkedUserByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the supervisor of group in charge of the ITIL object
               case Notification::SUPERVISOR_ASSIGN_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::ASSIGN);
                  break;

               //Notification to the group in charge of the ITIL object without supervisor
               case Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the user who's got the issue
               case Notification::RECIPIENT :
                  $this->addRecipientAddress();
                  break;

               //Send to the supervisor of the requester's group
               case Notification::SUPERVISOR_REQUESTER_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::REQUESTER);
                  break;

               //Send to the technician previously in charge of the ITIL object (before reassignation)
               case Notification::OLD_TECH_IN_CHARGE :
                  $this->addOldAssignTechnician();
                  break;

               //Assign to a supplier
               case Notification::SUPPLIER :
                  $this->addSupplier($this->isPrivate());
                  break;

               case Notification::REQUESTER_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::REQUESTER);
                  break;

               //Notification to the requester group without supervisor
               case Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::REQUESTER);
                  break;

               case Notification::ASSIGN_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::ASSIGN);
                  break;

               //Send to the ITIL object validation approver
               case Notification::VALIDATION_APPROVER :
                  $this->addValidationApprover($options);
                  break;

               //Send to the ITIL object validation requester
               case Notification::VALIDATION_REQUESTER :
                  $this->addValidationRequester($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::FOLLOWUP_AUTHOR :
                  $this->addFollowupAuthor($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::TASK_AUTHOR :
                  $this->addTaskAuthor($options);
                  break;

               //Send to the ITIL object followup author
               case Notification::TASK_ASSIGN_TECH :
                  $this->addTaskAssignUser($options);
                  break;

               //Send to the ITIL object task group assigned
               case Notification::TASK_ASSIGN_GROUP :
                  $this->addTaskAssignGroup($options);
                  break;

               //Notification to the ITIL object's observer group
               case Notification::OBSERVER_GROUP :
                  $this->addLinkedGroupByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the ITIL object's observer user
               case Notification::OBSERVER :
                  $this->addLinkedUserByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the supervisor of the ITIL object's observer group
               case Notification::SUPERVISOR_OBSERVER_GROUP :
                  $this->addLinkedGroupSupervisorByType(CommonITILActor::OBSERVER);
                  break;

               //Notification to the observer group without supervisor
               case Notification::OBSERVER_GROUP_WITHOUT_SUPERVISOR :
                  $this->addLinkedGroupWithoutSupervisorByType(CommonITILActor::OBSERVER);
                  break;

            }
      }
   }


   function addDataForTemplate($event, $options = []) {
      global $CFG_GLPI;

      $events    = $this->getAllEvents();
      $objettype = strtolower($this->obj->getType());

      // Get data from ITIL objects
      if ($event != 'alertnotclosed') {
         $this->data = $this->getDataForObject($this->obj, $options);

      } else {
         if (isset($options['entities_id'])
             && isset($options['items'])) {
            $entity = new Entity();
            if ($entity->getFromDB($options['entities_id'])) {
               $this->data["##$objettype.entity##"]      = $entity->getField('completename');
               $this->data["##$objettype.shortentity##"] = $entity->getField('name');
            }
            if ($item = getItemForItemtype($objettype)) {
               $objettypes = Toolbox::strtolower(getPlural($objettype));
               $items      = [];
               foreach ($options['items'] as $object) {
                  $item->getFromDB($object['id']);
                  $tmp = $this->getDataForObject($item, $options, true);
                  $this->data[$objettypes][] = $tmp;
               }
            }
         }
      }

      if (($event == 'validation')
          && isset($options['validation_status'])) {
         $this->data["##$objettype.action##"]
                     //TRANS: %s id of the approval's state
                     = sprintf(__('%1$s - %2$s'), __('Approval'),
                               TicketValidation::getStatus($options['validation_status']));
      } else {
         $this->data["##$objettype.action##"] = $events[$event];
      }

      $this->getTags();

      foreach ($this->tag_descriptions[parent::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->data[$tag])) {
            $this->data[$tag] = $values['label'];
         }
      }

   }


   /**
    * Get data from an item
    *
    * @param CommonDBTM $item    Object instance
    * @param array      $options Options
    * @param boolean    $simple  (false by default)
    *
    * @return array
   **/
   function getDataForObject(CommonDBTM $item, array $options, $simple = false) {
      global $CFG_GLPI, $DB;

      $objettype = strtolower($item->getType());

      $data["##$objettype.title##"]        = $item->getField('name');
      $data["##$objettype.content##"]      = $item->getField('content');
      $data["##$objettype.description##"]  = $item->getField('content');
      $data["##$objettype.id##"]           = sprintf("%07d", $item->getField("id"));

      $data["##$objettype.url##"]
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           $objettype."_".$item->getField("id"));

      $tab = '$1';
      $data["##$objettype.urlapprove##"]
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              $objettype."_".$item->getField("id")."_".
                                                        $item->getType().$tab);

      $entity = new Entity();
      if ($entity->getFromDB($this->getEntity())) {
         $data["##$objettype.entity##"]          = $entity->getField('completename');
         $data["##$objettype.shortentity##"]     = $entity->getField('name');
         $data["##$objettype.entity.phone##"]    = $entity->getField('phonenumber');
         $data["##$objettype.entity.fax##"]      = $entity->getField('fax');
         $data["##$objettype.entity.website##"]  = $entity->getField('website');
         $data["##$objettype.entity.email##"]    = $entity->getField('email');
         $data["##$objettype.entity.address##"]  = $entity->getField('address');
         $data["##$objettype.entity.postcode##"] = $entity->getField('postcode');
         $data["##$objettype.entity.town##"]     = $entity->getField('town');
         $data["##$objettype.entity.state##"]    = $entity->getField('state');
         $data["##$objettype.entity.country##"]  = $entity->getField('country');
      }

      $data["##$objettype.storestatus##"]  = $item->getField('status');
      $data["##$objettype.status##"]       = $item->getStatus($item->getField('status'));

      $data["##$objettype.urgency##"]      = $item->getUrgencyName($item->getField('urgency'));
      $data["##$objettype.impact##"]       = $item->getImpactName($item->getField('impact'));
      $data["##$objettype.priority##"]     = $item->getPriorityName($item->getField('priority'));
      $data["##$objettype.time##"]         = $item->getActionTime($item->getField('actiontime'));

      $data["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
      $data["##$objettype.closedate##"]    = Html::convDateTime($item->getField('closedate'));
      $data["##$objettype.solvedate##"]    = Html::convDateTime($item->getField('solvedate'));
      $data["##$objettype.duedate##"]      = Html::convDateTime($item->getField('time_to_resolve'));

      $data["##$objettype.category##"] = '';
      if ($item->getField('itilcategories_id')) {
         $data["##$objettype.category##"]
                              = Dropdown::getDropdownName('glpi_itilcategories',
                                                          $item->getField('itilcategories_id'));
      }

      $data["##$objettype.authors##"] = '';
      $data['authors']                = [];
      if ($item->countUsers(CommonITILActor::REQUESTER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
            $uid = $tmpusr['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();

               $tmp = [];
               $tmp['##author.id##']   = $uid;
               $tmp['##author.name##'] = $user_tmp->getName();

               if ($user_tmp->getField('locations_id')) {
                  $tmp['##author.location##']
                                    = Dropdown::getDropdownName('glpi_locations',
                                                                $user_tmp->getField('locations_id'));
               } else {
                  $tmp['##author.location##'] = '';
               }

               if ($user_tmp->getField('usertitles_id')) {
                  $tmp['##author.title##']
                                    = Dropdown::getDropdownName('glpi_usertitles',
                                                                $user_tmp->getField('usertitles_id'));
               } else {
                  $tmp['##author.title##'] = '';
               }

               if ($user_tmp->getField('usercategories_id')) {
                  $tmp['##author.category##']
                                    = Dropdown::getDropdownName('glpi_usercategories',
                                                                $user_tmp->getField('usercategories_id'));
               } else {
                  $tmp['##author.category##'] = '';
               }

               $tmp['##author.email##']  = $user_tmp->getDefaultEmail();
               $tmp['##author.mobile##'] = $user_tmp->getField('mobile');
               $tmp['##author.phone##']  = $user_tmp->getField('phone');
               $tmp['##author.phone2##'] = $user_tmp->getField('phone2');
               $data['authors'][]       = $tmp;
            } else {
               // Anonymous users only in xxx.authors, not in authors
               $users[] = $tmpusr['alternative_email'];
            }
         }
         $data["##$objettype.authors##"] = implode(', ', $users);
      }

      $data["##$objettype.suppliers##"] = '';
      $data['suppliers']              = [];
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmpspplier) {
            $sid      = $tmpspplier['suppliers_id'];
            $supplier = new Supplier();
            if ($sid
                && $supplier->getFromDB($sid)) {
               $suppliers[] = $supplier->getName();

               $tmp = [];
               $tmp['##supplier.id##']       = $sid;
               $tmp['##supplier.name##']     = $supplier->getName();
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.phone##']    = $supplier->getField('phonenumber');
               $tmp['##supplier.fax##']      = $supplier->getField('fax');
               $tmp['##supplier.website##']  = $supplier->getField('website');
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.address##']  = $supplier->getField('address');
               $tmp['##supplier.postcode##'] = $supplier->getField('postcode');
               $tmp['##supplier.town##']     = $supplier->getField('town');
               $tmp['##supplier.state##']    = $supplier->getField('state');
               $tmp['##supplier.country##']  = $supplier->getField('country');
               $tmp['##supplier.comments##'] = $supplier->getField('comment');

               $tmp['##supplier.type##'] = '';
               if ($supplier->getField('suppliertypes_id')) {
                  $tmp['##supplier.type##']
                     = Dropdown::getDropdownName('glpi_suppliertypes',
                                                 $supplier->getField('suppliertypes_id'));
               }

               $data['suppliers'][] = $tmp;
            }
         }
         $data["##$objettype.suppliers##"] = implode(', ', $suppliers);
      }

      $data["##$objettype.openbyuser##"] = '';
      if ($item->getField('users_id_recipient')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_recipient'));
         $data["##$objettype.openbyuser##"] = $user_tmp->getName();
      }

      $data["##$objettype.lastupdater##"] = '';
      if ($item->getField('users_id_lastupdater')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
         $data["##$objettype.lastupdater##"] = $user_tmp->getName();
      }

      $data["##$objettype.assigntousers##"] = '';
      if ($item->countUsers(CommonITILActor::ASSIGN)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($user_tmp->getFromDB($uid)) {
               $users[$uid] = $user_tmp->getName();
            }
         }
         $data["##$objettype.assigntousers##"] = implode(', ', $users);
      }

      $data["##$objettype.assigntosupplier##"] = '';
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmp) {
            $uid           = $tmp['suppliers_id'];
            $supplier_tmp  = new Supplier();
            if ($supplier_tmp->getFromDB($uid)) {
               $suppliers[$uid] = $supplier_tmp->getName();
            }
         }
         $data["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
      }

      $data["##$objettype.groups##"] = '';
      if ($item->countGroups(CommonITILActor::REQUESTER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.groups##"] = implode(', ', $groups);
      }

      $data["##$objettype.observergroups##"] = '';
      if ($item->countGroups(CommonITILActor::OBSERVER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::OBSERVER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.observergroups##"] = implode(', ', $groups);
      }

      $data["##$objettype.observerusers##"] = '';
      if ($item->countUsers(CommonITILActor::OBSERVER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();
            } else {
               $users[] = $tmp['alternative_email'];
            }
         }
         $data["##$objettype.observerusers##"] = implode(', ', $users);
      }

      $data["##$objettype.assigntogroups##"] = '';
      if ($item->countGroups(CommonITILActor::ASSIGN)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.assigntogroups##"] = implode(', ', $groups);
      }

      $data["##$objettype.solution.type##"] = '';
      $data["##$objettype.solution.description##"] = '';

      $itilsolution = new ITILSolution();
      $solution = $itilsolution->getFromDBByRequest([
         'WHERE'  => [
            'itemtype'  => $objettype,
            'items_id'  => $item->fields['id']
         ],
         'ORDER'  => 'date_creation DESC',
         'LIMIT'  => 1
      ]);

      if ($solution) {
         if ($itilsolution->getField('solutiontypes_id')) {
            $data["##$objettype.solution.type##"] = Dropdown::getDropdownName(
               'glpi_solutiontypes',
               $itilsolution->getField('solutiontypes_id')
            );
         }

         $data["##$objettype.solution.description##"] = $itilsolution->getField('content');
      }

      // Complex mode
      if (!$simple) {
         $followup_restrict = [];
         $followup_restrict['items_id'] = $item->getField('id');
         if (!isset($options['additionnaloption']['show_private'])
             || !$options['additionnaloption']['show_private']) {
            $followup_restrict['is_private'] = 0;
         }
         $followup_restrict['itemtype'] = $objettype;

         //Followup infos
         $followups          = getAllDataFromTable(
            'glpi_itilfollowups', [
               'WHERE'  => $followup_restrict,
               'ORDER'  => ['date_mod DESC', 'id ASC']
            ]
         );
         $data['followups'] = [];
         foreach ($followups as $followup) {
            $tmp                             = [];
            $tmp['##followup.isprivate##']   = Dropdown::getYesNo($followup['is_private']);
            $tmp['##followup.author##']      = Html::clean(getUserName($followup['users_id']));
            $tmp['##followup.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                         $followup['requesttypes_id']);
            $tmp['##followup.date##']        = Html::convDateTime($followup['date']);
            $tmp['##followup.description##'] = $followup['content'];

            $data['followups'][] = $tmp;
         }

         $data["##$objettype.numberoffollowups##"] = count($data['followups']);

         $data['log'] = [];
         // Use list_limit_max or load the full history ?
         foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $log) {
            $tmp                               = [];
            $tmp["##$objettype.log.date##"]    = $log['date_mod'];
            $tmp["##$objettype.log.user##"]    = $log['user_name'];
            $tmp["##$objettype.log.field##"]   = $log['field'];
            $tmp["##$objettype.log.content##"] = $log['change'];
            $data['log'][]                    = $tmp;
         }

         $data["##$objettype.numberoflogs##"] = count($data['log']);

         // Get unresolved items
         $restrict = [
            'NOT' => [
               $item->getTable() . '.status' => array_merge(
                  $item->getSolvedStatusArray(),
                  $item->getClosedStatusArray()
               )
            ]
         ];

         if ($item->maybeDeleted()) {
            $restrict[$item->getTable() . '.is_deleted'] = 0;
         }

         $data["##$objettype.numberofunresolved##"]
               = countElementsInTableForEntity($item->getTable(), $this->getEntity(), $restrict);

         // Document
         $iterator = $DB->request([
            'SELECT'    => 'glpi_documents.*',
            'FROM'      => 'glpi_documents',
            'LEFT JOIN' => [
               'glpi_documents_items'  => [
                  'ON' => [
                     'glpi_documents_items'  => 'documents_id',
                     'glpi_documents'        => 'id'
                  ]
               ]
            ],
            'WHERE'     => [
               'glpi_documents_items.itemtype'  => $item->getType(),
               'glpi_documents_items.items_id'  => $item->fields['id']
            ]
         ]);

         $data["documents"] = [];
         $addtodownloadurl   = '';
         if ($item->getType() == 'Ticket') {
            $addtodownloadurl = "%2526tickets_id=".$item->fields['id'];
         }
         while ($row = $iterator->next()) {
            $tmp                      = [];
            $tmp['##document.id##']   = $row['id'];
            $tmp['##document.name##'] = $row['name'];
            $tmp['##document.weblink##']
                                       = $row['link'];

            $tmp['##document.url##']  = $this->formatURL($options['additionnaloption']['usertype'],
                                                         "document_".$row['id']);
            $downloadurl              = "/front/document.send.php?docid=".$row['id'];

            $tmp['##document.downloadurl##']
                                       = $this->formatURL($options['additionnaloption']['usertype'],
                                                         $downloadurl.$addtodownloadurl);
            $tmp['##document.heading##']
                                       = Dropdown::getDropdownName('glpi_documentcategories',
                                                                  $row['documentcategories_id']);

            $tmp['##document.filename##']
                                       = $row['filename'];

            $data['documents'][]     = $tmp;
         }

         $data["##$objettype.urldocument##"]
                        = $this->formatURL($options['additionnaloption']['usertype'],
                                           $objettype."_".$item->getField("id").'_Document_Item$1');

         $data["##$objettype.numberofdocuments##"]
                        = count($data['documents']);

         //costs infos
         $costtype = $item->getType().'Cost';
         $costs    = $costtype::getCostsSummary($costtype, $item->getField("id"));

         $data["##$objettype.costfixed##"]    = $costs['costfixed'];
         $data["##$objettype.costmaterial##"] = $costs['costmaterial'];
         $data["##$objettype.costtime##"]     = $costs['costtime'];
         $data["##$objettype.totalcost##"]    = $costs['totalcost'];

         $costs          = getAllDataFromTable(
            getTableForItemType($costtype), [
               'WHERE'  => [$item->getForeignKeyField() => $item->getField('id')],
               'ORDER'  => ['begin_date DESC', 'id ASC']
            ]
         );
         $data['costs'] = [];
         foreach ($costs as $cost) {
            $tmp = [];
            $tmp['##cost.name##']         = $cost['name'];
            $tmp['##cost.comment##']      = $cost['comment'];
            $tmp['##cost.datebegin##']    = Html::convDate($cost['begin_date']);
            $tmp['##cost.dateend##']      = Html::convDate($cost['end_date']);
            $tmp['##cost.time##']         = $item->getActionTime($cost['actiontime']);
            $tmp['##cost.costtime##']     = Html::formatNumber($cost['cost_time']);
            $tmp['##cost.costfixed##']    = Html::formatNumber($cost['cost_fixed']);
            $tmp['##cost.costmaterial##'] = Html::formatNumber($cost['cost_material']);
            $tmp['##cost.totalcost##']    = CommonITILCost::computeTotalCost($cost['actiontime'],
                                                                             $cost['cost_time'],
                                                                             $cost['cost_fixed'],
                                                                             $cost['cost_material']);
            $tmp['##cost.budget##']       = Dropdown::getDropdownName('glpi_budgets',
                                                                      $cost['budgets_id']);
            $data['costs'][]             = $tmp;
         }
         $data["##$objettype.numberofcosts##"] = count($data['costs']);

         //Task infos
         $tasktype = $item->getType().'Task';
         $taskobj  = new $tasktype();
         $restrict = [$item->getForeignKeyField() => $item->getField('id')];
         if ($taskobj->maybePrivate()
             && (!isset($options['additionnaloption']['show_private'])
             || !$options['additionnaloption']['show_private'])) {
            $restrict['is_private'] = 0;
         }

         $tasks          = getAllDataFromTable(
            $taskobj->getTable(), [
               'WHERE'  => $restrict,
               'ORDER'  => ['date_mod DESC', 'id ASC']
            ]
         );
         $data['tasks'] = [];
         foreach ($tasks as $task) {
            $tmp                          = [];
            $tmp['##task.id##']           = $task['id'];
            if ($taskobj->maybePrivate()) {
               $tmp['##task.isprivate##'] = Dropdown::getYesNo($task['is_private']);
            }
            $tmp['##task.author##']       = Html::clean(getUserName($task['users_id']));

            $tmp_taskcatinfo = Dropdown::getDropdownName('glpi_taskcategories',
                                                         $task['taskcategories_id'], true, true, false);
            $tmp['##task.categoryid##']      = $task['taskcategories_id'];
            $tmp['##task.category##']        = $tmp_taskcatinfo['name'];
            $tmp['##task.categorycomment##'] = $tmp_taskcatinfo['comment'];

            $tmp['##task.date##']         = Html::convDateTime($task['date']);
            $tmp['##task.description##']  = $task['content'];
            $tmp['##task.time##']         = Ticket::getActionTime($task['actiontime']);
            $tmp['##task.status##']       = Planning::getState($task['state']);

            $tmp['##task.user##']         = Html::clean(getUserName($task['users_id_tech']));
            $tmp['##task.group##']
               = Html::clean(Toolbox::clean_cross_side_scripting_deep(Dropdown::getDropdownName("glpi_groups",
                                                        $task['groups_id_tech'])), true, 2, false);
            $tmp['##task.begin##']        = "";
            $tmp['##task.end##']          = "";
            if (!is_null($task['begin'])) {
               $tmp['##task.begin##']     = Html::convDateTime($task['begin']);
               $tmp['##task.end##']       = Html::convDateTime($task['end']);
            }

            $data['tasks'][]             = $tmp;
         }

         $data["##$objettype.numberoftasks##"] = count($data['tasks']);
      }
      return $data;
   }

   function getTags() {

      $itemtype  = $this->obj->getType();
      $objettype = strtolower($itemtype);

      //Locales
      $tags = [$objettype.'.id'                    => __('ID'),
                    $objettype.'.title'                 => __('Title'),
                    $objettype.'.url'                   => __('URL'),
                    $objettype.'.category'              => __('Category'),
                    $objettype.'.content'               => __('Description'),
                    $objettype.'.description'           => sprintf(__('%1$s: %2$s'), __('Ticket'),
                                                                   __('Description')),
                    $objettype.'.status'                => __('Status'),
                    $objettype.'.urgency'               => __('Urgency'),
                    $objettype.'.impact'                => __('Impact'),
                    $objettype.'.priority'              => __('Priority'),
                    $objettype.'.time'                  => __('Total duration'),
                    $objettype.'.creationdate'          => __('Opening date'),
                    $objettype.'.closedate'             => __('Closing date'),
                    $objettype.'.solvedate'             => __('Date of solving'),
                    $objettype.'.duedate'               => __('Time to resolve'),
                    $objettype.'.authors'               => _n('Requester', 'Requesters', Session::getPluralNumber()),
                    'author.id'                         => __('Requester ID'),
                    'author.name'                       => __('Requester'),
                    'author.location'                   => __('Requester location'),
                    'author.mobile'                     => __('Mobile phone'),
                    'author.phone'                      => __('Phone'),
                    'author.phone2'                     => __('Phone 2'),
                    'author.email'                      => _n('Email', 'Emails', 1),
                    'author.title'                      => _x('person', 'Title'),
                    'author.category'                   => __('Category'),
                    $objettype.'.suppliers'             => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
                    'supplier.id'                       => __('Supplier ID'),
                    'supplier.name'                     => __('Supplier'),
                    'supplier.phone'                    => __('Phone'),
                    'supplier.fax'                      => __('Fax'),
                    'supplier.website'                  => __('Website'),
                    'supplier.email'                    => __('Email'),
                    'supplier.address'                  => __('Address'),
                    'supplier.postcode'                 => __('Postal code'),
                    'supplier.town'                     => __('City'),
                    'supplier.state'                    => _x('location', 'State'),
                    'supplier.country'                  => __('Country'),
                    'supplier.comments'                 => _n('Comment', 'Comments', 2),
                    'supplier.type'                     => __('Third party type'),
                    $objettype.'.openbyuser'            => __('Writer'),
                    $objettype.'.lastupdater'           => __('Last updater'),
                    $objettype.'.assigntousers'         => __('Assigned to technicians'),
                    $objettype.'.assigntosupplier'      => __('Assigned to a supplier'),
                    $objettype.'.groups'                => _n('Requester group',
                                                              'Requester groups', Session::getPluralNumber()),
                    $objettype.'.observergroups'        => _n('Watcher group', 'Watcher groups', Session::getPluralNumber()),
                    $objettype.'.assigntogroups'        => __('Assigned to groups'),
                    $objettype.'.solution.type'         => __('Solution type'),
                    $objettype.'.solution.description'  => _n('Solution', 'Solutions', 1),
                    $objettype.'.observerusers'         => _n('Watcher', 'Watchers', Session::getPluralNumber()),
                    $objettype.'.action'                => _n('Event', 'Events', 1),
                    'followup.date'                     => __('Opening date'),
                    'followup.isprivate'                => __('Private'),
                    'followup.author'                   => __('Writer'),
                    'followup.description'              => __('Description'),
                    'followup.requesttype'              => __('Request source'),
                    $objettype.'.numberoffollowups'     => _x('quantity', 'Number of followups'),
                    $objettype.'.numberofunresolved'    => __('Number of unresolved items'),
                    $objettype.'.numberofdocuments'     => _x('quantity', 'Number of documents'),
                    $objettype.'.costtime'              => __('Time cost'),
                    $objettype.'.costfixed'             => __('Fixed cost'),
                    $objettype.'.costmaterial'          => __('Material cost'),
                    $objettype.'.totalcost'             => __('Total cost'),
                    $objettype.'.numberofcosts'         => __('Number of costs'),
                    'cost.name'                         => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Name')),
                    'cost.comment'                      => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Comments')),
                    'cost.datebegin'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Begin date')),
                    'cost.dateend'                      => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('End date')),
                    'cost.time'                         => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Duration')),
                    'cost.costtime'                     => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Time cost')),
                    'cost.costfixed'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Fixed cost')),
                    'cost.costmaterial'                 => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Material cost')),
                    'cost.totalcost'                    => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Total cost')),
                    'cost.budget'                       => sprintf(__('%1$s: %2$s'), __('Cost'),
                                                                   __('Budget')),
                    'task.author'                       => __('Writer'),
                    'task.isprivate'                    => __('Private'),
                    'task.date'                         => __('Opening date'),
                    'task.description'                  => __('Description'),
                    'task.categoryid'                   => __('Category id'),
                    'task.category'                     => __('Category'),
                    'task.categorycomment'              => __('Category comment'),
                    'task.time'                         => __('Total duration'),
                    'task.user'                         => __('User assigned to task'),
                    'task.group'                        => __('Group assigned to task'),
                    'task.begin'                        => __('Start date'),
                    'task.end'                          => __('End date'),
                    'task.status'                       => __('Status'),
                    $objettype.'.numberoftasks'         => _x('quantity', 'Number of tasks'),
                    $objettype.'.entity.phone'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Phone')),
                    $objettype.'.entity.fax'            => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Fax')),
                    $objettype.'.entity.website'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Website')),
                    $objettype.'.entity.email'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Email')),
                    $objettype.'.entity.address'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Address')),
                    $objettype.'.entity.postcode'       => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Postal code')),
                    $objettype.'.entity.town'           => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('City')),
                    $objettype.'.entity.state'          => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), _x('location', 'State')),
                    $objettype.'.entity.country'        => sprintf(__('%1$s (%2$s)'),
                                                                   __('Entity'), __('Country')),
                   ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => parent::TAG_FOR_ALL_EVENTS]);
      }

      //Foreach global tags
      $tags = ['log'       => __('Historical'),
                    'followups' => _n('Followup', 'Followups', Session::getPluralNumber()),
                    'tasks'     => _n('Task', 'Tasks', Session::getPluralNumber()),
                    'costs'     => _n('Cost', 'Costs', Session::getPluralNumber()),
                    'authors'   => _n('Requester', 'Requesters', Session::getPluralNumber()),
                    'suppliers' => _n('Supplier', 'Suppliers', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true]);
      }

      //Tags with just lang
      $tags = [$objettype.'.days'               => _n('Day', 'Days', Session::getPluralNumber()),
                    $objettype.'.attribution'        => __('Assigned to'),
                    $objettype.'.entity'             => __('Entity'),
                    $objettype.'.nocategoryassigned' => __('No defined category'),
                    $objettype.'.log'                => __('Historical'),
                    $objettype.'.tasks'              => _n('Task', 'Tasks', Session::getPluralNumber()),
                    $objettype.'.costs'              => _n('Cost', 'Costs', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true]);
      }

      //Tags without lang
      $tags = [$objettype.'.urlapprove'     => __('Web link to approval the solution'),
                    $objettype.'.entity'         => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Complete name')),
                    $objettype.'.shortentity'    => sprintf(__('%1$s (%2$s)'),
                                                            __('Entity'), __('Name')),
                    $objettype.'.numberoflogs'   => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            _x('quantity', 'Number of items')),
                    $objettype.'.log.date'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('Date')),
                    $objettype.'.log.user'       => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('User')),
                    $objettype.'.log.field'      => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            __('Field')),
                    $objettype.'.log.content'    => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                            _x('name', 'Update')),
                    'document.url'               => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('URL')),
                    'document.downloadurl'       => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Download URL')),
                    'document.heading'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Heading')),
                    'document.id'                => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('ID')),
                    'document.filename'          => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('File')),
                    'document.weblink'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Web link')),
                    'document.name'              => sprintf(__('%1$s: %2$s'), __('Document'),
                                                            __('Name')),
                     $objettype.'.urldocument'   => sprintf(__('%1$s: %2$s'),
                                                            _n('Document', 'Documents', Session::getPluralNumber()),
                                                            __('URL'))];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false]);
      }

      //Tickets with a fixed set of values
      $status         = $this->obj->getAllStatusArray(false);
      $allowed_ticket = [];
      foreach ($status as $key => $value) {
         $allowed_ticket[] = $key;
      }

      $tags = [$objettype.'.storestatus' => ['text'     => __('Status value in database'),
                                                       'allowed_values'
                                                                  => $allowed_ticket]];
      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']]);
      }
   }
}
