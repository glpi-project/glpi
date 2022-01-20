<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Features;

/**
 * Trait for itemtypes that can have a team
 * @since 10.0.0
 */
trait Teamwork
{
    /**
     * Get an array of all possible roles
     * @return array
     */
    abstract public static function getTeamRoles(): array;

    /**
     * Get the localized name for a team role
     * @param int $role
     * @param int $nb
     * @return string
     */
    abstract public static function getTeamRoleName(int $role, int $nb = 1): string;

    /**
     * Get all types of team members that are supported by this item type
     * @return array
     */
    abstract public static function getTeamItemtypes(): array;

    /**
     * Add a team member to this item
     * @param string $itemtype
     * @param int $items_id
     * @param array $params
     * @return bool
     * @since 10.0.0
     */
    abstract public function addTeamMember(string $itemtype, int $items_id, array $params = []): bool;

    /**
     * Remove a team member to this item
     * @param string $itemtype
     * @param int $items_id
     * @param array $params
     * @return bool
     * @since 10.0.0
     */
    abstract public function deleteTeamMember(string $itemtype, int $items_id, array $params = []): bool;

    /**
     * Get all team members
     * @return array
     * @since 10.0.0
     */
    abstract public function getTeam(): array;
}
