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

use Glpi\Features\AssetImage;

/// CommonDCModelDropdown class - dropdown for datacenter items models
abstract class CommonDCModelDropdown extends CommonDropdown
{
    use AssetImage;

    public $additional_fields_for_dictionnary = ['manufacturer'];


    public static function getFieldLabel()
    {
        return _n('Model', 'Models', 1);
    }

    /**
     * Return Additional Fields for this type
     *
     * @return array
     **/
    public function getAdditionalFields()
    {
        global $DB;

        $fields = parent::getAdditionalFields();

        if ($DB->fieldExists($this->getTable(), 'weight')) {
            $fields[] = [
                'name'   => 'weight',
                'type'   => 'integer',
                'label'  => __('Weight'),
                'min'    => 0,
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'required_units')) {
            $fields[] = [
                'name'   => 'required_units',
                'type'   => 'integer',
                'min'    => 1,
                'label'  => __('Required units')
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'depth')) {
            $fields[] = [
                'name'   => 'depth',
                'type'   => 'depth',
                'label'  => __('Depth')
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'power_connections')) {
            $fields[] = [
                'name'   => 'power_connections',
                'type'   => 'integer',
                'label'  => __('Power connections'),
                'min'    => 0,
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'power_consumption')) {
            $fields[] = [
                'name'   => 'power_consumption',
                'type'   => 'integer',
                'label'  => __('Power consumption'),
                'unit'   => __('watts'),
                'min'    => 0,
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'max_power')) {
            $fields[] = [
                'name'   => 'max_power',
                'type'   => 'integer',
                'label'  => __('Max. power (in watts)'),
                'unit'   => __('watts'),
                'min'    => 0,
            ];
        }

        if ($DB->fieldExists($this->getTable(), 'is_half_rack')) {
            $fields[] = [
                'name'   => 'is_half_rack',
                'type'   => 'bool',
                'label'  => __('Is half rack')
            ];
        }

        return $fields;
    }

    public function rawSearchOptions()
    {
        global $DB;
        $options = parent::rawSearchOptions();
        $table   = $this->getTable();

        if ($DB->fieldExists($table, 'weight')) {
            $options[] = [
                'id'       => '131',
                'table'    => $table,
                'field'    => 'weight',
                'name'     => __('Weight'),
                'datatype' => 'decimal'
            ];
        }

        if ($DB->fieldExists($table, 'required_units')) {
            $options[] = [
                'id'       => '132',
                'table'    => $table,
                'field'    => 'required_units',
                'name'     => __('Required units'),
                'datatype' => 'number'
            ];
        }

        if ($DB->fieldExists($table, 'depth')) {
            $options[] = [
                'id'       => '133',
                'table'    => $table,
                'field'    => 'depth',
                'name'     => __('Depth'),
            ];
        }

        if ($DB->fieldExists($table, 'power_connections')) {
            $options[] = [
                'id'       => '134',
                'table'    => $table,
                'field'    => 'power_connections',
                'name'     => __('Power connections'),
                'datatype' => 'number'
            ];
        }

        if ($DB->fieldExists($table, 'power_consumption')) {
            $options[] = [
                'id'       => '135',
                'table'    => $table,
                'field'    => 'power_consumption',
                'name'     => __('Power consumption'),
                'datatype' => 'decimal'
            ];
        }

        if ($DB->fieldExists($table, 'is_half_rack')) {
            $options[] = [
                'id'       => '136',
                'table'    => $table,
                'field'    => 'is_half_rack',
                'name'     => __('Is half rack'),
                'datatype' => 'bool'
            ];
        }

        return $options;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'picture_front':
            case 'picture_rear':
                if (isset($options['html']) && $options['html']) {
                    return Html::image(Toolbox::getPictureUrl($values[$field]), [
                        'alt'   => $options['searchopt']['name'],
                        'style' => 'height: 30px;',
                    ]);
                }
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function prepareInputForAdd($input)
    {
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->managePictures($input);
    }

    public function cleanDBonPurge()
    {
        Toolbox::deletePicture($this->fields['picture_front']);
        Toolbox::deletePicture($this->fields['picture_rear']);
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'depth':
                Dropdown::showFromArray(
                    $field['name'],
                    [
                        '1'      => __('1'),
                        '0.5'    => __('1/2'),
                        '0.33'   => __('1/3'),
                        '0.25'   => __('1/4')
                    ],
                    [
                        'value'   => $this->fields[$field['name']],
                        'width'   => '100%'
                    ]
                );
                break;
            default:
                throw new \RuntimeException("Unknown {$field['type']}");
        }
    }

    public static function getIcon()
    {
        $model_class  = get_called_class();
        $device_class = str_replace('Model', '', $model_class);
        return $device_class::getIcon();
    }
}
