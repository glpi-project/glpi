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

namespace Glpi\Features;

use CommonITILTask;
use DateInterval;
use DateTime;
use DateTimeZone;
use Dropdown;
use Entity;
use ExtraVisibilityCriteria;
use Glpi\RichText\RichText;
use Glpi\Toolbox\Sanitizer;
use Group_User;
use Html;
use Planning;
use PlanningEventCategory;
use PlanningRecall;
use QueryExpression;
use Reminder;
use RRule\RRule;
use RRule\RSet;
use Session;
use Toolbox;
use User;

trait PlanningEvent
{
    public function post_getEmpty()
    {
        if ($this->isField("users_id")) {
            $this->fields["users_id"] = Session::getLoginUserID();
        }

        if ($this->isField('rrule')) {
            $this->fields['rrule'] = [];
        }

        if ($this->isField('is_recursive')) {
            $this->fields['is_recursive'] = 1;
        }

        if ($this->isField('users_id_guests')) {
            $this->fields['users_id_guests'] = [];
        }

        parent::post_getEmpty();
    }

    public function post_addItem()
    {
       // Add document if needed
        $this->input = $this->addFiles($this->input, [
            'force_update'  => true,
            'content_field' => 'text'
        ]);

        if (
            !isset($this->input['_no_check_plan'])
            && isset($this->fields["users_id"])
            && isset($this->fields["begin"])
            && !empty($this->fields["begin"])
        ) {
            Planning::checkAlreadyPlanned(
                $this->fields["users_id"],
                $this->fields["begin"],
                $this->fields["end"],
                [
                    $this->getType() => [$this->fields['id']]
                ]
            );
        }

        if (isset($this->input['_planningrecall'])) {
            $this->input['_planningrecall']['items_id'] = $this->fields['id'];
            PlanningRecall::manageDatas($this->input['_planningrecall']);
        }
    }


    public function prepareInputForAdd($input)
    {
        global $DB;

        if (
            $DB->fieldExists(static::getTable(), 'users_id')
            && (!isset($input['users_id'])
              || empty($input['users_id']))
        ) {
            $input['users_id'] = Session::getLoginUserID();
        }

       // manage guests
        if (isset($input['users_id_guests']) && is_array($input['users_id_guests'])) {
            $input['users_id_guests'] = exportArrayToDB($input['users_id_guests']);
        }

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        if (!isset($input['uuid'])) {
            $input['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
        }

        $input["name"] = trim($input["name"]);
        if (empty($input["name"])) {
            $input["name"] = __('Without title');
        }

        $input["begin"] = $input["end"] = "NULL";

        if (isset($input['plan'])) {
            if (
                !empty($input['plan']["begin"])
                && !empty($input['plan']["end"])
                && ($input['plan']["begin"] < $input['plan']["end"])
            ) {
                $input['_plan']      = $input['plan'];
                unset($input['plan']);
                $input['is_planned'] = 1;
                $input["begin"]      = $input['_plan']["begin"];
                $input["end"]        = $input['_plan']["end"];
            } else if (
                isset($this->fields['begin'])
                    && isset($this->fields['end'])
            ) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
            }
        }

       // set new date.
        $input["date"] = $_SESSION["glpi_currenttime"];

       // encode rrule
        if (isset($input['rrule']) && is_array($input['rrule'])) {
            $input['rrule'] = $this->encodeRrule($input['rrule']);
        }

        return $input;
    }


