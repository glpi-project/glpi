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

use Glpi\Controller\Knowbase\DeleteArticleController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Response;

class DeleteArticleControllerTest extends DbTestCase
{
    private function callController(int $id): Response
    {
        return (new DeleteArticleController())->__invoke($id);
    }

    private function makeArticle(array $parents = []): int
    {
        $input = [
            'name'         => 'Delete article ' . $this->getUniqueString(),
            'answer'       => '<p>x</p>',
            'entities_id'  => 0,
            'is_recursive' => 1,
        ];
        if ($parents !== []) {
            $input['_parents'] = $parents;
        }

        return $this->createItem(KnowbaseItem::class, $input)->getID();
    }

    public function testChildlessArticleSucceeds(): void
    {
        $this->login();
        $this->setEntity(0, true);
        $id = $this->makeArticle();

        $response = $this->callController($id);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertFalse((new KnowbaseItem())->getFromDB($id));
    }

    public function testDeletingAnArticleThatHasChildrenFails(): void
    {
        $this->login();
        $this->setEntity(0, true);
        $parent_id = $this->makeArticle();
        $child_id  = $this->makeArticle([$parent_id]);

        $response = $this->callController($parent_id);

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertSame(
            [
                'success' => false,
                'message' => 'This article cannot be deleted because it contains sub-articles. They must be moved or deleted first.',
            ],
            json_decode((string) $response->getContent(), true),
        );

        // Both articles are untouched.
        $this->assertTrue((new KnowbaseItem())->getFromDB($parent_id));
        $this->assertTrue((new KnowbaseItem())->getFromDB($child_id));
    }
}
