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
use Glpi\Knowbase\History\HistoryBuilder;
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

    public function testHistoryIsRenderedOnePageAtATime(): void
    {
        $this->login();
        $this->setCurrentTime("2026-01-01 00:00:00");

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Original content',
        ]);

        // Two events per update: a revision and a "Renamed" log entry.
        $updates = HistoryRenderer::PAGE_SIZE;
        $base_time = strtotime("2026-01-01 00:00:00");
        for ($i = 1; $i <= $updates; $i++) {
            $this->setCurrentTime(date("Y-m-d H:i:s", $base_time + ($i * HOUR_TIMESTAMP)));
            $this->updateItem(KnowbaseItem::class, $kb->getID(), [
                'name'   => sprintf('Title %d', $i),
                'answer' => sprintf('Content %d', $i),
            ]);
        }

        // One revision and one "Renamed" event per update, plus the single
        // "Current version" event.
        $kb->getFromDB($kb->getID());
        $expected_labels = array_map(
            static fn($event) => $event->getLabel(),
            (new HistoryBuilder($kb))->buildHistory()->getEvents()
        );
        $total = count($expected_labels);
        $this->assertSame(1 + ($updates * 2), $total);

        // First page: as many events as the page size, and a marker pointing to
        // the next one.
        $first_page = $this->renderRevisions($kb);
        $this->assertCount(
            HistoryRenderer::PAGE_SIZE,
            $first_page->filter('[data-testid=history-event]')
        );
        $marker = $first_page->filter('[data-glpi-history-load-more]');
        $this->assertCount(1, $marker);
        $this->assertEquals(HistoryRenderer::PAGE_SIZE, $marker->attr('data-glpi-history-next-offset'));
        $this->assertEquals($kb->getID(), $marker->attr('data-glpi-kb-id'));

        // Only the newest event of the whole history is highlighted.
        $this->assertCount(1, $first_page->filter('.step-item.active'));
        $this->assertCount(1, $first_page->filter('[data-glpi-current-version]'));

        // Second page: the next events, no highlighted event, and a marker as
        // there are still events left.
        $second_page = $this->renderRevisionsPage($kb, HistoryRenderer::PAGE_SIZE);
        $this->assertCount(
            HistoryRenderer::PAGE_SIZE,
            $second_page->filter('[data-testid=history-event]')
        );
        $this->assertCount(0, $second_page->filter('.step-item.active'));
        $this->assertEquals(
            HistoryRenderer::PAGE_SIZE * 2,
            $second_page->filter('[data-glpi-history-load-more]')->attr('data-glpi-history-next-offset')
        );

        // Last page: the remaining events, and no marker.
        $last_page = $this->renderRevisionsPage($kb, HistoryRenderer::PAGE_SIZE * 2);
        $this->assertCount(
            $total - (HistoryRenderer::PAGE_SIZE * 2),
            $last_page->filter('[data-testid=history-event]')
        );
        $this->assertCount(0, $last_page->filter('[data-glpi-history-load-more]'));

        // Put together, the pages hold the whole history, in order and without
        // any duplicated or missing event.
        $labels = [];
        foreach ([$first_page, $second_page, $last_page] as $page) {
            foreach ($page->filter('[data-testid=history-event] .h4') as $node) {
                $labels[] = trim($node->textContent);
            }
        }
        $this->assertSame($expected_labels, $labels);
    }

    public function testPageBeyondTheHistoryIsEmpty(): void
    {
        $this->login();

        $kb = $this->createItem(KnowbaseItem::class, [
            'users_id' => 2,
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name' => 'Original title',
            'answer' => 'Original content',
        ]);
        $kb->getFromDB($kb->getID());

        $page = $this->renderRevisionsPage($kb, HistoryRenderer::PAGE_SIZE);

        $this->assertCount(0, $page->filter('[data-testid=history-event]'));
        $this->assertCount(0, $page->filter('[data-glpi-history-load-more]'));
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

    private function renderRevisionsPage(KnowbaseItem $kb, int $offset): Crawler
    {
        $renderer = new HistoryRenderer();
        $html = TemplateRenderer::getInstance()->render(
            $renderer->getPageTemplate(),
            $renderer->getPageParams($kb, $offset),
        );

        return new Crawler('<ul>' . $html . '</ul>');
    }
}
