<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Change;
use Group_Item;
use Problem;
use Ticket;

class GroupTechITILFilter extends AbstractGroupFilter
{
    public static function getName(): string
    {
        return __("Assigned group");
    }

    public static function getId(): string
    {
        return "group_tech_itil";
    }

    protected static function getGroupType(): int
    {
        return Group_Item::GROUP_TYPE_TECH;
    }

    protected static function getGroupFieldName(): string
    {
        return 'groups_id_tech';
    }

    protected static function getITILSearchOptionID(): int
    {
        return 8;
    }

    public static function canBeApplied(string $table): bool
    {
        return in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true);
    }
}

