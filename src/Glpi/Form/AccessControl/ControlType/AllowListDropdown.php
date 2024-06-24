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

namespace Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use Group;
use Override;
use Profile;
use Search;
use User;

final class AllowListDropdown extends AbstractRightsDropdown
{
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
        $criteria = [];
        foreach ($users as $user_id) {
            $criteria[] = [
                'link'       => 'OR',
                'searchtype' => 'is',
                'field'      => 2, // ID
                'value'      => $user_id,
            ];
        }

        foreach ($groups as $group_id) {
            $criteria[] = [
                'link'       => 'OR',
                'searchtype' => 'equals',
                'field'      => 13, // Linked group,
                'value'      => $group_id,
            ];
        }

        foreach ($profiles as $profile_id) {
            $criteria[] = [
                'link'       => 'OR',
                'searchtype' => 'equals',
                'field'      => 20, // Profile
                'value'      => $profile_id,
            ];
        }

        if (empty($criteria)) {
            // We must provide a criteria that will return all users, otherwise
            // the request will default to the last search made by the user.
            $criteria[] = [
                'link'       => 'OR',
                'searchtype' => 'contains',
                'field'      => 'view',
                'value'      => '',
            ];
        }

        // Execute search
        $params = ['criteria' => $criteria];
        $search_data = Search::getDatas(User::class, $params);

        return [
            'count' => $search_data['data']['totalcount'],
            'link' => User::getSearchURL() . "?" . http_build_query($params),
        ];
    }

    #[Override]
    protected static function getAjaxUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . "/ajax/form/allowListDropdownValue.php";
    }

    #[Override]
    protected static function getTypes(): array
    {
        return [
            User::getType(),
            Profile::getType(),
            Group::getType(),
        ];
    }
}
