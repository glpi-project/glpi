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

namespace tests\units\Glpi\Form;

use DbTestCase;
use FormBuilder;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Form\QuestionType\QuestionTypesManager;
use Glpi\Form\Section;

class Form extends DbTestCase
{
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

        foreach ($questions as $type) {
            $form_builder->addQuestion(
                name: "Question $i",
                type: $type::class,
                description: $i % 4 === 0 ? "Description of question $i" : "", // Add a description every 4 questions
                is_mandatory: $i % 2 === 0, // Half of the questions are mandatory
            );

            // Add a section every 10 questions
            if ($i % 10 === 0) {
                $form_builder->addSection("Section " . ($i / 10));
            }
        }

        $form = $this->createForm($form_builder);

        // Render form
        ob_start();
        $this->boolean($form->showForm($form->getID()))->isTrue();
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
                ->addQuestion('Question 1', QuestionTypeShortAnswerText::class)
                ->addQuestion('Question 2', QuestionTypeShortAnswerText::class)
                ->addSection('Section 2')
                ->addQuestion('Question 3', QuestionTypeShortAnswerText::class)
        );
        $this
            ->integer(count($form->getSections()))
            ->isEqualTo(2)
        ;
        $this
            ->integer(count($form->getQuestions()))
            ->isEqualTo(3)
        ;

        foreach ($form->getSections() as $section) {
            foreach ($section->getQuestions() as $question) {
                $this->deleteItem(Question::class, $question->getID());
            }
            $this->deleteItem(Section::class, $section->getID());
        }

        // Until the form is reloaded, its internal sections and questions data
        // shouldn't change
        $this
            ->integer(count($form->getSections()))
            ->isEqualTo(2)
        ;
        $this
            ->integer(count($form->getQuestions()))
            ->isEqualTo(3)
        ;

        // Relaod form
        $form->getFromDB($form->getID());
        $this
            ->integer(count($form->getSections()))
            ->isEqualTo(0)
        ;
        $this
            ->integer(count($form->getQuestions()))
            ->isEqualTo(0)
        ;
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
        $form_with_section = $this->createItem(\Glpi\Form\Form::class, [
            'name'        => 'Form with first section',
            'entities_id' => $entity->getID(),
        ]);
        $this
            ->integer(count($form_with_section->getSections()))
            ->isEqualTo(1)
        ;

        $form_without_section = $this->createItem(\Glpi\Form\Form::class, [
            'name'                  => 'Form without first section',
            'entities_id'           => $entity->getID(),
            '_do_not_init_sections' => true,
        ]);
        $this
            ->integer(count($form_without_section->getSections()))
            ->isEqualTo(0)
        ;
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
        $form = $this->createItem(\Glpi\Form\Form::class, [
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
                    'id'                        => uniqid(),
                    '_use_uuid'                 => true,
                    'forms_sections_id'          => $section->getID(),
                    '_use_uuid_for_sections_id' => false,
                    'name'                      => 'Question name',
                    'type'                      => QuestionTypeShortAnswerText::class,
                ],
            ],
        ]);
        $this
            ->integer(count($form->getQuestions()))
            ->isEqualTo(1)
        ;

        $id = $form->getID();
        $this->hasSessionMessages(INFO, ["Item successfully updated: <a  href='/glpi/front/form/form.form.php?id=$id'  title=\"Form with first section\">Form with first section</a>"]);
    }

    /**
     * Data provider for the testGetSections method
     *
     * @return iterable
     */
    protected function testGetSectionsProvider(): iterable
    {
        $form_1 = $this->createForm(new FormBuilder());
        yield [$form_1, []];

        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addSection('Section 2')
                ->addSection('Section 3')
        );
        yield [$form_2, ["Section 1", "Section 2", "Section 3"]];

        $form_3 = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addSection('Section 2')
                ->addQuestion('Question 1', QuestionTypeShortAnswerText::class)
                ->addSection('Section 3')
                ->addQuestion('Question 2', QuestionTypeShortAnswerText::class)
                ->addQuestion('Question 3', QuestionTypeShortAnswerText::class)
                ->addSection('Section 4')
        );
        yield [$form_3, ["Section 1", "Section 2", "Section 3", "Section 4"]];
    }

    /**
     * Test the getSections method
     *
     * @dataProvider testGetSectionsProvider
     *
     * @param \Glpi\Form\Form $form
     * @param array           $expected_sections_names
     *
     * @return void
     */
    public function testGetSections(
        \Glpi\Form\Form $form,
        array $expected_sections_names
    ): void {
        $sections = $form->getSections();
        $names = array_map(fn($section) => $section->getName(), $sections);
        $names = array_values($names); // Strip keys
        $this
            ->array($names)
            ->isEqualTo($expected_sections_names)
        ;
    }

    /**
     * Provider for the testPost_updateItem method
     *
     * @return iterable
     */
    protected function testPost_updateItemProvider(): iterable
    {
        $this->login();
        $form = $this->createForm(new FormBuilder());

        // Submit one basic section
        yield [
            'input' => [
                'id'                        => $form->getID(),
                '_delete_missing_questions' => true,
                '_delete_missing_sections'  => true,
                '_sections'                 => [
                    [
                        'id'             => 'section_1',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 1"
                    ]
                ],
                '_questions' => [
                    [
                        'id'                        => "question_1",
                        '_use_uuid'                 => true,
                        'forms_sections_id'         => "section_1",
                        '_use_uuid_for_sections_id' => true,
                        'name'                      => 'Question 1',
                        'type'                      => QuestionTypeShortAnswerText::class,
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 1',
                    'q_names' => ['Question 1'],
                ]
            ],
        ];

        // Submit multiple ordered sections while deleting the previous section
        yield [
            'input' => [
                'id'                        => $form->getID(),
                '_delete_missing_questions' => true,
                '_delete_missing_sections'  => true,
                '_sections'                 => [
                    [
                        'id'             => 'section_2',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 2",
                        'rank'           => 1
                    ],
                    [
                        'id'             => 'section_3',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 3",
                        'rank'           => 0
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ]
            ],
        ];

        // Ensure no sections are deleted when "_delete_missing_questions" is not set
        yield [
            'input' => [
                'id'                        => $form->getID(),
                '_sections'                 => [
                    [
                        'id'             => 'section_4',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 4",
                        'rank'           => 2
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 4',
                    'q_names' => [],
                ]
            ],
        ];

        // Ensure an existing section can be updated
        yield [
            'input' => [
                'id'        => $form->getID(),
                '_sections' => [
                    [
                        'id'             => 'section_4',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 4 (updated)",
                        'rank'           => 2
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 4 (updated)',
                    'q_names' => [],
                ]
            ],
        ];

        // Update using ID instead of UUID
        yield [
            'input' => [
                'id'        => $form->getID(),
                '_sections' => [
                    [
                        'id'             => $this->getSectionId($form, 'Section 4 (updated)'),
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => false,
                        'name'           => "Section 4 (updated two times)",
                        'rank'           => 2
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 4 (updated two times)',
                    'q_names' => [],
                ]
            ],
        ];

        // Add questions
        yield [
            'input' => [
                'id'                        => $form->getID(),
                '_delete_missing_questions' => true,
                '_delete_missing_sections'  => true,
                '_sections'                 => [
                    [
                        'id'             => 'section_2',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 2",
                        'rank'           => 1
                    ],
                    [
                        'id'             => 'section_3',
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => true,
                        'name'           => "Section 3",
                        'rank'           => 0
                    ],
                    [
                        'id'             => $this->getSectionId($form, 'Section 4 (updated two times)'),
                        'forms_forms_id' => $form->getID(),
                        '_use_uuid'      => false,
                        'name'           => "Section 4 (updated two times)",
                        'rank'           => 2
                    ],
                ],
                '_questions' => [
                    [
                        'id'                        => "question_2",
                        '_use_uuid'                 => true,
                        'forms_sections_id'         => "section_3",
                        '_use_uuid_for_sections_id' => true,
                        'name'                      => 'Question 2',
                        'type'                      => QuestionTypeShortAnswerText::class,
                        'rank'                      => 0,
                    ],
                    [
                        'id'                        => "question_3",
                        '_use_uuid'                 => true,
                        'forms_sections_id'         => "section_3",
                        '_use_uuid_for_sections_id' => true,
                        'name'                      => 'Question 3',
                        'type'                      => QuestionTypeShortAnswerText::class,
                        'rank'                      => 2,
                    ],
                    [
                        'id'                        => "question_4",
                        '_use_uuid'                 => true,
                        'forms_sections_id'         => "section_3",
                        '_use_uuid_for_sections_id' => true,
                        'name'                      => 'Question 4',
                        'type'                      => QuestionTypeShortAnswerText::class,
                        'rank'                      => 1,
                    ],
                    [
                        'id'                        => "question_5",
                        '_use_uuid'                 => true,
                        'forms_sections_id'         => $this->getSectionId($form, 'Section 4 (updated two times)'),
                        '_use_uuid_for_sections_id' => false,
                        'name'                      => 'Question 5',
                        'type'                      => QuestionTypeShortAnswerText::class,
                        'rank'                      => 0,
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => ['Question 2', 'Question 4', 'Question 3'],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 4 (updated two times)',
                    'q_names' => ["Question 5"],
                ]
            ],
        ];

        // Update question using real id
        yield [
            'input' => [
                'id'         => $form->getID(),
                '_questions' => [
                    [
                        'id'                        => $this->getQuestionsId($form, 'Question 5'),
                        '_use_uuid'                 => false,
                        'forms_sections_id'         => $this->getSectionId($form, 'Section 4 (updated two times)'),
                        '_use_uuid_for_sections_id' => false,
                        'name'                      => 'Question 5 (updated)',
                        'type'                      => QuestionTypeShortAnswerText::class,
                        'rank'                      => 0,
                    ],
                ],
            ],
            'expected_sections_content' => [
                [
                    'name'    => 'Section 3',
                    'q_names' => ['Question 2', 'Question 4', 'Question 3'],
                ],
                [
                    'name'    => 'Section 2',
                    'q_names' => [],
                ],
                [
                    'name'    => 'Section 4 (updated two times)',
                    'q_names' => ["Question 5 (updated)"],
                ]
            ],
        ];

        // Clear all data
        yield [
            'input' => [
                'id'         => $form->getID(),
                '_delete_missing_questions' => true,
                '_delete_missing_sections'  => true,
            ],
            'expected_sections_content' => [],
        ];
    }

    /**
     * Indirectly test the post_updateItem method by updating a form
     *
     * @dataProvider testPost_updateItemProvider
     *
     * @param array $input Form input to be applied as an update
     * @param array{name: string, q_names: array} $expected_sections_content Expected sections content after the update
     *
     * @return void
     */
    public function testPost_updateItem(
        array $input,
        array $expected_sections_content,
    ): void {
        // Update item and load its sections
        $form = $this->updateItem(\Glpi\Form\Form::class, $input['id'], $input);
        $sections = $form->getSections();
        $sections = array_values($sections); // Strip keys

        // Ensure section names are correct
        $section_names = array_map(
            fn($section) => $section->getName(),
            $sections
        );
        $expected_section_names = array_map(
            fn($expected_section) => $expected_section['name'],
            $expected_sections_content
        );

        $this
            ->array($section_names)
            ->isEqualTo($expected_section_names)
        ;

        // Validate each section content is valid
        foreach ($sections as $i => $section) {
            $questions = $section->getQuestions();
            $questions = array_values($section->getQuestions()); // Strip keys

            $questions_names = array_map(
                fn($question) => $question->getName(),
                $questions
            );
            $expected_question_names = $expected_sections_content[$i]['q_names'];

            $this
                ->array($questions_names)
                ->isEqualTo($expected_question_names)
            ;
        }
    }

    /**
     * Data provider for the testGetQuestions method
     *
     * @return iterable
     */
    protected function testGetQuestionsProvider(): iterable
    {
        $form_1 = $this->createForm(new FormBuilder());
        yield [$form_1, []];

        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addSection('Section 2')
                ->addSection('Section 3')
        );
        yield [$form_2, []];

        $form_3 = $this->createForm(
            (new FormBuilder())
                ->addSection('Section 1')
                ->addSection('Section 2')
                ->addQuestion('Question 1', QuestionTypeShortAnswerText::class)
                ->addSection('Section 3')
                ->addQuestion('Question 2', QuestionTypeShortAnswerText::class)
                ->addQuestion('Question 3', QuestionTypeShortAnswerText::class)
                ->addSection('Section 4')
        );
        yield [$form_3, ["Question 1", "Question 2", "Question 3"]];
    }

    /**
     * Test the getQuestions method
     *
     * @dataProvider testGetQuestionsProvider
     *
     * @param \Glpi\Form\Form $form
     * @param array           $expected_question_names
     *
     * @return void
     */
    public function testGetQuestions(
        \Glpi\Form\Form $form,
        array $expected_question_names
    ): void {
        $questions = $form->getQuestions();
        $names = array_map(fn($question) => $question->getName(), $questions);
        $names = array_values($names); // Strip keys
        $this
            ->array($names)
            ->isEqualTo($expected_question_names)
        ;
    }
}
