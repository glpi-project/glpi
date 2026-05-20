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

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

$cron = new CronTask();
if (!$cron->getFromDBByCrit(['itemtype' => 'ProjectTask', 'name' => 'projecttasksreminder'])) {
    CronTask::register(
        'ProjectTask',
        'projecttasksreminder',
        HOUR_TIMESTAMP,
        [
            'comment' => '',
            'param'   => 3,
            'mode'    => CronTask::MODE_INTERNAL,
        ]
    );
}

$notification = new Notification();
if (!countElementsInTable(Notification::getTable(), ['itemtype' => 'ProjectTask', 'event' => 'planningrecall'])) {
    $notifications_id = $notification->add([
        'name'         => 'Project Task Planning Reminder',
        'itemtype'     => 'ProjectTask',
        'event'        => 'planningrecall',
        'is_recursive' => 1,
        'is_active'    => 1,
    ]);

    if ($notifications_id) {
        $template = new NotificationTemplate();
        $template->getFromDBByCrit(['itemtype' => 'ProjectTask']);
        $notificationtemplates_id = $template->fields['id'] ?? 0;

        if ($notificationtemplates_id) {
            $notif_template = new Notification_NotificationTemplate();
            $notif_template->add([
                'notifications_id'         => $notifications_id,
                'mode'                     => 'mailing',
                'notificationtemplates_id' => $notificationtemplates_id,
            ]);
        }

        $target = new NotificationTarget();
        foreach ([Notification::TEAM_USER, Notification::AUTHOR, Notification::TEAM_GROUP] as $items_id) {
            $target->add([
                'items_id'         => $items_id,
                'type'             => Notification::USER_TYPE,
                'notifications_id' => $notifications_id,
            ]);
        }
    }
}
