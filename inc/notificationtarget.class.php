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
class NotificationTarget extends CommonDBTM {

   //Indicates that the object which raises the event is the object in which to look for
   //users, technicians and so on
   //False for items like Reservation, CartridgeItem, ConsumableItem
   var $real_object = true;

   var $notification_targets = array();

   // From CommonDBTM
   public $dohistory = true;

   //Array to store emails by notification
   var $targets = array();

   var $entity = '';

   //Object which raises the notification event
   var $obj = null;

   function __construct($entity='', $object = null) {
      if (!$entity == '') {
         $this->entity = $_SESSION['glpiactive_entity'];
      }
      else {
         $this->entity = $entity;
      }
      $this->obj = $object;
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['mailing'][113];
   }

   /**
    * Get a notificationtarget class by giving the object which raises the event
    * @param item the object which raises the event
    * @return a notificationtarget class or false
    */
   static function getInstance($item) {
      $name = 'NotificationTarget'.$item->getType();
      if (class_exists($name)) {
         return new $name (($item->isField('entities_id')?$item->getField('entities_id'):0),
                           $item);
      }
      else {
         return false;
      }
   }

   /**
    * Get a notificationtarget class by giving an itemtype
    * @param itemtype the itemtype of the object which raises the event
    * @return a notificationtarget class or false
    */
   static function getInstanceByType($itemtype) {
      $name = 'NotificationTarget'.$itemtype;
      if (class_exists($name)) {
         return new $name ();
      }
      else {
         return false;
      }
   }

   function showForm($target, $notifications_id) {
      global $DB, $LANG;

      if (!haveRight("notification", "r")) {
         return false;
      }

      $notification = new Notification;
      if ($notifications_id > 0) {
         $notification->check($notifications_id,'r');
      } else {
         // Create item
         $notification->check(-1,'w');
      }

      $notification->getFromDB($notifications_id);

      //$this->showAddNew($notification);

      echo "<div class='center'>";

      echo "<form name='notificationtargets_form' id='notificationtargets_form'
             method='post' action=' ";
      echo getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['mailing'][121] . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<input type='hidden' name='notifications_id' value='$notifications_id'>";
      $this->showNotificationTargets($notification);
      echo "</tr>";
      echo "</table></form></div>";
   }

   function dropdownTargets($used = array()) {
      Dropdown::showFromArray('type', $this->notification_targets);
   }

   function showAddNew(Notification $notification) {
      global $LANG;
      $notifications_id = $notification->fields['id'];
      $this->getNotficationTargets($_SESSION['glpiactive_entity']);

      $notification = new Notification;
      $canedit = $notification->can($notifications_id,'w');

      echo "<div class='center'>";

      echo "<form name='add_notificationtargets_form' id='add_notificationtargets_form'
             method='post' action=' ";
      echo getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>" . $LANG['mailing'][121] . "</th></tr>";
      echo "<tr class='tab_bg_2'><td>";
      echo "<input type='hidden' name='notifications_id' value='$notifications_id'>";
      NotificationTarget::dropdownTargets();
      echo "</td></tr>";
      echo "</table></form></div>";
   }

