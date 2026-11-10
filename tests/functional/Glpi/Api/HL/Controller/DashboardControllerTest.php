<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Api\HL\Controller;

use Computer;
use Glpi\Api\HL\Controller\DashboardController;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item;
use Glpi\Dashboard\Right;
use Glpi\Tests\HLAPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

class DashboardControllerTest extends HLAPITestCase
{
    /**
     * @return array{owner: int, dashboards: array<string, int>}
     */
    private function createFixtures(): array
    {
        global $DB;

        $owner_id = getItemByTypeName(User::class, TU_USER, true);
        $other_id = getItemByTypeName(User::class, 'post-only', true);

        $make = static function (string $key, int $users_id) use ($DB): int {
            $DB->insert(Dashboard::getTable(), [
                'key' => $key,
                'name' => $key,
                'context' => 'core',
                'users_id' => $users_id,
            ]);
            return $DB->insertId();
        };

        $ids = [
            'hlapi_public' => $make('hlapi_public', 0),
            'hlapi_private_owner' => $make('hlapi_private_owner', $owner_id),
            'hlapi_private_other' => $make('hlapi_private_other', $other_id),
            'hlapi_shared_user' => $make('hlapi_shared_user', $owner_id),
        ];
        $DB->insert(Right::getTable(), [
            'dashboards_dashboards_id' => $ids['hlapi_private_owner'],
            'itemtype' => User::class,
            'items_id' => $other_id,
        ]);
        $DB->insert(Item::getTable(), [
            'dashboards_dashboards_id' => $ids['hlapi_public'],
            'gridstack_id' => 'bn_count_Computer_1',
            'card_id' => 'bn_count_Computer',
            'x' => 0,
            'y' => 0,
            'width' => 2,
            'height' => 2,
            'card_options' => '{"color":"#ffffff"}',
        ]);
        $DB->insert(Item::getTable(), [
            'dashboards_dashboards_id' => $ids['hlapi_public'],
            'gridstack_id' => 'unknown_card_1',
            'card_id' => 'this_card_does_not_exist',
            'x' => 2,
            'y' => 0,
            'width' => 2,
            'height' => 2,
            'card_options' => null,
        ]);

        return ['owner' => $owner_id, 'dashboards' => $ids];
    }

