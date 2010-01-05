<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

require_once(GLPI_PHPMAILER_DIR . "/class.phpmailer.php");

/**
 *  glpi_phpmailer class extends
 */
class glpi_phpmailer extends phpmailer {

   /// Set default variables for all new objects
   var $WordWrap = 80;
   /// Defaut charset
   var $CharSet ="utf-8";

   /**
    * Constructor
   **/
   function __construct() {
      global $CFG_GLPI;

      // Comes from config
      $this->SetLanguage("en", GLPI_PHPMAILER_DIR . "/language/");

      if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
         $this->Mailer = "smtp";
         $this->Host = $CFG_GLPI['smtp_host'];
         if ($CFG_GLPI['smtp_username'] != '') {
            $this->SMTPAuth  = true;
            $this->Username  = $CFG_GLPI['smtp_username'];
            $this->Password  =  $CFG_GLPI['smtp_password'];
         }
         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPSSL) {
            $this->SMTPSecure = "ssl";
         }
         if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPTLS){
            $this->SMTPSecure = "tls";
         }
      }
      if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
         $this->do_debug = 3;
      }
   }

}

/**
 *  Mailing class for trackings
 */
class Mailing {

   //! mailing type (new,attrib,followup,finish)
   var $mailtype=NULL;
   /** Job class variable - job to be mailed
    * @see Job
    */
   var $job=NULL;
   /** User class variable - user who make changes
    * @see User
    */
   var $user=NULL;
   /// Is the followupadded private ?
   var $followupisprivate=NULL;

   /**
    * Constructor
    * @param $type mailing type (new,attrib,followup,finish)
    * @param $job Job to mail
    * @param $user User who made change
    * @param $followupisprivate true if the currently added/modified followup is private
    * @return nothing
    */
   function __construct ($type="",$job=NULL,$user=NULL,$followupisprivate=false) {

      $this->mailtype=$type;
      if (!isset($job->hardwaredatas) || !count($job->hardwaredatas)) {
         $job->getHardwareData();
      }
      $this->job=$job;
      $this->user=$user;
      $this->followupisprivate=$followupisprivate;
   }

   /**
    * Add new mail with lang to current email array
    *
    * @param $emails : emails array
    * @param $mail : new email to add
    * @param $lang used with this email - default to config language
    *
    */
   function addToEmailList(&$emails,$mail,$lang='') {
      global $CFG_GLPI;

      $new_mail=trim($mail);
      $new_lang=trim($lang);
      if (!empty($new_mail)) {
         if (isValidEmail($new_mail) && !isset($emails[$new_mail])) {
            $emails[$new_mail] = (empty($new_lang) ? $CFG_GLPI["language"] : $new_lang);
         }
      }
   }

