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
class NotificationTargetReservationItem extends NotificationTarget {

   function getAddressesByTarget($notifications_id,$itemtype,$data,$options=array()) {

   //Look for all targets whose type is NOTIFICATION_ITEM_USER
   switch ($data['type']) {

      case NOTIFICATION_USER_TYPE:
         switch ($data['items_id']) {
           //Send to the author of the ticket
            case NOTIFICATION_AUTHOR:
               $target->getItemAuthorAddress($notifications_id);
            break;
      }

      //Send to all the users of a profile
      case NOTIFICATION_TICKET_ASSIGN_TECH_TYPE:
         $target->getUsersAddressesByProfile($notifications_id,$data['items_id']);
      break;
      }
   }


   /**
    * Get users emails by profile
    * @param notifications_id the notification ID
    * @param profiles_id the profile ID to get users emails
    * @return nothing
    */
   function getUsersAddressesByProfile($notifications_id,$profiles_id) {
      global $DB;

      $item = NotificationTargetReservationItem::getReservationItem();

      $ri=new ReservationItem();
      if ($item) {
         $query=NotificationTargetTicket::getDistinctUserSql()."
                 FROM `glpi_profiles_users`
                 INNER JOIN `glpi_users`
                 ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                 WHERE `glpi_profiles_users`.`profiles_id`='".$profiles_id."'".
                    getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                     $item->getEntityID(),true);
                 if ($result2= $DB->query($query)) {
                    if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           $this->addToAddressesList($notifications_id,$row['email'],$row['lang']);
                           }
                        }
                 }
      }
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

   //Overload function in NotificationTarget because here it's needed to search into ReservationItem
   //And not directly into the $this->obj
   function getEntityAdminAddress($notifications_id) {
      global $DB;

      //Get reserved object
      $entity=-1;
      $item = NotificationTargetReservationItem::getReservationItem();
      if ($item) {
         //Get object's entity
         $entity = $item->getEntityID();
      }

      if ($entity>=0) {
         $query2 = "SELECT `admin_email` AS email
                    FROM `glpi_entitydatas`
                    WHERE `entities_id` = '".$entity."'";
         if ($result2 = $DB->query($query2)) {
            if ($DB->numrows($result2)==1) {
               $row = $DB->fetch_array($result2);
               $this->addToAddressesList($notifications_id,$row["email"]);
            }
         }
      }
   }

   static function getReservationItem() {
      $item = null;
      $ri=new ReservationItem();
      if ($ri->getFromDB($this->obj->fields["reservationitems_id"])) {
         if (class_exists($ri->fields['itemtype'])) {
            $item = new $ri->fields['itemtype'];
            if ($item->getFromDB($ri->fields['items_id'])) {
               return $item;
            }
         }
      }
      return $item;
   }

   function getEvents() {
      global $LANG;
      return array ('new' => $LANG['mailing'][19],
                    'update'=> $LANG['mailing'][23],
                    'delete'=>$LANG['mailing'][29]);
   }

   function getAdditionnalTargets() {
      global $LANG;
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" .
             NOTIFICATION_TICKET_SUPERVISOR_ASSIGN_GROUP] =
                                                      $LANG['common'][64]." ".$LANG['setup'][248];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" .
             NOTIFICATION_TICKET_SUPERVISOR_REQUESTER_GROUP] =
                                                      $LANG['common'][64]." ".$LANG['setup'][249];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_ITEM_TECH_IN_CHARGE] =
                                                      $LANG['common'][10];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_AUTHOR] =
                                                      $LANG['job'][4];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_ITEM_USER] =
                                                      $LANG['common'][34] . " " .$LANG['common'][1];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . ASSIGN_GROUP_MAILING] =
                                                      $LANG['setup'][248];
   }
}
?>