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

use Entity;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\EntityField;
use Glpi\Form\Destination\CommonITILField\EntityFieldConfig;
use Glpi\Form\Destination\CommonITILField\EntityFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Tests\AbstractDestinationFieldTest;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use Profile_User;
use User;

use function Safe\json_encode;

final class EntityFieldTest extends AbstractDestinationFieldTest
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
                    'itemtype' => Entity::class,
                    'items_id' => $entities[0]->getId(),
                ],
                "Entity 2"    => [
                    'itemtype' => Entity::class,
                    'items_id' => $entities[1]->getId(),
                ],
            ],
            'entities' => $entities,
        ];
    }

    public function testEntityFormFiller()
    {
        $form = $this->createAndGetFormWithMultipleEntityAndRequesterQuestions();
        $answers = $this->getAnswers();
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::FORM_FILLER
            ),
            answers: $answers['answers'],
            expected_entity_id: $this->getTestRootEntity(true)
        );

        $this->setEntity($answers['entities'][0]->getName(), false);
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::FORM_FILLER
            ),
            answers: $answers['answers'],
            expected_entity_id: $answers['entities'][0]->getId()
        );
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
            $form::class,
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

        // No answers, fallback to current entity
        $this->sendFormAndAssertTicketEntity(
            form: $form,
            config: new EntityFieldConfig(
                EntityFieldStrategy::LAST_VALID_ANSWER
            ),
            answers: [],
            expected_entity_id: $this->getTestRootEntity(only_id: true)
        );
    }

    #[Override]
    public static function provideConvertFieldConfigFromFormCreator(): iterable
    {
        yield 'Current active entity strategy' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 1, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_CURRENT
            ],
            'field_config' => new EntityFieldConfig(
                EntityFieldStrategy::FORM_FILLER
            ),
        ];

        yield 'Default requester user\'s entity' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 2, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_REQUESTER
            ],
            'field_config' => new EntityFieldConfig(
                EntityFieldStrategy::FORM_FILLER
            ),
        ];

        yield 'First dynamic requester user\'s entity (alphabetical)' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 3, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_REQUESTER_DYN_FIRST
            ],
            'field_config' => fn($migration, $form) => (new EntityField())->getDefaultConfig($form),
        ];

        yield 'Last dynamic requester user\'s entity (alphabetical)' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 4, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_REQUESTER_DYN_LAST
            ],
            'field_config' => fn($migration, $form) => (new EntityField())->getDefaultConfig($form),
        ];

        yield 'The form entity' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 5, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_FORM
            ],
            'field_config' => new EntityFieldConfig(
                EntityFieldStrategy::FROM_FORM
            ),
        ];

        yield 'Default entity of the validator' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 6, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_VALIDATOR
            ],
            'field_config' => fn($migration, $form) => (new EntityField())->getDefaultConfig($form),
        ];

        yield 'Specific entity' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity'       => 7, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_SPECIFIC
                'destination_entity_value' => getItemByTypeName(Entity::class, '_test_root_entity', true),
            ],
            'field_config' => new EntityFieldConfig(
                strategy: EntityFieldStrategy::SPECIFIC_VALUE,
                specific_entity_id: getItemByTypeName(Entity::class, '_test_root_entity', true)
            ),
        ];

        yield 'Default entity of a user type question answer' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity' => 8, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_USER
            ],
            'field_config' => fn($migration, $form) => (new EntityField())->getDefaultConfig($form),
        ];

        yield 'From a GLPI object > Entity type question answer' => [
            'field_key'     => EntityField::getKey(),
            'fields_to_set' => [
                'destination_entity'       => 9, // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_ENTITY_FROM_OBJECT
                'destination_entity_value' => 71, // Question ID
            ],
            'field_config' => fn($migration, $form) => new EntityFieldConfig(
                strategy: EntityFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $migration->getMappedItemTarget('PluginFormcreatorQuestion', 71)['items_id'] ?? throw new \Exception("Question not found")
            ),
        ];
    }

    public static function entityFromRequesterProvider(): iterable
    {
        $admin_profile       = getItemByTypeName(Profile::class, "Super-Admin", true);
        $selfservice_profile = getItemByTypeName(Profile::class, "Self-Service", true);
        $helpdesk_profile    = getItemByTypeName(Profile::class, "helpdesk", true);

        yield 'user with single profile' => [
            'profiles' => [
                ['profiles_id' => $admin_profile, 'entities_id' => "_test_child_3"],
            ],
            // The only found profile is used
            'expected_entity' => "_test_child_3",
        ];

        yield 'users with two profiles, one is helpdesk' => [
            'profiles' => [
                ['profiles_id' => $admin_profile, 'entities_id' => "_test_child_3"],
                ['profiles_id' => $selfservice_profile, 'entities_id' => "_test_child_2"],
            ],
            // First helpdesk profile is used
            'expected_entity' => "_test_child_2",
        ];

        yield 'users with two helpdesk profiles' => [
            'profiles' => [
                ['profiles_id' => $selfservice_profile, 'entities_id' => "_test_child_3"],
                ['profiles_id' => $selfservice_profile, 'entities_id' => "_test_child_2"],
            ],
            // First profile is used
            'expected_entity' => "_test_child_3",
        ];

        yield 'users with two helpdesk profiles, one being the default' => [
            'profiles' => [
                ['profiles_id' => $helpdesk_profile, 'entities_id' => "_test_child_3"],
                ['profiles_id' => $selfservice_profile, 'entities_id' => "_test_child_2"],
            ],
            // Default profile is used
            'expected_entity' => "_test_child_2",
        ];

        yield 'users with two central profiles' => [
            'profiles' => [
                ['profiles_id' => $admin_profile, 'entities_id' => "_test_child_1"],
                ['profiles_id' => $admin_profile, 'entities_id' => "_test_child_2"],
            ],
            // First profile is used
            'expected_entity' => "_test_child_1",
        ];

        // No case for the default profile as we only have one helpdesk profile
        // in our test dataset and we cant create another in a provider
    }

    #[DataProvider('entityFromRequesterProvider')]
    public function testEntityFromRequester(
        array $profiles,
        string $expected_entity,
    ): void {
        // Arrange: create a form with an actor question used as the requester
        $builder = new FormBuilder();
        $builder->addQuestion("Requester", QuestionTypeRequester::class);
        $form = $this->createForm($builder);

        // The form requester will be picked from the "Requester" question
        $requester_config = new RequesterFieldConfig(
            strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
            specific_question_ids: [$this->getQuestionId($form, "Requester")]
        );
        $this->setDestinationFieldConfig(
            form: $form,
            key: RequesterField::getKey(),
            config: $requester_config,
        );

        // The entity will be taken from the requester
        $form = Form::getById($form->getId());
        $entity_config = new EntityFieldConfig(
            strategy: EntityFieldStrategy::REQUESTER_ENTITY,
        );
        $this->setDestinationFieldConfig(
            form: $form,
            key: EntityField::getKey(),
            config: $entity_config,
        );

        // Create an user to use as a requester
        $default_profile = array_shift($profiles);
        $user = $this->createItem(User::class, [
            'name' => 'My_user',
            '_profiles_id'  => $default_profile['profiles_id'],
            '_entities_id'  => getItemByTypeName(
                Entity::class,
                $default_profile['entities_id'],
                true,
            ),
            '_is_recursive' => true,
        ]);

        // Add others profiles if needed
        foreach ($profiles as $profile) {
            $this->createItem(Profile_User::class, [
                'users_id'     => $user->getID(),
                'profiles_id'  => $profile['profiles_id'],
                'entities_id'  => getItemByTypeName(
                    Entity::class,
                    $profile['entities_id'],
                    true,
                ),
                'is_recursive' => true,
            ]);
        }

        // Act: submit an answer to the form
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Requester" => "users_id-{$user->getID()}",
        ]);

        // Assert: the ticket entity should be "_test_child_3"
        $this->assertEquals(
            getItemByTypeName(Entity::class, $expected_entity, true),
            $ticket->fields['entities_id'],
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
            $destination::class,
            $destination->getId(),
            ['config' => [EntityField::getKey() => $config->jsonSerialize()]],
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
        $ticket = current($created_items);

        // Check request type
        $this->assertEquals($expected_entity_id, $ticket->fields['entities_id']);
    }

    private function createAndGetFormWithMultipleEntityAndRequesterQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Entity 1", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Entity::class,
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));
        $builder->addQuestion("Entity 2", QuestionTypeItem::class, 0, json_encode([
            'itemtype' => Entity::class,
            'root_items_id'        => 0,
            'subtree_depth'        => 0,
            'selectable_tree_root' => false,
        ]));
        return $this->createForm($builder);
    }
}
