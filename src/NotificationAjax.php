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

use Glpi\Toolbox\Sanitizer;

/**
 *  NotificationAjax
 **/
class NotificationAjax implements NotificationInterface
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
       //waiting for a user ID
        $value = (int)$value;
        return $value > 0;
    }

    public static function testNotification()
    {
        $instance = new self();
        return $instance->sendNotification([
            '_itemtype'                   => 'NotificationAjax',
            '_items_id'                   => 1,
            '_notificationtemplates_id'   => 0,
            '_entities_id'                => 0,
            'fromname'                    => 'TEST',
            'subject'                     => 'Test notification',
            'content_text'                => "Hello, this is a test notification.",
            'to'                          => Session::getLoginUserID(),
            'event'                       => 'test_notification'
        ]);
    }


    public function sendNotification($options = [])
    {

        $data = [];
        $data['itemtype']                             = $options['_itemtype'];
        $data['items_id']                             = $options['_items_id'];
        $data['notificationtemplates_id']             = $options['_notificationtemplates_id'];
        $data['entities_id']                          = $options['_entities_id'];

        $data['sendername']                           = $options['fromname'];

        $data['name']                                 = $options['subject'];
        $data['body_text']                            = $options['content_text'];
        $data['recipient']                            = $options['to'];

        $data['event'] = $options['event'] ?? null; // `event` has been added in GLPI 10.0.7

        $data['mode'] = Notification_NotificationTemplate::MODE_AJAX;

        $queue = new QueuedNotification();

        if (!$queue->add(Sanitizer::sanitize($data))) {
            Session::addMessageAfterRedirect(__('Error inserting browser notification to queue'), true, ERROR);
            return false;
        } else {
           //TRANS to be written in logs %1$s is the to email / %2$s is the subject of the mail
            Toolbox::logInFile(
                "notification",
                sprintf(
                    __('%1$s: %2$s'),
                    sprintf(
                        __('A browser notification to %s was added to queue'),
                        $options['to']
                    ),
                    $options['subject'] . "\n"
                )
            );
        }

        return true;
    }

    /**
     * Get users own notifications
     *
     * @return array|false
     */
    public static function getMyNotifications()
    {
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
                foreach ($iterator as $row) {
                    $url = null;
                    if (is_a($row['itemtype'], CommonGLPI::class, true)) {
                        $item = new $row['itemtype']();
                        $url = $item->getFormURLWithID($row['items_id'], true);
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
    public static function raisedNotification($id)
    {
        global $DB;

        $now = date('Y-m-d H:i:s');
        $DB->update(
            'glpi_queuednotifications',
            [
                'sent_time'    => $now,
                'is_deleted'   => 1
            ],
            [
                'id'        => $id,
                'recipient' => Session::getLoginUserID()
            ]
        );
    }
}
