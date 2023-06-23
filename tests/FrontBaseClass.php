<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Toolbox\Sanitizer;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Panther\Client;

class FrontBaseClass extends \GLPITestCase
{
    protected Client $http_client;
    protected string $base_uri;

    private array $items_to_cleanup = [];
    public function beforeTestMethod($method)
    {
        global $CFG_GLPI, $DB;

        $this->base_uri    = trim($CFG_GLPI['url_base'], "/") . "/";
        //Firefox is buggy and will fail finding password field or submit button :'(
        $_SERVER['PANTHER_NO_SANDBOX'] = 1;
        $_SERVER['PANTHER_CHROME_ARGUMENTS'] = '--disable-dev-shm-usage';
        $this->http_client = Client::createChromeClient(null, null, [], $this->base_uri);

        $this->doCleanup();
        parent::beforeTestMethod($method);
    }

    public function afterTestMethod($method)
    {
        $this->doCleanup();
        parent::afterTestMethod($method);
    }

    protected function logIn()
    {

        $this->http_client->request('GET', $this->base_uri . 'index.php');
        $crawler = $this->http_client->waitFor('#login_name');

        $login_name = $crawler->filter('#login_name')->attr('name');
        $pass_name = $crawler->filter('input[type=password]')->attr('name');

        $crawler = $this->http_client->submitForm(
            'Sign in',
            [
                $login_name => TU_USER,
                $pass_name => TU_PASS,
            ]
        );

        //once logged in, we reach standard interface
        $this->string($this->http_client->getTitle())->isIdenticalTo('Standard interface - GLPI');
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
