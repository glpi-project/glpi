<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Tests\AbstractITILTemplatePredefinedFieldTest;
use Glpi\Tests\Glpi\SLMTrait;
use ITILTemplatePredefinedField;
use TicketTemplatePredefinedField;

final class TicketTemplatePredefinedFieldTest extends AbstractITILTemplatePredefinedFieldTest
{
    use SLMTrait;

    public function getConcreteClass(): ITILTemplatePredefinedField
    {
        return new TicketTemplatePredefinedField();
    }

    public function testPredefinedOLA(): void
    {
        // arrange
        $ola = $this->createOLA()['ola'];

        $template = $this->createItem(\TicketTemplate::class, ['name' => 'Test Template']);
        $this->createItem(
            TicketTemplatePredefinedField::class,
            [
                'tickettemplates_id' => $template->getID(),
                'num' => 190,
                'value' => $ola->getID(),
            ]
        );
        $template->getFromDBWithData($template->getID());
        //        $this->reloadItem($template);

        $ticket = new \Ticket();
        $default_values = $ticket->getDefaultValues();
        $options = ['_olas_id_tto' => []];

        $reflectionMethod = new \ReflectionMethod(\Ticket::class, 'setPredefinedFields');
        $r = new \ReflectionClass($ticket);
        $m = $r->getMethod('setPredefinedFields');//->setAccessible(true);
        $m->invokeArgs($ticket, [$template, &$options, $default_values]);

        $this->assertArrayHasKey('_olas_id', $ticket->fields);
        $this->assertEqualsCanonicalizing([$ola->getID()], $ticket->fields['_olas_id']);
    }
}
