<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

final class Azure extends \TheNetworg\OAuth2\Client\Provider\Azure implements ProviderInterface
{
    public function __construct(array $options = [])
    {
        $options['scopes'] = $this->getScopes();
        $options['defaultEndPointVersion'] = self::ENDPOINT_VERSION_2_0;

        parent::__construct($options);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $options = [
            'prompt' => 'login', // ensure user will have to specify the account to use
            'scope'  => $this->getScopes(),
        ];

        return parent::getAuthorizationUrl($options);
    }

    public static function getName(): string
    {
        return 'Azure';
    }

    public static function getAdditionalParameters(): array
    {
        return [
            [
                'key'     => 'tenant',
                'label'   => _x('oauth', 'Tenant ID'),
                'default' => 'common',
                'helper'  => _x('oauth', 'Use "common" if your application is shared by multiple tenants.'),
            ],
        ];
    }

    private function getScopes(): array
    {
        return [
            'openid', // required
            'email', // required to be able to fetch login
            'offline_access',
            'https://outlook.office.com/SMTP.Send',
        ];
    }
}
