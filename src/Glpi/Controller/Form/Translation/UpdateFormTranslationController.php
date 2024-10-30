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

namespace Glpi\Controller\Form\Translation;

use Dropdown;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_id}/{language}", name: "glpi_update_form_translation", methods: "POST")]
    public function __invoke(Request $request, int $form_id, string $language): Response
    {
        // Validate the language code
        if (Dropdown::getLanguages()[$language] === null) {
            throw new BadRequestHttpException('Invalid language code');
        }

        // Retrieve the form from the database
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new BadRequestHttpException('Form not found');
        }

        $input = $request->request->all();
        if ($this->processTranslations($input['translations'] ?? [], $language)) {
            $formTranslation = current(array_filter(
                FormTranslation::getTranslationsForItem($form),
                fn($translation) => $translation->fields['language'] === $language
            ));
            Session::addMessageAfterRedirect(
                $formTranslation->formatSessionMessageAfterAction(__('Item successfully updated'))
            );
        }

        return new RedirectResponse($request->getBasePath() . '/Form/Translation/' . $form_id . '/' . $language);
    }

    private function processTranslations(array $translations, string $language): bool
    {
        $success = false;
        foreach ($translations as $translation) {
            $itemtype = $translation['itemtype'];
            $items_id = $translation['items_id'];
            $item = $itemtype::getById($items_id);

            $translation_input = ['language' => $language] + $translation;

            $formTranslation = FormTranslation::getTranslation($item, $translation['key'], $language);
            if ($formTranslation !== null) {
                $success = $this->updateTranslation($formTranslation, $translation_input);
            } else {
                $success = $this->createTranslation($translation_input) !== false;
            }
        }

        return $success;
    }

    private function updateTranslation(FormTranslation $formTranslation, array $translation_input): bool
    {
        $translation_input['id'] = $formTranslation->getID();

        if (!$formTranslation->can($formTranslation->getID(), UPDATE, $translation_input)) {
            throw new AccessDeniedHttpException();
        }

        return $formTranslation->update($translation_input);
    }

    private function createTranslation(array $translation_input): false|int
    {
        $formTranslation = new FormTranslation();

        if (!$formTranslation->can(-1, CREATE, $translation_input)) {
            throw new AccessDeniedHttpException();
        }

        return $formTranslation->add($translation_input);
    }
}
