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
use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\AssigneeFieldConfig;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Supplier;
use Ticket;
use TicketTemplate;
use TicketTemplatePredefinedField;
use User;

final class AssigneeFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testAssigneeFromTemplate(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $from_template_config = new AssigneeFieldConfig(
            [ITILActorFieldStrategy::FROM_TEMPLATE]
        );

        // The default GLPI's template doesn't have a predefined value
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: []
        );

        $user = $this->createItem(User::class, ['name' => 'testAssigneeFromTemplate User']);
        $group = $this->createItem(Group::class, ['name' => 'testAssigneeFromTemplate Group']);
        $supplier = $this->createItem(Supplier::class, [
            'name' => 'testAssigneeFromTemplate Supplier',
            'entities_id' => $this->getTestRootEntity(true)
        ]);

        // Set the user as default assignee using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 5, // User assignee
            'value' => $user->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: [$user->getID()]
        );

        // Set the group as default assignee using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 8, // Group assignee
            'value' => $group->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID()]
        );

        // Set the supplier as default assignee using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 6, // Supplier assignee
            'value' => $supplier->getID(),
        ]);
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $from_template_config,
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID(), $supplier->getID()]
        );
    }

    public function testAssigneeFormFiller(): void
    {
        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $form_filler_config = new AssigneeFieldConfig(
            [ITILActorFieldStrategy::FORM_FILLER]
        );

        // The default GLPI's template doesn't have a predefined value
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

    public function testSpecificActors(): void
    {
        // Login is required to assign actors
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $user = $this->createItem(User::class, ['name' => 'testSpecificActors User']);
        $group = $this->createItem(Group::class, ['name' => 'testSpecificActors Group']);
        $supplier = $this->createItem(Supplier::class, [
            'name' => 'testSpecificActors Supplier',
            'entities_id' => $this->getTestRootEntity(true)
        ]);

        // Specific value: User
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new AssigneeFieldConfig(
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
            config: new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID()]
        );

        // Specific value: User, Group and Supplier
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user->getID(), $group->getID(), $supplier->getID()]
        );
    }

    public function testActorsFromSpecificQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $technician_profiles_id = getItemByTypeName('Profile', 'Technician', true);
        $user1 = $this->createItem(User::class, [
            'name' => 'testLocationFromSpecificQuestions User',
            '_profiles_id' => $technician_profiles_id
        ]);
        $user2 = $this->createItem(User::class, [
            'name' => 'testLocationFromSpecificQuestions User 2',
            '_profiles_id' => $technician_profiles_id
        ]);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);
        $supplier = $this->createItem(Supplier::class, [
            'name' => 'testLocationFromSpecificQuestions Supplier',
            'entities_id' => $this->getTestRootEntity(true)
        ]);

        // Using answer from first question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$this->getQuestionId($form, "Assignee 1")]
            ),
            answers: [
                "Assignee 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Assignee 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID(),
                ],
            ],
            expected_actors_ids: [$user1->getID()]
        );

        // Using answer from first and second question
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [
                    $this->getQuestionId($form, "Assignee 1"),
                    $this->getQuestionId($form, "Assignee 2")
                ]
            ),
            answers: [
                "Assignee 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Assignee 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID(),
                ],
            ],
            expected_actors_ids: [$user1->getID(), $user2->getID(), $group->getID(), $supplier->getID()]
        );
    }

    public function testActorsFromLastValidQuestion(): void
    {
        // Login is required to assign actors
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $last_valid_answer_config = new AssigneeFieldConfig(
            [ITILActorFieldStrategy::LAST_VALID_ANSWER]
        );

        $technician_profiles_id = getItemByTypeName('Profile', 'Technician', true);
        $user1 = $this->createItem(User::class, [
            'name' => 'testLocationFromSpecificQuestions User',
            '_profiles_id' => $technician_profiles_id
        ]);
        $user2 = $this->createItem(User::class, ['name' => 'testLocationFromSpecificQuestions User 2',
            '_profiles_id' => $technician_profiles_id
        ]);
        $group = $this->createItem(Group::class, ['name' => 'testLocationFromSpecificQuestions Group']);
        $supplier = $this->createItem(Supplier::class, [
            'name' => 'testLocationFromSpecificQuestions Supplier',
            'entities_id' => $this->getTestRootEntity(true)
        ]);

        // With multiple answers submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Assignee 1" => [
                    User::getForeignKeyField() . '-' . $user1->getID(),
                ],
                "Assignee 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID(),
                ],
            ],
            expected_actors_ids: [$user2->getID(), $group->getID(), $supplier->getID()]
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Assignee 1" => [
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
                "Assignee 2" => [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID(),
                ],
            ],
            expected_actors_ids: [$user2->getID(), $group->getID(), $supplier->getID()]
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
            'num' => 5, // User assignee
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
        $supplier = $this->createItem(Supplier::class, [
            'name' => 'testMultipleStrategies Supplier',
            'entities_id' => $this->getTestRootEntity(true)
        ]);

        // Set the user as default assignee using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 5, // User assignee
            'value' => $user1->getID(),
        ]);

        // Multiple strategies: FROM_TEMPLATE and SPECIFIC_VALUES
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::FROM_TEMPLATE, ITILActorFieldStrategy::SPECIFIC_VALUES],
                specific_itilactors_ids: [
                    User::getForeignKeyField() . '-' . $user2->getID(),
                    Group::getForeignKeyField() . '-' . $group->getID(),
                    Supplier::getForeignKeyField() . '-' . $supplier->getID()
                ]
            ),
            answers: [],
            expected_actors_ids: [$user1->getID(), $user2->getID(), $group->getID(), $supplier->getID()]
        );
    }

    private function sendFormAndAssertTicketActors(
        Form $form,
        AssigneeFieldConfig $config,
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
            ['config' => [(new AssigneeField())->getKey() => $config->jsonSerialize()]],
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

        $a = $ticket->getActorsForType(CommonITILActor::ASSIGN);

        // Check actors
        $this->assertEquals(
            array_map(fn(array $actor) => $actor['items_id'], $ticket->getActorsForType(CommonITILActor::ASSIGN)),
            $expected_actors_ids
        );
    }

    private function createAndGetFormWithMultipleActorsQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Assignee 1", QuestionTypeAssignee::class, '');
        $builder->addQuestion(
            "Assignee 2",
            QuestionTypeAssignee::class,
            '',
            json_encode((new QuestionTypeActorsExtraDataConfig(true))->jsonSerialize())
        );
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
