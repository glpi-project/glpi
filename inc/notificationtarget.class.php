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
class NotificationTarget extends CommonDBTM {

   var $prefix = '';

   //Indicates that the object which raises the event is the object in which to look for
   //users, technicians and so on
   //False for items like Reservation, CartridgeItem, ConsumableItem
   var $real_object = true;

   var $notification_targets = array();

   var $notificationoptions = 0;

   // From CommonDBTM
   public $dohistory = true;

   //Array to store emails by notification
   var $target = array();

   var $entity = '';

   //Object which raises the notification event
   var $obj = null;

   //Object which is associated with the event
   var $target_object = null;

   function __construct($entity='', $object = null) {
      if (!$entity == '') {
         $this->entity = $_SESSION['glpiactive_entity'];
      }
      else {
         $this->entity = $entity;
      }
      $this->obj = $object;
      $this->target_object = $object;

   }

   function getSubjectPrefix() {
      return "[GLPI] ";
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
      if ($plug = isPluginItemType($item->getType())) {
         $name = 'Plugin'.$plug['plugin'].'NotificationTarget'.$plug['class'];
      }
      else {
         $name = 'NotificationTarget'.$item->getType();
      }

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
      if (class_exists($itemtype)) {
         return NotificationTarget::getInstance(new $itemtype ());
      }
      else {
         return false;
      }

   }

   function showForNotification(Notification $notification) {
      global $DB, $LANG;

      if (!haveRight("notification", "r")) {
         return false;
      }

      echo "<div class='center'>";
      echo "<form name='notificationtargets_form' id='notificationtargets_form'
             method='post' action=' ";
      echo getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['mailing'][121] . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<input type='hidden' name='notifications_id' value='".$notification->getField('id')."'>";
      $this->showNotificationTargets($notification);
      echo "</tr>";
      echo "</table></form></div>";
   }

   function dropdownTargets($used = array()) {
      Dropdown::showFromArray('type', $this->notification_targets);
   }

