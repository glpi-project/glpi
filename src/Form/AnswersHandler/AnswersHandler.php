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

namespace Glpi\Form\AnswersHandler;

use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeInterface;

/**
 * Helper class to handle raw answers data
 */
class AnswersHandler
{
    /**
     * Singleton instance
     * @var AnswersHandler|null
     */
    protected static ?AnswersHandler $instance = null;

    /**
     * Private constructor to prevent instantiation (singleton)
     */
    private function __construct()
    {
    }

    /**
     * Get the singleton instance
     *
     * @return AnswersHandler
     */
    public static function getInstance(): AnswersHandler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Saves the given answers of a given form into an AnswersSet object
     *
     * @param Form  $form     The form to save answers for
     * @param array $answers  The answers to save
     * @param int   $users_id The author of the answers
     *
     * @return AnswersSet|false The created AnswersSet object or false on failure
     */
    public function saveAnswers(
        Form $form,
        array $answers,
        int $users_id
    ): AnswersSet|false {
        /** @var \DBmysql $DB */
        global $DB;

        // Find next answer index for this form
        $next_index = 1;
        $rows = $DB->request([
            'SELECT' => ['MAX' => 'index AS current_index'],
            'FROM'   => AnswersSet::getTable(),
            'WHERE'  => [
                'forms_forms_id' => $form->getID(),
            ],
        ]);
        $next_index = $rows->current()['current_index'] + 1;

        // Load relevant questions data from the DB
        $questions = $form->getQuestions();

        $formatted_answers = [];
        foreach ($answers as $question_id => $answer) {
            // We need to keep track of some extra data like label and type because
            // the linked question might be deleted one day but the answer must still
            // be readable.
            $formatted_answers[] = [
                'question' => $question_id,
                'value'    => $answer,
                'label'    => $questions[$question_id]->fields['name'],
                'type'     => $questions[$question_id]->fields['type'],
            ];
        }

        $answers_set = new AnswersSet();
        $id = $answers_set->add([
            'name'           => $form->getName() . " #$next_index",
            'forms_forms_id' => $form->getID(),
            'answers'        => json_encode($formatted_answers),
            'users_id'       => $users_id,
            'index'          => $next_index,
        ]);

        return $id !== false ? $answers_set : false;
    }

    /**
     * Insert additionnal data into the given raw answers array to be used when rendering
     *
     * @param array $answers The raw answers array
     *
     * @return array The formatted answers array
     */
    public function prepareAnswersForDisplay(array $answers): array
    {
        $computed_answers = [];

        // Insert types objects which will be used to render the answers
        foreach ($answers as $answer) {
            $type = $answer['type'] ?? "";
            if (!is_a($answer['type'], QuestionTypeInterface::class, true)) {
                continue;
            }

            $answer['type'] = new $type();
            $computed_answers[] = $answer;
        }

        return $computed_answers;
    }
}
