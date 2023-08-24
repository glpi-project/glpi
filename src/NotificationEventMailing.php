<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use PHPMailer\PHPMailer\PHPMailer;

class NotificationEventMailing extends NotificationEventAbstract
{
    public static function getTargetFieldName()
    {
        return 'email';
    }


    public static function getTargetField(&$data)
    {
        $field = self::getTargetFieldName();

        if (
            !isset($data[$field])
            && isset($data['users_id'])
        ) {
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


    public static function canCron()
    {
        return true;
    }


    public static function getAdminData()
    {
        global $CFG_GLPI;

        $admin = Config::getAdminEmailSender();
        if ($admin['email'] !== null) {
            $admin['language'] = $CFG_GLPI['language'];

            $user = new User();
            if ($user->getFromDBbyEmail($admin['email'])) {
                $admin['users_id'] = $user->getID();
            }

            return $admin;
        }

        return false;
    }


    public static function getEntityAdminsData($entity)
    {
        global $CFG_GLPI;

        $admin = Config::getAdminEmailSender($entity);
        if ($admin['email'] !== null) {
            $admin['language'] = $CFG_GLPI['language'];

            $user = new User();
            if ($user->getFromDBbyEmail($admin['email'])) {
                $admin['users_id'] = $user->getID();
            }

            return [$admin];
        }

        return false;
    }


    public static function send(array $data)
    {
        global $CFG_GLPI, $DB;

        $processed = [];

        foreach ($data as $row) {
           //make sure mailer is reset on each mail
            $mmail = new GLPIMailer();
            $current = new QueuedNotification();
            $current->getFromResultSet($row);

            try {
                $headers = importArrayFromDB($current->fields['headers']);
                if (is_array($headers) && count($headers)) {
                    foreach ($headers as $key => $val) {
                        $mmail->AddCustomHeader("$key: $val");
                    }
                }

                if ($current->fields['event'] === null) {
                    // Notifications that were pushed in queue before upgrade to GLPI 10.0.8+ have a `null` value in `event` field.
                    // Build the `In-Reply-To` header as it was done before GLPI 10.0.8.
                    $mmail->AddCustomHeader(
                        str_replace(
                            [
                                '%uuid',
                                '%itemtype',
                                '%items_id'
                            ],
                            [
                                Config::getUuid('notification'),
                                $current->fields['itemtype'],
                                $current->fields['items_id']
                            ],
                            "In-Reply-To: <GLPI-%uuid-%itemtype-%items_id>"
                        )
                    );
                } elseif (is_a($current->fields['itemtype'], CommonDBTM::class, true)) {
                    $reference_event = $current->fields['itemtype']::getMessageReferenceEvent($current->fields['event']);
                    if ($reference_event !== null && $reference_event !== $current->fields['event']) {
                        // Add `In-Reply-To` and `References` for mail grouping in reader when:
                        // - there is a reference event (i.e. we want to add current notification to a thread)
                        // - event is not the reference event (i.e. the thread has already be initiated).
                        // see https://datatracker.ietf.org/doc/html/rfc2822#section-3.6.4
                        $email_ref = NotificationTarget::getMessageIdForEvent(
                            $current->fields['itemtype'],
                            $current->fields['items_id'],
                            $reference_event
                        );
                        $mmail->AddCustomHeader("In-Reply-To: <{$email_ref}>");
                        $mmail->AddCustomHeader("References: <{$email_ref}>");
                    }
                }

                $mmail->SetFrom($current->fields['sender'], $current->fields['sendername']);

                if ($current->fields['replyto']) {
                    $mmail->AddReplyTo($current->fields['replyto'], $current->fields['replytoname']);
                }
                $mmail->Subject = $current->fields['name'];

                $is_html = !empty($current->fields['body_html']);

                $documents_ids = [];
                $documents_to_attach = [];
                if ($is_html || $CFG_GLPI['attach_ticket_documents_to_mail']) {
                    // Retieve document list if mail is in HTML format (for inline images)
                    // or if documents are attached to mail.
                    $item = getItemForItemtype($current->fields['itemtype']);
                    if (
                        $item !== false
                        && (
                            $current->fields['items_id'] > 0
                            || ($current->fields['itemtype'] == Entity::class && $current->fields['items_id'] == 0)
                        )
                        && $item->getFromDB($current->fields['items_id'])
                    ) {
                        $doc_crit = [
                            'items_id' => $current->fields['items_id'],
                            'itemtype' => $current->fields['itemtype'],
                        ];
                        if ($item instanceof CommonITILObject) {
                            $doc_crit = $item->getAssociatedDocumentsCriteria(true);
                            if ($is_html) {
                                // Remove documents having "NO_TIMELINE" position if mail is HTML, as
                                // these documents corresponds to inlined images.
                                // If notification is in plain text, they should be kepts as they cannot be rendered in text.
                                $doc_crit[] = [
                                    'timeline_position' => ['>', CommonITILObject::NO_TIMELINE]
                                ];
                            }
                        }
                        $doc_items_iterator = $DB->request(
                            [
                                'SELECT' => ['documents_id'],
                                'FROM' => Document_Item::getTable(),
                                'WHERE' => $doc_crit,
                            ]
                        );
                        foreach ($doc_items_iterator as $doc_item) {
                            $documents_ids[] = $doc_item['documents_id'];
                        }
                    }
                }

                $mmail->isHTML($is_html);
                if (!$is_html) {
                    $mmail->Body = GLPIMailer::normalizeBreaks($current->fields['body_text']);
                    $documents_to_attach = $documents_ids; // Attach all documents
                } else {
                    $mmail->Body = '';
                    $inline_docs = [];
                    foreach ($documents_ids as $document_id) {
                        $doc = new Document();
                        if ($doc->getFromDB($document_id) === false) {
                            trigger_error(sprintf('Unable to load document %d.', $document_id), E_USER_WARNING);
                            continue;
                        }
                        // Add embeded image if tag present in ticket content
                        if (
                            preg_match_all(
                                '/' . preg_quote($doc->fields['tag']) . '/',
                                $current->fields['body_html'],
                                $matches,
                                PREG_PATTERN_ORDER
                            )
                        ) {
                            // Make sure file still exists
                            if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
                                trigger_error('Failed to add document ' . $doc->fields['filepath'] . ' to mail: file not found', E_USER_WARNING);
                                continue;
                            }
                            $image_path = Document::getImage(
                                GLPI_DOC_DIR . "/" . $doc->fields['filepath'],
                                'mail'
                            );
                            if (
                                $mmail->AddEmbeddedImage(
                                    $image_path,
                                    $doc->fields['tag'],
                                    $doc->fields['filename'],
                                    'base64',
                                    $doc->fields['mime']
                                )
                            ) {
                                $inline_docs[$document_id] = $doc->fields['tag'];
                            }
                        } else {
                            // Attach only documents that are not inlined images
                            $documents_to_attach[] = $document_id;
                        }
                    }

                    // manage inline images (and not added as documents in object)
                    $matches = [];
                    if (
                        preg_match_all(
                            "/<img[^>]*src=(\"|')[^\"']*document\.send\.php\?docid=([0-9]+)[^\"']*(\"|')[^<]*>/",
                            $current->fields['body_html'],
                            $matches
                        )
                    ) {
                        if (isset($matches[2])) {
                            foreach ($matches[2] as $pos => $docID) {
                                if (in_array($docID, $inline_docs)) {
                                    // Already in mapping
                                    continue;
                                }

                                $doc = new Document();
                                if ($doc->getFromDB($docID) === false) {
                                    $inline_docs[$docID] = 'notfound'; // Add mapping entry to ensure that src is converted to an absolute URL
                                    trigger_error(sprintf('Unable to load document %d.', $docID), E_USER_WARNING);
                                    continue;
                                }

                                //find width
                                $custom_width = null;
                                if (preg_match("/width=[\"|'](\d+)(\.\d+)?[\"|']/", $matches[0][$pos], $wmatches)) {
                                    $custom_width = intval($wmatches[1]);
                                }
                                $custom_height = null;
                                if (preg_match("/height=[\"|'](\d+)(\.\d+)?[\"|']/", $matches[0][$pos], $hmatches)) {
                                    $custom_height = intval($hmatches[1]);
                                }

                                // Make sure file still exists
                                if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
                                    trigger_error('Failed to add document ' . $doc->fields['filepath'] . ' to mail: file not found', E_USER_WARNING);
                                    continue;
                                }

                                $img_infos = getimagesize(GLPI_DOC_DIR . "/" . $doc->fields['filepath']);

                                if (!$img_infos) {
                                    // Failure to read image size, skip to avoid a divide by zero exception
                                    continue;
                                }

                                $initial_width = $img_infos[0];
                                $initial_height = $img_infos[1];

                                if ($custom_width !== null && $custom_height === null) {
                                    //compute height if needed
                                    $custom_height = $initial_height * $custom_width / $initial_width;
                                } elseif ($custom_height !== null && $custom_width === null) {
                                    //compute width if needed
                                    $custom_width = $initial_width * $custom_height / $initial_height;
                                } elseif ($custom_height === null && $custom_width === null) {
                                    //if both are null keep initial size
                                    $custom_width = $initial_width;
                                    $custom_height = $initial_height;
                                }

                                $image_path = Document::getImage(
                                    GLPI_DOC_DIR . "/" . $doc->fields['filepath'],
                                    'mail',
                                    $custom_width,
                                    $custom_height
                                );
                                if (
                                    $mmail->AddEmbeddedImage(
                                        $image_path,
                                        $doc->fields['tag'],
                                        $doc->fields['filename'],
                                        'base64',
                                        $doc->fields['mime']
                                    )
                                ) {
                                    $inline_docs[$docID] = $doc->fields['tag'];
                                }
                            }
                        }
                    }

                    // replace img[src] by cid:tag in html content
                    // replace a[href] by absolute URL
                    foreach ($inline_docs as $docID => $tag) {
                        $current->fields['body_html'] = preg_replace(
                            [
                                '/src=["\'][^"\']*document\.send\.php\?docid=' . $docID . '(&[^"\']+)?["\']/',
                                '/href=["\'][^"\']*document\.send\.php\?docid=' . $docID . '(&[^"\']+)?["\']/',
                            ],
                            [
                                'src="cid:' . $tag . '"',
                                'href="' . $CFG_GLPI['url_base'] . '/front/document.send.php?docid=' . $docID . '$1"',
                            ],
                            $current->fields['body_html']
                        );
                    }

                    $mmail->Body = GLPIMailer::normalizeBreaks($current->fields['body_html']);
                    $mmail->AltBody = GLPIMailer::normalizeBreaks($current->fields['body_text']);
                }

                self::attachDocuments($mmail, $documents_to_attach);

                $recipient = $current->getField('recipient');
                if (defined('GLPI_FORCE_MAIL')) {
                    //force recipient to configured email address
                    $recipient = GLPI_FORCE_MAIL;
                    //add original email addess to message body
                    $text = sprintf(__('Original email address was %1$s'), $current->getField('recipient'));
                    $mmail->Body .= "<br/>$text";
                    $mmail->AltBody .= $text;
                }

                $mmail->AddAddress($recipient, $current->fields['recipientname']);

                if (!empty($current->fields['messageid'])) {
                    $mmail->MessageID = "<" . $current->fields['messageid'] . ">";
                }
            } catch (\Throwable $e) {
                self::handleFailedSend($current, $e->getMessage());
            }

            if (!$mmail->Send()) {
                self::handleFailedSend($current, $mmail->ErrorInfo);
            } else {
               //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
                Toolbox::logInFile(
                    "mail",
                    sprintf(
                        __('%1$s: %2$s'),
                        sprintf(
                            __('An email was sent to %s'),
                            $current->fields['recipient']
                        ),
                        $current->fields['name'] . "\n"
                    )
                );
                $mmail->ClearAddresses();
                $processed[] = $current->getID();
                $current->update(['id'        => $current->fields['id'],
                    'sent_time' => $_SESSION['glpi_currenttime']
                ]);
                $current->delete(['id'        => $current->fields['id']]);
            }
        }

        return count($processed);
    }

