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

use Glpi\Api\HL\Doc\Schema;
use Glpi\Api\HL\Search\PolymorphicJoin;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\GLPITestCase;
use RuntimeException;

class PolymorphicJoinTest extends GLPITestCase
{
    private static function getJoin(): array
    {
        return [
            'fkey' => 'id',
            'discriminator' => '_type',
            'branches' => [
                'FirstMember' => [
                    'table' => 'glpi_first_links',
                    'field' => 'parents_id',
                    'joins' => [
                        'INNER JOIN' => [
                            'glpi_firsts AS member' => [
                                'ON' => ['member' => 'id', 'branch' => 'firsts_id'],
                            ],
                        ],
                    ],
                    'columns' => [
                        'id' => 'branch.id',
                        'firsts_id' => 'branch.firsts_id',
                        'first_name' => 'member.name',
                    ],
                    'properties' => ['_type', 'id', 'first'],
                ],
                'SecondMember' => [
                    'table' => 'glpi_second_links',
                    'field' => 'parents_id',
                    'columns' => [
                        'id' => 'branch.id',
                        'seconds_id' => new QueryExpression('42'),
                    ],
                    'properties' => ['_type', 'id', 'second'],
                ],
            ],
        ];
    }

    public function testIsPolymorphicJoin(): void
    {
        $this->assertTrue(PolymorphicJoin::isPolymorphicJoin(self::getJoin()));
        $this->assertFalse(PolymorphicJoin::isPolymorphicJoin(['table' => 'glpi_users', 'fkey' => 'users_id']));
    }

    public function testGetColumns(): void
    {
        $this->assertEquals(['id', 'firsts_id', 'first_name', 'seconds_id'], PolymorphicJoin::getColumns(self::getJoin()));
    }

    public function testMissingDiscriminator(): void
    {
        $join = self::getJoin();
        unset($join['discriminator']);
        $this->expectException(RuntimeException::class);
        PolymorphicJoin::build($join);
    }

    public function testBuild(): void
    {
        $union = PolymorphicJoin::build(self::getJoin(), alias: 'members');
        $queries = $union->getQueries();
        $this->assertCount(2, $queries);

        // Each branch selects all columns, using NULL for the ones it doesn't provide
        $first = $queries[0]->getQuery();
        $this->assertStringContainsString("'FirstMember' AS `_type`", $first);
        $this->assertStringContainsString('`branch`.`parents_id` AS `_parent_id`', $first);
        $this->assertStringContainsString('`branch`.`id` AS `id`', $first);
        $this->assertStringContainsString('`branch`.`firsts_id` AS `firsts_id`', $first);
        $this->assertStringContainsString('`member`.`name` AS `first_name`', $first);
        $this->assertStringContainsString('NULL AS `seconds_id`', $first);
        $this->assertStringContainsString('FROM `glpi_first_links` AS `branch`', $first);
        $this->assertStringContainsString('INNER JOIN `glpi_firsts` AS `member` ON (`member`.`id` = `branch`.`firsts_id`)', $first);
        $this->assertStringNotContainsString('WHERE', $first);

        $second = $queries[1]->getQuery();
        $this->assertStringContainsString("'SecondMember' AS `_type`", $second);
        $this->assertStringContainsString('NULL AS `firsts_id`', $second);
        $this->assertStringContainsString('NULL AS `first_name`', $second);
        $this->assertStringContainsString('42 AS `seconds_id`', $second);
        $this->assertStringNotContainsString('JOIN', $second);

        // All branches must select the columns in the same order for the union to be valid
        $fn_get_aliases = static function (string $sql): array {
            preg_match_all('/ AS `([^`]+)`(?:,| FROM)/', $sql, $matches);
            return $matches[1];
        };
        $this->assertEquals(['_type', '_parent_id', 'id', 'firsts_id', 'first_name', 'seconds_id'], $fn_get_aliases($first));
        $this->assertEquals($fn_get_aliases($first), $fn_get_aliases($second));

        $this->assertStringContainsString(' UNION ALL ', $union->getQuery());
        $this->assertStringEndsWith(' AS `members`', $union->getQuery());
    }

    public function testBuildForParents(): void
    {
        $union = PolymorphicJoin::build(self::getJoin(), [3, 5]);
        foreach ($union->getQueries() as $query) {
            $this->assertStringContainsString('WHERE `branch`.`parents_id` IN', $query->getQuery());
        }
        $this->assertEquals([3, 5, 3, 5], $union->getParams());
    }

    public function testGetPropertyPathsByColumn(): void
    {
        $properties = [
            'id' => ['type' => 'integer'],
            'team' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'x-join' => self::getJoin(),
                    'properties' => [
                        '_type' => ['type' => 'string'],
                        'id' => ['type' => 'integer'],
                        'first' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer', 'x-field' => 'firsts_id'],
                                'name' => ['type' => 'string', 'x-field' => 'first_name'],
                            ],
                        ],
                        'second' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'integer', 'x-field' => 'seconds_id'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->assertEquals([
            '_type' => '_type',
            'id' => 'id',
            'firsts_id' => 'first.id',
            'first_name' => 'first.name',
            'seconds_id' => 'second.id',
        ], PolymorphicJoin::getPropertyPathsByColumn('team', Schema::flattenProperties($properties)));
    }

    public function testSchemaJoins(): void
    {
        $properties = [
            'id' => ['type' => 'integer'],
            'team' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'x-join' => ['fkey' => 'id', 'branches' => self::getJoin()['branches']],
                    'discriminator' => ['propertyName' => '_type'],
                    'properties' => [
                        '_type' => ['type' => 'string'],
                        'first' => [
                            'type' => 'object',
                            // Nested joins are ignored as all data comes from the branches
                            'x-join' => ['table' => 'glpi_firsts', 'fkey' => 'firsts_id', 'field' => 'id'],
                            'properties' => [
                                'id' => ['type' => 'integer', 'x-field' => 'firsts_id'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $joins = Schema::getJoins($properties);
        $this->assertEquals(['team'], array_keys($joins));
        $this->assertTrue(PolymorphicJoin::isPolymorphicJoin($joins['team']));
        $this->assertEquals('_type', $joins['team']['discriminator']);
    }

    public function testNestedPolymorphicJoin(): void
    {
        $properties = [
            'parent' => [
                'type' => 'object',
                'x-join' => ['table' => 'glpi_parents', 'fkey' => 'parents_id', 'field' => 'id'],
                'properties' => [
                    'team' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'x-join' => self::getJoin(),
                            'properties' => [],
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(RuntimeException::class);
        Schema::getJoins($properties);
    }
}
