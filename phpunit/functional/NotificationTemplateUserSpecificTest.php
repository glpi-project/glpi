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

/**
 * Test for user-specific template generation fix.
 * This validates the fix for the bug where users of the same type were sharing
 * the same cached template, causing plugins to generate identical data (like tokens)
 * for all users of the same type.
 *
 * @see https://github.com/glpi-project/glpi/pull/20186
 */
class NotificationTemplateUserSpecificTest extends DbTestCase
{
    /**
     * Test that each user gets a unique template cache key
     * even when they have the same language and user type.
     */
    public function testUniqueTemplateCacheKeyPerUser(): void
    {
        $this->login();

        $template = new \NotificationTemplate();
        $template->getFromDB(1); // Use an existing template

        $user = new \User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        // Two different users with the same language and type
        $user1_infos = [
            'language' => 'en_GB',
            'users_id' => 2, // TU_USER
            'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
        ];

        $user2_infos = [
            'language' => 'en_GB', // Same language as user1
            'users_id' => 4, // tech user
            'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER], // Same type as user1
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
        $this->assertNotEquals(
            $template_id1,
            $template_id2,
            'Each user should get a unique template cache key to ensure user-specific data is generated'
        );
    }

    /**
     * Test that current_user_infos is properly set on NotificationTarget
     * when getForTemplate is called.
     */
    public function testCurrentUserInfosIsSetOnTarget(): void
    {
        $this->login();

        $user = new \User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        $user_infos = [
            'language' => 'en_GB',
            'users_id' => 2,
            'email'    => 'test@example.com',
            'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
        ];

        $options = [
            '_user_infos' => $user_infos,
            'additionnaloption' => $user_infos['additionnaloption'],
        ];

        // Call getForTemplate with user_infos in options
        $target->getForTemplate('passwordexpires', $options);

        // Verify that current_user_infos is set
        $this->assertNotEmpty($target->current_user_infos);
        $this->assertEquals($user_infos['users_id'], $target->current_user_infos['users_id']);
        $this->assertEquals($user_infos['email'], $target->current_user_infos['email']);
    }

    /**
     * Test that different email users get unique template cache keys
     */
    public function testUniqueTemplateCacheKeyPerEmailUser(): void
    {
        $this->login();

        $template = new \NotificationTemplate();
        $template->getFromDB(1);

        $user = new \User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        $email_user1 = [
            'language' => 'en_GB',
            'email' => 'user1@example.com',
            'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
        ];

        $email_user2 = [
            'language' => 'en_GB',
            'email' => 'user2@example.com',
            'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
        ];

        $template->resetComputedTemplates();

        $template_id1 = $template->getTemplateByLanguage($target, $email_user1, 'passwordexpires', []);
        $template_id2 = $template->getTemplateByLanguage($target, $email_user2, 'passwordexpires', []);

        $this->assertNotFalse($template_id1);
        $this->assertNotFalse($template_id2);
        $this->assertNotEquals(
            $template_id1,
            $template_id2,
            'Email users should get unique templates based on their email address'
        );
    }

    /**
     * Test that mixed users (GLPI users and email users) all get unique templates
     */
    public function testMixedUserTypesGetUniqueTemplates(): void
    {
        $this->login();

        $template = new \NotificationTemplate();
        $template->getFromDB(1);

        $user = new \User();
        $target = new \NotificationTargetUser(0, 'passwordexpires', $user);

        $scenarios = [
            'glpi_user_1' => [
                'language' => 'en_GB',
                'users_id' => 2,
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
            'glpi_user_2' => [
                'language' => 'en_GB',
                'users_id' => 4,
                'additionnaloption' => ['usertype' => \NotificationTarget::GLPI_USER],
            ],
            'external_email_1' => [
                'language' => 'en_GB',
                'email' => 'external1@example.com',
                'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
            ],
            'external_email_2' => [
                'language' => 'en_GB',
                'email' => 'external2@example.com',
                'additionnaloption' => ['usertype' => \NotificationTarget::EXTERNAL_USER],
            ],
        ];

        $template->resetComputedTemplates();
        $template_ids = [];

        foreach ($scenarios as $name => $user_infos) {
            $tid = $template->getTemplateByLanguage($target, $user_infos, 'passwordexpires', []);
            $this->assertNotFalse($tid, "Template generation should succeed for scenario: $name");
            $template_ids[$name] = $tid;
        }

        // All template IDs should be unique
        $unique_ids = array_unique($template_ids);
        $this->assertCount(
            count($scenarios),
            $unique_ids,
            'All scenarios should generate unique template cache keys'
        );
    }
}
