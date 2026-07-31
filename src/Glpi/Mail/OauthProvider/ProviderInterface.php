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

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Contract shared by OAuth providers usable for both IMAP and SMTP
 * authorizations (as opposed to `Glpi\Mail\SMTP\OauthProvider\ProviderInterface`,
 * which only covers SMTP-sending usage).
 */
interface ProviderInterface
{
    /**
     * @return string
     */
    public function getAuthorizationUrl(array $options = []);

    /**
     * @param string $grant
     * @return AccessTokenInterface
     */
    public function getAccessToken($grant, array $options = []);

    /**
     * Returns the details (email, first/last name) of the resource owner
     * that granted the given access token.
     */
    public function getOwnerDetails(AccessToken $token): ?OwnerDetails;

    /**
     * Returns the default IMAP connection settings (host, port, ssl) for
     * this provider.
     *
     * @return array{host: string, port: int, ssl: string}
     */
    public static function getImapDefaults(): array;

    /**
     * Returns provider name.
     */
    public static function getName(): string;
}