    public function prepareInputForUpdate($input)
    {
       // manage guests
        if (isset($input['users_id_guests']) && is_array($input['users_id_guests'])) {
            $input['users_id_guests'] = exportArrayToDB($input['users_id_guests']);

           // avoid warning on update method (string comparison with old value)
            $this->fields['users_id_guests'] = exportArrayToDB($input['users_id_guests']);
        }

        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        if (isset($input['_planningrecall'])) {
            PlanningRecall::manageDatas($input['_planningrecall']);
        }

        if (isset($input["name"])) {
            $input["name"] = trim($input["name"]);

            if (empty($input["name"])) {
                $input["name"] = __('Without title');
            }
        }

        if (isset($input['plan'])) {
            if (
                !empty($input['plan']["begin"])
                && !empty($input['plan']["end"])
                && ($input['plan']["begin"] < $input['plan']["end"])
            ) {
                $input['_plan']      = $input['plan'];
                unset($input['plan']);
                $input['is_planned'] = 1;
                $input["begin"]      = $input['_plan']["begin"];
                $input["end"]        = $input['_plan']["end"];
            } else if (
                isset($this->fields['begin'])
                    && isset($this->fields['end'])
            ) {
                Session::addMessageAfterRedirect(
                    __('Error in entering dates. The starting date is later than the ending date'),
                    false,
                    ERROR
                );
            }
        }

        $input = $this->addFiles($input, ['content_field' => 'text']);

       // encode rrule
        if (isset($input['rrule']) && is_array($input['rrule'])) {
            $input['rrule'] = $this->encodeRrule($input['rrule']);
        }

        return $input;
    }

    public function encodeRrule(array $rrule = [])
    {

        if ($rrule['freq'] == null) {
            return "";
        }

        if (isset($rrule['exceptions'])) {
            if (is_string($rrule['exceptions']) && strlen($rrule['exceptions'])) {
                $rrule['exceptions'] = explode(', ', $rrule['exceptions']);
            }
            if (!is_array($rrule['exceptions']) || count($rrule['exceptions']) === 0) {
                unset($rrule['exceptions']);
            }
        }

        if (count($rrule) > 0) {
            $rrule = json_encode($rrule);
        }

        return $rrule;
    }


    public function post_updateItem($history = 1)
    {
        if (
            !isset($this->input['_no_check_plan'])
            && isset($this->fields["users_id"])
            && isset($this->fields["begin"])
            && !empty($this->fields["begin"])
        ) {
            Planning::checkAlreadyPlanned(
                $this->fields["users_id"],
                $this->fields["begin"],
                $this->fields["end"],
                [
                    $this->getType() => [$this->fields['id']]
                ]
            );
        }
        if (in_array("begin", $this->updates)) {
            PlanningRecall::managePlanningUpdates(
                $this->getType(),
                $this->getID(),
                $this->fields["begin"]
            );
        }
    }


    public function pre_updateInDB()
    {
       // Set new user if initial user have been deleted
        if (
            isset($this->fields['users_id'])
            && $this->fields['users_id'] == 0
            && $uid = Session::getLoginUserID()
        ) {
            $this->fields['users_id'] = $uid;
            $this->updates[]          = "users_id";
        }
    }

    /**
     * Delete a specific instance of a serie
     * Add an exception into the serie
     *
     * @see addInstanceException
     */
    public function deleteInstance(int $id = 0, string $day = "")
    {
        $this->addInstanceException($id, $day);
    }

    /**
     * Add an exception into a serie
     *
     * @param int $id of the serie
     * @param string $day the exception
     *
     * @return bool
     */
    public function addInstanceException(int $id = 0, string $day = "")
    {
        $this->getFromDB($id);
        $rrule = json_decode($this->fields['rrule'], true) ?? [];
        $rrule = array_merge_recursive($rrule, [
            'exceptions' => [
                $day
            ]
        ]);
        return $this->update([
            'id'             => $id,
            'rrule'          => $rrule,
            '_no_check_plan' => true,
        ]);
    }


    /**
     * Clone recurrent event into a non recurrent event
     * (and add an exception to the orginal one)
     *
     * @param int $id of the serie
     * @param string $start the new start for the event (in case of dragging)
     *
     * @return object the new object
     */
    public function createInstanceClone(int $id = 0, string $start = "")
    {
        $this->getFromDB($id);
        $fields = $this->fields;
        unset($fields['id'], $fields['uuid'], $fields['rrule']);
        $fields['plan'] = [
            'begin' => $fields['begin'],
            'end'   => $fields['end'],
        ];
       // avoid checking availability, will be done after when updating new dates
        $fields['_no_check_plan'] = true;

        $instance = new static();
        $new_id = $instance->add($fields);
        $instance->getFromDB($new_id);

        $this->addInstanceException($id, date("Y-m-d", strtotime($start)));

        return $instance;
    }


