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
 * @since 9.1
 */


class ComputerAntivirus extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype = 'Computer';
    public static $items_id = 'computers_id';
    public $dohistory       = true;



    public static function getTypeName($nb = 0)
    {
        return _n('Antivirus', 'Antiviruses', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

       // can exists for template
        if (
            ($item->getType() == 'Computer')
            && Computer::canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    'glpi_computerantiviruses',
                    ["computers_id" => $item->getID(), 'is_deleted' => 0 ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForComputer($item, $withtemplate);
        return true;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
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
            'field'              => 'antivirus_version',
            'name'               => _n('Version', 'Versions', 1),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'signature_version',
            'name'               => __('Signature database version'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        return $tab;
    }


    public static function rawSearchOptionsToAdd()
    {
        $tab = [];
        $name = _n('Antivirus', 'Antiviruses', Session::getPluralNumber());

        $tab[] = [
            'id'                 => 'antivirus',
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '167',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'name',
            'name'               => __('Name'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '168',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'antivirus_version',
            'name'               => _n('Version', 'Versions', 1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '169',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'is_active',
            'linkfield'          => '',
            'name'               => __('Active'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'searchtype'         => ['equals']
        ];

        $tab[] = [
            'id'                 => '170',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'is_uptodate',
            'linkfield'          => '',
            'name'               => __('Is up to date'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'searchtype'         => ['equals']
        ];

        $tab[] = [
            'id'                 => '171',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'signature_version',
            'name'               => __('Signature database version'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'text',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '172',
            'table'              => 'glpi_computerantiviruses',
            'field'              => 'date_expiration',
            'name'               => __('Expiration date'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'date',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        return $tab;
    }

    /**
     * Display form for antivirus
     *
     * @param integer $ID      id of the antivirus
     * @param array   $options
     *
     * @return boolean TRUE if form is ok
     **/
    public function showForm($ID, array $options = [])
    {

        if (!Session::haveRight("computer", READ)) {
            return false;
        }

        $comp = new Computer();
        if ($ID > 0) {
            $this->check($ID, READ);
            $comp->getFromDB($this->fields['computers_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $comp->getFromDB($options['computers_id']);
        }

        $this->showFormHeader($options);

        if ($this->isNewID($ID)) {
            echo "<input type='hidden' name='computers_id' value='" . $options['computers_id'] . "'>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . Computer::getTypeName(1) . "</td>";
        echo "<td>" . $comp->getLink() . "</td>";
        $this->autoinventoryInformation();
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input('name', ['value' => $this->fields['name']]);
        echo "</td>";
        echo "<td>" . __('Active') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . Manufacturer::getTypeName(1) . "</td>";
        echo "<td>";
        Dropdown::show('Manufacturer', ['value' => $this->fields["manufacturers_id"]]);
        echo "</td>";
        echo "<td>" . __('Up to date') . "</td>";
        echo "<td>";
        Dropdown::showYesNo('is_uptodate', $this->fields['is_uptodate']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Antivirus version') . "</td>";
        echo "<td>";
        echo Html::input('antivirus_version', ['value' => $this->fields['antivirus_version']]);
        echo "</td>";
        echo "<td>" . __('Signature database version') . "</td>";
        echo "<td>";
        echo Html::input('signature_version', ['value' => $this->fields['signature_version']]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Expiration date') . "</td>";
        echo "<td>";
        Html::showDateField("date_expiration", ['value' => $this->fields['date_expiration']]);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        $options['canedit'] = Session::haveRight("computer", UPDATE);
        $this->showFormButtons($options);

        return true;
    }


    /**
     * Print the computers antiviruses
     *
     * @param Computer $comp          Computer object
     * @param integer  $withtemplate  Template or basic item (default 0)
     *
     * @return void
     **/
    public static function showForComputer(Computer $comp, $withtemplate = 0)
    {
        global $DB;

        $ID = $comp->fields['id'];

        if (
            !$comp->getFromDB($ID)
            || !$comp->can($ID, READ)
        ) {
            return;
        }
        $canedit = $comp->canEdit($ID);

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='" . ComputerAntivirus::getFormURL() . "?computers_id=$ID&amp;withtemplate=" .
                  $withtemplate . "'>";
            echo __('Add an antivirus');
            echo "</a></div>\n";
        }

        echo "<div class='spaced center table-responsive'>";

        $result = $DB->request(
            [
                'FROM'  => ComputerAntivirus::getTable(),
                'WHERE' => [
                    'computers_id' => $ID,
                    'is_deleted'   => 0,
                ],
            ]
        );

        echo "<table class='tab_cadre_fixehov'>";
        $colspan = 8;
        echo "<tr class='noHover'><th colspan='$colspan'>" . self::getTypeName($result->numrows()) .
           "</th></tr>";

        if ($result->numrows() != 0) {
            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . __('Automatic inventory') . "</th>";
            $header .= "<th>" . Manufacturer::getTypeName(1) . "</th>";
            $header .= "<th>" . __('Antivirus version') . "</th>";
            $header .= "<th>" . __('Signature database version') . "</th>";
            $header .= "<th>" . __('Active') . "</th>";
            $header .= "<th>" . __('Up to date') . "</th>";
            $header .= "<th>" . __('Expiration date') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(
                                            __('%1$s = %2$s'),
                                            Computer::getTypeName(1),
                                            $comp->getName()
                                        )
            );

            $antivirus = new self();
            foreach ($result as $data) {
                 $antivirus->getFromDB($data['id']);
                 echo "<tr class='tab_bg_2'>";
                 echo "<td>" . $antivirus->getLink() . "</td>";
                 echo "<td>" . Dropdown::getYesNo($data['is_dynamic']) . "</td>";
                 echo "<td>";
                if ($data['manufacturers_id']) {
                    echo Dropdown::getDropdownName(
                        'glpi_manufacturers',
                        $data['manufacturers_id']
                    ) . "</td>";
                } else {
                    echo "</td>";
                }
                echo "<td>" . $data['antivirus_version'] . "</td>";
                echo "<td>" . $data['signature_version'] . "</td>";
                echo "<td>" . Dropdown::getYesNo($data['is_active']) . "</td>";
                echo "<td>" . Dropdown::getYesNo($data['is_uptodate']) . "</td>";
                echo "<td>" . Html::convDate($data['date_expiration']) . "</td>";
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        // Clear date if empty to avoid SQL error
        if (empty($input['date_expiration'])) {
            unset($input['date_expiration']);
        }

        return $input;
    }


    public static function getIcon()
    {
        return "ti ti-virus-search";
    }
}
