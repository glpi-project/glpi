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

/**
 * FieldUnicity Class
 **/
class FieldUnicity extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory          = true;

    public $first_level_menu   = "config";
    public $second_level_menu  = "fieldunicity";
    public $can_be_translated  = false;

    public static $rightname          = 'config';


    public static function getTypeName($nb = 0)
    {
        return __('Fields unicity');
    }


    public static function canCreate()
    {
        return static::canUpdate();
    }


    /**
     * @since 0.85
     **/
    public static function canPurge()
    {
        return static::canUpdate();
    }

    public function getAdditionalFields()
    {

        return [['name'  => 'is_active',
            'label' => __('Active'),
            'type'  => 'bool',
        ],
            ['name'  => 'itemtype',
                'label' => _n('Type', 'Types', 1),
                'type'  => 'unicity_itemtype',
            ],
            ['name'  => 'fields',
                'label' => __('Unique fields'),
                'type'  => 'unicity_fields',
            ],
            ['name'  => 'action_refuse',
                'label' => __('Record into the database denied'),
                'type'  => 'bool',
            ],
            ['name'  => 'action_notify',
                'label' => __('Send a notification'),
                'type'  => 'bool',
            ],
        ];
    }


    /**
     * Define tabs to display
     *
     * @param $options array
     **/
    public function defineTabs($options = [])
    {

        $ong          = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            if ($item->getType() == $this->getType()) {
                return __('Duplicates');
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            self::showDoubles($item);
        }
        return true;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'unicity_itemtype':
                $this->showItemtype($ID, $this->fields['itemtype']);
                break;

            case 'unicity_fields':
                self::selectCriterias($this);
                break;
        }
    }


    /**
     * Display a dropdown which contains all the available itemtypes
     *
     * @param integer $ID     The field unicity item id
     * @param integer $value  The selected value (default 0)
     *
     * @return void
     **/
    public function showItemtype($ID, $value = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        //Criteria already added : only display the selected itemtype
        if ($ID > 0) {
            if ($item = getItemForItemtype($this->fields['itemtype'])) {
                echo $item->getTypeName();
            }
            echo "<input type='hidden' name='itemtype' value='" . $this->fields['itemtype'] . "'>";
        } else {
            $options = [];
            //Add criteria : display dropdown
            foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
                if ($item = getItemForItemtype($itemtype)) {
                    if ($item->canCreate()) {
                        $options[$itemtype] = $item->getTypeName(1);
                    }
                }
            }
            asort($options);
            $rand = Dropdown::showFromArray('itemtype', $options, ['display_emptychoice' => true]);

            $params = ['itemtype' => '__VALUE__',
                'id'       => $ID,
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_itemtype$rand",
                "span_fields",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownUnicityFields.php",
                $params
            );
        }
    }


    /**
     * Return criteria unicity for an itemtype, in an entity
     *
     * @param string  $itemtype       the itemtype for which unicity must be checked
     * @param integer $entities_id    the entity for which configuration must be retrivied
     * @param boolean $check_active
     *
     * @return array an array of fields to check, or an empty array if no
     **/
    public static function getUnicityFieldsConfig($itemtype, $entities_id = 0, $check_active = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        //Get the first active configuration for this itemtype
        $request = [
            'FROM'   => 'glpi_fieldunicities',
            'WHERE'  => [
                'itemtype'  => $itemtype,
            ] + getEntitiesRestrictCriteria('glpi_fieldunicities', '', $entities_id, true),
            'ORDER'  => ['entities_id DESC'],
        ];

        if ($check_active) {
            $request['WHERE']['is_active'] = 1;
        }
        $iterator = $DB->request($request);

        $current_entity = false;
        $return         = [];
        foreach ($iterator as $data) {
            //First row processed
            if (!$current_entity) {
                $current_entity = $data['entities_id'];
            }
            //Process only for one entity, not more
            if ($current_entity != $data['entities_id']) {
                break;
            }
            $return[] = $data;
        }
        return $return;
    }


    /**
     * Display a list of available fields for unicity checks
     *
     * @param CommonDBTM $unicity
     *
     * @return void
     **/
    public static function selectCriterias(CommonDBTM $unicity)
    {
        echo "<span id='span_fields'>";

        if (!isset($unicity->fields['itemtype']) || !$unicity->fields['itemtype']) {
            echo  "</span>";
            return;
        }

        if (!isset($unicity->fields['entities_id'])) {
            $unicity->fields['entities_id'] = $_SESSION['glpiactive_entity'];
        }

        $unicity_fields = explode(',', $unicity->fields['fields']);

        self::dropdownFields(
            $unicity->fields['itemtype'],
            ['values' => $unicity_fields,
                'name'   => '_fields',
            ]
        );
        echo "</span>";
    }


    /** Dropdown fields for a specific itemtype
     *
     * @since 0.84
     *
     * @param string $itemtype
     * @param array  $options
     **/
    public static function dropdownFields($itemtype, $options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $p = [
            'name'    => 'fields',
            'display' => true,
            'values'  => [],
        ];

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        //Search option for this type
        if ($target = getItemForItemtype($itemtype)) {
            //Construct list
            $values = [];
            foreach ($DB->listFields(getTableForItemType($itemtype)) as $field) {
                $searchOption = $target->getSearchOptionByField('field', $field['Field']);
                if (
                    !empty($searchOption)
                    && !in_array($field['Field'], $target->getUnallowedFieldsForUnicity())
                ) {
                    $values[$field['Field']] = $searchOption['name'];
                }
            }
            $p['multiple'] = 1;
            $p['size']     = 15;

            return Dropdown::showFromArray($p['name'], $values, $p);
        }
        return false;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => self::getTypeName(),
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
            'datatype'           => 'number',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'fields',
            'name'               => __('Unique fields'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'unicity_types',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'action_refuse',
            'name'               => __('Record into the database denied'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'action_notify',
            'name'               => __('Send a notification'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => $this->getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $values
     * @param $options   array
     **/
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'fields':
                if (
                    isset($values['itemtype'])
                    && !empty($values['itemtype'])
                ) {
                    if ($target = getItemForItemtype($values['itemtype'])) {
                        $searchOption = $target->getSearchOptionByField('field', $values[$field]);
                        $fields       = explode(',', $values[$field]);
                        $message      = [];
                        foreach ($fields as $field) {
                            $searchOption = $target->getSearchOptionByField('field', $field);

                            if (isset($searchOption['name'])) {
                                $message[] = $searchOption['name'];
                            }
                        }
                        return implode(', ', $message);
                    }
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @since 0.84
     *
     * @param $field
     * @param $name               (default '')
     * @param $values             (default '')
     * @param $options      array
     **/
    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'fields':
                if (
                    isset($values['itemtype'])
                    && !empty($values['itemtype'])
                ) {
                    $options['values'] = explode(',', $values[$field]);
                    $options['name']   = $name;
                    return self::dropdownFields($values['itemtype'], $options);
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    /**
     * Perform checks to be sure that an itemtype and at least a field are selected
     *
     * @param array $input  the values to insert in DB
     *
     * @return array the input values to insert, but modified
     **/
    public static function checkBeforeInsert($input)
    {

        if (
            !$input['itemtype']
            || empty($input['_fields'])
        ) {
            Session::addMessageAfterRedirect(
                __("It's mandatory to select a type and at least one field"),
                true,
                ERROR
            );
            $input = [];
        } else {
            $input['fields'] = implode(',', $input['_fields']);
            unset($input['_fields']);
        }
        return $input;
    }


    public function prepareInputForAdd($input)
    {
        return self::checkBeforeInsert($input);
    }


    public function prepareInputForUpdate($input)
    {

        $input['fields'] = implode(',', $input['_fields']);
        unset($input['_fields']);

        return $input;
    }


    /**
     * Delete all criterias for an itemtype
     *
     * @param string $itemtype
     *
     * @return void
     **/
    public static function deleteForItemtype($itemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $DB->delete(
            self::getTable(),
            [
                'itemtype'  => ['LIKE', "%Plugin$itemtype%"],
            ]
        );
    }


    /**
     * List doubles
     *
     * @param FieldUnicity $unicity
     **/
    public static function showDoubles(FieldUnicity $unicity)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $fields       = [];
        $where_fields = [];
        if (!$item = getItemForItemtype($unicity->fields['itemtype'])) {
            return;
        }
        foreach (explode(',', $unicity->fields['fields']) as $field) {
            $fields[]       = $field;
            $where_fields[] = $field;
        }

        echo "<table class='tab_cadre_fixe'>";
        if (count($fields) > 0) {
            $colspan = count($fields) + 1;
            echo "<tr class='tab_bg_2'><th colspan='" . $colspan . "'>" . __('Duplicates') . "</th></tr>";

            $entities = [$unicity->fields['entities_id']];
            if ($unicity->fields['is_recursive']) {
                $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
            }

            $where = [];
            if ($item->maybeTemplate()) {
                $where[$item->getTable() . '.is_template'] = 0;
            }

            foreach ($where_fields as $where_field) {
                if (getTableNameForForeignKeyField($where_field)) {
                    $where = $where + [
                        'NOT'          => [$where_field => null],
                        $where_field   => ['<>', 0],
                    ];
                } else {
                    $where = $where + [
                        'NOT'          => [$where_field => null],
                        $where_field   => ['<>', ''],
                    ];
                }
            }

            $iterator = $DB->request([
                'SELECT'    => $fields,
                'COUNT'     => 'cpt',
                'FROM'      => $item->getTable(),
                'WHERE'     => [
                    $item->getTable() . '.entities_id'  => $entities,
                ] + $where,
                'GROUPBY'   => $fields,
                'ORDERBY'   => 'cpt DESC',
            ]);
            $results = [];
            foreach ($iterator as $data) {
                if ($data['cpt'] > 1) {
                    $results[] = $data;
                }
            }

            if (empty($results)) {
                echo "<tr class='tab_bg_2'>";
                echo "<td class='center' colspan='$colspan'>" . __('No item to display') . "</td></tr>";
            } else {
                echo "<tr class='tab_bg_2'>";
                foreach ($fields as $field) {
                    $searchOption = $item->getSearchOptionByField('field', $field);
                    echo "<th>" . $searchOption["name"] . "</th>";
                }
                echo "<th>" . _x('quantity', 'Number') . "</th></tr>";

                foreach ($results as $result) {
                    echo "<tr class='tab_bg_2'>";
                    foreach ($fields as $field) {
                        $table = getTableNameForForeignKeyField($field);
                        if ($table != '') {
                            echo "<td>" . Dropdown::getDropdownName($table, $result[$field]) . "</td>";
                        } else {
                            echo "<td>" . $result[$field] . "</td>";
                        }
                    }
                    echo "<td class='numeric'>" . $result['cpt'] . "</td></tr>";
                }
            }
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center'>" . __('No item to display') . "</td></tr>";
        }
        echo "</table>";
    }


    /**
     * Display debug information for current object
     **/
    public function showDebug()
    {

        $params = ['action_type' => true,
            'action_user' => getUserName(Session::getLoginUserID()),
            'entities_id' => $_SESSION['glpiactive_entity'],
            'itemtype'    => get_class($this),
            'date'        => $_SESSION['glpi_currenttime'],
            'refuse'      => true,
            'label'       => ['name' => 'test'],
            'field'       => ['action_refuse' => true],
            'double'      => [],
        ];

        NotificationEvent::debugEvent($this, $params);
    }


    public static function getIcon()
    {
        return "ti ti-fingerprint";
    }
}
