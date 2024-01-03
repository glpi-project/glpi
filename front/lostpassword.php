<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

$SECURITY_STRATEGY = 'no_check';

include('../inc/includes.php');

if (
    !$CFG_GLPI['notifications_mailing']
    || !countElementsInTable(
        'glpi_notifications',
        ['itemtype' => 'User', 'event' => 'passwordforget', 'is_active' => 1]
    )
) {
    exit();
}

$user = new User();

// Manage lost password
// REQUEST needed : GET on first access / POST on submit form
if (isset($_REQUEST['password_forget_token'])) {
    if (isset($_POST['password'])) {
        $user->showUpdateForgottenPassword($_REQUEST);
    } else {
        User::showPasswordForgetChangeForm($_REQUEST['password_forget_token']);
    }
} else {
    if (isset($_POST['email'])) {
        $user->showForgetPassword($_POST['email']);
    } else {
        User::showPasswordForgetRequestForm();
    }
}

exit();
