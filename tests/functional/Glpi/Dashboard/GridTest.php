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

namespace tests\units\Glpi\Dashboard;

use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Grid;
use Glpi\Dashboard\Item;
use Glpi\Dashboard\Right;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GridTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->login();
        Grid::$all_dashboards = [];
        $this->clearDashboardSession();
        $this->clearDashboardConfig();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Grid::$all_dashboards = [];
        $this->clearDashboardSession();
        $this->clearDashboardConfig();
    }

    private function clearDashboardSession(): void
    {
        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with($key, 'glpidefault_dashboard_')) {
                $_SESSION[$key] = '';
            }
        }
        unset($_SESSION['last_dashboards']);
        unset($_REQUEST['_target']);
    }

    private function clearDashboardConfig(): void
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI as $key => $value) {
            if (str_starts_with($key, 'default_dashboard_')) {
                $CFG_GLPI[$key] = '';
            }
        }
    }

    private function clearAllDashboardsFromDB(): void
    {
        global $DB;

        $DB->delete(Right::getTable(), ['NOT' => ['id' => 0] ]);
        $DB->delete(Item::getTable(), ['NOT' => ['id' => 0] ]);
        $DB->delete(Dashboard::getTable(), ['NOT' => ['id' => 0] ]);
        Dashboard::$all_dashboards = [];
        Grid::$all_dashboards = [];
    }

    public function testGetDefaultDashboardForMenuStrictWithNoDefaultValue(): void
    {
        Grid::$all_dashboards = [
            'test_dashboard' => ['key' => 'test_dashboard', 'name' => 'Test', 'context' => 'core'],
        ];

        $result = Grid::getDefaultDashboardForMenu('central', true);
        $this->assertSame('', $result);
    }

    public function testGetDefaultDashboardForMenutWithSessionDefaultValue(): void
    {
        global $CFG_GLPI;

        $_SESSION['glpidefault_dashboard_central'] = 'test_dashboard';
        $CFG_GLPI['default_dashboard_central'] = 'test_dashboard2';

        $result = Grid::getDefaultDashboardForMenu('central', true);
        $this->assertSame('test_dashboard', $result);
    }

    public function testGetDefaultDashboardForMenuWithSessionDisabled(): void
    {
        global $CFG_GLPI;

        $_SESSION['glpidefault_dashboard_central'] = 'disabled';
        $CFG_GLPI['default_dashboard_central'] = 'test_dashboard';

        $result = Grid::getDefaultDashboardForMenu('central', true);
        $this->assertSame('', $result);
    }

    public function testGetDefaultDashboardForMenuUsesConfigValue(): void
    {
        global $CFG_GLPI;

        $CFG_GLPI['default_dashboard_central'] = 'test_dashboard';

        $result = Grid::getDefaultDashboardForMenu('central', true);
        $this->assertSame('test_dashboard', $result);
    }

    public function testGetDefaultDashboardForMenuNotStrictWithNoDefaultValue(): void
    {
        $this->clearAllDashboardsFromDB();

        $this->createItem(Dashboard::class, [
            'key'     => 'mini_tickets',
            'name'    => 'Mini Tickets',
            'context' => 'mini_core',
        ]);
        $this->createItem(Dashboard::class, [
            'key'     => 'test_dashboard',
            'name'    => 'Test Dashboard',
            'context' => 'core',
        ]);
        Dashboard::$all_dashboards = [];

        $result = Grid::getDefaultDashboardForMenu('central', false);
        $this->assertNotSame('mini_tickets', $result);
        $this->assertSame('test_dashboard', $result);

        $this->clearAllDashboardsFromDB();
        $this->createItem(Dashboard::class, [
            'key'     => 'mini_tickets',
            'name'    => 'Mini Tickets',
            'context' => 'mini_core',
        ]);
        Dashboard::$all_dashboards = [];

        $result = Grid::getDefaultDashboardForMenu('central', false);
        $this->assertSame('', $result);
    }

    public function testGetDefaultDashboardWithNoStrictForMenuMiniTicket(): void
    {
        $this->clearAllDashboardsFromDB();

        $this->createItem(Dashboard::class, [
            'key'     => 'mini_tickets',
            'name'    => 'Mini Tickets',
            'context' => 'mini_core',
        ]);
        Dashboard::$all_dashboards = [];

        $result = Grid::getDefaultDashboardForMenu('mini_ticket', false);
        $this->assertSame('mini_tickets', $result);
    }

    public static function deniedProvider(): array
    {
        throw new AccessDeniedHttpException();
    }

    public static function deniedWithMessageProvider(): array
    {
        $exception = new AccessDeniedHttpException();
        $exception->setMessageToDisplay('Custom denial message');
        throw $exception;
    }

    public static function accessDeniedProvider(): iterable
    {
        yield 'default message' => [
            'provider' => self::class . '::deniedProvider',
            'expected' => "You don't have permission to perform this action.",
        ];
        yield 'message to display' => [
            'provider' => self::class . '::deniedWithMessageProvider',
            'expected' => 'Custom denial message',
        ];
    }

    public function testShowSendsTheFiltersOfTheDisplayedDashboard(): void
    {
        global $CFG_GLPI;

        (new Dashboard('assistance'))->saveFilter('{"requesttype":"2"}');
        (new Dashboard('central'))->saveFilter('{"itilcategory":"1"}');
        $_REQUEST['_target'] = $CFG_GLPI['root_doc'] . '/front/dashboard_helpdesk.php';

        $show = static function (): string {
            Grid::$all_dashboards = [];
            \ob_start();
            (new Grid('assistance'))->show();
            return (string) \ob_get_clean();
        };

        $html = $show();
        $this->assertStringContainsString('"current":"assistance"', $html);
        $this->assertStringContainsString('"filters":{"requesttype":"2"}', $html);

        // The last dashboard viewed on the page is displayed instead, with its own filters.
        $_SESSION['last_dashboards']['/front/dashboard_helpdesk.php'] = 'central';
        $html = $show();
        $this->assertStringContainsString('"current":"central"', $html);
        $this->assertStringContainsString('"filters":{"itilcategory":"1"}', $html);
    }

    public function testShowLoadsTheCardsInBatchesExceptInADebugSession(): void
    {
        $show = static function (): string {
            Grid::$all_dashboards = [];
            \ob_start();
            (new Grid('central'))->show();
            return (string) \ob_get_clean();
        };

        $html = $show();
        $this->assertStringContainsString('"batches":4', $html);
        // The page sends the requests itself, without waiting for its scripts.
        $this->assertStringContainsString('window.glpi_dashboard_early_cards', $html);

        // Each card keeps its own request, and its own profile, in a debug session.
        $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;
        try {
            $html = $show();
            $this->assertStringContainsString('"batches":0', $html);
            $this->assertStringNotContainsString('window.glpi_dashboard_early_cards', $html);
        } finally {
            $_SESSION['glpi_use_mode'] = \Session::NORMAL_MODE;
        }
    }

    #[DataProvider('accessDeniedProvider')]
    public function testGetCardHtmlWithAccessDeniedProvider(string $provider, string $expected): void
    {
        $grid = $this->getMockBuilder(Grid::class)
            ->onlyMethods(['getAllDasboardCards'])
            ->getMock();
        $grid->expects($this->atLeastOnce())->method('getAllDasboardCards')->willReturn([
            'denied_card' => [
                'widgettype' => ['bigNumber'],
                'label'      => 'Denied card',
                'provider'   => $provider,
            ],
        ]);

        $html = $grid->getCardHtml('denied_card', ['args' => ['widgettype' => 'bigNumber']]);

        $this->assertStringContainsString(\htmlescape($expected), $html);
        $this->assertStringNotContainsString('Error rendering card!', $html);
    }
}
