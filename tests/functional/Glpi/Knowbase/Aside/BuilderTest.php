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

namespace tests\unit\Glpi\Knowbase\Aside;

use Glpi\Knowbase\Aside\Builder;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItemCategory;

final class BuilderTest extends DbTestCase
{
    /**
     * Builds a complex tree and asserts its structure:
     *
     *  Root articles : _knowbaseitem01, _knowbaseitem02 (fixtures), "Root article"
     *  Animals       : "Cat article", "Dog article"
     *    └─ Birds    : "Eagle article"
     *  Plants        : "Rose article"
     */
    public function testBuildTreeReturnsCompleteHierarchy(): void
    {
        // Arrange: create articles and categories
        $this->login();

        $animals = $this->makeCategory('Animals');
        $birds   = $this->makeCategory('Birds', $animals->getID());
        $plants  = $this->makeCategory('Plants');

        $this->makeArticle('Root article');
        $this->makeArticle('Cat article', $animals->getID());
        $this->makeArticle('Dog article', $animals->getID());
        $this->makeArticle('Eagle article', $birds->getID());
        $this->makeArticle('Rose article', $plants->getID());

        // Act: build the tree
        $tree = (new Builder())->buildTree();

        // Assert: root level
        $this->assertEquals(
            ['_knowbaseitem01', '_knowbaseitem02', 'Root article'],
            array_column($tree->getArticles(), 'title'),
        );
        $this->assertEquals(
            ['Animals', 'Plants'],
            array_column($tree->getCategories(), 'title'),
        );
        $this->assertCount(2, $tree->getCategories());

        // Assert: animals category
        $animals_node = $tree->getCategories()[0];
        $this->assertEquals('Animals', $animals_node->title);
        $this->assertEquals(
            ['Cat article', 'Dog article'],
            array_column($animals_node->getArticles(), 'title'),
        );
        $this->assertCount(1, $animals_node->getCategories());

        // Assert: birds sub-category
        $birds_node = $animals_node->getCategories()[0];
        $this->assertEquals('Birds', $birds_node->title);
        $this->assertEquals(
            ['Eagle article'],
            array_column($birds_node->getArticles(), 'title'),
        );
        $this->assertEmpty($birds_node->getCategories());

        // Assert:  Plants category
        $plants_node = $tree->getCategories()[1];
        $this->assertEquals('Plants', $plants_node->title);
        $this->assertEquals(
            ['Rose article'],
            array_column($plants_node->getArticles(), 'title'),
        );
        $this->assertEmpty($plants_node->getCategories());
    }

    public function testCategoryIllustrationIsPropagatedFromDb(): void
    {
        $this->login();

        $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => 'Custom illustrated',
            'knowbaseitemcategories_id' => 0,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
            'illustration'              => 'kb-graduation',
        ]);
        $this->makeCategory('Default illustrated');

        $tree = (new Builder())->buildTree();

        $by_title = [];
        foreach ($tree->getCategories() as $node) {
            $by_title[$node->title] = $node;
        }

        $this->assertSame('kb-graduation', $by_title['Custom illustrated']->illustration);
        $this->assertSame('kb-faq', $by_title['Default illustrated']->illustration);
    }

    public function testBuildTreeIncludesCategoryId(): void
    {
        $this->login();

        $cat = $this->makeCategory('IdCheck');

        $tree = (new Builder())->buildTree();

        $found = null;
        foreach ($tree->getCategories() as $node) {
            if ($node->title === 'IdCheck') {
                $found = $node;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame($cat->getID(), $found->id);
    }

    private function makeCategory(string $name, int $parent_id = 0): KnowbaseItemCategory
    {
        return $this->createItem(KnowbaseItemCategory::class, [
            'name'                      => $name,
            'knowbaseitemcategories_id' => $parent_id,
            'entities_id'               => $this->getTestRootEntity(only_id: true),
            'is_recursive'              => 1,
        ]);
    }

    private function makeArticle(string $name, int $category_id = 0): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'name'        => $name,
            'answer'      => '<p>Content</p>',
            '_categories' => $category_id > 0 ? [$category_id] : [],
        ]);
    }
}
