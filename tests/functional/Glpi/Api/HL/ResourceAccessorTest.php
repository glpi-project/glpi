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

use CommonDBTM;
use Glpi\Api\HL\APIException;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Tests\GLPITestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Ticket;

class ResourceAccessorTest extends GLPITestCase
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
        $schema_b = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'members' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'x-mapped-from' => 'id',
                        'x-mapper' => static fn($v) => [],
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                        ],
                    ],
                    'x-input-mapper' => static fn($v) => ['_members' => $v],
                ],
            ],
        ];
        return [
            [$schema_a, ['name' => 'Test', 'status' => 4, 'renamed' => 'test', 'extra' => 'not exist'], ['name' => 'Test', 'status' => 4, 'old_name' => 'test']],
            [$schema_a, ['name' => 'Test', 'status' => ['id' => 4]], ['name' => 'Test', 'status' => 4]],
            // Properties with an input mapper are handled by getMappedInputParamsBySchema
            [$schema_b, ['name' => 'Test', 'members' => [['id' => 2]]], ['name' => 'Test']],
        ];
    }

    #[DataProvider('getInputParamsBySchemaProvider')]
    public function testGetInputParamsBySchema($schema, $request_params, $expected)
    {
        $this->assertEquals($expected, ResourceAccessor::getInputParamsBySchema($schema, $request_params));
    }

    public function testGetMappedInputParamsBySchema(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'members' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'x-input-mapper' => static fn(mixed $v, CommonDBTM $item) => [
                        '_members' => $v,
                        '_is_new' => $item->isNewItem(),
                    ],
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'x-input-mapper' => static fn(mixed $v) => ['_tags' => $v],
                ],
                'readonly_members' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'readOnly' => true,
                    'x-input-mapper' => static fn(mixed $v) => ['_readonly_members' => $v],
                ],
            ],
        ];

        // Only properties with an input mapper which are present in the request and not read-only are mapped
        $this->assertEquals(
            ['_members' => [1, 2], '_is_new' => true],
            ResourceAccessor::getMappedInputParamsBySchema($schema, [
                'name' => 'Test',
                'members' => [1, 2],
                'readonly_members' => [3],
            ], new Ticket())
        );
        $this->assertEquals(
            ['_members' => [], '_is_new' => true, '_tags' => ['a']],
            ResourceAccessor::getMappedInputParamsBySchema($schema, ['members' => [], 'tags' => ['a']], new Ticket())
        );
        $this->assertEquals([], ResourceAccessor::getMappedInputParamsBySchema($schema, ['name' => 'Test'], new Ticket()));
    }

    public function testGetMappedInputParamsBySchemaWithInvalidValue(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'members' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'x-input-mapper' => static function (mixed $v) {
                        throw new InvalidArgumentException('Invalid members');
                    },
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'x-input-mapper' => static fn(mixed $v) => ['_tags' => $v],
                ],
            ],
        ];

        try {
            ResourceAccessor::getMappedInputParamsBySchema($schema, ['members' => [1], 'tags' => ['a']], new Ticket());
            $this->fail('An APIException should have been thrown');
        } catch (APIException $e) {
            $this->assertEquals([
                'members' => [
                    [
                        'error' => 'invalid',
                        'message' => 'Invalid members',
                    ],
                ],
            ], $e->getDetails());
        }
    }

    public function testCreateWithInvalidProperties()
    {
        $schema_with_validation = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'maxLength' => 32, 'required' => true],
                'age' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120, 'required' => true],
                'height' => ['type' => 'number', 'minimum' => 10, 'required' => true],
                'weight' => ['type' => 'number', 'maximum' => 300, 'multipleOf' => 0.5, 'required' => true],
                'eye_color' => ['type' => 'number', 'required' => true],
            ],
        ];

        $invalid_data = [
            'name' => str_repeat('a', 40), // Exceeds maxLength
            'age' => -5, // Below minimum
            'height' => 5, // Below minimum
            'weight' => 349.8, // Above maximum and not multiple of 0.5
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
            [
                'error' => 'multipleOf',
                'message' => 'This field must be a multiple of 0.5',
                'multipleOf' => 0.5,
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

    public function testValidatePreconditions(): void
    {
        $ticket = new Ticket();
        $ticket->fields = [
            'id' => 1,
            'name' => 'Test Ticket',
            'content' => 'This is a test ticket.',
            'date_mod' => '2026-09-01 12:00:00',
        ];
        $rm = new ReflectionMethod(ResourceAccessor::class, 'validatePreconditions');

        $this->assertEmpty($rm->invoke(null, $ticket, [
            'If-Modified-Since' => ['2026-08-15 12:00:00'],
        ]));
        $this->assertArrayHasKey('If-Modified-Since', $rm->invoke(null, $ticket, [
            'If-Modified-Since' => ['2026-09-02 2:00:00'],
        ]));

        $this->assertEmpty($rm->invoke(null, $ticket, [
            'If-Unmodified-Since' => ['2026-09-02 12:00:00'],
        ]));
        $this->assertArrayHasKey('If-Unmodified-Since', $rm->invoke(null, $ticket, [
            'If-Unmodified-Since' => ['2026-08-15 12:00:00'],
        ]));
    }

    public function testValidatePreconditionsRejectsMalformedHeader(): void
    {
        $ticket = new Ticket();
        $ticket->fields = [
            'id' => 1,
            'date_mod' => '2026-09-01 12:00:00',
        ];
        $rm = new ReflectionMethod(ResourceAccessor::class, 'validatePreconditions');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for If-Modified-Since header.');
        $rm->invoke(null, $ticket, [
            'If-Modified-Since' => ['not-a-date'],
        ]);
    }

    public function testValidatePreconditionsRejectsConflictingHeaders(): void
    {
        $ticket = new Ticket();
        $ticket->fields = [
            'id' => 1,
            'date_mod' => '2026-09-01 12:00:00',
        ];
        $rm = new ReflectionMethod(ResourceAccessor::class, 'validatePreconditions');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('If-Modified-Since and If-Unmodified-Since headers cannot be used together.');
        $rm->invoke(null, $ticket, [
            'If-Modified-Since' => ['2026-09-01 12:00:00'],
            'If-Unmodified-Since' => ['2026-09-01 12:00:00'],
        ]);
    }
}
