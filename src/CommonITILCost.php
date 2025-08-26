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

/**
 * CommonITILCost Class
 *
 * @since 0.85
 **/
abstract class CommonITILCost extends CommonDBChild
{
    public $dohistory        = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Cost', 'Costs', $nb);
    }

    public static function getIcon()
    {
        return Infocom::getIcon();
    }

    public function getItilObjectItemType()
    {
        return str_replace('Cost', '', static::class);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // can exists for template
        if (
            (get_class($item) == static::$itemtype)
            && ($item instanceof CommonDBTM)
            && static::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    static::getTable(),
                    [$item::getForeignKeyField() => $item->getID()]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonITILObject && !$item instanceof Project) {
            return false;
        }

        self::showForObject($item);
        return true;
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
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Title'),
            'searchtype'         => 'contains',
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
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
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'begin_date',
            'name'               => __('Begin date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => static::getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'actiontime',
            'name'               => __('Duration'),
            'datatype'           => 'timestamp',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'cost_time',
            'name'               => __('Time cost'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => static::getTable(),
            'field'              => 'cost_fixed',
            'name'               => __('Fixed cost'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'cost_material',
            'name'               => __('Material cost'),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => Budget::getTable(),
            'field'              => 'name',
            'name'               => Budget::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => Entity::getTable(),
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd(): array
    {
        global $DB;

        $tab = [];

        $tab[] = [
            'id'                 => 'cost',
            'name'               => _n('Cost', 'Costs', 1),
        ];

        $tab[] = [
            'id'                 => '48',
            'table'              => static::getTable(),
            'field'              => 'totalcost',
            'name'               => __('Total cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation'        => new QueryExpression(
                '(' . QueryFunction::sum(
                    expression: new QueryExpression($DB::quoteName('TABLE.actiontime') . ' * ' . $DB::quoteName('TABLE.cost_time') . ' / '
                        . HOUR_TIMESTAMP . ' + ' . $DB::quoteName('TABLE.cost_fixed') . ' + ' . $DB::quoteName('TABLE.cost_material'))
                ) . ' / ' . QueryFunction::count('TABLE.id') . ') * '
                    . QueryFunction::count(expression: 'TABLE.id', distinct: true)
            ),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '42',
            'table'              => static::getTable(),
            'field'              => 'cost_time',
            'name'               => __('Time cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation'        => new QueryExpression(
                '(' . QueryFunction::sum(
                    expression: new QueryExpression($DB::quoteName('TABLE.actiontime') . ' * ' . $DB::quoteName('TABLE.cost_time') . ' / '
                        . HOUR_TIMESTAMP)
                ) . ' / ' . QueryFunction::count('TABLE.id') . ') * '
                    . QueryFunction::count(expression: 'TABLE.id', distinct: true)
            ),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => static::getTable(),
            'field'              => 'actiontime',
            'name'               => sprintf(__('%1$s - %2$s'), _n('Cost', 'Costs', 1), __('Duration')),
            'datatype'           => 'timestamp',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
        ];

        $tab[] = [
            'id'                 => '43',
            'table'              => static::getTable(),
            'field'              => 'cost_fixed',
            'name'               => __('Fixed cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation'        => new QueryExpression(
                '(' . QueryFunction::sum(
                    expression: 'TABLE.cost_fixed'
                ) . ' / ' . QueryFunction::count('TABLE.id') . ') * '
                    . QueryFunction::count(expression: 'TABLE.id', distinct: true)
            ),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => static::getTable(),
            'field'              => 'cost_material',
            'name'               => __('Material cost'),
            'datatype'           => 'decimal',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation'        => new QueryExpression(
                '(' . QueryFunction::sum(
                    expression: 'TABLE.cost_material'
                ) . ' / ' . QueryFunction::count('TABLE.id') . ') * '
                    . QueryFunction::count(expression: 'TABLE.id', distinct: true)
            ),
            'nometa'             => true, // cannot GROUP_CONCAT a SUM
        ];

        return $tab;
    }

    /**
     * Init cost for creation based on previous cost
     *
     * @return void
     **/
    public function initBasedOnPrevious(): void
    {

        $item = getItemForItemtype(static::$itemtype);
        if (
            !isset($this->fields[static::$items_id])
            || !$item->getFromDB($this->fields[static::$items_id])
        ) {
            return;
        }

        // Set actiontime to
        $this->fields['actiontime']
                    = max(
                        0,
                        $item->fields['actiontime']
                        - $this->getTotalActionTimeForItem($this->fields[static::$items_id])
                    );
        $lastdata     = $this->getLastCostForItem($this->fields[static::$items_id]);

        if (isset($lastdata['end_date'])) {
            $this->fields['begin_date'] = $lastdata['end_date'];
        }
        if (isset($lastdata['cost_time'])) {
            $this->fields['cost_time'] = $lastdata['cost_time'];
        }
        if (isset($lastdata['cost_fixed'])) {
            $this->fields['cost_fixed'] = $lastdata['cost_fixed'];
        }
        if (isset($lastdata['budgets_id'])) {
            $budget_id = $lastdata['budgets_id'];
            $budget    = new Budget();
            if ($budget->getFromDB($budget_id) && $budget->fields['is_deleted'] == 0) {
                $this->fields['budgets_id'] = $budget_id;
            }
        }
        if (isset($lastdata['name'])) {
            $this->fields['name'] = $lastdata['name'];
        }
    }

    /**
     * Get total action time used on costs for an item
     *
     * @param integer $items_id ID of the item
     **/
    public function getTotalActionTimeForItem($items_id)
    {
        global $DB;

        $result = $DB->request([
            'SELECT' => ['SUM' => 'actiontime AS sumtime'],
            'FROM'   => static::getTable(),
            'WHERE'  => [static::$items_id => $items_id],
        ])->current();

        return $result['sumtime'];
    }

    /**
     * Get last datas for an item
     *
     * @param integer $items_id ID of the item
     **/
    public function getLastCostForItem($items_id)
    {
        global $DB;

        $result = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::$items_id => $items_id,
            ],
            'ORDER'  => [
                'end_date DESC',
                'id DESC',
            ],
        ])->current();
        return $result;
    }

    /**
     * Print the item cost form
     *
     * @param integer $ID ID of the item
     * @param array $options options used
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID <= 0 && !isset($options['parent']) || !($options['parent'] instanceof CommonDBTM)) {
            // parent is mandatory in new item form
            trigger_error('Parent item must be defined in `$options["parent"]`.', E_USER_WARNING);
            return false;
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $options[static::$items_id] = $options['parent']->fields["id"];
            $this->check(-1, CREATE, $options);
            $this->initBasedOnPrevious();
        }

        if ($ID > 0) {
            $items_id = $this->fields[static::$items_id];
        } else {
            $items_id = $options['parent']->fields["id"];
        }

        $item = getItemForItemtype(static::$itemtype);
        if (!$item->getFromDB($items_id)) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/management/cost.html.twig', [
            'item' => $this,
            'no_header' => true,
            'items_id_field' => static::$items_id,
            'parent_id' => $item->getID(),
            'params' => [
                'canedit' => $this->canUpdateItem(),
            ],
        ]);

        return true;
    }

    /**
     * Print the item costs
     *
     * @return false|float total cost
     **/
    public static function showForObject(CommonITILObject|Project $item): false|float
    {
        global $DB;

        $forproject = false;
        if (is_a($item, Project::class, true)) {
            $forproject = true;
        }

        $ID = $item->getID();

        if (
            !$item->getFromDB($ID)
            || !$item->canViewItem()
            || !static::canView()
        ) {
            return false;
        }
        $canedit = false;
        if (!$forproject) {
            $canedit = $item->canAddItem(self::class);
        }

        $items_ids = $ID;
        if ($forproject) {
            $alltickets = ProjectTask::getAllTicketsForProject($ID);
            $items_ids = (count($alltickets) ? $alltickets : 0);
        }
        $iterator = $DB->request([
            'SELECT' => [
                static::$items_id, 'id', 'name', 'begin_date', 'end_date', 'actiontime',
                'budgets_id', 'cost_time', 'cost_fixed', 'cost_material', 'comment',
            ],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                static::$items_id   => $items_ids,
            ],
            'ORDER'  => 'begin_date',
        ]);

        $rand   = mt_rand();

        if (
            $canedit
            && !in_array($item->fields['status'], array_merge(
                $item->getClosedStatusArray(),
                $item->getSolvedStatusArray()
            ))
        ) {
            $twig_params = [
                'cancreate' => static::canCreate(),
                'id'        => $ID,
                'rand'      => $rand,
                'type'      => static::getType(),
                'parenttype' => static::$itemtype,
                'items_id'  => static::$items_id,
                'add_new_label' => __('Add a new cost'),
            ];
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div id='viewcost{{ id }}_{{ rand }}'></div>
                <script>
                    function viewAddCost{{ id }}_{{ rand }} (btn) {
                        // Hide the triggering button
                        $(btn).hide();
                        {% do call('Ajax::updateItemJsCode', [
                            'viewcost' ~ id ~ '_' ~ rand,
                            config('root_doc') ~ '/ajax/viewsubitem.php',
                            {
                                'type': type,
                                'parenttype': parenttype,
                                (items_id): id,
                                'id': -1
                            }
                        ]) %}
                    }
                </script>
                {% if cancreate %}
                    <div class="text-center mt-1 mb-3">
                        <button type="button" class="btn btn-primary" onclick="viewAddCost{{ id }}_{{ rand }}(this);">
                            {{ add_new_label }}
                        </button>
                    </div>
                {% endif %}
TWIG, $twig_params);
        }

        $total          = 0.;
        $total_time     = 0;
        $total_costtime = 0;
        $total_fixed    = 0;
        $total_material = 0;

        if ($forproject) {
            $super_header = _n('Ticket cost', 'Ticket costs', count($iterator));
        } else {
            $super_header = sprintf(__('%1$s: %2$s'), __('Item duration'), CommonITILObject::getActionTime($item->fields['actiontime']));
        }

        $entries = [];
        $ticket = new Ticket();
        $budget = new Budget();
        $ticket_links = [];
        $budget_links = [];
        foreach ($iterator as $data) {
            $name = (empty($data['name']) ? sprintf(__('%1$s (%2$s)'), $data['name'], $data['id']) : $data['name']);

            $total_time += $data['actiontime'];
            $total_costtime += ($data['actiontime'] * $data['cost_time'] / HOUR_TIMESTAMP);
            $total_fixed += $data['cost_fixed'];
            $total_material += $data['cost_material'];
            $cost = self::computeTotalCost(
                $data['actiontime'],
                $data['cost_time'],
                $data['cost_fixed'],
                $data['cost_material']
            );
            $total += (float) $cost;
            if (!array_key_exists($data['budgets_id'], $budget_links)) {
                $budget->getFromDB($data['budgets_id']);
                $budget_links[$data['budgets_id']] = $budget->getLink();
            }
            $entry = [
                'itemtype' => static::getType(),
                'id'       => $data['id'],
                'name'     => sprintf(__s('%1$s %2$s'), htmlescape($name), Html::showToolTip($data['comment'], ['display' => false])),
                'begin_date' => $data['begin_date'],
                'end_date' => $data['end_date'],
                'budget' => $budget_links[$data['budgets_id']],
                'actiontime' => CommonITILObject::getActionTime($data['actiontime']),
                'cost_time' => $data['cost_time'],
                'cost_fixed' => $data['cost_fixed'],
                'cost_material' => $data['cost_material'],
                'totalcost' => $cost,
            ];
            if ($forproject) {
                if (!array_key_exists($data[static::$items_id], $ticket_links)) {
                    $ticket->getFromDB($data[static::$items_id]);
                    $ticket_links[$data[static::$items_id]] = $ticket->getLink();
                }
                $entry['ticket'] = $ticket_links[$data[static::$items_id]];
            }
            $entries[] = $entry;
        }

        $columns = [
            'name' => __('Name'),
            'begin_date' => __('Begin date'),
            'end_date' => __('End date'),
            'budget' => Budget::getTypeName(1),
            'actiontime' => __('Duration'),
            'cost_time' => __('Time cost'),
            'cost_fixed' => __('Fixed cost'),
            'cost_material' => __('Material cost'),
            'totalcost' => __('Total cost'),
        ];
        $footer = [
            'name' => '',
            'begin_date' => '',
            'end_date' => '',
            'budget' => '',
            'actiontime' => CommonITILObject::getActionTime($total_time),
            'cost_time' => Html::formatNumber($total_costtime),
            'cost_fixed' => Html::formatNumber($total_fixed),
            'cost_material' => Html::formatNumber($total_material),
            'totalcost' => Html::formatNumber($total),
        ];
        if ($forproject) {
            $columns = [
                'ticket' => Ticket::getTypeName(1),
            ] + $columns;
            $footer = [
                'ticket' => '',
            ] + $footer;
        }
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'datatable_costs' . $ID . $rand,
            'is_tab' => true,
            'nofilter' => true,
            'super_header' => $super_header,
            'columns' => $columns,
            'formatters' => [
                'ticket' => 'raw_html',
                'name' => 'raw_html',
                'begin_date' => 'date',
                'end_date' => 'date',
                'budget' => 'raw_html',
                'cost_time' => 'number',
                'cost_fixed' => 'number',
                'cost_material' => 'number',
                'totalcost' => 'number',
            ],
            'footers' => [$footer],
            'entries' => $entries,
            'row_class' => $canedit ? 'cursor-pointer' : '',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
        if ($canedit) {
            $cost_class = static::class;
            $parent_class = static::$itemtype;
            $items_id_field = static::$items_id;
            echo Html::scriptBlock("
                $(() => {
                    $('#datatable_costs{$ID}{$rand}').on('click', 'tbody tr', (e) => {
                        const cost_id = $(e.currentTarget).data('id');
                        if (cost_id) {
                            $('#viewcost{$ID}_{$rand}').load('/ajax/viewsubitem.php',{
                                type: '" . jsescape($cost_class) . "',
                                parenttype: '" . jsescape($parent_class) . "',
                                '" . jsescape($items_id_field) . "': $ID,
                                id: cost_id
                            });
                        }
                    });
                });
            ");
        }
        return $total;
    }

    /**
     * Get costs summary values
     *
     * @param string $type type
     * @param integer $ID ID of the ticket
     *
     * @return array of costs and actiontime
     * @used-by src/NotificationTargetCommonITILObject.php
     **/
    public static function getCostsSummary($type, $ID)
    {
        global $DB;

        $result = $DB->request(
            [
                'SELECT'    => [
                    'actiontime',
                    'cost_time',
                    'cost_fixed',
                    'cost_material',
                ],
                'FROM'      => getTableForItemType($type),
                'WHERE'     => [
                    static::$items_id      => $ID,
                ],
                'ORDER'     => [
                    'begin_date',
                ],
            ]
        );

        $tab = ['totalcost'   => 0,
            'actiontime'   => 0,
            'costfixed'    => 0,
            'costtime'     => 0,
            'costmaterial' => 0,
        ];

        foreach ($result as $data) {
            $tab['actiontime']   += $data['actiontime'];
            $tab['costfixed']    += $data['cost_fixed'];
            $tab['costmaterial'] += $data['cost_material'];
            $tab['costtime']     += ($data['actiontime'] * $data['cost_time'] / HOUR_TIMESTAMP);
            $tab['totalcost']    +=  self::computeTotalCost(
                $data['actiontime'],
                $data['cost_time'],
                $data['cost_fixed'],
                $data['cost_material']
            );
        }
        foreach ($tab as $key => $val) {
            $tab[$key] = Html::formatNumber($val);
        }
        return $tab;
    }


    /**
     * Computer total cost of a item
     *
     * @param float $actiontime actiontime
     * @param float $cost_time time cost
     * @param float $cost_fixed fixed cost
     * @param float $cost_material material cost
     * @param boolean $edit used for edit of computation ? (true by default)
     *
     * @return string total cost formatted string
     **/
    public static function computeTotalCost($actiontime, $cost_time, $cost_fixed, $cost_material, $edit = true)
    {
        $cost = ($actiontime * $cost_time / HOUR_TIMESTAMP) + $cost_fixed + $cost_material;
        return Html::formatNumber($cost, $edit);
    }
}
