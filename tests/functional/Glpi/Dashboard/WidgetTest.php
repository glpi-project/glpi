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

use Glpi\Dashboard\Grid;
use Glpi\Dashboard\Widget;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Ticket;

/* Test for inc/dashboard/widget.class.php */

class WidgetTest extends DbTestCase
{
    public function tearDown(): void
    {
        Grid::$embed = false;
        parent::tearDown();
    }

    public function testSearchShowListInEmbedModeReturnsResults(): void
    {
        // Create a ticket while logged in so it exists in the DB
        $this->login();
        $this->createItem(Ticket::class, [
            'name'    => 'Embed dashboard test ticket',
            'content' => 'Test',
            'status'  => Ticket::INCOMING,
        ]);

        // Simulate embed session manually (mirrors what initEmbedSession does,
        // without going through initEmbed which requires a valid token)
        global $CFG_GLPI;
        Grid::$embed = true;
        Session::destroy();
        Session::start();
        $_SESSION['glpiactive_entity']           = 0;
        $_SESSION['glpiactive_entity_recursive'] = 1;
        $_SESSION['glpiname']                    = 'embed_dashboard';
        $_SESSION['glpigroups']                  = [];
        $_SESSION['glpiactiveentities']          = getSonsOf('glpi_entities', 0);
        $_SESSION['glpiactiveentities_string']   = "'" . implode("', '", $_SESSION['glpiactiveentities']) . "'";
        $_SESSION['glpi_use_mode']               = Session::NORMAL_MODE;
        foreach ($CFG_GLPI['user_pref_field'] as $field) {
            if (array_key_exists($field, $CFG_GLPI)) {
                $_SESSION["glpi$field"] = $CFG_GLPI[$field];
            }
        }

        $this->assertArrayHasKey('glpilist_limit', $_SESSION);

        $html = Widget::searchShowList([
            'itemtype'   => Ticket::class,
            's_criteria' => [],
            'limit'      => 20,
            'color'      => '#CCCCCC',
        ]);

        $this->assertStringContainsString('Embed dashboard test ticket', $html);
    }

    public function testSearchShowListWithoutEmbedModeReturnsNoResults(): void
    {
        // Create a ticket while logged in
        $this->login();
        $this->createItem(Ticket::class, [
            'name'    => 'Embed dashboard test ticket',
            'content' => 'Test',
            'status'  => Ticket::INCOMING,
        ]);

        // Simulate a session without any profile (as initEmbedSession used to leave it)
        Session::destroy();
        Session::start();
        $_SESSION['glpiactiveentities']        = [0];
        $_SESSION['glpiactiveentities_string'] = "'0'";
        $_SESSION['glpiactive_entity']         = 0;
        $_SESSION['glpigroups']                = [];
        $_SESSION['glpilist_limit']            = 20;
        $_SESSION['glpiname']                  = '';
        // Grid::$embed stays false

        $html = Widget::searchShowList([
            'itemtype'   => Ticket::class,
            's_criteria' => [],
            'limit'      => 20,
            'color'      => '#CCCCCC',
        ]);

        $this->assertStringNotContainsString('Embed dashboard test ticket', $html);
    }


    public function testGetAllTypes()
    {
        $types = Widget::getAllTypes();

        $this->assertNotEmpty($types);
        foreach ($types as $specs) {
            $this->assertArrayHasKey('label', $specs);
            $this->assertArrayHasKey('function', $specs);
            $this->assertArrayHasKey('image', $specs);
        }
    }


    public static function palettes()
    {
        return [
            [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 4,
                'revert'    => true,
                'expected'  => [
                    'names'  => ['a', 'b', 'c', 'd'],
                    'colors' => [
                        '#a6a6a6',
                        '#808080',
                        '#595959',
                        '#333333',
                    ],
                ],
            ], [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 4,
                'revert'    => false,
                'expected'  => [
                    'names'  => ['a', 'b', 'c', 'd'],
                    'colors' => [
                        '#595959',
                        '#808080',
                        '#a6a6a6',
                        '#cccccc',
                    ],
                ],
            ], [
                'bg_color'  => "#FFFFFF",
                'nb_series' => 1,
                'revert'    => true,
                'expected'  => [
                    'names'  => ['a'],
                    'colors' => [
                        '#999999',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('palettes')]
    public function testGetGradientPalette(
        string $bg_color,
        int $nb_series,
        bool $revert,
        array $expected
    ) {
        $this->assertEquals(
            $expected,
            Widget::getGradientPalette($bg_color, $nb_series, $revert)
        );
    }
}
