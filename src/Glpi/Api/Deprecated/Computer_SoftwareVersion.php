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
 * @since 9.5
 */
class Computer_SoftwareVersion implements DeprecatedInterface
{
    use CommonDeprecatedTrait;

    public function getType(): string
    {
        return "Item_SoftwareVersion";
    }

    public function mapCurrentToDeprecatedHateoas(array $hateoas): array
    {
        $hateoas = $this->replaceCurrentHateoasRefByDeprecated($hateoas);
        return $hateoas;
    }

    public function mapDeprecatedToCurrentFields(object $fields): object
    {
        $this
         ->renameField($fields, "computers_id", "items_id")
         ->addField($fields, "itemtype", "Computer")
         ->renameField($fields, "is_template_computer", "is_template_item")
         ->renameField($fields, "is_deleted_computer", "is_deleted_item");

        return $fields;
    }

    public function mapCurrentToDeprecatedFields(array $fields): array
    {
        $this
         ->renameField($fields, "items_id", "computers_id")
         ->deleteField($fields, "itemtype")
         ->renameField($fields, "is_template_item", "is_template_computer")
         ->renameField($fields, "is_deleted_item", "is_deleted_computer");

        return $fields;
    }

    public function mapDeprecatedToCurrentCriteria(array $criteria): array
    {
        $criteria[] = [
            "link"       => 'AND',
            "field"      => "5",
            "searchtype" => 'equals',
            "value"      => "Computer",
        ];

        return $criteria;
    }

    public function mapCurrentToDeprecatedSearchOptions(array $soptions): array
    {
        $this
         ->updateSearchOptionsUids($soptions)
         ->updateSearchOptionsTables($soptions)
         ->alterSearchOption($soptions, "3", [
             'name'                  => "Computer",
             'table'                 => "glpi_computers",
             'field'                 => "name",
             'datatype'              => "dropdown",
             'uid'                   => "Computer_SoftwareVersion.Computer.name",
             'available_searchtypes' => [
                 "contains",
                 "notcontains",
                 "equals",
                 "notequals",
             ],
         ])
         ->deleteSearchOption($soptions, "5");

        return $soptions;
    }
}