    /**
     * Populate the planning with planned event
     *
     * @param $options   array of possible options:
     *    - who          ID of the user (0 = undefined)
     *    - whogroup     ID of the group of users (0 = undefined)
     *    - begin        Date
     *    - end          Date
     *    - color
     *    - event_type_color
     *    - check_planned (boolean)
     *    - display_done_events (boolean)
     *
     * @return array of planning item
     **/
    public static function populatePlanning($options = []): array
    {
        global $DB, $CFG_GLPI;

        $default_options = [
            'genical'             => false,
            'color'               => '',
            'event_type_color'    => '',
            'check_planned'       => false,
            'display_done_events' => true,
        ];
        $options = array_merge($default_options, $options);

        $events    = [];
        $event_obj = new static();
        $itemtype  = $event_obj->getType();
        $item_fk   = getForeignKeyFieldForItemType($itemtype);
        $table     = static::getTable();
        $has_bg    = $DB->fieldExists($table, 'background');

        if (
            !isset($options['begin']) || $options['begin'] == 'NULL'
            || !isset($options['end']) || $options['end'] == 'NULL'
        ) {
            return $events;
        }

        $who        = $options['who'];
        $whogroup   = $options['whogroup'];
        $begin      = $options['begin'];
        $end        = $options['end'];

        if ($options['genical']) {
            $_SESSION["glpiactiveprofile"][static::$rightname] = READ;
        }
        $visibility_criteria = [];
        if ($event_obj instanceof ExtraVisibilityCriteria) {
            $visibility_criteria = $event_obj::getVisibilityCriteria(true);
        }
        $nreadpub  = [];
        $nreadpriv = [];

       // See public event ?
        if (
            !$options['genical']
            && (Session::getLoginUserID() !== false && $who == Session::getLoginUserID())
            && static::canView()
            && isset($visibility_criteria['WHERE'])
        ) {
            $nreadpub = $visibility_criteria['WHERE'];
        }
        unset($visibility_criteria['WHERE']);

        if ($whogroup === "mine") {
            if (isset($_SESSION['glpigroups'])) {
                $whogroup = $_SESSION['glpigroups'];
            } else if ($who > 0) {
                $whogroup = array_column(Group_User::getUserGroups($who), 'id');
            }
        }

       // See my private event ?
        if ($who > 0) {
            $nreadpriv = ["$table.users_id" => $who];

           // guests accounts
            if ($DB->fieldExists($table, 'users_id_guests')) {
                $nreadpriv = ['OR' => [
                    "$table.users_id" => $who,
                    "$table.users_id_guests" => ['LIKE', '%"' . $who . '"%'],
                ]
                ];
            }
        }

        if ($whogroup > 0) {
            if ($itemtype == 'Reminder') {
                $ngrouppriv = ["glpi_groups_reminders.groups_id" => $whogroup];
            } else {
                $ngrouppriv = [$itemtype::getTableField('groups_id') => $whogroup];
            }
            if (!empty($nreadpriv)) {
                $nreadpriv['OR'] = [$nreadpriv, $ngrouppriv];
            } else {
                $nreadpriv = $ngrouppriv;
            }
        }

        $NASSIGN = [];

        if (
            count($nreadpub)
            && count($nreadpriv)
        ) {
            $NASSIGN = ['OR' => [$nreadpub, $nreadpriv]];
        } else if (count($nreadpub)) {
            $NASSIGN = $nreadpub;
        } else {
            $NASSIGN = $nreadpriv;
        }

        if (!count($NASSIGN)) {
            return $events;
        }

        $WHERE = [
            'begin' => ['<', $end],
            'end'   => ['>', $begin]
        ] + [$NASSIGN]; // "encapsulate" nassign to prevent OR overriding

        if ($DB->fieldExists($table, 'is_planned')) {
            $WHERE["$table.is_planned"] = 1;
        }

        if ($options['check_planned']) {
            $WHERE['state'] = ['!=', Planning::INFO];
        }

        if (!$options['display_done_events']) {
            $WHERE[] = [
                'OR' => [
                    'state'  => Planning::TODO,
                    'AND'    => [
                        'state'  => Planning::INFO,
                        'end'    => ['>', new QueryExpression('NOW()')]
                    ]
                ]
            ];
        }

        $event_obj->getEmpty();
        if (isset($event_obj->fields['rrule'])) {
            unset($WHERE['end']);
            $WHERE[] = [
                'OR' => [
                    'end'   => ['>', $begin],
                    'rrule' => ['!=', ""],
                ]
            ];
        }

        $criteria = [
            'SELECT'          => ["$table.*"],
            'DISTINCT'        => true,
            'FROM'            => $table,
            'WHERE'           => $WHERE,
            'ORDER'           => 'begin'
        ] + $visibility_criteria;

        if (isset($event_obj->fields['planningeventcategories_id'])) {
            $c_table = PlanningEventCategory::getTable();
            $criteria['SELECT'][] = "$c_table.color AS cat_color";
            $criteria['JOIN'] = [
                $c_table => [
                    'FKEY' => [
                        $c_table => 'id',
                        $table   => 'planningeventcategories_id',
                    ]
                ]
            ];
        }

        $iterator = $DB->request($criteria);

        $events_toadd = [];

        if (count($iterator)) {
            foreach ($iterator as $data) {
                if ($event_obj->getFromDB($data["id"]) && $event_obj->canViewItem()) {
                    $key = $data["begin"] .
                      "$$" . $itemtype .
                      "$$" . $data["id"] .
                      "$$" . $who .
                      "$$" . $whogroup;
                    if (isset($options['from_group_users'])) {
                        $key .= "_gu";
                    }

                    $url = (!$options['genical'])
                    ? $event_obj->getFormURLWithID($data['id'])
                    : $CFG_GLPI["url_base"] .
                    static::getFormURLWithID($data['id'], false);

                    $is_rrule = isset($data['rrule']) && strlen($data['rrule']) > 0;

                    $events[$key] = [
                        'color'            => $options['color'],
                        'event_type_color' => $options['event_type_color'],
                        'event_cat_color'  => $data['cat_color'] ?? "",
                        'itemtype'         => $itemtype,
                        $item_fk           => $data['id'],
                        'id'               => $data['id'],
                        'users_id'         => $data["users_id"],
                        'state'            => $data["state"],
                        'background'       => $has_bg ? $data['background'] : false,
                        'name'             => Sanitizer::unsanitize($data['name']), // name is re-encoded on JS side
                        'text'             => $data['text'] !== null
                     ? RichText::getSafeHtml($data['text'])
                     : '',
                        'ajaxurl'          => $CFG_GLPI["root_doc"] . "/ajax/planning.php" .
                                        "?action=edit_event_form" .
                                        "&itemtype=$itemtype" .
                                        "&id=" . $data['id'] .
                                        "&url=$url",
                        'editable'         => $event_obj->canUpdateItem(),
                        'url'              => $url,
                        'begin'            => !$is_rrule && (strcmp($begin, $data["begin"]) > 0)
                                          ? $begin
                                          : $data["begin"],
                        'end'              => !$is_rrule && (strcmp($end, $data["end"]) < 0)
                                          ? $end
                                          : $data["end"],
                        'rrule'            => isset($data['rrule']) && !empty($data['rrule'])
                                          ? json_decode($data['rrule'], true)
                                          : []
                    ];

                    // when checking avaibility, we need to explode rrules events
                    // to check if future occurences of the primary event
                    // doesn't match current range
                    if ($options['check_planned'] && count($events[$key]['rrule'])) {
                        $event      = $events[$key];
                        $duration   = strtotime($event['end']) - strtotime($event['begin']);

                        $rset = static::getRsetFromRRuleField($event['rrule'], $event['begin']);

                       // - rrule object doesn't any duration property,
                       //   so we remove the duration from the begin part of the range
                       //   (minus 1second to avoid mathing precise end date)
                       //   to check if event started before begin and could be still valid
                       // - also set begin and end dates like it was as UTC
                       //   (Rrule lib will always compare with UTC)
                        $begin_datetime = new DateTime($options['begin'], new DateTimeZone('UTC'));
                        $begin_datetime->sub(new DateInterval("PT" . ($duration - 1) . "S"));
                        $end_datetime   = new DateTime($options['end'], new DateTimeZone('UTC'));
                        $occurences = $rset->getOccurrencesBetween($begin_datetime, $end_datetime);

                       // add the found occurences to the final tab after replacing their dates
                        foreach ($occurences as $currentDate) {
                            $occurence_begin = $currentDate;
                            $occurence_end   = (clone $currentDate)->add(new DateInterval("PT" . $duration . "S"));

                            $events_toadd[] = array_merge($event, [
                                'begin' => $occurence_begin->format('Y-m-d H:i:s'),
                                'end'   => $occurence_end->format('Y-m-d H:i:s'),
                            ]);
                        }

                       // remove primary event (with rrule)
                       // as the final array now have all the occurences
                        unset($events[$key]);
                    }
                }
            }
        }

        if (count($events_toadd)) {
            $events = $events + $events_toadd;
        }

        return $events;
    }


