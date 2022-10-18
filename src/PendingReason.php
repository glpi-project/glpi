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
 * ITILCategory class
 **/
class PendingReason extends CommonDropdown
{
   // From CommonDBTM
    public $dohistory = true;

   // From CommonDBTM
    public $can_be_translated = true;

   // Rights managment
    public static $rightname = 'pendingreason';

    public static function getTypeName($nb = 0)
    {
        return _n('Pending reason', 'Pending reasons', $nb);
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'followup_frequency',
                'label' => __('Automatic follow-up frequency'),
                'type'  => '',
                'list'  => true
            ],
            [
                'name'      => 'itilfollowuptemplates_id',
                'label'     => ITILFollowupTemplate::getTypeName(1),
                'type'      => 'dropdownValue',
                'list'      => true
            ],
            [
                'name'  => 'followups_before_resolution',
                'label' => __('Follow-ups before automatic resolution'),
                'type'  => '',
                'list'  => true
            ],
            [
                'name'      => 'solutiontemplates_id',
                'label'     => SolutionTemplate::getTypeName(1),
                'type'      => 'dropdownValue',
                'list'      => true
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '200',
            'table'              => $this->getTable(),
            'field'              => 'followup_frequency',
            'name'               => __('Automatic follow-up frequency'),
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '201',
            'table'              => $this->getTable(),
            'field'              => 'followups_before_resolution',
            'name'               => __('Follow-ups before automatic resolution'),
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '202',
            'table'              => ITILFollowupTemplate::getTable(),
            'field'              => 'name',
            'linkfield'          => ITILFollowupTemplate::getForeignKeyField(),
            'name'               => __('Follow-up template'),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '203',
            'table'              => SolutionTemplate::getTable(),
            'field'              => 'name',
            'linkfield'          => SolutionTemplate::getForeignKeyField(),
            'name'               => SolutionTemplate::getTypeName(1),
            'massiveaction'      => false,
            'searchtype'         => ['equals', 'notequals'],
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    /**
     * Display specific "followup_frequency" field
     *
     * @param $value
     * @param $name
     * @param $options
     * @param $long_label If false give less details in the default label
     */
    public static function displayFollowupFrequencyfield(
        $value = null,
        $name = "",
        $options = [],
        $long_label = true
    ) {
        $values = self::getFollowupFrequencyValues();

       // Short label for forms with input labels
        $label = __("Disabled");

        if ($long_label) {
           // Long default value label for forms with icons instead of labels
            $label = __("Automatic follow-up disabled");
        }

        if ($value) {
            if (!isset($values[$value])) {
                $value = null;
            }
        }

        $options['value']               = $value;
        $options['emptylabel']          = $label;
        $options['display_emptychoice'] = true;
        $options['display']             = false;
        $options['width']               = '95%';

        if (empty($name)) {
            $name = "followup_frequency";
        }

        return Dropdown::showFromArray($name, $values, $options);
    }

    /**
     * Get possibles followup frequency values for pending reasons
     * @return array timestamp before each bump => label
     */
    public static function getFollowupFrequencyValues(): array
    {
        return [
            DAY_TIMESTAMP      => __("Every day"),
            2 * DAY_TIMESTAMP  => __("Every two days"),
            3 * DAY_TIMESTAMP  => __("Every three days"),
            4 * DAY_TIMESTAMP  => __("Every four days"),
            5 * DAY_TIMESTAMP  => __("Every five days"),
            6 * DAY_TIMESTAMP  => __("Every six days"),
            WEEK_TIMESTAMP     => __("Every week"),
            2 * WEEK_TIMESTAMP => __("Every two weeks"),
            3 * WEEK_TIMESTAMP => __("Every three weeks"),
            4 * WEEK_TIMESTAMP => __("Every four weeks"),
        ];
    }

    /**
     * Display specific "followups_before_resolution" field
     *
     * @param $value
     * @param $name
     * @param $options
     * @param $long_label If false give less details in the default label
     */
    public static function displayFollowupsNumberBeforeResolutionField(
        $value = null,
        $name = "",
        $options = [],
        $long_label = true
    ) {
        $values = self::getFollowupsBeforeResolutionValues();

       // Short label for forms with input labels
        $label = __("Disabled");

        if ($long_label) {
           // Long default value label for forms with icons instead of labels
            $label = __("Automatic resolution disabled");
        }

        if ($value) {
            if (!isset($values[$value])) {
                $value = null;
            }
        }

        if (empty($name)) {
            $name = "followups_before_resolution";
        }

        $options['value']               = $value;
        $options['emptylabel']          = $label;
        $options['display_emptychoice'] = true;
        $options['display']             = false;
        $options['width']               = '95%';

        return Dropdown::showFromArray($name, $values, $options);
    }

    /**
     * Get possibles values for 'followups_before_resolution' field of pending reasons
     * @return array number of bump before resolution => label
     */
    public static function getFollowupsBeforeResolutionValues(): array
    {
        return [
            1 => __("After one follow-up"),
            2 => __("After two follow-ups"),
            3 => __("After three follow-ups"),
        ];
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {

        if ($field['name'] == 'followup_frequency') {
            echo self::displayFollowupFrequencyfield($this->fields['followup_frequency'], "", [], false);
        } else if ($field['name'] == 'followups_before_resolution') {
            echo self::displayFollowupsNumberBeforeResolutionField($this->fields['followups_before_resolution'], "", [], false);
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if ($field == 'followup_frequency') {
            if ($values[$field] == 0) {
                return __("Disabled");
            }
            return self::getFollowupFrequencyValues()[$values[$field]];
        } else if ($field == 'followups_before_resolution') {
            if ($values[$field] == 0) {
                return __("Disabled");
            }
            return self::getFollowupsBeforeResolutionValues()[$values[$field]];
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {

        if ($field == 'followup_frequency') {
            return self::displayFollowupFrequencyfield($values[$field], $name, $options, false);
        } else if ($field == 'followups_before_resolution') {
            return self::displayFollowupsNumberBeforeResolutionField($values[$field], $name, $options, false);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                PendingReason_Item::class,
            ]
        );
    }
}
