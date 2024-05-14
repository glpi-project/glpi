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

namespace tests\units\Glpi\Form;

use Computer;
use DbTestCase;
use Glpi\Form\QuestionType\AbstractQuestionTypeShortAnswer;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Glpi\Form\QuestionType\QuestionTypeLongText;
use Glpi\Form\QuestionType\QuestionTypeNumber;
use Glpi\Form\QuestionType\QuestionTypeShortText;

class Question extends DbTestCase
{
    /**
     * Data provider for testGetQuestionType method
     *
     * @return iterable
     */
    protected function testGetQuestionTypeProvider(): iterable
    {
        $question = new \Glpi\Form\Question();

        // First set of tests: valid values
        $question->fields = [
            'type' => QuestionTypeShortText::class,
        ];
        yield [$question, new QuestionTypeShortText()];

        $question->fields = [
            'type' => QuestionTypeNumber::class,
        ];
        yield [$question, new QuestionTypeNumber()];

        $question->fields = [
            'type' => QuestionTypeEmail::class,
        ];
        yield [$question, new QuestionTypeEmail()];

        $question->fields = [
            'type' => QuestionTypeLongText::class,
        ];
        yield [$question, new QuestionTypeLongText()];

        // Second set: Invalid values
        $question->fields = [
            'type' => "not a type"
        ];
        yield [$question, null];

        $question->fields = [
            'type' => Computer::class
        ];
        yield [$question, null];

        $question->fields = [
            'type' => AbstractQuestionTypeShortAnswer::class,
        ];
        yield [$question, null];
    }

    /**
     * Test the getQuestionType method
     *
     * @dataProvider testGetQuestionTypeProvider
     *
     * @param \Glpi\Form\Question $question Test subject
     * @param mixed               $expected The expected value
     *
     * @return void
     */
    public function testGetQuestionType(
        \Glpi\Form\Question $question,
        $expected,
    ): void {
        $type = $question->getQuestionType();
        $this
            ->variable($type)
            ->isEqualTo($expected)
        ;
    }
}
