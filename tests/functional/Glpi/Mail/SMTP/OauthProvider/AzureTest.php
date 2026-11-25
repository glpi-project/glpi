<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Mail\SMTP\OauthProvider;

use Glpi\Mail\SMTP\OauthProvider\Azure;
use Glpi\Tests\GLPITestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OAuthAuthorization;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;

use function Safe\json_encode;

class AzureTest extends GLPITestCase
{
    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function scopesProvider(): iterable
    {
        yield 'SMTP' => [
            ['type' => OAuthAuthorization::TYPE_SMTP],
            Azure::getSmtpDefaultScopes(),
        ];
        yield 'IMAP' => [
            ['type' => OAuthAuthorization::TYPE_IMAP],
            ['openid', 'email', 'offline_access', 'https://outlook.office.com/IMAP.AccessAsUser.All'],
        ];
        yield 'explicit scopes' => [
            ['scopes' => ['openid', 'https://outlook.office.com/SMTP.Send']],
            ['openid', 'https://outlook.office.com/SMTP.Send'],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string> $expected_scopes
     */
    #[DataProvider('scopesProvider')]
    public function testRefreshTokenRequestContainsScopes(array $options, array $expected_scopes): void
    {
        $token_request = null;
        $handler = new MockHandler([
            // OpenID configuration, used to resolve the token endpoint
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'token_endpoint' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            ])),
            // Token endpoint
            function (RequestInterface $request) use (&$token_request): Response {
                $token_request = $request;
                return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                    'access_token'  => 'access-token',
                    'refresh_token' => 'new-refresh-token',
                    'expires_in'    => 3600,
                ]));
            },
        ]);

        $provider = new Azure($options + [
            'clientId'     => 'client-id',
            'clientSecret' => 'client-secret',
            'tenant'       => 'common',
        ]);
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]));

        $provider->getAccessToken('refresh_token', ['refresh_token' => 'refresh-token']);

        $this->assertInstanceOf(RequestInterface::class, $token_request);
        parse_str((string) $token_request->getBody(), $params);
        $this->assertSame('refresh_token', $params['grant_type']);
        $this->assertSame(implode(' ', $expected_scopes), $params['scope'] ?? null);
    }
}
