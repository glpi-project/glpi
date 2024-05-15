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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use Glpi\Form\AccessControl\FormAccessParameters;
use JsonConfigInterface;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Session\SessionInfo;

class AllowList extends \GLPITestCase
{
    /**
     * Test the `getLabel` method.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->string($allow_list->getLabel());
    }

    /**
     * Test the `getIcon` method.
     *
     * @return void
     */
    public function testGetIcon(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->string($allow_list->getIcon());
    }

    /**
     * Test the `getConfigClass` method.
     *
     * @return void
     */
    public function testGetConfigClass(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Not much to test here, just ensure the method run without errors
        $class = $allow_list->getConfigClass();
        $this->string($class);

        // Ensure the class exists and is valid
        $is_valid =
            is_a($class, JsonConfigInterface::class, true)
            && !(new \ReflectionClass($class))->isAbstract()
        ;
        $this->boolean($is_valid)->isTrue();
    }

    /**
     * Test the `renderConfigForm` method.
     *
     * @return void
     */
    public function testRenderConfigForm(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // We only validate that the function run without errors.
        // The rendered content should be validated by an E2E test.
        $this->string($allow_list->renderConfigForm(new AllowListConfig()));
        $this->string($allow_list->renderConfigForm(new AllowListConfig(
            user_ids   : [1, 2, 3],
            group_ids  : [4, 5, 6],
            profile_ids: [7, 8, 9],
        )));
    }


    /**
     * Test the `getWeight` method.
     *
     * @return void
     */
    public function testGetWeight(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->integer($allow_list->getWeight());
    }


    /**
     * Test the `createConfigFromUserInput` method.
     *
     * @return void
     */
    public function testCreateConfigFromUserInput(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Test default fallback values
        $config = $allow_list->createConfigFromUserInput([]);
        $this->object($config)->isInstanceOf(AllowListConfig::class);
        $this->array($config->getUserIds())->isEqualTo([]);
        $this->array($config->getGroupIds())->isEqualTo([]);
        $this->array($config->getProfileIds())->isEqualTo([]);

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
            ]
        ]);
        $this->object($config)->isInstanceOf(AllowListConfig::class);
        $this->array($config->getUserIds())->isEqualTo([1, 2, 3]);
        $this->array($config->getGroupIds())->isEqualTo([4, 5, 6]);
        $this->array($config->getProfileIds())->isEqualTo([7, 8, 9]);
    }

    /**
     * Data provider for the `testCanAnswer` method.
     *
     * @return iterable
     */
    protected function testCanAnswerProvider(): iterable
    {
        yield 'Refuse all when allow list is empty' => [
            'config'     => $this->getEmptyAllowList(),
            'parameters' => $this->getAuthenticatedUserParameters(),
            'expected'   => false,
        ];
        yield 'Refuse unauthenticated users' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getUnauthenticatedUserParameters(),
            'expected'   => false,
        ];
        yield 'Allow directly allowed user' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getDirectlyAllowedUserParameters(),
            'expected'   => true,
        ];
        yield 'Deny not directly allowed user' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getNotDirectlyAllowedUserParameters(),
            'expected'   => false,
        ];
        yield 'Allow user by group' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getAllowedUserByGroupParameters(),
            'expected'   => true,
        ];
        yield 'Deny user by group' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getNotAllowedUserByGroupParameters(),
            'expected'   => false,
        ];
        yield 'Allow user by profile' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getAllowedUserByProfileParameters(),
            'expected'   => true,
        ];
        yield 'Deny user by profile' => [
            'config'     => $this->getFullyConfiguredAllowListConfig(),
            'parameters' => $this->getNotAllowedUserByProfileParameters(),
            'expected'   => false,
        ];
    }

    /**
     * Test the `canAnswer` method.
     *
     * @dataProvider testCanAnswerProvider
     *
     * @return void
     */
    public function testCanAnswer(
        AllowListConfig $config,
        FormAccessParameters $parameters,
        bool $expected
    ): void {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();
        $this->boolean(
            $allow_list->canAnswer($config, $parameters)
        )->isEqualTo($expected);
    }

    private function getEmptyAllowList(): AllowListConfig
    {
        return new AllowListConfig();
    }


    private function getFullyConfiguredAllowListConfig(): AllowListConfig
    {
        return new AllowListConfig(
            user_ids   : [1, 2, 3],
            group_ids  : [4, 5, 6],
            profile_ids: [7, 8, 9],
        );
    }

    private function getAuthenticatedUserParameters(): FormAccessParameters
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

    private function getUnauthenticatedUserParameters(): FormAccessParameters
    {
        return new FormAccessParameters(
            session_info: null,
            url_parameters: []
        );
    }

    private function getDirectlyAllowedUserParameters(): FormAccessParameters
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

    private function getNotDirectlyAllowedUserParameters(): FormAccessParameters
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

    private function getAllowedUserByGroupParameters(): FormAccessParameters
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

    private function getNotAllowedUserByGroupParameters(): FormAccessParameters
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

    private function getAllowedUserByProfileParameters(): FormAccessParameters
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

    private function getNotAllowedUserByProfileParameters(): FormAccessParameters
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
