<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControl;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

final class FormAccessControlManagerTest extends DbTestCase
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
        $this->assertInstanceOf(FormAccessControlManager::class, $instance);
    }

    public function testCreateMisingAccessControlsForForm(): void
    {
        $manager = $this->getManager();
        $form = $this->createAndGetFormWithoutAccessControls();

        $manager->createMissingAccessControlsForForm($form);
        $this->assertCount(2, $form->getAccessControls());
    }

    public function testCreateMisingAccessControlsForFormThatAlreadyHasAccessPolicies(): void
    {
        $manager = $this->getManager();
        $form = $this->createAndGetFormWithActiveAccessControls();

        // This test ensure that we don't create duplicate access controls.
        // If getFormWithActiveAccessControls try to recreate the existing
        // access controls, there will be an SQL unicity constraint error.
        $manager->createMissingAccessControlsForForm($form);
        $this->assertCount(2, $form->getAccessControls());
    }

    public function testGetActiveAccessControlsForFormWithoutPolicies(): void
    {
        $form = $this->createAndGetFormWithoutAccessControls();
        $this->checkGetActiveAccessControlsForForm(
            form: $form,
            expected_access_controls: [],
        );
    }

    public function testGetActiveAccessControlsForFormWithActivePolicies(): void
    {
        $form = $this->createAndGetFormWithActiveAccessControls();
        $this->checkGetActiveAccessControlsForForm(
            form: $form,
            expected_access_controls: [AllowList::class, DirectAccess::class],
        );
    }

    public function testGetActiveAccessControlsForFormWithDisabledPolicies(): void
    {
        $form = $this->createAndGetFormWithOneDisabledAndOneActiveAccessControls();
        $this->checkGetActiveAccessControlsForForm(
            form: $form,
            expected_access_controls: [DirectAccess::class],
        );
    }

    private function checkGetActiveAccessControlsForForm(
        Form $form,
        array $expected_access_controls
    ): void {
        $manager = $this->getManager();
        $controls = $manager->getActiveAccessControlsForForm($form);

        $active_controls = array_map(
            fn (FormAccessControl $control) => $control->fields['strategy'],
            $controls
        );

        $this->assertEquals($expected_access_controls, $active_controls);
    }

    public static function sortAccessControlsProvider(): iterable
    {
        // Weights reminder: Access control: 10 < Direct access: 20
        yield 'No access controls' => [
            'access_controls' => [],
            'expected'        => [],
        ];
        yield 'Form with two disabled access controls' => [
            'access_controls' => [
                static::getInactiveAllowListAccessControl(),
                static::getInactiveDirectAccessControl(),
            ],
            'expected' => [AllowList::class, DirectAccess::class],
        ];
        yield 'Form with one active access controls' => [
            'access_controls' => [
                static::getInactiveAllowListAccessControl(),
                static::getActiveDirectAccessControl(),
            ],
            'expected' => [DirectAccess::class, AllowList::class],
        ];
        yield 'Form with two active access controls' => [
            'access_controls' => [
                static::getActiveAllowListAccessControl(),
                static::getActiveDirectAccessControl(),
            ],
            'expected' => [AllowList::class, DirectAccess::class],
        ];
    }

    #[DataProvider('sortAccessControlsProvider')]
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
        $this->assertEquals($expected, $sorted_controls);
    }

    public function testAdminCanBypassFormRestrictions(): void
    {
        $form = $this->getFormAccessibleOnlyToTechUser();
        $access_parameters = new FormAccessParameters(
            bypass_restriction: true,
        );

        $this->assertTrue(
            $this->getManager()->canAnswerForm($form, $access_parameters)
        );
    }

    public function testFormWithoutRestrictionCantBeAnswered(): void
    {
        $form = $this->createForm(new FormBuilder());
        $access_parameters = $this->getEmptyParameters();

        $this->assertFalse(
            $this->getManager()->canAnswerForm($form, $access_parameters)
        );
    }

    public function testInvalidTokenAndInvalidUserMustFail(): void
    {
        $this->checkCanAnswerForm(
            form: $this->createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken(),
            parameters: self::getEmptyParameters(),
            expected: false,
        );
    }

    public function testInvalidTokenAndValidUserMustSucceed(): void
    {
        $this->checkCanAnswerForm(
            form: $this->createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken(),
            parameters: self::getTechUserParameters(),
            expected: true,
        );
    }

    public function testValidTokenAndInvalidUserMustSucceed(): void
    {
        $this->checkCanAnswerForm(
            form: $this->createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken(),
            parameters: self::getValidTokenParameters(),
            expected: true,
        );
    }

    public function testValidTokenAndValidUserMustSucceed(): void
    {
        $this->checkCanAnswerForm(
            form: $this->createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken(),
            parameters: self::getTechUserAndValidTokenParameters(),
            expected: true,
        );
    }

    private function checkCanAnswerForm(
        Form $form,
        FormAccessParameters $parameters,
        bool $expected
    ): void {
        $this->assertEquals(
            $expected,
            $this->getManager()->canAnswerForm($form, $parameters)
        );
    }

    public static function computeVotesProvider(): iterable
    {
        yield "Vote must fail if there is one 'Deny' vote" => [
            'votes' => [AccessVote::Grant, AccessVote::Abstain, AccessVote::Deny],
            'expected' => false,
        ];
        yield "Vote must succeed if there is at least on 'grant' and no 'deny' vote" => [
            'votes' => [AccessVote::Grant, AccessVote::Abstain, AccessVote::Abstain],
            'expected' => true,
        ];
        yield "Vote must fail if there there are no 'grant' votes" => [
            'votes' => [AccessVote::Abstain, AccessVote::Abstain, AccessVote::Abstain],
            'expected' => false,
        ];
    }

    #[DataProvider('computeVotesProvider')]
    public function testComputeVote(array $votes, bool $expected): void
    {
        $manager = $this->getManager();
        $this->assertEquals(
            $expected,
            // Easier to call directly the private method to validate this behavior.
            // Indeed, we offer no stategy with the Deny vote in GLPI core so
            // we can't test it with the public API without heavy mocking.
            $this->callPrivateMethod($manager, 'computeVote', $votes)
        );
    }

    public function testGetWarningForInactiveFormWithoutAccessControlPolicies(): void
    {
        $form = $this->createForm((new FormBuilder())->setIsActive(false));
        $this->checkGetWarnings($form, [
            'This form is not visible to anyone because it is not active.',
            'This form will not be visible to any users as there are currently no active access policies.',
        ]);
    }

    public function testGetWarningForActiveFormWithoutAccessControlPolicies(): void
    {
        $form = $this->createForm((new FormBuilder())->setIsActive(true));
        $this->checkGetWarnings($form, [
            'This form will not be visible to any users as there are currently no active access policies.',
        ]);
    }

    public function testGetWarningForInactiveFormWithAccessControlPolicies(): void
    {
        $this->checkGetWarnings($this->getInactiveFormWithActiveAccessControls(), [
            'This form is not visible to anyone because it is not active.',
        ]);
    }

    public function testGetWarningForActiveFormWithAccessControlPolicies(): void
    {
        $this->checkGetWarnings($this->getActiveFormWithActiveAccessControls(), []);
    }

    public function testGetWarningForInactiveFormWithInactiveAccessControlPolicies(): void
    {
        $this->checkGetWarnings($this->getInactiveFormWithInactiveAccessControls(), [
            'This form is not visible to anyone because it is not active.',
            'This form will not be visible to any users as there are currently no active access policies.',
        ]);
    }

    public function testGetWarningForActiveFormWithInactiveAccessControlPolicies(): void
    {
        $this->checkGetWarnings($this->getActiveFormWithInactiveAccessControls(), [
            'This form will not be visible to any users as there are currently no active access policies.',
        ]);
    }

    private function checkGetWarnings(Form $form, array $expected): void
    {
        $this->assertEquals($expected, $this->getManager()->getWarnings($form));
    }

    private function getManager(): FormAccessControlManager
    {
        return FormAccessControlManager::getInstance();
    }

    private function getFormAccessibleOnlyToTechUser(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ))
        );
    }

    private function getActiveFormWithActiveAccessControls(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->setIsActive(true)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );
    }

    private function getInactiveFormWithActiveAccessControls(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->setIsActive(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );
    }

    private function getActiveFormWithInactiveAccessControls(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->setIsActive(true)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );

        $control = $this->getAccessControl($form, DirectAccess::class);
        $this->updateItem($control::class, $control->getID(), ['is_active' => 0]);
        $form->getFromDB($form->getID());

        return $form;
    }

    private function getInactiveFormWithInactiveAccessControls(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->setIsActive(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );

        $control = $this->getAccessControl($form, DirectAccess::class);
        $this->updateItem($control::class, $control->getID(), ['is_active' => 0]);
        $form->getFromDB($form->getID());

        return $form;
    }

    private function createAndGetFormWithoutAccessControls(): Form
    {
        return $this->createForm(new FormBuilder());
    }

    private function createAndGetFormWithActiveAccessControls(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );
    }

    private function createAndGetFormWithOneDisabledAndOneActiveAccessControls(): Form
    {
        $form = $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );

        $control = self::getAccessControl($form, AllowList::class);
        self::updateItem($control::class, $control->getID(), ['is_active' => 0]);
        $form->getFromDB($form->getID());

        return $form;
    }

    private function createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->addAccessControl(AllowList::class, new AllowListConfig(
                    user_ids: [
                        getItemByTypeName(User::class, "tech", true),
                    ],
                ))
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                ))
        );
    }

    private static function getActiveAllowListAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy'  => AllowList::class,
            'is_active' => 1,
        ];
        return $control;
    }

    private static function getInactiveAllowListAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy'  => AllowList::class,
            'is_active' => 0,
        ];
        return $control;
    }

    private static function getActiveDirectAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy' => DirectAccess::class,
            'is_active' => 1,
        ];
        return $control;
    }

    private static function getInactiveDirectAccessControl()
    {
        $control = new FormAccessControl();
        $control->fields = [
            'strategy' => DirectAccess::class,
            'is_active' => 0,
        ];
        return $control;
    }

    private static function getEmptyParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: self::getEmptySessionInfo(),
            url_parameters: []
        );
    }

    private static function getTechUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: self::getTechUserSessionInfo(),
            url_parameters: []
        );
    }

    private static function getValidTokenParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: self::getEmptySessionInfo(),
            url_parameters: self::getUrlParametersWithValidToken(),
        );
    }

    private static function getTechUserAndValidTokenParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: self::getTechUserSessionInfo(),
            url_parameters: self::getUrlParametersWithValidToken(),
        );
    }

    private static function getEmptySessionInfo(): SessionInfo
    {
        // This session info should not match any user
        return new SessionInfo(
            user_id: 0,
            group_ids: [],
            profile_id: 0,
        );
    }

    private static function getTechUserSessionInfo(): SessionInfo
    {
        $tech_user = getItemByTypeName(User::class, "tech");
        return new SessionInfo(
            user_id: $tech_user->getID(),
            group_ids: [],
            profile_id: 6, // Technician profile
        );
    }

    private static function getUrlParametersWithValidToken(): array
    {
        return ['token' => 'my_token'];
    }
}
