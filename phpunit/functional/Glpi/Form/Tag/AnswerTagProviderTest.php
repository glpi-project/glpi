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
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\AnswerTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class AnswerTagProviderTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTagsForEmptyForm(): void
    {
        $form = $this->createForm(new FormBuilder());
        $this->checkGetTags($form, []);
    }

    public function testGetTagsForFormWithFirstAndLastNameQuestion(): void
    {
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $this->checkGetTags($form, [
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
        ]);
    }

    private function checkGetTags(Form $form, array $expected_tags): void
    {
        $tagProvider = new AnswerTagProvider();
        $tags = $tagProvider->getTags($form);
        $this->assertEqualsCanonicalizing($expected_tags, $tags);
    }

    public function testGetTagContentForValueWithInvalidValue(): void
    {
        $this->checkGetTagContentForValue(
            value: 'not a valid question id',
            answers_set : $this->getEmptyAnswerSet(),
            expected_content: '',
        );
    }

    public function testGetTagContentForValueUsingFormWithFirstAndLastNameQuestions(): void
    {
        $answers_handler = AnswersHandler::getInstance();
        $form = $this->createAndGetFormWithFirstAndLastNameQuestions();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "First name") => "John",
            $this->getQuestionId($form, "Last name") => "Smith",
        ], 0 /* Invalid user id but we dont care for this here */);

        $this->checkGetTagContentForValue(
            value: $this->getQuestionId($form, 'First name'),
            answers_set : $answers,
            expected_content: 'John',
        );
        $this->checkGetTagContentForValue(
            value: $this->getQuestionId($form, 'Last name'),
            answers_set : $answers,
            expected_content: 'Smith',
        );
    }

    private function checkGetTagContentForValue(
        string $value,
        AnswersSet $answers_set,
        string $expected_content
    ): void {
        $tag_provider = new AnswerTagProvider();
        $computed_content = $tag_provider->getTagContentForValue(
            $value,
            $answers_set,
        );
        $this->assertEquals($expected_content, $computed_content);
    }

    private function createAndGetFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }

    private function getEmptyAnswerSet(): AnswersSet
    {
        $answers = new AnswersSet();
        $answers->fields['answers'] = json_encode([]);
        return $answers;
    }
}
