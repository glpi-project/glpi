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
class NotificationTarget extends CommonDBChild {

   var $prefix = '';
   // From CommonDBChild
   public $itemtype = 'Notification';
   public $items_id = 'notifications_id';
   public $table    = 'glpi_notificationtargets';

   var $notification_targets        = array();
   var $notification_targets_labels = array();
   var $notificationoptions         = 0;

   // Data from the objet which can be used by the template
   // See https://forge.indepnet.net/projects/5/wiki/NotificationTemplatesTags
   var $datas            = array();
   var $tag_descriptions = array();

   // From CommonDBTM
   public $dohistory = true;

   //Array to store emails by notification
   var $target = array();
   var $entity = '';

   //Object which raises the notification event
   var $obj = null;

   //Object which is associated with the event
   var $target_object = null;

   // array of event name => event label
   var $events     = array();
   var $options    = array();
   var $raiseevent = '';

   const NO_OPTION          = 0;
   const TAG_LANGUAGE       = 'lang';
   const TAG_VALUE          = 'tag';
   const TAG_FOR_ALL_EVENTS = 0;


   function __construct($entity='', $event='', $object=null, $options=array()) {

      if ($entity === '') {
         $this->entity = (isset($_SESSION['glpiactive_entity'])?$_SESSION['glpiactive_entity']:0);
      } else {
         $this->entity = $entity;
      }

      if ($object) {
         if ($object instanceof CommonDBTM && isset($object->fields['id'])) {
            // Reread to avoid slashes issue
            $object->getFromDB($object->fields['id']);
         }
         $this->obj = $object;
         $this->getObjectItem($event);
      }

      $this->raiseevent = $event;
      $this->options    = $options;
      $this->getNotificationTargets($entity);
      $this->getAdditionalTargets($event);
      asort($this->notification_targets);
   }


   /// Validate send before doing it (may be overloaded : exemple for private tasks or followups)
   function validateSendTo($user_infos) {
      return true;
   }


   function getSubjectPrefix($event='') {
      return "[GLPI] ";
   }


   static function getTypeName() {
      global $LANG;

      return $LANG['mailing'][113];
   }


   function getNameID($with_comment=0) {

      if (isset($this->notification_targets_labels[$this->getField("type")]
                                                  [$this->getField("items_id")])) {

         return $this->notification_targets_labels[$this->getField("type")]
                                                  [$this->getField("items_id")];
      }
      return "";
   }


   /**
    * Get a notificationtarget class by giving the object which raises the event
    *
    * @param $item the object which raises the event
    * @param $event the event which will be used
    * @param $options array of options
    *
    * @return a notificationtarget class or false
   **/
   static function getInstance($item, $event='', $options=array()) {

      if ($plug = isPluginItemType($item->getType())) {
         $name = 'Plugin'.$plug['plugin'].'NotificationTarget'.$plug['class'];
      } else {
         $name = 'NotificationTarget'.$item->getType();
      }

      $entity = 0;
      if (class_exists($name)) {
         //Item which raises the event contains an entityID
         if ($item->getField('entities_id') != NOT_AVAILABLE) {
            $entity = $item->getField('entities_id');

         //Entity ID exists in the options array
         } else if (isset($options['entities_id'])) {
            $entity = $options['entities_id'];
         }
         return new $name($entity, $event, $item, $options);
      }
      return false;
   }


   /**
    * Get a notificationtarget class by giving an itemtype
    *
    * @param $itemtype the itemtype of the object which raises the event
    * @param $event the event which will be used
    * @param $options array of options
    *
    * @return a notificationtarget class or false
   **/
   static function getInstanceByType($itemtype, $event='', $options=array()) {

      if ($itemtype != '' && class_exists($itemtype)) {
         return NotificationTarget::getInstance(new $itemtype (), $event, $options);
      }
      return false;
   }


   function showForNotification(Notification $notification) {
      global $LANG;

      if (!haveRight("notification", "r")) {
         return false;
      }

      echo "<form name='notificationtargets_form' id='notificationtargets_form'
             method='post' action=' ";
      echo getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['mailing'][121] . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<input type='hidden' name='notifications_id' value='".$notification->getField('id')."'>";
      echo "<input type='hidden' name='itemtype' value='".$notification->getField('itemtype')."'>";
      $this->showNotificationTargets($notification);
      echo "</tr>";
      echo "</table></form>";
   }


