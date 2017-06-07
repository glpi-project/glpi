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

/* Test for inc/notificationajaxsetting.class.php .class.php */

class NotificationAjaxSetting extends DbTestCase {

   public function testGetTable() {
      $this->string(\NotificationAjaxSetting::getTable())->isIdenticalTo('glpi_configs');
   }

   public function testGetTypeName() {
      $this->string(\NotificationAjaxSetting::getTypeName())->isIdenticalTo('Ajax followups configuration');
      $this->string(\NotificationAjaxSetting::getTypeName(10))->isIdenticalTo('Ajax followups configuration');
   }

   public function testDefineTabs() {
      $instance = new \NotificationAjaxSetting();
      $tabs = $instance->defineTabs();
      $this->array($tabs)
         ->hasSize(1)
         ->isIdenticalTo(['NotificationAjaxSetting$1' => 'Setup']);
   }

   public function testGetTabNameForItem() {
      $instance = new \NotificationAjaxSetting();
      $this->array($instance->getTabNameForItem($instance))->isIdenticalTo(['1' => 'Setup']);
   }

   public function testDisplayTabContentForItem() {
      $this->output(
         function () {
            $instance = new \NotificationAjaxSetting();
            $instance->displayTabContentForItem($instance);
         }
      )->hasLengthGreaterThan(100);
   }

   public function testGetEnableLabel() {
      $this->string(\NotificationAjaxSetting::getEnableLabel())->isIdenticalTo('Enable followups via ajax calls');
   }

   public function testGetMode() {
      $this->string(\NotificationAjaxSetting::getMode())
         ->isIdenticalTo(\Notification_NotificationTemplate::MODE_AJAX);
   }

   public function testShowFormConfig() {
      global $CFG_GLPI;

      $this->variable($CFG_GLPI['notifications_ajax'])->isEqualTo(0);

      $this->output(
         function () {
            $instance = new \NotificationAjaxSetting();
            $instance->showFormConfig();
         }
      )->contains('Notifications are disabled.');

      $CFG_GLPI['notifications_ajax'] = 1;

      $this->output(
         function () {
            $instance = new \NotificationAjaxSetting();
            $instance->showFormConfig();
         }
      )->notContains('Notifications are disabled.');

      //rest to defaults
      $CFG_GLPI['notifications_ajax'] = 0;
   }
}
