<?php
/*
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
 * MailCollector class
 *
 * Merge with collect GLPI system after big modification in it
 *
 * modif and debug by  INDEPNET Development Team.
 * Original class ReceiveMail 1.0 by Mitul Koradia Created: 01-03-2006
 * Description: Reciving mail With Attechment
 * Email: mitulkoradia@gmail.com
**/
class MailCollector  extends CommonDBTM {

   // Specific one
   /// working charset of the mail
   public $charset         = "";
   /// IMAP / POP connection
   public $marubox         = '';
   /// ID of the current message
   public $mid             = -1;
   /// structure used to store the mail structure
   public $structure       = false;
   /// structure used to store files attached to a mail
   public $files;
   /// structure used to store alt files attached to a mail
   public $altfiles;
   /// Tag used to recognize embedded images of a mail
   public $tags;
   /// Message to add to body to build ticket
   public $addtobody;
   /// Number of fetched emails
   public $fetch_emails    = 0;
   /// Maximum number of emails to fetch : default to 10
   public $maxfetch_emails = 10;
   /// Max size for attached files
   public $filesize_max    = 0;
   /// Body converted
   public $body_converted  = false;

   public $dohistory       = true;

   static $rightname       = 'config';

   // Destination folder
   const REFUSED_FOLDER  = 'refused';
   const ACCEPTED_FOLDER = 'accepted';


   static function getTypeName($nb=0) {
      return _n('Receiver', 'Receivers', $nb);
   }


