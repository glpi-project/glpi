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
 * FieldUnicity Class
 **/
class FieldUnicity extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory          = true;

    public $can_be_translated  = false;

    public static $rightname          = 'config';


    public static function getTypeName($nb = 0)
    {
        return __('Fields unicity');
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function canCreate(): bool
    {
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        return static::canUpdate();
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'is_active',
                'label' => __('Active'),
                'type'  => 'bool',
            ],
            [
                'name'  => 'itemtype',
                'label' => _n('Type', 'Types', 1),
                'type'  => 'unicity_itemtype',
            ],
            [
                'name'  => 'fields',
                'label' => __('Unique fields'),
                'type'  => 'unicity_fields',
            ],
            [
                'name'  => 'action_refuse',
                'label' => __('Record into the database denied'),
                'type'  => 'bool',
            ],
            [
                'name'  => 'action_notify',
                'label' => __('Send a notification'),
                'type'  => 'bool',
            ],
        ];
    }

    public function defineTabs($options = [])
    {
        $ong          = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            if ($item::class === static::class) {
                return self::createTabEntry(__('Duplicates'), 0, $item::class, 'ti ti-copy');
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === self::class) {
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
        global $CFG_GLPI;

        //Criteria already added : only display the selected itemtype
        if ($ID > 0) {
            if ($item = getItemForItemtype($this->fields['itemtype'])) {
                echo htmlescape($item::getTypeName());
            }
            echo "<input type='hidden' name='itemtype' value='" . htmlescape($this->fields['itemtype']) . "'>";
        } else {
            $options = [];
            //Add criteria : display dropdown
            foreach ($CFG_GLPI['unicity_types'] as $itemtype) {
                if ($item = getItemForItemtype($itemtype)) {
                    if ($item::canCreate()) {
                        $options[$itemtype] = $item::getTypeName(1);
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
        global $DB;

        // Get the first active configuration for this itemtype
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
            // First row processed
            if (!$current_entity) {
                $current_entity = $data['entities_id'];
            }
            // Process only for one entity, not more
            if ($current_entity !== $data['entities_id']) {
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
                    && !in_array($field['Field'], $target->getUnallowedFieldsForUnicity(), true)
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
            'table'              => static::getTable(),
            'field'              => 'name',
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
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => static::getTable(),
            'field'              => 'fields',
            'name'               => __('Unique fields'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => static::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'unicity_types',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'action_refuse',
            'name'               => __('Record into the database denied'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'action_notify',
            'name'               => __('Send a notification'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '86',
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => static::getTable(),
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

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'fields':
                if (!empty($values['itemtype'])) {
                    if ($target = getItemForItemtype($values['itemtype'])) {
                        $searchOption = $target->getSearchOptionByField('field', $values[$field]);
                        $fields       = explode(',', $values[$field]);
                        $message      = [];
                        foreach ($fields as $f) {
                            $searchOption = $target->getSearchOptionByField('field', $f);

                            if (isset($searchOption['name'])) {
                                $message[] = $searchOption['name'];
                            }
                        }
                        return htmlescape(implode(', ', $message));
                    }
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'fields':
                if (!empty($values['itemtype'])) {
                    $options['values'] = explode(',', $values[$field]);
                    $options['name']   = $name;
                    return self::dropdownFields($values['itemtype'], $options);
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    private function prepareInput(array $input): array|false
    {
        if (array_key_exists('_fields', $input)) {
            // Convert multiple values received from the UI into a coma separated list
            $input['fields'] = implode(',', $input['_fields']);
            unset($input['_fields']);
        }

        if (
            (
                ($this->isNewItem() || array_key_exists('itemtype', $input))
                && empty($input['itemtype'])
            )
            || (
                ($this->isNewItem() || array_key_exists('fields', $input))
                && empty($input['fields'])
            )
        ) {
            Session::addMessageAfterRedirect(
                __s("It's mandatory to select a type and at least one field"),
                true,
                ERROR
            );
            return false;
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
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

        $entities = [$unicity->fields['entities_id']];
        if ($unicity->fields['is_recursive']) {
            $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
        }

        $where = [];
        if ($item->maybeTemplate()) {
            $where[$item::getTable() . '.is_template'] = 0;
        }

        foreach ($where_fields as $where_field) {
            $where += [
                'NOT' => [$where_field => null],
                $where_field => ['<>', getTableNameForForeignKeyField($where_field) ? 0 : ''],
            ];
        }
        $item_table = $item::getTable();

        $iterator = $DB->request([
            'SELECT'    => $fields,
            'COUNT'     => 'cpt',
            'FROM'      => $item_table,
            'WHERE'     => [
                $item_table . '.entities_id'  => $entities,
            ] + $where,
            'GROUPBY'   => $fields,
            'ORDERBY'   => 'cpt DESC',
        ]);

        $entries = [];
        foreach ($iterator as $data) {
            if ($data['cpt'] > 1) {
                $entry = [];
                foreach ($fields as $field) {
                    $table = getTableNameForForeignKeyField($field);
                    $entry[$field] = $table !== '' ? Dropdown::getDropdownName($table, $data[$field]) : $data[$field];
                }
                $entry['number'] = $data['cpt'];
                $entries[] = $entry;
            }
        }

        $columns = [];
        foreach ($fields as $field) {
            $searchOption = $item->getSearchOptionByField('field', $field);
            $columns[$field] = $searchOption["name"];
        }
        $columns['number'] = _x('quantity', 'Number');
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => $columns,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-fingerprint";
    }
}
