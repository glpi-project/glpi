<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Progress;

final class SessionProgress implements \JsonSerializable
{
    public readonly string $key;
    public readonly int $max;
    public readonly \DateTimeImmutable $startDate;
    public int $current = 0;
    public string|int|float|bool|null $data;

    public function __construct(string $key, int $max)
    {
        $this->startDate = new \DateTimeImmutable();
        $this->data = '';
        $this->key = $key;
        $this->max = $max;
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'max' => $this->max,
            'current' => $this->current,
            'startDate' => $this->startDate->format('Y-m-d H:i:s'),
            'data' => $this->data,
        ];
    }
}