    /**
     * Handle a failure when trying to send an email
     * @param QueuedNotification $notification The notification that failed
     * @param string $error The error message to log
     * @return void
     */
    private static function handleFailedSend(QueuedNotification $notification, string $error): void
    {
        global $CFG_GLPI;

        $messageerror = __('Error in sending the email');
        Session::addMessageAfterRedirect($messageerror . "<br/>" . $error, true, ERROR);

        $retries = $CFG_GLPI['smtp_max_retries'] - $notification->fields['sent_try'];
        Toolbox::logInFile(
            "mail-error",
            sprintf(
                __('%1$s. Message: %2$s, Error: %3$s'),
                sprintf(
                    __('Warning: an email was undeliverable to %s with %d retries remaining'),
                    $notification->fields['recipient'],
                    $retries
                ),
                $notification->fields['name'],
                $error . "\n"
            )
        );

        if ($retries <= 0) {
            Toolbox::logInFile(
                "mail-error",
                sprintf(
                    __('%1$s: %2$s'),
                    sprintf(
                        __('Fatal error: giving up delivery of email to %s'),
                        $notification->fields['recipient']
                    ),
                    $notification->fields['name'] . "\n"
                )
            );
            $notification->delete(['id' => $notification->fields['id']]);
        }

        $input = [
            'id'        => $notification->fields['id'],
            'sent_try'  => $notification->fields['sent_try'] + 1
        ];

        if ($CFG_GLPI["smtp_retry_time"] > 0) {
            $input['send_time'] = date("Y-m-d H:i:s", strtotime('+' . $CFG_GLPI["smtp_retry_time"] . ' minutes')); //Delay X minutes to try again
        }
        $notification->update($input);
    }

