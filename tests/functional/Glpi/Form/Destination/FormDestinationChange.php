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
use Change_Item;
use Override;
use Change;

class FormDestinationChange extends AbstractFormDestinationType
{
    use FormTesterTrait;

    #[Override]
    protected function getTestedInstance(): \Glpi\Form\Destination\FormDestinationChange
    {
        return new \Glpi\Form\Destination\FormDestinationChange();
    }

    #[Override]
    public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();

        // Create a form with a single FormDestinationChange destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(\Glpi\Form\Destination\FormDestinationChange::class, ['name' => 'test'])
        );

        // There are no change in the database named after this form
        $changes = (new Change())->find(['name' => 'Change from form: Test form 1']);
        $this->array($changes)->hasSize(0);

        // Submit form, a single change should be created
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $changes = (new Change())->find(['name' => 'Change from form: Test form 1']);
        $this->array($changes)->hasSize(1);

        // Make sure link with the form answers was created too
        $change = array_pop($changes);
        $links = (new Change_Item())->find([
            'changes_id' => $change['id'],
            'items_id'   => $answers->getID(),
            'itemtype'   => $answers::getType(),
        ]);
        $this->array($links)->hasSize(1);
    }
}
