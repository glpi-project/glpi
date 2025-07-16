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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Error\ErrorHandler;
use Glpi\Event;
use Glpi\Http\RedirectResponse;
use Glpi\Mail\SMTP\OauthConfig;

Session::checkRight("config", UPDATE);

if (isset($_POST["update"])) {
    $config = new Config();
    $config->update($_POST);
    Event::log(0, "system", 3, "setup", sprintf(
        __('%1$s edited the emails notifications configuration'),
        $_SESSION["glpiname"] ?? __("Unknown"),
    ));

    $redirect_to_smtp_oauth = $_SESSION['redirect_to_smtp_oauth'] ?? false;
    unset($_SESSION['redirect_to_smtp_oauth']);
    if ($redirect_to_smtp_oauth) {
        $provider = OauthConfig::getInstance()->getSmtpOauthProvider();

        if ($provider !== null) {
            try {
                $auth_url = $provider->getAuthorizationUrl();
                $_SESSION['smtp_oauth2_state'] = $provider->getState();
                return new RedirectResponse($auth_url);
            } catch (Throwable $e) {
                ErrorHandler::logCaughtException($e);
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(_x('oauth', 'Authorization failed with error: %s'), $e->getMessage())),
                    false,
                    ERROR
                );
                Html::back();
            }
        }
    }

    Html::back();
}

$menus = ["config", "notification", "config"];
$config_id = Config::getConfigIDForContext('core');
NotificationMailingSetting::displayFullPageForItem($config_id, $menus);
