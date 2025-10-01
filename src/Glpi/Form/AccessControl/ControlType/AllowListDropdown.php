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

namespace Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use Glpi\DBAL\QuerySubQuery;
use Group;
use Group_User;
use Override;
use Profile;
use Profile_User;
use User;

final class AllowListDropdown extends AbstractRightsDropdown
{
    #[Override]
    protected static function addAllUsersOption(): true
    {
        return true;
    }

    /**
     * Count users for the given criteria.
     *
     * @param array $users
     * @param array $groups
     *
     * @return array ['count' => int, 'link' => string]
     */
    public static function countUsersForCriteria(
        array $users,
        array $groups,
        array $profiles,
    ): array {
        return [
            'count' => self::countUsers($users, $groups, $profiles),
            'link'  => self::computeSearchResultLink($users, $groups, $profiles),
        ];
    }

    #[Override]
    protected static function getAjaxUrl(): string
    {
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . "/ajax/form/allowListDropdownValue.php";
    }

    #[Override]
    protected static function getTypes(array $options = []): array
    {
        return [
            User::getType(),
            Profile::getType(),
            Group::getType(),
        ];
    }

    protected static function countUsers(
        array $users,
        array $groups,
        array $profiles,
    ): int {
        global $CFG_GLPI;

        $condition = [
            'is_deleted' => 0,
            'id' => ['<>', $CFG_GLPI['system_user']],
        ];

        $all_users_are_allowed = in_array(AbstractRightsDropdown::ALL_USERS, $users);
        if (!$all_users_are_allowed) {
            $condition['OR'] = [];

            // Filter by user
            if ($users !== []) {
                $condition['OR'][] = ['id' => array_values($users)];
            }

            // Filter by group
            if ($groups !== []) {
                $groups_with_parents = [];
                foreach ($groups as $group) {
                    $groups_with_parents[] = $group;
                    $parents = getSonsOf(Group::getTable(), $group);
                    array_push($groups_with_parents, ...$parents);
                }

                $groups_with_parents = array_unique($groups_with_parents);
                $condition['OR'][] = [
                    'id' => new QuerySubQuery([
                        'SELECT' => 'users_id',
                        'FROM'   => Group_User::getTable(),
                        'WHERE'  => ['groups_id' => array_values($groups_with_parents)],
                    ]),
                ];
            }

            // Filter by profile
            if ($profiles !== []) {
                $condition['OR'][] = [
                    'id' => new QuerySubQuery([
                        'SELECT' => 'users_id',
                        'FROM'   => Profile_User::getTable(),
                        'WHERE'  => ['profiles_id' => array_values($profiles)],
                    ]),
                ];
            }

            // Empty condition must find 0 users
            if (empty($condition['OR'])) {
                $condition['OR'][] = ['id' => -1];
            }
        }

        return countElementsInTable(User::getTable(), $condition);
    }

    protected static function computeSearchResultLink(
        array $users,
        array $groups,
        array $profiles,
    ): string {
        global $CFG_GLPI;

        $criteria = [];
        $all_users_are_allowed = in_array(AbstractRightsDropdown::ALL_USERS, $users);

        if (!$all_users_are_allowed) {
            $user_filters = [];

            foreach ($users as $user_id) {
                $user_filters[] = [
                    'link'       => 'OR',
                    'searchtype' => 'equals',
                    'field'      => 1, // ID
                    'value'      => $user_id,
                ];
            }

            foreach ($groups as $group_id) {
                $user_filters[] = [
                    'link'       => 'OR',
                    'searchtype' => 'under',
                    'field'      => 13, // Linked group,
                    'value'      => $group_id,
                ];
            }

            foreach ($profiles as $profile_id) {
                $user_filters[] = [
                    'link'       => 'OR',
                    'searchtype' => 'equals',
                    'field'      => 20, // Profile
                    'value'      => $profile_id,
                ];
            }

            // Group criteria into a block to ease the addition of new criteria.
            $criteria[] = [
                'link' => 'AND',
                'criteria' => $user_filters,
            ];
        }

        // Exclude system user
        $criteria[] = [
            'link'       => 'AND',
            'searchtype' => 'notequals',
            'field'      => '1',
            'value'      => $CFG_GLPI['system_user'],
        ];

        $params = ['criteria' => $criteria];
        return User::getSearchURL() . "?" . http_build_query($params);
    }
}
