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

namespace Glpi\Mail\SMTP;

use Config;
use Glpi\Mail\SMTP\OauthProvider\Azure;
use Glpi\Mail\SMTP\OauthProvider\Google;
use Glpi\Mail\SMTP\OauthProvider\ProviderInterface;
use GLPIKey;
use League\OAuth2\Client\Provider\AbstractProvider;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

final class OauthConfig
{
    /**
     * Singleton instance.
     */
    private static ?OauthConfig $instance = null;

    /**
     * Singleton constructor. Keep it private to force usage of `getInstance()`.
     */
    private function __construct() {}

    /**
     * Get singleton instance.
     *
     * @return OauthConfig
     */
    public static function getInstance(): OauthConfig
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Return configured SMTP Oauth provider instance.
     */
    public function getSmtpOauthProvider(): (ProviderInterface&AbstractProvider)|null
    {
        $config = Config::getConfigurationValues(
            'core',
            [
                'url_base',
                'proxy_name',
                'proxy_user',
                'proxy_passwd',
                'proxy_port',
                'smtp_oauth_provider',
                'smtp_oauth_client_id',
                'smtp_oauth_client_secret',
                'smtp_oauth_options',
            ]
        );

        $provider_class = $config['smtp_oauth_provider'];

        if (
            !is_string($provider_class)
            || !is_a($provider_class, ProviderInterface::class, true)
            || !is_a($provider_class, AbstractProvider::class, true)
        ) {
            return null;
        }

        $client_id        = $config['smtp_oauth_client_id'];
        $client_secret    = (new GLPIKey())->decrypt($config['smtp_oauth_client_secret']);
        $provider_options = [];
        try {
            $provider_options = json_decode($config['smtp_oauth_options'], true);
        } catch (JsonException $e) {
            //no error
        }

        if ($config['proxy_name'] !== '') {
            // Connection using proxy
            $provider_options['proxy'] = $config['proxy_user'] !== ''
                ? sprintf(
                    '%s:%s@%s:%s',
                    rawurlencode($config['proxy_user']),
                    rawurlencode((new GLPIKey())->decrypt($config['proxy_passwd'])),
                    $config['proxy_name'],
                    $config['proxy_port']
                )
                : sprintf(
                    '%s:%s',
                    $config['proxy_name'],
                    $config['proxy_port']
                );
        }

        $provider = new $provider_class(
            [
                'clientId'     => $client_id,
                'clientSecret' => $client_secret,
                'redirectUri'  => $config['url_base'] . '/front/smtp_oauth2_callback.php',
            ] + $provider_options
        );

        return $provider;
    }

    /**
     * Return list of available oauth providers classnames.
     *
     * @return array
     */
    public function getSupportedProviders(): array
    {
        return [
            Azure::class,
            Google::class,
        ];
    }
}
