<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* Test for inc/dashboard/dashboard.class.php */

class Dashboard extends DbTestCase
{
    private $dashboard = null;

    public function beforeTestMethod($method)
    {
        $this->dashboard = new \Glpi\Dashboard\Dashboard('test_dashboard');

        parent::beforeTestMethod($method);
    }

    public function testLoad()
    {
        $d_key = $this->dashboard->load(true);
        $this->integer($d_key)->isGreaterThan(0);

        $items = $this->getPrivateProperty('items');
        $this->array($items)->hasSize(3);

        $rights = $this->getPrivateProperty('rights');
        $this->array($rights)->hasSize(2);
    }


    public function testGetFromDB()
    {
       // we need to test we get the dashboard by it's key and not it's id
        $this->boolean($this->dashboard->getFromDB(1))->isFalse();
        $this->boolean($this->dashboard->getFromDB('test_dashboard'))->isTrue();
        $this->string($this->getPrivateProperty('key'))->isEqualTo('test_dashboard');
        $this->array($this->getPrivateProperty('fields'))->isNotEmpty();
    }


    public function testGetTitle()
    {
        $this->string($this->dashboard->getTitle())->isEqualTo("Test_Dashboard");
    }


    public function testSaveNew()
    {
        $this->string($this->dashboard->saveNew(
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
                    ]
                ], [
                    'gridstack_id' => 'bn_count_Computer_5',
                    'card_id'      => 'bn_count_Computer',
                    'x'            => 2,
                    'y'            => 0,
                    'width'        => 2,
                    'height'       => 2,
                    'card_options' => [
                        'color' => '#FFFFFF',
                    ]
                ],
            ],
            [
                [
                    'entities_id' => 0,
                ]
            ]
        ))->isEqualTo("new-dashboard");

        $items = $this->getPrivateProperty('items');
        $this->array($items)->hasSize(2);

        $rights = $this->getPrivateProperty('rights');
        $this->array($rights)->hasSize(1);
    }


    public function testSaveTitle()
    {
        $new_title = "new Title";
        $this->dashboard->saveTitle($new_title);
        $this->string($this->dashboard->getTitle())->isEqualTo($new_title);

       // key of dashboard should not have changed
        $this->string($this->getPrivateProperty('key'))->isEqualTo('test_dashboard');
    }


    public function testClone()
    {
        $clone_name = sprintf(__('Copy of %s'), "Test_Dashboard");
        $clone_key  = \Toolbox::slugify($clone_name);
        $this->array($this->dashboard->cloneCurrent())->isEqualTo([
            'title' => $clone_name,
            'key'   => $clone_key
        ]);

        $this->boolean($this->dashboard->getFromDB($clone_key))->isTrue();
        $this->string($this->dashboard->fields['context'])->isEqualTo('core');

        $this->string($this->getPrivateProperty('key'))->isEqualTo($clone_key);
        $this->string($this->getPrivateProperty('key'))->isEqualTo($clone_key);
        $items = $this->getPrivateProperty('items');
        $this->array($items)->hasSize(3);
        $this->array($this->getPrivateProperty('rights'))->hasSize(4);

        foreach ($items as $item) {
            $this->array($item)->hasKey('card_options');
            $this->array($item['card_options'])->hasSize(1);
        }
    }


    public function testGetAll()
    {
       // get "core" dashboards
        $dasboards = $this->dashboard::getAll(true, false);
        $this->array($dasboards)
         ->hasSize(5)
         ->hasKey('test_dashboard')
         ->hasKey('test_dashboard2');

        $this->array($dasboards['test_dashboard'])
         ->hasKey('items')
         ->hasKey('rights');
        $this->array($dasboards['test_dashboard2'])
         ->hasKey('items')
         ->hasKey('rights');

        $this->array($dasboards['test_dashboard']['items'])->hasSize(3);
        $this->array($dasboards['test_dashboard']['rights'])->hasSize(4);
        $this->array($dasboards['test_dashboard2']['items'])->hasSize(0);
        $this->array($dasboards['test_dashboard2']['rights'])->hasSize(4);

        $dasboards = $this->dashboard::getAll(true, false, "inexistent_context");
        $this->array($dasboards)
         ->hasSize(0);
    }


    public function testDelete()
    {
        global $DB;

        $this->dashboard->getFromDB('test_dashboard');
        $dashboards_id = $this->dashboard->fields['id'];

        $this->boolean($this->dashboard->delete([
            'key' => 'test_dashboard'
        ]))->isTrue();

        $items = iterator_to_array($DB->request([
            'FROM' => \Glpi\Dashboard\Item::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id
            ]
        ]));
        $this->array($items)->isEmpty();
        $rights     = iterator_to_array($DB->request([
            'FROM' => \Glpi\Dashboard\Right::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id
            ]
        ]));
        $this->array($rights)->isEmpty();
    }


    public function getPrivateProperty(string $propertyName)
    {
        $reflector = new \ReflectionClass("Glpi\Dashboard\Dashboard");
        $property  = $reflector->getProperty($propertyName);
        $property->setAccessible(true);

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
                        'card_options' => []
                    ], [
                        'gridstack_id' => 'bn_count_Computer_5',
                        'card_id'      => 'bn_count_Computer',
                        'x'            => 2,
                        'y'            => 0,
                        'width'        => 2,
                        'height'       => 2,
                        'card_options' => []
                    ],
                ],
                'rights'  => [
                    [
                        'entities_id' => 0
                    ]
                ],
            ]
        ];

        $this->boolean(\Glpi\Dashboard\Dashboard::importFromJson($import))->isTrue();
        $this->boolean($this->dashboard->getFromDB($key))->isTrue();
        $this->string($this->dashboard->getTitle())->isEqualTo($title);
        $this->string($this->getPrivateProperty('key'))->isEqualTo($key);
        $this->array($this->getPrivateProperty('items'))->hasSize(2);
        $this->array($this->getPrivateProperty('rights'))->hasSize(1);
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
            ]
        ];

        $this->array(\Glpi\Dashboard\Dashboard::convertRights($raw))->isEqualTo([
            'entities_id' => [0],
            'profiles_id' => [3, 4],
            'users_id'    => [2],
            'groups_id'   => [],
        ]);
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

        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isFalse();

        $_SESSION['glpiactiveentities'] = [0];
        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

        $_SESSION['glpiactiveentities'] = [];
        $_SESSION['glpiactiveprofile'] = ['id' => 3];
        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

        $_SESSION['glpiactiveprofile'] = ['id' => 1];
        $_SESSION['glpiID'] = 2;
        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

        $_SESSION['glpiID'] = 1;
        $_SESSION['glpigroups'] = [3];
        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isTrue();

        $_SESSION['glpigroups'] = [];
        $this->boolean(\Glpi\Dashboard\Dashboard::checkRights($rights))->isFalse();
    }
}
