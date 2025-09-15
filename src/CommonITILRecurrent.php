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
use Glpi\Features\Clonable;

use function Safe\preg_match;
use function Safe\strtotime;

/**
 * Base class for recurrent tickets and changes
 *
 * @since 10.0.0
 */
abstract class CommonITILRecurrent extends CommonDropdown
{
    use Clonable;

    /**
     * @var bool From CommonDBTM
     */
    public $dohistory = true;

    /**
     * @var bool From CommonDropdown
     */
    public $display_dropdowntitle = false;

    /**
     * @var bool From CommonDropdown
     */
    public $can_be_translated = false;

    /**
     * Concrete items to be instanciated
     */
    abstract public static function getConcreteClass();

    /**
     * Template class to use to create the concrete items
     */
    abstract public static function getTemplateClass();

    /**
     * Predefined field class to use to set the concrete items's data
     */
    abstract public static function getPredefinedFieldsClass();

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        // Tabs on CommonITILRecurrent items
        if ($item instanceof self) {
            switch ($tabnum) {
                // First tab : display next creation date
                case 1:
                    $item->showInfos();
                    return true;
            }
        }

        return false;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Only display tab if user can read ITILTemplates
        if (!Session::haveRight('itiltemplate', READ)) {
            return '';
        }

        // Tabs on CommonITILRecurrent items
        if ($item instanceof self) {
            $ong = [];
            $ong[1] = self::createTabEntry(_n('Information', 'Information', Session::getPluralNumber()), icon: 'ti ti-info-circle');
            return $ong;
        }

