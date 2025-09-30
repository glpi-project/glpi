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

namespace tests\units\Glpi\CalDAV;

use DbTestCase;
use Glpi\CalDAV\Server;
use Ramsey\Uuid\Uuid;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\HTTP\Response;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VJournal;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\Property\FlatText;
use Sabre\VObject\Property\ICalendar\Recur;
use Sabre\VObject\Property\IntegerValue;
use Sabre\VObject\Property\Text;
use Sabre\VObject\Reader;

/* Test for inc/glpi/caldav/server.class.php */

class ServerTest extends DbTestCase
{
    protected function propfindMainEndpointsProvider()
    {
        $dataset = [];

        $all_groups_id = array_keys(getAllDataFromTable('glpi_groups', ['is_task' => 1]));
        $group_1_id = getItemByTypeName('Group', '_test_group_1', true);
        $group_2_id = getItemByTypeName('Group', '_test_group_2', true);

        $users = [
            getItemByTypeName('User', 'glpi', true) => [
                'name'   => 'glpi',
                'pass'   => 'glpi',
                'groups' => [$group_1_id, $group_2_id],
                'seeall' => false,
            ],
            getItemByTypeName('User', 'tech', true) => [
                'name'   => 'tech',
                'pass'   => 'tech',
                'groups' => [$group_1_id],
                'seeall' => false,
            ],
            getItemByTypeName('User', 'normal', true) => [
                'name'   => 'normal',
                'pass'   => 'normal',
                'groups' => [$group_2_id],
                'seeall' => false,
            ],
            getItemByTypeName('User', 'post-only', true) => [
                'name'   => 'post-only',
                'pass'   => 'postonly',
                'groups' => [],
                'seeall' => false,
            ],
            getItemByTypeName('User', TU_USER, true) => [
                'name'   => TU_USER,
                'pass'   => TU_PASS,
                'groups' => [],
                'seeall' => true,
            ],
            getItemByTypeName('User', 'jsmith123', true) => [
                'name'   => 'jsmith123',
                'pass'   => TU_PASS,
                'groups' => [],
                'seeall' => true,
            ],
            getItemByTypeName('User', 'e2e_tests', true) => [
                'name'   => 'e2e_tests',
                'pass'   => 'glpi',
                'groups' => [],
                'seeall' => false,
            ],
        ];

        ksort($users);

        // Create test groups
        foreach ($users as $user_id => $user_data) {
            foreach ($user_data['groups'] as $group_id) {
                $group_user = new \Group_User();
                $this->assertGreaterThan(
                    0,
                    (int) $group_user->add([
                        'groups_id' => $group_id,
                        'users_id'  => $user_id,
                    ])
                );
            }
        }

        foreach ($users as $user_data) {
            // All users should be able to get "/", "principals/" and "calendars/" collections properties
            // and should receive same result.
            $dataset[] = [
                'path' => '/',
                'expected_results' => [
                    [
                        'href'         => '',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'principals/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/',
                        'resourcetype' => 'd:collection',
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
            $dataset[] = [
                'path' => 'principals/',
                'expected_results' => [
                    [
                        'href'         => 'principals/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'principals/groups/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'principals/users/',
                        'resourcetype' => 'd:collection',
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
            $dataset[] = [
                'path' => 'calendars/',
                'expected_results' => [
                    [
                        'href'         => 'calendars/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/groups/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/users/',
                        'resourcetype' => 'd:collection',
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
        }

        // All users should be able to get "groups" principals properties
        // but result will only contain data for user groups (or all groups if user has enough rights).
        foreach ($users as $user_data) {
            $groups_expected_results = [
                [
                    'href'         => 'principals/groups/',
                    'resourcetype' => 'd:collection',
                ],
            ];
            $groups = $user_data['seeall'] ? $all_groups_id : $user_data['groups'];
            foreach ($groups as $group_id) {
                // Group principal should be listed in 'principals/groups/' result
                $groups_expected_results[] = [
                    'href'         => 'principals/groups/' . $group_id . '/',
                    'resourcetype' => 'd:principal',
                ];

                // Group principal properties should be accessible at 'principals/groups/$group_id/'
                $dataset[] = [
                    'path' => 'principals/groups/' . $group_id . '/',
                    'expected_results' => [
                        [
                            'href'         => 'principals/groups/' . $group_id . '/',
                            'resourcetype' => 'd:principal',
                        ],
                    ],
                    'login' => $user_data['name'],
                    'pass' => $user_data['pass'],
                ];
            }
            $dataset[] = [
                'path' => 'principals/groups/',
                'expected_results' => $groups_expected_results,
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
        }

        // 'glpi' user can see all users principals
        $expected_results = [
            [
                'href'         => 'principals/users/',
                'resourcetype' => 'd:collection',
            ],
        ];
        foreach ($users as $user_data) {
            $expected_results[] = [
                'href'         => 'principals/users/' . $user_data['name'] . '/',
                'resourcetype' => 'd:principal',
            ];
        }
        $dataset[] = [
            'path'             => 'principals/users/',
            'expected_results' => $expected_results,
            'login'            => 'glpi',
            'pass'             => 'glpi',
        ];

        // 'tech', 'normal', 'post-only' user can see only themselves in principals
        foreach (['tech', 'normal', 'post-only'] as $user_name) {
            $user_id   = getItemByTypeName('User', $user_name, true);
            $user_data = $users[$user_id];

            $dataset[] = [
                'path' => 'principals/users/',
                'expected_results' => [
                    [
                        'href'         => 'principals/users/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'principals/users/' . $user_data['name'] . '/',
                        'resourcetype' => 'd:principal',
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
        }

        // 'glpi' user can see all users calendars
        $expected_results = [
            [
                'href'         => 'calendars/users/',
                'resourcetype' => 'd:collection',
            ],
        ];
        foreach ($users as $user_data) {
            $expected_results[] = [
                'href'         => 'calendars/users/' . $user_data['name'] . '/',
                'resourcetype' => 'd:collection',
            ];
        }
        $dataset[] = [
            'path'             => 'calendars/users/',
            'expected_results' => $expected_results,
            'login'            => 'glpi',
            'pass'             => 'glpi',
        ];
        foreach ($users as $user_data) {
            $dataset[] = [
                'path'             => 'calendars/users/' . $user_data['name'] . '/',
                'expected_results' => [
                    [
                        'href'         => 'calendars/users/' . $user_data['name'] . '/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/users/' . $user_data['name'] . '/calendar/',
                        'resourcetype' => ['d:collection', 'cal:calendar'],
                    ],
                ],
                'login'            => 'glpi',
                'pass'             => 'glpi',
            ];
        }

        // 'tech', 'normal', 'post-only' user can see only themselves in calendars
        foreach (['tech', 'normal', 'post-only'] as $user_name) {
            $user_id   = getItemByTypeName('User', $user_name, true);
            $user_data = $users[$user_id];

            $dataset[] = [
                'path' => 'calendars/users/',
                'expected_results' => [
                    [
                        'href'         => 'calendars/users/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/users/' . $user_data['name'] . '/',
                        'resourcetype' => 'd:collection',
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
            $dataset[] = [
                'path' => 'calendars/users/' . $user_data['name'] . '/',
                'expected_results' => [
                    [
                        'href'         => 'calendars/users/' . $user_data['name'] . '/',
                        'resourcetype' => 'd:collection',
                    ],
                    [
                        'href'         => 'calendars/users/' . $user_data['name'] . '/calendar/',
                        'resourcetype' => ['d:collection', 'cal:calendar'],
                    ],
                ],
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
        }

        // All users should be able to get "groups" calendars properties
        // but result will only contain data for user groups (or all groups if user has enough rights).
        foreach ($users as $user_data) {
            $groups_expected_results = [
                [
                    'href'         => 'calendars/groups/',
                    'resourcetype' => 'd:collection',
                ],
            ];
            $groups = $user_data['seeall'] ? $all_groups_id : $user_data['groups'];
            foreach ($groups as $group_id) {
                // Group principal should be listed in 'calendars/groups/' result
                $groups_expected_results[] = [
                    'href'         => 'calendars/groups/' . $group_id . '/',
                    'resourcetype' => 'd:collection',
                ];

                // Group calendar list properties should be accessible at 'calendars/groups/$group_id/'
                $dataset[] = [
                    'path' => 'calendars/groups/' . $group_id . '/',
                    'expected_results' => [
                        [
                            'href'         => 'calendars/groups/' . $group_id . '/',
                            'resourcetype' => 'd:collection',
                        ],
                        [
                            'href'         => 'calendars/groups/' . $group_id . '/calendar/',
                            'resourcetype' => ['d:collection', 'cal:calendar'],
                        ],
                    ],
                    'login' => $user_data['name'],
                    'pass' => $user_data['pass'],
                ];
            }
            $dataset[] = [
                'path' => 'calendars/groups/',
                'expected_results' => $groups_expected_results,
                'login' => $user_data['name'],
                'pass' => $user_data['pass'],
            ];
        }

        return $dataset;
    }

    public function testPropfindOnMainEndpointsWException()
    {
        foreach ($this->propfindMainEndpointsProvider() as $row) {
            $path = $row['path'];

            $server = $this->getServerInstance('PROPFIND', $path);
            $this->validateThatAuthenticationIsRequired($server);
        }
    }

    /**
     * Test PROPFIND method on main endpoints (principals, calendars collections and calendars).
     * Tests only validates that response is ok and endpoints href and resourcetypes are correctly set.
     */
    public function testPropfindOnMainEndpoints()
    {
        foreach ($this->propfindMainEndpointsProvider() as $row) {
            $path = $row['path'];
            $expected_results = $row['expected_results'];
            $login = $row['login'];
            $pass = $row['pass'];

            $this->login($login, $pass);

            $server = $this->getServerInstance('PROPFIND', $path);
            $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

            $response = new Response();
            $server->invokeMethod($server->httpRequest, $response, false);

            $this->validateResponseIsOk($response, 207, 'application/xml'); // 207 'Multi-Status'
            $xpath = $this->getXpathFromResponse($response);

            $this->assertEquals(
                count($expected_results),
                (int) $xpath->evaluate('count(/d:multistatus/d:response)')
            );

            $response_index = 1;
            foreach ($expected_results as $expected_result) {
                $result_path = '/d:multistatus/d:response[' . $response_index . ']';

                $this->assertEquals(
                    $expected_result['href'],
                    $xpath->evaluate('string(' . $result_path . '/d:href)')
                );

                $resourcetypes = $expected_result['resourcetype'];
                if (!is_array($resourcetypes)) {
                    $resourcetypes = [$resourcetypes];
                }
                $this->assertEquals(
                    count($resourcetypes),
                    (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/d:resourcetype/child::node())')
                );
                foreach ($resourcetypes as $resourcetype) {
                    $this->assertEquals(
                        1,
                        (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/d:resourcetype/' . $resourcetype . ')')
                    );
                }

                $response_index++;
            }
        }
    }

    /**
     * Test PROPFIND method on calendar endpoints.
     * Tests validates that mandatory properties are correctly set.
     */
    public function testPropfindOnPrincipalCalendarWException()
    {
        $login = 'tech';
        $pass  = 'tech';
        $user  = getItemByTypeName('User', $login);

        $group = new \Group();
        $group_id = (int) $group->add([
            'name'    => 'Test group',
            'is_task' => 1,
        ]);
        $this->assertGreaterThan(0, $group_id);
        $group->getFromDB($group_id);

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $user->fields['id'],
            ])
        );

        $this->login($login, $pass);

        $calendars = [
            [
                'path' => 'calendars/users/' . $user->fields['name'] . '/calendar/',
                'name' => $user->getFriendlyName(),
            ],
            [
                'path' => 'calendars/groups/' . $group_id . '/calendar/',
                'name' => $group->getFriendlyName(),
            ],
        ];

        foreach ($calendars as $calendar) {
            $calendar_path = $calendar['path'];
            $calendar_name = $calendar['name'];

            $server = $this->getServerInstance('PROPFIND', $calendar_path);
            $this->validateThatAuthenticationIsRequired($server);
        }
    }

    /**
     * Test PROPFIND method on calendar endpoints.
     * Tests validates that mandatory properties are correctly set.
     */
    public function testPropfindOnPrincipalCalendar()
    {
        $login = 'tech';
        $pass  = 'tech';
        $user  = getItemByTypeName('User', $login);

        $group = new \Group();
        $group_id = (int) $group->add([
            'name'    => 'Test group',
            'is_task' => 1,
        ]);
        $this->assertGreaterThan(0, $group_id);
        $group->getFromDB($group_id);

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $user->fields['id'],
            ])
        );

        $this->login($login, $pass);

        $calendars = [
            [
                'path' => 'calendars/users/' . $user->fields['name'] . '/calendar/',
                'name' => $user->getFriendlyName(),
            ],
            [
                'path' => 'calendars/groups/' . $group_id . '/calendar/',
                'name' => $group->getFriendlyName(),
            ],
        ];

        foreach ($calendars as $calendar) {
            $calendar_path = $calendar['path'];
            $calendar_name = $calendar['name'];

            $server = $this->getServerInstance('PROPFIND', $calendar_path);
            $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

            $response = new Response();
            $server->invokeMethod($server->httpRequest, $response, false);

            $this->validateResponseIsOk($response, 207, 'application/xml'); // 207 'Multi-Status'
            $xpath = $this->getXpathFromResponse($response);

            $result_path = '/d:multistatus/d:response[1]';

            $this->assertEquals($calendar_path, $xpath->evaluate('string(' . $result_path . '/d:href)'));

            $this->assertEquals(
                2,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/d:resourcetype/child::node())')
            );
            $this->assertEquals(
                1,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/d:resourcetype/d:collection)')
            );
            $this->assertEquals(
                1,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/d:resourcetype/cal:calendar)')
            );

            $this->assertEquals(
                $calendar_name,
                $xpath->evaluate('string(' . $result_path . '/d:propstat/d:prop/d:displayname)')
            );

            $this->assertEquals(
                3,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp)')
            );
            $this->assertEquals(
                1,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp[@name="VEVENT"])')
            );
            $this->assertEquals(
                1,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp[@name="VJOURNAL"])')
            );
            $this->assertEquals(
                1,
                (int) $xpath->evaluate('count(' . $result_path . '/d:propstat/d:prop/cal:supported-calendar-component-set/cal:comp[@name="VTODO"])')
            );
        }
    }

    /**
     * Test ACL on main objects.
     */
    public function testAclWException()
    {
        $user  = getItemByTypeName('User', 'tech');

        $group = new \Group();
        $group_id = (int) $group->add([
            'name'    => 'Test group',
            'is_task' => 1,
        ]);
        $this->assertGreaterThan(0, $group_id);
        $this->assertTrue($group->getFromDB($group_id));

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $user->fields['id'],
            ])
        );

        $objects = [
            'principals/users/' . $user->fields['name'],
            'calendars/users/' . $user->fields['name'] . '/calendar/',
            'principals/groups/' . $group_id,
            'calendars/groups/' . $group_id . '/calendar/',
        ];

        $users_access = [
            'normal' => 'HTTP/1.1 403 Forbidden',
            'tech'   => 'HTTP/1.1 200 OK',
        ];

        foreach ($users_access as $username => $expected_status) {
            $this->login($username, $username);

            foreach ($objects as $path) {
                $server = $this->getServerInstance('PROPFIND', $path);
                $this->validateThatAuthenticationIsRequired($server);
            }
        }
    }

    /**
     * Test ACL on main objects.
     */
    public function testAcl()
    {
        $user  = getItemByTypeName('User', 'tech');

        $group = new \Group();
        $group_id = (int) $group->add([
            'name'    => 'Test group',
            'is_task' => 1,
        ]);
        $this->assertGreaterThan(0, $group_id);
        $this->assertTrue($group->getFromDB($group_id));

        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id' => $group_id,
                'users_id'  => $user->fields['id'],
            ])
        );

