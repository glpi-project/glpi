<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

/* Test for inc/notification_notificationtemplate.class.php */

class Notification_NotificationTemplate extends DbTestCase {

   public function testGetTypeName() {
      $this->string(\Notification_NotificationTemplate::getTypeName(0))->isIdenticalTo('Templates');
      $this->string(\Notification_NotificationTemplate::getTypeName(1))->isIdenticalTo('Template');
      $this->string(\Notification_NotificationTemplate::getTypeName(2))->isIdenticalTo('Templates');
      $this->string(\Notification_NotificationTemplate::getTypeName(10))->isIdenticalTo('Templates');
   }

   public function testGetTabNameForItem() {
      $n_nt = new \Notification_NotificationTemplate();
      $this->boolean($n_nt->getFromDB(1))->isTrue();

      $notif = new \Notification();
      $this->boolean($notif->getFromDB($n_nt->getField('notifications_id')))->isTrue();

      $_SESSION['glpishow_count_on_tabs'] = 1;

      //not logged => no ACLs
      $name = $n_nt->getTabNameForItem($notif);
      $this->string($name)->isIdenticalTo('');

      $this->login();
      $name = $n_nt->getTabNameForItem($notif);
      $this->string($name)->isIdenticalTo('Templates <sup class=\'tab_nb\'>1</sup>');

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $name = $n_nt->getTabNameForItem($notif);
      $this->string($name)->isIdenticalTo('Templates');

      $toadd = $n_nt->fields;
      unset($toadd['id']);
      $toadd['mode'] = \Notification_NotificationTemplate::MODE_XMPP;
      $this->integer((int)$n_nt->add($toadd))->isGreaterThan(0);

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $name = $n_nt->getTabNameForItem($notif);
      $this->string($name)->isIdenticalTo('Templates <sup class=\'tab_nb\'>2</sup>');
   }

   public function testShowForNotification() {
      $notif = new \Notification();
      $this->boolean($notif->getFromDB(1))->isTrue();

      //not logged, no ACLs
      $this->output(
         function () use ($notif) {
            \Notification_NotificationTemplate::showForNotification($notif);
         }
      )->isEmpty();

      $this->login();

      $this->output(
         function () use ($notif) {
            \Notification_NotificationTemplate::showForNotification($notif);
         }
      )->isIdenticalTo("<div class='center'><table class='tab_cadre_fixehov'><tr><th>ID</th><th>Template</th><th>Mode</th></tr><tr class='tab_bg_2'><td><a  href='/glpi/front/notification_notificationtemplate.form.php?id=1'  data-toggle=\"tooltip\" title=\"1\">1</a></td><td><a  href='/glpi/front/notificationtemplate.form.php?id=6'  data-toggle=\"tooltip\" title=\"Alert Tickets not closed\">Alert Tickets not closed</a></td><td>Email</td></tr><tr><th>ID</th><th>Template</th><th>Mode</th></tr></table></div>");
   }

   public function testGetName() {
      $n_nt = new \Notification_NotificationTemplate();
      $this->boolean($n_nt->getFromDB(1))->isTrue();
      $this->string($n_nt->getName())->isIdenticalTo('1');
   }

   public function testShowForFormNotLogged() {
      //not logged, no ACLs
      $this->output(
         function () {
            $n_nt = new \Notification_NotificationTemplate();
            $n_nt->showForm(1);
         }
      )->isEmpty();
   }

   public function testShowForForm() {

      $this->login();
      $this->output(
         function () {
            $n_nt = new \Notification_NotificationTemplate();
            $n_nt->showForm(1);
         }
      )->matches('/_glpi_csrf/');
   }

   public function testGetMode() {
      $mode = \Notification_NotificationTemplate::getMode(\Notification_NotificationTemplate::MODE_MAIL);
      $expected = [
         'label'  => 'Email',
         'from'   => 'core'
      ];
      $this->array($mode)->isIdenticalTo($expected);

      $mode = \Notification_NotificationTemplate::getMode('not_a_mode');
      $this->string($mode)->isIdenticalTo(NOT_AVAILABLE);
   }

   public function testGetModes() {
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
      $this->array($modes)->isIdenticalTo($expected);

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
      $this->array($modes)->isIdenticalTo($expected);
   }

   public function testGetSpecificValueToDisplay() {
      $n_nt = new \Notification_NotificationTemplate();
      $display = $n_nt->getSpecificValueToDisplay('id', 1);
      $this->string($display)->isEmpty();

      $display = $n_nt->getSpecificValueToDisplay('mode', \Notification_NotificationTemplate::MODE_AJAX);
      $this->string($display)->isIdenticalTo('Browser');

      $display = $n_nt->getSpecificValueToDisplay('mode', 'not_a_mode');
      $this->string($display)->isIdenticalTo('not_a_mode (N/A)');
   }

   public function testGetSpecificValueToSelect() {
      $n_nt = new \Notification_NotificationTemplate();
      $select = $n_nt->getSpecificValueToSelect('id', 1);
      $this->string($select)->isEmpty();

      $select = $n_nt->getSpecificValueToSelect('mode', 'a_name', \Notification_NotificationTemplate::MODE_AJAX);
      //FIXME: why @selected?
      $this->string($select)->matches(
         "/<select name='a_name' id='dropdown_a_name\d+' class='forSelect2' size='1'><option value='mailing'>Email<\/option><option value='ajax' selected>Browser<\/option><\/select>/"
      );
   }

   public function testGetModeClass() {
      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL);
      $this->string($class)->isIdenticalTo('NotificationMailing');

      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'event');
      $this->string($class)->isIdenticalTo('NotificationEventMailing');

      $class = \Notification_NotificationTemplate::getModeClass(\Notification_NotificationTemplate::MODE_MAIL, 'setting');
      $this->string($class)->isIdenticalTo('NotificationMailingSetting');

      //register new mode
      \Notification_NotificationTemplate::registerMode(
         'testmode',
         'A test label',
         'anyplugin'
      );

      $class = \Notification_NotificationTemplate::getModeClass('testmode');
      $this->string($class)->isIdenticalTo('PluginAnypluginNotificationTestmode');

      $class = \Notification_NotificationTemplate::getModeClass('testmode', 'event');
      $this->string($class)->isIdenticalTo('PluginAnypluginNotificationEventTestmode');

      $class = \Notification_NotificationTemplate::getModeClass('testmode', 'setting');
      $this->string($class)->isIdenticalTo('PluginAnypluginNotificationTestmodeSetting');
   }

   public function testHasActiveMode() {
      global $CFG_GLPI;
      $this->boolean(\Notification_NotificationTemplate::hasActiveMode())->isFalse();
      $CFG_GLPI['notifications_ajax'] = 1;
      $this->boolean(\Notification_NotificationTemplate::hasActiveMode())->isTrue();
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