   /**
    * Give mails to send the mail
    *
    * Determine email to send mail using global config and Mailing type
    * @param $sendprivate false : all users; true : only users who have the right to see private followups
    *
    * @return array containing email
    */
   function get_users_to_send_mail($sendprivate=false) {
      global $DB,$CFG_GLPI;

      $emails=array();
      $query="SELECT *
              FROM `glpi_mailingsettings`
              WHERE `type`='".$this->mailtype."'";
      $result=$DB->query($query);
      if ($DB->numrows($result)) {
         $selectdistinctuser ="SELECT DISTINCT `glpi_users`.`email` AS email,
                                               `glpi_users`.`language` AS lang ";
         $join="";
         $joinprofile=""; //cas PROFILE_MAILING_TYPE
         // If send private is the user can see private followups ?
         if ($sendprivate) {
            $join=" INNER JOIN `glpi_profiles_users`
                        ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`".
                            getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                       $this->job->fields['entities_id'],true).")
                    INNER JOIN `glpi_profiles`
                        ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                            AND `glpi_profiles`.`interface` = 'central'
                            AND `glpi_profiles`.`show_full_ticket` = '1') ";
            $joinprofile=" INNER JOIN `glpi_profiles`
                              ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`
                                  AND `glpi_profiles`.`interface` = 'central'
                                  AND `glpi_profiles`.`show_full_ticket` = '1') ";
         }
         while ($data=$DB->fetch_assoc($result)) {
            switch ($data["mailingtype"]) {
               case USER_MAILING_TYPE :
                  switch ($data["items_id"]) {
                     // ADMIN SEND
                     case ADMIN_MAILING :
                        $this->addToEmailList($emails,$CFG_GLPI["admin_email"]);
                        break;

                     // ADMIN ENTITY SEND
                     case ADMIN_ENTITY_MAILING :
                        $query2 = "SELECT `admin_email` AS email
                                   FROM `glpi_entitydatas`
                                   WHERE `entities_id` = '".$this->job->fields["entities_id"]."'";
                        if ($result2 = $DB->query($query2)) {
                           if ($DB->numrows($result2)==1) {
                              $row = $DB->fetch_array($result2);
                              $this->addToEmailList($emails,$row['email']);
                           }
                        }
                        break;

                     // ASSIGN SEND
                     case ASSIGN_MAILING :
                        if (isset($this->job->fields["users_id_assign"])
                            && $this->job->fields["users_id_assign"]>0) {

                           $query2 = "$selectdistinctuser
                                      FROM `glpi_users`
                                      $join
                                      WHERE `glpi_users`.`id` = '".
                                                $this->job->fields["users_id_assign"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                        break;

                     // ASSIGN SEND
                     case ASSIGN_ENT_MAILING :
                        if (!$sendprivate && isset($this->job->fields["suppliers_id_assign"])
                            && $this->job->fields["suppliers_id_assign"]>0) {

                           $query2 = "SELECT DISTINCT `glpi_suppliers`.`email` AS email
                                      FROM `glpi_suppliers`
                                      WHERE `glpi_suppliers`.`id` = '".
                                                $this->job->fields["suppliers_id_assign"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email']);
                              }
                           }
                        }
                        break;

                     // ASSIGN GROUP SEND
                     case ASSIGN_GROUP_MAILING :
                        if (isset($this->job->fields["groups_id_assign"])
                            && $this->job->fields["groups_id_assign"]>0) {

                           $query="SELECT `glpi_users`.`email` AS email,
                                          `glpi_users`.`language` AS lang
                                   FROM `glpi_groups_users`
                                   INNER JOIN `glpi_users`
                                       ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`) $join
                                   WHERE `glpi_users`.`is_deleted`='0'
                                         AND `glpi_groups_users`.`groups_id`='".
                                                   $this->job->fields["groups_id_assign"]."'";
                           if ($result2= $DB->query($query)) {
                              if ($DB->numrows($result2)) {
                                 while ($row=$DB->fetch_assoc($result2)) {
                                    $this->addToEmailList($emails,$row['email'],$row['lang']);
                                 }
                              }
                           }
                        }
                        break;

                     // SUPERVISOR ASSIGN GROUP SEND
                     case SUPERVISOR_ASSIGN_GROUP_MAILING :
                        if (isset($this->job->fields["groups_id_assign"])
                            && $this->job->fields["groups_id_assign"]>0) {

                           $query2 = "$selectdistinctuser
                                      FROM `glpi_groups`
                                      LEFT JOIN `glpi_users`
                                          ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)
                                      $join
                                      WHERE `glpi_groups`.`id` = '".
                                                $this->job->fields["groups_id_assign"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                        break;

                     // RECIPIENT SEND
                     case RECIPIENT_MAILING :
                        if (isset($this->job->fields["users_id_recipient"])
                            && $this->job->fields["users_id_recipient"]>0) {

                           $query2 = "$selectdistinctuser
                                      FROM `glpi_users`
                                      $join
                                      WHERE `glpi_users`.`id` = '".
                                                $this->job->fields["users_id_recipient"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                        break;

                     // AUTHOR SEND
                     case AUTHOR_MAILING :
                        if ($this->job->fields["use_email_notification"]) {
                           // Uemail = mail of the users_id ? -> use right of the users_id to see private followups
                           // Else not see private
                           $users_idsend=false;
                           $users_idlang=$CFG_GLPI["language"];
                           if (!$sendprivate) {
                              $users_idsend=true;
                           }
                           // Is the user have the same mail that user_email ?
                           $query2 = "$selectdistinctuser
                                      FROM `glpi_users`
                                      $join
                                      WHERE `glpi_users`.`id` = '".
                                                $this->job->fields["users_id"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 if ($row['email']==$this->job->fields["user_email"]) {
                                    $users_idsend=true;
                                    $users_idlang=$row['lang'];
                                 }
                              }
                           }

                           //don't send email if last followup from user, STOP spamming user!
                           $query3 = "SELECT * FROM glpi_ticketfollowups
                                          WHERE tickets_id = '".$this->job->fields["id"]."'
                                          ORDER by date DESC LIMIT 1";
                           $result3=$DB->query($query3);
                           if($data=$DB->fetch_array($result3)){
                              $fup=new TicketFollowup();
                              $fup->getFromDB($data['ID']);
                              if($this->job->fields["users_id"] == $fup->fields["users_id"]) {
                                    $users_idsend=false;
                              }
                           }
                           $DB->free_result($result3);

                           if ($users_idsend) {
                              $this->addToEmailList($emails,$this->job->fields["user_email"],
                                                    $users_idlang);
                           }
                        }
                        break;

                     // SUPERVISOR ASSIGN GROUP SEND
                     case SUPERVISOR_AUTHOR_GROUP_MAILING :
                        if (isset($this->job->fields["groups_id"])
                            && $this->job->fields["groups_id"]>0) {

                           $query2 = "$selectdistinctuser
                                      FROM `glpi_groups`
                                      LEFT JOIN `glpi_users`
                                          ON (`glpi_users`.`id` = `glpi_groups`.`users_id`)
                                      $join
                                      WHERE `glpi_groups`.`id` = '".
                                                $this->job->fields["groups_id"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                        break;

                     // OLD ASSIGN SEND
                     case OLD_ASSIGN_MAILING :
                        if (isset($this->job->fields["_old_assign"])
                            && $this->job->fields["_old_assign"]>0) {

                           $query2 = "$selectdistinctuser
                                      FROM `glpi_users`
                                      $join
                                      WHERE `glpi_users`.`id` = '".
                                                $this->job->fields["_old_assign"]."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                        break;

                     // TECH SEND
                     case TECH_MAILING :
                        if (isset($this->job->fields["items_id"])
                            && $this->job->fields["items_id"]>0
                            && isset($this->job->fields["itemtype"])
                            && class_exists($this->job->fields["itemtype"])) {

                           $item= new $this->job->fields["itemtype"]();
                           if ($item->getFromDB($this->job->fields["items_id"])) {
                              if ($tmp=$item->getField('users_id_tech')) {
                                 $query2 = "$selectdistinctuser
                                          FROM `glpi_users`
                                          $join
                                          WHERE `glpi_users`.`id` = '".$tmp."'";
                                 if ($result2 = $DB->query($query2)) {
                                    if ($DB->numrows($result2)==1) {
                                       $row = $DB->fetch_array($result2);
                                       $this->addToEmailList($emails,$row['email'],$row['email']);
                                    }
                                 }
                              }
                           }
                        }
                        break;

                     // USER SEND
                     case USER_MAILING :
                        if (isset($this->job->fields["items_id"])
                            && $this->job->fields["items_id"]>0
                            && isset($this->job->fields["itemtype"])
                            && class_exists($this->job->fields["itemtype"])) {

                           $item= new $this->job->fields["itemtype"]();
                           if ($item->getFromDB($this->job->fields["items_id"])) {
                              if ($tmp=$item->getField('users_id')) {
                                 $query2 = "$selectdistinctuser
                                          FROM `glpi_users`
                                          $join
                                          WHERE `glpi_users`.`id` = '".$tmp."'";
                                 if ($result2 = $DB->query($query2)) {
                                    if ($DB->numrows($result2)==1) {
                                       $row = $DB->fetch_array($result2);
                                       $this->addToEmailList($emails,$row['email'],$row['lang']);
                                    }
                                 }
                              }
                           }
                        }
                        break;
                  } //fin switch ($data["items_id"])
                  break; //fin case USER_MAILING_TYPE

               case PROFILE_MAILING_TYPE :
                  $query="$selectdistinctuser
                          FROM `glpi_profiles_users`
                          INNER JOIN `glpi_users` ON (`glpi_profiles_users`.`users_id`=`glpi_users`.`id`)
                          $joinprofile
                          WHERE `glpi_users`.`is_deleted`='0'
                                AND `glpi_profiles_users`.`profiles_id`='".$data["items_id"]."' ".
                                getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                           $this->job->fields['entities_id'],true);
                  if ($result2= $DB->query($query)) {
                     if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           $this->addToEmailList($emails,$row['email'],$row['lang']);
                        }
                     }
                  }
                  break;

               case GROUP_MAILING_TYPE :
                  $query="$selectdistinctuser
                          FROM `glpi_groups_users`
                          INNER JOIN `glpi_users` ON (`glpi_groups_users`.`users_id`=`glpi_users`.`id`)
                          $join
                          WHERE `glpi_users`.`is_deleted`='0'
                                AND `glpi_groups_users`.`groups_id`='".$data["items_id"]."'";
                  if ($result2= $DB->query($query)) {
                     if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           $this->addToEmailList($emails,$row['email'],$row['lang']);
                        }
                     }
                  }
                  break;
            }// fin switch ($data["mailingtype"])
         } // fin while
      }// fin if
      return $emails;
   }

   /**
    * Format the mail body to send
   * @param $format text or html
    * @param $sendprivate true if the email contains private followups
    * @return mail body string
    */
   function get_mail_body($format="text", $sendprivate=false) {
      global $CFG_GLPI, $LANG;

      // Create message body from Job and type
      $body="";
      if ($format=="html") {
         if ($CFG_GLPI["show_link_in_mail"] && !empty($CFG_GLPI["url_base"])) {
            $body.="URL: <a href=\"".$CFG_GLPI["url_base"]."/index.php?redirect=ticket_".
                    $this->job->fields["id"]."\">".
                    $CFG_GLPI["url_base"]."/index.php?redirect=ticket_".
                    $this->job->fields["id"]." </a><br><br>";
         }
         $body.=$this->job->textDescription($format);
         $body.=$this->job->textFollowups($format, $sendprivate);
         $body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
         $body.="</body></html>";
         $body=str_replace("\n","<br>\n",$body);
      } else { // text format
         if ($CFG_GLPI["show_link_in_mail"] && !empty($CFG_GLPI["url_base"])) {
            $body.=$LANG['mailing'][1]."\n"; $body.="URL: ".$CFG_GLPI["url_base"].
                   "/index.php?redirect=ticket_".$this->job->fields["id"]."\n";
         }
         $body.=$this->job->textDescription($format);
         $body.=$this->job->textFollowups($format, $sendprivate);
         $body.="\n-- \n".$CFG_GLPI["mailing_signature"];
         $body=str_replace("<br />","\n",$body);
         $body=str_replace("<br>","\n",$body);
      }
      return $body;
}

   /**
    * Format the mail sender to send
    * @return mail sender email string
    */
   function get_mail_sender() {
      global $CFG_GLPI,$DB;

      $query = "SELECT `admin_email` AS email
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->job->fields["entities_id"]."'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            $data=$DB->fetch_assoc($result);
            if (isValidEmail($data["email"])) {
               return $data["email"];
            }
         }
      }
      return $CFG_GLPI["admin_email"];
   }

   /**
    * Format the mail subject to send
    * @return mail subject string
    */
   function get_mail_subject() {
      global $LANG;

      // Create the message subject
      $subject=sprintf("%s%07d%s","[GLPI #",$this->job->fields["id"],"] ");

//      if (isMultiEntitiesMode()) {
//         $subject.=Dropdown::getDropdownName("glpi_entities",$this->job->fields['entities_id'])." | ";
//      }

      switch ($this->mailtype) {
         case "new" :
            $subject.=$LANG['mailing'][9];
            break;

         case "attrib" :
            $subject.=$LANG['mailing'][12];
            break;

         case "followup" :
            $subject.=$LANG['mailing'][10];
            break;

         case "update" :
            $subject.=$LANG['mailing'][30];
            break;

         case "finish" :
            $subject.=$LANG['mailing'][11]." ".convDateTime($this->job->fields["date_mod"]);
            break;

         default :
            $subject.=$LANG['mailing'][13];
            break;
      }

      if (utf8_strlen($this->job->fields['name'])>150) {
         $subject.=" - ".utf8_substr($this->job->fields['name'],0,150)." (...)";
      } else {
         $subject.=" - ".$this->job->fields['name'];
      }
      return $subject;
   }

   /**
    * Get reply to address
    * @param $sender sender address
    * @return return mail
    */
   function get_reply_to_address ($sender) {
      global $CFG_GLPI,$DB;

      $replyto=$CFG_GLPI["admin_email"];
      // Entity conf
      $query = "SELECT `admin_email` AS email, `admin_reply` AS reply
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->job->fields["entities_id"]."'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            $data=$DB->fetch_assoc($result);
            if (isValidEmail($data["reply"])) {
               return $data["reply"];
            } else if (isValidEmail($data["email"])) {
               $replyto=$data["email"];
            }
         }
      }
      // Global conf
      if (isValidEmail($CFG_GLPI["admin_reply"])) {
         return $CFG_GLPI["admin_reply"];
      }
      // No specific config
      switch ($this->mailtype) {
         case "new" :
            if (isset($this->job->fields["user_email"])
                && isValidEmail($this->job->fields["user_email"])) {
               $replyto=$this->job->fields["user_email"];
            } else {
               $replyto=$sender;
            }
            break;

         case "followup" :

         case "update" :
            if (isset($this->user->fields["email"]) && isValidEmail($this->user->fields["email"])) {
               $replyto=$this->user->fields["email"];
            } else {
               $replyto=$sender;
            }
            break;
      }
      return $replyto;
   }

   /**
    * Send mail function
    *
    * Construct email and send it
    *
    * @return mail subject string
    */
   function send() {
      global $CFG_GLPI,$LANG;

      if ($CFG_GLPI["use_mailing"]) {
         if (!is_null($this->job)  && in_array($this->mailtype,array("new",
                                                                 "update",
                                                                 "followup",
                                                                 "finish"))) {
            $senderror=false;
            // get users to send mail
            $users=array();
            // All users
            $users[0]=$this->get_users_to_send_mail(0);
            // Users who could see private followups
            $users[1]=$this->get_users_to_send_mail(1);
            // Delete users who can see private followups to all users list
            foreach ($users[1] as $email => $lang) {
               if (isset($users[0][$email])) {
                  unset($users[0][$email]);
               }
            }
            // New Followup is private : do not send to common users
            if ($this->followupisprivate) {
               unset($users[0]);
            }
            $subjects=array();
            // get sender
            $sender= $this->get_mail_sender();
            // get reply-to address : user->email ou job_email if not set OK
            $replyto=$this->get_reply_to_address ($sender);
            $messageerror=$LANG['mailing'][47];
            // Send all mails

            foreach ($users as $private=>$someusers) {
               if (count($someusers)) {
                  $mmail=new glpi_phpmailer();
                  $mmail->From=$sender;
                  $mmail->AddReplyTo("$replyto", '');
                  $mmail->FromName=$sender;
                  $mmail->isHTML(true);

                  $bodys=array();
                  $altbodys=array();
                  foreach ($someusers as $email => $lang) {
                     if (!isset($subjects[$lang]) || !isset($bodys[$lang])
                         || !isset($altbodys[$lang])) {

                        loadLanguage($lang);
                        if (!isset($subjects[$lang])) {
                           $subjects[$lang]=$this->get_mail_subject();
                        }
                        $bodys[$lang]=$this->get_mail_body("html",$private);
                        $altbodys[$lang]=$this->get_mail_body("text",$private);
                     }
                     $mmail->Subject=$subjects[$lang];
                     $mmail->Body=$bodys[$lang];
                     $mmail->AltBody=$altbodys[$lang];
                     $mmail->AddAddress($email, "");
                     $mmail->MessageID="GLPI-".$this->job->fields["id"].".".time().".".rand().
                                       "@".php_uname('n');

                     if (!$mmail->Send()) {
                        $senderror=true;
                        addMessageAfterRedirect($messageerror."<br>".$mmail->ErrorInfo);
                     } else {
                        logInFile("mail",$LANG['tracking'][38]." ".$email.": ".$subjects[$lang]."\n");
                     }
                     $mmail->ClearAddresses();
                  }
               }
            }
            // Reinit language
            loadLanguage();
            if ($senderror) {
               return false;
            }
         } else {
            addMessageAfterRedirect($LANG['mailing'][112]);
         }
      }
      return true;
   }

}

/**
 *  Mailing class for reservations
 */
class MailingResa {
   /** Reservation class variable
    * @see Reservation
    */
   var $resa;
   //! type of mailing (new, update, delete)
   var $mailtype;

   /**
    * Constructor
    * @param $type mailing type (new,attrib,followup,finish)
    * @param $resa Reservation to mail
    * @return nothing
    */
   function __construct ($resa,$type="new") {
      $this->resa=$resa;
      $this->mailtype=$type;
   }

   /**
    * Add new mail with lang to current email array
    *
    * @param $emails : emails array
    * @param $mail : new email to add
    * @param $lang used with this email - default to config language
    *
    */
   function addToEmailList(&$emails,$mail,$lang='') {
      global $CFG_GLPI;

      $new_mail=trim($mail);
      $new_lang=trim($lang);
      if (!empty($new_mail)) {
         if (isValidEmail($new_mail) && !isset($emails[$new_mail])) {
            $emails[$new_mail] = (empty($new_lang) ? $CFG_GLPI["language"] : $new_lang);
         }
      }
   }

   /**
    * Give mails to send the mail
    *
    * Determine email to send mail using global config and Mailing type
    *
    * @return array containing email
    */
   function get_users_to_send_mail() {
      global $DB,$CFG_GLPI;

      $emails=array();
      $query="SELECT *
              FROM `glpi_mailingsettings`
              WHERE `type`='resa'";
      $result=$DB->query($query);
      $selectuser="SELECT `glpi_users`.`email` AS email,
                          `glpi_users`.`language` AS lang ";
      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_assoc($result)) {
            switch ($data["mailingtype"]) {
               case USER_MAILING_TYPE :
                  switch ($data["items_id"]) {
                     // ADMIN SEND
                     case ADMIN_MAILING :
                        $this->addToEmailList($emails,$CFG_GLPI["admin_email"]);
                        break;

                     // ADMIN ENTITY SEND
                     case ADMIN_ENTITY_MAILING :
                        $ri=new ReservationItem();
                        $entity=-1;
                        if ($ri->getFromDB($this->resa->fields["reservationitems_id"])) {
                           if (class_exists($ri->fields['itemtype'])) {
                              $item = new $ri->fields['itemtype'];
                              if ($item->getFromDB($ri->fields['items_id'])) {
                                 $entity=$item->getField('entities_id');
                              }
                           }
                        }
                        if ($entity>=0) {
                           $query2 = "SELECT `admin_email` AS email
                                      FROM `glpi_entitydatas`
                                      WHERE `entities_id` = '".$entity."'";
                           if ($result2 = $DB->query($query2)) {
                              if ($DB->numrows($result2)==1) {
                                 $row = $DB->fetch_array($result2);
                                 $this->addToEmailList($emails,$row['email']);
                              }
                           }
                        }
                        break;

                     // AUTHOR SEND
                     case AUTHOR_MAILING :
                        $user = new User;
                        if ($user->getFromDB($this->resa->fields["users_id"])) {
                           $this->addToEmailList($emails,$user->fields["email"],
                                                 $user->fields['language']);
                        }
                        break;

                     // TECH SEND
                     case TECH_MAILING :
                        $ri=new ReservationItem();
                        if ($ri->getFromDB($this->resa->fields["reservationitems_id"])) {
                           if (class_exists($ri->fields["itemtype"])) {
                              $item = new $ri->fields["itemtype"]();
                              if ($item->getFromDB($ri->fields["items_id"])) {
                                 if ($tmp=$item->getField('users_id_tech')) {
                                    $query2 = "$selectuser
                                             FROM `glpi_users`
                                             WHERE `glpi_users`.`id` = '".$tmp."'";
                                    if ($result2 = $DB->query($query2)) {
                                       if ($DB->numrows($result2)==1) {
                                          $row = $DB->fetch_row($result2);
                                          $this->addToEmailList($emails,$row['email'],$row['lang']);
                                       }
                                    }
                                 }
                              }
                           }
                        }
                        break;

                     // USER SEND
                     case USER_MAILING :
                        $ri=new ReservationItem();
                        if ($ri->getFromDB($this->resa->fields["reservationitems_id"])) {
                           if (class_exists($ri->fields["itemtype"])) {
                              $item = new $ri->fields["itemtype"]();
                              if ($item->getFromDB($ri->fields["items_id"])) {
                                 if ($tmp=$item->getField('users_id')) {
                                    $query2 = "$selectuser
                                             FROM `glpi_users`
                                             WHERE `glpi_users`.`id` = '".$tmp."'";
                                    if ($result2 = $DB->query($query2)) {
                                       if ($DB->numrows($result2)==1) {
                                          $row = $DB->fetch_row($result2);
                                          $this->addToEmailList($emails,$row['email'],$row['lang']);
                                       }
                                    }
                                 }
                              }
                           }
                        }
                        break;
                  } //fin switch ($data["items_id"])
                  break;

               case PROFILE_MAILING_TYPE :
                  // Get entity
                  $ri=new ReservationItem();
                  $ri->getFromDB($this->resa->fields['reservationitems_id']);
                  if (class_exists($ri->fields['itemtype'])) {
                     $item = new $ri->fields['itemtype']();
                     if ($item->getFromDB($ri->fields['items_id'])) {
                        $entities_id=$item->getField('entities_id');
                        $query="$selectuser
                              FROM `glpi_profiles_users`
                              INNER JOIN `glpi_users`
                                       ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                              WHERE `glpi_profiles_users`.`profiles_id`='".$data["items_id"]."'".
                                    getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                               $entities_id,true);
                        if ($result2= $DB->query($query)) {
                           if ($DB->numrows($result2)) {
                              while ($row=$DB->fetch_assoc($result2)) {
                                 $this->addToEmailList($emails,$row['email'],$row['lang']);
                              }
                           }
                        }
                     }
                  }
                  break;

               case GROUP_MAILING_TYPE :
                  $query="$selectuser
                          FROM `glpi_groups_users`
                          INNER JOIN `glpi_users`
                                 ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_groups_users`.`groups_id`='".$data["items_id"]."'";
                  if ($result2= $DB->query($query)) {
                     if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           $this->addToEmailList($emails,$row['email'],$row['lang']);
                        }
                     }
                  }
                  break;
            } //fin switch ($data["mailingtype"])
         }
      }
      return $emails;
   }

