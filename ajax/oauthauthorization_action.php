<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Exception\Http\BadRequestHttpException;

Session::checkRight(OAuthApplication::$rightname, UPDATE);

if (isset($_POST['id']) && isset($_POST['type']) && isset($_POST['request_authorization'])) {
    $application = new OAuthApplication();
    $application->check($_POST['id'], UPDATE);
    $application->redirectToAuthorizationUrl($_POST['type']);
} elseif (isset($_POST['id']) && isset($_POST['refresh'])) {
    $authorization = new OAuthAuthorization();
    $authorization->getFromDB($_POST['id']);
    $authorization->check($_POST['id'], UPDATE);

    if ($authorization->refreshToken()) {
        Session::addMessageAfterRedirect(__s('Token refreshed'), false, INFO);
    } else {
        Session::addMessageAfterRedirect(
            htmlescape($authorization->getLastError() ?? __('Unable to refresh token')),
            false,
            ERROR
        );
    }
    Html::back();
} elseif (isset($_POST['id']) && isset($_POST['test'])) {
    $authorization = new OAuthAuthorization();
    $authorization->getFromDB($_POST['id']);
    $authorization->check($_POST['id'], READ);

    $result = $authorization->testConnection();
    Session::addMessageAfterRedirect(
        htmlescape($result['message']),
        false,
        $result['success'] ? INFO : ERROR
    );
    Html::back();
} elseif (isset($_POST['id']) && isset($_POST['delete'])) {
    $authorization = new OAuthAuthorization();
    $authorization->getFromDB($_POST['id']);
    $authorization->check($_POST['id'], DELETE);

    $authorization->revokeAuthorization();
    Html::back();
} else {
    throw new BadRequestHttpException();
}
