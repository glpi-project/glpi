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

namespace Glpi\Controller\Helpdesk;

use Glpi\Controller\AbstractController;
use Glpi\Helpdesk\HomePageTabs;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use User;
use Session;

final class IndexController extends AbstractController
{
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->tiles_manager = new TilesManager();
    }

    #[SecurityStrategy(Firewall::STRATEGY_HELPDESK_ACCESS)]
    #[Route(
        "/Helpdesk",
        name: "glpi_helpdesk_index",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        $user = User::getById(Session::getLoginUserID());

        // Will rename the file to "home.html.twig" later, don't want to remove
        // the original file yet.
        return $this->render('pages/helpdesk/index.html.twig', [
            'title' => __("Home"),
            'menu'  => ['helpdesk-home'],
            'tiles' => $this->tiles_manager->getTiles(),
            'tabs'  => new HomePageTabs(),
            'password_alert' => $user->getPasswordExpirationMessage(),
        ]);
    }
}
