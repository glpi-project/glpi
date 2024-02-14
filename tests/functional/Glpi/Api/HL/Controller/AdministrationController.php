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

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Http\Request;

class AdministrationController extends \HLAPITestCase
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
                    $this->array($content)->isNotEmpty();
                    foreach ($content as $v) {
                        $this->boolean(is_array($v))->isTrue();
                        $this->array($v)->hasKeys(['id', 'username', 'realname', 'firstname']);
                        // Should never have "name" field as it should be mapped to "username"
                        // Should never pass the password fields to the client
                        $this->array($v)->notHasKeys(['name', 'password', 'password2']);
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
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->integer($user['id'])->isGreaterThan(0);
                    $this->string($user['username'])->isEqualTo(TU_USER);
                    $this->array($user['emails'])->size->isGreaterThanOrEqualTo(1);
                });
        });

        $request = new Request('GET', '/Administration/User');
        $request->setParameter('filter', 'emails.email=like=*glpi.com');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->integer($user['id'])->isGreaterThan(0);
                    $this->string($user['username'])->isEqualTo(TU_USER);
                    $this->array($user['emails'])->size->isGreaterThanOrEqualTo(1);
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
            ]
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
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->string($user['username'])->isEqualTo(TU_USER);
                });
        });
    }

    public function testSearchGroups()
    {
        $this->api->autoTestSearch('/Administration/Group', [
            ['name' => __FUNCTION__ . '_1'],
            ['name' => __FUNCTION__ . '_2'],
            ['name' => __FUNCTION__ . '_3']
        ]);
    }

    public function testSearchEntities()
    {
        $this->login();
        $this->api->autoTestSearch('/Administration/Entity', [
            [
                'name' => __FUNCTION__ . '_1',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true)
            ],
            [
                'name' => __FUNCTION__ . '_2',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true)
            ],
            [
                'name' => __FUNCTION__ . '_3',
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true)
            ]
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

    protected function getItemProvider()
    {
        return [
            ['User', getItemByTypeName('User', TU_USER, true)],
            ['Group', getItemByTypeName('Group', '_test_group_1', true)],
            ['Entity', getItemByTypeName('Entity', '_test_root_entity', true)],
            ['Profile', getItemByTypeName('Profile', 'Super-Admin', true)],
        ];
    }

    /**
     * @dataProvider getItemProvider
     */
    public function testGetItem(string $type, int $id)
    {
        $this->login('glpi', 'glpi');
        $this->api->call(new Request('GET', "/Administration/$type/$id"), function ($call) use ($id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($id) {
                    $this->boolean(is_array($content))->isTrue();
                    $this->integer($content['id'])->isEqualTo($id);
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
                    $this->string($content['username'])->isEqualTo(TU_USER);
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
                    $this->string($content['username'])->isEqualTo(TU_USER);
                });
        });

        // filters shouldn't affect /me
        $request = new Request('GET', '/Administration/User/me');
        $request->setParameter('filter', 'username==' . TU_USER . '_other');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK()
                ->jsonContent(function ($content) {
                    $this->string($content['username'])->isEqualTo(TU_USER);
                });
        });
    }

    public function testGetMyEmails()
    {
        $this->login();
        $this->api->call(new Request('GET', '/Administration/User/me/emails'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    $has_expected_email = false;
                    foreach ($content as $v) {
                        $this->boolean(is_array($v))->isTrue();
                        $this->array($v)->hasKeys(['id', 'email', 'is_default']);
                        if ($v['email'] === TU_USER . '@glpi.com') {
                            $has_expected_email = true;
                        }
                    }
                    $this->boolean($has_expected_email)->isTrue();
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
                'email'    => TU_USER . '@glpi.com'
            ]
        ])->current()['id'];

        $this->api->call(new Request('GET', "/Administration/User/me/emails/$email_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasKeys(['id', 'email', 'is_default']);
                    $this->string($content['email'])->isEqualTo(TU_USER . '@glpi.com');
                });
        });

        // Try getting an email that doesn't exist
        $this->api->call(new Request('GET', "/Administration/User/me/emails/999999999"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });

        // Log in as another user and try to get the email of the first user (should fail)
        $this->login('tech', 'tech');
        $this->api->call(new Request('GET', "/Administration/User/me/emails/$email_id"), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    private function addCustomUserPicture(int $user_id, string $picture_path)
    {
        global $DB;
        $picture_path = \Toolbox::savePicture($picture_path, '', true);
        $this->variable($picture_path)->isNotFalse();
        $DB->update('glpi_users', [
            'id' => $user_id,
            'picture' => $picture_path
        ], [
            'id' => $user_id
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
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/pics/picture.png'));
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/me'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->string($content['picture'])->contains('/front/document.send.php');
                });
        });

        $this->api->call(new Request('GET', '/Administration/User/me/picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'));
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
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/pics/picture.png'));
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/' . $tu_id . '/Picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'));
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
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/pics/picture.png'));
                });
        });
        $this->addCustomUserPicture($_SESSION['glpiID'], GLPI_ROOT . '/tests/fixtures/uploads/foo.png');

        $this->api->call(new Request('GET', '/Administration/User/username/' . TU_USER . '/Picture'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->content(function ($content) {
                    $this->string($content)->isIdenticalTo(file_get_contents(GLPI_ROOT . '/tests/fixtures/uploads/foo.png'));
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
                'parent' => getItemByTypeName('Entity', '_test_root_entity', true)
            ]);
    }
}
