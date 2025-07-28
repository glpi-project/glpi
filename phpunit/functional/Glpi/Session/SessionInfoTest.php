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

namespace tests\units\Glpi\Session;

use Glpi\Session\SessionInfo;

final class SessionInfoTest extends \GLPITestCase
{
    public function testGetUserId(): void
    {
        $session_info = new SessionInfo(
            user_id: 500,
        );
        $this->assertEquals(500, $session_info->getUserId());
    }

    public function testGetGroupIds(): void
    {
        $session_info = new SessionInfo(
            group_ids: [1, 2, 3],
        );
        $this->assertEquals([1, 2, 3], $session_info->getGroupIds());
    }

    public function testGetProfileId(): void
    {
        $session_info = new SessionInfo(
            profile_id: 13,
        );
        $this->assertEquals(13, $session_info->getProfileId());
    }
}
