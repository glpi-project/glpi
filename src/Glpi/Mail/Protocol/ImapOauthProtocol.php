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

namespace Glpi\Mail\Protocol;

use Laminas\Mail\Protocol\Exception\RuntimeException;
use Laminas\Mail\Protocol\Imap;
use Safe\Exceptions\FilesystemException;
use Throwable;

use function Safe\base64_decode;
use function Safe\fwrite;
use function Safe\json_decode;
use function Safe\preg_match;

/**
 * IMAP protocol authenticating with an OAuth access token (SASL XOAUTH2),
 * for mailboxes authorized on an `OAuthApplication`.
 */
class ImapOauthProtocol extends Imap implements ProtocolInterface
{
    /**
     * Error explained by the server on the last failed login, if any.
     */
    private ?string $last_error = null;

    /**
     * @param int $application_id ID of the `OAuthApplication` the mailbox is authorized on.
     * @param int $timeout        Connection and read timeout, in seconds.
     */
    public function __construct(
        private readonly int $application_id,
        private readonly int $timeout = self::TIMEOUT_CONNECTION,
    ) {
        parent::__construct();
    }

    public function getApplicationId(): int
    {
        return $this->application_id;
    }

    public function getLastError(): ?string
    {
        return $this->last_error;
    }

    /**
     * Same as the parent method, but with a configurable timeout.
     *
     * {@inheritDoc}
     */
    public function connect($host, $port = null, $ssl = false)
    {
        $transport = 'tcp';
        $is_tls    = false;

        switch (is_string($ssl) ? strtolower($ssl) : $ssl) {
            case 'ssl':
                $transport = 'ssl';
                $port = $port ?: 993;
                break;
            case 'tls':
                $is_tls = true;
                $port = $port ?: 143;
                break;
            default:
                $port = $port ?: 143;
                break;
        }

        $this->socket = $this->setupSocket($transport, $host, (int) $port, $this->timeout);

        if (!$this->assumedNextLine('* OK')) {
            throw new RuntimeException('host doesn\'t allow connection');
        }

        if ($is_tls) {
            $result = $this->requestAndResponse('STARTTLS');
            $result = $result && stream_socket_enable_crypto($this->socket, true, $this->getCryptoMethod());
            if (!$result) {
                throw new RuntimeException('cannot enable TLS');
            }
        }
    }

    /**
     * Authenticates with the `AUTHENTICATE XOAUTH2` SASL mechanism.
     *
     * @param string $user     Email of the authorized mailbox.
     * @param string $password OAuth access token of the mailbox (no password is used with OAuth).
     *
     * @return bool
     */
    public function login($user, $password)
    {
        $this->last_error = null;

        if (!is_resource($this->socket)) {
            throw new RuntimeException('not connected');
        }

        $tag = null;
        $this->sendRequest(
            'AUTHENTICATE',
            ['XOAUTH2', base64_encode(sprintf("user=%s\1auth=Bearer %s\1\1", $user, $password))],
            $tag
        );

        $challenge = null;
        while (true) {
            // Lines are split by hand: a bare `+` continuation line (without
            // trailing space) cannot be handled by `nextTaggedLine()`.
            $line = rtrim($this->nextLine(), "\r\n");
            [$line_tag, $response] = array_pad(explode(' ', $line, 2), 2, '');

            if ($line_tag === '+') {
                // On failure, the server sends a base64 JSON challenge describing
                // the error, and waits for an empty client response before
                // sending its final tagged answer.
                $challenge = $response;
                $this->sendEmptyResponse();
                continue;
            }

            if ($line_tag !== $tag) {
                continue; // untagged information, not the command result
            }

            if (preg_match('/^OK\b/i', $response) === 1) {
                return true;
            }

            $this->last_error = $this->buildErrorMessage($response, $challenge);
            return false;
        }
    }

    /**
     * Sends an empty client response to a SASL challenge.
     */
    private function sendEmptyResponse(): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('cannot write - connection closed?');
        }

        try {
            fwrite($this->socket, "\r\n");
        } catch (FilesystemException $e) {
            throw new RuntimeException('cannot write - connection closed?', 0, $e);
        }
    }

    /**
     * Builds a readable error from the tagged server answer and the XOAUTH2
     * error challenge (`{"status":"401","schemes":"bearer","scope":"..."}`).
     */
    private function buildErrorMessage(string $response, ?string $challenge): string
    {
        $message = trim($response);

        if ($challenge !== null && $challenge !== '') {
            try {
                $payload = json_decode(base64_decode($challenge, true), true);
            } catch (Throwable) {
                $payload = null;
            }
            if (is_array($payload) && isset($payload['status'])) {
                $message .= sprintf(' (status: %s)', $payload['status']);
            }
        }

        return $message;
    }
}
