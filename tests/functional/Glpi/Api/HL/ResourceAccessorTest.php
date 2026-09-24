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

use CommonITILObject;
use Document_Item;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\FileUpload\FileManager;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use RuntimeException;
use Ticket;

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

    public function testSinglePictureRemovalKeepsFileOnRollback(): void
    {
        global $DB;

        $monitor_model = $this->createItem(\MonitorModel::class, [
            'name' => __FUNCTION__,
        ]);
        $monitor_model_id = $monitor_model->getID();

        $picture_path = \Toolbox::savePicture(GLPI_ROOT . '/tests/fixtures/uploads/bar.png', '', true);
        $this->assertIsString($picture_path);

        $DB->update(\MonitorModel::getTable(), [
            'picture_front' => $picture_path,
        ], [
            'id' => $monitor_model_id,
        ]);

        $monitor_model = new \MonitorModel();
        $this->assertTrue($monitor_model->getFromDB($monitor_model_id));

        $schema = [
            'type' => 'object',
            'properties' => [
                'picture_front_upload' => [
                    'type' => 'string',
                    'x-input-field' => 'picture_front',
                    'x-file-upload-options' => [
                        'upload_as' => FileManager::UPLOAD_AS_PICTURE,
                    ],
                ],
            ],
        ];
        $request_params = [
            'picture_front_upload' => '',
        ];
        $rollback_journal = [
            'documents' => [],
            'files' => [],
            'pictures' => [],
            'deferred_picture_deletions' => [],
        ];

        $full_picture_path = GLPI_PICTURE_DIR . '/' . $picture_path;
        $this->assertFileExists($full_picture_path);

        $rm = new ReflectionMethod(ResourceAccessor::class, 'handlePostCreateOrUpdate');

        $DB->beginTransaction();
        try {
            $rm->invokeArgs(null, [$monitor_model, $schema, $request_params, ['id' => $monitor_model_id], &$rollback_journal]);
            throw new RuntimeException('Simulated later post-action failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated later post-action failure', $e->getMessage());
        } finally {
            $DB->rollBack();
        }

        $reloaded_monitor_model = new \MonitorModel();
        $this->assertTrue($reloaded_monitor_model->getFromDB($monitor_model_id));
        $this->assertSame($picture_path, $reloaded_monitor_model->fields['picture_front']);
        $this->assertFileExists($full_picture_path);
        $this->assertSame([$picture_path], $rollback_journal['deferred_picture_deletions']);

        FileManager::deletePicture($picture_path);
    }

    public function testLinkCreatedDocumentsToItemThrowsWhenAssociationIsRejected(): void
    {
        global $DB;

        $this->login();

        $source_ticket = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__ . '_source',
            'content' => __FUNCTION__ . '_source',
        ]);
        $target_ticket = $this->createItem(Ticket::class, [
            'name' => __FUNCTION__ . '_target',
            'content' => __FUNCTION__ . '_target',
        ]);
        $document = $this->addDocumentToItem('association-rejection.txt', __FUNCTION__, $source_ticket);

        $rm = new ReflectionMethod(ResourceAccessor::class, 'linkCreatedDocumentsToItem');
        $original_slave = $DB->slave;
        $DB->slave = true;
        try {
            $rm->invoke(null, [$document], $target_ticket->getID(), Ticket::class);
            $this->fail('Expected the document association to be rejected.');
        } catch (RuntimeException $e) {
            $this->assertSame('Failed to link uploaded document to item', $e->getMessage());
        } finally {
            $DB->slave = $original_slave;
        }

        $this->assertEquals(0, countElementsInTable(Document_Item::getTable(), [
            'documents_id' => $document->getID(),
            'items_id' => $target_ticket->getID(),
            'itemtype' => Ticket::class,
            'timeline_position' => CommonITILObject::NO_TIMELINE,
        ]));
    }
}
