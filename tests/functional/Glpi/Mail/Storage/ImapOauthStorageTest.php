<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Mail\Storage;

use CommonDBTM;
use Glpi\Mail\OauthProvider\OwnerDetails;
use Glpi\Mail\OauthProvider\ProviderInterface;
use Glpi\Mail\Protocol\ImapOauthProtocol;
use Glpi\Mail\Storage\ImapOauthStorage;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Mail\FakeImapServerProtocol;
use GLPIKey;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use OAuthApplication;
use OAuthAuthorization;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel;
use Throwable;

use function Safe\json_encode;

class ImapOauthStorageTest extends DbTestCase
{
    /**
     * Server answers to a successful XOAUTH2 authentication, then to the selection of a folder.
     */
    private const AUTHENTICATED_SESSION = "* OK IMAP4rev1 ready\r\n"
        . "TAG1 OK AUTHENTICATE completed\r\n"
        . "* 2 EXISTS\r\n"
        . "* FLAGS (\\Seen \\Deleted)\r\n"
        . "TAG2 OK [READ-WRITE] SELECT completed\r\n";

    public function tearDown(): void
    {
        TestableImapOauthStorage::$protocol_to_use = null;
        TestableImapOauthStorage::$authorization_to_use = null;
        parent::tearDown();
    }

    private function createApplication(): OAuthApplication
    {
        return $this->createItem(OAuthApplication::class, [
            'name'          => 'Test app ' . $this->getUniqueString(),
            'is_active'     => 1,
            'provider'      => OAuthApplication::GOOGLE,
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
        ], ['client_secret']);
    }

    private function createAuthorization(
        OAuthApplication $application,
        string $access_token,
        int $expires,
        string $type = OAuthAuthorization::TYPE_IMAP
    ): OAuthAuthorization {
        return $this->createItem(OAuthAuthorization::class, [
            'oauthapplications_id' => $application->getID(),
            'type'                 => $type,
            'email'                => 'mailbox@example.com',
            'token'                => json_encode(['access_token' => $access_token, 'expires' => $expires]),
            'refresh_token'        => 'the-refresh-token',
        ], ['token', 'refresh_token']);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function openStorage(
        OAuthApplication $application,
        FakeImapServerProtocol $protocol,
        ?FakeOauthProvider $provider = null,
        array $params = []
    ): TestableImapOauthStorage {
        TestableImapOauthStorage::$protocol_to_use = $protocol;
        TestableImapOauthStorage::$authorization_to_use = ProviderStubbedOAuthAuthorization::withProvider(
            $provider ?? new FakeOauthProvider()
        );

        return new TestableImapOauthStorage($params + [
            'application_id' => $application->getID(),
            'user'           => 'mailbox@example.com',
            'host'           => 'imap.gmail.com',
            'port'           => 993,
            'ssl'            => 'SSL',
        ]);
    }

    private function getBearerToken(FakeImapServerProtocol $protocol): string
    {
        $sent = $protocol->getSentData();
        $this->assertSame(1, preg_match('/^TAG1 AUTHENTICATE XOAUTH2 (\S+)\r\n/', $sent, $matches));
        $this->assertSame(1, preg_match('/auth=Bearer ([^\x01]+)/', base64_decode($matches[1]), $token));

        return $token[1];
    }

    public function testUsesTheStoredAccessToken(): void
    {
        $application = $this->createApplication();
        $this->createAuthorization($application, 'the-access-token', time() + 3600);

        $protocol = new FakeImapServerProtocol(self::AUTHENTICATED_SESSION, $application->getID());
        $provider = new FakeOauthProvider();
        $storage = $this->openStorage($application, $protocol, $provider, ['folder' => 'Support']);

        $this->assertSame(
            ['transport' => 'ssl', 'host' => 'imap.gmail.com', 'port' => 993, 'timeout' => 1],
            $protocol->socket_args
        );
        $this->assertSame('the-access-token', $this->getBearerToken($protocol));
        $this->assertStringContainsString("TAG2 SELECT \"Support\"\r\n", $protocol->getSentData());
        $this->assertSame('Support', $storage->getCurrentFolder());
        // No refresh was needed.
        $this->assertNull($provider->last_grant);
    }

    public function testRefreshesAnExpiredAccessToken(): void
    {
        $application = $this->createApplication();
        $authorization = $this->createAuthorization($application, 'the-expired-token', time() - 60);

        $provider = new FakeOauthProvider();
        $provider->access_token_result = new AccessToken([
            'access_token'  => 'the-new-token',
            'refresh_token' => 'the-new-refresh-token',
            'expires'       => time() + 3600,
        ]);

        $protocol = new FakeImapServerProtocol(self::AUTHENTICATED_SESSION, $application->getID());
        $storage = $this->openStorage($application, $protocol, $provider);

        $this->assertSame('refresh_token', $provider->last_grant);
        $this->assertSame(['refresh_token' => 'the-refresh-token'], $provider->last_options);
        $this->assertSame('the-new-token', $this->getBearerToken($protocol));
        $this->assertSame('INBOX', $storage->getCurrentFolder());

        // The refreshed tokens are stored.
        $this->assertTrue($authorization->getFromDB($authorization->getID()));
        $this->assertSame('the-new-token', $authorization->getAccessToken()?->getToken());
        $this->assertSame('the-new-refresh-token', (new GLPIKey())->decrypt($authorization->fields['refresh_token']));
    }

    public function testFailsWhenTheTokenCannotBeRefreshed(): void
    {
        $application = $this->createApplication();
        $this->createAuthorization($application, 'the-expired-token', time() - 60);

        $provider = new FakeOauthProvider();
        $provider->access_token_result = new \RuntimeException('invalid_grant');

        $protocol = new FakeImapServerProtocol(self::AUTHENTICATED_SESSION, $application->getID());

        try {
            $this->openStorage($application, $protocol, $provider);
            $this->fail('An exception was expected.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                'unable to get an access token for mailbox@example.com: Unable to refresh access token: invalid_grant',
                $e->getMessage()
            );
        }

        $this->hasPhpLogRecordThatContains('Error during token refresh: invalid_grant', LogLevel::WARNING);
        // No connection is opened without a token.
        $this->assertNull($protocol->socket_args);
    }

    public function testFailsWhenTheMailboxIsNotAuthorized(): void
    {
        $application = $this->createApplication();
        // A SMTP authorization cannot be used to collect emails.
        $this->createAuthorization($application, 'the-access-token', time() + 3600, OAuthAuthorization::TYPE_SMTP);

        $protocol = new FakeImapServerProtocol(self::AUTHENTICATED_SESSION, $application->getID());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('mailbox mailbox@example.com is not authorized on the OAuth application');

        $this->openStorage($application, $protocol);
    }

    public function testFailsWhenTheServerRejectsTheToken(): void
    {
        $application = $this->createApplication();
        $this->createAuthorization($application, 'the-access-token', time() + 3600);

        $protocol = new FakeImapServerProtocol(
            "* OK IMAP4rev1 ready\r\nTAG1 NO AUTHENTICATE failed.\r\n",
            $application->getID()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'cannot authenticate with OAuth access token of mailbox@example.com: NO AUTHENTICATE failed.'
        );

        $this->openStorage($application, $protocol);
    }

    public static function invalidParamsProvider(): iterable
    {
        yield 'no application' => [['user' => 'mailbox@example.com'], 'OAuth application must be defined'];
        yield 'no user' => [['application_id' => 1], 'need at least user in params'];
    }

    #[DataProvider('invalidParamsProvider')]
    public function testRequiresTheApplicationAndTheUser(array $params, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ImapOauthStorage($params);
    }

    public function testSharesAnAlreadyAuthenticatedConnection(): void
    {
        $protocol = new FakeImapServerProtocol(self::AUTHENTICATED_SESSION);
        $protocol->connect('imap.gmail.com', null, 'SSL');
        $this->assertTrue($protocol->login('mailbox@example.com', 'the-access-token'));

        $storage = new ImapOauthStorage($protocol);

        // Only the folder selection is sent on the shared connection.
        $this->assertStringEndsWith("TAG2 SELECT \"INBOX\"\r\n", $protocol->getSentData());
        $this->assertSame(1, substr_count($protocol->getSentData(), 'AUTHENTICATE'));
        $this->assertSame('INBOX', $storage->getCurrentFolder());
    }
}

/**
 * Storage using the fake IMAP server and the fake OAuth provider given by the test.
 */
final class TestableImapOauthStorage extends ImapOauthStorage
{
    public static ?ImapOauthProtocol $protocol_to_use = null;

