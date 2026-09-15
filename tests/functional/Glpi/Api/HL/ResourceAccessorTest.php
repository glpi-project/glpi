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
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Tests\DbTestCase;
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

        $response = ResourceAccessor::searchBySchema(AssetController::getKnownSchemas(null)['Computer'], [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'limit' => 10,
        ]);
        $response_data = json_decode((string) $response->getBody(), true);
        $response_headers = $response->getHeaders();
        $this->assertCount(10, $response_data);
        $this->assertEquals(__FUNCTION__ . '000', $response_data[0]['name']);
        $this->assertEquals(__FUNCTION__ . '009', $response_data[9]['name']);
        //FIXME When cursors are used, the Content-Range header should not be returned because it would be wrong.
        $this->assertEquals('0-9/30', $response_headers['Content-Range'][0]);
        $this->assertArrayHasKey('GLPI-Previous-Cursor', $response_headers);
        $this->assertArrayHasKey('GLPI-Next-Cursor', $response_headers);
        $next_cursor = $response_headers['GLPI-Next-Cursor'][0];

        $response = ResourceAccessor::searchBySchema(AssetController::getKnownSchemas(null)['Computer'], [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'limit' => 10,
            'cursor' => $next_cursor,
        ]);
        $response_data = json_decode((string) $response->getBody(), true);
        $response_headers = $response->getHeaders();
        $this->assertCount(10, $response_data);
        $this->assertEquals(__FUNCTION__ . '010', $response_data[0]['name']);
        $this->assertEquals(__FUNCTION__ . '019', $response_data[9]['name']);
        $next_cursor = $response_headers['GLPI-Next-Cursor'][0];

        $response = ResourceAccessor::searchBySchema(AssetController::getKnownSchemas(null)['Computer'], [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'limit' => 10,
            'cursor' => $next_cursor,
        ]);
        $response_data = json_decode((string) $response->getBody(), true);
        $response_headers = $response->getHeaders();
        $this->assertCount(10, $response_data);
        $this->assertEquals(__FUNCTION__ . '020', $response_data[0]['name']);
        $this->assertEquals(__FUNCTION__ . '029', $response_data[9]['name']);

        //FIXME Previous cursors are not properly implemented yet. They always return the first page.
        $previous_cursor = $response_headers['GLPI-Previous-Cursor'][0];
        $response = ResourceAccessor::searchBySchema(AssetController::getKnownSchemas(null)['Computer'], [
            'filter' => 'name=like=' . __FUNCTION__ . '*',
            'limit' => 10,
            'cursor' => $previous_cursor,
        ]);
        $response_data = json_decode((string) $response->getBody(), true);
        $this->assertCount(10, $response_data);
        $this->assertEquals(__FUNCTION__ . '010', $response_data[0]['name']);
        $this->assertEquals(__FUNCTION__ . '019', $response_data[9]['name']);
    }
}
