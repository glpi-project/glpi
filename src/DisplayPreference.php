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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\Search\SearchOption;

class DisplayPreference extends CommonDBTM
{
   // From CommonGLPI
    public $taborientation          = 'horizontal';
    public $get_item_to_display_tab = false;

   // From CommonDBTM
    public $auto_message_on_action  = false;

    protected $displaylist          = false;


    public static $rightname = 'search_config';

    const PERSONAL = 1024;
    const GENERAL  = 2048;

    public static function getTypeName($nb = 0)
    {
        return __('Search result display');
    }

    public function prepareInputForAdd($input)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'SELECT' => ['MAX' => 'rank AS maxrank'],
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $input['itemtype'],
                'users_id'  => $input['users_id']
            ]
        ])->current();
        $input['rank'] = $result['maxrank'] + 1;
        return $input;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'reset_to_default':
                $msg = __('This will reset the columns to the defaults for a new installation.');
                $msg2 = __('This will only work for types from GLPI itself or enabled plugins that support this action.');
                echo '<div class="alert alert-info">' . $msg . '<br>' . $msg2 . '</div>';
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'delete_for_user':
                $input = $ma->getInput();
                if (isset($input['users_id'])) {
                    $user = new User();
                    $user->getFromDB($input['users_id']);
                    foreach ($ids as $id) {
                        if ($input['users_id'] == Session::getLoginUserID()) {
                            if (
                                $item->deleteByCriteria(['users_id' => $input['users_id'],
                                    'itemtype' => $id
                                ])
                            ) {
                                 $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($user->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($user->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
            case 'reset_to_default':
                $input = $ma->getInput();
                if (!isset($input['users_id']) || $input['users_id'] <= 0) {
                    foreach ($ids as $itemtype) {
                        if (self::resetToDefaultOptions($itemtype)) {
                            $ma->itemDone($item->getType(), $itemtype, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $itemtype, MassiveAction::ACTION_KO);
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Get display preference for a user for an itemtype
     *
     * @param string  $itemtype  itemtype
     * @param integer $user_id   user ID
     *
     * @return array
     **/
    public static function getForTypeUser($itemtype, $user_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'OR'        => [
                    ['users_id' => $user_id],
                    ['users_id' => 0]
                ]
            ],
            'ORDER'  => ['users_id', 'rank']
        ]);

        $default_prefs = [];
        $user_prefs = [];

        foreach ($iterator as $data) {
            if ($data["users_id"] != 0) {
                $user_prefs[] = $data["num"];
            } else {
                $default_prefs[] = $data["num"];
            }
        }

        return count($user_prefs) ? $user_prefs : $default_prefs;
    }

    /**
     * Active personal config based on global one
     *
     * @param $input  array parameter (itemtype,users_id)
     **/
    public function activatePerso(array $input)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!Session::haveRight(self::$rightname, self::PERSONAL)) {
            return false;
        }

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $input['itemtype'],
                'users_id'  => 0
            ]
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                unset($data["id"]);
                $data["users_id"] = $input["users_id"];
                $this->fields     = $data;
                $this->addToDB();
            }
        } else {
           // No items in the global config
            $searchopt = SearchOption::getOptionsForItemtype($input["itemtype"]);
            if (count($searchopt) > 1) {
                $done = false;

                foreach ($searchopt as $key => $val) {
                    if (
                        is_int($key)
                        && is_array($val)
                        && ($key != 1)
                        && !$done
                    ) {
                        $data["users_id"] = $input["users_id"];
                        $data["itemtype"] = $input["itemtype"];
                        $data["rank"]     = 1;
                        $data["num"]      = $key;
                        $this->fields     = $data;
                        $this->addToDB();
                        $done = true;
                    }
                }
            }
        }
    }

    public function updateOrder(string $itemtype, int $users_id, array $order)
    {
        /** @var \DBmysql $DB */
        global $DB;

        // Fixed columns are not kept in the DB, so we should remove them from the order
        $fixed_cols = $this->getFixedColumns($itemtype);
        $official_order = array_diff($order, $fixed_cols);

        // Remove duplicates (in case of UI bug not preventing them)
        $official_order = array_unique($official_order);

        // Remove options not in the new order
        $DB->delete(
            self::getTable(),
            [
                'itemtype' => $itemtype,
                'users_id' => $users_id,
                'NOT'      => [
                    'num' => $official_order
                ]
            ]
        );

        foreach ($official_order as $rank => $num) {
            $DB->updateOrInsert(
                self::getTable(),
                [
                    'itemtype' => $itemtype,
                    'users_id' => $users_id,
                    'num'      => $num,
                    'rank' => $rank
                ],
                [
                    'itemtype' => $itemtype,
                    'users_id' => $users_id,
                    'num'      => $num,
                ]
            );
        }
    }

    /**
     * Order to move an item
     *
     * @param array  $input  array parameter (id,itemtype,users_id)
     * @param string $action       up or down
     **/
    public function orderItem(array $input, $action)
    {
        /** @var \DBmysql $DB */
        global $DB;

       // Get current item
        $criteria = [];
        if (isset($input['num'])) {
            $criteria = [
                'itemtype'  => $input['itemtype'],
                'users_id'  => $input['users_id'],
                'num'       => $input['num']
            ];
        } else {
            $criteria['id'] = $input['id'];
        }
        $result = $DB->request([
            'SELECT' => ['id', 'rank'],
            'FROM'   => $this->getTable(),
            'WHERE'  => $criteria
        ])->current();
        if (!$result) {
            return false;
        }
        $rank1  = $result['rank'];
        $input['id'] = $result['id'];

       // Get previous or next item
        $where = [];
        $order = 'rank ';
        switch ($action) {
            case "up":
                $where['rank'] = ['<', $rank1];
                $order .= 'DESC';
                break;

            case "down":
                $where['rank'] = ['>', $rank1];
                $order .= 'ASC';
                break;

            default:
                return false;
        }

        $result = $DB->request([
            'SELECT' => ['id', 'rank'],
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $input['itemtype'],
                'users_id'  => $input["users_id"]
            ] + $where,
            'ORDER'  => $order,
            'LIMIT'  => 1
        ])->current();

        $rank2  = $result['rank'];
        $ID2    = $result['id'];

       // Update items
        $DB->update(
            $this->getTable(),
            ['rank' => $rank2],
            ['id' => $input['id']]
        );

        $DB->update(
            $this->getTable(),
            ['rank' => $rank1],
            ['id' => $ID2]
        );
    }

    /**
     * Get the fixed columns for a given itemtype
     * A fixed columns is :
     * - Always displayed before the normal columns
     * - Can't be moved
     * - Must not be shown in the search option dropdown (can't be added to the list)
     */
    protected function getFixedColumns(string $itemtype): array
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $fixed_columns = [];

        // Get item for itemtype
        $item = null;
        if ($itemtype != AllAssets::getType()) {
            $item = getItemForItemtype($itemtype);
        }

        // ID is fixed for CommonITILObjects
        if ($item instanceof CommonITILObject) {
            $fixed_columns[] = 2;
        }

        // Name is always fixed
        $fixed_columns[] = 1;

        // Entity may be fixed
        if (
            Session::isMultiEntitiesMode()
            && (
                isset($CFG_GLPI["union_search_type"][$itemtype])
                || ($item && $item->maybeRecursive())
                || count($_SESSION["glpiactiveentities"]) > 1
            )
        ) {
            $fixed_columns[] = 80;
        }

        return $fixed_columns;
    }

    /**
     * @param string $itemtype The itemtype
     * @param bool $global True if global config, false if personal config
     * @return void|false
     */
    private function showConfigForm(string $itemtype, bool $global)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (class_exists($itemtype)) {
            $searchopt = Search::getCleanedOptions($itemtype);
            $available_itemtype = true;
        } else {
            $searchopt = [];
            $available_itemtype = false;
        }
        if (!is_array($searchopt)) {
            return false;
        }

        $IDuser = $global ? 0 : Session::getLoginUserID();

        $has_personal = false;
        if (!$global) {
            $iterator = $DB->request([
                'COUNT' => 'cpt',
                'FROM' => $this->getTable(),
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'users_id' => $IDuser
                ],
                'ORDER' => 'rank'
            ]);
            $has_personal = $iterator->current()['cpt'] > 0;
        }

        // Get fixed columns
        $fixed_columns = $this->getFixedColumns($itemtype);
        $group  = '';
        $already_added = self::getForTypeUser($itemtype, $IDuser);
        $available_to_add = [];
        foreach ($searchopt as $key => $val) {
            if (!is_array($val)) {
                $group = $val;
            } elseif (count($val) === 1) {
                $group = $val['name'];
            } elseif (
                !in_array($key, $fixed_columns)
                && (!isset($val['nodisplay']) || !$val['nodisplay'])
            ) {
                $available_to_add[$group][$key] = $val["name"];
            }
        }
        $entries = [];
        foreach ($fixed_columns as $val) {
            if (!isset($searchopt[$val])) {
                continue;
            }
            $entries[] = [
                'id'   => $val,
                'name' => $searchopt[$val]['name'],
                'group' => $this->nameOfGroupForItemInSearchopt($searchopt, $val),
                'fixed' => true
            ];
        }
        foreach ($already_added as $val) {
            if (!isset($searchopt[$val])) {
                continue;
            }
            $entries[] = [
                'id'   => $val,
                'name' => $searchopt[$val]['name'],
                'group' => $this->nameOfGroupForItemInSearchopt($searchopt, $val)
            ];
        }

        TemplateRenderer::getInstance()->display('components/search/displaypreference_config.html.twig', [
            'itemtype' => $itemtype,
            'users_id' => $IDuser,
            'available_to_add' => $available_to_add,
            'entries' => $entries,
            'has_personal' => $has_personal,
            'is_global' => $global,
            'available_itemtype' => $available_itemtype
        ]);
    }

    /**
     * Print the search config form
     *
     * @param string $itemtype  item type
     *
     * @return null|false (display) Returns false if there is a rights error.
     **/
    public function showFormPerso($itemtype)
    {
        return $this->showConfigForm($itemtype, false);
    }

    /**
     * Return the group name of an element in the searchopt array
     *
     * The group names are located before the items that belong to it, and are the only string keys, every item's key are integer.
     *
     * We first get the keys of the array to be able to iterate trought his items, including the group names.
     * So we iterate trought the array key's in a reverse order,
     * starting from the position before the item which we want to get the group name.
     * The first key of string type we encouter, is our item's group name.
     *
     * @param array $search_options
     * @param int   $search_option_key
     *
     * @return string Return the name of the group or an empty string.
     *
     * @since 10.0.8
     */
    private function nameOfGroupForItemInSearchopt(array $search_options, int $search_option_key): string
    {
        $search_options_keys = array_keys($search_options);

        for ($key = array_search($search_option_key, $search_options_keys) - 1; $key > 0; $key--) {
            if (is_string($search_options_keys[$key])) {
                return ($search_options[$search_options_keys[$key]]['name'] ?? $key) . " - ";
            }
        }

        return "";
    }

    /**
     * Print the search config form
     *
     * @param string $itemtype  item type
     *
     * @return null|false (display) Returns false if there is a rights error.
     **/
    public function showFormGlobal($itemtype)
    {
        return $this->showConfigForm($itemtype, true);
    }

    /**
     * show defined display preferences for a user
     *
     * @param $users_id integer user ID
     **/
    public static function showForUser($users_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'  => ['itemtype'],
            'COUNT'   => 'nb',
            'FROM'    => self::getTable(),
            'WHERE'   => [
                'users_id'  => $users_id
            ],
            'GROUPBY' => 'itemtype'
        ]);

        $specific_actions = [];
        if ($users_id > 0) {
            $specific_actions[ __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'delete_for_user'] = _x('button', 'Delete permanently');
        } else {
            $specific_actions[ __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'reset_to_default'] = _x('button', 'Reset to default');
        }
        $rand = mt_rand();
        $massiveactionparams = [
            'width'            => 400,
            'height'           => 200,
            'container'        => 'mass' . __CLASS__ . $rand,
            'specific_actions' => $specific_actions,
            'extraparams'      => ['massive_action_fields' => ['users_id']]
        ];

        TemplateRenderer::getInstance()->display('components/search/displaypreference_list.html.twig', [
            'massiveactionparams' => $massiveactionparams,
            'users_id' => $users_id,
            'preferences' => $iterator,
            'rand' => $rand
        ]);
    }

    /**
     * For tab management : force isNewItem
     *
     * @since 0.83
     **/
    public function isNewItem()
    {
        return false;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $ong['no_all_tab'] = true;
        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case 'Preference':
                if (Session::haveRight(self::$rightname, self::PERSONAL)) {
                    return __('Personal View');
                }
                break;

            case __CLASS__:
                $forced_tab = $_REQUEST['forcetab'] ?? null;
                $allow_tab_switch = !isset($_REQUEST['no_switch']) || !$_REQUEST['no_switch'];
                $global_only = $forced_tab === 'DisplayPreference$1' && !$allow_tab_switch;
                $personal_only = $forced_tab === 'DisplayPreference$2' && !$allow_tab_switch;
                $ong = [];
                $ong[1] = $personal_only ? null : __('Global View');
                if (Session::haveRight(self::$rightname, self::PERSONAL)) {
                    $ong[2] = $global_only ? null : __('Personal View');
                }
                return $ong;

            case Config::class:
                return self::createTabEntry(self::getTypeName(1), 0, __CLASS__, 'ti ti-columns-3');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case 'Preference':
                self::showForUser(Session::getLoginUserID());
                return true;

            case __CLASS__:
                /** @var DisplayPreference $item */
                switch ($tabnum) {
                    case 1:
                        $item->showFormGlobal($_GET["displaytype"]);
                        return true;

                    case 2:
                        Session::checkRight(self::$rightname, self::PERSONAL);
                        $item->showFormPerso($_GET["displaytype"]);
                        return true;
                }
                break;

            case Config::class:
                self::showForUser(0);
                return true;
        }
        return false;
    }

    public function getRights($interface = 'central')
    {
       //TRANS: short for : Search result user display
        $values[self::PERSONAL]  = ['short' => __('User display'),
            'long'  => __('Search result user display')
        ];
       //TRANS: short for : Search result default display
        $values[self::GENERAL]  =  ['short' => __('Default display'),
            'long'  => __('Search result default display')
        ];

        return $values;
    }

    /**
     * Change display preferences for an itemtype back to its defaults.
     * This only works with core itemtypes that have their default preferences specified in the empty_data.php script
     * or itemtypes from plugins that provide the defaults via the {@link Hooks::DEFAULT_DISPLAY_PREFS} hook.
     * @param string $itemtype The itemtype
     * @return array|null True if defaults existed and were reset OK. False otherwise.
     */
    public static function resetToDefaultOptions(string $itemtype): bool
    {
        /** @var \DBmysql $DB */
        global $DB;
        $tables = require(GLPI_ROOT . '/install/empty_data.php');
        $prefs = array_filter($tables[self::getTable()], static function ($pref) use ($itemtype) {
            return $pref['itemtype'] === $itemtype;
        });
        if (!count($prefs)) {
            // plugin type or not supported
            $plugin_opts = Plugin::doHookFunction(Hooks::DEFAULT_DISPLAY_PREFS, [
                'itemtype' => $itemtype,
                'prefs'    => []
            ]);
            if (count($plugin_opts['prefs'])) {
                $prefs = $plugin_opts['prefs'];
            } else {
                return false;
            }
        }

        $existing_opts = array_column($prefs, 'num');
        $DB->delete(
            self::getTable(),
            [
                'itemtype' => $itemtype,
                'users_id' => 0,
                'NOT'      => [
                    'num' => $existing_opts
                ]
            ]
        );
        foreach ($prefs as $pref) {
            $result = $DB->updateOrInsert(
                self::getTable(),
                [
                    'itemtype' => $itemtype,
                    'users_id' => 0,
                    'num'      => $pref['num'],
                    'rank'     => $pref['rank']
                ],
                [
                    'itemtype' => $itemtype,
                    'users_id' => 0,
                    'num'      => $pref['num'],
                ]
            );
            if (!$result) {
                return false;
            }
        }
        return true;
    }
}
