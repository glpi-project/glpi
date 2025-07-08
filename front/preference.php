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

use Glpi\Event;
use Glpi\Security\TOTPManager;

$user = new User();

// Manage 2FA
if (isset($_POST['disable_2fa'])) {
    $totp_manager = new TOTPManager();
    $totp_manager->disable2FAForUser(Session::getLoginUserID());
    Html::redirect(Preference::getSearchURL());
} elseif (isset($_POST['secret'], $_POST['totp_code'])) {
    $code = is_array($_POST['totp_code']) ? implode('', $_POST['totp_code']) : $_POST['totp_code'];
    $totp = new TOTPManager();
    if (Session::validateIDOR($_POST) && ($algorithm = $totp->verifyCodeForSecret($code, $_POST['secret'])) !== false) {
        $totp->setSecretForUser($_SESSION['glpiID'], $_POST['secret'], $algorithm);
    } else {
        Session::addMessageAfterRedirect(__s('Invalid code'), false, ERROR);
    }
    Html::redirect(Preference::getSearchURL() . '?regenerate_backup_codes=1');
}

if (
    isset($_POST["update"])
    && ($_POST["id"] == Session::getLoginUserID())
) {
    $user->update($_POST);
    Event::log(
        $_POST["id"],
        "users",
        5,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    if (Session::getCurrentInterface() == "central") {
        Html::header(Preference::getTypeName(1), '', 'preference');
    } else {
        Html::helpHeader(Preference::getTypeName(1));
    }

    $pref = new Preference();
    $pref->display(['main_class' => 'tab_cadre_fixe']);

    if (Session::getCurrentInterface() == "central") {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
