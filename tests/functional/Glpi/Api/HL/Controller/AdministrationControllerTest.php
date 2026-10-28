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

use Entity;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\Schemas;
use Glpi\Event;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use UserEmail;

class AdministrationControllerTest extends HLAPITestCase
{
    public function testSearchUsers()
    {
        $this->api->call(new Request('GET', '/Administration/User'), function ($call) {
            $call->response
                ->isUnauthorizedError();
        });

        $this->login();
        $this->api->call(new Request('GET', '/Administration/User'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content);
                    foreach ($content as $v) {
                        $this->assertTrue(is_array($v));
                        $this->assertCount(4, array_intersect(array_keys($v), ['id', 'username', 'realname', 'firstname']));
                        // Should never have "name" field as it should be mapped to "username"
                        // Should never pass the password fields to the client
                        $this->assertCount(0, array_intersect(array_keys($v), ['name', 'password', 'password2']));
                    }
                });
        });

        // Test a basic RSQL filter
        $request = new Request('GET', '/Administration/User');
        $request->setParameter('filter', 'username==' . TU_USER);
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $user = $content[0];
                    $this->assertGreaterThan(0, $user['id']);
                    $this->assertEquals(TU_USER, $user['username']);
                    $this->assertGreaterThanOrEqual(1, $user['emails']);
                });
        });

        $request = new Request('GET', '/Administration/User');
        $request->setParameter('filter', 'emails.email=like=*glpi.com');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $user = $content[0];
                    $this->assertGreaterThan(0, $user['id']);
                    $this->assertEquals(TU_USER, $user['username']);
                    $this->assertGreaterThanOrEqual(1, $user['emails']);
                });
        });
    }

    public function testSearchUserPagination()
    {
        $this->api->autoTestSearch('/Administration/User', [
            [
                'firstname' => 'Test',
                'realname'  => 'User',
            ],
            [
                'firstname' => 'Test2',
                'realname'  => 'User2',
            ],
            [
                'firstname' => 'Test3',
                'realname'  => 'User3',
            ],
        ], 'username');
    }

    public function testUserSearchByEmail()
    {
        $this->login();
        $request = new Request('GET', '/Administration/User');
        $request->setParameter('filter', 'emails.email==' . TU_USER . '@glpi.com');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $user = $content[0];
                    $this->assertEquals(TU_USER, $user['username']);
                });
        });
    }

    public function testSearchGroups()
    {
        $this->api->autoTestSearch('/Administration/Group', [
            ['name' => __FUNCTION__ . '_1'],
            ['name' => __FUNCTION__ . '_2'],
            ['name' => __FUNCTION__ . '_3'],
        ]);
    }

    public function testSearchEntities()
    {
        $this->login();
        $this->api->autoTestSearch('/Administration/Entity', [
            [
                'name' => __FUNCTION__ . '_1',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true),
            ],
            [
                'name' => __FUNCTION__ . '_2',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true),
            ],
            [
                'name' => __FUNCTION__ . '_3',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true),
            ],
        ]);
    }

    public function testSearchProfiles()
    {
        $this->api->autoTestSearch('/Administration/Profile', [
            ['name' => __FUNCTION__ . '_1'],
            ['name' => __FUNCTION__ . '_2'],
            ['name' => __FUNCTION__ . '_3'],
        ]);
    }

    public static function getItemProvider()
    {
        return [
            ['User', getItemByTypeName('User', TU_USER, true)],
            ['Group', getItemByTypeName('Group', '_test_group_1', true)],
            ['Entity', getItemByTypeName('Entity', '_test_root_entity', true)],
            ['Profile', getItemByTypeName('Profile', 'Super-Admin', true)],
        ];
    }

    #[DataProvider('getItemProvider')]
    public function testGetItem(string $type, int $id)
    {
        $this->login('glpi', 'glpi');
        $this->api->call(new Request('GET', "/Administration/$type/$id"), function ($call) use ($id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($id) {
                    $this->assertIsArray($content);
                    $this->assertEquals($id, $content['id']);
                });
        });
    }

    public function testGetUserByUsername()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/username/' . TU_USER), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals(TU_USER, $content['username']);
                });
        });
    }

    public function testGetMe()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals(TU_USER, $content['username']);
                });
        });

        // filters shouldn't affect /me
        $request = new Request('GET', '/Administration/User/me');
        $request->setParameter('filter', 'username==' . TU_USER . '_other');
        $this->api->call($request, function ($call) {
            $call->response->isNotFoundError();
        });
    }

    public function testGetMyEmails()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/me/email'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content);
                    $has_expected_email = false;
                    foreach ($content as $v) {
                        $this->assertIsArray($v);
                        $this->assertCount(3, array_intersect(array_keys($v), ['id', 'email', 'is_default']));
                        if ($v['email'] === TU_USER . '@glpi.com') {
                            $has_expected_email = true;
                        }
                    }
                    $this->assertTrue($has_expected_email);
                });
        });
    }

    public function testGetMySpecificEmail()
    {
        global $DB;

        $this->login();
        // Get ID of TU_USER email with email = TU_USER . '@glpi.com'
        $email_id = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_useremails',
            'WHERE'  => [
                'users_id' => getItemByTypeName('User', TU_USER, true),
                'email'    => TU_USER . '@glpi.com',
            ],
        ])->current()['id'];

        $this->api->call(new Request('GET', "/Administration/User/me/email/$email_id"), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, array_intersect(array_keys($content), ['id', 'email', 'is_default']));
                    $this->assertEquals(TU_USER . '@glpi.com', $content['email']);
                });
        });

        // Try getting an email that doesn't exist
        $this->api->call(new Request('GET', "/Administration/User/me/email/999999999"), function ($call) {
            $call->response->isNotFoundError();
        });

        // Log in as another user and try to get the email of the first user (should fail)
        $this->login('tech', 'tech');
        $this->api->call(new Request('GET', "/Administration/User/me/email/$email_id"), function ($call) {
            $call->response->isNotFoundError();
        });
    }

    private function addCustomUserPicture(int $user_id, string $picture_path)
    {
        global $DB;
        $picture_path = \Toolbox::savePicture($picture_path, '', true);
        $this->assertIsString($picture_path);
        $DB->update('glpi_users', [
            'id' => $user_id,
            'picture' => $picture_path,
        ], [
            'id' => $user_id,
        ]);
    }

    private function getPicturePathFromUrl(string $picture_url): string
    {
        $this->assertStringContainsString('_pictures%2F', $picture_url);
        $reference = substr($picture_url, strpos($picture_url, '_pictures%2F') + strlen('_pictures%2F'));
        return GLPI_PICTURE_DIR . '/' . urldecode($reference);
    }

    public function testGetMyPicture()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/me/picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertStringContainsString('/front/document.send.php', $content['picture']);
                });
        });

        $this->api->call(new Request('GET', '/Administration/User/me/picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'), $content);
                });
        });
    }

    public function testGetUserPictureByID()
    {
        $this->login();

        $tu_id = getItemByTypeName('User', TU_USER, true);
        $this->api->call(new Request('GET', '/Administration/User/' . $tu_id . '/Picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/' . $tu_id . '/Picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'), $content);
                });
        });
    }

    public function testGetUserPictureByUsername()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Administration/User/username/' . TU_USER . '/Picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/username/' . TU_USER . '/Picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'), $content);
                });
        });
    }

    public function testCreateUpdateDeleteUser()
    {
        $this->api
            ->autoTestCRUD('/Administration/User', [
                'username'  => 'testuser',
                'password'  => 'testuser',
                'password2' => 'testuser',
                'firstname' => 'Test',
                'realname'  => 'User',
            ], [
                'username'  => 'testuser2',
                'firstname' => 'Test2',
                'realname'  => 'User2',
            ]);
    }

    public function testCreateUpdateDeleteGroup()
    {
        $this->api->autoTestCRUD('/Administration/Group');
    }

    public function testCreateUpdateDeleteProfile()
    {
        $this->api->autoTestCRUD('/Administration/Profile');
    }

    public function testCreateUpdateDeleteEntity()
    {
        $this->api
            ->autoTestCRUD('/Administration/Entity', [
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]);
    }

    public function testMultisort()
    {
        $this->loginWeb();

        $this->createItem('User', [
            'name' => 'testuser1',
            'firstname' => 'John',
            'realname' => 'User1',
        ]);
        $this->createItem('User', [
            'name' => 'testuser2',
            'firstname' => 'Mary',
            'realname' => 'User2',
        ]);
        $this->createItem('User', [
            'name' => 'testuser3',
            'firstname' => 'John',
            'realname' => 'User3',
        ]);

        $this->login();
        $request = new Request('GET', '/Administration/User');
        $request->setParameter('filter', 'username=in=(testuser1,testuser2,testuser3)');
        $request->setParameter('sort', 'firstname,username');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, $content);
                    $this->assertEquals('testuser1', $content[0]['username']);
                    $this->assertEquals('testuser3', $content[1]['username']);
                    $this->assertEquals('testuser2', $content[2]['username']);
                });
        });
        $request->setParameter('sort', 'firstname:desc,username');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, $content);
                    $this->assertEquals('testuser2', $content[0]['username']);
                    $this->assertEquals('testuser1', $content[1]['username']);
                    $this->assertEquals('testuser3', $content[2]['username']);
                });
        });
    }

    public function testGetUsedManagedItems()
    {
        $this->loginWeb();

        $entity_id = $this->getTestRootEntity(true);
        $computers_id_1 = $this->createItem('Computer', [
            'name' => __FUNCTION__,
            'entities_id' => $entity_id,
            'users_id' => \Session::getLoginUserID(),
        ])->getID();
        $computers_id_2 = $this->createItem('Computer', [
            'name' => __FUNCTION__ . '_tech',
            'entities_id' => $entity_id,
            'users_id_tech' => \Session::getLoginUserID(),
        ])->getID();
        $monitors_id_1 = $this->createItem('Monitor', [
            'name' => __FUNCTION__,
            'entities_id' => $entity_id,
            'users_id' => \Session::getLoginUserID(),
        ])->getID();
        $monitors_id_2 = $this->createItem('Monitor', [
            'name' => __FUNCTION__ . '_tech',
            'entities_id' => $entity_id,
            'users_id_tech' => \Session::getLoginUserID(),
        ])->getID();

        $expected_used = [
            'Computer' => [$computers_id_1],
            'Monitor' => [$monitors_id_1],
        ];

        $expected_managed = [
            'Computer' => [$computers_id_2],
            'Monitor' => [$monitors_id_2],
        ];

        $used_endpoints = ['/Administration/User/me/UsedItem', "/Administration/User/username/" . TU_USER . "/UsedItem", "/Administration/User/" . \Session::getLoginUserID() . "/UsedItem"];
        $managed_endpoints = ['/Administration/User/me/ManagedItem', "/Administration/User/username/" . TU_USER . "/ManagedItem", "/Administration/User/" . \Session::getLoginUserID() . "/ManagedItem"];

        $this->login();
        foreach ($used_endpoints as $endpoint) {
            $this->api->call(new Request('GET', $endpoint), function ($call) use ($expected_used) {
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($expected_used) {
                        $this->assertGreaterThanOrEqual(count($expected_used), count($content));
                        foreach ($expected_used as $type => $ids) {
                            $this->assertCount(count($ids), array_intersect(array_column(array_filter($content, static fn($v) => $v['_itemtype'] === $type), 'id'), $ids));
                        }
                    });
            });
        }
        foreach ($managed_endpoints as $endpoint) {
            $this->api->call(new Request('GET', $endpoint), function ($call) use ($expected_managed) {
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($expected_managed) {
                        $this->assertGreaterThanOrEqual(count($expected_managed), count($content));
                        foreach ($expected_managed as $type => $ids) {
                            $this->assertCount(count($ids), array_intersect(array_column(array_filter($content, static fn($v) => $v['_itemtype'] === $type), 'id'), $ids));
                        }
                    });
            });
        }
    }

    public function testUserScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $this->api->call(new Request('GET', '/Administration/User/Me'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/Me/Emails/Default'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'user']);
        $this->api->call(new Request('GET', '/Administration/User/Me'), function ($call) {
            $call->response->isOK();
        });
        $this->api->call(new Request('GET', '/Administration/User/Me/Emails/Default'), function ($call) {
            $call->response->isOK();
        });
    }

    public function testEmailScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/me/Emails/Default'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'email']);
        // Access to email scope doesn't allow broad access to current user info
        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/me/Emails/Default'), function ($call) {
            $call->response->isOK();
        });
    }

    private function assertUserPreferenceResponseOK($content)
    {
        $this->assertIsArray($content);
        // Spot check some known preferences
        $this->assertArrayHasKey('language', $content);
        $this->assertArrayHasKey('palette', $content);
        $this->assertArrayHasKey('csv_delimiter', $content);
        $this->assertArrayHasKey('refresh_view_interval', $content);
    }

    public function testGetUserPreferencesByID()
    {
        $this->login();

        $tu_id = getItemByTypeName('User', TU_USER, true);
        $this->api->call(new Request('GET', '/Administration/User/' . $tu_id . '/Preference'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                });
        });
    }

    public function testGetUserPreferencesByUsername()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Administration/User/' . TU_USER . '/Preference'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                });
        });
    }

    public function testGetMyPreferences()
    {
        $this->login();

        $this->api->call(new Request('GET', '/Administration/User/me/Preference'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                });
        });
    }

    public function testUpdateUserPreferencesByID()
    {
        $this->login();

        $tu_id = getItemByTypeName('User', TU_USER, true);
        $request = new Request('PATCH', '/Administration/User/' . $tu_id . '/Preference');
        $request->setParameter('palette', 'teclib');
        $request->setParameter('language', 'fr_FR');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                    $this->assertEquals('teclib', $content['palette']);
                    $this->assertEquals('fr_FR', $content['language']);
                });
        });
    }

    public function testUpdateUserPreferencesByUsername()
    {
        $this->login();

        $request = new Request('PATCH', '/Administration/User/' . TU_USER . '/Preference');
        $request->setParameter('palette', 'teclib');
        $request->setParameter('language', 'fr_FR');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                    $this->assertEquals('teclib', $content['palette']);
                    $this->assertEquals('fr_FR', $content['language']);
                });
        });
    }

    public function testUpdateMyPreferences()
    {
        $this->login();

        $request = new Request('PATCH', '/Administration/User/me/Preference');
        $request->setParameter('palette', 'teclib');
        $request->setParameter('language', 'fr_FR');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertUserPreferenceResponseOK($content);
                    $this->assertEquals('teclib', $content['palette']);
                    $this->assertEquals('fr_FR', $content['language']);
                });
        });
    }

    public function testSearchEventLogs()
    {
        $this->loginWeb();

        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $this->api->call(new Request('GET', '/Administration/EventLog'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content);
                    $first = $content[0];
                    $this->assertArrayHasKey('id', $first);
                    $this->assertArrayHasKey('items_id', $first);
                    $this->assertArrayHasKey('type', $first);
                    $this->assertArrayHasKey('date', $first);
                    $this->assertArrayHasKey('service', $first);
                    $this->assertArrayHasKey('level', $first);
                    $this->assertArrayHasKey('message', $first);
                });
        });

        $_SESSION['glpiactiveprofile']['system_logs'] = 0;

        $this->api->call(new Request('GET', '/Administration/EventLog'), function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public function testGetEventLogByID()
    {
        // find an existing event log ID
        global $DB;
        $eventlog_id = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Event::getTable(),
            'LIMIT'  => 1,
        ])->current()['id'];

        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $this->api->call(new Request('GET', '/Administration/EventLog/' . $eventlog_id), function ($call) use ($eventlog_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($eventlog_id) {
                    $this->assertIsArray($content);
                    $this->assertEquals($eventlog_id, $content['id']);
                });
        });

        $_SESSION['glpiactiveprofile']['system_logs'] = 0;
        $this->api->call(new Request('GET', '/Administration/EventLog/' . $eventlog_id), function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public static function crud22Provider()
    {
        return [
            ['UserCategory'],
            ['UserTitle'],
            ['ApprovalSubstitute'],
        ];
    }

    #[DataProvider('crud22Provider')]
    public function testCRUD22(string $itemtype)
    {
        $create_params = [];
        if ($itemtype === 'ApprovalSubstitute') {
            $create_params = [
                'user' => getItemByTypeName('User', TU_USER, true),
                'substitute' => getItemByTypeName('User', 'tech', true),
            ];
        }
        $this->api->autoTestCRUD(
            endpoint: '/Administration/' . $itemtype,
            create_params: $create_params,
            extra_options: ['skip_update_test' => $itemtype === 'ApprovalSubstitute']
        );
    }

    public function testCRUDUserEmails(): void
    {
        $this->login();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $this->api->autoTestCRUD(
            endpoint: "/Administration/User/$users_id/Email",
            create_params: [
                'user' => $users_id,
                'email' => TU_USER . '@example.com',
                'is_default' => 0,
            ],
            extra_options: ['skip_update_test' => true],
        );
    }

    public function testCRUDNoRightsUserEmails(): void
    {
        global $DB;

        $this->login('post-only', 'postonly');
        $users_id = getItemByTypeName('User', TU_USER, true);

        $DB->insert(UserEmail::getTable(), [
            'users_id' => $users_id,
            'email' => TU_USER . '@example.com',
            'is_default' => 0,
        ]);
        $useremail_id = $DB->insertId();

        $this->api->call(new Request('GET', "/Administration/User/$users_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id/Email/$useremail_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
        $create_request = new Request('POST', "/Administration/User/$users_id/Email");
        $create_request->setParameter('email', TU_USER . '@example.com');
        $this->api->call($create_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Administration/User/$users_id/Email/$useremail_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isAccessDenied();
        });
    }

    public function testAddDuplicateUserEmail()
    {
        $this->login();
        $users_id = getItemByTypeName('User', TU_USER, true);

        $create_request = new Request('POST', "/Administration/User/$users_id/Email");
        $create_request->setParameter('email', TU_USER . '@example.com');
        // First call should succeed
        $this->api->call($create_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });
        // Second call should fail with a 409 status
        $this->api->call($create_request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->status(fn($status) => $this->assertEquals(409, $status));
        });
    }

    public function testCreateUserWithPicture(): void
    {
        $bar_file_path = GLPI_ROOT . '/tests/fixtures/uploads/bar.png';
        $bar_file_content = file_get_contents($bar_file_path);

        $this->login();

        // multipart form data request with the document item's name, the file, and the entity ID
        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="username"

userwithpic
-----boundary
Content-Disposition: form-data; name="label"

Car
-----boundary
Content-Disposition: form-data; name="picture_upload"; filename="bar.png"

$bar_file_content
-----boundary
Content-Disposition: form-data; name="password"

testuser
-----boundary
Content-Disposition: form-data; name="password2"

testuser
-----boundary--
EOT;

        $request = new Request('POST', '/Administration/User', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);
        $new_location = null;
        $this->api->call($request, function ($call) use (&$new_location) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_location) {
                    $new_location = $headers['Location'];
                });
        });

        $this->api->call(new Request('GET', $new_location), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertNotEmpty($content['picture']);
                    $this->assertStringNotContainsString('picture.png', $content['picture']);
                });
        });

        $this->api->call(new Request('GET', $new_location . '/Picture'), function ($call) {
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertNotEmpty($content);
                });
        });
    }

    public function testUpdateUserPictureDeletesPreviousFile(): void
    {
        $first_picture_content = file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/bar.png');
        $second_picture_content = file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->login();

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="username"

{$this->getUniqueString()}
-----boundary
Content-Disposition: form-data; name="label"

Car
-----boundary
Content-Disposition: form-data; name="picture_upload"; filename="bar.png"

$first_picture_content
-----boundary
Content-Disposition: form-data; name="password"

testuser
-----boundary
Content-Disposition: form-data; name="password2"

testuser
-----boundary--
EOT;

        $request = new Request('POST', '/Administration/User', [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);
        $new_location = null;
        $this->api->call($request, function ($call) use (&$new_location) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_location) {
                    $new_location = $headers['Location'];
                });
        });

        $original_picture_path = null;
        $this->api->call(new Request('GET', $new_location), function ($call) use (&$original_picture_path) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$original_picture_path) {
                    $original_picture_path = $this->getPicturePathFromUrl($content['picture']);
                });
        });

        $this->assertNotNull($original_picture_path);
        $this->assertFileExists($original_picture_path);

        $multipart_body = <<<EOT
