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
use Glpi\DBAL\QuerySubQuery;

use function Safe\strtotime;

/**
 * LevelAgreement base Class for OLA & SLA
 * @since 9.2
 **/
abstract class LevelAgreement extends CommonDBChild
{
    // From CommonDBTM
    public $dohistory          = true;
    public static $rightname       = 'slm';

    // From CommonDBChild
    public static $itemtype = 'SLM';
    public static $items_id = 'slms_id';

    protected static $prefix            = '';
    protected static $prefixticket      = '';
    /** @var ''|class-string<LevelAgreementLevel> */
    protected static $levelclass        = '';
    /** @var string|class-string<CommonDBTM> */
    protected static $levelticketclass  = '';


    /**
     * Display a specific OLA or SLA warning.
     * Called into the above showForm() function
     *
     * @return void
     */
    abstract public function showFormWarning();

    /**
     * Return the text needed for a confirmation of adding level agreement to a ticket
     *
     * @return string[]
     */
    abstract public function getAddConfirmation(): array;

    /**
     * Get table fields
     *
     * @param integer $subtype of OLA/SLA, can be SLM::TTO or SLM::TTR
     *
     * @return array of 'date' and 'sla' field names
     */
    public static function getFieldNames($subtype)
    {
        $dateField = null;
        $laField  = null;

        switch ($subtype) {
            case SLM::TTO:
                $dateField = static::$prefixticket . 'time_to_own';
                $laField   = static::$prefix . 's_id_tto';
                break;

            case SLM::TTR:
                $dateField = static::$prefixticket . 'time_to_resolve';
                $laField   = static::$prefix . 's_id_ttr';
                break;
        }
        return [$dateField, $laField];
    }

    public static function getWaitingFieldName(): string
    {
        return static::$prefix . '_waiting_duration';
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(static::$levelclass, $ong, $options);
        $this->addStandardTab(Rule::class, $ong, $options);
        $this->addStandardTab(Ticket::class, $ong, $options);

        return $ong;
    }

    /**
     * Define calendar of the ticket using the SLA/OLA when using this calendar as sla/ola-s calendar
     *
     * @param integer $calendars_id calendars_id of the ticket
     **/
    public function setTicketCalendar($calendars_id)
    {
        if ($this->fields['use_ticket_calendar']) {
            $this->fields['calendars_id'] = $calendars_id;
        }
    }

    public function post_getEmpty()
    {
        $this->fields['number_time'] = 4;
        $this->fields['definition_time'] = 'hour';
    }

