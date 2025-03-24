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
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTagProviderTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTagsForFormWithoutQuestions(): void
    {
        $form = $this->createForm(new FormBuilder());
        $this->checkTestGetTags($form, []);
    }

    public function testGetTagsForFormWithoutWithFirstAndLastNameQuestions(): void
    {
        $form = $this->getFormWithFirstAndLastNameQuestions();
        $this->checkTestGetTags($form, [
            new Tag(
                label: 'Question: First name',
                value: $this->getQuestionId($form, 'First name'),
                provider: \Glpi\Form\Tag\QuestionTagProvider::class,
            ),
            new Tag(
                label: 'Question: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: \Glpi\Form\Tag\QuestionTagProvider::class,
            ),
        ]);
    }

    private function checkTestGetTags(Form $form, array $expected): void
    {
        $tagProvider = new \Glpi\Form\Tag\QuestionTagProvider();
        $tags = $tagProvider->getTags($form);
        $this->assertEquals($expected, $tags);
    }

    public function testGetTagContentForValueUsingInvalidValue(): void
    {
        $this->checkGetTagContentForValue('not a valid question id', '');
    }

    public function testGetTagContentForValueUsingFormWithFirstAndLastNameQuestions(): void
    {
        $form = $this->getFormWithFirstAndLastNameQuestions();
        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'First name'),
            'First name'
        );
        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'Last name'),
            'Last name'
        );
    }

    private function checkGetTagContentForValue(
        string $value,
        string $expected_content
    ): void {
        $tag_provider = new \Glpi\Form\Tag\QuestionTagProvider();

        $computed_content = $tag_provider->getTagContentForValue(
            $value,
            new AnswersSet(), // Answers don't (yet) matter for this provider.
        );
        $this->assertEquals($expected_content, $computed_content);
    }

    private function getFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
