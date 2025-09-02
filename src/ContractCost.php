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
 * ContractCost Class
 * @since 0.84
 */
class ContractCost extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'Contract';
    public static $items_id = 'contracts_id';
    public $dohistory       = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Cost', 'Costs', $nb);
    }

    public static function getIcon()
    {
        return Infocom::getIcon();
    }

    public function prepareInputForAdd($input)
    {

        if (
            !empty($input['begin_date'])
            && (empty($input['end_date'])
              || ($input['end_date'] === 'NULL')
              || ($input['end_date'] < $input['begin_date']))
        ) {
            $input['end_date'] = $input['begin_date'];
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {

        if (
            !empty($input['begin_date'])
            && (empty($input['end_date'])
              || ($input['end_date'] === 'NULL')
              || ($input['end_date'] < $input['begin_date']))
        ) {
            $input['end_date'] = $input['begin_date'];
        }

        return parent::prepareInputForUpdate($input);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        // can exist for template
        if (
            $item instanceof Contract
            && Contract::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable('glpi_contractcosts', ['contracts_id' => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof Contract) {
            return false;
        }

        self::showForContract($item, $withtemplate);
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
            'id'                 => '14',
            'table'              => static::getTable(),
            'field'              => 'cost',
            'name'               => _n('Cost', 'Costs', 1),
            'datatype'           => 'decimal',
        ];

        $tab[] = [
            'id'                 => '18',
            'table'              => 'glpi_budgets',
            'field'              => 'name',
            'name'               => Budget::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    public function initBasedOnPrevious(): void
    {
        $contract = new Contract();
        if (
            !isset($this->fields['contracts_id'])
            || !$contract->getFromDB($this->fields['contracts_id'])
        ) {
            return;
        }

        $lastdata = $this->getLastCostForContract($this->fields['contracts_id']);

        if (isset($lastdata['end_date'])) {
            $this->fields['begin_date'] = $lastdata['end_date'];
        }
        if (isset($lastdata['cost'])) {
            $this->fields['cost'] = $lastdata['cost'];
        }
        if (isset($lastdata['name'])) {
            $this->fields['name'] = $lastdata['name'];
        }
        if (isset($lastdata['budgets_id'])) {
            $this->fields['budgets_id'] = $lastdata['budgets_id'];
        }
    }

    /**
     * Get last datas for a contract
     *
     * @param integer $contracts_id ID of the contract
     * @return array
     **/
    public function getLastCostForContract($contracts_id)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => ['contracts_id' => $contracts_id],
            'ORDER'  => ['end_date DESC', 'id DESC'],
        ]);
        if ($result = $iterator->current()) {
            return $result;
        }

        return [];
    }

    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $options['contracts_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
            $this->initBasedOnPrevious();
        }

        TemplateRenderer::getInstance()->display('pages/management/cost.html.twig', [
            'item' => $this,
            'no_header' => true,
            'items_id_field' => static::$items_id,
            'parent_id' => $this->fields['contracts_id'],
            'params' => [
                'canedit' => $this->canUpdateItem(),
            ],
        ]);

        return true;
    }

    /**
     * Print the contract costs
     *
     * @param Contract $contract
     * @param integer  $withtemplate Template or basic item
     *
     * @return void
     **/
    public static function showForContract(Contract $contract, $withtemplate = 0)
    {
        global $DB;

        $ID = $contract->fields['id'];

        if (
            !$contract->getFromDB($ID)
            || !$contract->can($ID, READ)
        ) {
            return;
        }
        $canedit = $contract->can($ID, UPDATE);

        $sort = $_GET['sort'] ?: 'begin_date';
        $order = $_GET['order'] ?: 'ASC';

        if ($sort === 'budgets_id') {
            $sort = 'begin_date';
            $order = 'ASC';
        }

        $criteria = [
            'FROM'   => self::getTable(),
            'WHERE'  => ['contracts_id' => $ID],
            'ORDER'  => ["$sort $order"],
        ];
        $iterator = $DB->request($criteria);
        $rand   = mt_rand();

        if (
            $canedit
            && ($withtemplate != 2)
        ) {
            $twig_params = [
                'item' => $contract,
                'rand' => $rand,
                'button_msg' => __('Add a new cost'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center">
                    <button class="btn btn-primary" onclick="showCost{{ rand }}(-1)">{{ button_msg }}</button>
                </div>
                <div id="viewsubitem{{ rand }}" class="mb-3"></div>
                <script>
                    function showCost{{ rand }}(subitems_id) {
                        $.ajax({
                            url: '{{ config('root_doc') }}/ajax/viewsubitem.php',
                            method: 'POST',
                            data: {
                                type: 'ContractCost',
                                parenttype: '{{ item.getType()|e('js') }}',
                                contracts_id: {{ item.getID() }},
                                id: subitems_id
                            },
                            success: (data) => {
                                $('#viewsubitem{{ rand }}').html(data);
                            }
                        });
                    }
                    $(() => {
                        $('#contractcostlist{{ rand }} tbody tr').on('click', function() {
                            showCost{{ rand }}($(this).attr('data-id'));
                        });
                    });
                </script>
TWIG, $twig_params);
        }

        $entries = [];
        $budget_cache = [];
        foreach ($iterator as $data) {
            $name = empty($data['name']) ? sprintf(
                __('%1$s (%2$s)'),
                $data['name'],
                $data['id']
            ) : $data['name'];
            $name = sprintf(
                __s('%1$s %2$s'),
                htmlescape($name),
                Html::showToolTip(htmlescape($data['comment']), ['display' => false])
            );
            if (!isset($budget_cache[$data['budgets_id']])) {
                $budget_cache[$data['budgets_id']] = Dropdown::getDropdownName(table: 'glpi_budgets', id: $data['budgets_id'], default: '');
            }
            $entries[] = [
                'itemtype' => self::class,
                'id' => $data['id'],
                'row_class' => $canedit ? 'cursor-pointer' : '',
                'name' => $name,
                'begin_date' => $data['begin_date'],
                'end_date' => $data['end_date'],
                'budgets_id' => $budget_cache[$data['budgets_id']],
                'cost' => $data['cost'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'contractcostlist' . $rand,
            'is_tab' => true,
            'nofilter' => true,
            'sort' => $sort,
            'order' => $order,
            'columns' => [
                'name' => __('Name'),
                'begin_date' => __('Begin date'),
                'end_date' => __('End date'),
                'budgets_id' => Budget::getTypeName(1),
                'cost' => _n('Cost', 'Costs', 1),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'begin_date' => 'date',
                'end_date' => 'date',
                'cost' => 'number',
            ],
            'footers' => [
                [
                    '',
                    '',
                    '',
                    __('Total cost'),
                    array_sum(array_column($entries, 'cost')),
                ],
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);
    }
}
