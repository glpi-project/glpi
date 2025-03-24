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

namespace tests\units\Glpi\Form\AnswersHandler;

use CommonITILObject;
use DbTestCase;
use Glpi\Form\Answer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Question;
use Glpi\Tests\FormBuilder;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

class AnswersHandlerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testSaveAnswers(): void
    {
        self::login();
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        // Fist form
        $builder = new FormBuilder("Form 1");
        $builder
            ->addQuestion("First name", QuestionTypeShortText::class)
            ->addQuestion("Last name", QuestionTypeShortText::class)
            ->addQuestion("Age", QuestionTypeNumber::class)
            ->addQuestion("Thoughts about GLPI", QuestionTypeLongText::class)
        ;
        $form_1 = self::createForm($builder);

        $first_name_question = Question::getById(
            self::getQuestionId($form_1, "First name")
        );
        $last_name_question = Question::getById(
            self::getQuestionId($form_1, "Last name")
        );
        $age_question = Question::getById(
            self::getQuestionId($form_1, "Age")
        );
        $thoughts_question = Question::getById(
            self::getQuestionId($form_1, "Thoughts about GLPI")
        );

        // Submit first answer
        $this->validateAnswers(
            form: $form_1,
            users_id: $users_id,
            answers: [
                self::getQuestionId($form_1, "First name") => "John",
                self::getQuestionId($form_1, "Last name") => "Doe",
                self::getQuestionId($form_1, "Age") => 78,
                self::getQuestionId($form_1, "Thoughts about GLPI") => "I love GLPI!!!"
            ],
            expected_set: [
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
        );

        // Submit second answer
        $this->validateAnswers(
            form: $form_1,
            users_id: $users_id,
            answers: [
                self::getQuestionId($form_1, "First name") => "John",
                self::getQuestionId($form_1, "Last name") => "Smith",
                self::getQuestionId($form_1, "Age") => 19,
                self::getQuestionId($form_1, "Thoughts about GLPI") => "GLPI is incredible"
            ],
            expected_set: [
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
        );

        // Second form
        $builder = new FormBuilder("Form 2");
        $builder
            ->addQuestion("Contact email", QuestionTypeEmail::class)
        ;
        $form_2 = self::createForm($builder);

        $contact_email_question = Question::getById(
            self::getQuestionId($form_2, "Contact email")
        );

        $this->validateAnswers(
            form: $form_2,
            users_id: $users_id,
            answers: [
                self::getQuestionId($form_2, "Contact email") => "glpi@teclib.com",
            ],
            expected_set: [
                'forms_forms_id' => $form_2->getID(),
                'users_id'       => $users_id,
                'name'           => "Form 2 #1", // Back to #1 since this is a different form
                'index'          => 1,
                'answers'        => json_encode([
                    new Answer($contact_email_question, "glpi@teclib.com"),
                ]),
            ]
        );
    }

    private function validateAnswers(
        Form $form,
        array $answers,
        int $users_id,
        array $expected_set
    ): void {
        $form->getById($form->getID()); // Reload form
        $handler = AnswersHandler::getInstance();
        $answer_set = $handler->saveAnswers($form, $answers, $users_id);

        foreach ($expected_set as $field => $expected_value) {
            $this->assertEquals($expected_value, $answer_set->fields[$field]);
        }

        // The `createDestinations` part of the `saveAnswers` method is tested
        // by each possible destinations type in their own test file
    }

    public function testDestinationItemsAreLinkedToForm(): void
    {
        // Arrange: create a form with its default mandatory destination
        $builder = new FormBuilder("My test form");
        $builder->addQuestion("Name", QuestionTypeShortText::class);
        $builder->addDestination(FormDestinationTicket::class, "Second ticket");
        $builder->addDestination(FormDestinationChange::class, "First change");
        $builder->addDestination(FormDestinationProblem::class, "First problem");
        $form = $this->createForm($builder);

        // Act: submit an answer for this form
        $answers = $this->sendFormAndGetAnswerSet($form, [
            'Name' => 'My test answer',
        ]);
        $created_items = $answers->getCreatedItems();

        // Assert: the created ticket should be linked to the form
        $this->assertCount(4, $created_items);

        foreach ($created_items as $item) {
            $this->assertInstanceOf(CommonITILObject::class, $item);

            $linked_items = $item->getLinkedItems();
            $this->assertCount(1, $linked_items);
            $this->assertArrayHasKey(Form::class, $linked_items);

            $linked_forms_ids = $linked_items[Form::class];
            $this->assertCount(1, $linked_forms_ids);

            $linked_forms_id = current($linked_forms_ids);
            $this->assertEquals($form->getID(), $linked_forms_id);
        }
    }

    public function testDestinationWithTrueConditionsAreNotCreated(): void
    {
        // Arrange: create a form with an invalid condition
        $builder = new FormBuilder("My test form");
        $builder->addQuestion("Name", QuestionTypeShortText::class);
        $builder->addDestination(FormDestinationTicket::class, "Second ticket");
        $builder->setDestinationCondition(
            "Second ticket",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Name",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "GLPI",
                ],
            ],
        );

        // Act: submit an answer for this form
        $answers = $this->sendFormAndGetAnswerSet($this->createForm($builder), [
            'Name' => 'GLPI',
        ]);
        $created_items = $answers->getCreatedItems();

        // Assert: the ticket should have been created
        $this->assertCount(2, $created_items);
    }

    public function testDestinationWithFalseConditionsAreNotCreated(): void
    {
        // Arrange: create a form with an invalid condition
        $builder = new FormBuilder("My test form");
        $builder->addQuestion("Name", QuestionTypeShortText::class);
        $builder->addDestination(FormDestinationTicket::class, "Second ticket");
        $builder->setDestinationCondition(
            "Second ticket",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Name",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "GLPI",
                ],
            ],
        );

        // Act: submit an answer for this form
        $answers = $this->sendFormAndGetAnswerSet($this->createForm($builder), [
            'Name' => 'not GLPI',
        ]);
        $created_items = $answers->getCreatedItems();

        // Assert: the ticket should not have been created
        $this->assertCount(1, $created_items);
    }
}
