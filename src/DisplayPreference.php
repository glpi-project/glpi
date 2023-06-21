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
            $searchopt = Search::getOptions($input["itemtype"]);
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


    /**
     * Order to move an item
     *
     * @param array  $input  array parameter (id,itemtype,users_id)
     * @param string $action       up or down
     **/
    public function orderItem(array $input, $action)
    {
        global $DB;

       // Get current item
        $result = $DB->request([
            'SELECT' => 'rank',
            'FROM'   => $this->getTable(),
            'WHERE'  => ['id' => $input['id']]
        ])->current();
        $rank1  = $result['rank'];

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
     * Print the search config form
     *
     * @param string $target    form target
     * @param string $itemtype  item type
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public function showFormPerso($target, $itemtype)
    {
        global $DB;

        $searchopt = Search::getCleanedOptions($itemtype);
        if (!is_array($searchopt)) {
            return false;
        }

        $IDuser = Session::getLoginUserID();

        echo "<div id='tabsbody' class='m-n2'>";
        // Defined items
        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'users_id'  => $IDuser
            ],
            'ORDER'  => 'rank'
        ]);
        $numrows = count($iterator);

        // Get fixed columns
        $fixed_columns = $this->getFixedColumns($itemtype);

        if ($numrows == 0) {
            Session::checkRight(self::$rightname, self::PERSONAL);
            echo "<form method='post' action='$target' class='m-2'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            echo "<input type='hidden' name='users_id' value='$IDuser'>";
            echo __('No personal criteria. Create personal parameters?');
            echo "<input type='submit' name='activate' value=\"" . __('Create') . "\"
                class='btn btn-primary ms-3'>";
            Html::closeForm();
        } else {
            $already_added = self::getForTypeUser($itemtype, $IDuser);

            echo "<table class='table table-striped card-table m-n2'><tr><th colspan='4'>";
            echo "<form method='post' action='$target'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            echo "<input type='hidden' name='users_id' value='$IDuser'>";
            echo "<input type='submit' name='disable' value=\"" . __('Delete') . "\"
                class='btn'>";
            Html::closeForm();

            echo "</th></tr>";
            echo "<tr><td colspan='4'>";
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            echo "<input type='hidden' name='users_id' value='$IDuser'>";
            $group  = '';
            $values = [];
            foreach ($searchopt as $key => $val) {
                if (!is_array($val)) {
                    $group = $val;
                } elseif (count($val) === 1) {
                    $group = $val['name'];
                } elseif (
                    !in_array($key, $fixed_columns)
                    && !in_array($key, $already_added)
                    && (!isset($val['nodisplay']) || !$val['nodisplay'])
                ) {
                    $values[$group][$key] = $val["name"];
                }
            }
            if ($values) {
                Dropdown::showFromArray('num', $values);
                echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary ms-1'>";
            }
            Html::closeForm();
            echo "</td></tr>\n";

            foreach ($fixed_columns as $searchoption_index) {
                if (!isset($searchopt[$searchoption_index])) {
                    // Missing search option; do nothing
                    continue;
                }

                // Print fixed column
                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $searchopt[$searchoption_index]["name"] . "</td>";
                echo "<td colspan='3'>&nbsp;</td>";
                echo "</tr>";
            }

            $i = 0;
            if ($numrows) {
                foreach ($iterator as $data) {
                    if ((!in_array($data["num"], $fixed_columns)) && isset($searchopt[$data["num"]])) {
                        echo "<tr>";
                        echo "<td>";
                        echo $this->nameOfGroupForItemInSearchopt($searchopt, $data["num"]);
                        echo $searchopt[$data["num"]]["name"] . "</td>";

                        if ($i != 0) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='up'" .
                             " title=\"" . __s('Bring up') . "\"" .
                             " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fa fa-arrow-up'></i></button>";
                            Html::closeForm();
                            echo "</td>\n";
                        } else {
                            echo "<td>&nbsp;</td>";
                        }

                        if ($i != ($numrows - 1)) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='down'" .
                            " title=\"" . __s('Bring down') . "\"" .
                            " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fa fa-arrow-down'></i></button>";
                            Html::closeForm();
                            echo "</td>\n";
                        } else {
                            echo "<td>&nbsp;</td>";
                        }

                        if (!isset($searchopt[$data["num"]]["noremove"]) || $searchopt[$data["num"]]["noremove"] !== true) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='purge'" .
                             " title=\"" . _sx('button', 'Delete permanently') . "\"" .
                             " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fa fa-times-circle'></i></button>";
                            Html::closeForm();
                            echo "</td>\n";
                        } else {
                            echo "<td>&nbsp;</td>\n";
                        }
                        echo "</tr>";
                        $i++;
                    }
                }
            }
            echo "</table>";
        }
        echo "</div>";
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
     * @param array $searchopt
     * @param int   $searchoptkey
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
                return $search_options[$search_options_keys[$key]]['name'] . " - ";
            }
        }

        return "";
    }


    /**
     * Print the search config form
     *
     * @param string $target    form target
     * @param string $itemtype  item type
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public function showFormGlobal($target, $itemtype)
    {
        global $DB;

        $searchopt = Search::getCleanedOptions($itemtype);
        if (!is_array($searchopt)) {
            return false;
        }
        $IDuser = 0;

        $global_write = Session::haveRight(self::$rightname, self::GENERAL);

        echo "<div id='tabsbody' class='m-n2'>";
        // Defined items
        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'users_id'  => $IDuser
            ],
            'ORDER'  => 'rank'
        ]);
        $numrows = count($iterator);

        echo "<table class='table table-striped card-table'>";

        // Get fixed columns
        $fixed_columns = $this->getFixedColumns($itemtype);

        if ($global_write) {
            $already_added = self::getForTypeUser($itemtype, $IDuser);
            echo "<tr><td colspan='4'>";
            echo "<form method='post' action='$target'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
            echo "<input type='hidden' name='users_id' value='$IDuser'>";
            $group  = '';
            $values = [];
            foreach ($searchopt as $key => $val) {
                if (!is_array($val)) {
                    $group = $val;
                } elseif (count($val) === 1) {
                    $group = $val['name'];
                } elseif (
                    !in_array($key, $fixed_columns)
                    && !in_array($key, $already_added)
                    && (!isset($val['nodisplay']) || !$val['nodisplay'])
                ) {
                    $values[$group][$key] = $val["name"];
                }
            }
            if ($values) {
                Dropdown::showFromArray('num', $values);
                echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary ms-1'>";
            }
            Html::closeForm();
            echo "</td></tr>";
        }

        foreach ($fixed_columns as $searchoption_index) {
            if (!isset($searchopt[$searchoption_index])) {
                // Missing search option; do nothing
                continue;
            }

            // Print fixed column
            echo "<tr class='tab_bg_2'>";
            echo "<td>" . $searchopt[$searchoption_index]["name"] . "</td>";

            // Some extra table cells are only shown if the user can edit the data
            if ($global_write) {
                echo "<td colspan='3'>&nbsp;</td>";
            }

            echo "</tr>";
        }

        $i = 0;

        if ($numrows) {
            foreach ($iterator as $data) {
                if (
                    (!in_array($data["num"], $fixed_columns))
                    && isset($searchopt[$data["num"]])
                ) {
                    echo "<tr><td>";
                    echo $this->nameOfGroupForItemInSearchopt($searchopt, $data["num"]);
                    echo $searchopt[$data["num"]]["name"];
                    echo "</td>";

                    if ($global_write) {
                        if ($i != 0) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='up'" .
                            " title=\"" . __s('Bring up') . "\"" .
                            " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fa fa-arrow-up'></i></button>";
                            Html::closeForm();
                            echo "</td>";
                        } else {
                            echo "<td>&nbsp;</td>\n";
                        }

                        if ($i != ($numrows - 1)) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='down'" .
                             " title=\"" . __s('Bring down') . "\"" .
                             " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fa fa-arrow-down'></i></button>";
                            Html::closeForm();
                            echo "</td>";
                        } else {
                            echo "<td>&nbsp;</td>\n";
                        }

                        if (!isset($searchopt[$data["num"]]["noremove"]) || $searchopt[$data["num"]]["noremove"] !== true) {
                            echo "<td>";
                            echo "<form method='post' action='$target'>";
                            echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
                            echo "<input type='hidden' name='users_id' value='$IDuser'>";
                            echo "<input type='hidden' name='itemtype' value='$itemtype'>";
                            echo "<button type='submit' name='purge'" .
                            " title=\"" . _sx('button', 'Delete permanently') . "\"" .
                            " class='btn btn-icon btn-sm btn-ghost-secondary'><i class='fas fa-times-circle'></i></button>";
                            Html::closeForm();
                            echo "</td>\n";
                        } else {
                            echo "<td>&nbsp;</td>\n";
                        }
                    }

                    echo "</tr>";
                    $i++;
                }
            }
        }
        echo "</table>";
        echo "</div>";
    }


    /**
     * show defined display preferences for a user
     *
     * @param $users_id integer user ID
     **/
    public static function showForUser($users_id)
    {
        global $DB;

        $url = Toolbox::getItemTypeFormURL(__CLASS__);

        $iterator = $DB->request([
            'SELECT'  => ['itemtype'],
            'COUNT'   => 'nb',
            'FROM'    => self::getTable(),
            'WHERE'   => [
                'users_id'  => $users_id
            ],
            'GROUPBY' => 'itemtype'
        ]);

        if (count($iterator) > 0) {
            $rand = mt_rand();
            echo "<div class='spaced'>";
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['width'            => 400,
                'height'           => 200,
                'container'        => 'mass' . __CLASS__ . $rand,
                'specific_actions' => [__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'delete_for_user'
                                                       => _x('button', 'Delete permanently')
                ],
                'extraparams'      => ['massive_action_fields' => ['users_id']]
            ];

            Html::showMassiveActions($massiveactionparams);

            echo Html::hidden('users_id', ['value'                 => $users_id,
                'data-glpicore-ma-tags' => 'common'
            ]);
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th width='10'>";
            echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
            echo "<th colspan='2'>" . _n('Type', 'Types', 1) . "</th></tr>";
            foreach ($iterator as $data) {
                 echo "<tr class='tab_bg_1'><td width='10'>";
                 Html::showMassiveActionCheckBox(__CLASS__, $data["itemtype"]);
                 echo "</td>";
                if ($item = getItemForItemtype($data["itemtype"])) {
                    $name = $item->getTypeName(1);
                } else {
                    $name = $data["itemtype"];
                }
                echo "<td>$name</td><td class='numeric'>" . $data['nb'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
            echo "</div>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><td class='b center'>" . __('No item found') . "</td></tr>";
            echo "</table>";
        }
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
                $ong = [];
                $ong[1] = __('Global View');
                if (Session::haveRight(self::$rightname, self::PERSONAL)) {
                    $ong[2] = __('Personal View');
                }
                return $ong;
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
                switch ($tabnum) {
                    case 1:
                        $item->showFormGlobal(Toolbox::cleanTarget($_GET['_target']), $_GET["displaytype"]);
                        return true;

                    case 2:
                        Session::checkRight(self::$rightname, self::PERSONAL);
                        $item->showFormPerso(Toolbox::cleanTarget($_GET['_target']), $_GET["displaytype"]);
                        return true;
                }
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
}
