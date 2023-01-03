<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 *  This class manages the ajax notifications settings
 */
class NotificationAjaxSetting extends NotificationSetting
{
    public static function getTypeName($nb = 0)
    {
        return __('Browser followups configuration');
    }


    public function getEnableLabel()
    {
        return __('Enable followups from browser');
    }


    public static function getMode()
    {
        return Notification_NotificationTemplate::MODE_AJAX;
    }


    public function showFormConfig()
    {
        global $CFG_GLPI;

        echo "<form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' method='post'>";
        echo "<div>";
        echo "<input type='hidden' name='id' value='1'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'>" .
           "<th colspan='4'>" . _n('Browser notification', 'Browser notifications', Session::getPluralNumber()) .
           "</th></tr>";

        if ($CFG_GLPI['notifications_ajax']) {
            $sounds = [
                'sound_a' => __('Sound') . ' A',
                'sound_b' => __('Sound') . ' B',
                'sound_c' => __('Sound') . ' C',
                'sound_d' => __('Sound') . ' D',
            ];

            echo "<tr class='tab_bg_2'>";
            echo "<td> " . __('Default notification sound') . "</td><td>";
            $rand_sound = mt_rand();
            Dropdown::showFromArray("notifications_ajax_sound", $sounds, [
                'value'               => $CFG_GLPI["notifications_ajax_sound"],
                'display_emptychoice' => true,
                'emptylabel'          => __('Disabled'),
                'rand'                => $rand_sound,
            ]);
            echo "</td><td colspan='2'>&nbsp;</td></tr>";

            echo "<tr class='tab_bg_2'><td>" . __('Time to check for new notifications (in seconds)') .
              "</td>";
            echo "<td>";
            Dropdown::showNumber(
                'notifications_ajax_check_interval',
                ['value' => $CFG_GLPI["notifications_ajax_check_interval"],
                    'min'   => 5,
                    'max'   => 120,
                    'step'  => 5
                ]
            );
            echo "</td>";
            echo "<td>" . __('URL of the icon') . "</td>";
            echo "<td><input type='text' name='notifications_ajax_icon_url' value='" .
                    $CFG_GLPI["notifications_ajax_icon_url"] . "' " .
                    "placeholder='{$CFG_GLPI['root_doc']}/pics/glpi.png'/>";
            echo "</td></tr>";
        } else {
            echo "<tr><td colspan='4'>" . __('Notifications are disabled.') .
              "<a href='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>" .
                __('See configuration') .  "</a></td></tr>";
        }
        $options['candel']     = false;
        if ($CFG_GLPI['notifications_ajax']) {
            $options['addbuttons'] = ['test_ajax_send' => __('Send a test browser notification to you')];
        }
        $this->showFormButtons($options);
    }

    public static function getIcon()
    {
        return "far fa-comment-alt";
    }
}
