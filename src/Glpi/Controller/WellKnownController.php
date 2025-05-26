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

use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WellKnownController extends AbstractController
{
    /**
     *  Handle well-known URIs as defined in RFC 5785.
     *  https://www.iana.org/assignments/well-known-uris/well-known-uris.xhtml
     */
    #[Route(
        "/.well-known/change-password",
        name: "glpi_wellknown_change_password"
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function changePassword(Request $request): Response
    {
        return new RedirectResponse(
            $request->getBasePath() . '/front/updatepassword.php',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }
}
