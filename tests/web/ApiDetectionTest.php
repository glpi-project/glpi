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

namespace tests\units;

use Config;
use Glpi\Tests\FrontBaseClass;
use GuzzleHttp\Client as GuzzleClient;

class ApiDetectionTest extends FrontBaseClass
{
    public function setUp(): void
    {
        parent::setUp();

        Config::setConfigurationValues(
            'core',
            [
                'enable_api'                   => true,
                'enable_api_login_credentials' => true,
            ]
        );
    }

    /**
     * API endpoint must be detected as API and don't need a CSRF token.
     */
    public function testApiEndpointBypassesCsrfCheck()
    {
        $client   = new GuzzleClient();
        $response = $client->request(
            'GET',
            $this->base_uri . 'apirest.php/initSession',
            ['auth' => [TU_USER, TU_PASS]]
        );

        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('session_token', $data);
    }

    public function testFrontScriptWithApiLikePath()
    {
        $this->logIn();

        $this->http_client->request(
            'POST',
            $this->base_uri . 'front/central.php/apirest.php/',
            ['some_field' => 'some_value'],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        $response = $this->http_client->getResponse();
        $this->assertSame(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('The action you have requested is not allowed.', $data['message']);
    }
}
