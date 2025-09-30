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
use GlpiPlugin\Tester\Form\DayOfTheWeekPolicy;
use GlpiPlugin\Tester\Form\DayOfTheWeekPolicyConfig;
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
        $this->assertCount(3, $form->getAccessControls()); // 2 from core + 1 from plugins
    }

    public function testCreateMisingAccessControlsForFormThatAlreadyHasAccessPolicies(): void
    {
        $manager = $this->getManager();
        $form = $this->createAndGetFormWithActiveAccessControls();

        // This test ensure that we don't create duplicate access controls.
        // If getFormWithActiveAccessControls try to recreate the existing
        // access controls, there will be an SQL unicity constraint error.
        $manager->createMissingAccessControlsForForm($form);
        $this->assertCount(3, $form->getAccessControls()); // 2 from core + 1 from plugins
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
            fn(FormAccessControl $control) => $control->fields['strategy'],
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
            fn(FormAccessControl $control) => $control->fields['strategy'],
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
        $builder = new FormBuilder();
        $builder->setUseDefaultAccessPolicies(false);
        $form = $this->createForm($builder);
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

    public function testDeletedFormCannotBeAnswered(): void
    {
        // Create a form with active access controls
        $form = $this->createAndGetFormAccessibleOnlyToTechUserWithMandatoryToken();

        // Ensure the form can be answered before deletion
        $this->checkCanAnswerForm(
            form: $form,
            parameters: self::getTechUserAndValidTokenParameters(),
            expected: true,
        );

        // Mark the form as deleted by setting is_deleted = 1
        $this->updateItem(
            Form::class,
            $form->getID(),
            ['is_deleted' => 1]
        );

        // Reload the form to get the updated state
        $form->getFromDB($form->getID());

        // Verify that the form cannot be answered when deleted
        $this->checkCanAnswerForm(
            form: $form,
            parameters: self::getTechUserAndValidTokenParameters(),
            expected: false,
        );

        // Also test with admin bypass
        $admin_parameters = new FormAccessParameters(
            bypass_restriction: true,
        );
        $this->checkCanAnswerForm(
            form: $form,
            parameters: $admin_parameters,
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
        $builder = new FormBuilder();
        $builder->setIsActive(false);
        $builder->setUseDefaultAccessPolicies(false);
        $form = $this->createForm($builder);
        $this->checkGetWarnings($form, [
            'This form is not visible to anyone because it is not active.',
            'This form will not be visible to any users as there are currently no active access policies.',
        ]);
    }

    public function testGetWarningForActiveFormWithoutAccessControlPolicies(): void
    {
        $builder = new FormBuilder();
        $builder->setIsActive(true);
        $builder->setUseDefaultAccessPolicies(false);
        $form = $this->createForm($builder);
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

    public static function accessPoliciesFromPluginsAreTakenIntoAccountProvider(): iterable
    {
        yield 'On a wednesday' => [
            "date"     => "2025-03-18 10:45:00",
            "expected" => false,
        ];

        yield 'On a friday' => [
            "date"     => "2025-03-21 10:45:00",
            "expected" => true,
        ];
    }

    #[DataProvider('accessPoliciesFromPluginsAreTakenIntoAccountProvider')]
    public function testAccessPoliciesFromPluginsAreTakenIntoAccount(
        string $date,
        bool $expected,
    ): void {
        // Arrange: create a form with a plugin policy
        $builder = new FormBuilder();
        $builder->addAccessControl(
            DayOfTheWeekPolicy::class,
            new DayOfTheWeekPolicyConfig("Friday"),
        );
        $form = $this->createForm($builder);

        // Act: try to access the form on the given date
        $_SESSION['glpi_currenttime'] = $date;
        $can_access = $this->getManager()->canAnswerForm(
            $form,
            self::getTechUserParameters(),
        );

        // Assert
        $this->assertEquals($expected, $can_access);
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
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
        $builder = new FormBuilder();
        $builder->setUseDefaultAccessPolicies(false);
        return $this->createForm($builder);
    }

    private function createAndGetFormWithActiveAccessControls(): Form
    {
        return $this->createForm(
            (new FormBuilder())
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
                ->setUseDefaultAccessPolicies(false)
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
