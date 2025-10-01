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

namespace tests\units\Glpi\Form\Clone;

use Computer;
use DbTestCase;
use Entity;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\Category;
use Glpi\Form\Comment;
use Glpi\Form\Condition\CreationStrategy;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValidationStrategy;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Destination\CommonITILField\AssigneeField;
use Glpi\Form\Destination\CommonITILField\AssigneeFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsField;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldConfig;
use Glpi\Form\Destination\CommonITILField\AssociatedItemsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\EntityField;
use Glpi\Form\Destination\CommonITILField\EntityFieldConfig;
use Glpi\Form\Destination\CommonITILField\EntityFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILActorFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ITILCategoryField;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILCategoryFieldStrategy;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsField;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldConfig;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldStrategy;
use Glpi\Form\Destination\CommonITILField\LinkedITILObjectsFieldStrategyConfig;
use Glpi\Form\Destination\CommonITILField\LocationField;
use Glpi\Form\Destination\CommonITILField\LocationFieldConfig;
use Glpi\Form\Destination\CommonITILField\LocationFieldStrategy;
use Glpi\Form\Destination\CommonITILField\ObserverField;
use Glpi\Form\Destination\CommonITILField\ObserverFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequesterField;
use Glpi\Form\Destination\CommonITILField\RequesterFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeField;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldConfig;
use Glpi\Form\Destination\CommonITILField\RequestTypeFieldStrategy;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\CommonITILField\UrgencyField;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldConfig;
use Glpi\Form\Destination\CommonITILField\UrgencyFieldStrategy;
use Glpi\Form\Destination\FormDestinationChange;
use Glpi\Form\Destination\FormDestinationProblem;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\FormTranslation;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeActorsExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Form\QuestionType\QuestionTypeDateTimeExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Form\QuestionType\QuestionTypeItemDropdownExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeItemExtraDataConfig;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Form\QuestionType\QuestionTypeRequester;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Form\RenderLayout;
use Glpi\Form\Section;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\CommentDescriptionTagProvider;
use Glpi\Form\Tag\CommentTitleTagProvider;
use Glpi\Form\Tag\FormTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\SectionTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILCategory;
use Location;
use PHPUnit\Framework\Attributes\DataProvider;
use RequestType;
use Ticket;

final class FormCloneHelperTest extends DbTestCase
{
    use FormTesterTrait;

    public function testCloneSimpleForm(): void
    {
        $target_entity_id = getItemByTypeName(Entity::class, "_test_child_3", true);

        // Arrange: create a very basic form
        // The goal here is to ensure that basic properties that require
        // no specific handling are copied successfully.
        $category = $this->createItem(Category::class, [
            'name' => "My category",
        ]);

        $form = $this->createItem(Form::class, [
            'entities_id'                       => $target_entity_id,
            'is_recursive'                      => true,
            'is_active'                         => true,
            'is_deleted'                        => true,
            'is_draft'                          => true,
            'is_pinned'                         => true,
            'render_layout'                     => RenderLayout::SINGLE_PAGE->value,
            'name'                              => "My form",
            'header'                            => "My header",
            'illustration'                      => "my-illustration",
            'description'                       => "My description",
            'forms_categories_id'               => $category->getID(),
            'submit_button_visibility_strategy' => VisibilityStrategy::VISIBLE_IF->value,
        ]);

        // Act: clone the form
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        // Assert: the form should be cloned succesfully
        $this->assertTrue((bool) $clone->fields['is_recursive']);
        $this->assertTrue((bool) $clone->fields['is_active']);
        $this->assertTrue((bool) $clone->fields['is_deleted']);
        $this->assertTrue((bool) $clone->fields['is_draft']);
        $this->assertTrue((bool) $clone->fields['is_pinned']);
        $this->assertEquals(
            RenderLayout::SINGLE_PAGE->value,
            $clone->fields['render_layout']
        );
        $this->assertEquals("My form (copy)", $clone->fields['name']);
        $this->assertEquals("My header", $clone->fields['header']);
        $this->assertEquals("my-illustration", $clone->fields['illustration']);
        $this->assertEquals("My description", $clone->fields['description']);
        $this->assertEquals(
            $category->getID(),
            $clone->fields['forms_categories_id']
        );
        $this->assertEquals(
            VisibilityStrategy::VISIBLE_IF->value,
            $clone->fields['submit_button_visibility_strategy']
        );
    }

