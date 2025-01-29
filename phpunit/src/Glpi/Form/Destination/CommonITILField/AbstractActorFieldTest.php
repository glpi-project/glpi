<?php

namespace Glpi\PHPUnit\Tests\Glpi\Form\Destination\CommonITILField;

use Computer;
use DbTestCase;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use User;

abstract class AbstractActorFieldTest extends DbTestCase
{
    use FormTesterTrait;

    abstract protected function sendFormAndAssertTicketActors(
        Form $form,
        ITILActorFieldConfig $config,
        array $answers,
        array $expected_actors_ids
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
                '_users_id_tech' => [$users[0]->getID()]
            ],
            [
                'name' => 'testUserActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_users_id' => [$users[1]->getID()]
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors_ids: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_actors_ids: []
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ]
            ],
            expected_actors_ids: []
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
                '_users_id_tech' => [$users[0]->getID()]
            ],
            [
                'name' => 'testTechUserActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_users_id' => [$users[1]->getID()]
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors_ids: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_actors_ids: [$users[0]->getID()]
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ]
            ],
            expected_actors_ids: []
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
                '_groups_id' => [$groups[0]->getID()]
            ],
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[1]->getID()]
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors_ids: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_actors_ids: [$groups[0]->getID()]
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ]
            ],
            expected_actors_ids: []
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
                '_groups_id' => [$groups[0]->getID()]
            ],
            [
                'name' => 'testGroupActorsFromSpecificItemQuestions Computer 2',
                'entities_id' => $this->getTestRootEntity(true),
                '_groups_id_tech' => [$groups[1]->getID()]
            ],
        ]);

        // No answers
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [],
            expected_actors_ids: []
        );

        // Answer with first computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[0]->getID(),
                ]
            ],
            expected_actors_ids: []
        );

        // Answer with second computer
        $this->sendFormAndAssertTicketActors(
            form: $form,
            config: $config,
            answers: [
                'Computer question' => [
                    'itemtype' => Computer::class,
                    'items_id' => $computers[1]->getID(),
                ]
            ],
            expected_actors_ids: [$groups[1]->getID()]
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
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );

        return $this->createForm($builder);
    }
}
