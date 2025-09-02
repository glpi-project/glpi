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

namespace tests\units\Glpi\Form;

use AbstractRightsDropdown;
use Entity;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use User;

/**
 * Separate file for serializer tests related to form access policies.
 * This helps keeping the main serializer test file smaller and more readable.
 */
final class FormSerializerAccessPoliciesTest extends \DbTestCase
{
    use FormTesterTrait;

    private static FormSerializer $serializer;

    public static function setUpBeforeClass(): void
    {
        self::$serializer = new FormSerializer();
        parent::setUpBeforeClass();
    }

    public static function exportAndImportDirectAccessPolicyProvider(): iterable
    {
        yield 'Active' => [
            'is_active' => true,
        ];
        yield 'Inactive' => [
            'is_active' => false,
        ];
        yield 'Allow unauthenticated' => [
            'allow_unauthenticated' => true,
        ];
        yield 'Disallow unauthenticated' => [
            'allow_unauthenticated' => false,
        ];
    }

    #[DataProvider('exportAndImportDirectAccessPolicyProvider')]
    public function testExportAndImportDirectAccessPolicy(
        string $token = "my_token",
        bool $allow_unauthenticated = false,
        bool $is_active = true,
    ): void {
        // Arrange: create a form with a direct access policy
        $builder = new FormBuilder("My test form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            strategy: DirectAccess::class,
            config: new DirectAccessConfig(
                token: $token,
                allow_unauthenticated: $allow_unauthenticated,
            ),
            is_active: $is_active,
        );
        $form = $this->createForm($builder);

        // Act: export and import form
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate access policy config
        $policies = $form_copy->getAccessControls();
        $this->assertCount(1, $policies);
        $policy = current($policies);
        $this->assertSame($is_active, (bool) $policy->fields['is_active']);

        $strategy = $policy->getStrategy();
        $this->assertInstanceOf(DirectAccess::class, $strategy);

        /** @var DirectAccessConfig $config  */
        $config = $policy->getConfig();
        $this->assertSame($token, $config->getToken());
        $this->assertSame($allow_unauthenticated, $config->allowUnauthenticated());
    }

    public static function exportAndImportAllowListPolicyProvider(): iterable
    {
        yield 'Active' => [
            'is_active' => true,
        ];
        yield 'Inactive' => [
            'is_active' => false,
        ];
        yield 'With users' => [
            'user_ids' => [
                getItemByTypeName(User::class, "glpi", true),
                getItemByTypeName(User::class, "tech", true),
            ],
        ];
        yield 'With groups' => [
            'group_ids' => [
                getItemByTypeName(Group::class, "_test_group_1", true),
                getItemByTypeName(Group::class, "_test_group_2", true),
            ],
        ];
        yield 'With profiles' => [
            'profile_ids' => [
                getItemByTypeName(Profile::class, "Super-Admin", true),
                getItemByTypeName(Profile::class, "Read-Only", true),
            ],
        ];
        yield 'With users and special value' => [
            'user_ids' => [
                AbstractRightsDropdown::ALL_USERS,
                getItemByTypeName(User::class, "glpi", true),
                getItemByTypeName(User::class, "tech", true),
            ],
        ];
        yield 'With everything' => [
            'user_ids' => [
                AbstractRightsDropdown::ALL_USERS,
                getItemByTypeName(User::class, "glpi", true),
                getItemByTypeName(User::class, "tech", true),
            ],
            'group_ids' => [
                getItemByTypeName(Group::class, "_test_group_1", true),
                getItemByTypeName(Group::class, "_test_group_2", true),
            ],
            'profile_ids' => [
                getItemByTypeName(Profile::class, "Super-Admin", true),
                getItemByTypeName(Profile::class, "Read-Only", true),
            ],
        ];
    }

    #[DataProvider('exportAndImportAllowListPolicyProvider')]
    public function testExportAndImportAllowListPolicy(
        array $user_ids = [],
        array $group_ids = [],
        array $profile_ids = [],
        bool $is_active = true,
    ): void {
        // Arrange: Create a form with an allow list policy
        $builder = new FormBuilder("My test form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            strategy: AllowList::class,
            config: new AllowListConfig(
                user_ids   : $user_ids,
                group_ids  : $group_ids,
                profile_ids: $profile_ids,
            ),
            is_active: $is_active,
        );
        $form = $this->createForm($builder);

        // Act: Export and import form.
        $form_copy = $this->exportAndImportForm($form);

        // Assert: validate allow list policy config
        $policies = $form_copy->getAccessControls();
        $this->assertCount(1, $policies);
        $policy = current($policies);
        $this->assertSame($is_active, (bool) $policy->fields['is_active']);

        $strategy = $policy->getStrategy();
        $this->assertInstanceOf(AllowList::class, $strategy);

        /** @var AllowListConfig $config */
        $config = $policy->getConfig();
        $this->assertSame($user_ids, $config->getUserIds());
        $this->assertSame($group_ids, $config->getGroupIds());
        $this->assertSame($profile_ids, $config->getProfileIds());
    }