-----boundary
Content-Disposition: form-data; name="picture_upload"; filename="foo.png"

$second_picture_content
-----boundary--
EOT;

        $request = new Request('PATCH', $new_location, [
            'Content-Type' => 'multipart/form-data; boundary=---boundary',
        ], $multipart_body);
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        $updated_picture_path = null;
        $this->api->call(new Request('GET', $new_location), function ($call) use (&$updated_picture_path) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$updated_picture_path) {
                    $updated_picture_path = $this->getPicturePathFromUrl($content['picture']);
                });
        });

        $this->assertNotNull($updated_picture_path);
        $this->assertNotSame($original_picture_path, $updated_picture_path);
        $this->assertFileDoesNotExist($original_picture_path);
        $this->assertFileExists($updated_picture_path);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getEntitySettingsSchemaProperties(): array
    {
        return Schemas::getInstance(Router::API_VERSION)->getAllSchemas()['EntitySettings']['properties'];
    }

    private function createEntityForSettings(string $name): int
    {
        $this->loginWeb();
        $entities_id = $this->createItem('Entity', [
            'name' => $name,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $this->login();
        return $entities_id;
    }

    public function testGetEntitySettings(): void
    {
        $entities_id = $this->createEntityForSettings(__FUNCTION__);
        $expected_properties = array_keys($this->getEntitySettingsSchemaProperties());

        $this->api->call(new Request('GET', "/Administration/Entity/{$entities_id}/Settings"), function ($call) use ($entities_id, $expected_properties) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($entities_id, $expected_properties) {
                    $this->assertEqualsCanonicalizing($expected_properties, array_keys($content));
                    $this->assertSame($entities_id, $content['id']);

                    // Integer settings that inherit from the parent entity by default
                    $this->assertSame(-2, $content['cartridges_alert_repeat']);
                    $this->assertSame(-2, $content['default_consumables_alarm_threshold']);
                    $this->assertSame(-2, $content['use_licenses_alert']);
                    $this->assertSame(-2, $content['tickettype']);
                    $this->assertSame(-2, $content['is_contact_autoupdate']);
                    $this->assertSame(-2, $content['two_factor_enforcement']);
                    // String settings
                    $this->assertSame('-2', $content['autofill_buy_date']);
                    // Dropdowns with a strategy field use negative IDs to represent the strategy
                    foreach (['calendar', 'ticket_template', 'change_template', 'problem_template', 'default_contract', 'software_entity', 'transfer_model'] as $dropdown) {
                        $this->assertSame(-2, $content[$dropdown]['id'], "Dropdown $dropdown should inherit");
                    }
                });
        });

        $this->api->call(new Request('GET', '/Administration/Entity/99999999/Settings'), function ($call) {
            $call->response->isNotFoundError();
        });
    }

    public function testGetEntitySettingsRequiresAuthentication(): void
    {
        $this->api->call(new Request('GET', '/Administration/Entity/' . getItemByTypeName('Entity', '_test_root_entity', true) . '/Settings'), function ($call) {
            $call->response->isUnauthorizedError();
        });
    }

    public function testUpdateEntitySettings(): void
    {
        $entities_id = $this->createEntityForSettings(__FUNCTION__);
        $this->loginWeb();
        $calendars_id = $this->createItem('Calendar', ['name' => __FUNCTION__])->getID();
        $templates_id = $this->createItem('TicketTemplate', ['name' => __FUNCTION__, 'entities_id' => $this->getTestRootEntity(true), 'is_recursive' => 1])->getID();
        $states_id = $this->createItem('State', ['name' => __FUNCTION__])->getID();
        $this->login();

        $request = new Request('PATCH', "/Administration/Entity/{$entities_id}/Settings");
        foreach ([
            // Special values
            'cartridges_alert_repeat' => DAY_TIMESTAMP,
            'consumables_alert_repeat' => Entity::CONFIG_NEVER,
            'default_cartridges_alarm_threshold' => 15,
            'use_licenses_alert' => 1,
            'send_licenses_alert_before_delay' => 30,
            'use_reservations_alert' => 12,
            'default_contract_alert' => 12,
            'autoclose_delay' => Entity::CONFIG_NEVER,
            'autopurge_delay' => 90,
            'tickettype' => 2,
            'state_autoupdate_mode' => $states_id,
            'state_autoclean_mode' => -1,
            'is_user_autoclean' => 1,
            'two_factor_enforcement' => 0,
            'autofill_buy_date' => '2',
            'autofill_warranty_date' => 'STATUS_' . $states_id,
            'survey_config_ticket' => 2,
            'survey_rate_ticket' => 50,
            'survey_url_ticket' => 'https://example.com/survey',
            'survey_config_change' => 1,
            'survey_default_rate_change' => 4,
            // Dropdowns with a strategy field
            'calendar' => ['id' => $calendars_id],
            'ticket_template' => ['id' => $templates_id],
            'change_template' => ['id' => Entity::CONFIG_PARENT],
            'default_contract' => ['id' => Entity::CONFIG_AUTO],
            'software_entity' => ['id' => Entity::CONFIG_NEVER],
            'transfer_model' => ['id' => Entity::CONFIG_NEVER],
        ] as $name => $value) {
            $request->setParameter($name, $value);
        }
        $this->api->call($request, function ($call) use ($calendars_id, $templates_id, $states_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($calendars_id, $templates_id, $states_id) {
                    $this->assertSame(DAY_TIMESTAMP, $content['cartridges_alert_repeat']);
                    $this->assertSame(Entity::CONFIG_NEVER, $content['consumables_alert_repeat']);
                    $this->assertSame(15, $content['default_cartridges_alarm_threshold']);
                    $this->assertSame(1, $content['use_licenses_alert']);
                    $this->assertSame(30, $content['send_licenses_alert_before_delay']);
                    $this->assertSame(12, $content['use_reservations_alert']);
                    $this->assertSame(12, $content['default_contract_alert']);
                    $this->assertSame(Entity::CONFIG_NEVER, $content['autoclose_delay']);
                    $this->assertSame(90, $content['autopurge_delay']);
                    $this->assertSame(2, $content['tickettype']);
                    $this->assertSame($states_id, $content['state_autoupdate_mode']);
                    $this->assertSame(-1, $content['state_autoclean_mode']);
                    $this->assertSame(1, $content['is_user_autoclean']);
                    $this->assertSame(0, $content['two_factor_enforcement']);
                    $this->assertSame('2', $content['autofill_buy_date']);
                    $this->assertSame('STATUS_' . $states_id, $content['autofill_warranty_date']);
                    $this->assertSame(2, $content['survey_config_ticket']);
                    $this->assertSame(50, $content['survey_rate_ticket']);
                    $this->assertSame('https://example.com/survey', $content['survey_url_ticket']);
                    $this->assertSame(1, $content['survey_config_change']);
                    $this->assertSame(4, $content['survey_default_rate_change']);
                    $this->assertSame($calendars_id, $content['calendar']['id']);
                    $this->assertSame($templates_id, $content['ticket_template']['id']);
                    $this->assertSame(Entity::CONFIG_PARENT, $content['change_template']['id']);
                    $this->assertSame(Entity::CONFIG_AUTO, $content['default_contract']['id']);
                    $this->assertSame(Entity::CONFIG_NEVER, $content['software_entity']['id']);
                    $this->assertSame(Entity::CONFIG_NEVER, $content['transfer_model']['id']);
                });
        });

        // The input mappers must have set the real columns and the strategy fields.
        $entity = new Entity();
        $this->assertTrue($entity->getFromDB($entities_id));
        $this->assertEquals(DAY_TIMESTAMP, $entity->fields['cartridges_alert_repeat']);
        $this->assertEquals(Entity::CONFIG_NEVER, $entity->fields['consumables_alert_repeat']);
        $this->assertEquals(0, $entity->fields['2fa_enforcement_strategy']);
        $this->assertEquals($calendars_id, $entity->fields['calendars_id']);
        $this->assertEquals(0, $entity->fields['calendars_strategy']);
        $this->assertEquals($templates_id, $entity->fields['tickettemplates_id']);
        $this->assertEquals(0, $entity->fields['tickettemplates_strategy']);
        $this->assertEquals(0, $entity->fields['changetemplates_id']);
        $this->assertEquals(Entity::CONFIG_PARENT, $entity->fields['changetemplates_strategy']);
        $this->assertEquals(0, $entity->fields['contracts_id_default']);
        $this->assertEquals(Entity::CONFIG_AUTO, $entity->fields['contracts_strategy_default']);
        $this->assertEquals(0, $entity->fields['entities_id_software']);
        $this->assertEquals(Entity::CONFIG_NEVER, $entity->fields['entities_strategy_software']);
        $this->assertEquals(0, $entity->fields['transfers_id']);
        $this->assertEquals(Entity::CONFIG_NEVER, $entity->fields['transfers_strategy']);

        // A GET returns the same values (including the strategy based dropdown values)
        $this->api->call(new Request('GET', "/Administration/Entity/{$entities_id}/Settings"), function ($call) use ($calendars_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($calendars_id) {
                    $this->assertSame($calendars_id, $content['calendar']['id']);
                    $this->assertSame(Entity::CONFIG_PARENT, $content['change_template']['id']);
                    $this->assertSame(Entity::CONFIG_NEVER, $content['transfer_model']['id']);
                });
        });

        // Switching a dropdown from an ID back to a strategy
        $request = new Request('PATCH', "/Administration/Entity/{$entities_id}/Settings");
        $request->setParameter('calendar', ['id' => Entity::CONFIG_PARENT]);
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertSame(Entity::CONFIG_PARENT, $content['calendar']['id']);
                });
        });
        $entity->getFromDB($entities_id);
        $this->assertEquals(0, $entity->fields['calendars_id']);
        $this->assertEquals(Entity::CONFIG_PARENT, $entity->fields['calendars_strategy']);
    }

    /**
     * @return iterable<string, array{string, mixed, mixed}>
     */
    public static function invalidEntitySettingsProvider(): iterable
    {
        // [property, invalid value, expected value of the property after the failed update]
        yield 'special value not allowed' => ['autoclose_delay', -5, -2];
        yield 'zero not allowed' => ['autopurge_delay', 0, -2];
        yield 'above maximum' => ['autoclose_delay', 100, -2];
        yield 'frequency "never" must use the special value' => ['cartridges_alert_repeat', 0, -2];
        yield 'enum value not allowed' => ['tickettype', 5, -2];
        yield 'yes/no value not allowed' => ['use_licenses_alert', 2, -2];
        yield 'not an integer' => ['use_contracts_alert', 'yes', -2];
        yield 'dropdown strategy not allowed' => ['calendar', ['id' => Entity::CONFIG_NEVER], -2];
        yield 'dropdown strategy not allowed (contract)' => ['default_contract', ['id' => -3], -2];
        yield 'dropdown ID not an integer' => ['transfer_model', ['id' => 'abc'], -2];
        yield 'invalid autofill value' => ['autofill_use_date', 'always', '-2'];
        yield 'survey rate not a multiple of 10' => ['survey_rate_ticket', 55, 0];
    }

    #[DataProvider('invalidEntitySettingsProvider')]
    public function testUpdateEntitySettingsInvalid(string $property, mixed $value, mixed $expected_unchanged_value): void
    {
        $entities_id = $this->createEntityForSettings(__FUNCTION__);

        $request = new Request('PATCH', "/Administration/Entity/{$entities_id}/Settings");
        $request->setParameter($property, $value);
        $this->api->call($request, function ($call) use ($property) {
            $call->response->isNotOK();
            $call->response->jsonContent(function ($content) use ($property) {
                $this->assertSame('ERROR_INVALID_PARAMETER', $content['status']);
                $this->assertArrayHasKey($property, $content['detail']);
            });
        });

        // The entity was not modified
        $this->api->call(new Request('GET', "/Administration/Entity/{$entities_id}/Settings"), function ($call) use ($property, $expected_unchanged_value) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($property, $expected_unchanged_value) {
                    $current = is_array($content[$property]) ? $content[$property]['id'] : $content[$property];
                    $this->assertSame($expected_unchanged_value, $current);
                });
        });
    }

    public function testUpdateEntitySettingsPartialInvalidDoesNotUpdate(): void
    {
        $entities_id = $this->createEntityForSettings(__FUNCTION__);

        $request = new Request('PATCH', "/Administration/Entity/{$entities_id}/Settings");
        foreach ([
            'use_licenses_alert' => 1,
            'autoclose_delay' => -5,
        ] as $name => $value) {
            $request->setParameter($name, $value);
        }
        $this->api->call($request, function ($call) {
            $call->response->isNotOK();
        });

        $entity = new Entity();
        $this->assertTrue($entity->getFromDB($entities_id));
        $this->assertEquals(Entity::CONFIG_PARENT, $entity->fields['use_licenses_alert']);
    }

    public function testGraphQLEntitySettings(): void
    {
        $entities_id = $this->createEntityForSettings(__FUNCTION__);
        $root_entity_id = $this->getTestRootEntity(true);

        // The parent entity uses a specific configuration that the child entity inherits
        $this->loginWeb();
        $calendars_id = $this->createItem('Calendar', ['name' => __FUNCTION__])->getID();
        $this->updateItem('Entity', $root_entity_id, [
            'cartridges_alert_repeat' => DAY_TIMESTAMP,
            'use_licenses_alert' => 1,
            'calendars_id' => $calendars_id,
            'autofill_buy_date' => '2',
            'inquest_config' => 1,
            'inquest_rate' => 30,
        ]);
        $this->login();

        // Every property can be fetched (without the dropdowns which need a sub-selection)
        $scalar_fields = [];
        foreach ($this->getEntitySettingsSchemaProperties() as $name => $prop) {
            if ($prop['type'] !== 'object') {
                $scalar_fields[] = $name;
            }
        }
        // The GraphQL query complexity is limited so the fields are fetched in chunks
        foreach (array_chunk($scalar_fields, 40) as $i => $fields_chunk) {
            $query = 'query { Entity(id: ' . $entities_id . ') { id settings { id ' . implode(' ', $fields_chunk) . ' } } }';
            $this->graphql->call($query, function ($call) use ($entities_id, $fields_chunk) {
                $call->response
                    ->isOK()
                    ->data('Entity', function ($entities) use ($entities_id, $fields_chunk) {
                        $this->assertCount(1, $entities);
                        $settings = $entities[0]['settings'];
                        foreach ($fields_chunk as $field) {
                            $this->assertArrayHasKey($field, $settings, "Field $field was not returned");
                        }
                        $this->assertSame($entities_id, $settings['id']);
                    });
            });
        }

        $query = 'query { Entity(id: ' . $entities_id . ') { id settings { cartridges_alert_repeat two_factor_enforcement autofill_buy_date'
            . ' calendar { id } ticket_template { id } change_template { id } problem_template { id }'
            . ' default_contract { id } software_entity { id } transfer_model { id } ldap_directory { id } } } }';
        $this->graphql->call($query, function ($call) {
            $call->response
                ->isOK()
                ->data('Entity', function ($entities) {
                    $this->assertCount(1, $entities);
                    $settings = $entities[0]['settings'];
                    $this->assertSame(-2, $settings['cartridges_alert_repeat']);
                    $this->assertSame(-2, $settings['two_factor_enforcement']);
                    $this->assertSame('-2', $settings['autofill_buy_date']);
                    $this->assertSame(-2, $settings['calendar']['id']);
                    $this->assertSame(-2, $settings['transfer_model']['id']);
                });
        });

        // Raw values vs computed values
        $query = 'query { Entity(id: ' . $entities_id . ') { settings {'
            . ' raw_repeat: cartridges_alert_repeat computed_repeat: cartridges_alert_repeat(computed: true)'
            . ' raw_licenses: use_licenses_alert computed_licenses: use_licenses_alert(computed: true)'
            . ' raw_autofill: autofill_buy_date computed_autofill: autofill_buy_date(computed: true)'
            . ' raw_config: survey_config_ticket computed_config: survey_config_ticket(computed: true)'
            . ' computed_rate: survey_rate_ticket(computed: true)'
            . ' raw_calendar: calendar { id } computed_calendar: calendar(computed: true) { id name }'
            . ' } } }';
        $this->graphql->call($query, function ($call) use ($calendars_id) {
            $call->response
                ->isOK()
                ->data('Entity', function ($entities) use ($calendars_id) {
                    $settings = $entities[0]['settings'];
                    $this->assertSame(-2, $settings['raw_repeat']);
                    $this->assertSame(DAY_TIMESTAMP, $settings['computed_repeat']);
                    $this->assertSame(-2, $settings['raw_licenses']);
                    $this->assertSame(1, $settings['computed_licenses']);
                    $this->assertSame('-2', $settings['raw_autofill']);
                    $this->assertEquals('2', $settings['computed_autofill']);
                    $this->assertSame(-2, $settings['raw_config']);
                    $this->assertSame(1, $settings['computed_config']);
                    $this->assertSame(30, $settings['computed_rate']);
                    $this->assertSame(-2, $settings['raw_calendar']['id']);
                    $this->assertSame($calendars_id, $settings['computed_calendar']['id']);
                    $this->assertSame(__FUNCTION__, $settings['computed_calendar']['name'] ?? null);
                });
        });

        // The settings are not available as a top-level query
        $this->graphql->call('query { EntitySettings { id } }', function ($call) {
            $call->response->isCompletelyError();
        });
    }
}
