<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/* Test for inc/savedsearch.class.php */

class SavedSearch extends DbTestCase
{
    public function testGetVisibilityCriteria()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        // No restrictions when having the config UPDATE right
        $this->array(\SavedSearch::getVisibilityCriteria())->isEqualTo(['WHERE' => []]);
        $_SESSION["glpiactiveprofile"]['config'] = $_SESSION["glpiactiveprofile"]['config'] & ~UPDATE;
        $this->array(\SavedSearch::getVisibilityCriteria()['WHERE'])->isNotEmpty();
    }
    public function testAddVisibilityRestrict()
    {
       //first, as a super-admin
        $this->login();
        $this->string(\SavedSearch::addVisibilityRestrict())
         ->isIdenticalTo('');

        $this->login('normal', 'normal');
        $this->string(\SavedSearch::addVisibilityRestrict())
         ->isIdenticalTo("`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5'");

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

       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 0],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public'
            ]
        );

        $this->string(\SavedSearch::addVisibilityRestrict())
         ->isIdenticalTo("((`glpi_savedsearches`.`is_private` = '1' AND `glpi_savedsearches`.`users_id` = '5') OR `glpi_savedsearches`.`is_private` = '0')");
    }

    public function testGetMine()
    {
        global $DB;
        // needs a user
        // let's use TU_USER
        $this->login();
        $uid =  getItemByTypeName('User', TU_USER, true);

        // now add a bookmark on Ticket view
        $bk = new \SavedSearch();
        $this->boolean(
            (bool)$bk->add([
                'name'         => 'public',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid,
                'is_private'   => 0,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid
            ])
        )->isTrue();

        $this->boolean(
            (bool)$bk->add([
                'name'         => 'private',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid
            ])
        )->isTrue();

        $this->boolean(
            (bool)$bk->add([
                'name'         => 'private',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid + 1,
                'is_private'   => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid
            ])
        )->isTrue();
        // With UPDATE 'config' right, we still shouldn't see other user's private searches
        $this->integer(count($bk->getMine()))->isEqualTo(2);
        $_SESSION["glpiactiveprofile"]['config'] = $_SESSION["glpiactiveprofile"]['config'] & ~UPDATE;
        $this->integer(count($bk->getMine()))->isEqualTo(2);

        //add public saved searches read right for normal profile
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

        $this->integer(count($bk->getMine('Ticket')))->isEqualTo(1);

        //reset rights
        $DB->update(
            'glpi_profilerights',
            ['rights' => 0],
            [
                'profiles_id'  => 2,
                'name'         => 'bookmark_public'
            ]
        );
        //ACLs have changed: login again.
        $this->login('normal', 'normal');

        $this->integer(count($bk->getMine('Ticket')))->isEqualTo(0);
    }
}