    /**
     * Display a Planning Item
     *
     * @param $val        array of the item to display
     * @param $who        ID of the user (0 if all)
     * @param $type       position of the item in the time block (in, through, begin or end)
     *                    default '')
     * @param $complete   complete display (more details) (default 0)
     *
     * @return Nothing (display function)
     **/
    public static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
    {
        global $CFG_GLPI;

        $html = "";
        $rand     = mt_rand();
        $users_id = "";  // show users_id reminder
        $img      = "rdv_private.png"; // default icon for reminder
        $item_fk  = getForeignKeyFieldForItemType(static::getType());

        if ($val["users_id"] != Session::getLoginUserID()) {
            $users_id = "<br>" . sprintf(__('%1$s: %2$s'), __('By'), getUserName($val["users_id"]));
            $img      = "rdv_public.png";
        }

        $html .= "<img src='" . $CFG_GLPI["root_doc"] . "/pics/" . $img . "' alt='' title=\"" .
             static::getTypeName(1) . "\">&nbsp;";
        $html .= "<a id='reminder_" . $val[$item_fk] . $rand . "' href='" .
             Reminder::getFormURLWithID($val[$item_fk]) . "'>";

        $html .= $users_id;
        $html .= "</a>";
        $recall = '';
        if (isset($val[$item_fk])) {
            $pr = new PlanningRecall();
            if (
                $pr->getFromDBForItemAndUser(
                    $val['itemtype'],
                    $val[$item_fk],
                    Session::getLoginUserID()
                )
            ) {
                $recall = "<br><span class='b'>" . sprintf(
                    __('Recall on %s'),
                    Html::convDateTime($pr->fields['when'])
                ) .
                      "<span>";
            }
        }

       // $val["text"] has already been sanitized and decoded by self::populatePlanning()
        $content = $val["text"] . $recall;

        if ($complete) {
            $html .= "<span>" . Planning::getState($val["state"]) . "</span><br>";
            $html .= "<div class='event-description rich_text_container'>" . $content . "</div>";
        } else {
            $html .= Html::showToolTip(
                "<span class='b'>" . Planning::getState($val["state"]) . "</span><br>" . $content,
                ['applyto' => "reminder_" . $val[$item_fk] . $rand,
                    'display' => false
                ]
            );
        }
        return $html;
    }


