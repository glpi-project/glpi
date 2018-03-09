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

class NotificationEventMailing extends NotificationEventAbstract implements NotificationEventInterface {

   static public function getTargetFieldName() {
      return 'email';
   }


   static public function getTargetField(&$data) {
      $field = self::getTargetFieldName();

      if (!isset($data[$field])
         && isset($data['users_id'])) {
         // No email set : get default for user
         $data[$field] = UserEmail::getDefaultForUser($data['users_id']);
      }

      if (empty($data[$field]) or !NotificationMailing::isUserAddressValid($data[$field])) {
         $data[$field] = null;
      } else {
         $data[$field] = trim(Toolbox::strtolower($data[$field]));
      }

      return $field;
   }


   static public function canCron() {
      return true;
   }


   static public function getAdminData() {
      global $CFG_GLPI;

      if (!NotificationMailing::isUserAddressValid($CFG_GLPI['admin_email'])) {
         return false;
      }

      return [
         'email'     => $CFG_GLPI['admin_email'],
         'name'      => $CFG_GLPI['admin_email_name'],
         'language'  => $CFG_GLPI['language']
      ];
   }


   static public function getEntityAdminsData($entity) {
      global $DB, $CFG_GLPI;

      $iterator = $DB->request([
         'FROM'   => 'glpi_entities',
         'WHERE'  => ['id' => $entity]
      ]);

      $admins = [];

      while ($row = $iterator->next()) {
         if (NotificationMailing::isUserAddressValid($row['admin_email'])) {
            $admins[] = [
               'language'  => $CFG_GLPI['language'],
               'email'     => $row['admin_email'],
               'name'      => $row['admin_email_name']
            ];
         }
      }

      if (count($admins)) {
         return $admins;
      } else {
         return false;
      }
   }


