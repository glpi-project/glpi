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

namespace Glpi\Tests;

use Computer;
use Glpi\Asset\AssetDefinition;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Group;
use User;

abstract class AbstractActorFieldTest extends AbstractDestinationFieldTest
{
    use FormTesterTrait;

    abstract protected function sendFormAndAssertTicketActors(
        Form $form,
        ITILActorFieldConfig $config,
        array $answers,
        array $expected_actors,
    );

    abstract public function getFieldClass(): string;

    public function testUserActorsFromSpecificItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();

        $form = $this->createAndGetFormWithItemQuestions();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::USER_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, "Computer question")]
        );
        $users = $this->createItems(User::class, [
            ['name' => 'testUserActorsFromSpecificItemQuestions User 1'],
            ['name' => 'testUserActorsFromSpecificItemQuestions User 2'],
        ]);
        $computers = $this->createItems(Computer::class, [
            [
                'name' => 'testUserActorsFromSpecificItemQuestions Computer 1',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $users[0]->getID(),
            ],
            [
                'name' => 'testUserActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id' => $users[1]->getID(),
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_actors: []
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $users[1]->getID()]]
        );
    }

    public function testTechUserActorsFromSpecificItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();

        $form = $this->createAndGetFormWithItemQuestions();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::TECH_USER_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, "Computer question")]
        );
        $users = $this->createItems(User::class, [
            ['name' => 'testTechUserActorsFromSpecificItemQuestions User 1'],
            ['name' => 'testTechUserActorsFromSpecificItemQuestions User 2'],
        ]);
        $computers = $this->createItems(Computer::class, [
            [
                'name' => 'testTechUserActorsFromSpecificItemQuestions Computer 1',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $users[0]->getID(),
            ],
            [
                'name' => 'testTechUserActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id' => $users[1]->getID(),
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $users[0]->getID()]]
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_actors: []
        );
    }

    public function testGroupActorsFromSpecificItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();

        $form = $this->createAndGetFormWithItemQuestions();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::GROUP_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, "Computer question")]
        );
        $groups = $this->createItems(Group::class, [
            ['name' => 'testGroupActorsFromSpecificItemQuestions Group 1'],
            ['name' => 'testGroupActorsFromSpecificItemQuestions Group 2'],
        ]);
        $computers = $this->createItems(Computer::class, [
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 1',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id' => [$groups[0]->getID()],
            ],
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[1]->getID()],
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $groups[0]->getID()]]
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_actors: []
        );
    }

    public function testTechGroupActorsFromSpecificItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();

        $form = $this->createAndGetFormWithItemQuestions();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::TECH_GROUP_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, 'Computer question')]
        );
        $groups = $this->createItems(Group::class, [
            ['name' => 'testGroupActorsFromSpecificItemQuestions Group 1'],
            ['name' => 'testGroupActorsFromSpecificItemQuestions Group 2'],
        ]);
        $computers = $this->createItems(Computer::class, [
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 1',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id' => [$groups[0]->getID()],
            ],
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[1]->getID()],
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ],
            ],
            expected_actors: []
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $groups[1]->getID()]]
        );
    }

    public function testTechUserActorsFromCustomAssetItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        // Create a custom asset definition with tech user field enabled
        $definition = $this->initAssetDefinition();
        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            [
                'fields_display' => ['name', 'users_id_tech', 'groups_id_tech'],
            ],
            ['fields_display']
        );
        $definition->getFromDB($definition->getID());

        $asset_class = $definition->getAssetClassName();

        // Create form with custom asset question
        $form = $this->createAndGetFormWithCustomAssetQuestion($asset_class);

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::TECH_USER_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, "Custom asset question")]
        );

        $users = $this->createItems(User::class, [
            ['name' => 'testTechUserActorsFromCustomAssetItemQuestions User 1'],
            ['name' => 'testTechUserActorsFromCustomAssetItemQuestions User 2'],
        ]);

        $assets = $this->createItems($asset_class, [
            [
                'name' => 'testTechUserActorsFromCustomAssetItemQuestions Asset 1',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $users[0]->getID(),
            ],
            [
                'name' => 'testTechUserActorsFromCustomAssetItemQuestions Asset 2',
                'entities_id' => $this->getTestRootEntity(true),
                'users_id_tech' => $users[1]->getID(),
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first custom asset
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Custom asset question' => [
                    'itemtype' => $asset_class,
                    'items_id' => $assets[0]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $users[0]->getID()]]
        );

        // Answer with second custom asset
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Custom asset question' => [
                    'itemtype' => $asset_class,
                    'items_id' => $assets[1]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $users[1]->getID()]]
        );
    }

    public function testTechGroupActorsFromCustomAssetItemQuestions(): void
    {
        // Login is required to assign actors
        $this->login();

        // Create a custom asset definition with tech group field enabled
        $definition = $this->initAssetDefinition();
        $this->updateItem(
            AssetDefinition::class,
            $definition->getID(),
            [
                'fields_display' => ['name', 'users_id_tech', 'groups_id_tech'],
            ],
            ['fields_display']
        );
        $definition->getFromDB($definition->getID());

        $asset_class = $definition->getAssetClassName();

        // Create form with custom asset question
        $form = $this->createAndGetFormWithCustomAssetQuestion($asset_class);

        $field_inst = new ($this->getFieldClass())();
        $config_class = $field_inst->getConfigClass();
        $config = new $config_class(
            strategies: [ITILActorFieldStrategy::TECH_GROUP_FROM_OBJECT_ANSWER],
            specific_question_ids: [$this->getQuestionId($form, "Custom asset question")]
        );

        $groups = $this->createItems(Group::class, [
            ['name' => 'testTechGroupActorsFromCustomAssetItemQuestions Group 1'],
            ['name' => 'testTechGroupActorsFromCustomAssetItemQuestions Group 2'],
        ]);

        $assets = $this->createItems($asset_class, [
            [
                'name' => 'testTechGroupActorsFromCustomAssetItemQuestions Asset 1',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[0]->getID()],
            ],
            [
                'name' => 'testTechGroupActorsFromCustomAssetItemQuestions Asset 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[1]->getID()],
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors: []
        );

        // Answer with first custom asset
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Custom asset question' => [
                    'itemtype' => $asset_class,
                    'items_id' => $assets[0]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $groups[0]->getID()]]
        );

        // Answer with second custom asset
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Custom asset question' => [
                    'itemtype' => $asset_class,
                    'items_id' => $assets[1]->getID(),
                ],
            ],
            expected_actors: [['items_id' => $groups[1]->getID()]]
        );
    }

    private function createAndGetFormWithItemQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            'Computer question',
            QuestionTypeItem::class,
            '',
            json_encode((new QuestionTypeItemExtraDataConfig(Computer::class))->jsonSerialize())
        );

        return $this->createForm($builder);
    }

    private function createAndGetFormWithCustomAssetQuestion(string $asset_class): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            'Custom asset question',
            QuestionTypeItem::class,
            '',
            json_encode((new QuestionTypeItemExtraDataConfig($asset_class))->jsonSerialize())
        );

        return $this->createForm($builder);
    }
}
