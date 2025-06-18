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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Asset\Capacity\IsInventoriableCapacity;
use Glpi\Plugin\Hooks;

class RuleImportAsset extends Rule
{
    public const RULE_ACTION_LINK_OR_IMPORT    = 0;
    public const RULE_ACTION_LINK_OR_NO_IMPORT = 1;
    public const RULE_ACTION_DENIED            = 2;

    public const PATTERN_ENTITY_RESTRICT       = 202;
    public const PATTERN_NETWORK_PORT_RESTRICT = 203;
    public const PATTERN_ONLY_CRITERIA_RULE    = 204;

    public const LINK_RESULT_DENIED            = 0;
    public const LINK_RESULT_CREATE            = 1;
    public const LINK_RESULT_LINK              = 2;

    public $restrict_matching = Rule::AND_MATCHING;

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
                    Rule::REGEX_NOT_MATCH,
                ],
            ],
            'virtualmachinetypes_id' => [
                'table'     => 'glpi_virtualmachinetypes',
                'field'     => 'name',
                'name'      => VirtualMachineType::getTypeName(0),
                'type'      => 'dropdown',
                'allow_condition' => [
                    Rule::PATTERN_IS,
                    Rule::PATTERN_IS_NOT,
                    Rule::PATTERN_CONTAIN,
                    Rule::PATTERN_NOT_CONTAIN,
                    Rule::PATTERN_BEGIN,
                    Rule::PATTERN_END,
                    Rule::REGEX_MATCH,
                    Rule::REGEX_NOT_MATCH,
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
                'allow_condition' => [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT],
            ],
            'model' => [
                'name'            => sprintf('%s > %s', _n('Asset', 'Assets', 1), _n('Model', 'Models', 1)),
            ],
            'manufacturer' => [ // Manufacturer as Text to allow text criteria (contains, regex, ...)
                'name'            => Manufacturer::getTypeName(1),
            ],
            'mac' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('MAC')),
            ],
            'ip' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('IP')),
            ],
            'ifdescr' => [
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), NetworkPort::getTypename(1), __('Port description')),
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
                'name'            => sprintf('%s > %s > %s', _n('Asset', 'Assets', 1), OperatingSystem::getTypeName(1), _n('Comment', 'Comments', Session::getPluralNumber())),
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
                'name'            => _n('Linked asset', 'Linked assets', 1),
                'type'            => 'yesno',
                'allow_condition' => [Rule::PATTERN_FIND],
            ],

            'entityrestrict' => [
                'name'            => sprintf('%s > %s', __('General'), __('Restrict search in defined entity')),
                'allow_condition' => [self::PATTERN_ENTITY_RESTRICT],
            ],
            'link_criteria_port' => [
                'name'            => sprintf('%s > %s', __('General'), __('Restrict criteria to same network port')),
                'allow_condition' => [self::PATTERN_NETWORK_PORT_RESTRICT],
                'is_global'       => true,
            ],
            'only_these_criteria' => [
                'name'            => sprintf('%s > %s', __('General'), __('Only criteria of this rule in data')),
                'allow_condition' => [self::PATTERN_ONLY_CRITERIA_RULE],
                'is_global'       => true,
            ],
            'partial' => [
                'name'   => __('Is partial'),
                'type'   => 'yesno',
                'allow_condition' => [Rule::PATTERN_IS, Rule::PATTERN_IS_NOT],
            ],
        ];

        return $criteria;
    }

    public function getActions()
    {
        $actions = [
            '_inventory'   => [
                'name'   => __('Inventory link'),
                'type'   => 'inventory_type',
            ],
            '_ignore_import'  => [
                'name'   => __('Refuse import'),
                'type'   => 'yesonly',
            ],
        ];
        return $actions;
    }

    public static function getRuleActionValues()
    {
        return [
            self::RULE_ACTION_LINK_OR_IMPORT    => __('Link if possible'),
            self::RULE_ACTION_LINK_OR_NO_IMPORT => __('Link if possible, otherwise imports declined'),
            self::RULE_ACTION_DENIED            => __('Import denied (no log)'),
        ];
    }

    public function displayAdditionRuleActionValue($value)
    {
        $values = self::getRuleActionValues();
        return $values[$value] ?? '';
    }

    public function manageSpecificCriteriaValues($criteria, $name, $value)
    {
        switch ($criteria['type']) {
            case "state":
                $link_array = [
                    "0" => __('No'),
                    "1" => __('Yes if equal'),
                    "2" => __('Yes if empty'),
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
        return match ($criterion) {
            'entityrestrict' => [self::PATTERN_ENTITY_RESTRICT => __('Yes')],
            'link_criteria_port' => [self::PATTERN_NETWORK_PORT_RESTRICT => __('Yes')],
            'only_these_criteria' => [self::PATTERN_ONLY_CRITERIA_RULE => __('Yes')],
            default => [
                self::PATTERN_FIND => __('is already present'),
                self::PATTERN_IS_EMPTY => __('is empty'),
            ],
        };
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
                $array = static::getItemTypesForRules();
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
                        } elseif ($crit->fields["criteria"] == 'only_these_criteria') {
                            $this->only_these_criteria = true;
                        } elseif (
                            isset($definition_criteria['is_global'])
                             && $definition_criteria['is_global']
                        ) {
                            // If a value is missing, then there's a problem !
                            trigger_error('A value seems missing, criterion was: ' . $criterion, E_USER_WARNING);
                            return false;
                        }
                    } elseif (in_array($crit->fields["condition"], [Rule::PATTERN_FIND, Rule::PATTERN_IS_EMPTY])) {
                        $this->complex_criteria[] = $crit;
                        ++$this->found_criteria;
                    } elseif ($crit->fields["condition"] == Rule::PATTERN_EXISTS) {
                        if (
                            !isset($input[$crit->fields['criteria']])
                            || empty($input[$crit->fields['criteria']])
                        ) {
                            trigger_error('A value seems missing, criterion was: ' . $criterion, E_USER_WARNING);
                            return false;
                        }
                    } elseif ($crit->fields["criteria"] == 'itemtype') {
                        $this->complex_criteria[] = $crit;
                    } elseif ($crit->fields["criteria"] == 'entityrestrict') {
                        $this->restrict_entity = true;
                    }
                }
            }
        }

        foreach ($this->getCriteriaByID('tag') as $crit) {
            $this->complex_criteria[] = $crit;
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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         * @var array $PLUGIN_HOOKS
         */
        global $CFG_GLPI, $DB, $PLUGIN_HOOKS;

        $this->complex_criteria = [];
        $this->restrict_entity = false;
        $this->only_these_criteria = false;
        $this->link_criteria_port = false;

        if (!$this->preComputeCriteria($input)) {
            // logged in place, just ignore
            return false;
        }

        // No complex criteria
        if (empty($this->complex_criteria) || $this->found_criteria === 0) {
            return true;
        }

        // Get all equipment type
        $itemtypeselected = [];
        if (
            isset($input['itemtype'])
            && (is_array($input['itemtype']))
        ) {
            foreach ($input['itemtype'] as $k => $v) {
                if (!is_a($v, CommonDBTM::class, true)) {
                    unset($input['itemtype'][$k]);
                    continue;
                }
                $itemtypeselected[] = $v;
            }
        } elseif (
            isset($input['itemtype'])
            && (!empty($input['itemtype']))
            && is_a($input['itemtype'], CommonDBTM::class, true)
        ) {
            $itemtypeselected[] = $input['itemtype'];
        } else {
            foreach ($CFG_GLPI["asset_types"] as $itemtype) {
                if (
                    class_exists($itemtype)
                    && is_a($itemtype, CommonDBTM::class, true)
                    && $itemtype !== SoftwareLicense::class
                    && $itemtype !== Certificate::class
                ) {
                    $itemtypeselected[] = $itemtype;
                }
            }
            $itemtypeselected[] = Unmanaged::class;
            $itemtypeselected[] = Peripheral::class;//used for networkinventory
        }

        $found = false;
        foreach ($itemtypeselected as $itemtype) {
            $item = new $itemtype(); //$itemtypeselected entries are filtered to contain only CommonDBTM classes - should be safe.
            $itemtable = $item->getTable();

            // Build the request to check if the asset exists in GLPI
            $where_entity = $input['entities_id'] ?? [];
            if (!empty($where_entity) && !is_array($where_entity)) {
                $where_entity = [$where_entity];
            }

            $it_criteria = [
                'SELECT' => ["$itemtable.id"],
                'FROM'   => $itemtable, //to fill
                'WHERE'  => $item->getSystemSQLCriteria(), //to fill
            ];

            // do not reconcile if it's a template
            if ($item->maybeTemplate()) {
                $it_criteria['WHERE'][] = ['is_template' =>  0];
            }

            if ($this->link_criteria_port) {
                $this->handleLinkCriteriaPort($item, $it_criteria);
            } else {
                // 1 join per criterion
                $this->handleOneJoinPerCriteria($item, $it_criteria);
            }

            $this->handleFieldsCriteria($item, $it_criteria, $input);

            if (isset($PLUGIN_HOOKS[Hooks::USE_RULES])) {
                foreach ($PLUGIN_HOOKS[Hooks::USE_RULES] as $plugin => $val) {
                    if (!Plugin::isPluginActive($plugin)) {
                        continue;
                    }
                    if (is_array($val) && in_array(static::class, $val, true)) {
                        $params = [
                            'where_entity' => $where_entity,
                            'itemtype'     => $itemtype,
                            'input'        => $input,
                            'criteria'     => $this->complex_criteria,
                            'sql_criteria' => $it_criteria,
                        ];
                        $sql_results = Plugin::doOneHook(
                            $plugin,
                            Hooks::AUTO_RULEIMPORTASSET_GET_SQL_RESTRICTION,
                            $params
                        );

                        $it_criteria = array_merge_recursive($it_criteria, $sql_results);
                    }
                }
            }

            $result_glpi = $DB->request($it_criteria);

            if (count($result_glpi)) {
                $this->criterias_results['found_port'] = [];
                foreach ($result_glpi as $data) {
                    $this->criterias_results['found_inventories'][$itemtype][] = $data['id'];
                    foreach ($data as $alias => $value) {
                        if (
                            str_contains($alias, "portid")
                            && !is_null($value)
                            && is_numeric($value)
                            && $value > 0
                        ) {
                            $this->criterias_results['found_port'][] = $value;
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
            } elseif ($this->isNetPort($criteria->fields['criteria'])) {
                $is_networkport = true;
            }
        }

        if ($is_ip) {
            $it_criteria['LEFT JOIN']['glpi_networkports'] = [
                'ON'  => [
                    $itemtable           => 'id',
                    'glpi_networkports'  => 'items_id', [
                        'AND' => ['glpi_networkports.itemtype' => $itemtype],
                    ],
                ],
            ];
            $it_criteria['LEFT JOIN']['glpi_networknames'] = [
                'ON'  => [
                    'glpi_networkports'  => 'id',
                    'glpi_networknames'  => 'items_id', [
                        'AND' => ['glpi_networknames.itemtype' => 'NetworkPort'],
                    ],
                ],
            ];
            $it_criteria['LEFT JOIN']['glpi_ipaddresses'] = [
                'ON'  => [
                    'glpi_networknames'  => 'id',
                    'glpi_ipaddresses'   => 'items_id', [
                        'AND' => ['glpi_ipaddresses.itemtype' => 'NetworkName'],
                    ],
                ],
            ];
        } elseif ($is_networkport) {
            $it_criteria['LEFT JOIN']['glpi_networkports'] = [
                'ON'  => [
                    $itemtable           => 'id',
                    'glpi_networkports'  => 'items_id', [
                        'AND' => ['glpi_networkports.itemtype' => $itemtype],
                    ],
                ],
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
                            'AND' => [$astable . '.itemtype' => $itemtype],
                        ],
                    ],
                ];
                $it_criteria['LEFT JOIN']['glpi_networknames'] = [
                    'ON'  => [
                        $astable  => 'id',
                        'glpi_networknames'  => 'items_id', [
                            'AND' => ['glpi_networknames.itemtype' => 'NetworkPort'],
                        ],
                    ],
                ];
                $it_criteria['LEFT JOIN']['glpi_ipaddresses'] = [
                    'ON'  => [
                        'glpi_networknames'  => 'id',
                        'glpi_ipaddresses'   => 'items_id', [
                            'AND' => ['glpi_ipaddresses.itemtype' => 'NetworkName'],
                        ],
                    ],
                ];
            } elseif ($this->isNetPort($criterion->fields['criteria'])) {
                $astable = 'networkports_' . $criterion->fields['criteria'];
                $it_criteria['LEFT JOIN']['glpi_networkports AS ' . $astable] = [
                    'ON'  => [
                        $itemtable  => 'id',
                        $astable    => 'items_id', [
                            'AND' => [$astable . '.itemtype' => $itemtype],
                        ],
                    ],
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
                            ["$itemtable.name"   => null],
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
                        $ntable . '.mac' => $input['mac'],
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
                    } elseif (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }

                    $it_criteria['WHERE'][] = ['glpi_ipaddresses.name' => $input['ip']];
                    break;

                case 'ifdescr':
                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = "networkports_" . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } elseif (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }

                    $it_criteria['WHERE'][] = [$ntable . '.ifdescr' => $input['ifdescr']];
                    break;

                case 'ifnumber':
                    $ntable = 'glpi_networkports';
                    if (!$this->link_criteria_port) {
                        $ntable = "networkports_" . $criterion->fields['criteria'];
                        $it_criteria['SELECT'][] = $ntable . ".id AS portid_" . $criterion->fields['criteria'];
                    } elseif (!in_array('glpi_networkports.id AS portid', $it_criteria['SELECT'])) {
                        $it_criteria['SELECT'][] = 'glpi_networkports.id AS portid';
                    }
                    $it_criteria['WHERE'][] = [$ntable . '.logical_number' => $input['ifnumber']];
                    break;

                case 'tag':
                    if (isset($input['tag']) && isset($input['deviceid'])) {
                        $it_criteria['LEFT JOIN']['glpi_agents'] = [
                            'ON'  => [
                                'glpi_agents'  => 'items_id',
                                $itemtable     => 'id',
                            ],
                        ];
                        $it_criteria['WHERE'][] = [
                            'glpi_agents.deviceid' => $input['deviceid'],
                            'glpi_agents.tag' => $input['tag'],
                        ];
                    }
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
                                ["$itemtable.otherserial" => null],
                            ],
                        ];
                    } else {
                        $it_criteria['WHERE'][] = ["$itemtable.otherserial" => $input['otherserial']];
                    }
                    break;

                case 'model':
                    $modelclass = $itemtype . 'Model';
                    $options    = ['manufacturer' => $input['manufacturer']];
                    $mid        = Dropdown::importExternal(
                        $modelclass,
                        $input['model'],
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
                        $input['manufacturer'],
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
                                ["$itemtable.uuid" => null],
                            ],
                        ];
                    } else {
                        $it_criteria['WHERE'][] = [
                            "RAW" => [
                                "LOWER($itemtable.uuid)" => ItemVirtualMachine::getUUIDRestrictCriteria($input['uuid']),
                            ],
                        ];
                    }
                    break;

                case 'device_id':
                    $it_criteria['LEFT JOIN']['glpi_agents'] = [
                        'ON'  => [
                            'glpi_agents'  => 'items_id',
                            $itemtable     => 'id',
                        ],
                    ];
                    $it_criteria['WHERE'][] = [
                        'glpi_agents.device_id' => $input['device_id'],
                    ];
                    break;

                case 'domain':
                    $it_criteria['LEFT JOIN']['glpi_domains'] = [
                        'ON'  => [
                            'glpi_domains' => 'id',
                            $itemtable     => 'domains_id',
                        ],
                    ];
                    $it_criteria['WHERE'][] = [
                        'glpi_domains.name'  => $input['domains_id'],
                    ];
                    break;

                case 'linked_item':
                    $it_criteria['WHERE'][] = [
                        'itemtype' => $input['linked_item']['itemtype'],
                        'items_id' => $input['linked_item']['items_id'],
                    ];
                    break;
            }
        }
    }

    public function executeActions($output, $params, array $input = [])
    {
        $class = $params['class'] ?? null;
        $rules_id = $this->fields['id'];

        $rulesmatched = new RuleMatchedLog();
        $inputrulelog = [
            'date'      => date('Y-m-d H:i:s'),
            'rules_id'  => $rules_id,
        ];

        if ($class && method_exists($class, 'getAgent') && $class->getAgent()) {
            $inputrulelog['agents_id'] = $class->getAgent()->fields['id'];
        }

        if (!isset($params['return'])) {
            $inputrulelog['method'] = 'inventory'; //$class->getMethod();
        }

        if (count($this->actions)) {
            foreach ($this->actions as $action) {
                if ($action->fields["value"] == self::RULE_ACTION_DENIED) {
                    $output['action'] = self::LINK_RESULT_DENIED;
                    return $output;
                }

                if ($action->fields['field'] == '_ignore_import') {
                    $output['action'] = self::LINK_RESULT_CREATE;
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

                    $back_class = Unmanaged::class;
                    if (is_a($class, \Glpi\Inventory\MainAsset\MainAsset::class)) {
                        $back_class = $class->getItemtype();
                    }
                    if ($class && !isset($params['return'])) {
                        $class->rulepassed("0", $back_class, $rules_id);
                    }
                    $output['found_inventories'] = [0, $back_class, $rules_id];
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
                                        'itemtype'  => $itemtype,
                                    ];
                                    $rulesmatched->add($inputrulelog);
                                    $rulesmatched->cleanOlddata($items_id, $itemtype);
                                }
                            }
                            return $output;
                        }
                    } elseif ($action->fields["value"] != self::RULE_ACTION_LINK_OR_NO_IMPORT) {
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

                        $back_class = Unmanaged::class;
                        if (is_a($class, \Glpi\Inventory\MainAsset\MainAsset::class)) {
                            $back_class = $class->getItemtype();
                        }

                        if ($back_class === Unmanaged::class) {
                            $conf = new \Glpi\Inventory\Conf();
                            if ($conf->import_unmanaged == 0) {
                                return $output;
                            }
                        }

                        if ($class && !isset($params['return'])) {
                            $class->rulepassed("0", $back_class, $rules_id);
                        }
                        $output['found_inventories'] = [0, $back_class, $rules_id];
                        return $output;
                    }
                }
            }
        }
        return $output;
    }

    public function showSpecificCriteriasForPreview($fields)
    {
        $twig_params = [
            'entity_as_criterion' => false,
            'fields'              => $fields,
            'type_match'          => $this->fields['match'] === Rule::AND_MATCHING ? __('AND') : __('OR'),
        ];
        foreach ($this->criterias as $criterion) {
            if ($criterion->fields['criteria'] === 'entities_id') {
                $twig_params['entity_as_criterion'] = true;
                break;
            }
        }

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {% if not entity_as_criterion %}
                {{ fields.htmlField('', type_match|e, '', {
                    no_label: true,
                    field_class: 'col-2',
                    input_class: 'col-12'
                }) }}
                {{ fields.dropdownField('Entity', 'entities_id', 0, 'Entity'|itemtype_name, {
                    field_class: 'col-10',
                    label_class: 'col-5',
                    input_class: 'col-7'
                }) }}
            {% endif %}
            {{ fields.htmlField('', loop.first ? '' : type_match|e, '', {
                no_label: true,
                field_class: 'col-2',
                input_class: 'col-12'
            }) }}
            {{ fields.dropdownField('RefusedEquipment', 'refusedequipments_id', fields['refusedequipments_id']|default(null), 'RefusedEquipment'|itemtype_name, {
                field_class: 'col-10',
                label_class: 'col-5',
                input_class: 'col-7'
            }) }}
TWIG, $twig_params);
    }

    /**
     * Get itemtypes
     *
     * @global array $CFG_GLPI
     * @return array
     */
    public static function getItemTypesForRules()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $types = [];
        foreach ($CFG_GLPI["ruleimportasset_types"] as $itemtype) {
            if (is_a($itemtype, CommonDBTM::class, true)) {
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
            public function rulepassed($items_id, $itemtype, $rules_id) {}
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
            'ifdescr',
        ];
    }

    /**
     * Get global criteria
     *
     * @return array
     */
    public function getGlobalCriteria(): array
    {
        /** @var array $PLUGIN_HOOKS */
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
            'only_these_criteria',
        ], $this->getNetportCriteria());

        // Add plugin global criteria
        if (isset($PLUGIN_HOOKS['use_rules'])) {
            foreach ($PLUGIN_HOOKS['use_rules'] as $plugin => $val) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                if (is_array($val) && in_array(static::class, $val, true)) {
                    $criteria = Plugin::doOneHook(
                        $plugin,
                        Hooks::AUTO_RULEIMPORTASSET_ADD_GLOBAL_CRITERIA,
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
                        'name' => $name,
                    ] + $options
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get default rules as XML
     *
     * @return SimpleXMLElement|false
     */
    public function getDefaultRules(): SimpleXMLElement|false
    {
        $rules = parent::getDefaultRules();
        if (!$rules) {
            return false;
        }

        //add extra rules for active generic assets
        $definitions = AssetDefinitionManager::getInstance()->getDefinitions(true);
        foreach ($definitions as $definition) {
            if ($definition->hasCapacityEnabled(new IsInventoriableCapacity())) {
                $asset_classname = $definition->getAssetClassName();
                $main_asset = $definition->getCapacityConfiguration(IsInventoriableCapacity::class)->getValue('inventory_mainasset');

                $origin_rule_itemtype = \Computer::class;
                switch ($main_asset) {
                    case \Glpi\Inventory\MainAsset\GenericNetworkAsset::class:
                        $origin_rule_itemtype = \NetworkEquipment::class;
                        break;
                    case \Glpi\Inventory\MainAsset\GenericPrinterAsset::class:
                        $origin_rule_itemtype = \Printer::class;
                        break;
                }
                $this->addGenericAssetRules($rules, $asset_classname, $origin_rule_itemtype);
            }
        }

        return $rules;
    }

    public function addGenericAssetRules(
        SimpleXMLElement $rules,
        string $itemtype_to,
        string $itemtype_from
    ): void {
        $extra_rules = $rules->xpath(
            sprintf(
                "/rules/rule[rulecriteria/criteria = 'itemtype' and rulecriteria/pattern = '%s']",
                $itemtype_from
            )
        );

        //switch to DOMXML since SimpleXML cannot add full children...
        $drules = dom_import_simplexml($rules);

        foreach ($extra_rules as $rule) {
            $crule = clone($rule);
            $crule->uuid = str_replace(
                strtolower($itemtype_from),
                Toolbox::slugify($itemtype_to),
                $crule->uuid
            );
            $crule->name = str_replace(
                $itemtype_from,
                $itemtype_to,
                $crule->name
            );
            foreach ($crule->rulecriteria as $criteria) {
                if ($criteria->criteria == 'itemtype') {
                    $criteria->pattern = $itemtype_to;
                }
            }

            //switch to DOMXML since SimpleXML cannot add full children...
            $drule = dom_import_simplexml($crule);
            $drules->appendChild($drule->cloneNode(true));
        }
    }


    public static function getIcon()
    {
        return "ti ti-database-search";
    }
}
