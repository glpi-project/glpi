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
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTagProvider extends DbTestCase
{
    use FormTesterTrait;

    public function getTagsProvider(): iterable
    {
        yield 'Form with no questions' => [
            $this->createForm(new FormBuilder()),
            []
        ];

        $form = $this->getFormWithQuestions();
        yield 'Form with questions' => [
            'form' => $form,
            'expected' => [
                new Tag(
                    label: 'Question: First name',
                    value: $this->getQuestionId($form, 'First name'),
                    provider: \Glpi\Form\Tag\QuestionTagProvider::class,
                    color: \Glpi\Form\Tag\QuestionTagProvider::ACCENT_COLOR,
                ),
                new Tag(
                    label: 'Question: Last name',
                    value: $this->getQuestionId($form, 'Last name'),
                    provider: \Glpi\Form\Tag\QuestionTagProvider::class,
                    color: \Glpi\Form\Tag\QuestionTagProvider::ACCENT_COLOR,
                ),
            ]
        ];
    }

    /**
     * @dataProvider getTagsProvider
     */
    public function testGetTags(Form $form, array $expected): void
    {
        $tagProvider = new \Glpi\Form\Tag\QuestionTagProvider();
        $tags = $tagProvider->getTags($form);
        $this->array($tags)->isEqualTo($expected);
    }

    public function getTagContentForValueProvider(): iterable
    {
        yield 'Invalid value' => [
            'value' => 'not a valid question id',
            'expected' => ''
        ];

        $form = $this->getFormWithQuestions();
        yield 'Valid value: first name question' => [
            'value' => $this->getQuestionId($form, 'First name'),
            'expected' => 'First name'
        ];
        yield 'Valid value: last name question' => [
            'value' => $this->getQuestionId($form, 'Last name'),
            'expected' => 'Last name'
        ];
    }

    /**
     * @dataProvider getTagContentForValueProvider
     */
    public function testGetTagContentForValue(
        string $value,
        string $expected
    ): void {
        $tag_provider = new \Glpi\Form\Tag\QuestionTagProvider();

        $computed_content = $tag_provider->getTagContentForValue(
            $value,
            new AnswersSet(), // Answers don't (yet) matter for this provider.
        );

        $this->string($computed_content)->isEqualTo($expected);
    }

    private function getFormWithQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
