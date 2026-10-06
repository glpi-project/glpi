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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\AdministrationController;
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\Search\CursorPagination;
use Glpi\Tests\DbTestCase;
use Manufacturer;
use PHPUnit\Framework\Attributes\DataProvider;

class ResourceAccessorTest extends DbTestCase
{
    public static function getInputParamsBySchemaProvider()
    {
        $schema_a = [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'comment' => ['type' => 'string'],
                'renamed' => ['type' => 'string', 'x-field' => 'old_name'],
                'status' => [
                    'type' => 'object',
                    'x-join' => [],
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
        return [
            [$schema_a, ['name' => 'Test', 'status' => 4, 'renamed' => 'test', 'extra' => 'not exist'], ['name' => 'Test', 'status' => 4, 'old_name' => 'test']],
            [$schema_a, ['name' => 'Test', 'status' => ['id' => 4]], ['name' => 'Test', 'status' => 4]],
        ];
    }

    #[DataProvider('getInputParamsBySchemaProvider')]
    public function testGetInputParamsBySchema($schema, $request_params, $expected)
    {
        $this->assertEquals($expected, ResourceAccessor::getInputParamsBySchema($schema, $request_params));
    }

    public function testCreateWithInvalidProperties()
    {
        $schema_with_validation = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'maxLength' => 32, 'required' => true],
                'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120, 'required' => true],
                'height' => ['type' => 'number', 'minimum' => 10, 'required' => true],
                'weight' => ['type' => 'number', 'maximum' => 300, 'required' => true],
                'eye_color' => ['type' => 'number', 'required' => true],
            ],
        ];

        $invalid_data = [
            'name' => str_repeat('a', 40), // Exceeds maxLength
            'age' => -5, // Below minimum
            'height' => 5, // Below minimum
            'weight' => 350, // Above maximum
            // Missing required eye_color
        ];

