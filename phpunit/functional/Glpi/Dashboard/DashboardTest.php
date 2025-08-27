<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DbTestCase;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item;
use Glpi\Dashboard\Right;

/* Test for inc/dashboard/dashboard.class.php */

class DashboardTest extends DbTestCase
{
    private $dashboard = null;

    public function setUp(): void
    {
        $this->dashboard = new Dashboard('test_dashboard');
        parent::setUp();
    }

    public function testLoad()
    {
        $d_key = $this->dashboard->load(true);
        $this->assertGreaterThan(0, $d_key);

        $items = $this->getPrivateProperty('items');
        $this->assertCount(3, $items);

        $rights = $this->getPrivateProperty('rights');
        $this->assertCount(2, $rights);
    }


    public function testGetFromDB()
    {
        // we need to test we get the dashboard by it's key and not it's id
        $this->assertFalse($this->dashboard->getFromDB(1));
        $this->assertTrue($this->dashboard->getFromDB('test_dashboard'));
        $this->assertEquals('test_dashboard', $this->getPrivateProperty('key'));
        $this->assertNotEmpty($this->getPrivateProperty('fields'));
    }


    public function testGetTitle()
    {
        $this->assertEquals("Test_Dashboard", $this->dashboard->getTitle());
    }


    public function testSaveNew()
    {
        $this->assertEquals(
            "new-dashboard",
            $this->dashboard->saveNew(
                "New Dashboard",
                'my_context',
                [
                    [
                        'gridstack_id' => 'bn_count_Computer_4',
                        'card_id'      => 'bn_count_Computer',
                        'x'            => 0,
                        'y'            => 0,
                        'width'        => 2,
                        'height'       => 2,
                        'card_options' => [
                            'color' => '#FFFFFF',
                        ],
                    ], [
                        'gridstack_id' => 'bn_count_Computer_5',
                        'card_id'      => 'bn_count_Computer',
                        'x'            => 2,
                        'y'            => 0,
                        'width'        => 2,
                        'height'       => 2,
                        'card_options' => [
                            'color' => '#FFFFFF',
                        ],
                    ],
                ],
                [
                    'entities_id' => [0],
                ]
            )
        );

        $items = $this->getPrivateProperty('items');
        $this->assertCount(2, $items);

        $rights = $this->getPrivateProperty('rights');
        $this->assertCount(1, $rights);
    }


    public function testSaveTitle()
    {
        $new_title = "new Title";
        $this->dashboard->saveTitle($new_title);
        $this->assertEquals($new_title, $this->dashboard->getTitle());

        // key of dashboard should not have changed
        $this->assertEquals('test_dashboard', $this->getPrivateProperty('key'));
    }


    public function testClone()
    {
        $clone_name = sprintf(__('Copy of %s'), "Test_Dashboard");
        $clone_key_prefix = \Toolbox::slugify($clone_name);
        $clone = $this->dashboard->cloneCurrent();

        $this->assertEquals($clone_name, $clone['title']);

        $this->assertStringStartsWith($clone_key_prefix, $clone['key']);

        $this->assertTrue($this->dashboard->getFromDB($clone['key']));
        $this->assertEquals('core', $this->dashboard->fields['context']);

        $this->assertEquals($clone['key'], $this->getPrivateProperty('key'));
        $items = $this->getPrivateProperty('items');
        $this->assertCount(3, $items);
        $this->assertCount(4, $this->getPrivateProperty('rights'));

        foreach ($items as $item) {
            $this->assertArrayHasKey('card_options', $item);
            $this->assertCount(1, $item['card_options']);
        }
    }

    public function testCloneKeyUnicity()
    {
        $num_clones = 5;
        $original_key = 'test_dashboard';
        $this->dashboard = new Dashboard($original_key);

        $keys = [];

        for ($i = 0; $i < $num_clones; $i++) {
            $this->dashboard->load(true);
            $clone = $this->dashboard->cloneCurrent();
            $keys[] = $clone['key'];

            $this->assertNotEquals($original_key, $clone['key']);
        }

        $unique_keys = array_unique($keys);

        $this->assertCount($num_clones, $unique_keys);

        $this->assertEquals(count($keys), count($unique_keys));
    }


