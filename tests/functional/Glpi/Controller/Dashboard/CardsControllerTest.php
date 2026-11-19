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

namespace tests\units\Glpi\Controller\Dashboard;

use Glpi\Controller\Dashboard\CardsController;
use Glpi\Dashboard\Grid;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Safe\json_decode;
use function Safe\json_encode;

class CardsControllerTest extends DbTestCase
{
    public static function invalidPayloadProvider(): iterable
    {
        yield 'not JSON' => ['{'];
        yield 'no dashboard' => ['{"cards":[]}'];
        yield 'no cards' => ['{"dashboard":"central"}'];
        yield 'cards that are not a list' => ['{"dashboard":"central","cards":"all"}'];
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadIsRejected(string $content): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        (new CardsController())(Request::create('/Dashboard/Cards', 'POST', content: $content));
    }

    public function testUnknownDashboardIsDenied(): void
    {
        $this->login();

        $this->expectException(AccessDeniedHttpException::class);
        (new CardsController())($this->getCardsRequest('not_a_dashboard', []));
    }

    public function testDashboardWithoutRightIsDenied(): void
    {
        $this->login('post-only', 'postonly');

        $this->expectException(AccessDeniedHttpException::class);
        (new CardsController())($this->getCardsRequest('central', []));
    }

    public function testResponseIsStreamedAsText(): void
    {
        $this->login();

        $response = (new CardsController())($this->getCardsRequest('central', []));

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function testCardsAreRenderedOnePerLine(): void
    {
        $this->login();

        $cards = [
            ['gridstack_id' => 'first', 'card_id' => 'bn_count_Computer', 'args' => ['widgettype' => 'bigNumber', 'color' => '#e0e0e0']],
            // Entries that are not a card of the grid are ignored
            ['card_id' => 'bn_count_Computer'],
            'not a card',
            ['gridstack_id' => 'second', 'card_id' => 'not_a_card', 'args' => []],
            ['gridstack_id' => 'third', 'card_id' => 'bn_count_Monitor', 'args' => ['widgettype' => 'bigNumber']],
        ];

        $lines = \iterator_to_array(
            $this->callPrivateMethod(new CardsController(), 'getCardsLines', new Grid('central'), 'central', $cards),
            false
        );

        $this->assertCount(3, $lines);
        $results = [];
        foreach ($lines as $line) {
            $this->assertStringNotContainsString("\n", $line);
            $results[] = json_decode($line, true);
        }
        $this->assertSame(['first', 'second', 'third'], \array_column($results, 'id'));
        $this->assertStringContainsString('big-number', $results[0]['html']);
        $this->assertStringContainsString('empty-card', $results[1]['html']);
        $this->assertStringContainsString('big-number', $results[2]['html']);
    }

    public function testCardsAreRenderedWithoutBrowserCacheHeaders(): void
    {
        $this->login();

        // The response is streamed: a card must not try to send its own cache headers
        $grid = $this->createMock(Grid::class);
        $grid->expects($this->once())
            ->method('getCardHtml')
            ->with('bn_count_Computer', $this->callback(static fn(array $options): bool => ($options['force'] ?? false) === true))
            ->willReturn('<div class="card"></div>');

        $lines = \iterator_to_array(
            $this->callPrivateMethod(new CardsController(), 'getCardsLines', $grid, 'central', [
                ['gridstack_id' => 'first', 'card_id' => 'bn_count_Computer', 'args' => ['widgettype' => 'bigNumber']],
            ]),
            false
        );

        $this->assertSame(['{"id":"first","html":"<div class=\\"card\\"></div>"}'], $lines);
    }

    private function getCardsRequest(string $dashboard, array $cards): Request
    {
        return Request::create('/Dashboard/Cards', 'POST', content: json_encode(['dashboard' => $dashboard, 'cards' => $cards]));
    }
}
