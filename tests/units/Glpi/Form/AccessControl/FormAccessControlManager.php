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
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use Profile;
use User;

final class FormAccessControlManager extends DbTestCase
{
    use FormTesterTrait;

    /**
     * Test the `getInstance` method.
     *
     * @return void
     */
    public function testGetInstance(): void
    {
        $instance = $this->getManager();

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
    public function getAccessControlsForFormDataProvider(): iterable
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
     * @dataProvider getAccessControlsForFormDataProvider
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
        $controls = $this->getManager()->getAccessControlsForForm(
            $form,
            $only_active
        );
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

    public function testAdminCanBypassFormRestrictions(): void
    {
        $this->login('glpi', 'glpi');
        $form = $this->getFormAccessibleOnlyToTechUser();
        $access_parameters = $this->getEmptyParameters();

        $this->boolean(
            $this->getManager()->canAnswerForm($form, $access_parameters)
        )->isTrue();
    }

    public function testFormWithoutRestrictionCantBeAnswered(): void
    {
        $form = $this->createForm(new FormBuilder());
        $access_parameters = $this->getEmptyParameters();

        $this->boolean(
            $this->getManager()->canAnswerForm($form, $access_parameters)
        )->isFalse();
    }

    public function formWithMultipleRestrictionsProvider(): iterable
    {
        $form = $this->getFormAccessibleOnlyToTechUserWithMandatoryToken();

        yield 'Both parameters invalid' => [
            'form'       => $form,
            'parameters' => $this->getEmptyParameters(),
            'expected'   => false,
        ];
        yield 'Valid user and no token supplied' => [
            'form'       => $form,
            'parameters' => $this->getTechUserParameters(),
            'expected'   => false,
        ];
        yield 'Invalid user and valid token' => [
            'form'       => $form,
            'parameters' => $this->getValidTokenParameters(),
            'expected'   => false,
        ];
        yield 'Valid user and valid token' => [
            'form'       => $form,
            'parameters' => $this->getTechUserAndValidTokenParameters(),
            'expected'   => true,
        ];
    }

    /**
     * @dataProvider formWithMultipleRestrictionsProvider
     */
    public function testFormWithMultipleRestrictions(
        Form $form,
        FormAccessParameters $parameters,
        bool $expected
    ): void {
        $this->boolean(
            $this->getManager()->canAnswerForm($form, $parameters)
        )->isEqualTo($expected);
    }

    private function getManager(): \Glpi\Form\AccessControl\FormAccessControlManager
    {
        return \Glpi\Form\AccessControl\FormAccessControlManager::getInstance();
    }

    private function getFormAccessibleOnlyToTechUser(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig([
                    'user_ids' => [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ]))
        );
    }

    private function getFormAccessibleOnlyToTechUserWithMandatoryToken(): Form
    {
        return $this->createForm(
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
    }

    private function getEmptyParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: $this->getEmptySessionInfo(),
            url_parameters: []
        );
    }

    private function getTechUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: $this->getTechUserSessionInfo(),
            url_parameters: []
        );
    }

    private function getValidTokenParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: $this->getEmptySessionInfo(),
            url_parameters: $this->getUrlParametersWithValidToken(),
        );
    }

    private function getTechUserAndValidTokenParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: $this->getTechUserSessionInfo(),
            url_parameters: $this->getUrlParametersWithValidToken(),
        );
    }

    private function getEmptySessionInfo(): SessionInfo
    {
        // This session info should not match any user
        return new SessionInfo(
            user_id: 0,
            group_ids: [],
            profile_id: 0,
        );
    }

    private function getTechUserSessionInfo(): SessionInfo
    {
        $tech_user = getItemByTypeName(User::class, "tech");
        return new SessionInfo(
            user_id: $tech_user->getID(),
            group_ids: [],
            profile_id: 6, // Technician profile
        );
    }

    private function getUrlParametersWithValidToken(): array
    {
        return ['token' => 'my_token'];
    }
}
