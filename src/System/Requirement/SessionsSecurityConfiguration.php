<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\System\Requirement;

/**
 * @since 10.0.3
 */
class SessionsSecurityConfiguration extends AbstractRequirement
{
    public function __construct()
    {
        $this->title = __('Security configuration for sessions');
        $this->description = __('Ensure security is enforced on session cookies.');
        $this->optional = true;
    }

    protected function check()
    {
        $cookie_secure   = (bool)ini_get('session.cookie_secure');
        $cookie_httponly = (bool)ini_get('session.cookie_httponly');
        $cookie_samesite = ini_get('session.cookie_samesite');

        $is_https_request = ($_SERVER['HTTPS'] ?? 'off') === 'on' || (int)($_SERVER['SERVER_PORT'] ?? null) == 443;

        $validated = true;
        if ($is_https_request && !$cookie_secure) {
            $this->validation_messages[] = __('PHP directive "session.cookie_secure" should be set to "on" when GLPI can be accessed on HTTPS protocol.');
            $validated = false;
        }
        if (!$cookie_httponly) {
            $this->validation_messages[] = __('PHP directive "session.cookie_httponly" should be set to "on" to prevent client-side script to access cookie values.');
            $validated = false;
        }

        // 'session.cookie_samesite' can be:
        // - 'None':        Cookie will be sent in all cross-origin requests (even POST requests).
        //                  This may be dangerous, even if we have CSRF protection on POST requests.
        // - 'Lax':         Cookie will not be sent in POST cross-origin requests.
        //                  GET requests should not be used to write data, so it should be OK.
        // - 'Strict':      Cookie will not be sent in cross-origin requests (even GET requests).
        //                  This is the best security, but it will kill session in all requests that came from another app.
        //                  For instance, it will break oauthsso/oauthimap plugins.
        //                  We should consider it as valid, but we should not recommand it.
        // - '' (empty):    directive will not be sent to the browser, and browser should apply the Lax policy.
        if (!in_array(strtolower($cookie_samesite), ['lax', 'strict', ''])) {
            $this->validation_messages[] = __('PHP directive "session.cookie_samesite" should be set, at least, to "Lax", to prevent cookie to be sent on cross-origin POST requests.');
            $validated = false;
        }

        $this->validated = $validated;

        if ($validated) {
            $this->validation_messages[] = __s('Sessions configuration is secured.');
        }
    }
}
