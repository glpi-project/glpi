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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetTicket extends NotificationTarget {

   var $private_profiles = array();

   public $html_tags = array('##ticket.solution.description##');


   function __construct($entity='', $event='', $object=null, $options=array()) {
      parent::__construct($entity, $event, $object, $options);

      $this->options['sendprivate']=false;

      if (isset($options['followup_id'])) {
         $fup = new TicketFollowup();
         if ($fup->getFromDB($options['followup_id'])) {
            if ($fup->fields['is_private']) {
               $this->options['sendprivate'] = true;
            }
         }
      }

      if (isset($options['task_id'])) {
         $fup = new TicketTask();
         if ($fup->getFromDB($options['task_id'])) {
            if ($fup->fields['is_private']) {
               $this->options['sendprivate'] = true;
            }
         }
      }
   }


   /// Validate send before doing it (may be overloaded : exemple for private tasks or followups)
   function validateSendTo($user_infos) {

      // Private object and no right to see private items : do not send
      if ($this->isPrivate() && $user_infos['additionnaloption']==0) {
         return false;
      }
      return true;
   }


   /**
    * Get the email of the item's user : Overloaded manual address used
   **/
   function getItemAuthorAddress() {
      global $CFG_GLPI;

      $this->getLinkedUserByType(Ticket::REQUESTER);
   }


   function getSubjectPrefix($event='') {

      if ($event !='alertnotclosed') {
         return sprintf("[GLPI #%07d] ", $this->obj->getField('id'));
      }
      return parent::getSubjectPrefix();
   }


   function getSpecificTargets($data, $options) {

   //Look for all targets whose type is Notification::ITEM_USER
   switch ($data['type']) {
      case Notification::USER_TYPE :

         switch ($data['items_id']) {
            case Notification::TICKET_ASSIGN_TECH :
               $this->getLinkedUserByType(Ticket::ASSIGN);
               break;

            //Send to the group in charge of the ticket supervisor
            case Notification::TICKET_SUPERVISOR_ASSIGN_GROUP :
               $this->getLinkedGroupSupervisorByType(Ticket::ASSIGN);
               break;

            //Send to the user who's got the issue
            case Notification::TICKET_RECIPIENT :
               $this->getRecipientAddress();
               break;

            //Send to the supervisor of the requester's group
            case Notification::TICKET_SUPERVISOR_REQUESTER_GROUP :
               $this->getLinkedGroupSupervisorByType(Ticket::REQUESTER);
               break;

            //Send to the technician previously in charge of the ticket (before reassignation)
            case Notification::TICKET_OLD_TECH_IN_CHARGE :
               $this->getTicketOldAssignTechnicianAddress();
               break;

            //Assign to a supplier
            case Notification::TICKET_SUPPLIER :
               $this->getTicketSupplierAddress($this->options['sendprivate']);
               break;

            case Notification::TICKET_REQUESTER_GROUP :
               $this->getLinkedGroupByType(Ticket::REQUESTER);
               break;

            case Notification::TICKET_ASSIGN_GROUP :
               $this->getLinkedGroupByType(Ticket::ASSIGN);
               break;

            //Send to the ticket validation approver
            case Notification::TICKET_VALIDATION_APPROVER :
               $this->getTicketValidationApproverAddress($options);
               break;

            //Send to the ticket validation requester
            case Notification::TICKET_VALIDATION_REQUESTER :
               $this->getTicketValidationRequesterAddress($options);
               break;

            //Send to the ticket followup author
            case Notification::TICKET_FOLLOWUP_AUTHOR :
               $this->getTicketFollowupAuthor($options);
               break;

            //Send to the ticket followup author
            case Notification::TICKET_TASK_AUTHOR :
               $this->getTicketTaskAuthor($options);
               break;

            //Send to the ticket followup author
            case Notification::TICKET_TASK_ASSIGN_TECH :
               $this->getTicketTaskAssignUser($options);
               break;

            //Notification to the ticket's observer group
            case Notification::TICKET_OBSERVER_GROUP :
               $this->getLinkedGroupByType(Ticket::OBSERVER);
               break;

            //Notification to the ticket's observer user
            case Notification::TICKET_OBSERVER :
               $this->getLinkedUserByType(Ticket::OBSERVER);
               break;

            //Notification to the supervisor of the ticket's observer group
            case Notification::TICKET_SUPERVISOR_OBSERVER_GROUP :
               $this->getLinkedGroupSupervisorByType(Ticket::OBSERVER);
               break;

         }
      }
   }


   function addAdditionnalInfosForTarget() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_profiles`
                WHERE `glpi_profiles`.`show_full_ticket` = '1'";

      foreach ($DB->request($query) as $data) {
         $this->private_profiles[$data['id']] = $data['id'];
      }
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event='') {

      if ($this->obj) {
         $itemtype = $this->obj->getField('itemtype');

         if ($itemtype != NOT_AVAILABLE && $itemtype != '' &&  class_exists($itemtype)) {
            $item = new  $itemtype ();
            $item->getFromDB($this->obj->getField('items_id'));
            $this->target_object = $item;
         }
      }
   }


   /**
    * Add linked users to the notified users list
    *
    * @param $type type of linked users
   **/
   function getLinkedUserByType ($type) {
      global $DB,$CFG_GLPI;

      //Look for the user by his id
      $query =        $this->getDistinctUserSql().",
                      `glpi_tickets_users`.`use_notification` AS notif,
                      `glpi_tickets_users`.`alternative_email` AS altemail
               FROM `glpi_tickets_users`
               LEFT JOIN `glpi_users` ON (`glpi_tickets_users`.`users_id` = `glpi_users`.`id`)
               WHERE `glpi_tickets_users`.`tickets_id` = '".$this->obj->fields["id"]."'
                     AND `glpi_tickets_users`.`type` = '$type'";

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

      //Look for the user by his id
      $query = "SELECT `groups_id`
                FROM `glpi_groups_tickets`
                WHERE `glpi_groups_tickets`.`tickets_id` = '".$this->obj->fields["id"]."'
                      AND `glpi_groups_tickets`.`type` = '$type'";

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

      //Look for the user by his id
      $query =        $this->getDistinctUserSql()."
               FROM `glpi_groups_tickets`
               INNER JOIN `glpi_groups` ON (`glpi_groups_tickets`.`groups_id` = `glpi_groups`.`id`)
               INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)
               WHERE `glpi_groups_tickets`.`tickets_id` = '".$this->obj->fields["id"]."'
                     AND `glpi_groups_tickets`.`type` = '$type'";

      foreach ($DB->request($query) as $data) {
         //Add the group in the notified users list
         $this->addToAddressesList($data);
      }
   }


//    function getGroupSupervisorAddress ($assign=true) {
//       global $DB;
//
//       $group_field = ($assign?"groups_id_assign":"groups_id");
//
//       if (isset($this->obj->fields[$group_field]) && $this->obj->fields[$group_field]>0) {
//
//          $query = $this->getDistinctUserSql()."
//                   FROM `glpi_groups`
//                   LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)".
//                   $this->getJoinSql()."
//                   WHERE `glpi_groups`.`id` = '".$this->obj->fields[$group_field]."'";
//
//          foreach ($DB->request($query) as $data) {
//             $this->addToAddressesList($data);
//          }
//       }
//    }

//    function getRequestGroupAddresses() {
//
//       if ($this->obj->fields['groups_id']) {
//          $this->getUsersAddressesByGroup($this->obj->fields['groups_id']);
//       }
//    }


//    function getAssignGroupAddresses() {
//
//       if ($this->obj->fields['groups_id_assign']) {
//          $this->getUsersAddressesByGroup($this->obj->fields['groups_id_assign']);
//       }
//    }


   function getTicketAssignTechnicianAddress () {
      return $this->getUserByField ("users_id_assign");
   }


    function getTicketOldAssignTechnicianAddress () {
      global $CFG_GLPI;

       if (isset($this->options['_old_user'])
           && $this->options['_old_user']['type'] == Ticket::ASSIGN
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


         print_r($this->target);exit();
      }


    }


   //Get receipient
   function getRecipientAddress() {
      return $this->getUserByField ("users_id_recipient");
  }


   /**
    * Get supplier related to the ticket
   **/
   function getTicketSupplierAddress($sendprivate=false) {
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
   function getTicketValidationApproverAddress($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $query =        $this->getDistinctUserSql()."
                   FROM `glpi_ticketvalidations`
                   LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `glpi_ticketvalidations`.`users_id_validate`)
                   WHERE `glpi_ticketvalidations`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get requester related to the ticket validation
   **/
   function getTicketValidationRequesterAddress($options=array()) {
      global $DB;

      if (isset($options['validation_id'])) {
         $query =        $this->getDistinctUserSql()."
                   FROM `glpi_ticketvalidations`
                   LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `glpi_ticketvalidations`.`users_id`)
                   WHERE `glpi_ticketvalidations`.`id` = '".$options['validation_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getTicketFollowupAuthor($options=array()) {
      global $DB;

      if (isset($options['followup_id'])) {
         $query =        $this->getDistinctUserSql()."
                   FROM `glpi_ticketfollowups`
                   LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_ticketfollowups`.`users_id`)
                   WHERE `glpi_ticketfollowups`.`id` = '".$options['followup_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getTicketTaskAuthor($options=array()) {
      global $DB;

      if (isset($options['task_id'])) {
         $query =        $this->getDistinctUserSql()."
                   FROM `glpi_tickettasks`
                   LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickettasks`.`users_id`)
                   WHERE `glpi_tickettasks`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get author related to the followup
   **/
   function getTicketTaskAssignUser($options=array()) {
      global $DB;

      if (isset($options['task_id'])) {
         $query =        $this->getDistinctUserSql()."
                   FROM `glpi_tickettasks`
                   LEFT JOIN `glpi_ticketplannings`
                        ON (`glpi_ticketplannings`.`tickettasks_id` = `glpi_tickettasks`.`id`)
                   LEFT JOIN `glpi_users`
                        ON (`glpi_users`.`id` = `glpi_ticketplannings`.`users_id`)
                   WHERE `glpi_tickettasks`.`id` = '".$options['task_id']."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
      }
   }


   function addAdditionnalUserInfo($data) {
      global $DB;

      if (!isset($data['id'])) {
         return 1;
      }
      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_profiles_users`
                WHERE `users_id`='".$data['id']."' ".
                      getEntitiesRestrictRequest("AND", "glpi_profiles_users", "entities_id",
                                                 $this->obj->fields['entities_id'], true)."
                      AND profiles_id IN (".implode(',',$this->private_profiles).")";
      $result = $DB->query($query);

      if ($DB->result($result,0,'cpt')) {
         return 1;
      }
      return 0;
   }


   /**
    *Get events related to tickets
   **/
   function getEvents() {
      global $LANG;

      $events = array('new'             => $LANG['job'][13],
                      'update'          => $LANG['mailing'][30],
                      'solved'          => $LANG['mailing'][123],
                      'validation'      => $LANG['validation'][26],
                      'add_followup'    => $LANG['mailing'][10],
                      'update_followup' => $LANG['mailing'][134],
                      'delete_followup' => $LANG['mailing'][135],
                      'add_task'        => $LANG['job'][49],
                      'update_task'     => $LANG['job'][52],
                      'delete_task'     => $LANG['job'][53],
                      'closed'          => $LANG['mailing'][127],
                      'delete'          => $LANG['mailing'][129],
                      'alertnotclosed'  => $LANG['crontask'][15],
                      'recall'          => $LANG['sla'][9],
                      'satisfaction'    => $LANG['satisfaction'][3]);
      asort($events);
      return $events;
   }


   /**
    * Get additionnals targets for Tickets
   **/
   function getAdditionalTargets($event='') {
      global $LANG;

      if ($event=='update') {
         $this->addTarget(Notification::TICKET_OLD_TECH_IN_CHARGE, $LANG['setup'][236]);
      }

      if ($event=='satisfaction') {
         $this->addTarget(Notification::AUTHOR, $LANG['job'][4]);
         $this->addTarget(Notification::TICKET_RECIPIENT, $LANG['common'][37]);

      } else if ($event!='alertnotclosed') {
         $this->addTarget(Notification::TICKET_RECIPIENT, $LANG['common'][37]);
         $this->addTarget(Notification::TICKET_SUPPLIER, $LANG['financial'][26]);
         $this->addTarget(Notification::TICKET_SUPERVISOR_ASSIGN_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][248]);
         $this->addTarget(Notification::TICKET_SUPERVISOR_REQUESTER_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][249]);
         $this->addTarget(Notification::ITEM_TECH_IN_CHARGE, $LANG['common'][10]);
         $this->addTarget(Notification::TICKET_ASSIGN_TECH, $LANG['setup'][239]);
         $this->addTarget(Notification::TICKET_REQUESTER_GROUP, $LANG['setup'][249]);
         $this->addTarget(Notification::AUTHOR, $LANG['job'][4]);
         $this->addTarget(Notification::ITEM_USER, $LANG['mailing'][137]);
         $this->addTarget(Notification::TICKET_ASSIGN_GROUP, $LANG['setup'][248]);
         $this->addTarget(Notification::TICKET_OBSERVER_GROUP, $LANG['setup'][251]);
         $this->addTarget(Notification::TICKET_OBSERVER, $LANG['common'][104]);
         $this->addTarget(Notification::TICKET_SUPERVISOR_OBSERVER_GROUP,
                          $LANG['common'][64]." - ".$LANG['setup'][251]);
      }

      if ($event=='validation') {
         $this->addTarget(Notification::TICKET_VALIDATION_APPROVER,
                          $LANG['validation'][0].' - '.$LANG['validation'][21]);
         $this->addTarget(Notification::TICKET_VALIDATION_REQUESTER,
                          $LANG['validation'][0].' - '.$LANG['validation'][18]);
      }
      if ($event=='update_task' || $event=='add_task' || $event=='delete_task') {
         $this->addTarget(Notification::TICKET_TASK_ASSIGN_TECH,
                          $LANG['job'][7]." - ".$LANG['job'][6]);
         $this->addTarget(Notification::TICKET_TASK_AUTHOR,
                          $LANG['job'][7]." - ".$LANG['common'][37]);
      }
      if ($event=='update_followup' || $event=='add_followup' || $event=='delete_followup') {
         $this->addTarget(Notification::TICKET_FOLLOWUP_AUTHOR,
                          $LANG['job'][9]." - ".$LANG['common'][37]);
      }
   }


   /**
    * Restrict by profile and by config
    * to avoid send notification to a user without rights
   **/
   function getProfilesJoinSql() {

      $query = " INNER JOIN `glpi_profiles_users`
                     ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id` ".
                         getEntitiesRestrictRequest("AND", "glpi_profiles_users", "entities_id",
                                                    $this->getEntity(), true).")";

      if ($this->isPrivate()) {
         $query .= " INNER JOIN `glpi_profiles`
                     ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                         AND `glpi_profiles`.`interface` = 'central'
                         AND `glpi_profiles`.`show_full_ticket` = '1') ";
      }
      return $query;
   }


   function isPrivate() {

      if (isset($this->options['sendprivate']) && $this->options['sendprivate'] == 1) {
         return true;
      }
      return false;
   }


   /**
    * Get all data needed for template processing
   **/
   function getDatasForTemplate($event, $options=array()) {
      global $LANG, $CFG_GLPI;

      //----------- Ticket infos -------------- //

      if ($event != 'alertnotclosed') {
         $fields = array('ticket.title'        => 'name',
                         'ticket.content'      => 'content',
                         'ticket.costfixed'    => 'cost_fixed',
                         'ticket.costmaterial' => 'cost_material',
                         'ticket.useremail'    => 'user_email');

         foreach ($fields as $tag => $table_field) {
            $this->datas['##'.$tag.'##'] = $this->obj->getField($table_field);
         }

         $this->datas['##ticket.id##']              = sprintf("%07d",$this->obj->getField("id"));
         $this->datas['##ticket.url##']             = urldecode($CFG_GLPI["url_base"].
                                                                "/index.php?redirect=ticket_".
                                                                $this->obj->getField("id"));
         $this->datas['##ticket.urlapprove##']      = urldecode($CFG_GLPI["url_base"].
                                                                "/index.php?redirect=ticket_".
                                                                $this->obj->getField("id")."_4");
         $this->datas['##ticket.urlvalidation##']   = urldecode($CFG_GLPI["url_base"].
                                                                "/index.php?redirect=ticket_".
                                                                $this->obj->getField("id")."_7");

         $this->datas['##ticket.entity##']          = Dropdown::getDropdownName('glpi_entities',
                                                                                $this->getEntity());
         $events = $this->getAllEvents();

         if ($event != 'validation') {
            $this->datas['##ticket.action##'] = $events[$event];
         } else {
            $this->datas['##ticket.action##']
                        = $LANG['validation'][0].' - '.
                          TicketValidation::getStatus($options['validation_status']);
         }

         $this->datas['##ticket.storestatus##'] = $this->obj->getField('status');
         $this->datas['##ticket.status##']      = Ticket::getStatus($this->obj->getField('status'));
         $this->datas['##ticket.globalvalidation##']
                     = TicketValidation::getStatus($this->obj->getField('global_validation'));
         $this->datas['##ticket.type##']  = Ticket::getTicketTypeName($this->obj->getField('type'));
         $this->datas['##ticket.requesttype##']
                     = Dropdown::getDropdownName('glpi_requesttypes',
                                                 $this->obj->getField('requesttypes_id'));

         $this->datas['##ticket.urgency##']
                     = Ticket::getUrgencyName($this->obj->getField('urgency'));
         $this->datas['##ticket.impact##']   = Ticket::getImpactName($this->obj->getField('impact'));
         $this->datas['##ticket.priority##']
                     = Ticket::getPriorityName($this->obj->getField('priority'));
         $this->datas['##ticket.time##']
                     = Ticket::getActionTime($this->obj->getField('actiontime'));
         $this->datas['##ticket.costtime##'] = $this->obj->getField('cost_time');

         $this->datas['##ticket.creationdate##'] = convDateTime($this->obj->getField('date'));
         $this->datas['##ticket.closedate##']    = convDateTime($this->obj->getField('closedate'));
         $this->datas['##ticket.solvedate##']    = convDateTime($this->obj->getField('solvedate'));
         $this->datas['##ticket.duedate##']      = convDateTime($this->obj->getField('due_date'));

         $this->datas['##ticket.useremailnotification##']
                     = Dropdown::getYesNo($this->obj->getField('user_email_notification'));

         $entitydata = new EntityData;
         $autoclose_value = $CFG_GLPI['autoclose_delay'];

         if ($entitydata->getFromDB($this->getEntity())) {
            $autoclose_value = $entitydata->getField('autoclose_delay');

            // Set global config value
            if ($autoclose_value == -1) {
               $autoclose_value=$CFG_GLPI['autoclose_delay'];
            }
         }

         if ($autoclose_value > 0) {
                  $this->datas['##ticket.autoclose##'] = $autoclose_value;
                  $this->datas['##lang.ticket.autoclosewarning##']
                              = $LANG['job'][54]." ".$autoclose_value." ".$LANG['stats'][31];
         } else {
            $this->datas['##ticket.autoclose##']             = $LANG['setup'][307];
            $this->datas['##lang.ticket.autoclosewarning##'] = "";
         }

         if ($this->obj->getField('ticketcategories_id')) {
            $this->datas['##ticket.category##']
                        = Dropdown::getDropdownName('glpi_ticketcategories',
                                                    $this->obj->getField('ticketcategories_id'));
         } else {
            $this->datas['##ticket.category##'] = '';
         }

         if ($this->obj->getField('slas_id')) {
            $this->datas['##ticket.sla##']
                        = Dropdown::getDropdownName('glpi_slas', $this->obj->getField('slas_id'));
         } else {
            $this->datas['##ticket.sla##'] = '';
         }

         if ($this->obj->countUsers(Ticket::REQUESTER)) {
            $users = array();
            foreach ($this->obj->getUsers(Ticket::REQUESTER) as $uid => $tmp) {
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

               $this->datas['##authors##'][] = $tmp;
            }
            $this->datas['##ticket.authors##'] = implode(', ',$users);
         } else {
            $this->datas['##ticket.authors##'] = '';
         }

         if ($this->obj->getField('users_id_recipient')) {
            $user_tmp = new User;
            $user_tmp->getFromDB($this->obj->getField('users_id_recipient'));
            $this->datas['##ticket.openbyuser##'] = $user_tmp->getName();
         } else {
            $this->datas['##ticket.openbyuser##'] = '';
         }

         if ($this->obj->countUsers(Ticket::ASSIGN)) {
            $users = array();
            foreach ($this->obj->getUsers(Ticket::ASSIGN) as $uid => $tmp) {
               $user_tmp = new User;
               $user_tmp->getFromDB($uid);

               $users[$uid] = $user_tmp->getName();
            }
            $this->datas['##ticket.assigntousers##'] = implode(', ',$users);
         } else {
            $this->datas['##ticket.assigntousers##'] = '';
         }

         if ($this->obj->getField('suppliers_id_assign')) {
            $this->datas['##ticket.assigntosupplier##']
                          = Dropdown::getDropdownName('glpi_suppliers',
                                                      $this->obj->getField('suppliers_id_assign'));
         } else {
            $this->datas['##ticket.assigntosupplier##'] = '';
         }

         if ($this->obj->countGroups(Ticket::REQUESTER)) {
            $groups = array();
            foreach ($this->obj->getUsers(Ticket::REQUESTER) as $gid => $tmp) {
               $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $this->datas['##ticket.groups##'] = implode(', ',$groups);
         } else {
            $this->datas['##ticket.groups##'] = '';
         }

         if ($this->obj->countGroups(Ticket::OBSERVER)) {
            $groups = array();
            foreach ($this->obj->getGroups(Ticket::OBSERVER) as $gid => $tmp) {
               $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $this->datas['##ticket.observergroups##'] = implode(', ',$groups);
         } else {
            $this->datas['##ticket.observergroups##'] = '';
         }

         if ($this->obj->countGroups(Ticket::ASSIGN)) {
            $groups = array();
            foreach ($this->obj->getGroups(Ticket::ASSIGN) as $gid => $tmp) {
               $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
            }
            $this->datas['##ticket.assigntogroups##'] = implode(', ',$groups);
         } else {
            $this->datas['##ticket.assigntogroups##'] = '';
         }

         //Tags associated with the object linked to the ticket
         if ($this->target_object != null) {

            //Object type
            $this->datas['##ticket.itemtype##']  = $this->target_object->getTypeName();

            //Object name
            $this->datas['##ticket.item.name##'] = $this->target_object->getField('name');

            //Object serial
            if ($this->target_object->isField('serial')) {
               $this->datas['##ticket.item.serial##'] = $this->target_object->getField('serial');
            } else {
               $this->datas['##ticket.item.serial##'] = '';
            }

            //Object contact
            if ($this->target_object->isField('contact')) {
               $this->datas['##ticket.item.contact##'] = $this->target_object->getField('contact');
            } else {
               $this->datas['##ticket.item.contact##'] = '';
            }

            //Object contact num
            if ($this->target_object->isField('contact_num')) {
               $this->datas['##ticket.item.contactnumber##']
                           = $this->target_object->getField('contact_num');
            } else {
               $this->datas['##ticket.item.contactnumber##'] = '';
            }

            //Object otherserial
            if ($this->target_object->isField('otherserial')) {
               $this->datas['##ticket.item.otherserial##']
                           = $this->target_object->getField('otherserial');
            } else {
               $this->datas['##ticket.item.otherserial##'] = '';
            }

            //Object location
            if ($this->target_object->isField('location')) {
               $this->datas['##ticket.item.location##']
                           = Dropdown::getDropdownName('glpi_locations',
                                                       $user->getField('locations_id'));
            } else {
               $this->datas['##ticket.item.locationl##']='';
            }
            //Object user
            $this->datas['##ticket.item.user##'] = '';
            if ($this->obj->getField('users_id')) {
               $user_tmp = new User;
               if ($user_tmp->getFromDB($this->target_object->getField('users_id'))) {
                  $this->datas['##ticket.item.user##'] = $user_tmp->getName();
               }
            }

            //Object group
            if ($this->obj->getField('groups_id')) {
               $this->datas['##ticket.item.group##']
                           = Dropdown::getDropdownName('glpi_groups',
                                                       $this->target_object->getField('groups_id'));
            } else {
               $this->datas['##ticket.item.group##'] = '';
            }

            $modeltable = getSingular($this->getTable())."models";
            $modelfield = getForeignKeyFieldForTable($modeltable);

            if ($this->target_object->isField($modelfield)) {
               $this->datas['##ticket.item.model##'] = $this->target_object->getField($modelfield);
            } else {
               $this->datas['##ticket.item.model##'] = '';
            }

         } else {
            $this->datas['##ticket.itemtype##']         = '';
            $this->datas['##ticket.item.name##']        = '';
            $this->datas['##ticket.item.serial##']      = '';
            $this->datas['##ticket.item.otherserial##'] = '';
            $this->datas['##ticket.item.location##']    = '';
         }

         if ($this->obj->getField('ticketsolutiontypes_id')) {
            $this->datas['##ticket.solution.type##']
                        = Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                                    $this->obj->getField('ticketsolutiontypes_id'));
         } else {
            $this->datas['##ticket.solution.type##']='';
         }
         $this->datas['##ticket.solution.description##']
                     = unclean_cross_side_scripting_deep($this->obj->getField('solution'));


         // Linked tickets
         $linked_tickets = Ticket_Ticket::getLinkedTicketsTo($this->obj->getField('id'));
         if (count($linked_tickets)) {
            $linkedticket = new Ticket();
            foreach ($linked_tickets as $data) {
               $tmp = array();
               $tmp['##linkedticket.id##']   = $data['tickets_id'];
               $tmp['##linkedticket.link##'] = Ticket_Ticket::getLinkName($data['link']);
               $tmp['##linkedticket.url##']  = urldecode($CFG_GLPI["url_base"]."/index.php".
                                                         "?redirect=ticket_".$data['tickets_id']);

               $linkedticket->getFromDB($data['tickets_id']);
               $tmp['##linkedticket.title##']   = $linkedticket->getField('name');
               $tmp['##linkedticket.content##'] = $linkedticket->getField('content');

               $this->datas['linkedtickets'][] = $tmp;
            }
         }

         $restrict = "`tickets_id`='".$this->obj->getField('id')."'";
         if (!isset($options['additionnaloption']) || !$options['additionnaloption']) {
            $restrict .= " AND `is_private` = '0'";
         }
         $restrict .= " ORDER BY `date` DESC";

         //Task infos
         $tasks = getAllDatasFromTable('glpi_tickettasks',$restrict);

         foreach ($tasks as $task) {
            $tmp = array();
            $tmp['##task.isprivate##']   = Dropdown::getYesNo($task['is_private']);
            $tmp['##task.author##']      = html_clean(getUserName($task['users_id']));
            $tmp['##task.category##']    = Dropdown::getDropdownName('glpi_taskcategories',
                                                                     $task['taskcategories_id']);
            $tmp['##task.date##']        = convDateTime($task['date']);
            $tmp['##task.description##'] = $task['content'];
            $tmp['##task.time##']        = Ticket::getActionTime($task['actiontime']);

            $plan = new TicketPlanning();
            if ($plan->getFromDBbyTask($task['id'])) {
               $tmp['##task.planning.user##']   = html_clean(getUserName($plan->fields['users_id']));
               $tmp['##task.planning.begin##']  = convDateTime($plan->fields['begin']);
               $tmp['##task.planning.end##']    = convDateTime($plan->fields['end']);
               $tmp['##task.planning.status##'] = Planning::getState($plan->fields['state']);
            }

            $this->datas['tasks'][] = $tmp;
         }

         if (!empty($this->datas['tasks'])) {
            $this->datas['##ticket.numberoftasks##'] = count($this->datas['tasks']);
         } else {
            $this->datas['##ticket.numberoftasks##'] = 0;
         }

         //Followup infos
         $followups = getAllDatasFromTable('glpi_ticketfollowups',$restrict);
         foreach ($followups as $followup) {
            $tmp = array();
            $tmp['##followup.isprivate##']   = Dropdown::getYesNo($followup['is_private']);
            $tmp['##followup.author##']      = html_clean(getUserName($followup['users_id']));
            $tmp['##followup.requesttype##']
                  = Dropdown::getDropdownName('glpi_requesttypes', $followup['requesttypes_id']);
            $tmp['##followup.date##']        = convDateTime($followup['date']);
            $tmp['##followup.description##'] = $followup['content'];
            $this->datas['followups'][] = $tmp;
         }

         if (isset($this->datas['followups'])) {
            $this->datas['##ticket.numberoffollowups##'] = count($this->datas['followups']);
         } else {
            $this->datas['##ticket.numberoffollowups##'] = 0;
         }

         //Validation infos
         $restrict = "`tickets_id`='".$this->obj->getField('id')."'";

         if (isset($options['validation_id']) && $options['validation_id']) {
            $restrict .= " AND `glpi_ticketvalidations`.`id` = '".$options['validation_id']."'";
         }

         $restrict .= " ORDER BY `submission_date` DESC";
         $validations = getAllDatasFromTable('glpi_ticketvalidations',$restrict);

         foreach ($validations as $validation) {
            $tmp = array();
            $tmp['##validation.submission.title##']
                  = $LANG['validation'][27]." (".$LANG['job'][4]." ".
                    html_clean(getUserName($validation['users_id'])).")";
            $tmp['##validation.answer.title##']
                  = $LANG['validation'][32]." (".$LANG['validation'][21]." ".
                    html_clean(getUserName($validation['users_id_validate'])).")";

            $tmp['##validation.author##']       = html_clean(getUserName($validation['users_id']));

            $tmp['##validation.status##']       = TicketValidation::getStatus($validation['status']);
            $tmp['##validation.storestatus##']       = $validation['status'];
            $tmp['##validation.submissiondate##']    = convDateTime($validation['submission_date']);
            $tmp['##validation.commentsubmission##'] = $validation['comment_submission'];
            $tmp['##validation.validationdate##']    = convDateTime($validation['validation_date']);
            $tmp['##validation.validator##']    =  html_clean(getUserName($validation['users_id_validate']));
            $tmp['##validation.commentvalidation##'] = $validation['comment_validation'];
            $this->datas['validations'][] = $tmp;
         }

         // Ticket Satisfaction
         $inquest = new TicketSatisfaction();

         if ($inquest->getFromDB($this->obj->getField('id'))) {
            // internal inquest
            if ($inquest->fields['type'] == 1) {
               $this->datas['##ticket.urlsatisfaction##']
                           = urldecode($CFG_GLPI["url_base"]."/index.php?redirect=ticket_".
                                       $this->obj->getField("id")."_10");
            // external inquest
            } else if ($inquest->fields['type'] == 2) {
               $this->datas['##ticket.urlsatisfaction##']
                           = EntityData::generateLinkSatisfaction($this->obj);
            }

            $this->datas['##satisfaction.type##'] = $inquest->getTypeInquestName($inquest->getfield('type'));
            $this->datas['##satisfaction.datebegin##']    = convDateTime($inquest->fields['date_begin']);
            $this->datas['##satisfaction.dateanswered##'] = convDateTime($inquest->fields['date_answered']);
            $this->datas['##satisfaction.satisfaction##'] = $inquest->fields['satisfaction'];
            $this->datas['##satisfaction.description##']  = $inquest->fields['comment'];
         } else {
            $this->datas['##satisfaction.type##']         = '';
            $this->datas['##satisfaction.datebegin##']    = '';
            $this->datas['##satisfaction.dateanswered##'] = '';
            $this->datas['##satisfaction.satisfaction##'] = '';
            $this->datas['##satisfaction.description##']  = '';
         }


         // Use list_limit_max or load the full history ?
         foreach (Log::getHistoryData($this->obj,0,$CFG_GLPI['list_limit_max']) as $data) {
            $tmp = array();
            $tmp['##ticket.log.date##']    = $data['date_mod'];
            $tmp['##ticket.log.user##']    = $data['user_name'];
            $tmp['##ticket.log.field##']   = $data['field'];
            $tmp['##ticket.log.content##'] = $data['change'];
            $this->datas['log'][] = $tmp;
         }

         if (isset($this->datas['log'])) {
            $this->datas['##ticket.numberoflogs##'] = count($this->datas['log']);
         } else {
            $this->datas['##ticket.numberoflogs##'] = 0;
         }

      } else {
         $this->datas['##ticket.entity##']      = Dropdown::getDropdownName('glpi_entities',
                                                                            $options['entities_id']);
         $this->datas['##ticket.action##']      = $LANG['crontask'][15];
         $tmp = array();
         $t = new Ticket();
         foreach ($options['tickets'] as $ticket) {
            $t->getFromDB($ticket['id']);

            $tmp['##ticket.id##']           = sprintf("%07d",$ticket['id']);
            $tmp['##ticket.url##']          = urldecode($CFG_GLPI["url_base"].
                                                        "/index.php?redirect=ticket_".$ticket['id']);

            $tmp['##ticket.title##']       = $ticket['name'];
            $tmp['##ticket.status##']      = Ticket::getStatus($ticket['status']);
            $tmp['##ticket.globalvalidation##']
                                           = TicketValidation::getStatus($ticket['global_validation']);
            $tmp['##ticket.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                       $ticket['requesttypes_id']);

            $tmp['##ticket.urgency##']          = Ticket::getUrgencyName($ticket['urgency']);
            $tmp['##ticket.impact##']           = Ticket::getImpactName($ticket['impact']);
            $tmp['##ticket.priority##']         = Ticket::getPriorityName($ticket['priority']);
            $tmp['##ticket.time##']             = Ticket::getActionTime($ticket['actiontime']);
            $tmp['##ticket.costtime##']         = $ticket['cost_time'];
            $tmp['##ticket.creationdate##']     = convDateTime($ticket['date']);
            $tmp['##ticket.content##']          = $ticket['content'];


            if ($t->countUsers(Ticket::REQUESTER)) {
               $users = array();
               foreach ($t->getUsers(Ticket::REQUESTER) as $uid => $tmp) {
                  $user_tmp = new User;
                  $user_tmp->getFromDB($uid);

                  $users[$uid] = $user_tmp->getName();


                  $tmp2 = array();
                  $tmp2['##author##']      = $uid;
                  $tmp2['##author.name##'] = $user_tmp->getName();

                  if ($user_tmp->getField('locations_id')) {
                     $tmp2['##author.location##']
                                 = Dropdown::getDropdownName('glpi_locations',
                                                             $user_tmp->getField('locations_id'));
                  } else {
                     $tmp2['##author.location##'] = '';
                  }

                  $tmp2['##author.phone##']  = $user_tmp->getField('phone');
                  $tmp2['##author.phone2##'] = $user_tmp->getField('phone2');

                  $tmp['##authors##'][] = $tmp2;
               }
               $tmp['##ticket.authors##'] = implode(', ',$users);
            } else {
               $tmp['##ticket.authors##'] = '';
            }


            if ($t->countUsers(Ticket::ASSIGN)) {
               $users = array();
               foreach ($t->getUsers(Ticket::ASSIGN) as $uid => $tmp) {
                  $user_tmp = new User;
                  $user_tmp->getFromDB($uid);

                  $users[$uid] = $user_tmp->getName();
               }
               $tmp['##ticket.assigntousers##'] = implode(', ',$users);
            } else {
               $tmp['##ticket.assigntousers##'] = '';
            }

            if ($t->countGroups(Ticket::ASSIGN)) {
               $groups = array();
               foreach ($t->getGroups(Ticket::ASSIGN) as $gid => $tmp) {
                  $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
               }
               $tmp['##ticket.assigntogroups##'] = implode(', ',$groups);
            } else {
               $tmp['##ticket.assigntogroups##'] = '';
            }

            if ($t->countGroups(Ticket::REQUESTER)) {
               $groups = array();
               foreach ($t->getGroups(Ticket::REQUESTER) as $gid => $tmp) {
                  $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
               }
               $tmp['##ticket.groups##'] = implode(', ',$groups);
            } else {
               $tmp['##ticket.groups##'] = '';
            }

            if ($t->countUsers(Ticket::OBSERVER)) {
               $users = array();
               foreach ($t->getUsers(Ticket::OBSERVER) as $uid => $tmp) {
                  $user_tmp = new User;
                  $user_tmp->getFromDB($uid);
                  $users[$uid] = $user_tmp->getName();
               }
               $tmp['##ticket.observerusers##'] = implode(', ',$users);
            } else {
               $tmp['##ticket.observerusers##'] = '';
            }

            if ($t->countGroups(Ticket::OBSERVER)) {
               $groups = array();
               foreach ($t->getGroups(Ticket::OBSERVER) as $gid => $tmp) {
                  $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
               }
               $tmp['##ticket.observergroups##'] = implode(', ',$groups);
            } else {
               $tmp['##ticket.observergroups##'] = '';
            }

            if ($ticket['suppliers_id_assign']) {
               $tmp['##ticket.assigntosupplier##']
                     = Dropdown::getDropdownName('glpi_suppliers', $ticket['suppliers_id_assign']);
            } else {
               $tmp['##ticket.assigntosupplier##'] = '';
            }

            $this->datas['tickets'][] = $tmp;
         }
      }

      $this->getTags();
      foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
         if (!isset($this->datas[$tag])) {
            $this->datas[$tag] = $values['label'];
         }
      }
   }


   static function isAuthorMailingActivatedForHelpdesk() {
      global $DB,$CFG_GLPI;

      if ($CFG_GLPI['use_mailing']) {
         $query = "SELECT COUNT(`glpi_notifications`.`id`)
                   FROM `glpi_notifications`
                   INNER JOIN `glpi_notificationtargets`
                     ON (`glpi_notifications`.`id` = `glpi_notificationtargets`.`notifications_id`)
                   WHERE `glpi_notifications`.`itemtype` = 'Ticket'
                         AND `glpi_notifications`.`mode` = 'mail'
                         AND `glpi_notificationtargets`.`type` = '".Notification::USER_TYPE."'
                         AND `glpi_notificationtargets`.`items_id` = '".Notification::AUTHOR."'";

         if ($result = $DB->query($query)) {
            if ($DB->result($result,0,0) >0) {
               return true;
            }
         }
      }
      return false;
   }


   function getTags() {
      global $LANG;

      //Locales
      $tags = array('ticket.id'                    => $LANG['common'][2],
                    'ticket.title'                 => $LANG['common'][16],
                    'ticket.url'                   => $LANG['common'][94],
                    'ticket.entity'                => $LANG['entity'][0],
                    'ticket.category'              => $LANG['common'][36],
                    'ticket.content'               => $LANG['joblist'][6],
                    'ticket.description'           => $LANG['mailing'][5],
                    'ticket.status'                => $LANG['joblist'][0],
                    'ticket.type'                  => $LANG['common'][17],
                    'ticket.creationdate'          => $LANG['reports'][60],
                    'ticket.closedate'             => $LANG['reports'][61],
                    'ticket.solvedate'             => $LANG['reports'][64],
                    'ticket.sla'                   => $LANG['sla'][1],
                    'ticket.duedate'               => $LANG['sla'][5],
                    'ticket.requesttype'           => $LANG['job'][44],
                    'ticket.authors'               => $LANG['job'][18],
                    'author'                       => $LANG['common'][2].' '.$LANG['job'][4],
                    'author.name'                  => $LANG['job'][4],
                    'author.location'              => $LANG['common'][15],
                    'author.phone'                 => $LANG['help'][35],
                    'author.phone2'                => $LANG['help'][35].' 2',
                    'ticket.openbyuser'            => $LANG['common'][37],
//                      'ticket.group'                 => $LANG['common'][35],
                    'ticket.groups'                => $LANG['common'][53]." : ".$LANG['common'][35],
                    'ticket.attribution'           => $LANG['job'][5],
//                     'ticket.assigntouser'          => $LANG['job'][5]." - ".$LANG['job'][6],
                    'ticket.assigntousers'         => $LANG['job'][5]." - ".$LANG['job'][3],
//                      'ticket.assigntogroup'         => $LANG['job'][5]." - ".$LANG['common'][35],
                    'ticket.assigntogroups'        => $LANG['job'][5]." - ".$LANG['Menu'][36],
                    'ticket.assigntosupplier'      => $LANG['job'][5]." - ".$LANG['financial'][26],
                    'ticket.observergroups'        => $LANG['common'][104]." - ".$LANG['Menu'][36],
                    'ticket.observerusers'         => $LANG['common'][104]." - ".$LANG['Menu'][14],
                    'ticket.itemtype'              => $LANG['reports'][12],
                    'ticket.item.name'             => $LANG['financial'][104],
                    'ticket.item.serial'           => $LANG['common'][19],
                    'ticket.item.otherserial'      => $LANG['common'][20],
                    'ticket.item.location'         => $LANG['common'][15],
                    'ticket.item.model'            => $LANG['common'][22],
                    'ticket.item.contact'          => $LANG['common'][18],
                    'ticket.item.contactnumber'    => $LANG['common'][21],
                    'ticket.item.user'             => $LANG['common'][34],
                    'ticket.item.group'            => $LANG['common'][35],
                    'ticket.urgency'               => $LANG['joblist'][29],
                    'ticket.impact'                => $LANG['joblist'][30],
                    'ticket.priority'              => $LANG['joblist'][2],
                    'ticket.time'                  => $LANG['job'][20],
                    'ticket.costtime'              => $LANG['job'][40],
                    'ticket.costfixed'             => $LANG['job'][41],
                    'ticket.costmaterial'          => $LANG['job'][42],
                    'ticket.solution.type'         => $LANG['job'][48],
                    'ticket.solution.description'  => $LANG['jobresolution'][1],
                    'task.author'                  => $LANG['common'][37],
                    'task.isprivate'               => $LANG['common'][77],
                    'task.date'                    => $LANG['reports'][60],
                    'task.description'             => $LANG['joblist'][6],
                    'task.category'                => $LANG['common'][36],
                    'task.time'                    => $LANG['job'][20],
                    'task.planning.user'           => $LANG['common'][95],
                    'task.planning.begin'          => $LANG['search'][8],
                    'task.planning.end'            => $LANG['search'][9],
                    'task.planning.status'         => $LANG['joblist'][0],
                    'followup.date'                => $LANG['reports'][60],
                    'followup.isprivate'           => $LANG['common'][77],
                    'followup.author'              => $LANG['common'][37],
                    'followup.description'         => $LANG['joblist'][6],
                    'followup.requesttype'         => $LANG['job'][44],
                    'ticket.numberoffollowups'     => $LANG['mailing'][4],
                    'ticket.numberoftasks'         => $LANG['mailing'][122],
                    'ticket.nocategoryassigned'    => $LANG['mailing'][100],
                    'ticket.action'                => $LANG['mailing'][119],
                    'ticket.autoclose'             => $LANG['entity'][18],
                    'ticket.useremailnotification' => $LANG['job'][19],
                    'ticket.globalvalidation'      => $LANG['validation'][25]
                  );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }

     //Events specific for validation
     $tags = array('validation.author'            => $LANG['job'][4],
                   'validation.status'            => $LANG['validation'][28],
                   'validation.submissiondate'    => $LANG['validation'][3],
                   'validation.commentsubmission' => $LANG['validation'][5],
                   'validation.validationdate'    => $LANG['validation'][4],
                   'validation.validator'         => $LANG['validation'][21],
                   'validation.commentvalidation' => $LANG['validation'][6]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('validation')));
      }

      // Events for ticket satisfaction
      $tags = array('satisfaction.datebegin'           => $LANG['satisfaction'][6],
                    'satisfaction.dateanswered'        => $LANG['satisfaction'][4],
                    'satisfaction.satisfactionlevel'   => $LANG['satisfaction'][7],
                    'satisfaction.satisfactioncomment' => $LANG['satisfaction'][8]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'events' => array('satisfaction')));
      }

      $tags = array('satisfaction.type'  => $LANG['satisfaction'][9]." - ".
                                           $LANG['satisfaction'][10],);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => true,
                                   'lang'   => false,
                                   'events' => array('satisfaction')));
      }

      $tags = array('satisfaction.text' => $LANG['satisfaction'][12]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
                                   'label'  => $label,
                                   'value'  => false,
                                   'lang'   => true,
                                   'events' => array('satisfaction')));
      }

     //Foreach global tags
     $tags = array('followups'     => $LANG['mailing'][141],
                   'tasks'         => $LANG['mailing'][142],
                   'log'           => $LANG['mailing'][144],
                   'validation'    => $LANG['mailing'][143],
                   'linkedtickets' => $LANG['job'][55],
                   'authors'       => $LANG['job'][55]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'     => $tag,
                                   'label'   => $label,
                                   'value'   => false,
                                   'foreach' => true));
      }


      //Tags with just lang
      $tags = array('ticket.days'             => $LANG['stats'][31],
                    'ticket.linkedtickets'    => $LANG['job'][55],
                    'ticket.autoclosewarning' => $LANG['job'][54]." ? ".$LANG['stats'][31]);

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => false,
                                   'lang'  => true));
      }


      //Foreach tag for alertnotclosed
      $this->addTagToList(array('tag'     => 'tickets',
                                'label'   => $LANG['crontask'][15],
                                'value'   => false,
                                'foreach' => true,
                                'events'  => array('alertnotclosed')));

      //Tags without lang
      $tags = array('validation.submission.title' => $LANG['validation'][27],
                    'validation.answer.title' => $LANG['validation'][32],
                    'ticket.log.date'         => $LANG['mailing'][144]. ' : '.$LANG['common'][26],
                    'ticket.log.user'         => $LANG['mailing'][144]. ' : '.$LANG['common'][34],
                    'ticket.log.field'        => $LANG['mailing'][144]. ' : '.$LANG['event'][18],
                    'ticket.log.content'      => $LANG['mailing'][144]. ' : '.$LANG['event'][19],
                    'ticket.urlapprove'       => $LANG['document'][33].' '.$LANG['job'][51],
                    'ticket.urlvalidation'    => $LANG['document'][33].' '.$LANG['validation'][26],
                    'ticket.urlsatisfaction'  => $LANG['document'][33].' '.$LANG['satisfaction'][0],
                    'linkedticket.id'         => $LANG['job'][55]." - ".$LANG['common'][2],
                    'linkedticket.link'       => $LANG['job'][55]." - ".$LANG['setup'][620],
                    'linkedticket.url'        => $LANG['job'][55]." - ".$LANG['common'][94],
                    'linkedticket.title'      => $LANG['job'][55]." - ".$LANG['common'][16],
                    'linkedticket.content'    => $LANG['job'][55]." - ".$LANG['joblist'][6]
                   );

      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'   => $tag,
                                   'label' => $label,
                                   'value' => true,
                                   'lang'  => false));
      }

      //Tickets with a fixed set of values
      $status = Ticket::getAllStatusArray(false);
      $allowed_ticket = $allowed_validation = array();
      foreach ($status as $key => $value) {
         $allowed_ticket[] = $key;
      }
      $status = TicketValidation::getAllStatusArray(false,true);
      foreach ($status as $key => $value) {
         $allowed_validation[] = $key;
      }

      $tags = array('ticket.storestatus'          => array('text'           => $LANG['joblist'][36],
                                                           'allowed_values' => $allowed_ticket),
                    'validation.validationstatus' => array('text'           => $LANG['joblist'][36],
                                                           'allowed_values' => $allowed_validation));
      foreach ($tags as $tag => $label) {
         $this->addTagToList(array('tag'            => $tag,
                                   'label'          => $label['text'],
                                   'value'          => true,
                                   'lang'           => false,
                                   'allowed_values' => $label['allowed_values']));
      }

      asort($this->tag_descriptions);
   }

}
?>
