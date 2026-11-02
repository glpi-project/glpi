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

use Glpi\Controller\Knowbase\MoveModalController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveModalControllerTest extends DbTestCase
{
    public function testUnknownArticleIsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->callController(999999999, 0);
    }

    public function testModalIsDeniedWithoutUpdateRight(): void
    {
        $this->login();
        $article = $this->makeArticle();

        // Drop the knowbase UPDATE right, keeping READ so the article loads.
        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ;

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($article, 0);
    }

    public function testRootArticleIsDenied(): void
    {
        $this->login();

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController(KnowbaseItem::getRootId(), 0);
    }

    public function testRootLevelIsNotOfferedAndTheRootArticleStandsIn(): void
    {
        $this->login();
        $article = $this->makeArticle();

        $response = $this->callController($article, 0);
        $content  = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDoesNotMatchRegularExpression("/<option value='0'/", $content);
        $this->assertMatchesRegularExpression(
            "/<option value='" . KnowbaseItem::getRootId() . "' selected/",
            $content,
        );
    }

    public function testRealParentIsPreselected(): void
    {
        $this->login();
        $parent = $this->makeArticle();
        $child  = $this->makeArticle([$parent]);

        $content = $this->callController($child, $parent)->getContent();

        $this->assertMatchesRegularExpression(
            '/name="from_parent_id"\s+value="' . $parent . '"/',
            $content,
        );
    }

    public function testHintThatIsNotAnEdgeFallsBackToRoot(): void
    {
        $this->login();
        $child    = $this->makeArticle();
        $stranger = $this->makeArticle([$child]);

        $content = $this->callController($child, $stranger)->getContent();

        // Reverse edge: catches isParentOf() being called with swapped arguments.
        $this->assertMatchesRegularExpression(
            '/name="from_parent_id"\s+value="0"/',
            $content,
        );
    }

    public function testRealParentIsKeptEvenWhenItIsNotAnOfferedCandidate(): void
    {
        $this->login();
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Move modal parent ' . $this->getUniqueString(),
            'answer'   => '<p>x</p>',
            // Its author could edit it whatever the rights, so someone else owns it.
            'users_id' => getItemByTypeName('User', 'normal', true),
            'is_faq'   => 1,
        ])->getID();
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $parent,
            'users_id'         => Session::getLoginUserID(),
        ]);
        $child = $this->makeArticle([$parent]);

        // Hosting a child under an FAQ article needs PUBLISHFAQ, so the dropdown drops
        // this parent. It is still the edge to remove, or the move would duplicate.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        $content = $this->callController($child, $parent)->getContent();

        $this->assertMatchesRegularExpression(
            '/name="from_parent_id"\s+value="' . $parent . '"/',
            $content,
        );
        // Not vacuous: it really is refused as a destination.
        $this->assertDoesNotMatchRegularExpression(
            "/<option value='" . $parent . "'/",
            $content,
        );
    }

    private function callController(int $id, int $from_parent_id): Response
    {
        $request = Request::create(
            '/Knowbase/' . $id . '/MoveModal',
            'GET',
            ['from_parent_id' => $from_parent_id],
        );
        return (new MoveModalController())->__invoke($id, $request);
    }

    /** @param int[] $parents */
    private function makeArticle(array $parents = []): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'     => 'Move modal ' . $this->getUniqueString(),
            'answer'   => '<p>x</p>',
            '_parents' => $parents,
        ])->getID();
    }
}
