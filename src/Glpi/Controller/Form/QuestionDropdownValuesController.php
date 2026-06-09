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

namespace Glpi\Controller\Form;

use Glpi\Controller\AbstractController;
use Glpi\Controller\Form\Utils\CanCheckAccessPolicies;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionTypeSelectable;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuestionDropdownValuesController extends AbstractController
{
    use CanCheckAccessPolicies;
    private const DEFAULT_PAGE_SIZE = 50;

    #[Route(
        "/Form/Question/DropdownValues",
        name: "glpi_form_question_dropdown_values",
        methods: "POST"
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        $question = $this->loadQuestion($request);

        $this->checkFormAccessPolicies($question->getForm(), $request);

        if (!$question->getQuestionType() instanceof QuestionTypeDropdown) {
            throw new BadRequestHttpException();
        }

        $search_text = $request->request->getString('searchText', '');
        $page        = max(1, $request->request->getInt('page', 1));
        $page_size   = max(1, $request->request->getInt('page_limit', self::DEFAULT_PAGE_SIZE));

        $question_type = new QuestionTypeDropdown();
        $options       = $question_type->getOptions($question);

        $results = [];
        foreach ($options as $uuid => $option) {
            $key   = sprintf('%s-%s', AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION, $uuid);
            $label = FormTranslation::translate($question, $key) ?? $option;

            if ($search_text !== '' && stripos($label, $search_text) === false) {
                continue;
            }

            $results[] = ['id' => $uuid, 'text' => $label];
        }

        $total        = count($results);
        $offset       = ($page - 1) * $page_size;
        $page_results = array_slice($results, $offset, $page_size);

        return new JsonResponse([
            'results'    => $page_results,
            'count'      => $total,
            'pagination' => ['more' => $offset + $page_size < $total],
        ]);
    }

    private function loadQuestion(Request $request): Question
    {
        $question_id = $request->request->getInt('question_id');
        if (!$question_id) {
            throw new BadRequestHttpException();
        }

        $question = Question::getById($question_id);
        if (!$question instanceof Question) {
            throw new NotFoundHttpException();
        }

        return $question;
    }


}
