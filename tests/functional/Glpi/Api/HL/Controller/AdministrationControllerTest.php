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

use Glpi\Api\HL\Controller\AdministrationController;
use Glpi\Api\HL\Middleware\InternalAuthMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Event;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use Group;
use Group_User;
use PHPUnit\Framework\Attributes\DataProvider;
use User;
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
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id/Email/$useremail_id"), function ($call) {
            $call->response->isAccessDenied();
        });
        $create_request = new Request('POST', "/Administration/User/$users_id/Email");
        $create_request->setParameter('email', TU_USER . '@example.com');
        $this->api->call($create_request, function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Administration/User/$users_id/Email/$useremail_id"), function ($call) {
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
            $call->response->isOK();
        });
        // Second call should fail with a 409 status
        $this->api->call($create_request, function ($call) {
            $call->response->status(fn($status) => $this->assertEquals(409, $status));
        });
    }

    public function testCRUDGroupUsers(): void
    {
        $this->login();
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->api->autoTestCRUD(
            endpoint: "/Administration/Group/$groups_id/User",
            create_params: [
                'user' => getItemByTypeName('User', 'tech', true),
                'is_manager' => false,
            ],
            update_params: [
                'is_manager' => true,
            ],
        );
    }

    public function testGetUserGroups(): void
    {
        $this->login();
        $users_id = getItemByTypeName('User', 'tech', true);
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $group_user = $this->createItem(Group_User::class, [
            'users_id' => $users_id,
            'groups_id' => $groups_id,
        ]);
        // Link for another user in the same group which should not be returned
        $other_group_user = $this->createItem(Group_User::class, [
            'users_id' => getItemByTypeName('User', 'normal', true),
            'groups_id' => $groups_id,
        ]);

        $this->api->call(new Request('GET', "/Administration/User/$users_id/Group"), function ($call) use ($users_id, $group_user) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($users_id, $group_user) {
                    $this->assertNotEmpty($content);
                    $this->assertContains($group_user->getID(), array_column($content, 'id'));
                    foreach ($content as $link) {
                        $this->assertEquals($users_id, $link['user']['id']);
                    }
                });
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id/Group/" . $group_user->getID()), function ($call) use ($users_id, $groups_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($users_id, $groups_id) {
                    $this->assertEquals($users_id, $content['user']['id']);
                    $this->assertEquals($groups_id, $content['group']['id']);
                });
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id/Group/" . $other_group_user->getID()), function ($call) {
            $call->response->isNotFoundError();
        });
    }

    public static function addDuplicateGroupUserProvider(): iterable
    {
        yield 'integer form' => [false];
        yield 'object form' => [true];
    }

    #[DataProvider('addDuplicateGroupUserProvider')]
    public function testAddDuplicateGroupUser(bool $object_form): void
    {
        $this->login();
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $users_id = getItemByTypeName('User', 'tech', true);
        $create_request = new Request('POST', "/Administration/Group/$groups_id/User");
        $create_request->setParameter('user', $object_form ? ['id' => $users_id] : $users_id);
        // First call should succeed
        $this->api->call($create_request, function ($call) {
            $call->response->isOK();
        });
        // Second call should fail with a 409 status
        $this->api->call($create_request, function ($call) use ($groups_id) {
            $call->response
                ->status(fn($status) => $this->assertEquals(409, $status))
                ->headers(fn($headers) => $this->assertStringStartsWith("/Administration/Group/$groups_id/User/", $headers['Location']));
        });
        $this->assertEquals(1, countElementsInTable(Group_User::getTable(), [
            'groups_id' => $groups_id,
            'users_id' => $users_id,
        ]));
    }

    public function testAddGroupUserNoRightsDoesNotDiscloseMembership(): void
    {
        $this->login();
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $member_id = getItemByTypeName('User', 'tech', true);
        $non_member_id = getItemByTypeName('User', 'normal', true);
        $this->createItem(Group_User::class, [
            'users_id' => $member_id,
            'groups_id' => $groups_id,
        ]);

        $this->loginWeb();
        $this->api->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());
        $_SESSION['glpiactiveprofile'][Group::$rightname] = READ;
        $_SESSION['glpiactiveprofile'][User::$rightname] = READ;

        $responses = [];
        foreach ([$member_id, $non_member_id] as $users_id) {
            $create_request = new Request('POST', "/Administration/Group/$groups_id/User");
            $create_request->setParameter('user', $users_id);
            $this->api->call($create_request, function ($call) use (&$responses) {
                $call->response
                    ->isAccessDenied()
                    ->headers(fn($headers) => $this->assertArrayNotHasKey('Location', $headers))
                    ->jsonContent(function ($content) use (&$responses) {
                        $responses[] = $content;
                    });
            }, false);
        }
        // The responses must not allow distinguishing an existing membership from a new one
        $this->assertCount(2, $responses);
        $this->assertEquals($responses[0], $responses[1]);
        $this->assertEquals(1, countElementsInTable(Group_User::getTable(), ['groups_id' => $groups_id]));
    }

    public function testUpdateGroupUserUnicity(): void
    {
        // ResourceAccessor is called directly so a web session is needed
        $this->loginWeb();
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $group_user = $this->createItem(Group_User::class, [
            'users_id' => getItemByTypeName('User', 'tech', true),
            'groups_id' => $groups_id,
        ]);
        $other_group_user = $this->createItem(Group_User::class, [
            'users_id' => getItemByTypeName('User', 'normal', true),
            'groups_id' => $groups_id,
        ]);
        $schema = AdministrationController::getKnownSchemas('2.4.0')['Group_User'];

        // Moving the link onto another existing member of the same group is a conflict
        $response = ResourceAccessor::updateBySchema($schema, ['id' => $other_group_user->getID()], [
            'user' => getItemByTypeName('User', 'tech', true),
        ]);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));
        $this->assertTrue($other_group_user->getFromDB($other_group_user->getID()));
        $this->assertEquals(getItemByTypeName('User', 'normal', true), $other_group_user->fields['users_id']);

        // Updating other properties of a link doesn't conflict with itself
        $response = ResourceAccessor::updateBySchema($schema, ['id' => $group_user->getID()], [
            'is_manager' => true,
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($group_user->getFromDB($group_user->getID()));
        $this->assertEquals(1, $group_user->fields['is_manager']);
    }

    public function testGroupUserWrongGroup(): void
    {
        $this->login();
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $other_groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__ . '_other',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $group_user = $this->createItem(Group_User::class, [
            'users_id' => getItemByTypeName('User', 'tech', true),
            'groups_id' => $groups_id,
        ]);

        $endpoint = "/Administration/Group/$other_groups_id/User/" . $group_user->getID();
        $this->api->call(new Request('GET', $endpoint), function ($call) {
            $call->response->isNotFoundError();
        });
        $update_request = new Request('PATCH', $endpoint);
        $update_request->setParameter('is_manager', true);
        $this->api->call($update_request, function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('DELETE', $endpoint), function ($call) {
            $call->response->isNotFoundError();
        });
        // The link should be unchanged
        $this->assertTrue($group_user->getFromDB($group_user->getID()));
        $this->assertEquals(0, $group_user->fields['is_manager']);
    }

    public function testGraphQLOnlyMembershipProperties(): void
    {
        $this->login();
        $users_id = getItemByTypeName('User', 'tech', true);
        $groups_id = $this->createItem(Group::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $this->createItem(Group_User::class, [
            'users_id' => $users_id,
            'groups_id' => $groups_id,
        ]);

        // These properties are only available through GraphQL
        $this->api->call(new Request('GET', "/Administration/Group/$groups_id"), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(fn($content) => $this->assertArrayNotHasKey('users', $content));
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id"), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(fn($content) => $this->assertArrayNotHasKey('groups', $content));
        });
    }
}
