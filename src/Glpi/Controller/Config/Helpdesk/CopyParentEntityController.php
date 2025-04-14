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

use Entity;
use Glpi\Http\RedirectResponse;
use Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CopyParentEntityController extends AbstractTileController
{
    #[Route(
        "/Config/Helpdesk/CopyParentEntity",
        name: "glpi_config_helpdesk_copy_parent_entity",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        // Validate itemtype
        $entity = $this->getAndValidateLinkedItemFromRequest(
            Entity::class,
            $request->request->getInt('entities_id'),
        );
        $this->tiles_manager->copyTilesFromParentEntity($entity);

        return new RedirectResponse(Html::getBackUrl());
    }
}