    public function testGetAll()
    {
        // get "core" dashboards
        $dasboards = $this->dashboard::getAll(true, false);
        $this->assertCount(5, $dasboards);
        $this->assertArrayHasKey('test_dashboard', $dasboards);
        $this->assertArrayHasKey('test_dashboard2', $dasboards);

        $this->assertArrayHasKey('items', $dasboards['test_dashboard']);
        $this->assertArrayHasKey('rights', $dasboards['test_dashboard']);

        $this->assertArrayHasKey('items', $dasboards['test_dashboard2']);
        $this->assertArrayHasKey('rights', $dasboards['test_dashboard2']);

        $this->assertCount(3, $dasboards['test_dashboard']['items']);
        $this->assertCount(4, $dasboards['test_dashboard']['rights']);
        $this->assertCount(0, $dasboards['test_dashboard2']['items']);
        $this->assertCount(4, $dasboards['test_dashboard2']['rights']);

        $dasboards = $this->dashboard::getAll(true, false, "inexistent_context");
        $this->assertCount(0, $dasboards);
    }


    public function testDelete()
    {
        global $DB;

        $this->assertTrue($this->dashboard->getFromDB('test_dashboard'));
        $dashboards_id = $this->dashboard->fields['id'];

        $this->assertTrue($this->dashboard->delete([
            'key' => 'test_dashboard',
        ]));

        $items = iterator_to_array($DB->request([
            'FROM' => Item::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id,
            ],
        ]));
        $this->assertEmpty($items);
        $rights     = iterator_to_array($DB->request([
            'FROM' => Right::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id,
            ],
        ]));
        $this->assertEmpty($rights);
    }


    public function getPrivateProperty(string $propertyName)
    {
        $reflector = new \ReflectionClass("Glpi\Dashboard\Dashboard");
        $property  = $reflector->getProperty($propertyName);

        return $property->getValue($this->dashboard);
    }


    public function testImportFromJson()
    {
        $title  = 'Test Import';
        $key    = \Toolbox::slugify($title);
        $import = [
            $key => [
                'title'   => $title,
                'context' => 'core',
                'items'   => [
                    [
                        'gridstack_id' => 'bn_count_Computer_4',
                        'card_id'      => 'bn_count_Computer',
                        'x'            => 0,
                        'y'            => 0,
                        'width'        => 2,
                        'height'       => 2,
                        'card_options' => [],
                    ], [
                        'gridstack_id' => 'bn_count_Computer_5',
                        'card_id'      => 'bn_count_Computer',
                        'x'            => 2,
                        'y'            => 0,
                        'width'        => 2,
                        'height'       => 2,
                        'card_options' => [],
                    ],
                ],
                'rights'  => [
                    'entities_id' => [0],
                ],
            ],
        ];

        $this->assertTrue(Dashboard::importFromJson($import));
        $this->assertTrue($this->dashboard->getFromDB($key));
        $this->assertEquals($title, $this->dashboard->getTitle());
        $this->assertEquals($key, $this->getPrivateProperty('key'));
        $this->assertCount(2, $this->getPrivateProperty('items'));
        $this->assertCount(1, $this->getPrivateProperty('rights'));
    }

    public function testConvertRights()
    {
        $raw = [
            [
                'itemtype'                 => 'Entity',
                'items_id'                 => 0,
            ], [
                'itemtype'                 => 'Profile',
                'items_id'                 => 3,
            ], [
                'itemtype'                 => 'Profile',
                'items_id'                 => 4,
            ], [
                'itemtype'                 => 'User',
                'items_id'                 => 2,
            ],
        ];

        $this->assertEquals(
            [
                'entities_id' => [0],
                'profiles_id' => [3, 4],
                'users_id'    => [2],
                'groups_id'   => [],
            ],
            Dashboard::convertRights($raw)
        );
    }


    public function testCheckRights()
    {
        $rights = [
            'entities_id' => [0],
            'profiles_id' => [3 => 3, 4 => 4],
            'users_id'    => [2],
            'groups_id'   => [3],
        ];

        $_SESSION['glpiactiveentities'] = [];
        $_SESSION['glpiactiveprofile'] = ['id' => 1];
        $_SESSION['glpigroups'] = [];
        $_SESSION['glpiID'] = 1;

        $this->assertFalse(Dashboard::checkRights($rights));

        $_SESSION['glpiactiveentities'] = [0];
        $this->assertTrue(Dashboard::checkRights($rights));

        $_SESSION['glpiactiveentities'] = [];
        $_SESSION['glpiactiveprofile'] = ['id' => 3];
        $this->assertTrue(Dashboard::checkRights($rights));

        $_SESSION['glpiactiveprofile'] = ['id' => 1];
        $_SESSION['glpiID'] = 2;
        $this->assertTrue(Dashboard::checkRights($rights));

        $_SESSION['glpiID'] = 1;
        $_SESSION['glpigroups'] = [3];
        $this->assertTrue(Dashboard::checkRights($rights));

        $_SESSION['glpigroups'] = [];
        $this->assertFalse(Dashboard::checkRights($rights));
    }
}
