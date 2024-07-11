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

use Session;
use Glpi\Api\HL\Router;
use Glpi\Application\ErrorHandler;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class StatusController extends AbstractController
{
    #[Route(
        "/status.php",
        name: "glpi_status"
    )]
    #[SecurityStrategy('no_check')]
    public function __invoke(SymfonyRequest $request): SymfonyResponse
    {
        return new StreamedResponse($this->call(...));
    }

    private function call(): void
    {
        // Force in normal mode
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
        // Redirect handling to the High-Level API (we may eventually remove this script)
        $request = new Request('GET', '/Status/All', getallheaders() ?? []);

        try {
            $response = Router::getInstance()->handleRequest($request);
            $response->send();
        } catch (\Throwable $e) {
            ErrorHandler::getInstance()->handleException($e, true);
            $response = new Response(500);
            $response->send();
        }
    }
}
