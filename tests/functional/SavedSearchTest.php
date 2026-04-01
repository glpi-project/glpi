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

use Glpi\Tests\DbTestCase;
use MassiveAction;
use SavedSearch;
use Ticket;

/* Test for inc/savedsearch.class.php */

class SavedSearchTest extends DbTestCase
{
    public function testGetVisibilityCriteria()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        // No restrictions when having the config UPDATE right
        $this->assertEquals(
            ['WHERE' => []],
            SavedSearch::getVisibilityCriteria()
        );
        $_SESSION["glpiactiveprofile"]['config'] = $_SESSION["glpiactiveprofile"]['config'] & ~UPDATE;
        $this->assertNotEmpty(SavedSearch::getVisibilityCriteria()['WHERE']);
    }

    public function testAddVisibilityRestrict()
    {
        $test_root    = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $test_child_2 = getItemByTypeName('Entity', '_test_child_2', true);
        $test_child_3 = getItemByTypeName('Entity', '_test_child_3', true);

        //first, as a super-admin
        $this->login();
        $this->assertSame('', SavedSearch::addVisibilityRestrict());

        $this->login('normal', 'normal');
        $this->assertSame(
            "`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5' AND (true)",
            SavedSearch::addVisibilityRestrict()
        );

        //add public saved searches read right for normal profile
        global $DB;
        $DB->update(
            'glpi_profilerights',
            ['rights' => 1],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public',
            ]
        );

        //ACLs have changed: login again.
        $this->login('normal', 'normal');

        $this->assertSame(
            "((`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5') OR (`glpi_savedsearches`.`is_private` = '0')) AND (true)",
            SavedSearch::addVisibilityRestrict()
        );

        // Check entity restriction
        $this->setEntity('_test_root_entity', true);
        $this->assertSame(
            "((`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5') OR (`glpi_savedsearches`.`is_private` = '0')) AND ((`glpi_savedsearches`.`entities_id` IN ('$test_root', '$test_child_1', '$test_child_2', '$test_child_3') OR (`glpi_savedsearches`.`is_recursive` = '1' AND `glpi_savedsearches`.`entities_id` IN ('0'))))",
            SavedSearch::addVisibilityRestrict()
        );
    }

    public function testGetMine()
    {
        global $DB;

        $root_entity_id  = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $child_entity_id = getItemByTypeName(\Entity::class, '_test_child_1', true);

        // needs a user
        // let's use TU_USER
        $this->login();
        $tuuser_id =  getItemByTypeName(\User::class, TU_USER, true);
        $normal_id =  getItemByTypeName(\User::class, 'normal', true);

        // now add a bookmark on Ticket view
        $bk = new SavedSearch();
        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'public root recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $root_entity_id,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
            ])
        );
        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'public root NOT recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $root_entity_id,
                'is_recursive' => 0,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
            ])
        );
        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'public child 1 recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $child_entity_id,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
            ])
        );

        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'private TU_USER',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
            ])
        );

        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'private normal user',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $normal_id,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
            ])
        );
        // With UPDATE 'config' right, we still shouldn't see other user's private searches
        $expected = [
            'public root recursive',
            'public root NOT recursive',
            'public child 1 recursive',
            'private TU_USER',
        ];
        $mine = $bk->getMine();
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );
        $_SESSION["glpiactiveprofile"]['config'] = $_SESSION["glpiactiveprofile"]['config'] & ~UPDATE;
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );

        // Normal user cannot see public saved searches by default
        $this->login('normal', 'normal');

        $mine = $bk->getMine();
        $this->assertCount(1, $mine);
        $this->assertEqualsCanonicalizing(
            ['private normal user'],
            array_column($mine, 'name')
        );

        //add public saved searches read right for normal profile
        $DB->update(
            'glpi_profilerights',
            ['rights' => 1],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public',
            ]
        );
        $this->login('normal', 'normal'); // ACLs have changed: login again.
        $expected = [
            'public root recursive',
            'public root NOT recursive',
            'public child 1 recursive',
            'private normal user',
        ];
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );

        // Check entity restrictions
        $this->setEntity('_test_root_entity', false);
        $expected = [
            'public root recursive',
            'public root NOT recursive',
            'private normal user',
        ];
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );

        $this->setEntity('_test_child_1', true);
        $expected = [
            'public root recursive',
            'public child 1 recursive',
            'private normal user',
        ];
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );

        $this->setEntity('_test_child_1', false);
        $expected = [
            'public root recursive',
            'public child 1 recursive',
            'private normal user',
        ];
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($expected), $mine);
        $this->assertEqualsCanonicalizing(
            $expected,
            array_column($mine, 'name')
        );
    }

    public function testAvailableMassiveActions(): void
    {
        // Act: get saved searches massive actions
        $this->login();
        $actions = MassiveAction::getAllMassiveActions(SavedSearch::class);

        // Assert: validate the available actions
        $this->assertEquals([
            'Delete permanently',
            'Add to transfer list',
            'Unset as default',
            'Change count method',
            'Change visibility',
            'Change entity',
        ], array_values($actions));
    }

    public function testCannotChangeVisibilityMA()
    {
        $this->login();
        $private_savedsearch = $this->createItem(SavedSearch::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'users_id' => $_SESSION['glpiID'],
            'itemtype' => Ticket::class,
            'is_private' => 1,
            'type' => 1,
            'url' => '/front/ticket.php',
        ], ['url']);

        $ma = new MassiveAction([
            'is_private' => 0,
            'action' => 'change_visibility',
            'action_name' => 'change_visibility',
            'processor' => 'SavedSearch',
            'initial_item' => [
                'SavedSearch' => [$private_savedsearch->getID() => $private_savedsearch->getID()],
            ],
            'items' => [
                'SavedSearch' => [$private_savedsearch->getID() => $private_savedsearch->getID()],
            ],
        ], [
            '_single_item' => [
                'itemtype' => SavedSearch::class,
                'id' => 1,
            ],
        ], 'process', null);
        SavedSearch::processMassiveActionsForOneItemtype($ma, new SavedSearch(), [$private_savedsearch->getID()]);
        $this->assertEquals(0, $ma->results['noright']);
        $this->assertEquals(1, $ma->results['ok']);

        $_SESSION['glpiactiveprofile'][SavedSearch::$rightname] = 0;

        $actions = MassiveAction::getAllMassiveActions(SavedSearch::class);
        $this->assertNotContains('Change visibility', $actions);

        $ma = new MassiveAction([
            'is_private' => 0,
            'action' => 'change_visibility',
            'action_name' => 'change_visibility',
            'processor' => 'SavedSearch',
            'initial_item' => [
                'SavedSearch' => [$private_savedsearch->getID() => $private_savedsearch->getID()],
            ],
            'items' => [
                'SavedSearch' => [$private_savedsearch->getID() => $private_savedsearch->getID()],
            ],
        ], [
            '_single_item' => [
                'itemtype' => SavedSearch::class,
                'id' => 1,
            ],
        ], 'process', null);
        SavedSearch::processMassiveActionsForOneItemtype($ma, new SavedSearch(), [$private_savedsearch->getID()]);
        $this->assertEquals(1, $ma->results['noright']);
        $this->assertEquals(0, $ma->results['ok']);
    }

    public function testPrepareInputAdd()
    {
        $this->login();

        $saved_search = new SavedSearch();
        // URL and type must both be provided
        $this->assertFalse($saved_search->prepareInputForAdd([
            'type' => 1,
        ]));
        $this->assertFalse($saved_search->prepareInputForAdd([
            'url' => 'https://glpi-project.org?test=1',
        ]));
        $this->assertEquals([
            'type' => 1,
            'url' => 'https://glpi-project.org?test=1',
            'query' => 'test=1',
        ], $saved_search->prepareInputForAdd([
            'type' => 1,
            'url' => 'https://glpi-project.org?test=1',
        ]));
        $this->assertEquals([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 0,
            'query' => '',
        ], $saved_search->prepareInputForAdd([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 0,
        ]));

        // Remove permissions to only allow private saved searches
        $_SESSION['glpiactiveprofile'][SavedSearch::$rightname] = 0;

        $this->assertFalse($saved_search->prepareInputForAdd([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 0,
        ]));
        $this->assertEquals([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 1,
            'query' => '',
        ], $saved_search->prepareInputForAdd([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 1,
        ]));
        // is_private defaults to 1 in the DB
        $this->assertEquals([
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'query' => '',
        ], $saved_search->prepareInputForAdd([
            'type' => 1,
            'url' => 'https://glpi-project.org',
        ]));
    }

    public function testPrepateInputUpdate()
    {
        $this->login();

        $saved_search = new SavedSearch();
        $saved_search->fields = [
            'id' => 999,
            'type' => 1,
            'url' => 'https://glpi-project.org',
            'is_private' => 1,
        ];
        $this->assertEquals([
            'is_private' => 0,
        ], $saved_search->prepareInputForUpdate([
            'is_private' => 0,
        ]));
        $this->assertEquals([
            'is_private' => 1,
        ], $saved_search->prepareInputForUpdate([
            'is_private' => 1,
        ]));

        // Remove permissions to only allow private saved searches
        $_SESSION['glpiactiveprofile'][SavedSearch::$rightname] = 0;

        $this->assertFalse($saved_search->prepareInputForUpdate([
            'is_private' => 0,
        ]));
        $this->assertEquals([
            'is_private' => 1,
        ], $saved_search->prepareInputForUpdate([
            'is_private' => 1,
        ]));
    }
}
