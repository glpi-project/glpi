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


class QueuedWebhook extends CommonDBTM
{
    public static $rightname = 'config';


    public static function getTypeName($nb = 0)
    {
        return __('Webhook queue');
    }


    public static function canCreate()
    {
       // Everybody can create : human and cron
        return Session::getLoginUserID(false);
    }


    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @see CommonDBTM::getSpecificMassiveActions()
     **/
    public function getSpecificMassiveActions($checkitem = null, $is_deleted = false)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin && !$is_deleted) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'send'] = _x('button', 'Send');
        }

        return $actions;
    }

        /**
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'send':
                foreach ($ids as $id) {
                    if ($item->canEdit($id)) {
                        if ($item->fields['mode'] === Notification_NotificationTemplate::MODE_AJAX) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::NO_ACTION);
                        } elseif ($item->sendById($id)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function prepareInputForAdd($input)
    {
        global $DB;

        if (!isset($input['create_time']) || empty($input['create_time'])) {
            $input['create_time'] = $_SESSION["glpi_currenttime"];
        }
        if (!isset($input['send_time']) || empty($input['send_time'])) {
            $toadd = 0;
            if (isset($input['entities_id'])) {
                $toadd = Entity::getUsedConfig('delay_send_emails', $input['entities_id']);
            }
            if ($toadd > 0) {
                $input['send_time'] = date(
                    "Y-m-d H:i:s",
                    strtotime($_SESSION["glpi_currenttime"])
                    + $toadd * MINUTE_TIMESTAMP
                );
            } else {
                $input['send_time'] = $_SESSION["glpi_currenttime"];
            }
        }
        $input['sent_try'] = 0;

        return $input;
    }

        /**
     * Send notification in queue
     *
     * @param integer $ID Id
     *
     * @return boolean
     */
    public function sendById($ID)
    {
        if ($this->getFromDB($ID)) {
            return Webhook::send([$this->fields]);
        } else {
            return false;
        }
    }

    public static function getIcon()
    {
        return "ti ti-notification";
    }
}
