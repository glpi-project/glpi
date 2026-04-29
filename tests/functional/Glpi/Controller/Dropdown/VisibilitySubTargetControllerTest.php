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

use Glpi\Controller\Dropdown\VisibilitySubTargetController;
use Glpi\Tests\DbTestCase;
use Group;
use Profile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User;

final class VisibilitySubTargetControllerTest extends DbTestCase
{
    private function callController(array $post): Response
    {
        $controller = new VisibilitySubTargetController();
        return $controller->__invoke(Request::create('', 'POST', $post));
    }

    public function testInvokeReturnsEmptyResponseWhenItemsIdIsZero(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => Group::class,
            'items_id' => 0,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsEmptyResponseWhenTypeIsUnsupported(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => User::class,
            'items_id' => 1,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testInvokeReturnsRenderedResponseForGroupType(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => Group::class,
            'items_id' => 1,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $response->getContent());
    }

    public function testInvokeReturnsRenderedResponseForProfileType(): void
    {
        $this->login();

        $response = $this->callController([
            'type'     => Profile::class,
            'items_id' => 1,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', $response->getContent());
    }
}
