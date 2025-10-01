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

use Glpi\Features\PlanningEvent;

/**
 * Template for PlanningExternalEvent
 * @since 9.5
 **/
class PlanningExternalEventTemplate extends CommonDropdown
{
    use PlanningEvent {
        prepareInputForAdd as protected prepareInputForAddTrait;
        prepareInputForUpdate as protected prepareInputForUpdateTrait;
        rawSearchOptions as protected trait_rawSearchOptions;
    }

    // From CommonDBTM
    public $dohistory          = true;
    public $can_be_translated  = true;


    public static function getTypeName($nb = 0)
    {
        return _n('External events template', 'External events templates', $nb);
    }


    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'state',
                'label' => __('Status'),
                'type'  => 'planningstate',
            ], [
                'name'  => 'planningeventcategories_id',
                'label' => _n('Category', 'Categories', 1),
                'type'  => 'dropdownValue',
                'list'  => true,
            ], [
                'name'  => 'background',
                'label' => __('Background event'),
                'type'  => 'bool',
            ], [
                'name'  => 'plan',
                'label' => _n('Calendar', 'Calendars', 1),
                'type'  => 'plan',
            ], [
                'name'  => 'rrule',
                'label' => __('Repeat'),
                'type'  => 'rrule',
            ], [
                'name'  => 'text',
                'label' => __('Description'),
                'type'  => 'tinymce',
                // Images should remains in base64 in templates.
                // When an element will be created from a template, tinymce will catch the base64 image and trigger the
                // document upload process.
                'convert_images_to_documents' => false,
            ],
        ];
    }


    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        switch ($field['type']) {
            case 'planningstate':
                Planning::dropdownState("state", $this->fields["state"], true, [
                    'width' => '100%',
                ]);
                break;

            case 'plan':
                Planning::showAddEventClassicForm([
                    'duration'       => $this->fields['duration'],
                    'itemtype'       => self::getType(),
                    'items_id'       => $this->fields['id'],
                    '_display_dates' => false,
                ]);
                break;

            case 'rrule':
                echo self::showRepetitionForm($this->fields['rrule'] ?? '');
                break;
        }
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'state':
                return htmlescape(Planning::getState($values[$field]));
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
            case 'state':
                return Planning::dropdownState(
                    name: $name,
                    value: $values[$field],
                    display: false,
                    options: $options
                );
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function prepareInputForAdd($input)
    {
        $saved_input = $input;
        $input = $this->prepareInputForAddTrait($input);

        return $this->parseExtraInput($saved_input, $input);
    }


    public function prepareInputForupdate($input)
    {
        $saved_input = $input;
        $input = $this->prepareInputForupdateTrait($input);

        return $this->parseExtraInput($saved_input, $input);
    }

    public function parseExtraInput(array $orig_input = [], array $input = [])
    {
        if (
            isset($orig_input['plan'])
            && array_key_exists('_duration', $orig_input['plan'])
        ) {
            $input['duration'] = $orig_input['plan']['_duration'];
        }

        if (
            isset($orig_input['_planningrecall'])
            && array_key_exists('before_time', $orig_input['_planningrecall'])
        ) {
            $input['before_time'] = $orig_input['_planningrecall']['before_time'];
        }

        return $input;
    }


    public function rawSearchOptions()
    {
        return $this->trait_rawSearchOptions();
    }

    public static function getIcon()
    {
        return "ti ti-stack-2-filled";
    }
}
