<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Controller;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Router;
use Glpi\Application\ErrorHandler;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ApiController implements Controller
{
    #[Route(
        "/api.php{request_parameters}",
        name: "glpi_api",
        requirements: [
            'request_parameters' => '.*',
        ]
    )]
    #[SecurityStrategy('no_check')]
    public function __invoke(SymfonyRequest $request): SymfonyResponse
    {
        $_SERVER['PATH_INFO'] = $request->get('request_parameters');

        return new StreamedResponse($this->call(...));
    }

    private function call(): void
    {
        /**
         * High-level API entrypoint
         */

        // Ensure errors will not break API output.
        ErrorHandler::getInstance()->disableOutput();

        $method = $_SERVER['REQUEST_METHOD'];
        $relative_uri = $_SERVER['PATH_INFO'] ?? '';
        // Ensure uri starts with slash but does not end with a slash
        $relative_uri = '/' . trim($relative_uri, '/');

        // If the relative URI starts with /v1/ or is /v1 then we are dealing with a legacy API request
        if (preg_match('/^\/v1(\/|$)/', $relative_uri)) {
            // Include the legacy API entrypoint and then die
            $api = new \Glpi\Api\APIRest();
            $api->call();
            die();
        }

        $supported_versions = Router::getAPIVersions();
        if (preg_match('/^\/v\d+(\/|$)/', $relative_uri)) {
            // A specific API version has been requested
            //TODO Plan handling endpoints with specific versions
            // For now, just remove the version prefix from the URI
            $relative_uri = preg_replace('/^\/v\d+(\/|$)/', '/', $relative_uri);
        }

        $body = file_get_contents('php://input') ?? null;

        $headers = getallheaders() ?? [];
        $request = new Request($method, $relative_uri, $headers, $body);

        $router = Router::getInstance();

        try {
            $response = $router->handleRequest($request);
            $response->send();
        } catch (\InvalidArgumentException $e) {
            $response = new JSONResponse(
                AbstractController::getErrorResponseBody(
                    AbstractController::ERROR_INVALID_PARAMETER,
                    $e->getMessage()
                ),
                400
            );
        } catch (\Throwable $e) {
            ErrorHandler::getInstance()->handleException($e);
            $response = new Response(500);
            $response->send();
        }
    }
}
