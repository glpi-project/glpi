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

use Dropdown;
use Override;

final class CurrentTranslationEvent implements HistoryEventInterface
{
    public function __construct(
        private string $language,
        private string $date,
        private int $author,
    ) {}

    #[Override]
    public function getLabel(): string
    {
        $language = Dropdown::getLanguageName($this->language);
        return \sprintf(__('%s — Current version'), $language);
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Updated by");
    }

    #[Override]
    public function getDate(): string
    {
        return $this->date;
    }

    #[Override]
    public function getAuthor(): int
    {
        return $this->author;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }
}
