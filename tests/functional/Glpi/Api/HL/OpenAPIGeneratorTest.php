<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\OpenAPIGenerator;
use Glpi\Api\HL\Router;
use HLAPITestCase;

class OpenAPIGeneratorTest extends HLAPITestCase
{
    public function testExpandedEndpoints()
    {
        $this->login();
        // Some expanded paths to spot-check
        $to_check = [
            '/Assistance/Ticket',
            '/Assistance/Change',
            '/Assistance/Problem',
            '/Assistance/Ticket/{id}',
            '/Assets/Computer',
            '/Assets/Computer/{id}',
            '/Assets/Monitor/{id}',
        ];
        $generator = new OpenAPIGenerator(Router::getInstance(), Router::API_VERSION);
        $openapi = $generator->getSchema();

        foreach ($to_check as $path) {
            $this->assertArrayHasKey($path, $openapi['paths']);
        }

        // Check that the pre-expanded paths are not present
        $to_check = [
            '/Assistance/{itemtype}',
            '/Assistance/{itemtype}/{id}',
            '/Assets/{itemtype}',
        ];
        foreach ($to_check as $path) {
            $this->assertArrayNotHasKey($path, $openapi['paths']);
        }
    }

    /**
     * Endpoints that get expanded (for example /Assistance/{itemtype} where 'itemtype' is known to be Ticket, Change or Problem)
     * should not list the 'itemtype' parameter in the documentation.
     */
    public function testExpandedAttributesNoParameter()
    {
        $this->login();
        // Some expanded paths to spot-check
        $to_check = [
            ['path' => '/Assistance/Ticket', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Change', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Problem', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Ticket/{id}', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Change/{id}', 'placeholder' => 'itemtype'],
            ['path' => '/Assistance/Problem/{id}', 'placeholder' => 'itemtype'],
        ];

        $generator = new OpenAPIGenerator(Router::getInstance(), Router::API_VERSION);
        $openapi = $generator->getSchema();

        foreach ($to_check as $endpoint) {
            $this->assertEmpty(array_filter($openapi['paths'][$endpoint['path']]['get']['parameters'], static fn($v) => $v['name'] === $endpoint['placeholder']));
        }
    }
}