   /**
    * Display notification targets
    * @param notification the Notification object
    */
   function showNotificationTargets(Notification $notification) {
      global $LANG, $DB;
      $notifications_id = $notification->fields['id'];
      $this->getNotficationTargets($_SESSION['glpiactive_entity']);

      $notification = new Notification;
      $canedit = $notification->can($notifications_id,'w');

      $options="";
      // Get User mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id` , `glpi_notificationtargets`.`id`
                FROM `glpi_notificationtargets`
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . NOTIFICATION_USER_TYPE . "'
                ORDER BY `glpi_notificationtargets`.`items_id`";
      foreach ($DB->request($query) as $data) {
          if (isset($this->notification_targets[NOTIFICATION_USER_TYPE."_".$data["items_id"]])) {
            unset($this->notification_targets[NOTIFICATION_USER_TYPE."_".$data["items_id"]]);
         }
         switch ($data["items_id"]) {
            case NOTIFICATION_GLOBAL_ADMINISTRATOR :
               $name = $LANG['setup'][237];
            break;

            case NOTIFICATION_ENTITY_ADMINISTRATOR :
               $name = $LANG['setup'][237]." ".$LANG['entity'][0];
            break;

            case NOTIFICATION_TICKET_ASSIGN_TECH :
               $name = $LANG['setup'][239];
            break;

            case NOTIFICATION_AUTHOR :
               $name = $LANG['job'][4];
            break;

            case NOTIFICATION_ITEM_USER :
               $name = $LANG['common'][34] . " " . $LANG['common'][1];
            break;

            case NOTIFICATION_TICKET_OLD_TECH_IN_CHARGE :
               $name = $LANG['setup'][236];
            break;

            case NOTIFICATION_ITEM_TECH_IN_CHARGE :
               $name = $LANG['common'][10];
            break;

            case NOTIFICATION_TICKET_RECIPIENT :
               $name = $LANG['job'][3];
            break;

            case NOTIFICATION_TICKET_SUPPLIER :
               $name = $LANG['financial'][26];
            break;

            case ASSIGN_GROUP_MAILING :
               $name = $LANG['setup'][248];
            break;

            case NOTIFICATION_TICKET_SUPERVISOR_ASSIGN_GROUP :
                  $name = $LANG['common'][64]." ".$LANG['setup'][248];
            break;

            case NOTIFICATION_TICKET_SUPERVISOR_REQUESTER_GROUP :
               $name = $LANG['common'][64]." ".$LANG['setup'][249];
            break;

             default :
               $name="&nbsp;";
            break;
         }
         $options.= "<option value='" . $data["id"] . "'>" . $name . "</option>";

      }

      // Get Profile mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id`, `glpi_notificationtargets`.`id`,
                       `glpi_profiles`.`name` AS `prof`
                FROM `glpi_notificationtargets`
                LEFT JOIN `glpi_profiles` ON (`glpi_notificationtargets`.`items_id` = `glpi_profiles`.`id`)
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . NOTIFICATION_PROFILE_TYPE . "'
                ORDER BY `prof`";
      foreach ($DB->request($query) as $data) {
         $options.= "<option value='" . $data["id"] . "'>" . $LANG['profiles'][22] . " " .
                     $data["prof"] . "</option>";
         if (isset($this->notification_targets[NOTIFICATION_PROFILE_TYPE."_".$data["items_id"]])) {
            unset($this->notification_targets[NOTIFICATION_PROFILE_TYPE."_".$data["items_id"]]);
      }

      // Get Group mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id`, `glpi_notificationtargets`.`id`,
                       `glpi_groups`.`name` AS `name`
                FROM `glpi_notificationtargets`
                LEFT JOIN `glpi_groups` ON (`glpi_notificationtargets`.`items_id` = `glpi_groups`.`id`)
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . NOTIFICATION_GROUP_TYPE . "'
                ORDER BY `name`;";
         foreach ($DB->request($query) as $data) {
            $options.= "<option value='" . $data["id"] . "'>" . $LANG['common'][35] . " " .
                        $data["name"] . "</option>";
            if (isset($this->notification_targets[NOTIFICATION_GROUP_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[NOTIFICATION_GROUP_TYPE."_".$data["items_id"]]);
            }
         }
      }

      if ($canedit) {
         echo "<td class='right'>";
         if (count($this->notification_targets)) {
            echo "<select name='mailing_to_add[]' multiple size='5'>";
            foreach ($this->notification_targets as $key => $val) {
               list ($mailingtype, $items_id) = explode("_", $key);
               echo "<option value='$key'>" . $val . "</option>";
            }
            echo "</select>";
         }
         echo "</td><td class='center'>";
         if (count($this->notification_targets)) {
            echo "<input type='submit' class='submit' name='mailing_add' value='" .
                  $LANG['buttons'][8] . " >>'>";
         }
         echo "<br><br>";

         if (!empty($options)) {
            echo "<input type='submit' class='submit' name='mailing_delete' value='<< " .
                  $LANG['buttons'][6] . "'>";
         }
         echo "</td><td>";

      }
      else {
         echo "<td class='center'>";
      }

      if (!empty($options)) {
         echo "<select name='mailing_to_delete[]' multiple size='5'>";
         echo $options ."</select>";
      } else {
         echo "&nbsp;";
      }
      echo "</td>";
   }

   static function updateTargets($input) {

      $type = "";
      $action = "";

      $target = new NotificationTarget;

      if (isset($input['mailing_add'])) {
         $action = 'add';
      }
      else {
         $action = 'delete';
      }

      if (count($input["mailing_to_" . $action]) > 0) {
            switch ($action) {
               case "add" :
                  foreach ($input["mailing_to_add"] as $tmp => $val) {
                     list ($type, $items_id) = explode("_", $val);
                     $tmp = array();
                     $tmp['items_id'] = $items_id;
                     $tmp['type'] = $type;
                     $tmp['notifications_id'] = $input['notifications_id'];
                     $target->add($tmp);
                  }
                  break;

               case "delete" :
                  foreach ($input["mailing_to_delete"] as $tmp => $val) {
                     $tmp = array();
                     $tmp['id'] = $val;
                     $target->delete($tmp);
                  }
                  break;
            }
         }
   }

   /**
    * Add new mail with lang to current email array
    *
    * @param $mail : new email to add
    * @param $lang used with this email - default to config language
    *
    */
   function addToAddressesList($notifications_id, $mail,$lang='') {
      global $CFG_GLPI;

      $new_mail=trim($mail);
      $new_lang=trim($lang);
      if (!empty($new_mail)) {
         if (NotificationMail::isUserAddressValid($new_mail)
               && !isset($this->target [$notifications_id][$new_mail])) {
            logDebug("add $new_mail, $lang");
            $this->target[$notifications_id][$new_mail] =
                              (empty($new_lang) ? $CFG_GLPI["language"] : $new_lang);
         }
      }
   }

   /**
    * Get GLPI's global administrator email
    */
   function getAdminAddress($notifications_id) {
      global $CFG_GLPI;
      $this->addToAddressesList($notifications_id,$CFG_GLPI["admin_email"]);
   }

   /**
    * Get the email of the item's user
    */
   function getItemAuthorAddress($notifications_id) {
      $user = new User;
      if ($this->obj->isField('users_id')
            && $user->getFromDB($this->obj->fields["users_id"])) {
         $this->addToAddressesList($notifications_id,$user->fields["email"], $user->fields['language']);
      }
   }

   /**
    * Get entity admin email
    */
   function getEntityAdminAddress($notifications_id) {
      global $DB;

      foreach ($DB->request('glpi_entitydatas',
                            array('entities_id'=>$this->entity)) as $data) {
         $this->addToAddressesList($notifications_id,$data["email"]);
      }
   }

   /**
    * Get targets for all the users of a group
    */
   function getUsersAddressesByGroup($notifications_id,$group_id) {
      global $DB;
      $query="SELECT `glpi_users`.`email` AS email, `glpi_users`.`language` AS lang
              FROM `glpi_groups_users`
              INNER JOIN `glpi_users`
                      ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_groups_users`.`groups_id`='".$group_id."'";
      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($notifications_id,$data['email'], $data['lang']);
      }
   }

   /**
    *Get all the targets for a notification
    *@param notifications_id the notification id
    *@param item the item which raises the notification
    *@param options additionnal options if needed
    *@return a NotficiationTarget class filled with all the targets
    */
   static function getByNotificationIdAndEntity($notifications_id, $item,$options=array()) {
      global $DB,$CFG_GLPI;

      $itemtype = $item->getType();

      //Get a target object related to the itemtype
      $notificationtarget = NotificationTarget::getInstance($item);
      if ($notificationtarget) {
         foreach ($DB->request('glpi_notificationtargets',
                               array('notifications_id' => $notifications_id)) as $target) {
            //Get all the targets to send the notification to
            $notificationtarget->getAddressesByTarget($notifications_id,$itemtype,$target,$options);
         }
      }
      else {
         $notificationtarget = array();
      }
      return $notificationtarget;
   }

   static function getDistinctUserSql() {
      return "SELECT DISTINCT `glpi_users`.`email` AS email,
                               `glpi_users`.`language` AS lang ";
   }

   /**
    * Return all possible notification events for the object type
    * @return an array which contains : event => event label
    */
   function getEvents() {
      return array();
   }

   /**
    * Return all the targets for this notification
    * Values returned by this method are the ones for the alerts
    * Can be updated by implementing the getAdditionnalTargets() method
    * Can be overwitten (like dbconnection)
    * @param entity the entity on which the event is raised
    */
   function getNotficationTargets($entity) {
      global $LANG,$DB;

      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_GLOBAL_ADMINISTRATOR] = $LANG['setup'][237];
      $this->notification_targets[NOTIFICATION_USER_TYPE . "_" . NOTIFICATION_ENTITY_ADMINISTRATOR] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
      asort($this->notification_targets);
      $this->getAdditionalTargets($this->notification_targets);

      foreach ($DB->request('glpi_profiles') as $data) {
         $this->notification_targets[NOTIFICATION_PROFILE_TYPE ."_" . $data["id"]] =
                                       $LANG['profiles'][22] . " " .$data["name"];
      }

      $query = "SELECT `id`, `name`
                FROM `glpi_groups`
                ".getEntitiesRestrictRequest(" WHERE",'glpi_groups','entities_id',$entity,true)."
                ORDER BY `name`";
     foreach ($DB->request($query) as $data) {
         $this->notification_targets[NOTIFICATION_GROUP_TYPE ."_" . $data["id"]] =
                                       $LANG['common'][35] . " " .$data["name"];
      }
   }

   /**
    * Allows to add more notification targets
    * Can be overridden in some case (for example Ticket)
    */
   function getAdditionalTargets() {
   }

   /**
    * Get addresses by a method not defined in NotificationTarget (specific to an itemtype)
    */
   function getSpecificTargets ($notifications_id,$itemtype,$data,$options=array()) {

   }
   /**
    * Get item associated with the object on which the event was raised
    * (usefull for reservation, consumableitem and cartridgeitem)
    * @param itemtype the item type
    * @return the object associated with the itemtype
    */
   static function getObjectItem($itemtype) {
      $item = null;
      $ri=new $itemtype();
      $foreingkey = getForeignKeyFieldForTable(getTableForItemType($itemtype));
      if ($ri->getFromDB($this->obj->fields[$foreingkey])) {
         if (class_exists($ri->fields['itemtype'])) {
            $item = new $ri->fields['itemtype'];
            if ($item->getFromDB($ri->fields['items_id'])) {
               return $item;
            }
         }
      }
      return $item;
   }

   /**
    * Add user to the notified users list
    * @param notifications_id the notification id
    * @param field look for user looking for this field in the object which raises the event
    */
   function getUserByField ($notifications_id, $field) {
      global $DB;

      $field_value = false;

      //If the object which raises the notification does not contain the user informations
      //Then get the real object and look for the user infos in it
      if (!$this->real_object) {
         $obj = NotificationTarget::getObjectItem($this->obj->itemtype);
         $field_value = $obj->getField($field);
      }
      else {
         //The object contains the user informations
         $field_value = $this->obj->getField($field);
      }

      if ($field_value != '') {
         //Look for the user by his id
         $query = NotificationTargetTicket::getDistinctUserSql()."
                  FROM `glpi_users`
                  WHERE `glpi_users`.`id` = '".$this->obj->getField($field)."'";
         foreach ($DB->request($query) as $data) {
            //Add the user email and language in the notified users list
            $this->addToAddressesList($notifications_id,$data['email'],$data['lang']);
         }
      }
   }

   /**
    * Get thecnician in charge of the item
    */
   function getItemTechnicianInChargeAddress($notifications_id) {
      $this->getUserByField($notifications_id,'user_id_tech');
   }

   /**
    * Get user owner of the material
    */
   function getItemOwnerAddress($notifications_id) {
      $this->getUserByField($notifications_id,'user_id');
   }

   /**
    * Get users emails by profile
    * @param notifications_id the notification ID
    * @param profiles_id the profile ID to get users emails
    * @return nothing
    */
   function getUsersAddressesByProfile($notifications_id,$profiles_id) {
      global $DB;

      $field_value = false;
      if (!$this->real_object) {
         $obj = NotificationTarget::getObjectItem($this->obj->itemtype);
         $field_value = $obj->getEntityID();
      }
      else {
         $field_value = $item->getyEntityID();
      }

      if ($field_value) {
         $query=NotificationTargetTicket::getDistinctUserSql()."
                 FROM `glpi_profiles_users`
                 INNER JOIN `glpi_users`
                 ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                 WHERE `glpi_profiles_users`.`profiles_id`='".$profiles_id."'".
                    getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                     $field_value,true);
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($notifications_id,$data['email'],$data['lang']);
         }
      }
   }

   /**
    * Get admin which sends the notification
    * @return the sender's address
    */
   function getSender() {
      global $DB, $CFG_GLPI;
      //If the entity administrator's address is defined, return it
      foreach ($DB->request('glpi_entitydatas',
                      array('entities_id'=>$this->entity)) as $data) {
         if (NotificationMail::isUserAddressValid($data['email'])) {
            return $data['email'];
         }
      }
      //Entity admin is not defined, return the global admin's address
      return $CFG_GLPI["admin_email"];
   }

   /**
    * Get the reply to address
    * @return the reply to address
    */
   function getReplyTo() {

   }

   /**
    * Get addresses by type of notification
    * @param notifications_id id of the notification
    * @param itemtype the itemtype
    * @param target the NotificationTarget object associated with the type
    * @param options additionnal options
    */
   function getAddressesByTarget($notifications_id,$itemtype,$data,$options=array()) {
      //Look for all targets whose type is NOTIFICATION_USER_TYPE
      switch ($data['type']) {

         //Notifications for one people
         case NOTIFICATION_USER_TYPE:
            switch ($data['items_id']) {
               //Send to glpi's global admin (as defined in the mailing configuration)
               case NOTIFICATION_GLOBAL_ADMINISTRATOR :
                  $this->getAdminAddress($notifications_id);
               break;
               //Send to the entity's admninistrator
               case NOTIFICATION_ENTITY_ADMINISTRATOR :
                  $this->getEntityAdminAddress($notifications_id);
               break;
               //Technician in charge of the ticket
               case NOTIFICATION_ITEM_TECH_IN_CHARGE :
                  $this->getItemTechnicianInChargeAddress($notifications_id);
               break;
               //User who's owner of the material
               case NOTIFICATION_ITEM_USER :
                  $this->getItemOwnerAddress($notifications_id);
               break;
         }
         break;
         //Send to all the users of a group
         case NOTIFICATION_GROUP_TYPE:
            $this->getUsersAddressesByGroup($notifications_id,$data['items_id']);
         break;
         default :
            //Maybe a target specific to a type
            $this->getSpecificTargets($notifications_id,$itemtype,$data,$options);
         break;
      }
   }

}
?>
