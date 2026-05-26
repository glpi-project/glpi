<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use HLAPICallAsserter;

class ServiceCatalogControllerTest extends HLAPITestCase
{
    public function testGetMyServiceCatalogInfo(): void
    {
        $this->login();
        $this->api->call(new Request('GET', '/ServiceCatalog/My'), function ($call) {
            /** @var HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('How can we help you?', $content['helpdesk_home_title']);
                    $this->assertTrue($content['helpdesk_home_search_enabled']);
                    $this->assertGreaterThanOrEqual(2, count(array_filter($content['tiles'], static fn($t) => $t['_tile_type'] === 'FormTile')));
                    $this->assertGreaterThanOrEqual(4, count(array_filter($content['tiles'], static fn($t) => $t['_tile_type'] === 'GLPIPageTile')));
                });
        });
    }
}
