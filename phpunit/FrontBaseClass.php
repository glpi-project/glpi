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

use Symfony\Component\BrowserKit\HttpBrowser;

class FrontBaseClass extends GLPITestCase
{
    protected HttpBrowser $http_client;
    protected string $base_uri;

    private array $items_to_cleanup = [];
    public function setUp(): void
    {
        $this->http_client = new HttpBrowser();
        $this->base_uri    = trim(GLPI_URI, '/') . '/';

        $this->doCleanup();
        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->doCleanup();
        parent::tearDown();
    }

    protected function logIn()
    {
        $crawler = $this->http_client->request('GET', $this->base_uri . 'index.php');
        $login_name = $crawler->filter('#login_name')->attr('name');
        $pass_name = $crawler->filter('input[type=password]')->attr('name');
        $form = $crawler->selectButton('submit')->form();
        $form[$login_name] = TU_USER;
        $form[$pass_name] = TU_PASS;
        //proceed form submission
        $crawler = $this->http_client->submit($form);

        //once logged in, we reach standard interface
        $page_title = $crawler->filter('title')->text();
        $this->assertSame(
            'Standard interface - GLPI',
            $page_title
        );
    }

    protected function addToCleanup(string $itemtype, array $criteria)
    {
        $this->items_to_cleanup[$itemtype][] = $criteria;
    }

    protected function doCleanup()
    {
        global $DB;
        foreach ($this->items_to_cleanup as $itemtype => $criteria) {
            $DB->delete(
                $itemtype::getTable(),
                $criteria
            );
        }
    }
}
