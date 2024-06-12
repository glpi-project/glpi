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

namespace tests\units\Glpi\Form\AnswersHandler;

use DbTestCase;
use Glpi\Form\Answer;
use Glpi\Form\Question;
use Glpi\Tests\FormBuilder;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormTesterTrait;
use User;

class AnswersHandler extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Data provider for testSaveAnswers
     *
     * @return iterable
     */
    protected function testSaveAnswersProvider(): iterable
    {
        $this->login();
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        // Fist form
        $builder = new FormBuilder("Form 1");
        $builder
            ->addQuestion("First name", QuestionTypeShortText::class)
            ->addQuestion("Last name", QuestionTypeShortText::class)
            ->addQuestion("Age", QuestionTypeNumber::class)
            ->addQuestion("Thoughts about GLPI", QuestionTypeLongText::class)
        ;
        $form_1 = $this->createForm($builder);

        $first_name_question = Question::getById(
            $this->getQuestionId($form_1, "First name")
        );
        $last_name_question = Question::getById(
            $this->getQuestionId($form_1, "Last name")
        );
        $age_question = Question::getById(
            $this->getQuestionId($form_1, "Age")
        );
        $thoughts_question = Question::getById(
            $this->getQuestionId($form_1, "Thoughts about GLPI")
        );

        // Submit first answer
        yield [
            'form'     => $form_1,
            'users_id' => $users_id,
            'answers'  => [
                $this->getQuestionId($form_1, "First name") => "John",
                $this->getQuestionId($form_1, "Last name") => "Doe",
                $this->getQuestionId($form_1, "Age") => 78,
                $this->getQuestionId($form_1, "Thoughts about GLPI") => "I love GLPI!!!"
            ],
            'expected_set' => [
                'forms_forms_id' => $form_1->getID(),
                'users_id'       => $users_id,
                'name'           => "Form 1 #1",
                'index'          => 1,
                'answers'        => json_encode([
                    new Answer($first_name_question, "John"),
                    new Answer($last_name_question, "Doe"),
                    new Answer($age_question, 78),
                    new Answer($thoughts_question, "I love GLPI!!!"),
                ]),
            ]
        ];

        // Submit second answer
        yield [
            'form'     => $form_1,
            'users_id' => $users_id,
            'answers'  => [
                $this->getQuestionId($form_1, "First name") => "John",
                $this->getQuestionId($form_1, "Last name") => "Smith",
                $this->getQuestionId($form_1, "Age") => 19,
                $this->getQuestionId($form_1, "Thoughts about GLPI") => "GLPI is incredible"
            ],
            'expected_set' => [
                'forms_forms_id' => $form_1->getID(),
                'users_id'       => $users_id,
                'name'           => "Form 1 #2", // Increased to #2
                'index'          => 2,           // Increased to #2
                'answers'        => json_encode([
                    new Answer($first_name_question, "John"),
                    new Answer($last_name_question, "Smith"),
                    new Answer($age_question, 19),
                    new Answer($thoughts_question, "GLPI is incredible"),
                ]),
            ]
        ];

        // Second form
        $builder = new FormBuilder("Form 2");
        $builder
            ->addQuestion("Contact email", QuestionTypeEmail::class)
        ;
        $form_2 = $this->createForm($builder);

        $contact_email_question = Question::getById(
            $this->getQuestionId($form_2, "Contact email")
        );

        yield [
            'form'     => $form_2,
            'users_id' => $users_id,
            'answers'  => [
                $this->getQuestionId($form_2, "Contact email") => "glpi@teclib.com",
            ],
            'expected_set' => [
                'forms_forms_id' => $form_2->getID(),
                'users_id'       => $users_id,
                'name'           => "Form 2 #1", // Back to #1 since this is a different form
                'index'          => 1,
                'answers'        => json_encode([
                    new Answer($contact_email_question, "glpi@teclib.com"),
                ]),
            ]
        ];
    }

    /**
     * Test the saveAnswers method
     *
     * @dataProvider testSaveAnswersProvider
     *
     * @param Form  $form         The form to save answers for
     * @param array $answers      The answers to save
     * @param int   $users_id     The user id
     * @param array $expected_set The expected answers set
     *
     * @return void
     */
    public function testSaveAnswers(
        Form $form,
        array $answers,
        int $users_id,
        array $expected_set
    ): void {
        $handler = \Glpi\Form\AnswersHandler\AnswersHandler::getInstance();
        $answer_set = $handler->saveAnswers($form, $answers, $users_id);

        foreach ($expected_set as $field => $value) {
            $this
                ->variable($value)
                ->isEqualTo($answer_set->fields[$field])
            ;
        }

        // The `createDestinations` part of the `saveAnswers` method is tested
        // by each possible destinations type in their own test file
    }
}
