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

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("config", UPDATE);
$notificationajax = new NotificationAjaxSetting();

if (!empty($_POST["test_ajax_send"])) {
    NotificationAjax::testNotification();
    Html::back();
} elseif (!empty($_POST["update"])) {
    $config = new Config();
    $config->update($_POST);
    Event::log(0, "system", 3, "setup", sprintf(
        __('%1$s edited the browsers notifications configuration'),
        $_SESSION["glpiname"] ?? __("Unknown"),
    ));
    Html::back();
}

$menus = ["config", "notification", "config"];
$config_id = Config::getConfigIDForContext('core');
NotificationAjaxSetting::displayFullPageForItem($config_id, $menus);
