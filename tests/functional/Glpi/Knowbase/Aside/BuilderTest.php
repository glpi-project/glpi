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

use Glpi\Knowbase\Aside\Article;
use Glpi\Knowbase\Aside\Builder;
use Glpi\Knowbase\Aside\Tree;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use Session;

final class BuilderTest extends DbTestCase
{
    /**
     * Builds a multi-level tree via `_parents` and asserts its recursive
     * structure:
     *
     *  Home (root)   : _knowbaseitem01, _knowbaseitem02 (fixtures), "Top level
     *                  article", "Animals", "Plants"
     *  Animals       : "Cat article", "Dog article"
     *    └─ Birds    : "Eagle article"
     *  Plants        : "Rose article"
     */
    public function testBuildTreeReturnsCompleteHierarchy(): void
    {
        $this->login();

        $animals = $this->makeArticle('Animals');
        $birds   = $this->makeArticle('Birds', $animals->getID());
        $plants  = $this->makeArticle('Plants');

        $this->makeArticle('Top level article');
        $this->makeArticle('Cat article', $animals->getID());
        $this->makeArticle('Dog article', $animals->getID());
        $this->makeArticle('Eagle article', $birds->getID());
        $this->makeArticle('Rose article', $plants->getID());

        $tree = (new Builder())->buildTree();

        // The tree has a single root, the installation's root article, and every
        // other article hangs under it.
        $this->assertEquals(['Home'], array_column($tree->getArticles(), 'title'));

        $by_title = $this->getTopLevelArticles($tree);
        $this->assertEqualsCanonicalizing(
            ['_knowbaseitem01', '_knowbaseitem02', 'Animals', 'Plants', 'Top level article'],
            array_keys($by_title),
        );

        // Animals has three direct children: Birds (a nested article), Cat, Dog.
        $animals_node = $by_title['Animals'];
        $this->assertTrue($animals_node->hasChildren());
        $this->assertEqualsCanonicalizing(
            ['Cat article', 'Dog article', 'Birds'],
            array_column($animals_node->getChildren(), 'title'),
        );

        // Birds has one child: Eagle.
        $animals_children = array_column($animals_node->getChildren(), null, 'title');
        $birds_node = $animals_children['Birds'];
        $this->assertTrue($birds_node->hasChildren());
        $this->assertEquals(['Eagle article'], array_column($birds_node->getChildren(), 'title'));

        // Plants has one child: Rose.
        $plants_node = $by_title['Plants'];
        $this->assertTrue($plants_node->hasChildren());
        $this->assertEquals(['Rose article'], array_column($plants_node->getChildren(), 'title'));

        // Leaves have no children.
        $this->assertFalse($by_title['Top level article']->hasChildren());
        $cat_node = array_column($animals_node->getChildren(), null, 'title')['Cat article'];
        $this->assertFalse($cat_node->hasChildren());
    }

    public function testWithoutCurrentId(): void
    {
        $this->login();

        $this->makeArticle('Article A');
        $this->makeArticle('Article B');

        $tree = (new Builder())->buildTree();

        foreach ($tree->getArticles() as $article) {
            $this->assertFalse($article->is_current);
        }
    }

    public function testCurrentIdMarksMatchingTopLevelArticle(): void
    {
        $this->login();

        $this->makeArticle('Other article');
        $target = $this->makeArticle('Target article');

        $tree = (new Builder($target->getID()))->buildTree();

        $by_title = $this->getTopLevelArticles($tree);
        $this->assertFalse($by_title['Other article']->is_current);
        $this->assertTrue($by_title['Target article']->is_current);
    }

    public function testCurrentIdMarksMatchingNestedArticle(): void
    {
        $this->login();

        $parent = $this->makeArticle('Animals');
        $this->makeArticle('Top level article');
        $nested = $this->makeArticle('Nested article', $parent->getID());

        $tree = (new Builder($nested->getID()))->buildTree();

        $by_title = $this->getTopLevelArticles($tree);
        $this->assertFalse($by_title['Top level article']->is_current);

        $animals_node = $by_title['Animals'];
        $nested_by_title = array_column($animals_node->getChildren(), null, 'title');
        $this->assertTrue($nested_by_title['Nested article']->is_current);
    }

