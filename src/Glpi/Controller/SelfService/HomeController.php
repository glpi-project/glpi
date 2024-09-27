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

namespace Glpi\Controller\SelfService;

use Glpi\Controller\AbstractController;
use Glpi\Form\SelfService\TilesManager;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->tiles_manager = new TilesManager();
    }

    #[SecurityStrategy(Firewall::STRATEGY_HELPDESK_ACCESS)]
    #[Route(
        "/Home",
        name: "glpi_selfservice_home",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        // Compute tabs, will be handled by a service in later iterations (with a proper Tab object).
        $tabs = [
            [
                'id'    => 'open-tickets',
                'label' => __("Ongoing tickets"),
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 12,
                        'searchtype' => 'equals',
                        'value'      => 'notold',
                    ]
                ]
            ],
            [
                'id'    => 'solved-tickets',
                'label' => __("Solved tickets"),
                'criteria' => [
                    [
                        'link'       => 'AND',
                        'field'      => 12,
                        'searchtype' => 'equals',
                        'value'      => 'old',
                    ]
                ]
            ],
        ];

        // Will rename the file to "home.html.twig" later, don't want to remove
        // the original file yet.
        return $this->render('pages/self-service/new_home.html.twig', [
            'title' => __("Home"),
            'menu'  => ['helpdesk-home'],
            'tiles' => $this->tiles_manager->getTiles(),
            'tabs'  => $tabs,
        ]);
    }
}
