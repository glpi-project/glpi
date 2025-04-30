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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\Engine;
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Section;
use Glpi\Tests\FormBuilder;
use Override;
use tests\units\Glpi\Form\Condition\AbstractConditionHandler;

final class VisibilityConditionHandlerTest extends AbstractConditionHandler
{
    #[Override]
    public static function conditionHandlerProvider(): iterable
    {
        /**
         * Test default visibility behavior with questions
         * Visibility conditions are more tested in other test methods
         */
        yield "Is visible check" => [
            'question_type'       => QuestionTypeShortText::class,
            'condition_operator'  => ValueOperator::VISIBLE,
            'condition_value'     => null,
            'submitted_answer'    => null,
            'expected_result'     => true,
        ];

        yield "Is not visible check" => [
            'question_type'       => QuestionTypeShortText::class,
            'condition_operator'  => ValueOperator::NOT_VISIBLE,
            'condition_value'     => null,
            'submitted_answer'    => null,
            'expected_result'     => false,
        ];
    }

    /**
     * Test chained visibility conditions with questions
     */
    public function testChainedQuestionVisibility(): void
    {
        // Arrange: create a form with three questions with chained visibility conditions
        $form = new FormBuilder();

        // First question: always visible
        $form->addQuestion("Question 1", QuestionTypeShortText::class);

        // Second question: visible if Question 1 value equals "show"
        $form->addQuestion("Question 2", QuestionTypeShortText::class);
        $form->setQuestionVisibility("Question 2", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Question 1",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "show",
            ],
        ]);

        // Third question: visible if Question 2 is visible
        $form->addQuestion("Question 3", QuestionTypeShortText::class);
        $form->setQuestionVisibility("Question 3", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Question 2",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::VISIBLE,
                'value'          => null,
            ],
        ]);

        // Fourth question: visible if Question 2 is NOT visible
        $form->addQuestion("Question 4", QuestionTypeShortText::class);
        $form->setQuestionVisibility("Question 4", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Question 2",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::NOT_VISIBLE,
                'value'          => null,
            ],
        ]);

        $form = $this->createForm($form);

        // Scenario 1: Question 1 = "show" -> Question 2 visible -> Question 3 visible, Question 4 not visible
        $input1 = $this->mapInput($form, [
            'answers' => ['Question 1' => "show"],
        ]);

        $engine = new Engine($form, $input1);
        $output1 = $engine->computeVisibility();

        $q1_id = $this->getQuestionId($form, "Question 1");
        $q2_id = $this->getQuestionId($form, "Question 2");
        $q3_id = $this->getQuestionId($form, "Question 3");
        $q4_id = $this->getQuestionId($form, "Question 4");

        $this->assertTrue($output1->isQuestionVisible($q1_id), "Question 1 should be visible");
        $this->assertTrue($output1->isQuestionVisible($q2_id), "Question 2 should be visible when Question 1 = 'show'");
        $this->assertTrue($output1->isQuestionVisible($q3_id), "Question 3 should be visible when Question 2 is visible");
        $this->assertFalse($output1->isQuestionVisible($q4_id), "Question 4 should not be visible when Question 2 is visible");

        // Scenario 2: Question 1 = "hide" -> Question 2 not visible -> Question 3 not visible, Question 4 visible
        $input2 = $this->mapInput($form, [
            'answers' => ['Question 1' => "hide"],
        ]);

        $engine = new Engine($form, $input2);
        $output2 = $engine->computeVisibility();

        $this->assertTrue($output2->isQuestionVisible($q1_id), "Question 1 should be visible");
        $this->assertFalse($output2->isQuestionVisible($q2_id), "Question 2 should not be visible when Question 1 = 'hide'");
        $this->assertFalse($output2->isQuestionVisible($q3_id), "Question 3 should not be visible when Question 2 is not visible");
        $this->assertTrue($output2->isQuestionVisible($q4_id), "Question 4 should be visible when Question 2 is not visible");
    }

    /**
     * Test chained visibility conditions with comments
     */
    public function testChainedCommentVisibility(): void
    {
        // Arrange: create a form with chained visibility for comments
        $form = new FormBuilder();

        // First question: always visible
        $form->addQuestion("Question 1", QuestionTypeShortText::class);

        // First comment: always visible
        $form->addComment("Comment 1");

        // Second comment: visible if Question 1 value equals "show"
        $form->addComment("Comment 2");
        $form->setCommentVisibility("Comment 2", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Question 1",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "show",
            ],
        ]);

        // Third comment: visible if Comment 2 is visible
        $form->addComment("Comment 3");
        $form->setCommentVisibility("Comment 3", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Comment 2",
                'item_type'      => Type::COMMENT,
                'value_operator' => ValueOperator::VISIBLE,
                'value'          => null,
            ],
        ]);

        // Fourth comment: visible if Comment 2 is NOT visible
        $form->addComment("Comment 4");
        $form->setCommentVisibility("Comment 4", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Comment 2",
                'item_type'      => Type::COMMENT,
                'value_operator' => ValueOperator::NOT_VISIBLE,
                'value'          => null,
            ],
        ]);

        $form = $this->createForm($form);

        // Helper function to get comment IDs
        $getCommentId = function ($name) use ($form) {
            foreach ($form->getFormComments() as $comment) {
                if ($comment->fields['name'] === $name) {
                    return $comment->getID();
                }
            }
            return null;
        };

        // Scenario 1: Question 1 = "show" -> Comment 2 visible -> Comment 3 visible, Comment 4 not visible
        $input1 = $this->mapInput($form, [
            'answers' => ['Question 1' => "show"],
        ]);

        $engine = new Engine($form, $input1);
        $output1 = $engine->computeVisibility();

        $c1_id = $getCommentId("Comment 1");
        $c2_id = $getCommentId("Comment 2");
        $c3_id = $getCommentId("Comment 3");
        $c4_id = $getCommentId("Comment 4");

        $this->assertTrue($output1->isCommentVisible($c1_id), "Comment 1 should be visible");
        $this->assertTrue($output1->isCommentVisible($c2_id), "Comment 2 should be visible when Question 1 = 'show'");
        $this->assertTrue($output1->isCommentVisible($c3_id), "Comment 3 should be visible when Comment 2 is visible");
        $this->assertFalse($output1->isCommentVisible($c4_id), "Comment 4 should not be visible when Comment 2 is visible");

        // Scenario 2: Question 1 = "hide" -> Comment 2 not visible -> Comment 3 not visible, Comment 4 visible
        $input2 = $this->mapInput($form, [
            'answers' => ['Question 1' => "hide"],
        ]);

        $engine = new Engine($form, $input2);
        $output2 = $engine->computeVisibility();

        $this->assertTrue($output2->isCommentVisible($c1_id), "Comment 1 should be visible");
        $this->assertFalse($output2->isCommentVisible($c2_id), "Comment 2 should not be visible when Question 1 = 'hide'");
        $this->assertFalse($output2->isCommentVisible($c3_id), "Comment 3 should not be visible when Comment 2 is not visible");
        $this->assertTrue($output2->isCommentVisible($c4_id), "Comment 4 should be visible when Comment 2 is not visible");
    }

    /**
     * Test chained visibility conditions with sections
     */
    public function testChainedSectionVisibility(): void
    {
        // Arrange: create a form with chained visibility for sections
        $form = new FormBuilder();

        // First section: always visible (first section is always visible by design)
        $form->addSection("Section 1");

        // Add a question in the first section
        $form->addQuestion("Question 1", QuestionTypeShortText::class);

        // Second section: visible if Question 1 value equals "show"
        $form->addSection("Section 2");
        $form->setSectionVisibility("Section 2", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Question 1",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "show",
            ],
        ]);

        // Third section: visible if Section 2 is visible
        $form->addSection("Section 3");
        $form->setSectionVisibility("Section 3", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Section 2",
                'item_type'      => Type::SECTION,
                'value_operator' => ValueOperator::VISIBLE,
                'value'          => null,
            ],
        ]);

        // Fourth section: visible if Section 2 is NOT visible
        $form->addSection("Section 4");
        $form->setSectionVisibility("Section 4", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Section 2",
                'item_type'      => Type::SECTION,
                'value_operator' => ValueOperator::NOT_VISIBLE,
                'value'          => null,
            ],
        ]);

        $form = $this->createForm($form);

        // Helper function to get section IDs
        $getSectionId = function ($name) use ($form) {
            foreach ($form->getSections() as $section) {
                if ($section->fields['name'] === $name) {
                    return $section->getID();
                }
            }
            return null;
        };

        // Scenario 1: Question 1 = "show" -> Section 2 visible -> Section 3 visible, Section 4 not visible
        $input1 = $this->mapInput($form, [
            'answers' => ['Question 1' => "show"],
        ]);

        $engine = new Engine($form, $input1);
        $output1 = $engine->computeVisibility();

        $s1_id = $getSectionId("Section 1");
        $s2_id = $getSectionId("Section 2");
        $s3_id = $getSectionId("Section 3");
        $s4_id = $getSectionId("Section 4");

        $this->assertTrue($output1->isSectionVisible($s1_id), "Section 1 should be visible");
        $this->assertTrue($output1->isSectionVisible($s2_id), "Section 2 should be visible when Question 1 = 'show'");
        $this->assertTrue($output1->isSectionVisible($s3_id), "Section 3 should be visible when Section 2 is visible");
        $this->assertFalse($output1->isSectionVisible($s4_id), "Section 4 should not be visible when Section 2 is visible");

        // Scenario 2: Question 1 = "hide" -> Section 2 not visible -> Section 3 not visible, Section 4 visible
        $input2 = $this->mapInput($form, [
            'answers' => ['Question 1' => "hide"],
        ]);

        $engine = new Engine($form, $input2);
        $output2 = $engine->computeVisibility();

        $this->assertTrue($output2->isSectionVisible($s1_id), "Section 1 should be visible");
        $this->assertFalse($output2->isSectionVisible($s2_id), "Section 2 should not be visible when Question 1 = 'hide'");
        $this->assertFalse($output2->isSectionVisible($s3_id), "Section 3 should not be visible when Section 2 is not visible");
        $this->assertTrue($output2->isSectionVisible($s4_id), "Section 4 should be visible when Section 2 is not visible");
    }
}
