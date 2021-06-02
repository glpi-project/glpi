<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

class PendingReason_Item extends CommonDBRelation
{
   public static $itemtype_1 = 'PendingReason';
   public static $items_id_1 = 'pendingreasons_id';
   public static $take_entity_1 = false;

   public static $itemtype_2 = 'itemtype';
   public static $items_id_2 = 'items_id';
   public static $take_entity_2 = true;

   public static function getTypeName($nb = 0) {
      return _n('Item', 'Items', $nb);
   }

   public static function getForItem(CommonDBTM $item) {
      $em = new self();
      $find = $em->find([
         'itemtype' => $item::getType(),
         'items_id' => $item->getID(),
      ]);

      if (!count($find)) {
         return false;
      }

      $row_found = array_pop($find);
      return self::getById($row_found['id']);
   }

   /**
    * Create a pendingreason_item for a given item
    *
    * @param CommonDBTM $item
    * @param array      $fields field to insert (pendingreasons_id, followup_frequency
    *                           and followups_before_resolution)
    * @return bool true on success
    */
   public static function createForItem(CommonDBTM $item, array $fields): bool {
      $em = new self();
      $find = $em->find([
         'itemtype' => $item::getType(),
         'items_id' => $item->getID(),
      ]);

      if (count($find)) {
         // Clean existing entry
         $to_delete = array_pop($find);
         $fields['id'] = $to_delete['id'];
         $em->delete(['id' => $fields['id']]);
         unset($fields['id']);
         $em = new self();
      }

      $fields['itemtype'] = $item::getType();
      $fields['items_id'] = $item->getID();
      $fields['last_bump_date'] = $_SESSION['glpi_currenttime'];
      $success = $em->add($fields);
      if (!$success) {
         trigger_error("Failed to create PendingReason_Item", E_USER_WARNING);
      }

      return $success;
   }

   /**
    * Update a pendingreason_item for a given item
    *
    * @param CommonDBTM $item
    * @param array      $fields fields to update
    * @return bool true on success
    */
   public static function updateForItem(CommonDBTM $item, array $fields): bool {
      $em = new self();
      $find = $em->find([
         'itemtype' => $item::getType(),
         'items_id' => $item->getID(),
      ]);

      if (!count($find)) {
         trigger_error("Failed to update PendingReason_Item, no item found", E_USER_WARNING);
         return false;
      }

      $to_update = array_pop($find);
      $fields['id'] = $to_update['id'];
      $success = $em->update($fields);
      if (!$success) {
         trigger_error("Failed to update PendingReason_Item", E_USER_WARNING);
      }

      return $success;
   }

   /**
    * Delete a pendingreason_item for a given item
    *
    * @param CommonDBTM $item
    * @return bool true on success
    */
   public static function deleteForItem(CommonDBTM $item): bool {
      $em = new self();
      $find = $em->find([
         'itemtype' => $item::getType(),
         'items_id' => $item->getID(),
      ]);

      if (!count($find)) {
         // Nothing to delete
         return true;
      }

      $to_delete = array_pop($find);
      $success = $em->delete([
         'id' => $to_delete['id']
      ]);

      if (!$success) {
         trigger_error("Failed to delete PendingReason_Item", E_USER_WARNING);
      }

      return $success;
   }

   /**
    * Get auto resolve date
    *
    * @return string|bool date (Y-m-d H:i:s) or false
    */
   public function getNextFollowupDate() {
      if (empty($this->fields['followup_frequency'])) {
         return false;
      }

      if ($this->fields['followups_before_resolution'] > 0 && $this->fields['followups_before_resolution'] <= $this->fields['bump_count']) {
         return false;
      }

      $date = new DateTime($this->fields['last_bump_date']);
      $date->setTimestamp($date->getTimestamp() + $this->fields['followup_frequency']);
      return $date->format("Y-m-d H:i:s");
   }

   /**
    * Get auto resolve date
    *
    * @return string|bool date (Y-m-d H:i:s) or false
    */
   public function getAutoResolvedate() {
      if (empty($this->fields['followup_frequency']) || empty($this->fields['followups_before_resolution'])) {
         return false;
      }

      // If there was a bump, calculate from last_bump_date
      $date = new DateTime($this->fields['last_bump_date']);
      $date->setTimestamp($date->getTimestamp() + $this->fields['followup_frequency'] * ($this->fields['followups_before_resolution'] + 1 - $this->fields['bump_count']));

      return $date->format("Y-m-d H:i:s");
   }

