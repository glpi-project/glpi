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

namespace tests\units\Glpi\Api\HL\Doc;

use Glpi\Api\HL\Doc;
use GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaTest extends GLPITestCase
{
    public static function schemaArrayProvider()
    {
        return [
            [
                'schema' => new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING
                ),
                'array' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_STRING,
                ],
            ],
            [
                'schema' => new Doc\Schema(
                    type: Doc\Schema::TYPE_STRING,
                    enum: ['a', 'b', 'c'],
                ),
                'array' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_STRING,
                    'enum' => ['a', 'b', 'c'],
                ],
            ],
        ];
    }

    #[DataProvider('schemaArrayProvider')]
    public function testToArray($schema, $array)
    {
        $this->assertEquals($array, $schema->toArray());
    }

    #[DataProvider('schemaArrayProvider')]
    public function testFromArray($schema, $array)
    {
        $this->assertEquals($schema, Doc\Schema::fromArray($array));
    }
}
