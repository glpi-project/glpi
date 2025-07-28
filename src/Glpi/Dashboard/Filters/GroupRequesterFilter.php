<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Dashboard\Filters;

use Group_Item;

class GroupRequesterFilter extends AbstractGroupFilter
{
    public static function getName(): string
    {
        return __("Group / Requester group");
    }

    public static function getId(): string
    {
        return "group_requester";
    }

    protected static function getGroupType(): int
    {
        return Group_Item::GROUP_TYPE_NORMAL;
    }

    protected static function getGroupFieldName(): string
    {
        return 'groups_id';
    }

    protected static function getITILSearchOptionID(): int
    {
        return 71;
    }
}
