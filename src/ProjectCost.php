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

/// ProjectCost class
/// since version 0.85
class ProjectCost extends CommonDBChild
{
    // From CommonDBChild
    public static $itemtype = 'Project';
    public static $items_id = 'projects_id';
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
            empty($input['end_date'])
            || ($input['end_date'] === 'NULL')
            || ($input['end_date'] < $input['begin_date'])
        ) {
            $input['end_date'] = $input['begin_date'];
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (
            empty($input['end_date'])
            || ($input['end_date'] === 'NULL')
            || ($input['end_date'] < $input['begin_date'])
        ) {
            $input['end_date'] = $input['begin_date'];
        }

        return parent::prepareInputForUpdate($input);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // can exist for template
        if (($item::class === Project::class) && Project::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable('glpi_projectcosts', ['projects_id' => $item->getID()]);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Project) {
            return self::showForProject($item);
        }
        return false;
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
        $ticket = new Ticket();
        if (
            !isset($this->fields['projects_id'])
            || !$ticket->getFromDB($this->fields['projects_id'])
        ) {
            return;
        }

        $lastdata = $this->getLastCostForProject($this->fields['projects_id']);

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
     * Get last datas for a project
     *
     * @param integer $projects_id ID of the project
     **/
    public function getLastCostForProject($projects_id)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => ['projects_id' => $projects_id],
            'ORDER'  => ['end_date DESC', 'id DESC'],
        ]);

        if (count($iterator)) {
            return $iterator->current();
        }

        return [];
    }

    /**
     * Print the project cost form
     *
     * @param integer $ID ID of the item
     * @param array $options options used
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $options['projects_id'] = $options['parent']->getField('id');
            $this->check(-1, CREATE, $options);
            $this->initBasedOnPrevious();
        }

        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __s('Name') . "</td>";
        echo "<td>";
        echo "<input type='hidden' name='projects_id' value='" . ((int) $this->fields['projects_id']) . "'>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";
        echo "<td>" . _sn('Cost', 'Costs', 1) . "</td>";
        echo "<td>";
        echo "<input type='text' name='cost' value='" . htmlescape(Html::formatNumber($this->fields["cost"], true)) . "'
             size='14'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __s('Begin date') . "</td>";
        echo "<td>";
        Html::showDateField("begin_date", ['value' => $this->fields['begin_date']]);
        echo "</td>";
        $rowspan = 3;
        echo "<td rowspan='$rowspan'>" . __s('Comments') . "</td>";
        echo "<td rowspan='$rowspan' class='middle'>";
        echo "<textarea class='form-control' rows='" . ($rowspan + 3) . "' name='comment' >" . htmlescape($this->fields["comment"])
           . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __s('End date') . "</td>";
        echo "<td>";
        Html::showDateField("end_date", ['value' => $this->fields['end_date']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Budget::getTypeName(1)) . "</td>";
        echo "<td>";
        Budget::dropdown(['value' => $this->fields["budgets_id"]]);
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Print the project costs
     *
     * @param Project $project      object
     * @param int     $withtemplate Template or basic item (default 0)
     *
     * @return bool
     **/
    public static function showForProject(Project $project, $withtemplate = 0): bool
    {
        global $CFG_GLPI, $DB;

        $ID = $project->getID();

        if (
            !$project->getFromDB($ID)
            || !$project->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $project->can($ID, UPDATE);

        echo "<div class='center'>";

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['projects_id' => $ID],
            'ORDER'  => ['begin_date'],
        ]);

        $rand   = mt_rand();

        if ($canedit) {
            echo "<div id='viewcost" . $ID . "_$rand'></div>\n";
            echo "<script type='text/javascript' >\n";
            echo "function viewAddCost" . $ID . "_$rand() {\n";
            $params = ['type'         => self::class,
                'parenttype'   => 'Project',
                'projects_id' => $ID,
                'id'           => -1,
            ];
            Ajax::updateItemJsCode(
                "viewcost" . $ID . "_$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>";
            echo "<div class='center firstbloc'>"
               . "<a class='btn btn-primary' href='javascript:viewAddCost" . $ID . "_$rand();'>";
            echo __s('Add a new cost') . "</a></div>";
        }
        $total = 0;
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='5'>" . htmlescape(self::getTypeName(count($iterator)))
            . "</th></tr>";

        if (count($iterator)) {
            echo "<tr><th>" . __s('Name') . "</th>";
            echo "<th>" . __s('Begin date') . "</th>";
            echo "<th>" . __s('End date') . "</th>";
            echo "<th>" . htmlescape(Budget::getTypeName(1)) . "</th>";
            echo "<th>" . _sn('Cost', 'Costs', 1) . "</th>";
            echo "</tr>";

            Session::initNavigateListItems(
                self::class,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Project::getTypeName(1),
                    $project->getName()
                )
            );

            foreach ($iterator as $data) {
                $cost_id = (int) $data['id'];
                $project_id = (int) $data['projects_id'];

                echo "<tr class='tab_bg_2' "
                    . ($canedit ? "style='cursor:pointer' onClick=\"viewEditCost" . $project_id . "_" . $cost_id . "_$rand();\"" : '')
                    . ">";

                $name = empty($data['name'])
                    ? sprintf(
                        __('%1$s (%2$s)'),
                        $data['name'],
                        $cost_id
                    )
                    : $data['name'];
                echo "<td>";
                printf(
                    __s('%1$s %2$s'),
                    htmlescape($name),
                    Html::showToolTip(htmlescape($data['comment']), ['display' => false])
                );
                if ($canedit) {
                    $js = "function viewEditCost" . $project_id . "_" . $cost_id . "_$rand() {";
                    $js .= Ajax::updateItemJsCode(
                        toupdate: "viewcost" . $ID . "_$rand",
                        url: $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        parameters: [
                            'type'        => self::class,
                            'parenttype'  => 'Project',
                            'projects_id' => $project_id,
                            'id'          => $cost_id,
                            'display'     => false,
                        ],
                        display: false
                    );
                    $js .=  "};";

                    echo Html::scriptBlock($js);
                }
                echo "</td>";
                echo "<td>" . htmlescape(Html::convDate($data['begin_date'])) . "</td>";
                echo "<td>" . htmlescape(Html::convDate($data['end_date'])) . "</td>";
                echo "<td>" . htmlescape(Dropdown::getDropdownName('glpi_budgets', $data['budgets_id'])) . "</td>";
                echo "<td class='numeric'>" . htmlescape(Html::formatNumber($data['cost'])) . "</td>";
                $total += (float) $data['cost'];
                echo "</tr>";
                Session::addToNavigateListItems(self::class, $cost_id);
            }
            echo "<tr class='b noHover'><td colspan='3'>&nbsp;</td>";
            echo "<td class='right'>" . __s('Total cost') . '</td>';
            echo "<td class='numeric'>" . htmlescape(Html::formatNumber($total)) . '</td></tr>';
        } else {
            echo "<tr><th colspan='5'>" . __s('No results found') . "</th></tr>";
        }
        echo "</table>";
        echo "</div>";
        echo "<div>";
        $ticketcost = TicketCost::showForObject($project);
        echo "</div>";
        echo "<div class='b'>";
        printf(__s('%1$s: %2$s'), __s('Total cost'), $total + $ticketcost);
        echo "</div>";

        return true;
    }
}
