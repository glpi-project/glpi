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

/// ContractCost class
/// since version 0.84
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


    public function prepareInputForAdd($input)
    {

        if (
            !empty($input['begin_date'])
            && (empty($input['end_date'])
              || ($input['end_date'] == 'NULL')
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
              || ($input['end_date'] == 'NULL')
              || ($input['end_date'] < $input['begin_date']))
        ) {
            $input['end_date'] = $input['begin_date'];
        }

        return parent::prepareInputForUpdate($input);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        // can exists for template
        if (
            $item instanceof Contract
            && Contract::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable('glpi_contractcosts', ['contracts_id' => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

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
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Title'),
            'searchtype'         => 'contains',
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
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'begin_date',
            'name'               => __('Begin date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'end_date',
            'name'               => __('End date'),
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
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



    /**
     * Init cost for creation based on previous cost
     **/
    public function initBasedOnPrevious()
    {

        $contract = new Contract();
        if (
            !isset($this->fields['contracts_id'])
            || !$contract->getFromDB($this->fields['contracts_id'])
        ) {
            return false;
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
     * @param $contracts_id        integer  ID of the contract
     **/
    public function getLastCostForContract($contracts_id)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => $this->getTable(),
            'WHERE'  => ['contracts_id' => $contracts_id],
            'ORDER'  => ['end_date DESC', 'id DESC'],
        ]);
        if ($result = $iterator->current()) {
            return $result;
        }

        return [];
    }

    /**
     * Print the contract cost form
     *
     * @param $ID        integer  ID of the item
     * @param $options   array    options used
     **/
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

        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo "<input type='hidden' name='contracts_id' value='" . $this->fields['contracts_id'] . "'>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";
        echo "<td>" . _n('Cost', 'Costs', 1) . "</td>";
        echo "<td>";
        echo "<input type='text' name='cost' value='" . Html::formatNumber($this->fields["cost"], true) . "'
             size='14'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Begin date') . "</td>";
        echo "<td>";
        Html::showDateField("begin_date", ['value' => $this->fields['begin_date']]);
        echo "</td>";
        $rowspan = 3;
        echo "<td rowspan='$rowspan'>" . __('Comments') . "</td>";
        echo "<td rowspan='$rowspan' class='middle'>";
        echo "<textarea class='form-control' name='comment' >" . $this->fields["comment"] .
           "</textarea>";
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('End date') . "</td>";
        echo "<td>";
        Html::showDateField("end_date", ['value' => $this->fields['end_date']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . Budget::getTypeName(1) . "</td>";
        echo "<td>";
        Budget::dropdown(['value' => $this->fields["budgets_id"]]);
        echo "</td></tr>";

        $this->showFormButtons($options);

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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID = $contract->fields['id'];

        if (
            !$contract->getFromDB($ID)
            || !$contract->can($ID, READ)
        ) {
            return;
        }
        $canedit = $contract->can($ID, UPDATE);

        echo "<div class='center'>";

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['contracts_id' => $ID],
            'ORDER'  => 'begin_date',
        ]);
        $rand   = mt_rand();

        if (
            $canedit
            && ($withtemplate != 2)
        ) {
            echo "<div id='viewcost" . $ID . "_$rand'></div>\n";
            echo "<script type='text/javascript' >\n";
            echo "function viewAddCost" . $ID . "_$rand() {\n";
            $params = ['type'         => __CLASS__,
                'parenttype'   => 'Contract',
                'contracts_id' => $ID,
                'id'           => -1,
            ];
            Ajax::updateItemJsCode(
                "viewcost" . $ID . "_$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='javascript:viewAddCost" . $ID . "_$rand();'>";
            echo __('Add a new cost') . "</a></div>\n";
        }

        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='5'>" . self::getTypeName(count($iterator)) . "</th></tr>";

        if (count($iterator)) {
            echo "<tr><th>" . __('Name') . "</th>";
            echo "<th>" . __('Begin date') . "</th>";
            echo "<th>" . __('End date') . "</th>";
            echo "<th>" . Budget::getTypeName(1) . "</th>";
            echo "<th>" . _n('Cost', 'Costs', 1) . "</th>";
            echo "</tr>";

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Contract::getTypeName(1),
                    $contract->getName()
                )
            );

            $total = 0;
            foreach ($iterator as $data) {
                echo "<tr class='tab_bg_2' " .
                  ($canedit
                     ? "style='cursor:pointer' onClick=\"viewEditCost" . $data['contracts_id'] . "_" .
                     $data['id'] . "_$rand();\"" : '') . ">";
                $name = (empty($data['name']) ? sprintf(
                    __('%1$s (%2$s)'),
                    $data['name'],
                    $data['id']
                )
                                         : $data['name']);
                echo "<td>";
                printf(
                    __('%1$s %2$s'),
                    $name,
                    Html::showToolTip($data['comment'], ['display' => false])
                );
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditCost" . $data['contracts_id'] . "_" . $data["id"] . "_$rand() {\n";
                    $params = ['type'         => __CLASS__,
                        'parenttype'   => 'Contract',
                        'contracts_id' => $data["contracts_id"],
                        'id'           => $data["id"],
                    ];
                    Ajax::updateItemJsCode(
                        "viewcost" . $ID . "_$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo "</td>";
                echo "<td>" . Html::convDate($data['begin_date']) . "</td>";
                echo "<td>" . Html::convDate($data['end_date']) . "</td>";
                echo "<td>" . Dropdown::getDropdownName('glpi_budgets', $data['budgets_id']) . "</td>";
                echo "<td class='numeric'>" . Html::formatNumber($data['cost']) . "</td>";
                $total += $data['cost'];
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo "<tr class='b noHover'><td colspan='3'>&nbsp;</td>";
            echo "<td class='right'>" . __('Total cost') . '</td>';
            echo "<td class='numeric'>" . Html::formatNumber($total) . '</td></tr>';
        } else {
            echo "<tr><th colspan='5'>" . __('No item found') . "</th></tr>";
        }
        echo "</table>";
        echo "</div><br>";
    }
}
