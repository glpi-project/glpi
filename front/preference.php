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

use Glpi\Event;

include('../inc/includes.php');

$user = new User();


// Manage lost password
if (isset($_GET['lostpassword'])) {
    Html::nullHeader();
    if (isset($_GET['password_forget_token'])) {
        User::showPasswordForgetChangeForm($_GET['password_forget_token']);
    } else {
        User::showPasswordForgetRequestForm();
    }
    Html::nullFooter();
    exit();
}


Session::checkLoginUser();

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
        Html::header(Preference::getTypeName(1), $_SERVER['PHP_SELF'], 'preference');
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
