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

namespace Glpi\OAuth;

use Glpi\Http\Request;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

final class Server
{
    /**
     * @var ClientRepository
     */
    private $client_repository;

    /**
     * @var AccessTokenRepository
     */
    private $access_token_repository;

    /**
     * @var ScopeRepository
     */
    private $scope_repository;

    /**
     * @var AuthorizationServer
     */
    private $auth_server;

    /**
     * @var ResourceServer
     */
    private $resource_server;

    /**
     * Number of bytes used in the identifier and secret (32 bytes = 256 bit).
     */
    public const ID_SECRET_LENGTH_BYTES = 32;

    public const AUTH_CODE_LENGTH_BYTES = 32;

    public const GLPI_OAUTH_ACCESS_TOKEN_EXPIRES = 'PT1H';

    public function __construct()
    {
        $this->client_repository = new ClientRepository();
        $this->access_token_repository = new AccessTokenRepository();
        $this->scope_repository = new ScopeRepository();

        $public_key_path = GLPI_CONFIG_DIR . '/oauth.pub';
        $this->resource_server = new ResourceServer($this->access_token_repository, "file://$public_key_path");

        $private_key_path = GLPI_CONFIG_DIR . '/oauth.pem';
        $encryption_key = (new \GLPIKey())->get();
        $this->auth_server = new AuthorizationServer($this->client_repository, $this->access_token_repository, $this->scope_repository, "file://$private_key_path", $encryption_key);
        $this->auth_server->enableGrantType(
            new ClientCredentialsGrant(),
            new \DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new PasswordGrant(
                new UserRepository(),
                new RefreshTokenRepository()
            ),
            new \DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new AuthCodeGrant(
                new AuthCodeRepository(),
                new RefreshTokenRepository(),
                new \DateInterval('PT10M')
            ),
            new \DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new RefreshTokenGrant(new RefreshTokenRepository()),
            new \DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );
    }

    private static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public static function getAuthorizationServer(): AuthorizationServer
    {
        return (self::getInstance())->auth_server;
    }

    /**
     * @param Request $request
     * @return array
     * @phpstan-return {client_id: string, user_id: string, scopes: string[]}
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     */
    public static function validateAccessToken(Request $request): array
    {
        $new_request = self::getInstance()->resource_server->validateAuthenticatedRequest($request);
        return [
            'client_id' => $new_request->getAttribute('oauth_client_id'),
            'user_id' => $new_request->getAttribute('oauth_user_id'),
            'scopes' => $new_request->getAttribute('oauth_scopes'),
        ];
    }

    /**
     * Get all scopes available for clients.
     * @return array
     */
    public static function getAllowedScopes(): array
    {
        return [
            'email' => 'email',
            'user' => 'user',
            'api' => 'api',
            'inventory' => 'inventory',
            'status' => 'status',
        ];
    }

    public static function getScopeDescriptions(): array
    {
        return [
            'email' => __('Access to the user\'s email address'),
            'user' => __('Access to the user\'s information'),
            'api' => __('Access to the API'),
            'inventory' => __('Access to submit inventory from an agent'),
            'status' => __('Access to the status endpoint'),
        ];
    }

    public static function generateKeys(): void
    {
        $private_key_path = GLPI_CONFIG_DIR . '/oauth.pem';
        $public_key_path = GLPI_CONFIG_DIR . '/oauth.pub';
        if (!file_exists($private_key_path) && !file_exists($public_key_path)) {
            $config = [
                'digest_alg'       => 'sha512',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $success = false;
            $error = null;
            $res = openssl_pkey_new($config);
            if ($res && openssl_pkey_export_to_file($res, $private_key_path)) {
                // Export public key to the public key file
                $pubkey = openssl_pkey_get_details($res);
                if ($pubkey !== false && file_put_contents($public_key_path, $pubkey['key']) === strlen($pubkey['key'])) {
                    if (chmod($private_key_path, 0660) && chmod($public_key_path, 0660)) {
                        $success = true;
                    } else {
                        $error = 'Unable to set permissions on the generated keys';
                    }
                } else {
                    $error = 'Unable to export public key';
                }
            } else {
                $error = 'Unable to generate keys';
            }

            if (!$success) {
                // Key files didn't exist before and an error occured. We should try removing any that were created to be able to retry later
                if (file_exists($private_key_path)) {
                    unlink($private_key_path);
                }
                if (file_exists($public_key_path)) {
                    unlink($public_key_path);
                }
                throw new \RuntimeException($error);
            }
        }
    }
}