        $objects = [
            'principals/users/' . $user->fields['name'],
            'calendars/users/' . $user->fields['name'] . '/calendar/',
            'principals/groups/' . $group_id,
            'calendars/groups/' . $group_id . '/calendar/',
        ];

        $users_access = [
            'normal' => 'HTTP/1.1 403 Forbidden',
            'tech'   => 'HTTP/1.1 200 OK',
        ];

        foreach ($users_access as $username => $expected_status) {
            $this->login($username, $username);

            foreach ($objects as $path) {
                $server = $this->getServerInstance('PROPFIND', $path);
                $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($username . ':' . $username));

                $response = new Response();
                $server->invokeMethod($server->httpRequest, $response, false);
                $this->validateResponseIsOk($response, 207, 'application/xml'); // 207 'Multi-Status'

                $xpath = $this->getXpathFromResponse($response);
                $result_path = '/d:multistatus/d:response[1]';
                $this->assertEquals($expected_status, $xpath->evaluate('string(' . $result_path . '/d:propstat/d:status)'));
            }
        }
    }

    /**
     * Test PROPFIND method on calendar events requires authentication.
     */
    public function testPropfindCalendarEventsWException()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Evt description',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'weekly',
                'byweekday' => 'MO',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        // Validate PROPFIND method
        $server = $this->getServerInstance('PROPFIND', $event_path);
        $this->validateThatAuthenticationIsRequired($server);
    }

    /**
     * Test GET method on calendar events requires authentication.
     */
    public function testGetCalendarEventsWException()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Evt description',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'weekly',
                'byweekday' => 'MO',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        // Validate PROPFIND method
        $server = $this->getServerInstance('GET', $event_path);

        $this->validateThatAuthenticationIsRequired($server);
    }

    /**
     * Test DELETE method on calendar events requires authentication.
     */
    public function testDeleteCalendarEventsWException()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Evt description',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'weekly',
                'byweekday' => 'MO',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        // Validate PROPFIND method
        $server = $this->getServerInstance('DELETE', $event_path);

        $this->validateThatAuthenticationIsRequired($server);
    }

    /**
     * Test PUT method on calendar events requires authentication.
     */
    public function testPutCalendarEventsWException()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Evt description',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'weekly',
                'byweekday' => 'MO',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        // Validate PROPFIND method
        $server = $this->getServerInstance('PUT', $event_path);

        $this->validateThatAuthenticationIsRequired($server);
    }

    /**
     * Test PROPFIND, GET, DELETE, PUT methods on calendar events.
     * Tests validates that mandatory properties are correctly set.
     */
    public function testMethodsCalendarEvents()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Evt description',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'weekly',
                'byweekday' => 'MO',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        // Validate PROPFIND method
        $server = $this->getServerInstance('PROPFIND', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 207, 'application/xml'); // 207 'Multi-Status'
        $xpath = $this->getXpathFromResponse($response);

        $result_path = '/d:multistatus/d:response[1]';

        $this->assertEquals($event_path, $xpath->evaluate('string(' . $result_path . '/d:href)'));
        $this->assertEquals(
            (new \DateTime($event->fields['date_mod']))->format('D, d M Y H:i:s \G\M\T'),
            $xpath->evaluate('string(' . $result_path . '/d:propstat/d:prop/d:getlastmodified)')
        );
        $this->assertEquals(
            'text/calendar; charset=utf-8',
            $xpath->evaluate('string(' . $result_path . '/d:propstat/d:prop/d:getcontenttype)')
        );

        // Validate GET method
        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VEvent::class, $vcomp);
        $this->validateCommonVComponentProperties($vcomp, $event->fields);

        // Validate PUT method for update of an existing event
        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VEVENT
