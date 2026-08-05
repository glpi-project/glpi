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

namespace tests\units;

use Glpi\Mail\OauthProvider\OwnerDetails;
use Glpi\Mail\OauthProvider\ProviderInterface;
use Glpi\Tests\DbTestCase;
use GLPIKey;
use League\OAuth2\Client\Token\AccessToken;
use OAuthApplication;
use OAuthAuthorization;
use RuntimeException;

class OAuthAuthorizationTest extends DbTestCase
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

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function testAdd(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'code'                  => 'auth-code',
            'token'                 => 'token-json',
            'refresh_token'         => 'refresh-value',
        ], ['code', 'token', 'refresh_token']);

        $this->assertSame($app->getID(), (int) $authorization->fields['oauth_applications_id']);
        $this->assertSame(OAuthAuthorization::TYPE_IMAP, $authorization->fields['type']);
        $this->assertSame('user@example.com', $authorization->fields['email']);
    }

    public function testUpdate(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'token'                 => 'token-json',
            'refresh_token'         => 'refresh-value',
        ], ['token', 'refresh_token']);

        $updated = $this->updateItem(OAuthAuthorization::class, $authorization->getID(), [
            'email' => 'renamed@example.com',
        ]);

        $this->assertSame('renamed@example.com', $updated->fields['email']);
    }

    public function testPurge(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'to-purge@example.com',
        ]);

        $id = $authorization->getID();
        $this->assertTrue($authorization->delete(['id' => $id], true));
        $this->assertFalse($authorization->getFromDB($id));
    }

    // -------------------------------------------------------------------------
    // Encryption
    // -------------------------------------------------------------------------

    public function testTokenFieldsAreEncryptedOnAdd(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'code'                  => 'plain-code',
            'token'                 => 'plain-token',
            'refresh_token'         => 'plain-refresh',
        ], ['code', 'token', 'refresh_token']);

        $key = new GLPIKey();
        $this->assertNotSame('plain-code', $authorization->fields['code']);
        $this->assertNotSame('plain-token', $authorization->fields['token']);
        $this->assertNotSame('plain-refresh', $authorization->fields['refresh_token']);
        $this->assertSame('plain-code', $key->decrypt($authorization->fields['code']));
        $this->assertSame('plain-token', $key->decrypt($authorization->fields['token']));
        $this->assertSame('plain-refresh', $key->decrypt($authorization->fields['refresh_token']));
    }

    public function testUndisclosedFields(): void
    {
        $this->assertContains('code', OAuthAuthorization::$undisclosedFields);
        $this->assertContains('token', OAuthAuthorization::$undisclosedFields);
        $this->assertContains('refresh_token', OAuthAuthorization::$undisclosedFields);
    }

    // -------------------------------------------------------------------------
    // createFromCode()
    // -------------------------------------------------------------------------

    public function testCreateFromCodeFailsWhenTokenExchangeFails(): void
    {
        $this->login();

        $app = $this->createApplication();

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new RuntimeException('invalid_grant');

        $authorization = TestableOAuthAuthorization::withProvider($provider);
        $result = $authorization->createFromCode($app->getID(), OAuthAuthorization::TYPE_IMAP, 'a-code');

        $this->assertFalse($result);
        $this->assertStringContainsString('invalid_grant', $authorization->getLastError());
        $this->hasPhpLogRecordThatContains('Error during authorization code fetching: invalid_grant', 'Warning');
    }

    public function testCreateFromCodeFailsWhenEmailIsMissing(): void
    {
        $this->login();

        $app = $this->createApplication();

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new AccessToken(['access_token' => 'abc', 'expires_in' => 3600]);
        $provider->owner_details = new OwnerDetails();

        $authorization = TestableOAuthAuthorization::withProvider($provider);
        $result = $authorization->createFromCode($app->getID(), OAuthAuthorization::TYPE_IMAP, 'a-code');

        $this->assertFalse($result);
        $this->assertSame(
            'The authenticated account does not expose an email address',
            $authorization->getLastError()
        );
        $this->hasPhpLogRecordThatContains('Unable to get user email', 'Warning');
    }

    public function testCreateFromCodeSucceeds(): void
    {
        $this->login();

        $app = $this->createApplication();

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new AccessToken([
            'access_token'  => 'abc',
            'refresh_token' => 'refresh-abc',
            'expires_in'    => 3600,
        ]);
        $owner = new OwnerDetails();
        $owner->email = 'user@example.com';
        $provider->owner_details = $owner;

        $authorization = TestableOAuthAuthorization::withProvider($provider);
        $result = $authorization->createFromCode($app->getID(), OAuthAuthorization::TYPE_IMAP, 'a-code');

        $this->assertTrue($result);
        $this->assertNull($authorization->getLastError());
        $this->assertSame('user@example.com', $authorization->fields['email']);
        $this->assertSame(OAuthAuthorization::TYPE_IMAP, $authorization->fields['type']);
    }

    public function testCreateFromCodeUpdatesExistingAuthorizationForSameEmailAndType(): void
    {
        $this->login();

        $app = $this->createApplication();

        $provider = new FakeOauthProviderForTests();
        $owner = new OwnerDetails();
        $owner->email = 'user@example.com';
        $provider->owner_details = $owner;

        $provider->access_token_result = new AccessToken(['access_token' => 'first', 'expires_in' => 3600]);
        $first = TestableOAuthAuthorization::withProvider($provider);
        $this->assertTrue($first->createFromCode($app->getID(), OAuthAuthorization::TYPE_IMAP, 'code-1'));
        $first_id = $first->getID();

        $provider->access_token_result = new AccessToken(['access_token' => 'second', 'expires_in' => 3600]);
        $second = TestableOAuthAuthorization::withProvider($provider);
        $this->assertTrue($second->createFromCode($app->getID(), OAuthAuthorization::TYPE_IMAP, 'code-2'));

        $this->assertSame($first_id, $second->getID());

        global $DB;
        $count = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => OAuthAuthorization::getTable(),
            'WHERE' => [
                'oauth_applications_id' => $app->getID(),
                'email'                 => 'user@example.com',
                'type'                  => OAuthAuthorization::TYPE_IMAP,
            ],
        ])->current()['cpt'];
        $this->assertSame(1, (int) $count);
    }

    // -------------------------------------------------------------------------
    // refreshToken()
    // -------------------------------------------------------------------------

    public function testRefreshTokenFailsWithoutRefreshToken(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
        ]);

        $this->assertFalse($authorization->refreshToken());
        $this->assertNotNull($authorization->getLastError());
    }

    public function testRefreshTokenRotatesRefreshTokenWhenProviderIssuesANewOne(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'refresh_token'         => 'old-refresh',
        ], ['refresh_token']);

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new AccessToken([
            'access_token'  => 'new-access',
            'refresh_token' => 'new-refresh',
            'expires_in'    => 3600,
        ]);

        $testable = TestableOAuthAuthorization::withProvider($provider);
        $testable->getFromDB($authorization->getID());

        $this->assertTrue($testable->refreshToken());
        $this->assertSame('refresh_token', $provider->last_grant);
        $this->assertSame('old-refresh', $provider->last_options['refresh_token']);

        $testable->getFromDB($authorization->getID());
        $key = new GLPIKey();
        $this->assertSame('new-refresh', $key->decrypt($testable->fields['refresh_token']));
        $this->assertSame('new-access', (new AccessToken(json_decode($key->decrypt($testable->fields['token']), true)))->getToken());
    }

    public function testRefreshTokenKeepsRefreshTokenWhenProviderDoesNotRotateIt(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'refresh_token'         => 'stable-refresh',
        ], ['refresh_token']);

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new AccessToken([
            'access_token' => 'new-access',
            'expires_in'   => 3600,
        ]);

        $testable = TestableOAuthAuthorization::withProvider($provider);
        $testable->getFromDB($authorization->getID());
        $this->assertTrue($testable->refreshToken());

        $testable->getFromDB($authorization->getID());
        $key = new GLPIKey();
        $this->assertSame('stable-refresh', $key->decrypt($testable->fields['refresh_token']));
    }

    // -------------------------------------------------------------------------
    // testConnection()
    // -------------------------------------------------------------------------

    public function testTestConnectionSucceeds(): void
    {
        $this->login();

        $app = $this->createApplication();

        $token = new AccessToken(['access_token' => 'abc', 'expires_in' => 3600]);
        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'token'                 => json_encode($token->jsonSerialize()),
        ], ['token']);

        $provider = new FakeOauthProviderForTests();
        $testable = TestableOAuthAuthorization::withProvider($provider);
        $testable->getFromDB($authorization->getID());
        $testable->probe_result = true;

        $result = $testable->testConnection();
        $this->assertTrue($result['success']);
    }

    public function testTestConnectionFailsWhenProbeFails(): void
    {
        $this->login();

        $app = $this->createApplication();

        $token = new AccessToken(['access_token' => 'abc', 'expires_in' => 3600]);
        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'token'                 => json_encode($token->jsonSerialize()),
        ], ['token']);

        $provider = new FakeOauthProviderForTests();
        $testable = TestableOAuthAuthorization::withProvider($provider);
        $testable->getFromDB($authorization->getID());
        $testable->probe_result = false;

        $result = $testable->testConnection();
        $this->assertFalse($result['success']);
    }

    public function testTestConnectionRefreshesExpiredTokenFirst(): void
    {
        $this->login();

        $app = $this->createApplication();

        $expired_token = new AccessToken(['access_token' => 'old', 'expires' => time() - 100]);
        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
            'token'                 => json_encode($expired_token->jsonSerialize()),
            'refresh_token'         => 'refresh-value',
        ], ['token', 'refresh_token']);

        $provider = new FakeOauthProviderForTests();
        $provider->access_token_result = new AccessToken(['access_token' => 'fresh', 'expires_in' => 3600]);

        $testable = TestableOAuthAuthorization::withProvider($provider);
        $testable->getFromDB($authorization->getID());
        $testable->probe_result = true;

        $result = $testable->testConnection();

        $this->assertTrue($result['success']);
        $this->assertSame('refresh_token', $provider->last_grant);
    }

    // -------------------------------------------------------------------------
    // revokeAuthorization()
    // -------------------------------------------------------------------------

    public function testRevokeAuthorizationDeletesTheRow(): void
    {
        $this->login();

        $app = $this->createApplication();

        /** @var OAuthAuthorization $authorization */
        $authorization = $this->createItem(OAuthAuthorization::class, [
            'oauth_applications_id' => $app->getID(),
            'type'                  => OAuthAuthorization::TYPE_IMAP,
            'email'                 => 'user@example.com',
        ]);

        $id = $authorization->getID();
        $this->assertTrue($authorization->revokeAuthorization());
        $this->assertFalse((new OAuthAuthorization())->getFromDB($id));
    }
}

