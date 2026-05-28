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
use KnowbaseItemCategory;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateCategoryFromAsideController extends AbstractController
{
    #[Route(
        "/Knowbase/Aside/Category/CreateForm",
        name: "knowbase_aside_category_create_form",
        methods: 'GET',
    )]
    public function form(Request $request): Response
    {
        if (!KnowbaseItemCategory::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        $parent_id = $request->query->getInt('parent');
        $parent_name = '';

        if ($parent_id > 0) {
            $parent = new KnowbaseItemCategory();
            if (!$parent->can($parent_id, READ)) {
                throw new BadRequestHttpException();
            }
            $parent_name = (string) $parent->fields['name'];
        }

        return $this->render('pages/tools/kb/modal/aside_create_category.html.twig', [
            'parent_id'   => $parent_id,
            'parent_name' => $parent_name,
        ]);
    }

    #[Route(
        "/Knowbase/Aside/Category",
        name: "knowbase_aside_category_create",
        methods: 'POST',
    )]
    public function create(Request $request): JsonResponse
    {
        if (!KnowbaseItemCategory::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        $name = trim($request->request->getString('name'));
        if ($name === '') {
            return new JsonResponse([
                'errors' => ['name' => __('Title is mandatory')],
            ], 422);
        }

        $parent_id = $request->request->getInt('knowbaseitemcategories_id');
        $entities_id = (int) Session::getActiveEntity();

        if ($parent_id > 0) {
            $parent = new KnowbaseItemCategory();
            if (!$parent->can($parent_id, READ)) {
                throw new BadRequestHttpException();
            }
            $entities_id = (int) $parent->fields['entities_id'];
        }

        $input = [
            'name'                      => $name,
            'knowbaseitemcategories_id' => $parent_id,
            'entities_id'               => $entities_id,
        ];

        $category = new KnowbaseItemCategory();
        if (!$category->can(-1, CREATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        $new_id = $category->add($input);
        if ($new_id === false) {
            return new JsonResponse([
                'errors' => $this->collectSessionErrors(),
            ], 422);
        }

        return new JsonResponse([
            'id'        => (int) $new_id,
            'name'      => $name,
            'parent_id' => $parent_id,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function collectSessionErrors(): array
    {
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);

        return $messages === [] ? ['_global' => __('Unable to create the category.')] : ['_global' => implode("\n", $messages)];
    }
}
