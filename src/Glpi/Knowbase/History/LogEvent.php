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

use Override;

use function Safe\preg_match;

final class LogEvent implements HistoryEventInterface
{
    public function __construct(
        private string $label,
        private string $description,
        private string $date,
        private string $author,
        private ?string $old_value = null,
        private ?string $new_value = null,
    ) {}

    #[Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->description;
    }

    #[Override]
    public function getDate(): string
    {
        return $this->date;
    }

    #[Override]
    public function getAuthor(): int
    {
        preg_match("/.*(\d+)/", $this->author, $matches);
        return (int) $matches[1];
    }

    public function getNewValue(): ?string
    {
        return $this->new_value !== null ? strip_tags($this->new_value) : null;
    }

    public function getOldValue(): ?string
    {
        return $this->old_value !== null ? strip_tags($this->old_value) : null;
    }
}
