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

class NotificationEventAjax extends NotificationEventAbstract
{
    public static function getTargetFieldName()
    {
        return 'users_id';
    }


    public static function getTargetField(&$data)
    {
        $field = self::getTargetFieldName();

        if (!isset($data[$field])) {
           //Missing users_id; set to null
            $data[$field] = null;
        }

        return $field;
    }


    public static function canCron()
    {
       //notifications are pulled from web browser, it must not be handled from cron
        return false;
    }


    public static function getAdminData()
    {
       //since admin cannot be logged in; no ajax notifications for global admin
        return false;
    }


    public static function getEntityAdminsData($entity)
    {
       //since entities admin cannot be logged in; no ajax notifications for them
        return false;
    }


    public static function send(array $data)
    {
        trigger_error(
            __METHOD__ . ' should not be called!',
            E_USER_WARNING
        );
        return false;
    }
}
