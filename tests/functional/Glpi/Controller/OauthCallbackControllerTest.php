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
use Glpi\Exception\RedirectException;
use Glpi\Http\RedirectResponse;
use Glpi\Mail\OauthProvider\ProviderInterface;
use Glpi\Tests\DbTestCase;
use GLPIKey;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\AbstractProvider;
use OAuthApplication;
use OAuthAuthorization;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_encode;

/**
 * These tests cover:
 * - the controller's own responsibility: the same-site cookie-refresh bounce,
 *   and validating/consuming the `oauth2_*` session data (state, provider id,
 *   type) before delegating to `OAuthAuthorization::createFromCode()`;
 * - the whole authorization flow, end to end: redirection to the consent
 *   screen, callback, token exchange and storage of the authorization. The
 *   real Azure/Google providers are used; only their HTTP layer is replaced by
 *   a Guzzle `MockHandler`, so every request they send can be asserted.
 */
class OauthCallbackControllerTest extends DbTestCase
{
    private const AZURE_DISCOVERY = [
        'issuer'                 => 'https://login.microsoftonline.com/my-tenant/v2.0',
        'authorization_endpoint' => 'https://login.microsoftonline.com/my-tenant/oauth2/v2.0/authorize',
        'token_endpoint'         => 'https://login.microsoftonline.com/my-tenant/oauth2/v2.0/token',
        'jwks_uri'               => 'https://login.microsoftonline.com/my-tenant/discovery/v2.0/keys',
        'userinfo_endpoint'      => 'https://graph.microsoft.com/oidc/userinfo',
    ];

    private function createApplication(string $provider = OAuthApplication::AZURE): OAuthApplication
    {
        return $this->createItem(OAuthApplication::class, [
            'name'          => 'Test app ' . $this->getUniqueString(),
            'is_active'     => 1,
            'provider'      => $provider,
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
            'tenant_id'     => $provider === OAuthApplication::AZURE ? 'my-tenant' : '',
        ], ['client_secret']);
    }

