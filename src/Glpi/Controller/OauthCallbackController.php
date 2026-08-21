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

namespace Glpi\Controller;

use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use OAuthApplication;
use OAuthAuthorization;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OauthCallbackController extends AbstractController
{
    #[Route("/oauth/callback", name: "glpi_oauth_callback", methods: ["GET"])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        if (!$request->query->has('cookie_refresh')) {
            // Session cookie might not be sent back on the OAuth provider's
            // redirect if `session.cookie_samesite` is `strict`. Bouncing
            // through a same-site navigation forces the cookie to be
            // attached to the next (real) request.
            $url = $request->getUri() . (str_contains($request->getUri(), '?') ? '&' : '?') . 'cookie_refresh';
            return new Response(
                '<html><head><meta http-equiv="refresh" content="0;URL=\'' . htmlspecialchars($url) . '\'"/></head><body></body></html>'
            );
        }

        $application_id = $_SESSION['oauth2_provider_id'] ?? null;
        $type           = $_SESSION['oauth2_type'] ?? null;
        $state          = $_SESSION['oauth2_state'] ?? null;

        unset($_SESSION['oauth2_provider_id'], $_SESSION['oauth2_type'], $_SESSION['oauth2_state']);

        $application = new OAuthApplication();

        $error = $request->query->get('error');
        $error_description = $request->query->get('error_description');

        if (!empty($error) || !empty($error_description)) {
            Session::addMessageAfterRedirect(
                htmlspecialchars(sprintf(__('Authorization failed with error: %s'), $error_description ?? $error)),
                false,
                ERROR
            );
        } elseif (
            $application_id === null
            || $type === null
            || !$request->query->has('state')
            || $state === null
            || $request->query->get('state') !== $state
        ) {
            Session::addMessageAfterRedirect(__s('Unable to verify authorization code'), false, ERROR);
        } elseif (!$request->query->has('code')) {
            Session::addMessageAfterRedirect(__s('Unable to get authorization code'), false, ERROR);
        } else {
            $authorization = new OAuthAuthorization();
            if ($authorization->createFromCode((int) $application_id, (string) $type, (string) $request->query->get('code'))) {
                Session::addMessageAfterRedirect(__s('Authorization granted'), false, INFO);
            } else {
                Session::addMessageAfterRedirect(
                    htmlspecialchars($authorization->getLastError() ?? __('Unable to save authorization')),
                    false,
                    ERROR
                );
            }
        }

        $redirect_url = $application->getFromDB($application_id)
            ? $application->getLinkURL()
            : $application->getSearchURL(true);

        return new RedirectResponse($redirect_url);
    }
}
