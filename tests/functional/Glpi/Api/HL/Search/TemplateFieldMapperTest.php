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

namespace tests\units\Glpi\Api\HL\Search;

use Change;
use Glpi\Api\HL\Search\SearchOptionMapper;
use Glpi\Api\HL\Search\TemplateFieldMapper;
use Glpi\Tests\HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Ticket;
use TicketTemplateHiddenField;

class TemplateFieldMapperTest extends HLAPITestCase
{
    public function setUp(): void
    {
        parent::setUp();
        SearchOptionMapper::clearCache();
        TemplateFieldMapper::clearCache();
    }

    public static function itilTypeAndKindProvider(): iterable
    {
        foreach ([Ticket::class, Change::class, Problem::class] as $itil_type) {
            foreach (TemplateFieldMapper::KINDS as $kind) {
                yield "$itil_type $kind" => [$itil_type, $kind];
            }
        }
    }

    public function testGetFieldClass(): void
    {
        $this->assertSame(TicketTemplateHiddenField::class, TemplateFieldMapper::getFieldClass('Ticket', 'Hidden'));
        $this->assertNull(TemplateFieldMapper::getFieldClass('Ticket', 'Unknown'));
        $this->assertNull(TemplateFieldMapper::getFieldClass('Computer', 'Hidden'));
        $this->assertNull(TemplateFieldMapper::getTemplateClass('Computer'));
        $this->assertSame([], TemplateFieldMapper::getAllowedFields('Computer', 'Hidden'));
    }

    #[DataProvider('itilTypeAndKindProvider')]
    public function testNamesAreUniqueAndRoundTrip(string $itil_type, string $kind): void
    {
        $this->login();
        $allowed = TemplateFieldMapper::getAllowedFields($itil_type, $kind);
        $this->assertNotEmpty($allowed);
        $this->assertSame(count($allowed), count(array_unique($allowed)), 'Two template fields have the same name');
        foreach ($allowed as $num => $name) {
            $this->assertSame($name, TemplateFieldMapper::getNameForNum($itil_type, $kind, $num));
            $this->assertSame($num, TemplateFieldMapper::getNumForName($itil_type, $kind, $name));
        }
    }

    public function testPseudoFields(): void
    {
        $this->login();
        $this->assertSame(4, TemplateFieldMapper::getNumForName('Ticket', 'Hidden', 'requester_user'));
        $this->assertSame(71, TemplateFieldMapper::getNumForName('Ticket', 'Hidden', 'requester_group'));
        $this->assertSame('observer_user', TemplateFieldMapper::getNameForNum('Ticket', 'Hidden', 66));
        // Approval requests can be mandatory but not predefined
        $this->assertSame('approval_request', TemplateFieldMapper::getNameForNum('Ticket', 'Mandatory', -2));
        $this->assertNull(TemplateFieldMapper::getNameForNum('Ticket', 'Predefined', -2));
    }

    public function testExcludedFields(): void
    {
        $this->login();
        // Ticket tasks cannot be hidden and the title cannot be read-only
        $this->assertNull(TemplateFieldMapper::getNameForNum('Ticket', 'Hidden', 175));
        $this->assertNull(TemplateFieldMapper::getNameForNum('Ticket', 'Readonly', 1));
        $this->assertSame('name', TemplateFieldMapper::getNameForNum('Ticket', 'Hidden', 1));
    }

    public function testUnknownNames(): void
    {
        $this->login();
        $this->assertNull(TemplateFieldMapper::getNumForName('Ticket', 'Hidden', 'not_a_field'));
        $this->assertNull(TemplateFieldMapper::getNameForNum('Ticket', 'Hidden', 987654));
    }
}