   /**
    * Display notification targets
    *
    * @param $notification the Notification object
   **/
   function showNotificationTargets(Notification $notification) {
      global $LANG, $DB;

      if ($notification->getField('itemtype') != '') {
         $notifications_id = $notification->fields['id'];
         $this->getNotificationTargets($_SESSION['glpiactive_entity']);

         $canedit = $notification->can($notifications_id,'w');

         $options = "";
         // Get User mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id`,
                          `glpi_notificationtargets`.`id`
                   FROM `glpi_notificationtargets`
                   WHERE `glpi_notificationtargets`.`notifications_id` = '$notifications_id'
                         AND `glpi_notificationtargets`.`type` = '" . Notification::USER_TYPE . "'
                   ORDER BY `glpi_notificationtargets`.`items_id`";

         foreach ($DB->request($query) as $data) {
            if (isset($this->notification_targets[Notification::USER_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[Notification::USER_TYPE."_".$data["items_id"]]);
            }

            if (isset($this->notification_targets_labels[Notification::USER_TYPE]
                                                        [$data["items_id"]])) {
               $name = $this->notification_targets_labels[Notification::USER_TYPE][$data["items_id"]];
            } else {
                $name = "&nbsp;";
            }
            $options .= "<option value='" . $data["id"] . "'>" . $name . "</option>";
         }

         // Get Profile mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id`,
                          `glpi_notificationtargets`.`id`,
                          `glpi_profiles`.`name` AS `prof`
                   FROM `glpi_notificationtargets`
                   LEFT JOIN `glpi_profiles`
                        ON (`glpi_notificationtargets`.`items_id` = `glpi_profiles`.`id`)
                   WHERE `glpi_notificationtargets`.`notifications_id` = '$notifications_id'
                         AND `glpi_notificationtargets`.`type` = '" . Notification::PROFILE_TYPE . "'
                   ORDER BY `prof`";

         foreach ($DB->request($query) as $data) {
            $options .= "<option value='" . $data["id"] . "'>" . $LANG['profiles'][22] . " " .
                        $data["prof"] . "</option>";

            if (isset($this->notification_targets[Notification::PROFILE_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[Notification::PROFILE_TYPE."_".$data["items_id"]]);
            }
         }

         // Get Group mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id`,
                          `glpi_notificationtargets`.`id`,
                          `glpi_groups`.`name` AS `name`
                   FROM `glpi_notificationtargets`
                   LEFT JOIN `glpi_groups`
                        ON (`glpi_notificationtargets`.`items_id` = `glpi_groups`.`id`)
                   WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                         AND `glpi_notificationtargets`.`type` = '" . Notification::GROUP_TYPE . "'
                   ORDER BY `name`;";

         foreach ($DB->request($query) as $data) {
            $options .= "<option value='" . $data["id"] . "'>" . $LANG['common'][35] . " " .
                        $data["name"] . "</option>";

            if (isset($this->notification_targets[Notification::GROUP_TYPE."_".$data["items_id"]])) {
               unset($this->notification_targets[Notification::GROUP_TYPE."_".$data["items_id"]]);
            }
         }

         // Get Group mailing
         $query = "SELECT `glpi_notificationtargets`.`items_id`,
                          `glpi_notificationtargets`.`id`,
                          `glpi_groups`.`name` AS `name`
                   FROM `glpi_notificationtargets`
                   LEFT JOIN `glpi_groups`
                        ON (`glpi_notificationtargets`.`items_id` = `glpi_groups`.`id`)
                   WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                         AND `glpi_notificationtargets`.`type`
                                                         = '".Notification::SUPERVISOR_GROUP_TYPE."'
                   ORDER BY `name`;";

         foreach ($DB->request($query) as $data) {
            $options .= "<option value='" . $data["id"] . "'>" . $LANG['common'][64].' '.
                        $LANG['common'][35] . " " .$data["name"] . "</option>";

            if (isset($this->notification_targets[Notification::SUPERVISOR_GROUP_TYPE."_".
                                                  $data["items_id"]])) {

               unset($this->notification_targets[Notification::SUPERVISOR_GROUP_TYPE."_".
                                                 $data["items_id"]]);
            }
         }

         if ($canedit) {
            echo "<td class='right'>";

            if (count($this->notification_targets)) {
               echo "<select name='mailing_to_add[]' multiple size='5'>";

               foreach ($this->notification_targets as $key => $val) {
                  list ($type, $items_id) = explode("_", $key);
                  echo "<option value='$key'>".$this->notification_targets_labels[$type][$items_id].
                       "</option>";
               }

               echo "</select>";
            }

            echo "</td><td class='center'>";

            if (count($this->notification_targets)) {
               echo "<input type='submit' class='submit' name='mailing_add' value='".
                     $LANG['buttons'][8]." >>'>";
            }
            echo "<br><br>";

            if (!empty($options)) {
               echo "<input type='submit' class='submit' name='mailing_delete' value='<< ".
                     $LANG['buttons'][6]."'>";
            }
            echo "</td><td>";

         } else {
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

      $type   = "";
      $action = "";
      $target = NotificationTarget::getInstanceByType($input['itemtype']);

      if (isset($input['mailing_add'])) {
         $action = 'add';
      } else {
         $action = 'delete';
      }

      if (count($input["mailing_to_" . $action]) > 0) {
         switch ($action) {
            case "add" :
               foreach ($input["mailing_to_add"] as $tmp => $val) {
                  list ($type, $items_id) = explode("_", $val);
                  $tmp = array();
                  $tmp['items_id']         = $items_id;
                  $tmp['type']             = $type;
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


   function addAdditionnalInfosForTarget() {
   }


   function addAdditionnalUserInfo($data) {
      return NotificationTarget::NO_OPTION;
   }


   /**
    * Add new mail with lang to current email array
    *
    * @param $data : array of data (mail, lang)
   **/
   function addToAddressesList($data) {
      global $CFG_GLPI;

      $new_mail = trim($data['email']);
      $new_lang = '';
      if (isset($data['language'])) {
         $new_lang = trim($data['language']);
      }

      $notificationoption = $this->addAdditionnalUserInfo($data);
      if (!empty($new_mail)) {
         if (NotificationMail::isUserAddressValid($new_mail) && !isset($this->target[$new_mail])) {

            $this->target[$new_mail] = array ('language' => (empty($new_lang) ?$CFG_GLPI["language"]
                                                                              :$new_lang),
                                              'email'             => $new_mail,
                                              'additionnaloption' => $notificationoption);
         }
      }
   }


   /**
    * Get GLPI's global administrator email
   **/
   function getAdminAddress() {
      global $CFG_GLPI;

      $this->addToAddressesList(array("email"    => $CFG_GLPI["admin_email"],
                                      "language" => $CFG_GLPI["language"]));
   }


   /**
    * Get the email of the item's user
   **/
   function getItemAuthorAddress() {

      $user = new User;
      if ($this->obj->isField('users_id') && $user->getFromDB($this->obj->getField('users_id'))) {

         $this->addToAddressesList(array('email'    => $user->getField('email'),
                                         'language' => $user->getField('language'),
                                         'id'       => $user->getField('id')));
      }
   }


   /**
    * Get entity admin email
   **/
   function getEntityAdminAddress() {
      global $DB, $CFG_GLPI;

      foreach ($DB->request('glpi_entitydatas', array('entities_id' => $this->entity)) as $row) {
         $data['language'] = $CFG_GLPI['language'];
         $data['email']    = $row['admin_email'];
         $this->addToAddressesList($data);
      }
   }


   /**
    * Get targets for all the users of a group
   **/
   function getUsersAddressesByGroup($group_id) {
      global $DB;

      $query = $this->getDistinctUserSql()."
               FROM `glpi_groups_users`".
               $this->getJoinSql()."
               INNER JOIN `glpi_users` ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
               WHERE `glpi_groups_users`.`groups_id` = '".$group_id."'";

      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($data);
      }
   }


   /**
    * Get targets for all the users of a group
   **/
   function getSupervisorAddressByGroup($groups_id) {
      global $DB;

      $query = $this->getDistinctUserSql()."
               FROM `glpi_groups`
               LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)
               WHERE `glpi_groups`.`id` = '".$groups_id."'";

         foreach ($DB->request($query) as $data) {
            $this->addToAddressesList($data);
         }
         $this->addToAddressesList($data);
   }


   function getDistinctUserSql() {

      return  "SELECT DISTINCT `glpi_users`.id AS id,
                               `glpi_users`.`email` AS email,
                               `glpi_users`.`language` AS language";
   }


   /**
    * Return main notification events for the object type
    * Internal use only => should use getAllEvents
    *
    * @return an array which contains : event => event label
   **/
   function getEvents() {
      return array();
   }


   /**
    * Return all (GLPI + plugins) notification events for the object type
    *
    * @return an array which contains : event => event label
   **/
   function getAllEvents() {

      $this->events = $this->getEvents();
      //If plugin adds new events for an already defined type
      doHook('item_get_events', $this);

      return $this->events;
   }


   function addTarget ($target='', $label='', $type=Notification::USER_TYPE) {

      $key = $type.'_'.$target;
      $this->notification_targets[$key]                  = $key;
      $this->notification_targets_labels[$type][$target] = $label;
   }


   function addProfilesToTargets() {
      global $LANG, $DB;

      foreach ($DB->request('glpi_profiles') as $data) {
         $this->addTarget($data["id"], $LANG['profiles'][22] . " " .$data["name"],
                          Notification::PROFILE_TYPE);
      }
   }


   function addGroupsToTargets($entity) {
      global $LANG, $DB;

      $query = "SELECT `id`, `name`
                FROM `glpi_groups`".
                getEntitiesRestrictRequest(" WHERE", 'glpi_groups', 'entities_id', $entity, true)."
                ORDER BY `name`";

      foreach ($DB->request($query) as $data) {
         //Add group
         $this->addTarget($data["id"], $LANG['common'][35] . " " .$data["name"],
                          Notification::GROUP_TYPE);
         //Add group supervisor
         $this->addTarget($data["id"],
                          $LANG['common'][64].' '.$LANG['common'][35]." ".$data["name"],
                          Notification::SUPERVISOR_GROUP_TYPE);
      }
   }


   /**
    * Return all the targets for this notification
    * Values returned by this method are the ones for the alerts
    * Can be updated by implementing the getAdditionnalTargets() method
    * Can be overwitten (like dbconnection)
    *
    * @param $entity the entity on which the event is raised
   **/
   function getNotificationTargets($entity) {
      global $LANG;

      if (haveRight("config", "w")) {
         $this->addTarget(Notification::GLOBAL_ADMINISTRATOR, $LANG['setup'][237]);
      }
      $this->addTarget(Notification::ENTITY_ADMINISTRATOR,
                       $LANG['setup'][237]." ".$LANG['entity'][0]);

      $this->addProfilesToTargets();
      $this->addGroupsToTargets($entity);
   }


   /**
    * Allows to add more notification targets
    * Can be overridden in some case (for example Ticket)
    *
    * @param $event specif event to get additional targets
   **/
   function getAdditionalTargets($event='') {
   }


   /**
    * Get addresses by a method not defined in NotificationTarget (specific to an itemtype)
    *
    * @param $data
    * @param $options
   **/
   function getSpecificTargets ($data, $options) {
   }


   /**
    * Get item associated with the object on which the event was raised
    *
    * @return the object associated with the itemtype
   **/
   function getObjectItem($event='') {
      $this->target_object = $this->obj;
   }


   /**
    * Add user to the notified users list
    *
    * @param $field look for user looking for this field in the object which raises the event
    * @param $search_in_object search is done in the object ? if not  in target object.
    */
   function getUserByField ($field, $search_in_object=false) {
      global $DB;

      $id = 0;
      if (!$search_in_object) {
         $id = $this->obj->getField($field);

      } else if ($this->target_object) {
         $id = $this->target_object->getField($field);
      }

      if ($id) {
         //Look for the user by his id
         $query = $this->getDistinctUserSql()."
                  FROM `glpi_users`".
                  $this->getJoinProfileSql()."
                  WHERE `glpi_users`.`id` = '$id'";

         foreach ($DB->request($query) as $data) {
            //Add the user email and language in the notified users list
            $this->addToAddressesList($data);
         }
      }
   }


   /**
    * Get technician in charge of the item
   **/
   function getItemTechnicianInChargeAddress() {
      $this->getUserByField('users_id_tech', true);
   }


   /**
    * Get user owner of the material
   **/
   function getItemOwnerAddress() {
      $this->getUserByField('users_id', true);
   }


   /**
    * Get users emails by profile
    *
    * @param $profiles_id the profile ID to get users emails
    *
    * @return nothing
   **/
   function getUsersAddressesByProfile($profiles_id) {
      global $DB;

      $query = $this->getDistinctUserSql().",
               glpi_profiles_users.entities_id AS entity
               FROM `glpi_profiles_users`".
               $this->getJoinProfileSql()."
               INNER JOIN `glpi_users` ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
               WHERE `glpi_profiles_users`.`profiles_id` = '".$profiles_id."' ".
                     getEntitiesRestrictRequest(" AND", "glpi_profiles_users", "entities_id",
                                                $this->getEntity(), true);

      foreach ($DB->request($query) as $data) {
         $this->addToAddressesList($data);
      }
   }


   /**
    * Get admin which sends the notification
    *
    * @return the sender's address
   **/
   function getSender($options = array()) {
      global $DB, $CFG_GLPI;

      if (isset($options['entities_id'])) {
         $entity = $options['entities_id'];
      } else {
         $entity = ($this->obj->isField('entities_id')?$this->obj->getField('entities_id')
                                                      :$this->target_object->getField('entities_id'));
      }

      //If the entity administrator's address is defined, return it
      foreach ($DB->request('glpi_entitydatas', array('entities_id' => $entity)) as $data) {
         if (NotificationMail::isUserAddressValid($data['admin_email'])) {
            return $data['admin_email'];
         }
      }
      //Entity admin is not defined, return the global admin's address
      return $CFG_GLPI["admin_email"];
   }


   /**
    * Get the reply to address
    *
    * @return the reply to address
   **/
   function getReplyTo($options = array()) {
      global $DB, $CFG_GLPI;

      if (isset($options['entities_id'])) {
         $entity = $options['entities_id'];
      } else {
         $entity = ($this->obj->isField('entities_id')?$this->obj->getField('entities_id')
                                                      :$this->target_object->getField('entities_id'));
      }

      //If the entity administrator's address is defined, return it
      foreach ($DB->request('glpi_entitydatas', array('entities_id' => $entity)) as $data) {
         if (NotificationMail::isUserAddressValid($data['admin_reply'])) {
            return $data['admin_reply'];
         }
      }
      //Entity admin is not defined, return the global admin's address
      return $CFG_GLPI["admin_reply"];
   }


   /**
    * Get addresses by type of notification
    *
    * @param $data
    * @param $options
   **/
   function getAddressesByTarget($data, $options=array()) {

      //Look for all targets whose type is Notification::USER_TYPE
      switch ($data['type']) {
         //Notifications for one people
         case Notification::USER_TYPE :

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
               case Notification::AUTHOR :
                  $this->getItemAuthorAddress();
                  break;

               default :
                  //Maybe a target specific to a type
                  $this->getSpecificTargets($data,$options);
            }
            break;

         //Send to all the users of a group
         case Notification::GROUP_TYPE :
            $this->getUsersAddressesByGroup($data['items_id']);
            break;

         //Send to all the users of a group
         case Notification::SUPERVISOR_GROUP_TYPE :
            $this->getSupervisorAddressByGroup($data['items_id']);
            break;

         //Send to all the users of a profile
         case Notification::PROFILE_TYPE :
            $this->getUsersAddressesByProfile($data['items_id']);
            break;

         default :
            //Maybe a target specific to a type
            $this->getSpecificTargets($data,$options);
      }
   }


   /**
    * Get all data needed for template processing
    * Provides minimum informations for alerts
    * Can be overridden by each NotificationTartget class if needed
   **/
   function getDatasForTemplate($event, $options=array()) {
   }


   function getTargets() {
      return $this->target;
   }


   function getEntity() {
      /*
      if ($this->obj->isField('entities_id') != NOT_AVAILABLE) {
         return $this->obj->getField('entities_id');
      } else if ($this->target_object) {
         return $this->target_object->getField('entities_id');
      }
      return 0;
      */
      return $this->entity;
   }


   function clearAddressesList() {
      $this->target = array();
   }


   function getJoinProfileSql() {
      return "";
   }


   function getJoinSql() {
      return "";
   }


   function &getForTemplate($event, $options) {
      global $CFG_GLPI, $LANG;

      $this->datas = array();
      $this->addTagToList(array('tag'   => 'glpi.url',
                                'value' => $CFG_GLPI['root_doc'],
                                'label' => $LANG['setup'][227]));

      $this->getDatasForTemplate($event, $options);
      doHook('item_get_datas', $this);

      return $this->datas;
   }


   function getTags() {
      return $this->tag_descriptions;
   }


   function addTagToList($options = array()) {

      $p['tag']            = false;
      $p['value']          = true;
      $p['label']          = false;
      $p['events']         = NotificationTarget::TAG_FOR_ALL_EVENTS;
      $p['foreach']        = false;
      $p['lang']           = true;
      $p['allowed_values'] = array();

      foreach ($options as $key => $value) {
         $p[$key] = $value;
      }

      if ($p['tag']) {
         if (is_array($p['events'])) {
            $events = $this->getEvents();
            $tmp = array();

            foreach ($p['events'] as $event) {
               $tmp[$event] = $events[$event];
            }

            $p['events'] = $tmp;
         }

         if ($p['foreach']) {
            $tag = "##FOREACH".$p['tag']."## ##ENDFOREACH".$p['tag']."##";
            $this->tag_descriptions[NotificationTarget::TAG_VALUE][$tag] = $p;

          } else {
            if ($p['value']) {
               $tag = "##".$p['tag']."##";
               $this->tag_descriptions[NotificationTarget::TAG_VALUE][$tag] = $p;
            }

            if ($p['label']&&$p['lang']) {
               $tag = "##lang.".$p['tag']."##";
               $p['label'] = $p['label'];
               $this->tag_descriptions[NotificationTarget::TAG_LANGUAGE][$tag] = $p;
            }
         }
      }
   }

}
?>
