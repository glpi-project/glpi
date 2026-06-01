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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Knowbase\Aside\Category as AsideCategory;
use KnowbaseItem;
use KnowbaseItemCategory;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateCategoryFromAsideController extends AbstractController
{
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
        // A root category follows the current entity view; a sub-category inherits
        // the parent's scope so it stays visible wherever the parent is.
        $entities_id = (int) Session::getActiveEntity();
        $is_recursive = Session::getIsActiveEntityRecursive() ? 1 : 0;

        if ($parent_id > 0) {
            $parent = new KnowbaseItemCategory();
            if (!$parent->can($parent_id, READ)) {
                throw new BadRequestHttpException();
            }
            $entities_id = (int) $parent->fields['entities_id'];
            $is_recursive = (int) $parent->fields['is_recursive'];
        }

        $input = [
            'name'                      => $name,
            'knowbaseitemcategories_id' => $parent_id,
            'entities_id'               => $entities_id,
            'is_recursive'              => $is_recursive,
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
            'html'      => $this->renderNode((int) $new_id, $name, (string) ($category->fields['illustration'] ?? '')),
        ]);
    }

    private function renderNode(int $id, string $name, string $illustration): string
    {
        $node = new AsideCategory(
            title: $name,
            illustration: $illustration !== '' ? $illustration : 'kb-faq',
            id: $id,
        );

        return TemplateRenderer::getInstance()->render(
            'pages/tools/kb/_category_node.html.twig',
            [
                'category'            => $node,
                'can_create'          => KnowbaseItem::canCreate(),
                'can_create_category' => KnowbaseItemCategory::canCreate(),
                'can_update_category' => KnowbaseItemCategory::canUpdate(),
            ],
        );
    }

    #[Route(
        "/Knowbase/Aside/Category/{id}/EditForm",
        name: "knowbase_aside_category_edit_form",
        requirements: ['id' => '\d+'],
        methods: 'GET',
    )]
    public function editForm(int $id): Response
    {
        $category = new KnowbaseItemCategory();
        if (!$category->getFromDB($id)) {
            throw new BadRequestHttpException();
        }
        if (!$category->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        return $this->render('pages/tools/kb/category_edit_form.html.twig', [
            'id'           => $id,
            'illustration' => ((string) ($category->fields['illustration'] ?? '')) ?: 'kb-faq',
            'comment'      => (string) ($category->fields['comment'] ?? ''),
        ]);
    }

    #[Route(
        "/Knowbase/Aside/Category/{id}",
        name: "knowbase_aside_category_update",
        requirements: ['id' => '\d+'],
        methods: 'POST',
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $category = new KnowbaseItemCategory();
        if (!$category->getFromDB($id)) {
            throw new BadRequestHttpException();
        }
        if (!$category->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $input = ['id' => $id];
        if ($request->request->has('illustration')) {
            $input['illustration'] = $request->request->getString('illustration');
        }
        if ($request->request->has('comment')) {
            $input['comment'] = $request->request->getString('comment');
        }

        if ($category->update($input) === false) {
            return new JsonResponse([
                'errors' => $this->collectSessionErrors(),
            ], 422);
        }

        return new JsonResponse([
            'id'           => $id,
            'illustration' => (string) $category->fields['illustration'],
            'comment'      => (string) $category->fields['comment'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function collectSessionErrors(): array
    {
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        unset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);

        return $messages === [] ? ['_global' => __('Unable to save the category.')] : ['_global' => implode("\n", $messages)];
    }
}
