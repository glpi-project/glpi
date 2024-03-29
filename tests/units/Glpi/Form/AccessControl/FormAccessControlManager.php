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
use Group_User;
use Profile;
use User;

class FormAccessControlManager extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the `getInstance` method.
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $instance = \Glpi\Form\AccessControl\FormAccessControlManager::getInstance();

        // Not much to test here, just make sure the method run without errors
        $this
            ->object($instance)
            ->isInstanceOf(\Glpi\Form\AccessControl\FormAccessControlManager::class)
        ;
    }


    /**
     * Data provider for the `testGetAccessControlsForForm` test.
     *
     * @return iterable
     */
    protected function testGetAccessControlsForFormDataProvider(): iterable
    {
        // Form without access controls
        $form_1 = $this->createForm(new FormBuilder());
        yield [
            'form'                     => $form_1,
            'only_active'              => true,
            'expected_access_controls' => [],
        ];
        yield [
            'form'                     => $form_1,
            'only_active'              => false, // Trigger lazy creation
            'expected_access_controls' => [
                [
                    'forms_forms_id' => $form_1->getID(),
                    'strategy'       => AllowList::class,
                    'config'         => json_encode(new AllowListConfig([])), // Default config
                    'is_active'      => 0,
                ],
                [
                    'forms_forms_id' => $form_1->getID(),
                    'strategy'       => DirectAccess::class,
                    'config'         => json_encode(new DirectAccessConfig([])), // Default config
                    'is_active'      => 0,
                    '_ignore_token'  => true, // Token is randomly generated, we can't test its value
                ],
            ],
        ];

        // Still no active access controls
        yield [
            'form'                     => $form_1,
            'only_active'              => true,
            'expected_access_controls' => [],
        ];

        // Set one policy as active
        $this->updateItem(
            FormAccessControl::class,
            $this->getAccessControl($form_1, AllowList::class)->getID(),
            ['is_active' => 1]
        );
        yield [
            'form'                     => $form_1,
            'only_active'              => true,
            'expected_access_controls' => [
                [
                    'forms_forms_id' => $form_1->getID(),
                    'strategy'       => AllowList::class,
                    'config'         => json_encode(new AllowListConfig([])), // Default config
                    'is_active'      => 1, // Must now be active
                ]
            ],
        ];

        // Update the configuration
        $new_config = new AllowListConfig([
            'user_ids' => [1, 2, 3]
        ]);
        $this->updateItem(
            FormAccessControl::class,
            $this->getAccessControl($form_1, AllowList::class)->getID(),
            ['_config' => $new_config]
        );
        yield [
            'form'                     => $form_1,
            'only_active'              => true,
            'expected_access_controls' => [
                [
                    'forms_forms_id' => $form_1->getID(),
                    'strategy'       => AllowList::class,
                    'config'         => json_encode($new_config),
                    'is_active'      => 1, // Must now be active
                ]
            ],
        ];

        // Create a second form with policies already defined
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
        yield [
            'form'                     => $form_2,
            'only_active'              => true,
            'expected_access_controls' => [
                [
                    'forms_forms_id' => $form_2->getID(),
                    'strategy'       => DirectAccess::class,
                    'is_active'      => 1,
                    'config'         => json_encode(new DirectAccessConfig([
                        'token'                 => 'my_token',
                        'allow_unauthenticated' => true,
                        'force_direct_access'   => true,
                    ])),
                ],
                [
                    'forms_forms_id' => $form_2->getID(),
                    'strategy'       => AllowList::class,
                    'is_active'      => 1,
                    'config'         => json_encode(new AllowListConfig([
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
                    ])),
                ],
            ],
        ];
    }

    /**
     * Test the `getAccessControlsForForm` method
     *
     * @dataProvider testGetAccessControlsForFormDataProvider
     *
     * @param Form $form
     * @param bool $only_active
     * @param array $expected_access_controls
     *
     * @return void
     */
    public function testGetAccessControlsForForm(
        Form $form,
        bool $only_active,
        array $expected_access_controls,
    ): void {
        $manager = \Glpi\Form\AccessControl\FormAccessControlManager::getInstance();

        $controls = $manager->getAccessControlsForForm($form, $only_active);
        $this->array($controls)->hasSize(count($expected_access_controls));

        // Ensure both array are sorted in the same way to get a proper comparison.
        usort($controls, function ($a, $b) {
            return $a->fields['strategy'] <=> $b->fields['strategy'];
        });
        usort($expected_access_controls, function ($a, $b) {
            return $a['strategy'] <=> $b['strategy'];
        });

        foreach ($controls as $i => $control) {
            $this->object($control)->isInstanceOf(FormAccessControl::class);

            // Unset fields that can't be compared properly, such as IDs.
            unset($control->fields['id']);

            // We need to compare decoded JSON objects to ensure a
            // proper comparison with no false negative.
            $control->fields['config'] = json_decode($control->fields['config']);
            $expected_access_controls[$i]['config'] = json_decode($expected_access_controls[$i]['config']);

            if (isset($expected_access_controls[$i]['_ignore_token'])) {
                // We can't compare randomnly generated token values,
                // thus we remove it from the expected array.
                unset($control->fields['config']->token);
                unset($expected_access_controls[$i]['config']->token);

                // Remove special flag.
                unset($expected_access_controls[$i]['_ignore_token']);
            }

            $this
                ->array($control->fields)
                ->isEqualTo($expected_access_controls[$i])
            ;
        }
    }

    /**
     * Data provider for the `testCanUnauthenticatedUsersAccessForm` test.
     *
     * @return iterable
     */
    protected function testCanUnauthenticatedUsersAccessFormProvider(): iterable
    {
        // Form without access controls
        $form_1 = $this->createForm(new FormBuilder());
        yield [
            'form'     => $form_1,
            'expected' => false,
        ];

        // Form with the default DirectAccess policy
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([])) // Default config
        );
        yield [
            'form'     => $form_2,
            'expected' => false,
        ];

        // Form with a DirectAccess policy that don't allow unauthenticated users.
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'allow_unauthenticated' => false, // Disabled
                ]))
        );
        yield [
            'form'     => $form_2,
            'expected' => false,
        ];

        // Form with a DirectAccess policy that allow unauthenticated users.
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'allow_unauthenticated' => true,         // Enabled
                    'token'                 => 'my_token',
                ]))
        );
        yield [
            'form'     => $form_2,
            'expected' => false, // No token is supplied
        ];

        // Form with a DirectAccess policy that allow unauthenticated users.
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'allow_unauthenticated' => true,         // Enabled
                    'token'                 => 'my_token',
                ]))
        );
        $_GET['token'] = 'invalid_token';
        yield [
            'form'     => $form_2,
            'expected' => false, // Invalid token is supplied
        ];

        // Form with a DirectAccess policy that allow unauthenticated users.
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'allow_unauthenticated' => true,         // Enabled
                    'token'                 => 'my_token',
                ]))
        );
        $_GET['token'] = 'my_token';
        yield [
            'form'     => $form_2,
            'expected' => true, // Valid token is supplied
        ];
        unset($_GET['token']);
    }

    /**
     * Test the `canUnauthenticatedUsersAccessForm` method.
     *
     * @dataProvider testCanUnauthenticatedUsersAccessFormProvider
     *
     * @param Form $form
     * @param bool $expected
     *
     * @return void
     */
    public function testCanUnauthenticatedUsersAccessForm(
        Form $form,
        bool $expected
    ): void {
        $manager = \Glpi\Form\AccessControl\FormAccessControlManager::getInstance();

        $this->boolean(
            $manager->canUnauthenticatedUsersAccessForm($form)
        )->isEqualTo($expected);
    }

    /**
     * Data provider for the `testCanAnswerForm` test.
     *
     * @return iterable
     */
    protected function testCanAnswerFormProvider(): iterable
    {
        // Form without access controls -> should be visible for all authenticated users
        $form_1 = $this->createForm(new FormBuilder());
        yield [
            'form'     => $form_1,
            'expected' => [
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
        ];

        // Form with a direct access control restriction
        $form_2 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token' => 'my_token'
                ]))
        );
        yield [
            // Form is still accessible without token
            'form'     => $form_2,
            'expected' => [
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
        ];

        // Form with a direct access control restriction
        $form_3 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token'               => 'my_token',
                    'force_direct_access' => true,
                ]))
        );
        yield [
            // Form is NOT accessible without token
            'form'     => $form_3,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => false,
                'normal'    => false,
                'post-only' => false,
            ],
        ];
        $_GET['token'] = 'invalid_token';
        yield [
            // Wrong token
            'form'     => $form_3,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => false,
                'normal'    => false,
                'post-only' => false,
            ],
        ];
        $_GET['token'] = 'my_token';
        yield [
            // Valid token
            'form'     => $form_3,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
        ];
        unset($_GET['token']);

        // Form with an allow list
        $form_4 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig([
                    'user_ids' => [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                    'group_ids' => [
                        getItemByTypeName(Group::class, "_test_group_1", true),
                    ],
                    'profile_ids' => [
                        getItemByTypeName(Profile::class, "Self-service", true),
                    ],
                ]))
        );
        yield [
            'form'     => $form_4,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => true, // Allowed by user list
                'normal'    => false,
                'post-only' => true, // Allowed by profile list
            ],
        ];

        // Add "normal" to the allowed group
        $this->createItem(Group_User::class, [
            'groups_id' => getItemByTypeName(Group::class, "_test_group_1", true),
            'users_id'  => getItemByTypeName(User::class, "normal", true),
        ]);
        yield [
            'form'     => $form_4,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => true, // Allowed by user list
                'normal'    => true, // Allowed by group list
                'post-only' => true, // Allowed by profile list
            ],
        ];

        // Mix allow list + token usage
        $form_5 = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig([
                    'user_ids' => [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ]))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token'               => 'my_token',
                    'force_direct_access' => true,
                ]))
        );
        yield [
            'form'     => $form_5,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => false, // Allowed by user list but token is missing
                'normal'    => false,
                'post-only' => false,
            ],
        ];

        $_GET['token'] = 'my_token';
        yield [
            'form'     => $form_5,
            'expected' => [
                'glpi'      => true, // Admin can see all forms
                'tech'      => true, // Allowed by user list and token is valid
                'normal'    => false,
                'post-only' => false,
            ],
        ];
        unset($_GET['token']);
    }

    /**
     * Test the `canAnswerForm` method.
     *
     * @dataProvider testCanAnswerFormProvider
     *
     * @param Form $form
     * @param array $expected
     *
     * @return void
     */
    public function testCanAnswerForm(
        Form $form,
        array $expected
    ): void {
        $manager = \Glpi\Form\AccessControl\FormAccessControlManager::getInstance();

        foreach ($expected as $user => $can_answer) {
            $this->login($user, str_replace("-", "", $user));
            $this->boolean(
                $manager->canAnswerForm($form)
            )->isEqualTo($can_answer, "Failed for $user.");
        }
    }
}
