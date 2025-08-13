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

use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Safe\DateTime;

class PendingReason_Item extends CommonDBRelation
{
    public static $itemtype_1 = 'PendingReason';
    public static $items_id_1 = 'pendingreasons_id';
    public static $take_entity_1 = false;

    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
    public static $take_entity_2 = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Item', 'Items', $nb);
    }

    public static function getForItem(CommonDBTM $item, bool $get_empty = false)
    {
        $em = new self();
        $find = $em->find([
            'itemtype' => $item::getType(),
            'items_id' => $item->getID(),
        ]);

        if (!count($find)) {
            if ($get_empty) {
                $pending_item = new self();
                $pending_item->getEmpty();
            }

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
    public static function createForItem(CommonDBTM $item, array $fields): bool
    {
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
        if (!isset($fields['last_bump_date'])) {
            $fields['last_bump_date'] = $_SESSION['glpi_currenttime'];
        }
        $success = $em->add($fields);
        if (!$success) {
            trigger_error("Failed to create PendingReason_Item", E_USER_WARNING);
        } else {
            NotificationEvent::raiseEvent('pendingreason_add', $item);
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
    public static function updateForItem(CommonDBTM $item, array $fields): bool
    {
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
    public static function deleteForItem(CommonDBTM $item): bool
    {
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
            'id' => $to_delete['id'],
        ]);

        if (!$success) {
            trigger_error("Failed to delete PendingReason_Item", E_USER_WARNING);
        } else {
            NotificationEvent::raiseEvent('pendingreason_del', $item);
        }

        return $success;
    }

    /**
     * Get auto resolve date
     *
     * @return string|bool date (Y-m-d H:i:s) or false
     */
    public function getNextFollowupDate()
    {
        if (empty($this->fields['followup_frequency'])) {
            return false;
        }

        if (
            $this->fields['followups_before_resolution'] != 0
            && $this->fields['followups_before_resolution'] <= $this->fields['bump_count']
        ) {
            return false;
        }

        $calendar = Calendar::getById(
            PendingReason::getById($this->fields['pendingreasons_id'])->fields['calendars_id']
        );

        if ($calendar instanceof Calendar) {
            return $calendar->computeEndDate(
                $this->fields['last_bump_date'],
                $this->fields['followup_frequency'],
                0,
                true
            );
        }

        $lastBumpDate = new DateTime($this->fields['last_bump_date']);
        $lastBumpDate->add(DateInterval::createFromDateString(
            $this->fields['followup_frequency'] . ' seconds'
        ));

        return $lastBumpDate->format('Y-m-d H:i:s');
    }

    /**
     * Get auto resolve date
     *
     * @return string|bool date (Y-m-d H:i:s) or false
     */
    public function getAutoResolvedate()
    {
        if (empty($this->fields['followup_frequency']) || empty($this->fields['followups_before_resolution'])) {
            return false;
        }

        // -1 = auto resolution without bumps
        $expected_bumps = max($this->fields['followups_before_resolution'], 0);
        $remaining_bumps = $expected_bumps - $this->fields['bump_count'] + 1;

        $calendar = Calendar::getById(
            PendingReason::getById($this->fields['pendingreasons_id'])->fields['calendars_id']
        );

        if ($calendar instanceof Calendar) {
            return $calendar->computeEndDate(
                $this->fields['last_bump_date'],
                $this->fields['followup_frequency'] * $remaining_bumps,
                0,
                true
            );
        }

        $lastBumpDate = new DateTime($this->fields['last_bump_date']);
        $lastBumpDate->add(DateInterval::createFromDateString(
            $this->fields['followup_frequency'] * $remaining_bumps . ' seconds'
        ));

        return $lastBumpDate->format('Y-m-d H:i:s');
    }

    /**
     * Get the lastest "pending" action for the given item
     *
     * @param CommonITILObject $item
     * @return CommonDBTM|false
     */
    public static function getLastPendingTimelineItemDataForItem(CommonITILObject $item)
    {
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
                            ],
                        ]),
                    ],
                    [
                        'itemtype' => $task_class::getType(),
                        'items_id' => new QuerySubQuery([
                            'SELECT' => 'id',
                            'FROM'   => $task_class::getTable(),
                            'WHERE'  => [
                                $item::getForeignKeyField() => $item->getID(),
                            ],
                        ]),
                    ],
                ],
            ],
        ]);

        if (!count($data)) {
            return false;
        }

        $row = $data->current();
        $pending_item = self::getById($row['max_id']);

        return $pending_item;
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
        $pending_item = self::getLastPendingTimelineItemDataForItem($item);

        if (!$pending_item) {
            return false;
        }

        return
            $pending_item->fields['items_id'] == $timeline_item->fields['id']
            && $pending_item->fields['itemtype'] == $timeline_item::getType()
        ;
    }

    /**
     * Check that the given timeline_item is the last one added in it's
     * parent timeline
     *
     * @param CommonDBTM $timeline_item
     * @return boolean
     */
    public static function isLastTimelineItem(CommonDBTM $timeline_item): bool
    {
        global $DB;

        if ($timeline_item instanceof ITILFollowup) {
            $parent_itemtype = $timeline_item->fields['itemtype'];
            $parent_items_id = $timeline_item->fields['items_id'];
            $task_class = $parent_itemtype::getTaskClass();
        } elseif ($timeline_item instanceof TicketTask) {
            $parent_itemtype = Ticket::class;
            $parent_items_id = $timeline_item->fields[Ticket::getForeignKeyField()];
            $task_class = TicketTask::class;
        } elseif ($timeline_item instanceof ProblemTask) {
            $parent_itemtype = Problem::class;
            $parent_items_id = $timeline_item->fields[Problem::getForeignKeyField()];
            $task_class = ProblemTask::class;
        } elseif ($timeline_item instanceof ChangeTask) {
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
            ],
        ]);

        $tasks_query = new QuerySubQuery([
            'SELECT'    => ['date_creation'],
            'FROM'      => $task_class::getTable(),
            'WHERE'     => [
                $parent_itemtype::getForeignKeyField() => $parent_items_id,
            ],
        ]);

        $union = new QueryUnion([$followups_query, $tasks_query], false, 'timelinevents');
        $data = $DB->request([
            'SELECT' => ['MAX' => 'date_creation AS max_date_creation'],
            'FROM'   => $union,
        ]);

        if (!count($data)) {
            return false;
        }

        $row = $data->current();

        return $row['max_date_creation'] == $timeline_item->fields['date_creation'];
    }

    /**
     * Determines if a pending reason can be displayed for a given item.
     *
     * @param CommonDBTM $item
     * @return boolean
     */
    public static function canDisplayPendingReasonForItem(CommonDBTM $item): bool
    {
        if ($item->isNewItem()) {
            return true;
        }

        if (PendingReason_Item::isLastTimelineItem($item)) {
            return true;
        }

        return false;
    }

    /**
     * User might be trying to update the active pending reason by modifying the
     * pending reason data in a new timeline item form
     *
     * This method update the latest pending timeline item if the user has edited
     * the pending details while adding a new task or followup
     *
     * @param CommonDBTM $new_timeline_item
     * @return void
     */
    public static function handlePendingReasonUpdateFromNewTimelineItem(
        CommonDBTM $new_timeline_item
    ): void {
        $last_pending = self::getLastPendingTimelineItemDataForItem($new_timeline_item->input['_job']);
        $ticket_pending_updates = [];

        // There is no existing pending data on previous timeline items for this ticket
        // Nothing to be done here since the goal of this method is to update active pending data
        if (!$last_pending) {
            return;
        }

        if (isset($new_timeline_item->input['last_bump_date'])) {
            $ticket_pending_updates['last_bump_date'] = $new_timeline_item->input['last_bump_date'];
        }

        // The new timeline item is the latest pending reason
        // This mean there was no active pending reason before this timeline item was added
        // Nothing to be done here as we don't have any older active pending reason to update
        if (
            $last_pending->fields['itemtype'] == $new_timeline_item::getType()
            && $last_pending->fields['items_id'] == $new_timeline_item->getID()
        ) {
            self::updateForItem($new_timeline_item->input['_job'], $ticket_pending_updates);
            return;
        }

        // Pending reason was removed or is not enabled
        // Nothing to update here as it was already handled in CommonITILObject::prepareInputForUpdate
        if (!($new_timeline_item->input['pending'] ?? 0)) {
            return;
        }

        // If we reach this point, this mean a timeline item with pending information
        // was added on a CommonITILObject which already had pending data
        // This mean the user might be trying to update the existing pending reason data

        // Let's check if there is any real updates before going any further
        $pending_updates_timeline_item = [];

        $fields_to_check_for_updates = ['pendingreasons_id', 'followup_frequency', 'followups_before_resolution'];
        foreach ($fields_to_check_for_updates as $field) {
            if (
                isset($new_timeline_item->input[$field])
                && $new_timeline_item->input[$field] != $last_pending->fields[$field]
            ) {
                $pending_updates_timeline_item[$field] = $new_timeline_item->input[$field];
            }
        }

        // No actual updates -> nothing to be done
        if (count($pending_updates_timeline_item) == 0) {
            return;
        }

        $ticket_pending_updates += $pending_updates_timeline_item;
        $pending_updates_timeline_item = $ticket_pending_updates;

        $pending_updates_timeline_item['items_id'] = $new_timeline_item->getID();
        $pending_updates_timeline_item['itemtype'] = $new_timeline_item::getType();

        // Update last pending item and parent
        if ($ticket_pending_updates['pendingreasons_id'] > 0 || $new_timeline_item::getType() !== $last_pending::getType()) {
            self::createForItem($new_timeline_item, $pending_updates_timeline_item);
        }
        self::updateForItem($new_timeline_item->input['_job'], $ticket_pending_updates);
    }

    /**
     * Handle edit on a "pending action" from an item the timeline
     *
     * @param CommonDBTM $timeline_item
     * @return array
     */
    public static function handleTimelineEdits(CommonDBTM $timeline_item): array
    {
        if (self::getForItem($timeline_item)) {
            // Event was already marked as pending

            $is_pending = $timeline_item->input['pending'] ?? 0;
            if ($is_pending) {
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

                    if (
                        $timeline_item->input['_job']->fields['status'] == CommonITILObject::WAITING
                        && self::isLastPendingForItem($timeline_item->input['_job'], $timeline_item)
                    ) {
                        // Update parent if needed
                        self::updateForItem($timeline_item->input['_job'], $pending_updates);
                    }
                }
            } elseif (!$is_pending) {
                // Change status of parent if needed
                if ($timeline_item->input["_job"]->fields['status'] == CommonITILObject::WAITING) {
                    // get previous stored status for parent
                    if ($parent_pending = self::getForItem($timeline_item->input["_job"])) {
                        $timeline_item->input['_status'] = $parent_pending->fields['previous_status'] ?? CommonITILObject::ASSIGNED;
                    }
                }

                // No longer pending, remove pending data
                self::deleteForItem($timeline_item->input["_job"]);
                self::deleteForItem($timeline_item);
            }
        } else {
            // Not pending yet; did it change ?
            if (
                ($timeline_item->input['pending'] ?? 0)
                && isset($timeline_item->input['pendingreasons_id'])
                && $timeline_item->input['pendingreasons_id'] > 0
            ) {
                // Set parent status
                $timeline_item->input['_status'] = CommonITILObject::WAITING;

                // Create pending_item data for event and parent
                self::createForItem($timeline_item->input["_job"], [
                    'pendingreasons_id' => $timeline_item->input['pendingreasons_id'],
                    'followup_frequency'         => $timeline_item->input['followup_frequency'] ?? 0,
                    'followups_before_resolution'        => $timeline_item->input['followups_before_resolution'] ?? 0,
                    'previous_status'              => $timeline_item->input["_job"]->fields['status'],
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

    public static function canCreate(): bool
    {
        return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
    }

    public static function canView(): bool
    {
        return ITILFollowup::canView() || TicketTask::canView() || ChangeTask::canView() || ProblemTask::canView();
    }

    public static function canUpdate(): bool
    {
        return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
    }

    public static function canDelete(): bool
    {
        return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
    }

    public static function canPurge(): bool
    {
        return ITILFollowup::canUpdate() || TicketTask::canUpdate() || ChangeTask::canUpdate() || ProblemTask::canUpdate();
    }

    public function canCreateItem(): bool
    {
        $itemtype = $this->fields['itemtype'];
        $item = $itemtype::getById($this->fields['items_id']);
        return $item->canUpdateItem();
    }

    public function canViewItem(): bool
    {
        $itemtype = $this->fields['itemtype'];
        $item = $itemtype::getById($this->fields['items_id']);
        return $item->canViewItem();
    }

    public function canUpdateItem(): bool
    {
        $itemtype = $this->fields['itemtype'];
        $item = $itemtype::getById($this->fields['items_id']);
        return $item->canUpdateItem();
    }

    public function canDeleteItem(): bool
    {
        $itemtype = $this->fields['itemtype'];
        $item = $itemtype::getById($this->fields['items_id']);
        return $item->canUpdateItem();
    }

    public function canPurgeItem(): bool
    {
        $itemtype = $this->fields['itemtype'];
        $item = $itemtype::getById($this->fields['items_id']);
        return $item->canUpdateItem();
    }
}
