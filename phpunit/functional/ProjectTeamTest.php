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

/* Test for inc/projectteam.class.php */
class ProjectTeamTest extends DbTestCase
{
    public function testExpandTeamData()
    {
        $user = getItemByTypeName('User', TU_USER);
        $group_1 = getItemByTypeName('Group', '_test_group_1');
        $group_2 = getItemByTypeName('Group', '_test_group_2');
        $contact_1 = getItemByTypeName('Contact', '_contact01_name');
        $supplier_1 = getItemByTypeName('Supplier', '_suplier01_name');

        $expanded_team = \ProjectTeam::expandTeamData([
            'User'   => [
                ['items_id'  => $user->fields['id']]
            ],
            'Group'  => [
                ['items_id'  => $group_1->fields['id']],
                ['items_id'  => $group_2->fields['id']],
            ],
            'Supplier' => [
                ['items_id'  => $supplier_1->fields['id']],
            ],
            'Contact' => [
                ['items_id'  => $contact_1->fields['id']],
            ],
        ]);

        $this->assertCount(4, $expanded_team);
        $this->assertArrayHasKey('User', $expanded_team);
        $this->assertArrayHasKey('Group', $expanded_team);
        $this->assertArrayHasKey('Supplier', $expanded_team);
        $this->assertArrayHasKey('Contact', $expanded_team);
        $this->assertCount(1, $expanded_team['User']);
        $this->assertArrayHasKey('items_id', $expanded_team['User'][0]);
        $this->assertSame($user->fields['name'], $expanded_team['User'][0]['name']);
        $this->assertSame($user->fields['realname'], $expanded_team['User'][0]['realname']);
        $this->assertSame($user->fields['firstname'], $expanded_team['User'][0]['firstname']);
        $this->assertSame(
            formatUserName($user->fields['id'], $user->fields['name'], $user->fields['realname'], $user->fields['firstname']),
            $expanded_team['User'][0]['display_name']
        );

        $this->assertCount(2, $expanded_team['Group']);
        $this->assertArrayHasKey('items_id', $expanded_team['Group'][0]);
        $this->assertSame($group_1->fields['name'], $expanded_team['Group'][0]['name']);
        $this->assertNull($expanded_team['Group'][0]['realname']);
        $this->assertNull($expanded_team['Group'][0]['firstname']);
        $this->assertSame(
            formatUserName($group_1->fields['id'], $group_1->fields['name'], null, null),
            $expanded_team['Group'][0]['display_name']
        );

        $this->assertArrayHasKey('items_id', $expanded_team['Group'][1]);
        $this->assertSame($group_2->fields['name'], $expanded_team['Group'][1]['name']);
        $this->assertNull($expanded_team['Group'][1]['realname']);
        $this->assertNull($expanded_team['Group'][1]['firstname']);
        $this->assertSame(
            formatUserName($group_2->fields['id'], $group_2->fields['name'], null, null),
            $expanded_team['Group'][1]['display_name']
        );

        $this->assertCount(1, $expanded_team['Supplier']);
        $this->assertArrayHasKey('items_id', $expanded_team['Supplier'][0]);
        $this->assertSame($supplier_1->fields['name'], $expanded_team['Supplier'][0]['name']);
        $this->assertNull($expanded_team['Supplier'][0]['realname']);
        $this->assertNull($expanded_team['Supplier'][0]['firstname']);
        $this->assertSame(
            formatUserName($supplier_1->fields['id'], $supplier_1->fields['name'], null, null),
            $expanded_team['Supplier'][0]['display_name']
        );

        $this->assertCount(1, $expanded_team['Contact']);
        $this->assertArrayHasKey('items_id', $expanded_team['Contact'][0]);
        $this->assertSame($contact_1->fields['name'], $expanded_team['Contact'][0]['name']);
        $this->assertNull($expanded_team['Contact'][0]['realname']);
        $this->assertNull($expanded_team['Contact'][0]['firstname']);
        $this->assertSame(
            formatUserName($contact_1->fields['id'], $contact_1->fields['name'], null, null),
            $expanded_team['Contact'][0]['display_name']
        );
    }
}
