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

namespace tests\units\Glpi\Migration;

use Glpi\Exception\OAuth2KeyException;
use Glpi\OAuth\Server;

class ServerTest extends \DbTestCase
{
    public function tearDown(): void
    {
        //reset correct chmod
        \Safe\chmod(GLPI_CONFIG_DIR . '/oauth.pem', 0o644);
        \Safe\chmod(GLPI_CONFIG_DIR . '/oauth.pub', 0o644);
        parent::tearDown();
    }

    public function testKeys()
    {
        //by default, keys must be present and readable.
        $this->assertTrue(Server::checkKeys());
    }

    public function testPrivateKeyNotReadable()
    {
        //by default, keys must be present and readable.
        $this->assertTrue(Server::checkKeys());

        //change ACLs on private key to make it unreadable
        \Safe\chmod(GLPI_CONFIG_DIR . '/oauth.pem', 0o000);
        $this->expectException(OAuth2KeyException::class);
        $this->expectExceptionMessage('Either private or public OAuth keys cannot be read. Please check file system permissions');
        $this->assertTrue(Server::checkKeys());
    }

    public function testPublicKeyNotReadable()
    {
        //by default, keys must be present and readable.
        $this->assertTrue(Server::checkKeys());

        //change ACLs on public key to make it unreadable
        \Safe\chmod(GLPI_CONFIG_DIR . '/oauth.pub', 0o000);
        $this->expectException(OAuth2KeyException::class);
        $this->expectExceptionMessage('Either private or public OAuth keys cannot be read. Please check file system permissions');
        $this->assertTrue(Server::checkKeys());
    }
}
