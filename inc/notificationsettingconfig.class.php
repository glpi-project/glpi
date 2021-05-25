<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Notifications settings configuration class
 */
class NotificationSettingConfig extends CommonDBTM {

   public $table           = 'glpi_configs';
   protected $displaylist  = false;
   static $rightname       = 'config';

   public function update(array $input, $history = 1, $options = []) {
      if (isset($input['use_notifications'])) {
         $config = new Config();
         $tmp = [
            'id'                 => 1,
            'use_notifications'  => $input['use_notifications']
         ];
         $config->update($tmp);
         //disable all notifications types if notifications has been disabled
         if ($tmp['use_notifications'] == 0) {
            $modes = Notification_NotificationTemplate::getModes();
            foreach (array_keys($modes) as $mode) {
               $input['notifications_' . $mode] = 0;
            }
         }
      }

      $config = new Config();
      foreach ($input as $k => $v) {
         if (substr($k, 0, strlen('notifications_')) === 'notifications_') {
            $tmp = [
               'id'  => 1,
               $k    => $v
            ];
            $config->update($tmp);
         }
      }
   }

   /**
    * Show configuration form
    *
    * @return string|void
    */
   public function showForm($options = []) {
      global $CFG_GLPI;

      if (!isset($options['display'])) {
         $options['display'] = true;
      }

      $modes = Notification_NotificationTemplate::getModes();
      foreach ($modes as $mode_key => &$mode) {
         $settings_class = Notification_NotificationTemplate::getModeClass($mode_key, 'setting');
         $settings = new $settings_class();
         $mode['label']          = $settings->getEnableLabel();
         $mode['label_settings'] = $settings->getTypeName();
         $mode['is_active']      = (bool) $CFG_GLPI["notifications_$mode_key"];
         $mode['setting_url']    = $settings->getFormURL();
         $mode['icon']           = $settings::getIcon();
      }

      $out = TemplateRenderer::getInstance()->render(
         'components/setup/setup_notifications.html.twig',
         [
            'use_notifications' => (bool) $CFG_GLPI['use_notifications'],
            'has_active_mode'   => Notification_NotificationTemplate::hasActiveMode(),
            'can_update_config' => Session::haveRight("config", UPDATE) > 0,
            'modes'             => $modes,
         ]
      );

      if ($options['display']) {
         echo $out;
      } else {
         return $out;
      }
   }
}
