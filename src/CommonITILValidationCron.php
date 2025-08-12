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

use Glpi\DBAL\QueryFunction;

/**
 * Cron task for approval reminder
 *
 * @since 11.0.0
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
            'description' => __("Alerts on approval which are waiting"),
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
        global $CFG_GLPI, $DB;

        $cron_status = 1;

        if ($CFG_GLPI["use_notifications"]) {
            // Concrete classes for which approval reminders can be created
            $targets = [
                TicketValidation::class,
                ChangeValidation::class,
            ];

            foreach ($targets as $target) {
                $validation = new $target();
                $itemtype = $validation->getItilObjectItemType();
                foreach (Entity::getEntitiesToNotify('approval_reminder_repeat_interval') as $entity => $repeat) {
                    $iterator = $DB->request([
                        'SELECT' => 'validation.*',
                        'FROM'   => $validation->getTable() . ' AS validation',
                        'JOIN'   => [
                            $itemtype::getTable() => [
                                'ON' => [
                                    $itemtype::getTable() => 'id',
                                    'validation' => $itemtype::getForeignKeyField(),
                                ],
                            ],
                        ],
                        'WHERE'  => [
                            'validation.status'          => CommonITILValidation::WAITING,
                            'validation.entities_id'     => $entity,
                            'validation.submission_date' => ['<',
                                QueryFunction::dateSub(
                                    date: QueryFunction::now(),
                                    interval: $repeat,
                                    interval_unit: 'SECOND'
                                ),
                            ],
                            'OR'              => [
                                ['validation.last_reminder_date' => null],
                                [
                                    'validation.last_reminder_date' => ['<',
                                        QueryFunction::dateSub(
                                            date: QueryFunction::now(),
                                            interval: $repeat,
                                            interval_unit: 'SECOND'
                                        ),
                                    ],
                                ],
                            ],
                            $itemtype::getOpenCriteria(),
                        ],
                    ]);

                    foreach ($iterator as $data) {
                        $validation->getFromDB($data['id']);
                        $options = [
                            'validation_id'     => $validation->fields["id"],
                            'validation_status' => $validation->fields["status"],
                        ];
                        $item = $validation->getItem();
                        if (NotificationEvent::raiseEvent('validation_reminder', $item, $options, $validation)) {
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
