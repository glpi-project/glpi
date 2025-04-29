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

$SECURITY_STRATEGY = 'no_check';

/** @var array $CFG_GLPI */
global $CFG_GLPI;

include('../inc/includes.php');

// Cannot use `Session::checkLoginUser()` as it block users that have their password expired to be able to change it.
// Indeed, when password expired, sessions is loaded without profiles nor rights, and `Session::checkLoginUser()`
// considers it as an invalid session.
if (Session::getLoginUserID() === false) {
    Html::redirectToLogin();
}

switch (Session::getCurrentInterface()) {
    case 'central':
        Html::header(__('Update password'), $_SERVER['PHP_SELF']);
        break;
    case 'helpdesk':
        Html::helpHeader(__('Update password'));
        break;
    default:
        Html::simpleHeader(__('Update password'));
        break;
}

$user = new User();
$user->getFromDB(Session::getLoginUserID());

$success  = false;
$error_messages = [];

if (array_key_exists('update', $_POST)) {
    $current_password = $_POST['current_password'];
    if (!Auth::checkPassword($current_password, $user->fields['password'])) {
        $error_messages = [__('Incorrect password')];
    } else {
        $input = [
            'id'               => $user->fields['id'],
            'current_password' => $_POST['current_password'],
            'password'         => $_POST['password'],
            'password2'        => $_POST['password2'],
        ];
        if ($input['password'] === $input['current_password']) {
            $error_messages = [__('The new password must be different from current password')];
        } elseif ($input['password'] !== $input['password2']) {
            $error_messages = [__('The two passwords do not match')];
        } else {
            try {
                Config::validatePassword($input['password'], false);
                if ($user->update($input)) {
                    $success = true;
                } else {
                    $error_messages = [__('An error occurred during password update')];
                }
            } catch (\Glpi\Exception\PasswordTooWeakException $exception) {
                $error_messages = $exception->getMessages();
            }
        }
    }
}

if ($success) {
    echo '<table class="tab_cadre">';
    echo '<tr><th colspan="2">' . __('Password update') . '</th></tr>';
    echo '<tr>';
    echo '<td>';
    echo __('Your password has been successfully updated.');
    echo '<br />';
    echo '<a href="' . $CFG_GLPI['root_doc'] . '/front/logout.php?noAUTO=1">' . __('Log in') . '</a>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
} else {
    $user->showPasswordUpdateForm($error_messages);
}


switch (Session::getCurrentInterface()) {
    case 'central':
        Html::footer();
        break;
    case 'helpdesk':
        Html::helpFooter();
        break;
    default:
        Html::nullFooter();
        break;
}
