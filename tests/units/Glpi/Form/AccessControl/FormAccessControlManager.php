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
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
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

    public function testCreateMisingAccessControlsForForm(): void
    {
        $manager = $this->getManager();
        $form = $this->getFormWithoutAccessControls();

        $manager->createMissingAccessControlsForForm($form);
        $this->array($form->getAccessControls())->hasSize(2);
    }

    public function testCreateMisingAccessControlsForFormThatAlreadyHasAccess(): void
    {
        $manager = $this->getManager();
        $form = $this->getFormWithActiveAccessControls();

        // This test ensure that we don't create duplicate access controls.
        // If getFormWithActiveAccessControls try to recreate the existing
        // access controls, there will be an SQL unicity constraint error.
        $manager->createMissingAccessControlsForForm($form);
        $this->array($form->getAccessControls())->hasSize(2);
    }

    public function getActiveAccessControlsForFormProvider(): iterable
    {
        yield 'Form without access controls' => [
            'form'     => $this->getFormWithoutAccessControls(),
            'expected' => [],
        ];

        yield 'Form with active access controls' => [
            'form'     => $this->getFormWithActiveAccessControls(),
            'expected' => [AllowList::class, DirectAccess::class],
        ];

        yield 'Form with one active and one disabled access controls' => [
            'form'     => $this->getFormWithOneDisabledAndOneActiveAccessControls(),
            'expected' => [DirectAccess::class],
        ];
    }

    /**
     * @dataProvider getActiveAccessControlsForFormProvider
     */
    public function testGetActiveAccessControlsForForm(
        Form $form,
        array $expected
    ): void {
        $manager = $this->getManager();
        $controls = $manager->getActiveAccessControlsForForm($form);

        $active_controls = array_map(
            fn (FormAccessControl $control) => $control->fields['strategy'],
            $controls
        );

        $this->array($active_controls)->isEqualTo($expected);
    }

    public function sortAccessControlsProvider(): iterable
    {
        // Weights reminder: Access control: 10 < Direct access: 20
        yield 'No access controls' => [
            'access_controls' => [],
            'expected'        => [],
        ];
        yield 'Form with two disabled access controls' => [
            'access_controls' => [
                $this->getInactiveAllowListAccessControl(),
                $this->getInactiveDirectAccessControl(),
            ],
            'expected' => [AllowList::class, DirectAccess::class],
        ];
        yield 'Form with one active access controls' => [
            'access_controls' => [
                $this->getInactiveAllowListAccessControl(),
                $this->getActiveDirectAccessControl(),
            ],
            'expected' => [DirectAccess::class, AllowList::class],
        ];
        yield 'Form with two active access controls' => [
            'access_controls' => [
                $this->getActiveAllowListAccessControl(),
                $this->getActiveDirectAccessControl(),
            ],
            'expected' => [AllowList::class, DirectAccess::class],
        ];
    }

    /**
     * @dataProvider sortAccessControlsProvider
     */
    public function testSortAccessControls(
        array $access_controls,
        array $expected
    ): void {
        $manager = $this->getManager();
        $sorted_controls = $manager->sortAccessControls($access_controls);
        $sorted_controls = array_map(
            fn (FormAccessControl $control) => $control->fields['strategy'],
            $sorted_controls
        );
        $this->array($sorted_controls)->isEqualTo($expected);
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

    private function getFormWithoutAccessControls(): Form
    {
        return $this->createForm(new FormBuilder());
    }

    private function getFormWithActiveAccessControls(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig([
                    'user_ids' => [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ]))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token' => 'my_token',
                ]))
        );
    }

    private function getFormWithOneDisabledAndOneActiveAccessControls(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig([
                    'user_ids' => [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ]))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig([
                    'token' => 'my_token',
                ]))
        );

        $control = $this->getAccessControl($form, AllowList::class);
        $this->updateItem($control::class, $control->getID(), ['is_active' => 0]);
        $form->getFromDB($form->getID());

        return $form;
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
                    'token' => 'my_token',
                ]))
        );
    }

    private function getActiveAllowListAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy'  => AllowList::class,
            'is_active' => 1,
        ];
        return $control;
    }

    private function getInactiveAllowListAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy'  => AllowList::class,
            'is_active' => 0,
        ];
        return $control;
    }

    private function getActiveDirectAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy' => DirectAccess::class,
            'is_active' => 1,
        ];
        return $control;
    }

    private function getInactiveDirectAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy' => DirectAccess::class,
            'is_active' => 0,
        ];
        return $control;
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
