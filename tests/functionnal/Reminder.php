<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/reminder.class.php */

class Reminder extends DbTestCase {

   public function testAddVisibilityRestrict() {
      /**
       * Remove for now tests that are known to fail (so others may run)
      //first, as a super-admin
      $this->login();
      $expected = "(`glpi_reminders`.`users_id` = '6' OR `glpi_reminders_users`.`users_id` = '6' OR ((`glpi_profiles_reminders`.`profiles_id` = '4' AND (`glpi_profiles_reminders`.`entities_id` < '0' OR ((`glpi_profiles_reminders`.`entities_id` IN ('1', '2', '3') OR (`glpi_profiles_reminders`.`is_recursive` = '1' AND `glpi_profiles_reminders`.`entities_id` IN ('0'))))))) OR (`glpi_entities_reminders`.`entities_id` IN ('1', '2', '3') OR (`glpi_entities_reminders`.`is_recursive` = '1' AND `glpi_entities_reminders`.`entities_id` IN ('0'))))";
      $this->string(\Reminder::addVisibilityRestrict())
         ->isIdenticalTo($expected);

      $this->login('normal', 'normal');
      $this->string(trim(preg_replace('/\s+/', ' ', \Reminder::addVisibilityRestrict())))
         ->isIdenticalTo("`glpi_reminders`.`users_id` = '5'");

      $this->login('tech', 'tech');
      $this->string(trim(preg_replace('/\s+/', ' ', \Reminder::addVisibilityRestrict())))
         ->isIdenticalTo(preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '4'  OR `glpi_reminders_users`.`users_id` = '4'  OR ((`glpi_profiles_reminders`.`profiles_id`
                                 = '6'
                            AND (`glpi_profiles_reminders`.`entities_id` < '0'
                                  OR  (`glpi_entities_reminders`.`entities_id` IN ('0', '1', '2', '3')))))"));

      $_SESSION['glpigroups'] = [42, 1337];
      $this->string(trim(preg_replace('/\s+/', ' ', \Reminder::addVisibilityRestrict())))
         ->isIdenticalTo(preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = '4'  OR `glpi_reminders_users`.`users_id` = '4'  OR (`glpi_groups_reminders`.`groups_id`
                                 IN ('42','1337')
                            AND (`glpi_groups_reminders`.`entities_id` < 0
                                 OR (  1 )))  OR (`glpi_profiles_reminders`.`profiles_id`
                                 = '6'
                            AND (`glpi_profiles_reminders`.`entities_id` < 0
                                 OR (  1 ))) OR ( `glpi_entities_reminders`.`entities_id` IN ('0', '1', '2', '3')))"));
      */
   }
}
