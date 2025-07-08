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

use Glpi\Api\HL\Controller\CustomAssetController;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Http\Request;
use HLAPICallAsserter;
use HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CustomAssetControllerTest extends HLAPITestCase
{
    public function testGetAssetTypes(): void
    {
        $this->assertNotEmpty(CustomAssetController::getCustomAssetTypes());
    }

    public function testIndex(): void
    {
        $definitions = AssetDefinitionManager::getInstance()->getDefinitions();
        $this->assertNotEmpty($definitions);
        $types = array_map(static fn($d) => $d->fields['system_name'], $definitions);

        $this->login();
        $this->api->call(new Request('GET', '/Assets/Custom'), function ($call) use ($types) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($types) {
                    $this->assertGreaterThanOrEqual(count($types), count($content));
                    foreach ($content as $asset) {
                        $this->assertNotEmpty($asset['itemtype']);
                        $this->assertNotEmpty($asset['name']);
                        $this->assertEquals('/Assets/Custom/' . $asset['itemtype'], $asset['href']);
                    }
                });
        });
    }

    public static function searchProvider()
    {
        return [
            ['schema' => 'Test01', 'filters' => [], 'expected' => ['count' => ['>=', 2]]],
            ['schema' => 'Test01', 'filters' => ['name==Test0'], 'expected' => ['count' => ['=', 0]]],
            ['schema' => 'Test01', 'filters' => ['name==TestA'], 'expected' => ['count' => ['=', 1]]],
            ['schema' => 'Test01', 'filters' => ['name=like=Test*'], 'expected' => ['count' => ['>=', 2]]],
            ['schema' => 'Test01', 'filters' => ['custom_fields.teststring=="Test String A"'], 'expected' => ['count' => ['=', 1]]],
        ];
    }

    #[DataProvider('searchProvider')]
    public function testSearch(string $schema, array $filters, array $expected): void
    {
        $this->login();
        $request = new Request('GET', '/Assets/Custom/' . $schema);
        $request->setParameter('filter', $filters);
        $this->api->call($request, function ($call) use ($expected) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($expected) {
                    $this->checkSimpleContentExpect($content, $expected);
                });
        });
    }

    public function testCRUD(): void
    {
        $this->api->autoTestCRUD('/Assets/Custom/Test01', [
            'custom_fields' => ['teststring' => 'Test String A'],
        ], [
            'custom_fields' => ['teststring' => 'Test String A'],
        ]);
    }
}
