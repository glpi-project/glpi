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

namespace tests\units;

use Glpi\PHPUnit\Tests\Glpi\SLMTrait;

final class TicketTemplateMandatoryFieldTest extends \DbTestCase
{
    use SLMTrait;

    public function testMandatoryOLA(): void
    {
        // arrange
        $ola = $this->createOLA()['ola'];

        $template = $this->createItem(\TicketTemplate::class, ['name' => 'Test Template']);
        $this->createItem(
            \TicketTemplateMandatoryField::class,
            [
                'tickettemplates_id' => $template->getID(),
                'num' => 190,
            ]
        );

        $invalid_valid_data = $this->getMinimalCreationInput(\Ticket::class);
        $invalid_valid_data['_tickettemplate'] = $template->getID();
        // add _tickettemplate to input fields, to allow template compliance check
        $valid_data = $invalid_valid_data + ['_olas_id' => [$ola->getID()]];

        // act & assert
        $this->assertFalse((bool) (new \Ticket())->add($invalid_valid_data), 'Add should fail without OLA');
        $this->hasSessionMessages(ERROR, array_fill(0, 1, 'Mandatory fields are not filled. Please correct: OLA time to own'));

        $this->assertTrue((bool) (new \Ticket())->add($valid_data), 'Add should succeed with OLA');
    }
}
