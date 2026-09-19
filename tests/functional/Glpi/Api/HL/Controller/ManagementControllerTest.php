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

namespace tests\units\Glpi\Api\HL\Controller;

use Computer;
use Contract;
use DatabaseInstance;
use Document;
use Domain;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Features\AssignableItemInterface;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Line;

class ManagementControllerTest extends HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $management_types = [
            'Budget', 'Cluster', 'Contact', 'Contract', 'Database',
            'DataCenter', 'Document', 'Domain', 'Line', 'Supplier', 'DatabaseInstance',
        ];

        foreach ($management_types as $m_name) {
            $this->api->autoTestCRUD('/Management/' . $m_name);
        }

        $domains_id = $this->createItem(Domain::class, [
            'name' => 'test_domain',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $this->api->autoTestCRUD('/Management/DomainRecord', [
            'domain' => $domains_id,
            'ttl' => 3600,
        ], ['ttl' => 7200]);
    }

    public function testCRUDNoRights()
    {
        $this->login();

        $management_types = ManagementController::getManagementTypes(false);
        foreach ($management_types as $m_class => $m) {
            $create_request = new Request('POST', '/Management/' . $m['schema_name']);
            $create_request->setParameter('name', __FUNCTION__ . '_' . $m['schema_name']);
            $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $new_location = null;
            $new_items_id = null;
            $this->api->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use (&$new_location) {
                        $new_location = $headers['Location'];
                    })
                    ->jsonContent(function ($content) use (&$new_items_id) {
                        $new_items_id = $content['id'];
                    });
            });
            $deny_create = null;
            if ($m_class === Document::class) {
                $deny_create = static function () {
                    $_SESSION['glpiactiveprofile']['document'] = ALLSTANDARDRIGHT & ~CREATE;
                    $_SESSION['glpiactiveprofile']['followup'] = 0;
                };
            }
            $this->api->autoTestCRUDNoRights(
                endpoint: '/Management/' . $m['schema_name'],
                itemtype: $m_class,
                items_id: $new_items_id,
                deny_create: $deny_create
            );
        }
    }

    public function testAssignableRights()
    {
        $management_types = ManagementController::getManagementTypes(false);
        foreach ($management_types as $m_class => $m) {
            if (!is_subclass_of($m_class, AssignableItemInterface::class) || $m_class === DatabaseInstance::class) {
                continue;
            }
            $this->api->autoTestAssignableItemRights('/Management/' . $m['schema_name'], $m_class);
        }
    }

    public function testCRUDContractCost()
    {
        $this->loginWeb();
        $contracts_id = $this->createItem('Contract', [
            'name' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        $this->api->autoTestCRUD('/Management/Contract/' . $contracts_id . '/Cost', [
            'name' => __FUNCTION__,
            'cost' => 100,
        ], [
            'name' => __FUNCTION__ . '2',
            'cost' => 150,
        ]);
    }

    public function testCRUDDomainItemLink()
    {
        $this->loginWeb();
        $computers_id = getItemByTypeName(Computer::class, '_test_pc01', true);
        $domains_id = $this->createItem(Domain::class, [
            'name' => 'test_domain',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $database_id = $this->createItem('Database', [
            'name' => '_testDB01',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->api->autoTestCRUD('/Management/Database/' . $database_id . '/Domain', [
            'domain' => $domains_id,
        ], [
            'is_deleted' => 1,
        ]);
        $this->api->autoTestCRUD('/Assets/Computer/' . $computers_id . '/Domain', [
            'domain' => $domains_id,
        ], [
            'is_deleted' => 1,
        ]);
    }

    public function testCRUDLineItemLink()
    {
        $this->loginWeb();
        $computers_id = getItemByTypeName(Computer::class, '_test_pc01', true);
        $lines_id = $this->createItem(Line::class, [
            'name' => 'test_line',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->api->autoTestCRUD('/Assets/Computer/' . $computers_id . '/Line', [
            'line' => $lines_id,
        ], [
            'line' => $lines_id,
        ]);
    }

    public function testCRUDContractItemLink()
    {
        $this->loginWeb();
        $computers_id = getItemByTypeName(Computer::class, '_test_pc01', true);
        $contracts_id = $this->createItem(Contract::class, [
            'name' => 'test_line',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $lines_id = $this->createItem(Line::class, [
            'name' => 'test_line',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $projects_id = getItemByTypeName('Project', '_project01', true);

        $this->api->autoTestCRUD('/Assets/Computer/' . $computers_id . '/Contract', [
            'contract' => $contracts_id,
        ], [
            'contract' => $contracts_id,
        ]);

        $this->api->autoTestCRUD('/Management/Line/' . $lines_id . '/Contract', [
            'contract' => $contracts_id,
        ], [
            'contract' => $contracts_id,
        ]);

        $this->api->autoTestCRUD('/Project/Project/' . $projects_id . '/Contract', [
            'contract' => $contracts_id,
        ], [
            'contract' => $contracts_id,
        ]);
    }

    public function testCreateAndUpdateDocumentWithFile(): void
    {
        $bar_file_path = GLPI_ROOT . '/tests/fixtures/uploads/bar.png';
        $bar_file_content = file_get_contents($bar_file_path);
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $this->login();

        // multipart form data request with the document item's name, the file, and the entity ID
        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

test_document_with_file
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="bar.png"
Content-Type: image/png

$bar_file_content
-----boundary--
EOT;
        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $new_location = null;
        $doc_id = null;
        $this->api->call($request, function ($call) use (&$new_location, &$doc_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$new_location, &$doc_id) {
                    $new_location = $content['href'];
                    $doc_id = $content['id'];
                });
        });

        $download_url = null;
        $this->api->call(new Request('GET', $new_location), function ($call) use (&$download_url) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$download_url) {
                    $this->assertEquals('test_document_with_file', $content['name']);
                    $this->assertMatchesRegularExpression('/document\.send\.php\?docid=\d+/', $content['filepath']);
                    $this->assertMatchesRegularExpression('/Management\/Document\/\d+\/Download/', $content['download_url']);
                    $download_url = $content['download_url'];
                });
        });

        $doc = new Document();
        $doc->getFromDB($doc_id);

        // Make sure the downloaded file contents match the original file contents
        $this->api->call(new Request('GET', $download_url), function ($call) use ($bar_file_content) {
            $call->response
                ->isOK()
                ->content(function ($content) use ($bar_file_content) {
                    $this->assertEquals($bar_file_content, $content);
                });
        });

        $foo_file_path = GLPI_ROOT . '/tests/fixtures/uploads/foo.png';
        $foo_file_content = file_get_contents($foo_file_path);

        // multipart form data request to update the document item's file
        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

test_document_with_file_updated
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="foo.png"
Content-Type: image/png

$foo_file_content
-----boundary--
EOT;

        $request = new Request('PATCH', '/Management/Document/' . $doc_id, [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('test_document_with_file_updated', $content['name']);
                });
        });

        // Make sure the downloaded file contents match the updated file contents
        $this->api->call(new Request('GET', $download_url), function ($call) use ($foo_file_content) {
            $call->response
                ->isOK()
                ->content(function ($content) use ($foo_file_content) {
                    $this->assertEquals($foo_file_content, $content);
                });
        });
    }

    public function testCreateDocumentFileTooBig(): void
    {
        global $CFG_GLPI;

        $original_max_size = $CFG_GLPI['document_max_size'];
        $CFG_GLPI['document_max_size'] = 330 / 1024 / 1024; // 330 bytes
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $allowed_file_path = GLPI_ROOT . '/tests/fixtures/uploads/foo.png';
        $too_big_file_path = GLPI_ROOT . '/tests/fixtures/uploads/bar.png';

        $allowed_file_content = file_get_contents($allowed_file_path);
        $too_big_file_content = file_get_contents($too_big_file_path);

        $this->login();

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

test_document_with_file
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="bar.png"
Content-Type: image/png

$too_big_file_content
-----boundary--
EOT;
        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $this->api->call($request, function ($call) {
            $call->response
                ->isNotOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals(AbstractController::ERROR_INVALID_PARAMETER, $content['status']);
                    $this->assertEquals('file_size_exceeded', $content['detail']['file'][0]['error']);
                });
        });

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

test_document_with_file
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="bar.png"
Content-Type: image/png

$allowed_file_content
-----boundary--
EOT;
        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        $CFG_GLPI['document_max_size'] = $original_max_size;
    }

    public function testCreateDocumentFileNotAllowed(): void
    {
        $this->login();
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

test_document_with_file
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="bar.glpi"
Content-Type: text/glpi

test
-----boundary--
EOT;
        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $this->api->call($request, function ($call) {
            $call->response
                ->isNotOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals(AbstractController::ERROR_INVALID_PARAMETER, $content['status']);
                    $this->assertEquals('invalid_file_type', $content['detail']['file'][0]['error']);
                });
        });
    }

    /**
     * Build a PNG payload that is unique to the given test so that its hash, and therefore the path it would be
     * stored at, cannot collide with the files written by the other tests of the suite.
     * The extra bytes are appended after the end of the PNG stream so the detected mime type stays "image/png".
     */
    private function getUniquePngContent(string $marker): string
    {
        return file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/bar.png') . $marker;
    }

    /**
     * Get the path a document file is stored at, as computed by {@link Document::getUploadFileValidLocationName()}.
     */
    private function getDocumentFilePath(string $sha1sum, string $extension = 'PNG'): string
    {
        return GLPI_DOC_DIR . '/' . $extension . '/' . substr($sha1sum, 0, 2) . '/' . substr($sha1sum, 2) . '.' . $extension;
    }

    private function getDocumentMultipartBody(string $name, int $entities_id, string $file_content): string
    {
        return <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

$name
-----boundary
Content-Disposition: form-data; name="entity"

$entities_id
-----boundary
Content-Disposition: form-data; name="file"; filename="bar.png"
Content-Type: image/png

$file_content
-----boundary--
EOT;
    }

    public function testCreateDocumentWithFileNoRights(): void
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $file_content = $this->getUniquePngContent(__FUNCTION__);
        $sha1sum = sha1($file_content);
        $expected_path = $this->getDocumentFilePath($sha1sum);

        // Document::canCreate() also accepts the followup ADDMY right, so it has to be dropped too
        $_SESSION['glpiactiveprofile']['document'] = ALLSTANDARDRIGHT & ~CREATE;
        $_SESSION['glpiactiveprofile']['followup'] = 0;

        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $this->getDocumentMultipartBody(__FUNCTION__, $entities_id, $file_content));

        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        }, false);

        // The rights must be checked before anything is written, so neither the record nor the file may exist
        $this->assertEquals(0, countElementsInTable(Document::getTable(), ['sha1sum' => $sha1sum]));
        $this->assertFileDoesNotExist($expected_path);
    }

    public function testReplaceDocumentFileNoRights(): void
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $original_content = $this->getUniquePngContent(__FUNCTION__ . '_original');
        $replacement_content = $this->getUniquePngContent(__FUNCTION__ . '_replacement');
        $original_sha1sum = sha1($original_content);
        $replacement_sha1sum = sha1($replacement_content);

        $documents_id = null;
        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $this->getDocumentMultipartBody(__FUNCTION__, $entities_id, $original_content));
        $this->api->call($request, function ($call) use (&$documents_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$documents_id) {
                    $documents_id = $content['id'];
                });
        }, false);
        $this->assertNotNull($documents_id);

        $_SESSION['glpiactiveprofile']['document'] = ALLSTANDARDRIGHT & ~UPDATE;

        $request = new Request('PATCH', '/Management/Document/' . $documents_id, [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $this->getDocumentMultipartBody(__FUNCTION__ . '_updated', $entities_id, $replacement_content));
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        }, false);

        // The document must still point at the original file and the replacement must not have been written
        $document = new Document();
        $this->assertTrue($document->getFromDB($documents_id));
        $this->assertEquals($original_sha1sum, $document->fields['sha1sum']);
        $this->assertFileDoesNotExist($this->getDocumentFilePath($replacement_sha1sum));

        $original_path = $this->getDocumentFilePath($original_sha1sum);
        $this->assertFileExists($original_path);
        unlink($original_path);
    }

    public function testCreateDocumentWithFileInInaccessibleEntity(): void
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $this->setEntity('_test_child_1', false);
        $forbidden_entities_id = getItemByTypeName('Entity', '_test_child_2', true);

        $file_content = $this->getUniquePngContent(__FUNCTION__);
        $sha1sum = sha1($file_content);

        $request = new Request('POST', '/Management/Document', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $this->getDocumentMultipartBody(__FUNCTION__, $forbidden_entities_id, $file_content));

        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        }, false);

        $this->assertEquals(0, countElementsInTable(Document::getTable(), ['sha1sum' => $sha1sum]));
        $this->assertFileDoesNotExist($this->getDocumentFilePath($sha1sum));
    }
}
