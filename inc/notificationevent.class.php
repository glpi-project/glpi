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

// ----------------------------------------------------------------------
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class which manages notification events
 */
class NotificationEvent extends CommonDBTM {

   static function dropdownEvents($itemtype,$value='') {

      $events = array();
      $target = NotificationTarget::getInstanceByType($itemtype);
      if ($target) {
         $events = $target->getAllEvents();
      }
      $events[''] = DROPDOWN_EMPTY_VALUE;
      Dropdown::showFromArray('event', $events, array ('value' => $value));
   }


   /**
    * Raise a notification event event
    *
    * @param $event the event raised for the itemtype
    * @param $item the object which raised the event
    * @param $options array options used
    * @param $label used for debugEvent()
    */
   static function raiseEvent($event, $item, $options=array(), $label='') {
      global $CFG_GLPI;

      //If notifications are enabled in GLPI's configuration
      if ($CFG_GLPI["use_mailing"]) {
         $email_processed = array();
         $email_notprocessed = array();
         //Get template's informations
         $template = new NotificationTemplate;

         $notificationtarget = NotificationTarget::getInstance($item,$event,$options);
         $entity = $notificationtarget->getEntity();
         //Foreach notification
         foreach (Notification::getNotificationsByEventAndType($event, $item->getType(),
                                                               $entity) as $data) {
            $targets = getAllDatasFromTable('glpi_notificationtargets',
                                            'notifications_id='.$data['id']);

            $notificationtarget->clearAddressesList();

            //Process more infos (for example for tickets)
            $notificationtarget->addAdditionnalInfosForTarget();

            //Foreach notification targets
            foreach ($targets as $target) {
               $template->getFromDB($data['notificationtemplates_id']);

               //Get all users affected by this notification
               $notificationtarget->getAddressesByTarget($target,$options);

               //Set notification's signature (the one which corresponds to the entity)
               $template->setSignature(Notification::getMailingSignature($entity));

               foreach ($notificationtarget->getTargets() as $template_id => $users_infos) {
                  if ($label || $notificationtarget->validateSendTo($users_infos)) {
                     //If the user have not yet been notified
                     if (!isset($email_processed[$users_infos['language']][$users_infos['email']])) {
                        //If ther user's language is the same as the template's one
                        if (isset($email_notprocessed[$users_infos['language']]
                                                     [$users_infos['email']])) {
                           unset($email_notprocessed[$users_infos['language']]
                                                    [$users_infos['email']]);
                        }

                        if ($template->getTemplateByLanguage($notificationtarget, $users_infos,
                                                            $event, $options)) {

                           //Send notification to the user
                           if ($label=='') {
                              Notification::send ($template->getDataToSend($notificationtarget,
                                                                           $users_infos, $options));
                           } else {
                              $notificationtarget->getFromDB($target['id']);
                              echo "<tr class='tab_bg_2'><td>".$label."</td>";
                              echo "<td>".$notificationtarget->getNameID()."</td>";
                              echo "<td>".$template->getName()." (".$users_infos['language'].")</td>";
                              echo "<td>".$users_infos['email']."</td>";
                              echo "</tr>";
                           }
                           $email_processed[$users_infos['language']][$users_infos['email']]
                                                                     = $users_infos;

                        } else {
                           $email_notprocessed[$users_infos['language']][$users_infos['email']]
                                                                        = $users_infos;
                        }
                     }
                  }
               }
            }
         }
      }
      unset($email_processed);
      unset($email_notprocessed);
      $template = null;
      return true;
   }

 /**
    * Display debug information for an object
    *
    * @param $item the object
    * @param $options array
    */
   static function debugEvent($item, $options=array()) {
      global $LANG;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['setup'][137].' - '.$LANG['setup'][704]."</th></tr>";

      $events = array();
      if ($target = NotificationTarget::getInstanceByType(get_class($item))) {
         $events = $target->getAllEvents();

         if (count($events)>0) {
            echo "<tr><th>".$LANG['mailing'][119].'</th><th>'.$LANG['mailing'][121]."</th>";
            echo "<th>".$LANG['mailing'][113].'</th><th>'.$LANG['mailing'][118]."</th></tr>";

            foreach ($events as $event => $label) {
               self::raiseEvent($event, $item, $options, $label);
            }

         } else  {
            echo "<tr class='tab_bg_2 center'><td colspan='4'>".$LANG['stats'][2]."</td></tr>";
         }
      }
      echo "</table>";
   }
}
?>