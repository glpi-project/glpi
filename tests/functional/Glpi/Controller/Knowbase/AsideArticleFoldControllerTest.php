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
use Glpi\Controller\Knowbase\AsideArticleFoldController;
use Glpi\Http\Firewall;
use Glpi\Http\SessionManager;
use Glpi\Kernel\Listener\ControllerListener\FirewallStrategyListener;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use User;

class AsideArticleFoldControllerTest extends DbTestCase
{
    private function callController(int $id, bool $collapsed): void
    {
        $request = Request::create(
            '/Knowbase/Aside/Article/' . $id . '/Fold',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['collapsed' => $collapsed]),
        );
        (new AsideArticleFoldController())->__invoke($id, $request);
    }

    private function makeArticle(): int
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'   => 'Aside fold ' . $this->getUniqueString(),
            'answer' => '<p>x</p>',
        ])->getID();
    }

    private function applyFirewallStrategy(int $id): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            new AsideArticleFoldController(),
            Request::create('/Knowbase/Aside/Article/' . $id . '/Fold', 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
        );

        (new FirewallStrategyListener(new Firewall(), new SessionManager()))->onKernelController($event);
    }

    private function makeFaqArticleVisibleToHelpdesk(): int
    {
        $entity = $this->getTestRootEntity(only_id: true);

        $article = $this->createItem(KnowbaseItem::class, [
            'name'        => 'Aside fold helpdesk ' . $this->getUniqueString(),
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
    public function testHelpdeskProfileCanFoldFaqArticle(): void
    {
        $this->login();
        $id = $this->makeFaqArticleVisibleToHelpdesk();

        $this->login('post-only', 'postonly');

        $this->applyFirewallStrategy($id);
        $this->callController($id, false);

        $this->assertContains($id, KnowbaseItem::getUnfoldedIdsForCurrentUser());
    }

    public function testReadableArticleFoldStateIsPersisted(): void
    {
        $this->login();
        $id = $this->makeArticle();

        $this->callController($id, false);
        $this->assertContains($id, KnowbaseItem::getUnfoldedIdsForCurrentUser());

        $this->callController($id, true);
        $this->assertNotContains($id, KnowbaseItem::getUnfoldedIdsForCurrentUser());
    }

    public function testUnknownArticleIsIgnored(): void
    {
        $this->login();

        $this->callController(0, false);
        $this->callController(PHP_INT_MAX, false);

        $unfolded = KnowbaseItem::getUnfoldedIdsForCurrentUser();
        $this->assertNotContains(0, $unfolded);
        $this->assertNotContains(PHP_INT_MAX, $unfolded);
    }

    public function testArticleNotVisibleToUserIsIgnored(): void
    {
        $this->login();
        // No visibility target: a Technician has READ but cannot see it.
        $id = $this->makeArticle();

        $this->login('tech', 'tech');
        $this->callController($id, false);

        $this->assertNotContains($id, KnowbaseItem::getUnfoldedIdsForCurrentUser());
    }
}
