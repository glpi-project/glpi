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

namespace tests\units;

use DbTestCase;

/* Test for inc/reminder.class.php */

class ReminderTest extends DbTestCase
{
    public function testAddVisibilityRestrict()
    {
        $e2e_root        = getItemByTypeName('Entity', 'E2ETestEntity', true);
        $e2e_test_child1 = getItemByTypeName('Entity', 'E2ETestSubEntity1', true);
        $e2e_test_child2 = getItemByTypeName('Entity', 'E2ETestSubEntity2', true);
        $test_root       = getItemByTypeName('Entity', '_test_root_entity', true);
        $test_child_1    = getItemByTypeName('Entity', '_test_child_1', true);
        $test_child_2    = getItemByTypeName('Entity', '_test_child_2', true);
        $test_child_3    = getItemByTypeName('Entity', '_test_child_3', true);

        $all_entities = "'0', '$test_root', '$e2e_root', '$e2e_test_child1', '$e2e_test_child2', '$test_child_1', '$test_child_2', '$test_child_3'";

        //first, as a super-admin
        $this->login();
        $users_id = \Session::getLoginUserID();
        $expected = preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '$users_id'
               OR `glpi_reminders_users`.`users_id` = '$users_id'
               OR (`glpi_profiles_reminders`.`profiles_id` = '4'
                    AND (`glpi_profiles_reminders`.`no_entity_restriction` = '1'
                         OR ((`glpi_profiles_reminders`.`entities_id` IN ('$test_root', '$test_child_1', '$test_child_2', '$test_child_3')
                                   OR (`glpi_profiles_reminders`.`is_recursive` = '1'
                                        AND `glpi_profiles_reminders`.`entities_id` IN ('0'))))))
               OR ((`glpi_entities_reminders`.`entities_id` IN ('$test_root', '$test_child_1', '$test_child_2', '$test_child_3')
                         OR (`glpi_entities_reminders`.`is_recursive` = '1'
                              AND `glpi_entities_reminders`.`entities_id` IN ('0')))))");
        $this->assertSame(
            $expected,
            \Reminder::addVisibilityRestrict()
        );

        $this->login('normal', 'normal');
        $expected = preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '5'
               OR `glpi_reminders_users`.`users_id` = '5'
               OR (`glpi_profiles_reminders`.`profiles_id` = '2'
                    AND (`glpi_profiles_reminders`.`no_entity_restriction` = '1'
                         OR (`glpi_profiles_reminders`.`entities_id` IN ($all_entities))))
               OR (`glpi_entities_reminders`.`entities_id` IN ($all_entities)))");
        $this->assertSame(
            $expected,
            trim(preg_replace('/\s+/', ' ', \Reminder::addVisibilityRestrict()))
        );

        $this->login('tech', 'tech');
        $expected = preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '4'
               OR `glpi_reminders_users`.`users_id` = '4'
               OR (`glpi_profiles_reminders`.`profiles_id` = '6'
                    AND (`glpi_profiles_reminders`.`no_entity_restriction` = '1'
                         OR (`glpi_profiles_reminders`.`entities_id` IN ($all_entities))))
               OR (`glpi_entities_reminders`.`entities_id` IN ($all_entities)))");
        $this->assertSame(
            $expected,
            trim(preg_replace('/\s+/', ' ', \Reminder::addVisibilityRestrict()))
        );

        $bkp_groups = $_SESSION['glpigroups'];
        $_SESSION['glpigroups'] = [42, 1337];
        $str = \Reminder::addVisibilityRestrict();
        $_SESSION['glpigroups'] = $bkp_groups;
        $expected = preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '4'
               OR `glpi_reminders_users`.`users_id` = '4'
               OR (`glpi_groups_reminders`.`groups_id` IN ('42', '1337')
                    AND (`glpi_groups_reminders`.`no_entity_restriction` = '1'
                         OR (`glpi_groups_reminders`.`entities_id` IN ($all_entities))))
               OR (`glpi_profiles_reminders`.`profiles_id` = '6'
                    AND (`glpi_profiles_reminders`.`no_entity_restriction` = '1'
                         OR (`glpi_profiles_reminders`.`entities_id` IN ($all_entities))))
               OR (`glpi_entities_reminders`.`entities_id` IN ($all_entities)))");
        $this->assertSame(
            $expected,
            trim(preg_replace('/\s+/', ' ', $str))
        );
    }
}
