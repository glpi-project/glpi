<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Tests\HLAPITestCase;
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
            'custom_fields' => [
                'teststring' => 'Test String A',
                'customtagmulti' => null,
                'customtagsingle' => null,
                'testboolean' => null,
            ],
        ]);
    }

    public function testCRUDNoRights()
    {
        $this->api->autoTestCRUDNoRights(
            endpoint: '/Assets/Custom/Test01',
            itemtype: 'Glpi\\CustomAsset\\Test01Asset',
            items_id: getItemByTypeName('Glpi\\CustomAsset\\Test01Asset', 'TestA', true)
        );
    }

    public function testAssignableRights()
    {
        $this->api->autoTestAssignableItemRights('/Assets/Custom/Test01', 'Glpi\\CustomAsset\\Test01Asset');
    }

    public function testAssetModelAndTypeProperties()
    {
        $this->login();

        $asset_1 = getItemByTypeName('Glpi\\CustomAsset\\Test01Asset', 'TestB', true);
        $model_1 = getItemByTypeName('Glpi\\CustomAsset\\Test01AssetModel', 'Test01Model01', true);
        $type_1 = getItemByTypeName('Glpi\\CustomAsset\\Test01AssetType', 'Test01Type01', true);

        $this->api->call(new Request('GET', '/Assets/Custom/Test01/' . $asset_1), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNull($content['model']);
                    $this->assertNull($content['type']);
                });
        });

        $request = new Request('PATCH', '/Assets/Custom/Test01/' . $asset_1);
        $request->setParameter('model', $model_1);
        $request->setParameter('type', $type_1);
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });
        $this->api->call(new Request('GET', '/Assets/Custom/Test01/' . $asset_1), function ($call) use ($model_1, $type_1) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($model_1, $type_1) {
                    $this->assertEquals($model_1, $content['model']['id']);
                    $this->assertEquals($type_1, $content['type']['id']);
                });
        });
    }

    public function testModelsAndTypesDiscriminated()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Assets/Custom/Test01Model'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('Test01Model01', $content[0]['name']);
                });
        });
        $this->api->call(new Request('GET', '/Assets/Custom/Test02Model'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('Test02Model01', $content[0]['name']);
                });
        });

        $this->api->call(new Request('GET', '/Assets/Custom/Test01Type'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('Test01Type01', $content[0]['name']);
                });
        });
        $this->api->call(new Request('GET', '/Assets/Custom/Test02Type'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('Test02Type01', $content[0]['name']);
                });
        });
    }

    /**
     * Resolve the path a picture is stored at from the URL the API exposes for it.
     */
    private function getPicturePathFromUrl(string $picture_url): string
    {
        // Toolbox::getPictureUrl() urlencodes the reference, so the URL carries "_pictures%2F"
        $this->assertStringContainsString('_pictures%2F', $picture_url);
        $reference = substr($picture_url, strpos($picture_url, '_pictures%2F') + strlen('_pictures%2F'));
        return GLPI_PICTURE_DIR . '/' . urldecode($reference);
    }

    /**
     * Selective picture removal must work for custom asset models too.
     * The values of "pictures_remove" have to reach AssetImage::managePicturesHLAPI() under that exact input
     * name: mapping them to anything else makes the removal a silent no-op answered with a 200.
     */
    public function testRemovePictureFromCustomAssetModel(): void
    {
        $first_picture_content = file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/bar.png');
        $second_picture_content = file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->login();

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="name"

custom_asset_model_with_pictures
-----boundary
Content-Disposition: form-data; name="pictures_upload"; filename="bar.png"
Content-Type: image/png

$first_picture_content
-----boundary
Content-Disposition: form-data; name="pictures_upload"; filename="foo.png"
Content-Type: image/png

$second_picture_content
-----boundary--
EOT;

        $request = new Request('POST', '/Assets/Custom/Test01Model', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $new_location = null;
        $this->api->call($request, function ($call) use (&$new_location) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$new_location) {
                    $new_location = $content['href'];
                });
        });

        $pictures = [];
        $this->api->call(new Request('GET', $new_location), function ($call) use (&$pictures) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$pictures) {
                    $this->assertCount(2, $content['pictures']);
                    $pictures = $content['pictures'];
                });
        });

        $removed_path = $this->getPicturePathFromUrl($pictures[0]);
        $kept_path = $this->getPicturePathFromUrl($pictures[1]);
        $this->assertFileExists($removed_path);
        $this->assertFileExists($kept_path);

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="pictures_remove[]"

{$pictures[0]}
-----boundary--
EOT;

        $request = new Request('PATCH', $new_location, [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);

        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', $new_location), function ($call) use ($pictures) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($pictures) {
                    $this->assertCount(1, $content['pictures']);
                    $this->assertEquals($pictures[1], array_values($content['pictures'])[0]);
//                    $this->assertEquals([$pictures[1]], $content['pictures'], var_export([
//                        $pictures[1],
//                        $content['pictures']
//                    ], true));
                });
        });

        // The removed picture must be gone from disk as well, and only that one
        $this->assertFileDoesNotExist($removed_path);
        $this->assertFileExists($kept_path);

        unlink($kept_path);
    }
}
