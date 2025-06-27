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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowAddTileFormController extends AbstractTileController
{
    #[Route(
        "/Config/Helpdesk/ShowAddTileForm",
        name: "glpi_config_helpdesk_show_add_tile_form",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        $possible_tiles = [];
        foreach ($this->tiles_manager->getTileTypes() as $tile_type) {
            if ($tile_type::canCreate()) {
                $possible_tiles[] = $tile_type;
            }
        }
        if ($possible_tiles === []) {
            throw new AccessDeniedHttpException();
        }

        $possible_tiles_dropdown_values = [];
        foreach ($possible_tiles as $possible_tile) {
            $possible_tiles_dropdown_values[$possible_tile::class] = $possible_tile->getLabel();
        }

        // Render form
        return $this->render('pages/admin/helpdesk_home_config_add_tile_form.html.twig', [
            'possible_tiles' => $possible_tiles,
            'possible_tiles_dropdown_values' => $possible_tiles_dropdown_values,
        ]);
    }
}