   /**
    * Format the mail sender to send
    * @return mail sender email string
    */
   function get_mail_sender() {
      global $CFG_GLPI,$DB;

      $ri=new ReservationItem();
      $entity=-1;
      if ($ri->getFromDB($this->resa->fields["reservationitems_id"])) {
         if (class_exists($ri->fields['itemtype'])) {
            $item = new $ri->fields['itemtype']();
            if ($item->getFromDB($ri->fields['items_id'])) {
               $entity=$item->getField('entities_id');
            }
         }
      }
      if ($entity>=0) {
         $query = "SELECT `admin_email` AS email
                   FROM `glpi_entitydatas`
                   WHERE `entities_id` = '$entity'";
         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)) {
               $data=$DB->fetch_assoc($result);
               if (isValidEmail($data["email"])) {
                  return $data["email"];
               }
            }
         }
      }
      return $CFG_GLPI["admin_email"];
   }

   /**
    * Format the mail body to send
   * @param $format text or html
    * @return mail body string
    */
   function get_mail_body($format="text") {
      global $CFG_GLPI;

      // Create message body from Job and type
      $body="";
      if ($format=="html") {
         $body.=$this->resa->textDescription("html");
         $body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
         $body.="</body></html>";
         $body=str_replace("\n","<br>",$body);
      } else { // text format
         $body.=$this->resa->textDescription();
         $body.="\n-- \n".$CFG_GLPI["mailing_signature"];
         $body=str_replace("<br />","\n",$body);
         $body=str_replace("<br>","\n",$body);
      }
      return $body;
   }

   /**
    * Format the mail subject to send
    * @return mail subject string
    */
   function get_mail_subject() {
      global $LANG;

      // Create the message subject
      if ($this->mailtype=="new") {
         $subject="[GLPI] ".$LANG['mailing'][19];
      } else if ($this->mailtype=="update") {
         $subject="[GLPI] ".$LANG['mailing'][23];
      } else if ($this->mailtype=="delete") {
         $subject="[GLPI] ".$LANG['mailing'][29];
      }
      return $subject;
   }

   /**
    * Get reply to address
    * @param $sender sender address
    * @return return mail
    */
   function get_reply_to_address ($sender) {
      global $CFG_GLPI;

      $replyto="";
      $user = new User;
      if ($user->getFromDB($this->resa->fields["users_id"])) {
         if (isValidEmail($user->fields["email"])) {
            $replyto=$user->fields["email"];
         } else {
            $replyto=$sender;
         }
      } else {
         $replyto=$sender;
      }
      return $replyto;
   }

   /**
    * Send mail function
    *
    * Construct email and send it
    *
    * @return mail subject string
    */
   function send() {
      global $CFG_GLPI,$LANG;

      if ($CFG_GLPI["use_mailing"] && isValidEmail($CFG_GLPI["admin_email"])) {
         // get users to send mail
         $users=$this->get_users_to_send_mail();
         // get sender
         $sender= $this->get_mail_sender();
         // get reply-to address : user->email ou job_email if not set OK
         $replyto=$this->get_reply_to_address ($sender);

         $mmail=new glpi_phpmailer();
         $mmail->From=$sender;
         $mmail->AddReplyTo("$replyto", '');
         $mmail->FromName=$sender;
         $mmail->isHTML(true);

         // get subject
         $bodys=array();
         $altbodys=array();
         $subjects=array();
         // Send all mails
         if (count($users)) {
            foreach ($users as $email => $lang) {
               if (!isset($subjects[$lang]) || !isset($bodys[$lang]) || !isset($altbodys[$lang])) {
                  loadLanguage($lang);
                  $subjects[$lang]=$this->get_mail_subject();
                  $bodys[$lang]=$this->get_mail_body("html");
                  $altbodys[$lang]=$this->get_mail_body("text");
               }
               $mmail->Subject=$subjects[$lang];
               $mmail->Body=$bodys[$lang];
               $mmail->AltBody=$altbodys[$lang];
               $mmail->AddAddress($email, "");

               if (!$mmail->Send()) {
                  echo "<div class='center'>".$LANG['mailing'][47]."</div>";
                  return false;
               } else {
                  logInFile("mail",$LANG['reservation'][40]." ".$email.": ".$subjects[$lang]."\n");
               }
               $mmail->ClearAddresses();
            }
         } else {
            return false;
         }
      }
      return true;
   }

}

