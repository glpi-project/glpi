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

use Change;
use CommonITILObject_CommonITILObject;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsField;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldConfig;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldStrategyConfig;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Ticket;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

final class LinkedITILObjectsFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testLinkedITILObjectsFromSpecificValues(): void
    {
        $this->login();

        // Create tickets for testing
        $tickets = $this->createITILObjects(Ticket::class, 2);
        $form = $this->createAndGetFormWithITILObjectQuestions();
        $linktype = CommonITILObject_CommonITILObject::LINK_TO;

        $specific_values = new LinkedITILObjectsFieldConfig([
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES,
                linktype: $linktype,
                specific_itilobject: [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[1]->getID(),
                ],
            ),
        ]);

        // Test with no answers
        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $specific_values,
            answers: [],
            expected_linked_objects: [
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[1]->getID(),
                    'link' => $linktype,
                ],
            ]
        );

        // Test with answers (should still link to the specific value)
        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $specific_values,
            answers: [
                "Related Ticket" => [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[0]->getID(),
                ],
            ],
            expected_linked_objects: [
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[1]->getID(),
                    'link' => $linktype,
                ],
            ]
        );
    }

    public function testLinkedITILObjectsFromSpecificAnswers(): void
    {
        $this->login();

        // Create tickets for testing
        $tickets = $this->createITILObjects(Ticket::class, 2);
        $changes = $this->createITILObjects(Change::class, 1);

        $form = $this->createAndGetFormWithITILObjectQuestions();
        $linktype = CommonITILObject_CommonITILObject::SON_OF;

        $ticket_question_id = $this->getQuestionId($form, "Related Ticket");
        $change_question_id = $this->getQuestionId($form, "Related Change");

        $specific_answers = new LinkedITILObjectsFieldConfig([
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS,
                linktype: $linktype,
                specific_question_ids: [$ticket_question_id],
            ),
        ]);

        // Test with no answers
        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_linked_objects: []
        );

        // Test with answer from the specified question
        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $specific_answers,
            answers: [
                "Related Ticket" => [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[0]->getID(),
                ],
            ],
            expected_linked_objects: [
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[0]->getID(),
                    'link' => $linktype,
                ],
            ]
        );

        // Test with multiple specific question ids
        $multiple_answers = new LinkedITILObjectsFieldConfig([
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS,
                linktype: $linktype,
                specific_question_ids: [$ticket_question_id, $change_question_id],
            ),
        ]);

        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $multiple_answers,
            answers: [
                "Related Ticket" => [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[1]->getID(),
                ],
                "Related Change" => [
                    'itemtype' => Change::class,
                    'items_id' => $changes[0]->getID(),
                ],
            ],
            expected_linked_objects: [
                [
                    'itemtype' => Change::class,
                    'items_id' => $changes[0]->getID(),
                    'link' => CommonITILObject_CommonITILObject::PARENT_OF, // Change is parent of Ticket
                ],
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[1]->getID(),
                    'link' => $linktype,
                ],
            ]
        );
    }

    public function testLinkedITILObjectsFromSpecificDestinations(): void
    {
        $this->login();

        // Create a form with multiple destinations
        $builder = new FormBuilder();
        $builder->addQuestion(
            "Test question",
            QuestionTypeItem::class,
            "",
            json_encode(new QuestionTypeItemExtraDataConfig(Ticket::class))
        );
        $builder->addDestination(FormDestinationTicket::class, "First destination");
        $builder->addDestination(FormDestinationTicket::class, "Second destination");

        $form = $this->createForm($builder);
        $destinations = $form->getDestinations();
        $this->assertCount(3, $destinations);

        $first_destination = array_values($destinations)[1];
        $second_destination = array_values($destinations)[2];
        $linktype = CommonITILObject_CommonITILObject::DUPLICATE_WITH;

        // Configure the second destination to link to the first
        $specific_destinations = new LinkedITILObjectsFieldConfig([
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS,
                linktype: $linktype,
                specific_destination_ids: [$first_destination->getID()],
            ),
        ]);

        // Use the helper method to configure the second destination and submit the form
        $created_items = $this->sendFormAndReturnCreatedItems(
            $form,
            $specific_destinations,
            [],
            2 // second destination index
        );

        // Get created tickets
        /** @var Ticket[] $created_items */
        $this->assertCount(3, $created_items);

        // Find the tickets created by each destination
        $first_ticket = array_values($created_items)[1];
        $second_ticket = array_values($created_items)[2];

        // Check linked objects for first ticket
        $linked_objects = CommonITILObject_CommonITILObject::getAllLinkedTo($first_ticket::class, $first_ticket->getID());
        $this->assertEquals(
            [
                [
                    'itemtype' => $second_ticket::class,
                    'items_id' => $second_ticket->getID(),
                    'link'     => $linktype,
                ],
            ],
            array_values(array_map(fn($obj) => [
                'itemtype' => $obj['itemtype'],
                'items_id' => $obj['items_id'],
                'link'     => $obj['link'],
            ], $linked_objects)),
            "Expected linked objects from specific destinations"
        );

        // Check linked objects for second ticket
        $linked_objects = CommonITILObject_CommonITILObject::getAllLinkedTo($second_ticket::class, $second_ticket->getID());
        $this->assertEquals(
            [
                [
                    'itemtype' => $first_ticket::class,
                    'items_id' => $first_ticket->getID(),
                    'link'     => $linktype,
                ],
            ],
            array_values(array_map(fn($obj) => [
                'itemtype' => $obj['itemtype'],
                'items_id' => $obj['items_id'],
                'link'     => $obj['link'],
            ], $linked_objects)),
            "Expected linked objects from specific destinations"
        );
    }

    public function testMultipleStrategies(): void
    {
        $this->login();

        // Create ITIL objects for testing
        $tickets = $this->createITILObjects(Ticket::class, 2);
        $changes = $this->createITILObjects(Change::class, 1);

        $form = $this->createAndGetFormWithITILObjectQuestions();
        $ticket_question_id = $this->getQuestionId($form, "Related Ticket");

        // Multiple strategies: SPECIFIC_VALUES and SPECIFIC_ANSWERS
        $multiple_strategies = new LinkedITILObjectsFieldConfig([
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES,
                linktype: CommonITILObject_CommonITILObject::DUPLICATE_WITH,
                specific_itilobject: [
                    'itemtype' => Change::class,
                    'items_id' => $changes[0]->getID(),
                ],
            ),
            new LinkedITILObjectsFieldStrategyConfig(
                strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS,
                linktype: CommonITILObject_CommonITILObject::SON_OF,
                specific_question_ids: [$ticket_question_id],
            ),
        ]);

        $this->sendFormAndAssertLinkedITILObjects(
            form: $form,
            config: $multiple_strategies,
            answers: [
                "Related Ticket" => [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[0]->getID(),
                ],
            ],
            expected_linked_objects: [
                [
                    'itemtype' => Change::class,
                    'items_id' => $changes[0]->getID(),
                    'link' => CommonITILObject_CommonITILObject::DUPLICATE_WITH,
                ],
                [
                    'itemtype' => Ticket::class,
                    'items_id' => $tickets[0]->getID(),
                    'link' => CommonITILObject_CommonITILObject::SON_OF,
                ],
            ]
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        $destination_id = 13;
        yield 'Specific destinations strategy' => [
            'field_key'     => LinkedITILObjectsField::getKey(),
            'fields_to_set' => [],
            'field_config'  => fn($migration, $form) => new LinkedITILObjectsFieldConfig([
                new LinkedITILObjectsFieldStrategyConfig(
                    strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS,
                    linktype: CommonITILObject_CommonITILObject::LINK_TO,
                    specific_destination_ids: [$migration->getMappedItemTarget('PluginFormcreatorTargetTicket', $destination_id)['items_id']
                        ?? throw new \Exception("Destination not found"),],
                ),
            ]),
            'data' => function ($DB) use ($destination_id) {
                // Create a destination to link to
                $DB->insert('glpi_plugin_formcreator_targettickets', [
                    'id'                          => $destination_id,
                    'name'                        => 'Test destination',
                    'plugin_formcreator_forms_id' => 17,
                    'content'                     => 'Test content',
                ]);

                $DB->insert('glpi_plugin_formcreator_items_targettickets', [
                    'id'                                  => 1,
                    'plugin_formcreator_targettickets_id' => 12,
                    'link'                                => 1,
                    'itemtype'                            => 'PluginFormcreatorTargetTicket',
                    'items_id'                            => $destination_id,
                ]);
            },
        ];

        $question_id = 100;
        yield 'Specific answers strategy' => [
            'field_key'     => LinkedITILObjectsField::getKey(),
            'fields_to_set' => [],
            'field_config'  => fn($migration, $form) => new LinkedITILObjectsFieldConfig([
                new LinkedITILObjectsFieldStrategyConfig(
                    strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS,
                    linktype: CommonITILObject_CommonITILObject::LINK_TO,
                    specific_question_ids: [$migration->getMappedItemTarget('PluginFormcreatorQuestion', $question_id)['items_id']
                        ?? throw new \Exception("Question not found"),],
                ),
            ]),
            'data' => function ($DB) use ($question_id) {
                $DB->insert('glpi_plugin_formcreator_items_targettickets', [
                    'id'                                  => 1,
                    'plugin_formcreator_targettickets_id' => 12,
                    'link'                                => 1,
                    'itemtype'                            => 'PluginFormcreatorQuestion',
                    'items_id'                            => $question_id,
                ]);

                $DB->insert('glpi_plugin_formcreator_questions', [
                    'id'                             => $question_id,
                    'name'                           => 'Test Question',
                    'plugin_formcreator_sections_id' => 24,
                    'fieldtype'                      => 'glpiselect',
                    'itemtype'                       => 'Ticket',
                ]);
            },
        ];

        yield 'Specific values strategy' => [
            'field_key'     => LinkedITILObjectsField::getKey(),
            'fields_to_set' => [],
            'field_config'  => new LinkedITILObjectsFieldConfig([
                new LinkedITILObjectsFieldStrategyConfig(
                    strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES,
                    linktype: CommonITILObject_CommonITILObject::LINK_TO,
                    specific_itilobject: [
                        'itemtype' => Ticket::class,
                        'items_id' => 10,
                    ],
                ),
                new LinkedITILObjectsFieldStrategyConfig(
                    strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_VALUES,
                    linktype: CommonITILObject_CommonITILObject::DUPLICATE_WITH,
                    specific_itilobject: [
                        'itemtype' => Change::class,
                        'items_id' => 10,
                    ],
                ),
            ]),
            'data' => function ($DB) {
                $ticket_id = (new Ticket())->add([
                    'id'          => 10,
                    'name'        => 'Test Ticket for linked objects',
                    'content'     => 'Test content',
                    'entities_id' => 1, // Assuming root entity ID is 1
                ]);

                $change_id = (new Change())->add([
                    'id'          => 10,
                    'name'        => 'Test Change for linked objects',
                    'content'     => 'Test content',
                    'entities_id' => 1, // Assuming root entity ID is 1
                ]);

                $DB->insert('glpi_plugin_formcreator_items_targettickets', [
                    'id'                                  => 1,
                    'plugin_formcreator_targettickets_id' => 12,
                    'link'                                => 1,
                    'itemtype'                            => Ticket::class,
                    'items_id'                            => $ticket_id,
                ]);
                $DB->insert('glpi_plugin_formcreator_items_targettickets', [
                    'id'                                  => 2,
                    'plugin_formcreator_targettickets_id' => 12,
                    'link'                                => 2,
                    'itemtype'                            => Change::class,
                    'items_id'                            => $change_id,
                ]);
            },
        ];
    }

    #[Override]
    #[DataProvider('provideConvertFieldConfigFromFormCreator')]
    public function testConvertFieldConfigFromFormCreator(
        string $field_key,
        array $fields_to_set,
        callable|JsonFieldInterface $field_config,
        ?callable $data = null
    ): void {
        if ($data !== null) {
            global $DB;

            $data($DB);
        }

        parent::testConvertFieldConfigFromFormCreator(
            $field_key,
            $fields_to_set,
            $field_config
        );
    }

    private function sendFormAndAssertLinkedITILObjects(
        Form $form,
        LinkedITILObjectsFieldConfig $config,
        array $answers,
        array $expected_linked_objects
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [LinkedITILObjectsField::getKey() => $config->jsonSerialize()]],
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
        $ticket = current($created_items);
        $this->assertInstanceOf(Ticket::class, $ticket);

        // Check linked objects
        $linked_objects = CommonITILObject_CommonITILObject::getAllLinkedTo($ticket::class, $ticket->getID());
        $this->assertEquals(
            count($expected_linked_objects),
            count($linked_objects),
            "Expected " . count($expected_linked_objects) . " links but found " . count($linked_objects)
        );

        $this->assertEquals(
            $expected_linked_objects,
            array_values(array_map(fn($obj) => [
                'itemtype' => $obj['itemtype'],
                'items_id' => $obj['items_id'],
                'link' => $obj['link'],
            ], $linked_objects)),
        );
    }

    private function sendFormAndReturnCreatedItems(
        Form $form,
        LinkedITILObjectsFieldConfig $config,
        array $answers,
        int $destination_index
    ): array {
        // Insert config
        $destinations = $form->getDestinations();
        $destination_values = array_values($destinations);
        $this->assertArrayHasKey($destination_index, $destination_values);
        $destination = $destination_values[$destination_index];

        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [LinkedITILObjectsField::getKey() => $config->jsonSerialize()]],
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

        // Return created items for external verification
        return $answers->getCreatedItems();
    }

    private function createITILObjects(string $itemtype, int $count): array
    {
        $objects = [];
        for ($i = 1; $i <= $count; $i++) {
            $objects[] = $this->createItem($itemtype, [
                'name' => "$itemtype $i",
                'content' => "Test content for $itemtype $i",
                'entities_id' => $this->getTestRootEntity(true),
            ]);
        }
        return $objects;
    }

    private function createAndGetFormWithITILObjectQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Related Ticket", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Ticket::class,
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));
        $builder->addQuestion("Related Change", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Change::class,
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));
        $builder->addQuestion("Related Problem", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Problem::class,
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));

        return $this->createForm($builder);
    }
}
