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

use Glpi\Controller\Knowbase\ToggleFavoriteController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Favorite;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ToggleFavoriteControllerTest extends DbTestCase
{
    private function callController(int $id, bool $value): JsonResponse
    {
        $request = Request::create(
            '/Knowbase/' . $id . '/ToggleFavorite',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['value' => $value]),
        );
        return (new ToggleFavoriteController())->__invoke($id, $request);
    }

    private function countFavorites(int $id): int
    {
        return (int) countElementsInTable(KnowbaseItem_Favorite::getTable(), [
            'knowbaseitems_id' => $id,
            'users_id'         => Session::getLoginUserID(),
        ]);
    }

    private function makeArticle(): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'   => 'Fav toggle ' . $this->getUniqueString(),
            'answer' => '<p>x</p>',
        ])->getID();
    }

    public function testAddFavoriteTwiceIsIdempotent(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, true);
        $second = $this->callController($id, true); // must not throw (previously a 500)

        $this->assertSame(1, $this->countFavorites($id));
        $this->assertSame('{"favorite":true}', $second->getContent());
    }

    public function testRemoveFavoriteWhenAbsentIsNoop(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $response = $this->callController($id, false);

        $this->assertSame(0, $this->countFavorites($id));
        $this->assertSame('{"favorite":false}', $response->getContent());
    }

    public function testToggleOnThenOff(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, true);
        $this->assertSame(1, $this->countFavorites($id));

        $this->callController($id, false);
        $this->assertSame(0, $this->countFavorites($id));
    }
}
