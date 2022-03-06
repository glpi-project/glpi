<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Traits\VobjectConverterTrait;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTodo;

class PlanningExternalEvent extends CommonDBTM implements CalDAVCompatibleItemInterface {
   use Glpi\Features\PlanningEvent  {
      rawSearchOptions as protected trait_rawSearchOptions;
   }
   use VobjectConverterTrait;

   public $dohistory = true;
   static $rightname = 'externalevent';

   const MANAGE_BG_EVENTS =   1024;

   static function getTypeName($nb = 0) {
      return _n('External event', 'External events', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   static function canUpdate() {
      // we permits globally to update this object,
      // as users can update their onw items
      return Session::haveRightsOr(self::$rightname, [
         CREATE,
         UPDATE,
         self::MANAGE_BG_EVENTS
      ]);
   }

   function canUpdateItem() {
      if (!$this->canUpdateBGEvents()) {
         return false;
      }

      // the current user can update only this own events without UPDATE right
      // but not bg one, see above
      if ($this->fields['users_id'] != Session::getLoginUserID()
          && !Session::haveRight(self::$rightname, UPDATE)) {
         return false;
      }

      return parent::canUpdateItem();
   }


   function canPurgeItem() {
      if (!$this->canUpdateBGEvents()) {
         return false;
      }

      // the current user can update only this own events without PURGE right
      // but not bg one, see above
      if ($this->fields['users_id'] != Session::getLoginUserID()
          && !Session::haveRight(self::$rightname, PURGE)) {
         return false;
      }

      return parent::canPurgeItem();
   }

   /**
    * do we have the right to manage background events
    *
    * @return bool
    */
   function canUpdateBGEvents() {
      if ($this->fields["background"]
          && !Session::haveRight(self::$rightname, self::MANAGE_BG_EVENTS)) {
         return false;
      }

      return true;
   }


   function post_getFromDB() {
      $this->fields['users_id_guests'] = importArrayFromDB($this->fields['users_id_guests']);
   }


   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $canedit    = $this->can($ID, UPDATE);
      $rand       = mt_rand();
      $rand_plan  = mt_rand();
      $rand_rrule = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $is_ajax  = isset($options['from_planning_edit_ajax']) && $options['from_planning_edit_ajax'];
      $is_rrule = strlen($this->fields['rrule']) > 0;

      // set event for another user
      if (isset($options['res_itemtype'])
          && isset($options['res_items_id'])
          && strtolower($options['res_itemtype']) == "user") {
         $this->fields['users_id'] =  $options['res_items_id'];
      }

      if ($canedit) {
         $tpl_class = 'PlanningExternalEventTemplate';
         echo "<tr class='tab_bg_1' style='vertical-align: top'>";
         echo "<td colspan='2'>".$tpl_class::getTypeName()."</td>";
         echo "<td colspan='2'>";
         $tpl_class::dropdown([
            'value'     => $this->fields['planningexternaleventtemplates_id'],
            'entity'    => $this->getEntityID(),
            'rand'      => $rand,
            'on_change' => "template_update$rand(this.value)"
         ]);

         $ajax_url = $CFG_GLPI["root_doc"]."/ajax/planning.php";
         $JS = <<<JAVASCRIPT
            function template_update{$rand}(value) {
               $.ajax({
                  url: '{$ajax_url}',
                  type: "POST",
                  data: {
                     action: 'get_externalevent_template',
                     planningexternaleventtemplates_id: value
                  }
               }).done(function(data) {
                  // set common fields
                  if (data.name.length > 0) {
                     $("#textfield_name{$rand}").val(data.name);
                  }
                  $("#dropdown_state{$rand}").trigger("setValue", data.state);
                  if (data.planningeventcategories_id > 0) {
                     $("#dropdown_planningeventcategories_id{$rand}")
                        .trigger("setValue", data.planningeventcategories_id);
                  }
                  $("#dropdown_background{$rand}").trigger("setValue", data.background);
                  if (data.text.length > 0) {
                     if (contenttinymce = tinymce.get("text{$rand}")) {
                        contenttinymce.setContent(data.text);
                     }
                  }

                  // set planification fields
                  if (data.duration > 0) {
                     $("#dropdown_plan__duration_{$rand_plan}").trigger("setValue", data.duration);
                  }
                  $("#dropdown__planningrecall_before_time_{$rand_plan}")
                     .trigger("setValue", data.before_time);

                  // set rrule fields
                  if (data.rrule != null
                      && data.rrule.freq != null ) {
                     $("#dropdown_rrule_freq_{$rand_rrule}").trigger("setValue", data.rrule.freq);
                     $("#dropdown_rrule_interval_{$rand_rrule}").trigger("setValue", data.rrule.interval);
                     $("#showdate{$rand_rrule}").val(data.rrule.until);
                     $("#dropdown_rrule_byday_{$rand_rrule}").val(data.rrule.byday).trigger('change');
                     $("#dropdown_rrule_bymonth_{$rand_rrule}").val(data.rrule.bymonth).trigger('change');
                  }
               });
            }
JAVASCRIPT;
         echo Html::scriptBlock($JS);
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='2'>".__('Title')."</td>";
      echo "<td colspan='2'>";
      if (isset($options['start'])) {
         echo Html::hidden('day', ['value' => $options['start']]);
      }
      if ($canedit) {
         Html::autocompletionTextField($this, "name", [
            'size'   => '80',
            'entity' => -1,
            'user'   => $this->fields["users_id"],
            'rand'   => $rand
         ]);
      } else {
         echo $this->fields['name'];
      }
      if (isset($options['from_planning_edit_ajax']) && $options['from_planning_edit_ajax']) {
         echo Html::hidden('from_planning_edit_ajax');
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td colspan='2'>".User::getTypeName(1)."</td>";
      echo "<td colspan='2'>";
      User::dropdown([
         'name'          => 'users_id',
         'right'         => 'all',
         'value'         => $this->fields['users_id']
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td colspan='2'>".__('Guests')."</td>";
      echo "<td colspan='2'>";
      User::dropdown([
         'name'          => 'users_id_guests[]',
         'right'         => 'all',
         'values'        => $this->fields['users_id_guests'],
         'specific_tags' => [
            'multiple' => true
         ],
      ]);
      echo "<div style='font-style: italic'>".
            __("Each guest will have a read-only copy of this event").
            "</div>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Status')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         Planning::dropdownState("state", $this->fields["state"], true, [
            'rand' => $rand,
         ]);
      } else {
         echo Planning::getState($this->fields["state"]);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Category')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         PlanningEventCategory::dropdown([
            'value' => $this->fields['planningeventcategories_id'],
            'rand'  => $rand
         ]);
      } else {
         echo Dropdown::getDropdownName(
            PlanningEventCategory::getTable(),
            $this->fields['planningeventcategories_id']
         );
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'>".__('Background event')."</td>";
      echo "<td colspan='2'>";
      if ($canedit) {
         Dropdown::showYesNo('background', $this->fields['background'], -1, [
            'rand' => $rand,
         ]);
      } else {
         echo Dropdown::getYesNo($this->fields['background']);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td  colspan='2'>"._n('Calendar', 'Calendars', 1)."</td>";
      echo "<td>";
      Planning::showAddEventClassicForm([
         'items_id'  => $this->fields['id'],
         'itemtype'  => $this->getType(),
         'begin'     => $this->fields['begin'],
         'end'       => $this->fields['end'],
         'rand_user' => $this->fields['users_id'],
         'rand'      => $rand_plan,
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td  colspan='2'>".__('Repeat')."</td>";
      echo "<td>";
      echo self::showRepetitionForm($this->fields['rrule'] ?? '', [
         'rand' => $rand_rrule
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Description')."</td>".
           "<td colspan='3'>";

      if ($canedit) {
         Html::textarea([
            'name'              => 'text',
            'value'             => $this->fields["text"],
            'enable_richtext'   => true,
            'enable_fileupload' => true,
            'rand'              => $rand,
            'editor_id'         => 'text'.$rand,
         ]);
      } else {
         echo "<div>";
         echo Toolbox::unclean_html_cross_side_scripting_deep($this->fields["text"]);
         echo "</div>";
      }

      echo "</td></tr>";

      if ($is_ajax && $is_rrule) {
         $options['candel'] = false;
         $options['addbuttons'] = [
            'purge'          => "<i class='fas fa-trash-alt'></i>&nbsp;".__("Delete serie"),
            'purge_instance' => "<i class='far fa-trash-alt'></i>&nbsp;".__("Delete instance"),
         ];
      }

      $this->showFormButtons($options);

      return true;
   }

   function getRights($interface = 'central') {
      $values = parent::getRights();

      $values[self::MANAGE_BG_EVENTS] = __('manage background events');

      return $values;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            VObject::class,
         ]
      );
   }

   public static function getGroupItemsAsVCalendars($groups_id) {

      return self::getItemsAsVCalendars([self::getTableField('groups_id') => $groups_id]);
   }

   public static function getUserItemsAsVCalendars($users_id) {

      return self::getItemsAsVCalendars([
         'OR' => [
            self::getTableField('users_id')        => $users_id,
            self::getTableField('users_id_guests') => ['LIKE', '%"'.$users_id.'"%'],
         ]
      ]);
   }

   /**
    * Returns items as VCalendar objects.
    *
    * @param array $criteria
    *
    * @return \Sabre\VObject\Component\VCalendar[]
    */
   private static function getItemsAsVCalendars(array $criteria) {

      global $DB;

      $query = [
         'FROM'  => self::getTable(),
         'WHERE' => $criteria,
      ];

      $event_iterator = $DB->request($query);

      $vcalendars = [];
      foreach ($event_iterator as $event) {
         $item = new self();
         $item->getFromResultSet($event);
         $vcalendar = $item->getAsVCalendar();
         if (null !== $vcalendar) {
            $vcalendars[] = $vcalendar;
         }
      }

      return $vcalendars;
   }

   public function getAsVCalendar() {

      if (!$this->canViewItem()) {
         return null;
      }

      // Transform HTML text to plain text
      $this->fields['text'] = Html::clean(
         Toolbox::unclean_cross_side_scripting_deep(
            $this->fields['text']
         )
      );

      $is_task = in_array($this->fields['state'], [Planning::DONE, Planning::TODO]);
      $is_planned = !empty($this->fields['begin']) && !empty($this->fields['end']);
      $target_component = $this->getTargetCaldavComponent($is_planned, $is_task);
      if (null === $target_component) {
         return null;
      }

      $vcalendar = $this->getVCalendarForItem($this, $target_component);

      return $vcalendar;
   }

   public function getInputFromVCalendar(VCalendar $vcalendar) {

      $vcomp = $vcalendar->getBaseComponent();

      $input = $this->getCommonInputFromVcomponent($vcomp, $this->isNewItem());

      $input['text'] = $input['content'];
      unset($input['content']);

      if ($vcomp instanceof VTodo && !array_key_exists('state', $input)) {
         // Force default state to TO DO or event will be considered as VEVENT
         $input['state'] = \Planning::TODO;
      }

      return $input;
   }


   function rawSearchOptions() {
      return $this->trait_rawSearchOptions();
   }
}
