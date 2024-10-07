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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\ValidationField;
use Glpi\Form\Destination\CommonITILField\ValidationFieldConfig;
use Glpi\Form\Destination\CommonITILField\ValidationFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Group_User;
use User;

final class ValidationFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testValidationNoValidation(): void
    {
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $users = $this->createAndGetUserActors();
        $groups = $this->createAndGetGroupActors();

        // No validation with no answers and no specific actors
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::NO_VALIDATION
            ),
            answers: [],
            expected_validations: [],
            keys_to_be_considered: []
        );

        // No validation with no answers and specific actors
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::NO_VALIDATION,
                specific_actors: [
                    'users_id-' . $users[0]->getID(),
                    'users_id-' . $users[1]->getID(),
                    'groups_id-' . $groups[0]->getID(),
                ]
            ),
            answers: [],
            expected_validations: [],
            keys_to_be_considered: []
        );

        // No validation with answers and no specific actors
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::NO_VALIDATION
            ),
            answers: [
                "Assignee" => Group::getForeignKeyField() . '-' . $groups[0]->getID(),
                "GLPI User" => [
                    'itemtype' => User::class,
                    'items_id' => $users[3]->getID(),
                ],
            ],
            expected_validations: [],
            keys_to_be_considered: []
        );

        // No validation with answers and specific actors
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::NO_VALIDATION,
                specific_actors: [
                    'users_id-' . $users[0]->getID(),
                    'users_id-' . $users[1]->getID(),
                    'groups_id-' . $groups[0]->getID(),
                ]
            ),
            answers: [
                "Assignee" => User::getForeignKeyField() . '-' . $users[2]->getID(),
                "GLPI User" => [
                    'itemtype' => User::class,
                    'items_id' => $users[3]->getID(),
                ],
            ],
            expected_validations: [],
            keys_to_be_considered: []
        );
    }

    public function testValidationSpecificActors(): void
    {
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $users = $this->createAndGetUserActors();
        $groups = $this->createAndGetGroupActors();

        // With no answers
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::SPECIFIC_ACTORS,
                specific_actors: [
                    'users_id-' . $users[0]->getID(),
                    'users_id-' . $users[1]->getID(),
                    'groups_id-' . $groups[0]->getID(),
                ]
            ),
            answers: [],
            expected_validations: [
                [
                    'itemtype_target' => 'User',
                    'items_id_target' => $users[0]->getID(),
                ],
                [
                    'itemtype_target' => 'User',
                    'items_id_target' => $users[1]->getID(),
                ],
                [
                    'itemtype_target' => 'Group',
                    'items_id_target' => $groups[0]->getID(),
                ],
            ],
            keys_to_be_considered: ['itemtype_target', 'items_id_target']
        );

        // With answers
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::SPECIFIC_ACTORS,
                specific_actors: [
                    'users_id-' . $users[0]->getID(),
                    'users_id-' . $users[1]->getID(),
                    'groups_id-' . $groups[0]->getID(),
                ]
            ),
            answers: [
                "Assignee" => Group::getForeignKeyField() . '-' . $groups[1]->getID(),
                "GLPI User" => [
                    'itemtype' => User::class,
                    'items_id' => $users[3]->getID(),
                ],
            ],
            expected_validations: [
                [
                    'itemtype_target' => 'User',
                    'items_id_target' => $users[0]->getID(),
                ],
                [
                    'itemtype_target' => 'User',
                    'items_id_target' => $users[1]->getID(),
                ],
                [
                    'itemtype_target' => 'Group',
                    'items_id_target' => $groups[0]->getID(),
                ],
            ],
            keys_to_be_considered: ['itemtype_target', 'items_id_target']
        );
    }

    public function testValidationFromSpecificQuestions(): void
    {
        $this->login();

        $form = $this->createAndGetFormWithMultipleActorsQuestions();
        $users = $this->createAndGetUserActors();
        $groups = $this->createAndGetGroupActors();

        // Using answer from first question
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::SPECIFIC_ANSWERS,
                specific_question_ids: [$this->getQuestionId($form, "Assignee")]
            ),
            answers: [
                "Assignee" => Group::getForeignKeyField() . '-' . $groups[0]->getID(),
                "GLPI User" => [
                    'itemtype' => User::class,
                    'items_id' => $users[1]->getID(),
                ],
            ],
            expected_validations: [
                [
                    'itemtype_target' => 'Group',
                    'items_id_target' => $groups[0]->getID(),
                ],
            ],
            keys_to_be_considered: ['itemtype_target', 'items_id_target']
        );

        // Using answer from second question
        $this->sendFormAndAssertValidations(
            form: $form,
            config: new ValidationFieldConfig(
                strategy: ValidationFieldStrategy::SPECIFIC_ANSWERS,
                specific_question_ids: [$this->getQuestionId($form, "GLPI User")]
            ),
            answers: [
                "Assignee" => Group::getForeignKeyField() . '-' . $users[0]->getID(),
                "GLPI User" => [
                    'itemtype' => User::class,
                    'items_id' => $users[1]->getID(),
                ],
            ],
            expected_validations: [
                [
                    'itemtype_target' => 'User',
                    'items_id_target' => $users[1]->getID(),
                ],
            ],
            keys_to_be_considered: ['itemtype_target', 'items_id_target']
        );
    }

    private function sendFormAndAssertValidations(
        Form $form,
        ValidationFieldConfig $config,
        array $answers,
        array $expected_validations,
        array $keys_to_be_considered
    ): void {
        $field = new ValidationField();

        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [$field->getKey() => $config->jsonSerialize()]],
            ["config"],
        );

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            // Real answer will be decoded as string by default
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

        // Check validations
        $this->assertEquals(
            countElementsInTable(
                \TicketValidation::getTable(),
                ['tickets_id' => $ticket->fields['id']]
            ),
            count($expected_validations)
        );

        $validation = new \TicketValidation();
        $validations = $validation->find([
            'tickets_id' => $ticket->getID(),
        ]);
        $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
            $expected_validations,
            $validations,
            $keys_to_be_considered
        );
    }

    private function createAndGetFormWithMultipleActorsQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Assignee", QuestionTypeAssignee::class);
        $builder->addQuestion("GLPI User", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => User::class,
        ]));
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }

    private function createAndGetUserActors(): array
    {
        $users = $this->createItems(
            User::class,
            [
                ['name' => 'ValidationFieldTest User 1', 'entities_id' => $this->getTestRootEntity()->getID()],
                ['name' => 'ValidationFieldTest User 2', 'entities_id' => $this->getTestRootEntity()->getID()],
                ['name' => 'ValidationFieldTest User 3', 'entities_id' => $this->getTestRootEntity()->getID()],
                ['name' => 'ValidationFieldTest User 4', 'entities_id' => $this->getTestRootEntity()->getID()],
            ]
        );

        return $users;
    }

    private function createAndGetGroupActors(): array
    {
        $groups = $this->createItems(
            Group::class,
            [
                ['name' => 'ValidationFieldTest Group 1', 'entities_id' => $this->getTestRootEntity()->getID()],
                ['name' => 'ValidationFieldTest Group 2', 'entities_id' => $this->getTestRootEntity()->getID()],
            ]
        );

        $users_for_groups = $this->createItems(
            User::class,
            [
                [
                    'name' => 'ValidationFieldTest User for Group 1',
                    'entities_id' => $this->getTestRootEntity()->getID()
                ],
                [
                    'name' => 'ValidationFieldTest User for Group 2',
                    'entities_id' => $this->getTestRootEntity()->getID()
                ],
            ]
        );

        $this->createItems(
            Group_User::class,
            [
                ['users_id' => $users_for_groups[0]->getID(), 'groups_id' => $groups[0]->getID()],
                ['users_id' => $users_for_groups[1]->getID(), 'groups_id' => $groups[1]->getID()],
            ]
        );

        return $groups;
    }
}
