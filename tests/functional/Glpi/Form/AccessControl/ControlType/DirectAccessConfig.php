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

final class DirectAccessConfig extends \GLPITestCase
{
    public function testCreateFromRawArray(): void
    {
        $config = \Glpi\Form\AccessControl\ControlType\DirectAccessConfig::createFromRawArray(
            ['token' => 'token', 'allow_unauthenticated' => true],
        );
        $this->string($config->getToken())->isEqualTo('token');
        $this->boolean($config->allowUnauthenticated())->isEqualTo(true);
    }

    public function testGetToken(): void
    {
        $direct_access_config = new \Glpi\Form\AccessControl\ControlType\DirectAccessConfig(
            token: 'token',
        );
        $this->string($direct_access_config->getToken())->isEqualTo('token');
    }

    public function testAllowUnauthenticated(): void
    {
        $direct_access_config = new \Glpi\Form\AccessControl\ControlType\DirectAccessConfig(
            allow_unauthenticated: true,
        );
        $this->boolean($direct_access_config->allowUnauthenticated())->isEqualTo(true);
    }

    public function testEmptyTokenInitialization(): void
    {
        $direct_access_config = new \Glpi\Form\AccessControl\ControlType\DirectAccessConfig();
        $this->string($direct_access_config->getToken())->isNotEmpty();
    }
}
