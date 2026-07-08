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

namespace tests\units\Glpi\Controller\Knowbase;

use Glpi\Controller\Knowbase\KnowbaseItemController;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use Session;
use Symfony\Component\HttpFoundation\Request;

final class KnowbaseItemControllerTest extends DbTestCase
{
    public function testFullRendersEditModeWhenRequested(): void
    {
        $this->login();
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'Full mode test', 'answer' => 'a',
            'users_id' => Session::getLoginUserID(),
        ]);

        $request = new Request(query: ['mode' => 'edit']);
        $request->attributes->set('knowbaseitems_id', $item->getID());
        $response = (new KnowbaseItemController())->full($request);

        ob_start();
        $response->sendContent();
        $html = ob_get_clean();

        $this->assertStringContainsString('data-action="save"', $html);
    }

    public function testFullDefaultsToViewModeForUnknownModeValue(): void
    {
        $this->login();
        $item = $this->createItem(KnowbaseItem::class, [
            'name' => 'Full mode default test', 'answer' => 'a',
            'users_id' => Session::getLoginUserID(),
        ]);

        $request = new Request(query: ['mode' => 'something-invalid']);
        $request->attributes->set('knowbaseitems_id', $item->getID());
        $response = (new KnowbaseItemController())->full($request);

        ob_start();
        $response->sendContent();
        $html = ob_get_clean();

        $this->assertStringNotContainsString('data-action="save"', $html);
    }
}
