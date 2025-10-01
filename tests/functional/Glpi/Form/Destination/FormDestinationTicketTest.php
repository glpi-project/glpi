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

namespace tests\units\Glpi\Form\Destination;

use Glpi\Form\Destination\FormDestinationTicket;
use Glpi\Tests\Form\Destination\AbstractCommonITILFormDestinationType;
use Glpi\Tests\FormTesterTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

class FormDestinationTicketTest extends AbstractCommonITILFormDestinationType
{
    use FormTesterTrait;

    #[Override]
    protected function getTestedInstance(): FormDestinationTicket
    {
        return new FormDestinationTicket();
    }

    public static function formatConfigInputNameProvider(): iterable
    {
        yield 'Simple field' => [
            'field_key' => 'title',
            'expected'  => 'config[title]',
        ];
        yield 'Array field' => [
            'field_key' => 'my_values[]',
            'expected'  => 'config[my_values][]',
        ];
    }

    #[DataProvider('formatConfigInputNameProvider')]
    public function testFormatConfigInputName(
        string $field_key,
        string $expected
    ): void {
        $input_name = $this->getTestedInstance()->formatConfigInputName(
            $field_key,
        );
        $this->assertEquals($expected, $input_name);
    }
}
