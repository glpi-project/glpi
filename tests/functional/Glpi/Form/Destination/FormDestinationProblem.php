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
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Tests\Form\Destination\AbstractFormDestinationType;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Item_Problem;
use Override;
use Problem;

class FormDestinationProblem extends AbstractFormDestinationType
{
    use FormTesterTrait;

    #[Override]
    protected function getTestedInstance(): \Glpi\Form\Destination\FormDestinationProblem
    {
        return new \Glpi\Form\Destination\FormDestinationProblem();
    }

    #[Override]
    public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create a form with a single FormDestinationProblem destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addDestination(\Glpi\Form\Destination\FormDestinationProblem::class, ['name' => 'test'])
        );

        // There are no problems in the database named after this form
        $problems = (new Problem())->find(['name' => 'Problem from form: Test form 1']);
        $this->array($problems)->hasSize(0);

        // Submit form, a single problem should be created
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $problems = (new Problem())->find(['name' => 'Problem from form: Test form 1']);
        $this->array($problems)->hasSize(1);

        // Make sure link with the form answers was created too
        $problem = array_pop($problems);
        $links = (new Item_Problem())->find([
            'problems_id' => $problem['id'],
            'items_id'   => $answers->getID(),
            'itemtype'   => $answers::getType(),
        ]);
        $this->array($links)->hasSize(1);
    }
}
