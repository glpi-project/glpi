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

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AccessControl\AccessVote;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Form\AccessControl\FormAccessControlManager;
use Glpi\Form\AccessControl\FormAccessParameters;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Glpi\Session\SessionInfo;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class DirectAccessTest extends \DBTestCase
{
    use FormTesterTrait;

    public function testGetLabel(): void
    {
        $direct_access = new DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($direct_access->getLabel());
    }

    public function testGetIcon(): void
    {
        $direct_access = new DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->assertNotEmpty($direct_access->getIcon());
    }

    public function testGetConfig(): void
    {
        $direct_access = new DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $class = $direct_access->getConfig();
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
        $direct_access = new DirectAccess();

        // Mock server/query variables
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET['id'] = 1;

        // We only validate that the function run without errors.
        // The rendered content should be validated by an E2E test.
        $form = $this->createForm(
            (new FormBuilder())
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(
                    DirectAccess::class,
                    new DirectAccessConfig(
                        token: 'my token',
                        allow_unauthenticated: true,
                    )
                )
        );
        $access_control = $this->getAccessControl($form, DirectAccess::class);
        $this->assertNotEmpty($direct_access->renderConfigForm($access_control));
    }

    public function testGetWeight(): void
    {
        $direct_access = new DirectAccess();

        // Not much to test here, just ensure the method run without errors
        $this->assertGreaterThan(0, $direct_access->getWeight());
    }


    public function testCreateConfigFromUserInput(): void
    {
        $direct_access = new DirectAccess();

        // Test default fallback values
        $config = $direct_access->createConfigFromUserInput([]);
        $this->assertInstanceOf(DirectAccessConfig::class, $config);
        $this->assertNotEmpty($config->getToken());
        $this->assertFalse($config->allowUnauthenticated());

        // Test user supplied values
        $config = $direct_access->createConfigFromUserInput([
            '_token'                 => 'my token',
            '_allow_unauthenticated' => true,
        ]);
        $this->assertInstanceOf(DirectAccessConfig::class, $config);
        $this->assertEquals('my token', $config->getToken());
        $this->assertTrue($config->allowUnauthenticated());
    }

    public static function canAnswerProvider(): iterable
    {
        // Autenticated form
        $config_authenticated = self::getConfigWithAuthenticadedAccess();
        yield 'Authenticated form: allow authenticated user with correct token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getAuthenticatedSession(),
                url_parameters: self::getValidTokenUrlParameters()
            ),
            AccessVote::Grant,
        ];
        yield 'Authenticated form: abstain for authenticated user with wrong token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getInvalidTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Authenticated form: abstain for authenticated user with missing token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getMissingTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Authenticated form: abstain for unauthenticated user with correct token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getValidTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Authenticated form: abstain for unauthenticated user with wrong token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getInvalidTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Authenticated form: abstain for unauthenticated user with missing token' => [
            $config_authenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getMissingTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];

        // Unauthenticated form
        $config_unauthenticated = self::getConfigWithUnauthenticadedAccess();
        yield 'Unauthenticated form: allow authenticated user with correct token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getAuthenticatedSession(),
                url_parameters: self::getValidTokenUrlParameters()
            ),
            AccessVote::Grant,
        ];
        yield 'Unauthenticated form: abstain for authenticated user with wrong token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getInvalidTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Unauthenticated form: abstain for authenticated user with missing token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getMissingTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Unauthenticated form: allow unauthenticated user with correct token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getValidTokenUrlParameters()
            ),
            AccessVote::Grant,
        ];
        yield 'Unauthenticated form: deny unauthenticated user with wrong token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getInvalidTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
        yield 'Unauthenticated form: deny unauthenticated user with missing token' => [
            $config_unauthenticated,
            new FormAccessParameters(
                session_info: self::getUnauthenticatedSession(),
                url_parameters: self::getMissingTokenUrlParameters()
            ),
            AccessVote::Abstain,
        ];
    }

    #[DataProvider('canAnswerProvider')]
    public function testCanAnswer(
        DirectAccessConfig $config,
        FormAccessParameters $parameters,
        AccessVote $expected
    ): void {
        $direct_access = new DirectAccess();
        $this->assertEquals(
            $expected,
            $direct_access->canAnswer(new Form(), $config, $parameters)
        );
    }

    public static function formWarningsProvider(): iterable
    {
        yield 'form without blacklisted question types' => [
            (new FormBuilder())
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                    allow_unauthenticated: true,
                )),
            [],
        ];

        yield 'form with blacklisted question types' => [
            (new FormBuilder())
                ->addQuestion('My observer question', QuestionTypeObserver::class)
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                    allow_unauthenticated: true,
                )),
            [
                'This form contains question types that are not allowed for unauthenticated access. These questions will be hidden from unauthenticated users.',
            ],
        ];

        yield 'inactive form with blacklisted question types' => [
            (new FormBuilder())
                ->setIsActive(false)
                ->addQuestion('My observer question', QuestionTypeObserver::class)
                ->setUseDefaultAccessPolicies(false)
                ->addAccessControl(DirectAccess::class, new DirectAccessConfig(
                    token: 'my_token',
                    allow_unauthenticated: true,
                )),
            [
                'This form is not visible to anyone because it is not active.',
                'This form contains question types that are not allowed for unauthenticated access. These questions will be hidden from unauthenticated users.',
            ],
        ];
    }

    #[DataProvider('formWarningsProvider')]
    public function testGetWarningsForForm($formBuilder, $expectedWarnings): void
    {
        $form = $this->createForm($formBuilder);
        $this->assertEquals($expectedWarnings, FormAccessControlManager::getInstance()->getWarnings($form));
    }

    private static function getConfigWithAuthenticadedAccess(): DirectAccessConfig
    {
        return new DirectAccessConfig(
            token: 'my_token',
            allow_unauthenticated: false,
        );
    }

    private static function getConfigWithUnauthenticadedAccess(): DirectAccessConfig
    {
        return new DirectAccessConfig(
            token: 'my_token',
            allow_unauthenticated: true,
        );
    }

    private static function getAuthenticatedSession(): SessionInfo
    {
        // Dummy session data, won't be used.
        return new SessionInfo(
            user_id: 1,
            group_ids: [2, 3],
            profile_id: 4,
        );
    }

    private static function getUnauthenticatedSession(): null
    {
        return null;
    }

    private static function getValidTokenUrlParameters(): array
    {
        return ['token' => 'my_token'];
    }

    private static function getInvalidTokenUrlParameters(): array
    {
        return ['token' => 'not_my_token'];
    }

    private static function getMissingTokenUrlParameters(): array
    {
        return [];
    }
}
