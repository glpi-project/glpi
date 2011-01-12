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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

require_once(GLPI_PHPMAILER_DIR . "/class.phpmailer.php");

/**
 *  NotificationMail class extends phpmail and implements the NotificationInterface
**/
class NotificationMail extends phpmailer implements NotificationInterface {

   //! mailing type (new,attrib,followup,finish)
   var $mailtype = NULL;
   /** Job class variable - job to be mailed
    * @see Job
    */
   var $job = NULL;
   /** User class variable - user who make changes
    * @see User
    */
   var $user = NULL;
   /// Is the followupadded private ?
   var $followupisprivate = NULL;

   /// Set default variables for all new objects
   var $WordWrap = 80;
   /// Defaut charset
   var $CharSet = "utf-8";

   /**
    * Constructor
   **/
   function __construct() {
      global $CFG_GLPI;

      // Comes from config
      $this->SetLanguage("en", GLPI_PHPMAILER_DIR . "/language/");

      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $this->Mailer = "smtp";
         $this->Host   = $CFG_GLPI['smtp_host'].':'.$CFG_GLPI['smtp_port'];

         if ($CFG_GLPI['smtp_username'] != '') {
            $this->SMTPAuth = true;
            $this->Username = $CFG_GLPI['smtp_username'];
            $this->Password = decrypt($CFG_GLPI['smtp_passwd'], GLPIKEY);
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPSSL) {
            $this->SMTPSecure = "ssl";
         }

         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPTLS) {
            $this->SMTPSecure = "tls";
         }
      }

      if ($_SESSION['glpi_use_mode'] == DEBUG_MODE) {
         $this->do_debug = 3;
      }
   }


   /**
    * Determine if email is valid
    *
    * @param $address email to check
    * @param $options array options used
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

         if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
         } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
         } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
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
      global $CFG_GLPI,$LANG;

      $mmail = new NotificationMail;
      $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
      $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"]);
      $mmail->AddAddress($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"]);
      $mmail->Subject = "[GLPI] ".$LANG['mailing'][32];
      $mmail->Body    = $LANG['mailing'][31]."\n-- \n".$CFG_GLPI["mailing_signature"];

      if (!$mmail->Send()) {
         addMessageAfterRedirect($LANG['setup'][206], false, ERROR);
      } else {
         addMessageAfterRedirect($LANG['setup'][205]);
      }
   }


   /**
    * Format the mail sender to send
    *
    * @return mail sender email string
   */
   function getEntityAdminAddress() {
      global $CFG_GLPI,$DB;

      $query = "SELECT `admin_email` AS email
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->job->fields["entities_id"]."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $data = $DB->fetch_assoc($result);
            if (NotificationMail::isUserAddressValid($data["email"])) {
               return $data["email"];
            }
         }
      }
      return $CFG_GLPI["admin_email"];
   }


   function sendNotification($options=array()) {
      global $LANG;

      $mmail = new NotificationMail();
      $mmail->AddCustomHeader("Auto-Submitted: auto-generated");

      $mmail->SetFrom($options['from'], $options['fromname']);

      if ($options['replyto']) {
         $mmail->AddReplyTo($options['replyto'], $options['replytoname']);
      }
      $mmail->Subject  = $options['subject'];

      if (empty($options['content_html'])) {
         $mmail->isHTML(false);
         $mmail->Body = $options['content_text'];
      } else {
         $mmail->isHTML(true);
         $mmail->Body    = $options['content_html'];
         $mmail->AltBody = $options['content_text'];
      }

      $mmail->AddAddress($options['to'], $options['toname']);

      $mmail->MessageID = "GLPI-".$options["items_id"].".".time().".".rand(). "@".php_uname('n');

      $messageerror = $LANG['mailing'][47];

      if (!$mmail->Send()) {
         $senderror = true;
         addMessageAfterRedirect($messageerror."<br>".$mmail->ErrorInfo,true);
      } else {
         logInFile("mail", $LANG['tracking'][38]." ".$options['to']." : ".$options['subject']."\n");
      }

      $mmail->ClearAddresses();
      return true;
   }

}
?>