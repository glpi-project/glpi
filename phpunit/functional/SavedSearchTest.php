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

namespace tests\units;

use DbTestCase;

use function PHPUnit\Framework\assertContains;

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
            \SavedSearch::getVisibilityCriteria()
        );
        $_SESSION["glpiactiveprofile"]['config'] = $_SESSION["glpiactiveprofile"]['config'] & ~UPDATE;
        $this->assertNotEmpty(\SavedSearch::getVisibilityCriteria()['WHERE']);
    }

    public function testAddVisibilityRestrict()
    {
       //first, as a super-admin
        $this->login();
        $this->assertSame('', \SavedSearch::addVisibilityRestrict());

        $this->login('normal', 'normal');
        $this->assertSame(
            "`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5'",
            \SavedSearch::addVisibilityRestrict()
        );

        //add public saved searches read right for normal profile
        global $DB;
        $DB->update(
            'glpi_profilerights',
            ['rights' => 1],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public'
            ]
        );

        //ACLs have changed: login again.
        $this->login('normal', 'normal');

        $this->assertSame(
            "((`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5') OR (`glpi_savedsearches`.`is_private` = '0'))",
            \SavedSearch::addVisibilityRestrict()
        );

        // Check entity restriction
        $this->setEntity('_test_root_entity', true);
        $this->assertSame(
            "((`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5') OR (`glpi_savedsearches`.`is_private` = '0')) AND ((`glpi_savedsearches`.`entities_id` IN ('1', '2', '3') OR (`glpi_savedsearches`.`is_recursive` = '1' AND `glpi_savedsearches`.`entities_id` IN ('0'))))",
            \SavedSearch::addVisibilityRestrict()
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
        $bk = new \SavedSearch();
        $this->assertTrue(
            (bool)$bk->add([
                'name'         => 'public root recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $root_entity_id,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id
            ])
        );
        $this->assertTrue(
            (bool)$bk->add([
                'name'         => 'public root NOT recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $root_entity_id,
                'is_recursive' => 0,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id
            ])
        );
        $this->assertTrue(
            (bool)$bk->add([
                'name'         => 'public child 1 recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 0,
                'entities_id'  => $child_entity_id,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id
            ])
        );

        $this->assertTrue(
            (bool)$bk->add([
                'name'         => 'private TU_USER',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $tuuser_id,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id
            ])
        );

        $this->assertTrue(
            (bool)$bk->add([
                'name'         => 'private normal user',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $normal_id,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $tuuser_id
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
            ['name' => 'private normal user'],
            array_column($mine, 'name')
        );

        //add public saved searches read right for normal profile
        $DB->update(
            'glpi_profilerights',
            ['rights' => 1],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public'
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
}
