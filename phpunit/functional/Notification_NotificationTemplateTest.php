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

/* Test for inc/notification_notificationtemplate.class.php */

class Notification_NotificationTemplateTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $this->assertSame('Templates', \Notification_NotificationTemplate::getTypeName(0));
        $this->assertSame('Template', \Notification_NotificationTemplate::getTypeName(1));
        $this->assertSame('Templates', \Notification_NotificationTemplate::getTypeName(2));
        $this->assertSame('Templates', \Notification_NotificationTemplate::getTypeName(10));
    }

    public function testGetTabNameForItem()
    {
        $n_nt = new \Notification_NotificationTemplate();
        $this->assertTrue($n_nt->getFromDB(1));

        $notif = new \Notification();
        $this->assertTrue($notif->getFromDB($n_nt->getField('notifications_id')));

        $_SESSION['glpishow_count_on_tabs'] = 1;

        //not logged => no ACLs
        $name = $n_nt->getTabNameForItem($notif);
        $this->assertSame('', $name);

        $this->login();
        $name = $n_nt->getTabNameForItem($notif);
        $this->assertSame('Templates <span class=\'badge\'>1</span>', $name);

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $name = $n_nt->getTabNameForItem($notif);
        $this->assertSame('Templates', $name);

        $toadd = $n_nt->fields;
        unset($toadd['id']);
        $toadd['mode'] = \Notification_NotificationTemplate::MODE_XMPP;
        $this->assertGreaterThan(0, (int)$n_nt->add($toadd));

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $name = $n_nt->getTabNameForItem($notif);
        $this->assertSame('Templates <span class=\'badge\'>2</span>', $name);
    }

    public function testShowForNotification()
    {
        $notif = new \Notification();
        $this->assertTrue($notif->getFromDB(1));

        //not logged, no ACLs
        ob_start();
        \Notification_NotificationTemplate::showForNotification($notif);
        $output = ob_get_clean();
        $this->assertEmpty($output);

        $this->login();

        ob_start();
        \Notification_NotificationTemplate::showForNotification($notif);
        $output = ob_get_clean();
        $this->assertSame(
            "<div class='center'><table class='tab_cadre_fixehov'><tr><th>ID</th><th>Template</th><th>Mode</th></tr><tr class='tab_bg_2'><td><a  href='/glpi/front/notification_notificationtemplate.form.php?id=1'  title=\"1\">1</a></td><td><a  href='/glpi/front/notificationtemplate.form.php?id=6'  title=\"Alert Tickets not closed\">Alert Tickets not closed</a></td><td>Email</td></tr><tr><th>ID</th><th>Template</th><th>Mode</th></tr></table></div>",
            $output
        );
    }

    public function testGetName()
    {
        $n_nt = new \Notification_NotificationTemplate();
        $this->assertTrue($n_nt->getFromDB(1));
        $this->assertSame(1, $n_nt->getName());
    }

    public function testShowForFormNotLogged()
    {
        //not logged, no ACLs
        ob_start();
        $n_nt = new \Notification_NotificationTemplate();
        $n_nt->showForm(1);
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testShowForForm()
    {
        $this->login();
        ob_start();
        $n_nt = new \Notification_NotificationTemplate();
        $n_nt->showForm(1);
        $output = ob_get_clean();
        $this->assertMatchesRegularExpression('/_glpi_csrf/', $output);
    }

    public function testGetMode()
    {
        $mode = \Notification_NotificationTemplate::getMode(\Notification_NotificationTemplate::MODE_MAIL);
        $expected = [
            'label'  => 'Email',
            'from'   => 'core'
        ];
        $this->assertSame($expected, $mode);

        $mode = \Notification_NotificationTemplate::getMode('not_a_mode');
        $this->assertSame(NOT_AVAILABLE, $mode);
    }

    public function testGetModes()
    {
        $modes = \Notification_NotificationTemplate::getModes();
        $expected = [
            \Notification_NotificationTemplate::MODE_MAIL   => [
                'label'  => 'Email',
                'from'   => 'core'
            ],
            \Notification_NotificationTemplate::MODE_AJAX   => [
                'label'  => 'Browser',
                'from'   => 'core'
            ]
        ];
        $this->assertSame($expected, $modes);

        //register new mode
        \Notification_NotificationTemplate::registerMode(
            'test_mode',
            'A test label',
            'anyplugin'
        );
        $modes = \Notification_NotificationTemplate::getModes();
        $expected['test_mode'] = [
            'label'  => 'A test label',
            'from'   => 'anyplugin'
        ];
        $this->assertSame($expected, $modes);
    }

    public function testGetSpecificValueToDisplay()
    {
        $n_nt = new \Notification_NotificationTemplate();
        $display = $n_nt->getSpecificValueToDisplay('id', 1);
        $this->assertEmpty($display);

        $display = $n_nt->getSpecificValueToDisplay('mode', \Notification_NotificationTemplate::MODE_AJAX);
        $this->assertSame('Browser', $display);

        $display = $n_nt->getSpecificValueToDisplay('mode', 'not_a_mode');
        $this->assertSame('not_a_mode (N/A)', $display);
    }

    public function testGetSpecificValueToSelect()
    {
        $n_nt = new \Notification_NotificationTemplate();
        $select = $n_nt->getSpecificValueToSelect('id', 1);
        $this->assertEmpty($select);

        $select = $n_nt->getSpecificValueToSelect('mode', 'a_name', \Notification_NotificationTemplate::MODE_AJAX);
        //FIXME: why @selected?
        $this->assertMatchesRegularExpression(
            "/<select name='a_name' id='dropdown_a_name\d+'[^>]*><option value='mailing'>Email<\/option><option value='ajax' selected>Browser<\/option><\/select>/",
            $select
        );
    }

    public function testGetModeClass()
    {
        $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL);
        $this->assertSame('NotificationMailing', $class);

        $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'event');
        $this->assertSame('NotificationEventMailing', $class);

        $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'setting');
        $this->assertSame('NotificationMailingSetting', $class);

       //register new mode
        \Notification_NotificationTemplate::registerMode(
            'testmode',
            'A test label',
            'anyplugin'
        );

        $class = \Notification_NotificationTemplate::getModeClass('testmode');
        $this->assertSame('PluginAnypluginNotificationTestmode', $class);

        $class = \Notification_NotificationTemplate::getModeClass('testmode', 'event');
        $this->assertSame('PluginAnypluginNotificationEventTestmode', $class);

        $class = \Notification_NotificationTemplate::getModeClass('testmode', 'setting');
        $this->assertSame('PluginAnypluginNotificationTestmodeSetting', $class);
    }

    public function testHasActiveMode()
    {
        global $CFG_GLPI;
        $this->assertFalse(\Notification_NotificationTemplate::hasActiveMode());
        $CFG_GLPI['notifications_ajax'] = 1;
        $this->assertTrue(\Notification_NotificationTemplate::hasActiveMode());
        $CFG_GLPI['notifications_ajax'] = 0;
    }
}
