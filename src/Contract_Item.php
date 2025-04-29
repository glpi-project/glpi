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

/**
 * Contract_Item Class
 *
 * Relation between Contracts and Items
 **/
class Contract_Item extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contract';
    public static $items_id_1 = 'contracts_id';

    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function canCreateItem()
    {

        // Try to load the contract
        $contract = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
        if ($contract === false) {
            return false;
        }

        // Don't create a Contract_Item on contract that is alreay max used
        // Was previously done (until 0.83.*) by Contract_Item::can()
        if (
            ($contract->fields['max_links_allowed'] > 0)
            && (countElementsInTable(
                $this->getTable(),
                ['contracts_id' => $this->input['contracts_id']]
            )
                >= $contract->fields['max_links_allowed'])
        ) {
            return false;
        }

        return parent::canCreateItem();
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Link Contract/Item', 'Links Contract/Item', $nb);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype'])) {
                    if (isset($options['comments']) && $options['comments']) {
                        $tmp = Dropdown::getDropdownName(
                            getTableForItemType($values['itemtype']),
                            $values[$field],
                            1
                        );
                        return sprintf(
                            __('%1$s %2$s'),
                            $tmp['name'],
                            Html::showToolTip($tmp['comment'], ['display' => false])
                        );
                    }
                    return Dropdown::getDropdownName(
                        getTableForItemType($values['itemtype']),
                        $values[$field]
                    );
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype'], $options);
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'contract_types',
        ];

        return $tab;
    }


    /**
     * @since 0.84
     *
     * @param $contract_id   contract ID
     * @param $entities_id   entity ID
     *
     * @return array of items linked to contracts
     **/
    public static function getItemsForContract($contract_id, $entities_id)
    {

        $items = [];

        $types_iterator = self::getDistinctTypes($contract_id);

        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!getItemForItemtype($itemtype)) {
                continue;
            }

            $iterator = self::getTypeItems($contract_id, $itemtype);
            foreach ($iterator as $objdata) {
                $items[$itemtype][$objdata['id']] = $objdata;
            }
        }

        return $items;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Can exists on template
        if (Contract::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case 'Contract':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                    }
                    return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

                default:
                    if (
                        $_SESSION['glpishow_count_on_tabs']
                        && in_array($item->getType(), $CFG_GLPI["contract_types"])
                    ) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Contract::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        switch ($item->getType()) {
            case 'Contract':
                self::showForContract($item, $withtemplate);
                break;
            default:
                if (in_array($item->getType(), $CFG_GLPI["contract_types"])) {
                    self::showForItem($item, $withtemplate);
                }
                break;
        }
        return true;
    }


    /**
     * Print an HTML array of contract associated to an object
     *
     * @since 0.84
     *
     * @param CommonDBTM $item         CommonDBTM object wanted
     * @param integer    $withtemplate
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {

        $itemtype = $item->getType();
        $ID       = $item->fields['id'];

        if (
            !Contract::canView()
            || !$item->can($ID, READ)
        ) {
            return;
        }

        $canedit = $item->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($item);
        $number = count($iterator);

        $contracts = [];
        $used      = [];
        foreach ($iterator as $data) {
            $contracts[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ($withtemplate != 2)) {
            echo "<div class='firstbloc'>";
            echo "<form name='contractitem_form$rand' id='contractitem_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='$itemtype'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add a contract') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td>";
            Contract::dropdown(['entity'  => $item->getEntityID(),
                'used'    => $used,
                'expired' => false,
            ]);

            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced table-responsive'>";
        if ($withtemplate != 2) {
            if ($canedit && $number) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                    'container'     => 'mass' . __CLASS__ . $rand,
                ];
                Html::showMassiveActions($massiveactionparams);
            }
        }
        echo "<table class='tab_cadre_fixehov'>";

        $header_begin = "<tr>";
        $header_top = '';
        $header_bottom = '';
        $header_end = '';
        if ($canedit && $number && ($withtemplate != 2)) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }

        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . _x('phone', 'Number') . "</th>";
        $header_end .= "<th>" . ContractType::getTypeName(1) . "</th>";
        $header_end .= "<th>" . Supplier::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Start date') . "</th>";
        $header_end .= "<th>" . __('Initial contract period') . "</th>";
        $header_end .= "</tr>";

        if ($number > 0) {
            echo $header_begin . $header_top . $header_end;
            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                //         %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $item->getTypeName(1),
                    $item->getName()
                )
            );
            foreach ($contracts as $data) {
                $cID         = $data["id"];
                Session::addToNavigateListItems(__CLASS__, $cID);
                $contracts[] = $cID;
                $assocID     = $data["linkid"];
                $con         = new Contract();
                $con->getFromResultSet($data);
                echo "<tr class='tab_bg_1" . ($con->fields["is_deleted"] ? "_2" : "") . "'>";
                if ($canedit && ($withtemplate != 2)) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $assocID);
                    echo "</td>";
                }
                echo "<td class='center b'>";
                $name = $con->fields["name"];
                if (
                    $_SESSION["glpiis_ids_visible"]
                    || empty($con->fields["name"])
                ) {
                    $name = sprintf(__('%1$s (%2$s)'), $name, $con->fields["id"]);
                }
                echo "<a href='" . Contract::getFormURLWithID($cID) . "'>" . $name;
                echo "</a></td>";
                echo "<td class='center'>";
                echo Dropdown::getDropdownName("glpi_entities", $con->fields["entities_id"]) . "</td>";
                echo "<td class='center'>" . $con->fields["num"] . "</td>";
                echo "<td class='center'>";
                echo Dropdown::getDropdownName("glpi_contracttypes", $con->fields["contracttypes_id"]) .
                "</td>";
                echo "<td class='center'>" . $con->getSuppliersNames() . "</td>";
                echo "<td class='center'>" . Html::convDate($con->fields["begin_date"]) . "</td>";

                echo "<td class='center'>" . sprintf(
                    __('%1$s %2$s'),
                    $con->fields["duration"],
                    _n('month', 'months', $con->fields["duration"])
                );
                if (
                    ($con->fields["begin_date"] != '')
                     && !empty($con->fields["begin_date"])
                ) {
                    echo " -> " . Infocom::getWarrantyExpir(
                        $con->fields["begin_date"],
                        $con->fields["duration"],
                        0,
                        true
                    );
                }
                echo "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr></table>";
        }

        echo "</table>";
        if ($canedit && $number && ($withtemplate != 2)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    /**
     * Print the HTML array for Items linked to current contract
     *
     * @since 0.84
     *
     * @param Contract $contract     Contract object
     * @param integer  $withtemplate (default 0)
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public static function showForContract(Contract $contract, $withtemplate = 0)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $instID = $contract->fields['id'];

        if (!$contract->can($instID, READ)) {
            return false;
        }
        $canedit = $contract->can($instID, UPDATE);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);
        $number = count($types_iterator);

        $data    = [];
        $totalnb = 0;
        $used    = [];
        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            if ($item->canView()) {
                $itemtable = getTableForItemType($itemtype);
                $itemtype_2 = null;
                $itemtable_2 = null;

                $params = [
                    'SELECT' => [
                        $itemtable . '.*',
                        self::getTable() . '.id AS linkid',
                        'glpi_entities.id AS entity',
                    ],
                    'FROM'   => 'glpi_contracts_items',
                    'WHERE'  => [
                        'glpi_contracts_items.itemtype'     => $itemtype,
                        'glpi_contracts_items.contracts_id' => $instID,
                    ],
                ];

                if ($item instanceof Item_Devices) {
                    $itemtype_2 = $itemtype::$itemtype_2;
                    $itemtable_2 = $itemtype_2::getTable();
                    $namefield = 'name_device';
                    $params['SELECT'][] = $itemtable_2 . '.designation AS ' . $namefield;
                } else {
                    $namefield = $item->getNameField();
                    $namefield = "$itemtable.$namefield";
                }

                $params['LEFT JOIN'][$itemtable] = [
                    'FKEY' => [
                        $itemtable        => 'id',
                        self::getTable()  => 'items_id',
                    ],
                ];
                if ($itemtype != 'Entity') {
                    $params['LEFT JOIN']['glpi_entities'] = [
                        'FKEY' => [
                            $itemtable        => 'entities_id',
                            'glpi_entities'   => 'id',
                        ],
                    ];
                }

                if ($item instanceof Item_Devices) {
                    $id_2 = $itemtype_2::getIndexName();
                    $fid_2 = $itemtype::$items_id_2;

                    $params['LEFT JOIN'][$itemtable_2] = [
                        'FKEY' => [
                            $itemtable     => $fid_2,
                            $itemtable_2   => $id_2,
                        ],
                    ];
                }

                if ($item->maybeTemplate()) {
                    $params['WHERE'][] = [$itemtable . '.is_template' => 0];
                }
                $params['WHERE'] += getEntitiesRestrictCriteria($itemtable, '', '', $item->maybeRecursive());
                $params['ORDER'] = "glpi_entities.completename, $namefield";

                $iterator = $DB->request($params);
                $nb = count($iterator);

                if ($nb > $_SESSION['glpilist_limit']) {
                    $opt = ['order'      => 'ASC',
                        'is_deleted' => 0,
                        'reset'      => 'reset',
                        'start'      => 0,
                        'sort'       => 80,
                        'criteria'   => [0 => ['value'      => '$$$$' . $instID,
                            'searchtype' => 'contains',
                            'field'      => 29,
                        ],
                        ],
                    ];

                    $url  = $item::getSearchURL();
                    $url .= (strpos($url, '?') ? '&' : '?');
                    $url .= Toolbox::append_params($opt);
                    $link = "<a href='$url'>" . __('Device list') . "</a>";

                    $data[$itemtype] = ['longlist' => true,
                        'name'     => sprintf(
                            __('%1$s: %2$s'),
                            $item->getTypeName($nb),
                            $nb
                        ),
                        'link'     => $link,
                    ];
                } elseif ($nb > 0) {
                    $data[$itemtype] = [];
                    foreach ($iterator as $objdata) {
                        $data[$itemtype][$objdata['id']] = $objdata;
                        $used[$itemtype][$objdata['id']] = $objdata['id'];
                    }
                }
                $totalnb += $nb;
            }
        }

        if (
            $canedit
            && (($contract->fields['max_links_allowed'] == 0)
              || ($contract->fields['max_links_allowed'] > $totalnb))
            && ($withtemplate != 2)
        ) {
            echo "<div class='firstbloc'>";
            echo "<form name='contract_form$rand' id='contract_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='right'>";
            Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                       => $CFG_GLPI["contract_types"],
                'entity_restrict'
                                                       => ($contract->fields['is_recursive']
                                                           ? getSonsOf(
                                                               'glpi_entities',
                                                               $contract->fields['entities_id']
                                                           )
                                                           : $contract->fields['entities_id']),
                'checkright'
                                                       => true,
                'used'
                                                       => $used,
            ]);
            echo "</td><td class='center'>";
            echo "<input type='submit' name='add' value=\"" . _sx('button', 'Add') . "\" class='btn btn-primary'>";
            echo "<input type='hidden' name='contracts_id' value='$instID'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }

        echo "<div class='spaced'>";
        if ($canedit && $totalnb) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
        }
        echo "<table class='tab_cadre_fixehov'>";
        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';

        if ($canedit && $totalnb) {
            $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            $header_bottom .= "</th>";
        }
        $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
        $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
        $header_end .= "<th>" . __('Name') . "</th>";
        $header_end .= "<th>" . __('Serial number') . "</th>";
        $header_end .= "<th>" . __('Inventory number') . "</th>";
        $header_end .= "<th>" . __('Status') . "</th>";
        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        $totalnb = 0;
        foreach ($data as $itemtype => $datas) {
            if (isset($datas['longlist'])) {
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>&nbsp;</td>";
                }
                echo "<td class='center'>" . $datas['name'] . "</td>";
                echo "<td class='center' colspan='2'>" . $datas['link'] . "</td>";
                echo "<td class='center'>-</td><td class='center'>-</td></tr>";
            } else {
                $prem = true;
                $nb   = count($datas);
                foreach ($datas as $objdata) {
                    $item = new $itemtype();
                    if ($item instanceof Item_Devices) {
                        $name = $objdata["name_device"];
                    } else {
                        $name = $objdata["name"];
                    }
                    if (
                        $_SESSION["glpiis_ids_visible"]
                        || empty($data["name"])
                    ) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $objdata["id"]);
                    }

                    if ($item->can($objdata['id'], READ)) {
                        $link     = $item::getFormURLWithID($objdata['id']);
                        $namelink = "<a href=\"" . $link . "\">" . $name . "</a>";
                    } else {
                        $namelink = $name;
                    }

                    echo "<tr class='tab_bg_1'>";
                    if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $objdata["linkid"]);
                        echo "</td>";
                    }
                    if ($prem) {
                        $typename = $item->getTypeName($nb);
                        echo "<td class='center top' rowspan='$nb'>" .
                         ($nb  > 1 ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename) . "</td>";
                        $prem = false;
                    }
                    echo "<td class='center'>";
                    echo Dropdown::getDropdownName("glpi_entities", $objdata['entity']) . "</td>";
                    echo "<td class='center" .
                      (isset($objdata['is_deleted']) && $objdata['is_deleted'] ? " tab_bg_2_2'" : "'");
                    echo ">" . $namelink . "</td>";
                    echo"<td class='center'>" .
                      (isset($objdata["serial"]) ? "" . $objdata["serial"] . "" : "-") . "</td>";
                    echo "<td class='center'>" .
                      (isset($objdata["otherserial"]) ? "" . $objdata["otherserial"] . "" : "-") . "</td>";
                    echo "<td class='center'>";
                    if (isset($objdata["states_id"])) {
                        echo Dropdown::getDropdownName("glpi_states", $objdata['states_id']);
                    } else {
                        echo '&nbsp;';
                    }
                    echo "</td></tr>";
                }
            }
        }
        if ($number) {
            echo $header_begin . $header_bottom . $header_end;
        }

        echo "</table>";
        if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }


    public static function getRelationMassiveActionsSpecificities()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = $CFG_GLPI['contract_types'];

        return $specificities;
    }
}
