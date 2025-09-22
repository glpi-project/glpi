<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Altcha\AltchaManager;
use Glpi\Controller\AbstractController;
use Glpi\Controller\Form\Utils\CanCheckAccessPolicies;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\DelegationData;
use Glpi\Form\EndUserInputNameProvider;
use Glpi\Form\Form;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SubmitAnswerController extends AbstractController
{
    use CanCheckAccessPolicies;

    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)] // Some forms can be accessed anonymously
    #[Route(
        "/Form/SubmitAnswers",
        name: "glpi_form_submit_answers",
        methods: "POST"
    )]
    public function __invoke(Request $request): Response
    {
        $is_unauthenticated_user = !Session::isAuthenticated();
        if ($is_unauthenticated_user) {
            $altcha = $request->request->getString('altcha');
            if (!AltchaManager::getInstance()->verifySolution($altcha)) {
                throw new BadRequestHttpException();
            }
        }

        $form = $this->loadSubmittedForm($request);
        $this->checkFormAccessPolicies($form, $request);

        $answers = $this->saveSubmittedAnswers($form, $request);
        $links = $answers->getLinksToCreatedItems();

        if ($is_unauthenticated_user) {
            AltchaManager::getInstance()->removeChallenge($altcha);
        }

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
        if (!$form instanceof Form) {
            throw new NotFoundHttpException();
        }

        return $form;
    }

    private function saveSubmittedAnswers(
        Form $form,
        Request $request
    ): AnswersSet {
        $post = $request->request->all();
        $provider = new EndUserInputNameProvider();

        $delegation = new DelegationData(
            $request->request->getInt('delegation_users_id', 0) ?: null,
            $request->request->getBoolean('delegation_use_notification', false) ?: null,
            $request->request->getString('delegation_alternative_email', '') ?: null
        );
        $answers    = $provider->getAnswers($post);
        $files      = $provider->getFiles($post, $answers);
        if ($answers === []) {
            throw new BadRequestHttpException();
        }

        $handler = AnswersHandler::getInstance();

        // Check if answers are valid
        if (!$handler->validateAnswers($form, $answers)->isValid()) {
            throw new BadRequestHttpException();
        }

        $answers = $handler->removeUnusedAnswers($form, $answers);
        $answers = $handler->saveAnswers(
            $form,
            $answers,
            Session::getLoginUserID(),
            $files,
            $delegation
        );

        return $answers;
    }
}
