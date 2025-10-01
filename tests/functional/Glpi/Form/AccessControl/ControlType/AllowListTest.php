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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Group;
use PHPUnit\Framework\Attributes\DataProvider;

class AllowListTest extends \DbTestCase
{
    use FormTesterTrait;

    public function testGetLabel(): void
    {
        $allow_list = new AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($allow_list->getLabel());
    }

    public function testGetIcon(): void
    {
        $allow_list = new AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($allow_list->getIcon());
    }

    public function testGetConfig(): void
    {
        $allow_list = new AllowList();

        // Not much to test here, just ensure the method run without errors
        $class = $allow_list->getConfig();
        $this->assertNotEmpty($class);

        // Ensure the class exists and is valid
        $is_valid
            = is_a($class, JsonFieldInterface::class)
            && !(new \ReflectionClass($class))->isAbstract()
        ;
        $this->assertTrue($is_valid);
    }

    public function testRenderConfigForm(): void
    {
        $allow_list = new AllowList();

        // We only validate that the function run without errors.
        // The rendered content should be validated by an E2E test.
        $form = $this->createForm(
            (new FormBuilder())
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(
                    AllowList::class,
                    $this->getFullyConfiguredAllowListConfig()
                )
        );
        $access_control = $this->getAccessControl(
            $form,
            AllowList::class
        );
        $this->assertNotEmpty($allow_list->renderConfigForm($access_control));
    }

    public function testGetWeight(): void
    {
        $allow_list = new AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->assertGreaterThan(0, $allow_list->getWeight());
    }

    public function testCreateConfigFromUserInput(): void
    {
        $allow_list = new AllowList();

        // Test default fallback values
        $config = $allow_list->createConfigFromUserInput([]);
        $this->assertInstanceOf(AllowListConfig::class, $config);
        $this->assertEquals([], $config->getUserIds());
        $this->assertEquals([], $config->getGroupIds());
        $this->assertEquals([], $config->getProfileIds());

        // Test user supplied values
        $config = $allow_list->createConfigFromUserInput([
            '_allow_list_dropdown' => [
                'users_id-1',
                'users_id-2',
                'users_id-3',
                'groups_id-4',
                'groups_id-5',
                'groups_id-6',
                'profiles_id-7',
                'profiles_id-8',
                'profiles_id-9',
            ],
        ]);
        $this->assertInstanceOf(AllowListConfig::class, $config);
        $this->assertEquals([1, 2, 3], $config->getUserIds());
        $this->assertEquals([4, 5, 6], $config->getGroupIds());
        $this->assertEquals([7, 8, 9], $config->getProfileIds());
    }

    public static function canAnswerProvider(): iterable
    {
        $test_group_1_id = getItemByTypeName(Group::class, '_test_group_1', true);
        $test_group_2_id = getItemByTypeName(Group::class, '_test_group_2', true);

        yield 'Abstain if allow list is empty' => [
            'config'     => self::getEmptyAllowList(),
            'parameters' => self::getAuthenticatedUserParameters(),
            'expected'   => AccessVote::Abstain,
        ];
        yield 'Abstain if user is unauthenticated' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getUnauthenticatedUserParameters(),
            'expected'   => AccessVote::Abstain,
        ];
        yield 'Grant access to specifically allowed user' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getDirectlyAllowedUserParameters(),
            'expected'   => AccessVote::Grant,
        ];
        yield 'Abstain if user is not specifically allowed' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getNotDirectlyAllowedUserParameters(),
            'expected'   => AccessVote::Abstain,
        ];
        yield 'Grant access to specifically allowed group' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getAllowedUserByGroupParameters(),
            'expected'   => AccessVote::Grant,
        ];
        yield 'Abstain if group is not specifically allowed' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getNotAllowedUserByGroupParameters(),
            'expected'   => AccessVote::Abstain,
        ];
        yield 'Grant access to specifically allowed profile' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getAllowedUserByProfileParameters(),
            'expected'   => AccessVote::Grant,
        ];
        yield 'Abstain if profile is not specifically allowed' => [
            'config'     => self::getFullyConfiguredAllowListConfig(),
            'parameters' => self::getNotAllowedUserByProfileParameters(),
            'expected'   => AccessVote::Abstain,
        ];
        yield 'Grant access if all users are allowed' => [
            'config'     => self::getAllUsersAllowedConfig(),
            'parameters' => self::getAuthenticatedUserParameters(),
            'expected'   => AccessVote::Grant,
        ];
        yield 'Grant access if user is part of a child group' => [
            'config' => new AllowListConfig(
                user_ids   : [],
                group_ids  : [$test_group_1_id],
                profile_ids: [],
            ),
            'parameters' => new FormAccessParameters(
                session_info: new SessionInfo(
                    // Child of _test_group_1
                    group_ids: [$test_group_2_id],
                    // These others values don't matter, they won't be used
                    user_id: -1,
                    profile_id: -1,
                ),
                url_parameters: []
            ),
            'expected' => AccessVote::Grant,
        ];
    }

    #[DataProvider('canAnswerProvider')]
    public function testCanAnswer(
        AllowListConfig $config,
        FormAccessParameters $parameters,
        AccessVote $expected
    ): void {
        $allow_list = new AllowList();
        $this->assertEquals(
            $expected,
            $allow_list->canAnswer(new Form(), $config, $parameters)
        );
    }

    private static function getEmptyAllowList(): AllowListConfig
    {
        return new AllowListConfig();
    }


    private static function getFullyConfiguredAllowListConfig(): AllowListConfig
    {
        return new AllowListConfig(
            user_ids   : [1, 2, 3],
            group_ids  : [4, 5, 6],
            profile_ids: [7, 8, 9],
        );
    }

    private static function getAllUsersAllowedConfig(): AllowListConfig
    {
        return new AllowListConfig(
            user_ids   : [AbstractRightsDropdown::ALL_USERS],
            group_ids  : [],
            profile_ids: [],
        );
    }

    private static function getAuthenticatedUserParameters(): FormAccessParameters
    {
        // Dummy session data, won't be used.
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 1,
                group_ids: [2, 3],
                profile_id: 4,
            ),
            url_parameters: []
        );
    }

    private static function getUnauthenticatedUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: null,
            url_parameters: []
        );
    }

    private static function getDirectlyAllowedUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 1,
                group_ids: [],
                profile_id: 0,
            ),
            url_parameters: []
        );
    }

    private static function getNotDirectlyAllowedUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 0,
                group_ids: [],
                profile_id: 0,
            ),
            url_parameters: []
        );
    }

    private static function getAllowedUserByGroupParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 0,
                group_ids: [5],
                profile_id: 0,
            ),
            url_parameters: []
        );
    }

    private static function getNotAllowedUserByGroupParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 0,
                group_ids: [],
                profile_id: 0,
            ),
            url_parameters: []
        );
    }

    private static function getAllowedUserByProfileParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 0,
                group_ids: [],
                profile_id: 9,
            ),
            url_parameters: []
        );
    }

    private static function getNotAllowedUserByProfileParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: new SessionInfo(
                user_id: 0,
                group_ids: [],
                profile_id: 0,
            ),
            url_parameters: []
        );
    }
}
