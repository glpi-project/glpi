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

namespace tests\units;

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Search\SearchOption;
use Psr\Log\LogLevel;

class WebhookTest extends \DbTestCase
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
            $this->assertNotNull($id_opt_num);
        }
    }

    public function testGetWebhookBody()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'externalid' => 'ext1234',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $this->assertEquals('ext1234', $ticket->fields['externalid']);

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
            'payload' => $payload,
        ]);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup',
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->assertGreaterThan(0, count($queued_webhook));

        $body = json_decode($queued_webhook['body'], true);

        $this->assertEquals('new', $body['event']);
        $this->assertEquals('ext1234', $body['external_id']);
        $this->assertEquals($fup->getID(), $body['item']['id']);
        $this->assertEquals('Ticket', $body['item']['itemtype']);
        $this->assertEquals($ticket->getID(), $body['item']['items_id']);
        $this->assertEquals('Test followup', $body['item']['content']);
    }

    public function testWebhookURLTemplate()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'externalid' => 'ext1234',
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
            'payload' => $payload,
        ]);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup',
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->assertGreaterThan(0, count($queued_webhook));
        $this->assertEquals('http://localhost/ext1234/new/' . $fup->getID(), $queued_webhook['url']);
    }

    public function testWebhookHeaderTemplate()
    {
        $this->login();

        $ticket = $this->createItem('Ticket', [
            'name' => 'Test ticket',
            'content' => 'Test ticket content',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'externalid' => 'ext1234',
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
            'custom_headers' => $custom_headers,
        ], ['custom_headers']);

        $fup = $this->createItem('ITILFollowup', [
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'content' => 'Test followup',
        ]);

        $queued_webhooks = getAllDataFromTable(\QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
        $queued_webhook = reset($queued_webhooks);

        $this->assertGreaterThan(0, count($queued_webhook));
        $headers = json_decode($queued_webhook['headers'], true);

        $this->assertEquals('new', $headers['X-Test-Event']);
        $this->assertEquals('ext1234', $headers['X-Test-External-ID']);
        $this->assertEquals($fup->getID(), $headers['X-Test-Item-ID']);
        $this->assertEquals('new-ext1234-' . $fup->getID(), $headers['X-Test-Mixed']);
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
        $this->assertNotNull($webhook->getResultForPath('/Administration/User/' . $users_id, 'new', 'User', $users_id));
    }

    public function testGetAPIItemtypeData()
    {
        $this->login();
        $this->initAssetDefinition();

        $supported_types = \Webhook::getAPIItemtypeData();
        foreach ($supported_types as $controller => $type_data) {
            $this->assertTrue(is_subclass_of($controller, AbstractController::class));
            foreach ($type_data as $category => $types) {
                $this->assertMatchesRegularExpression('/main|subtypes/', $category);
                foreach ($types as $type_key => $type) {
                    $this->assertTrue(class_exists($type_key));
                    $this->assertNotEmpty($type);
                }
            }
        }
    }

    public function testGetAPIPath()
    {
        $this->login();

        $webhook = new \Webhook();
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertEquals('/Assets/Computer/' . $computer->getID(), $webhook->getAPIPath($computer));

        $custom_asset = getItemByTypeName('Glpi\\CustomAsset\\Test01Asset', 'TestA');
        $this->assertEquals('/Assets/Custom/Test01/' . $custom_asset->getID(), $webhook->getAPIPath($custom_asset));
    }

    /**
     * Ensure webhooks work even if the HL API is disabled
     * @return void
     */
    public function testWithHLAPIDisabled(): void
    {
        global $CFG_GLPI;
        $this->login();
        $CFG_GLPI['enable_hlapi'] = 0;
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
        $this->assertNotNull($webhook->getResultForPath('/Administration/User/' . $users_id, 'new', 'User', $users_id));
    }

    public function testGetMonacoSuggestions()
    {
        $itemtypes = \Webhook::getItemtypesDropdownValues();

        foreach ($itemtypes as $types) {
            $this->assertIsArray($types);
            foreach ($types as $itemtype => $label) {
                $suggestions = \Webhook::getMonacoSuggestions($itemtype);
                $this->assertNotEmpty($suggestions, "Missing suggestions for $itemtype");
            }
        }
    }

    public function testWebhookNotBlocker(): void
    {
        global $DB;

        $this->createItem(\Webhook::class, [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => \Agent::class,
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 1,
        ]);

        $orig_db = clone $DB;
        $DB = $this->getMockBuilder(\DB::class)
            ->onlyMethods(['tableExists'])
            ->getMock();
        $DB->beginTransaction();
        $DB->method('tableExists')->willReturnCallback(function ($table) {
            if ($table === 'glpi_webhooks') {
                throw new \Exception("Simulated failure");
            }
            return true;
        });

        $agent = $this->createItem(
            \Agent::class,
            [
                'deviceid' => 'any',
                'agenttypes_id' => 0,
                'itemtype' => '',
                'items_id' => 0,
            ]
        );

        $DB = $orig_db;
        $this->hasPhpLogRecordThatContains('Caught Exception: Simulated failure', LogLevel::ERROR);
        $this->hasSessionMessages(
            ERROR,
            [
                sprintf(
                    'An error occurred raising &quot;New&quot; webhook for item Agent (ID %1$s)',
                    $agent->getID()
                ),
            ]
        );
    }
}
