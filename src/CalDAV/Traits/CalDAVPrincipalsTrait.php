<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\CalDAV\Traits;

use EmptyIterator;
use Group;
use Iterator;
use Planning;
use Session;
use User;

/**
 * Trait used for CalDAV Principals listing and visibility management.
 *
 * @since 9.5.3
 */
trait CalDAVPrincipalsTrait
{
    /**
     * Check if principal objects are visible for current session.
     *
     * @param string $path
     *
     * @return bool
     */
    protected function canViewPrincipalObjects(string $path): bool
    {
        $principal_type = $this->getPrincipalItemtypeFromUri($path);
        switch ($principal_type) {
            case Group::class:
                $can_view = $this->canViewGroupObjects($this->getGroupIdFromPrincipalUri($path));
                break;
            case User::class:
                $can_view = $this->canViewUserObjects($this->getUsernameFromPrincipalUri($path));
                break;
            default:
                $can_view = false;
                break;
        }

        return $can_view;
    }

    /**
     * Check if group objects are visible for current session.
     *
     * @param int $group_id
     *
     * @return bool
     */
    protected function canViewGroupObjects(int $group_id): bool
    {
        $groups_iterator = $this->getVisibleGroupsIterator();
        foreach ($groups_iterator as $group) {
            if ($group['id'] === $group_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user objects are visible for current session.
     *
     * @param string $username
     *
     * @return bool
     */
    protected function canViewUserObjects(string $username): bool
    {
        $users_iterator = $this->getVisibleUsersIterator();
        foreach ($users_iterator as $user) {
            if ($user['name'] === $username) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get visible groups for current session.
     *
     * @return array
     */
    protected function getVisibleGroupsIterator(): Iterator
    {

        global $DB;

        if (
            !Session::haveRight(Planning::$rightname, Planning::READALL)
            && empty($_SESSION['glpigroups'])
        ) {
           // User cannot read planning of everyone and has no groups.
            return new EmptyIterator();
        }

        $groups_criteria = getEntitiesRestrictCriteria(
            Group::getTable(),
            'entities_id',
            $_SESSION['glpiactiveentities'],
            true
        );

       // Limit to groups visible in planning (see Planning::showAddGroupForm())
        $groups_criteria['is_task'] = 1;

       // Limit to users groups if user cannot read planning of everyone
        if (!Session::haveRight(Planning::$rightname, Planning::READALL)) {
            $groups_criteria['id'] = $_SESSION['glpigroups'];
        }

        $groups_iterator = $DB->request(
            [
                'FROM'  => Group::getTable(),
                'WHERE' => $groups_criteria,
            ]
        );

        return $groups_iterator;
    }

    /**
     * Get visible users for current session.
     *
     * @return array
     */
    protected function getVisibleUsersIterator(): Iterator
    {

        if (!Session::haveRightsOr(Planning::$rightname, [Planning::READALL, Planning::READGROUP])) {
           // Can see only personnal planning
            $rights = 'id';
        } else if (
            Session::haveRight(Planning::$rightname, Planning::READGROUP)
            && !Session::haveRight(Planning::$rightname, Planning::READALL)
        ) {
           // Can see only planning from users sharing same groups
            $rights = 'groups';
        } else {
           // Can see planning from users having rights on planning elements
            $rights = ['change', 'problem', 'reminder', 'task', 'projecttask'];
        }
        return User::getSqlSearchResult(false, $rights);
    }
}
