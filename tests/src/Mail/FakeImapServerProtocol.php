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

namespace Glpi\Tests\Mail;

use Glpi\Mail\Protocol\ImapOauthProtocol;

use function Safe\fclose;
use function Safe\fwrite;
use function Safe\stream_get_contents;
use function Safe\stream_set_blocking;
use function Safe\stream_set_timeout;
use function Safe\stream_socket_pair;

/**
 * `ImapOauthProtocol` talking to a scripted IMAP server instead of a real one.
 *
 * The socket is one end of a local socket pair: the server answers, given in
 * advance, are written on the other end before any command is sent, and the
 * commands sent by the client can be read back with `getSentData()`.
 */
final class FakeImapServerProtocol extends ImapOauthProtocol
{
    /**
     * Arguments `setupSocket()` was called with.
     *
     * @var array{transport: string, host: string, port: ?int, timeout: int}|null
     */
    public ?array $socket_args = null;

    /** @var resource|null */
    private $server = null;

    private string $sent_data = '';

    public function __construct(
        private readonly string $server_answers,
        int $application_id = 1,
    ) {
        parent::__construct($application_id, 1);
    }

    protected function setupSocket(string $transport, string $host, ?int $port, int $timeout)
    {
        $this->socket_args = [
            'transport' => $transport,
            'host'      => $host,
            'port'      => $port,
            'timeout'   => $timeout,
        ];

        [$client, $server] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        fwrite($server, $this->server_answers);
        stream_set_blocking($server, false);
        // A missing answer must not block the test forever.
        stream_set_timeout($client, 1);

        $this->server = $server;

        return $client;
    }

    /**
     * Returns everything the client sent to the server so far.
     */
    public function getSentData(): string
    {
        if ($this->server !== null) {
            $this->sent_data .= stream_get_contents($this->server);
        }

        return $this->sent_data;
    }

    public function __destruct()
    {
        // No `LOGOUT` is sent: no answer is scripted for it.
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->socket = null;
        if ($this->server !== null) {
            fclose($this->server);
            $this->server = null;
        }
        parent::__destruct();
    }
}
