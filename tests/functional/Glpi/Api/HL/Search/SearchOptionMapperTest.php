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

namespace tests\units\Glpi\Api\HL\Search;

use Computer;
use Glpi\Api\HL\Search\SearchOptionMapper;
use Glpi\Tests\HLAPITestCase;

class SearchOptionMapperTest extends HLAPITestCase
{
    public function setUp(): void
    {
        parent::setUp();
        SearchOptionMapper::clearCache();
    }

    public function testMainTableProperties(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'comment' => ['type' => 'string'],
                'not_a_column' => ['type' => 'string'],
            ],
        ]);
        $this->assertSame(2, $result['map']['id']);
        $this->assertSame(1, $result['map']['name']);
        $this->assertSame(16, $result['map']['comment']);
        $this->assertArrayNotHasKey('not_a_column', $result['map']);
    }

    public function testDropdownJoinProperties(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'entity' => [
                    'type' => 'object',
                    'x-join' => ['table' => 'glpi_entities', 'fkey' => 'entities_id', 'field' => 'id'],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'completename' => ['type' => 'string'],
                    ],
                ],
            ],
        ]);
        $this->assertSame(80, $result['map']['entity.completename']);
        // The identifier of a dropdown has no search option of its own
        $this->assertArrayNotHasKey('entity.id', $result['map']);
    }

    public function testExplicitOptionOverridesDerivation(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'name' => ['type' => 'string', 'x-search-option' => 4242],
                'comment' => ['type' => 'string', 'x-search-option' => null],
            ],
        ]);
        $this->assertSame(4242, $result['map']['name']);
        // A null override opts the property out of the mapping
        $this->assertArrayNotHasKey('comment', $result['map']);
    }

    public function testMappedPropertyInheritsOption(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'name' => ['type' => 'string'],
                'name_upper' => [
                    'type' => 'string',
                    'x-mapped-from' => 'name',
                    'x-mapper' => static fn($v) => strtoupper($v),
                ],
            ],
        ]);
        $this->assertSame(1, $result['map']['name_upper']);
    }

    public function testComputedAndUnknownSchemasAreNotMapped(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'computed' => ['type' => 'string', 'computation' => 'name'],
            ],
        ]);
        $this->assertSame([], $result['map']);
        $this->assertSame([], SearchOptionMapper::getMap('ThisSchemaDoesNotExist'));
        $this->assertNull(SearchOptionMapper::getOptionForProperty('ThisSchemaDoesNotExist', 'name'));
        $this->assertNull(SearchOptionMapper::getPropertyForOption('ThisSchemaDoesNotExist', 1));
    }

    public function testUnmappedOptionsAreReported(): void
    {
        $this->login();
        $result = SearchOptionMapper::mapSchema([
            'x-itemtype' => Computer::class,
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ]);
        $this->assertArrayNotHasKey(1, $result['unmapped']);
        $this->assertArrayHasKey(2, $result['unmapped']);
    }

    public function testRoundTripForRegisteredSchemas(): void
    {
        $this->login();
        foreach (['Ticket', 'Change', 'Problem', 'Computer'] as $schema_name) {
            $map = SearchOptionMapper::getMap($schema_name);
            $this->assertNotEmpty($map, "No properties mapped for $schema_name");
            foreach ($map as $path => $num) {
                $this->assertSame($num, SearchOptionMapper::getOptionForProperty($schema_name, $path));
                $reverse_path = SearchOptionMapper::getPropertyForOption($schema_name, $num);
                $this->assertNotNull($reverse_path, "Option $num of $schema_name has no property although $path maps to it");
                $this->assertSame($num, SearchOptionMapper::getOptionForProperty($schema_name, $reverse_path));
            }
        }
    }

    public function testWellKnownTicketOptions(): void
    {
        $this->login();
        $this->assertSame(1, SearchOptionMapper::getOptionForProperty('Ticket', 'name'));
        $this->assertSame(2, SearchOptionMapper::getOptionForProperty('Ticket', 'id'));
        $this->assertSame('name', SearchOptionMapper::getPropertyForOption('Ticket', 1));
        // The numeric option may be given as a string
        $this->assertSame('name', SearchOptionMapper::getPropertyForOption('Ticket', '1'));
        // Status is exposed as status.id and status.name. The readable property is preferred
        $this->assertSame('status.name', SearchOptionMapper::getPropertyForOption('Ticket', 12));
    }
}
