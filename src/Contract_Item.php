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

use Glpi\Application\View\TemplateRenderer;

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


    public function canCreateItem(): bool
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
                static::getTable(),
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
                    $table = getTableForItemType($values['itemtype']);
                    $value = (int) $values[$field];
                    $name = Dropdown::getDropdownName($table, $value);
                    if (isset($options['comments']) && $options['comments']) {
                        $comments = Dropdown::getDropdownComments($table, $value);
                        return sprintf(
                            __s('%1$s %2$s'),
                            htmlescape($name),
                            Html::showToolTip($comments, ['display' => false])
                        );
                    }
                    return htmlescape($name);
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
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
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
     * @param integer $contract_id   contract ID
     * @param integer $entities_id   entity ID
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
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return '';
        }

        // Can exists on template
        if (Contract::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Contract::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = self::countForMainItem($item);
                        $nb += countElementsInTable(Contract_User::getTable(), ['contracts_id' => $item->fields['id']]);
                    }
                    return self::createTabEntry(_n('Affected item', 'Affected items', Session::getPluralNumber()), $nb, $item::class, 'ti ti-package');
                default:
                    if (in_array($item::class, $CFG_GLPI["contract_types"], true)) {
                        $nb = self::countForItem($item);
                    }
                    return self::createTabEntry(Contract::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch ($item::class) {
            case Contract::class:
                self::showForContract($item, $withtemplate);
                break;
            default:
                if (in_array($item::class, $CFG_GLPI["contract_types"], true)) {
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

        $contracts = [];
        $used      = [];
        foreach ($iterator as $data) {
            $contracts[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ((int) $withtemplate !== 2)) {
            $twig_params = [
                'item' => $item,
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                {% import 'components/form/basic_inputs_macros.html.twig' as inputs %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Contract_Item'|itemtype_form_path }}">
                        <input type="hidden" name="itemtype" value="{{ get_class(item) }}">
                        <input type="hidden" name="items_id" value="{{ item.getID() }}">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <div class="d-flex">
                            <div class="col-auto">
                            {{ fields.dropdownField('Contract', 'contracts_id', 0, null, {
                                add_field_class: 'd-inline',
                                no_label: true,
                                entity: item.fields['entities_id'],
                                used: used,
                                expired: false
                            }) }}
                            </div>
                            <div class="col-auto">
                            {{ inputs.submit('add', _x('button', 'Add'), 1, {'class': 'btn btn-primary ms-1', 'icon': 'ti ti-link'}) }}
                           </div>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $type_cache = [];
        foreach ($contracts as $data) {
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'num'      => $data['num'],
            ];
            $con         = new Contract();
            $con->getFromResultSet($data);
            $entry['name'] = $con->getLink();
            if (!isset($entity_cache[$con->fields["entities_id"]])) {
                $entity_cache[$con->fields["entities_id"]] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $con->fields["entities_id"]
                );
            }
            $entry['entity'] = $entity_cache[$con->fields["entities_id"]];

            if (!isset($type_cache[$con->fields["contracttypes_id"]])) {
                $type_cache[$con->fields["contracttypes_id"]] = Dropdown::getDropdownName(
                    "glpi_contracttypes",
                    $con->fields["contracttypes_id"]
                );
            }
            $entry['type'] = $type_cache[$con->fields["contracttypes_id"]];
            $entry['supplier'] = $con->getSuppliersNames();
            $entry['begin_date'] = $con->fields["begin_date"];

            $duration = sprintf(
                __('%1$s %2$s'),
                $con->fields["duration"],
                _n('month', 'months', $con->fields["duration"])
            );

            if (!empty($con->fields["begin_date"])) {
                $duration .= ' -> ' . Infocom::getWarrantyExpir(
                    $con->fields["begin_date"],
                    $con->fields["duration"],
                    0,
                    true
                );
            }
            $entry['duration'] = $duration;
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'type' => _n('Type', 'Types', 1),
                'supplier' => Supplier::getTypeName(1),
                'begin_date' => __('Start date'),
                'duration' => __('Initial contract period'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'supplier' => 'raw_html',
                'begin_date' => 'date',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && (int) $withtemplate !== 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
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
        global $DB, $CFG_GLPI;

        $instID = $contract->fields['id'];

        if (!$contract->can($instID, READ)) {
            return false;
        }
        $canedit = $contract->can($instID, UPDATE);
        $rand    = mt_rand();

        $types_iterator = self::getDistinctTypes($instID);

        $data    = [];
        $totalnb = 0;
        $used    = [];
        foreach ($types_iterator as $type_row) {
            $itemtype = $type_row['itemtype'];
            if (!is_a($itemtype, CommonDBTM::class, true)) {
                continue;
            }

            $item = new $itemtype();

            if ($item::canView()) {
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

                if (is_a($itemtype, Item_Devices::class, true)) {
                    $itemtype_2 = $itemtype::$itemtype_2;
                    $itemtable_2 = $itemtype_2::getTable();
                    $namefield = 'name_device';
                    $params['SELECT'][] = $itemtable_2 . '.designation AS ' . $namefield;
                } else {
                    $namefield = $item::getNameField();
                    $namefield = "$itemtable.$namefield";
                }

                $params['LEFT JOIN'][$itemtable] = [
                    'FKEY' => [
                        $itemtable        => 'id',
                        self::getTable()  => 'items_id',
                    ],
                ];
                if ($itemtype !== Entity::class) {
                    $params['LEFT JOIN']['glpi_entities'] = [
                        'FKEY' => [
                            $itemtable        => 'entities_id',
                            'glpi_entities'   => 'id',
                        ],
                    ];
                }

                if (is_a($itemtype, Item_Devices::class, true)) {
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

                $data[$itemtype] = [];
                foreach ($iterator as $objdata) {
                    $data[$itemtype][$objdata['id']] = $objdata;
                    $used[$itemtype][$objdata['id']] = $objdata['id'];
                    $totalnb++;
                }
            }
        }

        // Add contract users
        $contract_users_table = Contract_User::getTable();
        $users_table = User::getTable();
        $user_params = [
            'SELECT' => [
                "$users_table.*",
                "$contract_users_table.id AS linkid",
            ],
            'FROM'   => $contract_users_table,
            'LEFT JOIN' => [
                $users_table => [
                    'FKEY' => [
                        $contract_users_table => 'users_id',
                        $users_table          => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                "$contract_users_table.contracts_id" => $instID,
            ],
            'ORDER' => "$users_table.name",
        ];

        $user_iterator = $DB->request($user_params);

        $data[User::class] = [];
        foreach ($user_iterator as $userdata) {
            $data[User::class][$userdata['id']] = $userdata;
            $used[User::class][$userdata['id']] = $userdata['id'];
            $totalnb++;
        }

        if (
            $canedit
            && (((int) $contract->fields['max_links_allowed'] === 0)
              || ($contract->fields['max_links_allowed'] > $totalnb))
            && ((int) $withtemplate !== 2)
        ) {
            $twig_params = [
                'contract' => $contract,
                'contract_types' => array_merge($CFG_GLPI["contract_types"], [User::class]),
                'entity_restrict' => $contract->fields['is_recursive']
                    ? getSonsOf('glpi_entities', $contract->fields['entities_id'])
                    : $contract->fields['entities_id'],
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Contract_Item'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="contracts_id" value="{{ contract.getID() }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownItemsFromItemtypes('', null, {
                                itemtypes: contract_types,
                                entity_restrict: entity_restrict,
                                checkright: true,
                                used: used
                            }) }}
                            {% set btn %}
                                <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                            {% endset %}
                            {{ fields.htmlField('', btn, null) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $state_cache = [];
        /** @var array<class-string<CommonDBTM>, array> $data */
        foreach ($data as $itemtype => $datas) {
            foreach ($datas as $objdata) {
                $entry = [
                    'itemtype' => $itemtype === User::class ? Contract_User::class : self::class,
                    'id'       => $objdata['linkid'],
                    'row_class' => isset($objdata['is_deleted']) && $objdata['is_deleted'] ? 'table-danger' : '',
                    'type'     => $itemtype::getTypeName(1),
                ];
                $item = getItemForItemtype($itemtype);
                $item->getFromResultSet($objdata);
                $entry['name'] = $item->getLink();

                if (isset($objdata['entity'])) {
                    if (!isset($entity_cache[$objdata['entity']])) {
                        $entity_cache[$objdata['entity']] = Dropdown::getDropdownName(
                            "glpi_entities",
                            $objdata['entity']
                        );
                    }
                    $entry['entity'] = $entity_cache[$objdata['entity']];
                } else {
                    $entry['entity'] = '-';
                }
                $entry['serial'] = $objdata['serial'] ?? '-';
                $entry['otherserial'] = $objdata['otherserial'] ?? '-';

                if (isset($objdata['states_id'])) {
                    if (!isset($state_cache[$objdata['states_id']])) {
                        $state_cache[$objdata['states_id']] = Dropdown::getDropdownName(
                            "glpi_states",
                            $objdata['states_id']
                        );
                    }
                    $entry['status'] = $state_cache[$objdata['states_id']];
                } else {
                    $entry['status'] = '-';
                }
                $entries[] = $entry;
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'serial' => __('Serial number'),
                'otherserial' => __('Inventory number'),
                'status' => __('Status'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && (int) $withtemplate !== 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = $CFG_GLPI['contract_types'];

        return $specificities;
    }
}
