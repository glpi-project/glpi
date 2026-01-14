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

namespace Glpi\Knowbase;

use Html;

final readonly class LastUpdateInfo
{
    public function __construct(
        private ?string $author_link,
        private ?string $author_name,
        private string $date,
        private bool $can_view_author,
    ) {}

    public function getAuthorLink(): ?string
    {
        return $this->author_link;
    }

    public function getAuthorName(): string
    {
        return $this->author_name ?: __("Deleted user");
    }

    public function getRawDate(): string
    {
        return $this->date;
    }

    public function getRelativeDate(): string
    {
        return Html::timestampToRelativeStr($this->date);
    }

    public function canViewAuthor(): bool
    {
        return $this->can_view_author;
    }
}
