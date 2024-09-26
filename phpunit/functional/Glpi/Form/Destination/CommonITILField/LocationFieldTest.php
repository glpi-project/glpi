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

namespace tests\units\Glpi\Form\Destination\CommonITILField;

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Destination\CommonITILField\LocationFieldConfig;
use Glpi\Form\Destination\CommonITILField\LocationFieldStrategy;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Location;
use TicketTemplate;
use TicketTemplatePredefinedField;

final class LocationFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testLocationFromTemplate(): void
    {
        $from_template_config = new LocationFieldConfig(
            LocationFieldStrategy::FROM_TEMPLATE
        );

        // The default GLPI's template doesn't have a predefined location
        $this->sendFormAndAssertTicketType(
            form: $this->createAndGetFormWithMultipleLocationQuestions(),
            config: $from_template_config,
            answers: [],
            expected_location_id: 0
        );

        // Create location
        $location = $this->createItem(Location::class, [
            'name' => 'testLocationFromTemplate Location'
        ]);

        // Set the default location using predefined fields
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 83, // Location
            'value' => $location->getID(),
        ]);
        $this->sendFormAndAssertTicketType(
            form: $this->createAndGetFormWithMultipleLocationQuestions(),
            config: $from_template_config,
            answers: [],
            expected_location_id: $location->getID()
        );
    }

    public function testSpecificLocation(): void
    {
        $form = $this->createAndGetFormWithMultipleLocationQuestions();

        $locations = $this->createItems(Location::class, [
            ['name' => 'testSpecificLocation Location 1'],
            ['name' => 'testSpecificLocation Location 2'],
        ]);

        // Specific value: First location
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_VALUE,
                specific_location_id: $locations[0]->getID()
            ),
            answers: [],
            expected_location_id: $locations[0]->getID()
        );

        // Specific value: Second location
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_VALUE,
                specific_location_id: $locations[1]->getID()
            ),
            answers: [],
            expected_location_id: $locations[1]->getID()
        );
    }

    public function testLocationFromSpecificQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleLocationQuestions();

        $locations = $this->createItems(Location::class, [
            ['name' => 'testLocationFromSpecificQuestion Location 1'],
            ['name' => 'testLocationFromSpecificQuestion Location 2'],
        ]);

        // Using answer from first question
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "Location 1")
            ),
            answers: [
                "Location 1" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[0]->getID(),
                ],
                "Location 2" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[1]->getID(),
                ],
            ],
            expected_location_id: $locations[0]->getID()
        );

        // Using answer from second question
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: new LocationFieldConfig(
                strategy: LocationFieldStrategy::SPECIFIC_ANSWER,
                specific_question_id: $this->getQuestionId($form, "Location 2")
            ),
            answers: [
                "Location 1" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[0]->getID(),
                ],
                "Location 2" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[1]->getID(),
                ],
            ],
            expected_location_id: $locations[1]->getID()
        );
    }

    public function testLocationFromLastValidQuestion(): void
    {
        $form = $this->createAndGetFormWithMultipleLocationQuestions();
        $last_valid_answer_config = new LocationFieldConfig(
            LocationFieldStrategy::LAST_VALID_ANSWER
        );

        $locations = $this->createItems(Location::class, [
            ['name' => 'testLocationFromLastValidQuestion Location 1'],
            ['name' => 'testLocationFromLastValidQuestion Location 2'],
            ['name' => 'testLocationFromLastValidQuestion Location 3'],
        ]);

        // With multiple answers submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Location 1" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[0]->getID(),
                ],
                "Location 2" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[1]->getID(),
                ],
            ],
            expected_location_id: $locations[1]->getID()
        );

        // Only first answer was submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Location 1" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[0]->getID(),
                ],
            ],
            expected_location_id: $locations[0]->getID()
        );

        // Only second answer was submitted
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [
                "Location 2" => [
                    'itemtype' => Location::getType(),
                    'items_id' => $locations[1]->getID(),
                ],
            ],
            expected_location_id: $locations[1]->getID()
        );

        // No answers, fallback to default value
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_location_id: 0
        );

        // Try again with a different template value
        $this->createItem(TicketTemplatePredefinedField::class, [
            'tickettemplates_id' => getItemByTypeName(TicketTemplate::class, "Default", true),
            'num' => 83, // Location
            'value' => $locations[2]->getID(),
        ]);
        $this->sendFormAndAssertTicketType(
            form: $form,
            config: $last_valid_answer_config,
            answers: [],
            expected_location_id: $locations[2]->getID()
        );
    }

    private function sendFormAndAssertTicketType(
        Form $form,
        LocationFieldConfig $config,
        array $answers,
        int $expected_location_id,
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => ['location' => $config->jsonSerialize()]],
            ["config"],
        );

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

        // Check location_id
        $this->assertEquals($expected_location_id, $ticket->fields['locations_id']);
    }

    private function createAndGetFormWithMultipleLocationQuestions(): Form
    {
        $this->login();

        $builder = new FormBuilder();
        $builder->addQuestion("Location 1", QuestionTypeItemDropdown::class, 0, json_encode([
            'itemtype' => Location::getType(),
        ]));
        $builder->addQuestion("Location 2", QuestionTypeItemDropdown::class, 0, json_encode([
            'itemtype' => Location::getType(),
        ]));
        $builder->addDestination(
            FormDestinationTicket::class,
            "My ticket",
        );
        return $this->createForm($builder);
    }
}
