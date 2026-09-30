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

namespace tests\units\Glpi\Security\ReAuth;

use Auth;
use Glpi\Security\ReAuth\CasReAuthStrategy;
use Glpi\Tests\DbTestCase;
use Glpi\Toolbox\HttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

#[Group('reauth')]
class CasReAuthStrategyTest extends DbTestCase
{
    /** CAS identity of the test user: the CAS login looks the GLPI user up by this name. */
    private const CAS_USER = TU_USER;

    /** URL of the last validation request sent to the mocked CAS server. */
    private ?string $validate_url = null;

    public function setUp(): void
    {
        global $CFG_GLPI;

        parent::setUp();

        $CFG_GLPI['url_base']    = 'http://glpi.test';
        $CFG_GLPI['cas_host']    = 'cas.test';
        $CFG_GLPI['cas_port']    = '8443';
        $CFG_GLPI['cas_uri']     = 'cas';
        $CFG_GLPI['cas_version'] = 'CAS_VERSION_3_0';

        $_SESSION['glpiauthtype'] = Auth::CAS;
    }

    /** CAS server answering every validation request with the given body. */
    private function makeStrategy(string $body): CasReAuthStrategy
    {
        return new CasReAuthStrategy($this->makeHttpClient(
            new MockHttpClient(function (string $method, string $url) use ($body) {
                $this->validate_url = $url;
                return new MockResponse($body);
            })
        ));
    }

    /** GLPI HTTP client whose requests are answered by the given mock. */
    private function makeHttpClient(MockHttpClient $mock): HttpClient
    {
        $http_client = new HttpClient(context: Auth::class);
        // No interface to mock: replace the inner Symfony client.
        $this->setPrivateProperty($http_client, 'client', $mock);

        return $http_client;
    }

    private static function cas20Success(string $user): string
    {
        return "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>"
            . "<cas:authenticationSuccess><cas:user>$user</cas:user></cas:authenticationSuccess>"
            . '</cas:serviceResponse>';
    }

    private static function cas20Failure(): string
    {
        return "<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>"
            . "<cas:authenticationFailure code='INVALID_TICKET'>Ticket not recognized</cas:authenticationFailure>"
            . '</cas:serviceResponse>';
    }

    private function makeCallbackRequest(?string $ticket = 'ST-1-abc'): Request
    {
        return Request::create('/ReAuth/CAS/Callback', 'GET', $ticket === null ? [] : ['ticket' => $ticket]);
    }

    /** Available for a session opened through CAS, whatever the user record. */
    public function testIsAvailableForCasSession(): void
    {
        // --- act + assert ---
        $this->assertTrue((new CasReAuthStrategy())->isAvailable($this->getTestUserId()));
    }

    public static function nonCasSessionProvider(): iterable
    {
        yield 'unknown' => [Auth::NOT_YET_AUTHENTIFIED];
        yield 'database' => [Auth::DB_GLPI];
        yield 'mail server' => [Auth::MAIL];
        yield 'ldap directory' => [Auth::LDAP];
        yield 'sso' => [Auth::EXTERNAL];
        yield 'x509' => [Auth::X509];
        yield 'api token' => [Auth::API];
        yield 'remember me cookie' => [Auth::COOKIE];
        yield 'oauth' => [Auth::OAUTH];
    }

    /** Not available when the session was not opened through CAS. */
    #[DataProvider('nonCasSessionProvider')]
    public function testIsAvailableIsFalseForNonCasSession(int $session_authtype): void
    {
        // --- arrange ---
        $_SESSION['glpiauthtype'] = $session_authtype;

        // --- act + assert ---
        $this->assertFalse((new CasReAuthStrategy())->isAvailable($this->getTestUserId()));
    }

    /** Not available once CAS is no longer configured: a weaker strategy takes over. */
    public function testIsAvailableIsFalseWithoutCasHost(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $CFG_GLPI['cas_host'] = '';

        // --- act + assert ---
        $this->assertFalse((new CasReAuthStrategy())->isAvailable($this->getTestUserId()));
    }

    /** The CAS login URL forces a fresh credential entry and comes back to the callback. */
    public function testStartReturnsRenewLoginUrl(): void
    {
        // --- act ---
        $url = (new CasReAuthStrategy())->start($this->getTestUserId());

        // --- assert ---
        $this->assertSame(
            'https://cas.test:8443/cas/login?service=' . rawurlencode('http://glpi.test/ReAuth/CAS/Callback')
                . '&renew=true',
            $url
        );
    }

