<?php
/*
 * @version $Id: notification.class.php 10030 2010-01-05 11:11:22Z moyo $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Class NotificationTarget
class NotificationTargetTicket extends NotificationTarget {

   function __construct($entity='', $object = null) {
      parent::__construct($entity, $object);
      if ($object != null) {
         $this->getObjectItem();
      }
   }

   function getSpecificTargets($notifications_id,$data,$options=array()) {

   if (isset($options['sendprivate']) && $options['sendprivate'] == true) {
      $sendprivate = true;
   }
   else {
      $sendprivate = false;
   }

   //Look for all targets whose type is NOTIFICATION_ITEM_USER
   switch ($data['type']) {

      case NOTIFICATION_USER_TYPE:
         switch ($data['items_id']) {
            case NOTIFICATION_TICKET_ASSIGN_TECH:
               $this->getTicketAssignTechnicianAddress($notifications_id);
            break;
            //Send to the group in charge of the ticket supervisor
            case NOTIFICATION_TICKET_SUPERVISOR_ASSIGN_GROUP :
               $this->getGroupSupervisorAddress($notifications_id,true);
            break;
            //Send to the user who's got the issue
            case NOTIFICATION_TICKET_RECIPIENT :
               $this->getReceipientAddress($notifications_id);
            break;
            //Send to the supervisor of the requester's group
            case NOTIFICATION_TICKET_SUPERVISOR_REQUESTER_GROUP :
               $this->getGroupSupervisorAddress($notifications_id,false);
            break;
            //Send to the technician previously in charge of the ticket (before reassignation)
            case NOTIFICATION_TICKET_OLD_TECH_IN_CHARGE :
               $this->getTicketOldAssignTechnicianAddress($notifications_id);
            break;
            //Assign to a supplier
            case NOTIFICATION_TICKET_SUPPLIER :
               $this->getTicketSupplierAddress($notifications_id);
            break;
         }
      //Send to all the users of a profile
      case NOTIFICATION_PROFILE_TYPE:
         $this->getUsersAddressesByProfile($notifications_id,$data['items_id']);
      break;

      }
   }

   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem() {
      if ($this->obj->getField('itemtype') != '') {
         $itemtype = $this->obj->getField('itemtype');
         $item = new  $itemtype ();
         $item->getFromDB($this->obj->getField('items_id'));
         $this->target_object = $item;
      }
   }

   function getTicketAssignTechnicianAddress ($notifications_id) {
      return $this->getUserByField ($notifications_id, "users_id_assign");
   }

   function getTicketOldAssignTechnicianAddress ($notifications_id) {
      return $this->getUserByField ($notifications_id, "_old_assign");
   }

   //Get receipient
   function getReceipientAddress($notifications_id) {
      return $this->getUserByField ($notifications_id, "users_id_recipient");
  }

   /**
    * Get supplier related to the ticket
    */
   function getTicketSupplierAddress($notifications_id, $sendprivate=true) {
      global $DB;

      if (!$sendprivate && isset($ths->obj->fields["suppliers_id_assign"])
          && $this->obj->fields["suppliers_id_assign"]>0) {

         $query = "SELECT DISTINCT `glpi_suppliers`.`email` AS email
                   FROM `glpi_suppliers`
                   WHERE `glpi_suppliers`.`id` = '".
                          $ticket->fields["suppliers_id_assign"]."'";
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($notifications_id,$data['email']);
         }
      }
   }

   /**
    * Get supervisor of a group (works for request group or assigned group)
    */
   function getGroupSupervisorAddress ($notifications_id, $assign=true) {
      global $DB;

      $group_field = ($assign?"groups_id_assign":"groups_id");

      if (isset($this->obj->fields[$group_field])
                && $this->obj->fields[$group_field]>0) {

         $query = NotificationTarget::getDistinctUserSql().
                   "FROM `glpi_groups`
                    LEFT JOIN `glpi_users`
                    ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)".
                   NotificationTargetTicket::getJoinProfileSql()."
                    WHERE `glpi_groups`.`id` = '".$this->obj->fields[$group_field]."'";
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($notifications_id,$data['email'], $data['lang']);
         }
      }
   }

   /**
    *Get events related to tickets
    */
   function getEvents() {
      global $LANG;
      return array ('new' => $LANG['mailing'][9],
                    'update' => $LANG['mailing'][30],
                    'solved' => $LANG['jobresolution'][2],
                    'add_followup' => $LANG['mailing'][10],
                    'add_task' => $LANG['job'][30],
                    'close' => $LANG['joblist'][33]);
   }

   /**
    * Get additionnals targets for Tickets
    */
   function getAdditionalTargets() {
      global $LANG;

      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" .
             NOTIFICATION_TICKET_SUPERVISOR_ASSIGN_GROUP] = $LANG['common'][64]." ".$LANG['setup'][248];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" .
             NOTIFICATION_TICKET_SUPERVISOR_REQUESTER_GROUP] = $LANG['common'][64]." ".$LANG['setup'][249];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_ITEM_TECH_IN_CHARGE] = $LANG['common'][10];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_AUTHOR] = $LANG['job'][4];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_TICKET_RECIPIENT] = $LANG['job'][3];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_ITEM_USER] = $LANG['common'][34] . " " .
                                                                $LANG['common'][1];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_TICKET_ASSIGN_TECH] = $LANG['setup'][239];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_TICKET_SUPPLIER] = $LANG['financial'][26];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . ASSIGN_GROUP_MAILING] = $LANG['setup'][248];

   }

   static function getJoinProfileSql() {
      return " INNER JOIN `glpi_profiles_users`
                        ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`".
                            getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                       $this->job->fields['entities_id'],true).")
                    INNER JOIN `glpi_profiles`
                        ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                            AND `glpi_profiles`.`interface` = 'central'
                            AND `glpi_profiles`.`show_full_ticket` = '1') ";
   }

   /**
    * Get all data needed for template processing
    */
   function getDatasForTemplate($event) {
      global $DB, $LANG, $CFG_GLPI;

      $tpldatas = array();

      //----------- Ticket infos -------------- //

      $fields = array ('ticket.name'=> 'name',
                       'ticket.id'=>'id',
                       'ticket.content'=>'content',
                       'costfixed'=>'cost_fixed',
                       'costmaterial'=>'cost_material',
                       'useremail'=>'user_email');

      foreach ($fields as $table_field => $name) {
      	$tpldatas['##'.$name.'##'] = $table_field;
      }

      $tpldatas['##ticket.url##'] = "<a href=\"".$CFG_GLPI["url_base"]."/index.php?redirect=ticket_".
                                    $this->obj->getField("id")."\">".$CFG_GLPI["url_base"].
                                    "/index.php?redirect=ticket_".
                                    $this->obj->getField("id")."\"</a>";

      $tpldatas['##ticket.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                             $this->obj->getField('entities_id'));
      $events = $this->getEvents();
      $tpldatas['##ticket.action##'] = $events[$event];
      $tpldatas['##ticket.status##'] = Ticket::getStatus($this->obj->getField('content'));
      $tpldatas['##ticket.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                  $this->obj->getField('requesttypes_id'));

      $tpldatas['##ticket.urgency##'] = Ticket::getUrgencyName($this->obj->getField('urgency'));
      $tpldatas['##ticket.impact##'] = Ticket::getImpactName($this->obj->getField('impact'));
      $tpldatas['##ticket.impact##'] = Ticket::getPriorityName($this->obj->getField('priority'));
      $tpldatas['##ticket.time##'] = convDateTime($this->obj->getField('realtime'));
      $tpldatas['##ticket.costtime##'] = $this->obj->getField('cost_time');

      if ($this->obj->getField('ticketcategories_id')) {
         $tpldatas['##ticket.category##'] = Dropdown::getDropdownName('glpi_ticketcategories',
                                                                  $this->obj->getField('ticketcategories_id'));
      }
      else {
         $tpldatas['##ticket.category##'] = '';
      }

      if ($this->obj->getField('users_id')) {
         $user = new User;
         $user->getFromDB($this->obj->getField('users_id'));
         $tpldatas['##ticket.author##'] = $user->getField('id');
         $tpldatas['##ticket.author.name##'] = $user->getField('name');
         if ($user->getField('locations_id')) {
            $tpldatas['##ticket.author.location##'] = Dropdown::getDropdownName('glpi_locations',
                                                                            $user->getField('locations_id'));
         }
         else {
            $tpldatas['##ticket.author.location##'] = '';
         }
         $tpldatas['##ticket.author.phone##'] = $user->getField('phone');
         $tpldatas['##ticket.author.phone2##'] = $user->getField('phone2');
      }

      if ($this->obj->getField('users_id_recipient')) {
         $tpldatas['##ticket.openbyuser##'] = Dropdown::getDropdownName('glpi_users',
                                                                    $this->obj->getField('users_id_recipient'));
      }
      else {
         $tpldatas['##ticket.openbyuser##'] = '';
      }

      if ($this->obj->getField('users_id_assign')) {
         $tpldatas['##ticket.assigntouser##'] = Dropdown::getDropdownName('glpi_users',
                                                                    $this->obj->getField('users_id_assign'));
      }
      else {
         $tpldatas['##ticket.assigntouser##'] = '';
      }
      if ($this->obj->getField('suppliers_id_assign')) {
         $tpldatas['##ticket.assigntosupplier##'] = Dropdown::getDropdownName('glpi_suppliers',
                                                                    $this->obj->getField('suppliers_id_assign'));
      }
      else {
         $tpldatas['##ticket.assigntosupplier##'] = '';
      }

      if ($this->obj->getField('groups_id')) {
         $tpldatas['##ticket.group##'] = Dropdown::getDropdownName('glpi_groups',
                                                                    $this->obj->getField('groups_id'));
      }
      else {
         $tpldatas['##ticket.group##'] = '';
      }

      if ($this->obj->getField('itemtype') != '') {
         $itemtype = $this->obj->getField('itemtype');
         $item = new  $itemtype ();
         $item->getFromDB($this->obj->getField('items_id'));
         $tpldatas['##ticket.itemtype##'] = $item->getTypeName();
         $tpldatas['##ticket.item##'] = $item->getField('name');
      }
      else {
         $tpldatas['##ticket.itemtype##'] = '';
         $tpldatas['##ticket.item##'] = '';
      }

      if ($this->obj->getField('ticketsolutiontypes_id')) {
         $tpldatas['##ticket.solution.type##'] = Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                               $this->obj->getField('ticketsolutiontypes_id'));
         $tpldatas['##ticket.solution.description##'] = $this->obj->getField('solution');
      }

      //Task infos
      $tasks = getAllDatasFromTable('glpi_tickettasks',
                                    "`tickets_id`='".$this->obj->getField('id')."'");
      foreach ($tasks as $task) {
         $tmp = array();
         $tmp['##task.author##'] =  Dropdown::getDropdownName('glpi_users',
                                                          $task['users_id']);
         $tmp['##task.category##'] = Dropdown::getDropdownName('glpi_taskcategories',
                                                           $task['taskcategories_id']);
         $tmp['##task.date##'] = convDateTime($task['date']);
         $tmp['##task.description##'] = $task['content'];
         $tmp['##task.time##'] = $task['realtime'];
         $tpldatas['tasks'][] = $tmp;
      }
      //Followup infos
      $followups = getAllDatasFromTable('glpi_ticketfollowups',
                                    "`tickets_id`='".$this->obj->getField('id')."'");
      foreach ($followups as $followup) {
         $tmp = array();
         if ($followup['users_id']) {}
         $tmp['##followup.author##'] =  Dropdown::getDropdownName('glpi_users',
                                                          $followup['users_id']);
         $tmp['##followup.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                  $followup['requesttypes_id']);
         $tmp['##followup.date##'] = convDateTime($followup['date']);
         $tmp['##followup.content##'] = $followup['content'];
         $tpldatas['followup'][] = $tmp;
      }

      return  $tpldatas;
   }

   /**
    * Get users emails by profile
    * @param notifications_id the notification ID
    * @param profiles_id the profile ID to get users emails
    * @return nothing
    */
   function getUsersAddressesByProfile($notifications_id,$profiles_id) {
      global $DB;

      if ($this->target_object) {
         $query=NotificationTargetTicket::getDistinctUserSql()."
                 FROM `glpi_profiles_users`
                 INNER JOIN `glpi_users`
                 ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                 WHERE `glpi_profiles_users`.`profiles_id`='".$profiles_id."'".
                    getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                     $this->obj->getEntityID(),true);
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($notifications_id,$data['email'],$data['lang']);
         }
      }
   }

}
?>