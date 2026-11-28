<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * Message added to the timeline of an ITIL object and not yet read by a user.
 *
 * The parent ITIL object (`parent_itemtype`, `parent_items_id`) can be reached
 * through the timeline item; it is stored here to avoid joins when filtering.
 */
class ITILUnreadMessage extends CommonDBChild
{
    public static string $itemtype = 'itemtype';
    public static string $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Unread message', 'Unread messages', $nb);
    }

    /**
     * Timeline item types that create unread messages.
     *
     * @return class-string<CommonDBTM>[]
     */
    public static function getSupportedTypes(): array
    {
        return [
            ITILFollowup::class,
            TicketTask::class,
            ProblemTask::class,
            ChangeTask::class,
            Document_Item::class,
            ITILSolution::class,
            ChangeValidation::class,
            TicketValidation::class,
        ];
    }

    /**
     * @return array{description?: string, parameter?: string}
     */
    public static function cronInfo(string $name): array
    {
        return ['description' => __("Delete old unread messages")];
    }

    public static function cronCleanOldMessages(CronTask $task): int
    {
        global $DB;

        $DB->delete(self::getTable(), [
            'is_read' => 1,
            'date' => ['<', QueryFunction::dateSub(QueryFunction::now(), 1, 'MONTH')],
        ]);
        $total = $DB->getAffectedRows();

        $DB->delete(self::getTable(), [
            'date' => ['<', QueryFunction::dateSub(QueryFunction::now(), 3, 'MONTH')],
        ]);
        $total += $DB->getAffectedRows();
        $task->addVolume($total);
        return 1;
    }

    /**
     * Delete unread messages linked to a purged item.
     */
    public static function cleanForItem(CommonDBTM $item): void
    {
        global $DB;

        if ($item instanceof User) {
            $DB->delete(self::getTable(), [
                'users_id' => $item->getID(),
            ]);
        } elseif ($item instanceof CommonITILObject) {
            $DB->delete(self::getTable(), [
                'parent_itemtype' => $item::class,
                'parent_items_id' => $item->getID(),
            ]);
        } elseif (in_array($item::class, self::getSupportedTypes(), true)) {
            $DB->delete(self::getTable(), [
                'itemtype' => $item::class,
                'items_id' => $item->getID(),
            ]);
        }
    }
}
