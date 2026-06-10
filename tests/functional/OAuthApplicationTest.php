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
use GLPIKey;
use MailCollector;
use OAuthApplication;

class OAuthApplicationTest extends DbTestCase
{
    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function testAdd(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'Test Azure App',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
            'tenant_id'     => 'my-tenant',
            'comment'       => 'Test comment',
        ], ['client_secret']); // client_secret is encrypted on save, skip field comparison

        $this->assertSame('Test Azure App', $app->fields['name']);
        $this->assertSame(1, (int) $app->fields['is_active']);
        $this->assertSame('azure', $app->fields['provider']);
        $this->assertSame('my-client-id', $app->fields['client_id']);
        $this->assertSame('my-tenant', $app->fields['tenant_id']);
    }

    public function testUpdate(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'Old name',
            'is_active'     => 0,
            'provider'      => 'google',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ], ['client_secret']);

        $app = $this->updateItem(OAuthApplication::class, $app->getID(), [
            'name'      => 'New name',
            'is_active' => 1,
        ]);

        $this->assertSame('New name', $app->fields['name']);
        $this->assertSame(1, (int) $app->fields['is_active']);
    }

    public function testPurge(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'To purge',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        $id = $app->getID();
        $this->assertTrue($app->delete(['id' => $id], true));
        $this->assertFalse($app->getFromDB($id));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testAddRequiresClientId(): void
    {
        $app    = new OAuthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => 'azure',
            'client_id'     => '',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Client ID is required']);
    }

    public function testAddRequiresClientSecret(): void
    {
        $app    = new OAuthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => '',
            'tenant_id'     => 'tenant',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Client secret is required']);
    }

    public function testAddRequiresProvider(): void
    {
        $app    = new OAuthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => '',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['A valid provider is required']);
    }

    public function testAddInvalidProvider(): void
    {
        $app    = new OAuthApplication();
        $result = $app->prepareInputForAdd([
            'name'          => 'Bad app',
            'provider'      => 'unknown_provider',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
        ]);

        $this->assertFalse($result);
        $this->hasSessionMessages(ERROR, ['Invalid provider']);
    }

    // -------------------------------------------------------------------------
    // Encryption
    // -------------------------------------------------------------------------

    public function testClientSecretIsEncryptedOnAdd(): void
    {
        $this->login();

        $plain_secret = 'super-secret-value';

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'Encrypted app',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => $plain_secret,
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        // Stored value must differ from plaintext
        $this->assertNotSame($plain_secret, $app->fields['client_secret']);

        // Decrypting must give back the original value
        $key = new GLPIKey();
        $this->assertSame($plain_secret, $key->decrypt($app->fields['client_secret']));
    }

    public function testClientSecretIsEncryptedOnUpdate(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'App to update secret',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'first-secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        $new_secret = 'new-secret-value';
        $this->updateItem(OAuthApplication::class, $app->getID(), [
            'client_secret' => $new_secret,
        ], ['client_secret']);

        $app->getFromDB($app->getID());
        $this->assertNotSame($new_secret, $app->fields['client_secret']);

        $key = new GLPIKey();
        $this->assertSame($new_secret, $key->decrypt($app->fields['client_secret']));
    }

    public function testEmptyClientSecretOnUpdateIsIgnored(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'App keep secret',
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'original-secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        $stored_after_add = $app->fields['client_secret'];

        // Updating with an empty secret must preserve the existing encrypted value
        $this->updateItem(OAuthApplication::class, $app->getID(), [
            'name'          => 'Renamed',
            'client_secret' => '',
        ], ['client_secret']);

        $app->getFromDB($app->getID());
        $this->assertSame($stored_after_add, $app->fields['client_secret']);
    }

    public function testClientSecretIsUndisclosed(): void
    {
        $this->assertContains('client_secret', OAuthApplication::$undisclosedFields);
    }

    // -------------------------------------------------------------------------
    // getActiveApplications
    // -------------------------------------------------------------------------

    public function testGetActiveApplications(): void
    {
        $this->login();

        /** @var OAuthApplication $active */
        $active = $this->createItem(OAuthApplication::class, [
            'name'          => 'Active app',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid-active',
            'client_secret' => 'secret',
            'tenant_id'     => 'tid-active'
        ], ['client_secret']);

        $this->createItem(OAuthApplication::class, [
            'name'          => 'Inactive app',
            'is_active'     => 0,
            'provider'      => 'azure',
            'client_id'     => 'cid-inactive',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant'
        ], ['client_secret']);

        $applications = OAuthApplication::getActiveApplications();
        $ids          = array_map(fn($a) => $a->getID(), $applications);

        $this->assertContains($active->getID(), $ids);
        foreach ($applications as $a) {
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

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'Tab test app',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant'
        ], ['client_secret']);

        $protocol_key  = 'oauth_imap_' . $app->getID();
        $host_linked   = '{mail.example.com/' . $protocol_key . '/novalidate-cert}INBOX';
        $host_unlinked = '{mail.other.com/imap/ssl}INBOX';

        // createItem is not usable here: MailCollector always reconstructs `host` from its
        // component fields, dropping unregistered protocols like oauth_imap_X in the process.
        $DB->insert(MailCollector::getTable(), [
            'name'  => 'Linked collector',
            'host'  => $host_linked,
            'login' => 'user@example.com',
        ]);
        $mc_linked = new MailCollector();
        $mc_linked->getFromDB($DB->insertId());

        /** @var MailCollector $mc_other */
        $mc_other = $this->createItem(MailCollector::class, [
            'name'  => 'Unlinked collector',
            'host'  => $host_unlinked,
            'login' => 'user2@example.com',
        ], ['host', 'server_type']);

        // Tab count must reflect exactly 1 linked collector
        $_SESSION['glpishow_count_on_tabs'] = 1;
        $tab_names = $app->getTabNameForItem($app);

        $this->assertArrayHasKey(1, $tab_names);
        $this->assertStringContainsString('tab-count-badge">1<', $tab_names[1]);

        // DB query must include the linked collector and exclude the unlinked one
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

        $this->assertContains($mc_linked->getID(), $found_ids);
        $this->assertNotContains($mc_other->getID(), $found_ids);
    }

    public function testGetProviders(): void
    {
        $providers = OAuthApplication::getProviders();
        $this->assertArrayHasKey('azure', $providers);
        $this->assertArrayHasKey('google', $providers);
    }

    // -------------------------------------------------------------------------
    // cleanDBonPurge
    // -------------------------------------------------------------------------

    public function testCleanDBonPurge(): void
    {
        global $DB;

        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'App to purge',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        $protocol_key = 'oauth_imap_' . $app->getID();

        $DB->insert(MailCollector::getTable(), [
            'name'  => 'Collector to clean',
            'host'  => '{mail.example.com/' . $protocol_key . '/ssl}INBOX',
            'login' => 'user@example.com',
        ]);
        $mc = new MailCollector();
        $mc->getFromDB($DB->insertId());

        // Purge the application
        $this->assertTrue($app->delete(['id' => $app->getID()], true));

        // The linked collector's host must now be cleared
        $mc->getFromDB($mc->getID());
        $this->assertSame('', $mc->fields['host']);
    }

    public function testCleanDBonPurgeDoesNotAffectUnrelatedCollectors(): void
    {
        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'App to purge 2',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        /** @var MailCollector $mc_other */
        $mc_other = $this->createItem(MailCollector::class, [
            'name'  => 'Unrelated collector',
            'host'  => '{mail.other.com/imap/ssl}INBOX',
            'login' => 'user2@example.com',
        ], ['host', 'server_type']);

        $host_before_purge = $mc_other->fields['host'];

        $this->assertTrue($app->delete(['id' => $app->getID()], true));

        // Unrelated collector must be untouched
        $mc_other->getFromDB($mc_other->getID());
        $this->assertSame($host_before_purge, $mc_other->fields['host']);
    }

    // -------------------------------------------------------------------------
    // countLinkedMailCollectors
    // -------------------------------------------------------------------------

    public function testCountLinkedMailCollectors(): void
    {
        global $DB;

        $this->login();

        /** @var OAuthApplication $app */
        $app = $this->createItem(OAuthApplication::class, [
            'name'          => 'Count test app',
            'is_active'     => 1,
            'provider'      => 'azure',
            'client_id'     => 'cid',
            'client_secret' => 'secret',
            'tenant_id'     => 'tenant',
        ], ['client_secret']);

        $protocol_key = 'oauth_imap_' . $app->getID();

        // Initially no linked collectors — no badge rendered
        $_SESSION['glpishow_count_on_tabs'] = 1;
        $tabs = $app->getTabNameForItem($app);
        $this->assertArrayHasKey(1, $tabs);
        $this->assertStringNotContainsString('tab-count-badge', $tabs[1]);

        // Add a collector with a trailing slash after the key
        $DB->insert(MailCollector::getTable(), [
            'name'  => 'Collector with slash',
            'host'  => '{mail.example.com/' . $protocol_key . '/ssl}INBOX',
            'login' => 'a@example.com',
        ]);

        // Add a collector with closing brace after the key
        $DB->insert(MailCollector::getTable(), [
            'name'  => 'Collector without options',
            'host'  => '{mail.example.com/' . $protocol_key . '}',
            'login' => 'b@example.com',
        ]);

        $tabs = $app->getTabNameForItem($app);
        $this->assertStringContainsString('tab-count-badge">2<', $tabs[1]);
    }

    // -------------------------------------------------------------------------
    // Rights
    // -------------------------------------------------------------------------

    public function testCannotCreateWithoutConfigRight(): void
    {
        $this->login();

        // canCreate() delegates to canUpdate(), so removing UPDATE must deny creation
        $saved = $_SESSION['glpiactiveprofile']['config'];
        $_SESSION['glpiactiveprofile']['config'] = $saved & ~UPDATE;

        try {
            $this->assertFalse(OAuthApplication::canCreate());
        } finally {
            $_SESSION['glpiactiveprofile']['config'] = $saved;
        }
    }

    public function testCannotPurgeWithoutConfigRight(): void
    {
        $this->login();

        // canPurge() delegates to canUpdate(), so removing UPDATE must deny purge
        $saved = $_SESSION['glpiactiveprofile']['config'];
        $_SESSION['glpiactiveprofile']['config'] = $saved & ~UPDATE;

        try {
            $this->assertFalse(OAuthApplication::canPurge());
        } finally {
            $_SESSION['glpiactiveprofile']['config'] = $saved;
        }
    }

    public function testCannotUpdateWithoutConfigRight(): void
    {
        $this->login();

        $saved = $_SESSION['glpiactiveprofile']['config'];
        $_SESSION['glpiactiveprofile']['config'] = READ;

        try {
            $this->assertFalse(OAuthApplication::canUpdate());
            $this->assertTrue(OAuthApplication::canView());
        } finally {
            $_SESSION['glpiactiveprofile']['config'] = $saved;
        }
    }

}
