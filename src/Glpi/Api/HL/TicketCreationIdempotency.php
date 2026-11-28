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

namespace Glpi\Api\HL;

use Closure;
use DBmysql;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\DBAL\QueryExpression;
use Glpi\Http\JSONResponse;
use Glpi\Http\Response;
use Session;
use Throwable;
use Ticket;

use function Safe\json_encode;
use function Safe\preg_match;

final class TicketCreationIdempotency
{
    public const TABLE = 'glpi_apiticketrequests';

    public function __construct(private readonly DBmysql $db) {}

    /**
     * @param array<string, mixed> $input
     * @param Closure(): (int|false) $create
     */
    public function create(string $key, array $input, Closure $create): int|false|Response
    {
        if (!preg_match('/\A(?:"([A-Za-z0-9._:-]{1,255})"|([A-Za-z0-9._:-]{1,255}))\z/', $key, $matches)) {
            return $this->error(400, AbstractController::ERROR_INVALID_PARAMETER, _x('api', 'Invalid Idempotency-Key'));
        }
        $key = $matches[1] !== '' ? $matches[1] : $matches[2];
        $key_hash = hash('sha256', json_encode([
            Session::getLoginUserID(), Router::getInstance()->getCurrentClient()['client_id'] ?? null, $key,
        ]));
        $fingerprint = hash('sha256', json_encode($this->normalize($input), JSON_PRESERVE_ZERO_FRACTION));

        // Collect expired entries using the expiry index. Only completed creations are stored.
        $this->db->delete(self::TABLE, ['expires_at' => ['<=', new QueryExpression('NOW()')]]);
        // MySQL lock names are limited to 64 bytes. Include the DB name for shared servers.
        $lock_name = hash('sha256', $this->db->dbdefault . '.hl-ticket-idempotency.' . $key_hash);
        $locked = $this->db->request([
            'SELECT' => new QueryExpression('GET_LOCK(?, 0)', 'acquired', [$lock_name]),
            'FROM' => new QueryExpression('DUAL'),
        ])->current()['acquired'];
        if ($locked === null) {
            return $this->error(503, 'ERROR_IDEMPOTENCY_UNAVAILABLE', _x('api', 'Idempotency storage is unavailable'));
        }
        if ((int) $locked === 0) {
            return $this->error(409, 'ERROR_IDEMPOTENCY_IN_PROGRESS', _x('api', 'A request with this key is still processing'));
        }

        try {
            $rows = $this->db->request(['FROM' => self::TABLE, 'WHERE' => ['key_hash' => $key_hash]]);
            if (count($rows) > 0) {
                return $this->replay($rows->current(), $fingerprint);
            }

            $this->db->beginTransaction();
            try {
                $id = $create();
                if ($id !== false) {
                    $this->db->insert(self::TABLE, [
                        'key_hash' => $key_hash,
                        'fingerprint' => $fingerprint,
                        'tickets_id' => $id,
                        'expires_at' => new QueryExpression('DATE_ADD(NOW(), INTERVAL 1 DAY)'),
                    ]);
                    $this->db->commit();
                    return $id;
                }
            } catch (Throwable $error) {
                $this->db->rollBack();
                throw $error;
            }

            $this->db->rollBack();
            return false;
        } finally {
            $this->db->request([
                'SELECT' => new QueryExpression('RELEASE_LOCK(?)', null, [$lock_name]),
                'FROM' => new QueryExpression('DUAL'),
            ]);
        }
    }

    /** @param array{fingerprint: string, tickets_id: int} $record */
    private function replay(array $record, string $fingerprint): int|Response
    {
        if (!hash_equals($record['fingerprint'], $fingerprint)) {
            return $this->error(409, 'ERROR_IDEMPOTENCY_CONFLICT', _x('api', 'This key was already used with different input'));
        }
        $ticket = new Ticket();
        $id = $record['tickets_id'];
        if (!$ticket->getFromDB($id) || $ticket->isDeleted()) {
            return $this->error(409, 'ERROR_IDEMPOTENCY_RESULT_UNAVAILABLE', _x('api', 'The original ticket is no longer available'));
        }
        if (!$ticket->can($id, READ)) {
            return AbstractController::getAccessDeniedErrorResponse();
        }
        return $id;
    }

    private function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }
        return array_map($this->normalize(...), $value);
    }

    private function error(int $status, string $code, string $title): Response
    {
        return new JSONResponse(['status' => $code, 'title' => $title, 'detail' => null], $status);
    }
}