    public static ?OAuthAuthorization $authorization_to_use = null;

    protected function createAuthorization(): OAuthAuthorization
    {
        return self::$authorization_to_use ?? parent::createAuthorization();
    }

    protected function createProtocol(int $application_id): ImapOauthProtocol
    {
        return self::$protocol_to_use ?? parent::createProtocol($application_id);
    }
}

/**
 * Authorization refreshing its token through a fake OAuth provider.
 */
final class ProviderStubbedOAuthAuthorization extends OAuthAuthorization
{
    private ProviderInterface $provider;

    public static function withProvider(ProviderInterface $provider): self
    {
        $instance = new self();
        $instance->provider = $provider;
        return $instance;
    }

    /**
     * The table name is derived from the class name, so this subclass would
     * otherwise resolve to a table of its own that does not exist.
     */
    public static function getTable($classname = null)
    {
        return CommonDBTM::getTable(OAuthAuthorization::class);
    }

    protected function resolveProvider(OAuthApplication $application, string $type): ProviderInterface
    {
        return $this->provider;
    }
}

/**
 * OAuth provider answering token requests without any HTTP call.
 */
final class FakeOauthProvider implements ProviderInterface
{
    public AccessToken|Throwable|null $access_token_result = null;

    public ?string $last_grant = null;

    /** @var array<string, mixed> */
    public array $last_options = [];

    public function getAuthorizationUrl(array $options = [])
    {
        return 'https://provider.example.com/authorize';
    }

    public function getAccessToken($grant, array $options = [])
    {
        $this->last_grant = $grant;
        $this->last_options = $options;

        if ($this->access_token_result instanceof Throwable) {
            throw $this->access_token_result;
        }

        return $this->access_token_result ?? throw new \RuntimeException('No token configured.');
    }

    public function getState()
    {
        return 'fake-state';
    }

    public function getOwnerDetails(AccessTokenInterface $token): ?OwnerDetails
    {
        return null;
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