    /** A fresh ticket for the identity of the user re-authenticates them. */
    public function testCompleteSucceeds(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));
        $strategy->start($this->getTestUserId());

        // --- act ---
        $result = $strategy->complete($this->getTestUserId(), $this->makeCallbackRequest());

        // --- assert : validated with renew, for the same service ---
        $this->assertTrue($result);
        $this->assertStringStartsWith('https://cas.test:8443/cas/p3/serviceValidate?', (string) $this->validate_url);
        parse_str((string) parse_url((string) $this->validate_url, PHP_URL_QUERY), $query);
        $this->assertSame(
            [
                'service' => 'http://glpi.test/ReAuth/CAS/Callback',
                'ticket'  => 'ST-1-abc',
                'renew'   => 'true',
            ],
            $query
        );
    }

    public static function casVersionProvider(): iterable
    {
        yield 'CAS 1.0' => ['CAS_VERSION_1_0', 'validate', "yes\n" . self::CAS_USER . "\n", "no\n\n"];
        yield 'CAS 2.0' => ['CAS_VERSION_2_0', 'serviceValidate', self::cas20Success(self::CAS_USER), self::cas20Failure()];
        yield 'CAS 3.0' => ['CAS_VERSION_3_0', 'p3/serviceValidate', self::cas20Success(self::CAS_USER), self::cas20Failure()];
    }

    /** Each protocol version is validated on its own endpoint, and its failure answer is refused. */
    #[DataProvider('casVersionProvider')]
    public function testCompleteHandlesProtocolVersions(
        string $version,
        string $path,
        string $success_body,
        string $failure_body
    ): void {
        global $CFG_GLPI;

        // --- arrange ---
        $CFG_GLPI['cas_version'] = $version;
        $users_id = $this->getTestUserId();

        // --- act + assert : success ---
        $strategy = $this->makeStrategy($success_body);
        $strategy->start($users_id);
        $this->assertTrue($strategy->complete($users_id, $this->makeCallbackRequest()));
        $this->assertStringStartsWith('https://cas.test:8443/cas/' . $path . '?', (string) $this->validate_url);

        // --- act + assert : failure ---
        $strategy = $this->makeStrategy($failure_body);
        $strategy->start($users_id);
        $this->assertFalse($strategy->complete($users_id, $this->makeCallbackRequest()));
    }

    /** Re-authenticating on CAS as someone else is refused. */
    public function testCompleteFailsForAnotherIdentity(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success('glpi'));
        $strategy->start($this->getTestUserId());

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
    }

    /** A CAS identity matching no GLPI user is refused. */
    public function testCompleteFailsForUnknownIdentity(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success('unknown.cas.user'));
        $strategy->start($this->getTestUserId());

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
    }

    /**
     * During an impersonation, the CAS identity kept since the login is the impersonator's one:
     * only the impersonated user's identity is accepted.
     */
    public function testCompleteChecksImpersonatedUserIdentity(): void
    {
        // --- arrange ---
        $_SESSION['phpCAS']['user'] = 'glpi';
        $impersonated_id = $this->getTestUserId();

        // --- act + assert : impersonator's identity refused ---
        $strategy = $this->makeStrategy(self::cas20Success('glpi'));
        $strategy->start($impersonated_id);
        $this->assertFalse($strategy->complete($impersonated_id, $this->makeCallbackRequest()));

        // --- act + assert : impersonated user's identity accepted ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));
        $strategy->start($impersonated_id);
        $this->assertTrue($strategy->complete($impersonated_id, $this->makeCallbackRequest()));
    }

    /** A callback without a started round-trip is refused, before any call to the CAS server. */
    public function testCompleteFailsWithoutStart(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
        $this->assertNull($this->validate_url);
    }

    /** A round-trip started for another user is refused. */
    public function testCompleteFailsForRoundTripOfAnotherUser(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));
        $strategy->start($this->getTestUserId() + 1);

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
    }

    /** A round-trip can only be completed once. */
    public function testCompleteIsSingleUse(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));
        $strategy->start($this->getTestUserId());
        $strategy->complete($this->getTestUserId(), $this->makeCallbackRequest());

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
    }

    /** A callback without a ticket is refused. */
    public function testCompleteFailsWithoutTicket(): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy(self::cas20Success(self::CAS_USER));
        $strategy->start($this->getTestUserId());

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest(null)));
    }

    /** An unreachable CAS server fails closed. */
    public function testCompleteFailsWhenCasServerIsUnreachable(): void
    {
        // --- arrange ---
        $strategy = new CasReAuthStrategy($this->makeHttpClient(new MockHttpClient(
            new MockResponse('', ['error' => 'Could not resolve host: cas.test'])
        )));
        $strategy->start($this->getTestUserId());

        // --- act ---
        $result = $strategy->complete($this->getTestUserId(), $this->makeCallbackRequest());

        // --- assert ---
        $this->assertFalse($result);
        $this->hasPhpLogRecordThatContains('Could not resolve host: cas.test', 'error');
    }

    public static function invalidResponseProvider(): iterable
    {
        yield 'not xml' => ['<html>maintenance</html'];
        yield 'empty user' => [self::cas20Success('')];
        yield 'success outside the CAS namespace' => [
            '<serviceResponse><authenticationSuccess><user>' . self::CAS_USER . '</user></authenticationSuccess></serviceResponse>',
        ];
    }

    /** Anything else than a CAS success answer is refused. */
    #[DataProvider('invalidResponseProvider')]
    public function testCompleteFailsOnInvalidResponse(string $body): void
    {
        // --- arrange ---
        $strategy = $this->makeStrategy($body);
        $strategy->start($this->getTestUserId());

        // --- act + assert ---
        $this->assertFalse($strategy->complete($this->getTestUserId(), $this->makeCallbackRequest()));
    }

    /** Verification is only done on the way back from CAS, never through the core verify endpoint. */
    public function testVerifyAlwaysFails(): void
    {
        // --- act + assert ---
        $this->assertFalse((new CasReAuthStrategy())->verify($this->getTestUserId(), new Request()));
    }

    /** Test the prompt metadata and the out of band verify endpoint. */
    public function testMetadata(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $strategy = new CasReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/cas_form.html.twig', $strategy->getPromptTemplate());
        $this->assertSame(50, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
        $this->assertSame($CFG_GLPI['root_doc'] . '/ReAuth/CAS', $strategy->getVerifyUrl());
        $this->assertSame('GET', $strategy->getVerifyHttpMethod());
    }

    private function getTestUserId(): int
    {
        return getItemByTypeName(\User::class, TU_USER, true);
    }
}
