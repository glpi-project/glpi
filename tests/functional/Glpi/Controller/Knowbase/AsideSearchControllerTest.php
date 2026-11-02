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

use Glpi\Controller\Knowbase\AsideSearchController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Knowbase\Aside\SearchResultsBuilder;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Request;

class AsideSearchControllerTest extends DbTestCase
{
    /**
     * @param array<string, string|int> $query
     */
    private function callController(array $query): string
    {
        $controller = new AsideSearchController();
        return $controller->__invoke(new Request($query))->getContent();
    }

    public function testRendersOneRowPerMatchingArticle(): void
    {
        $this->login();

        $id = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Reset a password zqxjctrlone',
            'answer' => '<p>Open the user form</p>',
        ])->getID();
        $this->createItem(KnowbaseItem::class, [
            'name'   => 'Unrelated article',
            'answer' => '<p>Content</p>',
        ]);

        $html = $this->callController(['contains' => 'zqxjctrlone']);

        $this->assertStringContainsString('data-glpi-kb-article-id="' . $id . '"', $html);
        $this->assertStringContainsString('Reset a password zqxjctrlone', $html);
        $this->assertStringContainsString('Open the user form', $html);
        $this->assertStringNotContainsString('Unrelated article', $html);

        // A single page of results: nothing left for the reader to scroll to.
        $this->assertStringNotContainsString('data-glpi-kb-aside-search-load-more', $html);
    }

    public function testFullPageOfResultsIsFollowedByTheNextOne(): void
    {
        $this->login();

        for ($i = 0; $i <= SearchResultsBuilder::PAGE_SIZE; $i++) {
            $this->createItem(KnowbaseItem::class, [
                'name'   => "Article $i zqxjctrltwo",
                'answer' => '<p>Content</p>',
            ]);
        }

        $html = $this->callController(['contains' => 'zqxjctrltwo']);

        $this->assertStringContainsString('data-glpi-kb-aside-search-load-more', $html);
        $this->assertStringContainsString(
            'data-glpi-kb-aside-search-next-offset="' . SearchResultsBuilder::PAGE_SIZE . '"',
            $html,
        );
        // The marker carries the search it belongs to, so a page arriving late
        // can be told apart from the search the reader is now running.
        $this->assertStringContainsString('data-glpi-kb-aside-search-contains="zqxjctrltwo"', $html);

        // Asking for that next page returns the last result and stops there.
        $next = $this->callController([
            'contains' => 'zqxjctrltwo',
            'offset'   => SearchResultsBuilder::PAGE_SIZE,
        ]);
        $this->assertSame(1, substr_count($next, 'data-glpi-kb-article-id='));
        $this->assertStringNotContainsString('data-glpi-kb-aside-search-load-more', $next);
    }

    public function testEmptySearchIsRejected(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController(['contains' => '   ']);
    }

    /**
     * Only the offsets this controller hands out are valid: any other one would
     * return results overlapping the page the reader already has.
     */
    public function testOffsetOutsideOfThePageBoundariesIsRejected(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController(['contains' => 'anything', 'offset' => 7]);
    }

    public function testNegativeOffsetIsRejected(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController(['contains' => 'anything', 'offset' => -50]);
    }
}
