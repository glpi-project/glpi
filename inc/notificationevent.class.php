<?php
/*
 * @version $Id: entitydata.class.php 10111 2010-01-12 09:03:26Z walid $
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Class which manages notification events
 */
class NotificationEvent extends CommonDBTM {

   //Store values used in template
   var $values = array();

   static function dropdownEvents($itemtype,$value='') {
      $events = array();

      $target = NotificationTarget::getInstanceByType($itemtype);
      if ($target) {
         $events = $target->getEvents();
      }
      $events[''] = '-----';
      Dropdown::showFromArray('event',$events, array ('value'=>$value));
   }

   /**
    * Raise a notification event event
    * @param event the event raised for the itemtype
    * @param item the object which raised the event
    */
   static function raiseEvent($event,$item) {
      global $CFG_GLPI, $DB;

      //If notifications are enabled in GLPI's configuration
      if ($CFG_GLPI["use_mailing"]) {

         if ($item->isField('entities_id')) {
            $entity = $item->getField('entities_id');
         }
         else {
            //Objects don't necessary have an entities_id field (like DBConnection)
            $entity = 0;
         }

         $itemtype = $item->getType();

         $notifications = array();
         //Get all the targets
         $query = "SELECT DISTINCT `glpi_notificationtargets`.`type`,
                                   `glpi_notificationtargets`.`items_id`
                   FROM `glpi_notificationtargets`, `glpi_notifications`
                   WHERE `glpi_notifications`.`id` = `glpi_notificationtargets`.`notifications_id`
                      AND `glpi_notifications`.`itemtype`='$itemtype'
                        AND `glpi_notifications`.`event`='$event';";

         foreach ($DB->request($query) as $data) {
            //Get the first notification for this target
            $query = "SELECT `glpi_notifications`.*
                      FROM `glpi_notifications`
                      INNER JOIN `glpi_notificationtargets`
                         ON (`glpi_notificationtargets`.`notifications_id` = `glpi_notifications`.`id`
                            AND `glpi_notificationtargets`.`type`='".$data['type']."'
                              AND `glpi_notificationtargets`.`items_id`='".$data['items_id']."')
                     LEFT JOIN glpi_entities on (glpi_entities.id = glpi_notifications.entities_id)
                     WHERE `glpi_notifications`.`itemtype`='$itemtype'
                        AND `glpi_notifications`.`event`='$event'";
               $query.= getEntitiesRestrictRequest(" AND",
                                                   "glpi_notifications",
                                                   'entities_id',
                                                   $entity,
                                                   true);
               $query.=" ORDER BY glpi_entities.level DESC LIMIT 1";

            foreach ($DB->request($query) as $data2) {
               //Create a table which contains templates informations + targets associated with it
               if (!isset($notifications[$data2['notificationtemplates_id']])) {
                  $template = new NotificationTemplate;
                  $template->getFromDB($data2['notificationtemplates_id']);
                  $notifications[$data2['notificationtemplates_id']]['template'] = $template;
                  $notifications[$data2['notificationtemplates_id']]['targets'][] = $data;
               }
               else {
                  $notifications[$data2['notificationtemplates_id']]['targets'][] = $data;
               }
            }
         }

         //Store user's emails & language
         $target = NotificationTarget::getInstance($item);
         foreach ($notifications as $id => $values) {
            $template_targets = $values['targets'];
            foreach ($template_targets as $template_target) {
               //Get all users by target type
               $target->getAddressesByTarget($id,$template_target);
            }
         }

         $languages = array();
         //Get all the languages in which the template must be displayed
         foreach ($target->getTargets() as $template_id => $users_infos) {
            //Send mail for each user
            foreach ($users_infos as $email => $language) {
               if (NotificationMail::isUserAddressValid($email)) {
                  //Is template already processed in this language ?
                  if (!isset($languages[$template_id][$language])) {
                     $template = $notifications[$template_id]['template'];
                     $languages[$template_id][$language] =
                     $template->getTemplateByLanguage($target,$language,$event);
                  }
                  $options['to'] = $email;
                  $options['from'] = $target->getSender();
                  $options['replyto'] = $target->getReplyTo();
                  $options['items_id'] = $item->getField('id');
                  Notification::send ($event, $options, $languages[$template_id][$language]);

               }
            }
         }
        loadLanguage();
      }
      return true;
   }

   public function getValues() {
      return $$this->values;
   }
}
?>