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

namespace Glpi\Controller\Session;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Html;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChangeProfileController extends AbstractController
{
    #[Route(
        "/Session/ChangeProfile",
        name: "glpi_change_profile",
        methods: "POST",
    )]
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    public function __invoke(Request $request): Response
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Validate profile
        $profile_id = $request->request->getInt('id');
        if (!isset($_SESSION["glpiprofiles"][$profile_id])) {
            throw new AccessDeniedHttpException();
        }

        // Apply new profile
        Session::changeProfile($profile_id);

        // Compute redirection URL
        if (Session::getCurrentInterface() == "helpdesk") {
            $go_to_create_ticket = $_SESSION['glpiactiveprofile']['create_ticket_on_login'];
            $route = $go_to_create_ticket ? "/ServiceCatalog" : "/Helpdesk";
            $redirect = $request->getBasePath() . $route;
        } else {
            $_SESSION['_redirected_from_profile_selector'] = true;
            $redirect = Html::getBackUrl();
        }

        return new RedirectResponse($redirect);
    }
}
