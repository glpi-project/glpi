<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\AccessControl;

use CommonGLPI;
use Computer;
use DbTestCase;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Impact;
use Profile;
use Ticket;
use User;

class FormAccessControl extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the `getTypeName` method.
     *
     * @return void
     */
    public function testGetTypeName(): void
    {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        // Not much to test here, just ensure the method run without errors
        $this->string($form_access_control::getTypeName());
    }

    /**
     * Test the `getIcon` method.
     *
     * @return void
     */
    public function testGetIcon(): void
    {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        // Not much to test here, just ensure the method run without errors
        $this->string($form_access_control::getIcon());
    }

    /**
     * Provider for the `testCan` method
     *
     * @return iterable
     */
    protected function testCanProvider(): iterable
    {
        $this->login('glpi', 'glpi');
        yield ['view' => true, 'create' => false, 'update' => true, 'delete' => false, 'purge' => false];

        $this->login('tech', 'tech');
        yield ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false];

        $this->login('normal', 'normal');
        yield ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false];

        $this->login('post-only', 'postonly');
        yield ['view' => false, 'create' => false, 'update' => false, 'delete' => false, 'purge' => false];
    }

    /**
     * Test all `canXxx` methods
     *
     * @dataProvider testCanProvider
     *
     * @param bool $view
     * @param bool $create
     * @param bool $update
     * @param bool $delete
     * @param bool $purge
     *
     * @return void
     */
    public function testCan(
        bool $view,
        bool $create,
        bool $update,
        bool $delete,
        bool $purge,
    ): void {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        $this->boolean((bool) $form_access_control::canView())->isEqualTo($view);
        $this->boolean((bool) $form_access_control::canCreate())->isEqualTo($create);
        $this->boolean((bool) $form_access_control::canUpdate())->isEqualTo($update);
        $this->boolean((bool) $form_access_control::canDelete())->isEqualTo($delete);
        $this->boolean((bool) $form_access_control::canPurge())->isEqualTo($purge);
    }

    /**
     * Provider for the `testCanItem` method
     *
     * @return iterable
     */
    protected function testCanItemProvider(): iterable
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([]))
        );
        $control = $this->getAccessControl($form, DirectAccess::class);

        $this->login('glpi', 'glpi');
        yield ['item' => $control, 'view' => true, 'update' => true, 'delete' => false, 'purge' => false];

        $this->login('tech', 'tech');
        yield ['item' => $control, 'view' => false, 'update' => false, 'delete' => false, 'purge' => false];

        $this->login('normal', 'normal');
        yield ['item' => $control, 'view' => false, 'update' => false, 'delete' => false, 'purge' => false];

        $this->login('post-only', 'postonly');
        yield ['item' => $control, 'view' => false, 'update' => false, 'delete' => false, 'purge' => false];
    }

    /**
     * Test all `canXxxItem` methods, expect canCreateItem that has its own
     * dedicated tests as it expect a different format.
     *
     * @dataProvider testCanItemProvider
     *
     * @param \Glpi\Form\AccessControl\FormAccessControl $item
     * @param bool $view
     * @param bool $update
     * @param bool $delete
     * @param bool $purge
     *
     * @return void
     */
    public function testCanItem(
        \Glpi\Form\AccessControl\FormAccessControl $item,
        bool $view,
        bool $update,
        bool $delete,
        bool $purge,
    ): void {
        $this->boolean($item->canViewItem() && (bool) $item::canView())->isEqualTo($view);
        $this->boolean($item->canUpdateItem() && (bool) $item::canUpdate())->isEqualTo($update);
        $this->boolean($item->canDeleteItem() && (bool) $item::canDelete())->isEqualTo($delete);
        $this->boolean($item->canPurgeItem() && (bool) $item::canPurge())->isEqualTo($purge);
    }

    /**
     * Provider for the `testCanCreateItem` method
     *
     * @return iterable
     */
    protected function testCanCreateItemProvider(): iterable
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
        );
        $input = [
            'forms_forms_id' => $form->getID(),
            'strategy'       => new DirectAccess(),
            'config'         => new DirectAccessConfig([]),
            'is_active'      => true,
        ];

        $this->login('glpi', 'glpi');
        yield ['input' => $input, 'expected' => false];

        $this->login('tech', 'tech');
        yield ['input' => $input, 'expected' => false];

        $this->login('normal', 'normal');
        yield ['input' => $input, 'expected' => false];

        $this->login('post-only', 'postonly');
        yield ['input' => $input, 'expected' => false];
    }

    /**
     * Test for the `canCreateItem` method.
     *
     * @dataProvider testCanCreateItemProvider
     *
     * @param array $input
     * @param bool $expected
     *
     * @return void
     */
    public function testCanCreateItem(array $input, bool $expected): void
    {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();
        $form_access_control->input = $input;

        $this->boolean(
            $form_access_control->canCreateItem()
            && (bool) $form_access_control::canCreate()
        )->isEqualTo($expected);
    }

    /**
     * Data provider for the "testGetTabNameForItem" method
     *
     * @return iterable
     */
    protected function testGetTabNameForItemProvider(): iterable
    {
        $this->login();

        // Invalid types
        yield [new Computer(), false];
        yield [new Ticket(), false];
        yield [new Impact(), false];

        // Valid type form
        $form = $this->createForm(new FormBuilder());
        yield [$form, "Access control"];
        $_SESSION['glpishow_count_on_tabs'] = true;
        yield [$form, "Access control"]; // No changes
    }

    /**
     * Test the "getTabNameForItem" method.
     *
     * @dataProvider testGetTabNameForItemProvider
     *
     * @param CommonGLPI $item
     * @param string|false $expected_tab_name
     *
     * @return void
     */
    public function testGetTabNameForItem(
        CommonGLPI $item,
        string|false $expected_tab_name
    ): void {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        $tab_name = $form_access_control->getTabNameForItem($item);

        if ($tab_name !== false) {
            // Strip tags to keep only the relevant data
            $tab_name = strip_tags($tab_name);
        }

        $this->variable($tab_name)->isEqualTo($expected_tab_name);
    }

    /**
     * Data provider for the "testDisplayTabContentForItem" method
     *
     * @return iterable
     */
    protected function testDisplayTabContentForItemProvider(): iterable
    {
        $this->login();

        // Mock server/query variables
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['id'] = 1;

        // Invalid types
        yield [new Computer(), false];
        yield [new Ticket(), false];
        yield [new Impact(), false];

        // Form without access controls
        $form_1 = $this->createForm(new FormBuilder());
        yield [$form_1, true];

        // Form with all possibles access controls
        // We will try to send the most complex config possible, as default values
        // are already tested by the previous case.
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addQuestion("Name", QuestionTypeShortText::class)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token'                 => 'my_token',
                    'allow_unauthenticated' => true,
                    'force_direct_access'   => true,
                ]))
                ->addAccessControl(AllowList::class, new AllowListConfig([
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
                ]))
        );
        yield [$form_2, true];
    }

    /**
     * Tests for the "displayTabContentForItem" method
     *
     * Note: the tab content itself is not verified here as it would be too
     * complex.
     * It should be verified using a separate E2E test instead.
     * Any error while rendering the tab will still be caught by this tests so
     * it isn't completely useless.
     *
     * @dataProvider testdisplayTabContentForItemProvider
     *
     * @param CommonGLPI $item
     * @param bool       $expected_return
     *
     * @return void
     */
    public function testDisplayTabContentForItem(
        CommonGLPI $item,
        bool $expected_return
    ): void {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        ob_start();
        $return = $form_access_control->displayTabContentForItem($item);
        ob_end_clean();

        $this->variable($return)->isEqualTo($expected_return);
    }

    /**
     * Data provider for the `testPrepareInputForAdd` method.
     *
     * @return iterable
     */
    protected function testPrepareInputForAddProvider(): iterable
    {
        // Test invalid strategy
        yield [
            'input' => [
                'strategy' => "not a strategy",
            ],
            'expected_fields' => false,
            'warning'         => "Invalid access control strategy: not a strategy",
        ];

        // Test direct input of a raw config item
        yield [
            'input' => [
                'strategy' => DirectAccess::class,
                '_config'  => new DirectAccessConfig(['token' => "my_token"]),
            ],
            'expected_fields' => [
                'strategy' => DirectAccess::class,
                'config'   => json_encode([
                    'token'                 => "my_token",
                    'allow_unauthenticated' => false,
                    'force_direct_access'   => false,
                ]),
            ],
        ];

        // Test user supplied input
        yield [
            'input' => [
                'strategy' => DirectAccess::class,
                '_token'   => "my_token",
            ],
            'expected_fields' => [
                '_token'   => "my_token", // Special fields are not deleted
                'strategy' => DirectAccess::class,
                'config'   => json_encode([
                    'token'                 => "my_token",
                    'allow_unauthenticated' => false,
                    'force_direct_access'   => false,
                ]),
            ],
        ];
    }

    /**
     * Test the `prepareInputForAdd` method.
     *
     * @dataProvider testPrepareInputForAddProvider
     *
     * @param array $input
     * @param array|false $expected_fields
     *
     * @return void
     */
    public function testPrepareInputForAdd(
        array $input,
        array|false $expected_fields,
        ?string $warning = null
    ): void {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();
        $form_access_control->input = $input;

        $prepared_input = $form_access_control->prepareInputForAdd($input);

        $run = function () use ($prepared_input, $expected_fields) {
            if ($expected_fields == false) {
                $this->boolean($prepared_input)->isFalse();
            } else {
                $this->array($prepared_input)->isEqualTo($expected_fields);
            }
        };

        if (!is_null($warning)) {
            $this->when($run)
                ->error()
                ->withMessage($warning)
                ->withType(E_USER_WARNING)
                ->exists()
            ;
        } else {
            $run();
        }
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
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        // Make sure `_no_message_link` is added to the fields
        $prepared_input = $form_access_control->prepareInputForUpdate([
            'strategy' => DirectAccess::class,
            '_config'  => new DirectAccessConfig(['token' => "my_token"]),
        ]);
        $this->array($prepared_input)->isEqualTo([
            '_no_message_link' => true,
            'strategy'         => DirectAccess::class,
            'config'           => json_encode(new DirectAccessConfig(['token' => "my_token"]))
        ]);
    }

    /**
     * Test the `getStrategy` method.
     *
     * @return void
     */
    public function testGetStrategy(): void
    {
        $form_access_control = new \Glpi\Form\AccessControl\FormAccessControl();

        // Read strategy from field
        $form_access_control->fields = ['strategy' => DirectAccess::class];
        $strategy = $form_access_control->getStrategy();
        $this->object($strategy)->isInstanceOf(DirectAccess::class);
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
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token'                 => 'my_token',
                    'allow_unauthenticated' => true,
                    'force_direct_access'   => true,
                ]))
                ->addAccessControl(AllowList::class, new AllowListConfig([
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
                ]))
        );

        // Check config of the direct access policy
        $access_control_1 = $this->getAccessControl($form, DirectAccess::class);
        $this->string(json_encode($access_control_1->getConfig()))
            ->isEqualTo(json_encode([
                'token'                 => 'my_token',
                'allow_unauthenticated' => true,
                'force_direct_access'   => true,
            ]))
        ;

        // Check config of the allow list policy
        $access_control_2 = $this->getAccessControl($form, AllowList::class);
        $this->string(json_encode($access_control_2->getConfig()))
            ->isEqualTo(json_encode([
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
            ]))
        ;
    }
}
