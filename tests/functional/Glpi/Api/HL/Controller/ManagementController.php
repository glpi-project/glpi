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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;

class ManagementController extends \HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $management_types = [
            \Appliance::class => 'Appliance',
            \Budget::class => 'Budget',
            \Certificate::class => 'Certificate',
            \Cluster::class => 'Cluster',
            \Contact::class => 'Contact',
            \Contract::class => 'Contract',
            \Database::class => 'Database',
            \Datacenter::class => 'DataCenter',
            \Document::class => 'Document',
            \Domain::class => 'Domain',
            //\SoftwareLicense::class => 'License',
            \Line::class => 'Line',
            \Supplier::class => 'Supplier',
        ];

        $this->login('glpi', 'glpi');

        foreach ($management_types as $m_class => $m_name) {
            $request = new Request('POST', '/Management/' . $m_name);
            $request->setParameter('name', 'Test ' . $m_name);
            $request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));

            $new_item_location = null;

            // Create
            $this->api->call($request, function ($call) use ($m_name, &$new_item_location) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->headers(function ($headers) use ($m_name, &$new_item_location) {
                        $this->array($headers)->hasKey('Location');
                        $this->string($headers['Location'])->startWith("/Management/{$m_name}/");
                        $new_item_location = $headers['Location'];
                    });
            });

            // Get
            $this->api->call(new Request('GET', $new_item_location), function ($call) use ($m_name) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($m_name) {
                        $this->array($content)->hasKey('id');
                        $this->integer($content['id'])->isGreaterThan(0);
                        $this->array($content)->hasKey('name');
                        $this->string($content['name'])->isEqualTo("Test {$m_name}");
                    });
            });

            // Update
            $request = new Request('PATCH', $new_item_location);
            $request->setParameter('name', 'Test ' . $m_name . ' updated');
            $this->api->call($request, function ($call) use ($m_name) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($m_name) {
                        $this->checkSimpleContentExpect($content, ['name' => "Test {$m_name} updated"]);
                    });
            });

            // Get
            $can_be_trashed = false;
            $this->api->call(new Request('GET', $new_item_location), function ($call) use ($m_name, &$can_be_trashed) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($m_name, &$can_be_trashed) {
                        $this->checkSimpleContentExpect($content, ['name' => "Test {$m_name} updated"]);
                        $can_be_trashed = $this->array($content)->hasKey('is_deleted');
                    });
            });

            // Delete
            $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK();
            });

            if ($can_be_trashed) {
                // Verify item still exists but has is_deleted=1
                $this->api->call(new Request('GET', $new_item_location), function ($call) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK()
                        ->jsonContent(function ($content) {
                            $this->boolean((bool) $content['is_deleted'])->isTrue();
                        });
                });

                // Force delete
                $request = new Request('DELETE', $new_item_location);
                $request->setParameter('force', 1);
                $this->api->call($request, function ($call) {
                    /** @var \HLAPICallAsserter $call */
                    $call->response
                        ->isOK();
                });
            }

            // Verify item does not exist anymore
            $this->api->call(new Request('GET', $new_item_location), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isNotFoundError();
            });
        }
    }
}
