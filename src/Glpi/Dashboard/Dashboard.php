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

namespace Glpi\Dashboard;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Debug\Profiler;
use Glpi\Exception\TooManyResultsException;
use Ramsey\Uuid\Uuid;
use Session;
use Toolbox;

use function Safe\json_decode;

class Dashboard extends CommonDBTM
{
    /** @var int */
    protected $id      = 0;
    /** @var string */
    protected $key     = "";
    /** @var string */
    protected $title   = "";
    /** @var bool */
    protected $embed   = false;
    /** @var ?array  */
    protected $items   = null;
    /** @var ?array */
    protected $rights  = null;
    /** @var string */
    protected $filters  = "";

    /** @var array */
    public static $all_dashboards = [];
    public static $rightname = 'dashboard';


    public function __construct(string $dashboard_key = "")
    {
        $this->key = $dashboard_key;
    }


    public static function getIndexName()
    {
        return "key";
    }


    /**
     * Retrieve the current dashboard from the DB (or from cache)
     * with its rights and items
     *
     * @param bool $force if true, don't use cache
     *
     * @return false|int Id of the loaded dashboard, or false on failure
     */
    public function load(bool $force = false)
    {
        Profiler::getInstance()->start(__METHOD__);
        $loaded = true;
        if (
            $force
            || count($this->fields) === 0
            || (int) $this->fields['id'] === 0
            || $this->fields['name'] == ''
        ) {
            $loaded = $this->getFromDB($this->key);
        }

        if ($loaded) {
            if ($force || $this->items === null) {
                $this->items = Item::getForDashboard($this->fields['id']);
            }

            if ($force || $this->rights === null) {
                $this->rights = Right::getForDashboard($this->fields['id']);
            }
        }

        Profiler::getInstance()->stop(__METHOD__);
        return $this->fields['id'] ?? false;
    }


    public function getID()
    {
        // Force usage of the `id` field
        if (isset($this->fields['id'])) {
            return (int) $this->fields['id'];
        }
        return -1;
    }

