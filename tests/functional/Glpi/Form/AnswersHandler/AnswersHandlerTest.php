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
use Entity;
use Glpi\Form\Answer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\ValidationResult;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

class AnswersHandlerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testSaveAnswers(): void
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
                self::getQuestionId($form_1, "Thoughts about GLPI") => "I love GLPI!!!",
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
                self::getQuestionId($form_1, "Thoughts about GLPI") => "GLPI is incredible",
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

    public static function provideTestValidateAnswers(): iterable
    {
        // Basic mandatory form builder
        $mandatory_form_builder = (new FormBuilder("Validation Test Form"))
                ->addQuestion("Mandatory Name", QuestionTypeShortText::class, is_mandatory: true)
                ->addQuestion("Mandatory Email", QuestionTypeEmail::class, is_mandatory: true)
                ->addQuestion("Optional Comment", QuestionTypeLongText::class, is_mandatory: false);

        yield 'All mandatory fields are filled - should be valid' => [
            'builder' => $mandatory_form_builder,
            'answers' => [
                'Mandatory Name' => 'John Doe',
                'Mandatory Email' => 'john.doe@example.com',
                'Optional Comment' => 'This is an optional comment',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        yield 'Missing one mandatory field - should be invalid' => [
            'builder' => $mandatory_form_builder,
            'answers' => [
                'Mandatory Email' => 'john.doe@example.com',
                'Optional Comment' => 'This is an optional comment',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Name' => 'This field is mandatory',
            ],
        ];

        yield 'Missing all mandatory fields - should be invalid' => [
            'builder' => $mandatory_form_builder,
            'answers' => [
                'Optional Comment' => 'This is an optional comment',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Name' => 'This field is mandatory',
                'Mandatory Email' => 'This field is mandatory',
            ],
        ];

        yield 'Empty answers - should be invalid with multiple errors' => [
            'builder' => $mandatory_form_builder,
            'answers' => [],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Name' => 'This field is mandatory',
                'Mandatory Email' => 'This field is mandatory',
            ],
        ];

        yield 'Empty string in mandatory field - should be invalid' => [
            'builder' => $mandatory_form_builder,
            'answers' => [
                'Mandatory Name' => '',
                'Mandatory Email' => 'john.doe@example.com',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Name' => 'This field is mandatory',
            ],
        ];

        $mandatory_entity_question_form_buidler = (new FormBuilder("Mandatory Item Question Type Test Form"))
            ->addQuestion(
                "Mandatory Entity Question",
                QuestionTypeItem::class,
                extra_data: json_encode(
                    new QuestionTypeItemExtraDataConfig(itemtype: Entity::class)
                ),
                is_mandatory: true
            );

        yield 'Empty entity question - should be invalid with empty choice selected' => [
            'builder' => $mandatory_entity_question_form_buidler,
            'answers' => [
                'Mandatory Entity Question' => [
                    'itemtype' => Entity::class,
                    'items_id' => -1, // Empty choice
                ],
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Entity Question' => 'This field is mandatory',
            ],
        ];

        yield 'Filled entity question - should be valid' => [
            'builder' => $mandatory_entity_question_form_buidler,
            'answers' => [
                'Mandatory Entity Question' => [
                    'itemtype' => Entity::class,
                    'items_id' => 0, // Root entity
                ],
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        $mandatory_location_question_form_builder = (new FormBuilder("Mandatory Dropdown Item Question Type Test Form"))
            ->addQuestion(
                "Mandatory Location Question",
                QuestionTypeItemDropdown::class,
                extra_data: json_encode(
                    new QuestionTypeItemDropdownExtraDataConfig(itemtype: Location::class)
                ),
                is_mandatory: true
            );

        yield 'Empty location dropdown question - should be invalid with empty choice selected' => [
            'builder' => $mandatory_location_question_form_builder,
            'answers' => [
                'Mandatory Location Question' => [
                    'itemtype' => Location::class,
                    'items_id' => -1, // Empty choice
                ],
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Mandatory Location Question' => 'This field is mandatory',
            ],
        ];

        yield 'Filled location dropdown question - should be valid' => [
            'builder' => $mandatory_location_question_form_builder,
            'answers' => [
                'Mandatory Location Question' => [
                    'itemtype' => Location::class,
                    'items_id' => 1,
                ],
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        // Conditonnal validation form builder
        $validation_conditional_form_builder = (new FormBuilder("Conditional Validation Test Form"))
            ->addQuestion("Main Question", QuestionTypeShortText::class, is_mandatory: true)
            ->addQuestion("Conditional Question", QuestionTypeShortText::class, is_mandatory: true);
        $validation_conditional_form_builder->setQuestionValidation(
            "Conditional Question",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Conditional Question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::MATCH_REGEX,
                    'value'          => "/^Conditional Validation$/",
                ],
            ]
        );

        yield 'Validation condition met - should be valid' => [
            'builder' => $validation_conditional_form_builder,
            'answers' => [
                'Main Question'        => 'Show Conditional',
                'Conditional Question' => 'Conditional Validation',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        yield 'Validation condition not met - should be invalid' => [
            'builder' => $validation_conditional_form_builder,
            'answers' => [
                'Main Question'        => 'Show Conditional',
                'Conditional Question' => 'Invalid answer',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Conditional Question' => 'The value must match the requested format',
            ],
        ];

        yield 'Empty answer in conditional question - should be invalid' => [
            'builder' => $validation_conditional_form_builder,
            'answers' => [
                'Main Question'        => 'Show Conditional',
                'Conditional Question' => '',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Conditional Question' => 'This field is mandatory',
            ],
        ];

        // Conditional visibility form builder
        $visibility_conditional_form_builder = (new FormBuilder("Conditional Validation Test Form"))
            ->addQuestion("Main Question", QuestionTypeShortText::class, is_mandatory: true)
            ->addQuestion("Conditional Question", QuestionTypeShortText::class, is_mandatory: true);
        $visibility_conditional_form_builder->setQuestionVisibility(
            "Conditional Question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Main Question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Show Conditional",
                ],
            ]
        );

        yield 'Conditional question shown and filled - should be valid' => [
            'builder' => $visibility_conditional_form_builder,
            'answers' => [
                'Main Question' => 'Show Conditional',
                'Conditional Question' => 'This is a conditional answer',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        yield 'Conditional question not shown - should be valid' => [
            'builder' => $visibility_conditional_form_builder,
            'answers' => [
                'Main Question' => 'Do not show',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        yield 'Conditional question shown but not filled - should be invalid' => [
            'builder' => $visibility_conditional_form_builder,
            'answers' => [
                'Main Question' => 'Show Conditional',
                'Conditional Question' => '',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Conditional Question' => 'This field is mandatory',
            ],
        ];

        // Both validation and visibility form builder
        $both_conditional_form_builder = (new FormBuilder("Both Conditional Validation Test Form"))
            ->addQuestion("Main Question", QuestionTypeShortText::class, is_mandatory: true)
            ->addQuestion("Conditional Question", QuestionTypeShortText::class, is_mandatory: true);
        $both_conditional_form_builder->setQuestionVisibility(
            "Conditional Question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Main Question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Show Conditional",
                ],
            ]
        );
        $both_conditional_form_builder->setQuestionValidation(
            "Conditional Question",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Conditional Question",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::MATCH_REGEX,
                    'value'          => "/^Both Conditional Validation$/",
                ],
            ]
        );

        yield 'Conditional question shown and validation condition met - should be valid' => [
            'builder' => $both_conditional_form_builder,
            'answers' => [
                'Main Question'        => 'Show Conditional',
                'Conditional Question' => 'Both Conditional Validation',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];

        yield 'Conditional question shown but validation condition not met - should be invalid' => [
            'builder' => $both_conditional_form_builder,
            'answers' => [
                'Main Question'        => 'Show Conditional',
                'Conditional Question' => 'Invalid answer',
            ],
            'expectedIsValid' => false,
            'expectedErrors' => [
                'Conditional Question' => 'The value must match the requested format',
            ],
        ];

        yield 'Conditional question not shown and not filled - should be valid' => [
            'builder' => $both_conditional_form_builder,
            'answers' => [
                'Main Question' => 'Do not show',
            ],
            'expectedIsValid' => true,
            'expectedErrors' => [],
        ];
    }

    #[DataProvider('provideTestValidateAnswers')]
    public function testValidateAnswers(
        FormBuilder $builder,
        array $answers,
        bool $expectedIsValid,
        array $expectedErrors
    ): void {
        self::login();

        $form = self::createForm($builder);
        $handler = AnswersHandler::getInstance();

        // Convert answer keys from question names to question IDs
        $mapped_answers = [];
        foreach ($answers as $question_name => $answer) {
            $question_id = self::getQuestionId($form, $question_name);
            $mapped_answers[$question_id] = $answer;
        }
        $result = $handler->validateAnswers($form, $mapped_answers);

        $this->assertEquals($expectedIsValid, $result->isValid(), "Validation result should match expected value");
        $this->assertCount(count($expectedErrors), $result->getErrors(), "Number of errors should match expected value");

        // Convert expected errors to expected format
        $currentErrors = $result->getErrors();
        foreach ($expectedErrors as $name => $error) {
            $this->assertContains(
                [
                    'question_id'   => self::getQuestionId($form, $name),
                    'question_name' => $name,
                    'message'       => $error,
                ],
                $currentErrors,
                "Expected error message should be present in the result"
            );
        }
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
            if ($field === 'answers') {
                $this->assertArraysEqualRecursive(
                    json_decode($expected_value, true),
                    json_decode($answer_set->fields[$field], true),
                );
            } else {
                $this->assertEquals($expected_value, $answer_set->fields[$field]);
            }
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

    public function testCustomMandatoryMessage(): void
    {
        // Arrange: create a form with a  mandatory number question
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            name: "Your phone number",
            type: QuestionTypeNumber::class,
            is_mandatory: true,
        );
        $form = $this->createForm($builder);
        $number_question_id = $this->getQuestionId($form, "Your phone number");
        $number_question = Question::getById($number_question_id);

        // Act: try to validate the form
        $handler = AnswersHandler::getInstance();
        $result = $handler->validateAnswers($form, []);

        // Assert: check error message
        $this->assertEquals(false, $result->isValid());

        // Insert errors into a result object to make sure we compare with
        // the expected format
        $expected_errors = new ValidationResult();
        $expected_errors->addError($number_question, "Please enter a valid number");
        $this->assertEquals(
            $expected_errors->getErrors(),
            $result->getErrors()
        );
    }

    public static function validateEmailsProvider(): iterable
    {
        yield [
            'email'    => "notanemail",
            'expected' => false,
            'errors'   => ["Please enter a valid email"],
        ];
        yield [
            'email'    => "email@teclib.com",
            'expected' => true,
        ];
    }

    #[DataProvider('validateEmailsProvider')]
    public function testValidateEmails(
        string $email,
        bool $expected,
        array $errors = [],
    ): void {
        // Arrange: create a form with an email question
        $builder = new FormBuilder("My form with an email question");
        $builder->addQuestion("Your email", QuestionTypeEmail::class);
        $form = $this->createForm($builder);
        $email_question_id = $this->getQuestionId($form, "Your email");
        $email_question = Question::getById($email_question_id);

        // Act: try to validate the form
        $handler = AnswersHandler::getInstance();
        $result = $handler->validateAnswers($form, [
            $email_question_id => $email,
        ]);

        // Assert: check validity
        $this->assertEquals($expected, $result->isValid());

        // Insert errors into a result object to make sure we compare with
        // the expected format
        $formatted_errors = new ValidationResult();
        foreach ($errors as $error) {
            $formatted_errors->addError($email_question, $error);
        }
        $this->assertEquals(
            $formatted_errors->getErrors(),
            $result->getErrors()
        );
    }
}
