<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Mail\SMTP\OauthProvider;

use League\OAuth2\Client\Token\AccessToken;

interface ProviderInterface
{
    public function __construct(array $options = []);

    /**
     * @return string
     * @see \League\OAuth2\Client\Provider\AbstractProvider::getAuthorizationUrl()
     */
    public function getAuthorizationUrl(array $options = []);

    /**
     * @return \League\OAuth2\Client\Token\AccessTokenInterface
     * @see \League\OAuth2\Client\Provider\AbstractProvider::getAccessToken()
     */
    public function getAccessToken($grant, array $options = []);

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     * @see \League\OAuth2\Client\Provider\AbstractProvider::getResourceOwner()
     */
    public function getResourceOwner(AccessToken $token);

    /**
     * Returns provider name.
     *
     * @return string
     */
    public static function getName(): string;

    /**
     * Returns additional parameters.
     * Result is an array of parameters, each one is an array having following values:
     *  - `key` (mandatory): key to use when passing the value on instance constructor options
     *  - `label` (mandatory): label to display on configuration form
     *  - `default` (optional): default value
     *  - `helper` (optional): text displayed in helper tooltip
     *
     * @return array
     */
    public static function getAdditionalParameters(): array;
}