    public function getFromDB($ID)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'key' => $ID,
            ],
            'LIMIT' => 1,
        ]);
        if (count($iterator) == 1) {
            $this->fields = $iterator->current();
            $this->key    = $ID;
            $this->post_getFromDB();
            return true;
        } elseif (count($iterator) > 1) {
            throw new TooManyResultsException(
                sprintf(
                    '`%1$s::getFromDB()` expects to get one result, %2$s found in query "%3$s".',
                    static::class,
                    count($iterator),
                    $iterator->getSql()
                )
            );
        }

        if (\is_numeric($ID)) {
            // Search also on the `id` field.
            // This is mandatory to handle the `$this->getFromDB($this->getID());` reload case.
            $iterator = $DB->request([
                'FROM'  => self::getTable(),
                'WHERE' => [
                    'id' => $ID,
                ],
                'LIMIT' => 1,
            ]);
            if (count($iterator) == 1) {
                $this->fields = $iterator->current();
                $this->key    = $this->fields['key'];
                $this->post_getFromDB();
                return true;
            } elseif (count($iterator) > 1) {
                throw new TooManyResultsException(
                    sprintf(
                        '`%1$s::getFromDB()` expects to get one result, %2$s found in query "%3$s".',
                        static::class,
                        count($iterator),
                        $iterator->getSql()
                    )
                );
            }
        }

        return false;
    }


    /**
     * Return the title of the current dashboard
     *
     * @return string
     */
    public function getTitle(): string
    {
        $this->load();
        return $this->fields['name'] ?? "";
    }

    /**
     * Do we have the right to view the current dashboard
     *
     * @return bool
     */
    public function canViewCurrent(): bool
    {
        Profiler::getInstance()->start(__METHOD__);
        $this->load();

        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            // User is always allowed to view its own dashboards.
            Profiler::getInstance()->stop(__METHOD__);
            return true;
        }

        // check global (admin) right
        if (self::canView() && !$this->isPrivate()) {
            Profiler::getInstance()->stop(__METHOD__);
            return true;
        }

        // check access rights defined using sharing feature
        $this->load();

        $rights = self::convertRights($this->rights ?? []);
        $result = self::checkRights($rights);
        Profiler::getInstance()->stop(__METHOD__);
        return $result;
    }

    /**
     * Check if user has right to update the current dashboard.
     *
     * @return bool
     */
    public function canUpdateCurrent(): bool
    {
        $this->load();

        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            // User is always allowed to update its own dashboards.
            return true;
        }

        return self::canUpdate();
    }

    /**
     * Check if user has right to detele the current dashboard.
     *
     * @return bool
     */
    public function canDeleteCurrent(): bool
    {
        $this->load();

        if ($this->fields['users_id'] === Session::getLoginUserID()) {
            // User is always allowed to delete its own dashboards.
            return true;
        }

        return self::canPurge();
    }


    /**
     * Save the current dashboard instance to DB
     *
     * @param string $title label of the dashboard, will be suglified to have a corresponding key
     * @param string $context of the dashboard, filter the dashboard collection by a key
     * @param array $items cards for the dashboard
     * @param array $rights for the dashboard
     *
     * @return string
     */
    public function saveNew(
        string $title = "",
        string $context = "core",
        array $items = [],
        array $rights = []
    ): string {
        $this->fields['name']   = $title;
        $this->fields['context'] = $context;
        $this->fields['users_id'] = Session::getLoginUserID();
        $this->key    = Toolbox::slugify($title);
        $this->items  = $items;
        $this->rights = $rights;

        $this->save();

        return $this->key;
    }


    /**
     * Save current dashboard
     *
     * @param bool $skip_child skip saving rights and items
     *
     * @return void
     */
    public function save(bool $skip_child = false)
    {
        global $DB, $GLPI_CACHE;

        $DB->updateOrInsert(
            self::getTable(),
            [
                'key'      => $this->key,
                'name'     => $this->fields['name'],
                'context'  => $this->fields['context'],
                'users_id' => $this->fields['users_id'],
            ],
            [
                'key'  => $this->key,
            ]
        );

        // reload dashboard
        $this->getFromDB($this->key);

        //save items
        if (!$skip_child && count($this->items) > 0) {
            $this->saveItems($this->items);
        }

        //save rights
        if (!$skip_child && count($this->rights) > 0) {
            $this->saveRights($this->rights);
        }
    }

    /**
     * Reset the current dashboard to a default state.
     * @param string $default_dashboard_key The key of the dashboard in the default data to use as the source of the state.
     * @return bool true on success, false on failure
     */
    public function resetToDefault(string $default_dashboard_key): bool
    {
        $this->load();
        if ($this->fields['context'] !== 'core' && $this->fields['context'] !== 'mini_core') {
            return false;
        }

        $default_dashboards = require(GLPI_ROOT . '/install/migrations/update_9.4.x_to_9.5.0/dashboards.php');

        $target_dashboard = null;

        foreach ($default_dashboards as $dashboard) {
            if ($dashboard['key'] === $default_dashboard_key) {
                $target_dashboard = $dashboard;
                $target_dashboard['_items'] = array_map(static function ($item) {
                    $item['card_options'] = json_decode($item['card_options'], true);
                    return $item;
                }, $target_dashboard['_items']);
                break;
            }
        }

        if ($target_dashboard === null || $target_dashboard['context'] !== $this->fields['context']) {
            return false;
        }

        $this->saveFilter('');
        $this->saveRights([]);
        $this->saveItems($target_dashboard['_items']);

        return true;
    }

    public function showResetForm(): void
    {
        $this->load();
        $default_dashboard_data = require(GLPI_ROOT . '/install/migrations/update_9.4.x_to_9.5.0/dashboards.php');
        $default_dashboards = [];
        foreach ($default_dashboard_data as $dashboard) {
            if ($dashboard['context'] !== $this->fields['context']) {
                continue;
            }
            $default_dashboards[$dashboard['key']] = $dashboard['name'];
        }
        TemplateRenderer::getInstance()->display('components/dashboard/reset.html.twig', [
            'dashboard' => $this,
            'default_dashboards' => $default_dashboards,
        ]);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb([
            Item::class,
            Right::class,
            Filter::class,
        ]);
    }

    /**
     * Save items in DB for the current dashboard
     *
     * @param array $items cards of the dashboard, contains:
     *    - gridstack_id: unique id of the card in the grid, usually build like card_id.uuidv4
     *    - card_id: key of array return by getAllDasboardCards
     *    - x: position in grid
     *    - y: position in grid
     *    - width: size in grid
     *    - height: size in grid
     *    - card_options, sub array, depends on the card, contains at least a key color
     *
     * @return void
     */
    public function saveItems(array $items = [])
    {
        $this->load();
        $this->items   = $items;

        $this->deleteChildrenAndRelationsFromDb([
            Item::class,
        ]);

        Item::addForDashboard($this->fields['id'], $items);
    }

    /**
     * Save title DB for the current dashboard
     *
     * @param string $title of the current dashboard
     *
     * @return void
     */
    public function saveTitle(string $title = "")
    {
        if (!strlen($title)) {
            return;
        }

        $this->load();
        $this->fields['name'] = $title;
        $this->save(true);
    }


    /**
     * Save rights (share) in DB for the current dashboard
     *
     * @param array $rights contains these data:
     * - 'users_id'    => [items_id]
     * - 'groups_id'   => [items_id]
     * - 'entities_id' => [items_id]
     * - 'profiles_id' => [items_id]
     *
     * @return void
     */
    public function saveRights(array $rights = [])
    {
        $this->load();
        $this->rights = $rights;

        $this->deleteChildrenAndRelationsFromDb([
            Right::class,
        ]);

        Right::addForDashboard($this->fields['id'], $rights);
    }

    /**
     * Save filter in DB for the  curent dashboard
     *
     * @param string $filters filter parameters in JSON format
     *
     * @return void
     */
    public function saveFilter(string $filters = ''): void
    {
        $this->load();
        $this->filters = $filters;

        Filter::addForDashboard($this->fields['id'], $filters);
    }

    /**
     * Save filter in DB for the  curent dashboard
     *
     * @return string
     */
    public function getFilter(): string
    {
        $this->load();
        $this->filters = Filter::getForDashboard($this->fields['id']);
        return $this->filters;
    }

    /**
     * Clone current Dashboard.
     * (Clean gridstack_id-id in new one)
     *
     * @return array with [title, key]
     */
    public function cloneCurrent(): array
    {
        $this->load();

        $this->fields['name'] = sprintf(__('Copy of %s'), $this->fields['name']);
        $this->fields['users_id'] = Session::getLoginUserID();
        $this->key = Toolbox::slugify($this->fields['name']) . '-' . Uuid::uuid4()->toString();

        // replace gridstack_id (with uuid V4) in the copy, to avoid cache issue
        $this->items = array_map(function (array $item) {
            $item['gridstack_id'] = $item['card_id'] . Uuid::uuid4();

            return $item;
        }, $this->items);

        // convert right to the good format
        $this->rights = self::convertRights($this->rights);

        $this->save();

        return [
            'title' => $this->fields['name'],
            'key'   => $this->key,
        ];
    }


    /**
     * Retrieve all dashboards and store them into a static var
     *
     * @param bool   $force don't check dashboard are already loaded and force their load
     * @param bool   $check_rights use to remove rights checking (use in embed)
     * @param ?string $context only dashboard for given context
     *
     * @return array dasboards
     */
    public static function getAll(bool $force = false, bool $check_rights = true, ?string $context = 'core'): array
    {
        global $DB;

        if ($force || count(self::$all_dashboards) == 0) {
            self::$all_dashboards = [];

            $dashboards = iterator_to_array($DB->request(['FROM' => self::getTable()]));
            $items      = iterator_to_array($DB->request(['FROM' => Item::getTable()]));
            $rights     = iterator_to_array($DB->request(['FROM' => Right::getTable()]));

            foreach ($dashboards as $dashboard) {
                $key = $dashboard['key'];
                $id  = $dashboard['id'];

                $d_rights = array_filter($rights, static fn($right_line) => $right_line['dashboards_dashboards_id'] == $id);
                $dashboardItem = new self($key);
                if ($check_rights && !$dashboardItem->canViewCurrent()) {
                    continue;
                }
                $dashboard['rights'] = self::convertRights($d_rights);

                $d_items = array_filter($items, static fn($item) => $item['dashboards_dashboards_id'] == $id);
                $d_items = array_map(static function ($item) {
                    $item['card_options'] = importArrayFromDB($item['card_options']);
                    return $item;
                }, $d_items);
                $dashboard['items'] = $d_items;

                self::$all_dashboards[$key] = $dashboard;
            }
        }

        // Return dashboards filtered by context (if applicable)
        if ($context !== null && $context !== '') {
            return array_filter(self::$all_dashboards, static fn($dashboard) => $dashboard['context'] === $context);
        }

        return self::$all_dashboards;
    }


    /**
     * Convert right from DB entries to a array with type foreign keys.
     * Ex:
     * IN
     * [
     *    [
     *       'itemtype' => Entity::class
     *       'items_id' => yyy
     *    ], [
     *       ...
     *    ],
     * ]
     *
     * OUT
     * [
     *   'entities_id' => [...]
     *   'profiles_id' => [...]
     *   'users_id'    => [...]
     *   'groups_id'   => [...]
     * ]
     *
     * @param array $raw_rights right from DB
     *
     * @return array converter rights
     */
    public static function convertRights(array $raw_rights = []): array
    {
        $rights = [
            'entities_id' => [],
            'profiles_id' => [],
            'users_id'    => [],
            'groups_id'   => [],
        ];
        foreach ($raw_rights as $right_line) {
            $fk = getForeignKeyFieldForItemType($right_line['itemtype']);
            $rights[$fk][] = $right_line['items_id'];
        }

        return $rights;
    }


    /**
     * Check a current set of rights
     *
     * @param array $rights
     *
     * @return bool
     */
    public static function checkRights(array $rights = []): bool
    {
        $default_rights = [
            'entities_id' => [],
            'profiles_id' => [],
            'users_id'    => [],
            'groups_id'   => [],
        ];
        $rights = array_merge_recursive($default_rights, $rights);

        if (!Session::getLoginUserID()) {
            return false;
        }

        // check specific rights
        if (
            count(array_intersect($rights['entities_id'], $_SESSION['glpiactiveentities']))
            || in_array($_SESSION["glpiactiveprofile"]['id'], $rights['profiles_id'])
            || in_array($_SESSION['glpiID'], $rights['users_id'])
            || count(array_intersect($rights['groups_id'], $_SESSION['glpigroups']))
        ) {
            return true;
        }

        return false;
    }


    /**
     * Import dashboards from a variable
     *
     * @param string|array $import json or php array representing the dashboards collection
     * [
     *    dashboard_key => [
     *       'title'  => '...',
     *       'items'  => [...],
     *       'rights' => [...],
     *    ], [
     *       ...
     *    ]
     * ]
     *
     * @return bool
     */
    public static function importFromJson($import = null)
    {
        if (!is_array($import)) {
            if (!Toolbox::isJSON($import)) {
                return false;
            }
            $import = json_decode($import, true);
        }

        foreach ($import as $key => $dashboard) {
            $dash_object = new self($key);
            $dash_object->saveNew(
                $dashboard['title']  ?? $key,
                $dashboard['context']  ?? "core",
                $dashboard['items']  ?? [],
                $dashboard['rights'] ?? []
            );
        }

        return true;
    }

    /**
     * @param bool $is_private
     *
     * @return bool
     */
    public function setPrivate($is_private)
    {
        $this->load();

        return $this->update([
            'id'       => $this->fields['id'],
            'key'      => $this->fields['key'],
            'users_id' => ($is_private ? Session::getLoginUserID() : 0),
        ]);
    }

    /**
     * @return string (int as string... should be a boolean.)
     */
    public function getPrivate()
    {
        $this->load();
        if (!isset($this->fields['users_id'])) {
            return '0';
        }
        return $this->fields['users_id'] != '0' ? '1' : '0';
    }

    /**
     * Is this dashboard private ?
     *
     * @return bool true if private; false otherwise
     */
    public function isPrivate(): bool
    {
        if ((bool) $this->getPrivate() === false) {
            return false;
        }
        return $this->fields['users_id'] != Session::getLoginUserID();
    }

    public static function getIcon()
    {
        return "ti ti-dashboard";
    }

    /**
     * Return default dashboards data.
     *
     * @return list<array{
     *   key: string,
     *   name: string,
     *   context: string,
     *   items: list<array{
     *     x: int,
     *     y: int,
     *     width: int,
     *     height: int,
     *     gridstack_id: string,
     *     card_id: string,
     *     card_options: array{
     *       color: string,
     *       widgettype: string,
     *     },
     *   }>,
     * }>
     */
    public static function getDefaults(): array
    {
        return [
            [
                'key' => 'central',
                'name' => __('Central'),
                'context' => 'core',
                'items' => [
                    [
                        'x' => 3,
                        'y' => 0,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Computer_4a315743-151c-40cb-a20b-762250668dac',
                        'card_id' => 'bn_count_Computer',
                        'card_options' => [
                            'color' => '#e69393',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 0,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Software_0690f524-e826-47a9-b50a-906451196b83',
                        'card_id' => 'bn_count_Software',
                        'card_options' => [
                            'color' => '#aaddac',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 6,
                        'y' => 2,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Rack_c6502e0a-5991-46b4-a771-7f355137306b',
                        'card_id' => 'bn_count_Rack',
                        'card_options' => [
                            'color' => '#0e87a0',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 2,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_SoftwareLicense_e755fd06-283e-4479-ba35-2d548f8f8a90',
                        'card_id' => 'bn_count_SoftwareLicense',
                        'card_options' => [
                            'color' => '#27ab3c',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 3,
                        'y' => 2,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Monitor_7059b94c-583c-4ba7-b100-d40461165318',
                        'card_id' => 'bn_count_Monitor',
                        'card_options' => [
                            'color' => '#b52d30',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 14,
                        'y' => 7,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Ticket_a74c0903-3387-4a07-9111-b0938af8f1e7',
                        'card_id' => 'bn_count_Ticket',
                        'card_options' => [
                            'color' => '#ffdc64',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 20,
                        'y' => 7,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Problem_c1cf5cfb-f626-472e-82a1-49c3e200e746',
                        'card_id' => 'bn_count_Problem',
                        'card_options' => [
                            'color' => '#f08d7b',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 4,
                        'width' => 5,
                        'height' => 4,
                        'gridstack_id' => 'count_Computer_Manufacturer_6129c451-42b5-489d-b693-c362adf32d49',
                        'card_id' => 'count_Computer_Manufacturer',
                        'card_options' => [
                            'color' => '#f8faf9',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 14,
                        'y' => 9,
                        'width' => 6,
                        'height' => 5,
                        'gridstack_id' => 'top_ticket_user_requester_c74f52a8-046a-4077-b1a6-c9f840d34b82',
                        'card_id' => 'top_ticket_user_requester',
                        'card_options' => [
                            'color' => '#f9fafb',
                            'widgettype' => 'hbar',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 17,
                        'y' => 7,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_late_04c47208-d7e5-4aca-9566-d46e68c45c67',
                        'card_id' => 'bn_count_tickets_late',
                        'card_options' => [
                            'color' => '#f8911f',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 14,
                        'y' => 0,
                        'width' => 12,
                        'height' => 7,
                        'gridstack_id' => 'ticket_status_2e4e968b-d4e6-4e33-9ce9-a1aaff53dfde',
                        'card_id' => 'ticket_status',
                        'card_options' => [
                            'color' => '#fafafa',
                            'widgettype' => 'stackedbars',
                            'use_gradient' => '0',
                            'limit' => '12',
                        ],
                    ],
                    [
                        'x' => 20,
                        'y' => 9,
                        'width' => 6,
                        'height' => 5,
                        'gridstack_id' => 'top_ticket_ITILCategory_37736ba9-d429-4cb3-9058-ef4d111d9269',
                        'card_id' => 'top_ticket_ITILCategory',
                        'card_options' => [
                            'color' => '#fbf9f9',
                            'widgettype' => 'hbar',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 9,
                        'y' => 2,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Printer_517684b0-b064-49dd-943e-fcb6f915e453',
                        'card_id' => 'bn_count_Printer',
                        'card_options' => [
                            'color' => '#365a8f',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 9,
                        'y' => 0,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Phone_f70c489f-02c1-46e5-978b-94a95b5038ee',
                        'card_id' => 'bn_count_Phone',
                        'card_options' => [
                            'color' => '#d5e1ec',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 23,
                        'y' => 7,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Change_ab950dbd-cd25-466d-8dff-7dcaca386564',
                        'card_id' => 'bn_count_Change',
                        'card_options' => [
                            'color' => '#cae3c4',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 8,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Group_b84a93f2-a26c-49d7-82a4-5446697cc5b0',
                        'card_id' => 'bn_count_Group',
                        'card_options' => [
                            'color' => '#e0e0e0',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 10,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Profile_770b35e8-68e9-4b4f-9e09-5a11058f069f',
                        'card_id' => 'bn_count_Profile',
                        'card_options' => [
                            'color' => '#e0e0e0',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Supplier_36ff9011-e4cf-4d89-b9ab-346b9857d734',
                        'card_id' => 'bn_count_Supplier',
                        'card_options' => [
                            'color' => '#c9c9c9',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 10,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_KnowbaseItem_a3785a56-bed4-4a30-8387-f251f5365b3b',
                        'card_id' => 'bn_count_KnowbaseItem',
                        'card_options' => [
                            'color' => '#c9c9c9',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 10,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Entity_9b82951a-ba52-45cc-a2d3-1d238ec37adf',
                        'card_id' => 'bn_count_Entity',
                        'card_options' => [
                            'color' => '#f9f9f9',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 11,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Document_7dc7f4b8-61ff-4147-b994-5541bddd7b66',
                        'card_id' => 'bn_count_Document',
                        'card_options' => [
                            'color' => '#b4b4b4',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 11,
                        'y' => 10,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Project_4d412ee2-8b79-469b-995f-4c0a05ab849d',
                        'card_id' => 'bn_count_Project',
                        'card_options' => [
                            'color' => '#b3b3b3',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 6,
                        'y' => 0,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_NetworkEquipment_c537e334-d584-43bc-b6de-b4a939143e89',
                        'card_id' => 'bn_count_NetworkEquipment',
                        'card_options' => [
                            'color' => '#bfe7ea',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 8,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_User_ac0cbe52-3593-43c1-8ecc-0eb115de494d',
                        'card_id' => 'bn_count_User',
                        'card_options' => [
                            'color' => '#fafafa',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 5,
                        'y' => 4,
                        'width' => 5,
                        'height' => 4,
                        'gridstack_id' => 'count_Monitor_MonitorModel_5a476ff9-116e-4270-858b-c003c20841a9',
                        'card_id' => 'count_Monitor_MonitorModel',
                        'card_options' => [
                            'color' => '#f5fafa',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 10,
                        'y' => 4,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_NetworkEquipment_State_81f2ae35-b366-4065-ac26-02ea4e3704a6',
                        'card_id' => 'count_NetworkEquipment_State',
                        'card_options' => [
                            'color' => '#f5f3ef',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'assets',
                'name' => __('Assets'),
                'context' => 'core',
                'items' => [
                    [
                        'x' => 0,
                        'y' => 0,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Computer_34cfbaf9-a471-4852-b48c-0dadea7644de',
                        'card_id' => 'bn_count_Computer',
                        'card_options' => [
                            'color' => '#f3d0d0',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 0,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Software_60091467-2137-49f4-8834-f6602a482079',
                        'card_id' => 'bn_count_Software',
                        'card_options' => [
                            'color' => '#d1f1a8',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 3,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Printer_c9a385d4-76a3-4971-ad0e-1470efeafacc',
                        'card_id' => 'bn_count_Printer',
                        'card_options' => [
                            'color' => '#5da8d6',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 12,
                        'y' => 3,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_PDU_60053eb6-8dda-4416-9a4b-afd51889bd09',
                        'card_id' => 'bn_count_PDU',
                        'card_options' => [
                            'color' => '#ffb62f',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 12,
                        'y' => 0,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Rack_0fdc196f-20d2-4f63-9ddb-b75c165cc664',
                        'card_id' => 'bn_count_Rack',
                        'card_options' => [
                            'color' => '#f7d79a',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 16,
                        'y' => 3,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Phone_c31fde2d-510a-4482-b17d-2f65b61eae08',
                        'card_id' => 'bn_count_Phone',
                        'card_options' => [
                            'color' => '#a0cec2',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 16,
                        'y' => 0,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Enclosure_c21ce30a-58c3-456a-81ec-3c5f01527a8f',
                        'card_id' => 'bn_count_Enclosure',
                        'card_options' => [
                            'color' => '#d7e8e4',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 0,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_NetworkEquipment_76f1e239-777b-4552-b053-ae5c64190347',
                        'card_id' => 'bn_count_NetworkEquipment',
                        'card_options' => [
                            'color' => '#c8dae4',
                            'widgettype' => 'bigNumber',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 3,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_SoftwareLicense_576e58fe-a386-480f-b405-1c2315b8ab47',
                        'card_id' => 'bn_count_SoftwareLicense',
                        'card_options' => [
                            'color' => '#9bc06b',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 3,
                        'width' => 4,
                        'height' => 3,
                        'gridstack_id' => 'bn_count_Monitor_890e16d3-b121-48c6-9713-d9c239d9a970',
                        'card_id' => 'bn_count_Monitor',
                        'card_options' => [
                            'color' => '#dc6f6f',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_Computer_Manufacturer_986e92e8-32e8-4a6f-806f-6f5383acbb3f',
                        'card_id' => 'count_Computer_Manufacturer',
                        'card_options' => [
                            'color' => '#f3f5f1',
                            'widgettype' => 'hbar',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_Computer_State_290c5920-9eab-4db8-8753-46108e60f1d8',
                        'card_id' => 'count_Computer_State',
                        'card_options' => [
                            'color' => '#fbf7f7',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_Computer_ComputerType_c58f9c7e-22d5-478b-8226-d2a752bcbb09',
                        'card_id' => 'count_Computer_ComputerType',
                        'card_options' => [
                            'color' => '#f5f9fa',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 12,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_NetworkEquipment_Manufacturer_8132b21c-6f7f-4dc1-af54-bea794cb96e9',
                        'card_id' => 'count_NetworkEquipment_Manufacturer',
                        'card_options' => [
                            'color' => '#fcf8ed',
                            'widgettype' => 'hbar',
                            'use_gradient' => '0',
                            'limit' => '5',
                        ],
                    ],
                    [
                        'x' => 16,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'count_Monitor_Manufacturer_43b0c16b-af82-418e-aac1-f32b39705c0d',
                        'card_id' => 'count_Monitor_Manufacturer',
                        'card_options' => [
                            'color' => '#f9fbfb',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '5',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'assistance',
                'name' => __('Assistance'),
                'context' => 'core',
                'items' => [
                    [
                        'x' => 0,
                        'y' => 0,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Ticket_344e761b-f7e8-4617-8c90-154b266b4d67',
                        'card_id' => 'bn_count_Ticket',
                        'card_options' => [
                            'color' => '#ffdc64',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 4,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Problem_bdb4002b-a674-4493-820f-af85bed44d2a',
                        'card_id' => 'bn_count_Problem',
                        'card_options' => [
                            'color' => '#f0967b',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 6,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Change_b9b87513-4f40-41e6-8621-f51f9a30fb19',
                        'card_id' => 'bn_count_Change',
                        'card_options' => [
                            'color' => '#cae3c4',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 2,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_late_1e9ae481-21b4-4463-a830-dec1b68ec5e7',
                        'card_id' => 'bn_count_tickets_late',
                        'card_options' => [
                            'color' => '#f8911f',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 3,
                        'y' => 6,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_incoming_336a36d9-67fe-4475-880e-447bd766b8fe',
                        'card_id' => 'bn_count_tickets_incoming',
                        'card_options' => [
                            'color' => '#a0e19d',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 9,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_closed_e004bab5-f2b6-4060-a401-a2a8b9885245',
                        'card_id' => 'bn_count_tickets_closed',
                        'card_options' => [
                            'color' => '#515151',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 6,
                        'y' => 6,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_assigned_7455c855-6df8-4514-a3d9-8b0fce52bd63',
                        'card_id' => 'bn_count_tickets_assigned',
                        'card_options' => [
                            'color' => '#eaf5f7',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 9,
                        'y' => 6,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_solved_5e9759b3-ee7e-4a14-b68f-1ac024ef55ee',
                        'card_id' => 'bn_count_tickets_solved',
                        'card_options' => [
                            'color' => '#d8d8d8',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 3,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_waiting_102b2c2a-6ac6-4d73-ba47-8b09382fe00e',
                        'card_id' => 'bn_count_tickets_waiting',
                        'card_options' => [
                            'color' => '#ffcb7d',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_TicketRecurrent_13f79539-61f6-45f7-8dde-045706e652f2',
                        'card_id' => 'bn_count_TicketRecurrent',
                        'card_options' => [
                            'color' => '#fafafa',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 6,
                        'y' => 8,
                        'width' => 3,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_planned_267bf627-9d5e-4b6c-b53d-b8623d793ccf',
                        'card_id' => 'bn_count_tickets_planned',
                        'card_options' => [
                            'color' => '#6298d5',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 12,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'top_ticket_ITILCategory_0cba0c84-6c62-4cd8-8564-18614498d8e4',
                        'card_id' => 'top_ticket_ITILCategory',
                        'card_options' => [
                            'color' => '#f1f5ef',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 16,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'top_ticket_RequestType_b9e43f34-8e94-4a6e-9023-c5d1e2ce7859',
                        'card_id' => 'top_ticket_RequestType',
                        'card_options' => [
                            'color' => '#f9fafb',
                            'widgettype' => 'hbar',
                            'use_gradient' => '1',
                            'limit' => '4',
                        ],
                    ],
                    [
                        'x' => 20,
                        'y' => 6,
                        'width' => 4,
                        'height' => 4,
                        'gridstack_id' => 'top_ticket_Entity_a8e65812-519c-488e-9892-9adbe22fbd5c',
                        'card_id' => 'top_ticket_Entity',
                        'card_options' => [
                            'color' => '#f7f1f0',
                            'widgettype' => 'donut',
                            'use_gradient' => '1',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 3,
                        'y' => 0,
                        'width' => 12,
                        'height' => 6,
                        'gridstack_id' => 'ticket_evolution_76fd4926-ee5e-48db-b6d6-e2947c190c5e',
                        'card_id' => 'ticket_evolution',
                        'card_options' => [
                            'color' => '#f3f7f8',
                            'widgettype' => 'areas',
                            'use_gradient' => '0',
                            'limit' => '12',
                        ],
                    ],
                    [
                        'x' => 15,
                        'y' => 0,
                        'width' => 11,
                        'height' => 6,
                        'gridstack_id' => 'ticket_status_5b256a35-b36b-4db5-ba11-ea7c125f126e',
                        'card_id' => 'ticket_status',
                        'card_options' => [
                            'color' => '#f7f3f2',
                            'widgettype' => 'stackedbars',
                            'use_gradient' => '0',
                            'limit' => '12',
                        ],
                    ],
                ],
            ],
            [
                'key' => 'mini_tickets',
                'name' => __('Mini tickets dashboard'),
                'context' => 'mini_core',
                'items' => [
                    [
                        'x' => 24,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_closed_ccf7246b-645a-40d2-8206-fa33c769e3f5',
                        'card_id' => 'bn_count_tickets_closed',
                        'card_options' => [
                            'color' => '#fafafa',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 0,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_Ticket_d5bf3576-5033-40fb-bbdb-292294a7698e',
                        'card_id' => 'bn_count_Ticket',
                        'card_options' => [
                            'color' => '#ffd957',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 4,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_incoming_055e813c-b0ce-4687-91ef-559249e8ddd8',
                        'card_id' => 'bn_count_tickets_incoming',
                        'card_options' => [
                            'color' => '#6fd169',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 8,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_waiting_793c665b-b620-4b3a-a5a8-cf502defc008',
                        'card_id' => 'bn_count_tickets_waiting',
                        'card_options' => [
                            'color' => '#ffcb7d',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 12,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_assigned_d3d2f697-52b4-435e-9030-a760dd649085',
                        'card_id' => 'bn_count_tickets_assigned',
                        'card_options' => [
                            'color' => '#eaf4f7',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 16,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_planned_0c7f3569-c23b-4ee3-8e85-279229b23e70',
                        'card_id' => 'bn_count_tickets_planned',
                        'card_options' => [
                            'color' => '#6298d5',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                    [
                        'x' => 20,
                        'y' => 0,
                        'width' => 4,
                        'height' => 2,
                        'gridstack_id' => 'bn_count_tickets_solved_ae2406cf-e8e8-410b-b355-46e3f5705ee8',
                        'card_id' => 'bn_count_tickets_solved',
                        'card_options' => [
                            'color' => '#d7d7d7',
                            'widgettype' => 'bigNumber',
                            'use_gradient' => '0',
                            'limit' => '7',
                        ],
                    ],
                ],
            ],
        ];
    }
}
