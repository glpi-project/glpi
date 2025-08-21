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

namespace tests\units\Glpi\Form\AccessControl;

use DbTestCase;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;
use Profile;
use User;

class FormAccessControlTest extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the `getTypeName` method.
     *
     * @return void
     */
    public function testGetTypeName(): void
    {
        $form_access_control = new FormAccessControl();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($form_access_control::getTypeName());
    }

    /**
     * Test the `getIcon` method.
     *
     * @return void
     */
    public function testGetIcon(): void
    {
        $form_access_control = new FormAccessControl();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($form_access_control::getIcon());
    }

    public static function canProvider(): iterable
    {
        yield [
            'glpi',
            'glpi',
            ['view' => true, 'create' => true, 'update' => true, 'delete' => false, 'purge' => false],
        ];
        yield [
            'tech',
            'tech',
            ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false],
        ];
        yield [
            'normal',
            'normal',
            ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false],
        ];
        yield [
            'post-only',
            'postonly',
            ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false],
        ];
    }

    #[DataProvider('canProvider')]
    public function testCan(
        string $login,
        string $password,
        array $rights,
    ): void {
        $this->login($login, $password);
        $form_access_control = new FormAccessControl();

        $this->assertEquals($rights['view'], $form_access_control::canView());
        $this->assertEquals($rights['create'], $form_access_control::canCreate());
        $this->assertEquals($rights['update'], $form_access_control::canUpdate());
        $this->assertEquals($rights['delete'], $form_access_control::canDelete());
        $this->assertEquals($rights['purge'], $form_access_control::canPurge());
    }

    public function testCanItemForGlpiUser(): void
    {
        $this->login('glpi', 'glpi');
        $this->checkCanItem(
            item: $this->createAndGetAccessControl(),
            expected_rights: ['view' => true, 'update' => true, 'delete' => false, 'purge' => false],
        );
    }

    public function testCanItemForTechUser(): void
    {
        $this->login('tech', 'tech');
        $this->checkCanItem(
            item: $this->createAndGetAccessControl(),
            expected_rights: ['view' => false, 'update' => false, 'delete' => false, 'purge' => false],
        );
    }

    public function testCanItemForNormalUser(): void
    {
        $this->login('normal', 'normal');
        $this->checkCanItem(
            item: $this->createAndGetAccessControl(),
            expected_rights: ['view' => false, 'update' => false, 'delete' => false, 'purge' => false],
        );
    }

    public function testCanItemForPostOnlyUser(): void
    {
        $this->login('post-only', 'postonly');
        $this->checkCanItem(
            item: $this->createAndGetAccessControl(),
            expected_rights: ['view' => false, 'update' => false, 'delete' => false, 'purge' => false],
        );
    }

    private function checkCanItem(
        FormAccessControl $item,
        array $expected_rights,
    ): void {
        $this->assertEquals($expected_rights['view'], $item->canViewItem() && (bool) $item::canView());
        $this->assertEquals($expected_rights['update'], $item->canUpdateItem() && (bool) $item::canUpdate());
        $this->assertEquals($expected_rights['delete'], $item->canDeleteItem() && (bool) $item::canDelete());
        $this->assertEquals($expected_rights['purge'], $item->canPurgeItem() && (bool) $item::canPurge());
    }

    public function testCanCreateItemForGlpiUser(): void
    {
        $this->login('glpi', 'glpi');
        $form = $this->createAndGetSimpleForm();
        $this->checkCanCreateItem(
            input: [
                'forms_forms_id' => $form->getID(),
                'strategy'       => new DirectAccess(),
                'config'         => new DirectAccessConfig(),
                'is_active'      => true,
            ],
            expected: true,
        );
    }

    public function testCanCreateItemForTechUser(): void
    {
        $this->login('tech', 'tech');
        $form = $this->createAndGetSimpleForm();
        $this->checkCanCreateItem(
            input: [
                'forms_forms_id' => $form->getID(),
                'strategy'       => new DirectAccess(),
                'config'         => new DirectAccessConfig(),
                'is_active'      => true,
            ],
            expected: false,
        );
    }

    public function testCanCreateItemForNormalUser(): void
    {
        $this->login('normal', 'normal');
        $form = $this->createAndGetSimpleForm();
        $this->checkCanCreateItem(
            input: [
                'forms_forms_id' => $form->getID(),
                'strategy'       => new DirectAccess(),
                'config'         => new DirectAccessConfig(),
                'is_active'      => true,
            ],
            expected: false,
        );
    }

    public function testCanCreateItemForPostOnlyUser(): void
    {
        $this->login('post-only', 'postonly');
        $form = $this->createAndGetSimpleForm();
        $this->checkCanCreateItem(
            input: [
                'forms_forms_id' => $form->getID(),
                'strategy'       => new DirectAccess(),
                'config'         => new DirectAccessConfig(),
                'is_active'      => true,
            ],
            expected: false,
        );
    }

    private function checkCanCreateItem(
        array $input,
        bool $expected
    ): void {
        $form_access_control = new FormAccessControl();
        $form_access_control->input = $input;

        $this->assertEquals(
            $expected,
            $form_access_control->canCreateItem() && (bool) $form_access_control::canCreate()
        );
    }

    public function testGetTabNameForEmptyForm(): void
    {
        $form = $this->createAndGetSimpleForm();
        $this->checkGetTabNameForItem($form, "Access controls 1"); // 1 for default policy
    }

    public function testGetTabNameWithActivePolicies(): void
    {
        $form = $this->createAndGetComplexForm();

        $this->checkGetTabNameForItem($form, "Access controls 2");
        $this->checkGetTabNameForItem($form, "Access controls", count: false);
    }

    public function testGetTabNameWithInactiveAndActivePolicies(): void
    {
        $form = $this->createAndGetComplexForm();
        $allow_list_control = $this->getAccessControl($form, AllowList::class);
        $this->updateItem(FormAccessControl::class, $allow_list_control->getId(), [
            'is_active' => false,
        ]);

        $this->checkGetTabNameForItem($form, "Access controls 1");
        $this->checkGetTabNameForItem($form, "Access controls", count: false);
    }

    public function testGetTabNameWithInactivePolicies(): void
    {
        $form = $this->createAndGetComplexForm();
        $allow_list_control = $this->getAccessControl($form, AllowList::class);
        $this->updateItem(FormAccessControl::class, $allow_list_control->getId(), [
            'is_active' => false,
        ]);
        $direct_access_control = $this->getAccessControl($form, DirectAccess::class);
        $this->updateItem(FormAccessControl::class, $direct_access_control->getId(), [
            'is_active' => false,
        ]);
        $this->checkGetTabNameForItem($form, "Access controls");
    }

    private function checkGetTabNameForItem(
        Form $form,
        string $expected,
        bool $count = true
    ): void {
        // Reload to clear cached data
        $form->getFromDB($form->getID());

        $this->login();
        $_SESSION['glpishow_count_on_tabs'] = $count;

        $form_access_control = new FormAccessControl();
        $tab_name = $form_access_control->getTabNameForItem($form);

        // Strip tags to keep only the relevant data
        $tab_name = strip_tags($tab_name);
        $this->assertEquals($expected, $tab_name);
    }

    public function testDisplayTabContentForItem(): void
    {
        // Mock server/query variables
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['id'] = 1;

        $this->login();

        $form = $this->createAndGetComplexForm();
        $form_access_control = new FormAccessControl();

        ob_start();
        $return = $form_access_control->displayTabContentForItem($form);
        ob_end_clean();

        $this->assertTrue($return);
    }

    public static function prepareInputForAddProvider(): iterable
    {
        yield 'Valid config' => [
            'input' => [
                'strategy' => DirectAccess::class,
                '_config'  => new DirectAccessConfig(token: "my_token"),
            ],
            'expected_fields' => [
                'strategy' => DirectAccess::class,
                'config'   => json_encode([
                    'token'                 => "my_token",
                    'allow_unauthenticated' => false,
                ]),
            ],
        ];
    }

    #[DataProvider('prepareInputForAddProvider')]
    public function testPrepareInputForAdd(
        array $input,
        array|false $expected_fields,
        ?string $warning = null
    ): void {
        $form_access_control = new FormAccessControl();
        $form_access_control->input = $input;

        $prepared_input = $form_access_control->prepareInputForAdd($input);
        $this->assertEquals($expected_fields, $prepared_input);
    }

    /**
     * Test the `prepareInputForUpdate` method.
     *
     * Most tests are already handle by the `prepareInputForAdd` method, we
     * only test here update specific features.
     *
     * @return void
     */
    public function testPrepareInputForUpdate(): void
    {
        $form_access_control = new FormAccessControl();

        // Make sure `_no_message_link` is added to the fields
        $prepared_input = $form_access_control->prepareInputForUpdate([
            'strategy' => DirectAccess::class,
            '_config'  => new DirectAccessConfig(token: "my_token"),
        ]);
        $this->assertEquals([
            '_no_message_link' => true,
            'strategy'         => DirectAccess::class,
            'config'           => json_encode(new DirectAccessConfig(token: "my_token")),
        ], $prepared_input);
    }

    /**
     * Test the `getStrategy` method.
     *
     * @return void
     */
    public function testGetStrategy(): void
    {
        $form_access_control = new FormAccessControl();

        // Read strategy from field
        $form_access_control->fields = ['strategy' => DirectAccess::class];
        $strategy = $form_access_control->getStrategy();
        $this->assertInstanceOf(DirectAccess::class, $strategy);
    }

    /**
     * Test the `getConfig` method.
     *
     * @return void
     */
    public function testGetConfig(): void
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                    allow_unauthenticated: true,
                ))
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "glpi", true),
                        getItemByTypeName(User::class, "normal", true),
                        getItemByTypeName(User::class, "tech", true),
                    ],
                    group_ids: [
                        getItemByTypeName(Group::class, "_test_group_1", true),
                        getItemByTypeName(Group::class, "_test_group_2", true),
                    ],
                    profile_ids: [
                        getItemByTypeName(Profile::class, "Super-Admin", true),
                    ],
                ))
        );

        // Check config of the direct access policy
        $access_control_1 = $this->getAccessControl($form, DirectAccess::class);
        $this->assertEquals(json_encode([
            'token'                 => 'my_token',
            'allow_unauthenticated' => true,
        ]), json_encode($access_control_1->getConfig()));

        // Check config of the allow list policy
        $access_control_2 = $this->getAccessControl($form, AllowList::class);
        $this->assertEquals(json_encode([
            'user_ids'    => [
                getItemByTypeName(User::class, "glpi", true),
                getItemByTypeName(User::class, "normal", true),
                getItemByTypeName(User::class, "tech", true),
            ],
            'group_ids'   => [
                getItemByTypeName(Group::class, "_test_group_1", true),
                getItemByTypeName(Group::class, "_test_group_2", true),
            ],
            'profile_ids' => [
                getItemByTypeName(Profile::class, "Super-Admin", true),
            ],
        ]), json_encode($access_control_2->getConfig()));
    }

    public function testGetNormalizedInputName(): void
    {
        $form_access_control = new FormAccessControl();
        $form_access_control->fields = ['id' => 1];

        $this->assertEquals(
            "_access_control[1][test]",
            $form_access_control->getNormalizedInputName("test")
        );
    }

    private function createAndGetAccessControl(): FormAccessControl
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig())
        );
        return $this->getAccessControl($form, DirectAccess::class);
    }

    private function createAndGetSimpleForm(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
    }

    private function createAndGetComplexForm(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                    allow_unauthenticated: true,
                ))
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "glpi", true),
                        getItemByTypeName(User::class, "normal", true),
                        getItemByTypeName(User::class, "tech", true),
                    ],
                    group_ids: [
                        getItemByTypeName(Group::class, "_test_group_1", true),
                        getItemByTypeName(Group::class, "_test_group_2", true),
                    ],
                    profile_ids: [
                        getItemByTypeName(Profile::class, "Super-Admin", true),
                    ],
                ))
        );
    }
}
