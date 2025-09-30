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

use Computer;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Monitor;
use Override;

include_once __DIR__ . '/../../../../../abstracts/AbstractDestinationFieldTest.php';

final class AssociatedItemsFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    public function testAssociatedItemsFromSpecificItems(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $specific_values = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
            specific_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID(),
                    $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID(),
                ],
            ]
        );

        // Test with no answers
        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_values,
            answers: [],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers
        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_values,
            answers: [
                "Your Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
                "Your Monitors" => [
                    'itemtype' => Monitor::getType(),
                    'items_id' => $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );
    }

    public function testAssociatedItemsFromSpecificAnswers(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $specific_answers = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
            specific_question_ids: [
                $this->getQuestionId($form, "Your Computer"),
                $this->getQuestionId($form, "Your Monitors"),
                $this->getQuestionId($form, "Computer"),
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_associated_items: [
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );
    }

    public function testAssociatedItemsFromSpecificAnswersInFrench(): void
    {
        // Same as the previous test but using another language.
        // This may trigger an error because CommonITILObject::getAllTypesForHelpdesk
        // is language dependant

        $this->login();
        \Session::loadLanguage('fr_FR');

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $specific_answers = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
            specific_question_ids: [
                $this->getQuestionId($form, "Your Computer"),
                $this->getQuestionId($form, "Your Monitors"),
                $this->getQuestionId($form, "Computer"),
            ]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [],
            expected_associated_items: [
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $specific_answers,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );
    }

    public function testAssociatedItemsFromLastValidAnswer(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $last_valid_answer = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $last_valid_answer,
            answers: [],
            expected_associated_items: [
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers: match QuestionTypeItem
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $last_valid_answer,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers: match QuestionTypeUserDevice
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $last_valid_answer,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ],
        );
    }

    public function testAssociatedItemsFromAllValidAnswers(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();
        $all_valid_answers = new AssociatedItemsFieldConfig(
            strategies: [AssociatedItemsFieldStrategy::ALL_VALID_ANSWERS]
        );

        // Test with no answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [],
            expected_associated_items: [
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with only one answer
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                    $computers[1]->getID() => $computers[1]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );

        // Test with answers with same computers
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: $all_valid_answers,
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[0]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
                "Computer" => [
                    'itemtype' => Computer::getType(),
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );
    }

    public function testMultipleStrategies(): void
    {
        $this->login();

        // Create computers and monitors
        $computers = $this->createComputers(2);
        $monitors = $this->createMonitors(2);

        $form = $this->createAndGetFormWithMultipleItemQuestions();

        // Multiple strategies: SPECIFIC_VALUES and SPECIFIC_ANSWERS
        $this->sendFormAndAssertAssociatedItems(
            form: $form,
            config: new AssociatedItemsFieldConfig(
                strategies: [
                    AssociatedItemsFieldStrategy::SPECIFIC_VALUES,
                    AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS,
                ],
                specific_associated_items: [
                    Computer::getType() => [
                        $computers[0]->getID(),
                    ],
                ],
                specific_question_ids: [
                    $this->getQuestionId($form, "Your Monitors"),
                ]
            ),
            answers: [
                "Your Computer" => [
                    'Computer_' . $computers[1]->getID(),
                ],
                "Your Monitors" => [
                    'Monitor_' . $monitors[0]->getID(),
                    'Monitor_' . $monitors[1]->getID(),
                ],
            ],
            expected_associated_items: [
                Computer::getType() => [
                    $computers[0]->getID() => $computers[0]->getID(),
                ],
                Monitor::getType() => [
                    $monitors[0]->getID() => $monitors[0]->getID(),
                    $monitors[1]->getID() => $monitors[1]->getID(),
                ],
                Form::getType() => [
                    $form->getID() => $form->getID(),
                ],
            ]
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'None' => [
            'field_key'     => AssociatedItemsField::getKey(),
            'fields_to_set' => [
                'associate_rule' => 1, // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_NONE
            ],
            'field_config' => fn($migration, $form) => (new AssociatedItemsField())->getDefaultConfig($form),
        ];

        yield 'Equals to the answer to the question' => [
            'field_key'     => AssociatedItemsField::getKey(),
            'fields_to_set' => [
                'associate_rule'     => 3, // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_ANSWER
                'associate_question' => 74,
            ],
            'field_config' => fn($migration, $form) => new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $migration->getMappedItemTarget(
                        'PluginFormcreatorQuestion',
                        74
                    )['items_id'] ?? throw new \Exception("Question not found"),
                ]
            ),
        ];

        yield 'Last valid answer' => [
            'field_key'     => AssociatedItemsField::getKey(),
            'fields_to_set' => [
                'associate_rule' => 4, // PluginFormcreatorAbstractItilTarget::ASSOCIATE_RULE_LAST_ANSWER
            ],
            'field_config' => new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::LAST_VALID_ANSWER]
            ),
        ];
    }

    public function testConvertSpecificAssetFieldConfigFromFormCreator(): void
    {
        global $DB;
        $computer_id = (new Computer())->add([
            'name'        => 'Test Computer for associated items',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $DB->insert(
            'glpi_plugin_formcreator_items_targettickets',
            [
                'id'                                  => 1,
                'plugin_formcreator_targettickets_id' => 12,
                'link'                                => 0,
                'itemtype'                            => Computer::getType(),
                'items_id'                            => $computer_id,
            ]
        );
        $monitor_id = (new Monitor())->add([
            'name'        => 'Test Monitor for associated items',
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $DB->insert(
            'glpi_plugin_formcreator_items_targettickets',
            [
                'id'                                  => 2,
                'plugin_formcreator_targettickets_id' => 12,
                'link'                                => 0,
                'itemtype'                            => Monitor::getType(),
                'items_id'                            => $monitor_id,
            ]
        );

        $this->testConvertFieldConfigFromFormCreator(
            field_key: AssociatedItemsField::getKey(),
            fields_to_set: [
                'associate_rule' => 2,
            ],
            field_config: new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
                specific_associated_items: [
                    Computer::getType() => $computer_id,
                    Monitor::getType()  => $monitor_id,
                ]
            )
        );
    }

    /**
     * Test that we can submit an empty value for a specific values strategy.
     * A peculiarity of the front-end integration returns the value "0" if no
     * itemtype is selected.
     *
     * However, no corresponding id is returned, so we need to ensure
     * that the "0" value is properly ignored.
     */
    public function testSubmitEmptyValueForSpecificValuesStrategy(): void
    {
        $this->login();

        // Create a computer
        $computer = $this->createItem(Computer::class, [
            'name' => "Computer",
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Create a form
        $form = $this->createForm(new FormBuilder());

        $destination = current($form->getDestinations());
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            [
                'config' => [
                    AssociatedItemsField::getKey() => [
                        'strategies' => [AssociatedItemsFieldStrategy::SPECIFIC_VALUES],
                        'specific_associated_items' => [
                            'itemtype' => [
                                Computer::getType(),
                                '0',
                            ],
                            'items_id' => [
                                $computer->getID(),
                            ],
                        ],
                    ],
                ],
            ],
            ["config"],
        );

        $destination = current($form->getDestinations());
        $concrete_destination = $destination->getConcreteDestinationItem();
        $this->assertInstanceOf(
            AbstractCommonITILFormDestination::class,
            $concrete_destination
        );

        /**
         * @var AbstractCommonITILFormDestination $concrete_destination
         * @var AssociatedItemsFieldConfig $config
         */
        $config = $concrete_destination->getConfigurableFieldByKey(
            AssociatedItemsField::getKey()
        )->getConfig($form, $destination->getConfig());

        $this->assertEquals(
            [
                Computer::getType() => [
                    $computer->getID(),
                ],
            ],
            $config->getSpecificAssociatedItems()
        );
    }

    private function sendFormAndAssertAssociatedItems(
        Form $form,
        AssociatedItemsFieldConfig $config,
        array $answers,
        array $expected_associated_items
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [AssociatedItemsField::getKey() => $config->jsonSerialize()]],
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

        // Check associated items
        $this->assertEquals(
            $expected_associated_items,
            $ticket->getLinkedItems(),
        );
    }

    private function createComputers(int $count): array
    {
        $computers = [];
        for ($i = 1; $i <= $count; $i++) {
            $computers[] = $this->createItem(Computer::class, ['name' => "Computer $i", 'entities_id' => 0]);
        }
        return $computers;
    }

    private function createMonitors(int $count): array
    {
        $monitors = [];
        for ($i = 1; $i <= $count; $i++) {
            $monitors[] = $this->createItem(Monitor::class, ['name' => "Monitor $i", 'entities_id' => 0]);
        }
        return $monitors;
    }

    private function createAndGetFormWithMultipleItemQuestions(): Form
    {
        $computer = $this->createItem(Computer::class, ['name' => "Computer 1", 'entities_id' => 0]);

        $builder = new FormBuilder();
        $builder->addQuestion("Your Computer", QuestionTypeUserDevice::class);
        $builder->addQuestion("Your Monitors", QuestionTypeUserDevice::class, null, json_encode([
            'is_multiple_devices' => true,
        ]));
        $builder->addQuestion("Computer", QuestionTypeItem::class, $computer->getID(), json_encode([
            'itemtype'             => Computer::getType(),
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));

        return $this->createForm($builder);
    }
}
