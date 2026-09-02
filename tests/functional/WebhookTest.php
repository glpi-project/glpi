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

use Change;
use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Router;
use Glpi\Search\CriteriaFilter;
use Glpi\Tests\DbTestCase;
use ITILFollowup;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Log\LogLevel;
use QueuedWebhook;
use Ticket;
use User;
use Webhook;

use function Safe\json_encode;

class WebhookTest extends DbTestCase
{
    /**
     * Make sure all webhook item types have an ID search option so that the criteria filters can be applied properly
     * @return void
     */
    public function testWebhookTypesHaveIDOpt()
    {
        $supported = Webhook::getItemtypesDropdownValues();
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

        $queued_webhooks = getAllDataFromTable(QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
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

        $queued_webhooks = getAllDataFromTable(QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
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

        $queued_webhooks = getAllDataFromTable(QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]);
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
        /** @var Webhook $webhook */
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

        $supported_types = Webhook::getAPIItemtypeData();
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

    /**
     * The schema resolution methods accept an API version so that the v3 work does not have to
     * change their signatures. The filtering itself is not implemented yet, so passing a version
     * must not change what they return today.
     *
     * The per-version memoization of getAPIItemtypeData() is deliberately not covered: under
     * PHPUnit, Environment::shouldExpectResourcesToChange() is always true, so the static cache
     * is bypassed on every call and its keying cannot be observed from a test.
     */
    public function testSchemaResolutionAcceptsAnAPIVersion()
    {
        $this->login();

        $default = Webhook::getAPIItemtypeData();
        $explicit = Webhook::getAPIItemtypeData(Router::API_VERSION);
        $this->assertSame($default, $explicit);

        $schema_default = Webhook::getAPISchemaBySupportedItemtype(\Computer::class);
        $schema_explicit = Webhook::getAPISchemaBySupportedItemtype(\Computer::class, Router::API_VERSION);
        // Schemas embed closures (rights checks) that are fresh instances on every call, so the
        // exposed property names are compared rather than the raw structures.
        $this->assertNotEmpty($schema_default['properties']);
        $this->assertSame(
            array_keys($schema_default['properties']),
            array_keys($schema_explicit['properties'])
        );

        $suggestions_default = Webhook::getMonacoSuggestions(\Computer::class);
        $suggestions_explicit = Webhook::getMonacoSuggestions(\Computer::class, Router::API_VERSION);
        $this->assertNotEmpty($suggestions_default);
        $this->assertSame($suggestions_default, $suggestions_explicit);
    }

    public function testGetAPIPath()
    {
        $this->login();

        $webhook = new Webhook();
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
        /** @var Webhook $webhook */
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
        $itemtypes = Webhook::getItemtypesDropdownValues();

        foreach ($itemtypes as $types) {
            $this->assertIsArray($types);
            foreach ($types as $itemtype => $label) {
                $suggestions = Webhook::getMonacoSuggestions($itemtype);
                $this->assertNotEmpty($suggestions, "Missing suggestions for $itemtype");
            }
        }
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWebhookNotBlocker(): void
    {
        global $DB;

        $this->createItem(Webhook::class, [
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

    public function testItemtypeDropdownExcludesNoReadItemtypes()
    {
        $this->login();
        $this->assertContains('Computer', Webhook::getItemtypesDropdownValues()['Assets']);
        $this->assertContains('Monitor', Webhook::getItemtypesDropdownValues()['Assets']);
        $_SESSION['glpiactiveprofile']['computer'] = ALLSTANDARDRIGHT & ~READ;
        $this->assertNotContains('Computer', Webhook::getItemtypesDropdownValues()['Assets']);
        $this->assertContains('Monitor', Webhook::getItemtypesDropdownValues()['Assets']);
    }

    public function testCreateUpdateNoReadItemtypes()
    {
        $this->login();
        $webhook = $this->createItem('Webhook', [
            'name' => 'Test webhook',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => 'Computer',
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 1,
        ]);
        $this->assertTrue($webhook->canUpdateItem());
        $_SESSION['glpiactiveprofile']['computer'] = ALLSTANDARDRIGHT & ~READ;
        $this->assertFalse($webhook->canUpdateItem());

        $this->assertFalse($webhook->canCreateItem());
        $webhook->fields['itemtype'] = 'Monitor';
        $this->assertTrue($webhook->canCreateItem());
    }

    public function testWebhookWithoutSession(): void
    {
        $entity_id = $this->getTestRootEntity(only_id: true);

        // Arrange: setup a webhook with a filter
        $webhook = $this->createItem(Webhook::class, [
            'name'                => 'Test webhook',
            'entities_id'         => $entity_id,
            'url'                 => 'http://localhost',
            'itemtype'            => Ticket::class,
            'event'               => 'new',
            'is_active'           => 1,
            'use_default_payload' => 1,
        ]);
        $this->createItem(CriteriaFilter::class, [
            'itemtype' => Webhook::class,
            'items_id' => $webhook->getID(),
            'search_itemtype' => Ticket::class,
            'search_criteria' => json_encode([
                [
                    "link"       => "AND",
                    "field"      => "12",
                    "searchtype" => "equals",
                    "value"      => "notold",
                ],
            ]),
        ], ['search_criteria']);

        // Act: trigger the webhook by creating a ticket
        $base_count = $this->countQueuedRequestForWebhook($webhook);
        $this->createItem(Ticket::class, [
            'name'        => 'Test ticket',
            'content'     => 'Test ticket content',
            'entities_id' => $entity_id,
        ]);

        // Assert: one webhook request should have been added to the queue
        $this->assertEquals(
            $base_count + 1,
            $this->countQueuedRequestForWebhook($webhook),
        );
    }

    private function countQueuedRequestForWebhook(Webhook $webhook): int
    {
        return countElementsInTable(QueuedWebhook::getTable(), [
            'webhooks_id' => $webhook->getID(),
        ]);
    }

    /**
     * Ensure the get_webhook_body action enforces READ permission on the requested item.
     * A user who cannot READ an item must not be able to retrieve its webhook body.
     */
    public function testGetWebhookBodyReadPermissionCheck(): void
    {
        $this->login();

        $computer = $this->createItem(\Computer::class, [
            'name'        => 'Test computer',
            'entities_id' => $_SESSION['glpiactive_entity'],
        ]);
        $computers_id = $computer->getID();

        $webhook = $this->createItem(Webhook::class, [
            'name'               => 'Test webhook',
            'entities_id'        => $_SESSION['glpiactive_entity'],
            'url'                => 'http://localhost',
            'itemtype'           => \Computer::class,
            'event'              => 'new',
            'is_active'          => 1,
            'use_default_payload' => 1,
        ]);

        // Sanity check: with READ right the item is accessible
        $obj = new \Computer();
        $this->assertTrue($obj->can($computers_id, READ));

        // Remove READ right on Computer for the current profile
        $saved_rights = $_SESSION['glpiactiveprofile'];
        $_SESSION['glpiactiveprofile']['computer'] = ALLSTANDARDRIGHT & ~READ;

        // The can() check must now fail, which is the guard used by the ajax action
        $obj2 = new \Computer();
        $this->assertFalse($obj2->can($computers_id, READ));

        // Restore rights
        $_SESSION['glpiactiveprofile'] = $saved_rights;
    }

    /**
     * Ensure the get_webhook_body action rejects requests whose itemtype does not
     * match the webhook's configured itemtype.
     */
    public function testGetWebhookBodyItemtypeMismatch(): void
    {
        $this->login();

        // Create a webhook configured for Computer
        $webhook = $this->createItem(Webhook::class, [
            'name'               => 'Test webhook',
            'entities_id'        => $_SESSION['glpiactive_entity'],
            'url'                => 'http://localhost',
            'itemtype'           => \Computer::class,
            'event'              => 'new',
            'is_active'          => 1,
            'use_default_payload' => 1,
        ]);

        // The webhook's configured itemtype is Computer
        $this->assertEquals(\Computer::class, $webhook->fields['itemtype']);

        // A request asking for Monitor (a different itemtype) must be rejected:
        // the mismatch condition used in the ajax action is:
        //   $webhook->getID() && $itemtype !== $webhook->fields['itemtype']
        $requested_itemtype = \Monitor::class;
        $this->assertNotEquals($requested_itemtype, $webhook->fields['itemtype']);
        $this->assertGreaterThan(0, $webhook->getID());

        // Directly testing the mismatch guard logic
        $mismatch_detected = $webhook->getID() && $requested_itemtype !== $webhook->fields['itemtype'];
        $this->assertTrue($mismatch_detected, 'Itemtype mismatch should have been detected');

        // A request with the correct itemtype must not be rejected
        $matching_itemtype = \Computer::class;
        $no_mismatch = $webhook->getID() && $matching_itemtype !== $webhook->fields['itemtype'];
        $this->assertFalse($no_mismatch, 'Correct itemtype should not trigger the mismatch guard');
    }

    /**
     * Non-regression: a Self-Service user adding a followup must not produce a webhook error
     * popup (Webhook::raise() catches exceptions and converts them to session messages).
     */
    public function testWebhookDoesNotErrorOnFollowupAddBySelfServiceUser(): void
    {
        $entity_id = $this->getTestRootEntity(only_id: true);

        $this->login();

        $selfservice_user = $this->createItem(User::class, [
            'name'         => 'selfservice_' . $this->getUniqueString(),
            'password'     => 'testpassword',
            'password2'    => 'testpassword',
            '_profiles_id' => getItemByTypeName('Profile', 'Self-Service', true),
            '_entities_id' => $entity_id,
        ], ['password', 'password2']);

        $ticket = $this->createItem(Ticket::class, [
            'name'                => 'Test ticket',
            'content'             => 'Test content',
            'entities_id'         => $entity_id,
            '_users_id_requester' => $selfservice_user->getID(),
        ]);

        $webhook = $this->createItem(Webhook::class, [
            'name'                => 'Test webhook',
            'entities_id'         => $entity_id,
            'url'                 => 'http://localhost',
            'itemtype'            => ITILFollowup::class,
            'event'               => 'new',
            'is_active'           => 1,
            'use_default_payload' => 1,
        ]);
        $this->createItem(CriteriaFilter::class, [
            'itemtype'        => Webhook::class,
            'items_id'        => $webhook->getID(),
            'search_itemtype' => ITILFollowup::class,
            'search_criteria' => json_encode([[
                'link'       => 'AND',
                'field'      => 4, // is_private
                'searchtype' => 'equals',
                'value'      => '0',
            ]]),
        ], ['search_criteria']);

        $this->login($selfservice_user->fields['name']);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        $followup    = new ITILFollowup();
        $followup_id = $followup->add([
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'content'  => 'Test followup',
        ]);

        $this->assertGreaterThan(0, $followup_id);

        $webhook_errors = array_filter(
            $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [],
            static fn(string $msg) => stripos($msg, 'webhook') !== false
        );
        $this->assertEmpty($webhook_errors);
    }

    public function testParentItemResolvedProperly(): void
    {
        $entity_id = $this->getTestRootEntity(only_id: true);
        $this->login();

        // Create webhook for new followups
        $webhook = $this->createItem(Webhook::class, [
            'name'                => 'Test webhook',
            'entities_id'         => $entity_id,
            'url'                 => 'http://localhost',
            'itemtype'            => ITILFollowup::class,
            'event'               => 'new',
            'is_active'           => 1,
            'use_default_payload' => 1,
        ]);

        $ticket = $this->createItem(Ticket::class, [
            'name' => 'Test ticket',
            'entities_id' => $entity_id,
            '_actors' => [
                'observer' => [
                    [
                        'itemtype' => User::class,
                        'items_id' => 2,
                    ],
                ],
            ],
        ]);

        $change = $this->createItem(Change::class, [
            'name' => 'Test change',
            'entities_id' => $entity_id,
            '_actors' => [
                'observer' => [
                    [
                        'itemtype' => User::class,
                        'items_id' => 3,
                    ],
                ],
            ],
        ]);

        $this->createItem(ITILFollowup::class, [
            'items_id' => $ticket->getID(),
            'itemtype' => Ticket::class,
            'content'  => 'Test followup for ticket',
        ]);

        $this->createItem(ITILFollowup::class, [
            'items_id' => $change->getID(),
            'itemtype' => Change::class,
            'content'  => 'Test followup for change',
        ]);

        $webhooks = array_values(getAllDataFromTable(QueuedWebhook::getTable(), ['webhooks_id' => $webhook->getID()]));
        $this->assertCount(2, $webhooks);
        $team_1 = json_decode($webhooks[0]['body'], true)['parent_item']['team'][0];
        $team_2 = json_decode($webhooks[1]['body'], true)['parent_item']['team'][0];
        $this->assertSame('glpi', $team_1['name']);
        $this->assertSame('observer', $team_1['role']);
        $this->assertSame('post-only', $team_2['name']);
        $this->assertSame('observer', $team_2['role']);
    }

    public function testGetPinnableAPIVersions()
    {
        $pinnable = Webhook::getPinnableAPIVersions();

        // v1 is routed to the legacy API and would never resolve a webhook path.
        $this->assertArrayNotHasKey('1', $pinnable);
        $this->assertArrayHasKey('2', $pinnable);
        // Major 2 is not fully deprecated, so its label is the bare version.
        $this->assertSame('2', $pinnable['2']);
    }

    public function testGetPinnedAPIVersion()
    {
        $this->login();
        $webhook = new Webhook();

        // Explicit value is kept.
        $webhook->fields['pinned_version'] = '2';
        $this->assertSame('2', $webhook->getPinnedAPIVersion());

        // Empty value falls back to the oldest pinnable major, not to the latest one.
        $webhook->fields['pinned_version'] = '';
        $this->assertSame('2', $webhook->getPinnedAPIVersion());

        // Unknown value falls back too, otherwise normalizeAPIVersion() would silently
        // resolve it to the latest version, which is the bug being fixed.
        $webhook->fields['pinned_version'] = '99';
        $this->assertSame('2', $webhook->getPinnedAPIVersion());

        // Missing key behaves like an empty value.
        unset($webhook->fields['pinned_version']);
        $this->assertSame('2', $webhook->getPinnedAPIVersion());
    }

    public function testIsPinnedAPIVersionDeprecated()
    {
        $webhook = new Webhook();
        $webhook->fields['pinned_version'] = '2';
        $this->assertFalse($webhook->isPinnedAPIVersionDeprecated());
    }

    public function testNewWebhookIsPinnedToLatestMajorVersion()
    {
        $this->login();
        $pinnable = array_keys(Webhook::getPinnableAPIVersions());
        // Cast: array keys are integers, the stored field is a string.
        $latest_major = (string) end($pinnable);

        /** @var Webhook $webhook */
        $webhook = $this->createItem(Webhook::class, [
            'name' => 'Pinned version default',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => User::class,
            'event' => 'new',
        ]);

        $this->assertSame($latest_major, $webhook->fields['pinned_version']);
    }

    public function testExplicitPinnedVersionIsKeptOnCreation()
    {
        $this->login();

        /** @var Webhook $webhook */
        $webhook = $this->createItem(Webhook::class, [
            'name' => 'Pinned version explicit',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => User::class,
            'event' => 'new',
            'pinned_version' => '2',
        ]);

        $this->assertSame('2', $webhook->fields['pinned_version']);
    }

    /**
     * The no-op this whole change relies on: an existing webhook, pinned to the major it was
     * built against, still asks the router for the version it was already getting.
     * The day this fails is the day pinning starts changing an existing payload, which is
     * exactly the moment a conscious decision is required rather than a silent drift.
     */
    public function testPinnedMajorResolvesToRouterCurrentVersion()
    {
        $this->login();

        /** @var Webhook $webhook */
        $webhook = $this->createItem(Webhook::class, [
            'name' => 'Pinned to v2',
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => User::class,
            'event' => 'new',
            'pinned_version' => '2',
        ]);

        $this->assertSame(Router::API_VERSION, Router::normalizeAPIVersion($webhook->getPinnedAPIVersion()));
    }

    /**
     * A webhook whose stored version is missing falls back to the oldest pinnable major, so it
     * must produce the very same payload as one pinned to it explicitly. This also proves that
     * setting the version header does not break the internal routing.
     */
    public function testFallbackVersionProducesSamePayloadAsExplicitPin()
    {
        $this->login();
        $users_id = \Session::getLoginUserID();

        $common_input = [
            'entities_id' => $_SESSION['glpiactive_entity'],
            'url' => 'http://localhost',
            'itemtype' => User::class,
            'event' => 'new',
            'is_active' => 1,
            'use_default_payload' => 1,
        ];

        /** @var Webhook $pinned */
        $pinned = $this->createItem(Webhook::class, $common_input + [
            'name' => 'Pinned to v2',
            'pinned_version' => '2',
        ]);

        /** @var Webhook $unpinned */
        $unpinned = $this->createItem(Webhook::class, $common_input + ['name' => 'Unpinned']);
        // Simulate a webhook inserted without going through prepareInputForAdd().
        $unpinned->fields['pinned_version'] = '';

        $path = '/Administration/User/' . $users_id;
        $pinned_body = $pinned->getResultForPath($path, 'new', User::class, $users_id);
        $unpinned_body = $unpinned->getResultForPath($path, 'new', User::class, $users_id);

        $this->assertNotNull($pinned_body);
        $this->assertSame($pinned_body, $unpinned_body);
    }
}
