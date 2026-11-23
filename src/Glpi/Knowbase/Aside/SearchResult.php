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

namespace Glpi\Knowbase\Aside;

/**
 * A single article matched by the aside search, as one row of the results list.
 */
final class SearchResult
{
    /**
     * @param string $excerpt Plain text preview of the article's content, see
     *                        `SearchResultsBuilder::buildExcerpt()`.
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $illustration,
        public readonly string $link,
        public readonly string $excerpt,
        public readonly bool $is_current = false,
    ) {}
}
