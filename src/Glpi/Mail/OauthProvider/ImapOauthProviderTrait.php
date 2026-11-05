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

namespace Glpi\Mail\OauthProvider;

use League\OAuth2\Client\Token\AccessTokenInterface;
use OAuthAuthorization;

/**
 * Shared logic for OAuth providers usable for both IMAP and SMTP
 * authorizations.
 *
 * This is a trait, rather than a base class, because the SMTP provider
 * classes using it already extend third-party OAuth2 client library
 * classes (`TheNetworg\OAuth2\Client\Provider\Azure`,
 * `League\OAuth2\Client\Provider\Google`), and PHP does not support
 * multiple inheritance.
 *
 * Classes using this trait must implement `Glpi\Mail\OauthProvider\ProviderInterface`.
 */
trait ImapOauthProviderTrait
{
    /**
     * Returns the scopes to request for the given authorization type.
     *
     * @param string $type One of `OAuthAuthorization::TYPE_IMAP`/`TYPE_SMTP`.
     * @return list<string>
     */
    public function getScopesForType(string $type): array
    {
        return $type === OAuthAuthorization::TYPE_SMTP
            ? $this->getSmtpScopes()
            : $this->getImapScopes();
    }

    /**
     * @return array{host: string, port: int, ssl: string}
     */
    abstract public static function getImapDefaults(): array;

    abstract public function getOwnerDetails(AccessTokenInterface $token): ?OwnerDetails;

    /**
     * Scopes required to authorize IMAP access.
     *
     * @return list<string>
     */
    abstract protected function getImapScopes(): array;

    /**
     * Scopes required to authorize SMTP access.
     *
     * @return list<string>
     */
    abstract protected function getSmtpScopes(): array;
}
