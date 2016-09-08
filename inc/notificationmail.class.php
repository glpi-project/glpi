<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  NotificationMail class implements the NotificationInterface
**/
class NotificationMail implements NotificationInterface {

   /**
    * Determine if email is valid
    *
    * @param $address         email to check
    * @param $options   array of options used (by default 'checkdns'=>false)
    *     - checkdns :check dns entry
    *
    * @return boolean
    * from http://www.linuxjournal.com/article/9585
   **/
   static function isUserAddressValid($address, $options=array('checkdns'=>false)) {

      $checkdns = $options['checkdns'];
      $isValid  = true;
      $atIndex  = strrpos($address, "@");

      if (is_bool($atIndex) && !$atIndex) {
         $isValid = false;

      } else {
         $domain    = substr($address, $atIndex+1);
         $local     = substr($address, 0, $atIndex);
         $localLen  = strlen($local);
         $domainLen = strlen($domain);

         if (($localLen < 1) || ($localLen > 64)) {
            // local part length exceeded
            $isValid = false;
         } else if (($domainLen < 1) || ($domainLen > 255)) {
            // domain part length exceeded
            $isValid = false;
         } else if (($local[0] == '.') || ($local[$localLen-1] == '.')) {
            // local part starts or ends with '.'
            $isValid = false;
         } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
         } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
         } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                str_replace("\\\\","",$local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
               $isValid = false;
            }
         }

         if ($checkdns) {
            if ($isValid
                && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
               // domain not found in DNS
               $isValid = false;
            }

         } else if (!preg_match('/\\./', $domain) || !preg_match("/[a-zA-Z0-9]$/", $domain)) {
            // domain has no dots or do not end by alphenum char
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
      $mmail->AddAddress($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"]);
      $mmail->Subject = "[GLPI] ".__('Mail test');
      $mmail->Body    = __('This is a test email.')."\n-- \n".$CFG_GLPI["mailing_signature"];

      if (!$mmail->Send()) {
         Session::addMessageAfterRedirect(__('Failed to send test email to administrator'), false,
                                          ERROR);
      } else {
         Session::addMessageAfterRedirect(__('Test email sent to administrator'));
      }
   }


   /**
    * @param $options   array
   **/
   function sendNotification($options=array()) {

      $data = array();
      $data['itemtype']                             = $options['_itemtype'];
      $data['items_id']                             = $options['_items_id'];
      $data['notificationtemplates_id']             = $options['_notificationtemplates_id'];
      $data['entities_id']                          = $options['_entities_id'];

      $data["headers"]['Auto-Submitted']            = "auto-generated";
      $data["headers"]['X-Auto-Response-Suppress']  = "OOF, DR, NDR, RN, NRN";

      $data['sender']                               = $options['from'];
      $data['sendername']                           = $options['fromname'];

      if ($options['replyto']) {
         $data['replyto']       = $options['replyto'];
         $data['replytoname']   = $options['replytoname'];
      }

      $data['name']                                 = $options['subject'];

      $data['body_text']                            = $options['content_text'];
      if (!empty($options['content_html'])) {
         $data['body_html'] = $options['content_html'];
      }

      $data['recipient']                            = $options['to'];
      $data['recipientname']                        = $options['toname'];

      if (!empty($options['messageid'])) {
         $data['messageid'] = $options['messageid'];
      }

      if (isset($options['documents'])) {
         $data['documents'] = $options['documents'];
      }

      $mailqueue = new QueuedMail();

      if (!$mailqueue->add(Toolbox::addslashes_deep($data))) {
         $senderror = true;
         Session::addMessageAfterRedirect(__('Error inserting email to queue'), true);
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
?>