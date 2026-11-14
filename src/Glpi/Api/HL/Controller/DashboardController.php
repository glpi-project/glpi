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

namespace Glpi\Api\HL\Controller;

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Dashboard;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Error\ErrorHandler;
use GraphQL\Deferred;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Session;
use stdClass;
use Throwable;

use function Safe\json_decode;
use function Safe\json_encode;

#[Route(path: '/Dashboards', priority: 1, tags: ['Dashboards'])]
#[Doc\Route(
    parameters: [
        new Doc\Parameter(
            name: 'dashboard_id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
        new Doc\Parameter(
            name: 'id',
            schema: new Doc\Schema(Doc\Schema::TYPE_INTEGER),
            location: Doc\Parameter::LOCATION_PATH,
        ),
    ]
)]
final class DashboardController extends AbstractController
{
    /**
     * How long (in seconds) the data of a card is cached.
     */
    private const CARD_DATA_CACHE_TTL = 300;

    protected static function getRawKnownSchemas(): array
    {
        global $DB;

        // always include core and mini_core contexts
        $known_contexts = ['core', 'mini_core'];
        $iterator = $DB->request([
            'SELECT'          => 'context',
            'DISTINCT'        => true,
            'FROM'            => Dashboard\Dashboard::getTable(),
        ]);
        foreach ($iterator as $data) {
            $context = $data['context'];
            if (!in_array($context, $known_contexts, true)) {
                $known_contexts[] = $context;
            }
        }

        $all_cards = Dashboard\Grid::getAllDasboardCards();
        $all_widgets = [];
        foreach ($all_cards as $card) {
            if (isset($card['widgettype']) && is_array($card['widgettype'])) {
                $all_widgets = array_merge($all_widgets, $card['widgettype']);
            }
        }
        $all_widgets = array_values(array_unique(array_filter($all_widgets)));

        $known_filters = array_map(static fn($f) => $f::getId(), Dashboard\Filter::getRegisteredFilterClasses());

        // The values are already completely resolved. Lists and nested objects must not be resolved by looking up related items,
        // and scalars of nested objects have no schema to look up either, so everything nested has its own resolver.
        $resolver = [self::class, 'graphQLResolveCardValueField'];
        $array_of = static fn(array $items, string $description = '') => [
            'type' => Doc\Schema::TYPE_ARRAY,
            'description' => $description,
            'items' => $items,
            'x-graphql-resolver' => $resolver,
        ];
        $string = static fn(string $description = '', bool $nested = false) => [
            'type' => Doc\Schema::TYPE_STRING,
            'description' => $description,
        ] + ($nested ? ['x-graphql-resolver' => $resolver] : []);
        $integer = static fn(string $description = '', bool $nested = false) => [
            'type' => Doc\Schema::TYPE_INTEGER,
            'format' => Doc\Schema::FORMAT_INTEGER_INT32,
            'description' => $description,
        ] + ($nested ? ['x-graphql-resolver' => $resolver] : []);
        $common = [
            'label' => $string('Localized label of the card data'),
            'icon' => $string('Icon class (webfont) of the card data'),
        ];
        $schema = static fn(string $description, array $properties) => [
            'x-version-introduced' => '2.4.0',
            'x-graphql-noquery' => true,
            'type' => Doc\Schema::TYPE_OBJECT,
            'description' => $description,
            'properties' => $properties + $common,
        ];

        $card_value_members = [
            'DashboardCardNumber' => $schema('A single number', [
                'number' => $integer(),
                'itemtype' => $string('The itemtype counted, if the card is for searching a specific itemtype'),
            ]),
            'DashboardCardList' => $schema('A list of labelled numbers. Empty if there is no data.', [
                'items' => $array_of([
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'properties' => [
                        'label' => $string(nested: true),
                        'number' => $integer(nested: true),
                        'color' => $string(nested: true),
                    ],
                ]),
                'distributed' => ['type' => Doc\Schema::TYPE_BOOLEAN],
            ]),
            'DashboardCardSeries' => $schema('Series of values for a set of labels', [
                'labels' => $array_of(['type' => Doc\Schema::TYPE_STRING], 'Labels of the points on the axis shared by all series'),
                'series' => $array_of([
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'properties' => [
                        'name' => $string(nested: true),
                        'values' => $array_of(
                            ['type' => Doc\Schema::TYPE_NUMBER, 'format' => Doc\Schema::FORMAT_NUMBER_DOUBLE],
                            'One value for each label'
                        ),
                    ],
                ]),
            ]),
            'DashboardCardArticles' => $schema('A list of articles. Empty if there is no data.', [
                'number' => $integer(),
                'articles' => $array_of([
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'properties' => [
                        'date' => $string(nested: true),
                        'label' => $string(nested: true),
                        'content' => $string(nested: true),
                        'author' => $string(nested: true),
                    ],
                ]),
            ]),
        ];

        return $card_value_members + [
            'Dashboard' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Dashboard::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-rights-conditions' => [
                    'read' => [self::class, 'getDashboardReadCriteria'],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'key' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'readOnly' => true,
                    ],
                    'name' => [
                        'type' => Doc\Schema::TYPE_STRING,
                    ],
                    'context' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Dashboard context which controls where it may be used',
                        'enum' => $known_contexts,
                    ],
                    'user' => self::getDropdownTypeSchema(class: \User::class, full_schema: 'User'),
                    'filters' => self::getChildrenTypeSchema(
                        parent_class: Dashboard\Dashboard::class,
                        class: Dashboard\Filter::class,
                        name_field: null,
                        full_schema: 'DashboardFilter',
                        graphql_only: true,
                        params: ['x-version-introduced' => '2.3.0']
                    ),
                    'items' => self::getChildrenTypeSchema(
                        parent_class: Dashboard\Dashboard::class,
                        class: Dashboard\Item::class,
                        name_field: null,
                        full_schema: 'DashboardItem',
                        graphql_only: true,
                        params: ['x-version-introduced' => '2.3.0']
                    ),
                    'rights' => self::getChildrenTypeSchema(
                        parent_class: Dashboard\Dashboard::class,
                        class: Dashboard\Right::class,
                        name_field: null,
                        full_schema: 'DashboardRight',
                        graphql_only: true,
                        params: ['x-version-introduced' => '2.4.0']
                    ),
                ],
            ],
            'DashboardFilter' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Filter::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-skip-itemtype-canview' => true, // access is governed by the dashboard visibility and the conditions below
                'x-rights-conditions' => [
                    'read' => [self::class, 'getDashboardFilterReadCriteria'],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'user' => self::getDropdownTypeSchema(class: \User::class, full_schema: 'User'),
                    'filter' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'JSON encoded filters',
                    ],
                ],
            ],
            'DashboardItem' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Item::class,
                'x-graphql-noquery' => true,
                'x-skip-itemtype-canview' => true, // access is governed by the visibility of the parent dashboard
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'unique_key' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'gridstack_id',
                        'description' => 'Unique key of the item in the dashboard',
                    ],
                    'card' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'x-field' => 'card_id',
                        'description' => 'The card type of the dashboard item. Usually corresponds to a widget key and some extra information. For example, "bn_count_Computer" is big number widget showing the count of computers.',
                    ],
                    'x' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'X position of the item in the dashboard grid',
                    ],
                    'y' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Y position of the item in the dashboard grid',
                    ],
                    'width' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Width of the item in the dashboard grid',
                    ],
                    'height' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'Height of the item in the dashboard grid',
                    ],
                    'card_options' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'JSON encoded options specific to the card type',
                    ],
                    'value' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        // A computed value has no properties of its own to flatten
                        'properties' => [],
                        'oneOf' => array_map(
                            static fn(string $schema_name) => ['$ref' => '#/components/schemas/' . $schema_name],
                            array_keys($card_value_members)
                        ),
                        'discriminator' => ['propertyName' => '_type'],
                        'x-version-introduced' => '2.4.0',
                        'x-graphql-only' => true,
                        'readOnly' => true,
                        'description' => 'The data of the dashboard item. The type depends on the card. Search URLs are not included. Null if the card data is not supported.',
                        'x-graphql-requires' => ['card', 'card_options', 'dashboard'],
                        'x-graphql-args' => [
                            'demo' => [
                                'type' => Type::boolean(),
                                'defaultValue' => false,
                                'description' => 'Return randomly generated demo data instead of real data. Demo data is never cached.',
                            ],
                        ],
                        'x-graphql-resolver' => [self::class, 'graphQLResolveDashboardItemValue'],
                    ],
                ],
            ],
            'DashboardRight' => [
                'x-version-introduced' => '2.2.0',
                'x-itemtype' => Dashboard\Right::class,
                'x-graphql-noquery' => true,
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-skip-itemtype-canview' => true, // access is governed by the dashboard visibility and the conditions below
                'x-rights-conditions' => [
                    'read' => [self::class, 'getDashboardRightReadCriteria'],
                ],
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'readOnly' => true,
                    ],
                    'dashboard' => self::getDropdownTypeSchema(class: Dashboard\Dashboard::class, full_schema: 'Dashboard'),
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'Type of the item the right is granted to',
                        'enum' => [
                            \User::class,
                            \Group::class,
                            \Entity::class,
                            \Profile::class,
                        ],
                    ],
                    'items_id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => 'ID of the item the right is granted to',
                    ],
                ],
            ],
            'DashboardCard' => [
                'x-version-introduced' => '2.2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'card' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The card type key',
                    ],
                    'widget' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'List of widget types that can be used to display this card',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'enum' => $all_widgets,
                        ],
                    ],
                    'group' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The localized group name this card belongs to',
                    ],
                    'itemtype' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The itemtype this card is related to, if any',
                    ],
                    'label' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => 'The localized label of the card',
                    ],
                    'filters' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'description' => 'List of filters applicable to this card',
                        'items' => [
                            'type' => Doc\Schema::TYPE_STRING,
                            'enum' => $known_filters,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Restrict dashboards to those the current user may view.
     * SQL equivalent of {@see Dashboard\Dashboard::canViewCurrent()}.
     * @return array<string, mixed>|false
     */
    public static function getDashboardReadCriteria(): array|false
    {
        $user_id = Session::getLoginUserID();
        if (!$user_id) {
            return false;
        }

        $conditions = [
            // Own dashboards (private or not)
            ['_.users_id' => $user_id],
        ];
        if (Dashboard\Dashboard::canView()) {
            // Public dashboards
            $conditions[] = ['_.users_id' => 0];
        }

        // Dashboards shared with the user through users, entities, profiles or groups
        $share_conditions = [
            ['itemtype' => \User::class, 'items_id' => $user_id],
        ];
        if (!empty($_SESSION['glpiactiveentities'])) {
            $share_conditions[] = ['itemtype' => \Entity::class, 'items_id' => array_map('intval', $_SESSION['glpiactiveentities'])];
        }
        if (isset($_SESSION['glpiactiveprofile']['id'])) {
            $share_conditions[] = ['itemtype' => \Profile::class, 'items_id' => (int) $_SESSION['glpiactiveprofile']['id']];
        }
        if (!empty($_SESSION['glpigroups'])) {
            $share_conditions[] = ['itemtype' => \Group::class, 'items_id' => array_map('intval', $_SESSION['glpigroups'])];
        }
        $conditions[] = [
            '_.id' => new QuerySubQuery([
                'SELECT' => 'dashboards_dashboards_id',
                'FROM' => Dashboard\Right::getTable(),
                'WHERE' => ['OR' => $share_conditions],
            ]),
        ];

        return ['WHERE' => ['OR' => $conditions]];
    }

    /**
     * Saved filters are personal.
     * @return array<string, mixed>|false
     */
    public static function getDashboardFilterReadCriteria(): array|false
    {
        $user_id = Session::getLoginUserID();
        if (!$user_id) {
            return false;
        }
        return ['WHERE' => ['_.users_id' => $user_id]];
    }

    /**
     * Only users that can update a dashboard may see who it is shared with.
     * SQL equivalent of {@see Dashboard\Dashboard::canUpdateCurrent()}.
     * @return array<string, mixed>|bool
     */
    public static function getDashboardRightReadCriteria(): array|bool
    {
        $user_id = Session::getLoginUserID();
        if (!$user_id) {
            return false;
        }
        if (Dashboard\Dashboard::canUpdate()) {
            return true;
        }
        return [
            'WHERE' => [
                '_.dashboards_dashboards_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM' => Dashboard\Dashboard::getTable(),
                    'WHERE' => ['users_id' => $user_id],
                ]),
            ],
        ];
    }

    /**
     * Normalize the data returned by `Glpi\Dashboard\Grid::getCardData()` for the GraphQL types.
     * @param array<string, mixed> $data Card data
     * @param array<string, mixed> $card Card definition from the catalog
     * @return array<string, mixed>|null The normalized data, or null if the data doesn't match any type
     */
    public static function normalizeCardData(array $data, array $card): ?array
    {
        $common = [
            'label' => isset($data['label']) ? (string) $data['label'] : null,
            'icon' => isset($data['icon']) ? (string) $data['icon'] : null,
        ];

        if (!array_key_exists('data', $data)) {
            if (!isset($data['number'])) {
                return null;
            }
            return [
                '_type' => 'DashboardCardNumber',
                'number' => (int) $data['number'],
                'itemtype' => isset($data['itemtype']) ? (string) $data['itemtype'] : null,
            ] + $common;
        }

        $rows = $data['data'];
        if (!is_array($rows)) {
            return null;
        }

        // Series
        if (isset($rows['series']) && is_array($rows['series'])) {
            return [
                '_type' => 'DashboardCardSeries',
                'labels' => array_map('strval', array_values((array) ($rows['labels'] ?? []))),
                'series' => array_map(static fn($series) => [
                    'name' => (string) ($series['name'] ?? ''),
                    // Points are either plain numbers or objects with a value (and URL)
                    'values' => array_map(
                        static function ($point) {
                            $value = is_array($point) ? ($point['value'] ?? null) : $point;
                            return $value === null ? null : (float) $value;
                        },
                        array_values((array) ($series['data'] ?? []))
                    ),
                ], array_values($rows['series'])),
            ] + $common;
        }

        // Providers use a "nodata" marker instead of an empty list
        if (array_key_exists('nodata', $rows)) {
            $rows = [];
        }
        if (!array_is_list($rows)) {
            return null;
        }

        $is_articles = ($rows !== [] && array_key_exists('content', $rows[0]))
            || ($rows === [] && in_array('articleList', $card['widgettype'] ?? [], true));
        if ($is_articles) {
            return [
                '_type' => 'DashboardCardArticles',
                'number' => (int) ($data['number'] ?? count($rows)),
                'articles' => array_map(static fn($row) => [
                    'date' => isset($row['date']) ? (string) $row['date'] : null,
                    'label' => isset($row['label']) ? (string) $row['label'] : null,
                    'content' => isset($row['content']) ? (string) $row['content'] : null,
                    'author' => isset($row['author']) ? (string) $row['author'] : null,
                ], $rows),
            ] + $common;
        }

        return [
            '_type' => 'DashboardCardList',
            'distributed' => isset($data['distributed']) ? (bool) $data['distributed'] : null,
            'items' => array_map(static fn($row) => [
                'label' => isset($row['label']) ? (string) $row['label'] : null,
                'number' => isset($row['number']) ? (int) $row['number'] : null,
                'color' => isset($row['color']) ? (string) $row['color'] : null,
            ], $rows),
        ] + $common;
    }

    /**
     * Resolve a field of a normalized card value. The data is already completely resolved, so the fields are read as they are.
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function graphQLResolveCardValueField(mixed $source, array $args, stdClass $context, ResolveInfo $info): mixed
    {
        return $source[$info->fieldName] ?? null;
    }

    /**
     * Cache key for the data of a card.
     *
     * Only the data is cached, never any formatted output, so format related settings (date/number format, timezone, ...)
     * are intentionally not part of the key. The key covers what can change the data itself:
     * - the card and its options (including the filters to apply)
     * - the language, as names of statuses, categories, etc. are translated
     * - the viewer: user, active profile, active entities and groups
     *   Most providers only depend on the active entities, but some also limit the data to what the user is involved in
     *   (see {@see \Ticket::getCriteriaFromProfile()} and the cards checking the validations targeting the user).
     *   These factors are always included to be safe, even if it means less sharing between users, as card data is small.
     * @param string $card_id
     * @param array<string, mixed> $card Card definition
     * @param array<string, mixed> $options Decoded card options
     * @return string
     */
    private static function getCardDataCacheKey(string $card_id, array $card, array $options): string
    {
        $normalize = static function (array $array) use (&$normalize): array {
            ksort($array);
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = $normalize($value);
                }
            }
            return $array;
        };

        $groups = array_map('intval', $_SESSION['glpigroups'] ?? []);
        sort($groups);

        return 'hlapi_dashboard_card_' . sha1(json_encode([
            'card' => $card_id,
            'options' => $normalize($options),
            'language' => Session::getLanguage() ?? '',
            'user' => Session::getLoginUserID(),
            'profile' => $_SESSION['glpiactiveprofile']['id'] ?? 0,
            'entities' => $_SESSION['glpiactiveentities_string'] ?? '',
            'groups' => $groups,
        ]));
    }

    /**
     * @param mixed $source The dashboard item row. `card`, `card_options` and `dashboard` are always loaded because of `x-graphql-requires`.
     * @param array<string, mixed> $args
     * @param stdClass $context
     * @param ResolveInfo $info
     * @return array<string, mixed>|Deferred|null The normalized card data, or null if the data of the card is not supported (see {@see self::normalizeCardData()})
     * @throws Error If the value cannot be computed. The field is nullable, so GraphQL reports the error against this
     *               item only and the client can decide how to handle it (hide the item, show an error, etc).
     */
    public static function graphQLResolveDashboardItemValue(mixed $source, array $args, stdClass $context, ResolveInfo $info): array|Deferred|null
    {
        $card_id = $source['card'] ?? null;
        if (!is_string($card_id) || $card_id === '') {
            throw new Error('Dashboard item has no card');
        }

        // Like the web UI, access to the card data is governed by the dashboard visibility: the dashboard creator is
        // responsible for sharing it, and card data is typically a summary without much detail.
        // The card catalog is the same for every item, so only load it once per request
        $context->dashboard_cards ??= Dashboard\Grid::getAllDasboardCards();
        $card = $context->dashboard_cards[$card_id] ?? null;
        if ($card === null) {
            throw new Error('Dashboard card "' . $card_id . '" is not available');
        }

        $options = [];
        if (is_string($source['card_options'] ?? null) && $source['card_options'] !== '') {
            $options = importArrayFromDB($source['card_options']);
            if (!is_array($options)) {
                $options = [];
            }
        }

        // Like the web UI, the filters the current user saved for the dashboard are always applied.
        // They are part of the options, so they are also part of the cache key.
        $dashboards_id = (int) ($source['dashboard' . chr(0x1F) . 'id'] ?? 0);
        if ($dashboards_id > 0) {
            $context->dashboard_filters ??= [];
            $context->dashboard_filters[$dashboards_id] ??= json_decode(Dashboard\Filter::getForDashboard($dashboards_id), true);
            $filters = $context->dashboard_filters[$dashboards_id];
            if (is_array($filters) && $filters !== []) {
                $options['args']['apply_filters'] = $filters;
            }
        }

        $demo = (bool) ($args['demo'] ?? false);
        // Demo data is random, and cards can opt out of caching (like in the web UI)
        if ($demo || !($card['cache'] ?? true)) {
            return self::computeCardValue($card_id, $card, $options, $demo);
        }

        // All the items of a dashboard are resolved together, so their cache entries are read with a single request to the cache.
        // Every item registers its key before the first deferred callback runs. The callbacks are run after all the items are resolved.
        $cache_key = self::getCardDataCacheKey($card_id, $card, $options);
        $batch = $context->dashboard_card_cache ??= new stdClass();
        $batch->pending ??= [];
        $batch->loaded ??= [];
        $batch->pending[$cache_key] = true;

        return new Deferred(static function () use ($batch, $cache_key, $card_id, $card, $options): ?array {
            global $GLPI_CACHE;

            if (!array_key_exists($cache_key, $batch->loaded)) {
                $to_read = array_keys(array_diff_key($batch->pending, $batch->loaded));
                $read = iterator_to_array($GLPI_CACHE->getMultiple($to_read));
                foreach ($to_read as $key) {
                    // Null means not cached
                    $batch->loaded[$key] = $read[$key] ?? null;
                }
            }

            $cached = $batch->loaded[$cache_key];
            if (is_array($cached)) {
                return $cached;
            }
            if ($cached === false) {
                // Cached "unsupported" result
                return null;
            }

            $value = self::computeCardValue($card_id, $card, $options, false);
            $GLPI_CACHE->set($cache_key, $value ?? false, self::CARD_DATA_CACHE_TTL);
            // The same card may be in the dashboard several times
            $batch->loaded[$cache_key] = $value ?? false;
            return $value;
        });
    }

    /**
     * Get the normalized data of a card, without using the cache.
     * @param string $card_id
     * @param array<string, mixed> $card Card definition from the catalog
     * @param array<string, mixed> $options Decoded card options
     * @param bool $demo Whether to use generated demo data
     * @return array<string, mixed>|null The normalized data, or null if the data of the card is not supported
     * @throws Error If the data cannot be computed
     */
    private static function computeCardValue(string $card_id, array $card, array $options, bool $demo): ?array
    {
        try {
            $data = Dashboard\Grid::getCardData($card_id, $options, $demo);
            // The options of the card are merged into the data, but the client already has them
            $data = array_diff_key($data, $options['args'] ?? []);
            return self::normalizeCardData($data, $card);
        } catch (Throwable $e) {
            // Log the details server-side, but only expose a generic message to the client
            ErrorHandler::logCaughtException($e);
            throw new Error('Unable to get the value of the dashboard card "' . $card_id . '"');
        }
    }
}
