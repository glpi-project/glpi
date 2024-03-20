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

use FreeJsonConfigInterface;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use SessionInfo;

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
            is_a($class, FreeJsonConfigInterface::class, true)
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
        $this->string($allow_list->renderConfigForm(new AllowListConfig([
            'user_ids'    => [1, 2, 3],
            'group_ids'   => [4, 5, 6],
            'profile_ids' => [7, 8, 9],
        ])));
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
        $this->array($config->user_ids)->isEqualTo([]);
        $this->array($config->group_ids)->isEqualTo([]);
        $this->array($config->profile_ids)->isEqualTo([]);

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
        $this->array($config->user_ids)->isEqualTo([1, 2, 3]);
        $this->array($config->group_ids)->isEqualTo([4, 5, 6]);
        $this->array($config->profile_ids)->isEqualTo([7, 8, 9]);
    }

    /**
     * Test the `allowUnauthenticatedUsers` method.
     *
     * @return void
     */
    public function testAllowUnauthenticatedUsers(): void
    {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();

        // Not much to test here, just ensure the method run without errors
        $this->boolean($allow_list->allowUnauthenticatedUsers(new AllowListConfig()));
    }

    /**
     * Data provider for the `testCanAnswer` method.
     *
     * @return iterable
     */
    protected function testCanAnswerProvider(): iterable
    {
        // Mock session info
        $session_info = new SessionInfo(
            user_id   : 1,           // User has id 1
            group_ids : [1, 2, 3],   // User is part of groups 1, 2 and 3
            profile_id: 1,           // User has profile 1
        );

        // Default config, allow all users
        yield [
            'config'  => new AllowListConfig(),
            'session' => $session_info,
            'expected' => true,
        ];

        // User allowlist
        yield [
            'config'  => new AllowListConfig([
                'user_ids' => [2] // Not our user
            ]),
            'session' => $session_info,
            'expected' => false,
        ];
        yield [
            'config'  => new AllowListConfig([
                'user_ids' => [1] // Our user
            ]),
            'session' => $session_info,
            'expected' => true,
        ];

        // Group allowlist
        yield [
            'config'  => new AllowListConfig([
                'group_ids' => [4, 5] // Not our user
            ]),
            'session' => $session_info,
            'expected' => false,
        ];
        yield [
            'config'  => new AllowListConfig([
                'group_ids' => [1, 2] // Our user
            ]),
            'session' => $session_info,
            'expected' => true,
        ];

        // Profile allowlist
        yield [
            'config'  => new AllowListConfig([
                'profile_ids' => [2] // Not our user
            ]),
            'session' => $session_info,
            'expected' => false,
        ];
        yield [
            'config'  => new AllowListConfig([
                'profile_ids' => [1] // Our user
            ]),
            'session' => $session_info,
            'expected' => true,
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
        SessionInfo $session,
        bool $expected
    ): void {
        $allow_list = new \Glpi\Form\AccessControl\ControlType\AllowList();
        $this->boolean($allow_list->canAnswer($config, $session))->isEqualTo($expected);
    }
}
