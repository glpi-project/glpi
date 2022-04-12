<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

/** User mention notification */
$notification_exists = countElementsInTable('glpi_notifications', ['itemtype' => 'Ticket', 'event' => 'user_mention']) > 0;
if (!$notification_exists) {
    $DB->insertOrDie(
        'glpi_notifications',
        [
            'id'              => null,
            'name'            => 'New user mentionned',
            'entities_id'     => 0,
            'itemtype'        => 'Ticket',
            'event'           => 'user_mention',
            'comment'         => '',
            'is_recursive'    => 1,
            'is_active'       => 1,
            'date_creation'   => new \QueryExpression('NOW()'),
            'date_mod'        => new \QueryExpression('NOW()')
        ],
        '10.0 Add user mention notification'
    );
    $notification_id = $DB->insertId();

    $notificationtemplate = new NotificationTemplate();
    if ($notificationtemplate->getFromDBByCrit(['name' => 'Tickets', 'itemtype' => 'Ticket'])) {
        $DB->insertOrDie(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notification_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $notificationtemplate->fields['id'],
            ],
            '10.0 Add user mention notification template'
        );
    }

    $DB->insertOrDie(
        'glpi_notificationtargets',
        [
            'items_id'         => '39',
            'type'             => '1',
            'notifications_id' => $notification_id,
        ],
        '10.0 Add user mention notification target'
    );
}
/** /User mention notification */

/** Fix non encoded notifications */
$notifications = getAllDataFromTable('glpi_notificationtemplatetranslations');
foreach ($notifications as $notification) {
    if ($notification['content_html'] !== null && preg_match('/(<|>|(&(?!#?[a-z0-9]+;)))/i', $notification['content_html']) === 1) {
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_notificationtemplatetranslations',
                [
                    'content_html' => Sanitizer::sanitize($notification['content_html']),
                ],
                [
                    'id' => $notification['id'],
                ]
            )
        );
    }
}
/** Fix non encoded notifications */
