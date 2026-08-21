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

namespace tests\units\Glpi\Controller;

use Glpi\Controller\OauthCallbackController;
use Glpi\Http\RedirectResponse;
use Glpi\Tests\DbTestCase;
use OAuthApplication;
use Symfony\Component\HttpFoundation\Request;

/**
 * These tests focus on the controller's own responsibility: the same-site
 * cookie-refresh bounce, and validating/consuming the `oauth2_*` session
 * data (state, provider id, type) before delegating to
 * `OAuthAuthorization::createFromCode()`.
 *
 * The actual token-exchange logic (success, token-exchange failure, missing
 * email) is already covered independently, with a fake provider, by
 * `OAuthAuthorizationTest` — it is not re-tested here, since doing so would
 * require performing (or elaborately mocking) a real HTTP call to an OAuth2
 * provider.
 */
class OauthCallbackControllerTest extends DbTestCase
{
    private function createApplication(): OAuthApplication
    {
        return $this->createItem(OAuthApplication::class, [
            'name'          => 'Test app ' . $this->getUniqueString(),
            'is_active'     => 1,
            'provider'      => OAuthApplication::AZURE,
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
            'tenant_id'     => 'my-tenant',
        ], ['client_secret']);
    }

    public function tearDown(): void
    {
        unset($_SESSION['oauth2_provider_id'], $_SESSION['oauth2_type'], $_SESSION['oauth2_state']);
        parent::tearDown();
    }

    public function testFirstHitBouncesThroughCookieRefresh(): void
    {
        $this->login();

        $request = Request::create('/oauth/callback?state=abc&code=xyz', 'GET');

        $response = (new OauthCallbackController())($request);

        $this->assertStringContainsString('cookie_refresh', $response->getContent());
        $this->assertStringContainsString('meta http-equiv="refresh"', $response->getContent());
    }

    public function testProviderErrorIsReportedAndSessionIsCleared(): void
    {
        $this->login();

        $app = $this->createApplication();
        $_SESSION['oauth2_provider_id'] = $app->getID();
        $_SESSION['oauth2_type']        = 'IMAP';
        $_SESSION['oauth2_state']       = 'expected-state';

        $request = Request::create(
            '/oauth/callback?cookie_refresh&error=access_denied&error_description=User+denied+access',
            'GET'
        );

        $response = (new OauthCallbackController())($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->hasSessionMessages(ERROR, ['Authorization failed with error: User denied access']);
        $this->assertArrayNotHasKey('oauth2_provider_id', $_SESSION);
        $this->assertArrayNotHasKey('oauth2_type', $_SESSION);
        $this->assertArrayNotHasKey('oauth2_state', $_SESSION);
    }

    public function testStateMismatchIsRejected(): void
    {
        $this->login();

        $app = $this->createApplication();
        $_SESSION['oauth2_provider_id'] = $app->getID();
        $_SESSION['oauth2_type']        = 'IMAP';
        $_SESSION['oauth2_state']       = 'expected-state';

        $request = Request::create('/oauth/callback?cookie_refresh&state=wrong-state&code=xyz', 'GET');

        $response = (new OauthCallbackController())($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->hasSessionMessages(ERROR, ['Unable to verify authorization code']);
    }

    public function testMissingSessionStateIsRejected(): void
    {
        $this->login();

        // No oauth2_* session values seeded at all.
        $request = Request::create('/oauth/callback?cookie_refresh&state=abc&code=xyz', 'GET');

        $response = (new OauthCallbackController())($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->hasSessionMessages(ERROR, ['Unable to verify authorization code']);
    }

    public function testMissingCodeIsRejected(): void
    {
        $this->login();

        $app = $this->createApplication();
        $_SESSION['oauth2_provider_id'] = $app->getID();
        $_SESSION['oauth2_type']        = 'IMAP';
        $_SESSION['oauth2_state']       = 'expected-state';

        $request = Request::create('/oauth/callback?cookie_refresh&state=expected-state', 'GET');

        $response = (new OauthCallbackController())($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->hasSessionMessages(ERROR, ['Unable to get authorization code']);
    }
}
