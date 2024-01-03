<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use Glpi\Toolbox\Sanitizer;
use Symfony\Component\BrowserKit\HttpBrowser;

class Computer extends \FrontBaseClass
{
    public function testComputerCreate()
    {
        $this->logIn();
        $this->addToCleanup(\Computer::class, ['uuid' => 'thetestuuidtoremove']);

        //load computer form
        $crawler = $this->http_client->request('GET', $this->base_uri . 'front/computer.form.php');

        $crawler = $this->http_client->request(
            'POST',
            $this->base_uri . 'front/computer.form.php',
            [
                'add'  => true,
                'name' => 'A test > computer & name',
                'uuid' => 'thetestuuidtoremove',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_glpi_csrf_token' => $crawler->filter('input[name=_glpi_csrf_token]')->attr('value')
            ]
        );

        $computer = new \Computer();
        $this->boolean($computer->getFromDBByCrit(['uuid' => 'thetestuuidtoremove']))->isTrue();
        $this->string(Sanitizer::unsanitize($computer->fields['name'], false))->isIdenticalTo('A test > computer & name');
    }
}