    public function testNewFormUuidIsGeneratedWhenCloning(): void
    {
        // Arrange: create a form
        $form = $this->createItem(Form::class, [
            'uuid' => '12345',
        ]);

        // Act: clone form
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        // Assert:a new uuid should be generated for the cloned form
        $this->assertNotEquals('12345', $clone->fields['uuid']);
        $this->assertNotEmpty($clone->fields['uuid']);
    }

    public function testFormUsageCountIsResetWhenCloning(): void
    {
        // Arrange: create a form with a defined value for usage_count
        $builder = new FormBuilder("My form");
        $builder->setUsageCount(100);
        $form = $this->createForm($builder);

        // Act: clone form
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        // Assert: usage count should be reset on the new form
        $this->assertEquals(100, $form->fields['usage_count']);
        $this->assertEquals(0, $clone->fields['usage_count']);
    }

    public function testFormSubmitButtonConditionsWhenCloning(): void
    {
        // Arrange: create a form with a submit condition
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "Are you ready to submit?",
            QuestionTypeShortText::class,
        );
        $builder->setSubmitButtonVisibility(VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Are you ready to submit?",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "Yes",
            ],
        ]);
        $form = $this->createForm($builder);

        // Act: clone the form and get the cloned conditions
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_conditions = $clone->getConfiguredConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the question uuid referenced in the condition data must match
        // the cloned question uuid, not the original question.
        $this->assertEquals(
            $cloned_question->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public function testCloneFormWithSimpleSection(): void
    {
        // Arrange: create a form with a section.
        // The goal here is to ensure that basic properties that require
        // no specific handling are copied successfully.
        $builder = new FormBuilder("My form");
        $builder->addSection("My section name");
        $form = $this->createForm($builder);
        $this->updateItem(
            Section::class,
            $this->getSectionId($form, "My section name"),
            [
                'description'         => 'My section description',
                'rank'                => 5,
                'visibility_strategy' => VisibilityStrategy::HIDDEN_IF->value,
            ],
        );

        // Act: clone form and get the cloned section
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        // Assert: the section should be cloned correctly
        $this->assertEquals("My section name", $cloned_section->fields['name']);
        $this->assertEquals(
            "My section description",
            $cloned_section->fields['description'],
        );
        $this->assertEquals(5, $cloned_section->fields['rank']);
        $this->assertEquals(
            VisibilityStrategy::HIDDEN_IF->value,
            $cloned_section->fields['visibility_strategy'],
        );
        $this->assertEquals(
            $clone->getID(),
            $cloned_section->fields['forms_forms_id'],
        );
    }

    public function testNewSectionUuidIsGeneratedWhenCloning(): void
    {
        // Arrange: create a form with a section
        $builder = new FormBuilder("My form");
        $builder->addSection("My section name");
        $form = $this->createForm($builder);
        $section = Section::getById(
            $this->getSectionId($form, "My section name")
        );

        // Act: clone form and get the cloned section
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        // Assert: uuid should be different from original section
        $this->assertNotEquals(
            $section->fields['uuid'],
            $cloned_section->fields['uuid'],
        );
        $this->assertNotEmpty($cloned_section->fields['uuid']);
    }

    public function testSectionVisibilityConditionsWhenCloning(): void
    {
        // Arrange: create a form with a visibility condition on a section
        $builder = new FormBuilder("My form");
        $builder->addSection("Section 1");
        $builder->addQuestion(
            "Do you want to see the next section?",
            QuestionTypeShortText::class,
        );
        $builder->addSection("Section 2");
        $builder->setSectionVisibility(
            "Section 2",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Do you want to see the next section?",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: clone the form and get the cloned section's conditions
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_sections = $clone->getSections();
        $cloned_section_2 = array_pop($cloned_sections);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        $cloned_conditions = $cloned_section_2->getConfiguredConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        // Assert: the question uuid referenced in the condition data must match
        // the cloned question uuid, not the original question.
        $this->assertEquals(
            $cloned_question->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public function testCloneFormWithSimpleQuestion(): void
    {
        // Arrange: create a form with a question.
        // The goal here is to ensure that basic properties that require
        // no specific handling are copied successfully.
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "My question name",
            QuestionTypeDateTime::class
        );
        $form = $this->createForm($builder);
        $this->updateItem(
            Question::class,
            $this->getQuestionId($form, "My question name"),
            [
                'is_mandatory'    => true,
                'vertical_rank'   => 10,
                'horizontal_rank' => 5,
                'description'     => 'My question description',
                'default_value'   => '14:30',
                'extra_data'      => json_encode(
                    new QuestionTypeDateTimeExtraDataConfig(
                        is_date_enabled: false,
                        is_time_enabled: true
                    )
                ),
                'visibility_strategy' => VisibilityStrategy::HIDDEN_IF->value,
                'validation_strategy' => ValidationStrategy::VALID_IF->value,
            ],
        );

        // Act: clone form and get the cloned question
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the question should be cloned correctly
        $this->assertEquals(
            "My question name",
            $cloned_question->fields['name'],
        );
        $this->assertEquals(
            QuestionTypeDateTime::class,
            $cloned_question->fields['type'],
        );
        $this->assertTrue((bool) $cloned_question->fields['is_mandatory']);
        $this->assertEquals(10, $cloned_question->fields['vertical_rank']);
        $this->assertEquals(5, $cloned_question->fields['horizontal_rank']);
        $this->assertEquals(
            "My question description",
            $cloned_question->fields['description'],
        );
        $this->assertEquals("14:30", $cloned_question->fields['default_value']);
        $this->assertEquals(
            json_encode(
                new QuestionTypeDateTimeExtraDataConfig(
                    is_date_enabled: false,
                    is_time_enabled: true
                )
            ),
            $cloned_question->fields['extra_data']
        );
        $this->assertEquals(
            VisibilityStrategy::HIDDEN_IF->value,
            $cloned_question->fields['visibility_strategy'],
        );
        $this->assertEquals(
            ValidationStrategy::VALID_IF->value,
            $cloned_question->fields['validation_strategy'],
        );
    }

    public function testNewQuestionUuidIsGeneratedWhenCloning(): void
    {
        // Arrange: create a form with a question
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "My question name",
            QuestionTypeDateTime::class
        );
        $form = $this->createForm($builder);
        $question = Question::getById(
            $this->getQuestionId($form, "My question name")
        );

        // Act: clone form and get the cloned question
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: uuid should be different from original question
        $this->assertNotEquals(
            $question->fields['uuid'],
            $cloned_question->fields['uuid'],
        );
        $this->assertNotEmpty($cloned_question->fields['uuid']);
    }

    public function testQuestionParentUuidIsCorrectWhenCloning(): void
    {
        // Arrange: create a form with a uuid
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "My question name",
            QuestionTypeDateTime::class
        );
        $form = $this->createForm($builder);

        // Act: clone form and get the cloned question
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the forms_sections_uuid field should match the parent section
        $this->assertEquals(
            $cloned_section->fields['uuid'],
            $cloned_question->fields['forms_sections_uuid'],
        );
    }

    public function testQuestionVisibilityConditionsWhenCloning(): void
    {
        // Arrange: create a form with a visibility condition on a question
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "Do you want to see the next question?",
            QuestionTypeShortText::class,
        );
        $builder->addQuestion(
            "The next question",
            QuestionTypeShortText::class,
        );
        $builder->setQuestionVisibility(
            "The next question",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Do you want to see the next question?",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: clone the form and get the cloned question's condition
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_questions = $clone->getQuestions();
        $cloned_question_2 = array_pop($cloned_questions);
        $cloned_question_1 = array_pop($cloned_questions);

        $cloned_conditions = $cloned_question_2->getConfiguredConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        // Assert: the question uuid referenced in the condition data must match
        // the cloned question uuid, not the original question.
        $this->assertEquals(
            $cloned_question_1->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question_1->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public function testQuestionValidationConditionsWhenCloning(): void
    {
        // Arrange: create a form with a validation condition on a question
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "This question is valid if you say yes",
            QuestionTypeShortText::class,
        );
        $builder->setQuestionValidation(
            "This question is valid if you say yes",
            ValidationStrategy::VALID_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "This question is valid if you say yes",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: clone the form and get the cloned question's condition
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        $cloned_conditions = $cloned_question->getConfiguredValidationConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        // Assert: the question uuid referenced in the condition data must match
        // the cloned question uuid, not the original question.
        $this->assertEquals(
            $cloned_question->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public function testCloneFormWithSimpleComment(): void
    {
        // Arrange: create a form with a comment.
        // The goal here is to ensure that basic properties that require
        // no specific handling are copied successfully.
        $builder = new FormBuilder("My form");
        $builder->addComment("My comment name");
        $form = $this->createForm($builder);
        $this->updateItem(
            Comment::class,
            $this->getCommentId($form, "My comment name"),
            [
                'description'     => 'My comment description',
                'vertical_rank'   => 8,
                'horizontal_rank' => 12,
                'visibility_strategy' => VisibilityStrategy::VISIBLE_IF->value,
            ],
        );

        // Act: clone form and get the cloned comment
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_comments = $clone->getFormComments();
        $cloned_comment = array_pop($cloned_comments);

        // Assert: the comment should be cloned correctly
        $this->assertEquals(
            "My comment name",
            $cloned_comment->fields['name'],
        );
        $this->assertEquals(
            "My comment description",
            $cloned_comment->fields['description'],
        );
        $this->assertEquals(8, $cloned_comment->fields['vertical_rank']);
        $this->assertEquals(12, $cloned_comment->fields['horizontal_rank']);
        $this->assertEquals(
            VisibilityStrategy::VISIBLE_IF->value,
            $cloned_comment->fields['visibility_strategy'],
        );
    }

    public function testNewCommentUuidIsGeneratedWhenCloning(): void
    {
        // Arrange: create a form with a question
        $builder = new FormBuilder("My form");
        $builder->addComment("My comment name");
        $form = $this->createForm($builder);
        $comment = Comment::getById(
            $this->getCommentId($form, "My comment name")
        );

        // Act: clone form and get the cloned comment
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_comments = $clone->getFormComments();
        $cloned_comment = array_pop($cloned_comments);

        // Assert: uuid should be different from original comment
        $this->assertNotEquals(
            $comment->fields['uuid'],
            $cloned_comment->fields['uuid'],
        );
        $this->assertNotEmpty($cloned_comment->fields['uuid']);
    }

    public function testCommentParentUuidIsCorrectWhenCloning(): void
    {
        // Arrange: create a form with a uuid
        $builder = new FormBuilder("My form");
        $builder->addComment("My comment name");
        $form = $this->createForm($builder);

        // Act: clone form and get the cloned comment
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        $cloned_comments = $clone->getFormComments();
        $cloned_comment = array_pop($cloned_comments);

        // Assert: the forms_sections_uuid field should match the parent section
        $this->assertEquals(
            $cloned_section->fields['uuid'],
            $cloned_comment->fields['forms_sections_uuid'],
        );
    }

    public function testCommentVisibilityConditionsWhenCloning(): void
    {
        // Arrange: create a form with a visibility condition on a question
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "Do you want to see the next comment?",
            QuestionTypeShortText::class,
        );
        $builder->addComment("The next comment");
        $builder->setCommentVisibility(
            "The next comment",
            VisibilityStrategy::VISIBLE_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Do you want to see the next comment?",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: clone the form and get the comment's condition
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        $clone_comments = $clone->getFormComments();
        $clone_comment = array_pop($clone_comments);

        $cloned_conditions = $clone_comment->getConfiguredConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        // Assert: the question uuid referenced in the condition data must match
        // the cloned question uuid, not the original question.
        $this->assertEquals(
            $cloned_question->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public function testAllowListAccessControlIsCopiedWhenCloning(): void
    {
        // Arrange: create a form with a specific allow list
        $builder = new FormBuilder("My form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(AllowList::class, new AllowListConfig(
            user_ids: [1, 2, 3],
            group_ids: [4, 5, 6],
            profile_ids: [7, 8, 9],
        ));
        $form = $this->createForm($builder);

        // Act: clone form and get its access policices
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_policies = $clone->getAccessControls();
        $cloned_policy = array_pop($cloned_policies);
        $cloned_config = $cloned_policy->getConfig();

        // Assert: the policy should exist with the same config
        $this->assertInstanceOf(AllowListConfig::class, $cloned_config);
        /** @var AllowListConfig $cloned_config */
        $this->assertEquals([1, 2, 3], $cloned_config->getUserIds());
        $this->assertEquals([4, 5, 6], $cloned_config->getGroupIds());
        $this->assertEquals([7, 8, 9], $cloned_config->getProfileIds());
    }

    public function testDirectAccessAccessControlIsCopiedWhenCloning(): void
    {
        // Arrange: create a form with a specific direct access config
        $builder = new FormBuilder("My form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(DirectAccess::class, new DirectAccessConfig(
            allow_unauthenticated: true,
        ));
        $form = $this->createForm($builder);

        // Act: clone form and get its access policices
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_policies = $clone->getAccessControls();
        $cloned_policy = array_pop($cloned_policies);
        $cloned_config = $cloned_policy->getConfig();

        $original_policies = $form->getAccessControls();
        $original_policy = array_pop($original_policies);
        $original_config = $original_policy->getConfig();

        // Assert: the policy should exist with the same config
        $this->assertInstanceOf(DirectAccessConfig::class, $cloned_config);
        $this->assertInstanceOf(DirectAccessConfig::class, $original_config);

        /** @var DirectAccessConfig $cloned_config */
        /** @var DirectAccessConfig $original_config */
        $this->assertTrue($cloned_config->allowUnauthenticated());
        $this->assertNotEmpty($cloned_config->getToken());
        $this->assertNotEquals( // Special case: the token must change
            $original_config->getToken(),
            $cloned_config->getToken(),
        );
    }

    public function testFormDestinationsAreCopiedWhenCloning(): void
    {
        // Arrange: create a form with a destination
        $builder = new FormBuilder("My form"); // A default destination will be added
        $builder->addDestination(FormDestinationChange::class, "Change");
        $builder->addDestination(FormDestinationProblem::class, "Problem");
        $form = $this->createForm($builder);

        // Act: clone form and get its destinations
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_destinations = array_values($clone->getDestinations());

        // Assert: the 3 destinations should exist
        $this->assertInstanceOf(
            FormDestinationTicket::class,
            $cloned_destinations[0]->getConcreteDestinationItem(),
        );
        $this->assertEquals(
            "Ticket",
            $cloned_destinations[0]->fields['name'],
        );
        $this->assertInstanceOf(
            FormDestinationChange::class,
            $cloned_destinations[1]->getConcreteDestinationItem(),
        );
        $this->assertEquals(
            "Change",
            $cloned_destinations[1]->fields['name'],
        );
        $this->assertInstanceOf(
            FormDestinationProblem::class,
            $cloned_destinations[2]->getConcreteDestinationItem(),
        );
        $this->assertEquals(
            "Problem",
            $cloned_destinations[2]->fields['name'],
        );
    }

    public function testFormDestinationsConditionsWhenCloning(): void
    {
        // Arrange: create a form with a conditional destination
        $builder = new FormBuilder("My form");
        $builder->addQuestion(
            "Do you want to create a change?",
            QuestionTypeShortText::class,
        );
        $builder->addDestination(FormDestinationChange::class, "Change");
        $builder->setDestinationCondition(
            "Change",
            CreationStrategy::CREATED_IF,
            [
                [
                    'logic_operator' => LogicOperator::AND,
                    'item_name'      => "Do you want to create a change?",
                    'item_type'      => Type::QUESTION,
                    'value_operator' => ValueOperator::EQUALS,
                    'value'          => "Yes",
                ],
            ]
        );
        $form = $this->createForm($builder);

        // Act: clone the form and get its destinations condition
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_destinations = $clone->getDestinations();
        $cloned_destination = array_pop($cloned_destinations);

        $cloned_conditions = $cloned_destination->getConfiguredConditionsData();
        $cloned_condition = array_pop($cloned_conditions);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the condition UUID should match the cloned question.
        $this->assertEquals(
            $cloned_question->fields['uuid'],
            $cloned_condition->getItemUuid(),
        );
        $this->assertEquals(
            "question-" . $cloned_question->fields['uuid'],
            $cloned_condition->getItemDropdownKey(),
        );
    }

    public static function formDestinationQuestionIdIsUpdatedWhenCloningProvider(): iterable
    {
        // This provider contain all itil fields configuration that rely on a
        // question id.

        yield [
            'question_type' => QuestionTypeItem::class,
            'question_extra_data' => new QuestionTypeItemExtraDataConfig(
                Entity::class
            ),
            'destination_config_key' => EntityField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new EntityFieldConfig(
                strategy: EntityFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $question_id,
            ),
            'get_assert_value' => fn(EntityFieldConfig $config) => $config->getSpecificQuestionId(),
        ];

        yield [
            'question_type' => QuestionTypeItemDropdown::class,
            'question_extra_data' => new QuestionTypeItemDropdownExtraDataConfig(
                ITILCategory::class
            ),
            'destination_config_key' => ITILCategoryField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new ITILCategoryFieldConfig(
                strategy: ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $question_id,
            ),
            'get_assert_value' => fn(ITILCategoryFieldConfig $config) => $config->getSpecificQuestionId(),
        ];

        yield [
            'question_type' => QuestionTypeItemDropdown::class,
            'question_extra_data' => new QuestionTypeItemDropdownExtraDataConfig(
                Location::class
            ),
            'destination_config_key' => LocationField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $question_id,
            ),
            'get_assert_value' => fn(LocationFieldConfig $config) => $config->getSpecificQuestionId(),
        ];

        yield [
            'question_type' => QuestionTypeItemDropdown::class,
            'question_extra_data' => new QuestionTypeItemDropdownExtraDataConfig(
                RequestType::class
            ),
            'destination_config_key' => RequestTypeField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new RequestTypeFieldConfig(
                strategy: RequestTypeFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $question_id,
            ),
            'get_assert_value' => fn(RequestTypeFieldConfig $config) => $config->getSpecificQuestionId(),
        ];

        yield [
            'question_type' => QuestionTypeUrgency::class,
            'question_extra_data' => null,
            'destination_config_key' => UrgencyField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new UrgencyFieldConfig(
                strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $question_id,
            ),
            'get_assert_value' => fn(UrgencyFieldConfig $config) => $config->getSpecificQuestionId(),
        ];
    }

    #[DataProvider('formDestinationQuestionIdIsUpdatedWhenCloningProvider')]
    public function testFormDestinationQuestionIdIsUpdatedWhenCloning(
        string $question_type,
        ?JsonFieldInterface $question_extra_data,
        string $destination_config_key,
        callable $get_destination_config_value,
        callable $get_assert_value,
    ): void {
        // Note: this test is hard to read but it is hard to do better here as this
        // case require heavy configuration and we are forced to delay some parts
        // with callables.

        // Arrange: create a form with a destination that rely on a fkey
        $builder = new FormBuilder("My form");
        $data = $question_extra_data === null ? null : json_encode($question_extra_data->jsonSerialize());
        $builder->addQuestion("Test question", $question_type, extra_data: $data);
        $form = $this->createForm($builder);

        $destinations = $form->getDestinations();
        $destination = array_pop($destinations);
        $destination_config_value = $get_destination_config_value(
            $this->getQuestionId($form, "Test question"),
        );
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            [
                'config' => [
                    $destination_config_key => $destination_config_value->jsonSerialize(),
                ],
            ],
            ["config"],
        );

        // Act: clone form and get its destinations
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_destinations = $clone->getDestinations();
        $cloned_destination = array_pop($cloned_destinations);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the cloned question must be referenced in the config
        $this->assertEquals(
            $cloned_question->fields['id'],
            $get_assert_value($destination_config_value::jsonDeserialize(
                $cloned_destination->getConfig()[$destination_config_key]
            )),
        );
    }

    public static function formDestinationQuestionIdsAreUpdatedWhenCloningProvider(): iterable
    {
        // This provider contain all itil fields configuration that rely on a
        // question ids.

        yield [
            'question_type' => QuestionTypeItem::class,
            'question_extra_data' => new QuestionTypeItemExtraDataConfig(
                Computer::class
            ),
            'destination_config_key' => AssociatedItemsField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new AssociatedItemsFieldConfig(
                strategies: [AssociatedItemsFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$question_id],
            ),
            'get_assert_value' => fn(AssociatedItemsFieldConfig $config) => $config->getSpecificQuestionIds(),
        ];

        yield [
            'question_type' => QuestionTypeRequester::class,
            'question_extra_data' => new QuestionTypeActorsExtraDataConfig(),
            'destination_config_key' => RequesterField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new RequesterFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$question_id],
            ),
            'get_assert_value' => fn(RequesterFieldConfig $config) => $config->getSpecificQuestionIds(),
        ];

        yield [
            'question_type' => QuestionTypeObserver::class,
            'question_extra_data' => new QuestionTypeActorsExtraDataConfig(),
            'destination_config_key' => ObserverField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new ObserverFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$question_id],
            ),
            'get_assert_value' => fn(ObserverFieldConfig $config) => $config->getSpecificQuestionIds(),
        ];

        yield [
            'question_type' => QuestionTypeAssignee::class,
            'question_extra_data' => new QuestionTypeActorsExtraDataConfig(),
            'destination_config_key' => AssigneeField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new AssigneeFieldConfig(
                strategies: [ITILActorFieldStrategy::SPECIFIC_ANSWERS],
                specific_question_ids: [$question_id],
            ),
            'get_assert_value' => fn(AssigneeFieldConfig $config) => $config->getSpecificQuestionIds(),
        ];

        yield [
            'question_type' => QuestionTypeItem::class,
            'question_extra_data' => new QuestionTypeItemExtraDataConfig(
                Ticket::class,
            ),
            'destination_config_key' => LinkedITILObjectsField::getKey(),
            'get_destination_config_value' => fn(int $question_id) => new LinkedITILObjectsFieldConfig(
                [
                    new LinkedITILObjectsFieldStrategyConfig(
                        strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_ANSWERS,
                        specific_question_ids: [$question_id],
                    ),
                ]
            ),
            'get_assert_value' => fn(LinkedITILObjectsFieldConfig $config) => $config->getStrategyConfigByIndex(0)->getSpecificQuestionIds(),
        ];
    }

    #[DataProvider('formDestinationQuestionIdsAreUpdatedWhenCloningProvider')]
    public function testFormDestinationQuestionIdsAreUpdatedWhenCloning(
        string $question_type,
        JsonFieldInterface $question_extra_data,
        string $destination_config_key,
        callable $get_destination_config_value,
        callable $get_assert_value,
    ): void {
        // Note: this test is hard to read but it is hard to do better here as this
        // case require heavy configuration and we are forced to delay some parts
        // with callables.

        // Arrange: create a form with a destination that rely on a fkey
        $builder = new FormBuilder("My form");
        $data = json_encode($question_extra_data->jsonSerialize());
        $builder->addQuestion("Test question", $question_type, extra_data: $data);
        $form = $this->createForm($builder);

        $destinations = $form->getDestinations();
        $destination = array_pop($destinations);
        $destination_config_value = $get_destination_config_value(
            $this->getQuestionId($form, "Test question"),
        );
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            [
                'config' => [
                    $destination_config_key => $destination_config_value->jsonSerialize(),
                ],
            ],
            ["config"],
        );

        // Act: clone form and get its destinations
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_destinations = $clone->getDestinations();
        $cloned_destination = array_pop($cloned_destinations);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the cloned question must be referenced in the config
        $this->assertEquals(
            [$cloned_question->fields['id']],
            $get_assert_value($destination_config_value::jsonDeserialize(
                $cloned_destination->getConfig()[$destination_config_key]
            )),
        );
    }

    public function testFormDestinationOtherDestinationsIdsAreUpdatedWhenCloning(): void
    {
        // Arrange: create a form with a destination that rely on another
        // destination id
        $builder = new FormBuilder("My form");
        $builder->addDestination(FormDestinationTicket::class, "Ticket 2");
        $form = $this->createForm($builder);

        $destinations = $form->getDestinations();
        $ticket2_destination = array_pop($destinations);
        $config = [
            LinkedITILObjectsField::getKey() => (new LinkedITILObjectsFieldConfig(
                [
                    new LinkedITILObjectsFieldStrategyConfig(
                        strategy: LinkedITILObjectsFieldStrategy::SPECIFIC_DESTINATIONS,
                        specific_destination_ids: [
                            $this->getDestinationId($form, "Ticket"),
                        ],
                    ),
                ]
            ))->jsonSerialize(),
        ];
        $this->updateItem(
            $ticket2_destination::class,
            $ticket2_destination->getId(),
            [
                'config' => $config,
            ],
            ["config"],
        );

        // Act: clone form and get its destinations
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_destinations = $clone->getDestinations();
        $cloned_ticket2_destination = array_pop($cloned_destinations);
        $cloned_ticket1_destination = array_pop($cloned_destinations);

        // Assert: the cloned destination must be referenced in the config
        $this->assertEquals(
            [$cloned_ticket1_destination->fields['id']],
            $cloned_ticket2_destination->getConfig()[LinkedITILObjectsField::getKey()]['strategy_configs'][0]['specific_destination_ids']
        );
    }

    public function testIdsInFormTagsAreUpdatedWhenCloning(): void
    {
        $form_tag_provider        = new FormTagProvider();
        $section_tag_provider     = new SectionTagProvider();
        $question_tag_provider    = new QuestionTagProvider();
        $answers_tag_provider     = new AnswerTagProvider();
        $comment_tag_provider     = new CommentTitleTagProvider();
        $description_tag_provider = new CommentDescriptionTagProvider();

        // Arrange: create a form and set up some fields using tags
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $builder->addComment("My first comment");
        $builder->addComment("My second comment");
        $form = $this->createForm($builder);

        $destinations = $form->getDestinations();
        $destination = array_pop($destinations);
        $config = [
            // Use the form name in the title
            TitleField::getKey() => (new SimpleValueConfig(
                $form_tag_provider->getTags($form)[0]->html
            ))->jsonSerialize(),
            // Use section, questions and comment data in the content
            ContentField::getKey() => (new SimpleValueConfig(
                $section_tag_provider->getTags($form)[0]->html
                . $question_tag_provider->getTags($form)[0]->html
                . $answers_tag_provider->getTags($form)[0]->html
                . $comment_tag_provider->getTags($form)[0]->html
                . $comment_tag_provider->getTags($form)[1]->html // Add a second one to make sure it works with more than 1 tag of the same type
                . $description_tag_provider->getTags($form)[0]->html
                . $description_tag_provider->getTags($form)[1]->html
            ))->jsonSerialize(),
        ];
        $this->updateItem(
            $destination::class,
            $destination->getId(),
            [
                'config' => $config,
            ],
            ["config"],
        );

        // Act: clone form and get its content
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        $cloned_destinations = $clone->getDestinations();
        $cloned_destination = array_pop($cloned_destinations);

        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        $cloned_comments = $clone->getFormComments();
        $cloned_comment_1 = array_pop($cloned_comments);
        $cloned_comment_2 = array_pop($cloned_comments);

        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        // Assert: the id in the tags of the cloned title and content fields must
        // target the cloned form, not the original form.
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$clone->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\FormTagProvider\"",
            $cloned_destination->getConfig()[TitleField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_section->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\SectionTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_question->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\QuestionTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_question->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\AnswerTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_comment_1->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentTitleTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_comment_2->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentTitleTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_comment_1->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentDescriptionTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
        $this->assertStringContainsString(
            "data-form-tag-value=\"{$cloned_comment_2->getID()}\" data-form-tag-provider=\"Glpi\Form\Tag\CommentDescriptionTagProvider\"",
            $cloned_destination->getConfig()[ContentField::getKey()]['value'],
        );
    }

    public function testFormTranslationsAreCopiedWhenCloning(): void
    {
        // Arrange: create a form with a translation
        $builder = new FormBuilder("My form");
        $form = $this->createForm($builder);
        $this->addTranslationToForm(
            $form,
            "fr_FR",
            Form::TRANSLATION_KEY_NAME,
            "Mon formulaire",
        );

        // Act: clone form
        $form_id = $form->clone();
        $clone = Form::getById($form_id);

        // Assert: the translated form name must be as expected
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals(
            "Mon formulaire",
            $clone->getServiceCatalogItemTitle()
        );
    }

    public function testSectionTranslationsAreCopiedWhenCloning(): void
    {
        // Arrange: create a section with a translation
        $builder = new FormBuilder("My form");
        $builder->addSection("My section");
        $form = $this->createForm($builder);
        $sections = $form->getSections();
        $this->addTranslationToForm(
            array_pop($sections),
            "fr_FR",
            Section::TRANSLATION_KEY_NAME,
            "Ma section",
        );

        // Act: clone form and get the section
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_sections = $clone->getSections();
        $cloned_section = array_pop($cloned_sections);

        // Assert: the translated form section must be as expected
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals(
            "Ma section",
            FormTranslation::translate(
                $cloned_section,
                Section::TRANSLATION_KEY_NAME,
            ),
        );
    }

    public function testQuestionTranslationsAreCopiedWhenCloning(): void
    {
        // Arrange: create a question with a translation
        $builder = new FormBuilder("My form");
        $builder->addQuestion("My question", QuestionTypeShortText::class);
        $form = $this->createForm($builder);
        $questions = $form->getQuestions();
        $this->addTranslationToForm(
            array_pop($questions),
            "fr_FR",
            Question::TRANSLATION_KEY_NAME,
            "Ma question",
        );

        // Act: clone form and get the section
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_questions = $clone->getQuestions();
        $cloned_question = array_pop($cloned_questions);

        // Assert: the translated form section must be as expected
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals(
            "Ma question",
            FormTranslation::translate(
                $cloned_question,
                Question::TRANSLATION_KEY_NAME,
            ),
        );
    }

    public function testCommentTranslationsAreCopiedWhenCloning(): void
    {
        // Arrange: create a comment with a translation
        $builder = new FormBuilder("My form");
        $builder->addComment("My comment");
        $form = $this->createForm($builder);
        $comments = $form->getFormComments();
        $this->addTranslationToForm(
            array_pop($comments),
            "fr_FR",
            Comment::TRANSLATION_KEY_NAME,
            "Mon commentaire",
        );

        // Act: clone form and get the section
        $form_id = $form->clone();
        $clone = Form::getById($form_id);
        $cloned_comments = $clone->getFormComments();
        $cloned_comment = array_pop($cloned_comments);

        // Assert: the translated form section must be as expected
        $_SESSION['glpilanguage'] = 'fr_FR';
        $this->assertEquals(
            "Mon commentaire",
            FormTranslation::translate(
                $cloned_comment,
                Comment::TRANSLATION_KEY_NAME,
            ),
        );
    }
}
