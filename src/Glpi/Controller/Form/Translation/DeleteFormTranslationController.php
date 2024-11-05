<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\Form\Translation\FormTranslation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_translation_id}/Delete", name: "glpi_delete_form_translation", methods: "POST")]
    public function __invoke(Request $request, int $form_translation_id): Response
    {
        // Right check
        if (!FormTranslation::canUpdate()) {
            throw new AccessDeniedHttpException();
        }

        $formTranslation = new FormTranslation();
        if (!$formTranslation->getFromDB($form_translation_id)) {
            throw new BadRequestHttpException("Form translation not found");
        }

        // Delete the form translation
        $formTranslation->delete([
            'id' => $form_translation_id,
            'purge' => true,
        ]);

        $form = $formTranslation->getItem();
        if ($form === false) {
            throw new BadRequestHttpException("Form not found");
        }

        return new RedirectResponse($form->getLinkURL());
    }
}
