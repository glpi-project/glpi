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

namespace Glpi\Controller\Config\Helpdesk;

use CommonDBTM;
use Config;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Helpdesk\Tile\TileInterface;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Session\SessionInfo;
use Profile;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddTileController extends AbstractController
{
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->tiles_manager = new TilesManager();
    }

    #[Route(
        "/Config/Helpdesk/AddTile",
        name: "glpi_config_helpdesk_add_tile",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        // Read parameters
        $profile_id = $request->request->getInt('_profile_id');
        $itemtype = $request->request->getString('_itemtype');
        $input = $request->request->all();
        unset($input['_profile_id']);
        unset($input['_itemtype']);

        // Validate parameters
        if (
            !is_a($itemtype, TileInterface::class, true)
            || !is_a($itemtype, CommonDBTM::class, true)
        ) {
            throw new BadRequestHttpException();
        }
        if (!$itemtype::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        $profile = Profile::getById($profile_id);
        if (!$profile) {
            throw new NotFoundHttpException();
        }

        $this->tiles_manager->addTile($profile, $itemtype, $input);

        // Re-render the tile list
        $tiles = $this->tiles_manager->getTiles(new SessionInfo(
            profile_id: $profile_id,
        ), check_availability: false);
        return $this->render('pages/admin/helpdesk_home_config_tiles.html.twig', [
            'tiles_manager' => $this->tiles_manager,
            'tiles' => $tiles,
        ]);
    }
}
