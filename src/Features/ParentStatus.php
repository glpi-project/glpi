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

use CommonITILActor;
use CommonITILObject;
use PendingReason_Item;
use Session;

/**
 * ParentStatus
 *
 * @since 10.0.4
 */
trait ParentStatus
{
    public function updateParentStatus(CommonITILObject $parentitem, array $input): void
    {
        $needupdateparent = false;

       // Set pending reason data on parent and self
        if ($input['pending'] ?? 0) {
            PendingReason_Item::createForItem($parentitem, [
                'pendingreasons_id'           => $input['pendingreasons_id'] ?? 0,
                'followup_frequency'          => $input['followup_frequency'] ?? 0,
                'followups_before_resolution' => $input['followups_before_resolution'] ?? 0,
            ]);
            PendingReason_Item::createForItem($this, [
                'pendingreasons_id'           => $input['pendingreasons_id'] ?? 0,
                'followup_frequency'          => $input['followup_frequency'] ?? 0,
                'followups_before_resolution' => $input['followups_before_resolution'] ?? 0,
            ]);
        }

        if (
            isset($input["_close"])
            && $input["_close"]
            && ($parentitem->isSolved())
        ) {
            $update = [
                'id'        => $parentitem->fields['id'],
                'status'    => CommonITILObject::CLOSED,
                'closedate' => $_SESSION["glpi_currenttime"],
                '_accepted' => true,
            ];

           // Use update method for history
            $parentitem->update($update);
        }

       // Set parent status to pending
        if ($input['pending'] ?? 0) {
            $input['_status'] = CommonITILObject::WAITING;
        } elseif ($parentitem->fields["status"] == CommonITILObject::WAITING) {
            $input["_reopen"] = true;
        }

       //manage reopening of ITILObject
        $reopened = false;
        if (!isset($input['_status'])) {
            $input['_status'] = $parentitem->fields["status"];
        }
       // if reopen set (from followup form or mailcollector)
       // and status is reopenable and not changed in form
        $is_set_pending = $input['pending'] ?? 0;
        if (
            isset($input["_reopen"])
            && $input["_reopen"]
            && in_array($parentitem->fields["status"], $parentitem::getReopenableStatusArray())
            && $input['_status'] == $parentitem->fields["status"]
            && !$is_set_pending
        ) {
            if (
                isset($parentitem::getAllStatusArray($parentitem->getType())[CommonITILObject::ASSIGNED])
                && (
                    ($parentitem->countUsers(CommonITILActor::ASSIGN) > 0)
                    || ($parentitem->countGroups(CommonITILActor::ASSIGN) > 0)
                    || ($parentitem->countSuppliers(CommonITILActor::ASSIGN) > 0)
                )
            ) {
               //check if lifecycle allowed new status
                if (
                    Session::isCron()
                    || Session::getCurrentInterface() == "helpdesk"
                    || $parentitem::isAllowedStatus($parentitem->fields["status"], CommonITILObject::ASSIGNED)
                ) {
                    $needupdateparent = true;
                    $update['status'] = CommonITILObject::ASSIGNED;
                }
            } else {
               //check if lifecycle allowed new status
                if (
                    Session::isCron()
                    || Session::getCurrentInterface() == "helpdesk"
                    || $parentitem::isAllowedStatus($parentitem->fields["status"], CommonITILObject::INCOMING)
                ) {
                    $needupdateparent = true;
                    $update['status'] = CommonITILObject::INCOMING;
                }
            }

            if ($needupdateparent) {
                $update['id'] = $parentitem->fields['id'];

               // Use update method for history
                $parentitem->update($update);
                $reopened     = true;
            }
        }

        if (
            !empty($this->fields['begin'])
            && $parentitem->isStatusExists(CommonITILObject::PLANNED)
            && (($parentitem->fields["status"] == CommonITILObject::INCOMING)
              || ($parentitem->fields["status"] == CommonITILObject::ASSIGNED)
              || $needupdateparent)
        ) {
            $input['_status'] = CommonITILObject::PLANNED;
        }

       //change ITILObject status only if imput change
        if (
            !$reopened
            && $input['_status'] != $parentitem->fields['status']
        ) {
            $update['status'] = $input['_status'];
            $update['id']     = $parentitem->fields['id'];

           // don't notify on ITILObject - update event
            $update['_disablenotif'] = true;

           // Use update method for history
            $parentitem->update($update);
        }
    }
}