    public function testExportAndImportAllowListPolicyWithMapping(): void
    {
        // Arrange: create multiples users/profiles/groups and create a form
        // with an allow list policy that reference them.
        [$user_1, $user_2, $user_3, $user_4] = $this->createItemsWithNames(
            User::class,
            ["User 1", "User 2", "User 3", "User 4"]
        );
        [$group_1, $group_2, $group_3, $group_4] = $this->createItemsWithNames(
            Group::class,
            ["Group 1",  "Group 2", "Group 3", "Group 4"]
        );
        [$profile_1, $profile_2, $profile_3, $profile_4] = $this->createItemsWithNames(
            Profile::class,
            ["Profile 1", "Profile 2", "Profile 3", "Profile 4"]
        );

        $builder = new FormBuilder("My test form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(AllowList::class, new AllowListConfig(
            user_ids: [$user_1->getID(), $user_2->getID(), AbstractRightsDropdown::ALL_USERS],
            group_ids: [$group_1->getID(), $group_2->getID()],
            profile_ids: [$profile_1->getID(), $profile_2->getID()],
        ));
        $form = $this->createForm($builder);

        // Act: Map database items (items 1 and 2 are replaced by items 3 and 4)
        // then export and import form.
        $mapper = new DatabaseMapper([$this->getTestRootEntity(only_id: true)]);
        $mapper->addMappedItem(User::class, "User 1", $user_3->getID());
        $mapper->addMappedItem(User::class, "User 2", $user_4->getID());
        $mapper->addMappedItem(Group::class, "Group 1", $group_3->getID());
        $mapper->addMappedItem(Group::class, "Group 2", $group_4->getID());
        $mapper->addMappedItem(Profile::class, "Profile 1", $profile_3->getID());
        $mapper->addMappedItem(Profile::class, "Profile 2", $profile_4->getID());

        $json = $this->exportForm($form);
        $form_copy = $this->importForm($json, $mapper, []);

        // Assert: validate allow list policy config
        $policies = $form_copy->getAccessControls();
        $this->assertCount(1, $policies);
        $policy = current($policies);

        $strategy = $policy->getStrategy();
        $this->assertInstanceOf(AllowList::class, $strategy);

        /** @var AllowListConfig $config */
        $config = $policy->getConfig();
        $allowed_groups = $this->getItemsNames(Group::class, $config->getGroupIds());
        $allowed_profiles = $this->getItemsNames(Profile::class, $config->getProfileIds());
        $this->assertSame([ // Can't map this one to names because of the special "all" value
            getItemByTypeName(User::class, "User 3", true),
            getItemByTypeName(User::class, "User 4", true),
            AbstractRightsDropdown::ALL_USERS,
        ], $config->getUserIds());
        $this->assertSame(["Group 3", "Group 4"], $allowed_groups);
        $this->assertSame(["Profile 3", "Profile 4"], $allowed_profiles);
    }

    public function testAllowListPolicyDataRequirementsAreExported(): void
    {
        // Arrange: create multiples users/profiles/groups and create a form
        // with an allow list policy that references them.
        [$user_1, $user_2] = $this->createItemsWithNames(
            User::class,
            ["User 1", "User 2"]
        );
        [$group_1, $group_2] = $this->createItemsWithNames(
            Group::class,
            ["Group 1",  "Group 2"]
        );
        [$profile_1, $profile_2] = $this->createItemsWithNames(
            Profile::class,
            ["Profile 1", "Profile 2"]
        );

        $builder = new FormBuilder("My test form");
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(AllowList::class, new AllowListConfig(
            user_ids: [$user_1->getID(), $user_2->getID(), AbstractRightsDropdown::ALL_USERS],
            group_ids: [$group_1->getID(), $group_2->getID()],
            profile_ids: [$profile_1->getID(), $profile_2->getID()],
        ));
        $form = $this->createForm($builder);

        // Act:: export to JSON
        $json = $this->exportForm($form);

        // Assert: validate that all referenced items are required
        $data = json_decode($json, true);
        $requirements = $data['forms'][0]['data_requirements'];
        $this->assertEquals([
            ['itemtype' => Entity::class,  'name' => "Root entity > _test_root_entity"],
            ['itemtype' => User::class,    'name' => "User 1"],
            ['itemtype' => User::class,    'name' => "User 2"],
            ['itemtype' => Group::class,   'name' => "Group 1"],
            ['itemtype' => Group::class,   'name' => "Group 2"],
            ['itemtype' => Profile::class, 'name' => "Profile 1"],
            ['itemtype' => Profile::class, 'name' => "Profile 2"],
        ], $requirements);
    }
}
