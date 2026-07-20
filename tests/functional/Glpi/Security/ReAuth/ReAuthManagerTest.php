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

use Computer;
use Glpi\Exception\RedirectException;
use Glpi\Security\ReAuth\ReAuthManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Safe\DateTime;
use User;

#[Group('reauth')]
class ReAuthManagerTest extends DbTestCase
{
    use ReAuthTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->setCurrentTime('2026-06-22 10:00:00');
        $this->resetReAuthManager();
    }

    public function tearDown(): void
    {
        // Always restore the CLI flag so the reauth branch does not leak to other tests.
        unset($GLOBALS['GLPI_IS_COMMAND_LINE']);
        $this->resetReAuthManager();
        parent::tearDown();
    }

    public static function isReAuthenticatedProvider(): iterable
    {
        // [offset_to_now (null = unset), expected]
        yield 'validity ok' => [ReAuthManager::REAUTH_DELAY_SECONDS, true];
        yield 'validity expired' => [-ReAuthManager::REAUTH_DELAY_SECONDS, false];
        yield 'validity exactly now' => [0, false];
        yield 'validity not set' => [null, false];
    }

    #[DataProvider('isReAuthenticatedProvider')]
    public function testIsReAuthenticated(?int $offset, bool $expected): void
    {
        // --- arrange ---
        if ($offset === null) {
            unset($_SESSION['glpi_reauth_until']);
        } else {
            $_SESSION['glpi_reauth_until'] = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp() + $offset;
        }

        // --- act + assert ---
        $this->assertSame($expected, $this->getReAuthManager()->isReAuthenticated());
    }

    /** authenticate() sets glpi_reauth_until to currenttime + REAUTH_DELAY_SECONDS. */
    public function testAuthenticateSetsValidityToNowPlusDelay(): void
    {
        // --- arrange ---
        unset($_SESSION['glpi_reauth_until']);

        // --- act ---
        $this->getReAuthManager()->authenticate();

        // --- assert ---
        $expected = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp() + ReAuthManager::REAUTH_DELAY_SECONDS;
        $this->assertSame($expected, $_SESSION['glpi_reauth_until']);
        $this->assertTrue($this->getReAuthManager()->isReAuthenticated());
    }

    /** No exception is thrown when the session is already re-authenticated. */
    public function testCheckReAuthenticationOrRedirectDoesNothingWhenReAuthenticated(): void
    {
        // --- arrange ---
        $this->getReAuthManager()->authenticate();

        // --- act ---
        $this->getReAuthManager()->checkReAuthenticationOrRedirect();

        // --- assert : no exception thrown, the session is still valid ---
        $this->assertTrue($this->getReAuthManager()->isReAuthenticated());
    }

    /** Throws RedirectException when there is no valid re-authentication token in session. */
    public function testCheckReAuthenticationOrRedirectThrowsWhenNotReAuthenticated(): void
    {
        // --- arrange ---
        unset($_SESSION['glpi_reauth_until']);
        $this->fakeWebContext(request_uri: '/front/user.form.php?id=2');

        // --- act + assert ---
        $this->expectException(RedirectException::class);
        $this->getReAuthManager()->checkReAuthenticationOrRedirect();
    }

    /** GET request URL and data are stored in session before the RedirectException is thrown. */
    public function testRedirectToReauthStoresPostRequestData(): void
    {
        // --- arrange ---
        $this->fakeWebContext(
            request_uri: '/front/user.form.php?id=2',
            method: 'POST',
            post: ['name' => 'value'],
            referer: 'https://glpi.example.org/front/user.php',
        );

        // --- act ---
        // try/catch because $this->expectException() stop the test at the point exception is thrown
        // so testing $this->getReAuthManager()->xxx() would be impossible.
        try {
            $this->getReAuthManager()->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame('POST', $this->getReAuthManager()->getRequestedMethod());
        $this->assertSame('value', $this->getReAuthManager()->getRequestedPostData()['name']);
        // The GET query string of a POST request is preserved: browsers keep the action
        // URL's query string untouched on replay since the form data goes in the body.
        $this->assertSame('https://glpi.example.org/front/user.form.php?id=2', $this->getReAuthManager()->getRequestedURL());
        // _glpi_http_referer is only meaningful for POST replays: Html::getRefererUrl()
        // reads it from $_POST, so it must be present here.
        $this->assertArrayHasKey('_glpi_http_referer', $this->getReAuthManager()->getRequestedPostData());
    }

    /** The requested URL drops its GET query string: browsers rebuild it from the form fields on replay. */
    public function testRedirectToReauthStoresUrlWithoutGetParams(): void
    {
        // --- arrange ---
        $requested_url = '/front/user.form.php?id=2&another_param=value';
        $this->fakeWebContext(request_uri: $requested_url);

        // --- act ---
        try {
            $this->getReAuthManager()->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame('https://glpi.example.org/front/user.form.php', $this->getReAuthManager()->getRequestedURL());
        // _glpi_http_referer would only ever land in $_GET on replay, which
        // Html::getRefererUrl() never reads, so it must not be injected here.
        $this->assertArrayNotHasKey('_glpi_http_referer', $this->getReAuthManager()->getRequestedPostData());
    }

    /**
     * A referer pointing to the reauth prompt itself (e.g. abandoned then reached again from
     * another reauth-gated page) must not be stored as the origin URL, or the "Cancel" link
     * and the replayed request's referer would point back into the reauth flow.
     */
    public function testRedirectToReauthDoesNotStoreReauthRouteAsOriginUrl(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $this->fakeWebContext(
            request_uri: '/front/user.form.php?id=2',
            referer: 'https://glpi.example.org/ReAuth/Prompt',
        );

        // --- act ---
        // try/catch because $this->expectException() stop the test at the point exception is thrown
        // so testing $this->getReAuthManager()->xxx() would be impossible.
        try {
            $this->getReAuthManager()->redirectToReauth();
            $this->fail('A RedirectException should have been thrown.');
        } catch (RedirectException) {
        }

        // --- assert ---
        $this->assertSame($CFG_GLPI['root_doc'], $this->getReAuthManager()->getOriginURL());
    }

    /** All getters return safe defaults when the re-auth session keys are absent. */
    public function testGettersReturnDefaultsWhenSessionEmpty(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        unset(
            $_SESSION['glpi_reauth_requested_url'],
            $_SESSION['glpi_reauth_origin_url'],
            $_SESSION['glpi_reauth_requested_httpmethod'],
            $_SESSION['glpi_reauth_requested_post_data'],
        );

        // --- assert ---
        $this->assertSame('/', $this->getReAuthManager()->getRequestedURL());
        $this->assertSame('GET', $this->getReAuthManager()->getRequestedMethod());
        $this->assertSame($CFG_GLPI['root_doc'], $this->getReAuthManager()->getOriginURL());
        // Default method is GET: no _glpi_http_referer is injected (see testRedirectToReauthStoresPostRequestData).
        $this->assertSame([], $this->getReAuthManager()->getRequestedPostData());
    }

    /**
     * When the user has no stronger strategy available (no local password, no TOTP), the
     * always-available fallback strategy is selected and its confirmation prompt is used.
     */
    public function testFallbackStrategyIsSelectedWhenNoStrongStrategyAvailable(): void
    {
        global $DB;

        // --- arrange : logged-in user stripped of every strong strategy ---
        $this->login();
        $users_id = (int) $_SESSION['glpiID'];
        // Direct DB update to bypass the business layer, which would not let us clear the password/2FA fields.
        $DB->update('glpi_users', ['password' => '', '2fa' => null], ['id' => $users_id]);
        $manager = new ReAuthManager();

        // --- act + assert : the fallback prompt is used and any input is accepted ---
        $this->assertSame('pages/reauth/fallback_form.html.twig', $manager->getPromptTemplate());
        $this->assertTrue($manager->verify('no-check-is-done'));
    }

    /** Throws InvalidArgumentException when a non-CommonGLPI class is passed. */
    public function testAtLeastOneItemTypeRequiresReauthenticationThrowsOnInvalidType(): void
    {
        // --- act + assert ---
        $this->expectException(InvalidArgumentException::class);
        $this->getReAuthManager()->atLeastOneItemTypesRequiresReauthentication([\stdClass::class]);
    }

    public static function atLeastOneItemTypeRequiresReauthenticationProvider(): iterable
    {
        // [itemtypes, expected]
        yield 'one of the itemtypes requires reauth (1 item)' => [[User::class], true];
        yield 'one of the itemtypes requires reauth (2 items)' => [[Computer::class, User::class], true];
        yield 'none of the itemtypes requires reauth (0 item)' => [[], false];
        yield 'none of the itemtypes requires reauth (1 item)' => [[Computer::class], false];
    }

    /** Returns true only when at least one of the given itemtypes requires re-authentication. */
    #[DataProvider('atLeastOneItemTypeRequiresReauthenticationProvider')]
    public function testAtLeastOneItemTypeRequiresReauthentication(array $itemtypes, bool $expected): void
    {
        // --- arrange : web context, not yet re-authenticated ---
        $GLOBALS['GLPI_IS_COMMAND_LINE'] = false;
        unset($_SESSION['glpi_reauth_until']);

        // --- act + assert ---
        $this->assertSame($expected, $this->getReAuthManager()->atLeastOneItemTypesRequiresReauthentication($itemtypes));
    }

    /** A registered (plugin) strategy is selected when it is available and outranks the native ones. */
    public function testRegisteredAvailableStrategyWithHighestPriorityIsSelected(): void
    {
        // --- arrange ---
        $this->login();
        $users_id = (int) $_SESSION['glpiID'];
        $manager = $this->getReAuthManager();

        // Precondition: the plugin strategy only wins if it strictly outranks every strategy
        // already available to the user. A native strategy with an equal or higher priority
        // would win the tie (native strategies are resolved first and usort() is stable),
        // silently invalidating the assertion below. Guard against that here.
        $plugin_priority = 101;
        assert(
            $this->getHighestAvailableStrategyPriority($users_id) < $plugin_priority,
            'Test precondition failed : A strategy already available to the user outranks the tested plugin strategy.',
        );

        $manager->registerStrategy($this->makeStrategy('Plugin SSO', $plugin_priority, true));

        // --- act + assert : the plugin strategy wins the resolution ---
        $this->assertSame('Plugin SSO', $manager->getLabel());
    }

    /** A registered strategy that is not available for the user is ignored, even with a high priority. */
    public function testRegisteredUnavailableStrategyIsIgnored(): void
    {
        // --- arrange : highest priority but unavailable ---
        $this->login();
        $manager = $this->getReAuthManager();
        $manager->registerStrategy($this->makeStrategy('Plugin SSO', 999, false));

        // --- act + assert : resolution falls back to the native Password strategy ---
        $this->assertSame('Password', $manager->getLabel());
    }

    /** When the selected strategy is redirect-based, the manager exposes it and its redirect URL. */
    public function testRedirectStrategyIsDetectedAndExposesUrl(): void
    {
        // --- arrange : a redirect strategy outranking the native ones ---
        $this->login();
        $manager = $this->getReAuthManager();
        $manager->registerStrategy(
            $this->makeRedirectStrategy('Plugin SSO', 100, true, '/plugins/oauthsso/front/reauth.php'),
        );

        // --- act + assert ---
        $this->assertTrue($manager->isRedirectStrategy());
        $this->assertSame('/plugins/oauthsso/front/reauth.php', $manager->getReauthUrl());
    }

    /** A native (synchronous) strategy is not reported as redirect-based. */
    public function testNativeStrategyIsNotRedirect(): void
    {
        $this->login();
        $this->assertFalse($this->getReAuthManager()->isRedirectStrategy());
    }

    /** getReauthUrl() must not be called for a non-redirect strategy. */
    public function testGetReauthUrlThrowsForNonRedirectStrategy(): void
    {
        // --- arrange : native Password strategy selected ---
        $this->login();

        // --- act + assert ---
        $this->expectException(\LogicException::class);
        $this->getReAuthManager()->getReauthUrl();
    }
}
