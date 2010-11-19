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

/// MailCollector class
// Merge with collect GLPI system after big modification in it
// modif and debug by  INDEPNET Development Team.
/* Original class ReceiveMail 1.0 by Mitul Koradia Created: 01-03-2006
 * Description: Reciving mail With Attechment
 * Email: mitulkoradia@gmail.com
 */
class MailCollector  extends CommonDBTM {

   // Specific one
   /// working charset of the mail
   var $charset = "";
   /// IMAP / POP connection
   var $marubox = '';
   /// ID of the current message
   var $mid = -1;
   /// structure used to store the mail structure
   var $structure = false;
   /// structure used to store files attached to a mail
   var $files;
   /// Message to add to body to build ticket
   var $addtobody;
   /// Number of fetched emails
   var $fetch_emails = 0;
   /// Maximum number of emails to fetch : default to 10
   var $maxfetch_emails = 10;
   /// Max size for attached files
   var $filesize_max = 0;
   /// Body converted
   var $body_converted = false;

   public $dohistory = true;


   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][39];
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }


   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields['filesize_max'] = $CFG_GLPI['default_mailcollector_filesize_max'];
      $this->fields['is_active']=1;
   }


   function prepareInputForUpdate($input) {

      if (isset($input["passwd"])) {
         if (empty($input["passwd"])) {
            unset($input["passwd"]);
         } else {
            $input["passwd"] = encrypt($input["passwd"], GLPIKEY);
         }
      }

      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["host"] = constructMailServerConfig($input);
      }
      return $input;
   }


   function prepareInputForAdd($input) {

      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["host"] = constructMailServerConfig($input);
      }
      return $input;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];
      if ($this->fields['id'] > 0) {
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }


   /**
    * Print the mailgate form
    *
    * @param $ID Integer : Id of the item to print
    * @param $options array
    *     - target filename : where to go when done.
    *
    *@return boolean item found
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("config", "r")) {
         return false;
      }
      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }
      $options['colspan'] = 1;
      $this->showTabs($options);
      $this->showFormHeader($options);

      if (!function_exists('mb_list_encodings') || !function_exists('mb_convert_encoding')) {
         echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['mailgate'][4]."</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td><td>";
      autocompletionTextField($this, "name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][60]."&nbsp;:</td><td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td></tr>";

      showMailServerConfig($this->fields["host"]);

      echo "<tr class='tab_bg_1'><td>".$LANG['login'][6]."&nbsp;:</td><td>";
      autocompletionTextField($this, "login");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['login'][7]."&nbsp;:</td>";
      echo "<td><input type='password' name='passwd' value='' size='20' autocomplete='off'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td width='200px'> " . $LANG['mailgate'][7] . "&nbsp;:</td><td>";
      MailCollector::showMaxFilesize('filesize_max',$this->fields["filesize_max"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td><textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";

      if ($ID>0) {
         echo "<br>".$LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"]);
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }


   function showGetMessageForm($ID) {
      global $LANG;
      echo "<br><br><div class='center'>";
      echo "<form name='form' method='post' action='".getItemTypeFormURL(__CLASS__)."'>";
      echo "<table class='tab_cadre'>";
      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='submit' name='get_mails' value=\"".$LANG['mailgate'][2]."\" class='submit'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][16];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']    = $this->getTable();
      $tab[2]['field']    = 'is_active';
      $tab[2]['name']     = $LANG['common'][60];
      $tab[2]['datatype'] = 'bool';

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'host';
      $tab[3]['name']          = $LANG['setup'][170];
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'login';
      $tab[4]['name']          = $LANG['login'][6];
      $tab[4]['massiveaction'] = false;

      $tab[5]['table']    = $this->getTable();
      $tab[5]['field']    = 'filesize_max';
      $tab[5]['name']     = $LANG['mailgate'][7];
      $tab[5]['datatype'] = 'integer';

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      return $tab;
   }


   function deleteOrImportSeveralEmails($emails_ids = array(), $action=0, $entity=0) {
      global $DB, $LANG;

      $mailbox_id = 0;
      $query = "SELECT *
                FROM `glpi_notimportedemails`
                WHERE `id` IN (".implode(',',$emails_ids).")
                ORDER BY `mailcollectors_id`";

      $todelete = array();
      foreach ($DB->request($query) as $data) {
         $todelete[$data['mailcollectors_id']][$data['messageid']] = $data;
      }
      $ticket = new Ticket;
      foreach ($todelete as $mailcollector_id => $rejected) {
         if ($this->getFromDB($mailcollector_id)) {
            $this->mid = -1;
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
                     $tkt = $this->buildTicket($i, array('mailcollectors_id' => $mailcollector_id,
                                                         'play_rules'        => false));
                     $tkt['users_id'] = $rejected[$head['message_id']]['users_id'];
                     $tkt['entities_id'] = $entity;
                     $ticket->add($tkt);
                  }
                  //Delete email
                  if ($this->deleteMails($i)) {
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
                     addMessageAfterRedirect($LANG['mailgate'][14]."&nbsp;: ".strtr($id,$clean),
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
   * @param $mailgateID ID of the mailgate
   * @param $display display messages in MessageAfterRedirect or just return error
   *
   * @return if $display = false return messages result string
   **/
   function collect($mailgateID, $display=0) {
      global $LANG, $CFG_GLPI;

      if ($this->getFromDB($mailgateID)) {
         $this->mid = -1;
         $this->fetch_emails = 0;
         //Connect to the Mail Box
         $this->connect();
         $rejected = new NotImportedEmail;

         if ($this->marubox) {
            // Get Total Number of Unread Email in mail box
            $tot  =$this->getTotalMails(); //Total Mails in Inbox Return integer value
            $error = 0;
            $refused = 0;

            for($i=1 ; $i<=$tot && $this->fetch_emails<$this->maxfetch_emails ; $i++) {
               $tkt = $this->buildTicket($i, array('mailgates_id' => $mailgateID,
                                                   'play_rules'   => true));

               //Indicates that the mail must be deleted from the mailbox
               $delete_mail = false;

               //If entity assigned, or email refused by rule, or no user associated with the email
               $user_condition = ($CFG_GLPI["use_anonymous_helpdesk"] ||$tkt['users_id'] > 0);

               // entities_id set when new ticket / tickets_id when new followup
               if (((isset($tkt['entities_id']) || isset($tkt['tickets_id']))
                    && $user_condition)
                   || isset($tkt['_refuse_email_no_response'])
                   || isset($tkt['_refuse_email_with_response'])) {

                  if (isset($tkt['entities_id']) || isset($tkt['tickets_id'])) {
                     $tkt['_mailgate'] = $mailgateID;
                     $result = imap_fetchheader($this->marubox, $i);

                     // Is a mail responding of an already existgin ticket ?
                     if (isset($tkt['tickets_id']) ) {
                        // Deletion of message with sucess
                        if (false === is_array($result)) {
                           $fup = new TicketFollowup();
                           if ($fup->add($tkt)) {
                              $delete_mail = true;
                           }
                        } else {
                           $error++;
                        }

                     } else { // New ticket
                        // Deletion of message with sucess
                        if (false === is_array($result)) {
                           $track = new Ticket();
                           if ($track->add($tkt)) {
                              $delete_mail = true;
                           }
                        } else {
                           $error++;
                        }
                     }

                  } else {
                     if (isset($tkt['_refuse_email_with_response'])) {
                        $this->sendMailRefusedResponse($tkt['user_email'], $tkt['name']);
                     }
                     $delete_mail = true;
                     $refused++;
                  }
                  //Delete Mail from Mail box if ticket is added successfully
                  if ($delete_mail) {
                     $this->deleteMails($i);
                  }

               } else {
                  $input = array();
                  $input['mailcollectors_id'] = $mailgateID;
                  $input['from']              = $tkt['_head']['from'];
                  $input['to']                = $tkt['_head']['to'];

                  if (!$tkt['users_id']) {
                     $input['reason'] = NotImportedEmail::USER_UNKNOWN;

                  } else {
                     $input['reason'] = NotImportedEmail::MATCH_NO_RULE;
                  }

                  $input['users_id']  = $tkt['users_id'];
                  $input['subject']   = $this->textCleaner($tkt['_head']['subject']);
                  $input['messageid'] = $tkt['_head']['message_id'];
                  $input['date']      = $_SESSION["glpi_currenttime"];
                  $rejected->add($input);
               }
               $this->fetch_emails++;
            }
            imap_expunge($this->marubox);
            $this->close_mailbox();   //Close Mail Box

            if ($display) {

               if ($error==0) {
                  addMessageAfterRedirect($LANG['mailgate'][3]."&nbsp;: ".$this->fetch_emails);
               } else {
                  addMessageAfterRedirect($LANG['mailgate'][3]."&nbsp;: ".$this->fetch_emails.
                                          " ($error ".$LANG['common'][63].")",
                                          false, ERROR);
               }

            } else {
               return "Number of messages: available=$tot, collected=".$this->fetch_emails.
                       ($error>0?" ($error error(s))":"".
                       ($refused>0?" ($refused refused)":""));
            }

         } else {
            if ($display) {
               addMessageAfterRedirect($LANG['log'][41], false, ERROR);
            } else {
               return "Could not connect to mailgate server";
            }
         }

      } else {
         if ($display) {
            addMessageAfterRedirect($LANG['common'][54]."&nbsp;: mailgate ".$mailgateID,
                                    false, ERROR);
         } else {
            return 'Could find mailgate '.$mailgateID;
         }
      }
   }


   /** function buildTicket - Builds,and returns, the major structure of the ticket to be entered.
    *
    * @param $i mail ID
    * @param $options array options
    *
    * @return ticket fields array
    */
   function buildTicket($i,$options=array()) {

      $play_rules = (isset($options['play_rules']) && $options['play_rules']);

      $head = $this->getHeaders($i); // Get Header Info Return Array Of Headers
                                     // **Key Are (subject,to,toOth,toNameOth,from,fromName)
      $tkt = array ();
      // max size = 0 : no import attachments
      if ($this->fields['filesize_max']>0) {
         if (is_writable(GLPI_DOC_DIR."/_tmp/")) {
            $_FILES = $this->getAttached($i, GLPI_DOC_DIR."/_tmp/", $this->fields['filesize_max']);
         } else {
            logInFile('mailgate', GLPI_DOC_DIR."/_tmp/ is not writable");
         }
      }
      //  Who is the user ?
      $tkt['users_id'] = User::getOrImportByEmail($head['from']);
      // AUto_import
      $tkt['_auto_import'] = 1;
      // For followup : do not check users_id = login user
      $tkt['_do_not_check_users_id'] = 1;
      $body = $this->getBody($i);
      // Do it before using charset variable
      $head['subject'] = $this->decodeMimeString($head['subject']);
      $tkt['_head'] = $head;

      if (!empty($this->charset) && !$this->body_converted) {
         $body = encodeInUtf8($body,$this->charset);
         $this->body_converted = true;
      }

      if (!seems_utf8($body)) {
         $tkt['content'] = encodeInUtf8($body);
      } else {
         $tkt['content'] = $body;
      }

      // Add message from getAttached
      if ($this->addtobody) {
         $tkt['content'] .= $this->addtobody;
      }
      // Detect if it is a mail reply
      $glpi_message_match = "/GLPI-([0-9]+)\.[0-9]+\.[0-9]+@\w*/";
      // See In-Reply-To field

      if (isset($head['in_reply_to'])) {
         if (preg_match($glpi_message_match, $head['in_reply_to'], $match)) {
            $tkt['tickets_id'] = (int)$match[1];
         }
      }
      // See in References
      if (!isset($tkt['tickets_id']) && isset($head['references'])) {
         if (preg_match($glpi_message_match, $head['references'], $match)) {
            $tkt['tickets_id'] = (int)$match[1];
         }
      }

      // See in title
      if (!isset($tkt['tickets_id']) && preg_match('/\[GLPI #(\d+)\]/', $head['subject'], $match)) {
         $tkt['tickets_id'] = (int)$match[1];
      }

      // Found ticket link
      if (isset($tkt['tickets_id'])) {
         // it's a reply to a previous ticket
         $job = new Ticket();

         // Check if ticket  exists and users_id exists in GLPI
         /// TODO check if users_id have right to add a followup to the ticket
         if ($job->getFromDB($tkt['tickets_id'])
             && $job->fields['status'] != 'closed'
             && ($tkt['users_id'] > 0 || !strcasecmp($job->fields['user_email'], $head['from']))) {

            $content        = explode("\n", $tkt['content']);
            $tkt['content'] = "";
            $first_comment  = true;
            $to_keep        = array();

            foreach ($content as $ID => $val) {
               if (isset($val[0])&&$val[0]=='>') {
                  // Delete line at the top of the first comment
                  if ($first_comment) {
                     $first_comment = false;
                     if (isset($to_keep[$ID-1])) {
                        unset($to_keep[$ID-1]);
                     }
                  }

               } else {
                  // Detect a signature if already keep lines
                  $to_keep[$ID] = $ID;
               }
            }

            foreach ($to_keep as $ID ) {
               $tkt['content'] .= $content[$ID]."\n";
            }

            // Do not play rules for followups
            $play_rules = false;

         } else {
            // => to handle link in Ticket->post_addItem()
            $tkt['_linkedto'] = $tkt['tickets_id'];
            unset($tkt['tickets_id']);
         }
      }

      if (! isset($tkt['tickets_id'])) {
         // Mail followup
         $tkt['user_email'] = $head['from'];
         $tkt['use_email_notification'] = 1;
         // Which entity ?
         //$tkt['entities_id']=$this->fields['entities_id'];
         //$tkt['Subject']= $head['subject'];   // not use for the moment
         $tkt['name'] = $this->textCleaner($head['subject']);
         // Medium
         $tkt['urgency'] = "3";
         // No hardware associated
         $tkt['itemtype'] = "";
         // Mail request type

      } else {
         // Reopen if needed
         $tkt['add_reopen'] = 1;
      }

      $tkt['requesttypes_id'] = RequestType::getDefault('mail');
      $tkt['content']         = clean_cross_side_scripting_deep(html_clean($tkt['content']));

      if ($play_rules) {
         $rule_options['ticket']        = $tkt;
         $rule_options['headers']       = $head;
         $rule_options['mailcollector'] = $options['mailgates_id'];
         $rule_options['users_id']      = $tkt['users_id'];
         $rulecollection = new RuleMailCollectorCollection();
         $output         = $rulecollection->processAllRules(array(), array(), $rule_options);

         foreach ($output as $key => $value) {
            $tkt[$key] = $value;
         }
      }

      $tkt = addslashes_deep($tkt);
      return $tkt;
   }


   /** function textCleaner - Strip out unwanted/unprintable characters from the subject.
    *
   * @param $text text to clean
   *
   * @return clean text
   */
   function textCleaner($text) {

      $text = str_replace("=20", "\n", $text);
      return $text;
   }


   ///return supported encodings in lowercase.
   function mb_list_lowerencodings() {

      $r = mb_list_encodings();
      for ($n=sizeOf($r) ; $n--; ) {
         $r[$n] = utf8_strtolower($r[$n]);
      }
      return $r;
   }


   /**  Receive a string with a mail header and returns it
   // decoded to a specified charset.
   // If the charset specified into a piece of text from header
   // isn't supported by "mb", the "fallbackCharset" will be
   // used to try to decode it.
    *
    * @param $mimeStr mime header string
    * @param $inputCharset input charset
    * @param $targetCharset target charset
    * @param $fallbackCharset charset used if input charset not supported by mb
    *
    * @return decoded string
    **/
   function decodeMimeString($mimeStr, $inputCharset='utf-8', $targetCharset='utf-8',
                             $fallbackCharset='iso-8859-1') {

      if (function_exists('mb_list_encodings') && function_exists('mb_convert_encoding')) {
         $encodings       = $this->mb_list_lowerencodings();
         $inputCharset    = utf8_strtolower($inputCharset);
         $targetCharset   = utf8_strtolower($targetCharset);
         $fallbackCharset = utf8_strtolower($fallbackCharset);
         $decodedStr      = '';
         $mimeStrs        = imap_mime_header_decode($mimeStr);

         for ($n=sizeOf($mimeStrs), $i=0 ; $i<$n ; $i++) {
            $mimeStr = $mimeStrs[$i];
            $mimeStr->charset = utf8_strtolower($mimeStr->charset);

            if (($mimeStr->charset == 'default' && $inputCharset == $targetCharset)
                || $mimeStr->charset == $targetCharset) {

               $decodedStr .= $mimeStr->text;

            } else {
               if (in_array($mimeStr->charset, $encodings)) {
                  $this->charset = $mimeStr->charset;
               }

               $decodedStr .= mb_convert_encoding($mimeStr->text, $targetCharset,
                  (in_array($mimeStr->charset, $encodings) ? $mimeStr->charset : $fallbackCharset));
            }
         }
         return $decodedStr;
      }
      return $mimeStr;
   }


    ///Connect To the Mail Box
   function connect() {

      $this->marubox = @imap_open($this->fields['host'], $this->fields['login'],
                                  decrypt($this->fields['passwd'],GLPIKEY), 1);
   }


   /**
    * get the message structure if not already retrieved
    *
    * @param $mid : Message ID.
    */
    function getStructure ($mid) {

      if ($mid != $this->mid || !$this->structure) {
         $this->structure = imap_fetchstructure($this->marubox,$mid);

         if ($this->structure) {
            $this->mid = $mid;
         }
      }
   }


   function getAdditionnalHeaders($mid) {

      $head   = array();
      $header = explode("\n", imap_fetchheader($this->marubox, $mid));

      if (is_array($header) && count($header)) {
         foreach ($header as $line) {
            // is line with additional header?
            if (preg_match("/^X-/i", $line)) {
               // separate name and value
               if (preg_match("/^([^:]*): (.*)/i", $line, $arg)) {
                  $head[$arg[1]] = $arg[2];
               }
            }
         }
      }
      return $head;
   }


   /**
   *This function is use full to Get Header info from particular mail
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
   */
   function getHeaders($mid) { // Get Header info

      $mail_header = imap_header($this->marubox, $mid);
      $sender      = $mail_header->from[0];

      if (utf8_strtolower($sender->mailbox)!='mailer-daemon'
          && utf8_strtolower($sender->mailbox)!='postmaster') {

         $mail_details = array('from'       => utf8_strtolower($sender->mailbox).'@'.$sender->host,
                               'subject'    => $mail_header->subject,
                               'to'         => $mail_header->toaddress,
                               'message_id' => $mail_header->message_id);

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


   /**Get Mime type Internal Private Use
    *
    * @param $structure mail structure
    *
    * @return mime type
    **/
   function get_mime_type(&$structure) {

      $primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO",
                                 "IMAGE", "VIDEO", "OTHER");

      if ($structure->subtype) {
         return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype;
      }
      return "TEXT/PLAIN";
   }


   /**Get Part Of Message Internal Private Use
    *
    * @param $stream An IMAP stream returned by imap_open
    * @param $msg_number The message number
    * @param $mime_type mime type of the mail
    * @param $structure struture of the mail
    * @param $part_number The part number.
    *
    * @return data of false if error
    **/
   function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) {

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
            if ($structure->subtype && $structure->subtype=="HTML") {
               $text = str_replace("\r", "", $text);
               $text = str_replace("\n", "", $text);
            }

            if (count($structure->parameters)>0) {
               foreach ($structure->parameters as $param) {
                  if ((strtoupper($param->attribute)=='CHARSET')
                      && function_exists('mb_convert_encoding')
                      && strtoupper($param->value) != 'UTF-8') {

                     $text = mb_convert_encoding($text, 'utf-8',$param->value);
                     $this->body_converted=true;
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
    * used to get total unread mail from That mailbox
    *
    * Return :
    * Int Total Mail
    */
   function getTotalMails() {//Get Total Number off Unread Email In Mailbox

      $headers = imap_headers($this->marubox);
      return count($headers);
   }


   /**
   *GetAttech($mid,$path) / Prefer use getAttached
   *Save attached file from mail to given path of a particular location
   *
   * @param $mid mail id
   * @param $path path where to save
   *
   * @return  String of filename with coma separated
   *like a.gif,pio.jpg etc
   */
   function GetAttech($mid, $path) {

      $struckture = imap_fetchstructure($this->marubox,$mid);
      $ar = "";
      if (isset($struckture->parts) && count($struckture->parts)>0) {
         foreach ($struckture->parts as $key => $value) {
            $enc = $struckture->parts[$key]->encoding;

            if ($struckture->parts[$key]->ifdparameters) {
               $name = $struckture->parts[$key]->dparameters[0]->value;
               $message = imap_fetchbody($this->marubox,$mid,$key+1);

               if ($enc == 0) {
                  $message = imap_8bit($message);
               }
               if ($enc == 1) {
                  $message = imap_8bit ($message);
               }
               if ($enc == 2) {
                  $message = imap_binary ($message);
               }
               if ($enc == 3) {
                  $message = imap_base64 ($message);
               }
               if ($enc == 4) {
                  $message = quoted_printable_decode($message);
               }
               if ($enc == 5) {
                  $message = $message;
               }
               $fp = fopen($path.$name,"w");
               fwrite($fp,$message);
               fclose($fp);
               $ar = $ar.$name.",";
            }
         }
      }
      $ar = substr($ar, 0, (strlen($ar)-1));
      return $ar;
   }


   /**
    * Private function : Recursivly get attached documents
    *
    * @param $mid : message id
    * @param $path : temporary path
    * @param $maxsize : of document to be retrieved
    * @param $structure : of the message or part
    * @param $part : part for recursive
    *
    * Result is stored in $this->files
    *
    */
   function getRecursiveAttached ($mid, $path, $maxsize, $structure, $part="") {
      global $LANG;

      if ($structure->type == 1) { // multipart
         reset($structure->parts);
         while (list($index, $sub) = each($structure->parts)) {
            $this->getRecursiveAttached($mid, $path, $maxsize, $sub,
                                        ($part ? $part.".".($index+1) : ($index+1)));
         }

      } else {
         $filename = '';

         if ($structure->ifdparameters) {
            // get filename of attachment if present
            // if there are any dparameters present in this part
            if (count($structure->dparameters)>0) {
               foreach ($structure->dparameters as $dparam) {
                  if ((utf8_strtoupper($dparam->attribute)=='NAME')
                      || (utf8_strtoupper($dparam->attribute)=='FILENAME')) {

                     $filename = $dparam->value;
                  }
               }
            }
         }

         //if no filename found
         if (empty($filename) && $structure->ifparameters) {
            // if there are any parameters present in this part
            if (count($structure->parameters)>0) {
               foreach ($structure->parameters as $param) {
                  if ((utf8_strtoupper($param->attribute)=='NAME')
                      || (utf8_strtoupper($param->attribute)=='FILENAME')) {

                     $filename = $param->value;
                  }
               }
            }
         }

         if (empty($filename) && $structure->type==5 && $structure->subtype) {
            // Embeded image come without filename - generate trivial one
            $filename = "image_$part.".$structure->subtype;
         }

         // if no filename found, ignore this part
         if (empty($filename)) {
            return false;
         }
         $filename = $this->decodeMimeString($filename);

         if ($structure->bytes > $maxsize) {
            $this->addtobody .= "<br>".$LANG['mailgate'][6]." (" .
                                getSize($structure->bytes) . "): ".$filename;
            return false;
         }

         if (!Document::isValidDoc($filename)) {
            $this->addtobody .= "<br>".$LANG['mailgate'][5]." (" .
                                $this->get_mime_type($structure) . ") : ".$filename;
            return false;
         }

         if ($message=imap_fetchbody($this->marubox, $mid, $part)) {
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

            if (file_put_contents($path.$filename, $message)) {
               $this->files['multiple'] = true;
               $j = count($this->files)-1;
               $this->files[$j]['filename']['size']     = $structure->bytes;
               $this->files[$j]['filename']['name']     = $filename;
               $this->files[$j]['filename']['tmp_name'] = $path.$filename;
               $this->files[$j]['filename']['type']     = $this->get_mime_type($structure);
            }
         } // fetchbody
      } // Single part
   }


   /**
    * Public function : get attached documents in a mail
    *
    * @param $mid : message id
    * @param $path : temporary path
    * @param $maxsize : of document to be retrieved
    *
    * @return array like $_FILES
    */
   function getAttached ($mid, $path, $maxsize) {

      $this->getStructure($mid);
      $this->files     = array();
      $this->addtobody = "";
      $this->getRecursiveAttached($mid, $path, $maxsize, $this->structure);

      return ($this->files);
   }


   /**
    * Get The actual mail content from this mail
    *
    * @param $mid : mail Id
    */
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
    * @param $mid : mail Id
    */
   function deleteMails($mid) {
      return imap_delete($this->marubox, $mid);
   }


   /**
    * Close The Mail Box
    *
    */
   function close_mailbox() {
      imap_close($this->marubox, CL_EXPUNGE);
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][9],
                   'parameter'   => $LANG['crontask'][39]);
   }


   /**
    * Cron action on mailgate : retrieve mail and create tickets
    *
    * @return -1 : done but not finish 1 : done with success
    **/
   static function cronMailgate($task) {
      global $DB;

      NotImportedEmail::deleteLog();
      $query = "SELECT *
                FROM `glpi_mailcollectors`
                WHERE `is_active` = '1'";

      if ($result=$DB->query($query)) {
         $max = $task->fields['param'];

         if ($DB->numrows($result)>0) {
            $mc = new MailCollector();

            while ($max>0 && $data=$DB->fetch_assoc($result)) {
               $mc->maxfetch_emails = $max;

               $task->log("Collect mails from ".$data["host"]."\n");
               $message = $mc->collect($data["id"]);

               $task->log("$message\n");
               $task->addVolume($mc->fetch_emails);

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


   function showSystemInformations($width) {
      global $LANG, $CFG_GLPI, $DB;

      echo "<tr class='tab_bg_2'><th>".$LANG['setup'][704]." / ".$LANG['mailgate'][0]."</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $msg = $LANG['setup'][231].": ";
      switch($CFG_GLPI['smtp_mode']) {
         case MAIL_MAIL :
            $msg .= $LANG['setup'][650];
            break;

         case MAIL_SMTP :
            $msg .= $LANG['setup'][651];
            break;

         case MAIL_SMTPSSL :
            $msg .= $LANG['setup'][652];
            break;

         case MAIL_SMTPTLS :
            $msg .= $LANG['setup'][653];
            break;
      }

      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $msg .= " (".(empty($CFG_GLPI['smtp_username'])?'':$CFG_GLPI['smtp_username']."@").
                    $CFG_GLPI['smtp_host'].")";
      }
      echo wordwrap($msg."\n", $width, "\n\t\t");

      echo $LANG['mailgate'][0]."\n";
      foreach ($DB->request('glpi_mailcollectors') as $mc) {
         $msg = "\t".$LANG['common'][16].':"'.$mc['name'].'"  ';
         $msg .= " ".$LANG['common'][52].':'.$mc['host'];
         $msg .= " ".$LANG['login'][6].':"'.$mc['login'].'"';
         $msg .= " ".$LANG['login'][7].':'.
                 (empty($mc['passwd'])?$LANG['choice'][0]:$LANG['choice'][1]);
         $msg .= " ".$LANG['common'][60].':'.($mc['is_active']?$LANG['choice'][1]:$LANG['choice'][0]);
         echo wordwrap($msg."\n", $width, "\n\t\t");
      }
   }


   function sendMailRefusedResponse($to='', $subject='') {
      global $CFG_GLPI, $LANG;

      $mmail           = new NotificationMail;
      $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"]);
      $mmail->AddAddress($to);
      $mmail->Subject  = $LANG['mailgate'][16].' '.$subject;
      $mmail->Body     = $LANG['mailgate'][9]."\n-- \n".$CFG_GLPI["mailing_signature"];
      $mmail->Send();
   }


  function title() {
      global $LANG, $CFG_GLPI;

      if (countElementsInTable($this->getTable())) {
         $buttons = array ();
         $buttons["notimportedemail.php"] = $LANG['rulesengine'][142];
         displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", $LANG['rulesengine'][142], '',
                      $buttons);
      }
   }


   static function getNumberOfMailCollectors() {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_mailcollectors`";
      $result = $DB->query($query);

      return $DB->result($result, 0, 'cpt');
   }


   static function showMaxFilesize($name, $value = 0) {
      global $LANG;

      $sizes[0] = $LANG['ocsconfig'][11];
      for ($index=1 ; $index<100 ; $index++) {
         $sizes[$index*1048576] = $index. ' '.$LANG['common'][82];
      }
      Dropdown::showFromArray($name, $sizes, array('value' => $value));
   }

}

?>