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

use Glpi\DBAL\QueryFunction;

/**
 * CommonDevice Class
 * for Device*class
 */
abstract class CommonDevice extends CommonDropdown
{
    public static $rightname          = 'device';

    public $can_be_translated  = false;

   // From CommonDBTM
    public $dohistory           = true;

    public $first_level_menu  = "config";
    public $second_level_menu = "commondevice";
    public $third_level_menu  = "";

    public static function getTypeName($nb = 0)
    {
        return _n('Component', 'Components', $nb);
    }

    /**
     * Get all the kind of devices available inside the system.
     *
     * @since 0.85
     *
     * @return array Array of the types of CommonDevice available
     **/
    public static function getDeviceTypes()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['device_types'];
    }

    /**
     * Get the associated item_device associated with this device
     * This method can be overridden, for instance by the plugin
     *
     * @since 0.85
     * @since 9.3 added the $devicetype parameter
     *
     * @param class-string|null $devicetype class name of device type, defaults to called class name
     *
     * @return class-string<Item_Devices>
     **/
    public static function getItem_DeviceType($devicetype = null)
    {
        if (null === $devicetype) {
            $devicetype = static::class;
        }
        if ($plug = isPluginItemType($devicetype)) {
            return 'Plugin' . $plug['plugin'] . 'Item_' . $plug['class'];
        }
        return "Item_$devicetype";
    }

    public static function getMenuContent()
    {
        $menu = [];
        if (self::canView()) {
            $menu['title'] = static::getTypeName(Session::getPluralNumber());
            $menu['page']  = '/front/devices.php';
            $menu['icon']  = self::getIcon();

            $dps = Dropdown::getDeviceItemTypes();

            foreach ($dps as $tab) {
                /** @var class-string $key */
                foreach ($tab as $key => $val) {
                    if ($tmp = getItemForItemtype($key)) {
                        $menu['options'][$key] = [
                            'title' => $val,
                            'page'  => $tmp::getSearchURL(false),
                            'links' => [
                                'search' => $tmp::getSearchURL(false),
                            ]
                        ];
                        if ($tmp::canCreate()) {
                            $menu['options'][$key]['links']['add'] = $tmp::getFormURL(false);
                        }

                        if ($itemClass = getItemForItemtype(self::getItem_DeviceType($key))) {
                            $itemTypeName = sprintf(
                                _n('%s item', '%s items', Session::getPluralNumber()),
                                $key::getTypeName(1)
                            );

                            $listLabel = '<i class="fa fa-list pointer" title="' . $itemTypeName . '"></i>'
                            . '<span class="sr-only">' . $itemTypeName . '</span>';
                            $menu['options'][$key]['links'][$listLabel] = $itemClass::getSearchURL(false);

                            // item device self links
                            $item_device_key = $itemClass::class;
                            $item_device_search_url = $itemClass::getSearchURL(false);
                            $menu['options'][$item_device_key] = [
                                'title' => $itemTypeName,
                                'page'  => $item_device_search_url,
                                'links' => [
                                    'search' => $item_device_search_url,
                                ],
                            ];
                            if ($itemClass::canCreate()) {
                                $menu['options'][$item_device_key]['links']['add'] = $itemClass::getFormURL(false);
                            }
                        }
                    }
                }
            }
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'registeredIDChooser':
                RegisteredID::showAddChildButtonForItemForm($this, '_registeredID');
                RegisteredID::showChildsForItemForm($this, '_registeredID');
                break;
        }
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'manufacturers_id',
                'label' => Manufacturer::getTypeName(1),
                'type'  => 'dropdownValue'
            ]
        ];
    }

    public function canUnrecurs()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $this->fields['id'];
        if (
            ($ID < 0)
            || !$this->fields['is_recursive']
        ) {
            return true;
        }
        if (!parent::canUnrecurs()) {
            return false;
        }
        $entities = getAncestorsOf("glpi_entities", $this->fields['entities_id']);
        $entities[] = $this->fields['entities_id'];

       // RELATION : device -> item_device -> item
        $linktype  = static::getItem_DeviceType();
        $linktable = getTableForItemType($linktype);

        $result = $DB->request(
            [
                'SELECT'    => [
                    'itemtype',
                    QueryFunction::groupConcat(
                        expression: 'items_id',
                        distinct: true,
                        alias: 'ids'
                    ),
                ],
                'FROM'      => $linktable,
                'WHERE'     => [
                    $this->getForeignKeyField() => $ID,
                ],
                'GROUPBY'   => [
                    'itemtype',
                ]
            ]
        );

        foreach ($result as $data) {
            if (!empty($data["itemtype"])) {
                $itemtable = getTableForItemType($data["itemtype"]);
                if ($item = getItemForItemtype($data["itemtype"])) {
                    // For each itemtype which are entity dependant
                    if ($item->isEntityAssign()) {
                        if (
                            countElementsInTable($itemtable, ['id'  => $data["ids"],
                                'NOT' => ['entities_id' => $entities ]
                            ]) > 0
                        ) {
                            return false;
                        }
                    }
                }
            }
        }
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
            'id'                 => '1',
            'table'              => static::getTable(),
            'field'              => 'designation',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getItem_DeviceType()::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of items'),
            'datatype'           => 'count',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams' => [
                'jointype' => 'child'
            ]
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => static::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => static::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }

    public static function getNameField()
    {
        return 'designation';
    }

    /**
     * get the HTMLTable Header for the current device according to the type of the item that is requesting
     * @param string $itemtype The type of the item
     * @param HTMLTableBase $base The element on which adding the header
     *                            (ie.: HTMLTableMain or HTMLTableGroup)
     * @param HTMLTableSuperHeader|null $super The super header
     *                            (in case of adding to HTMLTableGroup) (default NULL)
     * @param HTMLTableHeader|null $father The father of the current headers
     *                            (default NULL)
     * @param array $options parameter such as restriction
     *
     * @return HTMLTableHeader|void
     * @throws Exception
     * @since 0.84
     */
    public static function getHTMLTableHeader(
        $itemtype,
        HTMLTableBase $base,
        ?HTMLTableSuperHeader $super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {
        if (isset($options['dont_display'][static::class])) {
            return $father;
        }

        if (static::canView()) {
            $content = "<a href='" . static::getSearchURL() . "'>" . static::getTypeName(1) . "</a>";
        } else {
            $content = static::getTypeName(1);
        }

        $linktype = static::getItem_DeviceType();
        if (in_array($itemtype, $linktype::itemAffinity()) || in_array('*', $linktype::itemAffinity())) {
            $column = $base->addHeader('device', $content, $super, $father);
            $column->setItemType(
                static::class,
                isset($options['itemtype_title']) ? $options['itemtype_title'] : ''
            );
        } else {
            $column = $father;
        }

        return $column;
    }

    /**
     * @param HTMLTableRow|null $row object
     * @param CommonDBTM|null $item object (default NULL)
     * @param HTMLTableCell|null $father object (default NULL)
     * @param array $options
     * @return HTMLTableCell|null
     * @throws Exception
     * @warning note the difference between getHTMLTableCellForItem and getHTMLTableCellsForItem
     * @since 0.84
     */
    public function getHTMLTableCellForItem(
        ?HTMLTableRow $row = null,
        ?CommonDBTM $item = null,
        ?HTMLTableCell $father = null,
        array $options = []
    ) {

        if (isset($options['dont_display'][static::class])) {
            return $father;
        }

        if (static::canView()) {
            $content = $this->getLink([
                'icon' => self::getIcon()
            ]);
        } else {
            $content = $this->getName([
                'icon' => self::getIcon()
            ]);
        }

        if ($options['canedit']) {
            $field_name  = 'quantity_' . static::class . '_' . $this->getID();
            $content .= "&nbsp;<span class='fa fa-plus pointer' title='" . __s('Add') . "'
                      onClick=\"$('#" . $field_name . "').show();\"
                      ><span class='sr-only'>" .  __s('Add') . "</span></span>";
            $content .= "<span id='$field_name' style='display:none'><br>";
            $content .= __s('Add') . "&nbsp;";

            $content  = [$content,
                ['function'   => 'Dropdown::showNumber',
                    'parameters' => [$field_name, ['value' => 0,
                        'min'   => 0,
                        'max'   => 10
                    ]
                    ]
                ],
                "</span>"
            ];
        }

        $linktype = static::getItem_DeviceType();
        if (in_array($item::class, $linktype::itemAffinity()) || in_array('*', $linktype::itemAffinity())) {
            $cell = $row->addCell(
                $row->getHeaderByName('common', 'device'),
                $content,
                $father,
                $this
            );
        } else {
            $cell = $father;
        }

        return $cell;
    }

    /**
     * Import a device is not exists
     *
     * @param array $input Array of datas
     *
     * @return integer ID of existing or new Device
     **/
    public function import(array $input)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $with_history = $input['with_history'] ?? true;
        unset($input['with_history']);

        if (empty($input['designation'])) {
            return 0;
        }
        $where      = [];
        $a_criteria = $this->getImportCriteria();
        foreach ($a_criteria as $field => $compare) {
            if (isset($input[$field])) {
                $compare = explode(':', $compare);
                switch ($compare[0]) {
                    case 'equal':
                        $where[$field] = $input[$field];
                        break;

                    case 'delta':
                        $where[] = [
                            [$field => ['>', ((int) $input[$field] - (int) $compare[1])]],
                            [$field => ['<', ((int) $input[$field] + (int) $compare[1])]]
                        ];
                        break;
                }
            }
        }

        $model_fk = getForeignKeyFieldForItemType(static::class . 'Model');
        if ($DB->fieldExists(static::getTable(), $model_fk)) {
            if (isset($input[$model_fk])) {
                $where[$model_fk] = $input[$model_fk];
            } else {
                $where[] = [
                    'OR' => [
                        [$model_fk => null],
                        [$model_fk => 0]
                    ]
                ];
            }
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => static::getTable(),
            'WHERE'  => $where
        ]);

        if (count($iterator) > 0) {
            $line = $iterator->current();
            return $line['id'];
        }

        return $this->add($input, [], $with_history);
    }

    /**
     * Criteria used for import function
     *
     * @since 0.84
     **/
    public function getImportCriteria()
    {
        return [
            'designation'      => 'equal',
            'manufacturers_id' => 'equal'
        ];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(static::getItem_DeviceType(), $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * @since 0.85
     **/
    public function post_workOnItem()
    {
        if (
            (isset($this->input['_registeredID']))
            && (is_array($this->input['_registeredID']))
        ) {
            $input = ['itemtype' => $this->getType(),
                'items_id' => $this->getID()
            ];

            foreach ($this->input['_registeredID'] as $id => $registered_id) {
                $id_object     = new RegisteredID();
                $input['name'] = $registered_id;

                if (isset($this->input['_registeredID_type'][$id])) {
                    $input['device_type'] = $this->input['_registeredID_type'][$id];
                } else {
                    $input['device_type'] = '';
                }
               //$input['device_type'] = '';
                if ($id < 0) {
                    if (!empty($registered_id)) {
                        $id_object->add($input);
                    }
                } else {
                    if (!empty($registered_id)) {
                        $input['id'] = $id;
                        $id_object->update($input);
                        unset($input['id']);
                    } else {
                        $id_object->delete(['id' => $id]);
                    }
                }
            }
            unset($this->input['_registeredID']);
        }
    }

    public function post_addItem()
    {
        $this->post_workOnItem();
        parent::post_addItem();
    }

    public function post_updateItem($history = true)
    {
        $this->post_workOnItem();
        parent::post_updateItem($history);
    }

    public static function getFormURL($full = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');
        $itemtype = static::class;
        return "$dir/front/device.form.php?itemtype=$itemtype";
    }

    public static function getSearchURL($full = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');
        $itemtype = static::class;
        return "$dir/front/device.php?itemtype=$itemtype";
    }

    public static function getIcon()
    {
        return "ti ti-components";
    }
}
