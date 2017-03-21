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

/* Test for inc/notificationsettingconfig.class.php .class.php */

class NotificationSettingConfigTest extends DbTestCase {

   public function testUpdate() {
      $current_config = Config::getConfigurationValues('core');

      $this->assertEquals(0, $current_config['use_notifications']);
      $this->assertEquals(0, $current_config['notifications_mailing']);
      $this->assertEquals(0, $current_config['notifications_ajax']);

      $settingconfig = new \NotificationSettingConfig();
      $settingconfig->update([
         'use_notifications' => 1
      ]);

      $current_config = Config::getConfigurationValues('core');

      $this->assertEquals(1, $current_config['use_notifications']);
      $this->assertEquals(0, $current_config['notifications_mailing']);
      $this->assertEquals(0, $current_config['notifications_ajax']);

      $settingconfig->update([
         'notifications_mailing' => 1
      ]);

      $current_config = Config::getConfigurationValues('core');

      $this->assertEquals(1, $current_config['use_notifications']);
      $this->assertEquals(1, $current_config['notifications_mailing']);
      $this->assertEquals(0, $current_config['notifications_ajax']);

      $settingconfig->update([
         'use_notifications' => 0
      ]);

      $current_config = Config::getConfigurationValues('core');

      $this->assertEquals(0, $current_config['use_notifications']);
      $this->assertEquals(0, $current_config['notifications_mailing']);
      $this->assertEquals(0, $current_config['notifications_ajax']);
   }

   public function testShowForm() {
      global $CFG_GLPI;

      $settingconfig = new \NotificationSettingConfig();
      $options = ['display' => false];

      $output = $settingconfig->showForm($options);
      $this->assertEquals('', $output);

      $this->login();

      $output = $settingconfig->showForm($options);
      $this->assertContains('Notifications configuration', $output);
      $this->assertNotContains('Notification templates', $output);

      $CFG_GLPI['use_notifications'] = 1;
      $output = $settingconfig->showForm($options);
      $this->assertContains('Notifications configuration', $output);
      $this->assertNotContains('Notification templates', $output);

      $CFG_GLPI['notifications_ajax'] = 1;
      $output = $settingconfig->showForm($options);
      $this->assertContains('Notifications configuration', $output);
      $this->assertContains('Notification templates', $output);
      $this->assertContains('Ajax followups configuration', $output);
      $this->assertNotContains('Email followups configuration', $output);

      $CFG_GLPI['notifications_mailing'] = 1;
      $output = $settingconfig->showForm($options);
      $this->assertContains('Notifications configuration', $output);
      $this->assertContains('Notification templates', $output);
      $this->assertContains('Ajax followups configuration', $output);
      $this->assertContains('Email followups configuration', $output);

      //reset
      $CFG_GLPI['use_notifications'] = 0;
      $CFG_GLPI['notifications_mailing'] = 0;
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