    public function testVisibleDashboardsForAdmin(): void
    {
        $this->login();
        $this->createFixtures();

        $this->graphql->call('query { Dashboard(filter: "key=like=hlapi_*") { key } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $keys = array_column($dashboards, 'key');
                    $this->assertContains('hlapi_public', $keys);
                    $this->assertContains('hlapi_private_owner', $keys);
                    $this->assertContains('hlapi_shared_user', $keys);
                    // Private dashboard of another user, not shared with the current user
                    $this->assertNotContains('hlapi_private_other', $keys);
                });
        });
    }

    public function testVisibleDashboardsForOtherUser(): void
    {
        $this->login('post-only', 'postonly');
        $this->createFixtures();

        $this->graphql->call('query { Dashboard(filter: "key=like=hlapi_*") { key } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $keys = array_column($dashboards, 'key');
                    // Own + shared with them. Public dashboards require the global dashboard view right.
                    $this->assertContains('hlapi_private_other', $keys);
                    $this->assertContains('hlapi_private_owner', $keys);
                    $this->assertNotContains('hlapi_shared_user', $keys);
                });
        });
    }

    public function testFetchByKey(): void
    {
        $this->login();
        $this->createFixtures();

        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { id key name } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $this->assertCount(1, $dashboards);
                    $this->assertEquals('hlapi_public', $dashboards[0]['key']);
                });
        });
    }

    public function testItemValueWithoutRequestingCardFields(): void
    {
        $this->login();
        $this->createFixtures();

        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { items { unique_key value { __typename ... on DashboardCardNumber { number label icon } } } } }', function ($call) {
            // The unknown card is reported as an error, but only for that item. The valid item is still returned.
            $call->response
                ->isPartialError()
                ->data('Dashboard', function ($dashboards) {
                    $items = array_column($dashboards[0]['items'], 'value', 'unique_key');
                    $this->assertArrayHasKey('bn_count_Computer_1', $items);
                    $value = $items['bn_count_Computer_1'];
                    $this->assertIsArray($value);
                    $this->assertEquals('DashboardCardNumber', $value['__typename']);
                    $this->assertArrayHasKey('number', $value);
                    $this->assertNull($items['unknown_card_1']);
                })
                ->errors(function ($errors) {
                    $this->assertCount(1, $errors);
                    $this->assertStringContainsString('this_card_does_not_exist', $errors[0]['message']);
                    $this->assertContains('value', $errors[0]['path']);
                });
        });
    }

    public function testRightsOnlyVisibleToEditors(): void
    {
        $this->login();
        $this->createFixtures();

        $query = 'query { Dashboard(filter: "key==hlapi_private_owner") { rights { itemtype items_id } } }';
        $this->graphql->call($query, function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $this->assertCount(1, $dashboards[0]['rights']);
                    $this->assertEquals(User::class, $dashboards[0]['rights'][0]['itemtype']);
                });
        });

        $this->login('post-only', 'postonly');
        $this->graphql->call($query, function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $this->assertEmpty($dashboards[0]['rights'] ?? []);
                });
        });
    }

    public function testSharedDashboardItemsReturnedForUserWithoutGlobalRight(): void
    {
        $this->login();
        $fixtures = $this->createFixtures();
        // Share the public dashboard (which has items) directly with the other user
        global $DB;
        $DB->insert(Right::getTable(), [
            'dashboards_dashboards_id' => $fixtures['dashboards']['hlapi_public'],
            'itemtype' => User::class,
            'items_id' => getItemByTypeName(User::class, 'post-only', true),
        ]);

        $this->login('post-only', 'postonly');
        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { items { unique_key card } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('Dashboard', function ($dashboards) {
                    $cards = array_column($dashboards[0]['items'], 'card', 'unique_key');
                    $this->assertEquals('bn_count_Computer', $cards['bn_count_Computer_1'] ?? null);
                });
        });
    }

    private function getComputerCountValue(): int
    {
        $number = null;
        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { items { unique_key value { __typename ... on DashboardCardNumber { number label icon } } } } }', function ($call) use (&$number) {
            $call->response
                ->isPartialError()
                ->data('Dashboard', function ($dashboards) use (&$number) {
                    $items = array_column($dashboards[0]['items'], 'value', 'unique_key');
                    $number = $items['bn_count_Computer_1']['number'];
                });
        });
        return $number;
    }

    public function testItemValueIsCachedAsData(): void
    {
        global $GLPI_CACHE;

        $this->login();
        $this->createFixtures();
        $GLPI_CACHE->clear();

        $before = $this->getComputerCountValue();
        $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);

        // Served from the cache, so the new computer is not counted yet
        $this->assertSame($before, $this->getComputerCountValue());

        // Once the cache is cleared the real value is returned
        $GLPI_CACHE->clear();
        $this->assertSame($before + 1, $this->getComputerCountValue());
    }

    public function testItemValueDemoArgument(): void
    {
        global $GLPI_CACHE;

        $this->login();
        $this->createFixtures();
        $GLPI_CACHE->clear();

        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { items { unique_key value(demo: true) { __typename ... on DashboardCardNumber { number } } } } }', function ($call) {
            $call->response
                ->isPartialError()
                ->data('Dashboard', function ($dashboards) {
                    $items = array_column($dashboards[0]['items'], 'value', 'unique_key');
                    $this->assertEquals('DashboardCardNumber', $items['bn_count_Computer_1']['__typename']);
                    $this->assertIsInt($items['bn_count_Computer_1']['number']);
                });
        });

        // Demo data must not be cached, otherwise it could be served in place of real data
        $before = $this->getComputerCountValue();
        $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $GLPI_CACHE->clear();
        $this->assertSame($before + 1, $this->getComputerCountValue());
    }

    public function testItemValueIsTyped(): void
    {
        $this->login();
        $this->createFixtures();

        $this->graphql->call('query { Dashboard(filter: "key==hlapi_public") { items { unique_key value { __typename ... on DashboardCardNumber { number label icon } } } } }', function ($call) {
            $call->response
                ->isPartialError()
                ->data('Dashboard', function ($dashboards) {
                    $items = array_column($dashboards[0]['items'], 'value', 'unique_key');
                    $value = $items['bn_count_Computer_1'];
                    $this->assertEquals('DashboardCardNumber', $value['__typename']);
                    $this->assertIsInt($value['number']);
                    // The label and icon are available, but search URLs are not part of the type
                    $this->assertIsString($value['label']);
                    $this->assertIsString($value['icon']);
                });
        });
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>|null}>
     */
    public static function normalizeProvider(): iterable
    {
        yield 'number' => [
            ['number' => 5, 'url' => 'front/ticket.php?x=1', 'label' => 'Ticket', 'icon' => 'ti ti-alert'],
            [],
            ['_type' => 'DashboardCardNumber', 'number' => 5, 'itemtype' => null, 'label' => 'Ticket', 'icon' => 'ti ti-alert'],
        ];
        yield 'number with search criteria' => [
            ['number' => 2, 'itemtype' => 'Ticket', 's_criteria' => [['field' => 1, 'searchtype' => 'equals', 'value' => 2]], 'label' => 'Late'],
            [],
            ['_type' => 'DashboardCardNumber', 'number' => 2, 'itemtype' => 'Ticket', 'label' => 'Late', 'icon' => null],
        ];
        yield 'list' => [
            ['data' => [['label' => 'New', 'number' => 3, 'url' => 'x', 'color' => '#fff']], 'distributed' => true, 'label' => 'States', 'icon' => 'ti'],
            [],
            [
                '_type' => 'DashboardCardList',
                'distributed' => true,
                'items' => [['label' => 'New', 'number' => 3, 'color' => '#fff']],
                'label' => 'States',
                'icon' => 'ti',
            ],
        ];
        yield 'list without data' => [
            ['data' => ['nodata' => true], 'label' => 'States'],
            [],
            ['_type' => 'DashboardCardList', 'distributed' => null, 'items' => [], 'label' => 'States', 'icon' => null],
        ];
        yield 'empty list' => [
            ['data' => []],
            [],
            ['_type' => 'DashboardCardList', 'distributed' => null, 'items' => [], 'label' => null, 'icon' => null],
        ];
        yield 'series with mixed point formats' => [
            [
                'data' => [
                    'labels' => ['2026-09', '2026-10'],
                    'series' => [
                        ['name' => 'New', 'data' => [['value' => 1, 'url' => 'x'], ['value' => 2, 'url' => 'y']]],
                        ['name' => 'Hours', 'data' => [1.5, 3]],
                    ],
                ],
                'label' => 'Evolution',
            ],
            [],
            [
                '_type' => 'DashboardCardSeries',
                'labels' => ['2026-09', '2026-10'],
                'series' => [
                    ['name' => 'New', 'values' => [1.0, 2.0]],
                    ['name' => 'Hours', 'values' => [1.5, 3.0]],
                ],
                'label' => 'Evolution',
                'icon' => null,
            ],
        ];
        yield 'articles' => [
            ['data' => [['date' => '2026-01-01', 'label' => 'Title', 'content' => 'Text', 'author' => 'glpi', 'url' => 'x']], 'number' => 1],
            [],
            [
                '_type' => 'DashboardCardArticles',
                'number' => 1,
                'articles' => [['date' => '2026-01-01', 'label' => 'Title', 'content' => 'Text', 'author' => 'glpi']],
                'label' => null,
                'icon' => null,
            ],
        ];
        yield 'empty articles' => [
            ['data' => ['nodata' => true], 'number' => 0],
            ['widgettype' => ['articleList']],
            ['_type' => 'DashboardCardArticles', 'number' => 0, 'articles' => [], 'label' => null, 'icon' => null],
        ];
        yield 'unsupported: no number or data' => [['foo' => 'bar'], [], null];
        yield 'unsupported: unknown data structure' => [['data' => ['foo' => 'bar']], [], null];
        yield 'unsupported: scalar data' => [['data' => 'foo'], [], null];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $card
     * @param array<string, mixed>|null $expected
     */
    #[DataProvider('normalizeProvider')]
    public function testNormalize(array $data, array $card, ?array $expected): void
    {
        $this->assertSame($expected, DashboardController::normalizeCardData($data, $card));
    }

    public function testItemValuesUseOneCacheReadAndComputeEachCardOnce(): void
    {
        global $GLPI_CACHE;

        $this->login();
        $this->createFixtures();
        $GLPI_CACHE->clear();

        // Counts the reads and writes of the cached card values
        $original_cache = $GLPI_CACHE;
        $counting_cache = new class ($original_cache) implements \Psr\SimpleCache\CacheInterface {
            /** @var string[] */
            public array $read_keys = [];
            /** @var string[] */
            public array $written_keys = [];
            public int $multiple_reads = 0;

            public function __construct(private readonly \Psr\SimpleCache\CacheInterface $inner) {}

            public function get(string $key, mixed $default = null): mixed
            {
                $this->read_keys[] = $key;
                return $this->inner->get($key, $default);
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->written_keys[] = $key;
                return $this->inner->set($key, $value, $ttl);
            }

            public function delete(string $key): bool
            {
                return $this->inner->delete($key);
            }

            public function clear(): bool
            {
                return $this->inner->clear();
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $this->multiple_reads++;
                $keys = is_array($keys) ? $keys : iterator_to_array($keys);
                array_push($this->read_keys, ...$keys);
                return $this->inner->getMultiple($keys, $default);
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
            {
                return $this->inner->setMultiple($values, $ttl);
            }

            public function deleteMultiple(iterable $keys): bool
            {
                return $this->inner->deleteMultiple($keys);
            }

            public function has(string $key): bool
            {
                return $this->inner->has($key);
            }
        };
        $GLPI_CACHE = $counting_cache;

        try {
            // The same dashboard items selected twice
            $selection = 'value { __typename ... on DashboardCardNumber { number } }';
            $query = 'query { Dashboard(filter: "key==hlapi_public") { first: items { unique_key ' . $selection . ' } second: items { unique_key ' . $selection . ' } } }';
            $this->graphql->call($query, function ($call) {
                $call->response
                    ->isPartialError()
                    ->data('Dashboard', function ($dashboards) {
                        $this->assertEquals($dashboards[0]['first'], $dashboards[0]['second']);
                    });
            });

            $card_prefix = 'hlapi_dashboard_card_';
            $card_reads = array_filter($counting_cache->read_keys, static fn($key) => str_starts_with($key, $card_prefix));
            $card_writes = array_filter($counting_cache->written_keys, static fn($key) => str_starts_with($key, $card_prefix));
            // One card is supported by the fixtures. The unknown one fails before the cache is used
            $this->assertCount(1, $card_reads);
            $this->assertCount(1, $card_writes);
            $this->assertSame(1, $counting_cache->multiple_reads);
        } finally {
            $GLPI_CACHE = $original_cache;
        }
    }

    public function testItemValueAppliesSavedFiltersOfCurrentUser(): void
    {
        global $DB, $GLPI_CACHE;

        $this->login();
        $fixtures = $this->createFixtures();
        $this->createItem(Computer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $GLPI_CACHE->clear();
        $count_without_filters = $this->getComputerCountValue();
        $this->assertGreaterThan(0, $count_without_filters);

        // The filters of other users are not applied
        $DB->insert(\Glpi\Dashboard\Filter::getTable(), [
            'dashboards_dashboards_id' => $fixtures['dashboards']['hlapi_public'],
            'users_id' => getItemByTypeName(User::class, 'post-only', true),
            'filter' => '{"dates":["2000-01-01","2000-01-02"]}',
        ]);
        $GLPI_CACHE->clear();
        $this->assertSame($count_without_filters, $this->getComputerCountValue());

        // The filters saved by the current user are applied, like they are by the web UI
        $DB->insert(\Glpi\Dashboard\Filter::getTable(), [
            'dashboards_dashboards_id' => $fixtures['dashboards']['hlapi_public'],
            'users_id' => $fixtures['owner'],
            'filter' => '{"dates":["2000-01-01","2000-01-02"]}',
        ]);
        // No need to clear the cache. Filters are part of the cache key so values with and without filters are not mixed up.
        $this->assertSame(0, $this->getComputerCountValue());

        // Removing the filters gives the original value back
        $DB->delete(\Glpi\Dashboard\Filter::getTable(), ['users_id' => $fixtures['owner']]);
        $this->assertSame($count_without_filters, $this->getComputerCountValue());
    }
}
