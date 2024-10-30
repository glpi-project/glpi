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

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dropdown;
use Session;

final class DeleteFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_id}/{language}/Delete", name: "glpi_delete_form_translation", methods: "POST")]
    public function __invoke(Request $request, int $form_id, string $language): Response
    {
        // Validate the language code
        if (Dropdown::getLanguages()[$language] === null) {
            throw new BadRequestHttpException('Invalid language code');
        }

        // Retrieve the form from the database
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new BadRequestHttpException("Form not found");
        }

        $this->processDeletions($form, $language);

        return new RedirectResponse($form->getLinkURL());
    }

    private function processDeletions(Form $form, string $language): void
    {
        $formTranslation = new FormTranslation();
        $handlers_with_sections = $form->listTranslationsHandlers($form);
        foreach ($handlers_with_sections as $handlers) {
            foreach ($handlers as $handler) {
                $input = [
                    'itemtype' => $handler->getParentItem()->getType(),
                    'items_id' => $handler->getParentItem()->getID(),
                    'key'      => $handler->getKey(),
                    'language' => $language,
                ];

                if ($formTranslation->getFromDBByCrit($input)) {
                    $input['id'] = $formTranslation->getID();

                    // Right check
                    if (!$formTranslation->can($formTranslation->getID(), PURGE, $input)) {
                        throw new AccessDeniedHttpException();
                    }

                    // Delete the form translation
                    $formTranslation->delete($input + ['purge' => true]);
                }
            }
        }
    }
}
