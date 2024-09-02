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

require_once __DIR__ . '/../NotificationSettingInstance.php';

/* Test for inc/notificationmailingsetting.class.php */

class NotificationSettingInstancetest extends DbTestCase
{
    public function testGetTable()
    {
        $this->assertSame('glpi_configs', \NotificationSetting::getTable());
    }

    public function testGetTypeName()
    {
        $this->expectExceptionMessage('getTypeName must be implemented');
        \NotificationSetting::getTypeName();
    }

    public function testDisplayTabContentForItem()
    {
        $instance = new \NotificationMailingSetting();
        $this->assertTrue(\NotificationSetting::displayTabContentForItem($instance));
    }

    public function testDisableAll()
    {
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
