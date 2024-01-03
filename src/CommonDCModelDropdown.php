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
        /** @var \DBmysql $DB */
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
        /** @var \DBmysql $DB */
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

    /**
     * Get the itemtype for this model
     *
     * @return string
     */
    public function getItemtypeForModel(): string
    {
        return str_replace('Model', '', get_called_class());
    }

    /**
     * Get the items in racks that are using this model
     *
     * @return array
     */
    public function getItemsRackForModel(): array
    {
        $itemtype = $this->getItemtypeForModel();
        return (new Item_Rack())->find([
            'itemtype' => $itemtype,
            'items_id' => new QuerySubQuery([
                'SELECT' => 'id',
                'FROM'   => $itemtype::getTable(),
                'WHERE'  => [
                    $this->getForeignKeyField() => $this->fields['id'],
                ]
            ])
        ]);
    }

    /**
     * Check if a cell is filled for a specific orientations, hpos and depth
     *
     * @param array $cell
     * @param int $orientation front or rear
     * @param int $hpos left, right or full
     * @param float $depth
     *
     * @return bool
     */
    private function isCellFilled(array $cell, int $orientation, int $hpos, float $depth): bool
    {
        // If hpos is full, check if both left and right are filled
        if ($hpos == Rack::POS_NONE) {
            return $this->isCellFilled($cell, $orientation, Rack::POS_LEFT, $depth)
                || $this->isCellFilled($cell, $orientation, Rack::POS_RIGHT, $depth);
        }

        if (isset($cell[$hpos])) {
            // Get the first $depth * 4 units of the cell to check if they are filled
            $accurateCell = array_slice(
                $orientation ?
                    array_reverse($cell[$hpos]) // If orientation is rear, reverse the array
                    : $cell[$hpos],
                0,
                $depth * 4
            );

            // Check if any of the units is filled
            if (in_array(1, $accurateCell)) {
                return true;
            }
        }

        return false;
    }

    public function prepareInputForAdd($input)
    {
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->managePictures($input);
        $input = $this->checkForRackIssues($input);

        return $input;
    }

    public function post_updateItem($history = true)
    {
        $this->updateRackItemsHorizontalPosition();
    }

    /**
     * Check if the racks items using this model can be updated without issues
     *
     * @param array $input
     * @return array|false
     */
    private function checkForRackIssues(array $input)
    {
        // Checks whether any fields that might be causing a problem have been modified
        if (
            (!isset($input['required_units'])
                || $input['required_units'] <= $this->fields['required_units']
            )
            && (!isset($input['is_half_rack'])
                || $input['is_half_rack'] == $this->fields['is_half_rack']
            )
            && (!isset($input['depth'])
                || $input['depth'] == $this->fields['depth']
            )
        ) {
            return $input;
        }

        // Check if the model is used by an asset in a rack
        // If so, check whether the new units required fit into the rack without modifying the positions
        $hasIssues = false;
        $itemtype = $this->getItemtypeForModel();
        $positionsToCheck = [];
        foreach ($this->getItemsRackForModel() as $item_rack) {
            $rack = Rack::getById($item_rack['racks_id']);
            $filled = $rack->getFilled($itemtype, $item_rack['items_id']);
            $requiredUnits = $input['required_units'] ?? $this->fields['required_units'];
            $orientation = $item_rack['orientation'];
            $hpos = $input['is_half_rack'] ?? $this->fields['is_half_rack'] ? $item_rack['hpos'] : Rack::POS_NONE;
            $depth = $input['depth'] ?? $this->fields['depth'];

            // Collect the positions to check
            for ($i = 0; $i < $requiredUnits; $i++) {
                $positionsToCheck[] = $item_rack['position'] + $i;

                if ($positionsToCheck[array_key_last($positionsToCheck)] > $rack->fields['number_units']) {
                    for ($j = 1; $j <= $requiredUnits - $i; $j++) {
                        $positionsToCheck[] = $item_rack['position'] - $j;
                    }
                    break;
                }
            }

            // Check if any of the positions to check are filled or out of bounds
            foreach ($positionsToCheck as $position) {
                if (
                    isset($filled[$position]) && $this->isCellFilled($filled[$position], $orientation, $hpos, $depth)
                    || $position < 1
                ) {
                    $hasIssues = true;
                    Session::addMessageAfterRedirect(
                        sprintf(
                            __(
                                'Unable to update model because it is used by an asset in the "%s" rack and the new required units do not fit into the rack'
                            ),
                            $rack->getLink()
                        ),
                        true,
                        ERROR
                    );
                    break 2;
                }
            }
        }

        return $hasIssues ? false : $input;
    }

    /**
     * Update the horizontal positions of the items in racks using this model
     *
     * @return void
     */
    private function updateRackItemsHorizontalPosition()
    {
        if (!$this->fields['is_half_rack']) {
            // If the model is not half rack, set the hpos to none for all rack items using this model
            $item_rack = new Item_Rack();
            foreach ($this->getItemsRackForModel() as $item) {
                if ($item['hpos'] == Rack::POS_NONE) {
                    continue;
                }

                $item_rack->update([
                    'id' => $item['id'],
                    'hpos' => Rack::POS_NONE,
                ]);
            }
        } else {
            // If the model is half rack, set the hpos to left for all rack items using this model and having hpos none
            $item_rack = new Item_Rack();
            foreach ($this->getItemsRackForModel() as $item) {
                if ($item['hpos'] != Rack::POS_NONE) {
                    continue;
                }

                $item_rack->update([
                    'id' => $item['id'],
                    'hpos' => Rack::POS_LEFT,
                ]);
            }
        }
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