    /**
     * Display a mini form html for setup a reccuring event
     * to construct an rrule array
     *
     * @param string $rrule  existing rrule entry with ical format (https://www.kanzaki.com/docs/ical/rrule.html)
     * @param array $options can contains theses keys:
     *                        - 'rand' => random string for generated inputs
     * @return string        the generated html
     */
    public static function showRepetitionForm(string $rrule = "", array $options = []): string
    {
        $rrule = json_decode($rrule, true) ?? [];
        $defaults = [
            'freq'       => null,
            'interval'   => 1,
            'until'      => null,
            'byday'      => [],
            'bymonth'    => [],
            'exceptions' => [],
        ];
        $rrule = array_merge($defaults, $rrule);

        $default_options = [
            'rand' => mt_rand(),
        ];
        $options = array_merge($default_options, $options);
        $rand    = $options['rand'];

        $out = "<div class='card' style='padding: 5px; width: 100%;'>";
        $out .= Dropdown::showFromArray('rrule[freq]', [
            null      => __("Never"),
            'daily'   => __("Each day"),
            'weekly'  => __("Each week"),
            'monthly' => __("Each month"),
            'yearly'  => __("Each year"),
        ], [
            'value'     => strtolower($rrule['freq'] ?? ""),
            'rand'      => $rand,
            'display'   => false,
            'on_change' => "$(\"#toggle_ar\").toggle($(\"#dropdown_rrule_freq_$rand\").val().length > 0)"
        ]);

        $display_tar = $rrule['freq'] == null ? "none" : "inline";
        $display_ar  = $rrule['freq'] == null
                     || !($rrule['interval'] > 1
                          || $rrule['until'] != null
                          || count($rrule['byday']) > 0
                          || count($rrule['bymonth']) > 0)
                        ? "none" : "table";

        $out .= "<span id='toggle_ar' style='display: $display_tar'>";
        $out .= "<a class='btn btn-primary'
                 title='" . __("Personalization") . "'
                 onclick='$(\"#advanced_repetition$rand\").toggle()'>
                 <i class='fas fa-cog'></i>
              </a>";
        $out .= "<div id='advanced_repetition$rand' style='display: $display_ar; max-width: 23'>";

        $out .= "<div class='field'>";
        $out .= "<label for='dropdown_interval$rand'>" . __("Interval") . "</label>";
        $out .= "<div>" . Dropdown::showNumber('rrule[interval]', [
            'value'   => $rrule['interval'],
            'min'     => 1,
            'rand'    => $rand,
            'display' => false,
        ]) . "</div>";
        $out .= "</div>";

        $out .= "<div class='field'>";
        $out .= "<label for='showdate$rand'>" . __("Until") . "</label>";
        $out .= "<div>" . Html::showDateField('rrule[until]', [
            'value'   => $rrule['until'],
            'rand'    => $rand,
            'display' => false,
        ]) . "</div>";
        $out .= "</div>";

        $out .= "<div class='field'>";
        $out .= "<label for='dropdown_byday$rand'>" . __("By day") . "</label>";
        $out .= "<div>" . Dropdown::showFromArray('rrule[byday]', [
            'MO' => __('Monday'),
            'TU' => __('Tuesday'),
            'WE' => __('Wednesday'),
            'TH' => __('Thursday'),
            'FR' => __('Friday'),
            'SA' => __('Saturday'),
            'SU' => __('Sunday'),
        ], [
            'values'              => $rrule['byday'],
            'rand'                => $rand,
            'display'             => false,
            'display_emptychoice' => true,
            'width'               => '100%',
            'multiple'            => true,
        ]) . "</div>";
        $out .= "</div>";

        $out .= "<div class='field'>";
        $out .= "<label for='dropdown_bymonth$rand'>" . __("By month") . "</label>";
        $out .= "<div>" . Dropdown::showFromArray('rrule[bymonth]', [
            1  => __('January'),
            2  => __('February'),
            3  => __('March'),
            4  => __('April'),
            5  => __('May'),
            6  => __('June'),
            7  => __('July'),
            8  => __('August'),
            9  => __('September'),
            10 => __('October'),
            11 => __('November'),
            12 => __('December'),
        ], [
            'values'              => $rrule['bymonth'],
            'rand'                => $rand,
            'display'             => false,
            'display_emptychoice' => true,
            'width'               => '100%',
            'multiple'            => true,
        ]) . "</div>";
        $out .= "</div>";

        $rand = mt_rand();
        $out .= "<div class='field'>";
        $out .= "<label for='showdate$rand'>" . __("Exceptions") . "</label>";
        $out .= "<div>" . Html::showDateField('rrule[exceptions]', [
            'value'    => implode(', ', $rrule['exceptions']),
            'rand'     => $rand,
            'display'  => false,
            'multiple' => true,
            'size'     => 30,
        ]) . "</div>";
        $out .= "</div>";

        $out .= "</div>"; // #advanced_repetition
        $out .= "</span>"; // #toggle_ar
        $out .= "</div>"; // .card
        return $out;
    }


