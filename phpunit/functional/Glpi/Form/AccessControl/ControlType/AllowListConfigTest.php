<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use Glpi\Form\AccessControl\ControlType\AllowListConfig;

final class AllowListConfigTest extends \GlpiTestCase
{
    public function testJsonDeserialize(): void
    {
        $config = AllowListConfig::jsonDeserialize([
            'user_ids'    => [1, 2, 3],
            'group_ids'   => [4, 5, 6],
            'profile_ids' => [7, 8, 9],
        ]);
        $this->assertEquals([1, 2, 3], $config->getUserIds());
        $this->assertEquals([4, 5, 6], $config->getGroupIds());
        $this->assertEquals([7, 8, 9], $config->getProfileIds());
    }

    public function testGetUserIds(): void
    {
        $allow_list_config = new AllowListConfig(
            user_ids: [1, 2, 3],
        );
        $this->assertEquals([1, 2, 3], $allow_list_config->getUserIds());
    }

    public function testGetGroupIds(): void
    {
        $allow_list_config = new AllowListConfig(
            group_ids: [4, 5, 6],
        );
        $this->assertEquals([4, 5, 6], $allow_list_config->getGroupIds());
    }

    public function testGetProfileIds(): void
    {
        $allow_list_config = new AllowListConfig(
            profile_ids: [7, 8, 9],
        );
        $this->assertEquals([7, 8, 9], $allow_list_config->getProfileIds());
    }
}
