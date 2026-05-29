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

namespace tests\units;

use Glpi\Tests\FrontBaseClass;

use function Safe\json_encode;

class TicketTest extends FrontBaseClass
{
    public function testTicketCreate()
    {
        $this->logIn();
        $this->addToCleanup(\Ticket::class, ['name' => ['LIKE', '%thetestuuidtoremove']]);

        //load computer form
        $crawler = $this->http_client->request('GET', $this->base_uri . 'front/ticket.form.php');

        $this->http_client->request(
            'POST',
            $this->base_uri . 'front/ticket.form.php',
            [
                'add'  => true,
                'name' => 'A \'test\' > "ticket" & name thetestuuidtoremove',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_glpi_csrf_token' => $crawler->filter('input[name=_glpi_csrf_token]')->attr('value'),
            ]
        );

        $ticket = new \Ticket();
        $this->assertTrue($ticket->getFromDBByCrit(['name' => ['LIKE', '%thetestuuidtoremove']]));
        $this->assertSame(
            'A \'test\' > "ticket" & name thetestuuidtoremove',
            $ticket->fields['name']
        );
    }

    public function testTicketCreateWithUtf8EmailRequester()
    {
        $this->logIn();
        $this->addToCleanup(\Ticket::class, ['name' => ['LIKE', 'Test ticket with UTF-8 email requester%']]);

        $utf8_emails = [
            'josé@example.com',
            'françois@example.fr',
            'müller@example.de',
            'andré.garcía@example.es',
            'jürgen_øvergård@example.no',
        ];

        foreach ($utf8_emails as $index => $email) {
            $ticket_name = 'Test ticket with UTF-8 email requester ' . $index;

            $crawler = $this->http_client->request('GET', $this->base_uri . 'front/ticket.form.php');
            $csrf_token = $crawler->filter('input[name=_glpi_csrf_token]')->attr('value');

            $crawler = $this->http_client->request(
                'POST',
                $this->base_uri . 'front/ticket.form.php',
                [
                    'add' => true,
                    'name' => $ticket_name,
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                    '_glpi_csrf_token' => $csrf_token,
                    '_actors' => json_encode([
                        'requester' => [
                            [
                                'itemtype' => 'User',
                                'items_id' => 0,
                                'use_notification' => 1,
                                'alternative_email' => $email,
                            ],
                        ],
                    ]),
                ]
            );

            $ticket = new \Ticket();
            $this->assertTrue(
                $ticket->getFromDBByCrit(['name' => $ticket_name]),
                sprintf('Ticket with UTF-8 email requester "%s" should be created', $email)
            );

            $ticket_users = (new \Ticket_User())->find([
                'tickets_id' => $ticket->fields['id'],
                'type' => \CommonITILActor::REQUESTER,
            ]);
            $this->assertCount(1, $ticket_users, sprintf('One requester should be associated with ticket for email "%s"', $email));

            $ticket_user = array_shift($ticket_users);
            $this->assertSame(
                $email,
                $ticket_user['alternative_email'],
                sprintf('Alternative email should be "%s"', $email)
            );
        }
    }
}
