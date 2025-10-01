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

use Glpi\Exception\AuthenticationFailedException;

use function Safe\session_destroy;

/**
 * @since 0.85
 */

global $CFG_GLPI;

if (!isset($_SESSION["glpicookietest"]) || ($_SESSION["glpicookietest"] != 'testcookie')) {
    if (!Session::canWriteSessionFiles()) {
        Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=2");
    } else {
        Html::redirect($CFG_GLPI['root_doc'] . "/index.php?error=1");
    }
}

if (isset($_POST['totp_code']) && is_array($_POST['totp_code'])) {
    $_POST['totp_code'] = implode('', $_POST['totp_code']);
}

$remember = ($_POST['login_remember'] ?? 0) && $CFG_GLPI["login_remember_time"];

$auth = new Auth();

// now we can continue with the process...
if (isset($_REQUEST['totp_cancel'])) {
    session_destroy();
    Html::redirect($CFG_GLPI['root_doc'] . '/index.php');
}
if ($auth->login($_POST['login_name'] ?? '', $_POST['login_password'] ?? '', ($_REQUEST["noAUTO"] ?? false), $remember, $_POST['auth'] ?? '')) {
    Auth::redirectIfAuthenticated();
} else {
    throw new AuthenticationFailedException(authentication_errors: $auth->getErrors());
}
