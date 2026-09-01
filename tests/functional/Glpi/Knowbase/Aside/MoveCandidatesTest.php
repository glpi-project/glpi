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

use Entity_KnowbaseItem;
use Glpi\Knowbase\Aside\Article;
use Glpi\Knowbase\Aside\Builder;
use Glpi\Knowbase\Aside\MoveCandidates;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use Session;

final class MoveCandidatesTest extends DbTestCase
{
    public function testRootLevelIsNotOfferedButTheRootArticleIs(): void
    {
        $this->login();
        $article = $this->makeArticle('Lonely ' . __FUNCTION__);

        $candidates = (new MoveCandidates($article->getID()))->build();

        // Nothing sits beside the root article, so there is no root level to move to.
        $this->assertArrayNotHasKey(0, $candidates);
        $this->assertArrayHasKey(KnowbaseItem::getRootId(), $candidates);
    }

    public function testArticleItselfIsNotACandidate(): void
    {
        $this->login();
        $article = $this->makeArticle('Self ' . __FUNCTION__);

        $candidates = (new MoveCandidates($article->getID()))->build();

        $this->assertArrayNotHasKey($article->getID(), $candidates);
    }

    public function testDescendantsAreNotCandidates(): void
    {
        $this->login();
        $moved       = $this->makeArticle('Moved ' . __FUNCTION__);
        $child       = $this->makeArticle('Child ' . __FUNCTION__, $moved->getID());
        $grand_child = $this->makeArticle('Grand child ' . __FUNCTION__, $child->getID());
        $unrelated   = $this->makeArticle('Unrelated ' . __FUNCTION__);

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($child->getID(), $candidates);
        $this->assertArrayNotHasKey($grand_child->getID(), $candidates);
        $this->assertArrayHasKey($unrelated->getID(), $candidates);
    }

    public function testChildLabelCarriesItsParentPath(): void
    {
        $this->login();
        $moved  = $this->makeArticle('Moved ' . __FUNCTION__);
        $parent = $this->makeArticle('Parent ' . __FUNCTION__);
        $child  = $this->makeArticle('Child ' . __FUNCTION__, $parent->getID());

        $candidates = (new MoveCandidates($moved->getID()))->build();

        // Every path starts at the root article, which hosts articles created without a parent.
        $this->assertSame(
            $this->rootTitle() . ' > Parent ' . __FUNCTION__,
            $candidates[$parent->getID()],
        );
        $this->assertSame(
            $this->rootTitle() . ' > Parent ' . __FUNCTION__ . ' > Child ' . __FUNCTION__,
            $candidates[$child->getID()],
        );
    }

    public function testMultiParentArticleAppearsOnceWithItsShortestPath(): void
    {
        $this->login();
        $moved   = $this->makeArticle('Moved ' . __FUNCTION__);
        $deep    = $this->makeArticle('Deep ' . __FUNCTION__);
        $middle  = $this->makeArticle('Middle ' . __FUNCTION__, $deep->getID());
        $shallow = $this->makeArticle('Shallow ' . __FUNCTION__);
        $shared  = $this->makeArticle(
            'Shared ' . __FUNCTION__,
            [$shallow->getID(), $middle->getID()],
        );

        $candidates = (new MoveCandidates($moved->getID()))->build();

        // Deep created before shallow: DFS would wrongly reach it first.
        $this->assertSame(
            $this->rootTitle() . ' > Shallow ' . __FUNCTION__ . ' > Shared ' . __FUNCTION__,
            $candidates[$shared->getID()],
        );
    }

    public function testDescendantThroughAnySharedParentIsNotACandidate(): void
    {
        $this->login();
        $moved        = $this->makeArticle('Moved ' . __FUNCTION__);
        $elsewhere    = $this->makeArticle('Elsewhere ' . __FUNCTION__);
        $shared_child = $this->makeArticle(
            'Shared child ' . __FUNCTION__,
            [$moved->getID(), $elsewhere->getID()],
        );

        $candidates = (new MoveCandidates($moved->getID()))->build();

        // Excluded via the moved-parent edge, even though reachable via elsewhere too.
        $this->assertArrayNotHasKey($shared_child->getID(), $candidates);
    }

