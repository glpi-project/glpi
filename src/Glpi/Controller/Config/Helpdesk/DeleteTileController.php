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
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteTileController extends AbstractController
{
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->tiles_manager = new TilesManager();
    }

    #[Route(
        "/Config/Helpdesk/DeleteTile",
        name: "glpi_config_helpdesk_delete_tile",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        // Read parameters
        $tile_id = $request->request->getInt('tile_id');
        $tile_itemtype = $request->request->getString('tile_itemtype');

        // Validate parameters
        if (
            $tile_id == 0
            || !is_a($tile_itemtype, TileInterface::class, true)
            || !is_a($tile_itemtype, CommonDBTM::class, true)
        ) {
            throw new BadRequestHttpException();
        }
        if (!$tile_itemtype::canPurge()) {
            throw new AccessDeniedHttpException();
        }

        // Try to load the given tile
        $tile = $tile_itemtype::getById($tile_id);
        if (!$tile) {
            throw new NotFoundHttpException();
        }
        if (!$tile->canDeleteItem()) {
            throw new AccessDeniedHttpException();
        }

        // Delete tile and return an empty response
        $this->tiles_manager->deleteTile($tile);
        return new Response();
    }
}
