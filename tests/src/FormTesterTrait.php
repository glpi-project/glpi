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

use CommonDBTM;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Comment;
use Glpi\Form\Condition\Type;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Glpi\Form\Tag\Tag;
use RuntimeException;
use Session;
use Ticket;
use User;

/**
 * Helper trait to tests helpdesk form related features
 * Should only be used on DbTestCase classes as it calls some of its methods.
 */
trait FormTesterTrait
{
    /**
     * Helper method to help creating complex forms using the FormBuilder class.
     *
     * @param FormBuilder $builder RuleConfiguration
     *
     * @return Form Created form
     */
    protected function createForm(FormBuilder $builder): Form
    {
        // Create form
        $form = $this->createItem(Form::class, [
            'name'                  => $builder->getName(),
            'description'           => $builder->getDescription(),
            'entities_id'           => $builder->getEntitiesId(),
            'is_recursive'          => $builder->getIsRecursive(),
            'is_active'             => $builder->getIsActive(),
            'header'                => $builder->getHeader(),
            'is_draft'              => $builder->getIsDraft(),
            'is_pinned'             => $builder->getIsPinned(),
            'forms_categories_id'   => $builder->getCategory(),
            'usage_count'           => $builder->getUsageCount(),
            '_init_sections'        => false,  // We will handle sections ourselves
            '_init_access_policies' => $builder->getUseDefaultAccessPolicies(),
        ]);

        $section_rank = 0;
        foreach ($builder->getSections() as $section_data) {
            // Create section
            $section = $this->createItem(Section::class, [
                'forms_forms_id' => $form->getID(),
                'name'           => $section_data['name'],
                'description'    => $section_data['description'],
                'rank'           => $section_rank++,
            ]);

            // Create questions
            $question_rank = 0;
            foreach ($section_data['questions'] as $question_data) {
                $this->createItem(Question::class, [
                    'forms_sections_id' => $section->getID(),
                    'name'              => $question_data['name'],
                    'type'              => $question_data['type'],
                    'is_mandatory'      => $question_data['is_mandatory'],
                    'description'       => $question_data['description'],
                    'default_value'     => $question_data['default_value'],
                    'extra_data'        => $question_data['extra_data'],
                    'horizontal_rank'   => $question_data['horizontal_rank'],
                    'vertical_rank'     => $question_rank++,
                ], [
                    'default_value', // The default value can be formatted by the question type
                ]);
            }

            // Create comments
            $comment_rank = 0;
            foreach ($section_data['comments'] as $comment_data) {
                $this->createItem(Comment::class, [
                    'forms_sections_id' => $section->getID(),
                    'name'              => $comment_data['name'],
                    'description'       => $comment_data['description'],
                    'vertical_rank'     => $comment_rank++,
                ]);
            }
        }

        // Create destinations
        foreach ($builder->getDestinations() as $itemtype => $destinations) {
            foreach ($destinations as $destination_data) {
                $this->createItem(FormDestination::class, [
                    'forms_forms_id' => $form->getID(),
                    'itemtype'       => $itemtype,
                    'name'           => $destination_data['name'],
                    'config'         => $destination_data['config'],
                ], ['config']);
            }
        }

        // Create access controls
        foreach ($builder->getAccessControls() as $strategy_class => $params) {
            $this->createItem(FormAccessControl::class, [
                'forms_forms_id' => $form->getID(),
                'strategy'       => $strategy_class,
                '_config'        => $params['config'],
                'is_active'      => $params['is_active'],
            ]);
        }

        // Add visibility conditions on form
        if (!empty($builder->getSubmitButtonVisibility())) {
            $form_conditions = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::SECTION) {
                    $item = Section::getById($this->getSectionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::COMMENT) {
                    $item = Comment::getById($this->getCommentId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $builder->getSubmitButtonVisibility()['conditions']);

            $this->updateItem(Form::class, $form->getID(), [
                'submit_button_visibility_strategy' => $builder->getSubmitButtonVisibility()['strategy'],
                'submit_button_conditions'          => json_encode($form_conditions),
            ], ['submit_button_conditions']);
        }

        // Add visibility conditions on questions
        foreach ($builder->getQuestionVisibility() as $name => $params) {
            // Find the correct question
            $id = $this->getQuestionId($form, $name);

            $params['conditions'] = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::SECTION) {
                    $item = Section::getById($this->getSectionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::COMMENT) {
                    $item = Comment::getById($this->getCommentId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $params['conditions']);

            $this->updateItem(Question::class, $id, [
                'visibility_strategy' => $params['strategy'],
                'conditions' => json_encode($params['conditions']),
            ], ['conditions']);
        }

        // Add validation conditions on questions
        foreach ($builder->getQuestionValidation() as $name => $params) {
            // Find the correct question
            $id = $this->getQuestionId($form, $name);

            $params['conditions'] = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $params['conditions']);

            // Add validation strategy
            if (isset($params['validation_strategy'])) {
                // Update item with validation strategy
                $this->updateItem(Question::class, $id, [
                    'validation_strategy' => $params['validation_strategy'],
                ]);
            }

            // Update item with conditions
            if (isset($params['conditions'])) {
                // Update item with conditions
                $this->updateItem(Question::class, $id, [
                    'validation_conditions' => json_encode($params['conditions']),
                ], ['validation_conditions']);
            }
        }

        // Add visibility conditions on comments
        foreach ($builder->getCommentVisibility() as $name => $params) {
            // Find the correct comment
            $id = $this->getCommentId($form, $name);

            $params['conditions'] = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::SECTION) {
                    $item = Section::getById($this->getSectionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::COMMENT) {
                    $item = Comment::getById($this->getCommentId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $params['conditions']);

            $this->updateItem(Comment::class, $id, [
                'visibility_strategy' => $params['strategy'],
                'conditions' => json_encode($params['conditions']),
            ], ['conditions']);
        }

        // Add visibility conditions on section
        foreach ($builder->getSectionVisibility() as $name => $params) {
            // Find the correct comment
            $id = $this->getSectionId($form, $name);

            $params['conditions'] = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::SECTION) {
                    $item = Section::getById($this->getSectionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::COMMENT) {
                    $item = Comment::getById($this->getCommentId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $params['conditions']);

            $this->updateItem(Section::class, $id, [
                'visibility_strategy' => $params['strategy'],
                'conditions' => json_encode($params['conditions']),
            ], ['conditions']);
        }

        // Add creation conditions on destinations
        foreach ($builder->getDestinationCondition() as $name => $params) {
            // Find the correct comment
            $id = $this->getDestinationId($form, $name);

            $params['conditions'] = array_map(function ($condition) use ($form) {
                // Find the correct UUID
                if ($condition['item_type'] == Type::SECTION) {
                    $item = Section::getById($this->getSectionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::QUESTION) {
                    $item = Question::getById($this->getQuestionId(
                        $form,
                        $condition['item_name']
                    ));
                } elseif ($condition['item_type'] == Type::COMMENT) {
                    $item = Comment::getById($this->getCommentId(
                        $form,
                        $condition['item_name']
                    ));
                } else {
                    throw new RuntimeException("Unknown type");
                }
                $item_uuid = $item->fields['uuid'];

                return [
                    'item'           => $condition['item_type']->value . "-" . $item_uuid,
                    'item_type'      => $condition['item_type'],
                    'item_uuid'      => $item_uuid,
                    'value'          => $condition['value'],
                    'value_operator' => $condition['value_operator']->value,
                    'logic_operator' => $condition['logic_operator']->value,
                ];
            }, $params['conditions']);

            $this->updateItem(FormDestination::class, $id, [
                'creation_strategy' => $params['strategy'],
                'conditions' => json_encode($params['conditions']),
            ], ['conditions']);
        }

        // Reload form to clear cached data
        $id = $form->getID();
        $form = new Form();
        $form->getFromDB($id);

        return $form;
    }

    /**
     * Helper method to access the ID of a question for a given form.
     *
     * @param Form        $form          Given form
     * @param string      $question_name Question name to look for
     * @param string|null $section_name  Optional section name, might be needed if
     *                                   multiple sections have questions with the
     *                                   same names.
     *
     * @return int The ID of the question
     */
    protected function getQuestionId(
        Form $form,
        string $question_name,
        ?string $section_name = null,
    ): int {
        // Make sure form is up to date
        $form->getFromDB($form->getID());

        // Get questions
        $questions = $form->getQuestions();

        if ($section_name === null) {
            // Search by name
            $filtered_questions = array_filter(
                $questions,
                fn($question) => $question->fields['name'] === $question_name
            );

            $question = array_pop($filtered_questions);
            return $question->getID();
        } else {
            // Find section
            $sections = $form->getSections();
            $filtered_sections = array_filter(
                $sections,
                fn($section) => $section->fields['name'] === $section_name
            );
            $section = array_pop($filtered_sections);

            // Search by name AND section
            $filtered_questions = array_filter(
                $questions,
                fn($question) => $question->fields['name'] === $question_name
                    && $question->fields['forms_sections_id'] === $section->getID()
            );
            $this->assertCount(1, $filtered_questions);
            $question = array_pop($filtered_questions);
            return $question->getID();
        }
    }

    /**
     * Helper method to access the ID of a section for a given form.
     *
     * @param Form        $form         Given form
     * @param string      $section_name Section name to look for
     *
     * @return int The ID of the section
     */
    protected function getSectionId(
        Form $form,
        string $section_name,
    ): int {
        // Make sure form is up to date
        $form->getFromDB($form->getID());

        // Get sections
        $sections = $form->getSections();

        // Search by name
        $filtered_sections = array_filter(
            $sections,
            fn($section) => $section->fields['name'] === $section_name
        );

        $section = array_pop($filtered_sections);
        return $section->getID();
    }

    /**
     * Helper method to access the ID of a comment for a given form.
     *
     * @param Form        $form         Given form
     * @param string      $comment_name Comment name to look for
     * @param string|null $section_name Optional section name, might be needed if
     *
     * @return int The ID of the comment
     */
    protected function getCommentId(
        Form $form,
        string $comment_name,
        ?string $section_name = null,
    ): int {
        // Make sure form is up to date
        $form->getFromDB($form->getID());

        // Get comments
        $comments = $form->getFormComments();

        if ($section_name === null) {
            // Search by name
            $filtered_comments = array_filter(
                $comments,
                fn($comment) => $comment->fields['name'] === $comment_name
            );

            $comment = array_pop($filtered_comments);
            return $comment->getID();
        } else {
            // Find section
            $sections = $form->getSections();
            $filtered_sections = array_filter(
                $sections,
                fn($section) => $section->fields['name'] === $section_name
            );
            $section = array_pop($filtered_sections);

            // Search by name AND section
            $filtered_comments = array_filter(
                $comments,
                fn($comment) => $comment->fields['name'] === $comment_name
                    && $comment->fields['forms_sections_id'] === $section->getID()
            );
            $this->assertCount(1, $filtered_comments);
            $comment = array_pop($filtered_comments);
            return $comment->getID();
        }
    }

    protected function getDestinationId(Form $form, string $name): int
    {
        $destinations = $form->getDestinations();
        $destination = array_filter(
            $destinations,
            fn($destination) => $destination->fields['name'] === $name
        );
        if (count($destination) !== 1) {
            throw new RuntimeException("Destination not found or ambiguous: $name");
        }
        $destination = current($destination);
        return $destination->getID();
    }

    /**
     * Get the given access control object of a form.
     *
     * @param Form   $form         Form to get the access control from
     * @param string $control_type Type of access control to get
     *
     * @return FormAccessControl
     */
    protected function getAccessControl(
        Form $form,
        string $control_type
    ): FormAccessControl {
        $controls = $form->getAccessControls();

        $controls = array_filter(
            $controls,
            fn($control) => $control->fields['strategy'] === $control_type
        );

        $control = array_pop($controls);
        return $control;
    }

    protected function getTagByName(array $tags, string $name): Tag
    {
        return current(array_filter(
            $tags,
            fn($tag) => $tag->label === $name,
        ));
    }

    protected function addSectionToForm(Form $form, string $section_name): Section
    {
        $section = $this->createItem(Section::class, [
            'forms_forms_id' => $form->getID(),
            'name'           => $section_name,
        ]);

        return $section;
    }

    protected function addQuestionToForm(Form $form, string $question_name): Question
    {
        // Get last section
        $sections = $form->getSections();
        $section = end($sections);

        $question = $this->createItem(Question::class, [
            'forms_sections_id' => $section->getID(),
            'name'              => $question_name,
        ]);

        return $question;
    }

    protected function addCommentBlockToForm(
        Form $form,
        string $title,
        string $content,
    ): Comment {
        // Get last section
        $sections = $form->getSections();
        $section = end($sections);

        $comment = $this->createItem(Comment::class, [
            'forms_sections_id' => $section->getID(),
            'name'              => $title,
            'description'       => $content,
        ]);

        return $comment;
    }

    protected function addTranslationToForm(
        CommonDBTM $item,
        string $language,
        string $key,
        string $translation,
    ): void {
        $form_translation = new FormTranslation();
        if (
            $form_translation->getFromDBByCrit([
                FormTranslation::$itemtype => $item->getType(),
                FormTranslation::$items_id  => $item->getID(),
                'language'                  => $language,
                'key'                       => $key,
            ]) === false
        ) {
            $form_translation = $this->createItem(FormTranslation::class, [
                FormTranslation::$itemtype => $item->getType(),
                FormTranslation::$items_id  => $item->getID(),
                'language'                  => $language,
                'key'                       => $key,
                'translations'              => '',
            ], ['translations']);
        }

        $this->updateItem(
            FormTranslation::class,
            $form_translation->getID(),
            [
                'translations' => [
                    'one' => $translation,
                ],
            ],
            ['translations']
        );
    }

    protected function sendFormAndGetAnswerSet(
        Form $form,
        array $answers = [],
    ): AnswersSet {
        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            if (is_numeric($answer)) {
                // Real answer will be decoded as string by default
                $answer = (string) $answer;
            }
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        return $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            getItemByTypeName(User::class, TU_USER, true)
        );
    }

    protected function sendFormAndGetCreatedTicket(
        Form $form, // We assume $form has a single "Ticket" destination
        array $answers = [],
    ): Ticket {
        $answers = $this->sendFormAndGetAnswerSet($form, $answers);

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);

        /** @var Ticket $ticket */
        $ticket = current($created_items);
        $this->assertInstanceOf(Ticket::class, $ticket);
        return $ticket;
    }

    private function exportForm(Form $form): string
    {
        return self::$serializer->exportFormsToJson([$form])->getJsonContent();
    }

    private function importForm(
        string $json,
        DatabaseMapper $mapper,
        array $skipped_forms = [],
    ): Form {
        $import_result = self::$serializer->importFormsFromJson($json, $mapper, $skipped_forms);
        $imported_forms = $import_result->getImportedForms();
        $this->assertCount(1, $imported_forms, "Failed to import form from JSON: $json");
        $form_copy = current($imported_forms);
        return $form_copy;
    }

    private function exportAndImportForm(Form $form): Form
    {
        // Export and import process
        $json = $this->exportForm($form);
        $active_entities = Session::getActiveEntities();
        $mapper = !empty($active_entities)
            ? new DatabaseMapper($active_entities)
            : new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);

        $form_copy = $this->importForm($json, $mapper, []);

        // Make sure it was not the same form object that was returned.
        $this->assertNotEquals($form_copy->getId(), $form->getId());

        // Make sure the new form really exist in the database.
        $this->assertNotFalse($form_copy->getFromDB($form_copy->getId()));

        return $form_copy;
    }

    protected function disableExistingForms(): void
    {
        $forms = (new Form())->find([]);
        foreach ($forms as $row) {
            $form = new Form();
            $form->update([
                'id' => $row['id'],
                'is_active' => false,
            ]);
        }
    }
}
