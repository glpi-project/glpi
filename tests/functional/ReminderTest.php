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

/* Test for inc/reminder.class.php */

class ReminderTest extends DbTestCase
{
    public function testChildGroupInheritsParentGroupVisibilityInListCriteria(): void
    {
        global $DB;

        $this->login();
        $parent_group = $this->createItem("Group", ['name' => 'Reminder parent group']);
        $child_group = $this->createItem("Group", [
            'name' => 'Reminder child group',
            'groups_id' => $parent_group->getID(),
        ]);

        $tech_user = getItemByTypeName("User", "tech", true);
        $this->createItem("Group_User", ['users_id' => $tech_user, 'groups_id' => $child_group->getID()]);

        $reminder = $this->createItem("Reminder", [
            'name'     => 'Reminder visible to parent group',
            'text'     => 'Reminder visible to parent group',
            'users_id' => \Session::getLoginUserID(),
        ]);
        $this->createItem("Group_Reminder", [
            'reminders_id'          => $reminder->getID(),
            'groups_id'             => $parent_group->getID(),
            'no_entity_restriction' => 1,
        ]);

        $this->login('tech', 'tech');
        $criteria = array_merge(\Reminder::getVisibilityCriteria(), [
            'SELECT' => 'name',
            'FROM'   => \Reminder::getTable(),
        ]);
        $names = array_column(iterator_to_array($DB->request($criteria)), 'name');
        $this->assertContains('Reminder visible to parent group', $names);
    }

    public function testGetListCriteriaIsValid(): void
    {
        global $DB;
        $this->login('post-only', 'postonly');
        $criteria = \Reminder::getListCriteria();
        $this->assertFalse($DB->request($criteria['public'])->isFailed(), 'Public criteria is not valid for post-only user');
        $this->assertFalse($DB->request($criteria['personal'])->isFailed(), 'Personal criteria is not valid for post-only user');

        $this->login();
        $criteria = \Reminder::getListCriteria();
        $this->assertFalse($DB->request($criteria['public'])->isFailed(), 'Public criteria is not valid for TU_USER user');
        $this->assertFalse($DB->request($criteria['personal'])->isFailed(), 'Personal criteria is not valid for TU_USER user');
    }
}
