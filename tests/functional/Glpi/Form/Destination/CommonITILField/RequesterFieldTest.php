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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use CommonITILActor;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Tests\FormBuilder;
use Group;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;
use User;

include_once __DIR__ . '/../../../../../abstracts/AbstractActorFieldTest.php';

final class RequesterFieldTest extends AbstractActorFieldTest
{
    #[Override]
    public function getFieldClass(): string
    {
        return RequesterField::class;
    }

    public function testRequesterFromTemplate(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $from_template_config = new RequesterFieldConfig(
            [ITILActorFieldStrategy::FROM_TEMPLATE]
        );

        // The default GLPI's template doesn't have a predefined location
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors: []
        );

        $user = $this->createItem(User::class, ['name' => 'testRequesterFromTemplate User']);
        $group = $this->createItem(Group::class, ['name' => 'testRequesterFromTemplate Group']);

        // Set the user as default requester using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 4, // User requester
            'value' => $user->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors: [['items_id' => $user->getID()]]
        );

        // Set the group as default requester using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 71, // Group requester
            'value' => $group->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors: [['items_id' => $user->getID()], ['items_id' => $group->getID()]]
        );
    }

    public function testRequesterFormFiller(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $form_filler_config = new RequesterFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER]
        );

        // The default GLPI's template doesn't have a predefined location
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_config,
            answers: [],
            expected_actors: []
        );

        $auth = $this->login();
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_config,
            answers: [],
            expected_actors: [
                [
                    'items_id' => $auth->getUser()->getID(),
                    'itemtype' => User::class,
                    // Make sure notifications are enabled
                    'use_notification' => 1,
                ],
            ]
        );
    }

    public function testRequesterFormFillerSupervisor(): void
    {
        $supervisor = $this->createItem(User::class, ['name' => 'testRequesterFormFillerSupervisor Supervisor']);
        $user = $this->createItem(User::class, [
            'name'                => 'testRequesterFormFillerSupervisor User',
            'password'            => 'testRequesterFormFillerSupervisor User',
            'password2'           => 'testRequesterFormFillerSupervisor User',
            'users_id_supervisor' => $supervisor->getID(),
        ], ['password', 'password2']);

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $form_filler_supervisor_config = new RequesterFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER_SUPERVISOR]
        );

        // Need user to be logged in
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors: []
        );

        // No supervisor set
        $auth = $this->login();
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors: []
        );

        // Supervisor set
        $auth = $this->login($user->fields['name'], $user->fields['name']);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors: [['items_id' => $auth->getUser()->fields['users_id_supervisor']]]
        );
    }

    public function testSpecificActors(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();

        $user = $this->createItem(User::class, [
            'name' => 'testSpecificActors User',
        ]);

        $group = $this->createItem(Group::class, ['name' => 'testSpecificActors Group']);

        // Specific value: User
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID(),
                ]
            ),
            answers: [],
            expected_actors: [['items_id' => $user->getID()]]
        );

        // Specific value: User and Group
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ]
            ),
            answers: [],
            expected_actors: [['items_id' => $user->getID()], ['items_id' => $group->getID()]]
        );
    }

    public function testActorsFromSpecificQuestions(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();

        $user1 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User']);
        $user2 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User 2']);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);

        $answers = [
            "Requester 1" => [
                User::getForeignKeyField() . '-' . $user1->getID(),
            ],
            "Requester 2" => [
                User::getForeignKeyField() . '-' . $user2->getID(),
                Group::getForeignKeyField() . '-' . $group->getID(),
            ],
            "Requester email 1" => 'test1@test.test',
            "Requester email 2" => 'test2@test.test',
        ];

        // Using answer from first requester question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Requester 1")]
            ),
            answers: $answers,
            expected_actors: [['items_id' => $user1->getID()]]
        );

        // Using answer from first and second requester questions
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Requester 1"),
                    $this->getQuestionId($form, "Requester 2"),
                ]
            ),
            answers: $answers,
            expected_actors: [['items_id' => $user1->getID()], ['items_id' => $user2->getID()], ['items_id' => $group->getID()]]
        );

        // Using answer from first email question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Requester email 1")]
            ),
            answers: $answers,
            expected_actors: [['items_id' => 0, 'alternative_email' => 'test1@test.test']]
        );

        // Using answer from first and second email questions
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Requester email 1"),
                    $this->getQuestionId($form, "Requester email 2"),
                ]
            ),
            answers: $answers,
            expected_actors: [
                ['items_id' => 0, 'alternative_email' => 'test1@test.test'],
                ['items_id' => 0, 'alternative_email' => 'test2@test.test'],
            ]
        );

        // Using answers from both requester and email questions
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Requester 1"),
                    $this->getQuestionId($form, "Requester 2"),
                    $this->getQuestionId($form, "Requester email 1"),
                    $this->getQuestionId($form, "Requester email 2"),
                ]
            ),
            answers: $answers,
            expected_actors: [
                ['items_id' => $user1->getID()],
                ['items_id' => $user2->getID()],
                ['items_id' => 0, 'alternative_email' => 'test1@test.test'],
                ['items_id' => 0, 'alternative_email' => 'test2@test.test'],
                ['items_id' => $group->getID()],
            ]
        );
    }

    public function testActorsFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $last_valid_answer_config = new RequesterFieldConfig(
            [ITILActorFieldStrategy::LAST_VALID_ANSWER]
        );

        $user1 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User']);
        $user2 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User 2']);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);

        // With multiple requester answers submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Requester 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors: [['items_id' => $user2->getID()], ['items_id' => $group->getID()]]
        );

        // With multiple email answers submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester email 1" => 'test1@test.test',
                "Requester email 2" => 'test2@test.test',
            ],
            expected_actors: [['items_id' => 0, 'alternative_email' => 'test2@test.test']]
        );

        // With multiple requester and email answers submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Requester 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
                "Requester email 1" => 'test1@test.test',
                "Requester email 2" => 'test2@test.test',
            ],
            expected_actors: [['items_id' => 0, 'alternative_email' => 'test2@test.test']]
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
            ],
            expected_actors: [['items_id' => $user1->getID()]]
        );

        // Only second answer was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors: [['items_id' => $user2->getID()], ['items_id' => $group->getID()]]
        );

        // Only first email question was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester email 1" => 'test1@test.test',
            ],
            expected_actors: [['items_id' => 0, 'alternative_email' => 'test1@test.test']]
        );

        // Only second email question was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Requester email 2" => 'test2@test.test',
            ],
            expected_actors: [['items_id' => 0, 'alternative_email' => 'test2@test.test']]
        );

        // No answers, fallback to default value
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_actors: []
        );

        // Try again with a different template value
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 4, // User requester
            'value' => $user1->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_actors: [['items_id' => $user1->getID()]]
        );
    }

    public function testMultipleStrategies(): void
    {
        // Login is required to assign actors
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $user1 = $this->createItem(User::class, ['name' => 'testMultipleStrategies User 1']);
        $user2 = $this->createItem(User::class, ['name' => 'testMultipleStrategies User 2']);
        $group = $this->createItem(Group::class, ['name' => 'testMultipleStrategies Group']);

        // Set the user as default requester using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 4, // User requester
            'value' => $user1->getID(),
        ]);

        // Multiple strategies: FROM_TEMPLATE and SPECIFIC_VALUES
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::FROM_TEMPLATE, ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ]
            ),
            answers: [],
            expected_actors: [['items_id' => $user1->getID()], ['items_id' => $user2->getID()], ['items_id' => $group->getID()]]
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Form author' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 1, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR
                    'actor_value' => 0,
                ],
            ],
            'field_config' => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::FORM_FILLER],
            ),
        ];

        yield 'Form validator' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 2, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR
                    'actor_value' => 0,
                ],
            ],
            'field_config' => fn($migration, $form) => (new RequesterField())->getDefaultConfig($form),
        ];

        yield 'Specific person' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 3, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON
                    'actor_value' => getItemByTypeName(User::class, 'glpi', true),
                ],
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 3, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON
                    'actor_value' => getItemByTypeName(User::class, 'tech', true),
                ],
            ],
            'field_config' => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    'User' => [
                        getItemByTypeName(User::class, 'glpi', true),
                        getItemByTypeName(User::class, 'tech', true),
                    ],
                ]
            ),
        ];

        yield 'Person from the question' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 4, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON
                    'actor_value' => 75,
                ],
            ],
            'field_config' => fn($migration, $form) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 75)['items_id']
                    ?? throw new \Exception("Question not found"),
                ]
            ),
        ];

        yield 'Specific group' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 5, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP
                    'actor_value' => getItemByTypeName(Group::class, '_test_group_1', true),
                ],
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 5, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP
                    'actor_value' => getItemByTypeName(Group::class, '_test_group_2', true),
                ],
            ],
            'field_config' => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    'Group' => [
                        getItemByTypeName(Group::class, '_test_group_1', true),
                        getItemByTypeName(Group::class, '_test_group_2', true),
                    ],
                ]
            ),
        ];

        yield 'Group from the question' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 6, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP
                    'actor_value' => 76,
                ],
            ],
            'field_config' => fn($migration, $form) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 76)['items_id']
                    ?? throw new \Exception("Question not found"),
                ]
            ),
        ];

        yield 'Actors from the question' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 9, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS
                    'actor_value' => 77,
                ],
            ],
            'field_config' => fn($migration, $form) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 77)['items_id']
                    ?? throw new \Exception("Question not found"),
                ]
            ),
        ];

        yield 'Group from an object' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 10, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP_FROM_OBJECT
                    'actor_value' => 0,
                ],
            ],
            'field_config' => fn($migration, $form) => (new RequesterField())->getDefaultConfig($form),
        ];

        yield 'Tech group from an object' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 11, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_TECH_GROUP_FROM_OBJECT
                    'actor_value' => 0,
                ],
            ],
            'field_config' => fn($migration, $form) => (new RequesterField())->getDefaultConfig($form),
        ];

        yield 'Form author\'s supervisor' => [
            'field_key'     => RequesterField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 1, // Requester
                    'actor_type'  => 12, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPERVISOR
                    'actor_value' => 0,
                ],
            ],
            'field_config' => fn($migration, $form) => (new RequesterField())->getDefaultConfig($form),
        ];
    }

    #[Override]
    #[DataProvider('provideConvertFieldConfigFromFormCreator')]
    public function testConvertFieldConfigFromFormCreator(
        string $field_key,
        array $fields_to_set,
        callable|JsonFieldInterface $field_config
    ): void {
        global $DB;

        $destination_id = $DB->request([
            'SELECT' => ['id'],
            'FROM' => 'glpi_plugin_formcreator_targettickets',
            'WHERE' => ['name' => 'Test form migration for target ticket'],
        ])->current()['id'];

        $DB->delete(
            'glpi_plugin_formcreator_targets_actors',
            [
                'itemtype' => 'PluginFormcreatorTargetTicket',
                'items_id' => $destination_id,
            ]
        );

        foreach ($fields_to_set as $fields) {
            $this->assertNotFalse($DB->insert(
                'glpi_plugin_formcreator_targets_actors',
                array_merge($fields, [
                    'itemtype' => 'PluginFormcreatorTargetTicket',
                    'items_id' => $destination_id,
                ])
            ));
        }

        parent::testConvertFieldConfigFromFormCreator($field_key, [], $field_config);
    }

    protected function sendFormAndAssertTicketActors(
        Form $form,
        ITILActorFieldConfig $config,
        array $answers,
        array $expected_actors,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [(new RequesterField())->getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            getItemByTypeName(User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        /** @var Ticket $ticket */
        $ticket = current($created_items);

        // Check actors
        $actors = $ticket->getActorsForType(CommonITILActor::REQUESTER);
        foreach ($expected_actors as $expected_actor) {
            $actor = array_shift($actors);
            $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
                $expected_actor,
                $actor,
                array_keys($expected_actor)
            );
        }
    }

    private function createAndGetFormWithMultipleActorsQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Requester 1", QuestionTypeRequester::class, '');
        $builder->addQuestion(
            "Requester 2",
            QuestionTypeRequester::class,
            '',
            json_encode((new QuestionTypeActorsExtraDataConfig(true))->jsonSerialize())
        );
        $builder->addQuestion("Requester email 1", QuestionTypeEmail::class);
        $builder->addQuestion("Requester email 2", QuestionTypeEmail::class, );
        return $this->createForm($builder);
    }
}
