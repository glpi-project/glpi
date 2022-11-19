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

/**
 * Cron task for approval reminder
 *
 * @since 10.1.0
 */
class CommonITILValidationCron extends CommonDBTM
{
    /**
     * Get cron task's description
     *
     * @return array
     */
    public static function cronInfo(): array
    {
        return [
            'description' => __("Alerts on approval which are waiting")
        ];
    }

    /**
     * Run the cron task
     *
     * @param CronTask $task
     *
     * @return int task status (0: no work to do, 1: work done)
     */
    public static function cronApprovalReminder(CronTask $task)
    {
        global $DB, $CFG_GLPI;

        $cron_status = 1;

        if ($CFG_GLPI["use_notifications"]) {
            // Concrete classes for which approval reminders can be created
            $targets = [
                TicketValidation::class,
                ChangeValidation::class,
            ];

            foreach ($targets as $itemtype) {
                $validation = new $itemtype();
                foreach (Entity::getEntitiesToNotify('approval_reminder_repeat_interval') as $entity => $repeat) {
                    $iterator = $DB->request([
                        'FROM'   => $itemtype::getTable(),
                        'WHERE'  => [
                            'status'          => CommonITILValidation::WAITING,
                            'entities_id'     => $entity,
                            'submission_date' => ['<', new QueryExpression(QueryFunction::currentTimestamp() . ' - INTERVAL ' . $repeat . ' second')],
                            'OR'              => [
                                ['last_reminder_date' => null],
                                ['last_reminder_date' => ['<', new QueryExpression(QueryFunction::currentTimestamp() . ' - INTERVAL ' . $repeat . ' second')]],
                            ],
                        ]
                    ]);

                    foreach ($iterator as $data) {
                        $validation->getFromDB($data['id']);
                        $options = [
                            'validation_id'     => $validation->fields["id"],
                            'validation_status' => $validation->fields["status"]
                        ];
                        $item = $validation->getItem();
                        if (NotificationEvent::raiseEvent('validation_reminder', $item, $options)) {
                            $validation->update([
                                'id'            => $validation->getID(),
                                'last_reminder_date' => $_SESSION["glpi_currenttime"],
                            ]);
                            $task->addVolume(1);
                        }
                    }
                }
            }
        }

        return $cron_status;
    }
}
