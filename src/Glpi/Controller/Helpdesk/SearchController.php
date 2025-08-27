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

namespace Glpi\Controller\Helpdesk;

use Glpi\Controller\AbstractController;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\ServiceCatalog\HomeSearchManager;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_HELPDESK_ACCESS)]
    #[Route(
        "/Helpdesk/Search",
        name: "glpi_helpdesk_search",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        // Read parameters
        $filter = $request->query->getString('filter');

        // Filter items
        $manager = HomeSearchManager::getInstance();
        $items_request = new ItemRequest(
            access_parameters: new FormAccessParameters(
                session_info: Session::getCurrentSessionInfo(),
            ),
            filter: $filter,
        );
        $items = $manager->getItems($items_request);

        return $this->render('pages/helpdesk/search.html.twig', [
            'items_by_label' => $items,
        ]);
    }
}
