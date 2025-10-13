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

namespace tests\units\Glpi\Form;

use CronTask;
use DbTestCase;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Comment;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeCheckbox;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\Section;
use Glpi\Helpdesk\Tile\FormTile;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Item_Ticket;
use Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Uuid\Uuid;
use User;

class FormTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the showForm method
     *
     * Note: the HTML content itself is not verified here as it would be too
     * complex.
     * It should be verified using a separate E2E test instead.
     * Any error while rendering the tab will still be caught by this tests so
     * we must try to send a vey complex form.
     *
     * @return void
     */
    public function testShowForm(): void
    {
        $this->login();
        $types_manager = QuestionTypesManager::getInstance();

        // Create a form with each possible types of questions and multiple sections
        $form_builder = new FormBuilder();
        $i = 1;

        // Make sure we have at least 50 questions
        $questions = [];
        do {
            $questions = array_merge(
                $questions,
                // Remove keys to make sure new values are added at the end
                array_values($types_manager->getQuestionTypes())
            );
        } while (count($questions) < 50);

        // Extra data for some question types
        $extra_datas = [
            QuestionTypeRadio::class => [
                'options' => [
                    123 => 'Radio 1',
                ],
            ],
            QuestionTypeCheckbox::class => [
                'options' => [
                    123 => 'Checkbox 1',
                ],
            ],
            QuestionTypeDropdown::class => [
                'options' => [
                    123 => 'Dropdown 1',
                ],
            ],
            QuestionTypeItem::class => [
                'itemtype'             => 'Computer',
                'root_items_id'        => 0,
                'subtree_depth'        => 0,
                'selectable_tree_root' => true,
            ],
            QuestionTypeItemDropdown::class => [
                'itemtype'             => 'Location',
                'categories_filter'    => [],
                'root_items_id'        => 0,
                'subtree_depth'        => 0,
                'selectable_tree_root' => true,
            ],
        ];

        foreach ($questions as $type) {
            $form_builder->addQuestion(
                name: "Question $i",
                type: $type::class,
                extra_data: isset($extra_datas[$type::class]) ? json_encode($extra_datas[$type::class]) : "",
                description: $i % 4 === 0 ? "Description of question $i" : "", // Add a description every 4 questions
                is_mandatory: $i % 2 === 0, // Half of the questions are mandatory
            );

            // Add a section every 10 questions
            if ($i % 10 === 0) {
                $form_builder->addSection("Section " . ($i / 10));
            }

            // Add a comment every 5 questions
            if ($i % 5 === 0) {
                $form_builder->addComment(
                    name: "Comment $i",
                    description: "Description of comment $i",
                );
            }
        }

        $form = $this->createForm($form_builder);

        // Render form
        ob_start();
        $this->assertTrue($form->showForm($form->getID()));
        ob_end_clean();
    }

    /**
     * Indirectly test the test the post_getFromDB method
     *
     * @return void
     */
    public function testPost_getFromDB(): void
    {
        $this->login();

        // First test: make sure lazy loaded data is cleared when a form is loaded
        $form = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addComment('Comment 1', 'Comment 1 description')
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addSection('Section 2')
                ->addQuestion('Question 3', QuestionTypeShortText::class)
                ->addComment('Comment 2', 'Comment 2 description')
        );
        $this->assertCount(2, $form->getSections());
        $this->assertCount(3, $form->getQuestions());
        $this->assertCount(2, $form->getFormComments());

        // Delete content
        foreach ($form->getSections() as $section) {
            foreach ($section->getQuestions() as $question) {
                $this->deleteItem(Question::class, $question->getID());
            }
            foreach ($section->getFormComments() as $comment) {
                $this->deleteItem(Comment::class, $comment->getID());
            }
            $this->deleteItem(Section::class, $section->getID());
        }

        // Until the form is reloaded, its internal sections and questions data
        // shouldn't change
        $this->assertCount(2, $form->getSections());
        $this->assertCount(3, $form->getQuestions());
        $this->assertCount(2, $form->getFormComments());

        // Reload form
        $form->getFromDB($form->getID());
        $this->assertCount(0, $form->getSections());
        $this->assertCount(0, $form->getQuestions());
        $this->assertCount(0, $form->getFormComments());
    }

    /**
     * Indirectly test the post_addItem method by adding a form
     *
     * @return void
     */
    public function testPost_addItem(): void
    {
        $this->login();
        $entity = $this->getTestRootEntity();

        // Ensure the first section is created unless we explicitly specify to
        // not create it
        $form_with_section = $this->createItem(Form::class, [
            'name'        => 'Form with first section',
            'entities_id' => $entity->getID(),
        ]);
        $this->assertCount(1, $form_with_section->getSections());

        $form_without_section = $this->createItem(Form::class, [
            'name'           => 'Form without first section',
            'entities_id'    => $entity->getID(),
            '_init_sections' => false,
        ]);
        $this->assertCount(0, $form_without_section->getSections());
    }

    /**
     * Indirectly test the prepareInputForUpdate method by updating a form
     *
     * @return void
     */
    public function testPrepareInputForUpdate(): void
    {
        $this->login();
        $entity = $this->getTestRootEntity();

        // Simulate that form was created a minute ago, as we need the current time
        // to change for this test to be effective
        $old_time = $_SESSION['glpi_currenttime'];
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s', strtotime('-1 minute', strtotime($old_time)));
        $form = $this->createItem(Form::class, [
            'name'        => 'Form with first section',
            'entities_id' => $entity->getID(),
        ]);
        $_SESSION['glpi_currenttime'] = $old_time;

        // Get base section
        $sections = $form->getSections();
        $section = array_pop($sections);

        // An form update that doesn't change any of the form fields themselves
        // should still be considered a valid update
        $form = $this->updateItem($form::class, $form->getID(), [
            '_update' => true, // Needed to get confirmation message
            '_questions' => [
                [
                    'uuid'                  => Uuid::uuid4(),
                    'forms_sections_uuid' => $section->fields['uuid'],
                    'name'                => 'Question name',
                    'type'                => QuestionTypeShortText::class,
                ],
            ],
            '_comments' => [
                [
                    'uuid'                  => Uuid::uuid4(),
                    'forms_sections_uuid' => $section->fields['uuid'],
                    'name'                => 'Comment name',
                    'description'         => 'Comment description',
                ],
            ],
        ]);
        $this->assertCount(1, $form->getQuestions());
        $this->assertCount(1, $form->getFormComments());

        $id = $form->getID();
        $this->hasSessionMessages(INFO, ['Item successfully updated: <a href="/front/form/form.form.php?id=' . $id . '" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Form with first section">Form with first section</a>']);
    }

    public function testGetSectionsOnEmptyForm(): void
    {
        $form = $this->createForm(new FormBuilder());
        $this->checkGetSections($form, []);
    }

    public function testGetSectionsOnFormWithFourSections(): void
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addSection('Section 2')
                ->addSection('Section 3')
                ->addSection('Section 4')
        );
        $this->checkGetSections($form, ["Section 1", "Section 2", "Section 3", "Section 4"]);
    }

    private function checkGetSections(
        Form $form,
        array $expected_sections_names
    ): void {
        $sections = $form->getSections();
        $names = array_map(fn($section) => $section->getName(), $sections);
        $names = array_values($names); // Strip keys
        $this->assertEquals($expected_sections_names, $names);
    }

    public function testGetQuestionsForEmptyForm()
    {
        $form = $this->createForm(new FormBuilder());
        $this->checkGetQuestions($form, []);
    }

    public function testGetQuestionsForFormWithQuestions()
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1', QuestionTypeShortText::class)
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addSection('Section 2', QuestionTypeShortText::class)
                ->addQuestion('Question 3', QuestionTypeShortText::class)
        );
        $this->checkGetQuestions($form, ["Question 1", "Question 2", "Question 3"]);
    }

    private function checkGetQuestions(
        Form $form,
        array $expected_question_names
    ): void {
        $questions = $form->getQuestions();
        $names = array_map(fn($question) => $question->getName(), $questions);
        $names = array_values($names); // Strip keys
        $this->assertEquals($expected_question_names, $names);
    }

    public function testGetCommentsOnEmptyForm(): void
    {
        $form = $this->createForm(new FormBuilder());
        $this->checkGetComments($form, [], []);
    }

    public function testGetCommentsOnFormWithComments(): void
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1', QuestionTypeShortText::class)
                ->addComment('Comment 1', 'Comment 1 description')
                ->addComment('Comment 2', 'Comment 2 description')
                ->addSection('Section 2', QuestionTypeShortText::class)
                ->addComment('Comment 3', 'Comment 3 description')
        );
        $this->checkGetComments(
            form: $form,
            expected_comment_names: ["Comment 1", "Comment 2", "Comment 3"],
            expected_comment_descriptions: ["Comment 1 description", "Comment 2 description", "Comment 3 description"]
        );
    }

    private function checkGetComments(
        Form $form,
        array $expected_comment_names,
        array $expected_comment_descriptions
    ): void {
        $comments = $form->getFormComments();
        $names = array_map(fn($comment) => $comment->getName(), $comments);
        $names = array_values($names); // Strip keys
        $this->assertEquals($expected_comment_names, $names);

        $descriptions = array_map(fn($comment) => $comment->fields['description'], $comments);
        $descriptions = array_values($descriptions); // Strip keys
        $this->assertEquals($expected_comment_descriptions, $descriptions);
    }

    /**
     * Ensure that update to a form questions are logged.
     *
     * @see Question::logCreationInParentForm
     * @see Question::logUpdateInParentForm
     * @see Question::logDeleteInParentForm
     *
     * Update to the form itself and its sections are already handled by GLPI's
     * framework and are not required to be tested here.
     */
    public function testLogs(): void
    {
        $this->login();

        $form = $this->createForm(new FormBuilder());
        $this->checkTestLogs($form, 3); // Created + mandatory target + default policy

        $this->addSectionToForm($form, "Section 1");
        $this->addSectionToForm($form, "Section 2");
        $this->addSectionToForm($form, "Section 3");
        $this->checkTestLogs($form, 6); // + 3 sections added

        $q1 = $this->addQuestionToForm($form, "Question 1");
        $this->addQuestionToForm($form, "Question 2");
        $this->checkTestLogs($form, 8); // + 2 questions added

        $c1 = $this->addCommentBlockToForm($form, "Title 1", "Content 1");
        $this->addCommentBlockToForm($form, "Title 2", "Content 2");
        $this->checkTestLogs($form, 10); // + 2 comments added

        $this->updateItem(Question::class, $q1->getId(), [
            'name' => 'Question 1 (updated)',
            'type' => QuestionTypeEmail::class,
        ]);
        $this->checkTestLogs($form, 12); // + 2 question fields updated

        $this->deleteItem(Question::class, $q1->getId());
        $this->checkTestLogs($form, 13); // + 1 question deleted

        $this->updateItem(Comment::class, $c1->getId(), [
            'name' => 'Title 1 (updated)',
        ]);
        $this->checkTestLogs($form, 14); // + 1 comment updated

        $this->deleteItem(Comment::class, $c1->getId());
        $this->checkTestLogs($form, 15); // + 1 comment deleted
    }

    private function checkTestLogs(
        Form $form,
        int $expected_logs_count
    ): void {
        $logs = (new Log())->find([
            'itemtype' => $form->getType(),
            'items_id' => $form->getID(),
        ]);

        $this->assertCount($expected_logs_count, $logs);
    }

    /**
     * Indirectly test the cleanDBonPurge method by purging a form
     *
     * @return void
     */
    public function testCleanDBonPurge(): void
    {
        // Count existing data to make sure residual data in the tests database
        // doesn't cause a failure when running tests locally.
        $forms = countElementsInTable(Form::getTable());
        $questions = countElementsInTable(Question::getTable());
        $sections = countElementsInTable(Section::getTable());
        $comments = countElementsInTable(Comment::getTable());
        $destinations = countElementsInTable(FormDestination::getTable());
        $access_controls = countElementsInTable(FormAccessControl::getTable());
        $form_tiles = countElementsInTable(FormTile::getTable());
        $linked_items = countElementsInTable(Item_Ticket::getTable());

        // Test subject that we are going to delete
        $form_to_be_deleted = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addComment('Comment 1', 'Comment 1 description')
                ->addSection('Section 2')
                ->addComment('Comment 2', 'Comment 2 description')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addQuestion('Question 2', QuestionTypeShortText::class)
                ->addQuestion('Question 3', QuestionTypeShortText::class)
                ->addDestination(FormDestinationTicket::class, 'Destinations 1')
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(AllowList::class, new AllowListConfig())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig())
        );
        // Submit form to link to some tickets
        $answers_handler = AnswersHandler::getInstance();
        $answers_handler->saveAnswers(
            $form_to_be_deleted,
            [],
            getItemByTypeName(User::class, TU_USER, true)
        );

        // Add a tile to the form, it should be deleted with the form
        $this->createItem(
            FormTile::class,
            [
                Form::getForeignKeyField() => $form_to_be_deleted->getID(),
            ]
        );

        // Control subject that we are going to keep, its data shouldn't be deleted
        $form_to_keep = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addComment('Comment 1', 'Comment 1 description')
                ->addDestination(FormDestinationTicket::class, 'Destinations 1')
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig())
        );
        // Submit form to link to some tickets
        $answers_handler = AnswersHandler::getInstance();
        $answers_handler->saveAnswers(
            $form_to_keep,
            [],
            getItemByTypeName(User::class, TU_USER, true)
        );

        // Add a tile to the form, it should be kept with the form
        $this->createItem(
            FormTile::class,
            [
                Form::getForeignKeyField() => $form_to_keep->getID(),
            ]
        );

        // Count items before deletion
        $this->assertEquals(2 + $forms, countElementsInTable(Form::getTable()));
        $this->assertEquals(6 + $questions, countElementsInTable(Question::getTable()));
        $this->assertEquals(3 + $sections, countElementsInTable(Section::getTable()));
        $this->assertEquals(3 + $comments, countElementsInTable(Comment::getTable()));
        $this->assertEquals(4 + $destinations, countElementsInTable(FormDestination::getTable())); // +1 mandatory for each form
        $this->assertEquals(3 + $access_controls, countElementsInTable(FormAccessControl::getTable()));
        $this->assertEquals(2 + $form_tiles, countElementsInTable(FormTile::getTable()));
        $this->assertEquals(4 + $linked_items, countElementsInTable(Item_Ticket::getTable())); // 2 Destination per ticket (1 manual + 1 auto) so 4 linked forms in total

        // Delete item
        $this->deleteItem(
            Form::class,
            $form_to_be_deleted->getID(),
            true
        );

        // Re count items after deletion
        $this->assertEquals(1 + $forms, countElementsInTable(Form::getTable()));
        $this->assertEquals(1 + $questions, countElementsInTable(Question::getTable()));
        $this->assertEquals(1 + $sections, countElementsInTable(Section::getTable()));
        $this->assertEquals(1 + $comments, countElementsInTable(Comment::getTable()));
        $this->assertEquals(2 + $destinations, countElementsInTable(FormDestination::getTable()));
        $this->assertEquals(1 + $access_controls, countElementsInTable(FormAccessControl::getTable()));
        $this->assertEquals(1 + $form_tiles, countElementsInTable(FormTile::getTable()));
        $this->assertEquals(2 + $linked_items, countElementsInTable(Item_Ticket::getTable()));
    }

    /**
     * Test the purgedraftforms cron task
     */
    public function testCronTaskPurgeDraftForms()
    {
        global $DB;

        // Count existing data to make sure residual data in the tests database
        // doesn't cause a failure when running tests locally.
        $forms = countElementsInTable(Form::getTable());

        // Create a draft form
        $this->createForm((new FormBuilder())->setIsDraft(true));

        // Retrieve the cron task
        $task = new CronTask();
        $task->fields['param'] = 7;

        // Run the cron task
        Form::cronPurgeDraftForms($task);

        // Ensure the draft form wasn't deleted because it was created less than 7 day ago
        $this->assertEquals(1 + $forms, countElementsInTable(Form::getTable()));

        // Create a draft form that was created more than 7 day ago
        $form = $this->createForm(
            (new FormBuilder())->setIsDraft(true)
                ->addSection('Section 1')
                ->addQuestion('Question 1', QuestionTypeShortText::class)
                ->addDestination(FormDestinationTicket::class, 'Destinations 1')
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig())
        );
        $DB->update(
            Form::getTable(),
            [
                'date_mod' => date('Y-m-d H:i:s', strtotime('-8 days')),
            ],
            [
                'id' => $form->getID(),
            ]
        );

        // Run the cron task
        Form::cronPurgeDraftForms($task);

        // Ensure the draft form was deleted
        // TODO: test seems weird, shouldn't it be 0 since the form was delete ?
        $this->assertEquals(1 + $forms, countElementsInTable(Form::getTable()));
    }

    public static function defineTabsProvider_defaultDestinations(): iterable
    {
        $default_form = new FormBuilder("My form");

        yield 'Form with default destination and no answers' => [
            'form' => $default_form,
            'number_of_answers' => 0,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Access controls 1',
                'Destinations 1',
                'Form translations',
            ],
        ];
        yield 'Form with default destination and 1 answers' => [
            'form' => $default_form,
            'number_of_answers' => 1,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 1',
                'Access controls 1',
                'Destinations 1',
                'Form translations',
            ],
        ];
        yield 'Form with default destination and 5 answers' => [
            'form' => $default_form,
            'number_of_answers' => 5,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 5',
                'Access controls 1',
                'Destinations 1',
                'Form translations',
            ],
        ];
    }

    public static function defineTabsProvider_withAdditionalTickets(): iterable
    {
        $form = new FormBuilder("My form");
        $form->addDestination(FormDestinationTicket::class, 'Destinations 1');
        $form->addDestination(FormDestinationTicket::class, 'Destinations 2');
        $form->addDestination(FormDestinationTicket::class, 'Destinations 3');

        yield 'Form with multiple tickets destinations and no answers' => [
            'form' => $form,
            'number_of_answers' => 0,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple tickets destinations and 1 answers' => [
            'form' => $form,
            'number_of_answers' => 1,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 4', // (1 (default) + 3) destinations * 1 answer
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple tickets destinations and 5 answers' => [
            'form' => $form,
            'number_of_answers' => 5,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 20', // (1 (default) + 3) destinations 5 answers
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
    }

    public static function defineTabsProvider_withAdditionalChanges(): iterable
    {
        $form = new FormBuilder("My form");
        $form->addDestination(FormDestinationChange::class, 'Destinations 1');
        $form->addDestination(FormDestinationChange::class, 'Destinations 2');
        $form->addDestination(FormDestinationChange::class, 'Destinations 3');

        yield 'Form with multiple changes destinations and no answers' => [
            'form' => $form,
            'number_of_answers' => 0,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple changes destinations and 1 answers' => [
            'form' => $form,
            'number_of_answers' => 1,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 1',
                'Changes 3', // 3 destinations * 1 answer
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple changes destinations and 5 answers' => [
            'form' => $form,
            'number_of_answers' => 5,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 5',
                'Changes 15', // 3 destinations * 5 answers
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
    }

    public static function defineTabsProvider_withAdditionalProblems(): iterable
    {
        $form = new FormBuilder("My form");
        $form->addDestination(FormDestinationProblem::class, 'Destinations 1');
        $form->addDestination(FormDestinationProblem::class, 'Destinations 2');
        $form->addDestination(FormDestinationProblem::class, 'Destinations 3');

        yield 'Form with multiple problems destinations and no answers' => [
            'form' => $form,
            'number_of_answers' => 0,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple problems destinations and 1 answers' => [
            'form' => $form,
            'number_of_answers' => 1,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 1',
                'Problems 3', // 3 destinations * 1 answer
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
        yield 'Form with multiple problems destinations and 5 answers' => [
            'form' => $form,
            'number_of_answers' => 5,
            'expected_tabs' => [
                'Form',
                'Service catalog',
                'Tickets 5',
                'Problems 15', // 3 destinations * 5 answers
                'Access controls 1',
                'Destinations 4',
                'Form translations',
            ],
        ];
    }


    #[DataProvider('defineTabsProvider_defaultDestinations')]
    #[DataProvider('defineTabsProvider_withAdditionalTickets')]
    #[DataProvider('defineTabsProvider_withAdditionalChanges')]
    #[DataProvider('defineTabsProvider_withAdditionalProblems')]
    public function testDefinedTabs(
        FormBuilder $form,
        int $number_of_answers,
        array $expected_tabs,
    ): void {
        $this->login();

        // Arrange: create the form and submit its answers
        $form = $this->createForm($form);
        for ($i = 0; $i < $number_of_answers; $i++) {
            $this->sendFormAndGetAnswerSet($form, []);
        }

        // Act: get tabs names
        $tabs = array_filter($form->defineTabs(), static fn($tab): bool => $tab !== 'no_all_tab', ARRAY_FILTER_USE_KEY);
        $tabs = array_map('strip_tags', $tabs); // Strip html
        $tabs = array_values($tabs); // Ignore keys

        // Ignore the historical tab as its number of item keep chaning and make
        // the test less reliable.
        $tabs = array_filter(
            $tabs,
            fn($tab): bool => !str_contains($tab, 'Historical'),
        );

        // Assert: the tabs should match the expected data
        $this->assertEquals($expected_tabs, $tabs);
    }
}