   /**
    * Display notification targets
    * @param notification the Notification object
    */
   function showNotificationTargets(Notification $notification) {
      global $LANG, $DB;
      if ($notification->getField('itemtype') != '') {
          $notifications_id = $notification->fields['id'];
         $this->getNotficationTargets($_SESSION['glpiactive_entity']);

         $notification = new Notification;
         $canedit = $notification->can($notifications_id,'w');

         $options="";
         // Get User mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id` , `glpi_notificationtargets`.`id`
                   FROM `glpi_notificationtargets`
                   WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                         AND `glpi_notificationtargets`.`type`='" . Notification::USER_TYPE . "'
                   ORDER BY `glpi_notificationtargets`.`items_id`";
         foreach ($DB->request($query) as $data) {
             if (isset($this->notification_targets[Notification::USER_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[Notification::USER_TYPE."_".$data["items_id"]]);
            }
            switch ($data["items_id"]) {
               case Notification::GLOBAL_ADMINISTRATOR :
                  $name = $LANG['setup'][237];
               break;

               case Notification::ENTITY_ADMINISTRATOR :
                  $name = $LANG['setup'][237]." ".$LANG['entity'][0];
               break;

               case Notification::TICKET_ASSIGN_TECH :
                  $name = $LANG['setup'][239];
               break;

               case Notification::AUTHOR :
                  $name = $LANG['job'][4];
               break;

               case Notification::ITEM_USER :
                  $name = $LANG['common'][34] . " " . $LANG['common'][1];
               break;

               case Notification::TICKET_OLD_TECH_IN_CHARGE :
                  $name = $LANG['setup'][236];
               break;

               case Notification::ITEM_TECH_IN_CHARGE :
                  $name = $LANG['common'][10];
               break;

               case Notification::TICKET_RECIPIENT :
                  $name = $LANG['job'][3];
               break;

               case Notification::TICKET_SUPPLIER :
                  $name = $LANG['financial'][26];
               break;

               case Notification::GROUP_MAILING :
                  $name = $LANG['setup'][248];
               break;

               case Notification::TICKET_SUPERVISOR_ASSIGN_GROUP :
                     $name = $LANG['common'][64]." ".$LANG['setup'][248];
               break;

               case Notification::TICKET_SUPERVISOR_REQUESTER_GROUP :
                  $name = $LANG['common'][64]." ".$LANG['setup'][249];
               break;

                default :
                  //TODO : add function to look for additionnal targets (plugins)
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
                         AND `glpi_notificationtargets`.`type`='" . Notification::PROFILE_TYPE . "'
                   ORDER BY `prof`";
         foreach ($DB->request($query) as $data) {
            $options.= "<option value='" . $data["id"] . "'>" . $LANG['profiles'][22] . " " .
                        $data["prof"] . "</option>";
            if (isset($this->notification_targets[Notification::PROFILE_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[Notification::PROFILE_TYPE."_".$data["items_id"]]);
         }

         // Get Group mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id`, `glpi_notificationtargets`.`id`,
                          `glpi_groups`.`name` AS `name`
                   FROM `glpi_notificationtargets`
                   LEFT JOIN `glpi_groups` ON (`glpi_notificationtargets`.`items_id` = `glpi_groups`.`id`)
                   WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                         AND `glpi_notificationtargets`.`type`='" . Notification::GROUP_TYPE . "'
                   ORDER BY `name`;";
            foreach ($DB->request($query) as $data) {
               $options.= "<option value='" . $data["id"] . "'>" . $LANG['common'][35] . " " .
                           $data["name"] . "</option>";
               if (isset($this->notification_targets[Notification::GROUP_TYPE."_".$data["items_id"]])) {
                  unset($this->notification_targets[Notification::GROUP_TYPE."_".$data["items_id"]]);
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
    * @param $options an additionnal option to sort users
    *
    */
   function addToAddressesList($mail,$lang='',$options='') {
      global $CFG_GLPI;

      $new_mail=trim($mail);
      $new_lang=trim($lang);

      if (!empty($new_mail)) {
         if (NotificationMail::isUserAddressValid($new_mail)
               && !isset($this->target[$new_mail])) {
            $this->target[$new_mail] = array ('language'=>(empty($new_lang) ?
                                                           $CFG_GLPI["language"] :
                                                           $new_lang),
                                              'email'=>$new_mail,
                                              'options'=>$options);

         }
      }
   }

   /**
    * Get GLPI's global administrator email
    */
   function getAdminAddress() {
      global $CFG_GLPI;
      $this->addToAddressesList($CFG_GLPI["admin_email"],
                                $CFG_GLPI["language"],
                                array('sendprivate'=>true));
   }

   /**
    * Get the email of the item's user
    */
   function getItemAuthorAddress() {
      $user = new User;
      if ($this->obj->isField('users_id')
            && $user->getFromDB($this->obj->getField('users_id'))) {
         $this->addToAddressesList($user->getField('email'),
                                   $user->getField('language'),
                                   $user->getField('entities_id'));
      }
   }

   /**
    * Get entity admin email
    */
   function getEntityAdminAddress() {
      global $DB,$CFG_GLPI;

      foreach ($DB->request('glpi_entitydatas',
                            array('entities_id'=>$this->entity)) as $data) {
         $this->addToAddressesList($data['admin_email'],
                                   $CFG_GLPI['language'],
                                   $this->entity);
      }
   }

   /**
    * Get targets for all the users of a group
    */
   function getUsersAddressesByGroup($group_id) {
      global $DB;
      $query=$this->getDistinctUserSql()."
              FROM `glpi_groups_users`".
              NotificationTarget::getJoinProfileSql()."
              INNER JOIN `glpi_users`
                      ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_groups_users`.`groups_id`='".$group_id."'";
      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($data['email'], $data['lang']);
      }
   }

   static function getDistinctUserSql() {
      return "SELECT DISTINCT `glpi_users`.`email` AS email,
                               `glpi_users`.`language` AS lang";
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

      if (haveRight("config","w")) {
         $this->notification_targets[Notification::USER_TYPE . "_" .
                                        Notification::GLOBAL_ADMINISTRATOR] = $LANG['setup'][237];
      }
      $this->notification_targets[Notification::USER_TYPE . "_" .
                                     Notification::ENTITY_ADMINISTRATOR] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
      $this->getAdditionalTargets();
      asort($this->notification_targets);

      foreach ($DB->request('glpi_profiles') as $data) {
         $this->notification_targets[Notification::PROFILE_TYPE ."_" . $data["id"]] =
                                       $LANG['profiles'][22] . " " .$data["name"];
      }

      $query = "SELECT `id`, `name`
                FROM `glpi_groups`
                ".getEntitiesRestrictRequest(" WHERE",'glpi_groups','entities_id',$entity,true)."
                ORDER BY `name`";
     foreach ($DB->request($query) as $data) {
         $this->notification_targets[Notification::GROUP_TYPE ."_" . $data["id"]] =
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
   function getSpecificTargets ($data,$options=array()) {

   }


   /**
    * Get item associated with the object on which the event was raised
    * @return the object associated with the itemtype
    */
   function getObjectItem() {
      $this->target_object = $this->obj;
   }

   /**
    * Add user to the notified users list
    * @param field look for user looking for this field in the object which raises the event
    */
   function getUserByField ($field) {
      global $DB;
      if ($this->target_object) {
         //Look for the user by his id
         $query = $this->getDistinctUserSql()."
                  FROM `glpi_users`".
                  NotificationTarget::getJoinProfileSql()."
                  WHERE `glpi_users`.`id` = '".$this->target_object->getField($field)."'";

         foreach ($DB->request($query) as $data) {
            //Add the user email and language in the notified users list
            $this->addToAddressesList($data['email'],$data['lang'],array());
         }
      }
   }

   /**
    * Get thecnician in charge of the item
    */
   function getItemTechnicianInChargeAddress() {
      $this->getUserByField('users_id_tech');
   }

   /**
    * Get user owner of the material
    */
   function getItemOwnerAddress() {
      $this->getUserByField('users_id');
   }

   /**
    * Get users emails by profile
    * @param profiles_id the profile ID to get users emails
    * @return nothing
    */
   function getUsersAddressesByProfile($profiles_id) {
      global $DB;

      if ($this->target_object) {
         $query=NotificationTargetTicket::getDistinctUserSql()."
                 FROM `glpi_profiles_users`".
                 NotificationTarget::getJoinProfileSql()
                ."INNER JOIN `glpi_users`
                 ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                 WHERE `glpi_profiles_users`.`profiles_id`='".$profiles_id."'".
                    getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                     $this->target_object->getEntityID(),true);
         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data['email'],$data['lang'],$data['entity']);
         }
      }
   }

   /**
    * Get admin which sends the notification
    * @return the sender's address
    */
   function getSender() {
      global $DB, $CFG_GLPI;

      $entity = ($this->obj->isField('entities_id')?$this->obj->getField('entities_id'):
                                                    $this->target_object->getField('entities_id'));
      //If the entity administrator's address is defined, return it
      foreach ($DB->request('glpi_entitydatas',
                      array('entities_id'=>$entity)) as $data) {
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
    * @param $data
    * @param $options additionnal options
    */
   function getAddressesByTarget($data,$options=array()) {
      //Look for all targets whose type is Notification::USER_TYPE

      switch ($data['type']) {

         //Notifications for one people
         case Notification::USER_TYPE:
            switch ($data['items_id']) {
               //Send to glpi's global admin (as defined in the mailing configuration)
               case Notification::GLOBAL_ADMINISTRATOR :
                  $this->getAdminAddress();
               break;
               //Send to the entity's admninistrator
               case Notification::ENTITY_ADMINISTRATOR :
                  $this->getEntityAdminAddress();
               break;
               //Technician in charge of the ticket
               case Notification::ITEM_TECH_IN_CHARGE :
                  $this->getItemTechnicianInChargeAddress();
               break;
               //User who's owner of the material
               case Notification::ITEM_USER :
                  $this->getItemOwnerAddress();
               break;
               //Send to the author of the ticket
               case Notification::AUTHOR:
                  $this->getItemAuthorAddress();
               break;
            }
         break;
         //Send to all the users of a group
         case Notification::GROUP_TYPE:
            $this->getUsersAddressesByGroup($data['items_id']);
         break;
         default :
            //Maybe a target specific to a type
            $this->getSpecificTargets($data,$options);
         break;
      }
   }

   /**
    * Get all data needed for template processing
    * Provides minimum informations for alerts
    * Can be overridden by each NotificationTartget class if needed
    */
   function getDatasForTemplate($event,$tpldata = array(), $options=array()) {
      return $tpldata;
   }

   function getTargets() {
      return $this->target;
   }

   function getEntity() {
      if ($this->obj->isField('entities_id')) {
         return $this->obj->getField('entities_id');
      }
      elseif ($this->target_object) {
         return $this->target_object->getField('entities_id');
      }
      else {
         return 0;
      }
   }

   function clearAddressesList() {
      $this->target = array();
   }

   static function getJoinProfileSql() {
      return "";
   }

   function getForTemplate($event,$options = array()) {
      global $CFG_GLPI;
      $tpldata = array();
      $tpldata['##glpi.url##'] = $CFG_GLPI['root_doc'];
      $tpldata = $this->getDatasForTemplate($event,$tpldata,$options);
      return $tpldata;
   }
}
?>
