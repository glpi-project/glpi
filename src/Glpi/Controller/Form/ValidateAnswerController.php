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

namespace Glpi\Controller\Form;

use Glpi\Controller\AbstractController;
use Glpi\Controller\Form\Utils\CanCheckAccessPolicies;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\EndUserInputNameProvider;
use Glpi\Form\Form;
use Glpi\Form\Section;
use Glpi\Form\ValidationResult;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ValidateAnswerController extends AbstractController
{
    use CanCheckAccessPolicies;

    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)] // Some forms can be accessed anonymously
    #[Route(
        "/Form/ValidateAnswers",
        name: "glpi_form_validate_answers",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        $form = $this->loadSubmittedForm($request);
        $section = $this->loadSubmittedSection($request);
        $this->checkFormAccessPolicies($form, $request);

        $questions_container = $section ?? $form;
        $validation_result = $this->checkSubmittedAnswersValidation($questions_container, $request);
        return new JsonResponse([
            'success' => $validation_result->isValid(),
            'errors' => $validation_result->getErrors(),
        ]);
    }

    private function loadSubmittedForm(Request $request): Form
    {
        $forms_id = $request->request->getInt("forms_id");
        if (!$forms_id) {
            throw new BadRequestHttpException();
        }

        $form = Form::getById($forms_id);
        if (!$form instanceof Form) {
            throw new NotFoundHttpException();
        }

        return $form;
    }

    private function loadSubmittedSection(Request $request): ?Section
    {
        $section_uuid = $request->request->getString("section_uuid");
        if (!$section_uuid) {
            return null;
        }

        $section = Section::getByUuid($section_uuid);
        if (!$section) {
            throw new NotFoundHttpException();
        }

        return $section;
    }

    private function checkSubmittedAnswersValidation(
        Form|Section $questions_container,
        Request $request
    ): ValidationResult {
        $post = $request->request->all();
        $provider = new EndUserInputNameProvider();

        $answers = $provider->getAnswers($post);
        if ($answers === []) {
            throw new BadRequestHttpException();
        }

        $handler = AnswersHandler::getInstance();
        return $handler->validateAnswers($questions_container, $answers);
    }
}
