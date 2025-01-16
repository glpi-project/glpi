<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
    public readonly \DateTimeImmutable $started_at;
    private ?\DateTimeImmutable $finished_at = null;
    private \DateTimeImmutable $updated_at;
    private bool $failed = false;
    private int $current = 0;
    private int $max;
    private string $data = '';

    public function __construct(string $key, int $max)
    {
        $this->started_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
        $this->key = $key;
        $this->max = $max;
    }

    public function finish(): void
    {
        $this->finished_at = new \DateTimeImmutable();
        $this->update();
    }

    public function fail(): void
    {
        $this->finish();
        $this->failed = true;
    }

    public function increment(int $increment = 1): void
    {
        $this->update();
        $this->current += $increment;
    }

    public function setCurrent(int $current): void
    {
        if ($this->current !== $current) {
            $this->update();
        }

        $this->current = $current;
    }

    public function setMax(int $max): void
    {
        if ($this->max !== $max) {
            $this->update();
        }

        $this->max = $max;
    }

    public function setData(string $data): void
    {
        if ($this->data !== $data) {
            $this->update();
        }

        $this->data = $data;
    }

    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'max' => $this->max,
            'current' => $this->current,
            'started_at' => $this->started_at->format('c'),
            'finished_at' => $this->finished_at?->format('c'),
            'updated_at' => $this->updated_at->format('c'),
            'data' => $this->data,
            'failed' => $this->failed,
        ];
    }

    private function update(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }
}
