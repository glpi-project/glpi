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

/* Test for inc/user.class.php */

use Glpi\Search\SearchOption;

class Webhook extends \DbTestCase
{
    /**
     * Make sure all webhook item types have an ID search option so that the criteria filters can be applied properly
     * @return void
     */
    public function testWebhookTypesHaveIDOpt()
    {
        $supported = \Webhook::getItemtypesDropdownValues();
        $itemtypes = [];
        foreach ($supported as $types) {
            $itemtypes = array_merge($itemtypes, array_keys($types));
        }

        /** @var \CommonDBTM $itemtype */
        foreach ($itemtypes as $itemtype) {
            $opts = SearchOption::getOptionsForItemtype($itemtype);
            $id_field = $itemtype::getIndexName();
            $item_table = $itemtype::getTable();
            $id_opt_num = null;
            foreach ($opts as $opt_num => $opt) {
                if (isset($opt['field']) && $opt['field'] === $id_field && $opt['table'] === $item_table) {
                    $id_opt_num = $opt_num;
                    break;
                }
            }
            if ($id_opt_num === null) {
                echo 'No ID option found for itemtype ' . $itemtype;
            }
            $this->variable($id_opt_num)->isNotNull();
        }
    }

    public function testGetWebhookBody()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'externalid' => 'ext1234',
            'entities_id' => $_SESSION['glpiactive_entity']
        ]);
        $this->string($ticket->fields['externalid'])->isEqualTo('ext1234');

        $payload = <<<JSON
            {
                "event": "{{ event }}",
                "external_id": "{{ parent_item.external_id }}",
                "item": {
                    "id": "{{ item.id }}",
                    "itemtype": "{{ item.itemtype }}",
                    "items_id": "{{ item.items_id }}",
                    "content": "{{ item.content }}"
                }
            }
JSON;

        $webhook = $this->createItem('Webhook', [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => 'ITILFollowup',
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 0,
            'payload' => $payload
        ]);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup'
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->array($queued_webhook)->size->isGreaterThan(0);

        $body = json_decode($queued_webhook['body'], true);

        $this->string($body['event'])->isEqualTo('new');
        $this->string($body['external_id'])->isEqualTo('ext1234');
        $this->string($body['item']['id'])->isEqualTo($fup->getID());
        $this->string($body['item']['itemtype'])->isEqualTo('Ticket');
        $this->string($body['item']['items_id'])->isEqualTo($ticket->getID());
        $this->string($body['item']['content'])->isEqualTo('Test followup');
    }

    public function testWebhookURLTemplate()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'externalid' => 'ext1234'
        ]);

        $payload = <<<JSON
            {
                "event": "{{ event }}"
            }
JSON;

        $webhook = $this->createItem('Webhook', [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost/{{ parent_item.external_id }}/{{ event }}/{{ item.id }}',
            'itemtype' => 'ITILFollowup',
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 0,
            'payload' => $payload
        ]);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup'
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->array($queued_webhook)->size->isGreaterThan(0);
        $this->string($queued_webhook['url'])->isEqualTo('http://localhost/ext1234/new/' . $fup->getID());
    }

    public function testWebhookHeaderTemplate()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'externalid' => 'ext1234'
        ]);

        $payload = <<<JSON
            {
                "event": "{{ event }}"
            }
JSON;
        $custom_headers = <<<JSON
            {
                "X-Test-Event": "{{ event }}",
                "X-Test-External-ID": "{{ parent_item.external_id }}",
                "X-Test-Item-ID": "{{ item.id }}",
                "X-Test-Mixed": "{{ event }}-{{ parent_item.external_id }}-{{ item.id }}"
            }
JSON;


        $webhook = $this->createItem('Webhook', [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => 'ITILFollowup',
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 0,
            'payload' => $payload,
            'custom_headers' => $custom_headers
        ], ['custom_headers']);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup'
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->array($queued_webhook)->size->isGreaterThan(0);
        $headers = json_decode($queued_webhook['headers'], true);

        $this->string($headers['X-Test-Event'])->isEqualTo('new');
        $this->string($headers['X-Test-External-ID'])->isEqualTo('ext1234');
        $this->string($headers['X-Test-Item-ID'])->isEqualTo($fup->getID());
        $this->string($headers['X-Test-Mixed'])->isEqualTo('new-ext1234-' . $fup->getID());
    }

    public function testGetResultForPath()
    {
        $this->login();
        /** @var \Webhook $webhook */
        $webhook = $this->createItem('Webhook', [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => 'User',
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 1,
        ]);
        $users_id = \Session::getLoginUserID();
        // Make sure we get at least something as a response.
        // The main purpose is to test the internal authentication middleware.
        $this->variable($webhook->getResultForPath('/Administration/User/' . $users_id, 'new', 'User', $users_id))->isNotNull();
    }
}
