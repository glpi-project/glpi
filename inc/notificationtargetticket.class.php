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

   function getSpecificTargets($notifications_id,$itemtype,$data,$options=array()) {

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
            //Send to the author of the ticket
            case NOTIFICATION_AUTHOR:
               $target->getItemAuthorAddress($notifications_id);
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

   function getByItemUserAddress($notifications_id,$field) {
      global $DB;

      if (isset($this->obj->fields["items_id"])
                && $this->obj->fields["items_id"]>0
                && isset($this->obj->fields["itemtype"])
                && class_exists($this->obj->fields["itemtype"])) {

         $item= new $this->obj->fields["itemtype"]();
         if ($item->getFromDB($this->obj->fields["items_id"])) {
            if ($item->isField($field)) {

               $query = NotificationTarget::getDistinctUserSql()."
                        FROM `glpi_users`".
                        NotificationTargetTicket::getJoinProfileSql().
                       "WHERE `glpi_users`.`id` = '".
                        $item->getField($field)."'";
               foreach ($DB->request($query) as $data) {
                  $this->addToAddressesList($notifications_id,$data['email'],$data['lang']);
               }
            }
         }
      }
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
    * Get users for a specific profile
    */
   function getUsersAddressesByProfile($notifications_id,$profile_id) {
      global $DB;

      $query=NotificationTarget::getDistinctUserSql()."
              FROM `glpi_profiles_users`
              INNER JOIN `glpi_users` ON (`glpi_profiles_users`.`users_id`=`glpi_users`.`id`)
              INNER JOIN `glpi_profiles`
                             ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                                 AND `glpi_profiles`.`interface` = 'central'
                                 AND `glpi_profiles`.`show_full_ticket` = '1')
              WHERE `glpi_users`.`is_deleted`='0'
                 AND `glpi_profiles_users`.`profiles_id`='".$profile_id."' ".
                 getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                        $this->obj->fields['entities_id'],true);

      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($notifications_id,$data['email'], $data['lang']);
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
                    'add_followp' => $LANG['mailing'][10],
                    'add_task' => $LANG['job'][30],
                    'close' => $LANG['joblist'][33]);
   }

   /**
    * Get additionnals targets for Tickets
    */
   function getAdditionnalTargets() {
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

}
?>