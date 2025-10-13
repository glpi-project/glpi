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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Features\Clonable;

/**
 * Budget class
 */
class Budget extends CommonDropdown
{
    use Clonable;

    // From CommonDBTM
    public $dohistory           = true;

    public static $rightname           = 'budget';
    protected $usenotepad       = true;

    public $can_be_translated = false;

    public function getCloneRelations(): array
    {
        return [
            Document_Item::class,
            KnowbaseItem_Item::class,
            ManualLink::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Budget', 'Budgets', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['management', self::class];
    }

    public static function getLogServiceName(): string
    {
        return 'management';
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            switch ($item->getType()) {
                case self::class:
                    return [1 => self::createTabEntry(__('Main')),
                        2 => self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), 0, $item::getType(), 'ti ti-package'),
                    ];
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item instanceof self) {
            switch ($tabnum) {
                case 1:
                    return $item->showValuesByEntity();

                case 2:
                    return $item->showItems();
            }
        }
        return true;
    }

    /**
     * Print the contact form
     *
     * @param integer $ID      Integer ID of the item
     * @param array  $options  Array of possible options:
     *     - target for the Form
     *     - withtemplate : template or basic item
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     **/
    public function showForm($ID, array $options = [])
    {
        TemplateRenderer::getInstance()->display('pages/management/budget.html.twig', [
            'item' => $this,
            'no_header' => true,
            'params' => [
                'canedit' => $this->canUpdateItem(),
            ],
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {

        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        unset($input['withtemplate']);

        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_budgettypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'begin_date',
            'name'               => __('Start date'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'date',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => _x('price', 'Value'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '50',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        // add objectlock search options
        $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));
        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * Get the SQL union query to get the list of items on a budget and the associated costs
     * @param bool $entity_restrict Whether to restrict the items to the current entity
     * @return QueryUnion
     */
    private function getItemListCriteria(bool $entity_restrict = true): QueryUnion
    {
        global $DB;

        $budgets_id = $this->fields['id'];

        // Get a list of possible itemtypes first, so we can filter them by read permissions
        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_infocoms',
            'WHERE'           => [
                'budgets_id'   => $budgets_id,
                'NOT'          => ['itemtype' => ['ConsumableItem', 'CartridgeItem', 'Software']],
            ],
            'ORDER'           => 'itemtype',
        ]);
        $itemtypes = [
            // These types shouldn't be in the glpi_infocoms table, but have their costs elsewhere
            'Contract', 'Ticket', 'Problem', 'Change', 'Project',
        ];
        foreach ($iterator as $row) {
            $itemtypes[] = $row['itemtype'];
        }
        $infocom_itemtypes = [];
        $other_cost_tables = [
            'Contract' => ContractCost::getTable(),
            'Ticket' => TicketCost::getTable(),
            'Problem' => ProblemCost::getTable(),
            'Change' => ChangeCost::getTable(),
            'Project' => ProjectCost::getTable(),
        ];
        foreach ($itemtypes as $itemtype) {
            if (in_array($itemtype, $infocom_itemtypes)) {
                continue; // prevent duplicates
            }

            if (!is_a($itemtype, CommonDBTM::class, true) || !$itemtype::canView()) {
                continue;
            }

            if (!in_array($itemtype, ['Contract', 'Ticket', 'Problem', 'Change', 'Project'], true)) {
                $infocom_itemtypes[] = $itemtype;
            }
        }

        $queries = [];

        foreach ($infocom_itemtypes as $itemtype) {
            $item_table = $itemtype::getTable();
            $criteria = [
                'SELECT'       => [
                    new QueryExpression($DB::quoteValue($itemtype), '_itemtype'),
                    $item_table => ['id', 'entities_id'],

                ],
                'FROM'         => 'glpi_infocoms',
                'INNER JOIN'   => [
                    $item_table => [
                        'ON' => [
                            $item_table => 'id',
                            'glpi_infocoms'   => 'items_id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_infocoms.itemtype'            => $itemtype,
                    'glpi_infocoms.budgets_id'          => $budgets_id,
                ],
                'ORDERBY'      => [
                    $item_table . '.entities_id',
                ],
            ];
            if ($entity_restrict) {
                $criteria['WHERE'] += getEntitiesRestrictCriteria($item_table);
            }

            /** @var CommonDBTM $item */
            $item = new $itemtype();

            $criteria['SELECT'][$item_table][] = $item->maybeDeleted() ? 'is_deleted' : new QueryExpression('0', 'is_deleted');
            $criteria['SELECT'][$item_table][] = $item->isField('serial') ? 'serial' : new QueryExpression('NULL', 'serial');
            $criteria['SELECT'][$item_table][] = $item->isField('otherserial') ? 'otherserial' : new QueryExpression('NULL', 'otherserial');
            if ($item instanceof Item_Devices) {
                $criteria['SELECT'][$item_table][] = $item::$items_id_2 . ' AS devices_id';
            } else {
                $criteria['SELECT'][] = new QueryExpression('NULL', 'devices_id');
            }
            $criteria['SELECT'][] = 'glpi_infocoms.value';
            if ($item->maybeTemplate()) {
                $criteria['WHERE'][$item_table . '.is_template'] = 0;
            }

            $queries[] = new QuerySubQuery($criteria);
        }

        foreach ($other_cost_tables as $itemtype => $cost_table) {
            $item_table = $itemtype::getTable();
            $item = new $itemtype();
            $criteria = [
                'SELECT' => [
                    new QueryExpression($DB::quoteValue($itemtype), '_itemtype'),
                    $item_table => ['id', 'entities_id'],
                    new QueryExpression('NULL', 'serial'),
                    new QueryExpression('NULL', 'otherserial'),
                    new QueryExpression('NULL', 'devices_id'),
                ],
                'FROM' => $cost_table,
                'INNER JOIN' => [
                    $item_table => [
                        'ON' => [
                            $item_table => 'id',
                            $cost_table => $itemtype::getForeignKeyField(),
                        ],
                    ],
                ],
                'WHERE' => [
                    $cost_table . '.budgets_id' => $budgets_id,
                ],
                'GROUPBY'      => [
                    $item_table . '.id',
                    $item_table . '.entities_id',
                ],
                'ORDERBY'      => [
                    $item_table . '.entities_id',
                    $item_table . '.name',
                ],
            ];
            if ($entity_restrict) {
                $criteria['WHERE'] += getEntitiesRestrictCriteria($item_table);
            }
            $criteria['ORDERBY'][] = $item_table . '.name';

            $criteria['SELECT'][] = match ($itemtype) {
                'Ticket', 'Problem', 'Change' => QueryFunction::sum(
                    expression: new QueryExpression($DB::quoteName("$cost_table.actiontime") . " * " . $DB::quoteName("$cost_table.cost_time") . "/" . HOUR_TIMESTAMP . "
                                      + " . $DB::quoteName("$cost_table.cost_fixed") . "
                                      + " . $DB::quoteName("$cost_table.cost_material")),
                    alias: 'value'
                ),
                default => QueryFunction::sum(expression: "{$cost_table}.cost", alias: 'value'),
            };
            $criteria['SELECT'][$item_table][] = $item->maybeDeleted() ? 'is_deleted' : '0 AS is_deleted';

            if ($item->maybeTemplate()) {
                $criteria['WHERE'][$item_table . '.is_template'] = 0;
            }

            $queries[] = new QuerySubQuery($criteria);
        }

        return new QueryUnion($queries);
    }

    /**
     * Print the HTML array of Items on a budget
     *
     * @return bool
     **/
    public function showItems(): bool
    {
        global $DB;

        $budgets_id = $this->fields['id'];

        if (!$this->can($budgets_id, READ)) {
            return false;
        }

        $start = $_GET['start'] ?? 0;

        $criteria = [
            'FROM' => $this->getItemListCriteria(),
            'START' => $start,
            'LIMIT' => $_SESSION['glpilist_limit'],
        ];
        $iterator = $DB->request($criteria);

        $count_criteria = $criteria;
        unset($count_criteria['START'], $count_criteria['LIMIT']);
        $count_criteria['COUNT'] = 'cpt';
        $total_count = $DB->request($count_criteria)->current()['cpt'];

        $entries = [];
        $entity_names = [];
        /** @var CommonDBTM[] $items */
        $items = [];
        foreach ($iterator as $data) {
            $itemtype = $data['_itemtype'];
            $entry = [
                'itemtype' => $itemtype,
                'id' => $data['id'],
                'type' => $itemtype::getTypeName(1),
                'serial' => $data['serial'] ?? '-',
                'otherserial' => $data['otherserial'] ?? '-',
                'value' => $data['value'] ? Html::formatNumber($data['value']) : '-',
            ];

            if (!array_key_exists($itemtype, $items)) {
                $items[$itemtype] = getItemForItemtype($itemtype);
            }

            $name = htmlescape(NOT_AVAILABLE);
            if ($items[$itemtype]->getFromDB($data["id"])) {
                if ($items[$itemtype] instanceof Item_Devices) {
                    $tmpitem = getItemForItemtype($items[$itemtype]::$itemtype_2);
                    if ($tmpitem->getFromDB($data['devices_id'])) {
                        $name = $tmpitem->getLink(['additional' => true]);
                    }
                } else {
                    $name = $items[$itemtype]->getLink(['additional' => true]);
                }
            }
            $entry['name'] = $name;

            if (!array_key_exists($data['entities_id'], $entity_names)) {
                $entity_names[$data['entities_id']] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $data["entities_id"]
                );
            }
            $entry['entity'] = $entity_names[$data['entities_id']];
            if (isset($data['is_deleted']) && $data['is_deleted']) {
                $entry['row_class'] = 'table-danger';
            }

            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'limit' => $_SESSION['glpilist_limit'],
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'type' => _n('Type', 'Types', 1),
                'entity' => Entity::getTypeName(1),
                'name' => __('Name'),
                'serial' => __('Serial number'),
                'otherserial' => __('Inventory number'),
                'value' => _x('price', 'Value'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => $total_count,
            'filtered_number' => $total_count,
            'showmassiveactions' => false,
        ]);

        return true;
    }

    /**
     * Print the HTML array of value consumed for a budget
     *
     * @return bool
     **/
    public function showValuesByEntity(): bool
    {
        global $DB;

        $budgets_id = $this->fields['id'];

        if (!$this->can($budgets_id, READ)) {
            return false;
        }

        $iterator = $DB->request([
            'FROM' => $this->getItemListCriteria(false),
        ]);

        $itemtypes = [];
        $entities = [
            -1 => [
                'name' => __('Other entities'),
                'itemtypes' => [],
                'total' => 0,
            ],
        ];
        $entries = [];
        $itemtype_totals = [];
        $grand_total = 0;
        $active_entities = $_SESSION['glpiactiveentities'];

        foreach ($iterator as $data) {
            $itemtype = $data['_itemtype'];
            $entity = in_array($data['entities_id'], $active_entities, false) ? $data['entities_id'] : -1;
            if (!in_array($itemtype, $itemtypes, true)) {
                $itemtypes[] = $itemtype;
            }

            if (!array_key_exists($entity, $entities)) {
                $entities[$entity] = [
                    'name' => Dropdown::getDropdownName(
                        "glpi_entities",
                        $data["entities_id"]
                    ),
                    'itemtypes' => [],
                    'total' => 0,
                ];
            }
            if (!array_key_exists($itemtype, $entities[$entity]['itemtypes'])) {
                $entities[$entity]['itemtypes'][$itemtype] = 0;
            }
            if (!array_key_exists($itemtype, $itemtype_totals)) {
                $itemtype_totals[$itemtype] = 0;
            }
            $entities[$entity]['itemtypes'][$itemtype] += $data['value'];
            $entities[$entity]['total'] += $data['value'];
            $itemtype_totals[$itemtype] += $data['value'];
            $grand_total += $data['value'];
        }

        foreach ($entities as $entities_id => $entity) {
            $entry = [
                'itemtype' => Entity::class,
                'id' => $entities_id,
                'entity' => $entity['name'],
            ];
            foreach ($itemtypes as $itemtype) {
                $entry[$itemtype] = $entity['itemtypes'][$itemtype] ?? 0;
            }
            $entry['total'] = $entity['total'];
            $entries[] = $entry;
        }

        $columns = [
            'entity' => Entity::getTypeName(1),
        ];
        $formatters = [
            'total' => 'number',
        ];
        foreach ($itemtypes as $itemtype) {
            $columns[$itemtype] = $itemtype::getTypeName(1);
            $formatters[$itemtype] = 'number';
        }
        $columns['total'] = _x('price', 'Total');

        $footer = [
            _x('price', 'Total'),
        ];
        foreach ($itemtypes as $itemtype) {
            $footer[] = Html::formatNumber($itemtype_totals[$itemtype]);
        }
        $footer[] = Html::formatNumber($grand_total);
        $col_count = count($columns);

        $budget_remaining = $this->fields['value'] - $grand_total;
        $overbudget = $budget_remaining < 0;
        $budget_remaining = Html::formatNumber(abs($budget_remaining));
        if ($overbudget) {
            $budget_remaining = '(' . $budget_remaining . ')';
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'super_header' => __('Total spent on the budget'),
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'footers' => [
                $footer,
                array_pad([__('Total spent on the budget'), Html::formatNumber($grand_total)], -$col_count, ''),
                array_pad([__('Total remaining on the budget'), $budget_remaining], -$col_count, ''),
            ],
            'footer_class' => 'fw-bold',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);

        return true;
    }

    public static function getIcon()
    {
        return "ti ti-calculator";
    }
}
