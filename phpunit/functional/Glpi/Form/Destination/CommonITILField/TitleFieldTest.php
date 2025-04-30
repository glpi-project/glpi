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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

final class TitleFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testDefaultTitle(): void
    {
        $this->sendFormAndAssertTicketTitle(
            expected_title: "My form name",
            form: $this->createAndGetFormWithFirstAndLastNameQuestions(),
            config: null,
        );
    }

    public function testSpecificTitle(): void
    {
        $this->sendFormAndAssertTicketTitle(
            expected_title: "My custom ticket title",
            form: $this->createAndGetFormWithFirstAndLastNameQuestions(),
            config: new SimpleValueConfig("My custom ticket title"),
        );
    }

    private function sendFormAndAssertTicketTitle(
        string $expected_title,
        Form $form,
        ?SimpleValueConfig $config,
    ): void {
        // Insert config
        if ($config !== null) {
            $destinations = $form->getDestinations();
            $this->assertCount(1, $destinations);
            $destination = current($destinations);
            $this->updateItem(
                $destination::getType(),
                $destination->getId(),
                ['config' => [TitleField::getKey() => $config->jsonSerialize()]],
                ["config"],
            );
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            [],
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);

        // Check request type
        $this->assertEquals($expected_title, $ticket->fields['name']);
    }

    private function createAndGetFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder("My form name");
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
