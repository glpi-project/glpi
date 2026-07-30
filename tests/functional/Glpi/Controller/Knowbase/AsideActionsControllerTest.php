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

use Glpi\Controller\Knowbase\AsideActionsController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;

class AsideActionsControllerTest extends DbTestCase
{
    private function callController(int $id): string
    {
        $controller = new AsideActionsController();
        return $controller->__invoke($id)->getContent();
    }

    public function testReturnsAllActionsForManagedArticle(): void
    {
        // Grant UPDATE + PURGE so all three actions are rendered (favorites is
        // available to the super-admin via CREATE).
        $this->login();
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] |= UPDATE | PURGE;

        // Article must live in an active entity for the delete (PURGE) gate.
        $id = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Managed article',
            'answer'       => '<p>Content</p>',
            'entities_id'  => $this->getTestRootEntity(only_id: true),
            'is_recursive' => 1,
        ])->getID();

        $html = $this->callController($id);

        $this->assertStringContainsString('data-glpi-kb-action="TOGGLE_FAVORITE"', $html);
        $this->assertStringContainsString('data-glpi-kb-action="TOGGLE_VALUE"', $html);
        $this->assertStringContainsString('data-glpi-kb-action="DELETE_ARTICLE"', $html);
        $this->assertStringContainsString('data-glpi-kb-action-param-field="is_faq"', $html);
    }

    public function testNonExistentArticleReturnsNotFound(): void
    {
        $this->login();

        $this->expectException(NotFoundHttpException::class);
        $this->callController(99999);
    }

    public function testUnviewableArticleReturnsAccessDenied(): void
    {
        // Article created by the super-admin with no shared visibility.
        $this->login();
        $id = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Private article',
            'answer'       => '<p>Secret</p>',
            'entities_id'  => $this->getTestRootEntity(only_id: true),
            'is_recursive' => 1,
        ])->getID();

        // A regular user without visibility access cannot view it.
        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($id);
    }
}
