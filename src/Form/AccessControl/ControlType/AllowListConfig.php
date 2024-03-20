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

use FreeJsonConfigInterface;

final class AllowListConfig implements FreeJsonConfigInterface
{
    public readonly array $user_ids;
    public readonly array $group_ids;
    public readonly array $profile_ids;

    public function __construct(array $data = [])
    {
        // Allowed users
        $user_ids = [];
        if (isset($data['user_ids']) && is_array($data['user_ids'])) {
            $user_ids = $data['user_ids'];
        }
        $this->user_ids = $user_ids;

        // Allowed groups
        $group_ids = [];
        if (isset($data['group_ids']) && is_array($data['group_ids'])) {
            $group_ids = $data['group_ids'];
        }
        $this->group_ids = $group_ids;

        // Allowed profiles
        $profile_ids = [];
        if (isset($data['profile_ids']) && is_array($data['profile_ids'])) {
            $profile_ids = $data['profile_ids'];
        }
        $this->profile_ids = $profile_ids;
    }
}