    public function testInvisibleArticleIsNotACandidate(): void
    {
        // Author both articles as another user so the "author" visibility
        // bypass never makes them directly visible to the restricted user.
        $glpi_user = getItemByTypeName('User', 'glpi', true);
        $this->login();

        $hidden = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Hidden ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
        ]);
        $moved = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Moved ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
        ]);

        $this->login('normal', 'normal');
        (new KnowbaseItem_User())->add([
            'knowbaseitems_id' => $moved->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($hidden->getID(), $candidates);
    }

    public function testAncestorThroughInvisibleIntermediateIsNotACandidate(): void
    {
        // Author every article as another user so the "author" visibility
        // bypass never makes them directly visible to the restricted user.
        $glpi_user = getItemByTypeName('User', 'glpi', true);
        $this->login();

        $moved = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Moved ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
        ]);
        $hidden_child = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Hidden child ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
            '_parents' => [$moved->getID()],
        ]);
        $visible_grandchild = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Visible grandchild ' . __FUNCTION__,
            'answer'   => '',
            'users_id' => $glpi_user,
            '_parents' => [$hidden_child->getID()],
        ]);

        $this->login('normal', 'normal');
        // Granting moved too would make hidden_child visible by inheritance.
        (new KnowbaseItem_User())->add([
            'knowbaseitems_id' => $visible_grandchild->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);

        // Visible and promoted to root, yet still a real descendant: that is the contrast.
        $roots = array_map(static fn($article) => $article->id, (new Builder())->buildTree()->getArticles());
        $this->assertContains($visible_grandchild->getID(), $roots);

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($visible_grandchild->getID(), $candidates);
    }

    public function testFaqArticleIsNotACandidateWithoutTheRightToPublishInIt(): void
    {
        $this->login();
        $moved = $this->makeArticle('Moved ' . __FUNCTION__);
        $faq   = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Faq ' . __FUNCTION__,
            'answer'   => '<p>x</p>',
            // Its author could edit it whatever the rights, so someone else owns it.
            'users_id' => getItemByTypeName('User', 'normal', true),
            'is_faq'   => 1,
        ]);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $faq->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);

        // Readable, but editing it needs PUBLISHFAQ, which the move endpoint enforces.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        // Not vacuous: the article does reach the tree, it is only refused as a parent.
        $this->assertContains($faq->getID(), $this->treeIds((new Builder())->buildTree()->getArticles()));

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($faq->getID(), $candidates);
    }

    public function testNonFaqArticleIsNotACandidateWithoutTheUpdateRight(): void
    {
        $this->login();
        $moved  = $this->makeArticle('Moved ' . __FUNCTION__);
        $parent = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Parent ' . __FUNCTION__,
            'answer'   => '<p>x</p>',
            // Its author could edit it whatever the rights, so someone else owns it.
            'users_id' => getItemByTypeName('User', 'normal', true),
        ]);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $parent->getID(),
            'users_id'         => Session::getLoginUserID(),
        ]);

        // Publishing in the FAQ is not editing a regular article, and gaining a child
        // is: the move endpoint enforces UPDATE on the new parent.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | KnowbaseItem::PUBLISHFAQ;

        // Not vacuous: the article does reach the tree, it is only refused as a parent.
        $this->assertContains(
            $parent->getID(),
            $this->treeIds((new Builder())->buildTree()->getArticles()),
        );

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($parent->getID(), $candidates);
    }

    public function testRootArticleIsNotACandidateWithoutTheUpdateRight(): void
    {
        $this->login();
        $moved = $this->makeArticle('Moved ' . __FUNCTION__);

        // The root article has neither an author rule nor a visibility rule of its own,
        // see `KnowbaseItem::canUpdateItem()`: only the global right stands in the way.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | KnowbaseItem::PUBLISHFAQ;

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey(KnowbaseItem::getRootId(), $candidates);
    }

    public function testIncoherentEntityArticleIsNotACandidateEvenIfVisible(): void
    {
        $entity_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $entity_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $this->login();
        $moved = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Moved ' . __FUNCTION__,
            'answer'       => '<p>x</p>',
            'entities_id'  => $entity_1,
            'is_recursive' => 0,
        ]);
        $foreign = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Foreign ' . __FUNCTION__,
            'answer'       => '<p>x</p>',
            'entities_id'  => $entity_2,
            'is_recursive' => 0,
        ]);
        // Share, independently of $foreign's own entities_id/is_recursive columns.
        $this->createItem(Entity_KnowbaseItem::class, [
            'knowbaseitems_id' => $foreign->getID(),
            'entities_id'      => $entity_1,
            'is_recursive'     => 0,
        ]);

        $this->login('normal', 'normal');
        $this->setEntity($entity_1, false);
        // Keeps the entity as the only possible reason for the refusal.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        // Not vacuous: the share really does make $foreign reach the tree.
        $this->assertContains($foreign->getID(), $this->treeIds((new Builder())->buildTree()->getArticles()));

        $candidates = (new MoveCandidates($moved->getID()))->build();

        $this->assertArrayNotHasKey($foreign->getID(), $candidates);
        // The root article is in entity 0 and recursive, so it stays coherent: the list
        // is not empty for another reason.
        $this->assertArrayHasKey(KnowbaseItem::getRootId(), $candidates);
    }

    /** Title of the root article, which prefixes every candidate path. */
    private function rootTitle(): string
    {
        $root = new KnowbaseItem();
        $this->assertTrue($root->getFromDB(KnowbaseItem::getRootId()));

        return (string) $root->fields['name'];
    }

    /**
     * @param Article[] $articles
     * @return int[]
     */
    private function treeIds(array $articles): array
    {
        $ids = [];
        foreach ($articles as $article) {
            $ids[] = $article->id;
            $ids   = array_merge($ids, $this->treeIds($article->getChildren()));
        }

        return $ids;
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
