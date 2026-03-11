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

namespace Glpi\Api\HL\Controller;

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\GraphQL;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use GraphQL\Utils\SchemaPrinter;

#[Route(path: '/GraphQL', priority: 1, tags: ['GraphQL'])]
final class GraphQLController extends AbstractController
{
    #[Route(path: '/', methods: ['POST'], security_level: Route::SECURITY_AUTHENTICATED, scopes: ['graphql'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'GraphQL API')]
    public function index(Request $request): Response
    {
        $result = GraphQL::processRequest($request);
        if ($result === []) {
            return new JSONResponse([]);
        }
        $response_data = $result['result']->toArray();
        $headers = [];
        if (isset($result['context']->pagination)) {
            // Backwards compatibility handling, but this was never correct. GraphQL requests may contain multiple queries, and each query may have its own pagination.
            if (count($result['context']->pagination) === 1) {
                $pagination = reset($result['context']->pagination);
                $headers['Content-Range'] = sprintf(
                    '%d-%d/%d',
                    $pagination['start'],
                    max($pagination['start'] + $pagination['limit'] - 1, 0),
                    $pagination['total_count']
                );
            }
            // More correct handling of paginations is adding a "pagination" field to the "extensions" part of the response, which is designed for this kind of metadata.
            $response_data['extensions']['pagination'] = $result['context']->pagination;
        }
        return new JSONResponse($response_data, 200, $headers);
    }

    #[Route(path: '/Schema', methods: ['GET'], security_level: Route::SECURITY_AUTHENTICATED, scopes: ['graphql'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'GraphQL API Schema')]
    public function getSchema(Request $request): Response
    {
        $schema_generator = new GraphQL\SchemaGenerator($this->getAPIVersion($request));
        return new Response(200, [], SchemaPrinter::doPrint($schema_generator->getSchema()));
    }
}
