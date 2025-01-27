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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;

class ManagementControllerTest extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $management_types = [
            'Budget', 'Cluster', 'Contact', 'Contract', 'Database',
            'DataCenter', 'Document', 'Domain', 'Line', 'Supplier',
        ];

        foreach ($management_types as $m_name) {
            $this->api->autoTestCRUD('/Management/' . $m_name);
        }
    }

    public function testDocumentDownload()
    {
        $this->login();
        // Not sure we can mock a file upload to actually test the download. At least we need to check the endpoint exists.
        $this->assertTrue($this->api->hasMatch(new Request('GET', '/Management/Document/1/Download')));
    }
}
