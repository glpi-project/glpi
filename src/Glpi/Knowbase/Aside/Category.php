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

final class Category implements Node
{
    /** @var Article[] */
    protected array $articles = [];

    /** @var Category[] */
    protected array $categories = [];

    public function __construct(
        public readonly string $title,
        public readonly string $illustration,
        public readonly int $id,
    ) {}

    public function addArticle(Article $article): void
    {
        $this->articles[] = $article;
    }

    public function addCategory(Category $category): void
    {
        $this->categories[] = $category;
    }

    /** @return Article[] */
    public function getArticles(): array
    {
        return $this->articles;
    }

    /** @return Category[] */
    public function getCategories(): array
    {
        return $this->categories;
    }
}
