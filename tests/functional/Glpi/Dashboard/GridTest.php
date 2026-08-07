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
use Glpi\Tests\DbTestCase;

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
}
