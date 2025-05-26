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

namespace tests\units\Glpi\Form\QuestionType;

use DbTestCase;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
use Glpi\Form\QuestionType\QuestionTypeDropdownExtraDataConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTypeDropdownTest extends DbTestCase
{
    use FormTesterTrait;

    public function testSingleValueDropdownAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Your favorite color",
            type: QuestionTypeDropdown::class,
            extra_data: json_encode(new QuestionTypeDropdownExtraDataConfig([
                'blue'   => 'Blue',
                'green'  => 'Green',
                'red'    => 'Red',
                'yellow' => 'Yellow',
                'black'  => 'Black',
            ]))
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Your favorite color" => ['red'],
        ]);

        $this->assertStringContainsString(
            "1) Your favorite color: Red",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testMultipleValuesDropdownAnswerAreDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion(
            name: "Your favorite colors",
            type: QuestionTypeDropdown::class,
            extra_data: json_encode(new QuestionTypeDropdownExtraDataConfig([
                'blue'   => 'Blue',
                'green'  => 'Green',
                'red'    => 'Red',
                'yellow' => 'Yellow',
                'black'  => 'Black',
            ], is_multiple_dropdown: true))
        );
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Your favorite colors" => ['red', 'yellow'],
        ]);

        $this->assertStringContainsString(
            "1) Your favorite colors: Red, Yellow",
            strip_tags($ticket->fields['content']),
        );
    }
}
