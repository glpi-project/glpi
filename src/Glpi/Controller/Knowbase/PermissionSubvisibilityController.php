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
use Glpi\Exception\Http\BadRequestHttpException;
use Group;
use KnowbaseItem;
use Profile;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PermissionSubvisibilityController extends AbstractController
{
    #[Route(
        "/Knowbase/PermissionSubvisibility",
        name: "knowbase_permission_subvisibility",
        methods: 'POST',
    )]
    public function __invoke(Request $request): Response
    {
        // Get mandatory param
        $type = $request->request->getString('type');
        if (!in_array($type, [Group::class, Profile::class], true)) {
            throw new BadRequestHttpException();
        }

        // If the user can't update the KB, he should problably not be able
        // to see this dropdown at all.
        if (!KnowbaseItem::canUpdate()) {
            throw new AccessDeniedHttpException();
        }

        $rand = mt_rand();
        $entity_dropdown_options = [
            'value' => $_SESSION['glpiactive_entity'],
            'name'  => 'entities_id',
            'width' => '100%',
            'rand'  => $rand,
        ];

        if (Session::canViewAllEntities()) {
            $entity_dropdown_options['toadd'] = [-1 => __('No restriction')];
        }

        return $this->render('pages/tools/kb/permission_subvisibility.html.twig', [
            'rand'                    => $rand,
            'entity_dropdown_options' => $entity_dropdown_options,
        ]);
    }
}
