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
use Glpi\Controller\CrudControllerTrait;
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GetTranslationContentController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/KnowbaseItem/{knowbaseitems_id}/Translation/{language}",
        name: "knowbaseitem_translation_content",
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = $request->attributes->getInt('knowbaseitems_id');
        $language = $request->attributes->getString('language');

        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $translation = new KnowbaseItemTranslation();
        $exists = $translation->getFromDBByCrit([
            'knowbaseitems_id' => $id,
            'language' => $language,
        ]);
        if ($exists) {
            $translation = $this->read(KnowbaseItemTranslation::class, $translation->getID());
            return new JsonResponse([
                'exists' => true,
                'name' => $translation->fields['name'],
                'answer' => $translation->fields['answer'],
            ]);
        }

        return new JsonResponse([
            'exists' => false,
            'name' => '',
            'answer' => '',
        ]);
    }
}
