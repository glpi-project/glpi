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

namespace tests\units\Glpi\Form\Tag;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\CommentDescriptionTagProvider;
use Glpi\Form\Tag\CommentTitleTagProvider;
use Glpi\Form\Tag\FormTagProvider;
use Glpi\Form\Tag\FormTagsManager;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\SectionTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class FormTagsManagerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTags(): void
    {
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();

        // All possible tags that may be returned for all cases.
        $tags = [
            new Tag(
                label: 'Form name: First and last name form',
                value: $form->getId(),
                provider: new FormTagProvider(),
            ),
            new Tag(
                label: 'Section: Personal information',
                value: $this->getSectionId($form, 'Personal information'),
                provider: new SectionTagProvider(),
            ),
            new Tag(
                label: 'Comment title: Comment title',
                value: $this->getCommentId($form, 'Comment title'),
                provider: new CommentTitleTagProvider(),
            ),
            new Tag(
                label: 'Comment description: Comment description',
                value: $this->getCommentId($form, 'Comment title'),
                provider: new CommentDescriptionTagProvider(),
            ),
            new Tag(
                label: 'Question: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: new QuestionTagProvider(),
            ),
            new Tag(
                label: 'Question: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: new QuestionTagProvider(),
            ),
            new Tag(
                label: 'Answer: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: new AnswerTagProvider(),
            ),
            new Tag(
                label: 'Answer: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: new AnswerTagProvider(),
            ),
        ];

        // Without filter
        $this->checkGetTags($form, "", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Section: Personal information'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
            $this->getTagByName($tags, 'Comment title: Comment title'),
            $this->getTagByName($tags, 'Comment description: Comment description'),
        ]);

        // With "name" filter
        $this->checkGetTags($form, "name", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "Question" filter
        $this->checkGetTags($form, "Question", [
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Question: Last name'),
        ]);

        // With "Answer" filter
        $this->checkGetTags($form, "Answer", [
            $this->getTagByName($tags, 'Answer: First name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "First" filter
        $this->checkGetTags($form, "First", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: First name'),
            $this->getTagByName($tags, 'Answer: First name'),
        ]);

        // With "Last" filter
        $this->checkGetTags($form, "Last", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "last" filter
        $this->checkGetTags($form, "last", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
            $this->getTagByName($tags, 'Question: Last name'),
            $this->getTagByName($tags, 'Answer: Last name'),
        ]);

        // With "Form name" filter
        $this->checkGetTags($form, "Form name", [
            $this->getTagByName($tags, 'Form name: First and last name form'),
        ]);

        // With "Section" filter
        $this->checkGetTags($form, "Section", [
            $this->getTagByName($tags, 'Section: Personal information'),
        ]);

        // With "Comment" filter
        $this->checkGetTags($form, "Comment", [
            $this->getTagByName($tags, 'Comment title: Comment title'),
            $this->getTagByName($tags, 'Comment description: Comment description'),
        ]);

        // With "Comment title" filter
        $this->checkGetTags($form, "Comment title", [
            $this->getTagByName($tags, 'Comment title: Comment title'),
        ]);

        // With "Comment description" filter
        $this->checkGetTags($form, "Comment description", [
            $this->getTagByName($tags, 'Comment description: Comment description'),
        ]);
    }

    private function checkGetTags(
        Form $form,
        string $filter,
        array $expected_tags
    ): void {
        $tag_manager = new FormTagsManager();
        $tags = $tag_manager->getTags($form, $filter);
        $this->assertEquals($expected_tags, $tags);
    }

    public function testInsertTagsContent()
    {
        $tag_manager = new FormTagsManager();
        $answers_handler = AnswersHandler::getInstance();

        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);

        $tags = $tag_manager->getTags($form);
        $form_name_tag = $this->getTagByName(
            $tags,
            'Form name: First and last name form'
        );
        $section_tag = $this->getTagByName(
            $tags,
            'Section: Personal information'
        );
        $first_name_question_tag = $this->getTagByName(
            $tags,
            'Question: First name'
        );
        $first_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: First name'
        );
        $last_name_question_tag = $this->getTagByName(
            $tags,
            'Question: Last name'
        );
        $last_name_answer_tag = $this->getTagByName(
            $tags,
            'Answer: Last name'
        );
        $comment_title_tag = $this->getTagByName(
            $tags,
            'Comment title: Comment title'
        );
        $comment_description_tag = $this->getTagByName(
            $tags,
            'Comment description: Comment description'
        );

        $content_with_tag
            = "$form_name_tag->html, "
            . "$section_tag->html, "
            . "$first_name_question_tag->html: $first_name_answer_tag->html, "
            . "$last_name_question_tag->html: $last_name_answer_tag->html, "
            . "$comment_title_tag->html, "
            . "$comment_description_tag->html"
        ;
        $computed_content = $tag_manager->insertTagsContent(
            $content_with_tag,
            $answers
        );

        $this->assertEquals('First and last name form, Personal information, First name: John, Last name: Smith, Comment title, Comment description', $computed_content);
    }

    public function testGetTagProviders(): void
    {
        $tag_manager = new FormTagsManager();
        $providers = $tag_manager->getTagProviders();
        $this->assertNotEmpty($providers);
    }

    private function createAndGetFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->setName("First and last name form");
        $builder->addSection("Personal information");
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        $builder->addComment("Comment title", "Comment description");
        return $this->createForm($builder);
    }
}
