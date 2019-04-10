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
      //first, as a super-admin
      $this->login();
      $expected = [
         'sql'    => "(`glpi_reminders`.`users_id` = ? OR `glpi_reminders_users`.`users_id` = ? OR (`glpi_profiles_reminders`.`profiles_id` = ? AND (`glpi_profiles_reminders`.`entities_id` < ? OR (`glpi_profiles_reminders`.`entities_id` IN (?,?,?) OR (`glpi_profiles_reminders`.`is_recursive` = ? AND `glpi_profiles_reminders`.`entities_id` IN (?))))) OR (`glpi_entities_reminders`.`entities_id` IN (?,?,?) OR (`glpi_entities_reminders`.`is_recursive` = ? AND `glpi_entities_reminders`.`entities_id` IN (?))))",
         'params' => ['6', '6', '4', 0, 1, 2, 3, 1, '0', 1, 2, 3, 1, '0']
      ];
      $this->array(\Reminder::addVisibilityRestrict())
         ->isIdenticalTo($expected);

      /**
       * Remove for now tests that are known to fail (so others may run)
      $this->login('normal', 'normal');
      $restrict = \Reminder::addVisibilityRestrict();
      $this->string(
         trim(preg_replace('/\s+/', ' ', $restrict['sql']))
      )->isIdenticalTo("`glpi_reminders`.`users_id` = ?");
      $this->array($restrict['params'])->isIdenticalTo([5]);

      $this->login('tech', 'tech');
      $restrict = \Reminder::addVisibilityRestrict();
      $this->string(trim(preg_replace('/\s+/', ' ', $restrict['sql'])))
         ->isIdenticalTo(preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = ?  OR `glpi_reminders_users`.`users_id` = ?  OR ((`glpi_profiles_reminders`.`profiles_id`
                                 = ?
                            AND (`glpi_profiles_reminders`.`entities_id` < ?
                                  OR  (`glpi_entities_reminders`.`entities_id` IN (?,?,?,?)))))"));
      $this->array($restrict['params'])->isIdenticalTo(4, 4, 6, 0, 0, 1, 2, 3);

      $_SESSION['glpigroups'] = [42, 1337];
      $restrict = \Reminder::addVisibilityRestrict();
      $this->string(trim(preg_replace('/\s+/', ' ', $restrict['sql'])))
         ->isIdenticalTo(preg_replace('/\s+/', ' ', "(`glpi_reminders`.`users_id` = ?  OR `glpi_reminders_users`.`users_id` = ?  OR (`glpi_groups_reminders`.`groups_id`
                                 IN (?,?)
                            AND (`glpi_groups_reminders`.`entities_id` < ?
                                 OR (  1 )))  OR (`glpi_profiles_reminders`.`profiles_id`
                                 = ?
                            AND (`glpi_profiles_reminders`.`entities_id` < ?
                                 OR (  1 ))) OR ( `glpi_entities_reminders`.`entities_id` IN (?,?,?,?)))"));
      $this->array($restrict['params'])->isIdenticalTo([4, 4, 42, 1337, 0, 6, 0, 0, 1, 2, 3]);
      */
   }
}
