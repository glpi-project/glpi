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

use GLPIKey;
use Glpi\Tests\DbTestCase;
use MailCollector;
use OauthApplication;

class OauthApplicationTest extends DbTestCase
{
    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function testAdd(): void
    {
        $this->login();

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'Test Azure App',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
            'tenant_id'     => 'my-tenant',
            'comment'       => 'Test comment',
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($app->getFromDB($id));
        $this->assertSame('Test Azure App', $app->fields['name']);
        $this->assertSame(1, (int) $app->fields['is_active']);
        $this->assertSame('azure', $app->fields['provider']);
        $this->assertSame('my-client-id', $app->fields['client_id']);
        $this->assertSame('my-tenant', $app->fields['tenant_id']);
    }

    public function testUpdate(): void
    {
        $this->login();

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'Old name',
            'is_active'     => 0,
            'provider'      => 'google',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ]);

        $success = $app->update([
            'id'        => $id,
            'name'      => 'New name',
            'is_active' => 1,
        ]);

        $this->assertTrue($success);
        $this->assertTrue($app->getFromDB($id));
        $this->assertSame('New name', $app->fields['name']);
        $this->assertSame(1, (int) $app->fields['is_active']);
    }

    public function testPurge(): void
    {
        $this->login();

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'To purge',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($app->delete(['id' => $id], true));
        $this->assertFalse($app->getFromDB($id));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testAddRequiresClientId(): void
    {
        $app    = new OauthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => 'azure',
            'client_id'     => '',
            'client_secret' => 'secret',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Client ID is required']);
    }

    public function testAddRequiresClientSecret(): void
    {
        $app    = new OauthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => '',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Client secret is required']);
    }

    // -------------------------------------------------------------------------
    // Encryption
    // -------------------------------------------------------------------------

    public function testClientSecretIsEncryptedOnAdd(): void
    {
        $this->login();

        $plain_secret = 'super-secret-value';

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'Encrypted app',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => $plain_secret,
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertTrue($app->getFromDB($id));

        $this->assertNotSame($plain_secret, $app->fields['client_secret']);

        $key       = new GLPIKey();
        $decrypted = $key->decrypt($app->fields['client_secret']);
        $this->assertSame($plain_secret, $decrypted);
    }

    public function testClientSecretIsEncryptedOnUpdate(): void
    {
        $this->login();

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'App to update secret',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'first-secret',
        ]);

        $new_secret = 'new-secret-value';
        $app->update(['id' => $id, 'client_secret' => $new_secret]);

        $this->assertTrue($app->getFromDB($id));
        $this->assertNotSame($new_secret, $app->fields['client_secret']);

        $key = new GLPIKey();
        $this->assertSame($new_secret, $key->decrypt($app->fields['client_secret']));
    }

    public function testEmptyClientSecretOnUpdateIsIgnored(): void
    {
        $this->login();

        $app = new OauthApplication();
        $id  = $app->add([
            'name'          => 'App keep secret',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'original-secret',
        ]);

        $this->assertTrue($app->getFromDB($id));
        $stored_after_add = $app->fields['client_secret'];

        $app->update(['id' => $id, 'name' => 'Renamed', 'client_secret' => '']);

        $this->assertTrue($app->getFromDB($id));
        $this->assertSame($stored_after_add, $app->fields['client_secret']);
    }

    public function testClientSecretIsUndisclosed(): void
    {
        $this->assertContains('client_secret', OauthApplication::$undisclosedFields);
    }

    // -------------------------------------------------------------------------
    // getActiveApplications
    // -------------------------------------------------------------------------

    public function testGetActiveApplications(): void
    {
        $this->login();

        $app        = new OauthApplication();
        $id_active  = $app->add([
            'name'          => 'Active app',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid-active',
            'client_secret' => 'secret',
        ]);
        $app->add([
            'name'          => 'Inactive app',
            'is_active'     => 0,
            'provider'      => 'azure',
            'client_id'     => 'cid-inactive',
            'client_secret' => 'secret',
        ]);

        $active     = OauthApplication::getActiveApplications();
        $active_ids = array_map(fn($a) => $a->getID(), $active);

        $this->assertContains($id_active, $active_ids);
        foreach ($active as $a) {
            $this->assertSame(1, (int) $a->fields['is_active']);
        }
    }

    // -------------------------------------------------------------------------
    // Linked MailCollectors tab
    // -------------------------------------------------------------------------

    public function testLinkedMailCollectorsTab(): void
    {
        global $DB;

        $this->login();

        $app    = new OauthApplication();
        $app_id = $app->add([
            'name'          => 'Tab test app',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ]);
        $this->assertGreaterThan(0, $app_id);
        $this->assertTrue($app->getFromDB($app_id));

        $protocol_key = 'oauth_imap_' . $app_id;

        $host_linked   = '{mail.example.com/' . $protocol_key . '/novalidate-cert}INBOX';
        $host_unlinked = '{mail.other.com/imap/ssl}INBOX';

        $mc_linked    = new MailCollector();
        $mc_linked_id = $mc_linked->add([
            'name'  => 'Linked collector',
            'host'  => $host_linked,
            'login' => 'user@example.com',
        ]);

        $mc_other    = new MailCollector();
        $mc_other_id = $mc_other->add([
            'name'  => 'Unlinked collector',
            'host'  => $host_unlinked,
            'login' => 'user2@example.com',
        ]);

        $this->assertGreaterThan(0, $mc_linked_id);
        $this->assertGreaterThan(0, $mc_other_id);

        // Tab count should reflect 1 linked collector
        $_SESSION['glpishow_count_on_tabs'] = 1;
        $tab_names = $app->getTabNameForItem($app);

        $this->assertArrayHasKey(1, $tab_names);
        $this->assertStringContainsString('1', strip_tags($tab_names[1]));

        // DB query should match only the linked collector
        $iterator  = $DB->request([
            'FROM'  => MailCollector::getTable(),
            'WHERE' => [
                'OR' => [
                    ['host' => ['LIKE', '%/' . $protocol_key . '/%']],
                    ['host' => ['LIKE', '%/' . $protocol_key . '}%']],
                ],
            ],
        ]);
        $found_ids = array_column(iterator_to_array($iterator), 'id');

        $this->assertContains($mc_linked_id, $found_ids);
        $this->assertNotContains($mc_other_id, $found_ids);
    }

    public function testGetProviders(): void
    {
        $providers = OauthApplication::getProviders();
        $this->assertArrayHasKey('azure', $providers);
        $this->assertArrayHasKey('google', $providers);
    }
}
