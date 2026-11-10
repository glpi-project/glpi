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

use CommonITILObject;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeImpact;
use Glpi\ImpactLevel;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class QuestionTypeImpactTest extends DbTestCase
{
    use FormTesterTrait;

    public function testImpactAnswerIsDisplayedInTicketDescription(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("What is the impact", QuestionTypeImpact::class);
        $form = $this->createForm($builder);

        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "What is the impact" => 5, // Very high
        ]);

        $this->assertStringContainsString(
            "1) What is the impact: Very high",
            strip_tags($ticket->fields['content']),
        );
    }

    public function testEmptyImpactAnswerIsNotDisplayedAsAll(): void
    {
        $builder = new FormBuilder();
        $builder->addQuestion("What is the impact", QuestionTypeImpact::class);
        $form = $this->createForm($builder);

        // An empty dropdown is submitted as "0"
        $ticket = $this->sendFormAndGetCreatedTicket($form, [
            "What is the impact" => "0",
        ]);

        $content = strip_tags($ticket->fields['content']);
        $this->assertStringContainsString("1) What is the impact:", $content);
        $this->assertStringNotContainsString("All", $content);
    }

    public static function validateAnswerProvider(): iterable
    {
        yield 'Enabled level' => ['answer' => "4", 'expected' => true];
        yield 'Disabled level' => ['answer' => "5", 'expected' => false];
        yield 'Fractional value' => ['answer' => "2.9", 'expected' => false];
        yield 'Out of range' => ['answer' => "9", 'expected' => false];
        yield 'Not a number' => ['answer' => "high", 'expected' => false];
    }

    #[DataProvider('validateAnswerProvider')]
    public function testValidateAnswer(string $answer, bool $expected): void
    {
        global $CFG_GLPI;

        // Allow levels 1 to 4 only
        $CFG_GLPI['impact_mask'] = (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4);

        $builder = new FormBuilder();
        $builder->addQuestion("Impact", QuestionTypeImpact::class);
        $form = $this->createForm($builder);
        $question = Question::getById($this->getQuestionId($form, "Impact"));

        $result = (new QuestionTypeImpact())->validateAnswer($question, $answer);
        $this->assertSame($expected, $result->isValid());
    }

    public function testGetImpactLevels(): void
    {
        global $CFG_GLPI;

        $impact_levels = array_combine(
            range(1, 5),
            array_map(
                fn($level) => CommonITILObject::getImpactName($level),
                range(1, 5),
            )
        );

        // Allow all impact levels
        $CFG_GLPI['impact_mask'] = array_reduce(range(1, 5), fn($mask, $level) => $mask | (1 << $level), 0);
        $this->assertEquals($impact_levels, ImpactLevel::getEnabledImpactValuesForDropdown());

        // Allow only the third impact level (the third level can't be disabled)
        $CFG_GLPI['impact_mask'] = 1 << 3;
        $this->assertEquals(
            [3 => $impact_levels[3]],
            ImpactLevel::getEnabledImpactValuesForDropdown()
        );

        // Allow the three first impact levels
        $CFG_GLPI['impact_mask'] = (1 << 1) | (1 << 2) | (1 << 3);
        $this->assertEquals(
            [
                1 => $impact_levels[1],
                2 => $impact_levels[2],
                3 => $impact_levels[3],
            ],
            ImpactLevel::getEnabledImpactValuesForDropdown(),
        );
    }

    public function testFormatPredefinedValue(): void
    {
        $type = new QuestionTypeImpact();

        $this->assertEquals("1", $type->formatPredefinedValue("very low"));
        $this->assertEquals("4", $type->formatPredefinedValue("High"));
        $this->assertNull($type->formatPredefinedValue("unknown"));
    }
}
