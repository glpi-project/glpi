<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use MailCollector;
use OAuthApplication;
use OAuthAuthorization;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Integration of the OAuth applications in the mail collector form.
 */
class MailCollectorOAuthFormTest extends DbTestCase
{
    private function createApplication(): OAuthApplication
    {
        $application = $this->createItem(OAuthApplication::class, [
            'name'          => 'Gmail ' . $this->getUniqueString(),
            'is_active'     => 1,
            'provider'      => OAuthApplication::GOOGLE,
            'client_id'     => 'my-client-id',
            'client_secret' => 'my-secret',
        ], ['client_secret']);

        foreach (['support@example.com', 'helpdesk@example.com'] as $email) {
            $this->createItem(OAuthAuthorization::class, [
                'oauthapplications_id' => $application->getID(),
                'type'                 => OAuthAuthorization::TYPE_IMAP,
                'email'                => $email,
            ]);
        }
        // SMTP authorizations cannot be used to collect emails.
        $this->createItem(OAuthAuthorization::class, [
            'oauthapplications_id' => $application->getID(),
            'type'                 => OAuthAuthorization::TYPE_SMTP,
            'email'                => 'smtp-only@example.com',
        ]);

        return $application;
    }

    private function renderForm(MailCollector $collector): Crawler
    {
        ob_start();
        $collector->showForm($collector->isNewItem() ? -1 : $collector->getID());
        return new Crawler(ob_get_clean());
    }

    private function isHidden(Crawler $field): bool
    {
        return in_array('d-none', explode(' ', (string) $field->closest('.form-field')?->attr('class')), true);
    }

    public function testOAuthApplicationIsAServerType(): void
    {
        $this->login();

        $application = $this->createApplication();
        $type = '/' . OAuthApplication::PROTOCOL_PREFIX . $application->getID();

        $collector = new MailCollector();
        $collector->getEmpty();
        $crawler = $this->renderForm($collector);

        $option = $crawler->filter('select[name="server_type"] option[value="' . $type . '"]');
        $this->assertCount(1, $option);
        $this->assertSame($application->fields['name'], $option->text());

        // Login and password are displayed until an OAuth application is selected.
        $this->assertFalse($this->isHidden($crawler->filter('input[name="login"]')));
        $this->assertFalse($this->isHidden($crawler->filter('input[name="passwd"]')));
        $this->assertTrue($this->isHidden($crawler->filter('select[name="_oauth_login_' . $application->getID() . '"]')));
    }

    public function testOAuthCollectorLoginIsAnAuthorizedMailbox(): void
    {
        $this->login();

        $application = $this->createApplication();

        $collector = $this->createItem(MailCollector::class, [
            'name'      => 'OAuth collector',
            'host'      => '{imap.gmail.com:993/' . OAuthApplication::PROTOCOL_PREFIX . $application->getID() . '/ssl}INBOX',
            'login'     => 'support@example.com',
            'is_active' => 0,
        ], ['host']);

        $crawler = $this->renderForm($collector);

        // The login is picked among the IMAP authorized mailboxes, the password is useless.
        $select = $crawler->filter('select[name="_oauth_login_' . $application->getID() . '"]');
        $this->assertCount(1, $select);
        $this->assertFalse($this->isHidden($select));
        $this->assertSame(
            ['', 'helpdesk@example.com', 'support@example.com'],
            $select->filter('option')->each(static fn(Crawler $option) => $option->attr('value'))
        );
        $this->assertSame('support@example.com', $select->filter('option[selected]')->attr('value'));

        $this->assertTrue($this->isHidden($crawler->filter('input[name="login"]')));
        $this->assertSame('support@example.com', $crawler->filter('input[name="login"]')->attr('value'));
        $this->assertTrue($this->isHidden($crawler->filter('input[name="passwd"]')));

        // A new mailbox can be authorized from the form.
        $button = $crawler->filter('button[data-glpi-oauth-authorize="' . $application->getID() . '"]');
        $this->assertCount(1, $button);
    }

    public function testAuthorizeButtonRequiresTheRightToUpdateTheApplication(): void
    {
        $this->login();

        $application = $this->createApplication();

        $collector = new MailCollector();
        $collector->getEmpty();

        $_SESSION['glpiactiveprofile'][OAuthApplication::$rightname] = READ;
        $crawler = $this->renderForm($collector);
        $this->assertCount(0, $crawler->filter('button[data-glpi-oauth-authorize="' . $application->getID() . '"]'));

        $_SESSION['glpiactiveprofile'][OAuthApplication::$rightname] = READ | UPDATE;
        $crawler = $this->renderForm($collector);
        $this->assertCount(1, $crawler->filter('button[data-glpi-oauth-authorize="' . $application->getID() . '"]'));
    }
}
