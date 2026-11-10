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

namespace tests\units\Glpi\Api\HL\Controller;

use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use QueuedNotification;
use Ticket;
use User;

class NotificationControllerTest extends HLAPITestCase
{
    public function testCRUDNotification()
    {
        $this->api->autoTestCRUD(
            '/Notifications/Notification',
            [
                'itemtype' => 'Ticket',
                'event' => 'new',
            ]
        );
    }

    public function testCRUDNotificationTemplate()
    {
        $this->api->autoTestCRUD(
            '/Notifications/NotificationTemplate',
            [
                'itemtype' => 'Ticket',
            ]
        );
    }

    public function testQueuedNotificationSensitiveBodyHidden()
    {
        $this->login();

        $entities_id = $this->getTestRootEntity(true);
        $sensitive = $this->createItem(QueuedNotification::class, [
            'itemtype'    => User::class,
            'event'       => 'passwordforget',
            'entities_id' => $entities_id,
            'name'        => __FUNCTION__ . '_sensitive',
            'mode'        => 'mailing',
            'body_text'   => 'Sensitive content',
            'body_html'   => '<p>Sensitive content</p>',
        ], ['body_text', 'body_html']);
        $not_sensitive = $this->createItem(QueuedNotification::class, [
            'itemtype'    => Ticket::class,
            'event'       => 'new',
            'entities_id' => $entities_id,
            'name'        => __FUNCTION__ . '_not_sensitive',
            'mode'        => 'mailing',
            'body_text'   => 'Regular content',
            'body_html'   => '<p>Regular content</p>',
        ]);

        $expected = [
            $sensitive->getID() => [
                'is_body_disclosed' => false,
                'body_text'         => '',
                'body_html'         => '',
            ],
            $not_sensitive->getID() => [
                'is_body_disclosed' => true,
                'body_text'         => 'Regular content',
                'body_html'         => '<p>Regular content</p>',
            ],
        ];

        foreach ($expected as $id => $expected_values) {
            $this->api->call(new Request('GET', '/Notifications/QueuedNotification/' . $id), function ($call) use ($expected_values) {
                $call->response
                    ->isOK()
                    ->jsonContent(function ($content) use ($expected_values) {
                        foreach ($expected_values as $property => $value) {
                            $this->assertSame($value, $content[$property], $property);
                        }
                    });
            });

            $this->graphql->call('query { QueuedNotification(id: ' . $id . ') { id is_body_disclosed body_text body_html } }', function ($call) use ($expected_values) {
                $call->response
                    ->isOK()
                    ->data('QueuedNotification', function ($results) use ($expected_values) {
                        $this->assertCount(1, $results);
                        foreach ($expected_values as $property => $value) {
                            $this->assertSame($value, $results[0][$property], $property);
                        }
                    });
            });
        }

        // Filtering on the computed property
        $request = new Request('GET', '/Notifications/QueuedNotification');
        $request->setParameter('filter', 'name=like=' . __FUNCTION__ . '*;is_body_disclosed==false');
        $this->api->call($request, function ($call) use ($sensitive) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($sensitive) {
                    $this->assertSame([$sensitive->getID()], array_column($content, 'id'));
                });
        });
    }
}
