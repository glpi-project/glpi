<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\FullFormTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

use function Safe\json_encode;

final class FullFormTagProviderTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTagsForForm(): void
    {
        $form = $this->createForm(new FormBuilder());

        $tag_provider = new FullFormTagProvider();
        $tags = $tag_provider->getTags($form);

        $this->assertEquals([
            new Tag(
                label: 'Full form (all questions and answers)',
                value: $form->getId(),
                provider: $tag_provider,
            ),
        ], $tags);
    }

    public function testGetTagContentForValueUsingInvalidValue(): void
    {
        $tag_provider = new FullFormTagProvider();

        $computed_content = $tag_provider->getTagContentForValue(
            'not a valid form id',
            $this->getEmptyAnswerSet(),
        );

        $this->assertEquals('', $computed_content);
    }

    public function testGetTagContentForValueResolvesQuestionsAndAnswers(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion('First name', QuestionTypeShortText::class);
        $builder->addQuestion('Last name', QuestionTypeShortText::class);
        $form = $this->createForm($builder);

        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, 'First name') => 'John',
            $this->getQuestionId($form, 'Last name') => 'Smith',
        ], 0 /* Invalid user id but we dont care for this here */);

        $tag_provider = new FullFormTagProvider();
        $computed_content = $tag_provider->getTagContentForValue(
            (string) $form->getId(),
            $answers,
        );

        // The generated content must not contain any unresolved question or
        // answer tag, only their final rendered values.
        $this->assertStringNotContainsString('data-form-tag="true"', $computed_content);
        $this->assertStringContainsString('First name', $computed_content);
        $this->assertStringContainsString('Last name', $computed_content);
        $this->assertStringContainsString('John', $computed_content);
        $this->assertStringContainsString('Smith', $computed_content);
    }

    private function getEmptyAnswerSet(): AnswersSet
    {
        $answers = new AnswersSet();
        $answers->fields['answers'] = json_encode([]);
        return $answers;
    }
}
