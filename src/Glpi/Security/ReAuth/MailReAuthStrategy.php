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

declare(strict_types=1);

namespace Glpi\Security\ReAuth;

use Auth;
use Override;
use Symfony\Component\HttpFoundation\Request;
use User;

/**
 * Mail server re-authentication strategy.
 *
 * Verifies the user identity by logging in on the IMAP/POP server with the password provided in
 * the prompt, reusing the same mail server configuration as the regular login flow.
 *
 * It fails closed: if the server is unreachable, the verification fails and no bypass is granted,
 * so the sensitive action stays protected.
 */
final class MailReAuthStrategy extends InPlaceReAuthStrategy
{
    #[Override]
    public function verify(int $users_id, Request $request): bool
    {
        $mail_password = (string) $request->request->get('user_input', '');

        // Guard against empty password and null-byte injection: some servers accept an
        // unauthenticated login on an empty password, which would turn this check into an
        // authentication bypass.
        if ($mail_password === '' || str_contains($mail_password, "\0")) {
            return false;
        }

        // user not in db
        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        // no connection string
        $mail_method = Auth::getMethodsByID(Auth::MAIL, (int) $user->fields['auths_id']);
        if (empty($mail_method['connect_string'])) {
            return false;
        }

        // connection_imap() returns false on a wrong password as well as on an unreachable
        // server. Both cases fail closed here.
        return (new Auth())->connection_imap(
            $mail_method['connect_string'],
            $user->fields['name'],
            $mail_password
        ) !== false;
    }

    #[Override]
    public function isAvailable(int $users_id, int $entities_id = 0): bool
    {
        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        if (SessionAuthType::resolve($user) !== Auth::MAIL) {
            return false;
        }

        // A strategy whose verify() can never succeed is not available: without a server to log
        // in against, the prompt would be a dead end, as no lower priority strategy would be
        // reached to take over.
        $mail_method = Auth::getMethodsByID(Auth::MAIL, (int) $user->fields['auths_id']);

        return !empty($mail_method['connect_string']);
    }

    #[Override]
    public function getLabel(): string
    {
        return __('Password');
    }

    #[Override]
    public function getPromptTemplate(): string
    {
        return 'pages/reauth/password_form.html.twig';
    }

    #[Override]
    public function getPriority(): int
    {
        return 50;
    }
}