    /**
     * Display a Planning Item
     *
     * @param array $val the item to display
     *
     * @return string
     **/
    public function getAlreadyPlannedInformation(array $val)
    {
        $itemtype = $this->getType();
        if ($item = getItemForItemtype($itemtype)) {
            $objectitemtype = (method_exists($item, 'getItilObjectItemType') ? $item->getItilObjectItemType() : $itemtype);

           //TRANS: %1$s is a type, %2$$ is a date, %3$s is a date
            $out  = sprintf(
                __('%1$s: from %2$s to %3$s:'),
                $item->getTypeName(1),
                Html::convDateTime($val["begin"]),
                Html::convDateTime($val["end"])
            );
            $out .= "<br/><a href='" . $objectitemtype::getFormURLWithID($val[getForeignKeyFieldForItemType($objectitemtype)]);
            if ($item instanceof CommonITILTask) {
                 $out .= "&amp;forcetab=" . $itemtype . "$1";
            }
            $out .= "'>";
            $out .= Html::resume_text($val["name"], 80) . '</a>';

            return $out;
        }
        return '';
    }

    /**
     * Returns RSet occurence corresponding to rrule field value.
     *
     * @param array  $rrule    RRule field value
     * @param string $dtstart  Start of first occurence
     *
     * @return \RRule\RSet
     */
    public static function getRsetFromRRuleField(array $rrule, $dtstart): RSet
    {
        $dtstart_datetime  = new DateTime($dtstart);
        $rrule['dtstart']  = $dtstart_datetime->format('Y-m-d\TH:i:s\Z');

       // create a ruleset containing dtstart, the rrule, and the exclusions
        $rset = new RSet();

       // manage date exclusions,
       // we need to set a top level property for that (not directly in rrule one)
        if (isset($rrule['exceptions'])) {
            foreach ($rrule['exceptions'] as $exception) {
                $exdate = new DateTime($exception);
                $exdate->setTime(
                    $dtstart_datetime->format('G'),
                    $dtstart_datetime->format('i'),
                    $dtstart_datetime->format('s')
                );
                $rset->addExDate($exdate->format('Y-m-d\TH:i:s\Z'));
            }

           // remove exceptions key (as libraries throw exception for unknow keys)
            unset($rrule['exceptions']);
        }

       // remove specific change from js library to match rfc
        if (isset($rrule['byweekday']) || isset($rrule['BYWEEKDAY'])) {
            $rrule['byday'] = $rrule['byweekday'] ?? $rrule['BYWEEKDAY'];
            unset($rrule['byweekday'], $rrule['BYWEEKDAY']);
        }

        $rset->addRRule(new RRule($rrule));

        return $rset;
    }


