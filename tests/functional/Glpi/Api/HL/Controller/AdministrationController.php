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
        $this->login();

        $seen_usernames = [];
        for ($i = 0; $i < 4; $i++) {
            $request = new Request('GET', '/Administration/User');
            $request->setParameter('start', $i);
            $request->setParameter('limit', 1);
            $this->api->call($request, function ($call) use ($i, &$seen_usernames) {
                /** @var \HLAPICallAsserter $call */
                $call->response
                    ->status(fn ($status) => $this->integer($status)->isEqualTo(206))
                    ->jsonContent(function ($content) use (&$seen_usernames) {
                        $this->array($content)->hasSize(1);
                        $user = $content[0];
                        $seen_usernames[] = $user['username'];
                    })
                    ->headers(function ($headers) use ($i) {
                        $this->array($headers)->hasKey('Content-Range');
                        $this->string($headers['Content-Range'])->matches('/' . $i . '-' . $i . '\/\d+/');
                    });
            });
        }
        // All seen usernames should be unique
        $this->integer(count($seen_usernames))->isEqualTo(count(array_unique($seen_usernames)));

        // Search users with high limit to get all users
        $request = new Request('GET', '/Administration/User');
        $request->setParameter('limit', 1000);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->status(fn ($status) => $this->integer($status)->isEqualTo(200))
                ->headers(function ($headers) {
                    $this->array($headers)->hasKey('Content-Range');
                    $this->string($headers['Content-Range'])->matches('/0-\d+\/\d+/');
                });
        });
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
        $this->api->call(new Request('GET', '/Administration/Group'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isUnauthorizedError();
        });

        $this->login();
        $this->api->call(new Request('GET', '/Administration/Group'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    foreach ($content as $v) {
                        $this->boolean(is_array($v))->isTrue();
                        $this->array($v)->hasKeys(['id', 'name', 'comment']);
                    }
                });
        });

        // Test a basic RSQL filter
        $request = new Request('GET', '/Administration/Group');
        $request->setParameter('filter', 'name==_test_group_1');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->integer($user['id'])->isGreaterThan(0);
                    $this->string($user['name'])->isEqualTo('_test_group_1');
                });
        });
    }

    public function testSearchEntities()
    {
        $this->api->call(new Request('GET', '/Administration/Entity'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isUnauthorizedError();
        });

        $this->login('glpi', 'glpi');
        $this->api->call(new Request('GET', '/Administration/Entity'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    foreach ($content as $v) {
                        $this->boolean(is_array($v))->isTrue();
                        $this->array($v)->hasKeys(['id', 'name', 'comment']);
                    }
                });
        });

        // Test a basic RSQL filter
        $request = new Request('GET', '/Administration/Entity');
        $request->setParameter('filter', 'name==_test_root_entity');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->integer($user['id'])->isGreaterThan(0);
                    $this->string($user['name'])->isEqualTo('_test_root_entity');
                });
        });
    }

    public function testSearchProfiles()
    {
        $this->api->call(new Request('GET', '/Administration/Profile'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isUnauthorizedError();
        });

        $this->login();
        $this->api->call(new Request('GET', '/Administration/Profile'), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->isNotEmpty();
                    foreach ($content as $v) {
                        $this->boolean(is_array($v))->isTrue();
                        $this->array($v)->hasKeys(['id', 'name', 'comment']);
                    }
                });
        });

        // Test a basic RSQL filter
        $request = new Request('GET', '/Administration/Profile');
        $request->setParameter('filter', 'name==Super-Admin');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->array($content)->hasSize(1);
                    $user = $content[0];
                    $this->integer($user['id'])->isGreaterThan(0);
                    $this->string($user['name'])->isEqualTo('Super-Admin');
                });
        });
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
        $this->login();

        $unique_id = __FUNCTION__;

        // Create a new user
        $request = new Request('POST', '/Administration/User');
        $request->setParameter('username', $unique_id);
        $request->setParameter('password', $unique_id);
        $request->setParameter('password2', $unique_id);
        $request->setParameter('firstname', 'FirstName');
        $request->setParameter('realname', 'RealName');

        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $new_item_location = $headers['Location'];
                });
        });

        // Get the new user
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_id) {
                    $this->string($content['username'])->isEqualTo($unique_id);
                    $this->string($content['firstname'])->isEqualTo('FirstName');
                    $this->string($content['realname'])->isEqualTo('RealName');
                });
        });

        // Try logging in with the new user
        $this->login($unique_id, $unique_id);
        // Log back in with the test user
        $this->login();

        // Update the new user
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('firstname', 'NewFirstName');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get the new user again and verify that the firstname has been updated
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->string($content['firstname'])->isEqualTo('NewFirstName');
                });
        });

        // Delete the new user
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Try getting the new user again (should be OK but is_deleted=1)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(fn ($content) => $this->boolean((bool) $content['is_deleted'])->isTrue());
        });

        // Actually delete the new user
        $request = new Request('DELETE', $new_item_location);
        $request->setParameter('force', 1);
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Try getting the new user again (should be a 404)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCreateUpdateDeleteGroup()
    {
        $this->login('glpi', 'glpi');

        $unique_id = __FUNCTION__;

        // Create a new group
        $request = new Request('POST', '/Administration/Group');
        $request->setParameter('name', $unique_id);

        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $new_item_location = $headers['Location'];
                });
        });

        // Get the new group
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(fn ($content) => $this->string($content['name'])->isEqualTo($unique_id));
        });

        // Update the new group
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('name', $unique_id . '2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get the new group again and verify that the name has been updated
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_id) {
                    $this->string($content['name'])->isEqualTo($unique_id . '2');
                });
        });

        // Delete the new group
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Try getting the new group again (should be a 404)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCreateUpdateDeleteProfile()
    {
        $this->login();

        $unique_id = __FUNCTION__;

        // Create a new profile
        $request = new Request('POST', '/Administration/Profile');
        $request->setParameter('name', $unique_id);

        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $new_item_location = $headers['Location'];
                });
        });

        // Get the new profile
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(fn ($content) => $this->string($content['name'])->isEqualTo($unique_id));
        });

        // Update the new profile
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('name', $unique_id . '2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get the new profile again and verify that the name has been updated
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_id) {
                    $this->string($content['name'])->isEqualTo($unique_id . '2');
                });
        });

        // Delete the new profile
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Try getting the new profile again (should be a 404)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }

    public function testCreateUpdateDeleteEntity()
    {
        $this->login('glpi', 'glpi');

        $unique_id = __FUNCTION__;

        // Create a new entity
        $request = new Request('POST', '/Administration/Entity');
        $request->setParameter('name', $unique_id);
        $request->setParameter('parent', getItemByTypeName('Entity', '_test_root_entity', true));

        $new_item_location = null;
        $this->api->call($request, function ($call) use (&$new_item_location) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->headers(function ($headers) use (&$new_item_location) {
                    $this->array($headers)->hasKey('Location');
                    $this->string($headers['Location'])->isNotEmpty();
                    $new_item_location = $headers['Location'];
                });
        });

        // Get the new entity
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(fn ($content) => $this->string($content['name'])->isEqualTo($unique_id));
        });

        // Update the new entity
        $request = new Request('PATCH', $new_item_location);
        $request->setParameter('name', $unique_id . '2');
        $this->api->call($request, function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Get the new entity again and verify that the name has been updated
        $this->api->call(new Request('GET', $new_item_location), function ($call) use ($unique_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($unique_id) {
                    $this->string($content['name'])->isEqualTo($unique_id . '2');
                });
        });

        // Delete the new entity
        $this->api->call(new Request('DELETE', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isOK();
        });

        // Try getting the new entity again (should be a 404)
        $this->api->call(new Request('GET', $new_item_location), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response->isNotFoundError();
        });
    }
}
