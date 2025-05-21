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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Helpdesk\Tile\TileInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddTileController extends AbstractTileController
{
    #[Route(
        "/Config/Helpdesk/AddTile",
        name: "glpi_config_helpdesk_add_tile",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        // Validate linked item
        $linked_item = $this->getAndValidateLinkedItemFromRequest(
            linked_itemtype: $request->request->getString('_itemtype_item'),
            linked_items_id: $request->request->getInt('_items_id_item')
        );

        // Validate tile type
        $tile_itemtype = $request->request->getString('_itemtype_tile');
        $tile_item = getItemForItemtype($tile_itemtype);
        if (
            !$tile_item instanceof TileInterface
            || !$tile_item instanceof CommonDBTM
        ) {
            throw new BadRequestHttpException();
        }
        if (!$tile_itemtype::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        // Prepare main input
        $input = $request->request->all();
        unset($input['_itemtype_tile']);
        unset($input['_itemtype_item']);
        unset($input['_items_id_item']);

        // Add tile
        $this->tiles_manager->addTile($linked_item, $tile_itemtype, $input);

        // Re-render the tile list
        $tiles = $this->tiles_manager->getTilesForItem($linked_item);
        return $this->render('pages/admin/helpdesk_home_config_tiles.html.twig', [
            'tiles_manager' => $this->tiles_manager,
            'tiles' => $tiles,
            // If we reach this point, the item was editable so we must keep
            // displaying the controls.
            'editable' => true,
        ]);
    }
}
