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

use Glpi\Controller\Knowbase\HistoryPageController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Knowbase\SidePanel\HistoryRenderer;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;

class HistoryPageControllerTest extends DbTestCase
{
    private function callController(int $id, int $offset): string
    {
        $controller = new HistoryPageController();
        return (string) $controller->__invoke($id, $offset)->getContent();
    }

    private function createArticle(): int
    {
        // The history panel is only available to users that may edit the
        // article.
        $this->login();
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] |= UPDATE;

        return $this->createItem(KnowbaseItem::class, [
            'name'         => 'Paginated history',
            'answer'       => '<p>Content</p>',
            'entities_id'  => $this->getTestRootEntity(only_id: true),
            'is_recursive' => 1,
        ])->getID();
    }

    public function testPageBoundaryOffsetsAreServed(): void
    {
        $id = $this->createArticle();

        // The first page holds the events of a freshly created article.
        $this->assertStringContainsString(
            'data-testid="history-event"',
            $this->callController($id, 0)
        );

        // The next boundary is past the end of this short history: an empty
        // page is a valid answer, not an error.
        $this->assertStringNotContainsString(
            'data-testid="history-event"',
            $this->callController($id, HistoryRenderer::PAGE_SIZE)
        );
    }

    /**
     * Only the offsets handed out by the "load more" marker can be served: any
     * other value would return events overlapping the previous page.
     */
    public function testOffsetsThatAreNotPageBoundariesAreRejected(): void
    {
        $id = $this->createArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($id, 7);
    }

    /**
     * An offset large enough to overflow to a float used to reach the query
     * builder and yield an invalid `LIMIT` clause.
     */
    public function testOverflowingOffsetIsRejected(): void
    {
        $id = $this->createArticle();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($id, PHP_INT_MAX);
    }
}
