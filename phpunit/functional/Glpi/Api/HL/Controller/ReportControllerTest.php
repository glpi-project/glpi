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

use Glpi\Http\Request;

class ReportControllerTest extends \HLAPITestCase
{
    public function testListStatisticReports()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Assistance/Stat'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->matchesSchema('StatReport[]')
                ->jsonContent(function ($content) {
                    $this->assertGreaterThan(7, count($content));
                    $tested = 0;
                    foreach ($content as $report) {
                        if (in_array($report['report_type'], ['Global', 'Asset'], true) && in_array($report['assistance_type'], ['Ticket', 'Change', 'Problem'], true)) {
                            $this->assertEmpty($report['report_group_fields']);
                            $tested++;
                        } elseif ($report['report_type'] === 'Characteristics' && in_array($report['assistance_type'], ['Ticket', 'Change', 'Problem'], true)) {
                            $this->assertCount(17, array_intersect(array_keys($report['report_group_fields']), [
                                'user', 'users_id_recipient', 'group', 'group_tree', 'usertitles_id',
                                'usercategories_id', 'itilcategories_id', 'itilcategories_tree', 'urgency', 'impact',
                                'priority', 'solutiontypes_id', 'technician', 'technician_followup', 'groups_id_assign',
                                'groups_tree_assign', 'suppliers_id_assign',
                            ]));
                            $tested++;
                        } elseif ($report['report_type'] === 'AssetCharacteristics' && in_array($report['assistance_type'], ['Ticket', 'Change', 'Problem'], true)) {
                            $this->assertCount(22, array_intersect(array_keys($report['report_group_fields']), [
                                'ComputerType', 'ComputerModel', 'OperatingSystem', 'Location', 'DeviceBattery',
                                'DeviceCamera', 'DeviceCase', 'DeviceControl', 'DeviceDrive', 'DeviceFirmware',
                                'DeviceGeneric', 'DeviceGraphicCard', 'DeviceHardDrive', 'DeviceMemory', 'DeviceNetworkCard',
                                'DevicePci', 'DevicePowerSupply', 'DeviceProcessor', 'DeviceSensor', 'DeviceSimcard',
                                'DeviceSoundCard', 'DeviceMotherboard',
                            ]));
                            $tested++;
                        }
                    }
                    $this->assertEquals(12, $tested);
                });
        });
    }

    public function testGetITILGlobalStats()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        foreach ($itil_types as $itil_type) {
            $this->api->call(new Request('GET', "/Assistance/Stat/$itil_type/Global"), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->matchesSchema('GlobalStats');
            });
        }
    }

    public function testGetITILStats()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        foreach ($itil_types as $itil_type) {
            $this->api->call(new Request('GET', "/Assistance/Stat/$itil_type/Characteristics"), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->status(fn($status) => $status === 400)
                    ->jsonContent(fn($content) => $this->assertEquals([
                        'status' => 'ERROR_INVALID_PARAMETER',
                        'title' => 'One or more parameters are invalid',
                        'detail' => null,
                        'additional_messages' => [
                            [
                                'priority' => 'error',
                                'message' => 'Missing parameter: field',
                            ],
                        ],
                    ], $content));
            });
            $request = new Request('GET', "/Assistance/Stat/$itil_type/Characteristics");
            $request->setParameter('field', 'user');
            $this->api->call($request, function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->matchesSchema('ITILStats[]');
            });
        }
    }

    public function testGetAssetStats()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        foreach ($itil_types as $itil_type) {
            $this->api->call(new Request('GET', "/Assistance/Stat/$itil_type/Asset"), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->matchesSchema('AssetStats[]');
            });
        }
    }

    public function testGetAssetCharacteristicsStats()
    {
        $this->login();
        $itil_types = ['Ticket', 'Change', 'Problem'];
        foreach ($itil_types as $itil_type) {
            $this->api->call(new Request('GET', "/Assistance/Stat/$itil_type/AssetCharacteristics"), function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->status(fn($status) => $status === 400)
                    ->jsonContent(fn($content) => $this->assertEquals([
                        'status' => 'ERROR_INVALID_PARAMETER',
                        'title' => 'One or more parameters are invalid',
                        'detail' => null,
                        'additional_messages' => [
                            [
                                'priority' => 'error',
                                'message' => 'Missing parameter: field',
                            ],
                        ],
                    ], $content));
            });
            $request = new Request('GET', "/Assistance/Stat/$itil_type/AssetCharacteristics");
            $request->setParameter('field', 'user');
            $this->api->call($request, function ($call) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->isOK()
                    ->matchesSchema('AssetCharacteristicsStats[]');
            });
        }
    }
}
