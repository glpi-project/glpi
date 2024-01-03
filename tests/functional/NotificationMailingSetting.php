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

/* Test for inc/notificationmailingsetting.class.php .class.php */

class NotificationMailingSetting extends DbTestCase
{
    public function testGetTable()
    {
        $this->string(\NotificationMailingSetting::getTable())->isIdenticalTo('glpi_configs');
    }

    public function testGetTypeName()
    {
        $this->string(\NotificationMailingSetting::getTypeName())->isIdenticalTo('Email followups configuration');
        $this->string(\NotificationMailingSetting::getTypeName(10))->isIdenticalTo('Email followups configuration');
    }

    public function testDefineTabs()
    {
        $instance = new \NotificationMailingSetting();
        $tabs = $instance->defineTabs();
        $this->array($tabs)
         ->hasSize(1)
         ->isIdenticalTo(['NotificationMailingSetting$1' => 'Setup']);
    }

    public function testGetTabNameForItem()
    {
        $instance = new \NotificationMailingSetting();
        $this->array($instance->getTabNameForItem($instance))->isIdenticalTo(['1' => 'Setup']);
    }

    public function testDisplayTabContentForItem()
    {

        $this->output(
            function () {
                $instance = new \NotificationMailingSetting();
                $instance->displayTabContentForItem($instance);
            }
        )->hasLengthGreaterThan(100);
    }

    public function testGetEnableLabel()
    {
        $settings = new \NotificationMailingSetting();
        $this->string($settings->getEnableLabel())->isIdenticalTo('Enable followups via email');
    }

    public function testGetMode()
    {
        $this->string(\NotificationMailingSetting::getMode())
         ->isIdenticalTo(\Notification_NotificationTemplate::MODE_MAIL);
    }

    public function testShowFormConfig()
    {
        global $CFG_GLPI;

        $this->variable($CFG_GLPI['notifications_mailing'])->isEqualTo(0);

        $this->output(
            function () {
                $instance = new \NotificationMailingSetting();
                $instance->showFormConfig();
            }
        )->contains('Notifications are disabled.');

        $CFG_GLPI['notifications_mailing'] = 1;

        $this->output(
            function () {
                $instance = new \NotificationMailingSetting();
                $instance->showFormConfig();
            }
        )->notContains('Notifications are disabled.');

       //rest to defaults
        $CFG_GLPI['notifications_mailing'] = 0;
    }
}
