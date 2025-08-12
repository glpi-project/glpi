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

namespace Glpi\Security;

use Profile;
use Profile_User;

/**
 * Check permission information for a user, including users other than the currently logged in one.
 */
final class PermissionManager
{
    public static function getInstance(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public function getAllEntities(int $users_id): array
    {
        global $DB;

        $profile_table = Profile::getTable();
        $iterator = $DB->request([
            'SELECT' => ['entities_id', 'is_recursive'],
            'FROM' => Profile_User::getTable(),
            'LEFT JOIN' => [
                $profile_table => [
                    'ON'    => [
                        $profile_table => 'id',
                        Profile_User::getTable() => 'profiles_id',
                    ],
                ],
            ],
            'WHERE' => [
                Profile_User::getTableField('users_id') => $users_id,
            ],
        ]);
        $entities = [];
        foreach ($iterator as $row) {
            $entities[] = [$row['entities_id']];
            if ($row['is_recursive']) {
                $entities[] = getSonsOf('glpi_entities', $row['entities_id']);
            }
        }

        // Avoid running array_merge in a loop by storing multiple arrays into $entities
        $entities = array_merge(...$entities);

        return array_unique($entities);
    }
}
