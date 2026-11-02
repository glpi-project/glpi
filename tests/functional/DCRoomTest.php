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

namespace tests\units;

use DCRoom;
use Glpi\Tests\DbTestCase;
use InvalidArgumentException;

final class DCRoomTest extends DbTestCase
{
    public function testAxisLabels(): void
    {
        $this->login();
        $room = $this->createItem(DCRoom::class, [
            'name' => 'Room with custom axis labels',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'vis_cols' => 3,
            'vis_rows' => 2,
        ]);

        $this->assertSame('B', $room->getAxisLabel(DCRoom::AXIS_COLUMN, 2));
        $this->assertSame('1', $room->getAxisLabel(DCRoom::AXIS_ROW, 1));

        $this->assertTrue($room->updateAxisLabel(DCRoom::AXIS_COLUMN, 2, ' Cold aisle '));
        $this->assertTrue($room->updateAxisLabel(DCRoom::AXIS_ROW, 1, 'Вход'));

        $room->getFromDB($room->getID());
        $this->assertSame([2 => 'Cold aisle'], $room->getAxisLabels(DCRoom::AXIS_COLUMN));
        $this->assertSame([1 => 'Вход'], $room->getAxisLabels(DCRoom::AXIS_ROW));
        $this->assertSame(
            'col: Cold aisle, row: Вход',
            $room->getAllPositions()['2,1']
        );

        $this->assertTrue($room->update([
            'id' => $room->getID(),
            'vis_cols' => 1,
        ]));
        $room->getFromDB($room->getID());
        $this->assertSame([2 => 'Cold aisle'], $room->getAxisLabels(DCRoom::AXIS_COLUMN));

        $this->assertTrue($room->update([
            'id' => $room->getID(),
            'vis_cols' => 3,
        ]));
        $this->assertTrue($room->updateAxisLabel(DCRoom::AXIS_COLUMN, 2, ''));
        $this->assertSame('B', $room->getAxisLabel(DCRoom::AXIS_COLUMN, 2));
        $this->assertSame([], $room->getAxisLabels(DCRoom::AXIS_COLUMN));
    }

    public function testAxisLabelValidation(): void
    {
        $this->login();
        $room = $this->createItem(DCRoom::class, [
            'name' => 'Room for axis label validation',
            'entities_id' => $this->getTestRootEntity()->getID(),
            'vis_cols' => 2,
            'vis_rows' => 2,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $room->updateAxisLabel(
            DCRoom::AXIS_COLUMN,
            1,
            str_repeat('Ж', DCRoom::AXIS_LABEL_MAX_LENGTH + 1)
        );
    }
}
