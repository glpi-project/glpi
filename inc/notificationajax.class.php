<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  NotificationAjax
**/
class NotificationAjax implements NotificationInterface {

   /**
    * Check data
    *
    * @param mixed $value   The data to check (may differ for every notification mode)
    * @param array $options Optionnal special options (may be needed)
    *
    * @return boolean
   **/
   static function check($value, $options = []) {
      //waiting for a user ID
      $value = (int)$value;
      return $value > 0;
   }

   static function testNotification() {
      $instance = new self();
      return $instance->sendNotification([
         '_itemtype'                   => 'NotificationAjax',
         '_items_id'                   => 1,
         '_notificationtemplates_id'   => 0,
         '_entities_id'                => 0,
         'fromname'                    => 'TEST',
         'subject'                     => 'Test notification',
         'content_text'                => "Hello, this is a test notification.",
         'to'                          => Session::getLoginUserID()
      ]);
   }


   function sendNotification($options = []) {

      $data = [];
      $data['itemtype']                             = $options['_itemtype'];
      $data['items_id']                             = $options['_items_id'];
      $data['notificationtemplates_id']             = $options['_notificationtemplates_id'];
      $data['entities_id']                          = $options['_entities_id'];

      $data['sendername']                           = $options['fromname'];

      $data['name']                                 = $options['subject'];
      $data['body_text']                            = $options['content_text'];
      $data['recipient']                            = $options['to'];

      $data['mode'] = Notification_NotificationTemplate::MODE_AJAX;

      $queue = new QueuedNotification();

      if (!$queue->add(Toolbox::addslashes_deep($data))) {
         Session::addMessageAfterRedirect(__('Error inserting browser notification to queue'), true, ERROR);
         return false;
      } else {
         //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
         Toolbox::logInFile("notification",
                            sprintf(__('%1$s: %2$s'),
                                    sprintf(__('A browser notification to %s was added to queue'),
                                            $options['to']),
                                    $options['subject']."\n"));
      }

      return true;
   }

   /**
    * Get users own notifications
    *
    * @return array|false
    */
   public static function getMyNotifications() {
      global $DB, $CFG_GLPI;

      $return = [];
      if ($CFG_GLPI['notifications_ajax']) {
         $iterator = $DB->request([
            'FROM'   => 'glpi_queuednotifications',
            'WHERE'  => [
               'is_deleted'   => false,
               'recipient'    => Session::getLoginUserID(),
               'mode'         => Notification_NotificationTemplate::MODE_AJAX
            ]
         ]);

         if ($iterator->numrows()) {
            while ($row = $iterator->next()) {
               $url = null;
               if ($row['itemtype'] != 'NotificationAjax' &&
                  method_exists($row['itemtype'], 'getFormURL')
               ) {
                  $item = new $row['itemtype'];
                  $url = $item->getFormURL(false)."?id={$row['items_id']}";
               }

               $return[] = [
                  'id'     => $row['id'],
                  'title'  => $row['name'],
                  'body'   => $row['body_text'],
                  'url'    => $url
               ];
            }
         }
      }

      if (count($return)) {
         return $return;
      } else {
         return false;
      }
   }

   /**
    * Mark raised notification as deleted
    *
    * @param integer $id Notification id
    *
    * @return void
    */
   static public function raisedNotification($id) {
      global $DB;

      $now = date('Y-m-d H:i:s');
      $DB->update(
         'glpi_queuednotifications', [
            'sent_time'    => $now,
            'is_deleted'   => 1
         ], [
            'id'        => $id,
            'recipient' => Session::getLoginUserID()
         ]
      );
   }
}
