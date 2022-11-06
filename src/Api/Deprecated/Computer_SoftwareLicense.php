<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
class Computer_SoftwareLicense implements DeprecatedInterface
{
    use CommonDeprecatedTrait;

    public function getType(): string
    {
        return "Item_SoftwareLicense";
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
         ->addField($fields, "itemtype", "Computer");

        return $fields;
    }

    public function mapCurrentToDeprecatedFields(array $fields): array
    {
        $this
         ->renameField($fields, "items_id", "computers_id")
         ->deleteField($fields, "itemtype");

        return $fields;
    }

    public function mapDeprecatedToCurrentCriteria(array $criteria): array
    {
        $criteria[] = [
            "link"       => 'AND',
            "field"      => "6",
            "searchtype" => 'equals',
            "value"      => "Computer"
        ];

        return $criteria;
    }

    public function mapCurrentToDeprecatedSearchOptions(array $soptions): array
    {
        $this
         ->updateSearchOptionsUids($soptions)
         ->updateSearchOptionsTables($soptions)
         ->alterSearchOption($soptions, "5", [
             'name'                  => "Computer",
             'table'                 => "glpi_computers",
             'field'                 => "name",
             'datatype'              => "dropdown",
             'uid'                   => "Computer_SoftwareLicense.Computer.name",
             'available_searchtypes' => [
                 "contains",
                 "notcontains",
                 "equals",
                 "notequals"
             ],
         ])
         ->deleteSearchOption($soptions, "6");

        return $soptions;
    }
}
