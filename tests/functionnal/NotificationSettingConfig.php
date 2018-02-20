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

namespace tests\units;

use \DbTestCase;

/* Test for inc/notificationsettingconfig.class.php */

class NotificationSettingConfig extends DbTestCase {

   public function testUpdate() {
      $current_config = \Config::getConfigurationValues('core');

      $this->variable($current_config['use_notifications'])->isEqualTo(0);
      $this->variable($current_config['notifications_mailing'])->isEqualTo(0);
      $this->variable($current_config['notifications_ajax'])->isEqualTo(0);

      $settingconfig = new \NotificationSettingConfig();
      $settingconfig->update([
         'use_notifications' => 1
      ]);

      $current_config = \Config::getConfigurationValues('core');

      $this->variable($current_config['use_notifications'])->isEqualTo(1);
      $this->variable($current_config['notifications_mailing'])->isEqualTo(0);
      $this->variable($current_config['notifications_ajax'])->isEqualTo(0);

      $settingconfig->update([
         'notifications_mailing' => 1
      ]);

      $current_config = \Config::getConfigurationValues('core');

      $this->variable($current_config['use_notifications'])->isEqualTo(1);
      $this->variable($current_config['notifications_mailing'])->isEqualTo(1);
      $this->variable($current_config['notifications_ajax'])->isEqualTo(0);

      $settingconfig->update([
         'use_notifications' => 0
      ]);

      $current_config = \Config::getConfigurationValues('core');

      $this->variable($current_config['use_notifications'])->isEqualTo(0);
      $this->variable($current_config['notifications_mailing'])->isEqualTo(0);
      $this->variable($current_config['notifications_ajax'])->isEqualTo(0);
   }

   public function testShowForm() {
      global $CFG_GLPI;

      $settingconfig = new \NotificationSettingConfig();
      $options = ['display' => false];

      $output = $settingconfig->showForm($options);
      $this->string($output)->isEmpty();

      $this->login();

      $this->output(
         function () use ($settingconfig) {
            $settingconfig->showForm();
         }
      )
         ->contains('Notifications configuration')
         ->notContains('Notification templates');

      $CFG_GLPI['use_notifications'] = 1;

      $this->output(
         function () use ($settingconfig) {
            $settingconfig->showForm();
         }
      )
         ->contains('Notifications configuration')
         ->notContains('Notification templates');

      $CFG_GLPI['notifications_ajax'] = 1;

      $this->output(
         function () use ($settingconfig) {
            $settingconfig->showForm();
         }
      )
         ->contains('Notifications configuration')
         ->contains('Notification templates')
         ->contains('Ajax followups configuration')
         ->notContains('Email followups configuration');

      $CFG_GLPI['notifications_mailing'] = 1;

      $this->output(
         function () use ($settingconfig) {
            $settingconfig->showForm();
         }
      )
         ->contains('Notifications configuration')
         ->contains('Notification templates')
         ->contains('Ajax followups configuration')
         ->contains('Email followups configuration');

      //reset
      $CFG_GLPI['use_notifications'] = 0;
      $CFG_GLPI['notifications_mailing'] = 0;
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