/**
 * Minimal fake provider used to exercise OAuthAuthorization's token-exchange
 * logic without ever performing a real HTTP/OAuth2 call.
 */
final class FakeOauthProviderForTests implements ProviderInterface
{
    /** @var AccessToken|\Throwable|null */
    public $access_token_result = null;

    public ?OwnerDetails $owner_details = null;

    public ?string $last_grant = null;

    public array $last_options = [];

    public function getAuthorizationUrl(array $options = [])
    {
        return 'https://example.com/authorize';
    }

    public function getAccessToken($grant, array $options = [])
    {
        $this->last_grant = $grant;
        $this->last_options = $options;

        if ($this->access_token_result instanceof \Throwable) {
            throw $this->access_token_result;
        }

        return $this->access_token_result;
    }

    public function getState()
    {
        return 'fake-state';
    }

    public function getOwnerDetails(AccessToken $token): ?OwnerDetails
    {
        return $this->owner_details;
    }

    public static function getImapDefaults(): array
    {
        return ['host' => 'imap.example.com', 'port' => 993, 'ssl' => 'SSL'];
    }

    public static function getName(): string
    {
        return 'Fake';
    }
}

/**
 * Test double allowing the OAuth provider and the low-level protocol probes
 * to be substituted, so token-exchange/refresh/troubleshoot logic can be
 * unit-tested without any real network access.
 */
final class TestableOAuthAuthorization extends OAuthAuthorization
{
    public ?bool $probe_result = null;

    private ?ProviderInterface $fake_provider = null;

    public static function withProvider(ProviderInterface $provider): self
    {
        $instance = new self();
        $instance->fake_provider = $provider;
        return $instance;
    }

    protected function resolveProvider(OAuthApplication $application, string $type): ProviderInterface
    {
        return $this->fake_provider;
    }

    protected function probeImapConnection(string $host, int $port, string $ssl, string $email, string $token): array
    {
        return $this->fakeProbeResult();
    }

    protected function probeSmtpConnection(string $host, int $port, string $email, string $token): array
    {
        return $this->fakeProbeResult();
    }

    /** @return array{success: bool, message: string} */
    private function fakeProbeResult(): array
    {
        $success = $this->probe_result ?? true;

        return [
            'success' => $success,
            'message' => $success ? 'Connection successful' : 'Connection failed',
        ];
    }
}
