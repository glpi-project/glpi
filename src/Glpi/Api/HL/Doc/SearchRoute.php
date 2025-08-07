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

namespace Glpi\Api\HL\Doc;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class SearchRoute extends Route
{
    public function __construct(?string $schema_name = null, ?string $description = null)
    {
        $responses = $schema_name ? [new Response(new SchemaReference($schema_name . '[]'))] : [];
        $description ??= $schema_name ? 'List or search for ' . ucfirst(getPlural($schema_name)) : 'List or search for items';
        parent::__construct(
            description: $description,
            parameters: [
                new Parameter(
                    name: 'filter',
                    schema: new Schema(Schema::TYPE_STRING),
                    description: 'RSQL query string'
                ),
                new Parameter(
                    name: 'start',
                    schema: new Schema(
                        type: Schema::TYPE_INTEGER,
                        format: Schema::FORMAT_INTEGER_INT64,
                        default: 0,
                        extra_data: [
                            'minimum' => 0,
                        ]
                    ),
                    description: 'The first item to return'
                ),
                new Parameter(
                    name: 'limit',
                    schema: new Schema(
                        type: Schema::TYPE_INTEGER,
                        format: Schema::FORMAT_INTEGER_INT64,
                        default: 100,
                        extra_data: [
                            'minimum' => 0,
                        ]
                    ),
                    description: 'The maximum number of items to return'
                ),
                new Parameter(
                    name: 'sort',
                    schema: new Schema(Schema::TYPE_STRING),
                    description: 'One or more properties to sort by in the form of property:direction where property is the full property name in dot notation and direction is either asc or desc.
                                  If no direction is provided, asc is assumed. Multiple sorts can be provided by separating them with a comma.',
                ),
            ],
            responses: $responses
        );
    }
}
