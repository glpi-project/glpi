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

namespace tests\units\Glpi\Form\Tag;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Glpi\Form\Tag\Tag;

final class FormTagsManager extends DbTestCase
{
    use FormTesterTrait;

    public function getTagsProvider(): iterable
    {
        $form = $this->getFormWithQuestions();

        // All possible tags that may be returned for all cases.
        $tags = [
            new Tag(
                label: 'Question: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: QuestionTagProvider::class,
                color: QuestionTagProvider::ACCENT_COLOR,
            ),
            new Tag(
                label: 'Question: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: QuestionTagProvider::class,
                color: QuestionTagProvider::ACCENT_COLOR,
            ),
            new Tag(
                label: 'Answer: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: AnswerTagProvider::class,
                color: AnswerTagProvider::ACCENT_COLOR,
            ),
            new Tag(
                label: 'Answer: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: AnswerTagProvider::class,
                color: AnswerTagProvider::ACCENT_COLOR,
            )
        ];

        yield 'Without filter' => [
            'form'     => $form,
            'filter'   => "",
            'expected' => [
                $this->getTagByName($tags, 'Question: First name'),
                $this->getTagByName($tags, 'Question: Last name'),
                $this->getTagByName($tags, 'Answer: First name'),
                $this->getTagByName($tags, 'Answer: Last name'),
            ],
        ];
        yield 'With "name" filter' => [
            'form'     => $form,
            'filter'   => "name",
            'expected' => [
                $this->getTagByName($tags, 'Question: First name'),
                $this->getTagByName($tags, 'Question: Last name'),
                $this->getTagByName($tags, 'Answer: First name'),
                $this->getTagByName($tags, 'Answer: Last name'),
            ],
        ];
        yield 'With "Question" filter' => [
            'form'     => $form,
            'filter'   => "Question",
            'expected' => [
                $this->getTagByName($tags, 'Question: First name'),
                $this->getTagByName($tags, 'Question: Last name'),
            ],
        ];
        yield 'With "Answer" filter' => [
            'form'     => $form,
            'filter'   => "Answer",
            'expected' => [
                $this->getTagByName($tags, 'Answer: First name'),
                $this->getTagByName($tags, 'Answer: Last name'),
            ],
        ];
        yield 'With "First" filter' => [
            'form'     => $form,
            'filter'   => "First",
            'expected' => [
                $this->getTagByName($tags, 'Question: First name'),
                $this->getTagByName($tags, 'Answer: First name'),
            ],
        ];
        yield 'With "Last" filter' => [
            'form'     => $form,
            'filter'   => "Last",
            'expected' => [
                $this->getTagByName($tags, 'Question: Last name'),
                $this->getTagByName($tags, 'Answer: Last name'),
            ],
        ];
        yield 'With "last" filter' => [
            'form'     => $form,
            'filter'   => "last",
            'expected' => [
                // Must still match despite the case difference.
                $this->getTagByName($tags, 'Question: Last name'),
                $this->getTagByName($tags, 'Answer: Last name'),
            ],
        ];
    }

    /**
     * @dataProvider getTagsProvider
     */
    public function testGetTags(
        Form $form,
        string $filter,
        array $expected
    ): void {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $tags = $tag_manager->getTags($form, $filter);
        $this->array($tags)->isEqualTo($expected);
    }

    public function testInsertTagsContent()
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $answers_handler = AnswersHandler::getInstance();

        $form = $this->getFormWithQuestions();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);

        $tags = $tag_manager->getTags($form);
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

        $content_with_tag =
            "$first_name_question_tag->html: $first_name_answer_tag->html, "
            . "$last_name_question_tag->html: $last_name_answer_tag->html"
        ;
        $computed_content = $tag_manager->insertTagsContent(
            $content_with_tag,
            $answers
        );

        $this->string($computed_content)->isEqualTo(
            'First name: John, Last name: Smith'
        );
    }

    public function testGetTagProviders(): void
    {
        $tag_manager = new \Glpi\Form\Tag\FormTagsManager();
        $providers = $tag_manager->getTagProviders();
        $this->array($providers)->isNotEmpty();
    }

    private function getFormWithQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
