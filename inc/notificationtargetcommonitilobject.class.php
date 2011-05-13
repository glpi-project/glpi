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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

abstract class NotificationTargetCommonITILObject extends NotificationTarget {

   function __construct($entity='', $event='', $object=null, $options=array()) {
      parent::__construct($entity, $event, $object, $options);

      // For compatibility
      $this->options['sendprivate'] = true;
   }

   /**
    * Add linked users to the notified users list
    *
    * @param $type type of linked users
   **/
   function getLinkedUserByType ($type) {
      global $DB,$CFG_GLPI;

      $userlinktable = getTableForItemType($this->obj->userlinkclass);
      $fkfield = $this->obj->getForeignKeyField();

      //Look for the user by his id
      $query =        $this->getDistinctUserSql().",
                      `$userlinktable`.`use_notification` AS notif,
                      `$userlinktable`.`alternative_email` AS altemail
               FROM `$userlinktable`
               LEFT JOIN `glpi_users` ON (`$userlinktable`.`users_id` = `glpi_users`.`id`)".
               $this->getProfileJoinSql()."
               WHERE `$userlinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                     AND `$userlinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the user email and language in the notified users list
         if ($data['notif']) {
            $author_email = $data['email'];
            $author_lang  = $data["language"];
            $author_id    = $data['id'];

            if (!empty($data['altemail'])
                && $data['altemail'] != $author_email
                && NotificationMail::isUserAddressValid($data['altemail'])) {
               $author_email = $data['altemail'];
            }
            if (empty($author_lang)) {
               $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
               $author_id = -1;
            }
            $this->addToAddressesList(array('email'    => $author_email,
                                            'language' => $author_lang,
                                            'id'       => $author_id));
         }
      }
   }

   /**
    * Add linked group to the notified user list
    *
    * @param $type type of linked groups
   **/
   function getLinkedGroupByType ($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield = $this->obj->getForreignKeyField();

      //Look for the user by his id
      $query = "SELECT `groups_id`
                FROM `$grouplinktable`
                WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                      AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->getUsersAddressesByGroup($data['groups_id']);
      }
   }


   /**
    * Add linked group supervisor to the notified user list
    *
    * @param $type type of linked groups
   **/
   function getLinkedGroupSupervisorByType ($type) {
      global $DB;

      $grouplinktable = getTableForItemType($this->obj->grouplinkclass);
      $fkfield = $this->obj->getForreignKeyField();

      //Look for the user by his id
      $query =        $this->getDistinctUserSql()."
               FROM `$grouplinktable`
               INNER JOIN `glpi_groups` ON (`$grouplinktable`.`groups_id` = `glpi_groups`.`id`)
               INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)".
               $this->getProfileJoinSql()."
               WHERE `$grouplinktable`.`$fkfield` = '".$this->obj->fields["id"]."'
                     AND `$grouplinktable`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addToAddressesList($data);
      }
   }

   /**
    * Get the email of the item's user : Overloaded manual address used
   **/
   function getItemAuthorAddress() {
      global $CFG_GLPI;

      $this->getLinkedUserByType(CommonITILObject::REQUESTER);
   }

    function getOldAssignTechnicianAddress () {
      global $CFG_GLPI;

       if (isset($this->options['_old_user'])
           && $this->options['_old_user']['type'] == CommonITILObject::ASSIGN
           && $this->options['_old_user']['use_notification']) {

            $user = new User();
            $user->getFromDB($this->options['_old_user']['users_id']);

            $author_email = $user->fields['email'];
            $author_lang  = $user->fields["language"];
            $author_id    = $user->fields['id'];

            if (!empty($this->options['_old_user']['alternative_email'])
                && $this->options['_old_user']['alternative_email'] != $author_email
                && NotificationMail::isUserAddressValid($this->options['_old_user']['alternative_email'])) {
               $author_email = $this->options['_old_user']['alternative_email'];
            }
            if (empty($author_lang)) {
               $author_lang = $CFG_GLPI["language"];
            }
            if (empty($author_id)) {
               $author_id = -1;
            }
            $this->addToAddressesList(array('email'    => $author_email,
                                            'language' => $author_lang,
                                            'id'       => $author_id));


      }


    }


   //Get receipient
   function getRecipientAddress() {
      return $this->getUserByField ("users_id_recipient");
  }



   /**
    * Get supplier related to the ticket
   **/
   function getSupplierAddress($sendprivate=false) {
      global $DB;

      if (!$sendprivate
          && isset($this->obj->fields["suppliers_id_assign"])
          && $this->obj->fields["suppliers_id_assign"]>0) {

         $query = "SELECT DISTINCT `glpi_suppliers`.`email` AS email
                   FROM `glpi_suppliers`
                   WHERE `glpi_suppliers`.`id` = '".$this->obj->fields["suppliers_id_assign"]."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }

   /**
    * Get approuver related to the ticket validation
   **/
   function getValidationApproverAddress($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemtype($this->obj->getType.'Validation');


         $query = $this->getDistinctUserSql()."
                  FROM `$validationtable`
                  LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$validationtable`.`users_id_validate`)".
                  $this->getProfileJoinSql()."
                  WHERE `$validationtable`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get requester related to the ticket validation
   **/
   function getValidationRequesterAddress($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $validationtable = getTableForItemtype($this->obj->getType.'Validation');

         $query = $this->getDistinctUserSql()."
                  FROM `$validationtable`
                  LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$validationtable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$validationtable`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getFollowupAuthor($options=array()) {
      global $DB;

      if (isset($options['followup_id'])) {
         $followuptable = getTableForItemtype($this->obj->getType.'Followup');

         $query = $this->getDistinctUserSql()."
                  FROM `$followuptable`
                  INNER JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `$followuptable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$followuptable`.`id` = '".$options['followup_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getTaskAuthor($options=array()) {
      global $DB;

      if (isset($options['task_id'])) {
         $tasktable = getTableForItemtype($this->obj->getType().'Task');

         $query = $this->getDistinctUserSql()."
                  FROM `$tasktable`
                  INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `$tasktable`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$tasktable`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getTaskAssignUser($options=array()) {
      global $DB;

      if (isset($options['task_id'])) {
         $tasktable = getTableForItemtype($this->obj->getType.'Task');

         /// TODO : Review it when task / planning management have been updated

         $query = $this->getDistinctUserSql()."
                  FROM `$tasktable`
                  INNER JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `glpi_tickettasks`.`users_id`)".
                  $this->getProfileJoinSql()."
                  WHERE `$tasktable`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }

   /**
    * Get additionnals targets for Tickets
   **/
   function getAdditionalTargets($event='') {
      global $LANG;

      if ($event=='update') {
         $this->addTarget(Notification::OLD_TECH_IN_CHARGE, $LANG['setup'][236]);
      }

      if ($event=='satisfaction') {
         $this->addTarget(Notification::AUTHOR, $LANG['job'][4]);
         $this->addTarget(Notification::RECIPIENT, $LANG['common'][37]);

      } else if ($event!='alertnotclosed') {
         $this->addTarget(Notification::RECIPIENT, $LANG['common'][37]);
         $this->addTarget(Notification::SUPPLIER, $LANG['financial'][26]);
         $this->addTarget(Notification::SUPERVISOR_ASSIGN_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][248]);
         $this->addTarget(Notification::SUPERVISOR_REQUESTER_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][249]);
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE, $LANG['common'][10]);
         $this->addTarget(Notification::ASSIGN_TECH, $LANG['setup'][239]);
         $this->addTarget(Notification::REQUESTER_GROUP, $LANG['setup'][249]);
         $this->addTarget(Notification::AUTHOR, $LANG['job'][4]);
         $this->addTarget(Notification::ITEM_USER, $LANG['mailing'][137]);
         $this->addTarget(Notification::ASSIGN_GROUP, $LANG['setup'][248]);
         $this->addTarget(Notification::OBSERVER_GROUP, $LANG['setup'][251]);
         $this->addTarget(Notification::OBSERVER, $LANG['common'][104]);
         $this->addTarget(Notification::SUPERVISOR_OBSERVER_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][251]);
      }

      if ($event=='validation') {
         $this->addTarget(Notification::VALIDATION_APPROVER,
                          $LANG['validation'][0].' - '.$LANG['validation'][21]);
         $this->addTarget(Notification::VALIDATION_REQUESTER,
                          $LANG['validation'][0].' - '.$LANG['validation'][18]);
      }
      if ($event=='update_task' || $event=='add_task' || $event=='delete_task') {
         $this->addTarget(Notification::TASK_ASSIGN_TECH,
                          $LANG['job'][7]." - ".$LANG['job'][6]);
         $this->addTarget(Notification::TASK_AUTHOR,
                          $LANG['job'][7]." - ".$LANG['common'][37]);
      }
      if ($event=='update_followup' || $event=='add_followup' || $event=='delete_followup') {
         $this->addTarget(Notification::FOLLOWUP_AUTHOR,
                          $LANG['job'][9]." - ".$LANG['common'][37]);
      }
   }


   function getSpecificTargets($data, $options) {
      /// TODO clean constant names
      //Look for all targets whose type is Notification::ITEM_USER
      switch ($data['type']) {
         case Notification::USER_TYPE :

         switch ($data['items_id']) {
            case Notification::ASSIGN_TECH :
               $this->getLinkedUserByType(CommonITILObject::ASSIGN);
               break;

            //Send to the group in charge of the ticket supervisor
            case Notification::SUPERVISOR_ASSIGN_GROUP :
               $this->getLinkedGroupSupervisorByType(CommonITILObject::ASSIGN);
               break;

            //Send to the user who's got the issue
            case Notification::RECIPIENT :
               $this->getRecipientAddress();
               break;

            //Send to the supervisor of the requester's group
            case Notification::SUPERVISOR_REQUESTER_GROUP :
               $this->getLinkedGroupSupervisorByType(CommonITILObject::REQUESTER);
               break;

            //Send to the technician previously in charge of the ticket (before reassignation)
            case Notification::OLD_TECH_IN_CHARGE :
               $this->getOldAssignTechnicianAddress();
               break;

            //Assign to a supplier
            case Notification::SUPPLIER :
               $this->getSupplierAddress($this->options['sendprivate']);
               break;

            case Notification::REQUESTER_GROUP :
               $this->getLinkedGroupByType(CommonITILObject::REQUESTER);
               break;

            case Notification::ASSIGN_GROUP :
               $this->getLinkedGroupByType(CommonITILObject::ASSIGN);
               break;

            //Send to the ticket validation approver
            case Notification::VALIDATION_APPROVER :
               $this->getValidationApproverAddress($options);
               break;

            //Send to the ticket validation requester
            case Notification::VALIDATION_REQUESTER :
               $this->getValidationRequesterAddress($options);
               break;

            //Send to the ticket followup author
            case Notification::FOLLOWUP_AUTHOR :
               $this->getFollowupAuthor($options);
               break;

            //Send to the ticket followup author
            case Notification::TASK_AUTHOR :
               $this->getTaskAuthor($options);
               break;

            //Send to the ticket followup author
            case Notification::TASK_ASSIGN_TECH :
               $this->getTaskAssignUser($options);
               break;

            //Notification to the ticket's observer group
            case Notification::OBSERVER_GROUP :
               $this->getLinkedGroupByType(CommonITILObject::OBSERVER);
               break;

            //Notification to the ticket's observer user
            case Notification::OBSERVER :
               $this->getLinkedUserByType(CommonITILObject::OBSERVER);
               break;

            //Notification to the supervisor of the ticket's observer group
            case Notification::SUPERVISOR_OBSERVER_GROUP :
               $this->getLinkedGroupSupervisorByType(CommonITILObject::OBSERVER);
               break;

         }
      }
   }

   /**
    * Get all data needed for template processing
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $LANG, $CFG_GLPI;

      $events = $this->getAllEvents();
      $objettype = strtolower($this->obj->getType());



      // Get datas from itil objects
      if ($event != 'alertnotclosed') {
         $this->datas = $this->getDatasForObject($this->obj);
      } else {
         if (isset($options['entities_id']) && isset($options['items'])) {
            $this->datas["##$objettype.entity##"] = Dropdown::getDropdownName('glpi_entities',
                                                                              $options['entities_id']);
            $item = new $this->obj->getType();
            $objettypes = getPlural($objettype);
            $items = array();
            foreach ($options['items'] as $object) {
               $item->getFromDB($object['id']);
               $tmp = $this->getDatasForObject($item, true);
               $this->datas[$objettypes][] = $tmp;
            }
         }
      }

      if ($event == 'validation' && isset($options['validation_status'])) {
         $this->datas["##$objettype.action##"]
                     = $LANG['validation'][0].' - '.
                        TicketValidation::getStatus($options['validation_status']);
      } else {
         $this->datas["##$objettype.action##"] = $events[$event];
      }

      $this->getTags();

      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }

   }


   function getDatasForObject(CommonDBTM $item, $simple) {
      global $CFG_GLPI, $LANG;

      $objettype = strtolower($item->getType());

      $datas["##$objettype.title##"] = $this->obj->getField('name');
      $datas["##$objettype.content##"] = $this->obj->getField('content');
      $datas["##$objettype.description##"] = $this->obj->getField('content');

      $datas["##$objettype.id##"]              = sprintf("%07d",$this->obj->getField("id"));
      $datas["##$objettype.url##"]             = urldecode($CFG_GLPI["url_base"].
                                                               "/index.php?redirect=".$objettype."_".
                                                               $this->obj->getField("id"));

      $datas["##$objettype.urlapprove##"]      = urldecode($CFG_GLPI["url_base"].
                                                               "/index.php?redirect=".$objettype."_".
                                                               $this->obj->getField("id")."_4");


      $datas["##$objettype.entity##"]          = Dropdown::getDropdownName('glpi_entities',
                                                                              $this->getEntity());

      $datas["##$objettype.storestatus##"] = $this->obj->getField('status');
      $datas["##$objettype.status##"]      = Ticket::getStatus($this->obj->getField('status'));

      $datas["##$objettype.urgency##"]
                  = CommonITILObject::getUrgencyName($this->obj->getField('urgency'));
      $datas["##$objettype.impact##"]   = CommonITILObject::getImpactName($this->obj->getField('impact'));
      $datas["##$objettype.priority##"]
                  = CommonITILObject::getPriorityName($this->obj->getField('priority'));
      $datas["##$objettype.time##"]
                  = CommonITILObject::getActionTime($this->obj->getField('actiontime'));

      $datas["##$objettype.creationdate##"] = convDateTime($this->obj->getField('date'));
      $datas["##$objettype.closedate##"]    = convDateTime($this->obj->getField('closedate'));
      $datas["##$objettype.solvedate##"]    = convDateTime($this->obj->getField('solvedate'));
      $datas["##$objettype.duedate##"]      = convDateTime($this->obj->getField('due_date'));

      $datas["##$objettype.category##"] = '';
      if ($this->obj->getField('ticketcategories_id')) {
         $datas["##$objettype.category##"]
                     = Dropdown::getDropdownName('glpi_ticketcategories',
                                                   $this->obj->getField('ticketcategories_id'));
      }

      $datas["##$objettype.authors##"] = '';
      if ($this->obj->countUsers(CommonITILObject::REQUESTER)) {
         $users = array();
         foreach ($this->obj->getUsers(CommonITILObject::REQUESTER) as $uid => $tmp) {
            $user_tmp = new User;
            $user_tmp->getFromDB($uid);
            $users[$uid] = $user_tmp->getName();

            $tmp = array();
            $tmp['##author##']      = $uid;
            $tmp['##author.name##'] = $user_tmp->getName();

            if ($user_tmp->getField('locations_id')) {
               $tmp['##author.location##']
                                 = Dropdown::getDropdownName('glpi_locations',
                                                            $user_tmp->getField('locations_id'));
            } else {
               $tmp['##author.location##'] = '';
            }

            $tmp['##author.phone##']  = $user_tmp->getField('phone');
            $tmp['##author.phone2##'] = $user_tmp->getField('phone2');

            $datas['##authors##'][] = $tmp;
         }
         $datas["##$objettype.authors##"] = implode(', ',$users);
      }

      $datas["##$objettype.openbyuser##"] = '';
      if ($this->obj->getField('users_id_recipient')) {
         $user_tmp = new User;
         $user_tmp->getFromDB($this->obj->getField('users_id_recipient'));
         $datas["##$objettype.openbyuser##"] = $user_tmp->getName();
      }

      $datas["##$objettype.assigntousers##"] = '';
      if ($this->obj->countUsers(CommonITILObject::ASSIGN)) {
         $users = array();
         foreach ($this->obj->getUsers(CommonITILObject::ASSIGN) as $uid => $tmp) {
            $user_tmp = new User;
            $user_tmp->getFromDB($uid);

            $users[$uid] = $user_tmp->getName();
         }
         $datas["##$objettype.assigntousers##"] = implode(', ',$users);
      }

      $datas["##$objettype.assigntosupplier##"] = '';
      if ($this->obj->getField('suppliers_id_assign')) {
         $datas["##$objettype.assigntosupplier##"]
                        = Dropdown::getDropdownName('glpi_suppliers',
                                                   $this->obj->getField('suppliers_id_assign'));
      }

      $datas["##$objettype.groups##"] = '';
      if ($this->obj->countGroups(CommonITILObject::REQUESTER)) {
         $groups = array();
         foreach ($this->obj->getUsers(CommonITILObject::REQUESTER) as $gid => $tmp) {
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.groups##"] = implode(', ',$groups);
      }

      $datas["##$objettype.observergroups##"] = '';
      if ($this->obj->countGroups(CommonITILObject::OBSERVER)) {
         $groups = array();
         foreach ($this->obj->getGroups(CommonITILObject::OBSERVER) as $gid => $tmp) {
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.observergroups##"] = implode(', ',$groups);
      }

      $datas["##$objettype.observerusers##"] = '';
      if ($this->obj->countUsers(CommonITILObject::OBSERVER)) {
         $users = array();
         foreach ($this->obj->getUsers(CommonITILObject::OBSERVER) as $uid => $tmp) {
            $user_tmp = new User;
            $user_tmp->getFromDB($uid);
            $users[$uid] = $user_tmp->getName();
         }
         $datas["##$objettype.observerusers##"] = implode(', ',$users);
      }


      $datas["##$objettype.assigntogroups##"] = '';
      if ($this->obj->countGroups(CommonITILObject::ASSIGN)) {
         $groups = array();
         foreach ($this->obj->getGroups(CommonITILObject::ASSIGN) as $gid => $tmp) {
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.assigntogroups##"] = implode(', ',$groups);
      }

      $datas["##$objettype.solution.type##"]='';
      if ($this->obj->getField('solutiontypes_id')) {
         $datas["##$objettype.solution.type##"]
                     = Dropdown::getDropdownName('glpi_solutiontypes',
                                                   $this->obj->getField('solutiontypes_id'));
      }

      $datas["##$objettype.solution.description##"]
                  = unclean_cross_side_scripting_deep($this->obj->getField('solution'));

      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($this->obj,0,$CFG_GLPI['list_limit_max']) as $data) {
         $tmp = array();
         $tmp["##$objettype.log.date##"]    = $data['date_mod'];
         $tmp["##$objettype.log.user##"]    = $data['user_name'];
         $tmp["##$objettype.log.field##"]   = $data['field'];
         $tmp["##$objettype.log.content##"] = $data['change'];
         $datas['log'][] = $tmp;
      }

      $datas["##$objettype.numberoflogs##"] = 0;
      if (isset($datas['log'])) {
         $datas["##$objettype.numberoflogs##"] = count($datas['log']);
      }

      return $datas;
   }

   function getTags() {
      global $LANG;
      $itemtype = $this->obj->getType();
      $objettype = strtolower($itemtype);

      //Locales
      $tags = array($objettype.'.id'                    => $LANG['common'][2],
                    $objettype.'.title'                 => $LANG['common'][16],
                    $objettype.'.url'                   => $LANG['common'][94],
                    $objettype.'.entity'                => $LANG['entity'][0],
                    $objettype.'.category'              => $LANG['common'][36],
                    $objettype.'.content'               => $LANG['joblist'][6],
                    $objettype.'.description'           => $LANG['mailing'][5],
                    $objettype.'.status'                => $LANG['joblist'][0],
                    $objettype.'.urgency'               => $LANG['joblist'][29],
                    $objettype.'.impact'                => $LANG['joblist'][30],
                    $objettype.'.priority'              => $LANG['joblist'][2],
                    $objettype.'.time'                  => $LANG['job'][20],
                    $objettype.'.creationdate'          => $LANG['reports'][60],
                    $objettype.'.closedate'             => $LANG['reports'][61],
                    $objettype.'.solvedate'             => $LANG['reports'][64],
                    $objettype.'.duedate'               => $LANG['sla'][5],
                    $objettype.'.authors'               => $LANG['job'][18],
                    'author'                            => $LANG['common'][2].' '.$LANG['job'][4],
                    'author.name'                       => $LANG['job'][4],
                    'author.location'                   => $LANG['common'][15],
                    'author.phone'                      => $LANG['help'][35],
                    'author.phone2'                     => $LANG['help'][35].' 2',
                    $objettype.'.openbyuser'            => $LANG['common'][37],
                    $objettype.'.assigntousers'         => $LANG['job'][5]." - ".$LANG['job'][3],
                    $objettype.'.assigntosupplier'      => $LANG['job'][5]." - ".$LANG['financial'][26],
                    $objettype.'.groups'                => $LANG['common'][53]." : ".$LANG['common'][35],
                    $objettype.'.observergroups'        => $LANG['common'][104]." - ".$LANG['Menu'][36],
                    $objettype.'.assigntogroups'        => $LANG['job'][5]." - ".$LANG['Menu'][36],
                    $objettype.'.solution.type'         => $LANG['job'][48],
                    $objettype.'.solution.description'  => $LANG['jobresolution'][1],
                    $objettype.'.observerusers'         => $LANG['common'][104]." - ".$LANG['Menu'][14],
                  );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }


     //Foreach global tags
     $tags = array('log'           => $LANG['mailing'][144],
                   'authors'       => $LANG['job'][18]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }


      //Tags with just lang
      $tags = array($objettype.'.days'               => $LANG['stats'][31],
                    $objettype.'.attribution'        => $LANG['job'][5],
                    $objettype.'.nocategoryassigned' => $LANG['mailing'][100]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }

      //Tags without lang
      $tags = array($objettype.'.urlapprove'       => $LANG['document'][33].' '.$LANG['job'][51],
                    $objettype.'.log.date'         => $LANG['mailing'][144]. ' : '.$LANG['common'][26],
                    $objettype.'.log.user'         => $LANG['mailing'][144]. ' : '.$LANG['common'][34],
                    $objettype.'.log.field'        => $LANG['mailing'][144]. ' : '.$LANG['event'][18],
                    $objettype.'.log.content'      => $LANG['mailing'][144]. ' : '.$LANG['event'][19],
                   );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tickets with a fixed set of values
      $status = $this->obj->getAllStatusArray(false);
      $allowed_ticket = array();
      foreach ($status as $key => $value) {
         $allowed_ticket[] = $key;
      }

      $tags = array($objettype.'.storestatus'          => array('text'           => $LANG['joblist'][36],
                                                           'allowed_values' => $allowed_ticket));
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']));
      }

   }
}
?>
