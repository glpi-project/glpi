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
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use KnowbaseItemCategory;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Persists whether a knowledge base aside category is collapsed (folded) for the
 * current user.
 */
final class AsideCategoryFoldController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Category/{id}/Fold",
        name: "knowbase_aside_category_fold",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'POST',
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(int $id, Request $request): Response
    {
        $collapsed = $request->getPayload()->get('collapsed');
        if ($collapsed === null) {
            throw new BadRequestHttpException();
        }

        if (!Session::isAuthenticated()) {
            // Nothing to be done as the user don't exist in the database, we
            // can't persist any data.
            return new Response();
        }

        KnowbaseItemCategory::setCategoryFoldedForCurrentUser(
            category_id: $id,
            folded: (bool) $collapsed
        );

        return new Response(); // OK
    }
}
