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

use CommonITILObject;
use DbTestCase;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class QuestionTypeUrgencyTest extends DbTestCase
{
    use FormTesterTrait;

    public function testUrgencyAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("What is the urgency", QuestionTypeUrgency::class);
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "What is the urgency" => 5, // Very high
        ]);

        $this->assertStringContainsString(
            "1) What is the urgency: Very high",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testGetUrgencyLevels(): void
    {
        global $CFG_GLPI;

        $questionType = new QuestionTypeUrgency();
        $urgency_levels = array_combine(
            range(1, 5),
            array_map(
                fn($level) => CommonITILObject::getUrgencyName($level),
                range(1, 5),
            )
        );

        // Allow all urgency levels
        $CFG_GLPI['urgency_mask'] = array_reduce(range(1, 5), fn($mask, $level) => $mask | (1 << $level), 0);
        $this->assertEquals($urgency_levels, $this->callPrivateMethod($questionType, 'getUrgencyLevels'));

        // Allow only the third urgency level (the third level can't be disabled)
        $CFG_GLPI['urgency_mask'] = 1 << 3;
        $this->assertEquals(
            [3 => $urgency_levels[3]],
            $this->callPrivateMethod($questionType, 'getUrgencyLevels')
        );

        // Allow the three first urgency levels
        $CFG_GLPI['urgency_mask'] = (1 << 1) | (1 << 2) | (1 << 3);
        $this->assertEquals(
            [
                1 => $urgency_levels[1],
                2 => $urgency_levels[2],
                3 => $urgency_levels[3],
            ],
            $this->callPrivateMethod($questionType, 'getUrgencyLevels'),
        );

        // Allow the two last urgency levels
        $CFG_GLPI['urgency_mask'] = (1 << 3) | (1 << 4) | (1 << 5);
        $this->assertEquals(
            [
                3 => $urgency_levels[3],
                4 => $urgency_levels[4],
                5 => $urgency_levels[5],
            ],
            $this->callPrivateMethod($questionType, 'getUrgencyLevels'),
        );
    }
}
