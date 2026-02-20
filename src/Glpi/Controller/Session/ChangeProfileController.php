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

namespace Glpi\Controller\Session;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use Html;
use Session;
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
        global $CFG_GLPI;

        // Validate profile
        $profile_id = $request->request->getInt('id');
        if (!isset($_SESSION["glpiprofiles"][$profile_id])) {
            throw new AccessDeniedHttpException();
        }

        // Apply new profile
        Session::changeProfile($profile_id);

        // If the profile change was made with an AJAX request, this mean this
        // was some background script and we do not need to redirect it to
        // another page.
        if ($request->isXmlHttpRequest()) {
            return new Response();
        }

        // Compute redirection URL
        if (Session::getCurrentInterface() == "helpdesk") {
            $go_to_create_ticket = $_SESSION['glpiactiveprofile']['create_ticket_on_login'];
            $route = $go_to_create_ticket ? "/ServiceCatalog" : "/Helpdesk";
            $redirect = $request->getBasePath() . $route;
        } else {
            $back_url = Html::getBackUrl();

            // Check if the back URL points to a specific item page (e.g. ticket.form.php?id=123).
            // After a profile change, the user's active entities are reset and they may no longer
            // have access to the item they were previously viewing, which would cause an
            // AccessDeniedHttpException and a silent redirect to the homepage (see #23187).
            // To avoid this confusing behavior, redirect directly to the central page
            // when the back URL references a specific item.
            $back_query = parse_url($back_url, PHP_URL_QUERY);
            $back_params = [];
            if ($back_query !== null) {
                parse_str($back_query, $back_params);
            }
            $points_to_specific_item = isset($back_params['id']) && (int) $back_params['id'] > 0;

            if ($points_to_specific_item) {
                $redirect = sprintf('%s/front/central.php', $request->getBasePath());
            } else {
                $separator = str_contains($back_url, '?') ? "&" : "?";
                $redirect = $back_url . $separator . '_redirected_from_profile_selector=true';
            }
        }

        return new RedirectResponse($redirect);
    }
}
