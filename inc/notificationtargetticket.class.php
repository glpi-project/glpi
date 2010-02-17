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

   function getSubjectPrefix() {
      return sprintf("[GLPI #%07d] ", $this->obj->getField('id'));
   }

   function getSpecificTargets($data,$options=array()) {

   if (isset($options['sendprivate']) && $options['sendprivate'] == true) {
      $sendprivate = true;
   }
   else {
      $sendprivate = false;
   }

   //Look for all targets whose type is Notification::ITEM_USER
   switch ($data['type']) {

      case Notification::USER_TYPE:
         switch ($data['items_id']) {
            case Notification::TICKET_ASSIGN_TECH:
               $this->getTicketAssignTechnicianAddress();
            break;
            //Send to the group in charge of the ticket supervisor
            case Notification::TICKET_SUPERVISOR_ASSIGN_GROUP :
               $this->getGroupSupervisorAddress(true);
            break;
            //Send to the user who's got the issue
            case Notification::TICKET_RECIPIENT :
               $this->getReceipientAddress();
            break;
            //Send to the supervisor of the requester's group
            case Notification::TICKET_SUPERVISOR_REQUESTER_GROUP :
               $this->getGroupSupervisorAddress(false);
            break;
            //Send to the technician previously in charge of the ticket (before reassignation)
            case Notification::TICKET_OLD_TECH_IN_CHARGE :
               $this->getTicketOldAssignTechnicianAddress();
            break;
            //Assign to a supplier
            case Notification::TICKET_SUPPLIER :
               $this->getTicketSupplierAddress();
            break;
         }
      //Send to all the users of a profile
      case Notification::PROFILE_TYPE:
         $this->getUsersAddressesByProfile($data['items_id']);
      break;
      }
   }

   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem() {
      if ($itemtype=$this->obj->getField('itemtype') && !empty($itemtype)) {
         $item = new  $itemtype ();
         $item->getFromDB($this->obj->getField('items_id'));
         $this->target_object = $item;
      }
   }

   function getTicketAssignTechnicianAddress () {
      return $this->getUserByField ("users_id_assign");
   }

   function getTicketOldAssignTechnicianAddress () {
      return $this->getUserByField ("_old_assign");
   }

   //Get receipient
   function getReceipientAddress() {
      return $this->getUserByField ("users_id_recipient");
  }

   /**
    * Get supplier related to the ticket
    */
   function getTicketSupplierAddress($sendprivate=true) {
      global $DB;

      if (!$sendprivate && isset($ths->obj->fields["suppliers_id_assign"])
          && $this->obj->fields["suppliers_id_assign"]>0) {

         $query = "SELECT DISTINCT `glpi_suppliers`.`email` AS email
                   FROM `glpi_suppliers`
                   WHERE `glpi_suppliers`.`id` = '".
                          $ticket->fields["suppliers_id_assign"]."'";
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data['email']);
         }
      }
   }

   /**
    * Get supervisor of a group (works for request group or assigned group)
    */
   function getGroupSupervisorAddress ($assign=true) {
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
            $this->addToAddressesList($data['email'], $data['lang']);
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
                    'solved' => $LANG['mailing'][123],
                    'add_followup' => $LANG['mailing'][10],
                    'add_task' => $LANG['job'][49],
                    'closed' => $LANG['mailing'][127]);
   }

   /**
    * Get additionnals targets for Tickets
    */
   function getAdditionalTargets() {
      global $LANG;

      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_SUPERVISOR_ASSIGN_GROUP] =
                                                      $LANG['common'][64]." ".$LANG['setup'][248];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_SUPERVISOR_REQUESTER_GROUP] =
                                                      $LANG['common'][64]." ".$LANG['setup'][249];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::ITEM_TECH_IN_CHARGE] = $LANG['common'][10];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::AUTHOR] = $LANG['job'][4];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_RECIPIENT] = $LANG['job'][3];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::ITEM_USER] = $LANG['common'][34] . " " .$LANG['common'][1];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_ASSIGN_TECH] = $LANG['setup'][239];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_SUPPLIER] = $LANG['financial'][26];
      $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::GROUP_MAILING] = $LANG['setup'][248];
     $this->notification_targets[Notification::USER_TYPE . "_" .
             Notification::TICKET_REQUESTER_GROUP] = $LANG['setup'][249];
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
   function getDatasForTemplate($event,$tpldata = array(), $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      //----------- Ticket infos -------------- //

      $fields = array ('ticket.title'=> 'name',
                       'ticket.content'=>'content',
                       'ticket.costfixed'=>'cost_fixed',
                       'ticket.costmaterial'=>'cost_material',
                       'ticket.useremail'=>'user_email');

      foreach ($fields as $tag => $table_field) {
         $tpldata['##'.$tag.'##'] = $this->obj->getField($table_field);
      }
      $tpldata['##ticket.id##'] = sprintf("%07d",$this->obj->getField("id"));
      $tpldata['##ticket.url##'] = urldecode($CFG_GLPI["url_base"]."/index.php?redirect=ticket_".
                                    $this->obj->getField("id"));

      $tpldata['##ticket.entity##'] = Dropdown::getDropdownName('glpi_entities',
                                                             $this->obj->getField('entities_id'));
      $events = $this->getEvents();
      $tpldata['##ticket.action##'] = $events[$event];
      $tpldata['##ticket.status##'] = Ticket::getStatus($this->obj->getField('status'));
      $tpldata['##ticket.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                  $this->obj->getField('requesttypes_id'));

      $tpldata['##ticket.urgency##'] = Ticket::getUrgencyName($this->obj->getField('urgency'));
      $tpldata['##ticket.impact##'] = Ticket::getImpactName($this->obj->getField('impact'));
      $tpldata['##ticket.priority##'] = Ticket::getPriorityName($this->obj->getField('priority'));
      $tpldata['##ticket.time##'] = convDateTime($this->obj->getField('realtime'));
      $tpldata['##ticket.costtime##'] = $this->obj->getField('cost_time');

     $tpldata['##ticket.creationdate##'] = convDateTime($this->obj->getField('date'));
     $tpldata['##ticket.closedate##'] = convDateTime($this->obj->getField('closedate'));

      if ($this->obj->getField('ticketcategories_id')) {
         $tpldata['##ticket.category##'] = Dropdown::getDropdownName('glpi_ticketcategories',
                                                                  $this->obj->getField('ticketcategories_id'));
      }
      else {
         $tpldata['##ticket.category##'] = '';
      }

      if ($this->obj->getField('users_id')) {
         $user = new User;
         $user->getFromDB($this->obj->getField('users_id'));
         $tpldata['##ticket.author##'] = $user->getField('id');
         $tpldata['##ticket.author.name##'] = $user->getField('name');
         if ($user->getField('locations_id')) {
            $tpldata['##ticket.author.location##'] = Dropdown::getDropdownName('glpi_locations',
                                                                            $user->getField('locations_id'));
         }
         else {
            $tpldata['##ticket.author.location##'] = '';
         }
         $tpldata['##ticket.author.phone##'] = $user->getField('phone');
         $tpldata['##ticket.author.phone2##'] = $user->getField('phone2');
      }

      if ($this->obj->getField('users_id_recipient')) {
         $tpldata['##ticket.openbyuser##'] = Dropdown::getDropdownName('glpi_users',
                                                                    $this->obj->getField('users_id_recipient'));
      }
      else {
         $tpldata['##ticket.openbyuser##'] = '';
      }

      if ($this->obj->getField('users_id_assign')) {
         $tpldata['##ticket.assigntouser##'] = Dropdown::getDropdownName('glpi_users',
                                                                    $this->obj->getField('users_id_assign'));
      }
      else {
         $tpldata['##ticket.assigntouser##'] = '';
      }
      if ($this->obj->getField('suppliers_id_assign')) {
         $tpldata['##ticket.assigntosupplier##'] = Dropdown::getDropdownName('glpi_suppliers',
                                                                    $this->obj->getField('suppliers_id_assign'));
      }
      else {
         $tpldata['##ticket.assigntosupplier##'] = '';
      }

      if ($this->obj->getField('groups_id')) {
         $tpldata['##ticket.group##'] = Dropdown::getDropdownName('glpi_groups',
                                                                    $this->obj->getField('groups_id'));
      }
      else {
         $tpldata['##ticket.group##'] = '';
      }

      if ($this->obj->getField('groups_id_assign')) {
         $tpldata['##ticket.assigntogroup##'] = Dropdown::getDropdownName('glpi_groups',
                                                                    $this->obj->getField('groups_id_assign'));
      }
      else {
         $tpldata['##ticket.group##'] = '';
      }

      //Hardware
      if ($this->target_object != null) {
         $tpldata['##ticket.itemtype##'] = $this->target_object->getTypeName();
         $tpldata['##ticket.item.name##'] = $this->target_object->getField('name');

         if ($this->target_object->isField('serial')) {
            $tpldata['##ticket.item.serial##'] = $this->target_object->getField('serial');
         }
         if ($this->target_object->isField('otherserial')) {
            $tpldata['##ticket.item.otherserial##'] = $this->target_object->getField('otherserial');
         }

         if ($this->target_object->isField('location')) {
            $tpldata['##ticket.item.location##'] =
                                             Dropdown::getDropdownName('glpi_locations',
                                                                   $user->getField('locations_id'));
         }
         $modeltable = getSingular($this->getTable())."models";
         $modelfield = getForeignKeyFieldForTable($modeltable);
         if ($this->target_object->isField($modelfield)) {
            $tpldata['##ticket.item.model##'] = $this->target_object->getField($modelfield);
         }
      }
      else {
         $tpldata['##ticket.itemtype##'] = '';
         $tpldata['##ticket.item.name##'] = '';
         $tpldata['##ticket.item.serial##'] = '';
         $tpldata['##ticket.item.otherserial##'] = '';
         $tpldata['##ticket.item.location##'] = '';
      }

      if ($this->obj->getField('ticketsolutiontypes_id')) {
         $tpldata['##ticket.solution.type##'] = Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                               $this->obj->getField('ticketsolutiontypes_id'));
         $tpldata['##ticket.solution.description##'] = $this->obj->getField('solution');
      }

      $restrict = "`tickets_id`='".$this->obj->getField('id')."'";
      if (!isset($options['sendprivate']) || !$options['sendprivate']) {
         $restrict.=" AND `is_private`='0'";
      }
      $restrict.=" ORDER BY `date` DESC";

      //Task infos
      $tasks = getAllDatasFromTable('glpi_tickettasks',$restrict);
      foreach ($tasks as $task) {
         $tmp = array();
         $tmp['##task.isprivate##'] =  Dropdown::getYesNo($task['is_private']);
         $tmp['##task.author##'] =  Dropdown::getDropdownName('glpi_users',
                                                          $task['users_id']);
         $tmp['##task.category##'] = Dropdown::getDropdownName('glpi_taskcategories',
                                                           $task['taskcategories_id']);
         $tmp['##task.date##'] = convDateTime($task['date']);
         $tmp['##task.description##'] = $task['content'];
         $tmp['##task.time##'] = $task['realtime'];
         $tpldata['tasks'][] = $tmp;
      }
      if (!empty($tpldata['tasks'])) {
         $tpldata['##ticket.numberoftasks##'] = count($tpldata['tasks']);
      }
      else {
         $tpldata['##ticket.numberoftasks##'] = 0;
      }

      //Followup infos
      $followups = getAllDatasFromTable('glpi_ticketfollowups',$restrict);
      foreach ($followups as $followup) {
         $tmp = array();
         $tmp['##followup.isprivate##'] =  Dropdown::getYesNo($followup['is_private']);
         $tmp['##followup.author##'] =  Dropdown::getDropdownName('glpi_users',
                                                          $followup['users_id']);
         $tmp['##followup.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                  $followup['requesttypes_id']);
         $tmp['##followup.date##'] = convDateTime($followup['date']);
         $tmp['##followup.description##'] = $followup['content'];
         $tpldata['followups'][] = $tmp;
      }
      if (isset($tpldata['followups'])) {
         $tpldata['##ticket.numberoffollowups##'] = count($tpldata['followups']);
      }
      else {
         $tpldata['##ticket.numberoffollowups##'] = 0;
      }

      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($this->obj,0,$CFG_GLPI['list_limit_max']) as $data) {
         $tmp = array();
         $tmp['##ticket.log.date##'] = $data['date_mod'];
         $tmp['##ticket.log.user##'] = $data['user_name'];
         $tmp['##ticket.log.field##'] = $data['field'];
         $tmp['##ticket.log.content##'] = $data['change'];
         $tpldata['log'][] = $tmp;
      }

      if (isset($tpldata['log'])) {
         $tpldata['##ticket.numberoflogs##'] = count($tpldata['log']);
      }
      else {
         $tpldata['##ticket.numberoflogs##'] = 0;
      }

      //Locales
      $labels = array ('##lang.ticket.id##'=>$LANG['common'][2],
                       '##lang.ticket.title##'=>$LANG['common'][16],
                       '##lang.ticket.url##'=>'URL',
                       '##lang.ticket.entity##' => $LANG['entity'][0],
                       '##lang.ticket.category##' =>$LANG['common'][36],
                       '##lang.ticket.content##' => $LANG['joblist'][6],
                       '##lang.ticket.description##' => $LANG['mailing'][5],
                       '##lang.ticket.status##'=> $LANG['joblist'][0],
                       '##lang.ticket.creationdate##' => $LANG['reports'][60],
                       '##lang.ticket.closedate##' => $LANG['reports'][61],
                       '##lang.ticket.requesttype##' => $LANG['job'][44],
                       '##lang.ticket.author##' => $LANG['common'][2].' '.$LANG['job'][4],
                       '##lang.ticket.author.name##' =>$LANG['job'][4],
                       '##lang.ticket.author.location##' =>$LANG['common'][15],
                       '##lang.ticket.author.phone##' =>$LANG['help'][35],
                       '##lang.ticket.openbyuser##' =>$LANG['job'][3],
                       '##lang.ticket.group##' =>$LANG['common'][35],
                       '##lang.ticket.assigntouser##' =>$LANG['job'][5]." - ".$LANG['job'][6],
                       '##lang.ticket.assigntogroup##' =>$LANG['job'][5]." - ".$LANG['common'][35],
                       '##lang.ticket.assigntosupplier##' =>$LANG['job'][5]." - ".$LANG['financial'][26],
                       '##lang.ticket.itemtype##' =>$LANG['reports'][12],
                       '##lang.ticket.item.name##' =>$LANG['financial'][104],
                       '##lang.ticket.item.serial##' =>$LANG['common'][19],
                       '##lang.ticket.item.otherserial##' =>$LANG['common'][20],
                       '##lang.ticket.item.location##' =>$LANG['common'][15],
                       '##lang.ticket.item.model##' =>$LANG['common'][22],
                       '##lang.ticket.urgency##' =>$LANG['joblist'][29],
                       '##lang.ticket.impact##' =>$LANG['joblist'][30],
                       '##lang.ticket.priority##' =>$LANG['joblist'][2],
                       '##lang.ticket.time##' =>$LANG['job'][20],
                       '##lang.ticket.costtime##' =>$LANG['job'][40],
                       '##lang.ticket.costfixed##' =>$LANG['job'][41],
                       '##lang.ticket.costmaterial##' =>$LANG['job'][42],
                       '##lang.ticket.solution.type##' =>$LANG['job'][48],
                       '##lang.ticket.solution.comment##' =>$LANG['common'][25],
                       '##lang.ticket.solution.name##' =>$LANG['jobresolution'][1],
                       '##lang.task.author##' =>$LANG['job'][4],
                       '##lang.task.isprivate##' =>$LANG['common'][77],
                       '##lang.task.date##' =>$LANG['reports'][60],
                       '##lang.task.description##' =>$LANG['joblist'][6],
                       '##lang.task.category##' =>$LANG['common'][36],
                       '##lang.task.time##' =>$LANG['job'][20],
                       '##lang.followup.date##' =>$LANG['reports'][60],
                       '##lang.followup.isprivate##' =>$LANG['common'][77],
                       '##lang.followup.author##' =>$LANG['job'][4],
                       '##lang.followup.description##' =>$LANG['joblist'][6],
                       '##lang.followup.requesttype##' =>$LANG['job'][44],
                       '##lang.ticket.numberoffollowups##' =>$LANG['mailing'][4],
                       '##lang.ticket.numberoftasks##' =>$LANG['mailing'][122],
                       '##lang.ticket.nocategoryassigned##' => $LANG['mailing'][100],
                       );
      foreach ($labels as $tag => $label) {
         $tpldata[$tag] = $label;
      }
      return  $tpldata;
   }

   /**
    * Get users emails by profile
    * @param notifications_id the notification ID
    * @param profiles_id the profile ID to get users emails
    * @return nothing
    */
/*
   function getUsersAddressesByProfile($profiles_id) {
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
            $this->addToAddressesList($data['email'],$data['lang']);
         }
      }
   }
*/
}
?>