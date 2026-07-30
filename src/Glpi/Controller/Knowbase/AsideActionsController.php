<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller\Knowbase;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns the rendered kebab menu items (add to favorites, add to FAQ, delete)
 * for a single knowledge base article. Used by the aside tree, which renders
 * only the kebab trigger and lazy-loads the menu content on hover/open so it
 * never has to load every tree article up-front just to gate the actions.
 */
final class AsideActionsController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/AsideActions",
        name: "knowbase_aside_actions",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'GET',
    )]
    public function __invoke(int $id): Response
    {
        $item = new KnowbaseItem();
        if (!$item->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        if (!$item->can($id, READ)) {
            throw new AccessDeniedHttpException();
        }

        return $this->render('pages/tools/kb/aside_actions.html.twig', [
            'actions' => $item->getAsideActions(),
        ]);
    }
}
