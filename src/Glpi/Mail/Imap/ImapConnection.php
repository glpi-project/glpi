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

use DirectoryTree\ImapEngine\Connection\ImapConnection as BaseImapConnection;
use Override;

/**
 * IMAP connection that honors the certificate validation setting with STARTTLS.
 *
 * The base implementation only sets the SSL context options for implicit TLS (`ssl`/`tls` transports).
 * With STARTTLS, the stream is opened in plain TCP and upgraded later, so the SSL context options
 * must be set in advance for the `validate_cert` setting to be taken into account.
 */
class ImapConnection extends BaseImapConnection
{
    /**
     * @param array<string, mixed> $proxy
     *
     * @return array<string, array<string, mixed>>
     */
    #[Override]
    protected function getDefaultSocketOptions(string $transport, array $proxy = [], bool $validateCert = true): array
    {
        $options = parent::getDefaultSocketOptions($transport, $proxy, $validateCert);

        if ($transport === 'starttls') {
            $options['ssl'] = [
                'verify_peer'      => $validateCert,
                'verify_peer_name' => $validateCert,
            ];
        }

        return $options;
    }
}