    /**
     * Builds an HTTP client answering with the given responses, in order,
     * and recording every request sent through it in `$history`.
     *
     * @param list<Response> $responses
     * @param array<int, array{request: RequestInterface}> $history
     */
    private function getMockedHttpClient(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getJsonResponse(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    /**
     * Runs the first leg of the flow (as `ajax/oauthauthorization_action.php`
     * does) and returns the URL of the provider's consent screen.
     */
    private function requestAuthorization(OAuthApplication $app, string $type, Client $http_client): string
    {
        $provider = $app->getOauthProvider($type);
        $this->assertInstanceOf(AbstractProvider::class, $provider);
        $provider->setHttpClient($http_client);

        try {
            $app->redirectToAuthorizationUrl($type, $provider);
        } catch (RedirectException $e) {
            return $e->getResponse()->getTargetUrl();
        }

        $this->fail('A redirection to the provider consent screen was expected.');
    }

    /**
     * @return array<string, string>
     */
    private function getQueryParameters(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        return $params;
    }

    /**
     * @return array<string, string>
     */
    private function getBodyParameters(RequestInterface $request): array
    {
        parse_str((string) $request->getBody(), $params);
        return $params;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Generates an RSA key pair, and returns an ID token signed with it
     * along with the JWKS document exposing its public part, so that the
     * real signature and claims validation of the Azure provider runs.
     *
     * @param array<string, mixed> $claims
     * @return array{string, array<string, mixed>}
     */
    private function getSignedIdTokenAndJwks(array $claims): array
    {
        $private_key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $rsa = openssl_pkey_get_details($private_key)['rsa'];

        $signing_input = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'RS256', 'kid' => 'test-kid']))
            . '.' . $this->base64UrlEncode(json_encode($claims));
        openssl_sign($signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256);

        $jwks = [
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => 'test-kid',
                'n'   => $this->base64UrlEncode($rsa['n']),
                'e'   => $this->base64UrlEncode($rsa['e']),
            ]],
        ];

        return [$signing_input . '.' . $this->base64UrlEncode($signature), $jwks];
    }

    private function getStoredAuthorization(OAuthApplication $app, string $email, string $type): OAuthAuthorization
    {
        $authorization = new OAuthAuthorization();
        $this->assertTrue($authorization->getFromDBByCrit([
            'oauthapplications_id' => $app->getID(),
            'email'                => $email,
            'type'                 => $type,
        ]));

        return $authorization;
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

    // -------------------------------------------------------------------------
    // End-to-end authorization flow
    // -------------------------------------------------------------------------

    public function testAuthorizationFlowWithAzure(): void
    {
        $this->login();

        $app = $this->createApplication(OAuthApplication::AZURE);
        $imap_scopes = 'openid email offline_access https://outlook.office.com/IMAP.AccessAsUser.All';

        // 1. Redirection to the consent screen: the provider resolves its
        //    authorization endpoint through the tenant OpenID configuration.
        $history = [];
        $auth_url = $this->requestAuthorization(
            $app,
            OAuthAuthorization::TYPE_IMAP,
            $this->getMockedHttpClient([$this->getJsonResponse(self::AZURE_DISCOVERY)], $history)
        );

        $this->assertCount(1, $history);
        $this->assertSame(
            'https://login.microsoftonline.com/my-tenant/v2.0/.well-known/openid-configuration?appid=my-client-id',
            (string) $history[0]['request']->getUri()
        );

        $this->assertStringStartsWith(self::AZURE_DISCOVERY['authorization_endpoint'] . '?', $auth_url);
        $auth_params = $this->getQueryParameters($auth_url);
        $this->assertSame('my-client-id', $auth_params['client_id']);
        $this->assertSame(OAuthApplication::getCallbackUrl(), $auth_params['redirect_uri']);
        $this->assertSame('code', $auth_params['response_type']);
        $this->assertSame($imap_scopes, $auth_params['scope']);
        $this->assertSame('login', $auth_params['prompt']);

        // The flow context is kept in session, for the callback to check it.
        $this->assertNotEmpty($auth_params['state']);
        $this->assertSame($auth_params['state'], $_SESSION['oauth2_state']);
        $this->assertSame($app->getID(), $_SESSION['oauth2_provider_id']);
        $this->assertSame(OAuthAuthorization::TYPE_IMAP, $_SESSION['oauth2_type']);

        // 2. Callback: the code is exchanged for tokens, the ID token signature
        //    and claims are validated, and the mailbox is read from its claims.
        [$id_token, $jwks] = $this->getSignedIdTokenAndJwks([
            'aud'         => 'my-client-id',
            'iss'         => self::AZURE_DISCOVERY['issuer'],
            'tid'         => 'my-tenant',
            'ver'         => '2.0',
            'iat'         => time(),
            'nbf'         => time(),
            'exp'         => time() + 3600,
            'oid'         => 'user-oid',
            'email'       => 'mailbox@example.com',
            'given_name'  => 'John',
            'family_name' => 'Doe',
        ]);

        $history = [];
        $http_client = $this->getMockedHttpClient([
            $this->getJsonResponse(self::AZURE_DISCOVERY),
            $this->getJsonResponse([
                'token_type'    => 'Bearer',
                'scope'         => $imap_scopes,
                'expires_in'    => 3600,
                'access_token'  => 'the-access-token',
                'refresh_token' => 'the-refresh-token',
                'id_token'      => $id_token,
            ]),
            $this->getJsonResponse($jwks),
        ], $history);

        $request = Request::create('/oauth/callback?' . http_build_query([
            'cookie_refresh' => '',
            'state'          => $auth_params['state'],
            'code'           => 'the-auth-code',
        ]), 'GET');
        $response = (new OauthCallbackController(HttpMockedOAuthAuthorization::withHttpClient($http_client)))($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($app->getLinkURL(), $response->getTargetUrl());
        $this->hasSessionMessages(INFO, ['Authorization granted']);
        $this->assertArrayNotHasKey('oauth2_provider_id', $_SESSION);
        $this->assertArrayNotHasKey('oauth2_type', $_SESSION);
        $this->assertArrayNotHasKey('oauth2_state', $_SESSION);

        // Requests sent to the provider: OpenID configuration, token endpoint, signing keys.
        $this->assertCount(3, $history);

        $token_request = $history[1]['request'];
        $this->assertSame('POST', $token_request->getMethod());
        $this->assertSame(self::AZURE_DISCOVERY['token_endpoint'], (string) $token_request->getUri());
        $token_params = $this->getBodyParameters($token_request);
        $this->assertSame('authorization_code', $token_params['grant_type']);
        $this->assertSame('the-auth-code', $token_params['code']);
        $this->assertSame('my-client-id', $token_params['client_id']);
        $this->assertSame('my-secret', $token_params['client_secret']);
        $this->assertSame(OAuthApplication::getCallbackUrl(), $token_params['redirect_uri']);
        $this->assertSame($imap_scopes, $token_params['scope']);

        $this->assertSame('GET', $history[2]['request']->getMethod());
        $this->assertSame(self::AZURE_DISCOVERY['jwks_uri'], (string) $history[2]['request']->getUri());

        // 3. The authorization is stored for the mailbox, with encrypted tokens.
        $authorization = $this->getStoredAuthorization($app, 'mailbox@example.com', OAuthAuthorization::TYPE_IMAP);
        $key = new GLPIKey();
        $this->assertSame('the-auth-code', $key->decrypt($authorization->fields['code']));
        $this->assertSame('the-refresh-token', $key->decrypt($authorization->fields['refresh_token']));
        $this->assertSame('the-access-token', $authorization->getAccessToken()?->getToken());
        $this->assertSame(OAuthAuthorization::TOKEN_VALID, $authorization->getTokenStatus()['code']);
    }

    public function testAuthorizationFlowWithGoogle(): void
    {
        $this->login();

        $app = $this->createApplication(OAuthApplication::GOOGLE);

        // 1. Redirection to the consent screen: Google endpoints are static,
        //    so no request is expected at this stage.
        $history = [];
        $auth_url = $this->requestAuthorization(
            $app,
            OAuthAuthorization::TYPE_IMAP,
            $this->getMockedHttpClient([], $history)
        );

        $this->assertCount(0, $history);

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $auth_url);
        $auth_params = $this->getQueryParameters($auth_url);
        $this->assertSame('my-client-id', $auth_params['client_id']);
        $this->assertSame(OAuthApplication::getCallbackUrl(), $auth_params['redirect_uri']);
        $this->assertSame('code', $auth_params['response_type']);
        // Default OpenID scopes are kept by the provider, they grant access to the userinfo endpoint.
        $this->assertSame('openid email profile https://mail.google.com/', $auth_params['scope']);
        // Required to obtain a refresh token.
        $this->assertSame('offline', $auth_params['access_type']);
        $this->assertSame('consent select_account', $auth_params['prompt']);

        $this->assertNotEmpty($auth_params['state']);
        $this->assertSame($auth_params['state'], $_SESSION['oauth2_state']);
        $this->assertSame($app->getID(), $_SESSION['oauth2_provider_id']);
        $this->assertSame(OAuthAuthorization::TYPE_IMAP, $_SESSION['oauth2_type']);

        // 2. Callback: the code is exchanged for tokens, then the mailbox is
        //    fetched from the userinfo endpoint.
        $history = [];
        $http_client = $this->getMockedHttpClient([
            $this->getJsonResponse([
                'token_type'    => 'Bearer',
                'scope'         => 'https://mail.google.com/',
                'expires_in'    => 3599,
                'access_token'  => 'the-access-token',
                'refresh_token' => 'the-refresh-token',
            ]),
            $this->getJsonResponse([
                'sub'            => '1234567890',
                'email'          => 'mailbox@example.com',
                'email_verified' => true,
                'given_name'     => 'John',
                'family_name'    => 'Doe',
            ]),
        ], $history);

        $request = Request::create('/oauth/callback?' . http_build_query([
            'cookie_refresh' => '',
            'state'          => $auth_params['state'],
            'code'           => 'the-auth-code',
        ]), 'GET');
        $response = (new OauthCallbackController(HttpMockedOAuthAuthorization::withHttpClient($http_client)))($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($app->getLinkURL(), $response->getTargetUrl());
        $this->hasSessionMessages(INFO, ['Authorization granted']);

        // Requests sent to the provider: token endpoint, userinfo endpoint.
        $this->assertCount(2, $history);

        $token_request = $history[0]['request'];
        $this->assertSame('POST', $token_request->getMethod());
        $this->assertSame('https://oauth2.googleapis.com/token', (string) $token_request->getUri());
        $token_params = $this->getBodyParameters($token_request);
        $this->assertSame('authorization_code', $token_params['grant_type']);
        $this->assertSame('the-auth-code', $token_params['code']);
        $this->assertSame('my-client-id', $token_params['client_id']);
        $this->assertSame('my-secret', $token_params['client_secret']);
        $this->assertSame(OAuthApplication::getCallbackUrl(), $token_params['redirect_uri']);

        $userinfo_request = $history[1]['request'];
        $this->assertSame('GET', $userinfo_request->getMethod());
        $this->assertSame('https://openidconnect.googleapis.com/v1/userinfo', (string) $userinfo_request->getUri());
        $this->assertSame('Bearer the-access-token', $userinfo_request->getHeaderLine('Authorization'));

        // 3. The authorization is stored for the mailbox, with encrypted tokens.
        $authorization = $this->getStoredAuthorization($app, 'mailbox@example.com', OAuthAuthorization::TYPE_IMAP);
        $key = new GLPIKey();
        $this->assertSame('the-auth-code', $key->decrypt($authorization->fields['code']));
        $this->assertSame('the-refresh-token', $key->decrypt($authorization->fields['refresh_token']));
        $this->assertSame('the-access-token', $authorization->getAccessToken()?->getToken());
    }

    public function testAuthorizationFlowReportsATokenEndpointError(): void
    {
        $this->login();

        $app = $this->createApplication(OAuthApplication::AZURE);
        $_SESSION['oauth2_provider_id'] = $app->getID();
        $_SESSION['oauth2_type']        = OAuthAuthorization::TYPE_IMAP;
        $_SESSION['oauth2_state']       = 'expected-state';

        $history = [];
        $http_client = $this->getMockedHttpClient([
            $this->getJsonResponse(self::AZURE_DISCOVERY),
            $this->getJsonResponse([
                'error'             => 'invalid_grant',
                'error_description' => 'AADSTS70008: The provided authorization code has expired.',
            ], 400),
        ], $history);

        $request = Request::create('/oauth/callback?cookie_refresh&state=expected-state&code=expired-code', 'GET');
        $response = (new OauthCallbackController(HttpMockedOAuthAuthorization::withHttpClient($http_client)))($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($app->getLinkURL(), $response->getTargetUrl());
        $this->hasSessionMessageThatContains('Unable to obtain access token from provider: invalid_grant', ERROR);
        $this->hasPhpLogRecordThatContains('Error during authorization code fetching: invalid_grant', 'Warning');

        $this->assertCount(2, $history);
        $this->assertSame(0, countElementsInTable(OAuthAuthorization::getTable(), ['oauthapplications_id' => $app->getID()]));
    }
}

/**
 * Authorization using the real provider configured on the application,
 * with its HTTP layer routed to the given (mocked) client.
 */
final class HttpMockedOAuthAuthorization extends OAuthAuthorization
{
    private Client $http_client;

    public static function withHttpClient(Client $http_client): self
    {
        $instance = new self();
        $instance->http_client = $http_client;
        return $instance;
    }

    /**
     * The table name is derived from the class name, so this subclass would
     * otherwise resolve to a table of its own that does not exist.
     */
    public static function getTable($classname = null)
    {
        return \CommonDBTM::getTable(OAuthAuthorization::class);
    }

    protected function resolveProvider(OAuthApplication $application, string $type): ProviderInterface
    {
        $provider = parent::resolveProvider($application, $type);
        if (!$provider instanceof AbstractProvider) {
            throw new \LogicException(sprintf('Unexpected provider class %s.', $provider::class));
        }
        $provider->setHttpClient($this->http_client);

        return $provider;
    }
}
