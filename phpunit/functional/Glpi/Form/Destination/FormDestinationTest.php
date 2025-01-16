<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Monitor;

final class FormDestinationTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGetTabNameForFormWithoutDestinations()
    {
        $this->login();

        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );

        $this->checkGetTabNameForItem($form, "Items to create");
    }

    public function testGetTabNameForFormWithDestinations()
    {
        $this->login();

        $_SESSION['glpishow_count_on_tabs'] = true;
        $form = $this->createAndGetFormWithFourDestinations();

        $this->checkGetTabNameForItem($form, "Items to create 4");
    }

    public function testGetTabNameForFormWithDestinationsWithoutCount()
    {
        $this->login();

        $_SESSION['glpishow_count_on_tabs'] = false;
        $form = $this->createAndGetFormWithFourDestinations();

        $this->checkGetTabNameForItem($form, "Items to create");
    }

    private function checkGetTabNameForItem(
        CommonGLPI $item,
        string $expected_tab_name
    ): void {
        $link = new FormDestination();
        $tab_name = $link->getTabNameForItem($item);

        // Strip tags to keep only the relevant data
        $tab_name = strip_tags($tab_name);

        $this->assertEquals($expected_tab_name, $tab_name);
    }

    /**
     * Test the displayTabContentForItem method
     *
     * The HTML content itselft is not tested, as it should be handled by an
     * E2E test instead.
     */
    public function testDisplayTabContentForItem(): void
    {
        $link = new FormDestination();
        $form = $this->createAndGetFormWithFourDestinations();

        // Render tab content
        ob_start();
        $return = $link->displayTabContentForItem($form);
        ob_end_clean();

        $this->assertTrue($return);
    }

    private function createAndGetFormWithFourDestinations(): Form
    {
        $builder = new FormBuilder();
        $builder->addQuestion("Name", QuestionTypeShortText::class)
            ->addDestination(FormDestinationTicket::class, 'destination 1')
            ->addDestination(FormDestinationTicket::class, 'destination 2')
            ->addDestination(FormDestinationTicket::class, 'destination 3')
            ->addDestination(FormDestinationTicket::class, 'destination 4')
        ;
        return $this->createForm($builder);
    }
}
