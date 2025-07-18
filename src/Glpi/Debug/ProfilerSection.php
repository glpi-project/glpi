<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Debug;

use Ramsey\Uuid\Uuid;

final class ProfilerSection
{
    private string $id;

    private ?string $parent_id;

    private string $category;

    private string $name;

    private int $start;

    private ?int $end = null;

    /**
     * @var array{start: int, end?: int}[] Array of start and end times of paises which will be removed from the final duration.
     */
    private array $pauses = [];

    public function __construct(string $category, string $name, $start, ?string $parent_id = null, ?string $id = null)
    {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->parent_id = $parent_id;
        $this->category = $category;
        $this->name = $name;
        $this->start = (int) $start;
    }

    public function end($time): void
    {
        // Force resume to complete the last pause.
        $this->resume();
        $this->end = (int) $time;
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getParentID(): ?string
    {
        return $this->parent_id;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDuration(): int
    {
        $end = $this->end ?? (int) (microtime(true) * 1000);
        $duration = $end - $this->start;

        // Remove paused time from the total runtime.
        foreach ($this->pauses as $pause) {
            $pause_end = $pause['end'] ?? $end;
            $duration -= $pause_end - $pause['start'];
        }

        return (int) $duration;
    }

    public function isFinished(): bool
    {
        return $this->end !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'category' => $this->category,
            'name' => $this->name,
            'start' => $this->start,
            'end' => $this->end,
            'duration' => $this->getDuration(),
        ];
    }

    public static function fromArray(array $array): self
    {
        $section = new self($array['category'], $array['name'], $array['start'], $array['parent_id'], $array['id']);
        $section->end($array['end']);
        return $section;
    }

    public function pause(): void
    {
        if (!$this->isPaused()) {
            $this->pauses[] = ['start' => microtime(true) * 1000];
        }
    }

    public function resume(): void
    {
        if (!$this->isPaused()) {
            // Not paused. Ignore.
            return;
        }
        $last_pause = array_key_last($this->pauses);
        $this->pauses[$last_pause]['end'] = microtime(true) * 1000;
    }

    public function isPaused(): bool
    {
        if (!count($this->pauses)) {
            return false;
        }
        $last_pause = end($this->pauses);
        return count($last_pause) === 1;
    }
}
