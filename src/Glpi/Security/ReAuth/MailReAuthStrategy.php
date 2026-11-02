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

namespace Glpi\Security\ReAuth;

use Auth;
use Override;
use Symfony\Component\HttpFoundation\Request;
use User;

/**
 * Mail server (IMAP) re-authentication strategy.
 *
 * Verifies the user identity by logging in to the mail server with the password
 * provided in the prompt, reusing the same mail server configuration as the
 * regular login flow.
 *
 * It fails closed: if the mail server is unreachable, the verification fails
 * and no bypass is granted, so the sensitive action stays protected.
 */
final class MailReAuthStrategy extends InPlaceReAuthStrategy
{
    #[Override]
    public function verify(int $users_id, Request $request): bool
    {
        $mail_password = (string) $request->request->get('user_input', '');
        if ($mail_password === '' || str_contains($mail_password, "\0")) {
            return false;
        }

        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        $mail_method = Auth::getMethodsByID(Auth::MAIL, (int) $user->fields['auths_id']);
        if ($mail_method === []) {
            return false;
        }

        return (new Auth())->connection_imap(
            $mail_method['connect_string'],
            $user->fields['name'],
            $mail_password
        ) === true;
    }

    #[Override]
    public function isAvailable(int $users_id, int $entities_id = 0): bool
    {
        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        $session_authtype = $_SESSION['glpiauthtype'] ?? Auth::NOT_YET_AUTHENTIFIED;
        // An SSO account backed by a mail server still knows its mail password
        $is_sso_backed_by_mail = $session_authtype === Auth::EXTERNAL
            && $user->fields['authtype'] === Auth::MAIL;

        if ($session_authtype !== Auth::MAIL && !$is_sso_backed_by_mail) {
            return false;
        }

        // A missing server or an empty connect_string can never be verified: let a weaker strategy
        // take over. An unreachable server, on the other hand, fails closed in verify().
        $method = Auth::getMethodsByID(Auth::MAIL, (int) $user->fields['auths_id']);
        return !empty($method['connect_string']);
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
