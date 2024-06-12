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

use Glpi\Tests\Form\Destination\AbstractFormDestinationType;
use Glpi\Tests\FormTesterTrait;
use Override;

class FormDestinationTicket extends AbstractFormDestinationType
{
    use FormTesterTrait;

    #[Override]
    protected function getTestedInstance(): \Glpi\Form\Destination\FormDestinationTicket
    {
        return new \Glpi\Form\Destination\FormDestinationTicket();
    }

    public function formatConfigInputNameProvider(): iterable
    {
        yield 'Simple field' => [
            'field_key' => 'title',
            'expected'  => 'config[title][value]',
        ];
        yield 'Array field' => [
            'field_key' => 'my_values[]',
            'expected'  => 'config[my_values][value][]',
        ];
    }

    /**
     * @dataProvider formatConfigInputNameProvider
     */
    public function testFormatConfigInputName(
        string $field_key,
        string $expected
    ): void {
        $input_name = $this->getTestedInstance()->formatConfigInputName(
            $field_key,
        );
        $this->string($input_name)->isEqualTo($expected);
    }
}
