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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\Route;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GLPITestCase;
use Psr\Http\Message\RequestInterface;

class Router extends GLPITestCase
{
    public function testMatch()
    {
        $router = TestRouter::getInstance();
        $this->variable($router->match(new Request('GET', '/test')))->isNotNull();
    }
}

// @codingStandardsIgnoreStart
class TestRouter extends \Glpi\Api\HL\Router
{
    // @codingStandardsIgnoreEnd
    public static function getInstance(): \Glpi\Api\HL\Router
    {
        static $router = null;
        if ($router === null) {
            $router = new static();
            $router->registerController(new TestController());
        }
        return $router;
    }
}

// @codingStandardsIgnoreStart
class TestController extends \Glpi\Api\HL\Controller\AbstractController
{
    // @codingStandardsIgnoreEnd
    /**
     * @param RequestInterface $request
     * @return Response
     */
    #[Route('/{req}', ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'], ['req' => '.*'], -1)]
    public function defaultRoute(RequestInterface $request): Response
    {
        return new Response(200, [], __FUNCTION__);
    }
}
