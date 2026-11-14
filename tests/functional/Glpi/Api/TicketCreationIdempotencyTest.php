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

namespace tests\units\Glpi\Api;

use Glpi\Api\TicketCreationIdempotency;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile_User;
use RuntimeException;
use Session;
use stdClass;
use Ticket;

class TicketCreationIdempotencyTest extends DbTestCase
{
    private function execute(string $key, stdClass $input, int $client = 0): array
    {
        global $DB;

        return (new TicketCreationIdempotency($DB))->execute($key, $input, $client, false, function () use ($input): array {
            $ticket = $this->createItem(Ticket::class, (array) $input + ['entities_id' => $_SESSION['glpiactive_entity']]);
            return [
                'body' => ['id' => $ticket->getID(), 'message' => 'Created'],
                'status' => 201,
                'headers' => ['location' => '/Ticket/' . $ticket->getID()],
            ];
        });
    }

    public function testExpiry(): void
    {
        global $DB;

        $this->login();
        $input = (object) ['name' => __FUNCTION__];
        $first = $this->execute(__FUNCTION__, $input);
        $record = $DB->request([
            'FROM' => TicketCreationIdempotency::TABLE,
            'WHERE' => ['tickets_id' => $first['body']['id']],
        ])->current();
        $this->assertSame(64, strlen($record['key_hash']));
        $this->assertSame(64, strlen($record['fingerprint']));
        $this->assertSame($first['body'], $this->execute(__FUNCTION__, $input)['body']);
        $DB->update(TicketCreationIdempotency::TABLE, ['expires_at' => '2000-01-01 00:00:00'], ['id' => $record['id']]);
        $next = $this->execute(__FUNCTION__, $input);
        $this->assertSame(201, $next['status']);
        $this->assertNotSame($first['body']['id'], $next['body']['id']);
        $this->assertCount(1, $DB->request([
            'FROM' => TicketCreationIdempotency::TABLE,
            'WHERE' => ['key_hash' => $record['key_hash']],
        ]));
    }

    public function testObjectOrderAndQuotedKey(): void
    {
        $this->login();
        $first = $this->execute(__FUNCTION__, (object) ['name' => __FUNCTION__, 'content' => 'Synthetic']);
        $retry = $this->execute('"' . __FUNCTION__ . '"', (object) ['content' => 'Synthetic', 'name' => __FUNCTION__]);
        $this->assertSame($first['body'], $retry['body']);
        $this->assertSame('true', $retry['headers']['Idempotency-Replayed']);
        $changed = $this->execute(__FUNCTION__, (object) ['name' => __FUNCTION__, 'content' => 'Changed']);
        $this->assertSame(409, $changed['status']);
    }

    public function testClientAndUserScope(): void
    {
        $this->login();
        $input = (object) ['name' => __FUNCTION__];
        $first = $this->execute(__FUNCTION__, $input, 1);
        $second_client = $this->execute(__FUNCTION__, $input, 2);
        $this->assertNotSame($first['body']['id'], $second_client['body']['id']);
        $this->login('tech');
        $second_user = $this->execute(__FUNCTION__, $input, 1);
        $this->assertNotSame($first['body']['id'], $second_user['body']['id']);
    }

    public function testReplayRechecksReadRights(): void
    {
        $this->login();
        $input = (object) ['name' => __FUNCTION__];
        $this->execute(__FUNCTION__, $input);
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = 0;
        $retry = $this->execute(__FUNCTION__, $input);
        $this->assertSame(403, $retry['status']);
        $this->assertSame('ERROR_RIGHT_MISSING', $retry['body'][0]);
    }

    public function testActiveEntityAndProfileScope(): void
    {
        $this->login();
        $this->setEntity('_test_root_entity', false);
        $input = (object) ['name' => __FUNCTION__];
        $first = $this->execute(__FUNCTION__, $input);
        $this->setEntity('_test_child_1', false);
        $child = $this->execute(__FUNCTION__, $input);
        $this->assertNotSame($first['body']['id'], $child['body']['id']);
        $this->setEntity('_test_root_entity', false);
        $this->assertSame($first['body'], $this->execute(__FUNCTION__, $input)['body']);

        $this->createItem(Profile_User::class, [
            'users_id' => Session::getLoginUserID(),
            'profiles_id' => 3,
            'entities_id' => $_SESSION['glpiactive_entity'],
            'is_recursive' => 1,
        ]);
        $this->login();
        Session::changeProfile(3);
        $this->assertSame(3, $_SESSION['glpiactiveprofile']['id']);
        $this->setEntity('_test_root_entity', false);
        $profile = $this->execute(__FUNCTION__, $input);
        $this->assertNotSame($first['body']['id'], $profile['body']['id']);
    }

    public static function deletionProvider(): iterable
    {
        yield 'soft deletion' => [false];
        yield 'purge' => [true];
    }

    #[DataProvider('deletionProvider')]
    public function testDeletedTicketIsNotRecreated(bool $purge): void
    {
        global $DB;

        $this->login();
        $input = (object) ['name' => __FUNCTION__];
        $first = $this->execute(__FUNCTION__, $input);
        $id = $first['body']['id'];
        if ($purge) {
            $DB->delete(Ticket::getTable(), ['id' => $id]);
        } else {
            $DB->update(Ticket::getTable(), ['is_deleted' => 1], ['id' => $id]);
        }
        $retry = $this->execute(__FUNCTION__, $input);
        $this->assertSame(409, $retry['status']);
        $this->assertSame('ERROR_IDEMPOTENCY_RESULT_UNAVAILABLE', $retry['body'][0]);
        $this->assertCount($purge ? 0 : 1, $DB->request(['FROM' => Ticket::getTable(), 'WHERE' => ['name' => __FUNCTION__]]));
    }

    public function testFailureRollsBackTicketAndReleasesKey(): void
    {
        global $DB;

        $this->login();
        $input = (object) ['name' => __FUNCTION__];
        $id = null;
        try {
            (new TicketCreationIdempotency($DB))->execute(__FUNCTION__, $input, 0, false, function () use ($input, &$id): array {
                $id = $this->createItem(Ticket::class, (array) $input)->getID();
                throw new RuntimeException('Synthetic failure after insert');
            });
            $this->fail('The failure must be propagated.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic failure after insert', $e->getMessage());
        }
        $this->assertNotNull($id);
        $this->assertFalse((new Ticket())->getFromDB($id));
        $retry = $this->execute(__FUNCTION__, $input);
        $this->assertSame(201, $retry['status']);
        $this->assertCount(1, $DB->request(['FROM' => Ticket::getTable(), 'WHERE' => ['name' => __FUNCTION__]]));
    }
}