UID:{$event->fields['uuid']}
SUMMARY:Test event updated from external source
DTSTART:20191010T113000Z
DTEND:20191010T123000Z
END:VEVENT
END:VCALENDAR
VCALENDAR
        );

        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $updated_event = new \PlanningExternalEvent();
        $this->assertTrue($updated_event->getFromDBByCrit(['uuid' => $event->fields['uuid']]));
        $this->assertIsArray($updated_event->fields);
        $this->assertEquals('Test event updated from external source', $updated_event->fields['name']);
        $this->assertEquals('2019-10-10 11:30:00', $updated_event->fields['begin']);
        $this->assertNull($updated_event->fields['rrule']); // Validate that RRULE has been removed

        // Validate DELETE method
        $server = $this->getServerInstance('DELETE', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertFalse($event->getFromDB($event_id)); // Cannot read it anymore

        // Validate PUT method for creation of a new event
        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VEVENT
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Test event created from external source
DTSTART:20191010T110000Z
DTEND:20191010T120000Z
END:VEVENT
END:VCALENDAR
VCALENDAR
        );

        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        $new_event = new \PlanningExternalEvent();
        $this->assertTrue($new_event->getFromDBByCrit(['uuid' => $event_uuid]));
        $this->assertIsArray($new_event->fields);
        $this->assertEquals($event_uuid, $new_event->fields['uuid']);
        $this->assertEquals('Test event created from external source', $new_event->fields['name']);
        $this->assertEquals('2019-10-10 11:00:00', $new_event->fields['begin']);
        $this->assertEquals('2019-10-10 12:00:00', $new_event->fields['end']);
    }

    /**
     * Test reading VEVENT object from PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadVEventFromPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Test event created in GLPI',
            'text'        => 'Description of the event.',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq'      => 'daily',
                'interval'  => 3,
                'byweekday' => 'MO,TU,WE,TH,FR',
            ],
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VEvent::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $event->fields);

        $rrule_specs = current($vcomp->RRULE->getJsonValue()); // Get first array element which actually correspond to rrule specs
        $this->assertEquals(['freq', 'interval', 'byday'], array_keys($rrule_specs));
        $this->assertEquals('DAILY', $rrule_specs['freq']);
        $this->assertEquals(3, $rrule_specs['interval']);
        $this->assertEquals(['MO', 'TU', 'WE', 'TH', 'FR'], $rrule_specs['byday']);
    }

    /**
     * Test writing a VEVENT object to PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testWriteVEventToPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VEVENT
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Test event created from external source
DESCRIPTION:Description of the event.
RRULE:FREQ=WEEKLY;INTERVAL=2;UNTIL=20191231T080000Z;BYDAY=MO,FR
DTSTART;TZID=Europe/Paris:20191101T090000
DTEND;TZID=Europe/Paris:20191101T120000
END:VEVENT
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        $event = new \PlanningExternalEvent();
        $this->assertTrue($event->getFromDBByCrit(['uuid' => $event_uuid]));
        $this->assertIsArray($event->fields);
        $this->assertEquals($event_uuid, $event->fields['uuid']);
        $this->assertEquals('2019-10-01 08:26:36', $event->fields['date_creation']);
        $this->assertEquals('Test event created from external source', $event->fields['name']);
        $this->assertEquals('Description of the event.', $event->fields['text']);
        $this->assertEquals(\Planning::INFO, $event->fields['state']);
        $this->assertEquals('2019-11-01 08:00:00', $event->fields['begin']);
        $this->assertEquals('2019-11-01 11:00:00', $event->fields['end']);
        $this->assertIsString($event->fields['rrule']);

        $json = json_decode($event->fields['rrule'], true);
        $this->assertIsArray($json);
        $this->assertEquals('WEEKLY', $json['freq']);
        $this->assertEquals(2, $json['interval']);
        $this->assertEquals('2019-12-31 08:00:00', $json['until']);
        $this->assertEquals(['MO', 'FR'], $json['byday']);
    }

    /**
     * Test reading VTODO object from PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadVTodoFromPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        // Not planned task
        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Task created in GLPI',
            'text'        => 'Description of the task.',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'state'       => \Planning::DONE,
        ]);
        $this->assertGreaterThan(0, $event_id);
        $event->getFromDB($event_id);

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VTodo::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $event->fields);

        $this->assertNull($vcomp->DTEND); // Be sure that VTODO does not contains a DTEND

        // Planned task
        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Task created in GLPI',
            'text'        => 'Description of the task.',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'rrule'       => [
                'freq' => 'monthly',
            ],
            'state'       => \Planning::TODO,
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VTodo::class, $vcomp);
        $this->assertNull($vcomp->DTEND); // Be sure that VTODO does not contains a DTEND

        $rrule_specs = current($vcomp->RRULE->getJsonValue()); // Get first array element which actually correspond to rrule specs
        $this->assertEquals(['freq'], array_keys($rrule_specs));
        $this->assertEquals('MONTHLY', $rrule_specs['freq']);
    }

    /**
     * Test writing a VTODO object to PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testWriteVTodoToPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        // Create planned task with TO DO state
        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Test task created from external source
DESCRIPTION:Description of the task.
STATUS:NEEDS-ACTION
DTSTART;TZID=Europe/Paris:20191101T090000
DUE;TZID=Europe/Paris:20191101T091500
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        $event = new \PlanningExternalEvent();
        $this->assertTrue($event->getFromDBByCrit(['uuid' => $event_uuid]));
        $this->assertIsArray($event->fields);
        $this->assertEquals($event_uuid, $event->fields['uuid']);
        $this->assertEquals('2019-10-01 08:26:36', $event->fields['date_creation']);
        $this->assertEquals('Test task created from external source', $event->fields['name']);
        $this->assertEquals('Description of the task.', $event->fields['text']);
        $this->assertEquals(\Planning::TODO, $event->fields['state']);
        $this->assertEquals('2019-11-01 08:00:00', $event->fields['begin']);
        $this->assertEquals('2019-11-01 08:15:00', $event->fields['end']);
        $this->assertNull($event->fields['rrule']);

        // Create done and not planned task
        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Test task created from external source
DESCRIPTION:Description of the task.
STATUS:COMPLETED
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        $event = new \PlanningExternalEvent();
        $this->assertTrue($event->getFromDBByCrit(['uuid' => $event_uuid]));
        $this->assertIsArray($event->fields);
        $this->assertEquals($event_uuid, $event->fields['uuid']);
        $this->assertEquals('2019-10-01 08:26:36', $event->fields['date_creation']);
        $this->assertEquals('Test task created from external source', $event->fields['name']);
        $this->assertEquals('Description of the task.', $event->fields['text']);
        $this->assertEquals(\Planning::DONE, $event->fields['state']);
        $this->assertNull($event->fields['begin']);
        $this->assertNull($event->fields['end']);
        $this->assertNull($event->fields['rrule']);
    }

    /**
     * Test reading VJOURNAL object from PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadVJournalFromPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event = new \PlanningExternalEvent();
        $event_id = (int) $event->add([
            'name'        => 'Note created in GLPI',
            'text'        => 'Description of the note.',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'state'       => \Planning::INFO,
        ]);
        $this->assertGreaterThan(0, $event_id);
        $this->assertTrue($event->getFromDB($event_id));

        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event->fields['uuid'] . '.ics';

        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VJournal::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $event->fields);

        // Be sure that VJOURNAL does not contains plan information
        $this->assertNull($vcomp->DTSTART);
        $this->assertNull($vcomp->DTEND);
        $this->assertNull($vcomp->DUE);
    }

    /**
     * Test writing a VJOURNAL object to PlanningExternalEvent class.
     * Tests validates that different properties are correctly handled.
     */
    public function testWriteVJournalToPlanningExternalEvent()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VJOURNAL
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Note created from external source
DESCRIPTION:Description of the note.
END:VJOURNAL
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        $event = new \PlanningExternalEvent();
        $this->assertTrue($event->getFromDBByCrit(['uuid' => $event_uuid]));
        $this->assertIsArray($event->fields);
        $this->assertEquals($event_uuid, $event->fields['uuid']);
        $this->assertEquals('2019-10-01 08:26:36', $event->fields['date_creation']);
        $this->assertEquals('Note created from external source', $event->fields['name']);
        $this->assertEquals('Description of the note.', $event->fields['text']);
        $this->assertEquals(\Planning::INFO, $event->fields['state']);
        $this->assertNull($event->fields['begin']);
        $this->assertNull($event->fields['end']);
        $this->assertNull($event->fields['rrule']);
    }

    /**
     * Test reading and writing VTODO object from Reminder class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadWriteVTodoFromReminder()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $reminder = new \Reminder();
        $reminder_id = (int) $reminder->add([
            'name'        => 'Test reminder created in GLPI',
            'text'        => 'Description of the reminder.',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertTrue($reminder->getFromDB($reminder_id));

        $creation_date = $reminder->fields['date_creation'];

        $reminder_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $reminder->fields['uuid'] . '.ics';

        // Test reading VTODO object
        $server = $this->getServerInstance('GET', $reminder_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VJournal::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $reminder->fields);

        // Test updating VTODO object
        $server = $this->getServerInstance('PUT', $reminder_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VJOURNAL
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$reminder->fields['uuid']}
SUMMARY:Test reminder updated from external source
DESCRIPTION:Updated description.
END:VJOURNAL
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($reminder->getFromDBByCrit(['uuid' => $reminder->fields['uuid']]));
        $this->assertIsArray($reminder->fields);
        $this->assertEquals($reminder->fields['uuid'], $reminder->fields['uuid']);
        $this->assertEquals($creation_date, $reminder->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Test reminder updated from external source', $reminder->fields['name']);
        $this->assertEquals('Updated description.', $reminder->fields['text']);
        $this->assertEquals(\Planning::INFO, $reminder->fields['state']);
    }

    /**
     * Test reading and writing VJOURNAL object from Reminder class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadWriteVJournalFromReminder()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $reminder = new \Reminder();
        $reminder_id = (int) $reminder->add([
            'name'        => 'Test reminder created in GLPI',
            'text'        => 'Description of the reminder.',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'plan'        => [
                'begin' => '2019-06-15 13:00:00',
                'end'   => '2019-06-15 13:45:00',
            ],
            'state'       => \Planning::TODO,
        ]);
        $this->assertGreaterThan(0, $reminder_id);
        $this->assertTrue($reminder->getFromDB($reminder_id));

        $creation_date = $reminder->fields['date_creation'];

        $reminder_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $reminder->fields['uuid'] . '.ics';

        // Test reading VTODO object
        $server = $this->getServerInstance('GET', $reminder_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VTodo::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $reminder->fields);

        // Test updating VTODO object
        $server = $this->getServerInstance('PUT', $reminder_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$reminder->fields['uuid']}
SUMMARY:Test reminder updated from external source
DESCRIPTION:Updated description.
STATUS:COMPLETED
DTSTART;TZID=Europe/Paris:20191101T090000
DUE;TZID=Europe/Paris:20191101T091500
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($reminder->getFromDBByCrit(['uuid' => $reminder->fields['uuid']]));
        $this->assertIsArray($reminder->fields);
        $this->assertEquals($reminder->fields['uuid'], $reminder->fields['uuid']);
        $this->assertEquals($creation_date, $reminder->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Test reminder updated from external source', $reminder->fields['name']);
        $this->assertEquals('Updated description.', $reminder->fields['text']);
        $this->assertEquals(\Planning::DONE, $reminder->fields['state']);
        $this->assertEquals('2019-11-01 08:00:00', $reminder->fields['begin']);
        $this->assertEquals('2019-11-01 08:15:00', $reminder->fields['end']);
    }

    /**
     * Test reading and writing VTODO object from TicketTask class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadWriteVTodoFromTicketTask()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $ticket = new \Ticket();
        $ticket_id = (int) $ticket->add([
            'name'               => 'Test ticket',
            'content'            => 'Ticket content.',
            'users_id_recipient' => $user->fields['id'],
            'entities_id'        => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $ticket_task = new \TicketTask();
        $ticket_task_id = (int) $ticket_task->add([
            'tickets_id'    => $ticket_id,
            'content'       => 'Description of the task.',
            'users_id_tech' => $user->fields['id'],
        ]);
        $this->assertGreaterThan(0, $ticket_task_id);
        $this->assertTrue($ticket_task->getFromDB($ticket_task_id));

        $creation_date = $ticket_task->fields['date_creation'];

        $ticket_task_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $ticket_task->fields['uuid'] . '.ics';

        // Test reading VTODO object
        $server = $this->getServerInstance('GET', $ticket_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VTodo::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $ticket_task->fields);

        // Test updating VTODO object (without plan information)
        $server = $this->getServerInstance('PUT', $ticket_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$ticket_task->fields['uuid']}
SUMMARY:Test ticket
DESCRIPTION:Updated description.
STATUS:NEEDS-ACTION
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($ticket_task->getFromDBByCrit(['uuid' => $ticket_task->fields['uuid']]));
        $this->assertIsArray($ticket_task->fields);
        $this->assertEquals($ticket_task->fields['uuid'], $ticket_task->fields['uuid']);
        $this->assertEquals($creation_date, $ticket_task->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Updated description.', $ticket_task->fields['content']);
        $this->assertEquals(\Planning::TODO, $ticket_task->fields['state']);

        // Test updating VTODO object (with plan information)
        $server = $this->getServerInstance('PUT', $ticket_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$ticket_task->fields['uuid']}
SUMMARY:Test ticket
DESCRIPTION:Updated description.
STATUS:COMPLETED
DTSTART;TZID=Europe/Paris:20191101T090000
DUE;TZID=Europe/Paris:20191101T091500
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($ticket_task->getFromDBByCrit(['uuid' => $ticket_task->fields['uuid']]));
        $this->assertIsArray($ticket_task->fields);
        $this->assertEquals($ticket_task->fields['uuid'], $ticket_task->fields['uuid']);
        $this->assertEquals($creation_date, $ticket_task->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Updated description.', $ticket_task->fields['content']);
        $this->assertEquals(\Planning::DONE, $ticket_task->fields['state']);
        $this->assertEquals('2019-11-01 08:00:00', $ticket_task->fields['begin']);
        $this->assertEquals('2019-11-01 08:15:00', $ticket_task->fields['end']);
    }

    /**
     * Test reading and writing VTODO object from ProjectTask class.
     * Tests validates that different properties are correctly handled.
     */
    public function testReadWriteVTodoFromProjectTask()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $project = new \Project();
        $project_id = (int) $project->add([
            'name'        => 'Test project',
            'content'     => 'Project content.',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $project_id);

        $project_task = new \ProjectTask();
        $project_task_id = (int) $project_task->add([
            'name'        => 'Test task created in GLPI',
            'content'     => 'Description of the task.',
            'projects_id' => $project_id,
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertGreaterThan(0, $project_task_id);
        $this->assertTrue($project_task->getFromDB($project_task_id));

        $project_task_team = new \ProjectTaskTeam();
        $project_task_team_id = (int) $project_task_team->add([
            'projecttasks_id' => $project_task_id,
            'itemtype'        => 'User',
            'items_id'        => $user->fields['id'],
        ]);
        $this->assertGreaterThan(0, $project_task_team_id);

        $creation_date = $project_task->fields['date_creation'];

        $project_task_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $project_task->fields['uuid'] . '.ics';

        // Test reading VTODO object
        $server = $this->getServerInstance('GET', $project_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VTodo::class, $vcomp);

        $this->validateCommonVComponentProperties($vcomp, $project_task->fields);
        $this->assertInstanceOf(IntegerValue::class, $vcomp->{'PERCENT-COMPLETE'});
        $this->assertEquals((int) $project_task->fields['percent_done'], $vcomp->{'PERCENT-COMPLETE'}->getValue());

        // Test updating VTODO object (without plan information)
        $server = $this->getServerInstance('PUT', $project_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$project_task->fields['uuid']}
SUMMARY:Test ticket
DESCRIPTION:Updated description.
STATUS:NEEDS-ACTION
PERCENT-COMPLETE:35
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($project_task->getFromDBByCrit(['uuid' => $project_task->fields['uuid']]));
        $this->assertIsArray($project_task->fields);
        $this->assertEquals($project_task->fields['uuid'], $project_task->fields['uuid']);
        $this->assertEquals($creation_date, $project_task->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Updated description.', $project_task->fields['content']);
        $this->assertEquals(35, $project_task->fields['percent_done']);

        // Test updating VTODO object (with plan information)
        $server = $this->getServerInstance('PUT', $project_task_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VTODO
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$project_task->fields['uuid']}
SUMMARY:Test ticket
DESCRIPTION:Updated description.
STATUS:COMPLETED
DTSTART;TZID=Europe/Paris:20191101T090000
DUE;TZID=Europe/Paris:20191101T091500
END:VTODO
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(204, $response->getStatus()); // 204 'No Content'

        $this->assertTrue($project_task->getFromDBByCrit(['uuid' => $project_task->fields['uuid']]));
        $this->assertIsArray($project_task->fields);
        $this->assertEquals($project_task->fields['uuid'], $project_task->fields['uuid']);
        $this->assertEquals($creation_date, $project_task->fields['date_creation']); // be sure that creation date is not overrided
        $this->assertEquals('Updated description.', $project_task->fields['content']);
        $this->assertEquals(100, $project_task->fields['percent_done']);
        $this->assertEquals('2019-11-01 08:00:00', $project_task->fields['plan_start_date']); // 1 hour offset between Europe/Paris and UTC
        $this->assertEquals('2019-11-01 08:15:00', $project_task->fields['plan_end_date']); // 1 hour offset between Europe/Paris and UTC
    }

    /**
     * Test that not handled properties are persistent when reading a previously saved object.
     */
    public function testPersistenceOfNotHandledProps()
    {
        $login = TU_USER;
        $pass  = TU_PASS;
        $user  = getItemByTypeName('User', $login);

        $this->login($login, $pass);

        $event_uuid = Uuid::uuid4()->toString();
        $event_path = 'calendars/users/' . $user->fields['name'] . '/calendar/' . $event_uuid . '.ics';

        // Store a new object
        $server = $this->getServerInstance('PUT', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));
        $server->httpRequest->setBody(
            <<<VCALENDAR
BEGIN:VCALENDAR
PRODID:-//glpi-project.org/NONSGML GLPI Test suite//EN
VERSION:2.0
BEGIN:VEVENT
CREATED:20191001T082636Z
LAST-MODIFIED:20191001T082638Z
DTSTAMP:20191001T082638Z
UID:{$event_uuid}
SUMMARY:Test event created from external source
DESCRIPTION:Description of the event.
RRULE:FREQ=WEEKLY;INTERVAL=2;UNTIL=20191231T080000Z;BYDAY=MO,FR
DTSTART;TZID=Europe/Paris:20191101T090000
DTEND;TZID=Europe/Paris:20191101T120000
CATEGORIES:First category
CATEGORIES:Another cat
LOCATION:Here
END:VEVENT
END:VCALENDAR
VCALENDAR
        );

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->assertEquals(201, $response->getStatus()); // 201 'Created'

        // Read created object
        $server = $this->getServerInstance('GET', $event_path);
        $server->httpRequest->addHeader('Authorization', 'Basic ' . base64_encode($login . ':' . $pass));

        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);

        $this->validateResponseIsOk($response, 200, 'text/calendar'); // 200 'OK'

        $vcalendar = Reader::read($response->getBodyAsString());
        $vcomp = $vcalendar->getBaseComponent();
        $this->assertInstanceOf(VEvent::class, $vcomp);

        $categories = $vcomp->select('CATEGORIES');
        $this->assertCount(2, $categories);
        $this->assertInstanceOf(Text::class, $categories[0]);
        $this->assertEquals('First category', $categories[0]->getValue());
        $this->assertInstanceOf(Text::class, $categories[1]);
        $this->assertEquals('Another cat', $categories[1]->getValue());
        $this->assertInstanceOf(FlatText::class, $vcomp->LOCATION);
        $this->assertEquals('Here', $vcomp->LOCATION->getValue());
    }

    /**
     * Validate that method invocation on server will result in a
     * NotAuthenticated exception.
     *
     * @param Server $server
     */
    private function validateThatAuthenticationIsRequired(Server $server)
    {
        $this->expectException(NotAuthenticated::class);
        $response = new Response();
        $server->invokeMethod($server->httpRequest, $response, false);
    }

    /**
     * Validate that response is OK.
     *
     * @param Response $response
     * @param integer              $status
     * @param string|null          $content_type
     */
    private function validateResponseIsOk(Response $response, int $status, string $content_type)
    {
        $this->assertEquals($status, $response->getStatus());
        $this->assertEquals(
            $content_type . '; charset=utf-8',
            $response->getHeader('Content-Type')
        );
        $response_body = $response->getBodyAsString();
        $this->assertNotEmpty($response_body);
        // If initial response was a stream, reading it changed stream pointer and make it hard to read again.
        // Setting body as text makes it infinitely readable.
        $response->setBody($response_body);
    }

    /**
     * Get a server instance.
     *
     * @param string $http_method
     * @param string $path
     *
     * @return Server
     */
    private function getServerInstance(string $http_method, string $path): Server
    {
        $base_url = '/caldav.php';

        $server = new Server();
        $server->httpRequest->setBaseUrl($base_url);
        $server->httpRequest->setMethod($http_method);
        $server->httpRequest->setUrl($base_url . $path);

        return $server;
    }

    /**
     * Get a XPath object from response.
     *
     * @param Response $response
     *
     * @return \DOMXPath
     */
    private function getXpathFromResponse(Response $response): \DOMXPath
    {
        $xml = new \DOMDocument();
        $this->assertTrue($xml->loadXML($response->getBodyAsString()));
        return new \DOMXPath($xml);
    }

    /**
     * Validate common VComponent properies based on object fields.
     *
     * @param Component $vcomp
     * @param array $fields
     */
    private function validateCommonVComponentProperties(Component $vcomp, array $fields)
    {
        $this->assertInstanceOf(FlatText::class, $vcomp->UID);
        $this->assertEquals($fields['uuid'], $vcomp->UID->getValue());

        if (array_key_exists('name', $fields)) {
            $this->assertInstanceOf(FlatText::class, $vcomp->SUMMARY);
            $this->assertEquals($fields['name'], $vcomp->SUMMARY->getValue());
        }

        $content = array_key_exists('text', $fields) ? $fields['text'] : $fields['content'];
        $this->assertInstanceOf(FlatText::class, $vcomp->DESCRIPTION);
        $this->assertEquals($content, $vcomp->DESCRIPTION->getValue());

        $creation_date = array_key_exists('date_creation', $fields) ? $fields['date_creation'] : $fields['date'];
        $this->assertInstanceOf(\Sabre\VObject\Property\ICalendar\DateTime::class, $vcomp->CREATED);
        $this->assertEquals($creation_date, $vcomp->CREATED->getDateTime()->format('Y-m-d H:i:s'));

        $this->assertInstanceOf(\Sabre\VObject\Property\ICalendar\DateTime::class, $vcomp->DTSTAMP);
        $this->assertEquals($fields['date_mod'], $vcomp->DTSTAMP->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertInstanceOf(\Sabre\VObject\Property\ICalendar\DateTime::class, $vcomp->{'LAST-MODIFIED'});
        $this->assertEquals($fields['date_mod'], $vcomp->{'LAST-MODIFIED'}->getDateTime()->format('Y-m-d H:i:s'));

        if (!empty($fields['begin'])) {
            $this->assertInstanceOf(\Sabre\VObject\Property\ICalendar\DateTime::class, $vcomp->DTSTART);
            $this->assertEquals($fields['begin'], $vcomp->DTSTART->getDateTime()->format('Y-m-d H:i:s'));
        } else {
            $this->assertNull($vcomp->DTSTART);
        }
        $end_field = $vcomp instanceof VEvent ? 'DTEND' : 'DUE';
        if (!empty($fields['end'])) {
            $this->assertInstanceOf(\Sabre\VObject\Property\ICalendar\DateTime::class, $vcomp->$end_field);
            $this->assertEquals($fields['end'], $vcomp->$end_field->getDateTime()->format('Y-m-d H:i:s'));
        } else {
            $this->assertNull($vcomp->$end_field);
        }

        if (!empty($fields['rrule'])) {
            $this->assertInstanceOf(Recur::class, $vcomp->RRULE);
            $this->assertCount(1, $vcomp->RRULE->getJsonValue());
        } else {
            $this->assertNull($vcomp->RRULE);
        }
    }
}
