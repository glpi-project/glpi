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

class SolutionTemplate_User extends CommonDBRelation
{
    public bool $auto_message_on_action = false;
    public static ?string $itemtype_1          = SolutionTemplate::class;
    public static ?string $items_id_1          = 'solutiontemplates_id';
    public static ?string $itemtype_2          = User::class;
    public static ?string $items_id_2          = 'users_id';
    public static int $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static bool $logs_for_item_2     = false;

    /**
     * @param SolutionTemplate $solutionTemplate SolutionTemplate instance
     *
     * @return array<int, mixed>
     **/
    public static function getUsers(SolutionTemplate $solutionTemplate): array
    {
        /** @var DBmysql $DB */
        global $DB;
        $results   = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                self::$items_id_1 => $solutionTemplate->getID(),
            ],
        ]);
        foreach ($iterator as $data) {
            $results[$data[self::$items_id_2]][] = $data;
        }
        return $results;
    }
}
