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
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\EndUserInputNameProvider;
use Glpi\Form\Form;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class SubmitAnswerController extends AbstractController
{
    #[SecurityStrategy('no_check')] // Some forms can be accessed anonymously
    #[Route(
        "/Form/SubmitAnswers",
        name: "glpi_form_submit_answers",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        $form = $this->loadSubmittedForm($request);
        $this->checkFormAccessPolicies($form, $request);

        $answers = $this->saveSubmittedAnswers($form, $request);
        $links = $answers->getLinksToCreatedItems();

        return new JsonResponse([
            'links_to_created_items' => $links,
        ]);
    }

    private function loadSubmittedForm(Request $request): Form
    {
        $forms_id = $request->request->getInt("forms_id");
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
            url_parameters: $request->request->all(),
        );

        if (!$form_access_manager->canAnswerForm($form, $parameters)) {
            throw new AccessDeniedHttpException();
        }
    }

    private function saveSubmittedAnswers(
        Form $form,
        Request $request
    ): AnswersSet {
        $post = $request->request->all();
        $answers = (new EndUserInputNameProvider())->getAnswers($post);
        if (empty($answers)) {
            throw new BadRequestHttpException();
        }

        $handler = AnswersHandler::getInstance();
        return $handler->saveAnswers(
            $form,
            $answers,
            Session::getLoginUserID()
        );
    }
}
