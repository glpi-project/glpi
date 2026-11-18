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

use Config;
use Glpi\Api\HL\TicketCreationIdempotency;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Ticket;

final class TicketCreationIdempotencyHttpTest extends TestCase
{
    public function testConcurrentCreationHasOneResult(): void
    {
        global $DB;

        Config::setConfigurationValues('core', ['enable_hlapi' => true]);
        $client = HttpClient::create(['base_uri' => rtrim(GLPI_URI, '/') . '/api.php/']);
        $token = $client->request('POST', 'token', ['json' => [
            'grant_type' => 'password',
            'client_id' => TU_OAUTH_CLIENT_ID,
            'client_secret' => TU_OAUTH_CLIENT_SECRET,
            'username' => TU_USER,
            'password' => TU_PASS,
            'scope' => 'api',
        ]])->toArray();
        $key = bin2hex(random_bytes(16));
        $name = 'Concurrent ticket creation ' . $key;
        $options = [
            'auth_bearer' => $token['access_token'],
            'headers' => ['Idempotency-Key' => $key],
            'json' => ['name' => $name, 'content' => 'Synthetic test'],
        ];
        $tickets = ['FROM' => Ticket::getTable(), 'WHERE' => ['name' => $name]];

        $responses = [];
        try {
            for ($i = 0; $i < 8; $i++) {
                $responses[] = $client->request('POST', 'v3.0/Assistance/Ticket', $options);
            }
            $ids = [];
            foreach ($responses as $response) {
                if ($response->getStatusCode() === 409) {
                    $this->assertSame('ERROR_IDEMPOTENCY_IN_PROGRESS', $response->toArray(false)['status']);
                    continue;
                }
                $this->assertSame(201, $response->getStatusCode(), $response->getContent(false));
                $ids[] = $response->toArray()['id'];
            }
            $this->assertCount(1, array_unique($ids));
            $retry = $client->request('POST', 'v2.3/Assistance/Ticket', $options);
            $this->assertSame(201, $retry->getStatusCode());
            $this->assertSame($ids[0], $retry->toArray()['id']);
            $this->assertCount(1, $DB->request($tickets));
        } finally {
            // Drain the outstanding requests before removing their test data.
            foreach ($responses as $response) {
                $response->getContent(false);
            }
            $ticket = new Ticket();
            foreach ($DB->request($tickets) as $row) {
                $DB->delete(TicketCreationIdempotency::TABLE, ['tickets_id' => $row['id']]);
                $ticket->delete(['id' => $row['id']], true, false);
            }
        }
    }
}
