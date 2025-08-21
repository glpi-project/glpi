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
use PHPUnit\Framework\Attributes\DataProvider;

class AdministrationControllerTest extends \HLAPITestCase
{
    public function testSearchUsers()
    {
        $this->api->call(new Request('GET', '/Administration/User'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isUnauthorizedError();
        });

        $this->login();
        $this->api->call(new Request('GET', '/Administration/User'), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals(TU_USER, $content['username']);
                });
        });
    }

    public function testGetMyEmails()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/me/email'), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(3, array_intersect(array_keys($content), ['id', 'email', 'is_default']));
                    $this->assertEquals(TU_USER . '@glpi.com', $content['email']);
                });
        });

        // Try getting an email that doesn't exist
        $this->api->call(new Request('GET', "/Administration/User/me/email/999999999"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        // Log in as another user and try to get the email of the first user (should fail)
        $this->login('tech', 'tech');
        $this->api->call(new Request('GET', "/Administration/User/me/email/$email_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertStringContainsString('/front/document.send.php', $content['picture']);
                });
        });

        $this->api->call(new Request('GET', '/Administration/User/me/picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/' . $tu_id . '/Picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->assertEquals(file_get_contents(GLPI_ROOT . '/public/pics/picture.png'), $content);
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/username/' . TU_USER . '/Picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
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
                /** @var \HLAPICallAsserter $call */
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
                /** @var \HLAPICallAsserter $call */
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
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/Me/Emails/Default'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'user']);
        $this->api->call(new Request('GET', '/Administration/User/Me'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });
        $this->api->call(new Request('GET', '/Administration/User/Me/Emails/Default'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });
    }

    public function testEmailScope()
    {
        $this->login(api_options: ['scope' => 'api']);
        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/me/Emails/Default'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->login(api_options: ['scope' => 'email']);
        // Access to email scope doesn't allow broad access to current user info
        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isAccessDenied()
                ->jsonContent(function ($content) {
                    $this->assertEquals('You do not have the required scope(s) to access this endpoint.', $content['detail']);
                });
        });
        $this->api->call(new Request('GET', '/Administration/User/me/Emails/Default'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK();
        });
    }
}
