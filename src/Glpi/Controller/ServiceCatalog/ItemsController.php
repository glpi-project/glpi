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

use Entity;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Category;
use Glpi\Form\ServiceCatalog\ItemRequest;
use Glpi\Form\ServiceCatalog\Provider\CategoryProvider;
use Glpi\Form\ServiceCatalog\ServiceCatalogManager;
use Glpi\Form\ServiceCatalog\SortStrategy\SortStrategyEnum;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use LogicException;
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
        $this->service_catalog_manager = ServiceCatalogManager::getInstance();
    }

    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        "/ServiceCatalog/Items",
        name: "glpi_form_list",
        methods: "GET",
    )]
    public function __invoke(Request $request): Response
    {
        // Read category
        $category_id = $request->query->get('category', 0);

        if (!is_numeric($category_id)) {
            // Invalid input
            throw new NotFoundHttpException();
        }

        if ($category_id > 0) {
            if (Category::getById($category_id) === false) {
                throw new NotFoundHttpException();
            }
        }

        // Read filter
        $filter = $request->query->getString('filter');

        // Read pagination params
        $page = max(1, $request->query->getInt('page', 1));
        $items_per_page = ServiceCatalogManager::ITEMS_PER_PAGE;

        // Read sort strategy
        $sort_strategy = $request->query->getEnum(
            'sort_strategy',
            SortStrategyEnum::class,
            SortStrategyEnum::getDefault()
        );

        // Build session + url params
        $session = Session::getCurrentSessionInfo();
        $parameters = new FormAccessParameters(
            session_info: $session,
            url_parameters: $request->query->all()
        );

        // Load entity
        $entity = Entity::getById($session->getCurrentEntityId());
        if (!$entity) {
            throw new LogicException();
        }

        // If we have a filter, we search in all categories
        if (!empty($filter)) {
            $category_id = null;
        }

        // Get items from the service catalog
        $item_request = new ItemRequest(
            access_parameters: $parameters,
            filter: $filter,
            category_id: $category_id,
            page: $page,
            items_per_page: $items_per_page,
            sort_strategy: $sort_strategy
        );
        $result = $this->service_catalog_manager->getItems($item_request);

        $category_provider = new CategoryProvider();
        $ancestors = $category_provider->getAncestors($item_request);

        return $this->render(
            'components/helpdesk_forms/service_catalog_items.html.twig',
            [
                'category_id'       => $category_id,
                'filter'            => $filter,
                'ancestors'         => $ancestors,
                'items'             => $result['items'],
                'total'             => $result['total'],
                'current_page'      => $page,
                'items_per_page'    => $items_per_page,
                'is_default_search' => false,
                'expand_categories' => $entity->shouldExpandCategoriesInServiceCatalog(),
            ]
        );
    }
}
