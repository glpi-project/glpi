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

namespace Glpi\Controller\DCRoom;

use DCRoom;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateAxisLabelController extends AbstractController
{
    #[Route(
        '/DCRoom/{id}/AxisLabel',
        name: 'glpi_dcroom_axis_label_update',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $room = new DCRoom();
        if (!$room->getFromDB($id)) {
            throw new NotFoundHttpException();
        }
        if (!$room->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $payload = $request->getPayload();
        $this->validateInputHasExactKeys($payload->all(), [
            'axis',
            'position',
            'label',
        ]);
        $axis = $payload->getString('axis');
        $position = $payload->getInt('position');
        $label = $payload->getString('label');

        try {
            if (!$room->updateAxisLabel($axis, $position, $label)) {
                throw new RuntimeException('Failed to update the room axis label.');
            }
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), previous: $e);
        }

        return new JsonResponse([
            'label' => $room->getAxisLabel($axis, $position),
        ]);
    }
}
