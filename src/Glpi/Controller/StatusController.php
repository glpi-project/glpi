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

namespace Glpi\Controller;

use Glpi\Api\HL\Router;
use Glpi\Error\ErrorHandler;
use Glpi\Http\Firewall;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class StatusController extends AbstractController
{
    #[Route(
        "/status.php",
        name: "glpi_status"
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(): SymfonyResponse
    {
        // Force in normal mode
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        // Redirect handling to the High-Level API (we may eventually remove this script)
        $request = new Request('GET', '/Status/All', getallheaders());

        try {
            $response = Router::getInstance()->handleRequest($request);
        } catch (Throwable $e) {
            ErrorHandler::logCaughtException($e);
            $response = new JSONResponse(null, 500);
        }

        return new SymfonyResponse(
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}
