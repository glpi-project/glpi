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

use KnowbaseItem;
use KnowbaseItemCategory;

final class Builder
{
    public function buildTree(): Tree
    {
        $tree = new Tree();
        $this->populateNode($tree, 0);
        return $tree;
    }

    private function populateNode(Node $node, int $category_id): void
    {
        global $DB;

        $articles = $DB->request(
            KnowbaseItem::getListRequest(
                ['knowbaseitemcategories_id' => $category_id],
                'browse',
            )
        );
        foreach ($articles as $article_data) {
            $node->addArticle(new Article(
                id: (int) $article_data['id'],
                title: $article_data['name'] ?? '',
                illustration: $article_data['illustration'] ?? 'kb-faq',
                link: KnowbaseItem::getFormURLWithID($article_data['id']),
            ));
        }

        $categories = (new KnowbaseItemCategory())->find([
            'knowbaseitemcategories_id' => $category_id,
        ]);
        foreach ($categories as $cat_data) {
            $category = new Category(
                title: $cat_data['name'] ?? '',
                illustration: $cat_data['illustration'] ?: 'kb-faq',
            );
            $this->populateNode($category, (int) $cat_data['id']);
            $node->addCategory($category);
        }
    }
}
