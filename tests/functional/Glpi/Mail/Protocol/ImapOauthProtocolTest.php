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

namespace tests\units\Glpi\Mail\Protocol;

use Glpi\Mail\Protocol\ImapOauthProtocol;
use Glpi\Mail\Protocol\ProtocolInterface;
use Glpi\Tests\GLPITestCase;
use Glpi\Tests\Mail\FakeImapServerProtocol;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;

use function Safe\json_encode;

class ImapOauthProtocolTest extends GLPITestCase
{
    public function testIsAMailServerProtocol(): void
    {
        $protocol = new ImapOauthProtocol(42);

        $this->assertInstanceOf(ProtocolInterface::class, $protocol);
        $this->assertSame(42, $protocol->getApplicationId());
    }

    public static function connectionProvider(): iterable
    {
        yield 'SSL, default port' => ['SSL', null, 'ssl', 993];
        yield 'SSL, lower case' => ['ssl', null, 'ssl', 993];
        yield 'SSL, custom port' => ['SSL', 1993, 'ssl', 1993];
        yield 'no encryption, default port' => [false, null, 'tcp', 143];
        yield 'no encryption, custom port' => [false, 1143, 'tcp', 1143];
    }

    #[DataProvider('connectionProvider')]
    public function testConnectOpensTheSocketWithTheExpectedTransport(
        string|bool $ssl,
        ?int $port,
        string $expected_transport,
        int $expected_port
    ): void {
        $protocol = new FakeImapServerProtocol("* OK IMAP4rev1 ready\r\n");

        $protocol->connect('imap.example.com', $port, $ssl);

        $this->assertSame(
            [
                'transport' => $expected_transport,
                'host'      => 'imap.example.com',
                'port'      => $expected_port,
                'timeout'   => 1,
            ],
            $protocol->socket_args
        );
        $this->assertSame('', $protocol->getSentData());
    }

    public function testConnectWithTlsUsesStartTls(): void
    {
        // STARTTLS is refused, so that no TLS handshake is attempted on the fake socket.
        $protocol = new FakeImapServerProtocol("* OK IMAP4rev1 ready\r\nTAG1 NO STARTTLS unavailable\r\n");

        try {
            $protocol->connect('imap.example.com', null, 'TLS');
            $this->fail('An exception was expected.');
        } catch (RuntimeException $e) {
            $this->assertSame('cannot enable TLS', $e->getMessage());
        }

        $this->assertSame('tcp', $protocol->socket_args['transport']);
        $this->assertSame(143, $protocol->socket_args['port']);
        $this->assertSame("TAG1 STARTTLS\r\n", $protocol->getSentData());
    }

    public function testConnectFailsWhenTheServerRefusesTheConnection(): void
    {
        $protocol = new FakeImapServerProtocol("* BYE too many connections\r\n");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('host doesn\'t allow connection');

        $protocol->connect('imap.example.com', null, 'SSL');
    }

    public function testLoginAuthenticatesWithXoauth2(): void
    {
        $protocol = new FakeImapServerProtocol(
            "* OK IMAP4rev1 ready\r\n"
            . "* CAPABILITY IMAP4rev1\r\n" // untagged lines are skipped
            . "TAG1 OK AUTHENTICATE completed\r\n"
        );
        $protocol->connect('imap.example.com', null, 'SSL');

        $this->assertTrue($protocol->login('mailbox@example.com', 'the-access-token'));
        $this->assertNull($protocol->getLastError());

        $this->assertSame(
            'TAG1 AUTHENTICATE XOAUTH2 '
                . base64_encode("user=mailbox@example.com\1auth=Bearer the-access-token\1\1")
                . "\r\n",
            $protocol->getSentData()
        );
    }

    public function testLoginReportsTheXoauth2Failure(): void
    {
        $challenge = base64_encode(json_encode([
            'status'  => '401',
            'schemes' => 'bearer',
            'scope'   => 'https://mail.google.com/',
        ]));
        $protocol = new FakeImapServerProtocol(
            "* OK IMAP4rev1 ready\r\n"
            . "+ {$challenge}\r\n"
            . "TAG1 NO AUTHENTICATE failed.\r\n"
        );
        $protocol->connect('imap.example.com', null, 'SSL');

        $this->assertFalse($protocol->login('mailbox@example.com', 'a-revoked-token'));
        $this->assertSame('NO AUTHENTICATE failed. (status: 401)', $protocol->getLastError());

        // The challenge is acknowledged with an empty response, as required by XOAUTH2.
        $this->assertStringEndsWith("\r\n\r\n", $protocol->getSentData());
    }

    public function testLoginHandlesABareContinuationLine(): void
    {
        $protocol = new FakeImapServerProtocol(
            "* OK IMAP4rev1 ready\r\n"
            . "+\r\n"
            . "TAG1 BAD invalid command\r\n"
        );
        $protocol->connect('imap.example.com', null, 'SSL');

        $this->assertFalse($protocol->login('mailbox@example.com', 'a-token'));
        $this->assertSame('BAD invalid command', $protocol->getLastError());
    }

    public function testLoginFailsWhenTheConnectionIsClosed(): void
    {
        $protocol = new FakeImapServerProtocol("* OK IMAP4rev1 ready\r\n");
        $protocol->connect('imap.example.com', null, 'SSL');

        $this->expectException(RuntimeException::class);

        $protocol->login('mailbox@example.com', 'a-token');
    }
}
