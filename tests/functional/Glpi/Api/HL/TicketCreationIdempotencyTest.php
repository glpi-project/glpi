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

namespace tests\units\Glpi\Api\HL;

use Glpi\Api\HL\TicketCreationIdempotency;
use Glpi\Http\Response;
use Glpi\Tests\DbTestCase;
use RuntimeException;
use Ticket;

final class TicketCreationIdempotencyTest extends DbTestCase
{
    public function testCreationFailureRollsBackAndReleasesKey(): void
    {
        global $DB;
        $this->login();
        $service = new TicketCreationIdempotency($DB);
        $input = ['name' => 'Synthetic rollback test', 'entities_id' => $_SESSION['glpiactive_entity']];
        $ticket = new Ticket();
        $failure = new RuntimeException('Synthetic failure after inserting the ticket');

        try {
            $service->create('rollback-test', $input, function () use ($ticket, $input, $failure): never {
                $this->assertGreaterThan(0, $ticket->add($input));
                throw $failure;
            });
            $this->fail('Expected the creation failure');
        } catch (RuntimeException $error) {
            $this->assertSame($failure, $error);
        }

        $this->assertFalse((new Ticket())->getFromDB($ticket->getID()));
        $id = $service->create('rollback-test', $input, static fn() => (new Ticket())->add($input));
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->assertSame($id, $service->create('rollback-test', $input, function (): never {
            $this->fail('Replay attempted to create another ticket');
        }));
    }

    public function testReplayRequiresReadAccessAndExpires(): void
    {
        global $DB;
        $this->login();
        $service = new TicketCreationIdempotency($DB);
        $input = ['name' => 'Synthetic ticket access test', 'entities_id' => $_SESSION['glpiactive_entity']];
        $create = static fn() => (new Ticket())->add($input);
        $id = $service->create('access-test', $input, $create);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        $this->assertSame($id, $service->create('access-test', $input, $create));

        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = CREATE;
        $denied = $service->create('access-test', $input, $create);
        $this->assertInstanceOf(Response::class, $denied);
        $this->assertSame(403, $denied->getStatusCode());

        $this->login();
        $DB->update(TicketCreationIdempotency::TABLE, ['expires_at' => '2000-01-01 00:00:00'], ['tickets_id' => $id]);
        $next = $service->create('access-test', $input, $create);
        $this->assertIsInt($next);
        $this->assertGreaterThan(0, $next);
        $this->assertNotSame($id, $next);
    }
}
