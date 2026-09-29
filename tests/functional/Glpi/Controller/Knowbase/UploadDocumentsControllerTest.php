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

use Document_Item;
use Glpi\Controller\Knowbase\UploadDocumentsController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadDocumentsControllerTest extends DbTestCase
{
    private function callController(int $kb_id): JsonResponse
    {
        $prefix = $this->getUniqueString();
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $prefix . 'foo.txt');

        $controller = new UploadDocumentsController();
        $request = new Request(content: json_encode([
            'files' => [[
                '_filename'        => $prefix . 'foo.txt',
                '_prefix_filename' => $prefix,
                '_tag_filename'    => $this->getUniqueString(),
            ]],
        ]));
        return $controller->__invoke($kb_id, $request);
    }

    private function countDocuments(int $kb_id): int
    {
        return countElementsInTable(Document_Item::getTable(), [
            'itemtype' => KnowbaseItem::class,
            'items_id' => $kb_id,
        ]);
    }

    public function testUploadOnEditableArticle(): void
    {
        $this->login();
        $kb_id = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Upload ' . $this->getUniqueString(),
            'answer'      => '<p>x</p>',
            'entities_id' => $this->getTestRootEntity()->getID(),
        ])->getID();

        $response = $this->callController($kb_id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, json_decode($response->getContent(), true)['documents']);
        $this->assertEquals(1, $this->countDocuments($kb_id));
    }

    public function testUploadIsDeniedWithReadOnlyAccessToTheArticle(): void
    {
        $this->login();

        // FAQ article authored by someone else: editing it needs PUBLISHFAQ
        $kb_id = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Upload ' . $this->getUniqueString(),
            'answer'      => '<p>x</p>',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'users_id'    => getItemByTypeName('User', 'normal', true),
            'is_faq'      => 1,
        ])->getID();
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb_id,
            'users_id'         => Session::getLoginUserID(),
        ]);

        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        // Documents may be created, the article may be read, not edited
        $this->assertTrue(Session::haveRight(\Document::$rightname, CREATE));
        $kb = new KnowbaseItem();
        $this->assertTrue($kb->getFromDB($kb_id));
        $this->assertTrue($kb->can($kb_id, READ));
        $this->assertFalse($kb->can($kb_id, UPDATE));

        try {
            $this->callController($kb_id);
            $this->fail('Upload should have been denied');
        } catch (AccessDeniedHttpException) {
        }
        $this->assertEquals(0, $this->countDocuments($kb_id));
    }
}
