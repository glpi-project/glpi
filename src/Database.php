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
 * Database Class
 **/
class Database extends CommonDBChild
{
   // From CommonDBTM
    public $auto_message_on_action = true;
    public static $rightname       = 'database';
    public static $mustBeAttached  = false;

   // From CommonDBChild
    public static $itemtype = 'DatabaseInstance';
    public static $items_id = 'databaseinstances_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Database', 'Databases', $nb);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Domain_Item', $ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
        return $ong;
    }


    public function showForm($ID, array $options = [])
    {
        $rand = mt_rand();
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";

        echo "<td><label for='textfield_name$rand'>" . __('Name') . "</label></td>";
        echo "<td>";
        echo Html::input(
            'name',
            [
                'value' => $this->fields['name'],
                'id'    => "textfield_name$rand",
            ]
        );
        echo "</td>";
        echo "<td><label for='is_active$rand'>" . __('Is active') . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active']);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        $database = new DatabaseInstance();
        $database->getFromDB($this->fields['databaseinstances_id']);
        echo "<tr>";
        echo "<td>" . DatabaseInstance::getTypeName(1) . "</td>";
        echo "<td>";
        if (isset($_REQUEST['databaseinstances_id']) && !empty($_REQUEST['databaseinstances_id'])) {
            echo $database->getLink();
            echo Html::hidden('databaseinstances_id', ['value' => $this->fields['databaseinstances_id']]);
        } else {
            $database::dropdown(['value' => $this->fields['databaseinstances_id']]);
        }
        echo "</td>";
        echo "<td><label for='size$rand'>" . sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio')) . "</label></td>";
        echo "<td>";
        echo Html::input(
            'size',
            [
                'id' => 'size' . $rand,
                'type' => 'number',
                'value' => $this->fields['size']
            ]
        );
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='is_onbackup$rand'>" . __('Has backup') . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo('is_onbackup', $this->fields['is_onbackup']);
        echo "</td>";
        echo "<td><label for='date_lastbackup$rand'>" . __('Last backup date') . "</label></td>";
        echo "<td>";
        Html::showDateTimeField(
            "date_lastbackup",
            [
                'value'      => $this->fields['date_lastbackup'],
                'maybeempty' => true
            ]
        );
        echo "</td></tr>\n";

        $this->showFormButtons($options);

        return true;
    }


    public static function getIcon()
    {
        return "ti ti-database";
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => $this->getTypeName(1)
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
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'size',
            'unit'               => 'auto',
            'name'               => __('Global size'),
            'datatype'           => 'number',
            'width'              => 1000,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => 'is_onbackup',
            'name'               => __('Is on backup'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '10',
            'table'              => $this->getTable(),
            'field'              => 'date_lastbackup',
            'name'               => __('Last backup date'),
            'datatype'           => 'date'
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => DatabaseInstance::getTable(),
            'field'              => 'name',
            'linkfield'          => '',
            'name'               => DatabaseInstance::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => Computer::getTable(),
            'field'              => 'name',
            'datatype'           => 'itemlink',
            'linkfield'          => 'items_id',
            'name'               => Computer::getTypeName(0),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => DatabaseInstance::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'item_itemtype',
                        'specific_itemtype'  => 'Computer'
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $this->getTable(),
            'field'              => 'is_dynamic',
            'name'               => __('Dynamic'),
            'datatype'           => 'bool'
        ];

        return $tab;
    }

    public static function rawSearchOptionsToAdd()
    {
        $tab = [];
        $name = self::getTypeName(Session::getPluralNumber());

        $tab[] = [
            'id'                 => 'database',
            'name'               => $name
        ];

        $tab[] = [
            'id'                 => '167',
            'table'              => self::getTable(),
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
            'id'                 => '166',
            'table'              => self::getTable(),
            'field'              => 'size',
            'name'               => sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio')),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'integer',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '169',
            'table'              => self::getTable(),
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
            'table'              => self::getTable(),
            'field'              => 'is_onbackup',
            'linkfield'          => '',
            'name'               => __('Is on backup'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'searchtype'         => ['equals']
        ];

        $tab[] = [
            'id'                 => '172',
            'table'              => self::getTable(),
            'field'              => 'date_lastbackup',
            'name'               => __('Last backup date'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'date',
            'joinparams'         => [
                'jointype'           => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '174',
            'table'              => self::getTable(),
            'field'              => 'is_dynamic',
            'linkfield'          => '',
            'name'               => __('Dynamic'),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype'           => 'child'
            ],
            'massiveaction'      => false,
            'forcegroupby'       => true,
            'searchtype'         => ['equals']
        ];

        return $tab;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            !$withtemplate
            && ($item->getType() == DatabaseInstance::class)
            && $item->canView()
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    [
                        'databaseinstances_id' => $item->getID(),
                        'is_deleted' => 0
                    ]
                );
            }
            return self::createTabEntry(self::getTypeName(), $nb);
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showForInstance($item);
    }

    /**
     * Display instances for database
     *
     * @param DatabaseInstance $instance Database object
     *
     * @return void|boolean
     **/
    public static function showForInstance(DatabaseInstance $instance)
    {

        $ID = $instance->fields['id'];

        if (!$instance->getFromDB($ID) || !$instance->can($ID, READ)) {
            return false;
        }
        $canedit = $instance->canEdit($ID);

        if ($canedit) {
            echo "<div class='center firstbloc'>" .
            "<a class='btn btn-primary' href='" . static::getFormURL() . "?databaseinstances_id=$ID'>";
            echo __('Add a database');
            echo "</a></div>\n";
        }

        echo "<div class='center'>";

        $databases = getAllDataFromTable(
            self::getTable(),
            [
                'WHERE'  => [
                    'databaseinstances_id' => $ID,
                ],
                'ORDER'  => 'name'
            ]
        );

        echo "<table class='tab_cadre_fixehov'>";

        Session::initNavigateListItems(
            self::class,
            sprintf(
                __('%1$s = %2$s'),
                DatabaseInstance::getTypeName(1),
                (empty($instance->fields['name']) ? "($ID)" : $instance->fields['name'])
            )
        );

        if (empty($databases)) {
            echo "<tr><th>" . __('No database') . "</th></tr>";
        } else {
            echo "<tr class='noHover'><th colspan='10'>" . self::getTypeName(Session::getPluralNumber()) . "</th></tr>";

            $header = "<tr><th>" . __('Name') . "</th>";
            $header .= "<th>" . sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio')) . "</th>";
            $header .= "<th>" . __('Is active') . "</th>";
            $header .= "<th>" . __('Has backup') . "</th>";
            $header .= "<th>" . __('Is dynamic') . "</th>";
            $header .= "</tr>";
            echo $header;

            $db = new self();
            foreach ($databases as $row) {
                $db->getFromDB($row['id']);
                echo "<tr class='" . ((isset($row['is_deleted']) && $row['is_deleted']) ? "tab_bg_2_2'" : "tab_bg_2") . "'>";
                echo "<td>" . $db->getLink() . "</td>";
                echo "<td>" . $row['size'] . "</td>";
                echo "<td>" . Dropdown::getYesNo($db->fields['is_active']) . "</td>";
                echo "<td>" . Dropdown::getYesNo($db->fields['is_onbackup']) . "</td>";
                echo "<td>" . Dropdown::getYesNo($db->fields['is_dynamic']) . "</td>";
                echo "</tr>";
                Session::addToNavigateListItems('DatabaseInstance', $row['id']);
            }
            echo $header;
        }
        echo "</table>";
        echo "</div>";
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input['date_lastbackup']) && empty($input['date_lastbackup'])) {
            unset($input['date_lastbackup']);
        }

        if (isset($input['size']) && empty($input['size'])) {
            unset($input['size']);
        }

        return parent::prepareInputForAdd($input);
    }

    public static function getAdditionalMenuLinks()
    {
        $links = [];
        if (static::canView()) {
            $insts = "<i class=\"ti ti-database-import\" title=\"" . DatabaseInstance::getTypeName(Session::getPluralNumber()) .
            "\"></i><span class='d-none d-xxl-block'>" . DatabaseInstance::getTypeName(Session::getPluralNumber()) . "</span>";
            $links[$insts] = DatabaseInstance::getSearchURL(false);
        }
        if (count($links)) {
            return $links;
        }
        return false;
    }

    public static function getAdditionalMenuOptions()
    {
        if (static::canView()) {
            return [
                'databaseinstance' => [
                    'title' => DatabaseInstance::getTypeName(Session::getPluralNumber()),
                    'page'  => DatabaseInstance::getSearchURL(false),
                    'icon'  => DatabaseInstance::getIcon(),
                    'links' => [
                        'add'    => '/front/databaseinstance.form.php',
                        'search' => '/front/dabataseinstance.php',
                    ]
                ]
            ];
        }
    }
}
