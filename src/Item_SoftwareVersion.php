<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;

class Item_SoftwareVersion extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'itemtype';
    public static $items_id_1 = 'items_id';
    public static $itemtype_2 = 'SoftwareVersion';
    public static $items_id_2 = 'softwareversions_id';


    public static $log_history_1_add    = Log::HISTORY_INSTALL_SOFTWARE;
    public static $log_history_1_delete = Log::HISTORY_UNINSTALL_SOFTWARE;

    public static $log_history_2_add    = Log::HISTORY_INSTALL_SOFTWARE;
    public static $log_history_2_delete = Log::HISTORY_UNINSTALL_SOFTWARE;


    public static function getTypeName($nb = 0)
    {
        return _n('Installation', 'Installations', $nb);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'items_id',
            'name'               => _n('Associated element', 'Associated elements', Session::getPluralNumber()),
            'massiveaction'      => false,
            'nosort'             => true,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_softwareversions',
            'field'              => 'name',
            'name'               => _n('Version', 'Versions', 1),
            'datatype'           => 'dropdown',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _x('software', 'Request source'),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    private function prepareInputForAddAndUpdate(array $input, bool $is_add): array|false
    {
        if (!isset($input['itemtype'], $input['items_id'])) {
            return $is_add ? false : $input;
        }
        $itemtype = $input['itemtype'];
        /** @var CommonDBTM $item */
        $item = getItemForItemtype($itemtype);
        if (
            (!isset($input['is_template_item']) && $item->maybeTemplate())
            || (!isset($input['is_deleted_item']) && $item->maybeDeleted())
        ) {
            if ($item->getFromDB($input['items_id'])) {
                if ($item->maybeTemplate()) {
                    $input['is_template_item'] = $item->getField('is_template');
                }
                if ($item->maybeDeleted()) {
                    $input['is_deleted_item']  = $item->getField('is_deleted');
                }
            } else {
                return false;
            }
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAndUpdate($input, true);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForAddAndUpdate($input, false);
        if ($input === false) {
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'add':
                Software::dropdownSoftwareToInstall(
                    'peer_softwareversions_id',
                    $_SESSION["glpiactive_entity"]
                );
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']) . "</span>";
                return true;

            case 'move_version':
                $input = $ma->getInput();
                if (isset($input['options'])) {
                    if (isset($input['options']['move'])) {
                        $options = ['softwares_id' => $input['options']['move']['softwares_id']];
                        if (isset($input['options']['move']['used'])) {
                            $options['used'] = $input['options']['move']['used'];
                        }
                        SoftwareVersion::dropdownForOneSoftware($options);
                        echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                        return true;
                    }
                }
                return false;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'move_version':
                $input = $ma->getInput();
                if (isset($input['softwareversions_id'])) {
                    foreach ($ids as $id) {
                        if ($item->can($id, UPDATE)) {
                            //Process rules
                            if (
                                $item->update(['id' => $id,
                                    'softwareversions_id'
                                                  => $input['softwareversions_id'],
                                ])
                            ) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;

            case 'add':
                $itemtoadd = new Item_SoftwareVersion();
                if (isset($_POST['peer_softwareversions_id'])) {
                    foreach ($ids as $id) {
                        if ($item->can($id, UPDATE)) {
                            //Process rules
                            if (
                                $itemtoadd->add([
                                    'items_id'              => $id,
                                    'itemtype'              => $item::getType(),
                                    'softwareversions_id'   => $_POST['peer_softwareversions_id'],
                                ])
                            ) {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($itemtoadd->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($itemtoadd->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
        }

        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function updateDatasForItem($itemtype, $items_id)
    {
        global $DB;

        $item = getItemForItemtype($itemtype);
        if ($item->getFromDB($items_id)) {
            $result = $DB->update(
                static::getTable(),
                [
                    'is_template_item'  => $item->maybeTemplate() ? $item->getField('is_template') : 0,
                    'is_deleted_item'   => $item->maybeDeleted() ? $item->getField('is_deleted') : 0,
                ],
                [
                    'items_id' => $items_id,
                    'itemtype' => $itemtype,
                ]
            );
            return $result;
        }
        return false;
    }

    /**
     * Get number of installed licenses of a version
     *
     * @param integer          $softwareversions_id version ID
     * @param string|integer[] $entity              to search for item in ('' = all active entities)
     *
     * @return integer number of installations
     **/
    public static function countForVersion($softwareversions_id, $entity = '')
    {
        global $DB;

        $item_version_table = self::getTable(self::class);
        $iterator = $DB->request([
            'SELECT'    => ['itemtype'],
            'DISTINCT'  => true,
            'FROM'      => $item_version_table,
            'WHERE'     => [
                'softwareversions_id'   => $softwareversions_id,
            ],
        ]);

        $target_types = [];
        foreach ($iterator as $data) {
            if (is_a($data['itemtype'], CommonDBTM::class, true)) {
                $target_types[] = $data['itemtype'];
            }
        }

        $count = 0;
        foreach ($target_types as $itemtype) {
            $itemtable = $itemtype::getTable();
            $request = [
                'FROM'         => 'glpi_items_softwareversions',
                'COUNT'        => 'cpt',
                'INNER JOIN'   => [
                    $itemtable  => [
                        'FKEY'   => [
                            $itemtable                    => 'id',
                            'glpi_items_softwareversions' => 'items_id', [
                                'AND' => [
                                    'glpi_items_softwareversions.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_items_softwareversions.softwareversions_id'     => $softwareversions_id,
                    'glpi_items_softwareversions.is_deleted'              => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', $entity),
            ];
            $item = new $itemtype();
            if ($item->maybeDeleted()) {
                $request['WHERE']["$itemtable.is_deleted"] = 0;
            }
            if ($item->maybeTemplate()) {
                $request['WHERE']["$itemtable.is_template"] = 0;
            }
            $count += $DB->request($request)->current()['cpt'];
        }
        return $count;
    }

    /**
     * Get number of installed versions of a software
     *
     * @param integer $softwares_id software ID
     *
     * @return number of installations
     **/
    public static function countForSoftware($softwares_id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => ['itemtype'],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_softwareversions',
            'INNER JOIN'   => [
                'glpi_items_softwareversions'   => [
                    'FKEY'   => [
                        'glpi_items_softwareversions' => 'softwareversions_id',
                        'glpi_softwareversions'       => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'softwares_id' => $softwares_id,
            ],
        ]);

        $target_types = [];
        foreach ($iterator as $data) {
            if (is_a($data['itemtype'], CommonDBTM::class, true)) {
                $target_types[] = $data['itemtype'];
            }
        }

        $count = 0;
        foreach ($target_types as $itemtype) {
            if (!getItemForItemtype($itemtype)) {
                trigger_error(
                    "Itemtype $itemtype not found",
                    E_USER_WARNING
                );
                continue;
            }
            $itemtable = $itemtype::getTable();
            $request = [
                'FROM'         => 'glpi_softwareversions',
                'COUNT'        => 'cpt',
                'INNER JOIN'   => [
                    'glpi_items_softwareversions'   => [
                        'FKEY'   => [
                            'glpi_items_softwareversions' => 'softwareversions_id',
                            'glpi_softwareversions'       => 'id',
                        ],
                    ],
                    $itemtable  => [
                        'FKEY'   => [
                            $itemtable                    => 'id',
                            'glpi_items_softwareversions' => 'items_id', [
                                'AND' => [
                                    'glpi_items_softwareversions.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_softwareversions.softwares_id'      => $softwares_id,
                    'glpi_items_softwareversions.is_deleted'  => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', '', true),
            ];
            $item = new $itemtype();
            if ($item->maybeDeleted()) {
                $request['WHERE']["$itemtable.is_deleted"] = 0;
            }
            if ($item->maybeTemplate()) {
                $request['WHERE']["$itemtable.is_template"] = 0;
            }
            $count += $DB->request($request)->current()['cpt'];
        }
        return $count;
    }

    /**
     * Show installation of a Software
     *
     * @param Software $software
     *
     * @return void
     **/
    public static function showForSoftware(Software $software)
    {
        self::showInstallations($software->getField('id'), 'softwares_id');
    }

    /**
     * Show installation of a Version
     *
     * @param SoftwareVersion $version
     *
     * @return void
     **/
    public static function showForVersion(SoftwareVersion $version)
    {
        self::showInstallations($version->getField('id'), 'id');
    }

    /**
     * Show installations of a software
     *
     * @param integer $searchID  value of the ID to search
     * @param string  $crit      to search : softwares_id (software) or id (version)
     *
     * @return void
     **/
    private static function showInstallations($searchID, $crit)
    {
        global $CFG_GLPI, $DB;

        if (!Software::canView() || !$searchID) {
            return;
        }

        $canedit       = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
        $canshowitems  = [];
        $item_version_table = self::getTable(self::class);

        $refcolumns = [
            'version'           => _n('Version', 'Versions', Session::getPluralNumber()),
            'item_type'         => __('Item type'),
            'itemname'          => __('Name'),
            'entity'            => Entity::getTypeName(1),
            'serial'            => __('Serial number'),
            'otherserial'       => __('Inventory number'),
            'location,itemname' => Location::getTypeName(1),
            'state,itemname'    => __('Status'),
            'groupe,itemname'   => Group::getTypeName(1),
            'username,itemname' => User::getTypeName(1),
            'lname'             => SoftwareLicense::getTypeName(Session::getPluralNumber()),
            'date_install'      => __('Installation date'),
        ];
        if ($crit != "softwares_id") {
            unset($refcolumns['version']);
        }

        if (isset($_GET["start"])) {
            $start = $_GET["start"];
        } else {
            $start = 0;
        }

        if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
            $order = "DESC";
        } else {
            $order = "ASC";
        }

        if (!empty($_GET["sort"]) && isset($refcolumns[$_GET["sort"]])) {
            // manage several param like location,compname :  order first
            $tmp  = explode(",", $_GET["sort"]);
            $sort = "`" . implode("` $order,`", $tmp) . "`";
        } else {
            if ($crit == "softwares_id") {
                $sort = "`entity` $order, `version`, `itemname`";
            } else {
                $sort = "`entity` $order, `itemname`";
            }
        }

        // Total Number of events
        if ($crit == "softwares_id") {
            // Software ID
            $number = self::countForSoftware($searchID);
        } else {
            //SoftwareVersion ID
            $number = self::countForVersion($searchID);
        }

        echo "<div class='center'>";
        if ($number < 1) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __s('No results found') . "</th></tr>";
            echo "</table></div>\n";
            return;
        }

        // Display the pager
        Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

        $queries = [];
        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            $canshowitems[$itemtype] = $itemtype::canView();
            $itemtable = $itemtype::getTable();
            $query = [
                'SELECT' => [
                    $item_version_table . '.*',
                    'glpi_softwareversions.name AS version',
                    'glpi_softwareversions.softwares_id AS sID',
                    'glpi_softwareversions.id AS vID',
                    "{$itemtable}.name AS itemname",
                    "{$itemtable}.id AS iID",
                    new QueryExpression($DB::quoteValue($itemtype), 'item_type'),
                ],
                'FROM'   => $item_version_table,
                'INNER JOIN' => [
                    'glpi_softwareversions' => [
                        'FKEY'   => [
                            $item_version_table     => 'softwareversions_id',
                            'glpi_softwareversions' => 'id',
                        ],
                    ],
                ],
                'LEFT JOIN' => [
                    $itemtable => [
                        'FKEY'   => [
                            $item_version_table  => 'items_id',
                            $itemtable        => 'id', [
                                'AND' => [
                                    $item_version_table . '.itemtype'  => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    "glpi_softwareversions.$crit"                => $searchID,
                    'glpi_items_softwareversions.is_deleted'     => 0,
                ],
            ];
            if ($DB->fieldExists($itemtable, 'serial')) {
                $query['SELECT'][] = $itemtable . '.serial';
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), $itemtable . ".serial");
            }
            if ($DB->fieldExists($itemtable, 'otherserial')) {
                $query['SELECT'][] = $itemtable . '.otherserial';
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), $itemtable . ".otherserial");
            }
            if ($DB->fieldExists($itemtable, 'users_id')) {
                $query['SELECT'][] = 'glpi_users.name AS username';
                $query['SELECT'][] = 'glpi_users.id AS userid';
                $query['SELECT'][] = 'glpi_users.realname AS userrealname';
                $query['SELECT'][] = 'glpi_users.firstname AS userfirstname';
                $query['LEFT JOIN']['glpi_users'] = [
                    'FKEY'   => [
                        $itemtable     => 'users_id',
                        'glpi_users'   => 'id',
                    ],
                ];
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), $itemtable . ".username");
                $query['SELECT'][] = new QueryExpression($DB::quoteValue('-1'), $itemtable . ".userid");
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), $itemtable . ".userrealname");
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), $itemtable . ".userfirstname");
            }
            if ($DB->fieldExists($itemtable, 'entities_id')) {
                $query['SELECT'][] = 'glpi_entities.completename AS entity';
                $query['LEFT JOIN']['glpi_entities'] = [
                    'FKEY'   => [
                        $itemtable     => 'entities_id',
                        'glpi_entities'   => 'id',
                    ],
                ];
                $query['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', '', true);
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), 'entity');
            }
            if ($DB->fieldExists($itemtable, 'locations_id')) {
                $query['SELECT'][] = 'glpi_locations.completename AS location';
                $query['LEFT JOIN']['glpi_locations'] = [
                    'FKEY'   => [
                        $itemtable     => 'locations_id',
                        'glpi_locations'   => 'id',
                    ],
                ];
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), 'location');
            }
            if ($DB->fieldExists($itemtable, 'states_id')) {
                $query['SELECT'][] = 'glpi_states.name AS state';
                $query['LEFT JOIN']['glpi_states'] = [
                    'FKEY'   => [
                        $itemtable     => 'states_id',
                        'glpi_states'   => 'id',
                    ],
                ];
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), 'state');
            }
            if ($DB->fieldExists($itemtable, 'groups_id')) {
                $query['SELECT'][] = 'glpi_groups.name AS groupe';
                $query['LEFT JOIN']['glpi_groups'] = [
                    'FKEY'   => [
                        $itemtable     => 'groups_id',
                        'glpi_groups'   => 'id',
                    ],
                ];
            } else {
                $query['SELECT'][] = new QueryExpression($DB::quoteValue(''), 'groupe');
            }
            if ($DB->fieldExists($itemtable, 'is_deleted')) {
                $query['WHERE']["{$itemtable}.is_deleted"] = 0;
            }
            if ($DB->fieldExists($itemtable, 'is_template')) {
                $query['WHERE']["{$itemtable}.is_template"] = 0;
            }
            $queries[] = $query;
        }
        $union = new QueryUnion($queries, true);
        $criteria = [
            'SELECT' => [],
            'FROM'   => $union,
            'ORDER'        => "$sort $order",
            'LIMIT'        => $_SESSION['glpilist_limit'],
            'START'        => $start,
        ];
        $iterator = $DB->request($criteria);

        $rand = mt_rand();

        if ($data = $iterator->current()) {
            $softwares_id  = $data['sID'];
            $soft          = new Software();
            $showEntity    = ($soft->getFromDB($softwares_id) && $soft->isRecursive());
            $title         = $soft->fields["name"];

            if ($crit === "id") {
                $title = sprintf(__('%1$s - %2$s'), $title, $data["version"]);
            }

            Session::initNavigateListItems(
                $data['item_type'],
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Software::getTypeName(1),
                    $title
                )
            );

            if ($canedit) {
                $rand = mt_rand();
                Html::openMassiveActionsForm('mass' . self::class . $rand);
                $massiveactionparams
                 = ['num_displayed'
                        => min($_SESSION['glpilist_limit'], $number),
                     'container'
                        => 'mass' . self::class . $rand,
                     'specific_actions'
                        => [self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'move_version'
                                       => _x('button', 'Move'),
                            'purge' => _x('button', 'Delete permanently'),
                        ],
                 ];
                // Options to update version
                $massiveactionparams['extraparams']['options']['move']['softwares_id'] = $softwares_id;
                if ($crit == 'softwares_id') {
                    $massiveactionparams['extraparams']['options']['move']['used'] = [];
                } else {
                    $massiveactionparams['extraparams']['options']['move']['used'] = [$searchID];
                }

                Html::showMassiveActions($massiveactionparams);
            }

            echo "<div class='table-responsive'>";
            echo "<table class='tab_cadre_fixehov'>";

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
                $header_end    .= "</th>";
            }
            $columns = $refcolumns;
            if (!$showEntity) {
                unset($columns['entity']);
            }

            foreach ($columns as $key => $val) {
                $header_end .= "<th" . ($sort == "`$key`" ? " class='order_$order'" : '') . ">";
                $header_end .= $key !== 'lname'
                    ? "<a href='javascript:reloadTab(\"sort=$key&amp;order=" . (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>" . htmlescape($val) . "</a>"
                    : htmlescape($val);
                $header_end .= "</th>";
            }

            $header_end .= "</tr>\n";
            echo $header_begin . $header_top . $header_end;

            do {
                Session::addToNavigateListItems($data['item_type'], $data["iID"]);

                echo "<tr class='tab_bg_2'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(self::class, $data["id"]);
                    echo "</td>";
                }

                if ($crit == "softwares_id") {
                    echo "<td><a href='" . htmlescape(SoftwareVersion::getFormURLWithID($data['vID'])) . "'>"
                     . htmlescape($data['version']) . "</a></td>";
                }

                $itemname = $data['itemname'];
                if (empty($itemname) || $_SESSION['glpiis_ids_visible']) {
                    $itemname = sprintf(__('%1$s (%2$s)'), $itemname, $data['iID']);
                }

                echo "<td>" . htmlescape($data['item_type']) . "</td>";

                $itemname = htmlescape($itemname);
                if ($canshowitems[$data['item_type']]) {
                    echo "<td><a href='" . htmlescape($data['item_type']::getFormURLWithID($data['iID'])) . "'>$itemname</a></td>";
                } else {
                    echo "<td>" . $itemname . "</td>";
                }

                if ($showEntity) {
                    echo "<td>" . htmlescape($data['entity']) . "</td>";
                }
                echo "<td>" . htmlescape($data['serial']) . "</td>";
                echo "<td>" . htmlescape($data['otherserial']) . "</td>";
                echo "<td>" . htmlescape($data['location']) . "</td>";
                echo "<td>" . htmlescape($data['state']) . "</td>";
                echo "<td>" . htmlescape($data['groupe']) . "</td>";
                echo "<td>";
                if ($data['userid']) {
                    echo formatUserLink(
                        $data['userid'],
                        $data['username'],
                        $data['userrealname'],
                        $data['userfirstname'],
                    );
                }
                echo "</td>";

                $lics = Item_SoftwareLicense::getLicenseForInstallation(
                    $data['item_type'],
                    $data['iID'],
                    $data['vID']
                );
                echo "<td>";

                if (count($lics)) {
                    foreach ($lics as $lic) {
                        $serial = $lic['serial'];

                        if (!empty($lic['type'])) {
                            $serial = sprintf(__('%1$s (%2$s)'), $serial, $lic['type']);
                        }

                        echo "<a href='" . htmlescape(SoftwareLicense::getFormURLWithID($lic['id'])) . "'>" . htmlescape($lic['name']);
                        echo "</a> - " . htmlescape($serial);

                        echo "<br>";
                    }
                }
                echo "</td>";

                echo "<td>" . htmlescape(Html::convDate($data['date_install'])) . "</td>";
                echo "</tr>";

                $iterator->next();
            } while ($data = $iterator->current());

            echo $header_begin . $header_bottom . $header_end;

            echo "</table>";
            echo "</div>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else { // Not found
            echo __s('No results found');
        }
        Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

        echo "</div>";
    }

    /**
     * Show number of installations per entity
     *
     * @param SoftwareVersion $version SoftwareVersion object
     *
     * @return void
     **/
    public static function showForVersionByEntity(SoftwareVersion $version)
    {
        global $DB;

        $softwareversions_id = $version->getField('id');

        if (!Software::canView() || !$softwareversions_id) {
            return;
        }

        echo "<div class='center'>";
        echo "<table class='tab_cadre'><tr>";
        echo "<th>" . htmlescape(Entity::getTypeName(1)) . "</th>";
        echo "<th>" . htmlescape(self::getTypeName(Session::getPluralNumber())) . "</th>";
        echo "</tr>\n";

        $tot = 0;

        $iterator = $DB->request([
            'SELECT' => ['id', 'completename'],
            'FROM'   => 'glpi_entities',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_entities'),
            'ORDER'  => ['completename'],
        ]);

        foreach ($iterator as $data) {
            $nb = self::countForVersion($softwareversions_id, $data['id']);
            if ($nb > 0) {
                echo "<tr class='tab_bg_2'><td>" . htmlescape($data["completename"]) . "</td>";
                echo "<td class='numeric'>" . $nb . "</td></tr>\n";
                $tot += $nb;
            }
        }

        if ($tot > 0) {
            echo "<tr class='tab_bg_1'><td class='center b'>" . __s('Total') . "</td>";
            echo "<td class='numeric b'>" . $tot . "</td></tr>\n";
        } else {
            echo "<tr class='tab_bg_1'><td colspan='2 b'>" . __s('No results found') . "</td></tr>\n";
        }
        echo "</table></div>";
    }

    /**
     * Get software related to a given item
     *
     * @param CommonDBTM $item  Item instance
     * @param ?string     $sort  Field to sort on
     * @param ?string     $order Sort order
     * @param array       $filters
     *
     * @return DBmysqlIterator
     */
    public static function getFromItem(CommonDBTM $item, $sort = null, $order = null, array $filters = []): DBmysqlIterator
    {
        global $DB;

        $selftable     = self::getTable(self::class);

        $select = [
            'glpi_softwares.softwarecategories_id',
            'glpi_softwares.name AS softname',
            "glpi_items_softwareversions.id",
            'glpi_states.name as state',
            'glpi_softwareversions.id AS verid',
            'glpi_softwareversions.softwares_id',
            'glpi_softwareversions.name AS version',
            'glpi_softwareversions.arch AS arch',
            'glpi_softwares.is_valid AS softvalid',
            'glpi_items_softwareversions.date_install AS dateinstall',
            "$selftable.is_dynamic",
        ];

        $request = [
            'SELECT'    => $select,
            'FROM'      => $selftable,
            'LEFT JOIN' => [
                'glpi_softwareversions' => [
                    'FKEY'   => [
                        $selftable              => 'softwareversions_id',
                        'glpi_softwareversions' => 'id',
                    ],
                ],
                'glpi_states'  => [
                    'FKEY'   => [
                        'glpi_softwareversions' => 'states_id',
                        'glpi_states'           => 'id',
                    ],
                ],
                'glpi_softwares'  => [
                    'FKEY'   => [
                        'glpi_softwareversions' => 'softwares_id',
                        'glpi_softwares'        => 'id',
                    ],
                ],
                'glpi_softwarecategories' => [
                    'FKEY'   => [
                        'glpi_softwares'          => 'softwarecategories_id',
                        'glpi_softwarecategories' => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                "{$selftable}.items_id" => $item->getField('id'),
                "{$selftable}.itemtype" => $item->getType(),
            ] + getEntitiesRestrictCriteria('glpi_softwares', '', '', true),
            'ORDER'     => ['softname', 'version'],
        ];

        if (count($filters)) {
            if (($filters['name'] ?? "") !== '') {
                $request['WHERE']['glpi_softwares.name'] = ['LIKE', '%' . $filters['name'] . '%'];
            }
            if (($filters['state'] ?? "") !== '') {
                $request['WHERE']['glpi_states.name'] = ['LIKE', '%' . $filters['state'] . '%'];
            }
            if (($filters['version'] ?? "") !== '') {
                $request['WHERE']['glpi_softwareversions.name'] = ['LIKE', '%' . $filters['version'] . '%'];
            }
            if (($filters['arch'] ?? "") !== '') {
                $request['WHERE']['glpi_softwareversions.arch'] = ['LIKE', '%' . $filters['arch'] . '%'];
            }
            if (isset($filters['is_dynamic']) && $filters['is_dynamic'] !== '') {
                $request['WHERE']["$selftable.is_dynamic"] = $filters['is_dynamic'];
            }
            if (($filters['software_category'] ?? "") !== '') {
                $request['WHERE']['glpi_softwarecategories.name'] = ['LIKE', '%' . $filters['software_category'] . '%'];
            }
            if (($filters['date_install'] ?? "") !== '') {
                $request['WHERE']['glpi_items_softwareversions.date_install'] = $filters['date_install'];
            }
        }

        if ($item->maybeDeleted()) {
            $request['WHERE']["{$selftable}.is_deleted"] = 0;
        }

        $crit = Session::getSavedOption(self::class, 'criterion', -1);
        if ($crit > -1) {
            $request['WHERE']['glpi_softwares.softwarecategories_id'] = (int) $crit;
        }

        return $DB->request($request);
    }

    /**
     * Show software installed on an asset
     *
     * @param CommonDBTM $item
     * @param integer  $withtemplate template case of the view process
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        if (!Software::canView()) {
            return;
        }

        $items_id      = $item->getID();
        $itemtype      = $item::class;
        $rand          = mt_rand();
        $filters       = $_GET['filters'] ?? [];
        $is_filtered   = count($filters) > 0;
        $canedit       = Session::haveRightsOr("software", [CREATE, UPDATE, DELETE, PURGE]);
        $entities_id   = $item->fields["entities_id"];

        $crit         = Session::getSavedOption(self::class, 'criterion', -1);

        $iterator = self::getFromItem($item, null, null, $filters);

        if (
            (empty($withtemplate) || ($withtemplate != 2))
            && $canedit
        ) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action='" . htmlescape(Item_SoftwareVersion::getFormURL()) . "'>";
            echo __s('Install a software');
            echo "<input type='hidden' name='itemtype' value='" . htmlescape($itemtype) . "'>";
            echo "<input type='hidden' name='items_id' value='$items_id'>";
            echo "<div class='d-flex'>";
            echo "<div class='col-auto'>";
            Software::dropdownSoftwareToInstall("softwareversions_id", $entities_id);
            echo "</div>";
            echo "<div class='col-auto'>";
            echo "<button type='submit' name='add' class='btn btn-primary ms-1'>";
            echo "<i class='ti ti-link'></i>" . _sx('button', 'Install');
            echo "</button>";
            echo "</div>";
            echo "</div>"; // d-flex
            Html::closeForm();
            echo "</div>"; //firstbloc
        }
        echo "<div class='spaced'>";

        Session::initNavigateListItems(
            'Software',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $itemtype::getTypeName(1),
                $item->getName()
            )
        );
        Session::initNavigateListItems(
            'SoftwareLicense',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                $itemtype::getTypeName(1),
                $item->getName()
            )
        );

        // Mini Search engine
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='2'>" . htmlescape(Software::getTypeName(Session::getPluralNumber())) . "</th></tr>";
        echo "<tr class='tab_bg_1'><td>";
        echo _sn('Category', 'Categories', 1) . "</td><td>";
        SoftwareCategory::dropdown(['value'      => $crit,
            'toadd'      => ['-1' =>  __('All categories')],
            'emptylabel' => __('Uncategorized software'),
            'on_change'  => 'reloadTab("start=0&criterion="+this.value)',
        ]);
        echo "</td></tr></table></div>";
        $number = count($iterator);
        $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
        if ($start >= $number) {
            $start = 0;
        }

        $installed = [];

        if ($number || $is_filtered) {
            echo "<div class='spaced'>";
            Html::printAjaxPager('', $start, $number);

            echo "<div class='table-responsive'>";
            if ($canedit) {
                $rand = mt_rand();
                Html::openMassiveActionsForm('mass' . self::class . $rand);
                $massiveactionparams
                = ['num_displayed'
                         => min($_SESSION['glpilist_limit'], $number),
                    'container'
                         => 'mass' . self::class . $rand,
                    'specific_actions'
                         => ['purge' => _x('button', 'Delete permanently')],
                ];

                Html::showMassiveActions($massiveactionparams);
            }
            echo "<table class='table table-hover table-striped border my-2'>";

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('mass' . self::class . $rand);
                $header_end    .= "</th>";
            }
            $header_end .= "<th>" . __s('Name') . "</th>";
            $header_end .= "<th>" . __s('Status') . "</th>";
            $header_end .= "<th>" . _sn('Version', 'Versions', 1) . "</th>";
            $header_end .= "<th>" . htmlescape(SoftwareLicense::getTypeName(1)) . "</th>";
            $header_end .= "<th>" . __s('Installation date') . "</th>";
            $header_end .= "<th>" . _sn('Architecture', 'Architectures', 1) . "</th>";
            $header_end .= "<th>" . __s('Automatic inventory') . "</th>";
            $header_end .= "<th>" . htmlescape(SoftwareCategory::getTypeName(1)) . "</th>";
            $header_end .= "<th>" . __s('Valid license') . "</th>";
            $header_end .= "<th>
                <button class='btn btn-sm show_filters " . ($is_filtered ? "btn-secondary" : "btn-outline-secondary") . "'>
                    <i class='ti ti-filter'></i>
                    <span class='d-none d-xl-block'>" . __s('Filter') . "</span>
                </button></th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            if ($is_filtered) {
                echo "<tr class='filter_row'>
                    <td>
                        <input type='hidden' name='filters[active]' value='1'>
                    </td>
                    <td>
                        <input type='text' class='form-control' name='filters[name]' value='" . htmlescape($filters['name'] ?? '') . "'>
                    </td>
                    <td>
                        <input type='text' class='form-control' name='filters[state]' value='" . htmlescape($filters['state'] ?? '') . "'>
                    </td>
                    <td>
                        <input type='text' class='form-control' name='filters[version]' value='" . htmlescape($filters['version'] ?? '') . "'>
                    </td>
                    <td></td>
                    <td>
                        " . Html::showDateField(
                    "filters[date_install]",
                    [
                        'value'   => ($filters['date_install'] ?? ''),
                        'display' => false,
                    ]
                ) . "
                    </td>
                    <td>
                        <input type='text' class='form-control' name='filters[arch]' value='" . htmlescape($filters['arch'] ?? '') . "'>
                    </td>
                    <td>" . Dropdown::showFromArray(
                    "filters[is_dynamic]",
                    [
                        null => "",
                        '1'  => __('Yes'),
                        '0'  => __('No'),
                    ],
                    [
                        'value'   => ($filters['is_dynamic'] ?? null),
                        'display' => false,
                    ]
                ) . "
                    </td>
                    <td>"
                    . SoftwareCategory::dropdown([
                        'value'      => $crit,
                        'toadd'      => ['-1' =>  __('All categories')],
                        'emptylabel' => __('Uncategorized software'),
                        'display'    => false,
                    ])
                     . "</td>
                    <td></td>
                    <td></td>
                </tr>";
            }

            for ($row = 0; $data = $iterator->current(); $row++) {
                if (($row >= $start) && ($row < ($start + $_SESSION['glpilist_limit']))) {
                    $licids = self::softwareByCategory(
                        $data,
                        $itemtype,
                        $items_id,
                        $withtemplate,
                        $canedit,
                        true
                    );
                } else {
                    $licids = self::softwareByCategory(
                        $data,
                        $itemtype,
                        $items_id,
                        $withtemplate,
                        $canedit,
                        false
                    );
                }
                Session::addToNavigateListItems('Software', $data["softwares_id"]);

                foreach ($licids as $licid) {
                    Session::addToNavigateListItems('SoftwareLicense', $licid);
                    $installed[] = $licid;
                }
                $iterator->next();
            }

            echo "<tfoot>";
            echo $header_begin . $header_bottom . $header_end;
            echo "</tfoot>";
            echo "</table>";
            echo "</div>";
            Html::printAjaxPager('', $start, $number);
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<p class='center b'>" . __s('No results found') . "</p>";
        }
        echo "</div>";

        if (
            (empty($withtemplate) || ($withtemplate != 2))
            && $canedit
        ) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' action='" . htmlescape(Item_SoftwareLicense::getFormURL()) . "'>";
            echo __s('Add a licence');
            echo "<input type='hidden' name='itemtype' value='" . htmlescape($itemtype) . "'>";
            echo "<input type='hidden' name='items_id' value='$items_id'>";
            echo "<div class='d-flex'>";
            echo "<div class='col-auto'>";
            Software::dropdownLicenseToInstall("softwarelicenses_id", $entities_id);
            echo "</div>";
            echo "<div class='col-auto'>";
            echo "<button type='submit' name='add' class='btn btn-primary ms-1'>";
            echo "<i class='ti ti-link'></i>" . _sx('button', 'Add');
            echo "</button>";
            echo "</div>";
            echo "</div>"; // d-flex
            Html::closeForm();
            echo "</div>"; //firstbloc


            echo "<form method='post' action='" . htmlescape(Item_SoftwareLicense::getFormURL()) . "'>";
            echo "<div class='spaced'><table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'><th colspan='2'>" . htmlescape(SoftwareLicense::getTypeName(Session::getPluralNumber())) . "</th></tr>";
            echo "</table></div>";
            Html::closeForm();
        }
        echo "<div class='spaced'>";
        // Affected licenses NOT installed
        $lic_where = [];
        if (count($installed)) {
            $lic_where['NOT'] = ['glpi_softwarelicenses.id' => $installed];
        }

        $lic_request = [
            'SELECT'       => [
                'glpi_softwarelicenses.*',
                'glpi_items_softwarelicenses.id AS linkid',
                'glpi_softwares.name AS softname',
                'glpi_softwareversions.name AS version',
                'glpi_states.name AS state',
            ],
            'FROM'         => SoftwareLicense::getTable(),
            'INNER JOIN'   => [
                'glpi_softwares'  => [
                    'FKEY'   => [
                        'glpi_softwarelicenses' => 'softwares_id',
                        'glpi_softwares'        => 'id',
                    ],
                ],
            ],
            'LEFT JOIN'    => [
                'glpi_items_softwarelicenses'   => [
                    'FKEY'   => [
                        'glpi_items_softwarelicenses' => 'softwarelicenses_id',
                        'glpi_softwarelicenses'       => 'id',
                    ],
                ],
                'glpi_softwareversions'   => [
                    'FKEY'   => [
                        'glpi_softwareversions' => 'id',
                        'glpi_softwarelicenses' => 'softwareversions_id_use',
                        [
                            'AND' => [
                                'glpi_softwarelicenses.softwareversions_id_use' => 0,
                                'glpi_softwarelicenses.softwareversions_id_buy' => new QueryExpression(DBmysql::quoteName('glpi_softwareversions.id')),
                            ],
                        ],
                    ],
                ],
                'glpi_states'  => [
                    'FKEY'   => [
                        'glpi_softwareversions' => 'states_id',
                        'glpi_states'           => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_items_softwarelicenses.items_id'  => $items_id,
                'glpi_items_softwarelicenses.itemtype'  => $itemtype,
            ] + $lic_where,
            'ORDER'     => ['softname', 'version'],
        ];
        if ($item->maybeDeleted()) {
            $lic_request['WHERE']['glpi_items_softwarelicenses.is_deleted'] = 0;
        }
        $lic_iterator = $DB->request($lic_request);

        if ($number = $lic_iterator->count()) {
            if ($canedit) {
                $rand = mt_rand();
                Html::openMassiveActionsForm('massSoftwareLicense' . $rand);

                $actions = ['Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR
                              . 'install' => _x('button', 'Install'),
                ];
                if (SoftwareLicense::canUpdate()) {
                    $actions['purge'] = _x('button', 'Delete permanently');
                }

                $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                    'container'        => 'massSoftwareLicense' . $rand,
                    'specific_actions' => $actions,
                ];

                Html::showMassiveActions($massiveactionparams);
            }
            echo "<table class='tab_cadre_fixehov'>";

            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('massSoftwareLicense' . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('massSoftwareLicense' . $rand);
                $header_end    .= "</th>";
            }
            $header_end .= "<th>" . __s('Name') . "</th><th>" . __s('Status') . "</th>";
            $header_end .= "<th>" . _sn('Version', 'Versions', 1) . "</th><th>" . htmlescape(SoftwareLicense::getTypeName(1)) . "</th>";
            $header_end .= "<th>" . __s('Installation date') . "</th>";
            $header_end .= "</tr>\n";
            echo $header_begin . $header_top . $header_end;

            foreach ($lic_iterator as $data) {
                self::displaySoftwareByLicense($data, $withtemplate, $canedit);
                Session::addToNavigateListItems('SoftwareLicense', $data["id"]);
            }

            echo $header_begin . $header_bottom . $header_end;

            echo "</table>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<p class='center b'>" . __s('No results found') . "</p>";
        }

        echo "</div>\n";
    }

    /**
     * Display a installed software for a category
     *
     * @param array   $data         data used to display
     * @param string  $itemtype     Type of the item
     * @param integer $items_id     ID of the item
     * @param integer $withtemplate template case of the view process
     * @param boolean $canedit      user can edit software ?
     * @param boolean $display      display and calculate if true or just calculate
     *
     * @return integer[] Found licenses ids
     **/
    private static function softwareByCategory(
        $data,
        $itemtype,
        $items_id,
        $withtemplate,
        $canedit,
        $display
    ) {
        global $DB;

        $ID    = $data["id"];
        $verid = $data["verid"];

        if ($display) {
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
                echo "<td>";
                Html::showMassiveActionCheckBox(self::class, $ID);
                echo "</td>";
            }
            echo "<td>";
            echo "<a href='" . htmlescape(Software::getFormURLWithID($data['softwares_id'])) . "'>";
            echo  htmlescape(
                $_SESSION["glpiis_ids_visible"]
                ? sprintf(__('%1$s (%2$s)'), $data["softname"], $data['softwares_id'])
                : $data["softname"]
            );
            echo "</a></td>";
            echo "<td>" . htmlescape($data["state"]) . "</td>";

            echo "<td>" . htmlescape($data["version"]);
            echo "</td><td>";
        }

        $iterator = $DB->request([
            'SELECT'       => [
                'glpi_softwarelicenses.*',
                'glpi_softwarelicensetypes.name AS type',
            ],
            'FROM'         => 'glpi_items_softwarelicenses',
            'INNER JOIN'   => [
                'glpi_softwarelicenses' => [
                    'FKEY'   => [
                        'glpi_items_softwarelicenses'   => 'softwarelicenses_id',
                        'glpi_softwarelicenses'             => 'id',
                    ],
                ],
            ],
            'LEFT JOIN'    => [
                'glpi_softwarelicensetypes'   => [
                    'FKEY'   => [
                        'glpi_softwarelicenses'       => 'softwarelicensetypes_id',
                        'glpi_softwarelicensetypes'   => 'id',
                    ],
                ],
            ],
            'WHERE'        => [
                "glpi_items_softwarelicenses.items_id"    => $items_id,
                'glpi_items_softwarelicenses.itemtype'    => $itemtype,
                'OR'                                            => [
                    'glpi_softwarelicenses.softwareversions_id_use' => $verid,
                    [
                        'glpi_softwarelicenses.softwareversions_id_use' => 0,
                        'glpi_softwarelicenses.softwareversions_id_buy' => $verid,
                    ],
                ],
            ],
        ]);

        $licids = [];
        foreach ($iterator as $licdata) {
            $licids[]  = $licdata['id'];
            $licserial = $licdata['serial'];

            if (!empty($licdata['type'])) {
                $licserial = sprintf(__('%1$s (%2$s)'), $licserial, $licdata['type']);
            }

            if ($display) {
                echo "<span class='b'>" . htmlescape($licdata['name']) . "</span> - " . htmlescape($licserial);

                $link_item = Toolbox::getItemTypeFormURL('SoftwareLicense');
                $link      = $link_item . "?id=" . $licdata['id'];
                $comment   = "<table><tr><td>" . __s('Name') . "</td><td>" . htmlescape($licdata['name']) . "</td></tr>"
                         . "<tr><td>" . __s('Serial number') . "</td><td>" . htmlescape($licdata['serial']) . "</td></tr>"
                         . "<tr><td>" . __s('Comments') . '</td><td>' . htmlescape($licdata['comment']) . "</td></tr>"
                         . "</table>";

                Html::showToolTip($comment, ['link' => $link]);
                echo "<br>";
            }
        }

        if ($display) {
            if (!count($licids)) {
                echo "&nbsp;";
            }

            echo "</td>";

            echo "<td>" . htmlescape(Html::convDate($data['dateinstall'])) . "</td>";
            echo "<td>" . htmlescape($data['arch']) . "</td>";

            if (isset($data['is_dynamic'])) {
                echo "<td>" . htmlescape(Dropdown::getYesNo($data['is_dynamic'])) . "</td>";
            }

            echo "<td>" . htmlescape(Dropdown::getDropdownName("glpi_softwarecategories", $data['softwarecategories_id']));
            echo "</td>";
            echo "<td>" . htmlescape(Dropdown::getYesNo($data["softvalid"])) . "</td>";
            echo "<td></td>"; // empty td for filter column
            echo "</tr>\n";
        }

        return $licids;
    }

    /**
     * Display a software for a License (not installed)
     *
     * @param array   $data         data used to display
     * @param integer $withtemplate template case of the view process
     * @param boolean $canedit      user can edit software ?
     *
     * @return void
     */
    private static function displaySoftwareByLicense($data, $withtemplate, $canedit)
    {

        $ID = $data['linkid'];

        $link = SoftwareLicense::getFormURLWithID($data['id']);

        echo "<tr class='tab_bg_1'>";
        if ($canedit) {
            echo "<td>";
            if (empty($withtemplate) || ($withtemplate != 2)) {
                Html::showMassiveActionCheckBox('Item_SoftwareLicense', $ID);
            }
            echo "</td>";
        }

        echo "<td>";
        echo "<a href='" . htmlescape(Software::getFormURLWithID($data['softwares_id'])) . "'>";
        echo htmlescape(
            $_SESSION["glpiis_ids_visible"]
            ? sprintf(__('%1$s (%2$s)'), $data["softname"], $data['softwares_id'])
            : $data["softname"]
        );
        echo "</a></td>";
        echo "<td>" . htmlescape($data["state"]) . "</td>";

        echo "<td>" . htmlescape($data["version"]);

        $serial = $data["serial"];

        if ($data["softwarelicensetypes_id"]) {
            $serial = sprintf(
                __('%1$s (%2$s)'),
                $serial,
                Dropdown::getDropdownName(
                    "glpi_softwarelicensetypes",
                    $data["softwarelicensetypes_id"]
                )
            );
        }
        echo "</td><td>" . htmlescape($data["name"]);
        if (!empty($serial)) {
            echo " - " . htmlescape($serial);
        }

        $comment = "<table><tr><td>" . __s('Name') . "</td>" . "<td>" . htmlescape($data['name']) . "</td></tr>"
                 . "<tr><td>" . __s('Serial number') . "</td><td>" . htmlescape($data['serial']) . "</td></tr>"
                 . "<tr><td>" . __s('Comments') . "</td><td>" . htmlescape($data['comment']) . "</td></tr></table>";

        Html::showToolTip($comment, ['link' => $link]);
        echo "</td></tr>\n";
    }

    /**
     * Update version installed on a item
     *
     * @param integer $instID              ID of the installed software link
     * @param integer $softwareversions_id ID of the new version
     * @param boolean $dohistory           Do history ? (default 1)
     *
     * @return void
     **/
    public function upgrade($instID, $softwareversions_id, $dohistory = true)
    {
        if ($this->getFromDB($instID)) {
            $items_id = $this->fields['items_id'];
            $itemtype = $this->fields['itemtype'];
            $this->delete(['id' => $instID]);
            $this->add([
                'itemtype'              => $itemtype,
                'items_id'              => $items_id,
                'softwareversions_id'   => $softwareversions_id,
            ]);
        }
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        $nb = 0;
        switch ($item::class) {
            case Software::class:
                /** @var Software $item */
                if (!$withtemplate) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForSoftware($item->getID());
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                }
                break;

            case SoftwareVersion::class:
                if (!$withtemplate) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForVersion($item->getID());
                    }
                    return [1 => __('Summary'),
                        2 => self::createTabEntry(
                            self::getTypeName(Session::getPluralNumber()),
                            $nb,
                            $item::class
                        ),
                    ];
                }
                break;

            default:
                // Installation allowed for template
                if (Software::canView()) {
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Software::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                }
                break;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        if ($item instanceof Software) {
            self::showForSoftware($item);
        } elseif ($item instanceof SoftwareVersion) {
            switch ($tabnum) {
                case 1:
                    self::showForVersionByEntity($item);
                    break;

                case 2:
                    self::showForVersion($item);
                    break;
            }
        } else {
            self::showForItem($item, $withtemplate);
        }
        return true;
    }

    protected static function getListForItemParams(CommonDBTM $item, $noent = false)
    {
        $table = self::getTable(self::class);

        $params = parent::getListForItemParams($item);
        unset($params['SELECT'], $params['ORDER']);
        $params['WHERE'] = [
            $table . '.items_id'   => $item->getID(),
            $table . '.itemtype'   => $item::getType(),
            $table . '.is_deleted' => 0,
        ];
        if ($noent === false) {
            $params['WHERE'] += getEntitiesRestrictCriteria($table, '', '', 'auto');
        }
        return $params;
    }

    public static function countForItem(CommonDBTM $item)
    {
        global $DB;

        $params = self::getListForItemParams($item);
        unset($params['SELECT'], $params['ORDER']);
        $params['COUNT'] = 'cpt';
        $iterator = $DB->request($params);
        return $iterator->current()['cpt'];
    }
}
