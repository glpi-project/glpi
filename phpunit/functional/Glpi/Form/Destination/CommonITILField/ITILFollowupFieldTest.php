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
use Glpi\Form\Destination\CommonITILField\ITILFollowupField;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILFollowupFieldStrategy;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use ITILFollowup;
use ITILFollowupTemplate;
use Ticket;

final class ITILFollowupFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testFollowupForNoFollowup(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $no_followup = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::NO_FOLLOWUP
        );

        // Test with no answers
        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $no_followup,
            expected_itilfollowups: []
        );
    }

    public function testFollowupForSpecificValues(): void
    {
        $templates = [
            $this->createITILFollowupTemplate('Followup template 1'),
            $this->createITILFollowupTemplate('Followup template 2'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_values = new ITILFollowupFieldConfig(
            strategy: ITILFollowupFieldStrategy::SPECIFIC_VALUES,
            specific_itilfollowuptemplates_ids: [$templates[0]->getID(), $templates[1]->getID()]
        );

        $this->sendFormAndAssertITILFollowup(
            form: $form,
            config: $specific_values,
            expected_itilfollowups: [
                $templates[0]->getID() => 'Followup template 1',
                $templates[1]->getID() => 'Followup template 2',
            ]
        );
    }

    private function sendFormAndAssertITILFollowup(
        Form $form,
        ITILFollowupFieldConfig $config,
        array $expected_itilfollowups
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [ITILFollowupField::getKey() => $config->jsonSerialize()]],
            ["config"],
        );

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

        // Check ITILFollowup
        $this->assertEquals(
            countElementsInTable(
                ITILFollowup::getTable(),
                [
                    'itemtype' => Ticket::getType(),
                    'items_id' => $ticket->getID(),
                ]
            ),
            count($expected_itilfollowups)
        );

        $itilfollowup  = new ITILFollowup();
        $itilfollowups = $itilfollowup->find([
            'itemtype' => Ticket::getType(),
            'items_id' => $ticket->getID(),
        ]);

        $this->assertEquals(
            array_values(
                array_map(
                    fn(array $itilfollowup) => strip_tags($itilfollowup['content']),
                    $itilfollowups
                )
            ),
            array_values($expected_itilfollowups)
        );
    }

    private function createAndGetFormWithMultipleDropdownItemQuestions(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }

    private function createITILFollowupTemplate(?string $content = null): ITILFollowupTemplate
    {
        return $this->createItem(ITILFollowupTemplate::class, [
            'name' => 'ITIL Followup Template',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'content' => $content ?? 'This is a followup template',
        ]);
    }
}
