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
use KnowbaseItemTranslation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteTranslationController extends AbstractController
{
    #[Route(
        "/Knowbase/KnowbaseItem/{knowbaseitems_id}/Translation/{language}/Delete",
        name: "knowbaseitem_translation_delete",
        methods: ["POST"],
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (int) $request->get('knowbaseitems_id');
        $language = $request->get('language');

        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($id)) {
            throw new NotFoundHttpException();
        }
        if (!$kbitem->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $translation = new KnowbaseItemTranslation();
        $found = $translation->find([
            'knowbaseitems_id' => $id,
            'language' => $language,
        ]);

        if (count($found) === 0) {
            return new JsonResponse([
                'success' => false,
                'message' => __('Translation not found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $existing_data = array_shift($found);
        $success = $translation->delete(['id' => $existing_data['id']]);

        if ($success) {
            return new JsonResponse([
                'success' => true,
                'message' => __('Translation deleted successfully'),
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => __('Failed to delete the translation'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
