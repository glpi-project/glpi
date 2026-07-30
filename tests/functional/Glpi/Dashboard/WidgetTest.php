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
use Glpi\Exception\Http\AccessDeniedHttpException;
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

    /**
     * Build an embed session using the exact profile/user the dashboard
     * was "shared" as, mirroring what Grid::initEmbed() does once the token
     * has been validated.
     */
    private function initEmbedSessionAs(array $params): void
    {
        Grid::$embed = true;
        (new Grid(''))->initEmbedSession($params);
    }

    public function testSearchShowListInEmbedModeReturnsResults(): void
    {
        // Log in as a user with full ticket rights, and capture the exact context
        // (profile/user/entity) that would have been recorded when they shared
        // the dashboard.
        $this->login();
        $profiles_id  = $_SESSION['glpiactiveprofile']['id'];
        $users_id     = Session::getLoginUserID();
        $entities_id  = $_SESSION['glpiactive_entity'];
        $is_recursive = $_SESSION['glpiactive_entity_recursive'];

        // Create the ticket explicitly in that same entity: the logged in user's
        // active entity is not necessarily the root entity (e.g. TU_USER defaults
        // to a sub-entity), and the embed session only sees entities_id and its
        // children, not its ancestors, so the ticket must land within that scope.
        $this->createItem(Ticket::class, [
            'name'        => 'Embed dashboard test ticket',
            'content'     => 'Test',
            'status'      => Ticket::INCOMING,
            'entities_id' => $entities_id,
        ]);

        $this->initEmbedSessionAs([
            'entities_id'  => $entities_id,
            'is_recursive' => $is_recursive,
            'profiles_id'  => $profiles_id,
            'users_id'     => $users_id,
        ]);

        $html = Widget::searchShowList([
            'itemtype'   => Ticket::class,
            // Filter on the ticket name so this assertion does not depend on how many
            // other tickets exist in the DB / where this one lands in the default sort
            // + pagination when the full test suite runs.
            's_criteria' => [
                ['field' => 1, 'searchtype' => 'contains', 'value' => 'Embed dashboard test ticket'],
            ],
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

    public function testSearchShowListInEmbedModeIsRestrictedToSharerRights(): void
    {
        // A ticket created by a full-rights user...
        $this->login();
        $this->createItem(Ticket::class, [
            'name'    => 'Embed dashboard restricted ticket',
            'content' => 'Test',
            'status'  => Ticket::INCOMING,
        ]);

        // ...must NOT be visible through an embed session that restores the
        // context of a low-privilege user (self-service, sees only their own
        // tickets). This is the actual regression test for the security issue:
        // the embed session must never grant more than the sharer could see,
        // unlike the previous implementation which bypassed all right checks.
        $this->login('post-only', 'postonly');
        $profiles_id  = $_SESSION['glpiactiveprofile']['id'];
        $users_id     = Session::getLoginUserID();
        $entities_id  = $_SESSION['glpiactive_entity'];
        $is_recursive = $_SESSION['glpiactive_entity_recursive'];

        $this->initEmbedSessionAs([
            'entities_id'  => $entities_id,
            'is_recursive' => $is_recursive,
            'profiles_id'  => $profiles_id,
            'users_id'     => $users_id,
        ]);

        $html = Widget::searchShowList([
            'itemtype'   => Ticket::class,
            's_criteria' => [],
            'limit'      => 20,
            'color'      => '#CCCCCC',
        ]);

        $this->assertStringNotContainsString('Embed dashboard restricted ticket', $html);
    }

    public function testInitEmbedThrowsOnInvalidToken(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $grid = new Grid('test_dashboard');
        $grid->initEmbed([
            'dashboard'    => 'test_dashboard',
            'entities_id'  => 0,
            'is_recursive' => 1,
            'profiles_id'  => 0,
            'users_id'     => 0,
            'token'        => 'invalid-token',
        ]);
    }

    public function testInitEmbedThrowsWhenUserNoLongerHasProfileOnEntity(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->login();
        $entities_id  = $_SESSION['glpiactive_entity'];
        $is_recursive = $_SESSION['glpiactive_entity_recursive'];
        $users_id     = (int) Session::getLoginUserID();
        // This profile is never assigned to the logged in user, so even
        // though the token is valid for this exact combination, the
        // DB-backed assignment check must reject it.
        $profiles_id = 999999;

        $token = Grid::getToken('test_dashboard', $entities_id, $is_recursive, $profiles_id, $users_id);

        $grid = new Grid('test_dashboard');
        $grid->initEmbed([
            'dashboard'    => 'test_dashboard',
            'entities_id'  => $entities_id,
            'is_recursive' => $is_recursive,
            'profiles_id'  => $profiles_id,
            'users_id'     => $users_id,
            'token'        => $token,
        ]);
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