   static public function send(array $data) {
      global $CFG_GLPI, $DB;

      $processed = [];

      foreach ($data as $row) {
         //make sure mailer is reset on each mail
         $mmail = new GLPIMailer();
         $current = new QueuedNotification();
         $current->getFromResultSet($row);

         $headers = importArrayFromDB($current->fields['headers']);
         if (is_array($headers) && count($headers)) {
            foreach ($headers as $key => $val) {
               $mmail->AddCustomHeader("$key: $val");
            }
         }

         // Add custom header for mail grouping in reader
         $mmail->AddCustomHeader("In-Reply-To: <GLPI-".$current->fields["itemtype"]."-".
                                 $current->fields["items_id"].">");

         $mmail->SetFrom($current->fields['sender'], $current->fields['sendername']);

         if ($current->fields['replyto']) {
            $mmail->AddReplyTo($current->fields['replyto'], $current->fields['replytoname']);
         }
         $mmail->Subject  = $current->fields['name'];

         if (empty($current->fields['body_html'])) {
            $mmail->isHTML(false);
            $mmail->Body = $current->fields['body_text'];
         } else {
            $mmail->isHTML(true);
            $mmail->Body = '';
            $current->fields['body_html'] = Html::entity_decode_deep($current->fields['body_html']);

            // manage item attached documents
            $document_items = $DB->request('glpi_documents_items', [
               'items_id' => $current->fields['items_id'],
               'itemtype' => $current->fields['itemtype'],
            ]);
            $inline_docs = [];
            $doc = new Document();
            if (count($document_items)) {
               foreach ($document_items as $doc_i_data) {
                  $doc->getFromDB($doc_i_data['documents_id']);
                  // Add embeded image if tag present in ticket content
                  if (preg_match_all('/'.Document::getImageTag($doc->fields['tag']).'/',
                                     $current->fields['body_html'], $matches, PREG_PATTERN_ORDER)) {
                     $tag = Document::getImageTag($doc->fields['tag']);
                     $image_path = Document::getImage(
                        GLPI_DOC_DIR."/".$doc->fields['filepath'],
                        'mail'
                     );
                     if ($mmail->AddEmbeddedImage($image_path,
                                                  $tag,
                                                  $doc->fields['filename'],
                                                  'base64',
                                                  $doc->fields['mime'])) {
                        $inline_docs[$doc_i_data['documents_id']] = $tag;
                     }
                  } else if ($CFG_GLPI['attach_ticket_documents_to_mail']) {
                     // Add all other attachments, according to configuration
                     $path = GLPI_DOC_DIR."/".$doc->fields['filepath'];
                     if (Document::isImage($path)) {
                        $path = Document::getImage(
                           $path,
                           'mail'
                        );
                     }
                     $mmail->addAttachment(
                        $path,
                        $doc->fields['filename']
                     );
                  }
               }
            }

            // manage inline images (and not added as documents in object)
            $matches = [];
            if (preg_match_all("/<img[^>]*src=(\"|')[^\"']*document\.send\.php\?docid=([0-9]+)[^\"']*(\"|')[^<]*>/",
                               $current->fields['body_html'],
                               $matches)) {
               if (isset($matches[2])) {
                  foreach ($matches[2] as $pos=>$docID) {
                     if (!in_array($docID, $inline_docs)) {
                        $doc->getFromDB($docID);
                        $tag = Document::getImageTag($doc->fields['tag']);

                        //find width
                        $width = null;
                        if (preg_match("/width=[\"|'](\d*(\.\d*)?)[\"|']/", $matches[0][$pos], $wmatches)) {
                           if (isset($wmatches[1])) {
                              $width = round($wmatches[1]);
                           }
                        }
                        $height = null;
                        if (preg_match("/height=[\"|'](\d*(\.\d*)?)[\"|']/", $matches[0][$pos], $hmatches)) {
                           if (isset($wmatches[1])) {
                              $height = round($hmatches[1]);
                           }
                        }

                        $image_path = Document::getImage(
                           GLPI_DOC_DIR."/".$doc->fields['filepath'],
                           'mail',
                           $width,
                           $height
                        );
                        if ($mmail->AddEmbeddedImage($image_path,
                                                     $tag,
                                                     $doc->fields['filename'],
                                                     'base64',
                                                     $doc->fields['mime'])) {
                           $inline_docs[$docID] = $tag;
                        }
                     }
                  }
               }
            }

            // replace img[src] and a[href] by cid:tag in html content
            foreach ($inline_docs as $docID => $tag) {
               $current->fields['body_html'] = preg_replace([
                     '/src=["\'][^"\']*document\.send\.php\?docid='.$docID.'[^"\']*["\']/',
                     '/href=["\'][^"\']*document\.send\.php\?docid='.$docID.'[^"\']*["\']/',
                  ], [
                     "src=\"cid:$tag\"",
                     "href='".$CFG_GLPI['url_base']."/front/document.send.php?docid=$docID'",
                  ],
                  $current->fields['body_html']);
            }

            $mmail->Body   .= $current->fields['body_html'];
            $mmail->AltBody = $current->fields['body_text'];
         }

         $recipient = $current->getField('recipient');
         if (defined('GLPI_FORCE_MAIL')) {
            //force recipient to configured email address
            $recipient = GLPI_FORCE_MAIL;
            //add original email addess to message body
            $text = sprintf(__('Original email address was %1$s'), $current->getField('recipient'));
            $mmail->Body      .= "<br/>$text";
            $mmail->AltBody   .= $text;
         }

         $mmail->AddAddress($recipient, $current->fields['recipientname']);

         if (!empty($current->fields['messageid'])) {
            $mmail->MessageID = "<".$current->fields['messageid'].">";
         }

         $messageerror = __('Error in sending the email');

         if (!$mmail->Send()) {
            Session::addMessageAfterRedirect($messageerror . "<br/>" . $mmail->ErrorInfo, true, ERROR);

            $retries = $CFG_GLPI['smtp_max_retries'] - $current->fields['sent_try'];
            Toolbox::logInFile("mail-error",
                              sprintf(__('%1$s. Message: %2$s, Error: %3$s'),
                                       sprintf(__('Warning: an email was undeliverable to %s with %d retries remaining'),
                                                $current->fields['recipient'], $retries),
                                       $current->fields['name'],
                                       $mmail->ErrorInfo."\n"));

            if ($retries <= 0) {
               Toolbox::logInFile("mail-error",
                                 sprintf(__('%1$s: %2$s'),
                                          sprintf(__('Fatal error: giving up delivery of email to %s'),
                                                $current->fields['recipient']),
                                          $current->fields['name']."\n"));
               $current->delete(['id' => $current->fields['id']]);
            }

            $mmail->ClearAddresses();
            $input = [
                'id'        => $current->fields['id'],
                'sent_try'  => $current->fields['sent_try'] + 1
            ];

            if ($CFG_GLPI["smtp_retry_time"] > 0) {
               $input['send_time'] = date("Y-m-d H:i:s", strtotime('+' . $CFG_GLPI["smtp_retry_time"] . ' minutes')); //Delay X minutes to try again
            }
            $current->update($input);
         } else {
            //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
            Toolbox::logInFile("mail",
                               sprintf(__('%1$s: %2$s'),
                                        sprintf(__('An email was sent to %s'),
                                                $current->fields['recipient']),
                                        $current->fields['name']."\n"));
            $mmail->ClearAddresses();
            $processed[] = $current->getID();
            $current->update(['id'        => $current->fields['id'],
                                'sent_time' => $_SESSION['glpi_currenttime']]);
            $current->delete(['id'        => $current->fields['id']]);
         }
      }

      return count($processed);
   }

   static protected function extraRaise($params) {
      //Set notification's signature (the one which corresponds to the entity)
      $entity = $params['notificationtarget']->getEntity();
      $params['template']->setSignature(Notification::getMailingSignature($entity));
   }
}
