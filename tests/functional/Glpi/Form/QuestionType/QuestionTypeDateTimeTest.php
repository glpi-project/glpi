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

use Glpi\Form\QuestionType\QuestionTypeDateTime;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTypeDateTimeTest extends DbTestCase
{
    use FormTesterTrait;

    public function testQuestionWithoutExtraDataDoesNotRaise(): void
    {
        // `glpi_forms_questions.extra_data` is nullable, and anything that
        // creates a question without supplying it -- the REST API, an import,
        // a migration -- leaves NULL there. Reading it must not raise.
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "When did it start?",
            type: QuestionTypeDateTime::class,
            extra_data: null,
        );
        $form = $this->createForm($builder);

        // getQuestions() preserves ids as keys, so getQuestionId() addresses
        // the question directly -- and keeps the type as Question rather than
        // Question|false.
        $question = $form->getQuestions()[$this->getQuestionId($form, "When did it start?")];
        $this->assertNull($question->fields['extra_data']);

        $type = new QuestionTypeDateTime();

        // Each of these reads extra_data; with a NULL value they used to raise
        // a TypeError out of Safe\json_decode(), which the surrounding
        // `catch (JsonException)` does not catch -- taking the whole form
        // render down with an HTTP 500 rather than just this question.
        $this->assertFalse($type->isDefaultValueCurrentTime($question));
        $this->assertTrue($type->isDateEnabled($question));
        $this->assertFalse($type->isTimeEnabled($question));
        $this->assertSame('date', $type->getInputType($question));
    }

    public function testDateTimeAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Date and time",
            type: QuestionTypeDateTime::class,
            extra_data: json_encode([
                'is_date_enabled' => 1,
                'is_time_enabled' => 1,
            ])
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Date and time" => "2024-08-22T10:13",
        ]);

        $this->assertStringContainsString(
            "1) Date and time: 2024-08-22 10:13",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testDateAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Date",
            type: QuestionTypeDateTime::class,
            extra_data: json_encode([
                'is_date_enabled' => 1,
                'is_time_enabled' => 0,
            ])
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Date" => "2024-08-22",
        ]);

        $this->assertStringContainsString(
            "1) Date: 2024-08-22",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testTimeAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Time",
            type: QuestionTypeDateTime::class,
            extra_data: json_encode([
                'is_date_enabled' => 0,
                'is_time_enabled' => 1,
            ])
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Time" => "23:10",
        ]);

        $this->assertStringContainsString(
            "1) Time: 23:10",
            strip_tags($ticket->fields['content']),
        );
    }
}
