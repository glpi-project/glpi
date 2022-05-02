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
    protected static $levelclass        = '';
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
     * @return array of strings
     */
    abstract public function getAddConfirmation();

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

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(static::$levelclass, $ong, $options);
        $this->addStandardTab('Rule', $ong, $options);
        $this->addStandardTab('Ticket', $ong, $options);

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

    /**
     * Print the form
     *
     * @param $ID        integer  ID of the item
     * @param $options   array    of possible options:
     *     - target filename : where to go when done.
     *     - withtemplate boolean : template or basic item
     *
     *@return boolean item found
     **/
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

           //force itemtype of parent
            static::$itemtype = get_class($slm);

            $this->check(-1, CREATE, $options);
        }

        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        echo Html::input("name", ['value' => $this->fields["name"]]);
        echo "<td rowspan='" . $rowspan . "'>" . __('Comments') . "</td>";
        echo "<td rowspan='" . $rowspan . "'>
            <textarea class='form-control' rows='8' name='comment' >" . $this->fields["comment"] . "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('SLM') . "</td>";
        echo "<td>";
        echo $slm->getLink();
        echo "<input type='hidden' name='slms_id' value='" . $this->fields['slms_id'] . "'>";
        echo "</td></tr>";

        if ($ID > 0) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Last update') . "</td>";
            echo "<td>" . ($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_1'><td>" . _n('Type', 'Types', 1) . "</td>";
        echo "<td>";
        self::getTypeDropdown(['value' => $this->fields["type"]]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __('Maximum time') . "</td>";
        echo "<td>";
        Dropdown::showNumber("number_time", ['value' => $this->fields["number_time"],
            'min'   => 0
        ]);
        $possible_values = self::getDefinitionTimeValues();
        $rand = Dropdown::showFromArray(
            'definition_time',
            $possible_values,
            ['value'     => $this->fields["definition_time"],
                'on_change' => 'appearhideendofworking()'
            ]
        );
        echo "\n<script type='text/javascript' >\n";
        echo "function appearhideendofworking() {\n";
        echo "if ($('#dropdown_definition_time$rand option:selected').val() == 'day'
                  || $('#dropdown_definition_time$rand option:selected').val() == 'month') {
               $('#title_endworkingday').show();
               $('#dropdown_endworkingday').show();
            } else {
               $('#title_endworkingday').hide();
               $('#dropdown_endworkingday').hide();
            }";
        echo "}\n";
        echo "appearhideendofworking();\n";
        echo "</script>\n";

        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><div id='title_endworkingday'>" . __('End of working day') . "</div></td>";
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
     * @return string
     *
     * @since 10.0.0
     */
    public static function getDefinitionTimeValues(): array
    {
        return [
            'minute' => _n('Minute', 'Minutes', Session::getPluralNumber()),
            'hour'   => _n('Hour', 'Hours', Session::getPluralNumber()),
            'day'    => _n('Day', 'Days', Session::getPluralNumber()),
            'month'  => _n('Month', 'Months', Session::getPluralNumber())
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
     */
    public function getLevelFromAction($nextaction)
    {
        if ($nextaction === false) {
            return false;
        }

        $pre  = static::$prefix;
        $nextlevel  = new static::$levelclass();
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
     * @param int $type
     *
     * @return false|OlaLevel_Ticket|SlaLevel_Ticket
     */
    public function getNextActionForTicket(Ticket $ticket, int $type)
    {
        $nextaction = new static::$levelticketclass();
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
        global $CFG_GLPI;

        if (!$slm->can($slm->fields['id'], READ)) {
            return false;
        }

        $instID   = $slm->fields['id'];
        $la       = new static();
        $calendar = new Calendar();
        $rand     = mt_rand();
        $canedit  = ($slm->canEdit($instID)
                   && Session::getCurrentInterface() == "central");

        if ($canedit) {
            echo "<div id='showLa$instID$rand'></div>\n";

            echo "<script type='text/javascript' >";
            echo "function viewAddLa$instID$rand() {";
            $params = ['type'                     => $la->getType(),
                'parenttype'               => $slm->getType(),
                $slm->getForeignKeyField() => $instID,
                'id'                       => -1
            ];
            Ajax::updateItemJsCode(
                "showLa$instID$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "}";
            echo "</script>";
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='javascript:viewAddLa$instID$rand();'>";
            echo __('Add a new item') . "</a></div>\n";
        }

       // list
        $laList = $la->find(['slms_id' => $instID]);
        Session::initNavigateListItems(
            __CLASS__,
            sprintf(
                __('%1$s = %2$s'),
                $slm::getTypeName(1),
                $slm->getName()
            )
        );
        echo "<div class='spaced'>";
        if (count($laList)) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_top .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_top .= "</th>";
                $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_bottom .= "</th>";
            }
            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "<th>" . _n('Type', 'Types', 1) . "</th>";
            $header_end .= "<th>" . __('Maximum time') . "</th>";
            $header_end .= "<th>" . _n('Calendar', 'Calendars', 1) . "</th>";

            echo $header_begin . $header_top . $header_end;
            foreach ($laList as $val) {
                $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditLa" .
                        $instID . $val["id"] . "$rand();\""
                        : '');
                echo "<script type='text/javascript' >";
                echo "function viewEditLa" . $instID . $val["id"] . "$rand() {";
                $params = ['type'                     => $la->getType(),
                    'parenttype'               => $slm->getType(),
                    $slm->getForeignKeyField() => $instID,
                    'id'                       => $val["id"]
                ];
                Ajax::updateItemJsCode(
                    "showLa$instID$rand",
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    $params
                );
                echo "};";
                echo "</script>\n";

                echo "<tr class='tab_bg_1'>";
                echo "<td width='10' $edit>";
                if ($canedit) {
                     Html::showMassiveActionCheckBox($la->getType(), $val['id']);
                }
                echo "</td>";
                $la->getFromDB($val['id']);
                echo "<td $edit>" . $la->getLink() . "</td>";
                echo "<td $edit>" . $la->getSpecificValueToDisplay('type', $la->fields['type']) . "</td>";
                echo "<td $edit>";
                echo $la->getSpecificValueToDisplay(
                    'number_time',
                    ['number_time'     => $la->fields['number_time'],
                        'definition_time' => $la->fields['definition_time']
                    ]
                );
                echo "</td>";
                if ($slm->fields['use_ticket_calendar']) {
                    $link = __('Calendar of the ticket');
                } else if (!$slm->fields['calendars_id']) {
                     $link =  __('24/7');
                } else if ($calendar->getFromDB($slm->fields['calendars_id'])) {
                    $link = $calendar->getLink();
                }
                echo "<td $edit>" . $link . "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;
            echo "</table>";

            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo __('No item to display');
        }
        echo "</div>";
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
        $rand    = mt_rand();
        $canedit = self::canUpdate();

        $rules_id_list = iterator_to_array($DB->request([
            'SELECT'          => 'rules_id',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_ruleactions',
            'WHERE'           => [
                'field' => $fk,
                'value' => $this->getID()
            ]
        ]));
        $nb = count($rules_id_list);

        echo "<div class='spaced'>";
        if (!$nb) {
            echo "<table class='tab_cadre_fixehov'>";
            echo "<tr><th>" . __('No item found') . "</th>";
            echo "</tr>\n";
            echo "</table>\n";
        } else {
            if ($canedit) {
                Html::openMassiveActionsForm('massRuleTicket' . $rand);
                $massiveactionparams
                 = ['num_displayed'    => min($_SESSION['glpilist_limit'], $nb),
                     'specific_actions' => ['update' => _x('button', 'Update'),
                         'purge'  => _x('button', 'Delete permanently')
                     ]
                 ];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<table class='tab_cadre_fixehov'>";
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
                $header_begin  .= "<th width='10'>";
                $header_top    .= Html::getCheckAllAsCheckbox('massRuleTicket' . $rand);
                $header_bottom .= Html::getCheckAllAsCheckbox('massRuleTicket' . $rand);
                $header_end    .= "</th>";
            }
            $header_end .= "<th>" . RuleTicket::getTypeName($nb) . "</th>";
            $header_end .= "<th>" . __('Active') . "</th>";
            $header_end .= "<th>" . __('Description') . "</th>";
            $header_end .= "</tr>\n";
            echo $header_begin . $header_top . $header_end;

            Session::initNavigateListItems(
                get_class($this),
                sprintf(
                    __('%1$s = %2$s'),
                    $rule->getTypeName(1),
                    $rule->getName()
                )
            );

            foreach ($rules_id_list as $data) {
                $rule->getFromDB($data['rules_id']);
                Session::addToNavigateListItems(get_class($this), $rule->fields["id"]);
                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox("RuleTicket", $rule->fields["id"]);
                    echo "</td>";
                    $ruleclassname = get_class($rule);
                    echo "<td><a href='" . $ruleclassname::getFormURLWithID($rule->fields["id"])
                       . "&amp;onglet=1'>" . $rule->fields["name"] . "</a></td>";
                } else {
                    echo "<td>" . $rule->fields["name"] . "</td>";
                }

                echo "<td>" . Dropdown::getYesNo($rule->fields["is_active"]) . "</td>";
                echo "<td>" . $rule->fields["description"] . "</td>";
                echo "</tr>\n";
            }
            echo $header_begin . $header_bottom . $header_end;
            echo "</table>\n";

            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        }
        echo "</div>";
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case 'SLM':
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            self::getTable(),
                            ['slms_id' => $item->getField('id')]
                        );
                    }
                    return self::createTabEntry(static::getTypeName($nb), $nb);
            }
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case 'SLM':
                self::showForSLM($item);
                break;
        }
        return true;
    }


    /**
     * Get data by type and ticket
     *
     * @param $tickets_id
     * @param $type
     */
    public function getDataForTicket($tickets_id, $type)
    {
        global $DB;

        list($dateField, $field) = static::getFieldNames($type);

        $iterator = $DB->request([
            'SELECT'       => [static::getTable() . '.id'],
            'FROM'         => static::getTable(),
            'INNER JOIN'   => [
                'glpi_tickets' => [
                    'FKEY'   => [
                        static::getTable()   => 'id',
                        'glpi_tickets'       => $field
                    ]
                ]
            ],
            'WHERE'        => ['glpi_tickets.id' => $tickets_id],
            'LIMIT'        => 1
        ]);

        if (count($iterator)) {
            return $this->getFromIter($iterator);
        }
        return false;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
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
            'massiveaction'      => false,
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'number_time',
            'name'               => _x('hour', 'Time'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'additionalfields'   => ['definition_time']
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'end_of_working_day',
            'name'               => __('End of working day'),
            'datatype'           => 'bool',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'type',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'specific'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => 'glpi_slms',
            'field'              => 'name',
            'name'               => __('SLM'),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        return $tab;
    }


    /**
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
            case 'number_time':
                switch ($values['definition_time']) {
                    case 'minute':
                        return sprintf(_n('%d minute', '%d minutes', $values[$field]), $values[$field]);

                    case 'hour':
                        return sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]);

                    case 'day':
                        return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);
                }
                break;

            case 'type':
                return self::getOneTypeName($values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    /**
     * @param $field
     * @param $name            (default '')
     * @param $values          (default '')
     * @param $options   array
     *
     * @return string
     **/
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
     * Get computed resolution time
     *
     * @return integer resolution time (default 0)
     **/
    public function getTime()
    {

        if (isset($this->fields['id'])) {
            if ($this->fields['definition_time'] == "minute") {
                return $this->fields['number_time'] * MINUTE_TIMESTAMP;
            }
            if ($this->fields['definition_time'] == "hour") {
                return $this->fields['number_time'] * HOUR_TIMESTAMP;
            }
            if ($this->fields['definition_time'] == "day") {
                return $this->fields['number_time'] * DAY_TIMESTAMP;
            }
            if ($this->fields['definition_time'] == "month") {
                return $this->fields['number_time'] * MONTH_TIMESTAMP;
            }
        }
        return 0;
    }


    /**
     * Get active time between to date time for the active calendar
     *
     * @param datetime $start begin
     * @param datetime $end end
     *
     * @return integer timestamp of delay
     **/
    public function getActiveTimeBetween($start, $end)
    {

        if ($end < $start) {
            return 0;
        }

        if (isset($this->fields['id'])) {
            $cal          = new Calendar();
            $work_in_days = ($this->fields['definition_time'] == 'day');

           // Based on a calendar
            if ($this->fields['calendars_id'] > 0) {
                if ($cal->getFromDB($this->fields['calendars_id'])) {
                    return $cal->getActiveTimeBetween($start, $end, $work_in_days);
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
     * Get date for current agreement
     *
     * @param string  $start_date        datetime start date
     * @param integer $additional_delay  integer  additional delay to add or substract (for waiting time)
     *
     * @return string|null  due date time (NULL if sla/ola not exists)
     **/
    public function computeDate($start_date, $additional_delay = 0)
    {

        if (isset($this->fields['id'])) {
            $delay = $this->getTime();
           // Based on a calendar
            if ($this->fields['calendars_id'] > 0) {
                $cal          = new Calendar();
                $work_in_days = ($this->fields['definition_time'] == 'day' || $this->fields['definition_time'] == 'month');

                if ($cal->getFromDB($this->fields['calendars_id']) && $cal->hasAWorkingDay()) {
                    return $cal->computeEndDate(
                        $start_date,
                        $delay,
                        $additional_delay,
                        $work_in_days,
                        $this->fields['end_of_working_day']
                    );
                }
            }

           // No calendar defined or invalid calendar
            if ($this->fields['number_time'] >= 0) {
                $starttime = strtotime($start_date);
                $endtime   = $starttime + $delay + $additional_delay;
                return date('Y-m-d H:i:s', $endtime);
            }
        }

        return null;
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
            $level = new static::$levelclass();
            $fk = getForeignKeyFieldForItemType(get_called_class());

            if ($level->getFromDB($levels_id)) { // level exists
                if ($level->fields[$fk] == $this->fields['id']) { // correct level
                    $work_in_days = ($this->fields['definition_time'] == 'day' || $this->fields['definition_time'] == 'month');
                    $delay        = $this->getTime();

                   // Based on a calendar
                    if ($this->fields['calendars_id'] > 0) {
                        $cal = new Calendar();
                        if ($cal->getFromDB($this->fields['calendars_id']) && $cal->hasAWorkingDay()) {
                            return $cal->computeEndDate(
                                $start_date,
                                $delay,
                                $level->fields['execution_time'] + $additional_delay,
                                $work_in_days
                            );
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
        return [SLM::TTO => __('Time to own'),
            SLM::TTR => __('Time to resolve')
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
        $name  = null;
        if (isset($types[$type])) {
            $name = $types[$type];
        }
        return $name;
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

        $params = ['name'  => 'type'];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        return Dropdown::showFromArray($params['name'], self::getTypes(), $options);
    }


    public function prepareInputForAdd($input)
    {

        if (
            $input['definition_time'] != 'day'
            && $input['definition_time'] != 'month'
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
            isset($input['definition_time']) && ($input['definition_time'] != 'day'
            && $input['definition_time'] != 'month')
        ) {
            $input['end_of_working_day'] = 0;
        }

        // Copy calendar settings from SLM
        $slm = new SLM();
        if (
            array_key_exists('slms_id', $input)
            && $input['slms_id'] != $this->fields['slms_id']
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
            $date = $this->computeExecutionDate(
                $ticket->fields['date'],
                $levels_id,
                $ticket->fields[$pre . '_waiting_duration']
            );
            if ($date != null) {
                $toadd['date']           = $date;
                $toadd[$pre . 'levels_id'] = $levels_id;
                $toadd['tickets_id']     = $ticket->fields["id"];
                $levelticket             = new static::$levelticketclass();
                $levelticket->add($toadd);
            }
        }
    }


    /**
     * remove a level to do for a ticket
     *
     * @param $ticket Ticket object
     *
     * @return void
     **/
    public static function deleteLevelsToDo(Ticket $ticket)
    {
        global $DB;

        $ticketfield = static::$prefix . "levels_id_ttr";

        if ($ticket->fields[$ticketfield] > 0) {
            $levelticket = new static::$levelticketclass();
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => $levelticket::getTable(),
                'WHERE'  => ['tickets_id' => $ticket->fields['id']]
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
        $classname = get_called_class();
        $fk        = getForeignKeyFieldForItemType($classname);
        $level     = new static::$levelclass();
        $level->deleteByCriteria([$fk => $this->getID()]);

       // Update tickets : clean SLA/OLA
        list($dateField, $laField) = static::getFieldNames($this->fields['type']);
        $iterator =  $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => [$laField => $this->fields['id']]
        ]);

        if (count($iterator)) {
            $ticket = new Ticket();
            foreach ($iterator as $data) {
                $ticket->deleteLevelAgreement($classname, $data['id'], $this->fields['type']);
            }
        }

        Rule::cleanForItemAction($this);
    }
}
