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

/* Test for inc/notificationsettingconfig.class.php */

class NotificationSettingConfigTest extends DbTestCase
{
    public function testUpdate()
    {
        $current_config = \Config::getConfigurationValues('core');

        $this->assertEquals(0, $current_config['use_notifications']);
        $this->assertEquals(0, $current_config['notifications_mailing']);
        $this->assertEquals(0, $current_config['notifications_ajax']);

        $settingconfig = new \NotificationSettingConfig();
        $settingconfig->update([
            'use_notifications' => 1
        ]);

        $current_config = \Config::getConfigurationValues('core');

        $this->assertEquals(1, $current_config['use_notifications']);
        $this->assertEquals(0, $current_config['notifications_mailing']);
        $this->assertEquals(0, $current_config['notifications_ajax']);

        $settingconfig->update([
            'notifications_mailing' => 1
        ]);

        $current_config = \Config::getConfigurationValues('core');

        $this->assertEquals(1, $current_config['use_notifications']);
        $this->assertEquals(1, $current_config['notifications_mailing']);
        $this->assertEquals(0, $current_config['notifications_ajax']);

        $settingconfig->update([
            'use_notifications' => 0
        ]);

        $current_config = \Config::getConfigurationValues('core');

        $this->assertEquals(0, $current_config['use_notifications']);
        $this->assertEquals(0, $current_config['notifications_mailing']);
        $this->assertEquals(0, $current_config['notifications_ajax']);
    }

    public function testShowForm()
    {
        global $CFG_GLPI;

        $settingconfig = new \NotificationSettingConfig();
        $options = ['display' => false];

        $output = $settingconfig->showConfigForm($options);
        $this->assertEmpty(trim($output)); // Only whitespaces, no real content

        $this->login();

        ob_start();
        $settingconfig->showConfigForm();
        $content = ob_get_clean();
        $this->assertStringContainsString('Notifications configuration', $content);
        $this->assertStringNotContainsString('Notification templates', $content);

        $CFG_GLPI['use_notifications'] = 1;

        ob_start();
        $settingconfig->showConfigForm();
        $content = ob_get_clean();
        $this->assertStringContainsString('Notifications configuration', $content);
        $this->assertStringNotContainsString('Notification templates', $content);

        $CFG_GLPI['notifications_ajax'] = 1;

        ob_start();
        $settingconfig->showConfigForm();
        $content = ob_get_clean();
        $this->assertStringContainsString('Notifications configuration', $content);
        $this->assertStringContainsString('Notification templates', $content);
        $this->assertStringContainsString('Browser followups configuration', $content);
        $this->assertStringNotContainsString('Email followups configuration', $content);

        $CFG_GLPI['notifications_mailing'] = 1;

        ob_start();
        $settingconfig->showConfigForm();
        $content = ob_get_clean();
        $this->assertStringContainsString('Notifications configuration', $content);
        $this->assertStringContainsString('Notification templates', $content);
        $this->assertStringContainsString('Browser followups configuration', $content);
        $this->assertStringContainsString('Email followups configuration', $content);

        //reset
        $CFG_GLPI['use_notifications'] = 0;
        $CFG_GLPI['notifications_mailing'] = 0;
        $CFG_GLPI['notifications_ajax'] = 0;
    }
}
