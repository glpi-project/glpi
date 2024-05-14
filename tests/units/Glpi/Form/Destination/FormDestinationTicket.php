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

namespace tests\units\Glpi\Form\Destination;

use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\Form\Destination\AbstractFormDestinationType;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Item_Ticket;
use Override;
use Ticket;

class FormDestinationTicket extends AbstractFormDestinationType
{
    use FormTesterTrait;

    #[Override]
    protected function getTestedInstance(): \Glpi\Form\Destination\FormDestinationTicket
    {
        return new \Glpi\Form\Destination\FormDestinationTicket();
    }

    #[Override]
    public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create a form with a single FormDestinationTicket destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(
                    \Glpi\Form\Destination\FormDestinationTicket::class,
                    'test',
                    [
                        'title'   => ['value' => 'Ticket title'],
                        'content' => ['value' => 'Ticket content'],
                    ]
                )
        );

        // There are no tickets in the database named after this form
        $tickets = (new Ticket())->find(['name' => 'Ticket title']);
        $this->array($tickets)->hasSize(0);

        // Submit form, a single ticket should be created
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $tickets = (new Ticket())->find(['name' => 'Ticket title']);
        $this->array($tickets)->hasSize(1);

        // Check fields
        $ticket = current($tickets);
        $this->string($ticket['content'])->isEqualTo('Ticket content');

        // Make sure link with the form answers was created too
        $ticket = array_pop($tickets);
        $links = (new Item_Ticket())->find([
            'tickets_id' => $ticket['id'],
            'items_id'   => $answers->getID(),
            'itemtype'   => $answers::getType(),
        ]);
        $this->array($links)->hasSize(1);
    }

    public function formatConfigInputNameProvider(): iterable
    {
        yield 'Simple field' => [
            'field_key' => 'title',
            'expected'  => 'config[title][value]',
        ];
        yield 'Array field' => [
            'field_key' => 'my_values[]',
            'expected'  => 'config[my_values][value][]',
        ];
    }

    /**
     * @dataProvider formatConfigInputNameProvider
     */
    public function testFormatConfigInputName(
        string $field_key,
        string $expected
    ): void {
        $input_name = $this->getTestedInstance()->formatConfigInputName(
            $field_key,
        );
        $this->string($input_name)->isEqualTo($expected);
    }
}
