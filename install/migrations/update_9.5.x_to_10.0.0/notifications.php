<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\DBAL\QueryExpression;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

/** User mention notification */
$notification_exists = countElementsInTable('glpi_notifications', ['itemtype' => 'Ticket', 'event' => 'user_mention']) > 0;
if (!$notification_exists) {
    $DB->insert(
        'glpi_notifications',
        [
            'id'              => null,
            'name'            => 'New user mentioned',
            'entities_id'     => 0,
            'itemtype'        => 'Ticket',
            'event'           => 'user_mention',
            'comment'         => '',
            'is_recursive'    => 1,
            'is_active'       => 1,
            'date_creation'   => new QueryExpression('NOW()'),
            'date_mod'        => new QueryExpression('NOW()'),
        ]
    );
    $notification_id = $DB->insertId();

    $notificationtemplate = new NotificationTemplate();
    if ($notificationtemplate->getFromDBByCrit(['name' => 'Tickets', 'itemtype' => 'Ticket'])) {
        $DB->insert(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notification_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $notificationtemplate->fields['id'],
            ]
        );
    }

    $DB->insert(
        'glpi_notificationtargets',
        [
            'items_id'         => '39',
            'type'             => '1',
            'notifications_id' => $notification_id,
        ]
    );
}
/** /User mention notification */
