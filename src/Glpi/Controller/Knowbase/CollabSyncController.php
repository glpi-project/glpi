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

namespace Glpi\Controller\Knowbase;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use KnowbaseItem;
use Safe\Exceptions\UrlException;
use Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use function Safe\base64_decode;
use function Safe\session_write_close;
use function Safe\strtotime;

/**
 * Relay of the real time collaborative edition of an article.
 *
 * The server does not read the Y.js data. It keeps the document updates in an
 * append-only log and the last awareness state of each client, and gives them
 * to the other clients when they poll.
 */
final class CollabSyncController extends AbstractController
{
    private const UPDATES_TABLE = 'glpi_knowbaseitems_collabupdates';
    private const AWARENESS_TABLE = 'glpi_knowbaseitems_collabawareness';

    /** Same delay as the Y.js awareness protocol (`outdatedTimeout`). */
    private const AWARENESS_TIMEOUT = 30;

    #[Route(
        "/Knowbase/{id}/Collab/Sync",
        name: "knowbase_collab_sync",
        requirements: [
            'id' => '\d+',
        ],
        methods: 'POST',
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($id)) {
            throw new NotFoundHttpException();
        }
        if (!$kbitem->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        // Each editor polls every second: do not keep the session file locked.
        session_write_close();

        $payload   = $request->getPayload();
        $client_id = $payload->getInt('client_id');
        $since     = $payload->getInt('since');
        $updates   = $payload->all('updates');
        $seed      = $payload->getString('seed');
        $awareness = $payload->getString('awareness');

        if ($client_id <= 0) {
            throw new BadRequestHttpException();
        }
        foreach ([...$updates, $seed, $awareness] as $data) {
            if (!is_string($data)) {
                throw new BadRequestHttpException();
            }
            try {
                base64_decode($data, true);
            } catch (UrlException) {
                throw new BadRequestHttpException();
            }
        }
        $user_id = (int) Session::getLoginUserID();

        $seeded = false;
        if ($seed !== '' || $updates !== []) {
            $seeded = $this->writeUpdates($id, $client_id, $updates, $seed);
        }

        $now = (string) Session::getCurrentTime();
        if ($awareness !== '') {
            $this->writeAwareness($id, $client_id, $user_id, $awareness, $now);
        }

        [$remote_updates, $cursor] = $this->readUpdates($id, $client_id, $since);

        return new JsonResponse([
            'seeded'    => $seeded,
            'updates'   => $remote_updates,
            'cursor'    => $cursor,
            'awareness' => $this->readAwareness($id, $client_id, $now),
            'user'      => [
                'id'   => $user_id,
                'name' => getUserName($user_id),
            ],
        ]);
    }

    /**
     * @param string[] $updates
     * @return bool True if the seed was accepted
     */
    private function writeUpdates(int $id, int $client_id, array $updates, string $seed): bool
    {
        global $DB;

        $seeded = false;
        $DB->beginTransaction();
        try {
            // Serialize the writes of an article: the ids then commit in order,
            // so a client that moves its cursor never skips an update.
            $DB->doQuery("SELECT `id` FROM `glpi_knowbaseitems` WHERE `id` = $id FOR UPDATE");

            // Only the first client fills the document from the saved HTML,
            // else the content would be there twice.
            if ($seed !== '' && countElementsInTable(self::UPDATES_TABLE, ['knowbaseitems_id' => $id]) === 0) {
                array_unshift($updates, $seed);
                $seeded = true;
            }

            foreach ($updates as $data) {
                $DB->insert(self::UPDATES_TABLE, [
                    'knowbaseitems_id' => $id,
                    'client_id'        => $client_id,
                    'data'             => $data,
                    'date_creation'    => Session::getCurrentTime(),
                ]);
            }
            $DB->commit();
        } catch (Throwable $e) {
            $DB->rollBack();
            throw $e;
        }

        return $seeded;
    }

    /**
     * @return array{0: string[], 1: int} The updates of the other clients, and the new cursor
     */
    private function readUpdates(int $id, int $client_id, int $since): array
    {
        global $DB;

        $updates = [];
        $cursor  = $since;
        $iterator = $DB->request([
            'SELECT' => ['id', 'client_id', 'data'],
            'FROM'   => self::UPDATES_TABLE,
            'WHERE'  => [
                'knowbaseitems_id' => $id,
                'id'               => ['>', $since],
            ],
            'ORDER'  => 'id ASC',
        ]);
        foreach ($iterator as $row) {
            $cursor = $row['id'];
            if ((int) $row['client_id'] !== $client_id) {
                $updates[] = $row['data'];
            }
        }

        return [$updates, $cursor];
    }

    private function writeAwareness(int $id, int $client_id, int $user_id, string $data, string $now): void
    {
        global $DB;

        $DB->updateOrInsert(
            self::AWARENESS_TABLE,
            [
                'users_id' => $user_id,
                'data'     => $data,
                'date_mod' => $now,
            ],
            [
                'knowbaseitems_id' => $id,
                'client_id'        => $client_id,
            ]
        );
    }

    /**
     * @return string[] The awareness states of the other connected clients
     */
    private function readAwareness(int $id, int $client_id, string $now): array
    {
        global $DB;

        $limit = date('Y-m-d H:i:s', strtotime($now) - self::AWARENESS_TIMEOUT);

        // Clients that closed the page without a "leave" message.
        $DB->delete(self::AWARENESS_TABLE, ['date_mod' => ['<', $limit]]);

        $states = [];
        $iterator = $DB->request([
            'SELECT' => ['data'],
            'FROM'   => self::AWARENESS_TABLE,
            'WHERE'  => [
                'knowbaseitems_id' => $id,
                'NOT'              => ['client_id' => $client_id],
            ],
        ]);
        foreach ($iterator as $row) {
            $states[] = $row['data'];
        }

        return $states;
    }
}
