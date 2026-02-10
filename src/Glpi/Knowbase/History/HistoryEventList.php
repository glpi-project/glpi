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

namespace Glpi\Knowbase\History;

use LogicException;

final class HistoryEventList
{
    /** @param HistoryEventInterface[] $events */
    public function __construct(
        private array $events = [],
    ) {}

    public function addEvent(HistoryEventInterface $event): void
    {
        $this->events[] = $event;
    }

    /** @return HistoryEventInterface[] */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function getLatestEvent(): HistoryEventInterface
    {
        $key = array_key_first($this->events);
        if ($key === null) {
            throw new LogicException("Item has no history events");
        }

        return $this->events[$key];
    }
}
