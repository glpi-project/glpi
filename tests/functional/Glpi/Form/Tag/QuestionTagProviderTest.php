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
use Glpi\Form\Condition\LogicOperator;
use Glpi\Form\Condition\Type;
use Glpi\Form\Condition\ValueOperator;
use Glpi\Form\Condition\VisibilityStrategy;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Form\Tag\QuestionTagProvider;
use Glpi\Form\Tag\Tag;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use User;

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
                provider: new QuestionTagProvider(),
            ),
            new Tag(
                label: 'Question: Last name',
                value: $this->getQuestionId($form, 'Last name'),
                provider: new QuestionTagProvider(),
            ),
        ]);
    }

    private function checkTestGetTags(Form $form, array $expected): void
    {
        $tagProvider = new QuestionTagProvider();
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
        $answers_set = $this->sendFormAndGetAnswerSet($form, [
            'First name' => 'John',
            'Last name'  => 'Doe',
        ]);
        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'First name'),
            'First name',
            $answers_set,
        );
        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'Last name'),
            'Last name',
            $answers_set,
        );
    }

    public function testGetTagContentForValueUsingHiddenQuestion(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Request type", QuestionTypeShortText::class);
        $builder->addQuestion("Access request", QuestionTypeShortText::class);
        $builder->setQuestionVisibility("Access request", VisibilityStrategy::VISIBLE_IF, [
            [
                'logic_operator' => LogicOperator::AND,
                'item_name'      => "Request type",
                'item_type'      => Type::QUESTION,
                'value_operator' => ValueOperator::EQUALS,
                'value'          => "access",
            ],
        ]);
        $form = $this->createForm($builder);

        // Hidden question: its answer is removed before being saved, as done
        // by the submit controller.
        $answers = AnswersHandler::getInstance()->removeUnusedAnswers($form, [
            $this->getQuestionId($form, 'Request type')   => 'other',
            $this->getQuestionId($form, 'Access request') => 'some value',
        ]);
        $answers_set = AnswersHandler::getInstance()->saveAnswers(
            $form,
            $answers,
            getItemByTypeName(User::class, TU_USER, true),
        );

        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'Request type'),
            'Request type',
            $answers_set,
        );
        $this->checkGetTagContentForValue(
            $this->getQuestionId($form, 'Access request'),
            '',
            $answers_set,
        );
    }

    public function testGetTagContentForValueUsingTranslation(): void
    {
        $form = $this->getFormWithFirstAndLastNameQuestions();
        $answers_set = $this->sendFormAndGetAnswerSet($form, [
            'First name' => 'John',
        ]);
        $question = Question::getById($this->getQuestionId($form, 'First name'));
        $this->addTranslationToForm(
            $question,
            'fr_FR',
            Question::TRANSLATION_KEY_NAME,
            'Prénom'
        );

        $original_language = $_SESSION['glpilanguage'];
        try {
            $_SESSION['glpilanguage'] = 'fr_FR';
            $this->checkGetTagContentForValue(
                $this->getQuestionId($form, 'First name'),
                'Prénom',
                $answers_set,
            );

            $_SESSION['glpilanguage'] = 'de_DE'; // No translation, falls back to original
            $this->checkGetTagContentForValue(
                $this->getQuestionId($form, 'First name'),
                'First name',
                $answers_set,
            );
        } finally {
            $_SESSION['glpilanguage'] = $original_language;
        }
    }

    private function checkGetTagContentForValue(
        string $value,
        string $expected_content,
        AnswersSet $answers_set = new AnswersSet(),
    ): void {
        $tag_provider = new QuestionTagProvider();

        $computed_content = $tag_provider->getTagContentForValue(
            $value,
            $answers_set,
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
