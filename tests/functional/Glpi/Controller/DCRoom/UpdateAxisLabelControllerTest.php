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

namespace tests\units\Glpi\Controller\DCRoom;

use DCRoom;
use Glpi\Controller\DCRoom\UpdateAxisLabelController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use function Safe\json_decode;

final class UpdateAxisLabelControllerTest extends DbTestCase
{
    public function testUpdateAxisLabel(): void
    {
        $this->login();
        $room = $this->createRoom();

        $response = $this->callController($room->getID(), [
            'axis' => DCRoom::AXIS_COLUMN,
            'position' => 2,
            'label' => 'UPS side',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['label' => 'UPS side'], json_decode($response->getContent(), true));
        $room->getFromDB($room->getID());
        $this->assertSame([2 => 'UPS side'], $room->getAxisLabels(DCRoom::AXIS_COLUMN));
    }

    public function testRejectsPositionOutsideRoom(): void
    {
        $this->login();
        $room = $this->createRoom();

        $this->expectException(BadRequestHttpException::class);
        $this->callController($room->getID(), [
            'axis' => DCRoom::AXIS_ROW,
            'position' => 3,
            'label' => 'Outside',
        ]);
    }

    public function testRequiresUpdateRight(): void
    {
        $this->login();
        $room = $this->createRoom();
        $this->login('normal', 'normal');

        $this->expectException(AccessDeniedHttpException::class);
        $this->callController($room->getID(), [
            'axis' => DCRoom::AXIS_ROW,
            'position' => 1,
            'label' => 'Restricted',
        ]);
    }

    private function createRoom(): DCRoom
    {
        return $this->createItem(DCRoom::class, [
            'name' => 'Room for axis label controller',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'vis_cols' => 2,
            'vis_rows' => 2,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function callController(int $room_id, array $parameters): JsonResponse
    {
        $request = new Request(request: $parameters);
        $request->setMethod(Request::METHOD_POST);

        return (new UpdateAxisLabelController())($room_id, $request);
    }
}
