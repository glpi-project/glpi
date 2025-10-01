<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

/**
 *  This class manages the ajax notifications settings
 */
class NotificationAjaxSetting extends NotificationSetting
{
    public static function getTypeName($nb = 0)
    {
        return __('Browser notifications configuration');
    }

    public function getEnableLabel()
    {
        return __('Enable browser notifications');
    }

    public static function getMode()
    {
        return Notification_NotificationTemplate::MODE_AJAX;
    }

    public function showFormConfig()
    {
        global $CFG_GLPI;

        if ($CFG_GLPI['notifications_ajax']) {
            $crontask = new CronTask();
            $crontask->getFromDBbyName('QueuedNotification', 'queuednotificationcleanstaleajax');

            TemplateRenderer::getInstance()->display('pages/setup/notification/ajax_setting.html.twig', [
                'stale_crontask_name' => $crontask->getName(),
                'item' => $this,
                'params' => [
                    'candel' => false,
                    'addbuttons' => ['test_ajax_send' => __('Send a test browser notification to you')],
                ],
            ]);
        } else {
            $twig_params = ['message' => __('Notifications are disabled.')];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="alert alert-warning">
                    <a href="{{ path('front/setup.notification.php') }}">{{ message }}</a>
                </div>
TWIG, $twig_params);
        }
    }

    public static function getIcon()
    {
        return "ti ti-message";
    }
}
