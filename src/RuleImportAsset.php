<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

class RuleImportAsset extends Rule
{
    const RULE_ACTION_LINK_OR_IMPORT    = 0;
    const RULE_ACTION_LINK_OR_NO_IMPORT = 1;
    const RULE_ACTION_DENIED            = 2;

    const PATTERN_ENTITY_RESTRICT       = 202;
    const PATTERN_NETWORK_PORT_RESTRICT = 203;
    const PATTERN_ONLY_CRITERIA_RULE    = 204;

    const LINK_RESULT_DENIED            = 0;
    const LINK_RESULT_CREATE            = 1;
    const LINK_RESULT_LINK              = 2;

    public $restrict_matching = Rule::AND_MATCHING;
    public $can_sort          = true;

    public static $rightname         = 'rule_import';

    /** @var bool */
    private $restrict_entity = false;
    /** @var integer */
    private $found_criteria = 0;
    /** @var array */
    private $complex_criteria = [];
    /** @var boolean */
    private $only_these_criteria = false;
    /** @var boolean */
    private $link_criteria_port = false;


    public function getTitle()
    {
        $col = new RuleImportAssetCollection();
        return $col->getTitle();
    }


    public function maxActionsCount()
    {
        return 1;
    }


    public function getCriterias()
    {

        static $criteria = [];

        if (count($criteria)) {
            return $criteria;
        }

        $criteria = [
            'entities_id' => [
                'table'     => 'glpi_entities',
                'field'     => 'entities_id',
                'name'      => __('Target entity for the asset'),
                'linkfield' => 'entities_id',
                'type'      => 'dropdown',
                'is_global'       => false,
                'allow_condition' => [
                    Rule::PATTERN_IS,
                    Rule::PATTERN_IS_NOT,
                    Rule::PATTERN_CONTAIN,
                    Rule::PATTERN_NOT_CONTAIN,
                    Rule::PATTERN_BEGIN,
                    Rule::PATTERN_END,
                    Rule::REGEX_MATCH,
                    Rule::REGEX_NOT_MATCH
                ],
            ],
            'states_id'  => [
                'table'     => 'glpi_states',
                'field'     => 'name',
                'name'      => __('Having the status'),
                'linkfield' => 'state',
                'type'      => 'dropdown',
            //Means that this criterion can only be used in a global search query
                'is_global' => true,
                'allow_condition' => [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT]
            ],
            'model' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), _n('Model', 'Models', 1)),
            ],
            'manufacturer' => [ // Manufacturer as Text to allow text criteria (contains, regex, ...)
                'name'            => Manufacturer::getTypeName(1)
            ],
            'mac' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('MAC')),
            ],
            'ip' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('IP')),
            ],
            'ifdescr' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('Port description'))
            ],
            'ifnumber' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), _n('Port number', 'Ports number', 1)),
            ],
            'serial' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Serial number')),
            ],
            'uuid' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('UUID')),
            ],
            'device_id' => [
                'name'            => sprintf('%s > %s', Agent::getTypeName(1), __('Device_id')),
            ],
            'mskey' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Serial of the operating system')),
            ],
            'name' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Name')),
            ],
            'tag' => [
                'name'            => sprintf('%s > %s', Agent::getTypeName(1), __('Inventory tag')),
            ],
            'osname' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), OperatingSystem::getTypeName(1)),
            ],
            'oscomment' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), OperatingSystem::getTypeName(1), __('Comments'))
            ],
            'itemtype' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), __('Item type')),
                'type'            => 'dropdown_inventory_itemtype',
                'is_global'       => false,
                'allow_condition' => [
                    Rule::PATTERN_IS,
                    Rule::PATTERN_IS_NOT,
                    Rule::PATTERN_EXISTS,
                    Rule::PATTERN_DOES_NOT_EXISTS,
                ],
            ],
            'domains_id' => [
                'table'           => 'glpi_domains',
                'field'           => 'name',
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), Domain::getTypeName(1)),
                'linkfield'       => 'domain',
                'type'            => 'dropdown',
                'is_global'       => false,
            ],

            'linked_item' => [
                'name'            => __('Linked asset', 'Linked assets', 1),
                'type'            => 'yesno',
                'allow_condition' => [Rule::PATTERN_FIND]
            ],

            'entityrestrict' => [
                'name'            => sprintf('%s > %s', __('General'), __('Restrict search in defined entity')),
                'allow_condition' => [self::PATTERN_ENTITY_RESTRICT],
            ],
            'link_criteria_port' => [
                'name'            => sprintf('%s > %s', __('General'), __('Restrict criteria to same network port')),
                'allow_condition' => [self::PATTERN_NETWORK_PORT_RESTRICT],
                'is_global'       => true
            ],
            'only_these_criteria' => [
                'name'            => sprintf('%s > %s', __('General'), __('Only criteria of this rule in data')),
                'allow_condition' => [self::PATTERN_ONLY_CRITERIA_RULE],
                'is_global'       => true
            ],
            'partial' => [
                'name'   => __('Is partial'),
                'type'   => 'yesno',
                'allow_condition' => [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT]
            ]
        ];

        return $criteria;
    }


    public function getActions()
    {
        $actions = [
            '_inventory'   => [
                'name'   => __('Inventory link'),
                'type'   => 'inventory_type'
            ],
            '_ignore_import'  => [
                'name'   => __('Refuse import'),
                'type'   => 'yesonly'
            ]
        ];
        return $actions;
    }


    public static function getRuleActionValues()
    {
        return [
            self::RULE_ACTION_LINK_OR_IMPORT    => __('Link if possible'),
            self::RULE_ACTION_LINK_OR_NO_IMPORT => __('Link if possible, otherwise imports declined'),
            self::RULE_ACTION_DENIED            => __('Import denied (no log)')
        ];
    }


    public function displayAdditionRuleActionValue($value)
    {

        $values = self::getRuleActionValues();
        if (isset($values[$value])) {
            return $values[$value];
        }
        return '';
    }


    public function manageSpecificCriteriaValues($criteria, $name, $value)
    {

        switch ($criteria['type']) {
            case "state":
                $link_array = [
                    "0" => __('No'),
                    "1" => __('Yes if equal'),
                    "2" => __('Yes if empty')
                ];

                Dropdown::showFromArray($name, $link_array, ['value' => $value]);
        }
        return false;
    }


    /**
     * Add more criteria
     *
     * @param string $criterion
     * @return array
     */
    public static function addMoreCriteria($criterion = '')
    {
        switch ($criterion) {
            case 'entityrestrict':
                return [self::PATTERN_ENTITY_RESTRICT => __('Yes')];
            case 'link_criteria_port':
                return [self::PATTERN_NETWORK_PORT_RESTRICT => __('Yes')];
            case 'only_these_criteria':
                return [self::PATTERN_ONLY_CRITERIA_RULE => __('Yes')];
            default:
                return [
                    self::PATTERN_FIND      => __('is already present'),
                    self::PATTERN_IS_EMPTY  => __('is empty')
                ];
        }
    }


    public function getAdditionalCriteriaDisplayPattern($ID, $condition, $pattern)
    {

        if (
            $condition == self::PATTERN_IS_EMPTY
            || $condition == self::PATTERN_ENTITY_RESTRICT
            || $condition == self::PATTERN_NETWORK_PORT_RESTRICT
            || $condition == self::PATTERN_ONLY_CRITERIA_RULE
        ) {
            return __('Yes');
        }
        if ($condition == self::PATTERN_IS || $condition == self::PATTERN_IS_NOT) {
            $crit = $this->getCriteria($ID);
            if (
                isset($crit['type'])
                 && $crit['type'] == 'dropdown_inventory_itemtype'
            ) {
                $array = $this->getItemTypesForRules();
                return $array[$pattern];
            }
        }
        return false;
    }


    public function displayAdditionalRuleCondition($condition, $criteria, $name, $value, $test = false)
    {

        if ($test) {
            return false;
        }

        switch ($condition) {
            case self::PATTERN_ENTITY_RESTRICT:
            case self::PATTERN_NETWORK_PORT_RESTRICT:
                return true;

            case Rule::PATTERN_FIND:
            case Rule::PATTERN_IS_EMPTY:
                Dropdown::showYesNo($name, 0, 0);
                return true;

            case Rule::PATTERN_EXISTS:
            case Rule::PATTERN_DOES_NOT_EXISTS:
                Dropdown::showYesNo($name, 1, 0);
                return true;
        }

        return false;
    }


    public function displayAdditionalRuleAction(array $action, $value = '')
    {

        switch ($action['type']) {
            case 'inventory_type':
            case 'fusion_type':
                Dropdown::showFromArray('value', self::getRuleActionValues());
                return true;
        }
        return false;
    }


    public function getCriteriaByID($ID)
    {

        $criteria = [];
        foreach ($this->criterias as $criterion) {
            if ($ID == $criterion->fields['criteria']) {
                $criteria[] = $criterion;
            }
        }
        return $criteria;
    }

    /**
     * Pre compute criteria to detect rules specificities
     *
     * @param array $input Input
     *
     * @return boolean
     */
    public function preComputeCriteria(array $input): bool
    {
        $global_criteria   = $this->getGlobalCriteria();

        foreach ($global_criteria as $criterion) {
            $criteria = $this->getCriteriaByID($criterion);
            if (!empty($criteria)) {
                foreach ($criteria as $crit) {
                    if (!isset($input[$criterion]) || ($input[$criterion] == '' && $crit->fields['condition'] != self::PATTERN_IS_EMPTY)) {
                        $definition_criteria = $this->getCriteria($crit->fields['criteria']);
                        if ($crit->fields["criteria"] == 'link_criteria_port') {
                            $this->link_criteria_port = true;
                        } else if ($crit->fields["criteria"] == 'only_these_criteria') {
                            $this->only_these_criteria = true;
                        } else if (
                            isset($definition_criteria['is_global'])
                             && $definition_criteria['is_global']
                        ) {
                         //If a value is missing, then there's a problem !
                            trigger_error('A value seems missing, criterion was: ' . $criterion, E_USER_WARNING);
                            return false;
                        }
                    } else if (in_array($crit->fields["condition"], [Rule::PATTERN_FIND, Rule::PATTERN_IS_EMPTY])) {
                        $this->complex_criteria[] = $crit;
                        ++$this->found_criteria;
                    } else if ($crit->fields["condition"] == Rule::PATTERN_EXISTS) {
                        if (
                            !isset($input[$crit->fields['criteria']])
                            || empty($input[$crit->fields['criteria']])
                        ) {
                            trigger_error('A value seems missing, criterion was: ' . $criterion, E_USER_WARNING);
                            return false;
                        }
                    } else if ($crit->fields["criteria"] == 'itemtype') {
                        $this->complex_criteria[] = $crit;
                    } else if ($crit->fields["criteria"] == 'entityrestrict') {
                        $this->restrict_entity = true;
                    }
                }
            }
        }

        foreach ($this->getCriteriaByID('states_id') as $crit) {
            $this->complex_criteria[] = $crit;
        }

       // check only_these_criteria
        if ($this->only_these_criteria) {
            $complex_strings = [];
            foreach ($global_criteria as $criterion) {
                $criteria = $this->getCriteriaByID($criterion);
                foreach ($criteria as $crit) {
                    $complex_strings[] = $crit->fields["criteria"];
                }
            }
            foreach ($input as $key => $crit) {
                if (
                    !in_array($key, $complex_strings)
                    && $key != "class"
                    && !is_object($crit)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function findWithGlobalCriteria($input)
    {
        global $DB, $PLUGIN_HOOKS, $CFG_GLPI;

        $this->complex_criteria = [];
        $this->restrict_entity = false;
        $this->only_these_criteria = false;
        $this->link_criteria_port = false;

        if (!$this->preComputeCriteria($input)) {
           //logged in place, just exit
            return false;
        }

       //No complex criteria
        if (empty($this->complex_criteria) || $this->found_criteria == 0) {
            return true;
        }

       // Get all equipment type
        $itemtypeselected = [];
        if (
            isset($input['itemtype'])
            && (is_array($input['itemtype']))
        ) {
            $itemtypeselected = array_merge($itemtypeselected, $input['itemtype']);
        } else if (
            isset($input['itemtype'])
            && (!empty($input['itemtype']))
        ) {
            $itemtypeselected[] = $input['itemtype'];
        } else {
            foreach ($CFG_GLPI["asset_types"] as $itemtype) {
                if (
                    class_exists($itemtype)
                    && $itemtype != 'SoftwareLicense'
                    && $itemtype != 'Certificate'
                ) {
                    $itemtypeselected[] = $itemtype;
                }
            }
            $itemtypeselected[] = "Unmanaged";
            $itemtypeselected[] = "Peripheral";//used for networkinventory
        }

        $found = false;
        foreach ($itemtypeselected as $itemtype) {
            $item = new $itemtype();
            $itemtable = $item->getTable();

           //Build the request to check if the asset exists in GLPI
            $where_entity = $input['entities_id'] ?? [];
            if (!empty($where_entity) && !is_array($where_entity)) {
                $where_entity = [$where_entity];
            }

            $it_criteria = [
                'SELECT' => ["$itemtable.id"],
                'FROM'   => $itemtable, //to fill
                'WHERE'  => [] //to fill
            ];

            if ($this->link_criteria_port) {
                $this->handleLinkCriteriaPort($item, $it_criteria);
            } else {
               // 1 join per criterion
                $this->handleOneJoinPerCriteria($item, $it_criteria);
            }

            $this->handleFieldsCriteria($item, $it_criteria, $input);

            if (isset($PLUGIN_HOOKS['use_rules'])) {
                foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                    if (!Plugin::isPluginActive($plugin)) {
                        continue;
                    }
                    if (is_array($val) && in_array($this->getType(), $val)) {
                        $params = [
                            'where_entity' => $where_entity,
                            'itemtype'     => $itemtype,
                            'input'        => $input,
                            'criteria'     => $this->complex_criteria,
                            'sql_criteria' => $it_criteria,
                        ];
                        $sql_results = Plugin::doOneHook(
                            $plugin,
                            "ruleImportAsset_getSqlRestriction",
                            $params
                        );

                        $it_criteria = array_merge_recursive($it_criteria, $sql_results);
                    }
                }
            }

            $result_glpi = $DB->request($it_criteria);

            if (count($result_glpi)) {
                foreach ($result_glpi as $data) {
                    $this->criterias_results['found_inventories'][$itemtype][] = $data['id'];
                    $this->criterias_results['found_port'] = 0;
                    foreach ($data as $alias => $value) {
                        if (
                            strstr($alias, "portid")
                            && !is_null($value)
                            && is_numeric($value)
                            && $value > 0
                        ) {
                            $this->criterias_results['found_port'] = $value;
                        }
                    }
                }
                $found = true;
            }
        }

        if ($found) {
            return true;
        }

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields['field'] == '_inventory' || $action->fields['field'] == '_fusion') {
                    if ($action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * ?
     *
     * @param CommonDBTM $item         Item instance
     * @param array      &$it_criteria Iterator criteria
     *
     * @return void
     */
    public function handleLinkCriteriaPort(CommonDBTM $item, array &$it_criteria)
    {
        $is_ip          = false;
        $is_networkport = false;
        $itemtable      = $item->getTable();
        $itemtype       = $item->getType();

        foreach ($this->complex_criteria as $criteria) {
            if ($criteria->fields['criteria'] == 'ip') {
                $is_ip = true;
                break;
            } else if ($this->isNetPort($criteria->fields['criteria'])) {
                $is_networkport = true;
            }
        }

        if ($is_ip) {
            $it_criteria['LEFT JOIN']['glpi_networkports'] = [
                'ON'  => [
                    $itemtable           => 'id',
                    'glpi_networkports'  => 'items_id', [
                        'AND' => ['glpi_networkports.itemtype' => $itemtype]
                    ]
                ]
            ];
            $it_criteria['LEFT JOIN']['glpi_networknames'] = [
                'ON'  => [
                    'glpi_networkports'  => 'id',
                    'glpi_networknames'  => 'items_id', [
                        'AND' => ['glpi_networknames.itemtype' => 'NetworkPort']
                    ]
                ]
            ];
            $it_criteria['LEFT JOIN']['glpi_ipaddresses'] = [
                'ON'  => [
                    'glpi_networknames'  => 'id',
                    'glpi_ipaddresses'   => 'items_id', [
                        'AND' => ['glpi_ipaddresses.itemtype' => 'NetworkName']
                    ]
                ]
            ];
        } else if ($is_networkport) {
            $it_criteria['LEFT JOIN']['glpi_networkports'] = [
                'ON'  => [
                    $itemtable           => 'id',
                    'glpi_networkports'  => 'items_id', [
                        'AND' => ['glpi_networkports.itemtype' => $itemtype]
                    ]
                ]
            ];
        }
    }

    /**
     * ?
     *
     * @param CommonDBTM $item         Item instance
     * @param array      &$it_criteria Iterator criteria
     *
     * @return void
     */
    public function handleOneJoinPerCriteria(CommonDBTM $item, array &$it_criteria)
    {
        $itemtable      = $item->getTable();
        $itemtype       = $item->getType();

        foreach ($this->complex_criteria as $criterion) {
            if ($criterion->fields['criteria'] == 'ip') {
                $astable = 'networkports_' . $criterion->fields['criteria'];
                $it_criteria['LEFT JOIN']['glpi_networkports AS ' . $astable] = [
                    'ON'  => [
                        $itemtable  => 'id',
                        $astable    => 'items_id', [
                            'AND' => [$astable . '.itemtype' => $itemtype]
                        ]
                    ]
                ];
                $it_criteria['LEFT JOIN']['glpi_networknames'] = [
                    'ON'  => [
                        $astable  => 'id',
                        'glpi_networknames'  => 'items_id', [
                            'AND' => ['glpi_networknames.itemtype' => 'NetworkPort']
                        ]
                    ]
                ];
                $it_criteria['LEFT JOIN']['glpi_ipaddresses'] = [
                    'ON'  => [
                        'glpi_networknames'  => 'id',
                        'glpi_ipaddresses'   => 'items_id', [
                            'AND' => ['glpi_ipaddresses.itemtype' => 'NetworkName']
                        ]
                    ]
                ];
            } else if ($this->isNetPort($criterion->fields['criteria'])) {
                $astable = 'networkports_' . $criterion->fields['criteria'];
                $it_criteria['LEFT JOIN']['glpi_networkports AS ' . $astable] = [
                    'ON'  => [
                        $itemtable  => 'id',
                        $astable    => 'items_id', [
                            'AND' => [$astable . '.itemtype' => $itemtype]
                        ]
                    ]
                ];
            }
        }
    }

    /**
     * Handle fields criteria
     *
     * @param CommonDBTM $item         Item instance
     * @param array      &$it_criteria Iterator criteria
     * @param array      $input        Input
     *
     * @return void
     */
    public function handleFieldsCriteria(CommonDBTM $item, &$it_criteria, $input)
    {
        $itemtable      = $item->getTable();
        $itemtype       = $item->getType();

        foreach ($this->complex_criteria as $criterion) {
            switch ($criterion->fields['criteria']) {
                case 'name':
                    if ($criterion->fields['condition'] == Rule::PATTERN_IS_EMPTY) {
                        $it_criteria['WHERE']['OR'] = [
                            ["$itemtable.name" => ''],
                            ["$itemtable.name"   => null]
                        ];
                    } else {
                        $it_criteria['WHERE'][] = ["$itemtable.name" => $input['name']];
                    }
                    break;

                case 'mac':
                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = 'networkports_' . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } else {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }

                    if (!is_array($input['mac'])) {
                        $input['mac'] = [$input['mac']];
                    }
                    $it_criteria['WHERE'][] = [
                        $ntable . '.mac' => $input['mac']
                    ];
                    break;

                case 'ip':
                    if (!is_array($input['ip'])) {
                        $input['ip'] = [$input['ip']];
                    }

                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = "networkports_" . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } else if (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }

                    $it_criteria['WHERE'][] = ['glpi_ipaddresses.name' => $input['ip']];
                    break;

                case 'ifdescr':
                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = "networkports_" . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } else if (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }

                    $it_criteria['WHERE'][] = [$ntable . '.ifdescr' => $input['ifdescr']];
                    break;

                case 'ifnumber':
                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = "networkports_" . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } else if (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }
                    $it_criteria['WHERE'][] = [$ntable . '.logical_number' => $input['ifnumber']];
                    break;

                case 'serial':
                    $serial = $input['serial'];
                    $conf = new Glpi\Inventory\Conf();

                    if (
                        isset($input['itemtype'])
                        && $input['itemtype'] == 'Monitor'
                        && $conf->import_monitor_on_partial_sn == true
                        && strlen($input["serial"]) >= 4
                    ) {
                        $serial = ['LIKE', '%' . $input['serial'] . '%'];
                    }

                    $it_criteria['WHERE'][] = ["$itemtable.serial" => $serial];
                    break;

                case 'otherserial':
                    if ($criterion->fields['condition'] == self::PATTERN_IS_EMPTY) {
                        $it_criteria['WHERE'][] = [
                            'OR' => [
                                ["$itemtable.otherserial" => ''],
                                ["$itemtable.otherserial" => null]
                            ]
                        ];
                    } else {
                        $it_criteria['WHERE'][] = ["$itemtable.otherserial" => $input['otherserial']];
                    }
                    break;

                case 'model':
                    $modelclass = $itemtype . 'Model';
                    $options    = ['manufacturer' => addslashes($input['manufacturer'])];
                    $mid        = Dropdown::importExternal(
                        $modelclass,
                        addslashes($input['model']),
                        -1,
                        $options,
                        '',
                        false
                    );
                    $it_criteria['WHERE'][] = [$itemtable . '.' . $modelclass::getForeignKeyField() => $mid];
                    break;

                case 'manufacturer':
                    $mid = Dropdown::importExternal(
                        'Manufacturer',
                        addslashes($input['manufacturer']),
                        -1,
                        [],
                        '',
                        false
                    );
                    $it_criteria['WHERE'][] = ["$itemtable.manufacturers_id" => $mid];
                    break;

                case 'states_id':
                    $condition = ["$itemtable.states_id" => $criterion->fields['pattern']];
                    if ($criterion->fields['condition'] == Rule::PATTERN_IS) {
                        $it_criteria['WHERE'][] = $condition;
                    } else {
                        $it_criteria['WHERE'][] = ['NOT' => $condition];
                    }
                    break;

                case 'uuid':
                    if ($criterion->fields['condition'] == self::PATTERN_IS_EMPTY) {
                        $it_criteria['WHERE'][] = [
                            'OR' => [
                                ["$itemtable.uuid" => ''],
                                ["$itemtable.uuid" => null]
                            ]
                        ];
                    } else {
                        $it_criteria['WHERE'][] = ["$itemtable.uuid" => $input['uuid']];
                    }
                    break;

                case 'device_id':
                    $it_criteria['LEFT JOIN']['glpi_agents'] = [
                        'ON'  => [
                            'glpi_agents'  => 'items_id',
                            $itemtable     => 'id'
                        ]
                    ];
                    $it_criteria['WHERE'][] = [
                        'glpi_agents.device_id' => $input['device_id']
                    ];
                    break;

                case 'domain':
                    $it_criteria['LEFT JOIN']['glpi_domains'] = [
                        'ON'  => [
                            'glpi_domains' => 'id',
                            $itemtable     => 'domains_id'
                        ]
                    ];
                    $it_criteria['WHERE'][] = [
                        'glpi_domains.name'  => $input['domains_id']
                    ];
                    break;

                case 'linked_item':
                    $it_criteria['WHERE'][] = [
                        'itemtype' => $input['linked_item']['itemtype'],
                        'items_id' => $input['linked_item']['items_id']
                    ];
                    break;
            }
        }
    }

    public function executeActions($output, $params, array $input = [])
    {
        $class = $params['class'] ?? null;
        $rules_id = $this->fields['id'];
        $output['rules_id'] = $rules_id;

        $rulesmatched = new RuleMatchedLog();
        $inputrulelog = [
            'date'      => date('Y-m-d H:i:s'),
            'rules_id'  => $rules_id
        ];

        if ($class && method_exists($class, 'getAgent') && $class->getAgent()) {
            $inputrulelog['agents_id'] = $class->getAgent()->fields['id'];
        }

        if (!isset($params['return'])) {
            $inputrulelog['method'] = 'inventory'; //$class->getMethod();
        }

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields['field'] == '_ignore_import' || $action->fields["value"] == self::RULE_ACTION_DENIED) {
                    $output['action'] = self::LINK_RESULT_DENIED;
                    return $output;
                }

                if ($action->fields['field'] != '_inventory' && $action->fields['field'] != '_fusion') {
                    if (count($this->criterias)) {
                        foreach ($this->criterias as $criterion) {
                            if ($criterion->fields['criteria'] == 'itemtype' && !is_numeric($criterion->fields['pattern'])) {
                                $itemtype = $criterion->fields['pattern'];
                                if ($class && method_exists($class, 'rulepassed')) {
                                    if (!isset($params['return'])) {
                                          $class->rulepassed("0", $itemtype, $rules_id);
                                    }
                                    $output['found_inventories'] = [0, $itemtype, $rules_id];
                                } else {
                                    $output['action'] = self::LINK_RESULT_CREATE;
                                }
                                return $output;
                            }
                        }
                    }

                    if ($class && !isset($params['return'])) {
                        $class->rulepassed("0", "Unmanaged", $rules_id);
                    }
                    $output['found_inventories'] = [0, 'Unmanaged', $rules_id];
                    return $output;
                }

                if (
                    $action->fields["value"] == self::RULE_ACTION_LINK_OR_IMPORT
                    || $action->fields["value"] == self::RULE_ACTION_LINK_OR_NO_IMPORT
                ) {
                    if (isset($this->criterias_results['found_inventories'])) {
                        foreach ($this->criterias_results['found_inventories'] as $itemtype => $inventory) {
                             $items_id = current($inventory);
                             $output['found_inventories'] = [$items_id, $itemtype, $rules_id];
                            if (!isset($params['return'])) {
                                if ($class) {
                                    $class->rulepassed($items_id, $itemtype, $rules_id, $this->criterias_results['found_port']);
                                } else {
                                    $inputrulelog = $inputrulelog + [
                                        'items_id'  => $items_id,
                                        'itemtype'  => $itemtype
                                    ];
                                    $rulesmatched->add($inputrulelog);
                                    $rulesmatched->cleanOlddata($items_id, $itemtype);
                                }
                            }
                            return $output;
                        }
                    } else if ($action->fields["value"] != self::RULE_ACTION_LINK_OR_NO_IMPORT) {
                       // Import into new equipment
                        if (count($this->criterias)) {
                            foreach ($this->criterias as $criterion) {
                                if ($criterion->fields['criteria'] == 'itemtype' && !is_numeric($criterion->fields['pattern'])) {
                                    $itemtype = $criterion->fields['pattern'];
                                    if ($class && !isset($params['return'])) {
                                         $class->rulepassed("0", $itemtype, $rules_id);
                                    }
                                    $output['found_inventories'] = [0, $itemtype, $rules_id];
                                    return $output;
                                }
                            }
                        }

                        if ($class && !isset($params['return'])) {
                            $class->rulepassed("0", "Unmanaged", $rules_id);
                        }
                        $output['found_inventories'] = [0, "Unmanaged", $rules_id];
                        return $output;
                    }
                }
            }
        }
        return $output;
    }


    public function showSpecificCriteriasForPreview($fields)
    {

        $entity_as_criterion = false;
        foreach ($this->criterias as $criterion) {
            if ($criterion->fields['criteria'] == 'entities_id') {
                $entity_as_criterion = true;
                break;
            }
        }
        if (!$entity_as_criterion) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . Entity::getTypeName(1) . "</td>";
            echo "<td>";
            Dropdown::show('Entity');
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<th colspan='2'>" . __('Use values found from an already refused equipment') . "</th>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . RefusedEquipment::getTypeName(1) . "</td>";
        echo "<td>";
        Dropdown::show(RefusedEquipment::getType(), ['value' => ($fields['refusedequipments_id'] ?? null)]);
        echo "</td></tr>";
    }

    /**
     * Create rules (initialisation)
     *
     *
     * @param boolean $reset        Whether to reset before adding new rules, defaults to true
     * @param boolean $with_plugins Use plugins rules or not
     * @param boolean $check        Check if rule exists before creating
     *
     * @return boolean
     */
    public static function initRules($reset = true, $with_plugins = true, $check = false): bool
    {
        global $PLUGIN_HOOKS;

        if ($reset) {
            $rule = new Rule();
            $rules = $rule->find(['sub_type' => 'RuleImportAsset']);
            foreach ($rules as $data) {
                $rule->delete($data);
            }
        }

        $rules = [];

        $rules[] = [
            'name'      => 'No creation on partial import',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'partial',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 1
                ], [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Global update (by mac+ifnumber restricted port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifnumber',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifnumber',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'link_criteria_port',
                    'condition' => self::PATTERN_NETWORK_PORT_RESTRICT,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global update (by mac+ifnumber not restricted port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifnumber',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifnumber',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global import (by mac+ifnumber)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifnumber',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global update (by ip+ifdescr restricted port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ip',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ip',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifdescr',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifdescr',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'link_criteria_port',
                    'condition' => self::PATTERN_NETWORK_PORT_RESTRICT,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global update (by ip+ifdescr not restricted port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ip',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ip',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifdescr',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifdescr',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global import (by ip+ifdescr)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ip',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'ifdescr',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Update only mac address (mac on switch port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'only_these_criteria',
                    'condition' => self::PATTERN_ONLY_CRITERIA_RULE,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Import only mac address (mac on switch port)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'only_these_criteria',
                    'condition' => self::PATTERN_ONLY_CRITERIA_RULE,
                    'pattern'   => 1
                ],
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer constraint (name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Computer update (by serial + uuid)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer update (by serial + uuid is empty in GLPI)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_IS_EMPTY,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import (by serial + uuid)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer update (by uuid)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer update (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer update (by name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import (by uuid)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import (by name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Computer import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Computer'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Printer constraint (name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Printer update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Printer update (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Printer import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Printer import (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Printer import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Printer'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment constraint (name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment update (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment import (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'NetworkEquipment import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'NetworkEquipment'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Device update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Peripheral'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Device import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Peripheral'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Device import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Peripheral'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Monitor update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Monitor'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Monitor import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Monitor'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Monitor import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Monitor'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Phone constraint (name)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Phone'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Phone update (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Phone'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Phone import (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Phone'
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Phone import denied',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Phone'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Cluster update (by uuid)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Cluster'
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Cluster import (by uuid)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Cluster'
                ],
                [
                    'criteria'  => 'uuid',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Cluster import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Cluster'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Enclosure update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Enclosure'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Enclosure import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Enclosure'
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Enclosure import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'Enclosure'
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Global constraint (name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_DOES_NOT_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Global update (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global update (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global import (by serial)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'serial',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global import (by mac)',
            'match'     => 'AND',
            'is_active' => 0,
            'criteria'  => [
                [
                    'criteria'  => 'mac',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Global import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => ''
                ]
            ],
            'action'    => '_deny'
        ];

        $rules[] = [
            'name'      => 'Database update (by name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'DatabaseInstance'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ],
                [
                    'criteria'  => 'linked_item',
                    'condition' => Rule::PATTERN_FIND,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Database import (by name)',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'DatabaseInstance'
                ],
                [
                    'criteria'  => 'name',
                    'condition' => Rule::PATTERN_EXISTS,
                    'pattern'   => 1
                ]
            ],
            'action'    => '_link'
        ];

        $rules[] = [
            'name'      => 'Database import denied',
            'match'     => 'AND',
            'is_active' => 1,
            'criteria'  => [
                [
                    'criteria'  => 'itemtype',
                    'condition' => Rule::PATTERN_IS,
                    'pattern'   => 'DatabaseInstance'
                ]
            ],
            'action'    => '_deny'
        ];

        //load default rules from plugins
        if ($with_plugins && isset($PLUGIN_HOOKS['add_rules'])) {
            $ria = new self();
            foreach ($PLUGIN_HOOKS['add_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $rules = array_merge(
                    $rules,
                    Plugin::doOneHook(
                        $plugin,
                        "ruleImportAsset_addGlobalCriteria",
                        $ria->getGlobalCriteria()
                    )
                );
            }
        }

        $ranking = 0;
        foreach ($rules as $rule) {
            $rulecollection = new RuleImportAssetCollection();
            $input = [
                'is_active' => $rule['is_active'],
                'name'      => $rule['name'],
                'match'     => $rule['match'],
                'sub_type'  => self::getType(),
                'ranking'   => $ranking
            ];

            $exists = false;
            if ($check === true) {
                $exists = $rulecollection->getFromDBByCrit($input);
            }

            if ($exists === true) {
                //rule already exists, ignore.
                continue;
            }
            $rule_id = $rulecollection->add($input);

            // Add criteria
            $ruleclass = $rulecollection->getRuleClass();
            foreach ($rule['criteria'] as $criteria) {
                $rulecriteria = new RuleCriteria(get_class($ruleclass));
                $criteria['rules_id'] = $rule_id;
                $rulecriteria->add($criteria, [], false);
            }

            // Add action
            $ruleaction = new RuleAction(get_class($ruleclass));
            $input = [
                'rules_id'     => $rule_id,
                'action_type'  => 'assign'
            ];

            switch ($rule['action']) {
                case '_link':
                    $input['field'] = '_inventory';
                    $input['value'] = self::RULE_ACTION_LINK_OR_IMPORT;
                    break;
                case '_deny':
                    $input['field'] = '_inventory';
                    $input['value'] = self::RULE_ACTION_DENIED;
                    break;
                case '_ignore_import':
                    $input['field'] = '_ignore_import';
                    $input['value'] = '1';
                    break;
            }

            $ruleaction->add($input, [], false);

            $ranking++;
        }
        return true;
    }

    /**
     * Get itemtypes have state_type and unmanaged devices
     *
     * @global array $CFG_GLPI
     * @return array
     */
    public static function getItemTypesForRules()
    {
        global $CFG_GLPI;

        $types = [];
        foreach ($CFG_GLPI["state_types"] as $itemtype) {
            if (class_exists($itemtype)) {
                $item = new $itemtype();
                $types[$itemtype] = $item->getTypeName();
            }
        }
        $types[""] = __('No itemtype defined');
        ksort($types);
        return $types;
    }

    public function addSpecificParamsForPreview($params)
    {
        $class = new class {
            public function rulepassed($items_id, $itemtype, $rules_id)
            {
            }
        };
        return $params + ['class' => $class];
    }

    /**
     * Get criteria related to network ports
     *
     * @return array
     */
    public function getNetportCriteria(): array
    {
        return [
            'mac',
            'ip',
            'ifnumber',
            'ifdescr'
        ];
    }

    /**
     * Get global criteria
     *
     * @return array
     */
    public function getGlobalCriteria(): array
    {
        global $PLUGIN_HOOKS;

        $criteria = array_merge([
            'manufacturer',
            'model',
            'name',
            'serial',
            'otherserial',
            'uuid',
            'device_id',
            'itemtype',
            'domains_id',
            'linked_item',
            'entity_restrict',
            'link_criteria_port',
            'only_these_criteria'
        ], $this->getNetportCriteria());

       //Add plugin global criteria
        if (isset($PLUGIN_HOOKS['use_rules'])) {
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array($this->getType(), $val)) {
                    $criteria = Plugin::doOneHook(
                        $plugin,
                        "ruleImportAsset_addGlobalCriteria",
                        $criteria
                    );
                }
            }
        }

        return $criteria;
    }

    /**
     * Check if criterion is related to network ports
     *
     * @param string $criterion Criterion to check
     *
     * @return boolean
     */
    public function isNetPort($criterion): bool
    {
        return in_array($criterion, $this->getNetportCriteria());
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'id':
                $rule = new static();
                $rule->getFromDB($values['id']);
                return $rule->getLink();
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        switch ($field) {
            case 'id':
                $options['display'] = false;
                return Rule::dropdown(
                    [
                        'sub_type' => static::class,
                        'display' => false,
                        'name' => $name
                    ] + $options
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }
}
