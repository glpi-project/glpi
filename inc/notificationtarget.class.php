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

      echo "<div class='center'>";

      echo "<form name='notificationtargets_form' id='notificationtargets_form'
             method='post' action=' ";
      echo getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['mailing'][121] . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<input type='hidden' name='notifications_id' value='$notifications_id'>";

      $item = new $notification->fields['itemtype'] ();
      $targets = $item->getNotficationTargets($_SESSION['glpiactive_entity']);
      $this->showNotificationTargets($notifications_id, $targets);
      echo "</tr>";
      echo "</table></form></div>";
   }

   function showNotificationTargets($notifications_id, $profiles) {
      global $LANG, $DB;

      $notification = new Notification;
      $canedit = $notification->can($notifications_id,'w');

      $options="";
      // Get User mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id` , `glpi_notificationtargets`.`id`
                FROM `glpi_notificationtargets`
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . USER_MAILING_TYPE . "'
                ORDER BY `glpi_notificationtargets`.`items_id`";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (isset($profiles[USER_MAILING_TYPE."_".$data["items_id"]])) {
               unset($profiles[USER_MAILING_TYPE."_".$data["items_id"]]);
            }
            switch ($data["items_id"]) {
               case ADMIN_MAILING :
                  $name = $LANG['setup'][237];
                  break;

               case ADMIN_ENTITY_MAILING :
                  $name = $LANG['setup'][237]." ".$LANG['entity'][0];
                  break;

               case ASSIGN_MAILING :
                  $name = $LANG['setup'][239];
                  break;

               case AUTHOR_MAILING :
                  $name = $LANG['job'][4];
                  break;

               case USER_MAILING :
                  $name = $LANG['common'][34] . " " . $LANG['common'][1];
                  break;

               case OLD_ASSIGN_MAILING :
                  $name = $LANG['setup'][236];
                  break;

               case TECH_MAILING :
                  $name = $LANG['common'][10];
                  break;

               case RECIPIENT_MAILING :
                  $name = $LANG['job'][3];
                  break;

               case ASSIGN_ENT_MAILING :
                  $name = $LANG['financial'][26];
                  break;

               case ASSIGN_GROUP_MAILING :
                  $name = $LANG['setup'][248];
                  break;

               case SUPERVISOR_ASSIGN_GROUP_MAILING :
                  $name = $LANG['common'][64]." ".$LANG['setup'][248];
                  break;

               case SUPERVISOR_AUTHOR_GROUP_MAILING :
                  $name = $LANG['common'][64]." ".$LANG['setup'][249];
                  break;

               default :
                  $name="&nbsp;";
                  break;
            }
            $options.= "<option value='" . $data["id"] . "'>" . $name . "</option>";

         }
      }
      // Get Profile mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id`, `glpi_notificationtargets`.`id`,
                       `glpi_profiles`.`name` AS prof
                FROM `glpi_notificationtargets`
                LEFT JOIN `glpi_profiles` ON (`glpi_notificationtargets`.`items_id` = `glpi_profiles`.`id`)
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . PROFILE_MAILING_TYPE . "'
                ORDER BY prof";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $options.= "<option value='" . $data["id"] . "'>" . $LANG['profiles'][22] . " " .
                        $data["prof"] . "</option>";
            if (isset($profiles[PROFILE_MAILING_TYPE."_".$data["items_id"]])) {
               unset($profiles[PROFILE_MAILING_TYPE."_".$data["items_id"]]);
            }
         }
      }
      // Get Group mailing
      $query = "SELECT `glpi_notificationtargets`.`items_id`, `glpi_notificationtargets`.`id`,
                       `glpi_groups`.`name` AS name
                FROM `glpi_notificationtargets`
                LEFT JOIN `glpi_groups` ON (`glpi_notificationtargets`.`items_id` = `glpi_groups`.`id`)
                WHERE `glpi_notificationtargets`.`notifications_id`='$notifications_id'
                      AND `glpi_notificationtargets`.`type`='" . GROUP_MAILING_TYPE . "'
                ORDER BY name;";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $options.= "<option value='" . $data["id"] . "'>" . $LANG['common'][35] . " " .
                        $data["name"] . "</option>";
            if (isset($profiles[GROUP_MAILING_TYPE."_".$data["items_id"]])) {
               unset($profiles[GROUP_MAILING_TYPE."_".$data["items_id"]]);
            }
         }
      }

      if ($canedit) {
         echo "<td class='right'>";
         if (count($profiles)) {
            echo "<select name='mailing_to_add[]' multiple size='5'>";
            foreach ($profiles as $key => $val) {
               list ($mailingtype, $items_id) = explode("_", $key);
               echo "<option value='$key'>" . $val . "</option>";
            }
            echo "</select>";
         }
         echo "</td><td class='center'>";
         if (count($profiles)) {
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
   function addToEmailList($notifications_id, $mail,$lang='') {
      global $CFG_GLPI;

      $new_mail=trim($mail);
      $new_lang=trim($lang);
      if (!empty($new_mail)) {
         if (isUserAddressValid($new_mail) && !isset($emails[$new_mail])) {
            $this->target[$notifications_id][$new_mail] = (empty($new_lang) ? $CFG_GLPI["language"] : $new_lang);
         }
      }
   }

   /**
    * Get GLPI's global administrator email
    */
   function getAdminEmail($notifications_id) {
      global $CFG_GLPI;
      $this->addToEmailList($notifications_id,$CFG_GLPI["admin_email"]);
   }

   /**
    * Get entity admin email
    */
   function getEntityAdminEmail($notifications_id) {
      global $DB;

      foreach ($DB->request('glpi_entitydatas',
                            array('entities_id'=>$this->entity)) as $data) {
         $this->addToEmailList($notifications_id,$data["email"]);
      }
   }

   function getUsersEmailsByGroup($notifications_id,$group_id) {
      global $DB;
      $query="SELECT `glpi_users`.`email` AS email, `glpi_users`.`language` AS lang
              FROM `glpi_groups_users`
              INNER JOIN `glpi_users`
                      ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_groups_users`.`groups_id`='".$group_id."'";
      foreach ($DB->request($query) as $data) {
         $this->addToEmailList($notifications_id,$data['email'], $data['lang']);
      }
   }

   static function getByNotificationIdAndEntity($notifications_id, $itemtype,$options=array()) {
      global $DB,$CFG_GLPI;

      $classname = 'NotificationTarget'.$itemtype;
      if (class_exists($classname)) {
         $notificationtarget = new $classname ();

         foreach ($DB->request($this->table,
                               array('notifications_id' => $notifications_id)) as $target) {
            $notificationtarget->getEmails($notifications_id,$itemtype,$target,$options);
         }
      }
      return $notificationtarget;
   }

   static function getDistinctUserSql() {
      return "SELECT DISTINCT `glpi_users`.`email` AS email,
                               `glpi_users`.`language` AS lang ";
   }

}
?>