    public function showForm($ID, array $options = [])
    {
        $rowspan = 3;
        if ($ID > 0) {
            $rowspan = 5;
        }

        // Get SLM object
        $slm = new SLM();
        if (isset($options['parent'])) {
            $slm = $options['parent'];
        } else {
            $slm->getFromDB($this->fields['slms_id']);
        }

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $options[static::$items_id] = $slm->getField('id');

            // force itemtype of parent
            static::$itemtype = get_class($slm);

            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __s('Name') . "</td>";
        echo "<td>";
        echo Html::input("name", ['value' => $this->fields["name"]]);
        echo "<td rowspan='" . $rowspan . "'>" . __s('Comments') . "</td>";
        echo "<td rowspan='" . $rowspan . "'>
            <textarea class='form-control' rows='8' name='comment' >" . htmlescape($this->fields["comment"]) . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __s('SLM') . "</td>";
        echo "<td>";
        echo $slm->getLink();
        echo "<input type='hidden' name='slms_id' value='" . intval($this->fields['slms_id']) . "'>";
        echo "</td></tr>";

        if ($ID > 0) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __s('Last update') . "</td>";
            echo "<td>" . htmlescape($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"]) : __('Never'));
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'><td>" . _sn('Type', 'Types', 1) . "</td>";
        echo "<td>";
        self::getTypeDropdown(['value' => $this->fields["type"]]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __s('Maximum time') . "</td>";
        echo "<td>";
        Dropdown::showNumber("number_time", ['value' => $this->fields["number_time"],
            'min'   => 0,
            'max'   => 1000,
        ]);
        $possible_values = self::getDefinitionTimeValues();
        $rand = Dropdown::showFromArray(
            'definition_time',
            $possible_values,
            ['value'     => $this->fields["definition_time"],
                'on_change' => 'appearhideendofworking()',
            ]
        );

        echo Html::scriptBlock(
            <<<JAVASCRIPT
            function appearhideendofworking() {
                if (
                    $('#dropdown_definition_time$rand option:selected').val() == 'day'
                    || $('#dropdown_definition_time$rand option:selected').val() == 'month'
                ) {
                    $('#title_endworkingday').show();
                    $('#dropdown_endworkingday').show();
                } else {
                    $('#title_endworkingday').hide();
                    $('#dropdown_endworkingday').hide();
                }
            }
            appearhideendofworking();
JAVASCRIPT
        );

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><div id='title_endworkingday'>" . __s('End of working day') . "</div></td>";
        echo "<td><div id='dropdown_endworkingday'>";
        Dropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
        echo "</div></td>";

        echo "<td colspan='2'>";
        $this->showFormWarning();
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Get possibles keys and labels for the definition_time field
     *
     * @return array<string, string>
     *
     * @since 10.0.0
     */
    public static function getDefinitionTimeValues(): array
    {
        return [
            'minute' => _n('Minute', 'Minutes', Session::getPluralNumber()),
            'hour'   => _n('Hour', 'Hours', Session::getPluralNumber()),
            'day'    => _n('Day', 'Days', Session::getPluralNumber()),
            'month'  => _n('Month', 'Months', Session::getPluralNumber()),
        ];
    }

    /**
     * Get the matching label for a given key (definition_time field)
     *
     * @param string $value
     *
     * @return string
     *
     * @since 10.0.0
     */
    public static function getDefinitionTimeLabel(string $value): string
    {
        return self::getDefinitionTimeValues()[$value] ?? "";
    }

    /**
     * Get a level for a given action
     *
     * since 10.0
     *
     * @param mixed $nextaction
     *
     * @return false|LevelAgreementLevel
     * @used-by templates/components/itilobject/service_levels.html.twig
     */
    public function getLevelFromAction($nextaction)
    {
        if ($nextaction === false) {
            return false;
        }

        $pre  = static::$prefix;
        $nextlevel  = getItemForItemtype(static::$levelclass);
        if (!$nextlevel->getFromDB($nextaction->fields[$pre . 'levels_id'])) {
            return false;
        }

        return $nextlevel;
    }

    /**
     * Get then next levelagreement action for a given ticket and "LA" type
     *
     * since 10.0
     *
     * @param Ticket $ticket
     * @param SLM::TTO|SLM::TTR $type
     *
     * @return false|OlaLevel_Ticket|SlaLevel_Ticket
     * @used-by templates/components/itilobject/service_levels.html.twig
     */
    public function getNextActionForTicket(Ticket $ticket, int $type)
    {
        /** @var OlaLevel_Ticket|SlaLevel_Ticket $nextaction */
        $nextaction = getItemForItemtype(static::$levelticketclass);
        if (!$nextaction->getFromDBForTicket($ticket->fields["id"], $type)) {
            return false;
        }

        return $nextaction;
    }

    /**
     * Print the HTML for a SLM
     *
     * @param SLM $slm Slm item
     */
    public static function showForSLM(SLM $slm)
    {
        if (!$slm->can($slm->fields['id'], READ)) {
            return false;
        }

        $instID   = $slm->fields['id'];
        $la       = new static();
        $calendar = new Calendar();
        $rand     = mt_rand();
        $canedit  = $slm->canEdit($instID) && Session::getCurrentInterface() === 'central';

        if ($canedit) {
            $twig_params = [
                'instID' => $instID,
                'rand'   => $rand,
                'la'     => $la,
                'slm'    => $slm,
                'btn_msg' => __('Add a new item'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div id="showLa{{ instID }}{{ rand }}"></div>
                <script>
                    function viewAddEditLa{{ instID }}{{ rand }}(item_id = -1) {
                        $('#showLa{{ instID }}{{ rand }}').load("{{ config('root_doc') }}/ajax/viewsubitem.php", {
                            type: "{{ la.getType() }}",
                            parenttype: "{{ slm.getType() }}",
                            {{ slm.getForeignKeyField() }}: {{ instID }},
                            id: item_id,
                        });
                    }
                    $(() => {
                        $('#levelagreement{{ instID }}').on('click', 'tbody tr', function () {
                            viewAddEditLa{{ instID }}{{ rand }}($(this).data('id'));
                        });
                    });
                </script>
                <div class="text-center mb-3">
                    <button name="new_la" type="button" class="btn btn-primary" onclick="viewAddEditLa{{ instID }}{{ rand }}();">{{ btn_msg }}</button>
                </div>
TWIG, $twig_params);
        }

        // list
        $laList = $la->find(['slms_id' => $instID]);

        $entries = [];
        foreach ($laList as $val) {
            $la->getFromResultSet($val);
            $link = '';
            if ($slm->fields['use_ticket_calendar']) {
                $link = __s('Calendar of the ticket');
            } elseif (!$slm->fields['calendars_id']) {
                $link =  __s('24/7');
            } elseif ($calendar->getFromDB($slm->fields['calendars_id'])) {
                $link = $calendar->getLink();
            }
            $entries[] = [
                'itemtype' => static::class,
                'id'       => $val['id'],
                'row_class' => 'cursor-pointer',
                'name'     => $la->getLink(),
                'type'     => $la::getSpecificValueToDisplay('type', $la->fields['type']),
                'maximum_time' => $la::getSpecificValueToDisplay('number_time', [
                    'number_time'     => $la->fields['number_time'],
                    'definition_time' => $la->fields['definition_time'],
                ]),
                'calendar' => $link,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'levelagreement' . $instID,
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'type' => _n('Type', 'Types', 1),
                'maximum_time' => __('Maximum time'),
                'calendar' => _n('Calendar', 'Calendars', 1),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'calendar' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);
    }

    /**
     * Display a list of rule for the current sla/ola
     * @return void
     */
    public function showRulesList()
    {
        global $DB;

        $fk      = static::getFieldNames($this->fields['type'])[1];
        $rule    = new RuleTicket();
        $canedit = self::canUpdate();

        $rules_id_list = iterator_to_array($DB->request([
            'SELECT'          => 'rules_id',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_ruleactions',
            'WHERE'           => [
                'field' => $fk,
                'value' => $this->getID(),
            ],
        ]));
        $nb = count($rules_id_list);

        $entries = [];
        foreach ($rules_id_list as $data) {
            $rule->getFromDB($data['rules_id']);
            $entries[] = [
                'itemtype' => RuleTicket::class,
                'id'       => $rule->getID(),
                'rule'     => $canedit ? $rule->getLink() : htmlescape($rule->fields["name"]),
                'active'   => Dropdown::getYesNo($rule->fields["is_active"]),
                'description' => $rule->fields["description"],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'rule' => RuleTicket::getTypeName($nb),
                'active' => __('Active'),
                'description' => __('Description'),
            ],
            'formatters' => [
                'rule' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . RuleTicket::class . mt_rand(),
                'specific_actions' => [
                    'update' => _x('button', 'Update'),
                    'purge'  => _x('button', 'Delete permanently'),
                ],
            ],
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'SLM':
                    /** @var SLM $item */
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            self::getTable(),
                            ['slms_id' => $item->getField('id')]
                        );
                    }
                    return self::createTabEntry(static::getTypeName($nb), $nb, $item::getType());
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch (true) {
            case $item instanceof SLM:
                self::showForSLM($item);
                break;
        }
        return true;
    }

    /**
     * Get all LevelAgreements related to the ticket, filtered by LevelAgreement type (SLM::TTR | SLM::TTO)
     *
     * @param int $tickets_id
     * @param int $type
     * @return false|iterable
     * @used-by templates/components/itilobject/service_levels.html.twig
     */
    public function getDataForTicket($tickets_id, $type)
    {
        global $DB;

        [, $field] = static::getFieldNames($type);

        $iterator = $DB->request([
            'SELECT'       => [static::getTable() . '.id'],
            'FROM'         => static::getTable(),
            'INNER JOIN'   => [
                'glpi_tickets' => [
                    'FKEY'   => [
                        static::getTable()   => 'id',
                        'glpi_tickets'       => $field,
                    ],
                ],
            ],
            'WHERE'        => ['glpi_tickets.id' => $tickets_id],
            'LIMIT'        => 1,
        ]);

        if (count($iterator)) {
            return self::getFromIter($iterator);
        }
        return false;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
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
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => static::getTable(),
            'field'              => 'number_time',
            'name'               => _x('hour', 'Time'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'additionalfields'   => ['definition_time'],
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'end_of_working_day',
            'name'               => __('End of working day'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => static::getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'specific',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_slms',
            'field'              => 'name',
            'name'               => __('SLM'),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'number_time':
                switch ($values['definition_time']) {
                    case 'minute':
                        return htmlescape(sprintf(_n('%d minute', '%d minutes', $values[$field]), $values[$field]));
                    case 'hour':
                        return htmlescape(sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]));
                    case 'day':
                        return htmlescape(sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]));
                }
                break;

            case 'type':
                return htmlescape(self::getOneTypeName($values[$field]));
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
            case 'type':
                $options['value'] = $values[$field];
                return self::getTypeDropdown($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get delay (due time duration) in seconds for the current agreement
     *
     * The time to own or to resolve duration
     *
     * @return integer own/resolution time (default 0)
     **/
    public function getTime()
    {
        if (isset($this->fields['id'])) {
            return match ($this->fields['definition_time']) {
                'minute' => $this->fields['number_time'] * MINUTE_TIMESTAMP,
                'hour'   => $this->fields['number_time'] * HOUR_TIMESTAMP,
                'day'    => $this->fields['number_time'] * DAY_TIMESTAMP,
                'month'  => $this->fields['number_time'] * MONTH_TIMESTAMP,
                default   => 0
            };
        }
        return 0;
    }

    /**
     * Elapsed time between two dates in seconds
     *
     * @param string $start start date formated 'Y-m-d H:i:s'
     * @param string $end end date formated 'Y-m-d H:i:s'
     *
     * @return integer elapsed time in seconds
     **/
    public function getActiveTimeBetween($start, $end)
    {
        if ($end < $start) {
            return 0;
        }

        if (isset($this->fields['id'])) {
            $cal          = new Calendar();

            // Based on a calendar
            if ($this->fields['calendars_id'] > 0) {
                if ($cal->getFromDB($this->fields['calendars_id'])) {
                    return $cal->getActiveTimeBetween($start, $end);
                }
            } else { // No calendar
                $timestart = strtotime($start);
                $timeend   = strtotime($end);
                return ($timeend - $timestart);
            }
        }
        return 0;
    }

    /**
     * Get due date for current agreement
     *
     * @param string  $start_date        datetime start date ('Y-m-d H:i:s')
     * @param integer $additional_delay  integer  additional delay to add or substract (for waiting time)
     *
     * @return string|null  due datetime 'Y-m-d H:i:s' (NULL if sla/ola not exists)
     **/
    public function computeDate($start_date, $additional_delay = 0)
    {
        if (isset($this->fields['id'])) {
            $delay = $this->getTime();
            // Based on a calendar
            if ($this->fields['calendars_id'] > 0) {
                $cal          = new Calendar();
                $work_in_days = ($this->fields['definition_time'] === 'day' || $this->fields['definition_time'] === 'month');

                if ($cal->getFromDB($this->fields['calendars_id']) && $cal->hasAWorkingDay()) {
                    return $cal->computeEndDate(
                        $start_date,
                        $delay,
                        (int) $additional_delay,
                        $work_in_days,
                        $this->fields['end_of_working_day']
                    );
                }
            }

            // No calendar defined or invalid calendar
            if ($this->fields['number_time'] >= 0) {
                $starttime = strtotime($start_date);
                $endtime   = $starttime + $delay + (int) $additional_delay;
                return date('Y-m-d H:i:s', $endtime);
            }
        }

        return null;
    }

    /**
     * Should calculation on this LevelAgreement target date be done using
     * the "work_in_day" parameter set to true ?
     *
     * @return bool
     */
    public function shouldUseWorkInDayMode(): bool
    {
        return
            $this->fields['definition_time'] === 'day'
            || $this->fields['definition_time'] === 'month'
        ;
    }

    /**
     * Get execution date of a level
     *
     * @param string  $start_date        start date
     * @param integer $levels_id         sla/ola level id
     * @param integer $additional_delay  additional delay to add or substract (for waiting time)
     *
     * @return string|null  execution date time (NULL if ola/sla not exists)
     **/
    public function computeExecutionDate($start_date, $levels_id, $additional_delay = 0)
    {
        if (isset($this->fields['id'])) {
            $level = getItemForItemtype(static::$levelclass);
            $fk = getForeignKeyFieldForItemType(static::class);

            if ($level->getFromDB($levels_id)) { // level exists
                if ((int) $level->fields[$fk] === (int) $this->fields['id']) { // correct level
                    $delay        = $this->getTime();

                    // Based on a calendar
                    if ($this->fields['calendars_id'] > 0) {
                        $cal = new Calendar();
                        if ($cal->getFromDB($this->fields['calendars_id']) && $cal->hasAWorkingDay()) {
                            // Take SLA into account
                            $date_with_sla = $cal->computeEndDate(
                                $start_date,
                                $delay,
                                0,
                                $this->shouldUseWorkInDayMode(),
                                $this->fields['end_of_working_day']
                            );

                            // Take waiting duration time into account
                            $date_with_waiting_time = $cal->computeEndDate(
                                $date_with_sla,
                                $additional_delay,
                            );

                            // Take current SLA escalation level into account
                            $date_with_sla_and_escalation_level = $cal->computeEndDate(
                                $date_with_waiting_time,
                                $level->fields['execution_time'],
                                0,
                                $level->shouldUseWorkInDayMode(),
                            );

                            return $date_with_sla_and_escalation_level;
                        }
                    }
                    // No calendar defined or invalid calendar
                    $delay    += $additional_delay + $level->fields['execution_time'];
                    $starttime = strtotime($start_date);
                    $endtime   = $starttime + $delay;
                    return date('Y-m-d H:i:s', $endtime);
                }
            }
        }
        return null;
    }

    /**
     * Get types
     *
     * @return array array of types
     **/
    public static function getTypes()
    {
        return [
            SLM::TTO => __('Time to own'),
            SLM::TTR => __('Time to resolve'),
        ];
    }

    /**
     * Get types name
     *
     * @param  integer $type
     * @return string  name
     **/
    public static function getOneTypeName($type)
    {
        $types = self::getTypes();
        return $types[$type] ?? null;
    }

    /**
     * Get SLA types dropdown
     *
     * @param array $options
     *
     * @return string
     */
    public static function getTypeDropdown($options)
    {
        return Dropdown::showFromArray($options['name'] ?? 'type', self::getTypes(), $options);
    }

    public function prepareInputForAdd($input)
    {
        if (
            $input['definition_time'] !== 'day'
            && $input['definition_time'] !== 'month'
        ) {
            $input['end_of_working_day'] = 0;
        }

        // Copy calendar settings from SLM
        $slm = new SLM();
        if (array_key_exists('slms_id', $input) && $slm->getFromDB($input['slms_id'])) {
            $input['use_ticket_calendar'] = $slm->fields['use_ticket_calendar'];
            $input['calendars_id'] = $slm->fields['calendars_id'];
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (
            isset($input['definition_time']) && ($input['definition_time'] !== 'day'
            && $input['definition_time'] !== 'month')
        ) {
            $input['end_of_working_day'] = 0;
        }

        // Copy calendar settings from SLM
        $slm = new SLM();
        if (
            array_key_exists('slms_id', $input)
            && (int) $input['slms_id'] !== (int) $this->fields['slms_id']
            && $slm->getFromDB($input['slms_id'])
        ) {
            $input['use_ticket_calendar'] = $slm->fields['use_ticket_calendar'];
            $input['calendars_id'] = $slm->fields['calendars_id'];
        }

        return $input;
    }

    /**
     * Add a level to do for a ticket
     *
     * Add an entry in slalevels_tickets | olalevels_tickets table
     * The level is set by $levels_id parameter or the current level set in slalevels_id_ttr | olalevels_id_ttr (if set)
     *
     * @param Ticket  $ticket Ticket object
     * @param integer $levels_id SlaLevel or OlaLevel ID
     *
     * @return void
     **/
    public function addLevelToDo(Ticket $ticket, $levels_id = 0)
    {
        $pre = static::$prefix;

        if (!$levels_id && isset($ticket->fields[$pre . 'levels_id_ttr'])) {
            $levels_id = $ticket->fields[$pre . "levels_id_ttr"];
        }

        if ($levels_id) {
            $toadd = [];

            // Compute start date
            if ($pre === "ola") {
                // OLA have their own start date which is set when the OLA is added to the ticket
                if (
                    (int) $this->fields['type'] === SLM::TTO
                    && $ticket->fields['ola_tto_begin_date'] !== null
                ) {
                    $date_field = "ola_tto_begin_date";
                } elseif (
                    (int) $this->fields['type'] === SLM::TTR
                    && $ticket->fields['ola_ttr_begin_date'] !== null
                ) {
                    $date_field = "ola_ttr_begin_date";
                } else {
                    // Fall back to default date in case the specific date fields
                    // are not set (which may be the case for tickets created
                    // before their addition)
                    $date_field = 'date';
                }
            } else {
                // SLA are based on the ticket opening date
                $date_field = 'date';
            }

            $date = $this->computeExecutionDate(
                $ticket->fields[$date_field],
                $levels_id,
                $ticket->fields[$pre . '_waiting_duration']
            );
            if ($date !== null) {
                $toadd['date']           = $date;
                $toadd[$pre . 'levels_id'] = $levels_id;
                $toadd['tickets_id']     = $ticket->fields["id"];
                $levelticket             = getItemForItemtype(static::$levelticketclass);
                $levelticket->add($toadd);
            }
        }
    }

    /**
     * remove a level to do for a ticket
     *
     * @param Ticket $ticket object
     *
     * @return void
     **/
    public static function deleteLevelsToDo(Ticket $ticket)
    {
        global $DB;

        $ticketfield = static::$prefix . "levels_id_ttr";

        if ($ticket->fields[$ticketfield] > 0) {
            $levelticket = getItemForItemtype(static::$levelticketclass);
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $levelticket::getTable(),
                'WHERE'  => ['tickets_id' => $ticket->fields['id']],
            ]);

            foreach ($iterator as $data) {
                $levelticket->delete(['id' => $data['id']]);
            }
        }
    }

    public function cleanDBonPurge()
    {
        global $DB;

        // Clean levels
        $fk        = getForeignKeyFieldForItemType(static::class);
        $level     = getItemForItemtype(static::$levelclass);
        $level->deleteByCriteria([$fk => $this->getID()]);

        // Update tickets : clean SLA/OLA
        [, $laField] = static::getFieldNames($this->fields['type']);
        $iterator =  $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => [$laField => $this->fields['id']],
        ]);

        if (count($iterator)) {
            $ticket = new Ticket();
            foreach ($iterator as $data) {
                $ticket->deleteLevelAgreement(static::class, $data['id'], $this->fields['type']);
            }
        }

        Rule::cleanForItemAction($this);
    }

    public function post_clone($source, $history)
    {
        // Clone levels
        $classname = static::class;
        $fk        = getForeignKeyFieldForItemType($classname);
        $level     = getItemForItemtype(static::$levelclass);
        foreach ($level->find([$fk => $source->getID()]) as $data) {
            $level->getFromDB($data['id']);
            $level->clone([$fk => $this->getID()]);
        }
    }

    /**
     * Getter for the protected $levelclass static property
     *
     * @return class-string<LevelAgreementLevel>
     */
    public function getLevelClass(): string
    {
        return static::$levelclass;
    }

    /**
     * Getter for the protected $levelticketclass static property
     *
     * @return class-string<CommonDBTM>
     */
    public function getLevelTicketClass(): string
    {
        return static::$levelticketclass;
    }

    /**
     * Remove level of previously assigned level agreements for a given ticket
     *
     * @param int $tickets_id
     *
     * @return void
     */
    public function clearInvalidLevels(int $tickets_id): void
    {
        // CLear levels of others LA of the same type
        // e.g. if a new LA TTR was assigned, clear levels from others (= previous) LA TTR
        $level_ticket_class = $this->getLevelTicketClass();
        $level_ticket = getItemForItemtype($level_ticket_class);
        $level_class = $this->getLevelClass();
        $levels = $level_ticket->find([
            'tickets_id' => $tickets_id,
            [$level_class::getForeignKeyField() => ['!=', $this->getID()]],
            [
                $level_class::getForeignKeyField() => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM' => $level_class::getTable(),
                    'WHERE' => [
                        static::getForeignKeyField() => new QuerySubQuery([
                            'SELECT' => 'id',
                            'FROM' => static::getTable(),
                            'WHERE' => ['type' => $this->fields['type']],
                        ]),
                    ],
                ]),
            ],
        ]);

        // Delete invalid levels
        foreach ($levels as $level) {
            $level_ticket->delete(['id' => $level['id']]);
        }
    }
}
