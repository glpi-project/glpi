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

namespace Glpi\Api;

use Closure;
use DBmysql;
use Glpi\DBAL\QueryExpression;
use Session;
use stdClass;
use Throwable;
use Ticket;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

/**
 * Durable replay of single-ticket creations on the primary database connection.
 * This is deliberately not a CommonDBTM item exposed through the generic API.
 *
 * @phpstan-type ApiResult array{body: array<array-key, mixed>, status: int, headers: array<string, string>}
 */
final class TicketCreationIdempotency
{
    public const TABLE = 'glpi_apiticketrequests';

    public function __construct(private readonly DBmysql $db) {}

    /**
     * @param Closure(): ApiResult $create
     * @return ApiResult
     */
    public function execute(string $key, stdClass $input, int $apiclients_id, bool $debug, Closure $create): array
    {
        if (!preg_match('/\A(?:"([A-Za-z0-9._:-]{1,255})"|([A-Za-z0-9._:-]{1,255}))\z/', $key, $matches)) {
            return $this->error(400, 'ERROR_IDEMPOTENCY_INVALID_KEY', __('Invalid Idempotency-Key.'));
        }
        $key = $matches[1] !== '' ? $matches[1] : $matches[2];
        $entities = $_SESSION['glpiactiveentities'];
        sort($entities);
        $key_hash = hash('sha256', json_encode([
            'POST Ticket', Session::getLoginUserID(), $apiclients_id,
            $_SESSION['glpiactiveprofile']['id'], $_SESSION['glpiactive_entity'],
            $entities, $key,
        ]));
        $fingerprint = hash('sha256', json_encode([
            $this->normalize($input), $debug,
        ], JSON_PRESERVE_ZERO_FRACTION));

        // Expired, completed records are collected when the next keyed request arrives.
        $this->db->delete(self::TABLE, ['expires_at' => ['<=', new QueryExpression('NOW()')]]);
        $locked = $this->lock($key_hash, true);
        if ($locked !== 1) {
            return $locked === 0
                ? $this->error(409, 'ERROR_IDEMPOTENCY_IN_PROGRESS', __('A request with this key is still processing.'))
                : $this->error(503, 'ERROR_IDEMPOTENCY_UNAVAILABLE', __('Idempotency storage is unavailable.'));
        }

        try {
            $rows = $this->db->request(['FROM' => self::TABLE, 'WHERE' => ['key_hash' => $key_hash]]);
            if (count($rows) > 0) {
                return $this->replay($rows->current(), $fingerprint);
            }

            $this->db->beginTransaction();
            try {
                $result = $create();
                $this->db->insert(self::TABLE, [
                    'key_hash' => $key_hash,
                    'fingerprint' => $fingerprint,
                    'tickets_id' => $result['body']['id'],
                    'response' => json_encode($result),
                    'expires_at' => new QueryExpression('DATE_ADD(NOW(), INTERVAL 1 DAY)'),
                ]);
                $this->db->commit();
                return $result;
            } catch (Throwable $e) {
                $this->db->rollBack();
                throw $e;
            }
        } finally {
            $this->lock($key_hash, false);
        }
    }

    /**
     * @param array{fingerprint: string, tickets_id: int, response: string} $record
     * @return ApiResult
     */
    private function replay(array $record, string $fingerprint): array
    {
        if (!hash_equals($record['fingerprint'], $fingerprint)) {
            return $this->error(409, 'ERROR_IDEMPOTENCY_CONFLICT', __('This key was already used with different input.'));
        }
        $ticket = new Ticket();
        if (!$ticket->getFromDB($record['tickets_id']) || $ticket->fields['is_deleted']) {
            return $this->error(409, 'ERROR_IDEMPOTENCY_RESULT_UNAVAILABLE', __('The original ticket is no longer available.'));
        }
        if (!$ticket->can($record['tickets_id'], READ)) {
            return $this->error(403, 'ERROR_RIGHT_MISSING', __('You do not have permission to view the original ticket.'));
        }
        $result = json_decode($record['response'], true);
        $result['headers']['Idempotency-Replayed'] = 'true';
        return $result;
    }

    private function lock(string $key_hash, bool $acquire): ?int
    {
        // Hash the database namespace too: MySQL lock names are limited to 64 bytes.
        $name = hash('sha256', $this->db->dbdefault . '.ticket-idempotency.' . $key_hash);
        $result = $this->db->request([
            'SELECT' => new QueryExpression($acquire ? 'GET_LOCK(?, 0)' : 'RELEASE_LOCK(?)', 'result', [$name]),
            'FROM' => new QueryExpression('DUAL'),
        ])->current()['result'];
        return $result === null ? null : (int) $result;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $fields = get_object_vars($value);
            ksort($fields, SORT_STRING);
            return (object) array_map($this->normalize(...), $fields);
        }
        return is_array($value) ? array_map($this->normalize(...), $value) : $value;
    }

    /**
     * @return ApiResult
     */
    private function error(int $status, string $code, string $message): array
    {
        return ['body' => [$code, $message], 'status' => $status, 'headers' => []];
    }
}
