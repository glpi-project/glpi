<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Controller\Dropdown;

use Computer;
use Entity;
use Glpi\Controller\Dropdown\VisibilityTargetController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
use Group;
use KnowbaseItem;
use Profile;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User;

final class VisibilityTargetControllerTest extends DbTestCase
{
    private function callController(array $post): Response
    {
        $controller = new VisibilityTargetController();
        return $controller->__invoke(Request::create('', 'POST', $post));
    }

    public function testInvokeWithoutTypeThrowsBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'right' => 'knowbase',
        ]);
    }

    public function testInvokeWithoutRightThrowsBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'type' => User::class,
        ]);
    }

    public function testInvokeWithUnsupportedTypeThrowsBadRequest(): void
    {
        $this->login();

        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'type'  => Computer::class,
            'right' => 'knowbase',
        ]);
    }

    public function testInvokeWithDisallowedRightThrowsBadRequest(): void
    {
        $this->login();

        // `config` would otherwise leak which profiles hold the admin right.
        $this->expectException(BadRequestHttpException::class);
        $this->callController([
            'type'  => Profile::class,
            'right' => 'config',
        ]);
    }

    public function testInvokeReturnsRenderedResponseForUserType(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => User::class,
            'right' => 'knowbase',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/name=["\']users_id["\']/', $response->getContent());
    }

    public function testInvokeRendersGroupDropdownWithSubVisibilityTarget(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => Group::class,
            'right' => 'knowbase',
        ]);

        $content = $response->getContent();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/name=["\']groups_id["\']/', $content);
        $this->assertMatchesRegularExpression('/id="subvisibility\d+"/', $content);
    }

    public function testInvokeRendersEntityDropdown(): void
    {
        $this->login();

        $response = $this->callController([
            'type'         => Entity::class,
            'right'        => 'knowbase',
            'is_recursive' => 1,
        ]);

        $content = $response->getContent();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/name=["\']entities_id["\']/', $content);
        $this->assertMatchesRegularExpression('/name=["\']is_recursive["\']/', $content);
    }

    public function testInvokeRendersProfileDropdown(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => Profile::class,
            'right' => 'knowbase',
        ]);

        $content = $response->getContent();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/name=["\']profiles_id["\']/', $content);
    }

    /**
     * Profile::dropdown is async-rendered (Ajax Select2), so profile names
     * never reach the initial HTML. Reflect into the private method instead.
     */
    public function testFaqRightUsesReadFaqBitmask(): void
    {
        $controller = new VisibilityTargetController();
        $method     = new ReflectionMethod($controller, 'getProfileCondition');

        $kb_condition  = $method->invoke($controller, 'knowbase');
        $faq_condition = $method->invoke($controller, 'faq');

        $this->assertSame('knowbase', $kb_condition['glpi_profilerights.name']);
        $this->assertSame('knowbase', $faq_condition['glpi_profilerights.name']);
        $this->assertSame(['&', READ | CREATE | UPDATE | PURGE], $kb_condition['glpi_profilerights.rights']);
        $this->assertSame(['&', KnowbaseItem::READFAQ], $faq_condition['glpi_profilerights.rights']);
    }

    public function testInvokeWithNobuttonHidesAddButton(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => User::class,
            'right'    => 'knowbase',
            'nobutton' => 1,
        ]);

        $this->assertStringNotContainsString('name="addvisibility"', $response->getContent());
    }

    public function testInvokeWithoutNobuttonShowsAddButton(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => User::class,
            'right' => 'knowbase',
        ]);

        $this->assertStringContainsString('name="addvisibility"', $response->getContent());
    }

    public function testInvokeSetsNoCacheHeaders(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => User::class,
            'right' => 'knowbase',
        ]);

        $cache_control = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache_control);
        $this->assertStringContainsString('no-cache', $cache_control);
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }
}
