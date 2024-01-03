<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
 * @since 10.0.0
 */
class PendingReasonCron extends CommonDBTM
{
    const TASK_NAME = 'pendingreason_autobump_autosolve';

    /**
     * Get task description
     *
     * @return string
     */
    public static function getTaskDescription(): string
    {
        return __("Send automated follow-ups on pending tickets and solve them if necessary");
    }

    public static function cronInfo($name)
    {
        return [
            'description' => self::getTaskDescription(),
        ];
    }

    /**
     * Run from cronTask
     *
     * @param CronTask $task
     */
    public static function cronPendingreason_autobump_autosolve(CronTask $task)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $config = Config::getConfigurationValues('core', ['system_user']);

        if (empty($config['system_user'])) {
            trigger_error("Missing system_user config", E_USER_WARNING);
            return 0;
        }

        $user = User::getById($config['system_user']);
        if (!$user) {
            trigger_error("Missing system_user user", E_USER_WARNING);
            return 0;
        }

        $targets = [
            Ticket::getType(),
            Change::getType(),
            Problem::getType(),
        ];

        $now = date("Y-m-d H:i:s");

        $data = $DB->request([
            'SELECT' => 'id',
            'FROM'   => PendingReason_Item::getTable(),
            'WHERE'  => [
                'pendingreasons_id'  => ['>', 0],
                'followup_frequency' => ['>', 0],
                'itemtype'           => $targets
            ]
        ]);

        foreach ($data as $row) {
            $pending_item = PendingReason_Item::getById($row['id']);
            $itemtype = $pending_item->fields['itemtype'];
            $item = $itemtype::getById($pending_item->fields['items_id']);
            if (!$item) {
                trigger_error("Failed to load item", E_USER_WARNING);
                continue;
            }

            if ($item->fields['status'] != CommonITILObject::WAITING) {
                $pending_item->delete([
                    'id' => $pending_item->fields['id'],
                ]);
                continue;
            }

            $next_bump = $pending_item->getNextFollowupDate();
            $resolve = $pending_item->getAutoResolvedate();

            if ($next_bump && $now > $next_bump) {
               // Load pending reason
                $pending_reason = PendingReason::getById($pending_item->fields['pendingreasons_id']);
                if (!$pending_reason) {
                    trigger_error("Failed to load PendingReason", E_USER_WARNING);
                    continue;
                }

                $template_id = $pending_reason->fields['itilfollowuptemplates_id'];

                // No template defined; can't bump
                if (!$template_id) {
                    continue;
                }

                // Load followup template
                $fup_template = ITILFollowupTemplate::getById($template_id);
                if (!$fup_template) {
                    trigger_error("Failed to load ITILFollowupTemplate::{$pending_reason->fields['itilfollowuptemplates_id']}", E_USER_WARNING);
                    continue;
                }

                $success = $pending_item->update([
                    'id'             => $pending_item->getID(),
                    'bump_count'     => $pending_item->fields['bump_count'] + 1,
                    'last_bump_date' => date("Y-m-d H:i:s"),
                ]);

                if (!$success) {
                     trigger_error("Can't bump, unable to update pending item", E_USER_WARNING);
                     continue;
                }

               // Add bump (new followup from template)
                $fup = new ITILFollowup();
                $fup->add([
                    'itemtype' => $item::getType(),
                    'items_id' => $item->getID(),
                    'users_id' => $config['system_user'],
                    'content' => Sanitizer::sanitize($fup_template->getRenderedContent($item)),
                    'is_private' => $fup_template->fields['is_private'],
                    'requesttypes_id' => $fup_template->fields['requesttypes_id'],
                    'timeline_position' => CommonITILObject::TIMELINE_RIGHT,
                    '_no_reopen' => 1,
                ]);
                $task->addVolume(1);
            } else if ($resolve && $now > $resolve) {
               // Load pending reason
                $pending_reason = PendingReason::getById($pending_item->fields['pendingreasons_id']);
                if (!$pending_reason) {
                    trigger_error("Failed to load PendingReason", E_USER_WARNING);
                    continue;
                }

               // Load solution template
                $solution_template = SolutionTemplate::getById($pending_reason->fields['solutiontemplates_id']);
                if (!$solution_template) {
                    trigger_error("Failed to load SolutionTemplate::{$pending_reason->fields['solutiontemplates_id']}", E_USER_WARNING);
                    continue;
                }

               // Add solution
                $solution = new ITILSolution();
                $solution->add([
                    'itemtype'         => $item::getType(),
                    'items_id'         => $item->getID(),
                    'solutiontypes_id' => $solution_template->fields['solutiontypes_id'],
                    'content'          => Sanitizer::sanitize($solution_template->getRenderedContent($item)),
                    'users_id'         => $config['system_user'],
                ]);
                $task->addVolume(1);
            }
        }

        return 1;
    }

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Automatic followups / resolution');
    }
}
