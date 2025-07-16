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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;

// Cannot use `Session::checkLoginUser()` as it block users that have their password expired to be able to change it.
// Indeed, when password expired, sessions is loaded without profiles nor rights, and `Session::checkLoginUser()`
// considers it as an invalid session.
if (Session::getLoginUserID() === false) {
    throw new AccessDeniedHttpException();
}

switch (Session::getCurrentInterface()) {
    case 'central':
        Html::header(__('Update password'));
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
        } elseif ($user->validatePassword($input['password'], $error_messages)) {
            // Password validation was successfull
            if ($user->update($input)) {
                $success = true;
            } else {
                $error_messages = [__('An error occurred during password update')];
            }
        }
    }
}

if ($success) {
    $twig_params = [
        'title' => __('Password update'),
        'message' => __('Your password has been successfully updated.'),
        'btn_label' => __('Log in'),
    ];
    // language=Twig
    echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
        <div class="d-flex justify-content-center">
            <div class="alert alert-success">
                <div class="alert-title">{{ title }}</div>
                <div>{{ message }}</div>
                <div class="d-flex flex-row-reverse mt-3">
                    <a href="{{ path('front/logout.php') }}?noAUTO=1" role="button" class="btn btn-primary">{{ btn_label }}</a>
                </div>
            </div>
        </div>
TWIG, $twig_params);
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
