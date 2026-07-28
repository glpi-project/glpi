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
 * LDAP re-authentication strategy.
 *
 * Verifies the user identity by performing an LDAP bind with the password
 * provided in the prompt, reusing the same directory configuration as the
 * regular login flow.
 *
 * It fails closed: if the directory is unreachable, the verification fails
 * and no bypass is granted, so the sensitive action stays protected.
 */
final class LdapReAuthStrategy extends InPlaceReAuthStrategy
{
    #[Override]
    public function verify(int $users_id, Request $request): bool
    {
        $ldap_password = (string) $request->request->get('user_input', '');

        // Guard against empty password and null-byte injection: some directories accept an
        // unauthenticated (anonymous) bind on an empty password, which would turn this check
        // into an authentication bypass.
        if ($ldap_password === '' || str_contains($ldap_password, "\0")) {
            return false;
        }

        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        $ldap_method = Auth::getMethodsByID(Auth::LDAP, (int) $user->fields['auths_id']);
        if ($ldap_method === []) {
            return false;
        }

        // connection_ldap() returns the LDAP entry on success, or false on a wrong password
        // or an unreachable directory. Both cases fail closed here.
        $result = (new Auth())->connection_ldap($ldap_method, $user->fields['name'], $ldap_password);

        return $result !== false;
    }

    #[Override]
    public function isAvailable(int $users_id, int $entities_id = 0): bool
    {
        $user = new User();
        if (!$user->getFromDB($users_id)) {
            return false;
        }

        return $user->fields['authtype'] === Auth::LDAP
            && (int) $user->fields['auths_id'] > 0;
    }

    #[Override]
    public function getLabel(): string
    {
        return __('Password');
    }

    #[Override]
    public function getPromptTemplate(): string
    {
        return 'pages/reauth/ldap_form.html.twig';
    }

    #[Override]
    public function getPriority(): int
    {
        return 50;
    }
}