    public function testArticleIllustrationIsPropagatedFromDb(): void
    {
        $this->login();

        $this->createItem(KnowbaseItem::class, [
            'name'         => 'With illustration',
            'answer'       => '<p>Content</p>',
            'illustration' => 'antivirus',
        ]);
        $this->makeArticle('Without illustration');

        $tree = (new Builder())->buildTree();

        $by_title = $this->getTopLevelArticles($tree);
        $this->assertSame('antivirus', $by_title['With illustration']->illustration);
        $this->assertSame('', $by_title['Without illustration']->illustration);
    }

    /**
     * An article whose parents are all invisible to the current user must
     * still be reachable, promoted to the root level (see Builder's
     * promote-to-root algorithm) — not silently dropped from the tree.
     */
    public function testArticleWithoutVisibleParentIsPromotedToRoot(): void
    {
        // Author both articles as another user so the "author" visibility
        // bypass never makes them directly visible to the restricted user below.
        $glpi_user = getItemByTypeName('User', 'glpi', true);
        $this->login();

        $parent = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Invisible parent ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
        ]);
        $child = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Promoted child ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
            '_parents' => [$parent->getID()],
        ]);

        // Restricted, non-admin user: no visibility on the parent, but
        // directly granted visibility on the child.
        $this->login('normal', 'normal');
        (new KnowbaseItem_User())->add([
            'knowbaseitems_id' => $child->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);

        $parent_obj = new KnowbaseItem();
        $this->assertTrue($parent_obj->getFromDB($parent->getID()));
        $this->assertFalse($parent_obj->canViewItem(), 'parent must NOT be viewable for this test');

        $tree = (new Builder())->buildTree();

        $titles = array_column($tree->getArticles(), 'title');
        $this->assertContains('Promoted child ' . __FUNCTION__, $titles, 'child promoted to root');
        $this->assertNotContains('Invisible parent ' . __FUNCTION__, $titles, 'invisible parent absent');
    }

    /**
     * An article with two visible parents must appear under each of them.
     */
    public function testArticleWithMultipleVisibleParentsAppearsUnderEach(): void
    {
        $this->login();

        $parent1 = $this->makeArticle('Parent one ' . __FUNCTION__);
        $parent2 = $this->makeArticle('Parent two ' . __FUNCTION__);
        $this->makeArticle('Shared child ' . __FUNCTION__, [$parent1->getID(), $parent2->getID()]);

        $tree = (new Builder())->buildTree();
        $by_title = $this->getTopLevelArticles($tree);

        $this->assertEquals(
            ['Shared child ' . __FUNCTION__],
            array_column($by_title['Parent one ' . __FUNCTION__]->getChildren(), 'title'),
        );
        $this->assertEquals(
            ['Shared child ' . __FUNCTION__],
            array_column($by_title['Parent two ' . __FUNCTION__]->getChildren(), 'title'),
        );
    }

    /**
     * The articles of the top level, i.e. the children of the root article every
     * article hangs under, keyed by title.
     *
     * @return array<string, Article>
     */
    private function getTopLevelArticles(Tree $tree): array
    {
        $root_id = KnowbaseItem::getRootId();
        foreach ($tree->getArticles() as $article) {
            if ($article->id === $root_id) {
                return array_column($article->getChildren(), null, 'title');
            }
        }

        $this->fail('The root article is missing from the tree');
    }

    /**
     * @param int|int[] $parent_id
     */
    private function makeArticle(string $name, int|array $parent_id = 0): KnowbaseItem
    {
        $parents = is_array($parent_id) ? $parent_id : ($parent_id > 0 ? [$parent_id] : []);
        return $this->createItem(KnowbaseItem::class, [
            'name'     => $name,
            'answer'   => '<p>Content</p>',
            '_parents' => $parents,
        ]);
    }
}
