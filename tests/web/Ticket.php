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

namespace tests\units;

use Glpi\Toolbox\Sanitizer;
use Symfony\Component\BrowserKit\HttpBrowser;

class Ticket extends \FrontBaseClass
{
    public function testTicketCreate()
    {
        $this->addToCleanup(\Ticket::class, ['name' => ['LIKE', '%thetestuuidtoremove']]);

        //create non panther browser
        $http_client = new HttpBrowser();

        //login
        $crawler = $http_client->request('GET', $this->base_uri . 'index.php');
        $login_name = $crawler->filter('#login_name')->attr('name');
        $pass_name = $crawler->filter('input[type=password]')->attr('name');
        $form = $crawler->selectButton('submit')->form();
        $form[$login_name] = TU_USER;
        $form[$pass_name] = TU_PASS;
        //proceed form submission
        $crawler = $http_client->submit($form);
        $page_title = $crawler->filter('title')->text();
        $this->string($page_title)->isIdenticalTo('Standard interface - GLPI');

        //load ticket form
        $crawler = $http_client->request('GET', $this->base_uri . 'front/ticket.form.php');

        $crawler = $http_client->request(
            'POST',
            $this->base_uri . 'front/ticket.form.php',
            [
                'add'  => true,
                'name' => 'A \'test\' > "ticket" & name thetestuuidtoremove',
                'content' => 'A \'test\' > "ticket" & name thetestuuidtoremove',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_glpi_csrf_token' => $crawler->filter('input[name=_glpi_csrf_token]')->attr('value')
            ]
        );

        $ticket = new \Ticket();
        $this->boolean($ticket->getFromDBByCrit(['name' => ['LIKE', '%thetestuuidtoremove']]))->isTrue();
        $this->string(Sanitizer::unsanitize($ticket->fields['name'], false))->isIdenticalTo('A \'test\' > "ticket" & name thetestuuidtoremove');
        $this->string(Sanitizer::unsanitize($ticket->fields['content'], false))->isIdenticalTo('A \'test\' > "ticket" & name thetestuuidtoremove');
    }

    /*public function testTicketCreatePanther()
    {
        $this->logIn();
        $this->addToCleanup(\Ticket::class, ['name' => ['LIKE', '%thetestuuidtoremove']]);

        //load ticket form
        $this->http_client->request('GET', $this->base_uri . 'front/ticket.form.php');

        $crawler = $this->http_client->waitFor('form[name=itil_form]');
        $tinyid = $crawler->filter('textarea[name=content]')->attr('id');
        $this->http_client->executeScript("tinymce.get('$tinyid').setContent('A \'test\' > \"ticket\" & name thetestuuidtoremove');");
        $this->http_client->takeScreenshot('ticket_add.png'); // see if that works...
        $this->http_client->submitForm(
            'add_btn',
            [
                'name' => 'A \'test\' > "ticket" & name thetestuuidtoremove',
            ]
        );

        $ticket = new \Ticket();
        $this->boolean($ticket->getFromDBByCrit(['name' => ['LIKE', '%thetestuuidtoremove']]))->isTrue();
        $this->string(Sanitizer::unsanitize($ticket->fields['name'], false))->isIdenticalTo('A \'test\' > "ticket" & name thetestuuidtoremove');
        $this->string(Sanitizer::unsanitize($ticket->fields['content'], false))->isIdenticalTo('A \'test\' > "ticket" & name thetestuuidtoremove');
    }*/
}
