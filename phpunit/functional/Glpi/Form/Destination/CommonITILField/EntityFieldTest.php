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
use Entity;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\EntityFieldConfig;
use Glpi\Form\Destination\CommonITILField\EntityFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class EntityFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    private function getAnswers()
    {
        $entities = $this->createItems(Entity::class, [
            ['name' => 'Entity 1', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Entity 2', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Entity 3', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Entity 4', 'entities_id' => $this->getTestRootEntity(true)],
            ['name' => 'Entity 5', 'entities_id' => $this->getTestRootEntity(true)],
        ]);

        return [
            'answers' => [
                "Entity 1"    => [
                    'itemtype' => Entity::getType(),
                    'items_id' => $entities[0]->getId(),
                ],
                "Entity 2"    => [
                    'itemtype' => Entity::getType(),
                    'items_id' => $entities[1]->getId(),
                ]
            ],
            'entities' => $entities
        ];
    }

    public function testEntityFromForm()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers()['answers'];
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::FROM_FORM
            ),
            answers: $answers,
            expected_entity_id: $form->fields['entities_id']
        );
    }

    public function testEntityFromFormWithSpecificEntityId()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers()['answers'];

        // Change the entity to a new one
        $new_entity = $this->createItem(
            Entity::class,
            ['name' => 'New entity', 'entities_id' => $this->getTestRootEntity(true)]
        );
        $this->updateItem(
            $form::getType(),
            $form->getId(),
            ['entities_id' => $new_entity->getId()]
        );

        // Refresh object
        $form->getFromDB($form->getId());

        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::FROM_FORM
            ),
            answers: $answers,
            expected_entity_id: $new_entity->getId()
        );
    }

    public function testEntityFromSpecificValue()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::SPECIFIC_VALUE,
                specific_entity_id: $answers['entities'][2]->getId()
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][2]->getId()
        );
    }

    public function testEntityFromSpecificAnswer()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();

        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "Entity 2")
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][1]->getId()
        );
    }

    public function testEntityFromLastValidAnswer()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();

        // With multiple answers submitted
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::LAST_VALID_ANSWER
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][1]->getId()
        );
    }

    public function testEntityFromLastValidAnswerWithOnlyFirstAnswer()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();

        // Only first answer was submitted
        unset($answers['answers']["Entity 2"]);
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::LAST_VALID_ANSWER
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][0]->getId()
        );
    }

    public function testEntityFromLastValidAnswerWithOnlySecondAnswer()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();

        // Only second answer was submitted
        unset($answers['answers']["Entity 1"]);
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::LAST_VALID_ANSWER
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][1]->getId()
        );
    }

    public function testEntityFromLastValidAnswerWithNoAnswer()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();

        // No answers, fallback to default value db value
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::LAST_VALID_ANSWER
            ),
            answers: [],
            expected_entity_id: 0
        );
    }

    private function sendFormAndAssertTicketEntity(
        Form $form,
        EntityFieldConfig $config,
        array $answers,
        int $expected_entity_id,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['entity' => $config->jsonSerialize()]],
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

        // Check request type
        $this->assertEquals($expected_entity_id, $ticket->fields['entities_id']);
    }

    private function createAndGetFormWithMultipleEntityAndRequesterQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Entity 1", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Entity::getType(),
        ]));
        $builder->addQuestion("Entity 2", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Entity::getType(),
        ]));
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
