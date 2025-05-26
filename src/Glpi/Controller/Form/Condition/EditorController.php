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

namespace Glpi\Controller\Form\Condition;

use Glpi\Controller\AbstractController;
use Glpi\Form\Condition\ConditionData;
use Glpi\Form\Condition\EditorManager;
use Glpi\Form\Condition\FormData;
use Glpi\Form\Condition\QuestionData;
use Glpi\Form\Condition\Type;
use Glpi\Form\Question;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditorController extends AbstractController
{
    public function __construct(
        private EditorManager $editor_manager,
    ) {}

    #[Route(
        "/Form/Condition/Visibility/Editor",
        name: "glpi_form_condition_visibility_editor",
        methods: "POST"
    )]
    public function visibilityEditor(Request $request): Response
    {
        $form_data = $request->request->all()['form_data'];
        $this->editor_manager->setFormData(new FormData($form_data));

        return $this->render('pages/admin/form/conditional_visibility_editor.html.twig', [
            'manager'            => $this->editor_manager,
            'defined_conditions' => $this->editor_manager->getDefinedConditions(),
            'items_values'       => $this->editor_manager->getItemsDropdownValues(),
        ]);
    }

    #[Route(
        "/Form/Condition/Validation/Editor",
        name: "glpi_form_condition_validation_editor",
        methods: "POST"
    )]
    public function validationEditor(Request $request): Response
    {
        $form_data = $request->request->all()['form_data'];
        $form_data = new FormData($form_data);
        $this->editor_manager->setFormData($form_data);

        // Check if the selected item is a question
        if ($form_data->getSelectedItemType() !== "question") {
            throw new InvalidArgumentException(
                sprintf(
                    'The selected item type "%s" is not supported for validation editor.',
                    $form_data->getSelectedItemType()
                )
            );
        }

        // Retrieve the question data
        $question_uuid = $form_data->getSelectedItemUuid();
        $question_name = current(array_filter(
            $form_data->getQuestionsData(),
            fn(QuestionData $question) => $question->getUuid() === $question_uuid
        ))->getName();

        // Retrieve the conditions data
        $conditions = $form_data->getConditionsData();
        $default_value_operator = current(array_keys($this->editor_manager->getValueOperatorForValidationDropdownValues(
            $question_uuid
        )));
        if (empty($conditions)) {
            $conditions[] = new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                value_operator: $default_value_operator,
                value: null,
            );
        }

        // If the last conditions is empty, we need to add a new one
        $last_index = count($conditions) - 1;
        if (empty($conditions[$last_index]->getItemUuid())) {
            $conditions[$last_index] = new ConditionData(
                item_uuid: $question_uuid,
                item_type: Type::QUESTION->value,
                value_operator: $default_value_operator,
                value: null,
            );
        }

        return $this->render('pages/admin/form/conditional_validation_editor.html.twig', [
            'question_uuid'      => $question_uuid,
            'question_name'      => $question_name,
            'manager'            => $this->editor_manager,
            'defined_conditions' => $conditions,
            'items_values'       => $this->editor_manager->getItemsDropdownValues(),
        ]);
    }
}
