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

namespace tests\units\Glpi;

class RoutingTest extends \FrontBaseClass
{
    /**
     * Load plugin tester index page
     *
     * @return void
     */
    public function testPluginRootRoute(): void
    {
        //no login required since "/" is stateless for tester plugin

        //load plugin tester index page
        $crawler = $this->http_client->request('GET', $this->base_uri . 'plugins/tester/');
        $this->assertSame('<body><p>Greeting from tester plugin controller / route.</p></body>', $crawler->html());
    }

    /**
     * Load plugin tester page that requires authentication
     *
     * @return void
     */
    public function testPluginTestRoute(): void
    {
        //load plugin tester test page
        $crawler = $this->http_client->request('GET', $this->base_uri . 'plugins/tester/Testuri');
        $this->assertStringContainsString('Access denied', $crawler->html());

        $this->logIn();
        $crawler = $this->http_client->request('GET', $this->base_uri . 'plugins/tester/Testuri');
        $this->assertSame('<body><p>Greeting from tester plugin controller /Testuri route.</p></body>', $crawler->html());
    }
}
