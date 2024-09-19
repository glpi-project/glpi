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

namespace Glpi\Controller\Form;

use Glpi\Controller\AbstractController;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FormListController extends AbstractController
{
    private ServiceCatalogManager $service_catalog_manager;

    public function __construct()
    {
        // TODO: replace by autowiring once dependency injection is fully implemented.
        $this->service_catalog_manager = new ServiceCatalogManager();
    }

    #[SecurityStrategy(Firewall::STRATEGY_HELPDESK_ACCESS)]
    #[Route(
        "/Forms",
        name: "glpi_form_list",
        methods: "GET",
    )]
    public function __invoke(Request $request): Response
    {
        $filter = $request->query->getString('filter');
        $parameters = new FormAccessParameters(
            session_info: Session::getCurrentSessionInfo(),
            url_parameters: $request->query->all()
        );
        $forms = $this->service_catalog_manager->getForms($parameters, $filter);

        return $this->render('components/helpdesk_forms/forms_list.html.twig', [
            'forms' => $forms,
        ]);
    }
}
