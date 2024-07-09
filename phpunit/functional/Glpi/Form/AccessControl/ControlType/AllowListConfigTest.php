<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

final class AllowListConfigTest extends \GLPITestCase
{
    public function testCreateFromRawArray(): void
    {
        $config = AllowListConfig::createFromRawArray([
            'user_ids'    => [1, 2, 3],
            'group_ids'   => [4, 5, 6],
            'profile_ids' => [7, 8, 9],
        ]);
        $this->assertEquals($config->getUserIds(), [1, 2, 3]);
        $this->assertEquals($config->getGroupIds(), [4, 5, 6]);
        $this->assertEquals($config->getProfileIds(), [7, 8, 9]);
    }

    public function testGetUserIds(): void
    {
        $allow_list_config = new AllowListConfig(
            user_ids: [1, 2, 3],
        );
        $this->assertEquals($allow_list_config->getUserIds(), [1, 2, 3]);
    }

    public function testGetGroupIds(): void
    {
        $allow_list_config = new AllowListConfig(
            group_ids: [4, 5, 6],
        );
        $this->assertEquals($allow_list_config->getGroupIds(), [4, 5, 6]);
    }

    public function testGetProfileIds(): void
    {
        $allow_list_config = new AllowListConfig(
            profile_ids: [7, 8, 9],
        );
        $this->assertEquals($allow_list_config->getProfileIds(), [7, 8, 9]);
    }
}
