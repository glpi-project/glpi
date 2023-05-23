<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * ValidationTemplate Class
 **/
class ITILValidationTemplate extends AbstractITILChildTemplate
{
    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'itilvalidationtemplate';

    public $can_be_translated = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Validation template', 'Validation templates', $nb);
    }


    public function getAdditionalFields()
    {
        $target   = new ITILValidationTemplate_Target();
        $user_ids = array_map(function ($target) {
            return $target['items_id'];
        }, $target->find([
            'itilvalidationtemplates_id' => $this->getID(),
            'itemtype'                   => \User::getType(),
        ]));
        $group_ids = array_map(function ($target) {
            return $target['items_id'];
        }, $target->find([
            'itilvalidationtemplates_id' => $this->getID(),
            'itemtype'                   => \Group::getType(),
        ]));

        return [
            [
                'name'  => 'itemtypes',
                'label' => __('Use template for'),
                'type'  => '',
                'list'  => true
            ],
            [
                'name'  => 'approver',
                'label' => __('Approver'),
                'type'  => '',
                'list'  => true
            ],
            [
                'name'           => 'content',
                'label'          => __('Content'),
                'type'           => 'tinymce',
                // As content copying from template is not using the image pasting process, these images
                // are not correctly processed. Indeed, document item corresponding to the destination item is not created and
                // image src is not containing the ITIL item foreign key, so image will not be visible for helpdesk profiles.
                // As fixing it is really complex (requires lot of refactoring in images handling, both on JS and PHP side),
                // it is preferable to disable usage of images in templates.
                'disable_images' => true,
            ]
        ];
    }

    /**
     * Display specific "itemtypes" field
     *
     * @param $value
     * @param $name
     * @param $options
     */
    public static function displayUseTemplateForfield(
        $value = null,
        $name = "",
        $options = []
    ) {
        $options['values']              = explode(',', $value);
        $options['emptylabel']          = __("Use template for");
        $options['display_emptychoice'] = true;
        $options['display']             = false;
        $options['width']               = '95%';
        $options['multiple']            = true;

        if (empty($name)) {
            $name = "itemtypes";
        }

        return Dropdown::showFromArray($name, self::getUseTemplateForValues(), $options);
    }

    /**
     * Display specific "approver" field
     *
     * @param $value
     * @param $name
     * @param $options
     */
    public static function displayValidatorField(
        $value = null,
        $options = []
    ) {
        if ($value) {
            $itemtype = array_key_first($value);
            $items_id = array_map(function ($item) {
                return $item['items_id'];
            }, $value[$itemtype]);
        }

        $options['users_id_requester'] = \Session::getLoginUserID();
        $options['itemtype_target']    = $itemtype ?? null;
        $options['groups_id']          = $value ? $value[$itemtype][0]['groups_id'] : null;
        $options['items_id_target']    = $value && $itemtype == 'Group' && count($items_id) == 1 ?
            $items_id[0] : $items_id ?? null;
        $options['right']              = 'validate_request';
        $options['display']            = false;

        return CommonITILValidation::dropdownValidator($options);
    }

    /**
     * Get possibles values for "itemtypes" field
     * @return array timestamp before each bump => label
     */
    public static function getUseTemplateForValues(): array
    {
        return [
            'TicketValidation' => __('Ticket Validation'),
            'ChangeValidation' => __('Change Validation'),
        ];
    }

    public function displaySpecificTypeField($id, $field = [], array $options = [])
    {
        if ($field['name'] == 'itemtypes') {
            echo self::displayUseTemplateForfield($this->fields['itemtypes'], "", []);
        } elseif ($field['name'] == 'approver') {
            echo self::displayValidatorField(ITILValidationTemplate_Target::getTargets($id), []);
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if ($field == 'itemtypes') {
            $value = '';
            foreach (explode(',', $values[$field]) as $key => $val) {
                $value .= ($key > 0 ? ', ' : '') . self::getUseTemplateForValues()[$val];
            }

            return $value;
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if ($field == 'itemtypes') {
            return self::displayUseTemplateForfield($values[$field], $name, $options, false);
        } elseif ($field == 'approver') {
            return self::displayValidatorField($values[$field], $name, $options, false);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '4',
            'name'               => __('Content'),
            'field'              => 'content',
            'table'              => $this->getTable(),
            'datatype'           => 'text',
            'htmltext'           => true
        ];

        $tab[] = [
            'id'                 => '5',
            'name'               => __('Use template for'),
            'field'              => 'itemtypes',
            'table'              => $this->getTable(),
            'datatype'           => 'specific',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "fas fa-layer-group";
    }

    public function prepareInput($input)
    {
        if (isset($input['itemtypes'])) {
            $input['itemtypes'] = implode(',', $input['itemtypes']);
        } else {
            $input['itemtypes'] = '';
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

    public function postTargets()
    {
        $itilValidationTemplatesTarget = new ITILValidationTemplate_Target();
        $itilValidationTemplatesTarget->deleteByCriteria([
            'itilvalidationtemplates_id' => $this->getID(),
        ]);

        if (isset($this->input['itemtype_target']) && isset($this->input['items_id_target'])) {
            if (!is_array($this->input['items_id_target'])) {
                $this->input['items_id_target'] = [$this->input['items_id_target']];
            }

            foreach ($this->input['items_id_target'] as $user_id) {
                $itilValidationTemplatesTarget->add([
                    'itilvalidationtemplates_id' => $this->getID(),
                    'itemtype'                   => $this->input['itemtype_target'],
                    'items_id'                   => $user_id,
                    'groups_id'                  => $this->input['groups_id'] ?? null,
                ]);
            }
        }
    }

    public function post_addItem($history = 1)
    {
        $this->postTargets();
    }

    public function post_updateItem($history = 1)
    {
        $this->postTargets();
    }
}
