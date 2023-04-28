<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

    private int $end;

    public function __construct(string $category, string $name, $start, ?string $parent_id = null, ?string $id = null)
    {
        $this->id = $id ?? Uuid::uuid4()->toString();
        $this->parent_id = $parent_id;
        $this->category = $category;
        $this->name = $name;
        $this->start = (int)$start;
    }

    public function end($time): void
    {
        $this->end = (int)$time;
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
        $end = $this->end ?? (int)(microtime(true) * 1000);
        return $end - $this->start;
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
        ];
    }

    public static function fromArray(array $array): self
    {
        $section = new self($array['category'], $array['name'], $array['start'], $array['parent_id'], $array['id']);
        $section->end($array['end']);
        return $section;
    }
}
