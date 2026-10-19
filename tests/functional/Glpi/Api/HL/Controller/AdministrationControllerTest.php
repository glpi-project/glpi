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
use Glpi\Event;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile_User;
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

    public function testCRUDUserProfileAuthorizations(): void
    {
        $this->login();
        $users_id = getItemByTypeName('User', 'normal', true);

        $this->api->autoTestCRUD(
            endpoint: "/Administration/User/$users_id/ProfileAuthorization",
            create_params: [
                'profile' => getItemByTypeName('Profile', 'Technician', true),
                'entity' => $this->getTestRootEntity(true),
                'is_recursive' => false,
            ],
            extra_options: ['skip_update_test' => true],
        );
    }

    public function testSearchUserProfileAuthorizations(): void
    {
        $users_id = getItemByTypeName('User', 'tech', true);

        $this->loginWeb();
        // 'tech' only has authorizations in the root entity, which is not part of the API session active entities
        $visible_authorization = $this->createItem(Profile_User::class, [
            'users_id' => $users_id,
            'profiles_id' => getItemByTypeName('Profile', 'Observer', true),
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 0,
        ])->getID();

        $this->login();
        $this->api->call(new Request('GET', "/Administration/User/$users_id/ProfileAuthorization"), function ($call) use ($users_id, $visible_authorization) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($users_id, $visible_authorization) {
                    // Authorizations in entities outside the active entities are not visible
                    $this->assertEquals([$visible_authorization], array_column($content, 'id'));
                    foreach ($content as $authorization) {
                        $this->assertEquals($users_id, $authorization['user']['id']);
                        $this->assertArrayHasKey('profile', $authorization);
                        $this->assertArrayHasKey('entity', $authorization);
                        $this->assertArrayHasKey('is_recursive', $authorization);
                        $this->assertArrayHasKey('is_dynamic', $authorization);
                    }
                });
        });
    }

    public function testCRUDNoRightsUserProfileAuthorizations(): void
    {
        $users_id = getItemByTypeName('User', TU_USER, true);
        $authorizations_id = array_key_first((new Profile_User())->find(['users_id' => $users_id], [], 1));

        $this->login('post-only', 'postonly');

        $this->api->call(new Request('GET', "/Administration/User/$users_id/ProfileAuthorization"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('GET', "/Administration/User/$users_id/ProfileAuthorization/$authorizations_id"), function ($call) {
            $call->response->isAccessDenied();
        });
        $create_request = new Request('POST', "/Administration/User/$users_id/ProfileAuthorization");
        $create_request->setParameter('profile', getItemByTypeName('Profile', 'Self-Service', true));
        $create_request->setParameter('entity', $this->getTestRootEntity(true));
        $this->api->call($create_request, function ($call) {
            $call->response->isAccessDenied();
        });
        $this->api->call(new Request('DELETE', "/Administration/User/$users_id/ProfileAuthorization/$authorizations_id"), function ($call) {
            $call->response->isAccessDenied();
        });
    }

    public function testAddUserProfileAuthorizationInvalidInput(): void
    {
        $this->login();
        $users_id = getItemByTypeName('User', 'normal', true);

        $request = new Request('POST', "/Administration/User/$users_id/ProfileAuthorization");
        $request->setParameter('entity', $this->getTestRootEntity(true));
        $this->api->call($request, function ($call) {
            $call->response->status(fn($status) => $this->assertEquals(400, $status));
        });

        $request = new Request('POST', "/Administration/User/$users_id/ProfileAuthorization");
        $request->setParameter('profile', getItemByTypeName('Profile', 'Technician', true));
        $request->setParameter('entity', 999999);
        $this->api->call($request, function ($call) {
            $call->response->status(fn($status) => $this->assertEquals(400, $status));
        });
    }

    public function testDeleteProfileAuthorizationOfOtherUser(): void
    {
        $this->login();
        $authorizations_id = array_key_first((new Profile_User())->find(['users_id' => getItemByTypeName('User', 'tech', true)], [], 1));
        $users_id = getItemByTypeName('User', 'normal', true);

        $this->api->call(new Request('DELETE', "/Administration/User/$users_id/ProfileAuthorization/$authorizations_id"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->assertTrue((new Profile_User())->getFromDB($authorizations_id));
    }

    /**
     * Create a user with the Admin profile in the given entity and log in with it using the API.
     */
    private function loginAsLimitedAdmin(int $entities_id, bool $is_recursive): void
    {
        $this->loginWeb();
        $this->createItem(User::class, [
            'name' => 'limited_admin',
            'password' => 'limited_admin',
            'password2' => 'limited_admin',
            '_profiles_id' => getItemByTypeName('Profile', 'Admin', true),
            '_entities_id' => $entities_id,
            '_is_recursive' => (int) $is_recursive,
        ], ['password', 'password2']);
        $this->login('limited_admin', 'limited_admin');
    }

    public function testCannotGrantOrRevokeHigherProfile(): void
    {
        $root_entity = $this->getTestRootEntity(true);
        $this->loginAsLimitedAdmin(0, true);
        $users_id = getItemByTypeName('User', 'normal', true);

        // Super-Admin has more rights than Admin
        $request = new Request('POST', "/Administration/User/$users_id/ProfileAuthorization");
        $request->setParameter('profile', getItemByTypeName('Profile', 'Super-Admin', true));
        $request->setParameter('entity', $root_entity);
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        });
        $this->assertEquals(0, countElementsInTable(Profile_User::getTable(), [
            'users_id' => $users_id,
            'profiles_id' => getItemByTypeName('Profile', 'Super-Admin', true),
        ]));

        // Technician has less rights than Admin
        $request = new Request('POST', "/Administration/User/$users_id/ProfileAuthorization");
        $request->setParameter('profile', getItemByTypeName('Profile', 'Technician', true));
        $request->setParameter('entity', $root_entity);
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        // Cannot revoke a Super-Admin authorization
        $tu_users_id = getItemByTypeName('User', TU_USER, true);
        $authorizations_id = array_key_first((new Profile_User())->find([
            'users_id' => $tu_users_id,
            'profiles_id' => getItemByTypeName('Profile', 'Super-Admin', true),
        ], [], 1));
        $this->assertNotNull($authorizations_id);
        $this->api->call(new Request('DELETE', "/Administration/User/$tu_users_id/ProfileAuthorization/$authorizations_id"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->assertTrue((new Profile_User())->getFromDB($authorizations_id));
    }

    public function testCannotGrantOrRevokeInInaccessibleEntity(): void
    {
        $root_entity = $this->getTestRootEntity(true);
        $child_entity = getItemByTypeName('Entity', '_test_child_1', true);
        $technician = getItemByTypeName('Profile', 'Technician', true);

        $this->loginWeb();
        $target_users_id = $this->createItem(User::class, [
            'name' => 'profile_target',
            '_profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            '_entities_id' => $root_entity,
            '_is_recursive' => 0,
        ])->getID();
        // Authorization in an entity the limited admin cannot see
        $child_authorization = $this->createItem(Profile_User::class, [
            'users_id' => $target_users_id,
            'profiles_id' => $technician,
            'entities_id' => $child_entity,
            'is_recursive' => 0,
        ])->getID();

        // Admin profile only in the test root entity, without its children
        $this->loginAsLimitedAdmin($root_entity, false);

        $endpoint = "/Administration/User/$target_users_id/ProfileAuthorization";

        // Child entity is not accessible
        $request = new Request('POST', $endpoint);
        $request->setParameter('profile', $technician);
        $request->setParameter('entity', $child_entity);
        $request->setParameter('is_recursive', false);
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        });

        // Recursive authorization would give access to the inaccessible child entities
        $request = new Request('POST', $endpoint);
        $request->setParameter('profile', $technician);
        $request->setParameter('entity', $root_entity);
        $request->setParameter('is_recursive', true);
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        });

        // Recursive by default
        $request = new Request('POST', $endpoint);
        $request->setParameter('profile', $technician);
        $request->setParameter('entity', $root_entity);
        $this->api->call($request, function ($call) {
            $call->response->isAccessDenied();
        });

        $this->assertEquals(0, countElementsInTable(Profile_User::getTable(), [
            'users_id' => $target_users_id,
            'profiles_id' => $technician,
            'entities_id' => $root_entity,
        ]));

        // Non-recursive authorization in the accessible entity
        $request = new Request('POST', $endpoint);
        $request->setParameter('profile', $technician);
        $request->setParameter('entity', $root_entity);
        $request->setParameter('is_recursive', false);
        $new_location = null;
        $this->api->call($request, function ($call) use (&$new_location) {
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_location) {
                    $new_location = $headers['Location'];
                });
        });

        // Authorizations in inaccessible entities are hidden and cannot be removed
        $this->api->call(new Request('GET', "$endpoint/$child_authorization"), function ($call) {
            $call->response->isNotFoundError();
        });
        $this->api->call(new Request('DELETE', "$endpoint/$child_authorization"), function ($call) {
            $call->response->isAccessDenied();
        });
        $this->assertTrue((new Profile_User())->getFromDB($child_authorization));

        // The new authorization can be removed
        $this->api->call(new Request('DELETE', $new_location), function ($call) {
            $call->response->isOK();
        });
    }

    /**
     * Create a user with a visible (test root entity) and a hidden (root entity) authorization.
     * @return array{users_id: int, visible: int, hidden: int}
     */
    private function createUserWithProfileAuthorizations(): array
    {
        $this->loginWeb();
        $users_id = $this->createItem(User::class, [
            'name' => 'graphql_profile_auth',
            '_profiles_id' => getItemByTypeName('Profile', 'Technician', true),
            '_entities_id' => $this->getTestRootEntity(true),
            '_is_recursive' => 0,
        ])->getID();
        $visible = array_key_first((new Profile_User())->find(['users_id' => $users_id], [], 1));
        // The root entity is not part of the active entities of the test session
        $hidden = $this->createItem(Profile_User::class, [
            'users_id' => $users_id,
            'profiles_id' => getItemByTypeName('Profile', 'Observer', true),
            'entities_id' => 0,
            'is_recursive' => 0,
        ])->getID();

        return ['users_id' => $users_id, 'visible' => $visible, 'hidden' => $hidden];
    }

    public function testGraphQLProfileAuthorizations(): void
    {
        ['users_id' => $users_id, 'visible' => $visible, 'hidden' => $hidden] = $this->createUserWithProfileAuthorizations();
        $technician = getItemByTypeName('Profile', 'Technician', true);
        $root_entity = $this->getTestRootEntity(true);
        $this->graphql->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        $this->graphql->call(
            "query { User(id: $users_id) { id profile_authorizations { id user { id } profile { id name } entity { id } is_recursive is_dynamic } } }",
            function ($call) use ($users_id, $visible, $technician, $root_entity) {
                $call->response
                    ->isOK()
                    ->data('User', function ($users) use ($users_id, $visible, $technician, $root_entity) {
                        $this->assertCount(1, $users);
                        $authorizations = $users[0]['profile_authorizations'];
                        // The authorization in the root entity is not visible
                        $this->assertEquals([$visible], array_column($authorizations, 'id'));
                        $this->assertEquals($users_id, $authorizations[0]['user']['id']);
                        $this->assertEquals($technician, $authorizations[0]['profile']['id']);
                        $this->assertEquals('Technician', $authorizations[0]['profile']['name']);
                        $this->assertEquals($root_entity, $authorizations[0]['entity']['id']);
                        $this->assertFalse($authorizations[0]['is_recursive']);
                        $this->assertFalse($authorizations[0]['is_dynamic']);
                    });
            }
        );

        $this->graphql->call(
            "query { Profile(id: $technician) { id profile_authorizations { id user { id } } } }",
            function ($call) use ($users_id, $visible, $hidden) {
                $call->response
                    ->isOK()
                    ->data('Profile', function ($profiles) use ($users_id, $visible, $hidden) {
                        $authorizations = $profiles[0]['profile_authorizations'];
                        $ids = array_column($authorizations, 'id');
                        $this->assertContains($visible, $ids);
                        $this->assertNotContains($hidden, $ids);
                        foreach ($authorizations as $authorization) {
                            if ($authorization['id'] === $visible) {
                                $this->assertEquals($users_id, $authorization['user']['id']);
                            }
                        }
                    });
            }
        );

        $this->graphql->call(
            "query { Entity(id: $root_entity) { id profile_authorizations { id entity { id } } } }",
            function ($call) use ($visible, $root_entity) {
                $call->response
                    ->isOK()
                    ->data('Entity', function ($entities) use ($visible, $root_entity) {
                        $authorizations = $entities[0]['profile_authorizations'];
                        $this->assertContains($visible, array_column($authorizations, 'id'));
                        foreach ($authorizations as $authorization) {
                            $this->assertEquals($root_entity, $authorization['entity']['id']);
                        }
                    });
            }
        );
    }

    public function testGraphQLProfileAuthorizationsWithoutRight(): void
    {
        ['visible' => $visible] = $this->createUserWithProfileAuthorizations();
        $technician = getItemByTypeName('Profile', 'Technician', true);
        $this->graphql->getRouter()->registerAuthMiddleware(new InternalAuthMiddleware());

        // Without the right to view users, the authorizations cannot be expanded
        $_SESSION['glpiactiveprofile']['user'] = 0;
        $this->graphql->call(
            "query { Profile(id: $technician) { id profile_authorizations { id user { id } entity { id } } } }",
            function ($call) use ($visible) {
                $call->response
                    ->isOK()
                    ->data('Profile', function ($profiles) use ($visible) {
                        $authorizations = $profiles[0]['profile_authorizations'];
                        $this->assertContains($visible, array_column($authorizations, 'id'));
                        foreach ($authorizations as $authorization) {
                            $this->assertNull($authorization['user']);
                            $this->assertNull($authorization['entity']);
                        }
                    });
            }
        );
    }

    public function testProfileAuthorizationsNotDirectlyAccessible(): void
    {
        ['users_id' => $users_id] = $this->createUserWithProfileAuthorizations();
        $this->login();

        // No GraphQL query for the authorizations themselves
        $this->graphql->call('query { ProfileAuthorization { id } }', function ($call) {
            $call->response->isCompletelyError();
        });

        // GraphQL-only properties are not part of the REST API
        $this->api->call(new Request('GET', "/Administration/User/$users_id"), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayNotHasKey('profile_authorizations', $content);
                });
        });
        $this->api->call(new Request('GET', '/Administration/Profile/' . getItemByTypeName('Profile', 'Technician', true)), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayNotHasKey('profile_authorizations', $content);
                });
        });
        $this->api->call(new Request('GET', '/Administration/Entity/' . $this->getTestRootEntity(true)), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayNotHasKey('profile_authorizations', $content);
                });
        });
    }
}
