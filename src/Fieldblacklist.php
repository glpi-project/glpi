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
 * Fieldblacklist Class
 **/
class Fieldblacklist extends CommonDropdown
{
    public static $rightname         = 'config';

    public $can_be_translated = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Ignored value for the unicity', 'Ignored values for the unicity', $nb);
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

        return [['name'  => 'itemtype',
            'label' => _n('Type', 'Types', 1),
            'type'  => 'blacklist_itemtype'
        ],
            ['name'  => 'field',
                'label' => _n('Field', 'Fields', 1),
                'type'  => 'blacklist_field'
            ],
            ['name'  => 'value',
                'label' => __('Value'),
                'type'  => 'blacklist_value'
            ]
        ];
    }


    /**
     * Get search function for the class
     *
     * @return array of search option
     **/
    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'forcegroupby'       => true
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'field',
            'name'               => _n('Field', 'Fields', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => [
                '0'                  => 'itemtype'
            ]
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'value',
            'name'               => __('Value'),
            'datatype'           => 'specific',
            'additionalfields'   => [
                '0'                  => 'itemtype',
                '1'                  => 'field'
            ],
            'massiveaction'      => false
        ];

        return $tab;
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'field':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $target       = getItemForItemtype($values['itemtype']);
                    $searchOption = $target->getSearchOptionByField('field', $values[$field]);
                    return $searchOption['name'];
                }
                break;

            case 'value':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $target = getItemForItemtype($values['itemtype']);
                    if (isset($values['field']) && !empty($values['field'])) {
                        $searchOption = $target->getSearchOptionByField('field', $values['field']);
                        return $target->getValueToDisplay($searchOption, $values[$field]);
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
            case 'field':
                if (
                    isset($values['itemtype'])
                    && !empty($values['itemtype'])
                ) {
                    $options['value'] = $values[$field];
                    $options['name']  = $name;
                    return self::dropdownField($values['itemtype'], $options);
                }
                break;

            case 'value':
                if (
                    isset($values['itemtype'])
                    && !empty($values['itemtype'])
                ) {
                    if ($item = getItemForItemtype($values['itemtype'])) {
                        if (isset($values['field']) && !empty($values['field'])) {
                             $searchOption = $item->getSearchOptionByField('field', $values['field']);
                             return $item->getValueToSelect($searchOption, $name, $values[$field], $options);
                        }
                    }
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function prepareInputForAdd($input)
    {

        $input = parent::prepareInputForAdd($input);
        return $input;
    }


    public function prepareInputForUpdate($input)
    {

        $input = parent::prepareInputForUpdate($input);
        return $input;
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'blacklist_itemtype':
                $this->showItemtype();
                break;

            case 'blacklist_field':
                $this->selectCriterias();
                break;

            case 'blacklist_value':
                $this->selectValues();
                break;
        }
    }


    /**
     * Display a dropdown which contains all the available itemtypes
     *
     * @return void
     **/
    public function showItemtype()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($this->fields['id'] > 0) {
            if ($item = getItemForItemtype($this->fields['itemtype'])) {
                echo $item->getTypeName(1);
            }
            echo "<input type='hidden' name='itemtype' value='" . $this->fields['itemtype'] . "'>";
        } else {
            //Add criteria : display dropdown
            $options = [];
            foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
                if ($item = getItemForItemtype($itemtype)) {
                    if ($item->can(-1, READ)) {
                        $options[$itemtype] = $item->getTypeName(1);
                    }
                }
            }
            asort($options);
            $rand = Dropdown::showFromArray(
                'itemtype',
                $options,
                ['value'               => $this->fields['value'],
                    'display_emptychoice' => true
                ]
            );

            $params = ['itemtype' => '__VALUE__',
                'id'       => $this->fields['id']
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_itemtype$rand",
                "span_fields",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownFieldsBlacklist.php",
                $params
            );
        }
    }


    public function selectCriterias()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        echo "<span id='span_fields' name='span_fields'>";

        if (!isset($this->fields['itemtype']) || !$this->fields['itemtype']) {
            echo "</span>";
            return;
        }

        if (!isset($this->fields['entities_id'])) {
            $this->fields['entities_id'] = $_SESSION['glpiactive_entity'];
        }

        if (
            $rand = self::dropdownField(
                $this->fields['itemtype'],
                ['value' => $this->fields['field']]
            )
        ) {
            $params = ['itemtype' => $this->fields['itemtype'],
                'id_field' => '__VALUE__',
                'id'       => $this->fields['id']
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_field$rand",
                "span_values",
                $CFG_GLPI["root_doc"] . "/ajax/dropdownValuesBlacklist.php",
                $params
            );
        }
        echo "</span>";
    }


    /** Dropdown fields for a specific itemtype
     *
     * @since 0.84
     *
     * @param string $itemtype
     * @param array  $options
     **/
    public static function dropdownField($itemtype, $options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        $p['name']    = 'field';
        $p['display'] = true;
        $p['value']   = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        if ($target = getItemForItemtype($itemtype)) {
            $criteria = [];
            foreach ($DB->listFields($target->getTable()) as $field) {
                $searchOption = $target->getSearchOptionByField('field', $field['Field']);

               // MoYo : do not know why  this part ?
               // if (empty($searchOption)) {
               //    if ($table = getTableNameForForeignKeyField($field['Field'])) {
               //       $searchOption = $target->getSearchOptionByField('field', 'name', $table);
               //    }
               // }

                if (
                    !empty($searchOption)
                    && !in_array($field['Type'], $target->getUnallowedFieldsForUnicity())
                    && !in_array($field['Field'], $target->getUnallowedFieldsForUnicity())
                ) {
                    $criteria[$field['Field']] = $searchOption['name'];
                }
            }
            return Dropdown::showFromArray($p['name'], $criteria, $p);
        }
        return false;
    }


    /**
     * @param $field  (default '')
     **/
    public function selectValues($field = '')
    {
        if ($field == '') {
            $field = $this->fields['field'];
        }
        echo "<span id='span_values' name='span_values'>";
        if ($this->fields['itemtype'] != '') {
            if ($item = getItemForItemtype($this->fields['itemtype'])) {
                $searchOption = $item->getSearchOptionByField('field', $field);
                $options      = [];
                if (isset($this->fields['entity'])) {
                    $options['entity']      = $this->fields['entity'];
                    $options['entity_sons'] = $this->fields['is_recursive'];
                }
                echo $item->getValueToSelect($searchOption, 'value', $this->fields['value'], $options);
            }
        }
        echo "</span>";
    }


    /**
     * Check if a field & value are blacklisted or not
     *
     * @param itemtype      itemtype of the blacklisted field
     * @param entities_id   the entity in which the field must be saved
     * @param field         the field to check
     * @param value         the field's value
     *
     * @return true is value if blacklisted, false otherwise
     **/
    public static function isFieldBlacklisted($itemtype, $entities_id, $field, $value)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => 'glpi_fieldblacklists',
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'field'     => $field,
                'value'     => $value
            ] + getEntitiesRestrictCriteria('glpi_fieldblacklists', 'entities_id', $entities_id, true)
        ])->current();
        return $result['cpt'] > 0;
    }
}
