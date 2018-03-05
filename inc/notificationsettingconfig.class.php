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

      $out = '';
      if (Session::haveRight("config", UPDATE)) {
         $out .= "<div class='center notifs_setup'>";
         $out .= "<form method='POST' action='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>";

         $out .= "<table class='tab_cadre'>";
         $out .= "<tr><th colspan='3'>" . __('Notifications configuration') . "</th></tr>";
         if ($CFG_GLPI['use_notifications'] && !Notification_NotificationTemplate::hasActiveMode()) {
            $out .= "<tr><td colspan='3'><div class='warning'><i class='fa fa-exclamation-triangle'></i>".__('You must enable at least one notification mode.')."</div></td></tr>";
         }
         $out .= "<tr>";
         $out .= "<td>" . __('Enable followup') . "</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo('use_notifications', $CFG_GLPI['use_notifications'], -1, ['display' => false]);
         $out .= "</td>";
         $out .= "</tr>";

         foreach (array_keys($modes) as $mode) {
            $settings_class = Notification_NotificationTemplate::getModeClass($mode, 'setting');
            $settings = new $settings_class();
            $classes[$mode] = $settings;

            $out .= "<tr>";
            $out .= "<td>" . $settings->getEnableLabel() . "</td>";
            $out .= "<td>";
            $out .= Dropdown::showYesNo("notifications_$mode", $CFG_GLPI["notifications_$mode"], -1, ['display' => false, 'disabled' => !$CFG_GLPI['use_notifications']]);
            $out .= "</td>";
            $out .= "</tr>";
         }

         $out .= "<tr><td colspan='3' class='center'><input class='submit' type='submit' value='" . __('Save')  . "'/></td></tr>";
         $out .= "</table>";
         $out .= Html::closeForm(false);

         $js = "$(function(){
            $('[name=use_notifications]').on('change', function() {
               var _val = $(this).find('option:selected').val();
               if (_val == '1') {
                  $('select[name!=use_notifications]').removeAttr('disabled');
               } else {
                  $('select[name!=use_notifications]').select2('enable', false);
               }
            });
         })";
         $out .= Html::scriptBlock($js);
      }

      $notifs_on = false;
      if ($CFG_GLPI['use_notifications']) {
         foreach (array_keys($modes) as $mode) {
            if ($CFG_GLPI['notifications_' . $mode]) {
               $notifs_on = true;
               break;
            }
         }
      }

      if ($notifs_on) {
         $out .= "<table class='tab_cadre'>";
         $out .= "<tr><th>" . _n('Notification', 'Notifications', 2)."</th></tr>";

         /* Glocal parameters */
         if (Session::haveRight("config", READ)) {
            $out .= "<tr class='tab_bg_1'><td class='center'><a href='notificationtemplate.php'>" .
                  _n('Notification template', 'Notification templates', 2) ."</a></td> </tr>";
         }

         if (Session::haveRight("notification", READ) && $notifs_on) {
            $out .= "<tr class='tab_bg_1'><td class='center'>".
                  "<a href='notification.php'>". _n('Notification', 'Notifications', 2)."</a></td></tr>";
         } else {
            $out .= "<tr class='tab_bg_1'><td class='center'>" .
               __('Unable to configure notifications: please configure at least one followup type using the above configuration.') .
                     "</td></tr>";
         }

         /* Per notification parameters */
         foreach (array_keys($modes) as $mode) {
            if (Session::haveRight("config", UPDATE) && $CFG_GLPI['notifications_' . $mode]) {
               $settings = $classes[$mode];
               $out .= "<tr class='tab_bg_1'><td class='center'>".
                  "<a href='" . $settings->getFormURL() ."'>". $settings->getTypeName() .
                  "</a></td></tr>";
            }
         }

         $out .= "</table>";
         $out .= "</div>";
      }

      if ($options['display']) {
         echo $out;
      } else {
         return $out;
      }
   }
}
