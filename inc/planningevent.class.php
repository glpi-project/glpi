<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use RRule\RRule;

trait PlanningEvent {

   function post_getEmpty() {
      if (isset($this->fields["users_id"])) {
         $this->fields["users_id"] = Session::getLoginUserID();
      }

      if (isset($this->field['rrule'])) {
         $this->field['rrule'] = json_decode($this->field['rrule'], true);
      }

      parent::post_getEmpty();
   }

   function post_addItem() {
      // Add document if needed
      $this->input = $this->addFiles($this->input, [
         'force_update'  => true,
         'content_field' => 'text']
      );

      if (isset($this->fields["users_id"])
          && isset($this->fields["begin"])
          && !empty($this->fields["begin"])) {
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


   function prepareInputForAdd($input) {
      global $DB;
      if ($DB->fieldExists(static::getTable(), 'users_id') && (!isset($input['users_id']) || empty($input['users_id']))) {
         $input['users_id'] = Session::getLoginUserID();
      }

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      $input["name"] = trim($input["name"]);
      if (empty($input["name"])) {
         $input["name"] = __('Without title');
      }

      $input["begin"] = $input["end"] = "NULL";

      if (isset($input['plan'])) {
         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && ($input['plan']["begin"] < $input['plan']["end"])) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else if (isset($this->fields['begin'])
                    && isset($this->fields['end'])) {
            Session::addMessageAfterRedirect(
                     __('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
         }
      }

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];

      // encode rrule
      if (isset($input['rrule'])) {
         $input['rrule'] = $this->encodeRrule($input['rrule']);
      }

      return $input;
   }


   function prepareInputForUpdate($input) {

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

         if (!empty($input['plan']["begin"])
             && !empty($input['plan']["end"])
             && ($input['plan']["begin"] < $input['plan']["end"])) {

            $input['_plan']      = $input['plan'];
            unset($input['plan']);
            $input['is_planned'] = 1;
            $input["begin"]      = $input['_plan']["begin"];
            $input["end"]        = $input['_plan']["end"];

         } else if (isset($this->fields['begin'])
                    && isset($this->fields['end'])) {
            Session::addMessageAfterRedirect(
                     __('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
         }
      }

      $input = $this->addFiles($input, ['content_field' => 'text']);

      // encode rrule
      if (isset($input['rrule'])) {
         $input['rrule'] = $this->encodeRrule($input['rrule']);
      }

      return $input;
   }

   function encodeRrule(Array $rrule = []) {
      if ($rrule['freq'] == null) {
         return "";
      }

      if (count($rrule) > 0) {
         $rrule = json_encode($rrule);
      }

      return $rrule;
   }


   function post_updateItem($history = 1) {
      if (isset($this->fields["users_id"])
          && isset($this->fields["begin"])
          && !empty($this->fields["begin"])) {
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


   function pre_updateInDB() {
      // Set new user if initial user have been deleted
      if (isset($this->fields['users_id'])
          && $this->fields['users_id'] == 0
          && $uid = Session::getLoginUserID()) {
         $this->fields['users_id'] = $uid;
         $this->updates[]          ="users_id";
      }
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
   static function populatePlanning($options = []) {
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
      $event_obj = new static;
      $itemtype  = $event_obj->getType();
      $item_fk   = getForeignKeyFieldForItemType($itemtype);
      $table     = self::getTable();
      $has_bg    = $DB->fieldExists($table, 'background');

      if (!isset($options['begin']) || $options['begin'] == 'NULL'
         || !isset($options['end']) || $options['end'] == 'NULL') {
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
      if ($event_obj instanceof CommonDBVisible) {
         $visibility_criteria = self::getVisibilityCriteria(true);
      }
      $nreadpub  = [];
      $nreadpriv = [];

      // See public event ?
      if (!$options['genical']
         && $who === Session::getLoginUserID()
         && self::canView()
         && isset($visibility_criteria['WHERE'])) {
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
      }

      if ($whogroup > 0 && $itemtype == 'Reminder') {
         $ngrouppriv = ["glpi_groups_reminders.groups_id" => $whogroup];
         if (!empty($nreadpriv)) {
            $nreadpriv['OR'] = [$nreadpriv, $ngrouppriv];
         } else {
            $nreadpriv = $ngrouppriv;
         }
      }

      $NASSIGN = [];

      if (count($nreadpub)
         && count($nreadpriv)) {
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
      ] + $NASSIGN;

      if ($DB->fieldExists($table, 'is_planned')) {
         $WHERE["$table.is_planned"] = 1;
      }

      if ($options['check_planned']) {
         $WHERE['state'] = ['!=', Planning::INFO];
      }

      if (!$options['display_done_events']) {
         $WHERE['OR'] = [
            'state'  => Planning::TODO,
            'AND'    => [
               'state'  => Planning::INFO,
               'end'    => ['>', new \QueryExpression('NOW()')]
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
         while ($data = $iterator->next()) {
            if ($event_obj->getFromDB($data["id"]) && $event_obj->canViewItem()) {
               $key = $data["begin"]."$$".$itemtype."$$".$data["id"];

               $url = (!$options['genical'])
                  ? $event_obj->getFormURLWithID($data['id'])
                  : $CFG_GLPI["url_base"].
                    self::getFormURLWithID($data['id'], false);

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
                  'name'             => Html::clean(Html::resume_text($data["name"], $CFG_GLPI["cut"])),
                  'text'             => Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($data["text"])),
                  $CFG_GLPI["cut"]),
                  'ajaxurl'          => $CFG_GLPI["root_doc"]."/ajax/planning.php".
                                        "?action=edit_event_form".
                                        "&itemtype=$itemtype".
                                        "&id=".$data['id'].
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
                  $rrule_data = array_merge($event['rrule'], ['dtstart' => $event['begin']]);
                  $rrule      = new RRule($rrule_data);

                  // rrule object doesn't any duration property,
                  // so we remove the duration from the begin part of the range
                  // (minus 1second to avoid mathing precise end date)
                  // to check if event started before begin and could be still valid
                  $begin_datetime = new DateTime($options['begin']);
                  $begin_datetime->sub(New DateInterval("PT".($duration - 1)."S"));

                  $occurences = $rrule->getOccurrencesBetween($begin_datetime, $options['end']);

                  // add the found occurences to the final tab after replacing their dates
                  foreach ($occurences as $currentDate) {
                     $events_toadd[] = array_merge($event, [
                        'begin' => $currentDate->format('Y-m-d H:i:s'),
                        'end'   => $currentDate->add(new DateInterval("PT".$duration."S"))
                                               ->format('Y-m-d H:i:s'),
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
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {
      global $CFG_GLPI;

      $html = "";
      $rand     = mt_rand();
      $users_id = "";  // show users_id reminder
      $img      = "rdv_private.png"; // default icon for reminder
      $item_fk  = getForeignKeyFieldForItemType(static::getType());

      if ($val["users_id"] != Session::getLoginUserID()) {
         $users_id = "<br>".sprintf(__('%1$s: %2$s'), __('By'), getUserName($val["users_id"]));
         $img      = "rdv_public.png";
      }

      $html.= "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title=\"".
             self::getTypeName(1)."\">&nbsp;";
      $html.= "<a id='reminder_".$val[$item_fk].$rand."' href='".
             Reminder::getFormURLWithID($val[$item_fk])."'>";

      $html.= $users_id;
      $html.= "</a>";
      $recall = '';
      if (isset($val[$item_fk])) {
         $pr = new PlanningRecall();
         if ($pr->getFromDBForItemAndUser($val['itemtype'], $val[$item_fk],
                                          Session::getLoginUserID())) {
            $recall = "<br><span class='b'>".sprintf(__('Recall on %s'),
                                                     Html::convDateTime($pr->fields['when'])).
                      "<span>";
         }
      }

      if ($complete) {
         $html.= "<span>".Planning::getState($val["state"])."</span><br>";
         $html.= "<div class='event-description rich_text_container'>".$val["text"].$recall."</div>";
      } else {
         $html.= Html::showToolTip("<span class='b'>".Planning::getState($val["state"])."</span><br>
                                   ".$val["text"].$recall,
                                   ['applyto' => "reminder_".$val[$item_fk].$rand,
                                         'display' => false]);
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
   static function showRepetitionForm(string $rrule = "", array $options = []): string {
      $rrule = json_decode($rrule, true) ?? [];
      $defaults = [
         'freq'     => null,
         'interval' => 1,
         'until'    => null,
         'byday'    => [],
         'bymonth'  => [],
      ];
      $rrule = array_merge($defaults, $rrule);

      $default_options = [
         'rand' => mt_rand(),
      ];
      $options = array_merge($default_options, $options);
      $rand    = $options['rand'];

      $out = "<div class='card' style='padding: 5px; width: 231px;'>";
      $out.= Dropdown::showFromArray('rrule[freq]', [
         null      => __("Never"),
         'daily'   => __("Each day"),
         'weekly'  => __("Each week"),
         'monthly' => __("Each month"),
         'yearly'  => __("Each year"),
      ], [
         'value'     => $rrule['freq'],
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

      $out.= "<span id='toggle_ar' style='display: $display_tar'>";
      $out.= "<a class='vsubmit'
                 title='".__("Personalization")."'
                 onclick='$(\"#advanced_repetition$rand\").toggle()'>
                 <i class='fas fa-cog'></i>
              </a>";
      $out.= "<div id='advanced_repetition$rand' style='display: $display_ar; max-width: 23'>";

      $out.= "<div class='field'>";
      $out.= "<label for='dropdown_interval$rand'>".__("Interval")."</label>";
      $out.= "<div>".Dropdown::showNumber('rrule[interval]', [
         'value'   => $rrule['interval'],
         'rand'    => $rand,
         'display' => false,
      ])."</div>";
      $out.= "</div>";

      $out.= "<div class='field'>";
      $out.= "<label for='showdate$rand'>".__("Until")."</label>";
      $out.= "<div>".Html::showDateTimeField('rrule[until]', [
         'value'   => $rrule['until'],
         'rand'    => $rand,
         'display' => false,
      ])."</div>";
      $out.= "</div>";

      $out.= "<div class='field'>";
      $out.= "<label for='dropdown_byday$rand'>".__("By day")."</label>";
      $out.= "<div>".Dropdown::showFromArray('rrule[byday]', [
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
         'multiple'            => true,
      ])."</div>";
      $out.= "</div>";

      $out.= "<div class='field'>";
      $out.= "<label for='dropdown_bymonth$rand'>".__("By month")."</label>";
      $out.= "<div>".Dropdown::showFromArray('rrule[bymonth]', [
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
         'multiple'            => true,
      ])."</div>";
      $out.= "</div>";

      $out.= "</div>"; // #advanced_repetition
      $out.= "</span>"; // #toggle_ar
      $out.= "</div>"; // .card
      return $out;
   }


   /**
    * Display a Planning Item
    *
    * @param array $val the item to display
    *
    * @return string
   **/
   public function getAlreadyPlannedInformation(array $val) {
      $itemtype = $this->getType();
      if ($item = getItemForItemtype($itemtype)) {
         $objectitemtype = (method_exists($item, 'getItilObjectItemType') ? $item->getItilObjectItemType() : $itemtype);

         //TRANS: %1$s is a type, %2$$ is a date, %3$s is a date
         $out  = sprintf(__('%1$s: from %2$s to %3$s:'), $item->getTypeName(1),
                         Html::convDateTime($val["begin"]), Html::convDateTime($val["end"]));
         $out .= "<br/><a href='".$objectitemtype::getFormURLWithID($val[getForeignKeyFieldForItemType($objectitemtype)]);
         if ($item instanceof CommonITILTask) {
            $out .= "&amp;forcetab=".$itemtype."$1";
         }
         $out .= "'>";
         $out .= Html::resume_text($val["name"], 80).'</a>';

         return $out;
      }
   }

}
