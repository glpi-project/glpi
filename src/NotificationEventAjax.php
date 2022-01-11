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

class NotificationEventAjax extends NotificationEventAbstract implements NotificationEventInterface
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
