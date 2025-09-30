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
use Glpi\Form\Destination\CommonITILField\ITILTaskField;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldConfig;
use Glpi\Form\Destination\CommonITILField\ITILTaskFieldStrategy;
use Glpi\Form\Form;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use TaskTemplate;
use TicketTask;

final class ITILTaskFieldTest extends DbTestCase
{
    use FormTesterTrait;

    public function testTaskForNoTask(): void
    {
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $no_task = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::NO_TASK
        );

        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $no_task,
            expected_itiltasks: []
        );
    }

    public function testTaskForSpecificValues(): void
    {
        $templates = [
            $this->createTaskTemplate('Task template 1'),
            $this->createTaskTemplate('Task template 2'),
        ];
        $form = $this->createAndGetFormWithMultipleDropdownItemQuestions();
        $specific_values = new ITILTaskFieldConfig(
            strategy: ITILTaskFieldStrategy::SPECIFIC_VALUES,
            specific_itiltasktemplates_ids: [$templates[0]->getID(), $templates[1]->getID(),]
        );

        $this->sendFormAndAssertITILTask(
            form: $form,
            config: $specific_values,
            expected_itiltasks: [
                $templates[0]->getID() => 'Task template 1',
                $templates[1]->getID() => 'Task template 2',
            ]
        );
    }

    private function sendFormAndAssertITILTask(
        Form $form,
        ITILTaskFieldConfig $config,
        array $expected_itiltasks
    ): void {
        // Insert config
        $destinations = $form->getDestinations();
        $this->assertCount(1, $destinations);
        $destination = current($destinations);
        $this->updateItem(
            $destination::getType(),
            $destination->getId(),
            ['config' => [ITILTaskField::getKey() => $config->jsonSerialize()]],
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

        // Check TicketTask
        $this->assertEquals(
            countElementsInTable(
                TicketTask::getTable(),
                [
                    'tickets_id' => $ticket->getID(),
                ]
            ),
            count($expected_itiltasks)
        );

        $tickettask  = new TicketTask();
        $tickettasks = $tickettask->find([
            'tickets_id' => $ticket->getID(),
        ]);

        $this->assertEquals(
            array_values(
                array_map(
                    fn(array $tickettask) => strip_tags($tickettask['content']),
                    $tickettasks
                )
            ),
            array_values($expected_itiltasks)
        );
    }

    private function createAndGetFormWithMultipleDropdownItemQuestions(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }

    private function createTaskTemplate(?string $content = null): TaskTemplate
    {
        return $this->createItem(TaskTemplate::class, [
            'name' => 'ITIL Task Template',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'content' => $content ?? 'This is a task template',
        ]);
    }
}
