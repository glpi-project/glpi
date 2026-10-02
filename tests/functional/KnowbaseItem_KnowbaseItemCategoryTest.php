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

namespace tests\units;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_KnowbaseItemCategory;
use KnowbaseItemCategory;

class KnowbaseItem_KnowbaseItemCategoryTest extends DbTestCase
{
    public function testFormRequiresRightsOnKnowbaseItem(): void
    {
        // Arrange: a FAQ article visible to everybody and a category, created by another user
        [$kbitem, $category] = $this->createVisibleArticleAndCategory(true);

        // A user that can read the knowledge base but not update it
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] = READ;
        $this->assertTrue($kbitem->getFromDB($kbitem->getID()));
        $this->assertTrue($kbitem->can($kbitem->getID(), READ));
        $this->assertFalse($kbitem->can($kbitem->getID(), UPDATE));

        // Act + Assert: the link is refused
        $this->assertInstanceOf(AccessDeniedHttpException::class, $this->submitForm($kbitem, $category));
        $this->assertSame(0, countElementsInTable(KnowbaseItem_KnowbaseItemCategory::getTable(), [
            'knowbaseitems_id' => $kbitem->getID(),
        ]));
    }

    public function testFormRequiresAccessToTheArticle(): void
    {
        // Arrange: a private article of another user, and a category
        [$kbitem, $category] = $this->createVisibleArticleAndCategory(false, false);

        // A user with the global knowledge base rights, but who cannot see this article
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] = READ | UPDATE | CREATE;
        $_SESSION['glpiactiveprofile'][KnowbaseItemCategory::$rightname] = READ;
        $this->assertTrue($kbitem->getFromDB($kbitem->getID()));
        $this->assertFalse($kbitem->can($kbitem->getID(), UPDATE));

        // Act + Assert: the link is refused although the global rights are granted
        $this->assertInstanceOf(AccessDeniedHttpException::class, $this->submitForm($kbitem, $category));
        $this->assertSame(0, countElementsInTable(KnowbaseItem_KnowbaseItemCategory::getTable(), [
            'knowbaseitems_id' => $kbitem->getID(),
        ]));
    }

    public function testFormAllowsUsersThatCanUpdateTheArticle(): void
    {
        // Arrange: an article visible to everybody, and a category
        [$kbitem, $category] = $this->createVisibleArticleAndCategory(false);

        // A user with the global knowledge base rights, who can see this article
        $this->login('normal', 'normal');
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] = READ | UPDATE | CREATE;
        $_SESSION['glpiactiveprofile'][KnowbaseItemCategory::$rightname] = READ;
        $this->assertTrue($kbitem->getFromDB($kbitem->getID()));
        $this->assertTrue($kbitem->can($kbitem->getID(), UPDATE));

        // Act + Assert: the link is created
        $this->assertNotInstanceOf(AccessDeniedHttpException::class, $this->submitForm($kbitem, $category));
        $this->assertSame(1, countElementsInTable(KnowbaseItem_KnowbaseItemCategory::getTable(), [
            'knowbaseitems_id' => $kbitem->getID(),
        ]));
    }

    /**
     * @return array{0: KnowbaseItem, 1: KnowbaseItemCategory}
     */
    private function createVisibleArticleAndCategory(bool $is_faq, bool $visible = true): array
    {
        $this->login();
        $kbitem = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Article of glpi',
            'answer'   => 'Answer',
            'users_id' => \Session::getLoginUserID(),
            'is_faq'   => $is_faq ? 1 : 0,
        ]);
        if ($visible) {
            $this->createItem(\Entity_KnowbaseItem::class, [
                'knowbaseitems_id' => $kbitem->getID(),
                'entities_id'      => 0,
                'is_recursive'     => 1,
            ]);
        }
        $category = $this->createItem(KnowbaseItemCategory::class, [
            'name'        => 'Category of glpi',
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);

        return [$kbitem, $category];
    }

    private function submitForm(KnowbaseItem $kbitem, KnowbaseItemCategory $category): ?\Throwable
    {
        $_POST = [
            'add'                       => 1,
            'knowbaseitems_id'          => $kbitem->getID(),
            'knowbaseitemcategories_id' => $category->getID(),
        ];

        $exception = null;
        try {
            include GLPI_ROOT . '/front/knowbaseitem_knowbaseitemcategory.form.php';
        } catch (\Throwable $e) {
            $exception = $e;
        } finally {
            $_POST = [];
        }

        return $exception;
    }
}
