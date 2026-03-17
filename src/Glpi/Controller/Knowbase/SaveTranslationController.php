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
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\RichText\RichText;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class SaveTranslationController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/KnowbaseItem/{knowbaseitems_id}/Translation",
        name: "knowbaseitem_translation_save",
        methods: ["POST"],
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = $request->attributes->getInt('knowbaseitems_id');

        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($id)) {
            throw new NotFoundHttpException();
        }

        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? null;
        $answer = $data['answer'] ?? null;
        $name = $data['name'] ?? null;

        if ($language === null || $answer === null) {
            return new JsonResponse([
                'success' => false,
                'message' => __('Missing required fields'),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Make sure answer content is not empty if specified
        if ($answer !== null) {
            $answer = strip_tags(trim($answer));
            if ($answer === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => __('Content cannot be empty'),
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        $answer = RichText::getSafeHtml($answer);

        // Make sure title is not empty if specified
        if ($name !== null) {
            $name = strip_tags(trim($name));
            if ($name === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => __('Title cannot be empty'),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Try to find a row for the given language
        $translation = new KnowbaseItemTranslation();
        $existing = $translation->find([
            'knowbaseitems_id' => $id,
            'language' => $language,
        ]);

        if (count($existing) > 0) {
            $existing_data = array_shift($existing);
            $update_data = [
                'id' => $existing_data['id'],
                'answer' => $answer,
            ];
            if ($name !== null) {
                $update_data['name'] = $name;
            }
            $this->update(
                KnowbaseItemTranslation::class,
                $existing_data['id'],
                $update_data,
            );
            $translation->update($update_data);
        } else {
            $input = [
                'knowbaseitems_id' => $id,
                'language' => $language,
                'answer' => $answer,
                'name' => $name ?? '',
                'users_id' => Session::getLoginUserID(),
            ];
            $this->add(KnowbaseItemTranslation::class, $input);
        }

        return new JsonResponse([
            'success' => true,
            'message' => __('Translation saved successfully'),
        ]);
    }
}
