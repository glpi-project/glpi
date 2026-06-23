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

namespace tests\units\Glpi\Dashboard;

use CommonITILObject;
use Computer;
use Glpi\Dashboard\Filters\TicketStatusFilter;
use Glpi\Tests\DbTestCase;
use Ticket;

class TicketStatusFilterTest extends DbTestCase
{
    public function testCanBeApplied()
    {
        $this->assertTrue(TicketStatusFilter::canBeApplied(Ticket::getTable()));
        $this->assertFalse(TicketStatusFilter::canBeApplied(Computer::getTable()));
    }

    public function testGetCriteria()
    {
        // statut valide -> renvoie un WHERE sur la colonne status
        $this->assertSame(
            ['WHERE' => ['glpi_tickets.status' => CommonITILObject::INCOMING]],
            TicketStatusFilter::getCriteria('glpi_tickets', (string) CommonITILObject::INCOMING)
        );

        // valeurs vides / 'all' / '0' -> tableau vide (pas de filtre)
        $this->assertSame([], TicketStatusFilter::getCriteria('glpi_tickets', ''));
        $this->assertSame([], TicketStatusFilter::getCriteria('glpi_tickets', 'all'));
        $this->assertSame([], TicketStatusFilter::getCriteria('glpi_tickets', '0'));
    }

    public function testGetSearchCriteria()
    {
        $this->login();

        // statut valide -> renvoie un critère avec field, searchtype et value
        $criteria = TicketStatusFilter::getSearchCriteria('glpi_tickets', (string) CommonITILObject::INCOMING);
        $this->assertCount(1, $criteria);
        $this->assertSame('AND', $criteria[0]['link']);
        $this->assertSame('equals', $criteria[0]['searchtype']);
        $this->assertSame(CommonITILObject::INCOMING, $criteria[0]['value']);
        $this->assertArrayHasKey('field', $criteria[0]);

        // valeurs vides / 'all' / '0' / '-1' -> tableau vide (pas de filtre)
        $this->assertSame([], TicketStatusFilter::getSearchCriteria('glpi_tickets', ''));
        $this->assertSame([], TicketStatusFilter::getSearchCriteria('glpi_tickets', 'all'));
        $this->assertSame([], TicketStatusFilter::getSearchCriteria('glpi_tickets', '0'));
        $this->assertSame([], TicketStatusFilter::getSearchCriteria('glpi_tickets', '-1'));
    }

    public function testGetSearchCriteriaDependsOnProfile()
    {
        // super-admin
        $this->login();
        $admin_criteria = TicketStatusFilter::getSearchCriteria('glpi_tickets', (string) CommonITILObject::INCOMING);
        $this->assertNotEmpty($admin_criteria);
        $admin_field = $admin_criteria[0]['field'];

        // profil normal
        $this->login('normal', 'normal');
        $normal_criteria = TicketStatusFilter::getSearchCriteria('glpi_tickets', (string) CommonITILObject::INCOMING);
        $this->assertNotEmpty($normal_criteria);
        $normal_field = $normal_criteria[0]['field'];

        $this->assertIsInt($admin_field);
        $this->assertIsInt($normal_field);
    }

    public function testGetSearchCriteriaWithHelpdeskProfile()
    {
        //  helpdesk
        $this->login('post-only', 'postonly');

        $criteria = TicketStatusFilter::getSearchCriteria('glpi_tickets', (string) CommonITILObject::INCOMING);
        $this->assertNotEmpty($criteria);
        $this->assertSame(CommonITILObject::INCOMING, $criteria[0]['value']);
        $this->assertSame('equals', $criteria[0]['searchtype']);
    }
}
