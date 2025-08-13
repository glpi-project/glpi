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
 * Cron task creating recurrent tickets and changes
 *
 * @since 10.0.0
 */
class CommonITILRecurrentCron extends CommonDBTM
{
    /**
     * Get cron task's description
     *
     * @return array
     */
    public static function cronInfo(): array
    {
        return [
            'description' => __("Create recurrent tickets and changes"),
        ];
    }

    /**
     * Run the cron task
     *
     * @param CronTask $task
     *
     * @return int task status (0: no work to do, 1: work done)
     */
    public static function cronRecurrentItems(CronTask $task)
    {
        global $DB;

        $total = 0;

        /**
         * Concrete classes for which recurrent items can be created
         * @var array<class-string<CommonITILRecurrent>>
         */
        $targets = [
            TicketRecurrent::class,
            RecurrentChange::class,
        ];

        foreach ($targets as $itemtype) {
            $iterator = $DB->request([
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    'next_creation_date' => ['<', QueryFunction::now()],
                    'is_active'          => 1,
                    'OR'                 => [
                        ['end_date' => null],
                        ['end_date' => ['>', QueryFunction::now()]],
                    ],
                ],
            ]);

            foreach ($iterator as $data) {
                $item = getItemForItemtype($itemtype);
                $item->fields = $data;
                // get items
                $related_items = $item->getRelatedElements();

                if ($item->fields['ticket_per_item']) {
                    foreach ($related_items as $related_itemtype => $related_items_ids) {
                        foreach ($related_items_ids as $related_item_id) {
                            if ($item->createItem([$related_itemtype => [$related_item_id]])) {
                                $total++;
                            } else {
                                //TRANS: %s is a name
                                $task->log(
                                    sprintf(
                                        __('Failed to create recurrent item %s'),
                                        $data['name']
                                    )
                                );
                            }
                        }
                    }
                } else {
                    if ($item->createItem($related_items)) {
                        $total++;
                    } else {
                        //TRANS: %s is a name
                        $task->log(
                            sprintf(
                                __('Failed to create recurrent item %s'),
                                $data['name']
                            )
                        );
                    }
                }
            }
        }

        $task->setVolume($total);
        return (int) ($total > 0);
    }
}
