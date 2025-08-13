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

/* Test for inc/notificationmailingsetting.class.php .class.php */

class NotificationMailingSettingTest extends DbTestCase
{
    public function testGetTable()
    {
        $this->assertSame('glpi_configs', \NotificationMailingSetting::getTable());
    }

    public function testGetTypeName()
    {
        $this->assertSame('Email notifications configuration', \NotificationMailingSetting::getTypeName());
        $this->assertSame('Email notifications configuration', \NotificationMailingSetting::getTypeName(10));
    }

    public function testDefineTabs()
    {
        $instance = new \NotificationMailingSetting();
        $tabs = $instance->defineTabs();
        $tabs = array_map('strip_tags', $tabs);
        $this->assertSame(
            ['NotificationMailingSetting$1' => 'Setup'],
            $tabs
        );
    }

    public function testGetTabNameForItem()
    {
        $instance = new \NotificationMailingSetting();
        $tabs = $instance->getTabNameForItem($instance);
        $tabs = array_map('strip_tags', $tabs);
        $this->assertSame(['1' => 'Setup'], $tabs);
    }

    public function testDisplayTabContentForItem()
    {
        ob_start();
        $instance = new \NotificationMailingSetting();
        $instance->displayTabContentForItem($instance);
        $content = ob_get_clean();
        $this->assertGreaterThan(100, strlen($content));
    }

    public function testGetEnableLabel()
    {
        $settings = new \NotificationMailingSetting();
        $this->assertSame('Enable email notifications', $settings->getEnableLabel());
    }

    public function testGetMode()
    {
        $this->assertSame(
            \Notification_NotificationTemplate::MODE_MAIL,
            \NotificationMailingSetting::getMode()
        );
    }

    public function testShowFormConfig()
    {
        global $CFG_GLPI;

        $this->assertEquals(0, $CFG_GLPI['notifications_mailing']);

        ob_start();
        $instance = new \NotificationMailingSetting();
        $instance->showFormConfig();
        $content = ob_get_clean();
        $this->assertStringContainsString('Notifications are disabled.', $content);

        $CFG_GLPI['notifications_mailing'] = 1;

        ob_start();
        $instance = new \NotificationMailingSetting();
        $instance->showFormConfig();
        $content = ob_get_clean();
        $this->assertStringNotContainsString('Notifications are disabled.', $content);

        //reset to defaults
        $CFG_GLPI['notifications_mailing'] = 0;
    }
}
