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

final class ShowEditTileFormController extends AbstractTileController
{
    #[Route(
        "/Config/Helpdesk/ShowEditTileForm",
        name: "glpi_config_helpdesk_show_edit_tile_form",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        // Validate itemtype
        $tile = $this->getAndValidateTileFromRequest(
            $request->query->getString('tile_itemtype'),
            $request->query->getInt('tile_id'),
        );
        if (!$tile::canUpdate() || !$tile->canUpdateItem()) {
            throw new AccessDeniedHttpException();
        }

        // Render form
        return $this->render('pages/admin/helpdesk_home_config_edit_tile_form.html.twig', [
            'tile' => $tile,
        ]);
    }
}
