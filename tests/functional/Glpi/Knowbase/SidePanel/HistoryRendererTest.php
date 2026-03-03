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
use Glpi\Knowbase\SidePanel\HistoryRenderer;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\DomCrawler\Crawler;

final class HistoryRendererTest extends DbTestCase
{
    public function testRevisionsAreRendered(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-01 12:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Original content',
        ]);

        $this->setCurrentTime("2026-01-01 14:00:00");
        $this->updateItem(KnowbaseItem::class, $kb->getID(), [
            'name' => 'Updated title',
        ]);

        $this->setCurrentTime("2026-01-01 16:00:00");
        $kb->getFromDB($kb->getID());
        $revisions = $this->renderRevisions($kb);

        $revisionNodes = $revisions->filter('[data-testid=history-event]');
        $this->assertEquals(2, $revisionNodes->count());

        $renamed = $revisionNodes->eq(0);
        $this->assertStringContainsString('Renamed', $renamed->text());
        $tooltip = $renamed->filter('[data-bs-toggle="tooltip"]')->first();
        $this->assertStringContainsString('Original title', $tooltip->attr('title'));
        $this->assertStringContainsString('Updated title', $tooltip->attr('title'));
        $this->assertEquals(0, $renamed->filter('[data-glpi-revert-revision]')->count());

        $currentVersion = $revisionNodes->eq(1);
        $this->assertStringContainsString('Current version', $currentVersion->text());
        $this->assertEquals(0, $currentVersion->filter('[data-glpi-revert-revision]')->count());
    }

    private function renderRevisions(KnowbaseItem $kb): Crawler
    {
        $renderer = new HistoryRenderer();
        $html = TemplateRenderer::getInstance()->render(
            $renderer->getTemplate(),
            $renderer->getParams($kb),
        );

        return new Crawler($html);
    }
}
