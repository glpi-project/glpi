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

namespace Glpi\Tests\Api\Deprecated;

class TicketFollowup implements DeprecatedInterface
{
    public static function getDeprecatedType(): string
    {
        return "TicketFollowup";
    }

    public static function getCurrentType(): string
    {
        return "ITILFollowup";
    }

    public static function getDeprecatedFields(): array
    {
        return [
            "id", "tickets_id", "date", "users_id", "users_id_editor", "content",
            "is_private", "requesttypes_id", "date_mod", "date_creation",
            "timeline_position", "links",
        ];
    }

    public static function getCurrentAddInput(): array
    {
        return [
            "users_id" => getItemByTypeName('User', TU_USER, true),
            "itemtype" => "Ticket",
            "items_id" => getItemByTypeName('Ticket', '_ticket01', true),
            "content"  => "New followup",
        ];
    }

    public static function getDeprecatedAddInput(): array
    {
        return [
            'tickets_id' => getItemByTypeName('Ticket', '_ticket01', true),
            'users_id'   => getItemByTypeName('User', TU_USER, true),
            'content'    => "Test insert deprecated",
        ];
    }

    public static function getDeprecatedUpdateInput(): array
    {
        return [
            'tickets_id' => getItemByTypeName('Ticket', '_ticket02', true),
        ];
    }

    public static function getExpectedAfterInsert(): array
    {
        return [
            "itemtype" => "Ticket",
            "items_id" => getItemByTypeName('Ticket', '_ticket01', true),
        ];
    }

    public static function getExpectedAfterUpdate(): array
    {
        return [
            "itemtype" => "Ticket",
            "items_id" => getItemByTypeName('Ticket', '_ticket02', true),
        ];
    }

    public static function getDeprecatedSearchQuery(): string
    {
        return "forcedisplay[0]=2&rawdata=1";
    }

    public static function getCurrentSearchQuery(): string
    {
        return "forcedisplay[0]=2&criteria[0][field]=6&criteria[0][searchtype]=equals&criteria[0][value]=Ticket&rawdata=1";
    }
}
