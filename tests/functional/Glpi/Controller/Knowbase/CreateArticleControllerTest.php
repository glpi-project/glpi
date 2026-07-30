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

use Glpi\Controller\Knowbase\CreateArticleController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItemCategory;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_decode;
use function Safe\json_encode;

final class CreateArticleControllerTest extends DbTestCase
{
    public function testCreatesArticleLinkedToCategory(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $cat = $this->createItem(KnowbaseItemCategory::class, [
            'name' => 'C1', 'knowbaseitemcategories_id' => 0,
            'entities_id' => $entity_id, 'is_recursive' => 1,
        ]);

        $request = new Request(content: json_encode([
            'name' => 'New from inline input',
            'knowbaseitemcategories_id' => $cat->getID(),
        ]));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('url', $data);

        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame('New from inline input', $item->fields['name']);
        $this->assertSame('', $item->fields['answer']);
        $this->assertSame([$cat->getID()], array_map('intval', $item->fields['_categories']));
    }

    public function testCreatedArticleIsScopedToActiveEntity(): void
    {
        $this->login();
        $child_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $this->setEntity($child_entity_id, false);

        $request = new Request(content: json_encode(['name' => 'Entity scoped test']));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame($child_entity_id, (int) $item->fields['entities_id']);
        $this->assertSame(0, (int) $item->fields['is_recursive']);
    }

    public function testCreatesUncategorizedArticleWhenNoCategoryGiven(): void
    {
        $this->login();

        $request = new Request(content: json_encode(['name' => 'No category']));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame([], $item->fields['_categories']);
    }

    public function testEmptyNameReturns400(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $request = new Request(content: json_encode(['name' => '   ']));
        (new CreateArticleController())($request);
    }

    public function testNonArrayJsonBodyIsRejected(): void
    {
        $this->login();
        $this->expectException(BadRequestHttpException::class);
        $request = new Request(content: json_encode(42));
        (new CreateArticleController())($request);
    }

    public function testUnreadableCategoryIsSilentlyDropped(): void
    {
        $this->login();

        $request = new Request(content: json_encode([
            'name' => 'Unreadable category test',
            'knowbaseitemcategories_id' => 999999,
        ]));
        $response = (new CreateArticleController())($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $item = new KnowbaseItem();
        $this->assertTrue($item->getFromDB($data['id']));
        $this->assertSame([], $item->fields['_categories']);
    }

    public function testUserWithoutCreateRightIsDenied(): void
    {
        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode(['name' => 'Should be denied']));
        (new CreateArticleController())($request);
    }
}
