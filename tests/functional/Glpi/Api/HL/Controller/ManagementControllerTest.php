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

namespace tests\units\Glpi\Api\HL\Controller;

use Document;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Features\AssignableItemInterface;
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

    public function testCRUDNoRights()
    {
        $this->login();

        $management_types = ManagementController::getManagementTypes(false);
        foreach ($management_types as $m_class => $m) {
            $create_request = new Request('POST', '/Management/' . $m['schema_name']);
            $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
            $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
            $new_location = null;
            $new_items_id = null;
            $this->api->call($create_request, function ($call) use (&$new_location, &$new_items_id) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use (&$new_location) {
                        $new_location = $headers['Location'];
                    })
                    ->jsonContent(function ($content) use (&$new_items_id) {
                        $new_items_id = $content['id'];
                    });
            });
            $deny_create = null;
            if ($m_class === Document::class) {
                $deny_create = static function () {
                    $_SESSION['glpiactiveprofile']['document'] = ALLSTANDARDRIGHT & ~CREATE;
                    $_SESSION['glpiactiveprofile']['followup'] = 0;
                };
            }
            $this->api->autoTestCRUDNoRights(
                endpoint: '/Management/' . $m['schema_name'],
                itemtype: $m_class,
                items_id: $new_items_id,
                deny_create: $deny_create
            );
        }
    }

    public function testAssignableRights()
    {
        $management_types = ManagementController::getManagementTypes(false);
        foreach ($management_types as $m_class => $m) {
            if (!is_subclass_of($m_class, AssignableItemInterface::class)) {
                continue;
            }
            $this->api->autoTestAssignableItemRights('/Management/' . $m['schema_name'], $m_class);
        }


    }
}
