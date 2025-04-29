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

namespace Glpi\Api\Deprecated;

/**
 * @since 9.4.0
 */
class TicketFollowup implements DeprecatedInterface
{
    use CommonDeprecatedTrait;

    public function getType(): string
    {
        return "ITILFollowup";
    }

    public function mapCurrentToDeprecatedHateoas(array $hateoas): array
    {
        $hateoas = $this->replaceCurrentHateoasRefByDeprecated($hateoas);
        return $hateoas;
    }

    public function mapDeprecatedToCurrentFields(object $fields): object
    {
        $this
         ->renameField($fields, "tickets_id", "items_id")
         ->addField($fields, "itemtype", "Ticket");

        return $fields;
    }

    public function mapCurrentToDeprecatedFields(array $fields): array
    {
        $this
         ->renameField($fields, "items_id", "tickets_id")
         ->deleteField($fields, "itemtype")
         ->deleteField($fields, "sourceitems_id")
         ->deleteField($fields, "sourceof_items_id");

        return $fields;
    }

    public function mapDeprecatedToCurrentCriteria(array $criteria): array
    {
        // Add itemtype condition
        $criteria[] = [
            "link"       => 'AND',
            "field"      => "6",
            "searchtype" => 'equals',
            "value"      => "Ticket",
        ];

        return $criteria;
    }

    public function mapCurrentToDeprecatedSearchOptions(array $soptions): array
    {
        $this
         ->updateSearchOptionsUids($soptions)
         ->updateSearchOptionsTables($soptions)
         ->alterSearchOption($soptions, "1", [
             "available_searchtypes" => ["contains"],
         ])
         ->alterSearchOption($soptions, "2", [
             "available_searchtypes" => [
                 "contains",
                 "equals",
                 "notequals",
             ],
         ])
         ->alterSearchOption($soptions, "3", [
             "available_searchtypes" => [
                 "equals",
                 "notequals",
                 "lessthan",
                 "morethan",
                 "contains",
             ],
         ])
         ->alterSearchOption($soptions, "4", [
             "available_searchtypes" => [
                 "equals",
                 "notequals",
                 "contains",
             ],
         ])
         ->alterSearchOption($soptions, "5", [
             "available_searchtypes" => [
                 "contains",
                 "equals",
                 "notequals",
             ],
         ])
         ->deleteSearchOption($soptions, "6")
         ->deleteSearchOption($soptions, "119")
         ->deleteSearchOption($soptions, "document");

        return $soptions;
    }
}
