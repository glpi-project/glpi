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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Reminder;

class ToolControllerTest extends HLAPITestCase
{
    public function testIndex()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Tools'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $asset) {
                        $this->assertNotEmpty($asset['itemtype']);
                        $this->assertNotEmpty($asset['name']);
                        $this->assertEquals('/Tools/' . $asset['itemtype'], $asset['href']);
                    }
                });
        });
    }

    public function testAutoSearch()
    {
        $this->login();
        $entity = $this->getTestRootEntity(true);
        $dataset = [
            [
                'name' => 'testAutoSearch_1',
            ],
            [
                'name' => 'testAutoSearch_2',
            ],
            [
                'name' => 'testAutoSearch_3',
            ],
        ];
        $this->api->call(new Request('GET', '/Tools'), function ($call) use ($dataset) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($dataset) {
                    global $CFG_GLPI;

                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $type) {
                        if ($type['itemtype'] === 'RSSFeed') {
                            $dataset = [
                                ['url' => $CFG_GLPI['url_base'] . '/api.php/v2/fakerss'],
                                ['url' => $CFG_GLPI['url_base'] . '/api.php/v2/fakerss2'],
                                ['url' => $CFG_GLPI['url_base'] . '/api.php/v2/fakerss3'],
                            ];
                        }
                        $this->api->autoTestSearch('/Tools/' . $type['itemtype'], $dataset, $type['itemtype'] === 'RSSFeed' ? 'url' : 'name');
                    }
                });
        });
    }

    public function testAutoCRUD()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Tools'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    global $CFG_GLPI;

                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $type) {
                        $create_params = [];
                        if ($type['itemtype'] === 'RSSFeed') {
                            $create_params['url'] = $CFG_GLPI['url_base'] . '/api.php/v2/fakerss';
                        }
                        $this->api->autoTestCRUD('/Tools/' . $type['itemtype'], $create_params);
                    }
                });
        });
    }

    public function testCRUDNoRights()
    {
        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->api->call(new Request('GET', '/Tools'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    global $CFG_GLPI;

                    $this->assertGreaterThanOrEqual(1, count($content));
                    foreach ($content as $type) {
                        $create_request = new Request('POST', $type['href']);
                        $create_request->setParameter('name', 'testCRUDNoRights' . random_int(0, 10000));
                        $create_request->setParameter('entity', getItemByTypeName('Entity', '_test_root_entity', true));
                        if ($type['itemtype'] === 'RSSFeed') {
                            $create_request->setParameter('url', $CFG_GLPI['url_base'] . '/api.php/v2/fakerss');
                        }
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
                        $this->api->autoTestCRUDNoRights(
                            endpoint: $type['href'],
                            itemtype: $type['itemtype'],
                            items_id: (int) $new_items_id,
                            create_params: [
                                'name' => __FUNCTION__,
                                'url' => $CFG_GLPI['url_base'] . '/api.php/v2/fakerss',
                            ]
                        );
                    }
                });
        });
    }

    public function testCRUDReminderTranslations(): void
    {
        $this->loginWeb();
        $this->login();

        $reminders_id = $this->createItem(Reminder::class, [
            'name' => 'Test Reminder',
            'text' => 'This is a test reminder.',
            'users_id' => $_SESSION['glpiID'],
        ])->getID();

        $this->api->call(new Request('GET', '/Tools/Reminder/' . $reminders_id . '/Translation'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEmpty($content);
                });
        });

        $create_request = new Request('POST', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR');
        $create_request->setParameter('text', 'Ceci est un rappel de test.');
        $this->api->call($create_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Ceci est un rappel de test.', $content['text']);
                });
        });

        $update_request = new Request('PATCH', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR');
        $update_request->setParameter('text', 'Ceci est un rappel de test mis à jour.');
        $this->api->call($update_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Ceci est un rappel de test mis à jour.', $content['text']);
                });
        });

        $this->api->call(new Request('DELETE', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCRUDNoRightsReminderTranslations(): void
    {
        $this->loginWeb();
        $this->login();

        $reminders_id = $this->createItem(Reminder::class, [
            'name' => 'Test Reminder',
            'text' => 'This is a test reminder.',
            'users_id' => 99,
        ])->getID();

        $this->api->call(new Request('GET', '/Tools/Reminder/' . $reminders_id . '/Translation'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
        $create_request = new Request('POST', '/Tools/Reminder/' . $reminders_id . '/Translation/fr_FR');
        $create_request->setParameter('text', 'Ceci est un rappel de test.');
        $this->api->call($create_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
    }
}
