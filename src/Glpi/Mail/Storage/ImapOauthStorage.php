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

namespace Glpi\Mail\Storage;

use Glpi\Mail\Protocol\ImapOauthProtocol;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;
use Laminas\Mail\Storage\Imap;
use Laminas\Mail\Storage\ParamsNormalizer;
use OAuthAuthorization;

/**
 * IMAP storage of a mailbox authorized on an `OAuthApplication`.
 *
 * It can be built either:
 *  - from connection params (`application_id`, `user`, `host`, `port`, `ssl`,
 *    `folder`, `novalidatecert`): the access token of the mailbox is resolved
 *    from its `OAuthAuthorization` (and refreshed when expired), then a new
 *    connection is opened;
 *  - from an `ImapOauthProtocol` that is already connected and authenticated,
 *    in order to share its connection (e.g. a diagnostic that already went
 *    through the handshake).
 */
class ImapOauthStorage extends Imap
{
    /**
     * @param array<string, mixed>|object|ImapOauthProtocol $params
     */
    public function __construct($params)
    {
        if ($params instanceof ImapOauthProtocol) {
            // Shared connection: the parent selects the INBOX on it.
            parent::__construct($params);
            return;
        }

        $params = ParamsNormalizer::normalizeParams($params);

        if (empty($params['application_id'])) {
            throw new InvalidArgumentException('OAuth application must be defined');
        }
        if (empty($params['user'])) {
            throw new InvalidArgumentException('need at least user in params');
        }

        $application_id = (int) $params['application_id'];
        $user           = (string) $params['user'];

        $token = $this->resolveAccessToken($application_id, $user);

        $this->has['flags'] = true;

        $protocol = $this->createProtocol($application_id);
        if (array_key_exists('novalidatecert', $params)) {
            $protocol->setNoValidateCert((bool) $params['novalidatecert']);
        }
        $this->protocol = $protocol;

        $ssl = $params['ssl'] ?? false;
        $protocol->connect(
            (string) ($params['host'] ?? 'localhost'),
            isset($params['port']) && $params['port'] !== '' ? (int) $params['port'] : null,
            is_string($ssl) ? $ssl : (bool) $ssl
        );
        if (!$protocol->login($user, $token)) {
            throw new RuntimeException(sprintf(
                'cannot authenticate with OAuth access token of %s%s',
                $user,
                $protocol->getLastError() !== null ? ': ' . $protocol->getLastError() : ''
            ));
        }

        $this->selectFolder((string) ($params['folder'] ?? 'INBOX'));
    }

    /**
     * Returns a usable access token for the mailbox, refreshing it when it is expired.
     *
     * @throws RuntimeException when the mailbox is not authorized or when no usable token can be obtained
     */
    protected function resolveAccessToken(int $application_id, string $user): string
    {
        $authorization = $this->createAuthorization();
        if (!$authorization->getFromDBForApplicationAndEmail($application_id, $user, OAuthAuthorization::TYPE_IMAP)) {
            throw new RuntimeException(sprintf('mailbox %s is not authorized on the OAuth application', $user));
        }

        $token = $authorization->getValidAccessToken();
        if ($token === null) {
            throw new RuntimeException(sprintf(
                'unable to get an access token for %s: %s',
                $user,
                $authorization->getLastError() ?? 'unknown error'
            ));
        }

        return $token->getToken();
    }

    /**
     * Isolated in its own (protected) method so tests can provide an
     * authorization working with a fake OAuth provider.
     */
    protected function createAuthorization(): OAuthAuthorization
    {
        return new OAuthAuthorization();
    }

    /**
     * Isolated in its own (protected) method so tests can provide a protocol
     * talking to a fake IMAP server.
     */
    protected function createProtocol(int $application_id): ImapOauthProtocol
    {
        return new ImapOauthProtocol($application_id);
    }
}
