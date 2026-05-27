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

use Glpi\Controller\Knowbase\UpdateCategoriesController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItemCategory;
use Session;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_encode;

final class UpdateCategoriesControllerTest extends DbTestCase
{
    public function testUpdatesCategoriesForExistingArticle(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $cat = $this->createItem(KnowbaseItemCategory::class, [
            'name' => 'C1', 'knowbaseitemcategories_id' => 0,
            'entities_id' => $entity_id, 'is_recursive' => 1,
        ]);
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'k', 'answer' => 'a', 'users_id' => Session::getLoginUserID(),
        ]);

        $request = new Request(content: json_encode(['categories_ids' => [$cat->getID()]]));
        $controller = new UpdateCategoriesController();
        $response = $controller($item->getID(), $request);

        $this->assertSame(200, $response->getStatusCode());
        $item->getFromDB($item->getID());
        $linked = $item->getCategoriesForDisplay();
        $this->assertSame([$cat->getID()], array_column($linked, 'id'));
    }

    public function testEmptyArrayClearsCategories(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $cat = $this->createItem(KnowbaseItemCategory::class, [
            'name' => 'C1', 'knowbaseitemcategories_id' => 0,
            'entities_id' => $entity_id, 'is_recursive' => 1,
        ]);
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'k', 'answer' => 'a', 'users_id' => Session::getLoginUserID(),
            '__categories_defined' => 1, '_categories' => [$cat->getID()],
        ]);

        $request = new Request(content: json_encode(['categories_ids' => []]));
        $response = (new UpdateCategoriesController())($item->getID(), $request);

        $this->assertSame(200, $response->getStatusCode());
        $item->getFromDB($item->getID());
        $this->assertSame([], $item->getCategoriesForDisplay());
    }

    public function testInvalidPayloadReturns400(): void
    {
        $this->login();
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'k', 'answer' => 'a', 'users_id' => Session::getLoginUserID(),
        ]);

        $request = new Request(content: json_encode(['nonsense' => true]));
        $response = (new UpdateCategoriesController())($item->getID(), $request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnauthorizedUserCannotUpdateCategories(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $cat = $this->createItem(KnowbaseItemCategory::class, [
            'name' => 'C1', 'knowbaseitemcategories_id' => 0,
            'entities_id' => $entity_id, 'is_recursive' => 1,
        ]);
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'k', 'answer' => 'a', 'users_id' => Session::getLoginUserID(),
        ]);

        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $request = new Request(content: json_encode(['categories_ids' => [$cat->getID()]]));
        (new UpdateCategoriesController())($item->getID(), $request);
    }

    public function testNonPositiveIdsAreFilteredOut(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);
        $cat = $this->createItem(KnowbaseItemCategory::class, [
            'name' => 'C1', 'knowbaseitemcategories_id' => 0,
            'entities_id' => $entity_id, 'is_recursive' => 1,
        ]);
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'k', 'answer' => 'a', 'users_id' => Session::getLoginUserID(),
        ]);

        // Mix: a valid id, a string that intval()s to 0, and a negative id.
        $request = new Request(
            content: json_encode(['categories_ids' => [$cat->getID(), 'garbage', -5, 0]])
        );
        $response = (new UpdateCategoriesController())($item->getID(), $request);

        $this->assertSame(200, $response->getStatusCode());
        $item->getFromDB($item->getID());
        $linked = $item->getCategoriesForDisplay();
        $this->assertSame([$cat->getID()], array_column($linked, 'id'));
    }
}
