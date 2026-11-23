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

namespace tests\units\Glpi\Controller\Knowbase;

use CommonITILObject;
use Document_Item;
use Glpi\Controller\Knowbase\UploadInlineImageController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadInlineImageControllerTest extends DbTestCase
{
    private function createKbArticle(): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'        => 'Test article for image upload',
            'answer'      => '<p>Test content</p>',
            'entities_id' => $this->getTestRootEntity()->getID(),
        ])->getID();
    }

    private function placeTempImage(string $filename = 'test_image.png'): string
    {
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);
        return $filename;
    }

    private function placeTempTextFile(string $filename = 'test_file.txt'): string
    {
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename);
        return $filename;
    }

    private function callController(int $kb_id, string $content): JsonResponse
    {
        $controller = new UploadInlineImageController();
        $request = new Request(content: $content);
        $request->attributes->set('knowbaseitems_id', $kb_id);
        return $controller->__invoke($kb_id, $request);
    }

    public function testSuccessfulUpload(): void
    {
        $this->login();
        $kb_id = $this->createKbArticle();
        $filename = $this->placeTempImage();

        $response = $this->callController($kb_id, json_encode([
            'filename' => $filename,
            'prefix'   => '',
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertStringContainsString('document.send.php', $body['url']);
        $this->assertStringContainsString('docid=', $body['url']);

        // Verify Document_Item was created with timeline_position = -1
        global $DB;
        $iterator = $DB->request([
            'FROM'  => Document_Item::getTable(),
            'WHERE' => [
                'itemtype' => KnowbaseItem::class,
                'items_id' => $kb_id,
            ],
        ]);
        $this->assertEquals(1, count($iterator));
        $row = $iterator->current();
        $this->assertEquals(CommonITILObject::NO_TIMELINE, (int) $row['timeline_position']);
    }

    public function testEmptyFilenameReturnsError(): void
    {
        $this->login();
        $kb_id = $this->createKbArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($kb_id, json_encode([
            'filename' => '',
        ]));
    }

    public function testNonExistentFileReturnsError(): void
    {
        $this->login();
        $kb_id = $this->createKbArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($kb_id, json_encode([
            'filename' => 'nonexistent_file.png',
        ]));
    }

    public function testNonImageFileReturnsError(): void
    {
        $this->login();
        $kb_id = $this->createKbArticle();
        $filename = $this->placeTempTextFile();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($kb_id, json_encode([
            'filename' => $filename,
        ]));
    }

    public function testNonExistentArticleReturnsError(): void
    {
        $this->login();
        $filename = $this->placeTempImage();

        $this->expectException(NotFoundHttpException::class);
        $this->callController(99999, json_encode([
            'filename' => $filename,
        ]));
    }

    public function testUnauthorizedUserReturnsError(): void
    {
        $this->login('normal', 'normal');
        $kb_id = $this->createKbArticle();
        $filename = $this->placeTempImage();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($kb_id, json_encode([
            'filename' => $filename,
        ]));
    }

    public function testPathTraversalReturnsError(): void
    {
        $this->login();
        $kb_id = $this->createKbArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($kb_id, json_encode([
            'filename' => '../etc/passwd',
        ]));
    }

    public function testInlineImageHiddenFromDocumentsList(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity()->getID();
        $kb_id = $this->createKbArticle();

        // Create an inline image (timeline_position = -1)
        $inline_doc_id = $this->createItem(\Document::class, [
            'name'        => 'Inline image',
            'entities_id' => $entity_id,
        ])->getID();
        $this->createItem(Document_Item::class, [
            'documents_id'      => $inline_doc_id,
            'itemtype'          => KnowbaseItem::class,
            'items_id'          => $kb_id,
            'timeline_position' => CommonITILObject::NO_TIMELINE,
        ]);

        // Create a regular document (timeline_position = 0)
        $regular_doc_id = $this->createItem(\Document::class, [
            'name'        => 'Regular document',
            'entities_id' => $entity_id,
        ])->getID();
        $this->createItem(Document_Item::class, [
            'documents_id'      => $regular_doc_id,
            'itemtype'          => KnowbaseItem::class,
            'items_id'          => $kb_id,
            'timeline_position' => 0,
        ]);

        // Call getDocumentsInfo() via reflection
        $kbitem = new KnowbaseItem();
        $kbitem->getFromDB($kb_id);
        $method = new \ReflectionMethod($kbitem, 'getDocumentsInfo');

        $documents = $method->invoke($kbitem);

        // Only the regular document should be returned
        $this->assertCount(1, $documents);
        $this->assertEquals($regular_doc_id, $documents[0]['id']);
    }
}
