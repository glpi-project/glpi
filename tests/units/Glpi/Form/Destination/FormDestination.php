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

use CommonGLPI;
use DbTestCase;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Monitor;

class FormDestination extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Data provider for the "testGetTabNameForItem" method
     *
     * @return iterable
     */
    final protected function testGetTabNameForItemProvider(): iterable
    {
        $this->login();

        // Invalid types
        yield [new Monitor(), false];
        yield [new CommonGLPI(), false];

        // Answers set with no destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
        yield [$form, "Items to create"];

        // Answers set with 4 destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(FormDestinationTicket::class, 'destination 1')
                ->addDestination(FormDestinationTicket::class, 'destination 2')
                ->addDestination(FormDestinationTicket::class, 'destination 3')
                ->addDestination(FormDestinationTicket::class, 'destination 4')
        );
        yield [$form, "Items to create 4"];

        // Disable tab count
        $_SESSION['glpishow_count_on_tabs'] = false;
        yield [$form, "Items to create"];
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
        $link = new \Glpi\Form\Destination\FormDestination();
        $tab_name = $link->getTabNameForItem($item);

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

        // Invalid types
        yield [new Monitor(), false];
        yield [new CommonGLPI(), false];

        // Answers set with no destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
        yield [$form, true];

        // Answers set with 4 destinations
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addDestination(FormDestinationTicket::class, 'destination 1')
                ->addDestination(FormDestinationTicket::class, 'destination 2')
                ->addDestination(FormDestinationTicket::class, 'destination 3')
                ->addDestination(FormDestinationTicket::class, 'destination 4')
        );
        yield [$form, true];
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
        $link = new \Glpi\Form\Destination\FormDestination();

        // Render tab content
        ob_start();
        $this
            ->boolean($link->displayTabContentForItem($item))
            ->isEqualTo($expected);
        ob_end_clean();
    }
}
