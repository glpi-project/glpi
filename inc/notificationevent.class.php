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
   static function raiseEvent($event,$item,$options=array()) {
      global $CFG_GLPI, $DB;

      logDebug("raise event : $event");
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
         $email_processed = array();
         $email_notprocessed = array();
         $template_processed = array();
         $signatures = array();

         $query = "SELECT `glpi_notifications`.*
                      FROM `glpi_notifications`
                     LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id` =
                                                   `glpi_notifications`.`entities_id`)
                     WHERE `glpi_notifications`.`itemtype`='$itemtype'
                        AND `glpi_notifications`.`event`='$event'";
          $query.= getEntitiesRestrictRequest(" AND",
                                              "glpi_notifications",
                                              'entities_id',
                                              $entity,
                                              true);
         $query.=" ORDER BY `glpi_entities`.`level` DESC";

         //Foreach notification
         foreach ($DB->request($query) as $data) {
            $targets = getAllDatasFromTable('glpi_notificationtargets',
                                            'notifications_id='.$data['id']);
            //Foreach notification targets
            foreach ($targets as $target) {
               $templates_id = $data['notificationtemplates_id'];

               $notificationtarget = NotificationTarget::getInstance($item);
               //Get all users affected by this notification
               $notificationtarget->getAddressesByTarget($target,$options);

               //Get template's informations
               $template = new NotificationTemplate;
               $template->getFromDB($templates_id);
               foreach ($notificationtarget->getTargets() as $template_id => $users_infos) {
                  $entity = $users_infos['entity'];
                  $email = $users_infos['email'];
                  $language = $users_infos['language'];

                  //If the user have not yet been notified
                  if (!isset($email_processed[$email])) {
                     //If ther user's language is the same as the template's one
                     if ($language == $template->getField('language')) {
                        //If the user was previously in the not notified users list, remove it
                        if (isset($email_notprocessed[$email])) {
                           unset($email_notprocessed[$email]);
                        }

                        //If the template's datas have not yet been collected, then do it
                        if (!isset($template_processed[$templates_id])) {
                           $template_processed[$templates_id] =
                              $template->getTemplateByLanguage($notificationtarget,
                                                               $language,
                                                               $event);
                        }

                        //Send notification to the user
                        $mailing_options['to'] = $users_infos['email'];
                        $mailing_options['from'] = $notificationtarget->getSender();
                        $mailing_options['replyto'] = $notificationtarget->getReplyTo();
                        $mailing_options['items_id'] = $item->getField('id');

                        //SIf the signature for this entity have not yet been collected, do it
                        if (!isset($signatures[$entity])) {
                            $signatures[$entity]= Notification::getMailingSignature($entity);
                        }

                        Notification::send ($event,
                                            $mailing_options,
                                            $template_processed[$templates_id],
                                            $entity,
                                            $signatures[$entity]);

                        $email_processed[$email] = $users_infos;
                     }
                     else {
                        $email_notprocessed[$email] = $users_infos;
                     }
                  }
               }
            }
         }

         if (!empty($email_notprocessed)) {
            //Get the default template, in case the user's language is not the one
            //of the selected template
            $default_template = NotificationTemplate::getDefault($itemtype);
            if ($default_template) {
               $notificationtarget = NotificationTarget::getInstance($item,$options);

               foreach ($email_notprocessed as $email => $users_infos) {
                  $entity = $users_infos['entity'];
                  $email = $users_infos['email'];
                  $language = $users_infos['language'];

                  //If the template's datas have not yet been collected, then do it
                  if (!isset($template_processed[$templates_id])) {
                     $template_language = $template->getTemplateByLanguage($notificationtarget,
                                                                           $language,
                                                                           $event);
                  }

                  $mailing_options['to'] = $email;
                  $mailing_options['from'] = $notificationtarget->getSender();
                  $mailing_options['replyto'] = $notificationtarget->getReplyTo();
                  $mailing_options['items_id'] = $item->getField('id');

                  //Store signature for each entity
                 if (!isset($signatures[$entity])) {
                     $signatures[$entity]= Notification::getMailingSignature($entity);
                  }
                  Notification::send ($event,
                                      $mailing_options,
                                      $template_processed[$templates_id],
                                      $entity,
                                      $signatures[$entity]);
               }
            }
         }
      }

      return true;
   }

   public function getValues() {
      return $$this->values;
   }
}
?>