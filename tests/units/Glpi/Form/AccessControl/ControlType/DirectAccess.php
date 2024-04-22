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

use JsonConfigInterface;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Session\SessionInfo;

class DirectAccess extends \GLPITestCase
{
    /**
     * Test the `getLabel` method.
     *
     * @return void
     */
    public function testGetLabel(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->string($direct_access->getLabel());
    }

    /**
     * Test the `getIcon` method.
     *
     * @return void
     */
    public function testGetIcon(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->string($direct_access->getIcon());
    }

    /**
     * Test the `getConfigClass` method.
     *
     * @return void
     */
    public function testGetConfigClass(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $class = $direct_access->getConfigClass();
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
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Mock server/query variables
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['id'] = 1;

        // We only validate that the function run without errors.
        // The rendered content should be validated by an E2E test.
        $this->string($direct_access->renderConfigForm(new DirectAccessConfig()));
        $this->string($direct_access->renderConfigForm(new DirectAccessConfig([
            'token'                 => 'my token',
            'allow_unauthenticated' => true,
            'force_direct_access'   => true,
        ])));
    }


    /**
     * Test the `getWeight` method.
     *
     * @return void
     */
    public function testGetWeight(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->integer($direct_access->getWeight());
    }


    /**
     * Test the `createConfigFromUserInput` method.
     *
     * @return void
     */
    public function testCreateConfigFromUserInput(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Test default fallback values
        $config = $direct_access->createConfigFromUserInput([]);
        $this->object($config)->isInstanceOf(DirectAccessConfig::class);
        $this->string($config->token)->isNotEmpty();
        $this->boolean($config->allow_unauthenticated)->isFalse();
        $this->boolean($config->force_direct_access)->isFalse();

        // Test user supplied values
        $config = $direct_access->createConfigFromUserInput([
            '_token'                 => 'my token',
            '_allow_unauthenticated' => true,
            '_force_direct_access'   => true,
        ]);
        $this->object($config)->isInstanceOf(DirectAccessConfig::class);
        $this->string($config->token)->isEqualTo('my token');
        $this->boolean($config->allow_unauthenticated)->isTrue();
        $this->boolean($config->force_direct_access)->isTrue();
    }

    /**
     * Test the `allowUnauthenticatedUsers` method.
     *
     * @return void
     */
    public function testAllowUnauthenticatedUsers(): void
    {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();

        // Allow unauthenticated users and a correct token was supplied
        $_GET['token'] = "my_token";
        $allow = new DirectAccessConfig([
            'allow_unauthenticated' => true,
            'token'                 => 'my_token',
        ]);
        $this->boolean($direct_access->allowUnauthenticatedUsers($allow))->isTrue();

        // Allow unauthenticated users and an invalid token was supplied
        $_GET['token'] = "invalid_token";
        $allow = new DirectAccessConfig([
            'allow_unauthenticated' => true,
            'token'                 => 'my_token',
        ]);
        $this->boolean($direct_access->allowUnauthenticatedUsers($allow))->isFalse();

        // Allow unauthenticated users but no token was supplied
        unset($_GET['token']);
        $allow = new DirectAccessConfig([
            'allow_unauthenticated' => true,
            'token'                 => 'my_token',
        ]);
        $this->boolean($direct_access->allowUnauthenticatedUsers($allow))->isFalse();

        // Deny unauthenticated users
        $deny = new DirectAccessConfig([
            'allow_unauthenticated' => false,
            'token'                 => 'my_token',
        ]);
        $this->boolean($direct_access->allowUnauthenticatedUsers($deny))->isFalse();

        // Deny by default
        $default = new DirectAccessConfig([]);
        $this->boolean($direct_access->allowUnauthenticatedUsers($default))->isFalse();
    }

    /**
     * Data provider for the `testCanAnswer` method.
     *
     * @return iterable
     */
    protected function testCanAnswerProvider(): iterable
    {
        // Session info doesn't matter here
        $session_info = new SessionInfo(
            user_id   : 1,           // User has id 1
            group_ids : [1, 2, 3],   // User is part of groups 1, 2 and 3
            profile_id: 1,           // User has profile 1
        );

        // Default config, allow all users
        yield [
            'config'   => new DirectAccessConfig(),
            'session'  => $session_info,
            'expected' => true,
        ];

        // Mock server/query variables
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['id'] = 1;

        // Test token usage
        $config = new DirectAccessConfig([
            'token' => "valid_token"
        ]);
        $_GET['token'] = "invalid_token";
        yield [
            'config'   => $config,
            'session'  => $session_info,
            'expected' => false,
        ];
        $_GET['token'] = "valid_token";
        yield [
            'config'   => $config,
            'session'  => $session_info,
            'expected' => true,
        ];
        unset($_GET['token']); // No token
        yield [
            'config'   => $config,
            'session'  => $session_info,
            'expected' => true,
        ];

        // Test force_direct_access usage
        $config = new DirectAccessConfig([
            'token'               => "valid_token",
            'force_direct_access' => true,
        ]);
        unset($_GET['token']); // No token
        yield [
            'config'   => $config,
            'session'  => $session_info,
            'expected' => false,
        ];
        $_GET['token'] = "valid_token";
        yield [
            'config'   => $config,
            'session'  => $session_info,
            'expected' => true,
        ];

        // Clean up $_GET
        unset($_GET['token']);
    }

    /**
     * Test the `canAnswer` method.
     *
     * @dataProvider testCanAnswerProvider
     *
     * @return void
     */
    public function testCanAnswer(
        DirectAccessConfig $config,
        SessionInfo $session,
        bool $expected
    ): void {
        $direct_access = new \Glpi\Form\AccessControl\ControlType\DirectAccess();
        $this->boolean($direct_access->canAnswer($config, $session))->isEqualTo($expected);
    }
}
