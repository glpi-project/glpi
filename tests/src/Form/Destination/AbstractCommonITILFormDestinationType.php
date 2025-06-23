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

namespace Glpi\Tests\Form\Destination;

use CommonDBTM;
use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\CommonITILField\ContentField;
use Glpi\Form\Destination\CommonITILField\SimpleValueConfig;
use Glpi\Form\Destination\CommonITILField\TitleField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;

abstract class AbstractCommonITILFormDestinationType extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Get the tested instance
     *
     * @return AbstractCommonITILFormDestination
     */
    abstract protected function getTestedInstance(): AbstractCommonITILFormDestination;

    final public function testCreateDestinations(): void
    {
        $this->login();
        $answers_handler = AnswersHandler::getInstance();
        $target = $this->getTestedInstance()->getTarget();

        $title_field = new TitleField();
        $content_field = new ContentField();

        // Create a form with a single FormDestinationTicket destination
        $form = $this->createForm(
            (new FormBuilder("Test form 1"))
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(
                    $this->getTestedInstance()::class,
                    'test',
                    [
                        $title_field->getKey()             => (new SimpleValueConfig("My title"))->jsonSerialize(),
                        $title_field->getAutoConfigKey()   => 0,
                        $content_field->getKey()           => (new SimpleValueConfig("My content"))->jsonSerialize(),
                        $content_field->getAutoConfigKey() => 0,
                    ]
                )
        );

        // There are no tickets in the database named after this form
        $itil_items = $target->find(['name' => 'My title']);
        $this->assertCount(0, $itil_items);

        // Submit form, a single itil item should be created
        $answers = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "My name",
        ], \Session::getLoginUserID());
        $itil_items = $target->find(['name' => 'My title']);
        $this->assertCount(1, $itil_items);

        // Check fields
        $itil_item = current($itil_items);
        $this->assertEquals('My content', $itil_item['content']);
    }

    final public function testGetTargetItemtype(): void
    {
        // Ensure the type defined in the child class is a valid CommonDBTM class
        $target = $this->getTestedInstance()->getTarget();
        $is_valid_class = is_a($target, CommonDBTM::class);

        $this->assertTrue($is_valid_class);
    }

    /**
     * Test the "renderConfigForm" method.
     *
     * The HTML content itself is not validated (as it should be done by an E2E
     * test instead), we just make sure the function run without errors.
     */
    final public function testRenderConfigForm(): void
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addDestination($this->getTestedInstance()::class, 'Test destination')
        );
        $destination = FormDestination::getById($this->getDestinationId($form, 'Test destination'));
        $concrete_destination = $this->getTestedInstance();
        $html = $concrete_destination->renderConfigForm($this->getSimpleForm(), $destination, []);
        $this->assertNotEmpty($html);
    }

    private function getSimpleForm(): Form
    {
        $builder = new FormBuilder();
        return $this->createForm($builder);
    }

    private function getAnswersOfFormWithNoDestination(): AnswersSet
    {
        $this->login();
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $answers_handler = AnswersHandler::getInstance();
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        return $answers_set;
    }

    private function getAnswersOfFormWithThreeDestination(): AnswersSet
    {
        $this->login();
        $destination = $this->getTestedInstance();
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination($destination::class, 'destination 1')
                ->addDestination($destination::class, 'destination 2')
                ->addDestination($destination::class, 'destination 3')
        );

        $answers_handler = AnswersHandler::getInstance();
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        return $answers_set;
    }
}
