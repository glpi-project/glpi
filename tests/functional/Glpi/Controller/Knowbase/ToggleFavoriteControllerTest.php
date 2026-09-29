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

use Entity_KnowbaseItem;
use Glpi\Controller\Knowbase\ToggleFavoriteController;
use Glpi\Http\Firewall;
use Glpi\Http\SessionManager;
use Glpi\Kernel\Listener\ControllerListener\FirewallStrategyListener;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Favorite;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use User;

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

    private function applyFirewallStrategy(int $id): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            new ToggleFavoriteController(),
            Request::create('/Knowbase/' . $id . '/ToggleFavorite', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new FirewallStrategyListener(new Firewall(), new SessionManager()))->onKernelController($event);
    }

    private function makeFaqArticleVisibleToHelpdesk(): int
    {
        $entity = $this->getTestRootEntity(only_id: true);

        $article = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Fav toggle helpdesk ' . $this->getUniqueString(),
            'answer'      => '<p>x</p>',
            'is_faq'      => 1,
            'users_id'    => getItemByTypeName(User::class, 'glpi', true),
            'entities_id' => $entity,
        ]);
        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $article->getID(),
            'entities_id'      => $entity,
            'is_recursive'     => 1,
        ]);

        return $article->getID();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testHelpdeskProfileCanToggleFavorite(): void
    {
        $this->login();
        $id = $this->makeFaqArticleVisibleToHelpdesk();

        $this->login('post-only', 'postonly');

        $this->applyFirewallStrategy($id);
        $response = $this->callController($id, true);

        $this->assertSame(1, $this->countFavorites($id));
        $this->assertSame('{"favorite":true}', $response->getContent());
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