        return '';
    }

    public function getCloneRelations(): array
    {
        return [];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(static::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input['periodicity'])) {
            $input['next_creation_date'] = $this->computeNextCreationDate(
                $input['begin_date'],
                $input['end_date'],
                $input['periodicity'],
                $input['create_before'],
                $input['calendars_id']
            );
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (
            isset($input['begin_date'])
            || isset($input['periodicity'])
            || isset($input['create_before'])
            || isset($input['end_date'])
            || isset($input['calendars_id'])
        ) {
            $input['next_creation_date'] = $this->computeNextCreationDate(
                $input['begin_date'] ?? $this->fields['begin_date'],
                $input['end_date'] ?? $this->fields['end_date'],
                $input['periodicity'] ?? $this->fields['periodicity'],
                $input['create_before'] ?? $this->fields['create_before'],
                $input['calendars_id'] ?? $this->fields['calendars_id']
            );
        }

        return $input;
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'is_active',
                'label' => __('Active'),
                'type'  => 'bool',
                'list'  => false,
            ],
            [
                'name'  => static::getTemplateClass()::getForeignKeyField(),
                'label' => static::getTemplateClass()::getTypeName(1),
                'type'  => 'dropdownValue',
                'list'  => true,
            ],
            [
                'name'  => 'begin_date',
                'label' => __('Start date'),
                'type'  => 'datetime',
                'list'  => false,
            ],
            [
                'name'  => 'end_date',
                'label' => __('End date'),
                'type'  => 'datetime',
                'list'  => false,
            ],
            [
                'name'  => 'periodicity',
                'label' => __('Periodicity'),
                'type'  => 'specific_timestamp',
                'min'   => DAY_TIMESTAMP,
                'step'  => DAY_TIMESTAMP,
                'max'   => 2 * MONTH_TIMESTAMP,
            ],
            [
                'name'  => 'create_before',
                'label' => __('Preliminary creation'),
                'type'  => 'timestamp',
                'max'   => 2 * WEEK_TIMESTAMP,
                'step'  => HOUR_TIMESTAMP,
            ],
            [
                'name'  => 'calendars_id',
                'label' => _n('Calendar', 'Calendars', 1),
                'type'  => 'dropdownValue',
                'list'  => true,
            ],
        ];
    }

    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['name']) {
            case 'periodicity':
                $this->displayPeriodicityInput();
                break;
        }
    }

    public static function getSpecificValueToDisplay(
        $field,
        $values,
        array $options = []
    ) {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'periodicity':
                if (preg_match('/([0-9]+)MONTH/', $values[$field], $matches)) {
                    return htmlescape(sprintf(_n('%d month', '%d months', (int) $matches[1]), (int) $matches[1]));
                }
                if (preg_match('/([0-9]+)YEAR/', $values[$field], $matches)) {
                    return htmlescape(sprintf(_n('%d year', '%d years', (int) $matches[1]), (int) $matches[1]));
                }
                return htmlescape(Html::timestampToString($values[$field], false));
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Display periodicity field
     * The displayed dropdown offer the following options:
     *    1 to 24 hours
     *    1 to 30 days
     *    1 to 11 months
     *    1 to 10 years
     */
    public function displayPeriodicityInput(): void
    {
        $possible_values = [];

        // Hours
        for ($i = 1; $i < 24; $i++) {
            $possible_values[$i * HOUR_TIMESTAMP] = sprintf(_n('%d hour', '%d hours', $i), $i);
        }

        // Days
        for ($i = 1; $i <= 30; $i++) {
            $possible_values[$i * DAY_TIMESTAMP] = sprintf(_n('%d day', '%d days', $i), $i);
        }

        // Months
        for ($i = 1; $i < 12; $i++) {
            $possible_values[$i . 'MONTH'] = sprintf(_n('%d month', '%d months', $i), $i);
        }

        // Years
        for ($i = 1; $i < 11; $i++) {
            $possible_values[$i . 'YEAR'] = sprintf(_n('%d year', '%d years', $i), $i);
        }

        Dropdown::showFromArray('periodicity', $possible_values, [
            'value' => $this->fields['periodicity'],
        ]);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => '11',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => __('Active'),
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '12',
            'table'    => static::getTemplateClass()::getTable(),
            'field'    => 'name',
            'name'     => static::getTemplateClass()::getTypeName(1),
            'datatype' => 'itemlink',
        ];

        $tab[] = [
            'id'       => '13',
            'table'    => $this->getTable(),
            'field'    => 'begin_date',
            'name'     => __('Start date'),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '17',
            'table'    => $this->getTable(),
            'field'    => 'end_date',
            'name'     => __('End date'),
            'datatype' => 'datetime',
        ];

        $tab[] = [
            'id'       => '15',
            'table'    => $this->getTable(),
            'field'    => 'periodicity',
            'name'     => __('Periodicity'),
            'datatype' => 'specific',
        ];

        $tab[] = [
            'id'       => '14',
            'table'    => $this->getTable(),
            'field'    => 'create_before',
            'name'     => __('Preliminary creation'),
            'datatype' => 'timestamp',
        ];

        $tab[] = [
            'id'       => '18',
            'table'    => 'glpi_calendars',
            'field'    => 'name',
            'name'     => _n('Calendar', 'Calendars', 1),
            'datatype' => 'itemlink',
        ];

        return $tab;
    }

    /**
     * Show next creation date
     */
    public function showInfos(): void
    {
        if (!is_null($this->fields['next_creation_date'])) {
            echo "<div class='center'>";
            //TRANS: %s is the date of next creation
            echo htmlescape(
                sprintf(
                    __('Next creation on %s'),
                    Html::convDateTime($this->fields['next_creation_date'])
                )
            );
            echo "</div>";
        }
    }

    /**
     * Compute next creation date of an item.
     *
     * @param string         $begin_date     Begin date of the recurrent item in 'Y-m-d H:i:s' format.
     * @param string         $end_date       End date of the recurrent item in 'Y-m-d H:i:s' format,
     *                                       or 'NULL' or empty value.
     * @param string|integer $periodicity    Periodicity of creation, could be:
     *                                        - an integer corresponding to seconds,
     *                                        - a string using "/([0-9]+)(MONTH|YEAR)/" pattern.
     * @param int            $create_before  Anticipated creation delay in seconds.
     * @param int|null       $calendars_id   ID of the calendar to use to restrict creation to working hours,
     *                                       or 0 / null for no calendar.
     *
     * @return string  Next creation date in 'Y-m-d H:i:s' format.
     */
    public function computeNextCreationDate(
        ?string $begin_date,
        ?string $end_date,
        $periodicity,
        ?int $create_before,
        ?int $calendars_id
    ): string {
        $now = strtotime(Session::getCurrentTime());
        $periodicity_pattern = '/([0-9]+)(MONTH|YEAR)/';

        if ($begin_date === null || DateTime::createFromFormat('Y-m-d H:i:s', $begin_date) === false) {
            // Invalid begin date.
            return 'NULL';
        }

        $has_end_date = $end_date !== null && DateTime::createFromFormat('Y-m-d H:i:s', $end_date) !== false;
        if ($has_end_date && strtotime($end_date) < $now) {
            // End date is in past.
            return 'NULL';
        }

        if (
            !is_int($periodicity) && !preg_match('/^\d+$/', $periodicity)
            && !preg_match($periodicity_pattern, $periodicity)
        ) {
            // Invalid periodicity.
            return 'NULL';
        }

        // Compute periodicity values
        $periodicity_as_interval = null;
        $periodicity_in_seconds = $periodicity;
        $matches = [];
        if (preg_match($periodicity_pattern, $periodicity, $matches)) {
            $periodicity_as_interval = "{$matches[1]} {$matches[2]}";
            $periodicity_in_seconds  = (int) $matches[1]
            * MONTH_TIMESTAMP
            * ('YEAR' === $matches[2] ? 12 : 1);
        } elseif ($periodicity % DAY_TIMESTAMP == 0) {
            $periodicity_as_interval = ($periodicity / DAY_TIMESTAMP) . ' DAY';
        } else {
            $periodicity_as_interval = ($periodicity / HOUR_TIMESTAMP) . ' HOUR';
        }

        // Check that anticipated creation delay is greater than periodicity.
        if ($create_before > $periodicity_in_seconds) {
            Session::addMessageAfterRedirect(
                __s('Invalid frequency. It must be greater than the preliminary creation.'),
                false,
                ERROR
            );
            return 'NULL';
        }

        $calendar = new Calendar();
        $is_calendar_valid = $calendars_id && $calendar->getFromDB($calendars_id) && $calendar->hasAWorkingDay();

        if (!$is_calendar_valid || $periodicity_in_seconds >= DAY_TIMESTAMP) {
            // Compute next occurrence without using the calendar if calendar is not valid
            // or if periodicity is at least one day.

            // First occurrence of creation
            $occurence_time = strtotime($begin_date);
            $creation_time  = $occurence_time - $create_before;

            // Add steps while creation time is in past
            while ($creation_time < $now) {
                $creation_time  = strtotime("+ $periodicity_as_interval", $creation_time);
                $occurence_time = $creation_time + $create_before;

                // Stop if end date reached
                if ($has_end_date && $occurence_time > strtotime($end_date)) {
                    return 'NULL';
                }
            }

            if ($is_calendar_valid) {
                // Jump to next working day if occurrence is outside working days.
                while (
                    $calendar->isHoliday(date('Y-m-d', $occurence_time))
                    || !$calendar->isAWorkingDay($occurence_time)
                ) {
                    $occurence_time = strtotime('+ 1 day', $occurence_time);
                }
                // Jump to next working hour if occurrence is outside working hours.
                if (!$calendar->isAWorkingHour($occurence_time)) {
                    // On the first iteration, we work with the start of the day
                    $tmp_search_time = date('Y-m-d', $occurence_time);

                    // Find the first calendar segment that is after the current date
                    while (
                        ($occurence_date = $calendar->computeEndDate(
                            $tmp_search_time,
                            0 // 0 second delay to get the first working "second"
                        )) < date('Y-m-d H:i:s', $now)
                    ) {
                        $tmp_search_time = date(
                            'Y-m-d H:i:s',
                            strtotime("+ $periodicity_as_interval", strtotime($occurence_date))
                        );
                    }

                    $occurence_time = strtotime($occurence_date);
                }
                $creation_time  = $occurence_time - $create_before;
            }
        } else {
            // Base computation on calendar if calendar is valid

            $occurence_date = $calendar->computeEndDate(
                $begin_date,
                0 // 0-second delay to get the first working "second"
            );
            $occurence_time = strtotime($occurence_date);
            $creation_time  = $occurence_time - $create_before;

            while ($creation_time < $now) {
                $occurence_date = $calendar->computeEndDate(
                    date('Y-m-d H:i:s', $occurence_time),
                    $periodicity_in_seconds,
                    0,
                    $periodicity_in_seconds >= DAY_TIMESTAMP
                );
                $occurence_time = strtotime($occurence_date);
                $creation_time  = $occurence_time - $create_before;

                // Stop if end date reached
                if ($has_end_date && $occurence_time > strtotime($end_date)) {
                    return 'NULL';
                }
            };
        }

        return date("Y-m-d H:i:s", $creation_time);
    }

    /**
     * Get create time
     *
     * @return int|false
     */
    public function getCreateTime()
    {
        return strtotime($this->fields['next_creation_date']) + $this->fields['create_before'];
    }

    /**
     * Handle predefined fields
     *
     * @param array $predefined
     * @param array $input
     *
     * @return array The modified $input
     */
    public function handlePredefinedFields(
        array $predefined,
        array $input
    ): array {
        if (count($predefined)) {
            foreach ($predefined as $predeffield => $predefvalue) {
                $input[$predeffield] = $predefvalue;
            }
        }

        // Set date to creation date
        $input['date'] = date('Y-m-d H:i:s', $this->getCreateTime());
        if (isset($predefined['date'])) {
            $input['date'] = Html::computeGenericDateTimeSearch(
                $predefined['date'],
                false,
                $this->getCreateTime()
            );
        }

        // Compute time_to_resolve if predefined based on create date
        if (isset($predefined['time_to_resolve'])) {
            $input['time_to_resolve'] = Html::computeGenericDateTimeSearch(
                $predefined['time_to_resolve'],
                false,
                $this->getCreateTime()
            );
        }

        return $input;
    }

    /**
     * Get all available types to which an ITIL object can be assigned
     **/
    public static function getAllTypesForHelpdesk()
    {
        return CommonITILObject::getAllTypesForHelpdesk();
    }

    /**
     * Create an item based on the specified template
     *
     * @param array $linked_items array of elements (itemtype => array(id1, id2, id3, ...))
     *
     * @param CommonITILObject|null $created_item   Will contain the created item instance
     *
     * @return boolean
     */
    public function createItem(array $linked_items = [], ?CommonITILObject &$created_item = null)
    {
        $result = false;

        $concrete_class = static::getConcreteClass();
        if (!is_a($concrete_class, CommonITILObject::class, true)) {
            throw new LogicException();
        }

        $template_class = static::getTemplateClass();
        if (!is_a($template_class, ITILTemplate::class, true)) {
            throw new LogicException();
        }

        $fields_class = static::getPredefinedFieldsClass();
        if (!is_a($fields_class, ITILTemplatePredefinedField::class, true)) {
            throw new LogicException();
        }

        $tmpl_fk = $template_class::getForeignKeyField();

        $template = new $template_class();

        // Create item based on specified template and entity information
        if ($template->getFromDB($this->fields[$tmpl_fk])) {
            // Get default values for item
            $input = $concrete_class::getDefaultValues($this->fields['entities_id']);

            // Set template id
            $input[$template::getForeignKeyField()] = $template->getID();

            // Apply itiltemplates predefined values
            $fields = new $fields_class();
            $predefined = $fields->getPredefinedFields($this->fields[$tmpl_fk], true);
            $input = $this->handlePredefinedFields($predefined, $input);

            if (array_key_exists('status', $predefined)) {
                $input['_do_not_compute_status'] = true;
            }

            // Set entity
            $input['entities_id'] = $this->fields['entities_id'];
            $input['_auto_import'] = true;

            $item = new $concrete_class();

            if ($items_id = $item->add($input)) {
                $created_item = $item;
                $msg = sprintf(
                    __('%s %d successfully created'),
                    $concrete_class::getTypeName(1),
                    $items_id
                );
                // add item if any
                if (count($linked_items) > 0) {
                    foreach ($linked_items as $linked_itemtype => $linked_items_ids) {
                        foreach ($linked_items_ids as $linked_item_id) {
                            $item_link = getItemForItemtype($concrete_class::getItemLinkClass());
                            $item_link->add(
                                [
                                    $item->getForeignKeyField() => $items_id,
                                    'itemtype' => $linked_itemtype,
                                    'items_id' => $linked_item_id,
                                ]
                            );
                        }
                    }
                }

                $result = true;
            } else {
                $msg = sprintf(
                    __('%s creation failed (check mandatory fields)'),
                    $concrete_class::getTypeName(1)
                );
            }
        } else {
            $msg = sprintf(
                __('%s creation failed (no template)'),
                $concrete_class::getTypeName(1)
            );
        }

        Log::history(
            $this->fields['id'],
            static::class,
            [0, '', $msg],
            '',
            Log::HISTORY_LOG_SIMPLE_MESSAGE
        );

        // Compute next creation date
        $input = [
            'id'                 => $this->getId(),
            'next_creation_date' => $this->computeNextCreationDate(
                $this->fields['begin_date'],
                $this->fields['end_date'],
                $this->fields['periodicity'],
                $this->fields['create_before'],
                $this->fields['calendars_id']
            ),
        ];
        $this->update($input);

        return $result;
    }

    public static function getIcon()
    {
        return "ti ti-alarm";
    }

    /**
     * Return classname corresponding to relations with items.
     *
     * @return string|null Classname, or null if relations with items is not handled.
     */
    public static function getItemLinkClass(): ?string
    {
        return null;
    }

    /**
     * Return elements related to the recurrent object.
     * Result keys corresponds to itemtypes, and values are arrays of ids `array(itemtype => array(id1, id2, id3, ...))`.
     *
     * @return array
     */
    public function getRelatedElements(): array
    {
        global $DB;
        $items = [];
        if (($item_class = static::getItemLinkClass()) !== null) {
            $iterator = $DB->request([
                'FROM'   => $item_class::getTable(),
                'WHERE'  => [
                    'ticketrecurrents_id' =>  $this->getId(),
                ],
            ]);
            foreach ($iterator as $data) {
                if (!array_key_exists($data['itemtype'], $items)) {
                    $items[$data['itemtype']] = [];
                }
                $items[$data['itemtype']][] = $data['items_id'];
            }
        }
        return $items;
    }
}
