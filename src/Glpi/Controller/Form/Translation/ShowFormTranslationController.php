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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_id}/{language}", name: "glpi_show_form_translation", methods: "GET")]
    public function __invoke(Request $request, int $form_id, string $language): Response
    {
        // Validate the language code
        if (!isset(Dropdown::getLanguages()[$language])) {
            throw new BadRequestHttpException('Invalid language code');
        }

        // Retrieve the form from the database
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new BadRequestHttpException('Form not found');
        }

        return $this->displayTranslation($form, $language);
    }

    private function displayTranslation(Form $form, string $language): Response
    {
        // Retrieve the form translation for the specified language
        $formTranslation = FormTranslation::getTranslation($form, Form::TRANSLATION_KEY_NAME, $language);
        if ($formTranslation === null) {
            throw new BadRequestHttpException('Specified language did not exist for this form');
        }

        // Right check
        if (!$formTranslation->can($formTranslation->getID(), READ)) {
            throw new AccessDeniedHttpException();
        }

        return new StreamedResponse(function () use ($formTranslation) {
            FormTranslation::displayFullPageForItem($formTranslation->getID(), ['admin', Form::getType()], []);
        });
    }
}
