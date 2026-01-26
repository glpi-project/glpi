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

namespace tests\units\Glpi\Knowbase\SidePanel;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Knowbase\SidePanel\CommentsRenderer;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Comment;
use Symfony\Component\DomCrawler\Crawler;

final class CommentsRendererTest extends DbTestCase
{
    public function testCommentsAreRendered(): void
    {
        $this->setCurrentTime("2026-01-01 12:00:00");

        // Arrange: create a KB entry with some comments
        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'My article',
        ]);
        $this->createItems(KnowbaseItem_Comment::class, [
            [
                'comment' => 'First comment',
                'users_id' => 2,
                'knowbaseitems_id' => $kb->getID(),
            ],
        ]);
        $this->setCurrentTime("2026-01-01 14:00:00");
        $this->createItems(KnowbaseItem_Comment::class, [
            [
                'comment' => 'Second comment',
                'users_id' => 9999999, // Simulate deleted user
                'knowbaseitems_id' => $kb->getID(),
            ],
        ]);

        // Act: render the comments
        $this->setCurrentTime("2026-01-01 16:00:00");
        $comments = $this->renderComments($kb);

        // Assert: validate the expected content for both comments
        $this->assertEquals(
            [
                "glpi · 4 hours ago",
                "Deleted user · 2 hours ago",
            ],
            $comments->filter('[data-testid=comment-header]')->each(fn($node) => $node->text()),
        );
        $this->assertEquals(
            [
                "First comment",
                "Second comment",
            ],
            $comments->filter('[data-testid=comment-content]')->each(fn($node) => $node->text()),
        );
    }

    private function renderComments(KnowbaseItem $kb): Crawler
    {
        $comments = new CommentsRenderer();
        $html = TemplateRenderer::getInstance()->render(
            $comments->getTemplate(),
            $comments->getParams($kb),
        );

        return new Crawler($html);
    }
}
