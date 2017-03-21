<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/notificationmailingsetting.class.php .class.php */

class NotificationSettingTest extends DbTestCase {

   public function testGetTable() {
      $this->assertEquals('glpi_configs', \NotificationSetting::getTable());
   }

   public function testGetTypeName() {
      $success = false;
      try {
         \NotificationSetting::getTypeName();
         $success = true;
      } catch (\RuntimeException $e) {
         $this->assertEquals('getTypeName must be implemented', $e->getMessage());
      }

      $this->assertFalse($success);
   }

   public function testDisplayTabContentForItem() {
      $instance = new \NotificationMailingSetting();
      $this->assertEquals(true, \NotificationSetting::displayTabContentForItem($instance));
   }

   public function testDisableAll() {
      global $CFG_GLPI;

      $this->assertEquals(0, $CFG_GLPI['use_notifications']);
      $this->assertEquals(0, $CFG_GLPI['notifications_mailing']);
      $this->assertArrayNotHasKey('notifications_xyz', $CFG_GLPI);

      $CFG_GLPI['use_notifications'] = 1;
      $CFG_GLPI['notifications_mailing'] = 1;
      $CFG_GLPI['notifications_xyz'] = 1;

      \NotificationSetting::disableAll();

      $this->assertEquals(0, $CFG_GLPI['use_notifications']);
      $this->assertEquals(0, $CFG_GLPI['notifications_mailing']);
      $this->assertEquals(0, $CFG_GLPI['notifications_xyz']);
   }
}
