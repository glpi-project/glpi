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

namespace tests\units\Glpi\Form\QuestionType;

use DbTestCase;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeDropdown;
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
            extra_data: json_encode([
                'options' => ['Blue', 'Green', 'Red', 'Yellow', 'Black'],
            ])
        );
        $builder->addDestination(FormDestinationTicket::class, "My ticket");
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Your favorite color" => ['Red'],
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
            extra_data: json_encode([
                'options' => ['Blue', 'Green', 'Red', 'Yellow', 'Black'],
                'is_multiple_dropdown' => 1,
            ])
        );
        $builder->addDestination(FormDestinationTicket::class, "My ticket");
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "Your favorite colors" => ['Red', 'Yellow'],
        ]);

        $this->assertStringContainsString(
            "1) Your favorite colors: Red, Yellow",
            strip_tags($ticket->fields['content']),
        );
    }
}
