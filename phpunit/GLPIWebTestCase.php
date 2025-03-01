<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GLPIWebTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Session::destroy();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Session::destroy();
    }

    protected static function login(string $user_name = \TU_USER, string $user_pass = \TU_PASS, bool $noauto = true): void
    {
        Session::start();

        $auth = new Auth();
        self::assertTrue($auth->login($user_name, $user_pass, $noauto));
    }

    protected static function createAuthenticatedClient(
        string $user_name = \TU_USER,
        string $user_pass = \TU_PASS,
        bool $noauto = true,
        array $options = [],
        array $server = [],
    ): KernelBrowser {
        $client = static::createClient($options, $server);

        self::login($user_name, $user_pass, $noauto);

        return $client;
    }
}
