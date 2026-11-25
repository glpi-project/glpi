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
    /**
     * @param OAuthAuthorization|null $authorization Authorization used to redeem the code.
     *                                               Not a service: it is only meant to be provided by tests,
     *                                               a new instance is used otherwise.
     */
    public function __construct(
        private readonly ?OAuthAuthorization $authorization = null,
    ) {}

    /**
     * Redirect URI registered on the provider.
     *
     * The session cookie might not be sent back on the OAuth provider's
     * redirect if `session.cookie_samesite` is `strict`: no session can be
     * checked here. Bouncing through a same-site navigation forces the cookie
     * to be attached to the next (real) request, which is handled by
     * `complete()` behind the firewall.
     */
    #[Route("/oauth/callback", name: "glpi_oauth_callback", methods: ["GET"])]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        $url = $request->getUriForPath('/oauth/callback/complete');
        $query = $request->getQueryString();
        if ($query !== null) {
            $url .= '?' . $query;
        }

        return new Response(
            '<html><head><meta http-equiv="refresh" content="0;URL=\'' . htmlspecialchars($url) . '\'"/></head><body></body></html>'
        );
    }

    #[Route("/oauth/callback/complete", name: "glpi_oauth_callback_complete", methods: ["GET"])]
    #[SecurityStrategy(Firewall::STRATEGY_CENTRAL_ACCESS)]
    public function complete(Request $request): Response
    {
        $application_id = $_SESSION['oauth2_provider_id'] ?? null;
        $type           = $_SESSION['oauth2_type'] ?? null;
        $state          = $_SESSION['oauth2_state'] ?? null;

        // Only consume the pending authorization the callback belongs to: a
        // stale callback (from an older attempt, or replayed) must not wipe
        // out a newer attempt still in progress.
        $is_state_valid = $application_id !== null
            && $type !== null
            && $state !== null
            && $request->query->get('state') === $state;
        if ($is_state_valid) {
            unset($_SESSION['oauth2_provider_id'], $_SESSION['oauth2_type'], $_SESSION['oauth2_state']);
        }

        $application = new OAuthApplication();

        $error = $request->query->get('error');
        $error_description = $request->query->get('error_description');

        if (!$is_state_valid) {
            Session::addMessageAfterRedirect(__s('Unable to verify authorization code'), false, ERROR);
        } elseif (!empty($error) || !empty($error_description)) {
            Session::addMessageAfterRedirect(
                htmlspecialchars(sprintf(__('Authorization failed with error: %s'), $error_description ?? $error)),
                false,
                ERROR
            );
        } elseif (!Session::haveRight(OAuthApplication::$rightname, UPDATE)) {
            // The firewall only checks the central access: the right to
            // update the application may have been lost since the consent was
            // requested (profile switch), so it is checked again here.
            Session::addMessageAfterRedirect(__s('You do not have permission to authorize this application'), false, ERROR);
        } elseif (!$request->query->has('code')) {
            Session::addMessageAfterRedirect(__s('Unable to get authorization code'), false, ERROR);
        } else {
            $authorization = $this->authorization ?? new OAuthAuthorization();
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
