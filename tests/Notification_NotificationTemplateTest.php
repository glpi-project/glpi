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

/* Test for inc/notification_notificationtemplate.class.php .class.php */

class Notification_NotificationTemplateTest extends DbTestCase {

   public function testGetTypeName() {
      $this->assertEquals('Templates', \Notification_NotificationTemplate::getTypeName(0));
      $this->assertEquals('Template', \Notification_NotificationTemplate::getTypeName(1));
      $this->assertEquals('Templates', \Notification_NotificationTemplate::getTypeName(2));
      $this->assertEquals('Templates', \Notification_NotificationTemplate::getTypeName(10));
   }

   public function testGetTabNameForItem() {
      $n_nt = new \Notification_NotificationTemplate();
      $n_nt->getFromDB(1);

      $notif = new \Notification();
      $this->assertTrue($notif->getFromDB($n_nt->getField('notifications_id')));

      $_SESSION['glpishow_count_on_tabs'] = 1;

      //not logged => no ACLs
      $name = $n_nt->getTabNameForItem($notif);
      $this->assertEquals('', $name);

      $this->login();
      $name = $n_nt->getTabNameForItem($notif);
      $this->assertEquals('Templates <sup class=\'tab_nb\'>1</sup>', $name);

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $name = $n_nt->getTabNameForItem($notif);
      $this->assertEquals('Templates', $name);

       /*$ticket3 = getItemByTypeName(Ticket::getType(), '_ticket03');*/
      $toadd = $n_nt->fields;
      unset($toadd['id']);
      $toadd['mode'] = \Notification_NotificationTemplate::MODE_XMPP;
      $this->assertGreaterThan(0, $n_nt->add($toadd));

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $name = $n_nt->getTabNameForItem($notif);
      $this->assertEquals('Templates <sup class=\'tab_nb\'>2</sup>', $name);
   }

   public function testShowForNotification() {
      $notif = new \Notification();
      $notif->getFromDB(1);

      //not logged, no ACLs
      $this->expectOutputString('');
      \Notification_NotificationTemplate::showForNotification($notif);

      $this->login();
      $this->expectOutputString("<div class='center'><table class='tab_cadre_fixehov'><tr><th>ID</th><th>Template</th><th>Mode</th></tr><tr class='tab_bg_2'><td><a  href='/glpi/front/notification_notificationtemplate.form.php?id=1'  title=\"1\">1</a></td><td><a  href='/glpi/front/notificationtemplate.form.php?id=6'  title=\"Alert Tickets not closed\">Alert Tickets not closed</a></td><td>Email</td></tr><tr><th>ID</th><th>Template</th><th>Mode</th></tr></table></div>");
      \Notification_NotificationTemplate::showForNotification($notif);
   }

   public function testGetName() {
      $n_nt = new \Notification_NotificationTemplate();
      $n_nt->getFromDB(1);
      $this->assertEquals('1', $n_nt->getName());
   }

   public function testShowForFormNotLogged() {
      $n_nt = new \Notification_NotificationTemplate();

      //not logged, no ACLs
      $this->expectOutputString('');
      $n_nt->showForm(1);
   }

   public function testShowForForm() {
      $n_nt = new \Notification_NotificationTemplate();

      $this->login();
      $this->expectOutputRegex('/_glpi_csrf/');
      $n_nt->showForm(1);
   }

   public function testGetMode() {
      $mode = \Notification_NotificationTemplate::getMode(\Notification_NotificationTemplate::MODE_MAIL);
      $expected = [
         'label'  => 'Email',
         'from'   => 'core'
      ];
      $this->assertEquals($expected, $mode);

      $mode = \Notification_NotificationTemplate::getMode('not_a_mode');
      $this->assertEquals(NOT_AVAILABLE, $mode);
   }

   public function testGetModes() {
      $modes = \Notification_NotificationTemplate::getModes();
      $expected = [
         \Notification_NotificationTemplate::MODE_MAIL   => [
            'label'  => 'Email',
            'from'   => 'core'
         ],
         \Notification_NotificationTemplate::MODE_AJAX   => [
            'label'  => 'Ajax',
            'from'   => 'core'
         ]
      ];
      $this->assertEquals($expected, $modes);

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
      $this->assertEquals($expected, $modes);
   }

   public function testGetSpecificValueToDisplay() {
      $n_nt = new \Notification_NotificationTemplate();
      $display = $n_nt->getSpecificValueToDisplay('id', 1);
      $this->assertEquals('', $display);

      $display = $n_nt->getSpecificValueToDisplay('mode', \Notification_NotificationTemplate::MODE_AJAX);
      $this->assertEquals('Ajax', $display);

      $display = $n_nt->getSpecificValueToDisplay('mode', 'not_a_mode');
      $this->assertEquals('not_a_mode (N/A)', $display);
   }

   public function testGetSpecificValueToSelect() {

      $n_nt = new \Notification_NotificationTemplate();
      $select = $n_nt->getSpecificValueToSelect('id', 1);
      $this->assertEquals('', $select);

      $select = $n_nt->getSpecificValueToSelect('mode', 'a_name', \Notification_NotificationTemplate::MODE_AJAX);
      $this->assertGreaterThanOrEqual(
         0,
         preg_match(
            "|<select name='ajax' id='dropdown_ajax\d+' size='1'><option value='mailing'>Email</option><option value='ajax'>Ajax</option><option value='test_mode'>A test label</option></select>|",
            $select
         )
      );
   }

   public function testGetModeClass() {
      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL);
      $this->assertEquals('NotificationMailing', $class);

      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'event');
      $this->assertEquals('NotificationEventMailing', $class);

      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'setting');
      $this->assertEquals('NotificationMailingSetting', $class);

      //register new mode
      \Notification_NotificationTemplate::registerMode(
         'testmode',
         'A test label',
         'anyplugin'
      );

      $class = \Notification_NotificationTemplate::getModeClass('testmode');
      $this->assertEquals('PluginAnypluginNotificationTestmode', $class);

      $class = \Notification_NotificationTemplate::getModeClass('testmode', 'event');
      $this->assertEquals('PluginAnypluginNotificationEventTestmode', $class);

      $class = \Notification_NotificationTemplate::getModeClass('testmode', 'setting');
      $this->assertEquals('PluginAnypluginNotificationTestmodeSetting', $class);
   }
}
