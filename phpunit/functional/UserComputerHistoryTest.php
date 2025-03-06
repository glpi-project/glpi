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

namespace tests\units;

use Computer;
use DbTestCase;
use Log;
use User;

class UserComputerHistoryTest extends DbTestCase
{
    protected const USER_NAME = 'test_user_history';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Computer
     */
    protected $computer;

    public function setUp(): void
    {
        parent::setUp();
        $this->login();

        $this->user = $this->createItem('User', [
            'name'     => self::USER_NAME,
            'realname' => 'Test User',
            'firstname' => 'History'
        ]);
        $this->assertGreaterThan(0, $this->user->getID());

        $root_entity_id = getItemByTypeName('Entity', '_test_root_entity', true);
        $this->computer = $this->createItem('Computer', [
            'name'   => 'Test Computer',
            'entities_id' => $root_entity_id
        ]);
        $this->assertGreaterThan(0, $this->computer->getID());

        global $DB;
        $DB->insert(
            'glpi_logs',
            [
                'itemtype'         => 'Computer',
                'items_id'         => $this->computer->getID(),
                'id_search_option' => 70,
                'old_value'        => '',
                'new_value'        => self::USER_NAME . ' (' . $this->user->getID() . ')',
                'date_mod'         => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'user_name'        => 'glpi'
            ]
        );

        $DB->insert(
            'glpi_logs',
            [
                'itemtype'         => 'Computer',
                'items_id'         => $this->computer->getID(),
                'id_search_option' => 70,
                'old_value'        => self::USER_NAME . ' (' . $this->user->getID() . ')',
                'new_value'        => '',
                'date_mod'         => date('Y-m-d H:i:s'),
                'user_name'        => 'glpi'
            ]
        );
    }

    public function testCountComputersForUser()
    {
        $count = $this->user->countComputersForUser();
        $this->assertEquals(2, $count);

        $count_filtered = $this->user->countComputersForUser(['user_name' => 'glpi']);
        $this->assertEquals(2, $count_filtered);

        $count_filtered = $this->user->countComputersForUser(['user_name' => 'non_existing_user']);
        $this->assertEquals(0, $count_filtered);
    }

    public function testGetComputersHistoryForUser()
    {
        $history = $this->user->getComputersHistoryForUser();
        $this->assertCount(2, $history);

        $this->assertArrayHasKey('display_history', reset($history));
        $this->assertArrayHasKey('id', reset($history));
        $this->assertArrayHasKey('date_mod', reset($history));
        $this->assertArrayHasKey('user_name', reset($history));
        $this->assertArrayHasKey('field', reset($history));
        $this->assertArrayHasKey('change', reset($history));

        $this->assertEquals('glpi', $history[0]['user_name']);
        $this->assertEquals('glpi', $history[1]['user_name']);

        $filtered_history = $this->user->getComputersHistoryForUser(['user_name' => 'glpi']);
        $this->assertCount(2, $filtered_history);

        $filtered_history = $this->user->getComputersHistoryForUser(['user_name' => 'non_existing_user']);
        $this->assertEmpty($filtered_history);
    }

    public function testGetDistinctUserNamesValuesInItemLog()
    {
        $usernames = $this->user->getDistinctUserNamesValuesInItemLog();
        $this->assertArrayHasKey('glpi', $usernames);
        $this->assertCount(1, $usernames);
    }

    public function testGetDistinctAffectedFieldValuesInItemLog()
    {
        $fields = $this->user->getDistinctAffectedFieldValuesInItemLog();
        $this->assertCount(1, $fields);

        $key = 'id_search_option::70;';
        $this->assertArrayHasKey($key, $fields);
    }

    public function testLogIntegration()
    {
        $changes = Log::getHistoryData($this->user);

        $found_computer_history = false;
        foreach ($changes as $change) {
            if (strpos($change['change'], 'Test Computer') !== false) {
                $found_computer_history = true;
                break;
            }
        }
        $this->assertTrue($found_computer_history);

        $log = new Log();
        $tab_count = $log->getTabNameForItem($this->user, 1);
        $this->assertStringContainsString((string)2, $tab_count);
    }

    public function testLogFilters()
    {
        $user_names = Log::getDistinctUserNamesValuesInItemLog($this->user);
        $this->assertArrayHasKey('glpi', $user_names);

        $affected_fields = Log::getDistinctAffectedFieldValuesInItemLog($this->user);
        $found_user_field = false;
        foreach (array_keys($affected_fields) as $key) {
            if (strpos($key, 'id_search_option::70') !== false) {
                $found_user_field = true;
                break;
            }
        }
        $this->assertTrue($found_user_field);
    }

    public function tearDown(): void
    {
        if ($this->user !== null && $this->user->getID() > 0) {
            $this->assertTrue($this->user->delete(['id' => $this->user->getID()]));
        }
        if ($this->computer !== null && $this->computer->getID() > 0) {
            $this->assertTrue($this->computer->delete(['id' => $this->computer->getID()], true));
        }

        global $DB;
        $DB->delete(
            'glpi_logs',
            [
                'itemtype' => 'Computer',
                'items_id' => $this->computer ? $this->computer->getID() : 0
            ]
        );

        parent::tearDown();
    }
}
