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
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Router;
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
        $result = json_decode((string) ResourceAccessor::searchBySchema($user_schema, ['limit' => 1])->getBody(), true);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('username', $result[0]);
        $this->assertArrayNotHasKey('date_sync', $result[0]);

        $result = json_decode((string) ResourceAccessor::getOneBySchema($user_schema, ['id' => 2], [])->getBody(), true);
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
        $response = json_decode((string) ResourceAccessor::searchBySchema($user_schema, ['sort' => 'date_sync'])->getBody(), true);
        $this->assertEquals("Invalid property for sorting: date_sync", $response['title']);

        // Filtering by the date_sync field should be ignored for the tech user
        $response = json_decode((string) ResourceAccessor::searchBySchema($user_schema, ['filter' => 'date_sync=gt="2040-01-01"'])->getBody(), true);
        $this->assertGreaterThan(5, count($response));
    }
}