    public function rawSearchOptions()
    {
        $tab = [
            [
                'id'            => 'common',
                'name'          => static::getTypeName()
            ], [
                'id'            => '1',
                'table'         => static::getTable(),
                'field'         => 'name',
                'name'          => __('Name'),
                'datatype'      => 'itemlink',
                'massiveaction' => false,
            ], [
                'id'            => '2',
                'table'         => static::getTable(),
                'field'         => 'id',
                'name'          => __('ID'),
                'massiveaction' => false,
                'datatype'      => 'number'
            ], [
                'id'            => '80',
                'table'         => 'glpi_entities',
                'field'         => 'completename',
                'name'          => Entity::getTypeName(1),
                'datatype'      => 'dropdown'
            ], [
                'id'            => '3',
                'table'         => static::getTable(),
                'field'         => 'state',
                'name'          => __('Status'),
                'datatype'      => 'specific',
                'massiveaction' => false,
                'searchtype'    => ['equals', 'notequals']
            ], [
                'id'            => '4',
                'table'         => $this->getTable(),
                'field'         => 'text',
                'name'          => __('Description'),
                'massiveaction' => false,
                'datatype'      => 'text',
                'htmltext'      => true
            ], [
                'id'            => '5',
                'table'         => PlanningEventCategory::getTable(),
                'field'         => 'name',
                'name'          => PlanningEventCategory::getTypeName(),
                'forcegroupby'  => true,
                'datatype'      => 'dropdown'
            ], [
                'id'            => '6',
                'table'         => static::getTable(),
                'field'         => 'background',
                'name'          => __('Background event'),
                'datatype'      => 'bool'
            ], [
                'id'            => '10',
                'table'         => static::getTable(),
                'field'         => 'rrule',
                'name'          => __('Repeat'),
                'datatype'      => 'text'
            ], [
                'id'            => '19',
                'table'         => static::getTable(),
                'field'         => 'date_mod',
                'name'          => __('Last update'),
                'datatype'      => 'datetime',
                'massiveaction' => false
            ], [
                'id'            => '121',
                'table'         => static::getTable(),
                'field'         => 'date_creation',
                'name'          => __('Creation date'),
                'datatype'      => 'datetime',
                'massiveaction' => false
            ]
        ];

        if (!count($this->fields)) {
            $this->getEmpty();
        }

        if (isset($this->fields['is_recursive'])) {
            $tab[] = [
                'id'            => 86,
                'table'         => static::getTable(),
                'field'         => 'is_recursive',
                'name'          => __('Child entities'),
                'datatype'      => 'bool'
            ];
        }

        if (isset($this->fields['users_id'])) {
            $tab[] = [
                'id'            => '70',
                'table'         => User::getTable(),
                'field'         => 'name',
                'name'          => User::getTypeName(1),
                'datatype'      => 'dropdown',
                'right'         => 'all'
            ];
        }

        if (isset($this->fields['users_id_guests'])) {
            $tab[] = [
                'id'            => '12',
                'table'         => static::getTable(),
                'field'         => 'users_id_guests',
                'name'          => __('Guests'),
                'datatype'      => 'text',
            ];
        }

        if (isset($this->fields['begin'])) {
            $tab[] = [
                'id'            => '8',
                'table'         => static::getTable(),
                'field'         => 'begin',
                'name'          => __('Planning start date'),
                'datatype'      => 'datetime'
            ];
        }

        if (isset($this->fields['end'])) {
            $tab[] = [
                'id'            => '9',
                'table'         => static::getTable(),
                'field'         => 'end',
                'name'          => __('Planning end date'),
                'datatype'      => 'datetime'
            ];
        }

        if (isset($this->fields['comment'])) {
            $tab[] = [
                'id'            => '11',
                'table'         => $this->getTable(),
                'field'         => 'comment',
                'name'          => _n('Comment', 'Comments', 1),
                'massiveaction' => false,
                'datatype'      => 'text',
            ];
        }

        return $tab;
    }
}
