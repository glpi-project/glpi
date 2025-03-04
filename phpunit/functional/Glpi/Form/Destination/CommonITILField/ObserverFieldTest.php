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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use CommonITILActor;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldConfig;
use Glpi\Form\Destination\CommonITILField\ObserverFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\PHPUnit\Tests\Glpi\Form\Destination\CommonITILField\AbstractDestinationFieldTest;
use Glpi\Tests\FormBuilder;
use Group;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Supplier;
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;
use User;

include_once __DIR__ . '/../../../../../abstracts/AbstractActorFieldTest.php';

final class ObserverFieldTest extends AbstractActorFieldTest
{
    #[Override]
    public function getFieldClass(): string
    {
        return ObserverField::class;
    }

    public function testObserverFromTemplate(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $from_template_config = new ObserverFieldConfig(
            [ITILActorFieldStrategy::FROM_TEMPLATE]
        );

        // The default GLPI's template doesn't have a predefined location
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: []
        );

        $user = $this->createItem(User::class, ['name' => 'testObserverFromTemplate User']);
        $group = $this->createItem(Group::class, ['name' => 'testObserverFromTemplate Group']);

        // Set the user as default observer using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 66, // User observer
            'value' => $user->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: [$user->getID()]
        );

        // Set the group as default observer using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 65, // Group observer
            'value' => $group->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID()]
        );
    }

    public function testObserverFormFiller(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $form_filler_config = new ObserverFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER]
        );

        // The default GLPI's template doesn't have a predefined location
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_config,
            answers: [],
            expected_actors_ids: []
        );

        $auth = $this->login();
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_config,
            answers: [],
            expected_actors_ids: [$auth->getUser()->getID()]
        );
    }

    public function testObserverFormFillerSupervisor(): void
    {
        $supervisor = $this->createItem(User::class, ['name' => 'testObserverFormFillerSupervisor Supervisor']);
        $user = $this->createItem(User::class, [
            'name'                => 'testObserverFormFillerSupervisor User',
            'password'            => 'testObserverFormFillerSupervisor User',
            'password2'           => 'testObserverFormFillerSupervisor User',
            'users_id_supervisor' => $supervisor->getID()
        ], ['password', 'password2']);

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $form_filler_supervisor_config = new ObserverFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER_SUPERVISOR]
        );

        // Need user to be logged in
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors_ids: []
        );

        // No supervisor set
        $auth = $this->login();
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors_ids: []
        );

        // Supervisor set
        $auth = $this->login($user->fields['name'], $user->fields['name']);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $form_filler_supervisor_config,
            answers: [],
            expected_actors_ids: [$auth->getUser()->fields['users_id_supervisor']]
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
            config: new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user->getID()]
        );

        // Specific value: User and Group
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID()]
        );
    }

    public function testActorsFromSpecificQuestions(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();

        $user1 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User']);
        $user2 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User 2']);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);

        // Using answer from first question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Observer 1")]
            ),
            answers: [
                "Observer 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Observer 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors_ids: [$user1->getID()]
        );

        // Using answer from first and second question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Observer 1"),
                    $this->getQuestionId($form, "Observer 2")
                ]
            ),
            answers: [
                "Observer 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Observer 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors_ids: [$user1->getID(), $user2->getID(), $group->getID()]
        );
    }

    public function testActorsFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $last_valid_answer_config = new ObserverFieldConfig(
            [ITILActorFieldStrategy::LAST_VALID_ANSWER]
        );

        $user1 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User']);
        $user2 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User 2']);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);

        // With multiple answers submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Observer 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Observer 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors_ids: [$user2->getID(), $group->getID()]
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Observer 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
            ],
            expected_actors_ids: [$user1->getID()]
        );

        // Only second answer was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Observer 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                ],
            ],
            expected_actors_ids: [$user2->getID(), $group->getID()]
        );

        // No answers, fallback to default value
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_actors_ids: []
        );

        // Try again with a different template value
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 66, // User observer
            'value' => $user1->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_actors_ids: [$user1->getID()]
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

        // Set the user as default observer using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 66, // User observer
            'value' => $user1->getID(),
        ]);

        // Multiple strategies: FROM_TEMPLATE and SPECIFIC_VALUES
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::FROM_TEMPLATE, ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user1->getID(), $user2->getID(), $group->getID()]
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Form author' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 1,
                    'actor_value' => 0,
                ]
            ],
            'field_config' => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::FORM_FILLER],
            )
        ];

        yield 'Form validator' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 2,
                    'actor_value' => 0,
                ]
            ],
            'field_config' => fn ($migration, $form) => (new ObserverField())->getDefaultConfig($form)
        ];

        yield 'Specific person' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 3,
                    'actor_value' => getItemByTypeName(User::class, 'glpi', true),
                ],
                [
                    'actor_role'  => 2,
                    'actor_type'  => 3,
                    'actor_value' => getItemByTypeName(User::class, 'tech', true),
                ]
            ],
            'field_config' => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    'User' => [
                        getItemByTypeName(User::class, 'glpi', true),
                        getItemByTypeName(User::class, 'tech', true),
                    ]
                ]
            )
        ];

        yield 'Person from the question' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 4,
                    'actor_value' => 75,
                ]
            ],
            'field_config' => fn ($migration, $form) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 75)['items_id']
                    ?? throw new \Exception("Question not found")
                ]
            )
        ];

        yield 'Specific group' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 5,
                    'actor_value' => getItemByTypeName(Group::class, '_test_group_1', true),
                ],
                [
                    'actor_role'  => 2,
                    'actor_type'  => 5,
                    'actor_value' => getItemByTypeName(Group::class, '_test_group_2', true),
                ]
            ],
            'field_config' => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    'Group' => [
                        getItemByTypeName(Group::class, '_test_group_1', true),
                        getItemByTypeName(Group::class, '_test_group_2', true),
                    ]
                ]
            )
        ];

        yield 'Group from the question' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 6,
                    'actor_value' => 76,
                ]
            ],
            'field_config' => fn ($migration, $form) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 76)['items_id']
                    ?? throw new \Exception("Question not found")
                ]
            )
        ];

        yield 'Actors from the question' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 9,
                    'actor_value' => 77,
                ]
            ],
            'field_config' => fn ($migration, $form) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 77)['items_id']
                    ?? throw new \Exception("Question not found")
                ]
            )
        ];

        yield 'Group from an object' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 10,
                    'actor_value' => 0,
                ]
            ],
            'field_config' => fn ($migration, $form) => (new ObserverField())->getDefaultConfig($form)
        ];

        yield 'Tech group from an object' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 11,
                    'actor_value' => 0,
                ]
            ],
            'field_config' => fn ($migration, $form) => (new ObserverField())->getDefaultConfig($form)
        ];

        yield 'Form author\'s supervisor' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 12,
                    'actor_value' => 0,
                ]
            ],
            'field_config' => fn ($migration, $form) => (new ObserverField())->getDefaultConfig($form)
        ];

        yield 'Specific supplier' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 7,
                    'actor_value' => fn(ObserverFieldTest $context) => $context->createItem(Supplier::class, [
                        'name' => '_test_supplier_1',
                        'entities_id' => $context->getTestRootEntity(true),
                    ])->getID(),
                ],
                [
                    'actor_role'  => 2,
                    'actor_type'  => 7,
                    'actor_value' => fn(ObserverFieldTest $context) => $context->createItem(Supplier::class, [
                        'name' => '_test_supplier_2',
                        'entities_id' => $context->getTestRootEntity(true),
                    ])->getID(),
                ]
            ],
            'field_config' => fn ($migration, $form) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    'Supplier' => [
                        getItemByTypeName(Supplier::class, '_test_supplier_1', true),
                        getItemByTypeName(Supplier::class, '_test_supplier_2', true),
                    ]
                ]
            )
        ];

        yield 'Supplier from the question' => [
            'field_key'     => ObserverField::getKey(),
            'fields_to_set' => [
                [
                    'actor_role'  => 2,
                    'actor_type'  => 8,
                    'actor_value' => 78,
                ]
            ],
            'field_config' => fn ($migration, $form) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget('PluginFormcreatorQuestion', 78)['items_id']
                    ?? throw new \Exception("Question not found")
                ]
            )
        ];
    }

    #[Override]
    #[DataProvider('provideConvertFieldConfigFromFormCreator')]
    public function testConvertFieldConfigFromFormCreator(
        string $field_key,
        array $fields_to_set,
        callable|JsonFieldInterface $field_config
    ): void {
        /** @var DBmysql $DB */
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
                'items_id' => $destination_id
            ]
        );

        foreach ($fields_to_set as $fields) {
            // Compute some values
            foreach ($fields as $key => $value) {
                if (is_callable($value)) {
                    $fields[$key] = $value($this);
                }
            }

            // Insert the actor
            $this->assertNotFalse($DB->insert(
                'glpi_plugin_formcreator_targets_actors',
                array_merge($fields, [
                    'itemtype' => 'PluginFormcreatorTargetTicket',
                    'items_id' => $destination_id
                ])
            ));
        }

        parent::testConvertFieldConfigFromFormCreator($field_key, [], $field_config);
    }

    protected function sendFormAndAssertTicketActors(
        Form $form,
        ITILActorFieldConfig $config,
        array $answers,
        array $expected_actors_ids,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [(new ObserverField())->getKey() => $config->jsonSerialize()]],
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
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        /** @var Ticket $ticket */
        $ticket = current($created_items);

        // Check actors
        $this->assertEquals(
            $expected_actors_ids,
            array_map(fn(array $actor) => $actor['items_id'], $ticket->getActorsForType(CommonITILActor::OBSERVER))
        );
    }

    private function createAndGetFormWithMultipleActorsQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Observer 1", QuestionTypeObserver::class, '');
        $builder->addQuestion(
            "Observer 2",
            QuestionTypeObserver::class,
            '',
            json_encode((new QuestionTypeActorsExtraDataConfig(true))->jsonSerialize())
        );
        return $this->createForm($builder);
    }
}
