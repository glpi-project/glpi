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

class NotificationEventAbstractTest extends DbTestCase
{
    /**
     * Test that the fix for user-specific template generation works correctly
     * This test validates that when multiple users of the same type receive
     * notifications, each gets a unique template instance with user-specific data
     */
    public function testUserSpecificTemplateGeneration(): void
    {
        $this->login();

        // Create a test ticket for notification
        $ticket = $this->createItem('Ticket', [
            'name'        => 'Test ticket for notification',
            'content'     => 'Test content',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        // Use a real notification target instead of mock to avoid type issues
        $target = new \NotificationTargetTicket(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'new',
            $ticket
        );

        // Set the event properly to avoid class name errors
        $target->setEvent('NotificationEventMailing');

        // Create a notification template
        $template = new \NotificationTemplate();
        $template->getFromDB(1); // Use existing template

        // Simulate notification event with multiple users of same type
        $users_list = [
            [
                'language' => 'en_GB',
                'users_id' => 2, // TU_USER
                'email'    => 'user1@test.com',
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
            [
                'language' => 'en_GB',
                'users_id' => 4, // tech user
                'email'    => 'user2@test.com',
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
        ];

        $generated_template_ids = [];

        // Simulate the core logic from NotificationEventAbstract::raise()
        // Don't reset the template cache between users to verify they get different IDs
        foreach ($users_list as $index => $users_infos) {
            // Create a new NotificationTarget instance for each user
            $user_specific_target = clone $target;
            $user_specific_target->clearAddressesList();
            $user_specific_target->addToRecipientsList($users_infos);

            // Explicitly reset target data to ensure plugin hooks generate fresh data per user
            $user_specific_target->data = [];

            // Get template by language with user-specific target
            $tid = $template->getTemplateByLanguage(
                $user_specific_target,
                $users_infos,
                'new',
                ['additionnaloption' => $users_infos['additionnaloption']]
            );

            $this->assertNotFalse($tid, "Template should be generated for user {$users_infos['users_id']}");
            $generated_template_ids[$index] = $tid;
        }

        // Verify that different template IDs are generated for different users
        // even if they have the same language and type
        $this->assertNotEquals(
            $generated_template_ids[0],
            $generated_template_ids[1],
            'Each user should get a unique template ID even if they are the same type'
        );

        // Verify templates are cached separately (both user-specific templates should be in cache now)
        // Note: There may also be a base template entry, but we're only counting user-specific ones
        $user_specific_templates = 0;
        foreach ($template->templates_by_languages as $key => $value) {
            if (strpos($key, '_base') === false) {
                $user_specific_templates++;
            }
        }
        $this->assertEquals(
            2,
            $user_specific_templates,
            'Each user should have a separate template cache entry'
        );
    }

    /**
     * Test that NotificationTarget cloning works correctly and isolates user data
     */
    public function testNotificationTargetCloning(): void
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Test ticket',
            'content'     => 'Test content',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $original_target = new \NotificationTargetTicket(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'new',
            $ticket
        );

        // Set the event properly to avoid class name errors
        $original_target->setEvent('NotificationEventMailing');

        // Add some data to the original target
        $original_target->data = ['original' => 'data'];

        // Add a recipient to original target
        $original_target->addToRecipientsList([
            'language' => 'en_GB',
            'users_id' => 2,
            'email'    => 'original@test.com',
        ]);

        // Clone the target
        $cloned_target = clone $original_target;

        // Clear addresses and reset data as done in the fix
        $cloned_target->clearAddressesList();
        $cloned_target->data = [];

        // Add different recipient to cloned target
        $cloned_target->addToRecipientsList([
            'language' => 'en_GB',
            'users_id' => 4,
            'email'    => 'cloned@test.com',
        ]);

        // Add different data to cloned target
        $cloned_target->data = ['cloned' => 'data'];

        // Verify isolation
        $this->assertEquals(['original' => 'data'], $original_target->data);
        $this->assertEquals(['cloned' => 'data'], $cloned_target->data);

        $original_targets = $original_target->getTargets();
        $cloned_targets = $cloned_target->getTargets();

        $this->assertNotEmpty($original_targets);
        $this->assertNotEmpty($cloned_targets);
        $this->assertNotEquals($original_targets, $cloned_targets);

        // Verify that each target has different recipients
        $original_emails = array_keys($original_targets);
        $cloned_emails = array_keys($cloned_targets);

        $this->assertContains('original@test.com', $original_emails);
        $this->assertContains('cloned@test.com', $cloned_emails);
        $this->assertNotContains('cloned@test.com', $original_emails);
        $this->assertNotContains('original@test.com', $cloned_emails);
    }

    /**
     * Test template cache key generation with different user scenarios
     */
    public function testTemplateCacheKeyUniqueness(): void
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name'        => 'Test ticket',
            'content'     => 'Test content',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $target = new \NotificationTargetTicket(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'new',
            $ticket
        );

        // Set the event properly
        $target->setEvent('NotificationEventMailing');

        $template = new \NotificationTemplate();
        $template->getFromDB(1);

        // Test scenarios that should generate different cache keys
        $scenarios = [
            'user1' => [
                'language' => 'en_GB',
                'users_id' => 2,
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
            'user2' => [
                'language' => 'en_GB',
                'users_id' => 4, // Different user, same language and type
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
            'email1' => [
                'language' => 'en_GB',
                'email' => 'user1@example.com',
                'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
            ],
            'email2' => [
                'language' => 'en_GB',
                'email' => 'user2@example.com', // Different email, same language
                'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
            ],
        ];

        $template_ids = [];

        foreach ($scenarios as $name => $user_infos) {
            $template->resetComputedTemplates();
            $tid = $template->getTemplateByLanguage($target, $user_infos, 'new', []);
            $this->assertNotFalse($tid, "Template generation should succeed for scenario: $name");
            $template_ids[$name] = $tid;
        }

        // All template IDs should be unique
        $unique_ids = array_unique($template_ids);
        $this->assertCount(
            count($scenarios),
            $unique_ids,
            'All scenarios should generate unique template cache keys: ' . json_encode($template_ids)
        );

        // Verify specific combinations that were problematic before the fix
        $this->assertNotEquals(
            $template_ids['user1'],
            $template_ids['user2'],
            'Different users with same language/type should get different template IDs'
        );

        $this->assertNotEquals(
            $template_ids['email1'],
            $template_ids['email2'],
            'Different emails with same language should get different template IDs'
        );
    }
}
