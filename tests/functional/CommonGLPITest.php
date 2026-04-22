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

namespace tests\units;

use Computer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

class CommonGLPITest extends DbTestCase
{
    public static function displayProvider(): iterable
    {
        $computer_id = \getItemByTypeName(Computer::class, '_test_pc01', true);

        yield [
            'credentials' => [TU_USER, TU_PASS],
            'itemtype'    => Computer::class,
            'items_id'    => -1,
            'exception'   => null,
        ];

        yield [
            'credentials' => [TU_USER, TU_PASS],
            'itemtype'    => Computer::class,
            'items_id'    => $computer_id,
            'exception'   => null,
        ];

        yield [
            'credentials' => [TU_USER, TU_PASS],
            'itemtype'    => Computer::class,
            'items_id'    => 999999,
            'exception'   => new NotFoundHttpException(),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'itemtype'    => Computer::class,
            'items_id'    => -1,
            'exception'   => new AccessDeniedHttpException(),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'itemtype'    => Computer::class,
            'items_id'    => $computer_id,
            'exception'   => new AccessDeniedHttpException(),
        ];

        yield [
            'credentials' => ['post-only', 'postonly'],
            'itemtype'    => Computer::class,
            'items_id'    => 999999,
            'exception'   => new NotFoundHttpException(),
        ];
    }

    #[DataProvider('displayProvider')]
    public function testDisplayFullPageForItem(array $credentials, string $itemtype, int $items_id, ?\Throwable $exception): void
    {
        $_SERVER['REQUEST_URI'] = $itemtype::getFormURLWithID($items_id);
        $_GET["id"]             = $items_id;

        $this->login(...$credentials);

        if ($exception !== null) {
            $this->expectExceptionObject($exception);
        } else {
            // Tests that something is sent to output
            $this->expectOutputRegex('/.+/');
        }

        $item = new $itemtype();
        $item->display(['id' => $items_id]);
    }

    public function testShowFriendlyNameBadgeInFormIsDisplayedByDefault(): void
    {
        $this->login();

        $computer_id = getItemByTypeName(Computer::class, '_test_pc01', true);
        $_SERVER['REQUEST_URI'] = Computer::getFormURLWithID($computer_id);
        $_GET['id'] = $computer_id;

        $computer = new Computer();
        ob_start();
        $computer->display(['id' => $computer_id]);
        $html = ob_get_clean();

        $crawler = new Crawler($html);
        $this->assertCount(1, $crawler->filter('#header-friendlyname'));
    }

    public function testShowFriendlyNameBadgeInFormIsHiddenForKnowbaseItem(): void
    {
        $this->login();

        $kb_item = $this->createItem(KnowbaseItem::class, [
            'name'   => "Test article",
            'answer' => 'Test answer',
        ]);
        $_SERVER['REQUEST_URI'] = KnowbaseItem::getFormURLWithID($kb_item->getID());
        $_GET['id'] = $kb_item->getID();

        ob_start();
        $kb_item->display(['id' => $kb_item->getID()]);
        $html = ob_get_clean();

        $crawler = new Crawler($html);
        $this->assertCount(0, $crawler->filter('#header-friendlyname'));
    }
}