/**
 *  Mailing class for alerts
 */
class MailingAlert {

   /// mailing type (contract,infocom,cartridge,consumable)
   var $mailtype=NULL;
   /// message to send
   var $message="";
   /// working entity
   var $entity="";

   /**
    * Constructor
    * @param $type mailing type (new,attrib,followup,finish)
    * @param $message Message to send
    * @param $entity Restrict to a defined entity
    * @return nothing
    */
   function __construct ($type,$message,$entity=-1) {
      $this->mailtype=$type;
      $this->message=$message;
      $this->entity=$entity;
   }

   /**
    * Give mails to send the mail
    *
    * Determine email to send mail using global config and Mailing type
    *
    * @return array containing email
    */
   function get_users_to_send_mail() {
      global $DB,$CFG_GLPI;

      $emails=array();
      $query="SELECT *
              FROM `glpi_mailingsettings`
              WHERE `type`='".$this->mailtype."'";
      $result=$DB->query($query);
      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_assoc($result)) {
            switch ($data["mailingtype"]) {
               case USER_MAILING_TYPE :
                  switch($data["items_id"]) {
                     // ADMIN SEND
                     case ADMIN_MAILING :
                        if (isValidEmail($CFG_GLPI["admin_email"])
                            && !isset($emails[$CFG_GLPI["admin_email"]])) {
                           $emails[$CFG_GLPI["admin_email"]]=$CFG_GLPI["language"];
                        }
                        break;

                     // ADMIN ENTITY SEND
                     case ADMIN_ENTITY_MAILING :
                        $query2 = "SELECT `admin_email` AS email
                                   FROM `glpi_entitydatas`
                                   WHERE `entities_id` = '".$this->entity."'";
                        if ($result2 = $DB->query($query2)) {
                           if ($DB->numrows($result2)==1) {
                              $row = $DB->fetch_array($result2);
                              if (isValidEmail($row['email']) && !isset($emails[$row['email']])) {
                                 $emails[$row['email']]=$CFG_GLPI["language"];
                              }
                           }
                        }
                        break;
                  }
                  break;

               case PROFILE_MAILING_TYPE :
                  $query="SELECT `glpi_users`.`email` AS email, `glpi_users`.`language` AS lang
                          FROM `glpi_profiles_users`
                          INNER JOIN `glpi_users`
                                 ON (`glpi_profiles_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_profiles_users`.`profiles_id`='".$data["items_id"]."'".
                                getEntitiesRestrictRequest("AND","glpi_profiles_users","entities_id",
                                                           $this->entity,true);
                  if ($result2= $DB->query($query)) {
                     if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           if (isValidEmail($row['email']) && !isset($emails[$row['email']])) {
                              $emails[$row['email']]=$row['lang'];
                           }
                        }
                     }
                  }
                  break;

               case GROUP_MAILING_TYPE :
                  $query="SELECT `glpi_users`.`email` AS email, `glpi_users`.`language` AS lang
                          FROM `glpi_groups_users`
                          INNER JOIN `glpi_users`
                                 ON (`glpi_groups_users`.`users_id` = `glpi_users`.`id`)
                          WHERE `glpi_groups_users`.`groups_id`='".$data["items_id"]."'";
                  if ($result2= $DB->query($query)) {
                     if ($DB->numrows($result2)) {
                        while ($row=$DB->fetch_assoc($result2)) {
                           if (isValidEmail($row['email'])&&!isset($emails[$row['email']])){
                              $emails[$row['email']]=$row['lang'];
                           }
                        }
                     }
                  }
                  break;
            }
         }
      }
      return $emails;
   }

   /**
    * Format the mail body to send
    * @param $format text or html
    * @return mail body string
    */
   function get_mail_body($format="text") {
      global $CFG_GLPI, $LANG;

      // Create message body from Job and type
      $body="";

      if ($format=="html") {
         $body.=$this->message;
         $body.="<br>-- <br>".$CFG_GLPI["mailing_signature"];
         $body.="</body></html>";
         $body=str_replace("\n","<br>",$body);
      } else { // text format
         $body.=$this->message;
         $body.="\n-- \n".$CFG_GLPI["mailing_signature"];
         $body=str_replace("<br />","\n",$body);
         $body=str_replace("<br>","\n",$body);
      }
      return $body;
   }

   /**
    * Format the mail subject to send
    * @return mail subject string
    */
   function get_mail_subject() {
      global $LANG;

      // Create the message subject
      $subject="[GLPI]";

      switch ($this->mailtype) {
         case "alertcartridge" :
            $subject.=" ".$LANG['mailing'][33]. " - ".Dropdown::getDropdownName("glpi_entities",$this->entity);
            break;

         case "alertconsumable" :
            $subject.=" ".$LANG['mailing'][36]. " - ".Dropdown::getDropdownName("glpi_entities",$this->entity);
            break;

         case "alertcontract" :
            $subject.=" ".$LANG['mailing'][39]. " - ".Dropdown::getDropdownName("glpi_entities",$this->entity);
            break;

         case "alertinfocom" :
            $subject.=" ".$LANG['mailing'][41]. " - ".Dropdown::getDropdownName("glpi_entities",$this->entity);
            break;

         case "alertlicense" :
            $subject.=" ".$LANG['mailing'][52]. " - ".Dropdown::getDropdownName("glpi_entities",$this->entity);
         break;
      }
      return $subject;
   }

   /**
    * Format the mail sender to send
    * @return mail sender email string
    */
   function get_mail_sender() {
      global $CFG_GLPI,$DB;

      $query = "SELECT `admin_email` AS email
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '".$this->entity."'";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            $data=$DB->fetch_assoc($result);
            if (isValidEmail($data["email"])) {
               return $data["email"];
            }
         }
      }
      return $CFG_GLPI["admin_email"];
   }

   /**
    * Send mail function
    *
    * Construct email and send it
    *
    * @return mail subject string
    */
   function send() {
      global $CFG_GLPI,$LANG;

      if ($CFG_GLPI["use_mailing"]) {
         // get users to send mail
         $users=$this->get_users_to_send_mail();
         // get subject OK
         $subject=$this->get_mail_subject();
         // get sender :  OK
         $sender= $this->get_mail_sender();
         // get reply-to address : user->email ou job_email if not set OK
         $replyto=$sender;

         $mmail=new glpi_phpmailer();
         $mmail->From=$sender;
         $mmail->AddReplyTo("$replyto", '');
         $mmail->FromName=$sender;
         $mmail->isHTML(true);

         // Send all mails

         // get subject
         $bodys=array();
         $altbodys=array();
         $subjects=array();
         // Send all mails
         if (count($users)) {
            foreach ($users as $email => $lang) {
               if (!isset($subjects[$lang]) || !isset($bodys[$lang]) || !isset($altbodys[$lang])) {
                  loadLanguage($lang);
                  $subjects[$lang]=$this->get_mail_subject();
                  $bodys[$lang]=$this->get_mail_body("html");
                  $altbodys[$lang]=$this->get_mail_body("text");
               }
               $mmail->Subject=$subjects[$lang];
               $mmail->Body=$bodys[$lang];
               $mmail->AltBody=$altbodys[$lang];
               $mmail->AddAddress($email, "");

               if (!$mmail->Send()) {
                  addMessageAfterRedirect($LANG['mailing'][47]);
                  return false;
               } else {
                  logInFile("mail",$LANG['mailing'][111]." ".$email.": ".$subjects[$lang]."\n");
               }
               $mmail->ClearAddresses();
            }
         } else {
            return false;
         }
      }
      return true;
   }

}

?>