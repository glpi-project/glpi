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

use DirectoryTree\ImapEngine\Connection\ConnectionInterface;
use DirectoryTree\ImapEngine\Connection\Streams\ImapStream;
use DirectoryTree\ImapEngine\Mailbox as BaseMailbox;
use Override;

/**
 * IMAP mailbox used by the native GLPI IMAP protocol.
 */
class Mailbox extends BaseMailbox
{
    #[Override]
    public function connect(?ConnectionInterface $connection = null): void
    {
        // Use the GLPI connection by default, including on reconnection.
        parent::connect($connection ?? new ImapConnection(new ImapStream()));
    }

    /**
     * Build the mailbox configuration from a parsed GLPI mail server connection string.
     *
     * @param array{address: string, port: int|string|null, ssl: bool, tls: bool|string, validate-cert: bool|string} $server_config
     *      Connection string specs, as returned by {@link \Toolbox::parseMailServerConnectString()}.
     * @param string $username
     * @param string $password
     *
     * @return array<string, mixed>
     */
    public static function buildConfig(array $server_config, string $username, string $password): array
    {
        $encryption = null;
        if ($server_config['ssl']) {
            $encryption = 'ssl';
        }
        if ($server_config['tls'] === true) {
            $encryption = 'starttls';
        }

        return [
            'host'          => $server_config['address'],
            'port'          => !empty($server_config['port'])
                ? (int) $server_config['port']
                : ($encryption === 'ssl' ? 993 : 143),
            'username'      => $username,
            'password'      => $password,
            'encryption'    => $encryption,
            'validate_cert' => $server_config['validate-cert'] !== false,
        ];
    }
}
