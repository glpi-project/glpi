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

namespace Glpi\Form\AnswersHandler;

use Glpi\DBAL\QueryExpression;
use Glpi\Form\Answer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Condition\Engine;
use Glpi\Form\Condition\EngineInput;
use Glpi\Form\Destination\AnswersSet_FormDestinationItem;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Form;

/**
 * Helper class to handle raw answers data
 */
final class AnswersHandler
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
     * Saves the given answers of a given form into an AnswersSet object and
     * create destinations objects.
     *
     * @param Form  $form     The form to save answers for
     * @param array $answers  The answers to save
     * @param int   $users_id The author of the answers
     *
     * @return AnswersSet The created AnswersSet object
     *
     * @throws \Exception If the data can't be fully saved
     */
    public function saveAnswers(
        Form $form,
        array $answers,
        int $users_id,
        array $files = []
    ): AnswersSet {
        /** @var \DBmysql $DB */
        global $DB;

        if ($DB->inTransaction()) {
            return $this->doSaveAnswers($form, $answers, $users_id);
        } else {
            // We do not want to commit the answers unless everything was processed
            // correctly
            $DB->beginTransaction();

            try {
                $answers_set = $this->doSaveAnswers($form, $answers, $users_id, $files);
                $DB->commit();
                return $answers_set;
            } catch (\Throwable $e) {
                $DB->rollback();
                trigger_error(
                    "Failed to save answers: " . $e->getMessage(),
                    E_USER_WARNING
                );

                // Propagate the exception
                throw $e;
            }
        }
    }

    /**
     * Saves the given answers of a given form into an AnswersSet object and
     * create destinations objects.
     *
     * Exported outside the `saveAnswers` method to allow running it with or
     * without transactions
     *
     * @param Form  $form     The form to save answers for
     * @param array $answers  The answers to save
     * @param int   $users_id The author of the answers
     *
     * @return AnswersSet The created AnswersSet object
     *
     * @throws \Exception If the data can't be saved
     */
    protected function doSaveAnswers(
        Form $form,
        array $answers,
        int $users_id,
        array $files = []
    ): AnswersSet {
        // Save answers
        $answers_set = $this->createAnswserSet(
            $form,
            $answers,
            $users_id
        );
        $answers_set->setSubmittedFiles($files);

        // Create destinations objects
        $this->createDestinations(
            $form,
            $answers_set
        );

        // Increment the form usage counter
        $this->incrementFormUsageCount($form);

        return $answers_set;
    }

    /**
     * Increment the usage count of a form
     *
     * @param Form $form The form to increment the usage count for
     *
     * @return void
     */
    protected function incrementFormUsageCount(Form $form): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Directly increment the counter in the database to avoid race conditions
        $DB->update(
            Form::getTable(),
            ['usage_count' => new QueryExpression('usage_count + 1')],
            ['id' => $form->getID()]
        );
    }

    /**
     * Saves the given answers of a given form into an AnswersSet object and
     * create destinations objects.
     *
     * Exported outside the `saveAnswers` method to allow running it with or
     * without transactions
     *
     * @param Form  $form     The form to save answers for
     * @param array $answers  The answers to save
     * @param int   $users_id The author of the answers
     *
     * @return AnswersSet The created AnswersSet object
     *
     * @throws \Exception If the data can't be saved
     */
    protected function createAnswserSet(
        Form $form,
        array $answers,
        int $users_id
    ): AnswersSet {
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
            if (!isset($questions[$question_id])) {
                // Ignore unknown question
                trigger_error(
                    "Unknown question: $question_id",
                    E_USER_WARNING
                );
                continue;
            }

            // We need to keep track of some extra data like label and type because
            // the linked question might be deleted one day but the answer must still
            // be readable.
            $question = $questions[$question_id];
            $prepared_answer = $question->getQuestionType()
                ->prepareEndUserAnswer($question, $answer)
            ;
            $formatted_answers[] = new Answer($question, $prepared_answer);
        }

        // Save to database
        $answers_set = new AnswersSet();
        $input = [
            'name'           => $form->getName() . " #$next_index",
            'entities_id'    => $form->fields['entities_id'],
            'forms_forms_id' => $form->getID(),
            'answers'        => json_encode($formatted_answers),
            'users_id'       => $users_id,
            'index'          => $next_index,
        ];
        $id = $answers_set->add($input);

        // If we can't save the answers, throw an exception as it make no sense
        // to keep going
        if (!$id) {
            throw new \Exception(
                "Failed to save answers: " . json_encode($input)
            );
        }

        return $answers_set;
    }

    /**
     * Create destinations for a given form and its answers
     *
     * @param Form       $form
     * @param AnswersSet $answers_set
     *
     * @throws \Exception If the data can't be saved
     *
     * @return void
     */
    protected function createDestinations(Form $form, AnswersSet $answers_set): void
    {
        // Get defined destinations
        $destinations = $form->getDestinations();

        // Init the contionnal creation engine
        $engine = new Engine($form, new EngineInput($answers_set->toArray()));
        $engine_output = $engine->computeItemsThatMustBeCreated();

        /** @var FormDestination $destination */
        foreach ($destinations as $destination) {
            $concrete_destination = $destination->getConcreteDestinationItem();
            if (!$concrete_destination) {
                // The configured destination might belong to an inactive plugin
                continue;
            }

            // Skip if the destination failed its required conditions.
            if (!$engine_output->itemMustBeCreated($destination)) {
                continue;
            }


            // Create destination item
            $items = $concrete_destination->createDestinationItems(
                $form,
                $answers_set,
                $destination->getConfig(),
            );

            // Link items to answers by creating a AnswersSet_FormDestinationItem object
            foreach ($items as $item) {
                $form_item = new AnswersSet_FormDestinationItem();
                $input = [
                    AnswersSet::getForeignKeyField() => $answers_set->getID(),
                    'itemtype'                       => $item::getType(),
                    'items_id'                       => $item->getID(),
                ];
                if (!$form_item->add($input)) {
                    throw new \Exception(
                        "Failed to create destination item: "
                        . json_encode($input)
                    );
                }
            }
        }
    }
}
