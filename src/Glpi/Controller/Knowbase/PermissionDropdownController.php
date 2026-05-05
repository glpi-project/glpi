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

use Entity;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Group;
use KnowbaseItem;
use Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use User;

final class PermissionDropdownController extends AbstractController
{
    #[Route(
        "/Knowbase/PermissionDropdown",
        name: "knowbase_permission_dropdown",
        methods: 'POST',
    )]
    public function __invoke(Request $request): Response
    {
        // Get mandatory params
        $type  = $request->request->getString('type');
        $right = $request->request->getString('right');
        if (empty($type) || empty($right)) {
            throw new BadRequestHttpException();
        }

        // Validate submitted type
        $valid_types = [User::class, Group::class, Entity::class, Profile::class];
        if (!in_array($type, $valid_types, true)) {
            throw new BadRequestHttpException();
        }

        // If the user can't update the KB, he should problably not be able
        // to see this dropdown at all.
        if (!KnowbaseItem::canUpdate()) {
            throw new AccessDeniedHttpException();
        }

        // Compute dropdown parameters
        $dropdown_options = match ($type) {
            User::class => [
                'right'      => 'all',
                'name'       => 'users_id',
                'width'      => '100%',
                'aria_label' => User::getTypeName(1),
            ],
            Group::class => [
                'name'       => 'groups_id',
                'width'      => '100%',
                'aria_label' => Group::getTypeName(1),
            ],
            Entity::class => [
                'value'       => $_SESSION['glpiactive_entity'],
                'name'        => 'entities_id',
                'entity'      => $request->request->get('entity', -1),
                'entity_sons' => $request->request->getBoolean('is_recursive'),
                'width'       => '100%',
                'aria_label'  => Entity::getTypeName(1),
            ],
            Profile::class => [
                'name'      => 'profiles_id',
                'width'     => '100%',
                'condition' => [
                    'glpi_profilerights.name' => 'knowbase',
                    'glpi_profilerights.rights' => [
                        '&',
                        $right === 'faq'
                            ? KnowbaseItem::READFAQ
                            : (READ | CREATE | UPDATE | PURGE),
                    ],
                ],
                'aria_label' => Profile::getTypeName(1),
            ],
        };

        return $this->render('pages/tools/kb/permission_dropdown.html.twig', [
            'type'             => $type,
            'dropdown_options' => $dropdown_options,
        ]);
    }
}
