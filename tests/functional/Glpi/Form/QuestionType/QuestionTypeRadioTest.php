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
use Glpi\Form\QuestionType\QuestionTypeRadio;
use Glpi\Form\QuestionType\QuestionTypeSelectableExtraDataConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTypeRadioTest extends DbTestCase
{
    use FormTesterTrait;

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
}
