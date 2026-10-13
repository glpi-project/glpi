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

namespace Glpi\Mail\Imap;

use Glpi\Mail\Protocol\ProtocolInterface;
use Override;

/**
 * Native IMAP protocol, used to authenticate users against an IMAP server.
 */
class ImapProtocol implements ProtocolInterface
{
    private bool $validate_cert = true;

    private string $host = '';

    private ?int $port = null;

    private string|bool $ssl = false;

    #[Override]
    public function setNoValidateCert(bool $novalidatecert)
    {
        $this->validate_cert = !$novalidatecert;

        return $this;
    }

    #[Override]
    public function connect($host, $port = null, $ssl = false)
    {
        // Connection is established on login, as the IMAP engine connects and authenticates in a single step.
        $this->host = $host;
        $this->port = $port !== null ? (int) $port : null;
        $this->ssl  = $ssl;
    }

    #[Override]
    public function login($user, $password)
    {
        $mailbox = new Mailbox(Mailbox::buildConfig(
            [
                'address'       => $this->host,
                'port'          => $this->port,
                'ssl'           => $this->ssl === 'SSL',
                'tls'           => $this->ssl === 'TLS',
                'validate-cert' => $this->validate_cert,
            ],
            $user,
            $password
        ));

        // Will throw an exception if the connection or the authentication fails.
        $mailbox->connect();
        $mailbox->disconnect();

        return true;
    }
}
