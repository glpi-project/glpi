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

    public function testGetMine()
    {
        global $DB;

        $root_entity_id  = getItemByTypeName(\Entity::class, '_test_root_entity', true);

        $test_group_1_id  = getItemByTypeName(\Group::class, '_test_group_1', true);

        // needs a user
        // let's use TU_USER
        $this->login();
        $tuuser_id =  getItemByTypeName(\User::class, TU_USER, true);
        $normal_id =  getItemByTypeName(\User::class, 'normal', true);

        // now add a bookmark on Ticket view
        $bk = new SavedSearch();
        $this->assertTrue(
            (bool) $bk->add(
                [
                    'name'         => 'private root recursive',
                    'type'         => 1,
                    'itemtype'     => 'Ticket',
                    'users_id'     => $tuuser_id,
                    'entities_id'  => 0,
                    'is_recursive' => 1,
                    'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
                ]
            )
        );
        $bk_private_id = $bk->getID();
        $this->assertTrue(
            (bool) $bk->add(
                [
                    'name'         => 'target user root recursive',
                    'type'         => 1,
                    'itemtype'     => 'Ticket',
                    'users_id'     => $tuuser_id,
                    'entities_id'  => 0,
                    'is_recursive' => 1,
                    'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
                ]
            )
        );
        $bk_target_user_id = $bk->getID();
        $this->assertTrue(
            (bool) $bk->add(
                [
                    'name'         => 'target group root recursive',
                    'type'         => 1,
                    'itemtype'     => 'Ticket',
                    'users_id'     => $tuuser_id,
                    'entities_id'  => 0,
                    'is_recursive' => 1,
                    'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
                ]
            )
        );
        $bk_target_group_id = $bk->getID();
        // has is_private => 0 in inputs, so a target will be automatically created for the bookmark's entity
        $this->assertTrue(
            (bool) $bk->add(
                [
                    'name'         => 'created public target entity root recursive',
                    'type'         => 1,
                    'itemtype'     => 'Ticket',
                    'users_id'     => $tuuser_id,
                    'is_private'   => 0,
                    'entities_id'  => 0,
                    'is_recursive' => 1,
                    'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
                ]
            )
        );
        $bk_target_entity_id = $bk->getID();
        $bk2 = new \SavedSearch();
        $bk2->getFromDB($bk_target_entity_id);
        $this->assertEquals(1, $bk2->countVisibilities());

        $this->assertTrue(
            (bool) $bk->add(
                [
                    'name'         => 'private normal user',
                    'type'         => 1,
                    'itemtype'     => 'Ticket',
                    'users_id'     => $normal_id,
                    'entities_id'  => 0,
                    'is_recursive' => 1,
                    'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id,
                ]
            )
        );
        $bk_private_normal_id = $bk->getID();
        // With UPDATE 'config' right, we still shouldn't see other user's searches without targets
        $expected = [
            'private root recursive',
            'target user root recursive',
            'target group root recursive',
            'created public target entity root recursive',
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

        // test each type of targets so that normal will be able to see them
        $bks_normal = [
            'private normal user',
            'created public target entity root recursive',
        ];
        // add normal to a group
        $group_user = new \Group_User();
        $group_user->add(
            [
                'users_id' => 5,
                'groups_id' => $test_group_1_id,
            ]
        );
        $this->login('normal', 'normal');

        $mine = $bk->getMine();
        $this->assertCount(count($bks_normal), $mine);
        $this->assertEqualsCanonicalizing(
            $bks_normal,
            array_column($mine, 'name')
        );

        // add normal as target for another savedsearch
        $DB->insert(
            'glpi_savedsearches_usertargets',
            [
                'users_id'  => 5,
                'savedsearches_id' => $bk_target_user_id,
            ]
        );
        $bks_normal[] = 'target user root recursive';
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($bks_normal), $mine);
        $this->assertEqualsCanonicalizing(
            $bks_normal,
            array_column($mine, 'name')
        );

        // add the group as target for a bookmark
        $DB->insert(
            'glpi_groups_savedsearches',
            [
                'savedsearches_id'  => $bk_target_group_id,
                'groups_id' => $test_group_1_id,
                'entities_id' => 0,
                'is_recursive' => 1,
            ]
        );

        $bks_normal[] = 'target group root recursive';
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($bks_normal), $mine);
        $this->assertEqualsCanonicalizing(
            $bks_normal,
            array_column($mine, 'name')
        );

        // add an entity target for an entity at a level below the current one
        $DB->insert(
            'glpi_entities_savedsearches',
            [
                'savedsearches_id'  => $bk_private_id,
                'entities_id' => $root_entity_id,
                'is_recursive' => 1,
            ]
        );
        $bks_normal[] = 'private root recursive';
        $mine = $bk->getMine('Ticket');
        $this->assertCount(count($bks_normal), $mine);
        $this->assertEqualsCanonicalizing(
            $bks_normal,
            array_column($mine, 'name')
        );

        $DB->delete(
            $group_user->getTable(),
            [
                'id' => $group_user->getID(),
            ]
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
