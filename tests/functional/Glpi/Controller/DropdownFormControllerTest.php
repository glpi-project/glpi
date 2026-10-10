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

namespace tests\units\Glpi\Controller;

use Entity;
use Glpi\Controller\DropdownFormController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Tests\DbTestCase;
use Location;
use Session;
use Symfony\Component\HttpFoundation\Request;

class DropdownFormControllerTest extends DbTestCase
{
    private function buildRequest(array $post): Request
    {
        $request = Request::create('/front/location.form.php', 'POST', $post);
        $request->attributes->set('class', Location::class);

        return $request;
    }

    public function testCannotMoveDropdownToInaccessibleEntity(): void
    {
        $this->login();
        $child_1 = getItemByTypeName(Entity::class, '_test_child_1', true);
        $child_2 = getItemByTypeName(Entity::class, '_test_child_2', true);

        $location = $this->createItem(Location::class, ['name' => 'Location', 'entities_id' => $child_1]);

        // A user that only has access to the first child entity
        $this->setEntity('_test_child_1', false);
        $this->assertFalse(Session::haveAccessToEntity($child_2));

        $controller = new DropdownFormController();

        // The location cannot be moved to an entity that is not accessible
        $exception = null;
        try {
            $controller($this->buildRequest(['update' => 1, 'id' => $location->getID(), 'name' => 'Location', 'entities_id' => $child_2]));
        } catch (AccessDeniedHttpException $e) {
            $exception = $e;
        }
        $this->assertInstanceOf(AccessDeniedHttpException::class, $exception);
        $this->assertTrue($location->getFromDB($location->getID()));
        $this->assertSame($child_1, $location->fields['entities_id']);
    }

    public function testCanUpdateDropdownInAccessibleEntity(): void
    {
        $this->login();
        $child_1 = getItemByTypeName(Entity::class, '_test_child_1', true);

        $location = $this->createItem(Location::class, ['name' => 'Location', 'entities_id' => $child_1]);
        $this->setEntity('_test_child_1', false);

        $controller = new DropdownFormController();
        $controller($this->buildRequest(['update' => 1, 'id' => $location->getID(), 'name' => 'Renamed', 'entities_id' => $child_1]));

        $this->assertTrue($location->getFromDB($location->getID()));
        $this->assertSame('Renamed', $location->fields['name']);
    }
}
