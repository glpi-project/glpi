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

namespace Glpi\Controller\Form\AllowListDropdown;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Form\AccessControl\ControlType\AllowListDropdown;
use Glpi\Form\Form;
use Group;
use Profile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use User;

final class CountUsersController extends AbstractController
{
    #[Route(
        path: "/Form/AllowListDropdown/CountUsers",
        name: "form_allow_list_dropdown_count_users",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        if (!Form::canView()) {
            throw new AccessDeniedHttpException();
        }

        $values = $request->query->all()['values'] ?? [];
        if (empty($values)) {
            // Empty $values mean no criteria has been defined in the dropdown.
            // No users should be found.
            $users = [-1];
            $groups = [-1];
            $profiles = [-1];

            // Do not display the link if there are no criteria, as the search
            // would be confusing with the '-1' criteria.
            $do_not_display_link = true;
        } else {
            $users = AllowListDropdown::getPostedIds($values, User::class);
            $groups = AllowListDropdown::getPostedIds($values, Group::class);
            $profiles = AllowListDropdown::getPostedIds($values, Profile::class);
            $do_not_display_link = false;
        }

        $data = AllowListDropdown::countUsersForCriteria(
            $users,
            $groups,
            $profiles
        );

        if ($do_not_display_link || !User::canView()) {
            unset($data['link']);
        }

        return new JsonResponse($data);
    }
}
