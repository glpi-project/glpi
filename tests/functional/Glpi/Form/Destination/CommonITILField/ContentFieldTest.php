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
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Ticket;

final class ContentFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testDefaultContentIsGeneratedFromQuestions(): void
    {
        $this->sendFormAndAssertTicketContentContains(
            expected_content: [
                // Contain references to all questions and their answers
                "1) First name",
                "2) Last name",
                "John",
                "Doe",
            ],
            form: $this->createAndGetFormWithFirstAndLastNameQuestions(),
            answers: [
                "First name" => "John",
                "Last name" => "Doe",
            ],
            config: null,
        );

        $this->sendFormAndAssertTicketContentContains(
            expected_content: [
                // Contain references to all questions and their answers
                "1) First name",
                "2) Last name",
                "Pierre",
                "Paul",
            ],
            form: $this->createAndGetFormWithFirstAndLastNameQuestions(),
            answers: [
                "First name" => "Pierre",
                "Last name" => "Paul",
            ],
            config: null,
        );
    }

    public function testSpecificContent(): void
    {
        $this->sendFormAndAssertTicketContentEquals(
            expected_content: "My custom ticket content",
            form: $this->createAndGetFormWithFirstAndLastNameQuestions(),
            answers: [],
            config: new SimpleValueConfig("My custom ticket content"),
        );
    }

    private function sendFormAndAssertTicketContentContains(
        array $expected_content,
        Form $form,
        array $answers,
        ?SimpleValueConfig $config,
    ): void {
        $ticket = $this->sendForm($form, $config, $answers);
        foreach ($expected_content as $expected) {
            $this->assertStringContainsString($expected, $ticket->fields['content']);
        }
    }

    private function sendFormAndAssertTicketContentEquals(
        string $expected_content,
        Form $form,
        array $answers,
        ?SimpleValueConfig $config,
    ): void {
        $ticket = $this->sendForm($form, $config, $answers);
        $this->assertEquals($expected_content, $ticket->fields['content']);
    }

    private function sendForm(
        Form $form,
        ?SimpleValueConfig $config,
        array $answers,
    ): Ticket {
        // Insert config
        if ($config !== null) {
            $destinations = $form->getDestinations();
            $this->assertCount(1, $destinations);
            $destination = current($destinations);
            $this->updateItem(
                $destination::getType(),
                $destination->getId(),
                [
                    'config' => [
                        ContentField::getKey() => $config->jsonSerialize(),
                        ContentField::getAutoConfigKey() => false,
                    ],
                ],
                ["config"],
            );
        }

        // The provider use a simplified answer format to be more readable.
        // Rewrite answers into expected format.
        $formatted_answers = [];
        foreach ($answers as $question => $answer) {
            $key = $this->getQuestionId($form, $question);
            $formatted_answers[$key] = $answer;
        }

        // Submit form
        $answers_handler = AnswersHandler::getInstance();
        $answers = $answers_handler->saveAnswers(
            $form,
            $formatted_answers,
            getItemByTypeName(\User::class, TU_USER, true)
        );

        // Get created ticket
        $created_items = $answers->getCreatedItems();
        $this->assertCount(1, $created_items);
        $ticket = current($created_items);
        $this->assertInstanceOf(Ticket::class, $ticket);

        /** @var Ticket $ticket */
        return $ticket;
    }

    private function createAndGetFormWithFirstAndLastNameQuestions(): Form
    {
        $builder = new FormBuilder("My form name");
        $builder->addQuestion("First name", QuestionTypeShortText::class);
        $builder->addQuestion("Last name", QuestionTypeShortText::class);
        return $this->createForm($builder);
    }
}