   static function canCreate() {
      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return static::canUpdate();
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuOptions() {

      if (static::canView()) {
         $options['options']['notimportedemail']['links']['search']
                                          = '/front/notimportedemail.php';
         return $options;
      }
      return false;
   }


   function post_getEmpty() {
      global $CFG_GLPI;

      $this->fields['filesize_max'] = $CFG_GLPI['default_mailcollector_filesize_max'];
      $this->fields['is_active']    = 1;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if (isset($input["passwd"])) {
         if (empty($input["passwd"])) {
            unset($input["passwd"]);
         } else {
            $input["passwd"] = Toolbox::encrypt(stripslashes($input["passwd"]), GLPIKEY);
         }
      }

      if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
         $input['passwd'] = '';
      }

      if (isset($input['mail_server']) && !empty($input['mail_server'])) {
         $input["host"] = Toolbox::constructMailServerConfig($input);
      }

      if (isset($input['name']) && !NotificationMail::isUserAddressValid($input['name'])) {
         Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
      }

      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (isset($input["passwd"])) {
         if (empty($input["passwd"])) {
            unset($input["passwd"]);
         } else {
            $input["passwd"] = Toolbox::encrypt(stripslashes($input["passwd"]), GLPIKEY);
         }
      }

      if (isset($input['mail_server']) && !empty($input['mail_server'])) {
         $input["host"] = Toolbox::constructMailServerConfig($input);
      }

      if (!NotificationMail::isUserAddressValid($input['name'])) {
         Session::addMessageAfterRedirect(__('Invalid email address'), false, ERROR);
      }

      return $input;
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               return _n('Action', 'Actions', Session::getPluralNumber());
         }
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == __CLASS__) {
         $item->showGetMessageForm($item->getID());
      }
      return true;
   }


   /**
    * Print the mailgate form
    *
    * @param $ID        integer  Id of the item to print
    * @param $options   array
    *     - target filename : where to go when done.
    *
    * @return boolean item found
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $options['colspan'] = 1;
      $this->showFormHeader($options);

      if (!function_exists('mb_list_encodings')
          || !function_exists('mb_convert_encoding')) {
         echo "<tr class='tab_bg_1'>".
              "<td colspan='2'>".__('mbstring extension not found. Warning with charsets used.').
              "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".sprintf(__('%1$s (%2$s)'), __('Name'), __('Email address')).
           "</td><td>";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>";

      if ($this->fields['errors']) {
         echo "<tr class='tab_bg_1_2'><td>".__('Connection errors')."</td>";
         echo "<td>".$this->fields['errors']."</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'><td>".__('Active')."</td><td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td></tr>";

      $type = Toolbox::showMailServerConfig($this->fields["host"]);

      echo "<tr class='tab_bg_1'><td>".__('Login')."</td><td>";
      Html::autocompletionTextField($this, "login");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Password')."</td>";
      echo "<td><input type='password' name='passwd' value='' size='20' autocomplete='off'>";
      if ($ID > 0) {
         echo "<input type='checkbox' name='_blank_passwd'>&nbsp;".__('Clear');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Use Kerberos authentication') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_kerberos", $this->fields["use_kerberos"]);
      echo "</td></tr>\n";


      if ($type != "pop") {
         echo "<tr class='tab_bg_1'><td>" . __('Accepted mail archive folder (optional)') . "</td>";
         echo "<td><input size='30' type='text' name='accepted' value=\"".$this->fields['accepted']."\">";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'><td>" . __('Refused mail archive folder (optional)') . "</td>";
         echo "<td><input size='30' type='text' name='refused' value=\"".$this->fields['refused']."\">";
         echo "</td></tr>\n";
      }


      echo "<tr class='tab_bg_1'>";
      echo "<td width='200px'> ". __('Maximum size of each file imported by the mails receiver').
           "</td><td>";
      self::showMaxFilesize('filesize_max', $this->fields["filesize_max"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Use mail date, instead of collect one') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_mail_date", $this->fields["use_mail_date"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Comments')."</td>";
      echo "<td><textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";

      if ($ID > 0) {
         echo "<br>";
         //TRANS: %s is the datetime of update
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      return true;
   }


   /**
    * @param $ID
   **/
   function showGetMessageForm($ID) {

      echo "<br><br><div class='center'>";
      echo "<form name='form' method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='submit' name='get_mails' value=\""._sx('button','Get email tickets now').
             "\" class='submit'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'is_active';
      $tab[2]['name']            = __('Active');
      $tab[2]['datatype']        = 'bool';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'host';
      $tab[3]['name']            = __('Connection string');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'string';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'login';
      $tab[4]['name']            = __('Login');
      $tab[4]['massiveaction']   = false;
      $tab[4]['datatype']        = 'string';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'filesize_max';
      $tab[5]['name']            = __('Maximum size of each file imported by the mails receiver');
      $tab[5]['datatype']        = 'integer';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'accepted';
      $tab[20]['name']           = __('Accepted mail archive folder (optional)');
      $tab[20]['datatype']       = 'string';

      $tab[21]['table']          = $this->getTable();
      $tab[21]['field']          = 'refused';
      $tab[21]['name']           = __('Refused mail archive folder (optional)');
      $tab[21]['datatype']       = 'string';

      $tab[22]['table']           = $this->getTable();
      $tab[22]['field']           = 'errors';
      $tab[22]['name']            = __('Connection errors');
      $tab[22]['datatype']        = 'integer';

      return $tab;
   }


   /**
    * @param $emails_ids   array
    * @param $action                (default 0)
    * @param $entity                (default 0)
   **/
   function deleteOrImportSeveralEmails($emails_ids=array(), $action=0, $entity=0) {
      global $DB;

      $mailbox_id = 0;
      $query      = "SELECT *
                     FROM `glpi_notimportedemails`
                     WHERE `id` IN (".implode(',',$emails_ids).")
                     ORDER BY `mailcollectors_id`";

      $todelete = array();
      foreach ($DB->request($query) as $data) {
         $todelete[$data['mailcollectors_id']][$data['messageid']] = $data;
      }
      $ticket = new Ticket();
      foreach ($todelete as $mailcollector_id => $rejected) {
         if ($this->getFromDB($mailcollector_id)) {
            $this->mid          = -1;
            $this->fetch_emails = 0;
            //Connect to the Mail Box
            $this->connect();
            // Get Total Number of Unread Email in mail box
            $tot = $this->getTotalMails(); //Total Mails in Inbox Return integer value

            for($i=1 ; $i<=$tot ; $i++) {
               $head = $this->getHeaders($i);
               if (isset($rejected[$head['message_id']])) {
                  if ($action == 1) {
                     $tkt = array();
                     $tkt = $this->buildTicket($i, array('mailgates_id' => $mailcollector_id,
                                                         'play_rules'   => false));
                     $tkt['_users_id_requester'] = $rejected[$head['message_id']]['users_id'];
                     $tkt['entities_id']         = $entity;
                     $ticket->add($tkt);
                     $folder = self::ACCEPTED_FOLDER;
                  } else {
                     $folder = self::REFUSED_FOLDER;
                  }
                  //Delete email
                  if ($this->deleteMails($i, $folder)) {
                     $rejectedmail = new NotImportedEmail();
                     $rejectedmail->delete(array('id' => $rejected[$head['message_id']]['id']));
                  }
                  // Unset managed
                  unset($rejected[$head['message_id']]);
               }
            }

            // Email not present in mailbox
            if (count($rejected)) {
               $clean = array('<' => '',
                              '>' => '');
               foreach ($rejected as $id => $data) {
                  if ($action == 1) {
                     Session::addMessageAfterRedirect(sprintf(__('Email %s not found. Impossible import.'),
                                                              strtr($id, $clean)),
                                                      false, ERROR);
                  } else { // Delete data in notimportedemail table
                     $rejectedmail = new NotImportedEmail();
                     $rejectedmail->delete(array('id' => $data['id']));
                  }
               }
            }
            imap_expunge($this->marubox);
            $this->close_mailbox();
         }
      }
   }


   /**
    * Constructor
    *
    * @param $mailgateID   ID of the mailgate
    * @param $display      display messages in MessageAfterRedirect or just return error (default 0=)
    *
    * @return if $display = false return messages result string
   **/
   function collect($mailgateID, $display=0) {
      global $CFG_GLPI;

      if ($this->getFromDB($mailgateID)) {
         $this->mid          = -1;
         $this->fetch_emails = 0;
         //Connect to the Mail Box
         $this->connect();
         $rejected = new NotImportedEmail();

         // Clean from previous collect (from GUI, cron already truncate the table)
         $rejected->deleteByCriteria(array('mailcollectors_id' => $this->fields['id']));

         if ($this->marubox) {
            // Get Total Number of Unread Email in mail box
            $tot         = $this->getTotalMails(); //Total Mails in Inbox Return integer value
            $error       = 0;
            $refused     = 0;
            $blacklisted = 0;

            for ($i=1 ; ($i <= $tot) && ($this->fetch_emails < $this->maxfetch_emails) ; $i++) {
               $tkt = $this->buildTicket($i, array('mailgates_id' => $mailgateID,
                                                   'play_rules'   => true));

               //Indicates that the mail must be deleted from the mailbox
               $delete_mail = false;

               //If entity assigned, or email refused by rule, or no user and no supplier associated with the email
               $user_condition = ($CFG_GLPI["use_anonymous_helpdesk"]
                                  || (isset($tkt['_users_id_requester'])
                                     && ($tkt['_users_id_requester'] > 0))
                                  || (isset($tkt['_supplier_email'])
                                      && $tkt['_supplier_email']));

               $rejinput                      = array();
               $rejinput['mailcollectors_id'] = $mailgateID;
               if (!$tkt['_blacklisted']) {
                  $rejinput['from']              = $tkt['_head']['from'];
                  $rejinput['to']                = $tkt['_head']['to'];
                  $rejinput['users_id']          = $tkt['_users_id_requester'];
                  $rejinput['subject']           = $this->textCleaner($tkt['_head']['subject']);
                  $rejinput['messageid']         = $tkt['_head']['message_id'];
               }
               $rejinput['date']              = $_SESSION["glpi_currenttime"];

               // Manage blacklisted emails
               if (isset($tkt['_blacklisted']) && $tkt['_blacklisted']) {
                  $this->deleteMails($i, self::REFUSED_FOLDER);
                  $blacklisted++;
               // entities_id set when new ticket / tickets_id when new followup
               } else if (((isset($tkt['entities_id']) || isset($tkt['tickets_id']))
                           && $user_condition)
                          || isset($tkt['_refuse_email_no_response'])
                          || isset($tkt['_refuse_email_with_response'])) {

                  if (isset($tkt['_refuse_email_with_response'])) {
                     $this->sendMailRefusedResponse($tkt['_head']['from'], $tkt['name']);
                     $delete_mail = self::REFUSED_FOLDER;
                     $refused++;
                  } else if (isset($tkt['_refuse_email_no_response'])) {
                     $delete_mail = self::REFUSED_FOLDER;
                     $refused++;
                  } else if (isset($tkt['entities_id'])
                             || isset($tkt['tickets_id'])) {

                     // Is a mail responding of an already existing ticket ?
                     if (isset($tkt['tickets_id']) ) {
                        $fup = new TicketFollowup();
                        if ($fup->add($tkt)) {
                           $delete_mail = self::ACCEPTED_FOLDER;
                        } else {
                           $error++;
                           $rejinput['reason'] = NotImportedEmail::FAILED_INSERT;
                           $rejected->add($rejinput);
                        }

                     } else { // New ticket
                        $track = new Ticket();
                        if ($track->add($tkt)) {
                           $delete_mail = self::ACCEPTED_FOLDER;
                        } else {
                           $error++;
                           $rejinput['reason'] = NotImportedEmail::FAILED_INSERT;
                           $rejected->add($rejinput);
                        }
                     }

                  } else {
                     // Case never raise
                     $delete_mail = self::REFUSED_FOLDER;
                     $refused++;
                  }
                  //Delete Mail from Mail box if ticket is added successfully
                  if ($delete_mail) {
                     $this->deleteMails($i, $delete_mail);
                  }

               } else {
                  if (!$tkt['_users_id_requester']) {
                     $rejinput['reason'] = NotImportedEmail::USER_UNKNOWN;

                  } else {
                     $rejinput['reason'] = NotImportedEmail::MATCH_NO_RULE;
                  }
                  $refused++;
                  $rejected->add($rejinput);
                  $this->deleteMails($i, self::REFUSED_FOLDER);
               }
               $this->fetch_emails++;
            }
            imap_expunge($this->marubox);
            $this->close_mailbox();   //Close Mail Box

            //TRANS: %1$d, %2$d, %3$d, %4$d and %5$d are number of messages
            $msg = sprintf(__('Number of messages: available=%1$d, retrieved=%2$d, refused=%3$d, errors=%4$d, blacklisted=%5$d'),
                           $tot, $this->fetch_emails, $refused, $error, $blacklisted);
            if ($display) {
               Session::addMessageAfterRedirect($msg, false, ($error ? ERROR : INFO));
            } else {
               return $msg;
            }

         } else {
            $msg = __('Could not connect to mailgate server');
            if ($display) {
               Session::addMessageAfterRedirect($msg, false, ERROR);
            } else {
               return $msg;
            }
         }

      } else {
         //TRANS: %s is the ID of the mailgate
         $msg = sprintf(__('Could not find mailgate %d'), $mailgateID);
         if ($display) {
            Session::addMessageAfterRedirect($msg, false, ERROR);
         } else {
            return $msg;
         }
      }
   }


   /** function buildTicket - Builds,and returns, the major structure of the ticket to be entered.
    *
    * @param $i                  mail ID
    * @param $options   array    of possible options
    *
    * @return ticket fields array
    */
   function buildTicket($i, $options=array()) {
      global $CFG_GLPI;

      $play_rules = (isset($options['play_rules']) && $options['play_rules']);
      $head       = $this->getHeaders($i); // Get Header Info Return Array Of Headers
                                           // **Key Are (subject,to,toOth,toNameOth,from,fromName)
      $tkt                 = array();
      $tkt['_blacklisted'] = false;
      // For RuleTickets
      $tkt['_mailgate']    = $options['mailgates_id'];


      // Use mail date if it's defined
      if ($this->fields['use_mail_date']) {
         $tkt['date'] = $head['date'];
      }
      // Detect if it is a mail reply
      $glpi_message_match = "/GLPI-([0-9]+)\.[0-9]+\.[0-9]+@\w*/";

      // Check if email not send by GLPI : if yes -> blacklist
      if (!isset($head['message_id'])
          || preg_match($glpi_message_match, $head['message_id'], $match)) {
         $tkt['_blacklisted'] = true;
         return $tkt;
      }
      // manage blacklist
      $blacklisted_emails   = Blacklist::getEmails();
      // Add name of the mailcollector as blacklisted
      $blacklisted_emails[] = $this->fields['name'];
      if (Toolbox::inArrayCaseCompare($head['from'], $blacklisted_emails)) {
         $tkt['_blacklisted'] = true;
         return $tkt;
      }

      // max size = 0 : no import attachments
      if ($this->fields['filesize_max'] > 0) {
         if (is_writable(GLPI_TMP_DIR)) {
            $tkt['_filename'] = $this->getAttached($i, GLPI_TMP_DIR."/", $this->fields['filesize_max']);
            $tkt['_tag']      = $this->tags;
         } else {
            //TRANS: %s is a directory
            Toolbox::logInFile('mailgate', sprintf(__('%s is not writable'), GLPI_TMP_DIR."/"));
         }
      }
      //  Who is the user ?
      $tkt['_users_id_requester']                              = User::getOrImportByEmail($head['from']);
      $tkt["_users_id_requester_notif"]['use_notification'][0] = 1;
      // Set alternative email if user not found / used if anonymous mail creation is enable
      if (!$tkt['_users_id_requester']) {
         $tkt["_users_id_requester_notif"]['alternative_email'][0] = $head['from'];
      }

      // Add to and cc as additional observer if user found
      if (count($head['ccs'])) {
         foreach ($head['ccs'] as $cc) {
            if (($cc != $head['from'])
                && !Toolbox::inArrayCaseCompare($cc, $blacklisted_emails) // not blacklisted emails
                && (($tmp = User::getOrImportByEmail($cc)) > 0)) {
               $nb = (isset($tkt['_users_id_observer']) ? count($tkt['_users_id_observer']) : 0);
               $tkt['_users_id_observer'][$nb] = $tmp;
               $tkt['_users_id_observer_notif']['use_notification'][$nb] = 1;
               $tkt['_users_id_observer_notif']['alternative_email'][$nb] = $cc;
            }
         }
      }

      if (count($head['tos'])) {
         foreach ($head['tos'] as $to) {
            if (($to != $head['from'])
                && !Toolbox::inArrayCaseCompare($to, $blacklisted_emails) // not blacklisted emails
                && (($tmp = User::getOrImportByEmail($to)) > 0)) {
                   $nb = (isset($tkt['_users_id_observer']) ? count($tkt['_users_id_observer']) : 0);
                   $tkt['_users_id_observer'][$nb] = $tmp;
                   $tkt['_users_id_observer_notif']['use_notification'][$nb] = 1;
                   $tkt['_users_id_observer_notif']['alternative_email'][$nb] = $to;
            }
         }
      }

      // Auto_import
      $tkt['_auto_import']           = 1;
      // For followup : do not check users_id = login user
      $tkt['_do_not_check_users_id'] = 1;
      $body                          = $this->getBody($i);

      // Do it before using charset variable
      $head['subject']               = $this->decodeMimeString($head['subject']);
      $tkt['_head']                  = $head;

      if (!empty($this->charset)
          && !$this->body_converted
          && mb_detect_encoding($body) != 'UTF-8') {
         $body                 = Toolbox::encodeInUtf8($body,$this->charset);
         $this->body_converted = true;
      }

      if (!Toolbox::seems_utf8($body)) {
         $tkt['content'] = Toolbox::encodeInUtf8($body);
      } else {
         $tkt['content'] = $body;
      }

      // See In-Reply-To field
      if (isset($head['in_reply_to'])) {
         if (preg_match($glpi_message_match, $head['in_reply_to'], $match)) {
            $tkt['tickets_id'] = intval($match[1]);
         }
      }

      // See in References
      if (!isset($tkt['tickets_id'])
          && isset($head['references'])) {
         if (preg_match($glpi_message_match, $head['references'], $match)) {
            $tkt['tickets_id'] = intval($match[1]);
         }
      }

      // See in title
      if (!isset($tkt['tickets_id'])
          && preg_match('/\[.+#(\d+)\]/',$head['subject'],$match)) {
         $tkt['tickets_id'] = intval($match[1]);
      }

      // Double encoding for > and < char to avoid misinterpretations
      $tkt['content'] = str_replace(array('&lt;', '&gt;'), array('&amp;lt;', '&amp;gt;'), $tkt['content']);

      $is_html = false;
      //If files are present and content is html
      if (isset($this->files)
          && count($this->files)
          && ($tkt['content'] != strip_tags($tkt['content']))
          && !isset($tkt['tickets_id'])) {
         $is_html = true;
         $tkt['content'] = Ticket::convertContentForTicket($tkt['content'],
                                                           array_merge($this->files, $this->altfiles),
                                                           $this->tags);
      }

      // Clean mail content
      $striptags = true;
      if ($CFG_GLPI["use_rich_text"] && !isset($tkt['tickets_id'])) {
         $striptags = false;
      }
      $tkt['content'] = $this->cleanMailContent(Html::entities_deep($tkt['content']), $striptags);

      if ($is_html && !isset($tkt['tickets_id'])) {
         $tkt['content'] = nl2br($tkt['content']);
      }

      $tkt['_supplier_email'] = false;
      // Found ticket link
      if (isset($tkt['tickets_id'])) {
         // it's a reply to a previous ticket
         $job = new Ticket();
         $tu  = new Ticket_User();
         $st  = new Supplier_Ticket();

         // Check if ticket  exists and users_id exists in GLPI
         /// TODO check if users_id have right to add a followup to the ticket
         if ($job->getFromDB($tkt['tickets_id'])
             && ($job->fields['status'] != CommonITILObject::CLOSED)
             && ($CFG_GLPI['use_anonymous_followups']
                 || ($tkt['_users_id_requester'] > 0)
                 || $tu->isAlternateEmailForITILObject($tkt['tickets_id'], $head['from'])
                 || ($tkt['_supplier_email'] = $st->isSupplierEmail($tkt['tickets_id'],
                                                                    $head['from'])))) {

            if ($tkt['_supplier_email']) {
               $tkt['content'] = sprintf(__('From %s'), $head['from'])."\n\n".$tkt['content'];
            }

            $content        = explode("\n", $tkt['content']);
            $tkt['content'] = "";
            $to_keep        = array();

            // Move requester to author of followup :
            $tkt['users_id'] = $tkt['_users_id_requester'];

            $begin_strip     = -1;
            $end_strip       = -1;
            $begin_match     = "/".NotificationTargetTicket::HEADERTAG.".*".
                                 NotificationTargetTicket::HEADERTAG."/";
            $end_match       = "/".NotificationTargetTicket::FOOTERTAG.".*".
                                 NotificationTargetTicket::FOOTERTAG."/";
            foreach ($content as $ID => $val) {
               // Get first tag for begin
               if ($begin_strip < 0) {
                  if (preg_match($begin_match,$val)) {
                     $begin_strip = $ID;
                  }
               }
               // Get last tag for end
               if ($begin_strip >= 0) {
                  if (preg_match($end_match,$val)) {
                     $end_strip = $ID;
                     continue;
                  }
               }
            }

            if ($begin_strip >= 0) {
               // Clean first and last lines
               $content[$begin_strip] = preg_replace($begin_match,'',$content[$begin_strip]);
            }
            if ($end_strip >= 0) {
               // Clean first and last lines
               $content[$end_strip] = preg_replace($end_match,'',$content[$end_strip]);
            }

            if ($begin_strip >= 0) {
               $length = count($content);
               // Use end strip if set
               if (($end_strip >= 0) && ($end_strip < $length)) {
                  $length = $end_strip;
               }

               for ($i = ($begin_strip+1); $i < $length; $i++) {
                  unset($content[$i]);
               }
            }

            $to_keep = array();
            // Aditional clean for thunderbird
            foreach ($content as $ID => $val) {
               if (!isset($val[0]) || ($val[0] != '>')) {
                  $to_keep[$ID] = $ID;
               }
            }

            $tkt['content'] = "";
            foreach ($to_keep as $ID ) {
               $tkt['content'] .= $content[$ID]."\n";
            }

            // Do not play rules for followups : WRONG : play rules only for refuse options
            //$play_rules = false;

         } else {
            // => to handle link in Ticket->post_addItem()
            $tkt['_linkedto'] = $tkt['tickets_id'];
            unset($tkt['tickets_id']);
         }
      }


      // Add message from getAttached
      if ($this->addtobody) {
         $tkt['content'] .= $this->addtobody;
      }

      $tkt['name'] = $this->textCleaner($head['subject']);
      if (!Toolbox::seems_utf8($tkt['name'])) {
         $tkt['name'] = Toolbox::encodeInUtf8($tkt['name']);
      }

      if (!isset($tkt['tickets_id'])) {
         // Which entity ?
         //$tkt['entities_id']=$this->fields['entities_id'];
         //$tkt['Subject']= $head['subject'];   // not use for the moment
         // Medium
         $tkt['urgency']  = "3";
         // No hardware associated
         $tkt['itemtype'] = "";
         // Mail request type

      } else {
         // Reopen if needed
         $tkt['add_reopen'] = 1;
      }

      $tkt['requesttypes_id'] = RequestType::getDefault('mail');

      if ($play_rules) {
         $rule_options['ticket']              = $tkt;
         $rule_options['headers']             = $head;
         $rule_options['mailcollector']       = $options['mailgates_id'];
         $rule_options['_users_id_requester'] = $tkt['_users_id_requester'];
         $rulecollection                      = new RuleMailCollectorCollection();
         $output                              = $rulecollection->processAllRules(array(), array(),
                                                                                 $rule_options);

         // New ticket : compute all
         if (!isset($tkt['tickets_id'])) {
            foreach ($output as $key => $value) {
               $tkt[$key] = $value;
            }

         } else { // Followup only copy refuse data
            $tkt['requesttypes_id'] = RequestType::getDefault('mailfollowup');
            $tobecopied = array('_refuse_email_no_response', '_refuse_email_with_response');
            foreach ($tobecopied as $val) {
               if (isset($output[$val])) {
                  $tkt[$val] = $output[$val];
               }
            }
         }
      }

      $tkt = Toolbox::addslashes_deep($tkt);
      return $tkt;
   }


   /** Clean mail content : HTML + XSS + blacklisted content
    *
    * @since version 0.85
    *
    * @param $string text to clean
    * @param $striptags remove html tags
    *
    * @return cleaned text
   **/
   function cleanMailContent($string, $striptags = true) {
      global $DB;

      // Delete html tags
      $string = Html::clean($string, $striptags, 2);

      // First clean HTML and XSS
      $string = Toolbox::clean_cross_side_scripting_deep($string);

      $rand   = mt_rand();
      // Move line breaks to special CHARS
      $string = str_replace(array("<br>"),"==$rand==", $string);

      $string = str_replace(array("\r\n", "\n", "\r"),"==$rand==", $string);

      // Wrap content for blacklisted items
      $itemstoclean = array();
      foreach ($DB->request('glpi_blacklistedmailcontents') as $data) {
         $toclean = trim($data['content']);
         if (!empty($toclean)) {
            $toclean        = str_replace(array("\r\n", "\n", "\r"),"==$rand==", $toclean);
            $itemstoclean[] = $toclean;
         }
      }
      if (count($itemstoclean)) {
         $string = str_replace($itemstoclean, '', $string);
      }
      $string = str_replace("==$rand==", "\n", $string);
      return $string;
   }


   /** function textCleaner - Strip out unwanted/unprintable characters from the subject.
    *
    * @param $text text to clean
    *
    * @return clean text
   **/
   function textCleaner($text) {

      $text = str_replace("=20", "\n", $text);
      $text =  Toolbox::clean_cross_side_scripting_deep($text);
      return $text;
   }


   ///return supported encodings in lowercase.
   function mb_list_lowerencodings() {

      // Encoding not listed
      static $enc = array('gb2312', 'gb18030');

      if (count($enc) == 2) {
         foreach (mb_list_encodings() as $encoding) {
            $enc[]   = Toolbox::strtolower($encoding);
            $aliases = mb_encoding_aliases($encoding);
            foreach ($aliases as $e) {
               $enc[] = Toolbox::strtolower($e);
            }
         }
      }
      return $enc;
   }


   /**
    * Receive a string with a mail header and returns it decoded to a specified charset.
    * If the charset specified into a piece of text from header
    * isn't supported by "mb", the "fallbackCharset" will be  used to try to decode it.
    *
    * @param $mimeStr         mime     header string
    * @param $inputCharset    input    charset (default 'utf-8')
    * @param $targetCharset   target   charset (default 'utf-8')
    * @param $fallbackCharset charset  used if input charset not supported by mb
    *                                  (default 'iso-8859-1')
    *
    * @return decoded string
   **/
   function decodeMimeString($mimeStr, $inputCharset='utf-8', $targetCharset='utf-8',
                             $fallbackCharset='iso-8859-1') {

      if (function_exists('mb_list_encodings')
          && function_exists('mb_convert_encoding')) {
         $encodings       = $this->mb_list_lowerencodings();
         $inputCharset    = Toolbox::strtolower($inputCharset);
         $targetCharset   = Toolbox::strtolower($targetCharset);
         $fallbackCharset = Toolbox::strtolower($fallbackCharset);
         $decodedStr      = '';
         $mimeStrs        = imap_mime_header_decode($mimeStr);

         for ($n=sizeOf($mimeStrs),$i=0 ; $i<$n ; $i++) {
            $mimeStr          = $mimeStrs[$i];
            $mimeStr->charset = Toolbox::strtolower($mimeStr->charset);

            if ((($mimeStr->charset == 'default') && ($inputCharset == $targetCharset))
                || ($mimeStr->charset == $targetCharset)) {

               $decodedStr .= $mimeStr->text;

            } else {
               if (in_array($mimeStr->charset, $encodings)) {
                  $this->charset = $mimeStr->charset;
               }

               $decodedStr .= mb_convert_encoding($mimeStr->text, $targetCharset,
                                                  (in_array($mimeStr->charset, $encodings)
                                                      ? $mimeStr->charset : $fallbackCharset));
            }
         }
         return $decodedStr;
      }
      return $mimeStr;
   }


   /**
     * Connect to the mail box
   **/
   function connect() {

      if ($this->fields['use_kerberos']) {
         $this->marubox = @imap_open($this->fields['host'], $this->fields['login'],
                                     Toolbox::decrypt($this->fields['passwd'], GLPIKEY),
                                     CL_EXPUNGE, 1);
      } else {
         $try_options = array(array('DISABLE_AUTHENTICATOR' => 'GSSAPI'),
                              array('DISABLE_AUTHENTICATOR' => 'PLAIN'));
         foreach($try_options as $option) {
            $this->marubox = @imap_open($this->fields['host'], $this->fields['login'],
                                        Toolbox::decrypt($this->fields['passwd'], GLPIKEY),
                                        CL_EXPUNGE, 1, $option);
            if (is_resource($this->marubox)) {
               break;
            }
         }

      }
      // Reset errors
      if ($this->marubox) {
         // call this to avoid the mailbox is empty error message
         if (imap_num_msg($this->marubox) == 0) {
             $errors = imap_errors();
         }


         if ($this->fields['errors'] > 0) {
            $this->update(array('id'     => $this->getID(),
                                'errors' => 0));
         }
      } else {
            $this->update(array('id'     => $this->getID(),
                                'errors' => ($this->fields['errors']+1)));
      }
   }


   /**
    * get the message structure if not already retrieved
    *
    * @param $mid : Message ID.
   **/
    function getStructure ($mid) {

      if (($mid != $this->mid)
          || !$this->structure) {
         $this->structure = imap_fetchstructure($this->marubox,$mid);

         if ($this->structure) {
            $this->mid = $mid;
         }
      }
   }


   /**
    * @param $mid
   **/
   function getAdditionnalHeaders($mid) {

      $head   = array();
      $header = explode("\n", imap_fetchheader($this->marubox, $mid));

      if (is_array($header) && count($header)) {
         foreach ($header as $line) {
            // is line with additional header?
            if (preg_match("/^X-/i", $line)
                || preg_match("/^Auto-Submitted/i", $line)
                || preg_match("/^Received/i", $line)) {
               // separate name and value
               if (preg_match("/^([^:]*): (.*)/i", $line, $arg)) {
                  $key = Toolbox::strtolower($arg[1]);

                  if (!isset($head[$key])) {
                     $head[$key] = '';
                  } else {
                     $head[$key] .= "\n";
                  }

                  $head[$key] .= trim($arg[2]);
               }
            }
         }
      }
      return $head;
   }


   /**
    * This function is use full to Get Header info from particular mail
    *
    * @param $mid               = Mail Id of a Mailbox
    *
    * @return Return Associative array with following keys
    * subject   => Subject of Mail
    * to        => To Address of that mail
    * toOth     => Other To address of mail
    * toNameOth => To Name of Mail
    * from      => From address of mail
    * fromName  => Form Name of Mail
   **/
   function getHeaders($mid) { // Get Header info

      $mail_header  = imap_header($this->marubox, $mid);
      $sender       = $mail_header->from[0];
      $to           = $mail_header->to[0];
      $date         = date("Y-m-d H:i:s", strtotime($mail_header->date));

      $mail_details = array();

      if ((Toolbox::strtolower($sender->mailbox) != 'mailer-daemon')
          && (Toolbox::strtolower($sender->mailbox) != 'postmaster')) {

         // Construct to and cc arrays
         $tos = array();
         $ccs = array();
         if (count($mail_header->to)) {
            foreach ($mail_header->to as $data) {
               $mailto = Toolbox::strtolower($data->mailbox).'@'.$data->host;
               if ($mailto === $this->fields['name']) {
                  $to = $data;
               }
               $tos[] = $mailto;
                           }
         }
         if (isset($mail_header->cc) && count($mail_header->cc)) {
            foreach ($mail_header->cc as $data) {
               $ccs[] = Toolbox::strtolower($data->mailbox).'@'.$data->host;
            }
         }

         // secu on subject setting
         if (!isset($mail_header->subject)) {
            $mail_header->subject = '';
         }

         $mail_details = array('from'       => Toolbox::strtolower($sender->mailbox).'@'.$sender->host,
                               'subject'    => $mail_header->subject,
                               'to'         =>  Toolbox::strtolower($to->mailbox).'@'.$to->host,
                               'message_id' => $mail_header->message_id,
                               'tos'        => $tos,
                               'ccs'        => $ccs,
                               'date'       => $date);

         if (isset($mail_header->references)) {
            $mail_details['references'] = $mail_header->references;
         }

         if (isset($mail_header->in_reply_to)) {
            $mail_details['in_reply_to'] = $mail_header->in_reply_to;
         }

         //Add additional headers in X-
         foreach ($this->getAdditionnalHeaders($mid) as $header => $value) {
            $mail_details[$header] = $value;
         }
      }

      return $mail_details;
   }


   /**
    * Get Mime type Internal Private Use
    *
    * @param $structure mail structure
    *
    * @return mime type
   **/
   function get_mime_type(&$structure) {

      // DO NOT REORDER IT
      $primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO",
                                 "IMAGE", "VIDEO", "OTHER");

      if ($structure->subtype) {
         return $primary_mime_type[intval($structure->type)] . '/' . $structure->subtype;
      }
      return "TEXT/PLAIN";
   }


   /**
    * Get Part Of Message Internal Private Use
    *
    * @param $stream       An IMAP stream returned by imap_open
    * @param $msg_number   The message number
    * @param $mime_type    mime type of the mail
    * @param $structure    structure of the mail (false by default)
    * @param $part_number  The part number (false by default)
    *
    * @return data of false if error
   **/
   function get_part($stream, $msg_number, $mime_type, $structure=false, $part_number=false) {

      if ($structure) {
         if ($mime_type == $this->get_mime_type($structure)) {

            if (!$part_number) {
               $part_number = "1";
            }

            $text = imap_fetchbody($stream, $msg_number, $part_number);

            if ($structure->encoding == 3) {
               $text =  imap_base64($text);
            } else if ($structure->encoding == 4) {
               $text =  imap_qprint($text);
            }

            //else { return $text; }
            if ($structure->subtype && ($structure->subtype == "HTML")) {
               $text = str_replace("\r", " ", $text);
               $text = str_replace("\n", " ", $text);
            }

            if (count($structure->parameters) > 0) {
               foreach ($structure->parameters as $param) {
                  if ((strtoupper($param->attribute) == 'CHARSET')
                      && function_exists('mb_convert_encoding')
                      && (strtoupper($param->value) != 'UTF-8')) {

                     $text                 = mb_convert_encoding($text, 'utf-8',$param->value);
                     $this->body_converted = true;
                  }
               }
            }
            return $text;
         }

         if ($structure->type == 1) { /* multipart */
            $prefix = "";
            reset($structure->parts);

            while (list($index, $sub_structure) = each($structure->parts)) {
               if ($part_number) {
                  $prefix = $part_number . '.';
               }
               $data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure,
                                       $prefix . ($index + 1));
               if ($data) {
                  return $data;
               }
            }
         }
      }
      return false;
   }


   /**
    * Used to get total unread mail from that mailbox
    *
    * @return an integer (Total Mail)
   **/
   function getTotalMails() {//Get Total Number off Unread Email In Mailbox

      $headers = imap_headers($this->marubox);
      return count($headers);
   }


   /**
    * Summary of getDecodedFetchbody
    * used to get decoded part from email
    *
    * @since version 0.90.2
    * @param $structure
    * @param $mid
    * @param $part
    *
    * @return bool|string
   **/
   private function getDecodedFetchbody($structure, $mid, $part) {

      if ($message = imap_fetchbody($this->marubox, $mid, $part)) {
         switch ($structure->encoding) {
            case 1 :
               $message = imap_8bit($message);
               break;

            case 2 :
               $message = imap_binary($message);
               break;

            case 3 :
               $message = imap_base64($message);
               break;

            case 4 :
               $message = quoted_printable_decode($message);
               break;
         }
         return $message;
      }

      return false;
   }


   /**
    * Private function : Recursivly get attached documents
    *
    * @param $mid          message id
    * @param $path         temporary path
    * @param $maxsize      of document to be retrieved
    * @param $structure    of the message or part
    * @param $part         part for recursive
    *
    * Result is stored in $this->files
   **/
   function getRecursiveAttached($mid, $path, $maxsize, $structure, $part="") {

      if ($structure->type == 1) { // multipart
         reset($structure->parts);
         while (list($index, $sub) = each($structure->parts)) {
            $this->getRecursiveAttached($mid, $path, $maxsize, $sub,
                                        ($part ? $part.".".($index+1) : ($index+1)));
         }

      } else {
         // fix monoparted mail
         if ($part == "") {
            $part = 1;
         }

         $filename = '';

         if ($structure->ifdparameters) {
            // get filename of attachment if present
            // if there are any dparameters present in this part
            if (count($structure->dparameters) > 0) {
               foreach ($structure->dparameters as $dparam) {
                  if ((Toolbox::strtoupper($dparam->attribute) == 'NAME')
                      || (Toolbox::strtoupper($dparam->attribute) == 'FILENAME')) {
                     $filename = $dparam->value;
                  }
               }
            }
         }

         //if no filename found
         if (empty($filename)
             && $structure->ifparameters) {

            // if there are any parameters present in this part
            if (count($structure->parameters)>0) {
               foreach ($structure->parameters as $param) {
                  if ((Toolbox::strtoupper($param->attribute) == 'NAME')
                      || (Toolbox::strtoupper($param->attribute) == 'FILENAME')) {
                     $filename = $param->value;
                  }
               }
            }
         }

         if (empty($filename)
             && ($structure->type == 5)
             && $structure->subtype) {
            // Embeded image come without filename - generate trivial one
            $filename = "image_$part.".$structure->subtype;
         } else if (empty($filename)
                    && ($structure->type == 2)
                    && $structure->subtype) {
             // Embeded email comes without filename - try to get "Subject:" or generate trivial one
             $filename = "msg_$part.EML"; // default trivial one :)!
             if (($message = $this->getDecodedFetchbody($structure, $mid, $part))
                 && (preg_match( "/Subject: *([^\r\n]*)/i",  $message,  $matches))) {
                 $filename = "msg_".$part."_".$this->decodeMimeString($matches[1]).".EML";
                $filename = preg_replace( "#[<>:\"\\\\/|?*]#u", "_", $filename) ;
             }
         }

         // if no filename found, ignore this part
         if (empty($filename)) {
            return false;
         }
         //try to avoid conflict between inline image and attachment
         $i = 2;
         while(in_array($filename, $this->files)) {
            //replace filename with name_(num).EXT by name_(num+1).EXT
            $new_filename = preg_replace("/(.*)_([0-9])*(\.[a-zA-Z0-9]*)$/", "$1_".$i."$3", $filename);
            if ($new_filename !== $filename) {
               $filename = $new_filename;
            } else {
               //the previous regex didn't found _num pattern, so add it with this one
               $filename = preg_replace("/(.*)(\.[a-zA-Z0-9]*)$/", "$1_".$i."$2", $filename);
            }
            $i++;
         }


         $filename = $this->decodeMimeString($filename);

         if ($structure->bytes > $maxsize) {
            $this->addtobody .= "\n\n".sprintf(__('%1$s: %2$s'), __('Too large attached file'),
                                               sprintf(__('%1$s (%2$s)'), $filename,
                                                       Toolbox::getSize($structure->bytes)));
            return false;
         }

         if (!Document::isValidDoc($filename)) {
            //TRANS: %1$s is the filename and %2$s its mime type
            $this->addtobody .= "\n\n".sprintf(__('%1$s: %2$s'), __('Invalid attached file'),
                                               sprintf(__('%1$s (%2$s)'), $filename,
                                                       $this->get_mime_type($structure)));
            return false;
         }

         if ((($structure->type == 2) && $structure->subtype)
             || ($message = $this->getDecodedFetchbody($structure, $mid, $part))) {
            if (file_put_contents($path.$filename, $message)) {
               $this->files[$filename] = $filename;
               // If embeded image, we add a tag
               if (($structure->type == 5)
                   && $structure->subtype) {
                  end($this->files);
                  $tag = Rule::getUuid();
                  $this->tags[$filename]  = $tag;

                  // Link file based on id
                  if (isset($structure->id)) {
                    $clean = array('<' => '',
                                    '>' => '');

                    $this->altfiles[strtr($structure->id, $clean)] = $filename;
                  }

               }
            }
         } // fetchbody
      } // Single part
   }


   /**
    * Public function : get attached documents in a mail
    *
    * @param $mid       message id
    * @param $path      temporary path
    * @param $maxsize   of document to be retrieved
    *
    * @return array containing extracted filenames in file/_tmp
   **/
   function getAttached($mid, $path, $maxsize) {

      $this->getStructure($mid);
      $this->files     = array();
      $this->altfiles  = array();
      $this->addtobody = "";
      $this->getRecursiveAttached($mid, $path, $maxsize, $this->structure);

      return ($this->files);
   }


   /**
    * Get The actual mail content from this mail
    *
    * @param $mid : mail Id
   **/
   function getBody($mid) {// Get Message Body

      $this->getStructure($mid);
      $body = $this->get_part($this->marubox, $mid, "TEXT/HTML", $this->structure);

      if ($body == "") {
         $body = $this->get_part($this->marubox, $mid, "TEXT/PLAIN", $this->structure);
      }

      if ($body == "") {
         return "";
      }

      return $body;
   }


   /**
    * Delete mail from that mail box
    *
    * @param $mid       String    mail Id
    * @param $folder    String   folder to move (delete if empty) (default '')
    *
    * @return Boolean
   **/
   function deleteMails($mid, $folder='') {
      if (!empty($folder) && isset($this->fields[$folder]) && !empty($this->fields[$folder])) {
         $name = mb_convert_encoding($this->fields[$folder], "UTF7-IMAP","UTF-8");
         if (imap_mail_move($this->marubox, $mid, $name)) {
            return true;
         }
         // raise an error and fallback to delete
         //TRANS: %1$s is the name of the folder, %2$s is the name of the receiver
         trigger_error(sprintf(__('Invalid configuration for %1$s folder in receiver %2$s'),
                               $folder, $this->getName()));
      }
      return imap_delete($this->marubox, $mid);
   }


   /**
    * Close The Mail Box
   **/
   function close_mailbox() {
      imap_close($this->marubox, CL_EXPUNGE);
   }


   /**
    * @param $name
   **/
   static function cronInfo($name) {

      switch($name) {
         case 'mailgate' :
            return array('description' => __('Retrieve email (Mails receivers)'),
                         'parameter'   => __('Number of emails to retrieve'));

         case 'mailgateerror' :
            return array('description' => __('Send alarms on receiver errors'));
      }
   }


   /**
    * Cron action on mailgate : retrieve mail and create tickets
    *
    * @param $task
    *
    * @return -1 : done but not finish 1 : done with success
   **/
   static function cronMailgate($task) {
      global $DB;

      NotImportedEmail::deleteLog();
      $query = "SELECT *
                FROM `glpi_mailcollectors`
                WHERE `is_active` = '1'";

      if ($result = $DB->query($query)) {
         $max = $task->fields['param'];

         if ($DB->numrows($result) > 0) {
            $mc = new self();

            while (($max > 0)
                   && ($data = $DB->fetch_assoc($result))) {
               $mc->maxfetch_emails = $max;

               $task->log("Collect mails from ".$data["name"]." (".$data["host"].")\n");
               $message = $mc->collect($data["id"]);

               $task->addVolume($mc->fetch_emails);
               $task->log("$message\n");

               $max -= $mc->fetch_emails;
            }
         }

         if ($max == $task->fields['param']) {
            return 0; // Nothin to do
         } else if ($max > 0) {
            return 1; // done
         }

         return -1; // still messages to retrieve
      }
      return 0;
   }


   /**
    * Send Alarms on mailgate errors
    *
    * @since version 0.85
    *
    * @param $task for log
   **/
   static function cronMailgateError($task) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }
      $cron_status   = 0;

      $query = "SELECT `glpi_mailcollectors`.*
                FROM `glpi_mailcollectors`
                WHERE `glpi_mailcollectors`.`errors`  > 0
                      AND `glpi_mailcollectors`.`is_active`";

      $items = array();
      foreach ($DB->request($query) as $data) {
         $items[$data['id']]  = $data;
      }

      if (count($items)) {
         if (NotificationEvent::raiseEvent('error', new self(), array('items' => $items))) {
            $cron_status = 1;
            if ($task) {
               $task->setVolume(count($items));
            }
         }
      }
      return $cron_status;
   }


   /**
    * @param $width
   **/
   function showSystemInformations($width) {
      global $CFG_GLPI, $DB;

      // No need to translate, this part always display in english (for copy/paste to forum)

      echo "<tr class='tab_bg_2'><th>Notifications</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $msg = 'Way of sending emails: ';
      switch($CFG_GLPI['smtp_mode']) {
         case MAIL_MAIL :
            $msg .= 'PHP';
            break;

         case MAIL_SMTP :
            $msg .= 'SMTP';
            break;

         case MAIL_SMTPSSL :
            $msg .= 'SMTP+SSL';
            break;

         case MAIL_SMTPTLS :
            $msg .= 'SMTP+TLS';
            break;
      }
      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $msg .= " (".(empty($CFG_GLPI['smtp_username']) ? 'anonymous' : $CFG_GLPI['smtp_username']).
                    "@".$CFG_GLPI['smtp_host'].")";
      }
      echo wordwrap($msg."\n", $width, "\n\t\t");
      echo "\n</pre></td></tr>";

      echo "<tr class='tab_bg_2'><th>Mails receivers</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      foreach ($DB->request('glpi_mailcollectors') as $mc) {
         $msg  = "Name: '".$mc['name']."'";
         $msg .= " Active: " .($mc['is_active'] ? "Yes" : "No");
         echo wordwrap($msg."\n", $width, "\n\t\t");

         $msg  = "\tServer: '". $mc['host']."'";
         $msg .= " Login: '".$mc['login']."'";
         $msg .= " Password: ".(empty($mc['passwd']) ? "No" : "Yes");
         echo wordwrap($msg."\n", $width, "\n\t\t");
      }
      echo "\n</pre></td></tr>";
   }


   /**
    * @param $to        (default '')
    * @param $subject   (default '')
   **/
   function sendMailRefusedResponse($to='', $subject='') {
      global $CFG_GLPI;

      $mmail = new GLPIMailer();
      $mmail->AddCustomHeader("Auto-Submitted: auto-replied");
      $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"]);
      $mmail->AddAddress($to);
      // Normalized header, no translation
      $mmail->Subject  = 'Re: ' . $subject;
      $mmail->Body     = __("Your email could not be processed.\nIf the problem persists, contact the administrator").
                         "\n-- \n".$CFG_GLPI["mailing_signature"];
      $mmail->Send();
   }


  function title() {
      global $CFG_GLPI;

      $buttons = array();
      if (countElementsInTable($this->getTable())) {
         $buttons["notimportedemail.php"] = __('List of not imported emails');
      }

      $errors  = getAllDatasFromTable($this->getTable(), '`errors` > 0');
      $message = '';
      if (count($errors)) {
         $servers = array();
         foreach ($errors as $data) {
            $this->getFromDB($data['id']);
            $servers[] = $this->getLink();
         }

         $message = sprintf(__('Receivers in error: %s'), implode(" ", $servers));
      }

      if (count($buttons)) {
         Html::displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png",
                            _n('Receiver', 'Receivers', Session::getPluralNumber()), $message, $buttons);
      }

   }


   static function getNumberOfMailCollectors() {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_mailcollectors`";
      $result = $DB->query($query);

      return $DB->result($result, 0, 'cpt');
   }


   /**
    * @since version 0.85
   **/
   static function getNumberOfActiveMailCollectors() {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_mailcollectors`
                WHERE `is_active` = 1";
      $result = $DB->query($query);

      return $DB->result($result, 0, 'cpt');
   }


   /**
    * @param $name
    * @param $value  (default 0)
   **/
   static function showMaxFilesize($name, $value=0) {

      $sizes[0] = __('No import');
      for ($index=1 ; $index<100 ; $index++) {
         $sizes[$index*1048576] = sprintf(__('%s Mio'), $index);
      }
      Dropdown::showFromArray($name, $sizes, array('value' => $value));
   }


   function cleanDBonPurge() {

      // mailcollector for RuleMailCollector, _mailgate for RuleTicket
      Rule::cleanForItemCriteria($this, 'mailcollector');
      Rule::cleanForItemCriteria($this, '_mailgate');
   }

   static public function unsetUndisclosedFields(&$fields) {
      unset($fields['passwd']);
   }

}