   /**
    * Display the "pending" mini form for a given timeline item
    *
    * @param CommonDBTM $item
    * @param int $rand
    */
   public static function showFormForTimelineItem(CommonDBTM $item, int $rand): void {
      global $CFG_GLPI;

      // Only show pending form if creating a new followup or editing one with a pending reason
      $pending_item = self::getForItem($item);
      if ($item->isNewItem() || $pending_item || self::isLastTimelineItem($item)) {
         if (!$pending_item) {
            $pending_item = new self();
            $pending_item->getEmpty();
         }

         echo "<tr><td colspan='4'>";

         // Display "pending" switch
         echo "<div class='fa-label'>";
         echo "<i class='fas fa-pause fa-fw' title='".__('Pending')."'></i>";
         echo "<span class='switch pager_controls'>";
         echo "<label for='pendingswitch$rand' title='".__('Pending')."'>";
         $pending_checked = !$pending_item->isNewItem() ? "checked='checked'" : "";
         echo "<input type='checkbox' id='pendingswitch$rand' name='pending' value='1' $pending_checked>";
         echo "<span class='lever'></span>";
         echo "</label>";
         echo "</span>";
         echo "</div>";

         // Display pending reason field
         $display_pending_reason = $pending_checked ? "" : "starthidden";
         echo "<div id='pending_reason_dropdown$rand' class='$display_pending_reason fa-label'>";
         echo "<i class='fas fa-tag fa-fw mr10px' title='". PendingReason::getTypeName(1) ."'></i>";
         PendingReason::dropdown([
            'emptylabel'          => __("No pending reason"),
            'display_emptychoice' => true,
            'rand'                => $rand,
            'value'               => $pending_item->fields["pendingreasons_id"],
         ]);
         echo "</div>";

         // Display auto bump field
         $display_pending_reason_extra = $pending_item->fields["pendingreasons_id"] > 0 ? "" : "starthidden";
         echo "<div id='pending_reason_followup_frequency_dropdown$rand' class='$display_pending_reason_extra fa-label'>";
         echo "<i class='fas fa-redo fa-fw mr10px' title='".__('Automatic follow-up')."'></i>";
         echo PendingReason::displayFollowupFrequencyfield($pending_item->fields["followup_frequency"]);
         echo "</div>";

         // Display auto solve field
         echo "<div id='pending_reason_followups_before_resolution_dropdown$rand' class='$display_pending_reason_extra fa-label'>";
         echo "<i class='fas fa-check fa-fw mr10px' title='".__('Automatic resolution')."'></i>";
         echo PendingReason::displayFollowupsNumberBeforeResolutionField($pending_item->fields["followups_before_resolution"]);
         echo "</div>";

         // JS handling visiblity and values of the previous fields
         $pending_ajax_url = $CFG_GLPI["root_doc"]."/ajax/pendingreason.php";
         echo Html::scriptBlock("
            $('#pendingswitch$rand').change(function() {
               if ($('#pendingswitch$rand').prop('checked')) {
                  $('#pending_reason_dropdown$rand').show();
                  if ($('#dropdown_pendingreasons_id$rand').val() > 0) {
                     $('#pending_reason_followup_frequency_dropdown$rand').show();
                     $('#pending_reason_followups_before_resolution_dropdown$rand').show();
                  }
               } else {
                  $('#pending_reason_dropdown$rand').hide();
                  $('#pending_reason_followup_frequency_dropdown$rand').hide();
                  $('#pending_reason_followups_before_resolution_dropdown$rand').hide();
               }
            });

            var pending_reasons_cache = [];
            $('#dropdown_pendingreasons_id$rand').change(function() {
               var pending_val = $('#dropdown_pendingreasons_id$rand').val();

               if (pending_val > 0) {
                  if (pending_reasons_cache[pending_val] == undefined) {
                     $.ajax({
                        url: '{$pending_ajax_url}',
                        type: 'POST',
                        data: {
                           pendingreasons_id: pending_val
                        }
                     }).done(function(data) {
                        $('#pending_reason_followup_frequency_dropdown$rand').show();
                        $('#pending_reason_followups_before_resolution_dropdown$rand').show();
                        $('#pending_reason_followup_frequency_dropdown$rand select').val(data.followup_frequency);
                        $('#pending_reason_followups_before_resolution_dropdown$rand select').val(data.followups_before_resolution);
                        $('#pending_reason_followup_frequency_dropdown$rand select').trigger('change');
                        $('#pending_reason_followups_before_resolution_dropdown$rand select').trigger('change');

                        pending_reasons_cache[pending_val] = data;
                     });
                  } else {
                     $('#pending_reason_followup_frequency_dropdown$rand').show();
                     $('#pending_reason_followups_before_resolution_dropdown$rand').show();
                  }
               } else {
                  $('#pending_reason_followup_frequency_dropdown$rand').hide();
                  $('#pending_reason_followups_before_resolution_dropdown$rand').hide();
               }
            });
         ");

         echo "</td></tr>";
      }
   }

   /**
    * Display pending informations in the main tab of a given CommonITILObject
    *
    * @param CommonITILObject $item
    */
   public static function displayStatusTooltip(CommonITILObject $item): void {
      $pending_item = self::getForItem($item);
      if ($pending_item) {
         $pending_reason = PendingReason::getById($pending_item->fields['pendingreasons_id']);

         if (!$pending_reason) {
            return;
         }

         echo '<div class="pending_detail">' . $pending_reason->getLink();
         $tooltip = "";

         $next_bump = $pending_item->getNextFollowupDate();
         if ($next_bump) {
            $tooltip .= sprintf(__("Next automatic follow-up scheduled on %s"), Html::convDate($next_bump)) . ".<br>";
         }

         $resolve = $pending_item->getAutoResolvedate();
         if ($resolve) {
            $tooltip .= sprintf(__("Automatic resolution scheduled on %s"), Html::convDate($resolve)) . ".<br>";
         }

         if (!empty($tooltip)) {
            Html::showToolTip($tooltip);
         }

         echo "</div>";
      }
   }

   /**
    * Check that the given timeline event is the lastest "pending" action for
    * the given item
    *
    * @param CommonITILObject $item
    * @param CommonDBTM       $timeline_item
    * @return boolean
    */
   public static function isLastPendingForItem(
      CommonITILObject $item,
      CommonDBTM $timeline_item
   ): bool {
      global $DB;

      $task_class = $item::getTaskClass();

      $data = $DB->request([
         'SELECT' => ['MAX' => 'id AS max_id'],
         'FROM'  => PendingReason_Item::getTable(),
         'WHERE' => [
            'OR' => [
               [
                  'itemtype' => ITILFollowup::getType(),
                  'items_id' => new QuerySubQuery([
                     'SELECT' => 'id',
                     'FROM'   => ITILFollowup::getTable(),
                     'WHERE'  => [
                        'itemtype' => $item::getType(),
                        'items_id' => $item->getID(),
                     ]
                  ])
               ],
               [
                  'itemtype' => $task_class::getType(),
                  'items_id' => new QuerySubQuery([
                     'SELECT' => 'id',
                     'FROM'   => $task_class::getTable(),
                     'WHERE'  => [
                        $item::getForeignKeyField() => $item->getID(),
                     ]
                  ])
               ],
            ]
         ]
      ]);

      if (!count($data)) {
         return false;
      }

      $row = $data->next();
      $pending_item = self::getById($row['max_id']);

      return $pending_item->fields['items_id'] == $timeline_item->fields['id'] && $pending_item->fields['itemtype'] == $timeline_item::getType();
   }

   /**
    * Check that the given timeline_item is the last one added in it's
    * parent timeline
    *
    * @param CommonDBTM $timeline_item
    * @return boolean
    */
   public static function isLastTimelineItem(CommonDBTM $timeline_item): bool {
      global $DB;

      if ($timeline_item instanceof ITILFollowup) {
         $parent_itemtype = $timeline_item->fields['itemtype'];
         $parent_items_id = $timeline_item->fields['items_id'];
         $task_class = $parent_itemtype::getTaskClass();
      } else if ($timeline_item instanceof TicketTask) {
         $parent_itemtype = Ticket::class;
         $parent_items_id = $timeline_item->fields[Ticket::getForeignKeyField()];
         $task_class = TicketTask::class;
      } else if ($timeline_item instanceof ProblemTask) {
         $parent_itemtype = Problem::class;
         $parent_items_id = $timeline_item->fields[Problem::getForeignKeyField()];
         $task_class = ProblemTask::class;
      } else if ($timeline_item instanceof ChangeTask) {
         $parent_itemtype = Change::class;
         $parent_items_id = $timeline_item->fields[Change::getForeignKeyField()];
         $task_class = ChangeTask::class;
      } else {
         return false;
      }

      $followups_query = new QuerySubQuery([
         'SELECT'    => ['date_creation'],
         'FROM'      => ITILFollowup::getTable(),
         'WHERE'     => [
            "itemtype" => $parent_itemtype,
            "items_id" => $parent_items_id,
         ]
      ]);

      $tasks_query = new QuerySubQuery([
         'SELECT'    => ['date_creation'],
         'FROM'      => $task_class::getTable(),
         'WHERE'     => [
            $parent_itemtype::getForeignKeyField() => $parent_items_id,
         ]
      ]);

      $union = new \QueryUnion([$followups_query, $tasks_query], false, 'timelinevents');
      $data = $DB->request([
         'SELECT' => ['MAX' => 'date_creation AS max_date_creation'],
         'FROM'   => $union
      ]);

      if (!count($data)) {
         return false;
      }

      $row = $data->next();

      return $row['max_date_creation'] == $timeline_item->fields['date_creation'];
   }

   /**
    * Handle edit on a "pending action" from an item the timeline
    *
    * @param CommonDBTM $timeline_item
    * @return array
    */
   public static function handleTimelineEdits(CommonDBTM $timeline_item): array {

      if (self::getForItem($timeline_item)) {
         // Event was already marked as pending

         if ($timeline_item->input['pending'] ?? 0) {
            // Still pending, check for update
            $pending_updates = [];
            if (isset($timeline_item->input['pendingreasons_id'])) {
               $pending_updates['pendingreasons_id'] = $timeline_item->input['pendingreasons_id'];
            }
            if (isset($timeline_item->input['followup_frequency'])) {
               $pending_updates['followup_frequency'] = $timeline_item->input['followup_frequency'];
            }
            if (isset($timeline_item->input['followups_before_resolution'])) {
               $pending_updates['followups_before_resolution'] = $timeline_item->input['followups_before_resolution'];
            }

            if (count($pending_updates) > 0) {
               self::updateForItem($timeline_item, $pending_updates);

               if ($timeline_item->input['_job']->fields['status'] == CommonITILObject::WAITING
                  && self::isLastPendingForItem($timeline_item->input['_job'], $timeline_item)) {
                  // Update parent if needed
                  self::updateForItem($timeline_item->input['_job'], $pending_updates);
               }
            }
         } else if (!$timeline_item->input['pending'] ?? 1) {
            // No longer pending, remove pending data
            self::deleteForItem($timeline_item->input["_job"]);
            self::deleteForItem($timeline_item);

            // Change status of parent if needed
            if ($timeline_item->input["_job"]->fields['status'] == CommonITILObject::WAITING) {
               $timeline_item->input['_status'] = CommonITILObject::ASSIGNED;
            }
         }
      } else {
         // Not pending yet; did it change ?
         if ($timeline_item->input['pending'] ?? 0) {
            // Set parent status
            $timeline_item->input['_status'] = CommonITILObject::WAITING;

            // Create pending_item data for event and parent
            self::createForItem($timeline_item->input["_job"], [
               'pendingreasons_id' => $timeline_item->input['pendingreasons_id'],
               'followup_frequency'         => $timeline_item->input['followup_frequency'] ?? 0,
               'followups_before_resolution'        => $timeline_item->input['followups_before_resolution'] ?? 0,
            ]);
            self::createForItem($timeline_item, [
               'pendingreasons_id' => $timeline_item->input['pendingreasons_id'],
               'followup_frequency'         => $timeline_item->input['followup_frequency'] ?? 0,
               'followups_before_resolution'        => $timeline_item->input['followups_before_resolution'] ?? 0,
            ]);
         }
      }

      return $timeline_item->input;
   }

   public static function canCreate() {
      return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
   }

   public static function canView() {
      return ITILFollowup::canView() || TicketTask::canView() || ChangeTask::canView() || ProblemTask::canView();
   }

   public static function canUpdate() {
      return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
   }

   public static function canDelete() {
      return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
   }

   public static function canPurge() {
      return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
   }

   public function canCreateItem() {
      $itemtype = $this->fields['itemtype'];
      $item = $itemtype::getById($this->fields['items_id']);
      return $item->canUpdateItem();
   }

   public function canViewItem() {
      $itemtype = $this->fields['itemtype'];
      $item = $itemtype::getById($this->fields['items_id']);
      return $item->canViewItem();
   }

   public function canUpdateItem() {
      $itemtype = $this->fields['itemtype'];
      $item = $itemtype::getById($this->fields['items_id']);
      return $item->canUpdateItem();
   }

   public function canDeleteItem() {
      $itemtype = $this->fields['itemtype'];
      $item = $itemtype::getById($this->fields['items_id']);
      return $item->canUpdateItem();
   }

   public function canPurgeItem() {
      $itemtype = $this->fields['itemtype'];
      $item = $itemtype::getById($this->fields['items_id']);
      return $item->canUpdateItem();
   }

}
