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

namespace Glpi\Api\HL\Controller;

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\GraphQL;
use Glpi\Api\HL\GraphQLGenerator;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/GraphQL', priority: 1, tags: ['GraphQL'])]
final class GraphQLController extends AbstractController
{
    #[Route(path: '/', methods: ['POST'], security_level: Route::SECURITY_AUTHENTICATED)]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'GraphQL API')]
    public function index(Request $request): Response
    {
        return new JSONResponse(GraphQL::processRequest($request));
    }

    #[Route(path: '/Schema', methods: ['GET'], security_level: Route::SECURITY_AUTHENTICATED)]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'GraphQL API Schema')]
    public function getSchema(Request $request): Response
    {
        $graphql_generator = new GraphQLGenerator($this->getAPIVersion($request));
        return new Response(200, [], $graphql_generator->getSchema());
    }
}
