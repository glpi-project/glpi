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

namespace Glpi\Controller\ServiceCatalog;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ItemsController extends AbstractController
{
    private ServiceCatalogManager $service_catalog_manager;

    public function __construct()
    {
        // TODO: replace by autowiring once dependency injection is fully implemented.
        $this->service_catalog_manager = new ServiceCatalogManager();
    }

    #[SecurityStrategy(Firewall::STRATEGY_HELPDESK_ACCESS)]
    #[Route(
        "/ServiceCatalog/Items",
        name: "glpi_form_list",
        methods: "GET",
    )]
    public function __invoke(Request $request): Response
    {
        // Read category
        $category = null;
        $category_id = $request->query->getInt('category');
        if ($category_id > 0) {
            $category = Category::getById($category_id);
            if (!$category) {
                throw new NotFoundHttpException();
            }
        }

        // Read filter
        $filter = $request->query->getString('filter');

        // Build session + url params
        $parameters = new FormAccessParameters(
            session_info: Session::getCurrentSessionInfo(),
            url_parameters: $request->query->all()
        );

        // Get items from the service catalog
        $item_request = new ItemRequest(
            access_parameters: $parameters,
            filter: $filter,
            category: $category,
        );
        $items = $this->service_catalog_manager->getItems($item_request);

        return $this->render(
            'components/helpdesk_forms/service_catalog_items.html.twig',
            [
                'items' => $items,
                'is_default_search' => false,
            ]
        );
    }
}
