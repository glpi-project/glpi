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

use Ajax;
use Computer;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Symfony\Component\DomCrawler\Crawler;

use function Safe\ob_get_clean;
use function Safe\ob_start;

class AjaxTest extends GLPITestCase
{
    /**
     * @return iterable<string, array{stored_tab: ?string, initial_tab: string}>
     */
    public static function initialTabProvider(): iterable
    {
        yield 'no tab stored in the session' => [
            'stored_tab'  => null,
            'initial_tab' => 'Computer$main',
        ];

        yield 'tab stored in the session' => [
            'stored_tab'  => 'Log$1',
            'initial_tab' => 'Log$1',
        ];

        yield 'stored tab that is not part of the tabs' => [
            'stored_tab'  => 'Unknown$1',
            'initial_tab' => 'Computer$main',
        ];
    }

    #[DataProvider('initialTabProvider')]
    public function testCreateTabsDisplaysOnlyThePaneOfTheInitialTab(?string $stored_tab, string $initial_tab): void
    {
        $_SERVER['REQUEST_URI'] = Computer::getFormURLWithID(1);
        if ($stored_tab !== null) {
            Session::setActiveTab(Computer::class, $stored_tab);
        }

        $tabs = [];
        foreach (['Computer$main', 'Item_Disk$1', 'Log$1'] as $key) {
            $tabs[$key] = [
                'title'  => $key,
                'url'    => '/ajax/common.tabs.php',
                'params' => '_glpi_tab=' . $key,
            ];
        }

        ob_start();
        Ajax::createTabs('tabspanel', 'tabcontent', $tabs, Computer::class, 1);
        $crawler = new Crawler(ob_get_clean());

        // The pane of the initial tab is displayed as soon as it is rendered, the other ones are faded in by Bootstrap.
        $expected_classes = [];
        foreach (array_keys($tabs) as $key) {
            $expected_classes[$key] = $key === $initial_tab ? 'tab-pane fade show active' : 'tab-pane fade';
        }

        $classes = [];
        $crawler->filter('a[data-bs-toggle="tab"]')->each(function (Crawler $link) use ($crawler, &$classes) {
            $pane = $crawler->filter((string) $link->attr('data-bs-target'));
            $this->assertCount(1, $pane);
            $classes[$link->attr('aria-label')] = $pane->attr('class');
        });

        $this->assertSame($expected_classes, $classes);
    }
}
