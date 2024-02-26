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

use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Tests\FormBuilder;
use Ticket;

class FormDestinationTicket extends DbTestCase
{
    /**
     * Indirectly test the \Glpi\Form\AnswersHandler\AnswersHandler->createDestinations()
     * method using a FormDestinationTicket object
     */
    public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create a form with a single FormDestinationTicket destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addDestination(\Glpi\Form\Destination\FormDestinationTicket::class, ['name' => 'test'])
        );

        // There are no tickets in the database named after this form
        $tickets = (new Ticket())->find(['name' => 'Ticket from form: Test form 1']);
        $this->array($tickets)->hasSize(0);

        // Submit form, a single ticket should be created
        $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $tickets = (new Ticket())->find(['name' => 'Ticket from form: Test form 1']);
        $this->array($tickets)->hasSize(1);
    }
}
