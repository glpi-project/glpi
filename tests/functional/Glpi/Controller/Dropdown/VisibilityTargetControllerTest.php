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
use Glpi\Controller\Dropdown\VisibilityTargetController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Tests\DbTestCase;
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
            'right' => 'interface',
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
            'right' => 'interface',
        ]);
    }

    public function testInvokeReturnsRenderedResponseForUserType(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => User::class,
            'right' => 'interface',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $response->getContent());
    }

    public function testInvokeWithNobuttonHidesAddButton(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => User::class,
            'right'    => 'interface',
            'nobutton' => 1,
        ]);

        $this->assertStringNotContainsString('name="addvisibility"', $response->getContent());
    }

    public function testInvokeWithoutNobuttonShowsAddButton(): void
    {
        $this->login();

        $response = $this->callController([
            'type'  => User::class,
            'right' => 'interface',
        ]);

        $this->assertStringContainsString('name="addvisibility"', $response->getContent());
    }
}
