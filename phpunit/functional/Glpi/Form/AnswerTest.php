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

namespace tests\unit\Glpi\Form;

use Glpi\Form\Answer;
use Glpi\Form\Question;
use GLPITestCase;

final class AnswerTest extends GLPITestCase
{
    private const FAKE_QUESTION_ID    = 47;
    private const FAKE_QUESTION_LABEL = "My fake question label";
    private const FAKE_QUESTION_TYPE  = "My fake question type";

    public function testGetQuestionId(): void
    {
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, "");

        $this->assertEquals(self::FAKE_QUESTION_ID, $answer->getQuestionId());
    }

    public function testGetRawAnswer(): void
    {
        $fake_answer = "My raw answer";
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, $fake_answer);

        $this->assertEquals($fake_answer, $answer->getRawAnswer());
    }

    public function testGetQuestionLabel(): void
    {
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, "");

        $this->assertEquals(
            self::FAKE_QUESTION_LABEL,
            $answer->getQuestionLabel()
        );
    }

    public function testGetRawQuestionType(): void
    {
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, "");

        $this->assertEquals(self::FAKE_QUESTION_TYPE, $answer->getRawType());
    }

    public function testJsonSerialize(): void
    {
        $fake_answer = "My raw answer";
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, $fake_answer);

        $expected_decoded_json = [
            'question_id'       => self::FAKE_QUESTION_ID,
            'question_label'    => self::FAKE_QUESTION_LABEL,
            'raw_question_type' => self::FAKE_QUESTION_TYPE,
            'raw_answer'        => $fake_answer,
        ];

        $this->assertEquals($expected_decoded_json, $answer->jsonSerialize());
    }

    public function testFromDecodedJsonData(): void
    {
        $fake_answer = "My raw answer";
        $data = [
            'question_id'       => self::FAKE_QUESTION_ID,
            'question_label'    => self::FAKE_QUESTION_LABEL,
            'raw_question_type' => self::FAKE_QUESTION_TYPE,
            'raw_answer'        => $fake_answer,
        ];

        $answer = Answer::fromDecodedJsonData($data);

        $this->assertEquals(self::FAKE_QUESTION_ID, $answer->getQuestionId());
        $this->assertEquals(self::FAKE_QUESTION_LABEL, $answer->getQuestionLabel());
        $this->assertEquals($fake_answer, $answer->getRawAnswer());
        $this->assertEquals(self::FAKE_QUESTION_TYPE, $answer->getRawType());
    }

    public function testEncodeThenDecode(): void
    {
        // Encoded item must be the same when decoded
        $fake_answer = "My raw answer";
        $question = $this->getFakeQuestion();
        $answer = new Answer($question, $fake_answer);

        $json = json_encode($answer);
        $answer_copy = Answer::fromDecodedJsonData(
            json_decode($json, true)
        );

        // Copy and original should have the same values
        $this->assertEquals($answer->getQuestionId(), $answer_copy->getQuestionId());
        $this->assertEquals($answer->getQuestionLabel(), $answer_copy->getQuestionLabel());
        $this->assertEquals($answer->getRawAnswer(), $answer_copy->getRawAnswer());
        $this->assertEquals($answer->getRawType(), $answer_copy->getRawType());
    }

    private function getFakeQuestion(): Question
    {
        $question                 = new Question();
        $question->fields['id']   = self::FAKE_QUESTION_ID;
        $question->fields['name'] = self::FAKE_QUESTION_LABEL;
        $question->fields['type'] = self::FAKE_QUESTION_TYPE;
        return $question;
    }
}
