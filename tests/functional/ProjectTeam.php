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
class ProjectTeam extends DbTestCase
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

        $this->array($expanded_team)
         ->hasSize(4)
         ->hasKeys(['User', 'Group', 'Supplier', 'Contact']);
        $this->array($expanded_team['User'])->hasSize(1);
        $this->array($expanded_team['User'][0])
         ->hasKeys(['items_id', 'name', 'realname', 'firstname', 'display_name']);
        $this->variable($expanded_team['User'][0]['name'])->isIdenticalTo($user->fields['name']);
        $this->variable($expanded_team['User'][0]['realname'])->isIdenticalTo($user->fields['realname']);
        $this->variable($expanded_team['User'][0]['firstname'])->isIdenticalTo($user->fields['firstname']);
        $this->variable($expanded_team['User'][0]['display_name'])
         ->isIdenticalTo(formatUserName($user->fields['id'], $user->fields['name'], $user->fields['realname'], $user->fields['firstname']));

        $this->array($expanded_team['Group'])->hasSize(2);
        $this->array($expanded_team['Group'][0])
         ->hasKeys(['items_id', 'name', 'realname', 'display_name']);
        $this->variable($expanded_team['Group'][0]['name'])->isIdenticalTo($group_1->fields['name']);
        $this->variable($expanded_team['Group'][0]['realname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Group'][0]['firstname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Group'][0]['display_name'])
         ->isIdenticalTo(formatUserName($group_1->fields['id'], $group_1->fields['name'], null, null));
        $this->array($expanded_team['Group'][1])
         ->hasKeys(['items_id', 'name', 'realname', 'display_name']);
        $this->variable($expanded_team['Group'][1]['name'])->isIdenticalTo($group_2->fields['name']);
        $this->variable($expanded_team['Group'][1]['realname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Group'][1]['firstname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Group'][1]['display_name'])
         ->isIdenticalTo(formatUserName($group_2->fields['id'], $group_2->fields['name'], null, null));

        $this->array($expanded_team['Supplier'])->hasSize(1);
        $this->array($expanded_team['Supplier'][0])
         ->hasKeys(['items_id', 'name', 'realname', 'display_name']);
        $this->variable($expanded_team['Supplier'][0]['name'])->isIdenticalTo($supplier_1->fields['name']);
        $this->variable($expanded_team['Supplier'][0]['realname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Supplier'][0]['firstname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Supplier'][0]['display_name'])
         ->isIdenticalTo(formatUserName($supplier_1->fields['id'], $supplier_1->fields['name'], null, null));

        $this->array($expanded_team['Contact'])->hasSize(1);
        $this->array($expanded_team['Contact'][0])
         ->hasKeys(['items_id', 'name', 'realname', 'display_name']);
        $this->variable($expanded_team['Contact'][0]['name'])->isIdenticalTo($contact_1->fields['name']);
        $this->variable($expanded_team['Contact'][0]['realname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Contact'][0]['firstname'])->isIdenticalTo(null);
        $this->variable($expanded_team['Contact'][0]['display_name'])
         ->isIdenticalTo(formatUserName($contact_1->fields['id'], $contact_1->fields['name'], null, $contact_1->fields['firstname']));
    }
}
