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

namespace Glpi\Tests\Form\Destination;

use CommonDBTM;
use CommonGLPI;
use Computer;
use DbTestCase;
use Glpi\Form\AnswersHandler\AnswersHandler;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortAnswerText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Impact;
use ReflectionClass;

abstract class AbstractFormDestinationType extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Get the tested instance
     *
     * @return \Glpi\Form\Destination\AbstractFormDestinationType
     */
    abstract protected function getTestedInstance(): \Glpi\Form\Destination\AbstractFormDestinationType;

    /**
     * Tests for the "createDestinations" method of the FormDestinationInterface.
     * Added here to ensure implementation by child classes.
     *
     * @return void
     */
    abstract public function testCreateDestinations(): void;

    /**
     * Test the getTargetItemtype method
     *
     * @return void
     */
    final public function testGetTargetItemtype(): void
    {
        // Ensure the type defined in the child class is a valid CommonDBTM class
        $type = $this->getTestedInstance()::getTargetItemtype();
        $is_valid_class = is_a($type, CommonDBTM::class, true)
            && !(new ReflectionClass($type))->isAbstract();

        $this->boolean($is_valid_class)->isTrue();
    }

    /**
     * Test the getFilterByAnswsersSetSearchOptionID method
     *
     * @return void
     */
    final public function testGetFilterByAnswsersSetSearchOptionID(): void
    {
        $this->login();

        // Get search option ID
        $search_option_id = $this->getTestedInstance()::getFilterByAnswsersSetSearchOptionID();
        $this->integer($search_option_id)->isGreaterThan(0);

        // Compute all available search options for the target itemtype
        $created_item = new ($this->getTestedInstance()::getTargetItemtype())();
        $available_search_options = $created_item->searchOptions();

        // Ensure the search option is available for the target itemtype
        $this
            ->boolean(isset($available_search_options[$search_option_id]))
            ->isTrue()
        ;
    }

    /**
     * Data provider for the "testGetTabNameForItem" method
     *
     * @return iterable
     */
    final protected function testGetTabNameForItemProvider(): iterable
    {
        $this->login();

        $destination = $this->getTestedInstance();
        $answers_handler = AnswersHandler::getInstance();
        $tab_name = $destination::getTargetItemtype()::getTypeName();

        // Invalid types
        yield [new Computer(), false];
        yield [new Impact(), false];
        yield [new Form(), false];

        // Answers set with no destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        yield [$answers_set, $tab_name];

        // Answers set with 3 destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addDestination($destination::class, ['name' => 'destination 1'])
                ->addDestination($destination::class, ['name' => 'destination 2'])
                ->addDestination($destination::class, ['name' => 'destination 3'])
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        yield [$answers_set, $tab_name . " 3"];

        // Disable tab count
        $_SESSION['glpishow_count_on_tabs'] = false;
        yield [$answers_set, $tab_name];
    }

    /**
     * Test the getTabNameForItem method
     *
     * @dataProvider testGetTabNameForItemProvider
     *
     * @param CommonGLPI $item
     * @param string|false $expected_tab_name
     *
     * @return void
     */
    final public function testGetTabNameForItem(
        CommonGLPI $item,
        string|false $expected_tab_name
    ): void {
        $destination = $this->getTestedInstance();
        $tab_name = $destination->getTabNameForItem($item);

        if ($tab_name !== false) {
            // Strip tags to keep only the relevant data
            $tab_name = strip_tags($tab_name);
        }
        $this->variable($tab_name)->isEqualTo($expected_tab_name);
    }

    /**
     * Data provider for the "testDisplayTabContentForItem" method
     *
     * @return iterable
     */
    final protected function testDisplayTabContentForItemProvider(): iterable
    {
        $this->login();

        $destination = $this->getTestedInstance();
        $answers_handler = AnswersHandler::getInstance();

        // Invalid types
        yield [new Computer(), false];
        yield [new Impact(), false];
        yield [new Form(), false];

        // Answers set with no destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        yield [$answers_set, true];

        // Answers set with 3 destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortAnswerText::class)
                ->addDestination($destination::class, ['name' => 'destination 1'])
                ->addDestination($destination::class, ['name' => 'destination 2'])
                ->addDestination($destination::class, ['name' => 'destination 3'])
        );
        $answers_set = $answers_handler->saveAnswers($form, [
            $this->getQuestionId($form, "Name") => "Pierre Paul Jacques",
        ], \Session::getLoginUserID());
        yield [$answers_set, true];
    }

    /**
     * Test the displayTabContentForItem method
     *
     * The HTML content itselft is not tested, as it should be handled by an
     * E2E test instead.
     *
     * @dataProvider testDisplayTabContentForItemProvider
     *
     * @param CommonGLPI $item
     * @param bool $expected
     *
     * @return void
     */
    final public function testDisplayTabContentForItem(
        CommonGLPI $item,
        bool $expected
    ): void {
        $destination = $this->getTestedInstance();

        // Render tab content
        ob_start();
        $this
            ->boolean($destination->displayTabContentForItem($item))
            ->isEqualTo($expected);
        ob_end_clean();
    }
}
