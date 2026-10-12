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

namespace tests\units\Glpi\Migration;

use Glpi\Exception\OAuth2KeyException;
use Glpi\OAuth\Server;
use Glpi\Tests\DbTestCase;

use function Safe\chmod;
use function Safe\copy;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

class ServerTest extends DbTestCase
{
    /**
     * Directory holding a disposable copy of the OAuth keys, if any.
     */
    private ?string $keys_directory = null;

    public function tearDown(): void
    {
        if ($this->keys_directory !== null) {
            foreach (['oauth.pem', 'oauth.pub'] as $key_filename) {
                if (!file_exists($this->keys_directory . '/' . $key_filename)) {
                    continue; //the copy may have failed
                }
                //reset correct chmod to be able to delete the file
                chmod($this->keys_directory . '/' . $key_filename, 0o600);
                unlink($this->keys_directory . '/' . $key_filename);
            }
            rmdir($this->keys_directory);
            $this->keys_directory = null;
        }

        parent::tearDown();
    }

    public function testKeys()
    {
        //by default, keys must be present and readable.
        $this->assertTrue(Server::checkKeys());
    }

    public function testPrivateKeyNotReadable()
    {
        //operate on a copy of the keys, as making the real ones unreadable would impact any other test running concurrently.
        $config_dir = $this->getKeysCopyDirectory();

        //keys must be present and readable.
        $this->assertTrue(Server::checkKeys($config_dir));

        //change ACLs on private key to make it unreadable
        chmod($config_dir . '/oauth.pem', 0o000);
        $this->expectException(OAuth2KeyException::class);
        $this->expectExceptionMessage('Either private or public OAuth keys cannot be read. Please check file system permissions');
        $this->assertTrue(Server::checkKeys($config_dir));
    }

    public function testPublicKeyNotReadable()
    {
        //operate on a copy of the keys, as making the real ones unreadable would impact any other test running concurrently.
        $config_dir = $this->getKeysCopyDirectory();

        //keys must be present and readable.
        $this->assertTrue(Server::checkKeys($config_dir));

        //change ACLs on public key to make it unreadable
        chmod($config_dir . '/oauth.pub', 0o000);
        $this->expectException(OAuth2KeyException::class);
        $this->expectExceptionMessage('Either private or public OAuth keys cannot be read. Please check file system permissions');
        $this->assertTrue(Server::checkKeys($config_dir));
    }

    /**
     * Copy the OAuth keys into a dedicated directory, removed during the teardown.
     *
     * @return string Path of the directory containing the copied keys.
     */
    private function getKeysCopyDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/glpi_test_oauth_keys_' . uniqid();
        mkdir($directory);
        $this->keys_directory = $directory;

        foreach (['oauth.pem', 'oauth.pub'] as $key_filename) {
            copy(
                GLPI_CONFIG_DIR . '/' . $key_filename,
                $directory . '/' . $key_filename
            );
        }

        return $directory;
    }
}
