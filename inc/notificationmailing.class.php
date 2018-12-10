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
 *  NotificationMailing class implements the NotificationInterface
**/
class NotificationMailing implements NotificationInterface {

   /**
    * Check data
    *
    * @param mixed $value   The data to check (may differ for every notification mode)
    * @param array $options Optionnal special options (may be needed)
    *
    * @return boolean
   **/
   static function check($value, $options = []) {
      return self::isUserAddressValid($value, $options);
   }

   /**
    * Determine if email is valid
    *
    * @param string $address email to check
    * @param array  $options options used (by default 'checkdns'=>false)
    *     - checkdns :check dns entry
    *
    * @return boolean
   **/
   static function isUserAddressValid($address, $options = ['checkdns'=>false]) {
      //drop sanitize...
      $address = Toolbox::stripslashes_deep($address);
      $isValid = GLPIMailer::ValidateAddress($address);

      $checkdns = (isset($options['checkdns']) ? $options['checkdns'] :  false);
      if ($checkdns) {
         $domain    = substr($address, strrpos($address, '@')+1);
         if ($isValid
               && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
            // domain not found in DNS
            $isValid = false;
         }
      }
      return $isValid;
   }


   static function testNotification() {
      global $CFG_GLPI;

      $mmail = new GLPIMailer();

      $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
      // For exchange
      $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");
      $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"], false);

      $text = __('This is a test email.')."\n-- \n".$CFG_GLPI["mailing_signature"];
      $recipient = $CFG_GLPI['admin_email'];
      if (defined('GLPI_FORCE_MAIL')) {
         //force recipient to configured email address
         $recipient = GLPI_FORCE_MAIL;
         //add original email addess to message body
         $text .= "\n" . sprintf(__('Original email address was %1$s'), $CFG_GLPI['admin_email']);
      }

      $mmail->AddAddress($recipient, $CFG_GLPI["admin_email_name"]);
      $mmail->Subject = "[GLPI] ".__('Mail test');
      $mmail->Body    = $text;

      if (!$mmail->Send()) {
         Session::addMessageAfterRedirect(__('Failed to send test email to administrator'), false,
                                          ERROR);
         GLPINetwork::addErrorMessageAfterRedirect();
         return false;
      } else {
         Session::addMessageAfterRedirect(__('Test email sent to administrator'));
         return true;
      }
   }


   function sendNotification($options = []) {

      $data = [];
      $data['itemtype']                             = $options['_itemtype'];
      $data['items_id']                             = $options['_items_id'];
      $data['notificationtemplates_id']             = $options['_notificationtemplates_id'];
      $data['entities_id']                          = $options['_entities_id'];

      $data["headers"]['Auto-Submitted']            = "auto-generated";
      $data["headers"]['X-Auto-Response-Suppress']  = "OOF, DR, NDR, RN, NRN";

      $data['sender']                               = $options['from'];
      $data['sendername']                           = $options['fromname'];

      if (isset($options['replyto']) && $options['replyto']) {
         $data['replyto']       = $options['replyto'];
         if (isset($options['replytoname'])) {
            $data['replytoname']   = $options['replytoname'];
         }
      }

      $data['name']                                 = $options['subject'];

      $data['body_text']                            = $options['content_text'];
      if (!empty($options['content_html'])) {
         $data['body_html'] = $options['content_html'];
      }

      $data['recipient']                            = Toolbox::stripslashes_deep($options['to']);
      $data['recipientname']                        = $options['toname'];

      if (!empty($options['messageid'])) {
         $data['messageid'] = $options['messageid'];
      }

      if (isset($options['documents'])) {
         $data['documents'] = $options['documents'];
      }

      $data['mode'] = Notification_NotificationTemplate::MODE_MAIL;

      $queue = new QueuedNotification();

      if (!$queue->add(Toolbox::addslashes_deep($data))) {
         Session::addMessageAfterRedirect(__('Error inserting email to queue'), true, ERROR);
         return false;
      } else {
         //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
         Toolbox::logInFile("mail",
                            sprintf(__('%1$s: %2$s'),
                                    sprintf(__('An email to %s was added to queue'),
                                            $options['to']),
                                    $options['subject']."\n"));
      }

      return true;
   }
}
