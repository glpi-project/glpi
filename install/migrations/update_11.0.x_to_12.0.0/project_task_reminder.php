<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

$migration->addField('glpi_projecttasks', 'recall', 'integer', ['nodefault' => true, 'null' => true]);

if (!countElementsInTable('glpi_crontasks', ['itemtype' => 'ProjectTask', 'name' => 'projecttasksreminder'])) {
    $DB->insert(
        'glpi_crontasks',
        [
            'itemtype'      => 'ProjectTask',
            'name'          => 'projecttasksreminder',
            'frequency'     => HOUR_TIMESTAMP,
            'param'         => 3,
            'state'         => CronTask::STATE_WAITING,
            'mode'          => CronTask::MODE_INTERNAL,
            'allowmode'     => CronTask::MODE_INTERNAL | CronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'lastrun'       => null,
            'comment'       => '',
            'hourmin'       => 0,
            'hourmax'       => 24,
        ]
    );
}

if (!countElementsInTable('glpi_notifications', ['itemtype' => 'ProjectTask', 'event' => 'planningrecall'])) {
    $DB->insert(
        'glpi_notifications',
        [
            'name'          => 'Project Task Planning Reminder',
            'entities_id'   => 0,
            'itemtype'      => 'ProjectTask',
            'event'         => 'planningrecall',
            'comment'       => '',
            'is_recursive'  => 1,
            'is_active'     => 1,
            'date_creation' => new QueryExpression('NOW()'),
            'date_mod'      => new QueryExpression('NOW()'),
        ]
    );
    $notifications_id = $DB->insertId();

    $template = $DB->request([
        'SELECT' => ['id'],
        'FROM'   => 'glpi_notificationtemplates',
        'WHERE'  => ['itemtype' => 'ProjectTask'],
        'LIMIT'  => 1,
    ])->current();

    if ($template) {
        $DB->insert(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notifications_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $template['id'],
            ]
        );
    }

    foreach ([Notification::TEAM_USER, Notification::AUTHOR, Notification::TEAM_GROUP] as $items_id) {
        $DB->insert(
            'glpi_notificationtargets',
            [
                'items_id'         => $items_id,
                'type'             => Notification::USER_TYPE,
                'notifications_id' => $notifications_id,
            ]
        );
    }
}
