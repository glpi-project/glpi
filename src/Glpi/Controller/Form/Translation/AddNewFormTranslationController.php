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
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddNewFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_id}/Add", name: "glpi_add_form_translation", methods: "POST")]
    public function __invoke(Request $request, int $form_id): Response
    {
        // Retrieve the form from the database
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new NotFoundHttpException('Form not found');
        }

        // Retrieve the language code from the request
        $language = $request->request->get('language');

        // Validate the language code
        if (!\array_key_exists($language, Dropdown::getLanguages())) {
            throw new BadRequestHttpException('Invalid language code');
        }

        $this->createTranslation($form, $language);

        // Redirect with a URL parameter to indicate the modal should be opened
        $redirect_url = $form->getFormURLWithID($form_id) . "&open_translation=$language";
        return new RedirectResponse($redirect_url);
    }

    private function createTranslation(Form $form, string $language): void
    {
        $form_translation = new FormTranslation();
        $handlers_with_sections = $form->listTranslationsHandlers();
        $first_handler = current(current($handlers_with_sections));

        $input = [
            FormTranslation::$itemtype => Form::class,
            FormTranslation::$items_id => $form->getID(),
            'language'                 => $language,
            'key'                      => $first_handler->getKey(),
            'translations'             => '{}',
        ];

        // Right check
        if (!$form_translation->can(-1, CREATE, $input)) {
            throw new AccessDeniedHttpException();
        }

        $form_translation->add($input);
    }
}
