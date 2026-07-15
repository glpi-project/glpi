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

namespace tests\units\Glpi\Knowbase\SidePanel;

use Glpi\Knowbase\SidePanel\SharingRenderer;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use User;

final class SharingRendererTest extends DbTestCase
{
    private function createKnowbaseItem(): KnowbaseItem
    {
        return $this->createItem(KnowbaseItem::class, [
            'users_id'    => getItemByTypeName(User::class, TU_USER, true),
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'name'        => $this->getUniqueString(),
            'answer'      => '<p>Test content</p>',
        ]);
    }

    private function createActiveToken(KnowbaseItem $kb): ShareToken
    {
        return $this->createItem(ShareToken::class, [
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);
    }

    public function testDisabledTokenIsStillExposedForReuse(): void
    {
        $this->login();
        $kb    = $this->createKnowbaseItem();
        $token = $this->createActiveToken($kb);

        $this->updateItem(ShareToken::class, $token->getID(), ['is_active' => 0]);

        $params = (new SharingRenderer())->getParams($kb);

        $this->assertFalse($params['is_published']);
        $this->assertNotNull($params['token']);
        $this->assertSame($token->getID(), (int) $params['token']['id']);
    }

    public function testActiveTokenIsPublished(): void
    {
        $this->login();
        $kb    = $this->createKnowbaseItem();
        $token = $this->createActiveToken($kb);

        $params = (new SharingRenderer())->getParams($kb);

        $this->assertTrue($params['is_published']);
        $this->assertNotNull($params['token']);
        $this->assertSame($token->getID(), (int) $params['token']['id']);
        $this->assertTrue($params['can_edit']);
    }

    public function testNoTokenIsNotPublished(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();

        $params = (new SharingRenderer())->getParams($kb);

        $this->assertFalse($params['is_published']);
        $this->assertNull($params['token']);
    }

    public function testCreateRightAloneCannotViewSharing(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $this->createActiveToken($kb);

        // CREATE alone satisfies canEdit(), but must not expose the token.
        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] = CREATE;

        $this->assertFalse((new SharingRenderer())->canView($kb));
    }

    public function testCreateRightAloneIsNotAllowedToEditSharing(): void
    {
        $this->login();
        $kb = $this->createKnowbaseItem();
        $this->createActiveToken($kb);

        $_SESSION['glpiactiveprofile'][KnowbaseItem::$rightname] = CREATE;

        $params = (new SharingRenderer())->getParams($kb);

        $this->assertFalse($params['can_edit']);
    }
}
