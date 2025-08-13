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

namespace Glpi\OAuth;

use DateInterval;
use Glpi\Exception\OAuth2KeyException;
use Glpi\Http\Request;
use GLPIKey;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\OpensslException;
use Throwable;

use function Safe\chmod;
use function Safe\file_put_contents;
use function Safe\openssl_pkey_export_to_file;
use function Safe\openssl_pkey_get_details;
use function Safe\openssl_pkey_new;
use function Safe\unlink;

final class Server
{
    private const PRIVATE_KEY_PATH = GLPI_CONFIG_DIR . '/oauth.pem';
    private const PUBLIC_KEY_PATH  = GLPI_CONFIG_DIR . '/oauth.pub';

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
        //check for keys
        self::checkKeys();

        $this->client_repository = new ClientRepository();
        $this->access_token_repository = new AccessTokenRepository();
        $this->scope_repository = new ScopeRepository();

        $this->resource_server = new ResourceServer($this->access_token_repository, "file://" . self::PUBLIC_KEY_PATH);

        $encryption_key = (new GLPIKey())->get();
        $this->auth_server = new AuthorizationServer($this->client_repository, $this->access_token_repository, $this->scope_repository, "file://" . self::PRIVATE_KEY_PATH, $encryption_key);
        $this->auth_server->enableGrantType(
            new ClientCredentialsGrant(),
            new DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new PasswordGrant(
                new UserRepository(),
                new RefreshTokenRepository()
            ),
            new DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new AuthCodeGrant(
                new AuthCodeRepository(),
                new RefreshTokenRepository(),
                new DateInterval('PT10M')
            ),
            new DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
        );

        $this->auth_server->enableGrantType(
            new RefreshTokenGrant(new RefreshTokenRepository()),
            new DateInterval(self::GLPI_OAUTH_ACCESS_TOKEN_EXPIRES)
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
     * @phpstan-return array{client_id: string, user_id: string, scopes: string[]}
     * @throws OAuthServerException
     * @throws OAuth2KeyException
     */
    public static function validateAccessToken(Request $request): array
    {
        //check for keys
        self::checkKeys();

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
            'graphql' => 'graphql',
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
            'graphql' => __('Access to the GraphQL endpoint'),
        ];
    }

    public static function checkKeys(): bool
    {
        if (
            file_exists(self::PRIVATE_KEY_PATH)
            && file_exists(self::PUBLIC_KEY_PATH)
        ) {
            // Keys are already generated

            if (is_readable(self::PRIVATE_KEY_PATH) && is_readable(self::PUBLIC_KEY_PATH)) {
                return true;
            } else {
                throw new OAuth2KeyException('Either private or public OAuth keys cannot be read. Please check file system permissions');
            }
        }

        return false;
    }
    public static function generateKeys(): void
    {
        if (self::checkKeys()) {
            // Keys are already generated
            return;
        }

        // Partial data: unsure how to proceed, let the user review the files.
        if (
            file_exists(self::PRIVATE_KEY_PATH)
            && !file_exists(self::PUBLIC_KEY_PATH)
        ) {
            throw new RuntimeException("Mising file: " . self::PUBLIC_KEY_PATH);
        }
        if (
            file_exists(self::PUBLIC_KEY_PATH)
            && !file_exists(self::PRIVATE_KEY_PATH)
        ) {
            throw new RuntimeException("Mising file: " . self::PRIVATE_KEY_PATH);
        }

        // If we reach this point, both file are missing and must be generated
        try {
            // Generate keys
            self::doGenerateKeys();
        } catch (Throwable $e) {
            // Make sure we don't save any partially generated data
            self::deleteKeys();

            // Propagate exception
            throw $e;
        }
    }

    private static function doGenerateKeys(): void
    {
        $config = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Generate key
        try {
            $key = openssl_pkey_new($config);
        } catch (OpensslException $e) {
            throw new RuntimeException("Unable to generate keys: " . $e->getMessage(), $e->getCode(), $e);
        }

        // Export private key to file
        try {
            openssl_pkey_export_to_file($key, self::PRIVATE_KEY_PATH);
        } catch (OpensslException $e) {
            throw new RuntimeException("Unable to export private key: " . $e->getMessage(), $e->getCode(), $e);
        }

        // Get public key
        try {
            $pubkey = openssl_pkey_get_details($key);
        } catch (OpensslException $e) {
            $error = openssl_error_string();
            throw new RuntimeException("Unable to get public key details: $error", $e->getCode(), $e);
        }

        // Export public key to file
        try {
            $written_bytes = file_put_contents(self::PUBLIC_KEY_PATH, $pubkey['key']);
        } catch (FilesystemException $e) {
            throw new RuntimeException("Unable to export public key: " . $e->getMessage(), $e->getCode(), $e);
        }
        if ($written_bytes !== strlen($pubkey['key'])) {
            throw new RuntimeException('Unable to export public key');
        }

        // Set permissions to both key files
        try {
            chmod(self::PRIVATE_KEY_PATH, 0o660);
            chmod(self::PUBLIC_KEY_PATH, 0o660);
        } catch (FilesystemException $e) {
            throw new RuntimeException('Unable to set permissions on the generated keys', $e->getCode(), $e);
        }
    }

    private static function deleteKeys(): void
    {
        if (file_exists(self::PRIVATE_KEY_PATH)) {
            unlink(self::PRIVATE_KEY_PATH);
        }
        if (file_exists(self::PUBLIC_KEY_PATH)) {
            unlink(self::PUBLIC_KEY_PATH);
        }
    }
}
