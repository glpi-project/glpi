<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Symfony\Component\Mime\Address;

/**
 *  NotificationMailing class implements the NotificationInterface
 **/
class NotificationMailing implements NotificationInterface
{
    /**
     * Check data
     *
     * @param mixed $value   The data to check (may differ for every notification mode)
     * @param array $options Optionnal special options (may be needed)
     *
     * @return boolean
     **/
    public static function check($value, $options = [])
    {
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
    public static function isUserAddressValid($address, $options = ['checkdns' => false])
    {
        //drop sanitize...
        $isValid = GLPIMailer::validateAddress($address);

        $checkdns = ($options['checkdns'] ?? false);
        if ($checkdns) {
            $domain    = substr($address, strrpos($address, '@') + 1);
            if (
                $isValid
                && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))
            ) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }


    /**
     * Sends a test email to the administrator.
     *
     * @return array An array containing success, error and debug information
     */
    public static function testNotification(): array
    {
        global $CFG_GLPI;

        $sender = Config::getEmailSender();
        if ($sender['email'] === null || !self::isUserAddressValid($sender['email'])) {
            return [
                'success' => false,
                'error'   => __('Sender email is not a valid email address.'),
                'debug'   => null,
            ];
        }

        $mmail = new GLPIMailer();
        $mail = $mmail->getEmail();

        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
        // For exchange
        $mail->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, NDR, RN, NRN');
        $mail->from(new Address($sender['email'], $sender['name'] ?? ''));

        $text = __('This is a test email.') . "\n-- \n" . $CFG_GLPI["mailing_signature"];
        $recipient = $CFG_GLPI['admin_email'];
        if (defined('GLPI_FORCE_MAIL')) {
            Toolbox::deprecated('Usage of the `GLPI_FORCE_MAIL` constant is deprecated. Please use a mail catcher service instead.');
            //force recipient to configured email address
            $recipient = GLPI_FORCE_MAIL;
            //add original email address to message body
            $text .= "\n" . sprintf(__('Original email address was %1$s'), $CFG_GLPI['admin_email']);
        }

        $mail->to(new Address($recipient, $CFG_GLPI['admin_email_name']));
        $mail->subject("[GLPI] " . __('Mail test'));
        $mail->text($text);

        $success = $mmail->send();

        return [
            'success' => $success,
            'error'   => $mmail->getError(),
            'debug'   => $mmail->getDebug(),
        ];
    }


    public function sendNotification($options = [])
    {
        global $CFG_GLPI;

        $data = [];
        $data['itemtype']                             = $options['_itemtype'];
        $data['items_id']                             = $options['_items_id'];
        $data['notificationtemplates_id']             = $options['_notificationtemplates_id'];
        $data['entities_id']                          = $options['_entities_id'];

        $data["headers"]['Auto-Submitted']            = "auto-generated";
        $data["headers"]['X-Auto-Response-Suppress']  = "OOF, DR, NDR, RN, NRN";

        $data['sender']                               = $options['from'];
        $data['sendername']                           = $options['fromname'];

        $data['event'] = $options['event'] ?? null; // `event` has been added in GLPI 10.0.7
        $data['itemtype_trigger'] = $options['itemtype_trigger'] ?? null;
        $data['items_id_trigger'] = $options['items_id_trigger'] ?? 0;

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

        $data['recipient']                            = $options['to'];
        $data['recipientname']                        = $options['toname'];

        if (!empty($options['messageid'])) {
            $data['messageid'] = $options['messageid'];
        }

        if (isset($options['documents'])) {
            $data['documents'] = $options['documents'];
        }

        $data['mode'] = Notification_NotificationTemplate::MODE_MAIL;

        $data['attach_documents'] = $options['attach_documents'] ?? $CFG_GLPI['attach_ticket_documents_to_mail'];

        $queue = new QueuedNotification();

        if (!$queue->add($data)) {
            Session::addMessageAfterRedirect(__s('Error inserting email to queue'), true, ERROR);
            return false;
        } else {
            //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
            Toolbox::logInFile(
                "mail",
                sprintf(
                    __('%1$s: %2$s'),
                    sprintf(
                        __('An email to %s was added to queue'),
                        $options['to']
                    ),
                    $options['subject'] . "\n"
                )
            );

            $itemtype = (string) $queue->fields['itemtype'];
            $event    = (string) $queue->fields['event'];
            if (NotificationTarget::shouldNotificationBeSentImmediately($itemtype, $event)) {
                NotificationEventMailing::send([$queue->fields]);
            }
        }

        return true;
    }
}