        $response = ResourceAccessor::createBySchema($schema_with_validation, $invalid_data, ['', '']);
        $this->assertEquals(400, $response->getStatusCode());
        $response_data = json_decode((string) $response->getBody(), true);
        $this->assertEquals(AbstractController::ERROR_INVALID_PARAMETER, $response_data['status']);
        $this->assertEquals('Invalid input parameters', $response_data['title']);
        $this->assertArrayHasKey('detail', $response_data);
        $this->assertCount(5, $response_data['detail']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'maxLength',
                'message' => 'This field must be at most 32 characters long',
                'maxLength' => 32,
            ],
        ], $response_data['detail']['name']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'range',
                'message' => 'This field must be between 0 and 120',
                'minimum' => 0,
                'maximum' => 120,
            ],
        ], $response_data['detail']['age']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'minimum',
                'message' => 'This field must be at least 10',
                'minimum' => 10,
            ],
        ], $response_data['detail']['height']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'maximum',
                'message' => 'This field must be at most 300',
                'maximum' => 300,
            ],
        ], $response_data['detail']['weight']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'required',
                'message' => 'This field is required',
            ],
        ], $response_data['detail']['eye_color']);
    }

    public function testUpdateWithInvalidProperties()
    {
        $schema_with_validation = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'maxLength' => 32, 'required' => true],
                'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120, 'required' => true],
                'height' => ['type' => 'number', 'minimum' => 10, 'required' => true],
                'weight' => ['type' => 'number', 'maximum' => 300, 'required' => true],
                'eye_color' => ['type' => 'number', 'required' => true],
            ],
        ];

        $invalid_data = [
            'name' => str_repeat('a', 40), // Exceeds maxLength
            'age' => -5, // Below minimum
            'height' => 5, // Below minimum
            'weight' => 350, // Above maximum
            // Missing required eye_color
        ];

        // unlike create, update will ignore missing required fields as they are expected to be already set in the existing resource
        $response = ResourceAccessor::updateBySchema($schema_with_validation, ['id' => 1], $invalid_data);
        $this->assertEquals(400, $response->getStatusCode());
        $response_data = json_decode((string) $response->getBody(), true);
        $this->assertEquals(AbstractController::ERROR_INVALID_PARAMETER, $response_data['status']);
        $this->assertEquals('Invalid input parameters', $response_data['title']);
        $this->assertArrayHasKey('detail', $response_data);
        $this->assertCount(4, $response_data['detail']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'maxLength',
                'message' => 'This field must be at most 32 characters long',
                'maxLength' => 32,
            ],
        ], $response_data['detail']['name']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'range',
                'message' => 'This field must be between 0 and 120',
                'minimum' => 0,
                'maximum' => 120,
            ],
        ], $response_data['detail']['age']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'minimum',
                'message' => 'This field must be at least 10',
                'minimum' => 10,
            ],
        ], $response_data['detail']['height']);
        $this->assertArrayIsEqualIgnoringKeysOrder([
            [
                'error' => 'maximum',
                'message' => 'This field must be at most 300',
                'maximum' => 300,
            ],
        ], $response_data['detail']['weight']);
    }

    public function testApplyFieldReadRestrictions(): void
    {
        global $DB;

        $this->login('post-only', 'postonly');
        $user_schema = AdministrationController::getKnownSchemas(Router::API_VERSION)['User'];
        $filtered = $this->callPrivateMethod(ResourceAccessor::class, 'applyFieldReadRestrictions', $user_schema);
        $this->assertArrayHasKey('properties', $filtered);
        $this->assertArrayHasKey('id', $filtered['properties']);
        $this->assertArrayHasKey('username', $filtered['properties']);
        $this->assertArrayHasKey('phone', $filtered['properties']);
        $this->assertArrayHasKey('picture', $filtered['properties']);
        $this->assertArrayNotHasKey('date_sync', $filtered['properties']);

        $this->login('tech', 'tech');
        $result = json_decode((string)ResourceAccessor::searchBySchema($user_schema, ['limit' => 1])->getBody(), true);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('username', $result[0]);
        $this->assertArrayNotHasKey('date_sync', $result[0]);

        $result = json_decode((string)ResourceAccessor::getOneBySchema($user_schema, ['id' => 2], [])->getBody(), true);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('username', $result);
        $this->assertArrayNotHasKey('date_sync', $result);

        // clear readOnly flag on date_sync field to test updates
        $user_schema['properties']['date_sync']['readOnly'] = false;

        // test updating as Super-Admin just to ensure the test is set up correctly
        $this->login();
        ResourceAccessor::updateBySchema($user_schema, ['id' => 2], ['date_sync' => '2026-07-01 05:06:00']);
        $this->assertEquals(
            expected: '2026-07-01 05:06:00',
            actual: $DB->request([
                'SELECT' => ['date_sync'],
                'FROM' => 'glpi_users',
                'WHERE' => ['id' => 2],
            ])->current()['date_sync']
        );

        $this->login('tech', 'tech');
        $response = ResourceAccessor::updateBySchema($user_schema, ['id' => 2], ['date_sync' => '2026-07-02 06:07:00']);
        // Request is OK but the field is ignored as it isn't in the schema anymore
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            expected: '2026-07-01 05:06:00',
            actual: $DB->request([
                'SELECT' => ['date_sync'],
                'FROM' => 'glpi_users',
                'WHERE' => ['id' => 2],
            ])->current()['date_sync']
        );

        $this->login();
        ResourceAccessor::createBySchema($user_schema, ['username' => 'testuser', 'date_sync' => '2026-07-03 07:08:00'], [AdministrationController::class, 'getUserByID']);
        $this->assertEquals(
            expected: '2026-07-03 07:08:00',
            actual: $DB->request([
                'SELECT' => ['date_sync'],
                'FROM' => 'glpi_users',
                'WHERE' => ['name' => 'testuser'],
            ])->current()['date_sync']
        );

        $this->login('tech', 'tech');
        $response = ResourceAccessor::createBySchema($user_schema, ['username' => 'testuser2', 'date_sync' => '2026-07-04 08:09:00'], [AdministrationController::class, 'getUserByID']);
        // Request is OK but the field is ignored as it isn't in the schema anymore
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            expected: null,
            actual: $DB->request([
                'SELECT' => ['date_sync'],
                'FROM' => 'glpi_users',
                'WHERE' => ['name' => 'testuser2'],
            ])->current()['date_sync']
        );

        // Sorting by the date_sync field should be ignored for the tech user
        $response = json_decode((string)ResourceAccessor::searchBySchema($user_schema, ['sort' => 'date_sync'])->getBody(), true);
        $this->assertEquals("Invalid property for sorting: date_sync", $response['title']);

        // Filtering by the date_sync field should be ignored for the tech user
        $response = json_decode((string)ResourceAccessor::searchBySchema($user_schema, ['filter' => 'date_sync=gt="2040-01-01"'])->getBody(), true);
        $this->assertGreaterThan(5, count($response));
    }

    public function testSearchCursors(): void
    {
        global $DB;

        $this->login();

        $test_entity_id = $this->getTestRootEntity(true);
        for ($i = 0; $i < 30; $i++) {
            $DB->insert('glpi_computers', [
                'name' => __FUNCTION__ . str_pad($i, 3, '0', STR_PAD_LEFT),
                'entities_id' => $test_entity_id,
            ]);
        }

        $prefix = __FUNCTION__;
        $schema = AssetController::getKnownSchemas(Router::API_VERSION)['Computer'];
        $fn_search = static function (array $params) use ($schema, $prefix) {
            $response = ResourceAccessor::searchBySchema($schema, $params + [
                'filter' => 'name=like=' . $prefix . '*',
                'limit' => 10,
            ]);
            return [
                array_column(json_decode((string) $response->getBody(), true), 'name'),
                $response->getHeaders(),
                $response->getStatusCode(),
            ];
        };
        $fn_names = static fn(int $from, int $to) => array_map(
            static fn($i) => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
            range($from, $to)
        );

        // First page (offset-based)
        [$names, $headers, $status] = $fn_search([]);
        $this->assertEquals($fn_names(0, 9), $names);
        $this->assertEquals(206, $status);
        $this->assertEquals('0-9/30', $headers['Content-Range'][0]);
        $this->assertArrayNotHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);

        // Second page
        [$names, $headers, $status] = $fn_search(['cursor' => $headers['GLPI-Next-Cursor'][0]]);
        $this->assertEquals($fn_names(10, 19), $names);
        $this->assertEquals(206, $status);
        $this->assertArrayNotHasKey('Content-Range', $headers);
        $this->assertArrayHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);

        // Third (last) page
        [$names, $headers, $status] = $fn_search(['cursor' => $headers['GLPI-Next-Cursor'][0]]);
        $this->assertEquals($fn_names(20, 29), $names);
        $this->assertEquals(200, $status);
        $this->assertArrayHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayNotHasKey('GLPI-Next-Cursor', $headers);

        // Back to the second page
        [$names, $headers] = $fn_search(['cursor' => $headers['GLPI-Previous-Cursor'][0]]);
        $this->assertEquals($fn_names(10, 19), $names);
        $this->assertArrayHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);

        // Back to the first page
        [$names, $headers] = $fn_search(['cursor' => $headers['GLPI-Previous-Cursor'][0]]);
        $this->assertEquals($fn_names(0, 9), $names);
        $this->assertArrayNotHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);

        // Descending sort
        [$names, $headers] = $fn_search(['sort' => 'name:desc']);
        $this->assertEquals(array_reverse($fn_names(20, 29)), $names);
        [$names, $headers] = $fn_search(['sort' => 'name:desc', 'cursor' => $headers['GLPI-Next-Cursor'][0]]);
        $this->assertEquals(array_reverse($fn_names(10, 19)), $names);
        [$names] = $fn_search(['sort' => 'name:desc', 'cursor' => $headers['GLPI-Previous-Cursor'][0]]);
        $this->assertEquals(array_reverse($fn_names(20, 29)), $names);

        // Cursor used with a different sort than it was generated for
        $response = ResourceAccessor::searchBySchema($schema, [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'limit' => 10,
            'cursor' => $headers['GLPI-Next-Cursor'][0],
        ]);
        $this->assertEquals(400, $response->getStatusCode());

        // Invalid cursor
        $response = ResourceAccessor::searchBySchema($schema, [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'cursor' => 'not a cursor',
        ]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public static function searchCursorsComplexProvider(): iterable
    {
        yield 'mixed directions' => ['sort' => 'contact:asc,name:desc', 'limit' => 5];
        yield 'nullable with duplicates' => ['sort' => 'serial', 'limit' => 5];
        yield 'nullable desc with secondary sort' => ['sort' => 'serial:desc,contact', 'limit' => 4];
        yield 'nullable on both sorts in mixed directions' => ['sort' => 'contact:desc,serial:asc', 'limit' => 4];
        yield 'joined nullable property' => ['sort' => 'manufacturer.name', 'limit' => 5];
        yield 'joined property with multiple mixed sorts' => ['sort' => 'manufacturer.name:desc,serial:asc,name:desc', 'limit' => 3];
        yield 'explicit id desc' => ['sort' => 'id:desc', 'limit' => 5];
        yield 'single item pages' => ['sort' => 'serial:desc,contact:desc', 'limit' => 1];
        yield 'single page' => ['sort' => 'contact', 'limit' => 100];
        yield 'page size matching total' => ['sort' => 'serial', 'limit' => 23];
        yield 'filter on joined property' => ['sort' => 'serial:desc', 'limit' => 3, 'manufacturer_filter' => 'A'];
        yield 'filter and sort on joined property' => ['sort' => 'manufacturer.name:desc,contact', 'limit' => 4, 'manufacturer_filter' => 'B'];
    }

    /**
     * Walk through all pages forwards and then backwards using cursors and compare to the expected order computed independently of the database.
     */
    #[DataProvider('searchCursorsComplexProvider')]
    public function testSearchCursorsComplex(string $sort, int $limit, ?string $manufacturer_filter = null): void
    {
        global $DB;

        $this->login();

        $prefix = __FUNCTION__;
        $test_entity_id = $this->getTestRootEntity(true);
        $manufacturers = [
            0 => null,
            1 => $this->createItem(Manufacturer::class, ['name' => $prefix . 'A']),
            2 => $this->createItem(Manufacturer::class, ['name' => $prefix . 'B']),
        ];

        // 23 rows so that pages are uneven, with NULLs and duplicate values in the sorted fields
        $rows = [];
        for ($i = 0; $i < 23; $i++) {
            $manufacturer = $manufacturers[$i % 3];
            $row = [
                'name' => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
                'serial' => $i % 4 === 0 ? null : 'S' . ($i % 3),
                'contact' => $i % 5 === 0 ? null : 'C' . ($i % 2),
                'manufacturers_id' => $manufacturer?->getID() ?? 0,
                'entities_id' => $test_entity_id,
            ];
            $DB->insert('glpi_computers', $row);
            $row['id'] = $DB->insertId();
            $row['manufacturer.name'] = $manufacturer?->fields['name'];
            $rows[] = $row;
        }

        // Compute the expected order
        $sort_order = [];
        foreach (explode(',', $sort) as $s) {
            $parts = explode(':', $s);
            $sort_order[$parts[0]] = strtoupper($parts[1] ?? 'ASC');
        }
        $sort_order['id'] ??= 'ASC';
        if ($manufacturer_filter !== null) {
            $rows = array_filter($rows, static fn($row) => $row['manufacturer.name'] === $prefix . $manufacturer_filter);
        }
        usort($rows, static function ($a, $b) use ($sort_order) {
            foreach ($sort_order as $field => $direction) {
                if ($a[$field] === $b[$field]) {
                    continue;
                }
                // MySQL considers NULL lower than any other value
                if ($a[$field] === null) {
                    $cmp = -1;
                } elseif ($b[$field] === null) {
                    $cmp = 1;
                } else {
                    $cmp = $a[$field] <=> $b[$field];
                }
                return $direction === 'DESC' ? -$cmp : $cmp;
            }
            return 0;
        });
        $expected_pages = array_chunk(array_column($rows, 'name'), $limit);

        $filter = 'name=like=' . $prefix . '*';
        if ($manufacturer_filter !== null) {
            $filter .= ';manufacturer.name=="' . $prefix . $manufacturer_filter . '"';
        }
        $schema = AssetController::getKnownSchemas(Router::API_VERSION)['Computer'];
        $fn_search = static function (?string $cursor) use ($schema, $filter, $sort, $limit) {
            $params = [
                'filter' => $filter,
                'sort' => $sort,
                'limit' => $limit,
            ];
            if ($cursor !== null) {
                $params['cursor'] = $cursor;
            }
            $response = ResourceAccessor::searchBySchema($schema, $params);
            return [
                array_column(json_decode((string) $response->getBody(), true), 'name'),
                $response->getHeaders(),
                $response->getStatusCode(),
            ];
        };

        // Forwards
        $pages = [];
        $cursor = null;
        do {
            [$names, $headers, $status] = $fn_search($cursor);
            $pages[] = $names;
            $cursor = $headers['GLPI-Next-Cursor'][0] ?? null;
            $this->assertEquals($cursor !== null ? 206 : 200, $status);
            if (count($pages) === 1) {
                $this->assertArrayNotHasKey('GLPI-Previous-Cursor', $headers);
            } else {
                $this->assertArrayHasKey('GLPI-Previous-Cursor', $headers);
            }
        } while ($cursor !== null && count($pages) <= count($expected_pages));
        $this->assertEquals($expected_pages, $pages);

        // Backwards from the last page
        $pages = [array_pop($pages)];
        $cursor = $headers['GLPI-Previous-Cursor'][0] ?? null;
        while ($cursor !== null && count($pages) <= count($expected_pages)) {
            [$names, $headers] = $fn_search($cursor);
            array_unshift($pages, $names);
            $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);
            $cursor = $headers['GLPI-Previous-Cursor'][0] ?? null;
        }
        $this->assertEquals($expected_pages, $pages);
    }

    /**
     * Cursors should be resilient to data changes between requests, unlike offsets.
     */
    public function testSearchCursorsWithDataChanges(): void
    {
        global $DB;

        $this->login();

        $prefix = __FUNCTION__;
        $test_entity_id = $this->getTestRootEntity(true);
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $DB->insert('glpi_computers', [
                'name' => $prefix . str_pad($i, 3, '0', STR_PAD_LEFT),
                'entities_id' => $test_entity_id,
            ]);
            $ids[$i] = $DB->insertId();
        }

        $schema = AssetController::getKnownSchemas(Router::API_VERSION)['Computer'];
        $fn_search = static function (array $params) use ($schema, $prefix) {
            $response = ResourceAccessor::searchBySchema($schema, $params + [
                'filter' => 'name=like=' . $prefix . '*',
                'sort' => 'name',
                'limit' => 4,
            ]);
            return [
                array_map(
                    static fn($name) => substr($name, strlen($prefix)),
                    array_column(json_decode((string) $response->getBody(), true), 'name')
                ),
                $response->getHeaders(),
            ];
        };

        [$names, $headers] = $fn_search([]);
        $this->assertEquals(['000', '001', '002', '003'], $names);

        // Delete the last record of the page (the one the cursor points to) and the first record of the next page, then add a record which sorts between them
        $DB->delete('glpi_computers', ['id' => [$ids[3], $ids[4]]]);
        $DB->insert('glpi_computers', [
            'name' => $prefix . '0035',
            'entities_id' => $test_entity_id,
        ]);

        [$names, $headers] = $fn_search(['cursor' => $headers['GLPI-Next-Cursor'][0]]);
        $this->assertEquals(['0035', '005', '006', '007'], $names);

        // Only 3 records remain before the new one
        [$names, $headers] = $fn_search(['cursor' => $headers['GLPI-Previous-Cursor'][0]]);
        $this->assertEquals(['000', '001', '002'], $names);
        $this->assertArrayNotHasKey('GLPI-Previous-Cursor', $headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $headers);
    }

    /**
     * Cursor values are client-controlled so they must be escaped
     */
    public function testSearchCursorsTamperedValue(): void
    {
        global $DB;

        $this->login();

        $test_entity_id = $this->getTestRootEntity(true);
        for ($i = 0; $i < 3; $i++) {
            $DB->insert('glpi_computers', [
                'name' => __FUNCTION__ . $i,
                'entities_id' => $test_entity_id,
            ]);
        }

        $cursor = CursorPagination::generateCursorToken(
            CursorPagination::TYPE_NEXT,
            ['name' => "zzz' OR '1'='1", 'id' => 0],
            ['name' => 'ASC', 'id' => 'ASC']
        );
        $response = ResourceAccessor::searchBySchema(AssetController::getKnownSchemas(Router::API_VERSION)['Computer'], [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'sort' => 'name',
            'cursor' => $cursor,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame([], json_decode((string) $response->getBody(), true));
    }

    /**
     * Properties from one-to-many joins have no single value per record to base a cursor position on
     */
    public function testSearchCursorsArrayJoinedSort(): void
    {
        global $DB;

        $this->login();

        $test_entity_id = $this->getTestRootEntity(true);
        for ($i = 0; $i < 3; $i++) {
            $DB->insert('glpi_computers', [
                'name' => __FUNCTION__ . $i,
                'entities_id' => $test_entity_id,
            ]);
        }
        $schema = AssetController::getKnownSchemas(Router::API_VERSION)['Computer'];

        foreach (['group.name', 'name,group.id:desc'] as $sort) {
            // Offset-based pagination still works, but no cursors are given
            $response = ResourceAccessor::searchBySchema($schema, [
                'filter' => 'name=like=' . __FUNCTION__ . '*',
                'sort' => $sort,
                'limit' => 2,
            ]);
            $this->assertEquals(206, $response->getStatusCode());
            $this->assertCount(2, json_decode((string) $response->getBody(), true));
            $headers = $response->getHeaders();
            $this->assertEquals('0-1/3', $headers['Content-Range'][0]);
            $this->assertArrayNotHasKey('GLPI-Previous-Cursor', $headers);
            $this->assertArrayNotHasKey('GLPI-Next-Cursor', $headers);
        }

        // A cursor cannot be used with such a sort
        $cursor = CursorPagination::generateCursorToken(
            CursorPagination::TYPE_NEXT,
            ['group.name' => 'Group', 'id' => 0],
            ['group.name' => 'ASC', 'id' => 'ASC']
        );
        $response = ResourceAccessor::searchBySchema($schema, [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'sort' => 'group.name',
            'cursor' => $cursor,
        ]);
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('group.name', json_decode((string) $response->getBody(), true)['title']);
    }
}
