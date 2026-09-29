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

use Glpi\Tests\DbTestCase;

/* Test for inc/rssfeed.class.php */

class RSSFeedTest extends DbTestCase
{
    public function testChildGroupInheritsParentGroupVisibilityInListCriteria(): void
    {
        global $DB;

        $this->login();
        $parent_group = $this->createItem("Group", ['name' => 'RSS parent group']);
        $child_group = $this->createItem("Group", [
            'name' => 'RSS child group',
            'groups_id' => $parent_group->getID(),
        ]);

        $tech_user = getItemByTypeName("User", "tech", true);
        $this->createItem("Group_User", ['users_id' => $tech_user, 'groups_id' => $child_group->getID()]);

        $rssfeed = $this->createItem("RSSFeed", [
            'name'      => 'RSS feed visible to parent group',
            'url'       => 'https://glpi-project.org/feed',
            'users_id'  => \Session::getLoginUserID(),
            'is_active' => 1,
        ]);
        $this->createItem("Group_RSSFeed", [
            'rssfeeds_id'           => $rssfeed->getID(),
            'groups_id'             => $parent_group->getID(),
            'no_entity_restriction' => 1,
        ]);

        $this->login('tech', 'tech');
        $criteria = array_merge(\RSSFeed::getVisibilityCriteria(), [
            'SELECT' => 'name',
            'FROM'   => \RSSFeed::getTable(),
        ]);
        $names = array_column(iterator_to_array($DB->request($criteria)), 'name');
        $this->assertContains('RSS feed visible to parent group', $names);
    }
}
