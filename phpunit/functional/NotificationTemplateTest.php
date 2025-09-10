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

namespace tests\units;

use DbTestCase;
use NotificationTarget;
use NotificationTargetTicket;
use NotificationTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use Ticket;
use TicketTask;
use User;

/* Test for inc/notificationtemplate.class.php */

class NotificationTemplateTest extends DbTestCase
{
    public function testClone()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'notificationtemplates_id',
            'FROM'   => \NotificationTemplateTranslation::getTable(),
            'LIMIT'  => 1,
        ]);

        $data = $iterator->current();
        $template = new NotificationTemplate();
        $template->getFromDB($data['notificationtemplates_id']);
        $added = $template->clone();
        $this->assertGreaterThan(0, (int) $added);

        $clonedTemplate = new NotificationTemplate();
        $this->assertTrue($clonedTemplate->getFromDB($added));

        unset($template->fields['id']);
        unset($template->fields['name']);
        unset($template->fields['date_creation']);
        unset($template->fields['date_mod']);

        unset($clonedTemplate->fields['id']);
        unset($clonedTemplate->fields['name']);
        unset($clonedTemplate->fields['date_creation']);
        unset($clonedTemplate->fields['date_mod']);

        $this->assertSame($clonedTemplate->fields, $template->fields);
    }

    public static function linksProvider(): iterable
    {
        global $CFG_GLPI;

        $base_url = $CFG_GLPI['url_base'];

        yield [
            'content' => <<<HTML
Relative link from GLPI: <a href="/">GLPI index</a>
HTML,
            'expected' => <<<HTML
Relative link from GLPI: <a href="{$base_url}/">GLPI index</a>
HTML,
        ];

        yield [
            'content' => <<<HTML
Relative link from GLPI: <a href="/front/computer.php?id=2" title="Computer 2">Computer</a>
HTML,
            'expected' => <<<HTML
Relative link from GLPI: <a href="{$base_url}/front/computer.php?id=2" title="Computer 2">Computer</a>
HTML,
        ];

        yield [
            'content' => <<<HTML
Absolute link from GLPI: <a href="{$base_url}/front/computer.php?id=2" title="Computer 2">Computer</a>
HTML,
            'expected' => <<<HTML
Absolute link from GLPI: <a href="{$base_url}/front/computer.php?id=2" title="Computer 2">Computer</a>
HTML,
        ];

        yield [
            'content' => <<<HTML
External link from GLPI: <a href="https://faq.teclib.com/01_getting_started/getting_started/" title="Faq">Faq</a>
HTML,
            'expected' => <<<HTML
External link from GLPI: <a href="https://faq.teclib.com/01_getting_started/getting_started/" title="Faq">Faq</a>
HTML,
        ];

        yield [
            'content' => <<<HTML
External link without protocol from GLPI: <a href="//faq.teclib.com/01_getting_started/getting_started/" title="Faq">Faq</a>
HTML,
            'expected' => <<<HTML
External link without protocol from GLPI: <a href="//faq.teclib.com/01_getting_started/getting_started/" title="Faq">Faq</a>
HTML,
        ];
    }

    #[DataProvider('linksProvider')]
    public function testConvertRelativeGlpiLinksToAbsolute(
        string $content,
        string $expected
    ): void {
        $instance = new NotificationTemplate();
        $result = $this->callPrivateMethod($instance, 'convertRelativeGlpiLinksToAbsolute', $content);
        $this->assertEquals($expected, $result);
    }

    public function testTemplateDataIsAdaptedToTimezone(): void
    {
        $this->login();
        $this->setCurrentTime('2025-07-22 10:00:00');

        $entity_id = $this->getTestRootEntity(true);
        $user_id = getItemByTypeName(User::class, TU_USER, true);

        $ticket = $this->createItem(
            Ticket::class,
            [
                'name'             => __FUNCTION__,
                'content'          => __FUNCTION__,
                'entities_id'      => $entity_id,
                '_users_id_assign' => $user_id,
            ]
        );

        $task = $this->createItem(
            TicketTask::class,
            [
                'state'         => \Planning::TODO,
                'tickets_id'    => $ticket->getID(),
                'actiontime'    => HOUR_TIMESTAMP,
                'content'       => __FUNCTION__,
                'users_id_tech' => $user_id,
                'date'          => '2025-07-23 09:00:00',
            ]
        );

        $user_infos = [
            'language' => 'en_GB',
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
            ],
            'username' => TU_USER,
            'email' => 'mail@example.com',
        ];

        $target = new NotificationTargetTicket($entity_id, 'new', $ticket, []);

        $template = getItemByTypeName(NotificationTemplate::class, 'Tickets');

        // Check generated dates without timezone
        $tid = $template->getTemplateByLanguage($target, $user_infos, 'new', ['item' => $ticket]);
        $notification_data = $template->getDataToSend($target, $tid, 'mail@example.com', $user_infos, []);

        $this->assertStringContainsString('Opening date : 2025-07-22 10:00', $notification_data['content_text']);
        $this->assertStringContainsString('[2025-07-23 09:00]', $notification_data['content_text']);

        // Check generated dates with a specific timezone
        $user_infos['additionnaloption']['timezone'] = 'Pacific/Fiji'; // +12 hours
        $tid = $template->getTemplateByLanguage($target, $user_infos, 'new', ['item' => $ticket]);
        $notification_data = $template->getDataToSend($target, $tid, 'mail@example.com', $user_infos, []);

        $this->assertStringContainsString('Opening date : 2025-07-22 22:00', $notification_data['content_text']);
        $this->assertStringContainsString('[2025-07-23 21:00]', $notification_data['content_text']);
    }

    /**
     * Test that each user gets a unique template even when they are of the same type
     * This test validates the fix for the bug where users of the same type were sharing
     * the same cached template, causing plugins to generate identical data (like tokens)
     * for all users of the same type.
     */
    public function testUniqueTemplatePerUser(): void
    {
        $this->login();

        // Create a real notification target for testing
        $user = new User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        $template = new NotificationTemplate();
        $template->getFromDB(1); // Use an existing template

        // Test with two different users of the same type (both GLPI users)
        $user1_infos = [
            'language' => 'en_GB',
            'users_id' => 2, // TU_USER
            'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER],
        ];

        $user2_infos = [
            'language' => 'en_GB', // Same language as user1
            'users_id' => 4, // tech
            'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER], // Same type as user1
        ];

        // Reset template cache to start fresh
        $template->resetComputedTemplates();

        // Get template for first user
        $template_id1 = $template->getTemplateByLanguage($target, $user1_infos, 'passwordexpires', []);
        $this->assertNotFalse($template_id1);

        // Get template for second user (same language and type, but different users_id)
        $template_id2 = $template->getTemplateByLanguage($target, $user2_infos, 'passwordexpires', []);
        $this->assertNotFalse($template_id2);

        // Verify that different template IDs are generated for different users
        // even if they have the same language and type
        $this->assertNotEquals(
            $template_id1,
            $template_id2,
            'Each user should get a unique template ID to ensure user-specific data is generated'
        );

        // Verify that both templates are cached separately
        $this->assertArrayHasKey($template_id1, $template->templates_by_languages);
        $this->assertArrayHasKey($template_id2, $template->templates_by_languages);

        // Test with identical user info but different users_id to ensure cache separation
        $user3_infos = [
            'language' => 'en_GB',
            'users_id' => 6, // normal user
            'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER],
        ];

        $template_id3 = $template->getTemplateByLanguage($target, $user3_infos, 'passwordexpires', []);
        $this->assertNotFalse($template_id3);

        // All three should be different
        $this->assertNotEquals($template_id1, $template_id3);
        $this->assertNotEquals($template_id2, $template_id3);

        // Test with email-based user (external user)
        $email_user_infos = [
            'language' => 'en_GB',
            'email' => 'external@example.com',
            'additionnaloption' => ['usertype' => NotificationTarget::EXTERNAL_USER],
        ];

        $template_id4 = $template->getTemplateByLanguage($target, $email_user_infos, 'passwordexpires', []);
        $this->assertNotFalse($template_id4);

        // Email-based user should also get unique template
        $this->assertNotEquals($template_id1, $template_id4);
        $this->assertNotEquals($template_id2, $template_id4);
        $this->assertNotEquals($template_id3, $template_id4);
    }

    /**
     * Test that template cache properly differentiates between users with emails
     */
    public function testTemplateUnicityWithEmailUsers(): void
    {
        $this->login();

        $user = new User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        $template = new NotificationTemplate();
        $template->getFromDB(1);
        $template->resetComputedTemplates();

        // Two different email users with same language
        $email_user1 = [
            'language' => 'en_GB',
            'email' => 'user1@example.com',
            'additionnaloption' => ['usertype' => NotificationTarget::EXTERNAL_USER],
        ];

        $email_user2 = [
            'language' => 'en_GB',
            'email' => 'user2@example.com',
            'additionnaloption' => ['usertype' => NotificationTarget::EXTERNAL_USER],
        ];

        $template_id1 = $template->getTemplateByLanguage($target, $email_user1, 'passwordexpires', []);
        $template_id2 = $template->getTemplateByLanguage($target, $email_user2, 'passwordexpires', []);

        $this->assertNotFalse($template_id1);
        $this->assertNotFalse($template_id2);
        $this->assertNotEquals($template_id1, $template_id2, 'Email users should get unique templates based on their email address');
    }

    /**
     * Test that the old behavior would have failed (users of same type getting same cache)
     * This test verifies what would happen WITHOUT our fix
     */
    public function testTemplateCacheKeyGeneration(): void
    {
        $this->login();

        $template = new NotificationTemplate();
        $template->getFromDB(1);

        $user = new User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        // Same user info except for users_id should generate different cache keys
        $user1_infos = [
            'language' => 'en_GB',
            'users_id' => 2,
            'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER],
        ];

        $user2_infos = [
            'language' => 'en_GB',
            'users_id' => 4, // Different user ID
            'additionnaloption' => ['usertype' => NotificationTarget::GLPI_USER],
        ];

        $template->resetComputedTemplates();

        $tid1 = $template->getTemplateByLanguage($target, $user1_infos, 'passwordexpires', []);
        $tid2 = $template->getTemplateByLanguage($target, $user2_infos, 'passwordexpires', []);

        // The cache keys (template IDs) should be different
        $this->assertNotEquals($tid1, $tid2, 'Template cache should differentiate between different users');

        // Test with same email addresses (should be different)
        $email_user1 = [
            'language' => 'en_GB',
            'email' => 'same@example.com',
            'additionnaloption' => ['usertype' => NotificationTarget::EXTERNAL_USER],
        ];

        $email_user2 = [
            'language' => 'en_GB',
            'email' => 'different@example.com',
            'additionnaloption' => ['usertype' => NotificationTarget::EXTERNAL_USER],
        ];

        $tid_email1 = $template->getTemplateByLanguage($target, $email_user1, 'passwordexpires', []);
        $tid_email2 = $template->getTemplateByLanguage($target, $email_user2, 'passwordexpires', []);

        $this->assertNotEquals($tid_email1, $tid_email2, 'Different email addresses should generate different template IDs');
    }
}
