<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
 * SoftwareVersion Class
 **/
class SoftwareVersion extends CommonDBChild
{
   // From CommonDBTM
    public $dohistory = true;

   // From CommonDBChild
    public static $itemtype  = 'Software';
    public static $items_id  = 'softwares_id';

    protected $displaylist = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Version', 'Versions', $nb);
    }


    public function cleanDBonPurge()
    {

        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_SoftwareVersion::class,
            ]
        );
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Item_SoftwareVersion', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    /**
     * @since 0.84
     *
     * @see CommonDBTM::getPreAdditionalInfosForName
     **/
    public function getPreAdditionalInfosForName()
    {

        $soft = new Software();
        if ($soft->getFromDB($this->fields['softwares_id'])) {
            return $soft->getName();
        }
        return '';
    }


    /**
     * Print the Software / version form
     *
     * @param $ID        integer  Id of the version or the template to print
     * @param $options   array    of possible options:
     *     - target form target
     *     - softwares_id ID of the software for add process
     *
     * @return true if displayed  false if item not found or not right to display
     *
     **/
    public function showForm($ID, array $options = [])
    {
        if ($ID > 0) {
            $this->check($ID, READ);
            $softwares_id = $this->fields['softwares_id'];
        } else {
            $softwares_id = $options['softwares_id'];
            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . _n('Software', 'Software', Session::getPluralNumber()) . "</td>";
        echo "<td>";
        if ($this->isNewID($ID)) {
            echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
        }
        echo "<a href='" . Software::getFormURLWithID($softwares_id) . "'>" .
             Dropdown::getDropdownName("glpi_softwares", $softwares_id) . "</a>";
        echo "</td>";
        echo "<td rowspan='4' class='middle'>" . __('Comments') . "</td>";
        echo "<td class='center middle' rowspan='4'>";
        echo "<textarea class='form-control' rows='3' name='comment' >" . $this->fields["comment"];
        echo "</textarea></td></tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . OperatingSystem::getTypeName(1) . "</td><td>";
        OperatingSystem::dropdown(['value' => $this->fields["operatingsystems_id"]]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Status') . "</td><td>";
        State::dropdown(['value'     => $this->fields["states_id"],
            'entity'    => $this->fields["entities_id"],
            'condition' => ['is_visible_softwareversion' => 1]
        ]);
        echo "</td></tr>\n";

       // Only count softwareversions_id_buy (don't care of softwareversions_id_use if no installation)
        if (
            (SoftwareLicense::countForVersion($ID) > 0)
            || (Item_SoftwareVersion::countForVersion($ID) > 0)
        ) {
            $options['candel'] = false;
        }

        $this->showFormButtons($options);

        return true;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_operatingsystems',
            'field'              => 'name',
            'name'               => OperatingSystem::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_states',
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => ['is_visible_softwareversion' => 1]
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        return $tab;
    }


    /**
     * Make a select box for  software to install
     *
     * @param $options array of possible options:
     *    - name          : string / name of the select (default is softwareversions_id)
     *    - softwares_id  : integer / ID of the software (mandatory)
     *    - value         : integer / value of the selected version
     *    - used          : array / already used items
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     **/
    public static function dropdownForOneSoftware($options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

       //$softwares_id,$value=0
        $p['softwares_id']          = 0;
        $p['value']                 = 0;
        $p['name']                  = 'softwareversions_id';
        $p['used']                  = [];
        $p['display_emptychoice']   = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

       // Make a select box
        $criteria = [
            'SELECT'    => [
                'glpi_softwareversions.*',
                'glpi_states.name AS sname'
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_softwareversions',
            'LEFT JOIN' => [
                'glpi_states'  => [
                    'ON' => [
                        'glpi_softwareversions' => 'states_id',
                        'glpi_states'           => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_softwareversions.softwares_id'   => $p['softwares_id']
            ],
            'ORDERBY'   => 'name'
        ];

        if (count($p['used'])) {
            $criteria['WHERE']['NOT'] = ['glpi_softwareversions.id' => $p['used']];
        }

        $iterator = $DB->request($criteria);

        $values = [];
        foreach ($iterator as $data) {
            $ID     = $data['id'];
            $output = $data['name'];

            if (empty($output) || $_SESSION['glpiis_ids_visible']) {
                $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
            }
            if (!empty($data['sname'])) {
                $output = sprintf(__('%1$s - %2$s'), $output, $data['sname']);
            }
            $values[$ID] = $output;
        }
        return Dropdown::showFromArray($p['name'], $values, $p);
    }


    /**
     * Show Versions of a software
     *
     * @param $soft Software object
     *
     * @return void
     **/
    public static function showForSoftware(Software $soft)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $softwares_id = $soft->getField('id');

        if (!$soft->can($softwares_id, READ)) {
            return false;
        }
        $canedit = $soft->canEdit($softwares_id);

        echo "<div class='spaced'>";

        if ($canedit) {
            echo "<div class='center firstbloc'>";
            echo "<a class='btn btn-primary' href='" . SoftwareVersion::getFormURL() . "?softwares_id=$softwares_id'>" .
                _x('button', 'Add a version') . "</a>";
            echo "</div>";
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_softwareversions.*',
                'glpi_states.name AS sname'
            ],
            'FROM'      => 'glpi_softwareversions',
            'LEFT JOIN' => [
                'glpi_states'  => [
                    'ON' => [
                        'glpi_softwareversions' => 'states_id',
                        'glpi_states'           => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'softwares_id' => $softwares_id
            ],
            'ORDERBY'   => 'name'
        ]);

        Session::initNavigateListItems(
            'SoftwareVersion',
            //TRANS : %1$s is the itemtype name,
            //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(
                                         __('%1$s = %2$s'),
                                         Software::getTypeName(1),
                                         $soft->getName()
                                     )
        );

        if (count($iterator)) {
            echo "<table class='table border table-striped'><tr>";
            echo "<th>" . self::getTypeName(Session::getPluralNumber()) . "</th>";
            echo "<th>" . __('Status') . "</th>";
            echo "<th>" . OperatingSystem::getTypeName(1) . "</th>";
            echo "<th>" . _n('Architecture', 'Architectures', 1) . "</th>";
            echo "<th>" . _n('Installation', 'Installations', Session::getPluralNumber()) . "</th>";
            echo "<th>" . __('Comments') . "</th>";
            echo "</tr>\n";

            $tot = 0;
            foreach ($iterator as $data) {
                Session::addToNavigateListItems('SoftwareVersion', $data['id']);
                $nb = Item_SoftwareVersion::countForVersion($data['id']);

                echo "<tr class='tab_bg_2'>";
                echo "<td><a href='" . SoftwareVersion::getFormURLWithID($data['id']) . "'>";
                echo $data['name'] . (empty($data['name']) ? "(" . $data['id'] . ")" : "") . "</a></td>";
                echo "<td>" . $data['sname'] . "</td>";
                echo "<td>" . Dropdown::getDropdownName(
                    'glpi_operatingsystems',
                    $data['operatingsystems_id']
                );
                echo "</td>";
                echo "<td>{$data['arch']}</td>";
                echo "<td>$nb</td>";
                echo "<td>" . nl2br($data['comment'] ?? "") . "</td></tr>\n";

                $tot += $nb;
            }

            echo "<tfoot>";
            echo "<tr class='tab_bg_1 noHover'><td class='right b' colspan='4'>" . __('Total') . "</td>";
            echo "<td class='b'>$tot</td><td></td>";
            echo "</tr>";
            echo "</tfoot>";
            echo "</table>";
        } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>\n";
        }

        echo "</div>";
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch (get_class($item)) {
                case Software::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable($this->getTable(), ['softwares_id' => $item->getID()]);
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == 'Software') {
            self::showForSoftware($item);
        }
        return true;
    }
}
