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

namespace tests\units\Glpi\Form\QuestionType;

use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionTypeSelectable;
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Tests\Form\QuestionType\AbstractQuestionTypeSelectableTest;
use Glpi\Tests\FormBuilder;
use Override;

final class QuestionTypeRadioTest extends AbstractQuestionTypeSelectableTest
{
    #[Override]
    protected function getQuestionType(): AbstractQuestionTypeSelectable
    {
        return new QuestionTypeRadio();
    }

    public function testRadioAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Your favorite software",
            type: QuestionTypeRadio::class,
            extra_data: json_encode(new QuestionTypeSelectableExtraDataConfig([
                'glpi'       => 'GLPI',
                'glpi_again' => 'GLPI again',
                'still_glpi' => 'Still GLPI',
            ]))
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Your favorite software" => 'glpi_again',
        ]);

        $this->assertStringContainsString(
            "1) Your favorite software: GLPI again",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testSinglePredefinedValueIsApplied(): void
    {
        $question = $this->createRadioQuestion(default_value: 'glpi');

        $question->setDefaultValueFromParameters([
            $question->fields['uuid'] => 'still_glpi',
        ]);

        $this->assertEquals('still_glpi', $question->fields['default_value']);
    }

    public function testMultiplePredefinedValuesAreIgnored(): void
    {
        $question = $this->createRadioQuestion(default_value: 'glpi');

        $question->setDefaultValueFromParameters([
            $question->fields['uuid'] => 'glpi_again,still_glpi',
        ]);

        // A radio question can only hold a single value: the ambiguous
        // parameter must be discarded and the configured default preserved.
        $this->assertEquals('glpi', $question->fields['default_value']);
    }

    private function createRadioQuestion(string $default_value): Question
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Your favorite software",
            type: QuestionTypeRadio::class,
            default_value: $default_value,
            extra_data: json_encode(new QuestionTypeSelectableExtraDataConfig([
                'glpi'       => 'GLPI',
                'glpi_again' => 'GLPI again',
                'still_glpi' => 'Still GLPI',
            ]))
        );
        $form = $this->createForm($builder);

        return Question::getById($this->getQuestionId($form, "Your favorite software"));
    }
}
