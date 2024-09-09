<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Controller\Form;

use Glpi\Controller\AbstractController;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RendererController extends AbstractController
{
    #[SecurityStrategy('no_check')] // Some forms can be accessed anonymously
    #[Route(
        "/Form/Render/{id}",
        name: "glpi_form_render",
        methods: "GET",
        requirements: ['id' => '\d+'],
    )]
    public function __invoke(Request $request): Response
    {
        $form = $this->loadTargetForm($request);
        $this->checkFormAccessPolicies($form, $request);

        return $this->render('pages/form_renderer.html.twig', [
            'title' => $form->fields['name'],
            'menu' => ['admin', Form::getType()],
            'form' => $form,
            'unauthenticated_user' => !Session::isAuthenticated(),
        ]);
    }

    private function loadTargetForm(Request $request): Form
    {
        $forms_id = (int) $request->get("id");
        if (!$forms_id) {
            throw new BadRequestHttpException();
        }

        $form = Form::getById($forms_id);
        if (!$form) {
            throw new NotFoundHttpException();
        }

        return $form;
    }

    private function checkFormAccessPolicies(Form $form, Request $request)
    {
        $form_access_manager = FormAccessControlManager::getInstance();

        $parameters = new FormAccessParameters(
            session_info: Session::getCurrentSessionInfo(),
            url_parameters: $request->query->all(),
        );

        if (!$form_access_manager->canAnswerForm($form, $parameters)) {
            throw new AccessDeniedHttpException();
        }
    }
}
