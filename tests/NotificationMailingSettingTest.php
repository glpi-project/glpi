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

class NotificationMailingSettingTest extends DbTestCase {

   public function testGetTable() {
      $this->assertEquals('glpi_configs', \NotificationMailingSetting::getTable());
   }

   public function testGetTypeName() {
      $this->assertEquals('Email followups configuration', \NotificationMailingSetting::getTypeName());
      $this->assertEquals('Email followups configuration', \NotificationMailingSetting::getTypeName(10));
   }

   public function testDefineTabs() {
      $instance = new \NotificationMailingSetting();
      $tabs = $instance->defineTabs();
      $this->assertCount(1, $tabs);
      $this->assertEquals(['NotificationMailingSetting$1' => 'Setup'], $tabs);
   }

   public function testGetTabNameForItem() {
      $instance = new \NotificationMailingSetting();
      $this->assertEquals(['1' => 'Setup'], $instance->getTabNameForItem($instance));
   }

   public function testDisplayTabContentForItem() {
      $instance = new \NotificationMailingSetting();

      ob_start();
      $instance->displayTabContentForItem($instance);
      $out = ob_get_contents();
      ob_end_clean();

      $this->assertGreaterThanOrEqual(100, strlen($out));
   }

   public function testGetEnableLabel() {
      $this->assertEquals('Enable followups via email', \NotificationMailingSetting::getEnableLabel());
   }

   public function testGetMode() {
      $this->assertEquals(
         \Notification_NotificationTemplate::MODE_MAIL,
         \NotificationMailingSetting::getMode()
      );
   }

   public function testShowFormConfig() {
      global $CFG_GLPI;

      $instance = new \NotificationMailingSetting();

      $this->assertEquals(0, $CFG_GLPI['notifications_mailing']);

      ob_start();
      $instance->showFormConfig();
      $out = ob_get_contents();
      ob_end_clean();

      $match = strpos($out, 'Notifications are disabled.');
      $this->assertGreaterThanOrEqual(1, $match);

      $CFG_GLPI['notifications_mailing'] = 1;

      ob_start();
      $instance->showFormConfig();
      $out = ob_get_contents();
      ob_end_clean();

      $match = strpos($out, 'Notifications are disabled.');
      $this->assertFalse($match);

      //rest to defaults
      $CFG_GLPI['notifications_mailing'] = 0;
   }
}