    /**
     * Attach documents to message.
     * Documents will not be attached if configuration says they should not be.
     *
     * @param GLPIMailer $mmail
     * @param array      $documents_ids
     *
     * @return void
     */
    private static function attachDocuments(GLPIMailer $mmail, array $documents_ids)
    {
        global $CFG_GLPI;

        if (!$CFG_GLPI['attach_ticket_documents_to_mail']) {
            return;
        }

        $document = new Document();
        foreach ($documents_ids as $document_id) {
            if ($document->getFromDB($document_id) === false) {
                trigger_error(sprintf('Unable to load document %d.', $document_id), E_USER_WARNING);
                continue;
            }
            $path = GLPI_DOC_DIR . "/" . $document->fields['filepath'];
            if (Document::isImage($path)) {
                $path = Document::getImage(
                    $path,
                    'mail'
                );
            }

            $encoding = PHPMailer::ENCODING_BASE64;
            $mime = mime_content_type($path);
            if ($mime == "message/rfc822") {
               // messages/rfc822 can't be encoded in base64 according to RFC2046
               // https://datatracker.ietf.org/doc/html/rfc2046
                $encoding = PHPMailer::ENCODING_8BIT;
            }

            $mmail->addAttachment(
                $path,
                $document->fields['filename'],
                $encoding
            );
        }
    }

    protected static function extraRaise($params)
    {
       //Set notification's signature (the one which corresponds to the entity)
        $entity = $params['notificationtarget']->getEntity();
        $params['template']->setSignature(Notification::getMailingSignature($entity));
    }
}
