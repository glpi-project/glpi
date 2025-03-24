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

namespace tests\units\Glpi\ContentTemplates\Parameters;

use Glpi\ContentTemplates\Parameters\KnowbaseItemParameters;

include_once __DIR__ . '/../../../../abstracts/AbstractParameters.php';

class KnowbaseItemParametersTest extends AbstractParameters
{
    public function testGetValues(): void
    {
        $this->login();

        $this->createItem('KnowbaseItem', [
            'name'        => 'kbi_testGetValues',
            'answer'      => "test answer' \"testGetValues",
        ]);

        $kbi_id = getItemByTypeName('KnowbaseItem', 'kbi_testGetValues', true);

        $parameters = new KnowbaseItemParameters();
        $values = $parameters->getValues(getItemByTypeName('KnowbaseItem', 'kbi_testGetValues'));
        $this->assertEquals(
            [
                'id'     => $kbi_id,
                'name'   => 'kbi_testGetValues',
                'answer' => "test answer' \"testGetValues",
                'link'   => '<a href="/glpi/front/knowbaseitem.form.php?id=' . $kbi_id . '" title="kbi_testGetValues">kbi_testGetValues</a>',
            ],
            $values
        );

        $this->testGetAvailableParameters($values, $parameters->getAvailableParameters());
    }
}
